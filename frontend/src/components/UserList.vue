<template>
  <div class="container mx-auto px-4 py-8">
    <!-- Live Updates Control -->
    <LiveUpdatesControl />

    <div class="flex items-center justify-between mb-8">
      <div>
        <h1 class="text-3xl font-bold text-gray-800 dark:text-white">
          Client Weather Monitoring
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
          Track weather conditions across all client locations
        </p>
      </div>
      <div class="text-right">
        <p class="text-sm text-gray-600 dark:text-gray-400">
          {{ usersStore.totalUsers }} total clients
        </p>
        <button
          v-if="!loadingAllWeather && !allWeatherLoaded"
          @click="loadAllWeather"
          class="mt-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm transition-colors shadow-sm"
        >
          Load Weather for This Page
        </button>
        <div
          v-if="loadingAllWeather"
          class="mt-2 text-sm text-gray-600 dark:text-gray-400"
        >
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
        class="animate-spin rounded-full h-12 w-12 border-4 border-gray-300 dark:border-gray-600 border-t-blue-600 dark:border-t-blue-400"
      ></div>
    </div>

    <!-- Error State -->
    <div
      v-else-if="usersStore.error"
      class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 rounded-lg"
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
    <div v-else-if="usersStore.users.length > 0">
      <div
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8"
      >
        <UserCard
          v-for="user in usersStore.users"
          :key="user.id"
          :user="user"
          @click="handleUserClick(user)"
        />
      </div>

      <!-- Pagination Controls -->
      <div
        class="flex items-center justify-between border-t dark:border-gray-700 pt-6"
      >
        <div class="text-sm text-gray-600 dark:text-gray-400">
          Showing {{ (usersStore.currentPage - 1) * usersStore.perPage + 1 }} to
          {{
            Math.min(
              usersStore.currentPage * usersStore.perPage,
              usersStore.totalUsers
            )
          }}
          of {{ usersStore.totalUsers }} clients
        </div>
        <div class="flex items-center space-x-2">
          <button
            @click="usersStore.previousPage()"
            :disabled="usersStore.currentPage === 1 || usersStore.loading"
            class="px-3 py-1 rounded-md bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            Previous
          </button>
          <span class="px-3 py-1 text-sm text-gray-700 dark:text-gray-300">
            Page {{ usersStore.currentPage }} of {{ usersStore.totalPages }}
          </span>
          <button
            @click="usersStore.nextPage()"
            :disabled="
              usersStore.currentPage === usersStore.totalPages ||
              usersStore.loading
            "
            class="px-3 py-1 rounded-md bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            Next
          </button>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-12">
      <p class="text-gray-500 dark:text-gray-400 text-lg">No clients found</p>
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
import LiveUpdatesControl from "./LiveUpdatesControl.vue";
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
  // Automatically load weather for clients on the first page
  loadAllWeather();
});

// Watch for page changes to reset weather loading
usersStore.$onAction(({ name, after }) => {
  if (name === "goToPage" || name === "nextPage" || name === "previousPage") {
    after(() => {
      // Reset weather loading for new page
      weatherLoadProgress.value = 0;
    });
  }
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

  const batchSize = 10; // Process 10 at a time to avoid rate limits
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
