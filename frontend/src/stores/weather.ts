import { defineStore } from "pinia";
import { ref } from "vue";
import type { Weather } from "@/types/user";
import { api } from "@/services/api";
import { useUsersStore } from "./users";

export const useWeatherStore = defineStore("weather", () => {
  const weatherData = ref<Map<number, Weather>>(new Map());
  const loading = ref<Map<number, boolean>>(new Map());
  const error = ref<Map<number, string>>(new Map());

  const fetchUserWeather = async (userId: number) => {
    loading.value.set(userId, true);
    error.value.delete(userId);

    try {
      const response = await api.getUserWeather(userId);
      weatherData.value.set(userId, response.data);

      // Update user store with weather data
      const usersStore = useUsersStore();
      usersStore.updateUserWeather(userId, response.data);

      return response.data;
    } catch (err) {
      const errorMessage =
        err instanceof Error ? err.message : "Failed to fetch weather";
      error.value.set(userId, errorMessage);
      console.error(`Weather fetch error for user ${userId}:`, err);
      throw err;
    } finally {
      loading.value.set(userId, false);
    }
  };

  const refreshUserWeather = async (userId: number) => {
    loading.value.set(userId, true);
    error.value.delete(userId);

    try {
      const response = await api.refreshWeather(userId);
      weatherData.value.set(userId, response.data);

      // Update user store with fresh weather data
      const usersStore = useUsersStore();
      usersStore.updateUserWeather(userId, response.data);

      return response.data;
    } catch (err) {
      const errorMessage =
        err instanceof Error ? err.message : "Failed to refresh weather";
      error.value.set(userId, errorMessage);
      console.error(`Weather refresh error for user ${userId}:`, err);
      throw err;
    } finally {
      loading.value.set(userId, false);
    }
  };

  const getWeatherForUser = (userId: number): Weather | undefined => {
    return weatherData.value.get(userId);
  };

  const isLoadingForUser = (userId: number): boolean => {
    return loading.value.get(userId) || false;
  };

  const getErrorForUser = (userId: number): string | undefined => {
    return error.value.get(userId);
  };

  const clearError = (userId: number) => {
    error.value.delete(userId);
  };

  return {
    weatherData,
    fetchUserWeather,
    refreshUserWeather,
    getWeatherForUser,
    isLoadingForUser,
    getErrorForUser,
    clearError,
  };
});
