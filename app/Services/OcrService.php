<?php

namespace App\Services;

use App\Exceptions\OcrException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Thin wrapper around the local Tesseract OCR executable.
 *
 * The binary path, language, page-segmentation mode and tessdata directory
 * come from config('services.ocr') so the application can locate Tesseract
 * on any machine without hard-coding it into the application code.
 *
 * Language data (eng.traineddata) is resolved in this order:
 *   1. services.ocr.tessdata_path (TESSDATA_PREFIX env value)
 *   2. storage/app/tessdata inside this project
 *   3. the tessdata folder next to the configured Tesseract executable
 * and is exported to the child process as TESSDATA_PREFIX, which is the
 * variable Tesseract itself reads. Without language data Tesseract starts
 * fine but fails every extraction with "Failed loading language", so
 * isAvailable() also verifies that at least one language actually loads.
 *
 * This service is optional infrastructure: the structured CSV / Excel
 * member importer never requires it. Callers must check isAvailable() and
 * treat extractText() failures as a friendly upload error, never a fatal.
 */
class OcrService
{
    private ?bool $available = null;

    private bool $languagesLoaded = false;

    /** @var list<string>|null */
    private ?array $languages = null;

    /**
     * Absolute path to tesseract, or 'tesseract' to resolve from the PATH.
     */
    public function binaryPath(): string
    {
        $configured = trim((string) config('services.ocr.tesseract_path', ''));

        return $configured !== '' ? $configured : 'tesseract';
    }

    public function language(): string
    {
        return config('services.ocr.tesseract_lang', 'eng') ?: 'eng';
    }

    /**
     * Absolute path to a directory containing *.traineddata files, or null
     * when none can be found (Tesseract then uses its built-in default).
     */
    public function tessdataPath(): ?string
    {
        $candidates = [];

        $configured = trim((string) config('services.ocr.tessdata_path', ''));
        if ($configured !== '') {
            $candidates[] = $configured;
        }

        $candidates[] = storage_path('app/tessdata');

        $binary = $this->binaryPath();
        if (str_contains($binary, '/') || str_contains($binary, '\\')) {
            $candidates[] = dirname($binary).DIRECTORY_SEPARATOR.'tessdata';
        }

        foreach ($candidates as $directory) {
            if ($this->hasTrainedData($directory)) {
                return $directory;
            }
        }

        return null;
    }

    /**
     * Report whether the configured Tesseract executable responds *and* its
     * language data loads, i.e. whether extraction can actually succeed.
     * Memoized for the current request.
     */
    public function isAvailable(): bool
    {
        if ($this->available !== null) {
            return $this->available;
        }

        $version = $this->run([$this->binaryPath(), '--version'], 15);

        if ($version === null || ! $version->isSuccessful()) {
            return $this->available = false;
        }

        // The binary answering --version is not enough: without a
        // *.traineddata file every extraction dies with "Failed loading
        // language". Treat that the same as a missing executable so callers
        // can surface one clear "OCR not available" message instead of a
        // confusing extraction error.
        $languages = $this->availableLanguages();

        return $this->available = $languages !== null && $languages !== [];
    }

    /**
     * Languages the installed Tesseract can actually load, or null when the
     * listing could not be produced at all.
     *
     * @return list<string>|null
     */
    public function availableLanguages(): ?array
    {
        if ($this->languagesLoaded) {
            return $this->languages;
        }

        $this->languagesLoaded = true;

        $process = $this->run([$this->binaryPath(), '--list-langs'], 15);

        if ($process === null || ! $process->isSuccessful()) {
            return $this->languages = null;
        }

        $languages = [];
        foreach (preg_split('/\r?\n/', (string) $process->getOutput()) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, 'List of available languages')) {
                continue;
            }

