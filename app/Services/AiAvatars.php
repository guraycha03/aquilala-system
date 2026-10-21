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
     * key => [label, file]
     *
     * @return array<string, array{label: string, file: string}>
     */
    public static function all(): array
    {
        return [
            'sprout'  => ['label' => 'Sprout',    'file' => 'sprout.svg'],
            'leaf'    => ['label' => 'Leaf',      'file' => 'leaf.svg'],
            'sun'     => ['label' => 'Sun',       'file' => 'sun.svg'],
            'droplet' => ['label' => 'Rain',      'file' => 'droplet.svg'],
            'store'   => ['label' => 'Storefront', 'file' => 'store.svg'],
            'basket'  => ['label' => 'Basket',    'file' => 'basket.svg'],
            'wheat'   => ['label' => 'Rice',      'file' => 'wheat.svg'],
            'tractor' => ['label' => 'Tractor',   'file' => 'tractor.svg'],
        ];
    }

    /**
     * The default: the association's own mark.
     */
    public static function defaultKey(): string
    {
        return 'sprout';
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

        return asset('images/ai-avatars/' . $avatars[$key]['file']);
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
