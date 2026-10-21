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
     * These are the pictures the application already owns, in
     * public/images. They stand in until a proper assistant picture is
     * dropped in there: the account stores the key, the file is ours.
     *
     * @return array<string, array{label: string, file: string}>
     */
    public static function all(): array
    {
        return [
            'robot'    => ['label' => 'Robot',    'file' => 'ai-avatar.png'],
            'aquilala' => ['label' => 'Aquilala', 'file' => 'logo.png'],
            'water'    => ['label' => 'Water',    'file' => 'logo_.png'],
            'leaf'     => ['label' => 'Leaf',     'file' => 'logo_2.png'],
            'sprout'   => ['label' => 'Sprout',   'file' => 'crop.png'],
            'store'    => ['label' => 'Store',    'file' => 'store.png'],
            'farmer'   => ['label' => 'Farmer',   'file' => 'farmer.png'],
            'product'  => ['label' => 'Product',  'file' => 'product.png'],
        ];
    }

    /**
     * The default: the assistant's own picture.
     */
    public static function defaultKey(): string
    {
        return 'robot';
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

        foreach (self::all() as $key => $avatar) {
            $out[] = [
                'key'   => $key,
                'label' => $avatar['label'],
                'url'   => self::url($key),
            ];
        }

        return $out;
    }
}
