<template>
  <div class="container mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-8">
      <div>
        <h1 class="text-3xl font-bold text-gray-800">
          Client Weather Monitoring
        </h1>
        <p class="text-gray-600 mt-1">
          Track weather conditions across all client locations
        </p>
      </div>
      <div class="text-right">
        <p class="text-sm text-gray-600">
          {{ usersStore.users.length }} clients
        </p>
        <button
          v-if="!loadingAllWeather && !allWeatherLoaded"
          @click="loadAllWeather"
          class="mt-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition-colors"
        >
          Load All Weather Data
        </button>
        <div v-if="loadingAllWeather" class="mt-2 text-sm text-gray-600">
          Loading: {{ weatherLoadProgress }}/{{ usersStore.users.length }}
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div
      v-if="usersStore.loading"
      class="flex justify-center items-center py-12"
    >
      <div
        class="animate-spin rounded-full h-12 w-12 border-4 border-gray-300 border-t-blue-600"
      ></div>
    </div>

    <!-- Error State -->
    <div
      v-else-if="usersStore.error"
      class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg"
    >
      <p class="font-medium">Error loading clients</p>
      <p class="text-sm">{{ usersStore.error }}</p>
      <button
        @click="usersStore.fetchUsers()"
        class="mt-2 text-sm underline hover:no-underline"
      >
        Try again
      </button>
    </div>

    <!-- Users Grid -->
    <div
      v-else-if="usersStore.users.length > 0"
      class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6"
    >
      <UserCard
        v-for="user in usersStore.users"
        :key="user.id"
        :user="user"
        @click="handleUserClick(user)"
      />
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-12">
      <p class="text-gray-500 text-lg">No clients found</p>
    </div>

    <!-- Weather Modal -->
    <WeatherModal
      v-if="selectedUser"
      :user="selectedUser"
      @close="handleCloseModal"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { useUsersStore } from "@/stores/users";
import { useWeatherStore } from "@/stores/weather";
import UserCard from "./UserCard.vue";
import WeatherModal from "./WeatherModal.vue";
import type { User } from "@/types/user";

const usersStore = useUsersStore();
const weatherStore = useWeatherStore();
const selectedUser = ref<User | null>(null);
const loadingAllWeather = ref(false);
const weatherLoadProgress = ref(0);

const allWeatherLoaded = computed(() => {
  return usersStore.users.every(
    (user) => weatherStore.getWeatherForUser(user.id) !== undefined
  );
});

onMounted(async () => {
  await usersStore.fetchUsers();
  // Automatically load weather for all clients
  loadAllWeather();
});

const handleUserClick = (user: User) => {
  selectedUser.value = user;
};

const handleCloseModal = () => {
  selectedUser.value = null;
};

// Batch load weather data with rate limiting
const loadAllWeather = async () => {
  loadingAllWeather.value = true;
  weatherLoadProgress.value = 0;

  const batchSize = 5; // Process 5 at a time to avoid rate limits
  const users = usersStore.users;

  for (let i = 0; i < users.length; i += batchSize) {
    const batch = users.slice(i, i + batchSize);

    // Process batch in parallel
    await Promise.allSettled(
      batch.map((user) => weatherStore.fetchUserWeather(user.id))
    );

    weatherLoadProgress.value = Math.min(i + batchSize, users.length);

    // Add a small delay between batches to respect rate limits
    if (i + batchSize < users.length) {
      await new Promise((resolve) => setTimeout(resolve, 1000));
    }
  }

  loadingAllWeather.value = false;
};
</script>
