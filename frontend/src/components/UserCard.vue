<template>
  <div
    @click="$emit('click')"
    class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200 cursor-pointer p-6"
  >
    <!-- User Info -->
    <div class="mb-4">
      <h3 class="text-lg font-semibold text-gray-800">{{ user.name }}</h3>
      <p class="text-sm text-gray-600">{{ user.email }}</p>
      <p class="text-xs text-gray-500 mt-1">
        {{ formatLocation(user.location.latitude, user.location.longitude) }}
      </p>
    </div>

    <!-- Weather Loading -->
    <div v-if="isLoading" class="flex items-center justify-center py-4">
      <div
        class="animate-spin rounded-full h-8 w-8 border-2 border-gray-300 border-t-blue-600"
      ></div>
    </div>

    <!-- Weather Error -->
    <div v-else-if="error" class="bg-red-50 rounded p-3">
      <p class="text-sm text-red-600">{{ error }}</p>
      <button
        @click.stop="fetchWeather"
        class="text-xs text-red-700 underline mt-1"
      >
        Retry
      </button>
    </div>

    <!-- Weather Display -->
    <div v-else-if="weather" class="border-t pt-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <img
            :src="`https://openweathermap.org/img/wn/${weather.current.icon}@2x.png`"
            :alt="weather.current.description"
            class="w-12 h-12"
          />
          <div>
            <p class="text-2xl font-bold text-gray-800">
              {{ Math.round(weather.current.temperature) }}°C
            </p>
            <p class="text-sm text-gray-600 capitalize">
              {{ weather.current.description }}
            </p>
          </div>
        </div>
        <div class="text-right text-sm text-gray-600">
          <p>Feels like {{ Math.round(weather.current.feels_like) }}°C</p>
          <p>Humidity {{ weather.current.humidity }}%</p>
        </div>
      </div>

      <!-- Cache indicator -->
      <div v-if="weather.cached_at" class="mt-3 text-xs text-gray-500">
        Updated {{ formatCacheTime(weather.cached_at) }}
      </div>
    </div>

    <!-- No Weather Data -->
    <div v-else class="border-t pt-4">
      <div class="flex items-center justify-between">
        <div class="text-gray-400">
          <svg
            class="w-8 h-8"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"
            />
          </svg>
        </div>
        <button
          @click.stop="fetchWeather"
          class="text-blue-600 hover:text-blue-700 text-sm font-medium flex items-center space-x-1"
        >
          <span>View Weather</span>
          <svg
            class="w-4 h-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 5l7 7-7 7"
            />
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { User } from "@/types/user";
import { useWeatherStore } from "@/stores/weather";

const props = defineProps<{
  user: User;
}>();

defineEmits<{
  click: [];
}>();

const weatherStore = useWeatherStore();

const weather = computed(
  () => props.user.weather || weatherStore.getWeatherForUser(props.user.id)
);
const isLoading = computed(() => weatherStore.isLoadingForUser(props.user.id));
const error = computed(() => weatherStore.getErrorForUser(props.user.id));

const fetchWeather = async () => {
  try {
    await weatherStore.fetchUserWeather(props.user.id);
  } catch (err) {
    // Error handled in store
  }
};

const formatLocation = (lat: number, lon: number): string => {
  const latDir = lat >= 0 ? "N" : "S";
  const lonDir = lon >= 0 ? "E" : "W";
  return `${Math.abs(lat).toFixed(2)}°${latDir}, ${Math.abs(lon).toFixed(
    2
  )}°${lonDir}`;
};

const formatCacheTime = (timestamp: string): string => {
  const date = new Date(timestamp);
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffMins = Math.floor(diffMs / 60000);

  if (diffMins < 1) return "just now";
  if (diffMins < 60) return `${diffMins} min ago`;

  const diffHours = Math.floor(diffMins / 60);
  return `${diffHours} hour${diffHours > 1 ? "s" : ""} ago`;
};
</script>
