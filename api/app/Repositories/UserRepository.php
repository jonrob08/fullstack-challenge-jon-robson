<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class UserRepository
{
    /**
     * Get all users with their locations
     */
    public function getAllWithLocations(): Collection
    {
        return User::select('id', 'name', 'email', 'latitude', 'longitude')
            ->orderBy('id')
            ->get();
    }

    /**
     * Get a single user by ID
     */
    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    /**
     * Get users with pagination
     */
    public function paginate(int $perPage = 20)
    {
        return User::select('id', 'name', 'email', 'latitude', 'longitude')
            ->orderBy('id')
            ->paginate($perPage);
    }

    /**
     * Get all user locations
     */
    public function getAllLocations(): array
    {
        return User::select('id', 'latitude', 'longitude')
            ->get()
            ->map(function ($user) {
                return [
                    'user_id' => $user->id,
                    'coordinates' => [$user->latitude, $user->longitude]
                ];
            })
            ->toArray();
    }

    /**
     * Get paginated users with optional location filter
     */
    public function getPaginated(int $perPage = 15, ?bool $hasLocation = null): LengthAwarePaginator
    {
        $query = User::query();

        if ($hasLocation === true) {
            $query->whereNotNull('latitude')->whereNotNull('longitude');
        } elseif ($hasLocation === false) {
            $query->whereNull('latitude')->orWhereNull('longitude');
        }

        return $query->orderBy('id')->paginate($perPage);
    }

    /**
     * Update user
     */
    public function update(User $user, array $data): User
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        
        $user->update($data);
        return $user;
    }

    /**
     * Delete user
     */
    public function delete(User $user): bool
    {
        return $user->delete();
    }

    /**
     * Get users with location data
     */
    public function getUsersWithLocation(): Collection
    {
        return User::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();
    }
}