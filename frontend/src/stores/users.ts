import { defineStore } from "pinia";
import { ref, computed } from "vue";
import type { User } from "@/types/user";
import { api } from "@/services/api";

export const useUsersStore = defineStore("users", () => {
  const users = ref<User[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);
  const selectedUserId = ref<number | null>(null);
  const currentPage = ref(1);
  const totalPages = ref(1);
  const perPage = ref(20);
  const totalUsers = ref(0);

  const selectedUser = computed(() =>
    users.value.find((user) => user.id === selectedUserId.value)
  );

  const fetchUsers = async (page: number = 1) => {
    loading.value = true;
    error.value = null;

    try {
      const response = await api.getUsers(page, perPage.value);
      users.value = response.data;

      // Update pagination
      if (response.meta) {
        currentPage.value = response.meta.current_page ?? currentPage.value;
        totalPages.value = response.meta.last_page ?? totalPages.value;
        totalUsers.value = response.meta.total ?? totalUsers.value;
      }
    } catch (err) {
      error.value =
        err instanceof Error ? err.message : "Failed to fetch users";
      console.error("Users fetch error:", err);
    } finally {
      loading.value = false;
    }
  };

  const goToPage = async (page: number) => {
    if (page >= 1 && page <= totalPages.value) {
      await fetchUsers(page);
    }
  };

  const nextPage = async () => {
    if (currentPage.value < totalPages.value) {
      await goToPage(currentPage.value + 1);
    }
  };

  const previousPage = async () => {
    if (currentPage.value > 1) {
      await goToPage(currentPage.value - 1);
    }
  };

  const selectUser = (userId: number | null) => {
    selectedUserId.value = userId;
  };

  const updateUserWeather = (userId: number, weather: any) => {
    const user = users.value.find((u) => u.id === userId);
    if (user) {
      user.weather = weather;
    }
  };

  return {
    users,
    loading,
    error,
    selectedUser,
    selectedUserId,
    currentPage,
    totalPages,
    perPage,
    totalUsers,
    fetchUsers,
    goToPage,
    nextPage,
    previousPage,
    selectUser,
    updateUserWeather,
  };
});
