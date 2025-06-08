<template>
  <Teleport to="body">
    <div
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
      @click="$emit('close')"
    >
      <div
        @click.stop
        class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto animate-slide-up"
      >
        <!-- Header -->
        <div
          class="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between"
        >
          <div>
            <h2 class="text-xl font-semibold text-gray-800">{{ user.name }}</h2>
            <p class="text-sm text-gray-600">{{ user.email }}</p>
          </div>
          <button
            @click="$emit('close')"
            class="text-gray-400 hover:text-gray-600 transition-colors"
          >
            <svg
              class="w-6 h-6"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>

        <!-- Content -->
        <div class="p-6">
          <!-- Loading State -->
          <div v-if="isLoading" class="flex justify-center py-12">
            <div
              class="animate-spin rounded-full h-12 w-12 border-4 border-gray-300 border-t-blue-600"
            ></div>
          </div>

          <!-- Error State -->
          <div
            v-else-if="error"
            class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg"
          >
            <p class="font-medium">Error loading weather</p>
            <p class="text-sm">{{ error }}</p>
            <button
              @click="fetchWeather"
              class="mt-2 text-sm underline hover:no-underline"
            >
              Try again
            </button>
          </div>

          <!-- Weather Details -->
          <div v-else-if="weather" class="space-y-6">
            <!-- Current Weather -->
            <div
              class="bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg p-6"
            >
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                  <img
                    :src="`https://openweathermap.org/img/wn/${weather.current.icon}@4x.png`"
                    :alt="weather.current.description"
                    class="w-24 h-24"
                  />
                  <div>
                    <p class="text-4xl font-bold text-gray-800">
                      {{ Math.round(weather.current.temperature) }}°C
                    </p>
                    <p class="text-lg text-gray-600 capitalize">
                      {{ weather.current.description }}
                    </p>
                    <p class="text-sm text-gray-500">
                      Feels like {{ Math.round(weather.current.feels_like) }}°C
                    </p>
                  </div>
                </div>
                <button
                  @click="refreshWeather"
                  :disabled="isLoading"
                  class="bg-white hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-lg shadow transition-colors disabled:opacity-50"
                >
                  Refresh
                </button>
              </div>
            </div>

            <!-- Weather Grid -->
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
              <WeatherDetailCard
                title="Humidity"
                :value="`${weather.current.humidity}%`"
                icon="💧"
              />
              <WeatherDetailCard
                title="Wind Speed"
                :value="`${weather.current.wind_speed} m/s`"
                icon="💨"
              />
              <WeatherDetailCard
                title="Pressure"
                :value="`${weather.current.pressure} hPa`"
                icon="🌡️"
              />
              <WeatherDetailCard
                title="Clouds"
                :value="`${weather.current.clouds}%`"
                icon="☁️"
              />
              <WeatherDetailCard
                title="Visibility"
                :value="`${(weather.current.visibility / 1000).toFixed(1)} km`"
                icon="👁️"
              />
              <WeatherDetailCard
                v-if="weather.current.rain"
                title="Rain (1h)"
                :value="`${weather.current.rain} mm`"
                icon="🌧️"
              />
            </div>

            <!-- Location -->
            <div class="border-t pt-4">
              <h3 class="text-sm font-medium text-gray-700 mb-2">
                Client Location
              </h3>
              <p class="text-sm text-gray-600">
                {{
                  formatLocation(
                    user.location.latitude,
                    user.location.longitude
                  )
                }}
              </p>
            </div>

            <!-- Cache Info -->
            <div class="text-xs text-gray-500 text-center">
              <p>Last updated: {{ formatDateTime(weather.cached_at) }}</p>
              <p v-if="weather.response_time_ms">
                Response time: {{ weather.response_time_ms.toFixed(0) }}ms
              </p>
            </div>
          </div>

          <!-- No Data State -->
          <div v-else class="text-center py-12">
            <p class="text-gray-500 mb-4">No weather data available</p>
            <button
              @click="fetchWeather"
              class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors"
            >
              Load Weather
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { computed, onMounted } from "vue";
import type { User } from "@/types/user";
import { useWeatherStore } from "@/stores/weather";
import WeatherDetailCard from "./WeatherDetailCard.vue";

const props = defineProps<{
  user: User;
}>();

defineEmits<{
  close: [];
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
    // Error is handled in the store
  }
};

const refreshWeather = async () => {
  try {
    await weatherStore.refreshUserWeather(props.user.id);
  } catch (err) {
    // Error is handled in the store
  }
};

const formatLocation = (lat: number, lon: number): string => {
  const latDir = lat >= 0 ? "N" : "S";
  const lonDir = lon >= 0 ? "E" : "W";
  return `${Math.abs(lat).toFixed(4)}°${latDir}, ${Math.abs(lon).toFixed(
    4
  )}°${lonDir}`;
};

const formatDateTime = (timestamp: string): string => {
  return new Date(timestamp).toLocaleString();
};

onMounted(() => {
  // Fetch weather if not already loaded
  if (!weather.value && !isLoading.value) {
    fetchWeather();
  }
});
</script>
