<?php

namespace App\Http\Controllers;

use App\Services\WeatherService;
use Illuminate\Http\JsonResponse;

class WeatherController extends Controller
{
    /**
     * Current weather for the sidebar climate widget, as JSON.
     *
     * The sidebar reads the cache only while rendering, so a cold cache means
     * the widget starts as a placeholder and the browser fills it in from this
     * endpoint in the background — page loads never wait on OpenWeather.
     *
     * This call may block for up to ~4s when the cache is cold; that cost is
     * paid once (per cache window) by a request the user is not waiting on.
     */
    public function current(): JsonResponse
    {
        return response()->json(WeatherService::getCurrentWeather());
    }
}
