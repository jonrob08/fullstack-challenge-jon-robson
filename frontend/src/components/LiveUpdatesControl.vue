<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
    <div class="flex items-center justify-between">
      <div class="flex items-center space-x-4">
        <!-- Connection Status -->
        <div class="flex items-center space-x-2">
          <div :class="statusIndicatorClasses" class="w-3 h-3 rounded-full"></div>
          <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ statusText }}
          </span>
        </div>

        <!-- Last Update Info -->
        <div v-if="lastUpdate" class="text-sm text-gray-500 dark:text-gray-400">
          Last update: {{ formatLastUpdate(lastUpdate) }}
        </div>
      </div>

      <!-- Toggle Switch -->
      <div class="flex items-center space-x-3">
        <span class="text-sm text-gray-600 dark:text-gray-400">Live Updates</span>
        <button
          @click="toggleLiveUpdates"
          :class="[
            liveUpdatesEnabled ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-700',
            'relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800'
          ]"
        >
          <span
            :class="[
              liveUpdatesEnabled ? 'translate-x-6' : 'translate-x-1',
              'inline-block h-4 w-4 transform rounded-full bg-white transition-transform'
            ]"
          />
        </button>
      </div>
    </div>

    <!-- Disconnected Warning -->
    <div
      v-if="!isConnected && liveUpdatesEnabled"
      class="mt-3 text-sm text-amber-600 dark:text-amber-400 flex items-center space-x-2"
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
      </svg>
      <span>Connection lost. Updates paused until reconnected.</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { useWebSocketStore } from "@/stores/websocket";

const websocketStore = useWebSocketStore();

const liveUpdatesEnabled = ref(true);
const isConnected = computed(() => websocketStore.isConnected);
const connectionState = computed(() => websocketStore.connectionState);
const lastUpdate = computed(() => websocketStore.lastUpdate);

const statusText = computed(() => {
  if (!liveUpdatesEnabled.value) {
    return 'Live updates disabled';
  }
  
  switch (connectionState.value) {
    case 'connecting':
      return 'Connecting...';
    case 'connected':
      return 'Connected';
    case 'disconnected':
      return 'Disconnected';
    case 'failed':
      return 'Connection failed';
    default:
      return connectionState.value;
  }
});

const statusIndicatorClasses = computed(() => {
  if (!liveUpdatesEnabled.value) {
    return 'bg-gray-400';
  }
  
  if (isConnected.value) {
    return 'bg-green-500 animate-pulse';
  } else if (connectionState.value === 'connecting') {
    return 'bg-yellow-500 animate-pulse';
  } else {
    return 'bg-red-500';
  }
});

const toggleLiveUpdates = () => {
  liveUpdatesEnabled.value = !liveUpdatesEnabled.value;
  
  if (liveUpdatesEnabled.value) {
    // Re-enable live updates
    if (websocketStore.enableLiveUpdates) {
      websocketStore.enableLiveUpdates();
    } else {
      console.error('enableLiveUpdates method not found on websocketStore');
    }
  } else {
    // Disable live updates
    if (websocketStore.disableLiveUpdates) {
      websocketStore.disableLiveUpdates();
    } else {
      console.error('disableLiveUpdates method not found on websocketStore');
    }
  }
};

const formatLastUpdate = (date: Date) => {
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffSecs = Math.floor(diffMs / 1000);
  
  if (diffSecs < 60) {
    return 'just now';
  }
  
  const diffMins = Math.floor(diffSecs / 60);
  if (diffMins < 60) {
    return `${diffMins} minute${diffMins > 1 ? 's' : ''} ago`;
  }
  
  return date.toLocaleTimeString();
};

// Initialize with live updates enabled
watch(liveUpdatesEnabled, (enabled) => {
  if (enabled && !isConnected.value) {
    websocketStore.enableLiveUpdates();
  }
}, { immediate: true });
</script>