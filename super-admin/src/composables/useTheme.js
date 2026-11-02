import { ref } from 'vue';

const isDark = ref(false);
let initialized = false;

export function useTheme() {
  const applyTheme = () => {
    if (typeof document === 'undefined') return;
    if (isDark.value) {
      document.documentElement.classList.add('dark');
      document.documentElement.setAttribute('data-theme', 'dark');
      localStorage.setItem('clinic_theme', 'dark');
    } else {
      document.documentElement.classList.remove('dark');
      document.documentElement.setAttribute('data-theme', 'light');
      localStorage.setItem('clinic_theme', 'light');
    }
  };

  const initTheme = () => {
    if (typeof window === 'undefined') return;
    const saved = localStorage.getItem('clinic_theme');
    if (saved) {
      isDark.value = saved === 'dark';
    } else {
      isDark.value = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    }
    applyTheme();
    initialized = true;
  };

  const toggleTheme = () => {
    isDark.value = !isDark.value;
    applyTheme();
  };

  if (!initialized && typeof window !== 'undefined') {
    initTheme();
  }

  return {
    isDark,
    initTheme,
    toggleTheme,
  };
}
