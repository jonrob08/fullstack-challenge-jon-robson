<template>
  <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-xs text-gray-600 dark:text-gray-400">{{ title }}</p>
        <p class="text-lg font-semibold text-gray-800 dark:text-white">
          {{ value }}
        </p>
      </div>
      <span class="text-2xl">{{ icon }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  title: string;
  value: string;
  icon: string;
}>();
</script>
