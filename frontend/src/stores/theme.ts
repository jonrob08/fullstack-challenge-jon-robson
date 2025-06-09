import { defineStore } from "pinia";
import { ref, watch, computed } from "vue";

export type Theme = "light" | "dark" | "system";

export const useThemeStore = defineStore("theme", () => {
  const theme = ref<Theme>("system");

  // Detect system preference
  const systemTheme = ref<"light" | "dark">(
    window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light"
  );

  // Computed property for actual theme
  const actualTheme = computed(() => {
    if (theme.value === "system") {
      return systemTheme.value;
    }
    return theme.value;
  });

  // Initialize theme from localStorage
  const initTheme = () => {
    const savedTheme = localStorage.getItem("theme") as Theme | null;
    if (savedTheme && ["light", "dark", "system"].includes(savedTheme)) {
      theme.value = savedTheme;
    }
  };

  // Apply theme to document
  const applyTheme = () => {
    const root = window.document.documentElement;

    if (actualTheme.value === "dark") {
      root.classList.add("dark");
    } else {
      root.classList.remove("dark");
    }
  };

  // Set theme
  const setTheme = (newTheme: Theme) => {
    theme.value = newTheme;
    localStorage.setItem("theme", newTheme);
  };

  // Watch for system theme changes
  const mediaQuery = window.matchMedia("(prefers-color-scheme: dark)");
  mediaQuery.addEventListener("change", (e) => {
    systemTheme.value = e.matches ? "dark" : "light";
  });

  // Watch theme changes and apply
  watch(
    actualTheme,
    () => {
      applyTheme();
    },
    { immediate: true }
  );

  // Initialize on store creation
  initTheme();
  applyTheme();

  return {
    theme,
    actualTheme,
    setTheme,
  };
});
