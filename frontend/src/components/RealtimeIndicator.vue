<template>
  <transition
    enter-active-class="transition ease-out duration-300"
    enter-from-class="opacity-0 scale-95"
    enter-to-class="opacity-100 scale-100"
    leave-active-class="transition ease-in duration-200"
    leave-from-class="opacity-100 scale-100"
    leave-to-class="opacity-0 scale-95"
  >
    <div
      v-if="showIndicator"
      class="absolute top-2 right-2 bg-green-500 text-white px-2 py-1 rounded-full text-xs font-medium flex items-center space-x-1"
    >
      <svg class="w-3 h-3 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
      </svg>
      <span>Live</span>
    </div>
  </transition>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";

const props = defineProps<{
  lastUpdate: Date | null;
}>();

const showIndicator = ref(false);

watch(() => props.lastUpdate, (newValue) => {
  if (newValue) {
    showIndicator.value = true;
    
    // Hide the indicator after 3 seconds
    setTimeout(() => {
      showIndicator.value = false;
    }, 3000);
  }
});
</script>