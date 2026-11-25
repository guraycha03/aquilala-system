<?php

namespace Tests\Unit;

use App\Support\CropPalette;
use Tests\TestCase;

class CropPaletteTest extends TestCase
{
    public function test_each_crop_has_a_pale_tint_and_a_dark_same_hue_ink(): void
    {
        $palette = new CropPalette();

        $eggplant = $palette->colors('Eggplant');

        $this->assertSame('#5B3A8A', $eggplant['ink']);
        $this->assertMatchesRegularExpression('/^#[0-9A-F]{6}$/', $eggplant['bg']);
        $this->assertNotSame('#000000', $eggplant['bg']);
        $this->assertSame($eggplant, $palette->colors('eggplant'));

        // Report/import spellings land on the same card as the directory name.
        $this->assertSame($palette->colors('String Bean'), $palette->colors('String Beans'));
        $this->assertSame($palette->colors('default'), $palette->colors('An unknown crop'));
    }
}
