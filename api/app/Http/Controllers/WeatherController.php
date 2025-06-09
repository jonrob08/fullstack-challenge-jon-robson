<?php

namespace App\Http\Controllers;

use App\Events\WeatherUpdated;
use App\Http\Resources\WeatherResource;
use App\Models\User;
use App\Repositories\WeatherRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class WeatherController extends Controller
{
    protected WeatherRepository $weatherRepository;

    public function __construct(WeatherRepository $weatherRepository)
    {
        $this->weatherRepository = $weatherRepository;
    }

    /**
     * Get weather for a specific user
     */
    public function show(User $user)
    {
        try {
            $weather = $this->weatherRepository->getWeatherForUser($user);
            return response()->json(['data' => new WeatherResource($weather)]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Weather service unavailable',
                'message' => $e->getMessage()
            ], 503);
        }
    }

    /**
     * Force refresh weather for a user (bypass cache)
     */
    public function refresh(User $user): JsonResponse
    {
        try {
            // Clear cache for this user's location
            $this->weatherRepository->clearCacheForUser($user);

            // Fetch fresh data
            $weather = $this->weatherRepository->getWeatherForUser($user);

            // Broadcast the weather update (skip in testing environment)
            if (app()->environment() !== 'testing') {
                broadcast(new WeatherUpdated($user->id, $weather));
            }

            return response()->json([
                'data' => new WeatherResource($weather),
                'message' => 'Weather data refreshed successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Unable to refresh weather data',
                'message' => $e->getMessage(),
            ], 503);
        }
    }

    /**
     * Get weather for all users
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $users = User::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->paginate($perPage);

        $weatherData = $this->weatherRepository->getWeatherForUsers($users);

        return response()->json([
            'data' => collect($weatherData)->map(function ($item) {
                return [
                    'user_id' => $item['user']->id,
                    'user_name' => $item['user']->name,
                    'weather' => $item['weather'] ? new WeatherResource($item['weather']) : null,
                    'error' => $item['error'],
                ];
            }),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }
}
