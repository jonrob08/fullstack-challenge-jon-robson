import { defineStore } from "pinia";
import { ref } from "vue";
import { echo, onConnectionStateChange } from "@/services/echo";
import { useWeatherStore } from "./weather";
import { useUsersStore } from "./users";

export const useWebSocketStore = defineStore("websocket", () => {
  const connectionState = ref<string>("connecting");
  const isConnected = ref(false);
  const lastUpdate = ref<Date | null>(null);
  const liveUpdatesEnabled = ref(true);
  let isInitialized = false;

  // Subscribe to weather updates
  const subscribeToWeatherUpdates = () => {
    if (!liveUpdatesEnabled.value) return;
    
    const weatherStore = useWeatherStore();
    const usersStore = useUsersStore();

    // Listen for individual weather updates
    echo.channel('weather')
      .listen('.weather.updated', (e: any) => {
        if (!liveUpdatesEnabled.value) return;
        
        console.log('Weather updated for user:', e.userId);
        
        // Update weather in store
        weatherStore.weatherData.set(e.userId, e.weather);
        
        // Update user's weather data
        usersStore.updateUserWeather(e.userId, e.weather);
        
        lastUpdate.value = new Date();
      })
      .listen('.weather.batch.updated', (e: any) => {
        if (!liveUpdatesEnabled.value) return;
        
        console.log('Batch weather update received:', e.totalUpdated);
        
        // Update all weather data from batch
        e.updates.forEach((update: any) => {
          weatherStore.weatherData.set(update.userId, update.weather);
          usersStore.updateUserWeather(update.userId, update.weather);
        });
        
        lastUpdate.value = new Date();
      });
  };

  // Subscribe to specific user weather updates
  const subscribeToUserWeather = (userId: number) => {
    if (!liveUpdatesEnabled.value) return;
    
    const weatherStore = useWeatherStore();
    const usersStore = useUsersStore();

    echo.channel(`weather.user.${userId}`)
      .listen('.weather.updated', (e: any) => {
        if (!liveUpdatesEnabled.value) return;
        
        console.log(`Weather updated for user ${userId}:`, e);
        
        // Update weather in store
        weatherStore.weatherData.set(userId, e.weather);
        
        // Update user's weather data
        usersStore.updateUserWeather(userId, e.weather);
        
        lastUpdate.value = new Date();
      });
  };

  // Unsubscribe from user weather updates
  const unsubscribeFromUserWeather = (userId: number) => {
    echo.leave(`weather.user.${userId}`);
  };

  // Initialize WebSocket connection
  const initialize = () => {
    if (isInitialized) return;
    isInitialized = true;
    
    // Monitor connection state
    onConnectionStateChange((state: string) => {
      connectionState.value = state;
      isConnected.value = state === 'connected';
      
      if (state === 'connected') {
        console.log('WebSocket connected successfully');
        if (liveUpdatesEnabled.value) {
          subscribeToWeatherUpdates();
        }
      } else if (state === 'disconnected') {
        console.log('WebSocket disconnected');
      }
    });
  };

  // Enable live updates
  const enableLiveUpdates = () => {
    liveUpdatesEnabled.value = true;
    if (isConnected.value) {
      subscribeToWeatherUpdates();
    }
  };

  // Disable live updates
  const disableLiveUpdates = () => {
    liveUpdatesEnabled.value = false;
    cleanup();
  };

  // Clean up on unmount
  const cleanup = () => {
    echo.leave('weather');
    // Leave all user channels
    const channels = Object.keys(echo.connector.channels);
    channels.forEach(channel => {
      if (channel.startsWith('weather.user.')) {
        echo.leave(channel);
      }
    });
  };

  // Initialize on first use
  initialize();

  return {
    connectionState,
    isConnected,
    lastUpdate,
    liveUpdatesEnabled,
    subscribeToUserWeather,
    unsubscribeFromUserWeather,
    enableLiveUpdates,
    disableLiveUpdates,
  };
});