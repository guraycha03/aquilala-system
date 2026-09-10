<?php

/*
|--------------------------------------------------------------------------
| Crop Maturity Guides (harvest-date auto-estimation)
|--------------------------------------------------------------------------
| Dedicated fallback table for the Expected Harvest Date estimate in the
| New Crop Batch modal.
|
| Priority used by FarmerCropController::buildYieldEstimatorData():
|   1. Product variety maturity  (rice_varieties.maturity_days, most specific)
|   2. Bulan catalog maturity    (config/bulan_crops.php -> maturity_days)
|   3. This fallback table       (umbrella groups & anything unlisted)
|
| Values are typical days from planting to first harvest, based on local
| provincial agronomy guides (Sorsogon/Bicol) and DA crop calendars.
*/

return [

    // Umbrella crop groups (no single true maturity — mid-range averages).
    'fallback_maturity_days' => [
        'vegetables' => 60,
        'root crops' => 120,
        'legumes'    => 90,
        'other'      => 150,
    ],

    // Optional extra overrides for any crop name not covered elsewhere.
    'maturity_days_overrides' => [],

];