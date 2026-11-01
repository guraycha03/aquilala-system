<?php

namespace App\Services;

/**
 * AiAvatars
 *
 * The assistant's face, chosen from a fixed set the system provides.
 *
 * There is no upload: every avatar here belongs to the system, so every
 * account gets one that looks the same everywhere, and nothing a member
 * uploads ends up on the server. The account stores the key of the one it
 * picked; the file is ours.
 */
class AiAvatars
{
    /**
     * The choices, in the order the picker shows them.
     *
     * These are the assistant pictures in public/images/ai-avatar. The first
     * picture is the default; the rest remain available as choices.
     *
     * @return array<string, array{label: string, file: string}>
     */
    public static function all(): array
    {
        return [
            '1' => ['label' => 'Assistant 1', 'file' => 'ai-avatar/1.png'],
            '2' => ['label' => 'Assistant 2', 'file' => 'ai-avatar/2.png'],
            '3' => ['label' => 'Assistant 3', 'file' => 'ai-avatar/3.png'],
            '4' => ['label' => 'Assistant 4', 'file' => 'ai-avatar/4.png'],
            '5' => ['label' => 'Assistant 5', 'file' => 'ai-avatar/5.png'],
            // Legacy keys remain valid for existing API clients and records;
            // the picker exposes only the numbered assistant choices above.
            'robot' => ['label' => 'Assistant 1', 'file' => 'ai-avatar/1.png'],
            'aquilala' => ['label' => 'Assistant 2', 'file' => 'ai-avatar/2.png'],
            'water' => ['label' => 'Assistant 3', 'file' => 'ai-avatar/3.png'],
            'leaf' => ['label' => 'Assistant 3', 'file' => 'ai-avatar/3.png'],
            'sprout' => ['label' => 'Assistant 4', 'file' => 'ai-avatar/4.png'],
            'store' => ['label' => 'Assistant 5', 'file' => 'ai-avatar/5.png'],
            'farmer' => ['label' => 'Assistant 2', 'file' => 'ai-avatar/2.png'],
            'product' => ['label' => 'Assistant 1', 'file' => 'ai-avatar/1.png'],
        ];
    }

    /**
     * The default: the assistant's own picture.
     */
    public static function defaultKey(): string
    {
        return '1';
    }

    /**
     * Is this one of ours?
     */
    public static function has(string $key): bool
    {
        return array_key_exists($key, self::all());
    }

    /**
     * The public URL of one avatar.
     */
    public static function url(string $key): string
    {
        $avatars = self::all();

        if (! isset($avatars[$key])) {
            $key = self::defaultKey();
        }

        return asset('images/' . $avatars[$key]['file']);
    }

    /**
     * Every choice, ready for the picker.
     *
     * @return array<int, array{key: string, label: string, url: string}>
     */
    public static function choices(): array
    {
        $out = [];

        foreach (array_filter(self::all(), static fn ($avatar, $key) => in_array((string) $key, ['1', '2', '3', '5'], true), ARRAY_FILTER_USE_BOTH) as $key => $avatar) {
            $out[] = [
                'key'   => $key,
                'label' => $avatar['label'],
                'url'   => self::url($key),
            ];
        }

        return $out;
    }
}
