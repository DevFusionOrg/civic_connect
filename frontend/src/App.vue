<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import MainLayout from './layouts/MainLayout.vue'
import AuthLayout from './layouts/AuthLayout.vue'
import ChatBot from './components/ChatBot.vue'

const route = useRoute()

const layout = computed(() => {
  const layoutMeta = route.meta.layout
  if (layoutMeta === 'AuthLayout') return AuthLayout
  return MainLayout
})
</script>

<template>
  <div class="app-theme-shell">
    <div class="app-theme-orb app-theme-orb-a"></div>
    <div class="app-theme-orb app-theme-orb-b"></div>
    <div class="app-theme-orb app-theme-orb-c"></div>

    <div class="app-main-layer">
      <component :is="layout">
        <div class="app-page-host">
          <router-view />
        </div>
      </component>
    </div>
    <ChatBot />
  </div>
</template>

<style>
.app-theme-shell {
  position: relative;
  min-height: 100vh;
  overflow-x: clip;
  background:
    radial-gradient(circle at top left, rgba(212, 175, 55, 0.15), transparent 28%),
    radial-gradient(circle at top right, rgba(255, 153, 51, 0.1), transparent 24%),
    linear-gradient(180deg, #fffdf7 0%, #fffbf0 52%, #fff9f0 100%);
}

.app-theme-orb {
  pointer-events: none;
  position: fixed;
  z-index: 0;
  border-radius: 9999px;
  filter: blur(56px);
}

.app-theme-orb-a {
  left: -6rem;
  top: 5rem;
  height: 20rem;
  width: 20rem;
  background: rgba(212, 175, 55, 0.15);
  animation: gentleFloat 6s ease-in-out infinite;
}

.app-theme-orb-b {
  right: -7rem;
  top: 8rem;
  height: 24rem;
  width: 24rem;
  background: rgba(255, 153, 51, 0.12);
  animation: gentleFloat 6s ease-in-out infinite;
  animation-delay: 1s;
}

.app-theme-orb-c {
  left: 35%;
  bottom: -5rem;
  height: 18rem;
  width: 18rem;
  background: rgba(212, 175, 55, 0.1);
  animation: gentleFloat 6s ease-in-out infinite;
  animation-delay: 2s;
}

@keyframes gentleFloat {
  0%, 100% {
    transform: translateY(0px);
  }
  50% {
    transform: translateY(-15px);
  }
}

.app-main-layer {
  position: relative;
  z-index: 1;
}

.app-page-host {
  min-height: 100%;
}

.app-page-host > * {
  background-color: transparent !important;
  background-image: none !important;
}
</style>