            $languages[] = $line;
        }

        return $this->languages = $languages;
    }

    /**
     * Run Tesseract over an image file and return the raw detected text.
     *
     * Several page-segmentation strategies are tried in turn: the first pass
     * uses Tesseract's automatic layout detection (or the configured PSM),
     * the retry assumes one uniform block of text, which suits photos of
     * typed member lists. Every pass also enables preserve_interword_spaces
     * so OCR does not collapse single-letter word gaps in names.
     *
     * An empty result after every successful pass returns an empty string
     * (nothing readable in the image); only a hard engine failure throws.
     *
     * @throws OcrException when the executable is missing, crashes, or the
     *                      image cannot be processed.
     */
    public function extractText(string $imagePath, ?string $language = null): string
    {
        if (! is_file($imagePath) || ! is_readable($imagePath)) {
            throw new OcrException('The uploaded image file cannot be read.');
        }

        if (! $this->isAvailable()) {
            throw new OcrException('The Tesseract OCR executable or its language data is not available on this server.');
        }

        $lang = $this->resolveLanguage($language ?: $this->language());

        $attempts = [];

        $firstPass = [$this->binaryPath(), $imagePath, 'stdout', '-l', $lang, '-c', 'preserve_interword_spaces=1'];
        $configuredPsm = trim((string) config('services.ocr.tesseract_psm', ''));
        if ($configuredPsm !== '') {
            $firstPass[] = '--psm';
            $firstPass[] = $configuredPsm;
        }
        $attempts[] = $firstPass;

        // Retry for photos of a single typed block (the common member-list
        // scan) when automatic layout detection returns nothing usable.
        $attempts[] = [$this->binaryPath(), $imagePath, 'stdout', '-l', $lang, '--psm', '6', '-c', 'preserve_interword_spaces=1'];

        $sawSuccess = false;
        $lastError = '';

        foreach ($attempts as $attempt) {
            $process = $this->run($attempt, 120);

            if ($process === null) {
                $lastError = 'The OCR engine could not be started.';

                continue;
            }

            if (! $process->isSuccessful()) {
                $lastError = trim((string) $process->getErrorOutput())
                    ?: 'Tesseract exited with code '.$process->getExitCode().'.';

                Log::warning('Tesseract OCR pass failed', [
                    'path' => $imagePath,
                    'command' => implode(' ', $attempt),
                    'exit_code' => $process->getExitCode(),
                    'stderr' => $lastError,
                ]);

                continue;
            }

            $sawSuccess = true;

            $text = trim((string) $process->getOutput());
            if ($text !== '') {
                return $text;
            }
        }

        if ($sawSuccess) {
            return '';
        }

        Log::error('Tesseract OCR failed', ['path' => $imagePath, 'error' => $lastError]);

        throw new OcrException('Tesseract could not extract text from this image: '.mb_substr($lastError, 0, 300));
    }

    /**
     * Fall back to an installed language when the configured one is missing,
     * so a wrong TESSERACT_LANG does not break scanning entirely.
     */
    private function resolveLanguage(string $language): string
    {
        $languages = $this->availableLanguages();

        if ($languages === null || $languages === [] || in_array($language, $languages, true)) {
            return $language;
        }

        $fallback = in_array('eng', $languages, true) ? 'eng' : $languages[0];

        Log::warning('Configured OCR language is not installed; using a fallback instead.', [
            'requested' => $language,
            'fallback' => $fallback,
            'available' => $languages,
        ]);

        return $fallback;
    }

    private function hasTrainedData(string $directory): bool
    {
        return is_dir($directory)
            && glob(rtrim($directory, '/\\').'/*.traineddata') !== [];
    }

    private function run(array $command, int $timeout): ?Process
    {
        // TESSDATA_PREFIX tells the child Tesseract where its language data
        // lives; without it the engine only finds the (often incomplete)
        // directory shipped next to the executable.
        $env = null;
        $tessdata = $this->tessdataPath();
        if ($tessdata !== null) {
            $env = ['TESSDATA_PREFIX' => $tessdata];
        }

        $process = new Process($command, null, $env, null, $timeout);

        try {
            $process->run();
        } catch (Throwable $e) {
            Log::error('OCR process errored', ['command' => implode(' ', $command), 'error' => $e->getMessage()]);

            return null;
        }

        return $process;
    }
}
