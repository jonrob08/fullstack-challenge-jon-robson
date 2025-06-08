<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WeatherController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/', function () {
    return response()->json([
        'name' => 'Weather API',
        'version' => '1.0',
        'endpoints' => [
            'users' => [
                'list' => 'GET /api/users',
                'show' => 'GET /api/users/{id}',
                'update' => 'PUT /api/users/{id}',
                'delete' => 'DELETE /api/users/{id}',
            ],
            'weather' => [
                'user_weather' => 'GET /api/users/{id}/weather',
                'refresh_user_weather' => 'POST /api/users/{id}/weather/refresh',
                'all_users_weather' => 'GET /api/weather',
            ],
        ],
    ]);
});

// User routes
Route::apiResource('users', UserController::class)->except(['store']);

// Weather routes
Route::prefix('users/{user}/weather')->group(function () {
    Route::get('/', [WeatherController::class, 'show']);
    Route::post('/refresh', [WeatherController::class, 'refresh']);
});

Route::get('/weather', [WeatherController::class, 'index']);
