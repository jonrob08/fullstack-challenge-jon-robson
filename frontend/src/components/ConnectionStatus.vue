<template>
  <div class="fixed bottom-4 right-4 z-50">
    <transition
      enter-active-class="transition ease-out duration-300"
      enter-from-class="translate-y-full opacity-0"
      enter-to-class="translate-y-0 opacity-100"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="translate-y-0 opacity-100"
      leave-to-class="translate-y-full opacity-0"
    >
      <div
        v-if="showStatus"
        :class="statusClasses"
        class="px-4 py-2 rounded-lg shadow-lg flex items-center space-x-2"
      >
        <div :class="indicatorClasses" class="w-2 h-2 rounded-full"></div>
        <span class="text-sm font-medium">{{ statusText }}</span>
        <button
          v-if="!isConnected"
          @click="reconnect"
          class="text-xs underline hover:no-underline ml-2"
        >
          Retry
        </button>
      </div>
    </transition>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { useWebSocketStore } from "@/stores/websocket";

const websocketStore = useWebSocketStore();
const showStatus = ref(true);

const isConnected = computed(() => websocketStore.isConnected);
const connectionState = computed(() => websocketStore.connectionState);

const statusText = computed(() => {
  switch (connectionState.value) {
    case 'connecting':
      return 'Connecting to live updates...';
    case 'connected':
      return 'Connected - Live updates active';
    case 'disconnected':
      return 'Disconnected - Refresh to reconnect';
    case 'failed':
      return 'Connection failed';
    default:
      return connectionState.value;
  }
});

const statusClasses = computed(() => {
  if (isConnected.value) {
    return 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 border border-green-200 dark:border-green-800';
  } else if (connectionState.value === 'connecting') {
    return 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 border border-yellow-200 dark:border-yellow-800';
  } else {
    return 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 border border-red-200 dark:border-red-800';
  }
});

const indicatorClasses = computed(() => {
  if (isConnected.value) {
    return 'bg-green-500 animate-pulse';
  } else if (connectionState.value === 'connecting') {
    return 'bg-yellow-500 animate-pulse';
  } else {
    return 'bg-red-500';
  }
});

const reconnect = () => {
  window.location.reload();
};

// Auto-hide success status after 5 seconds
watch(isConnected, (connected) => {
  if (connected) {
    setTimeout(() => {
      showStatus.value = false;
    }, 5000);
  } else {
    showStatus.value = true;
  }
});
</script>