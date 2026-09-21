<?php

namespace App\Services;

use App\Exceptions\OcrException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Thin wrapper around the local Tesseract OCR executable.
 *
 * The binary path and language come from config('services.ocr') so the
 * application can locate Tesseract on any machine without hard-coding it
 * into the application code.
 *
 * This service is optional infrastructure: the structured CSV / Excel / JSON
 * member importer never requires it. Callers must check isAvailable() and
 * treat extractText() failures as a friendly upload error, never a fatal.
 */
class OcrService
{
    private ?bool $available = null;

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
     * Report whether the configured Tesseract executable responds.
     * Memoized for the current request.
     */
    public function isAvailable(): bool
    {
        if ($this->available !== null) {
            return $this->available;
        }

        $process = $this->run([$this->binaryPath(), '--version'], 15);

        return $this->available = $process !== null && $process->isSuccessful();
    }

    /**
     * Run Tesseract over an image file and return the raw detected text.
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
            throw new OcrException('The Tesseract OCR executable is not available on this server.');
        }

        $lang = $language ?: $this->language();

        // "stdout" makes Tesseract write the recognized text to STDOUT.
        $process = $this->run([$this->binaryPath(), $imagePath, 'stdout', '-l', $lang], 120);

        if ($process === null) {
            throw new OcrException('The OCR engine could not be started.');
        }

        if (! $process->isSuccessful()) {
            Log::error('Tesseract OCR failed', [
                'path' => $imagePath,
                'exit_code' => $process->getExitCode(),
                'stderr' => trim((string) $process->getErrorOutput()),
            ]);

            throw new OcrException('Tesseract could not extract text from this image.');
        }

        return trim((string) $process->getOutput());
    }

    private function run(array $command, int $timeout): ?Process
    {
        $process = new Process($command, null, null, null, $timeout);

        try {
            $process->run();
        } catch (Throwable $e) {
            Log::error('OCR process errored', ['command' => implode(' ', $command), 'error' => $e->getMessage()]);

            return null;
        }

        return $process;
    }
}