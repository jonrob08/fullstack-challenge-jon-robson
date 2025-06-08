<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Major cities around the world for realistic weather data
        $locations = [
            ['name' => 'New York', 'lat' => 40.7128, 'lon' => -74.0060],
            ['name' => 'London', 'lat' => 51.5074, 'lon' => -0.1278],
            ['name' => 'Tokyo', 'lat' => 35.6762, 'lon' => 139.6503],
            ['name' => 'Sydney', 'lat' => -33.8688, 'lon' => 151.2093],
            ['name' => 'Paris', 'lat' => 48.8566, 'lon' => 2.3522],
            ['name' => 'Dubai', 'lat' => 25.2048, 'lon' => 55.2708],
            ['name' => 'Singapore', 'lat' => 1.3521, 'lon' => 103.8198],
            ['name' => 'Los Angeles', 'lat' => 34.0522, 'lon' => -118.2437],
            ['name' => 'Toronto', 'lat' => 43.6532, 'lon' => -79.3832],
            ['name' => 'Mumbai', 'lat' => 19.0760, 'lon' => 72.8777],
            ['name' => 'Beijing', 'lat' => 39.9042, 'lon' => 116.4074],
            ['name' => 'Cairo', 'lat' => 30.0444, 'lon' => 31.2357],
            ['name' => 'Moscow', 'lat' => 55.7558, 'lon' => 37.6173],
            ['name' => 'Buenos Aires', 'lat' => -34.6037, 'lon' => -58.3816],
            ['name' => 'Cape Town', 'lat' => -33.9249, 'lon' => 18.4241],
            ['name' => 'São Paulo', 'lat' => -23.5505, 'lon' => -46.6333],
            ['name' => 'Mexico City', 'lat' => 19.4326, 'lon' => -99.1332],
            ['name' => 'Bangkok', 'lat' => 13.7563, 'lon' => 100.5018],
            ['name' => 'Istanbul', 'lat' => 41.0082, 'lon' => 28.9784],
            ['name' => 'Seoul', 'lat' => 37.5665, 'lon' => 126.9780],
        ];

        // Create a few real users with known locations
        foreach ($locations as $index => $location) {
            User::create([
                'name' => "User from {$location['name']}",
                'email' => strtolower(str_replace(' ', '.', $location['name'])) . '@example.com',
                'password' => Hash::make('password'),
                'latitude' => $location['lat'],
                'longitude' => $location['lon'],
            ]);
        }

        // Create additional random users for bulk testing
        $additionalUsers = 480; // Total will be 500 users
        
        for ($i = 1; $i <= $additionalUsers; $i++) {
            // Generate random locations within reasonable bounds
            $latitude = $this->randomFloat(-60, 70, 4); // Avoid extreme polar regions
            $longitude = $this->randomFloat(-180, 180, 4);
            
            User::create([
                'name' => "Test User {$i}",
                'email' => "testuser{$i}@example.com",
                'password' => Hash::make('password'),
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);
        }
    }

    /**
     * Generate a random float between min and max with given decimals
     */
    private function randomFloat($min, $max, $decimals = 2): float
    {
        $scale = pow(10, $decimals);
        return mt_rand($min * $scale, $max * $scale) / $scale;
    }
}