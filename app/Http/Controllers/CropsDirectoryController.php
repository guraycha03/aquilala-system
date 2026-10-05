<?php

namespace App\Http\Controllers;

use App\Services\CropLibraryService;

class CropsDirectoryController extends Controller
{
    public function index(CropLibraryService $library)
    {
        $crops = $library->directoryCrops();

        $supportedCrops = collect($crops)->pluck('name');

        return view('farmer.crops-directory.index', compact('supportedCrops', 'crops'));
    }
}
