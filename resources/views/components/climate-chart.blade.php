@props(['chartId' => 'climateChart', 'title' => 'Seasonal Climate Impact'])

@php
use App\Models\HarvestReport;
use App\Helpers\SeasonHelper;

// Simulated historical data (in production, query HarvestReport and actual rainfall records)
$months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$rainfall = [45, 32, 28, 18, 55, 145, 210, 195, 175, 130, 85, 60];
$yields = [2.8, 2.5, 2.2, 1.8, 3.0, 4.2, 4.8, 4.5, 4.0, 3.5, 3.2, 2.9];
@endphp

<div class="glass-panel" style="background: rgba(255,255,255,0.75); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border-radius: 20px; padding: 20px; border: 1px solid rgba(255,255,255,0.3); box-shadow: 0 4px 24px rgba(0,0,0,0.04);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
        <div>

