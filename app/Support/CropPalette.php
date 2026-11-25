<?php

namespace App\Support;

/**
 * One crop colour palette for the whole system.
 *
 * The pastel backgrounds come from the Bulan/Bicol Crop Directory cards. The
 * paired ink is a darker version of the same hue, so a pale crop tint can be
 * used as a light card or tooltip background while its text stays readable.
 */
final class CropPalette
{
    /** @var array<string, array{0: string, 1: string}> base background, dark ink */
    private const COLORS = [
        // Grains
        'Rice' => ['#E8F5E9', '#2F6B45'],
        'Corn' => ['#FFF59D', '#7A5C00'],
        'Wheat' => ['#F5E6CA', '#6B4E1F'],

        // Fruits
        'Coconut' => ['#E0D5C1', '#5A4A32'],
        'Pili' => ['#D7CCC8', '#5B4A46'],
        'Banana' => ['#FFF176', '#7A6600'],
        'Mango' => ['#FFCC80', '#8A4B08'],
        'Papaya' => ['#FFB74D', '#8A4407'],
        'Pineapple' => ['#FFE082', '#7A5A00'],
        'Jackfruit' => ['#E6EE9C', '#556B1F'],
        'Calamansi' => ['#C5E1A5', '#3F6B2A'],
        'Watermelon' => ['#FFAB91', '#8A3A28'],
        'Orange' => ['#FFB74D', '#8A4A07'],
        'Lemon' => ['#FFF59D', '#7A6600'],

        // Vegetables
        'Tomato' => ['#FF8A80', '#8A2E28'],
        'Eggplant' => ['#D1C4E9', '#5B3A8A'],
        'Okra' => ['#C8E6C9', '#2F7040'],
        'String Bean' => ['#A5D6A7', '#2F6B35'],
        'Squash' => ['#FFE082', '#7A5A00'],
        'Ampalaya' => ['#A5D6A7', '#2F6B35'],
        'Pepper' => ['#FF8A80', '#8A2E28'],
        'Cabbage' => ['#C8E6C9', '#2F7040'],
        'Sayote' => ['#DCEDC8', '#4F702A'],
        'Luffa' => ['#C8E6C9', '#2F7040'],
        'Cucumber' => ['#A5D6A7', '#2F6B35'],
        'Lettuce' => ['#DCEDC8', '#4F702A'],
        'Malunggay' => ['#A5D6A7', '#2F6B35'],
        'Kangkong' => ['#C8E6C9', '#2F7040'],

        // Root crops
        'Cassava' => ['#D7CCC8', '#5B4A46'],
        'Sweet Potato' => ['#E1BEE7', '#6A2F7A'],
        'Taro' => ['#E1BEE7', '#6A2F7A'],
        'Potato' => ['#F5E6CA', '#6B4E1F'],
        'Carrot' => ['#FFCC80', '#8A4B08'],

        // Herbs and spices
        'Onion' => ['#F8BBD0', '#8A3459'],
        'Garlic' => ['#F5F5F5', '#4B5563'],
        'Ginger' => ['#E0D5C1', '#5A4A32'],
        'Turmeric' => ['#FFE082', '#7A5A00'],
        'Lemongrass' => ['#DCEDC8', '#4F702A'],

        // Legumes
        'Mung Bean' => ['#C8E6C9', '#2F7040'],
        'Soybean' => ['#FFF59D', '#7A5C00'],
        'Peanut' => ['#E0D5C1', '#5A4A32'],

        // Fibre and industrial
        'Abaca' => ['#D7CCC8', '#5B4A46'],
        'Coffee' => ['#D7CCC8', '#5B4A46'],
        'Cacao' => ['#CFD8DC', '#45545C'],
        'Rubber' => ['#ECEFF1', '#475569'],
        'Sugarcane' => ['#DCEDC8', '#4F702A'],
        'Tobacco' => ['#D7CCC8', '#5B4A46'],
        'Cotton' => ['#FAFAFA', '#475569'],

        // Names the directory and the reports use slightly differently.
        'String Beans' => ['#A5D6A7', '#2F6B35'],
        'Hot Pepper' => ['#FF8A80', '#8A2E28'],
        'Mungbean' => ['#C8E6C9', '#2F7040'],
        'Gabi / Taro' => ['#E1BEE7', '#6A2F7A'],
        'default' => ['#E8F5E9', '#2F6B45'],
    ];

    /** @var array<string, array{bg: string, ink: string}>|null */
    private ?array $keyed = null;

    /**
     * Pale background and matching dark ink for a crop name.
     *
     * @return array{bg: string, ink: string}
     */
    public function colors(?string $name): array
    {
        $entry = $this->entryFor($name);

        return [
            'bg' => $this->mix($entry[0], '#FFFFFF', 0.24),
            'ink' => $entry[1],
        ];
    }

    /**
     * The faded card tint used by the Crop Directory. Kept as a plain hex so
     * Chart.js tooltips and inline styles can both use it.
     */
    public function tint(?string $name): string
    {
        return $this->colors($name)['bg'];
    }

    /** Dark, same-hue text colour for the tint above. */
    public function ink(?string $name): string
    {
        return $this->colors($name)['ink'];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function entryFor(?string $name): array
    {
        if ($this->keyed === null) {
            $keyed = [];
            foreach (self::COLORS as $label => $pair) {
                $keyed[$this->key($label)] = $pair;
            }
            $this->keyed = $keyed;
        }

        return $this->keyed[$this->key($name)] ?? $this->keyed['default'];
    }

    private function key(?string $name): string
    {
        return preg_replace('/[^\p{L}\p{N}]+/u', '', mb_strtolower(trim((string) $name))) ?? 'default';
    }

    /** Mix $ratio of $hex with the rest of $with, both #RRGGBB. */
    private function mix(string $hex, string $with, float $ratio): string
    {
        $rgb = static fn (string $value): array => [
            hexdec(substr($value, 1, 2)),
            hexdec(substr($value, 3, 2)),
            hexdec(substr($value, 5, 2)),
        ];

        [$r1, $g1, $b1] = $rgb($hex);
        [$r2, $g2, $b2] = $rgb($with);

        return sprintf(
            '#%02X%02X%02X',
            (int) round($r1 * $ratio + $r2 * (1 - $ratio)),
            (int) round($g1 * $ratio + $g2 * (1 - $ratio)),
            (int) round($b1 * $ratio + $b2 * (1 - $ratio))
        );
    }
}
