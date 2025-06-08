import { defineStore } from "pinia";
import { ref, computed } from "vue";
import type { User } from "@/types/user";
import { api } from "@/services/api";

export const useUsersStore = defineStore("users", () => {
  const users = ref<User[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);
  const selectedUserId = ref<number | null>(null);

  const selectedUser = computed(() =>
    users.value.find((user) => user.id === selectedUserId.value)
  );

  const fetchUsers = async () => {
    loading.value = true;
    error.value = null;

    try {
      const response = await api.getUsers();
      users.value = response.data;
    } catch (err) {
      error.value =
        err instanceof Error ? err.message : "Failed to fetch users";
      console.error("Users fetch error:", err);
    } finally {
      loading.value = false;
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
    fetchUsers,
    selectUser,
    updateUserWeather,
  };
});
