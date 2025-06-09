<script setup lang="ts">
import { RouterView } from "vue-router";
import ThemeToggle from "@/components/ThemeToggle.vue";
import { useWebSocketStore } from "@/stores/websocket";

// Initialize WebSocket store
const websocketStore = useWebSocketStore();
</script>

<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 transition-colors">
    <header
      class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700"
    >
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
            Weather Dashboard
          </h1>
          <div class="flex items-center space-x-4">
            <span
              class="text-sm text-gray-500 dark:text-gray-400 hidden md:block"
            >
              Real-time weather monitoring for all clients
            </span>
            <ThemeToggle />
          </div>
        </div>
      </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <RouterView />
    </main>
  </div>
</template>
