<template>
  <div class="bg-transparent flex min-h-screen flex-col font-sans">
    <!-- Navigation -->
    <nav
      class="bg-gradient-to-r from-cream-50 to-cream-100 sticky top-0 z-50 border-b border-gold-200 shadow-md backdrop-blur-sm transition-all duration-300"
    >
      <div class="mx-auto w-full max-w-screen-2xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
          <div class="flex items-center gap-3">
            <router-link to="/" class="flex items-center gap-2 hover:opacity-80 transition-opacity">
              <img :src="logoDesktop" alt="CivicConnect" class="hidden h-10 w-auto md:block" />
              <img :src="logoMobile" alt="CivicConnect" class="block h-10 w-auto md:hidden" />
            </router-link>
          </div>

          <div class="hidden items-center gap-6 md:flex">
            <!-- Public Links -->
            <template v-if="!authStore.isAuthenticated">
              <router-link
                to="/"
                active-class="text-gold-600 font-semibold"
                class="text-text-light hover:text-gold-600 px-3 py-2 font-medium transition-colors duration-200"
                >Home</router-link
              >
            </template>

            <!-- Authenticated Links -->
            <template v-if="authStore.isAuthenticated">
              <!-- Admin Links -->
              <template v-if="authStore.isAdmin">
                <router-link
                  to="/admin/dashboard"
                  active-class="text-gold-600 font-semibold"
                  class="text-text-light hover:text-gold-600 px-3 py-2 font-medium transition-colors duration-200"
                  >Dashboard</router-link
                >
                <router-link
                  to="/admin/analytics"
                  active-class="text-gold-600 font-semibold"
                  class="text-text-light hover:text-gold-600 px-3 py-2 font-medium transition-colors duration-200"
                  >Analytics</router-link
                >
                <router-link
                  to="/admin/issues"
                  active-class="text-gold-600 font-semibold"
                  class="text-text-light hover:text-gold-600 px-3 py-2 font-medium transition-colors duration-200"
                  >Issues</router-link
                >
                <router-link
                  to="/issues-map"
                  active-class="text-gold-600 font-semibold"
                  class="text-text-light hover:text-gold-600 px-3 py-2 font-medium transition-colors duration-200"
                  >Map</router-link
                >
              </template>

              <template v-else-if="authStore.isCitizen">
                <router-link
                  :to="dashboardRoute"
                  active-class="text-gold-600 font-semibold"
                  class="text-text-light hover:text-gold-600 px-3 py-2 font-medium transition-colors duration-200"
                  >Dashboard</router-link
                >
                <router-link
                  to="/issues"
                  active-class="text-gold-600 font-semibold"
                  class="text-text-light hover:text-gold-600 px-3 py-2 font-medium transition-colors duration-200"
                  >Issues</router-link
                >
                <router-link
                  to="/issues-map"
                  active-class="text-gold-600 font-semibold"
                  class="text-text-light hover:text-gold-600 px-3 py-2 font-medium transition-colors duration-200"
                  >Map</router-link
                >
              </template>

              <!-- Staff Links -->
              <template v-if="authStore.isStaff">
                <router-link
                  to="/staff/dashboard"
                  active-class="text-gold-600 font-semibold"
                  class="text-text-light hover:text-gold-600 px-3 py-2 font-medium transition-colors duration-200"
                  >Staff Dashboard</router-link
                >
                <router-link
                  to="/staff/issues"
                  active-class="text-gold-600 font-semibold"
                  class="text-text-light hover:text-gold-600 px-3 py-2 font-medium transition-colors duration-200"
                  >Manage Issues</router-link
                >
                <router-link
                  to="/staff/reports"
                  active-class="text-gold-600 font-semibold"
                  class="text-text-light hover:text-gold-600 px-3 py-2 font-medium transition-colors duration-200"
                  >Reports</router-link
                >
              </template>

              <!-- Notification Bell -->
              <NotificationBell />

              <!-- Profile Dropdown -->
              <div class="group relative">
                <button
                  class="text-text hover:text-gold-600 flex items-center gap-2 font-medium transition-colors"
                >
                  <span
                    class="bg-gradient-to-r from-gold-200 to-gold-300 text-gold-700 rounded-full px-3 py-1 text-sm font-bold"
                  >
                    {{ userInitials }}
                  </span>
                </button>
                <div
                  class="invisible absolute right-0 z-50 mt-2 w-48 origin-top-right transform rounded-xl border border-gold-200 bg-cream-50 py-1 opacity-0 shadow-lg transition-all duration-200 group-hover:visible group-hover:opacity-100"
                >
                  <router-link
                    to="/profile"
                    class="text-text hover:text-gold-600 block px-4 py-2 text-sm hover:bg-gold-100 transition-colors"
                    >Profile</router-link
                  >
                  <!-- Citizen Specific Links -->
                  <template v-if="authStore.isCitizen">
                    <router-link
                      to="/my-issues"
                      class="text-text hover:text-gold-600 block px-4 py-2 text-sm hover:bg-gold-100 transition-colors"
                      >My Issues</router-link
                    >
                  </template>
                  <button
                    @click="handleLogout"
                    class="text-red-600 hover:bg-red-100 block w-full px-4 py-2 text-left text-sm transition-colors"
                  >
                    Sign out
                  </button>
                </div>
              </div>
            </template>

            <!-- Auth Buttons -->
            <template v-else>
              <div class="flex items-center gap-3">
                <router-link
                  to="/login"
                  class="text-gold-600 hover:text-gold-700 px-4 py-2 font-semibold transition-colors"
                  >Sign In</router-link
                >
                <router-link
                  to="/register"
                  class="btn-primary hover-lift shadow-md rounded-lg px-5 py-2 font-semibold text-white"
                  >Get Started</router-link
                >
              </div>
            </template>
          </div>

          <!-- Mobile menu button -->
          <div class="flex items-center gap-4 md:hidden">
            <NotificationBell v-if="authStore.isAuthenticated" />
            <button
              @click="mobileMenuOpen = !mobileMenuOpen"
              class="text-text rounded-lg p-2 transition-colors hover:bg-gold-100"
            >
              <Bars3Icon class="h-6 w-6" />
            </button>
          </div>
        </div>
      </div>

      <!-- Mobile Menu -->
      <div
        v-if="mobileMenuOpen"
        class="absolute left-0 z-40 w-full border-t border-gold-200 bg-cream-50 shadow-lg md:hidden"
      >
        <div class="space-y-1 px-4 pt-2 pb-6">
          <template v-if="authStore.isAuthenticated">
            <router-link
              v-if="authStore.isCitizen"
              :to="dashboardRoute"
              active-class="text-gold-600 font-semibold"
              class="text-text hover:text-gold-600 block rounded-lg px-3 py-3 text-base font-medium hover:bg-gold-100 transition-colors"
              @click="mobileMenuOpen = false"
              >Dashboard</router-link
            >

            <!-- Citizen Links -->
            <template v-if="authStore.isCitizen">
              <router-link
                to="/issues"
                active-class="text-gold-600 font-semibold"
                class="text-text hover:text-gold-600 block rounded-lg px-3 py-3 text-base font-medium hover:bg-gold-100 transition-colors"
                @click="mobileMenuOpen = false"
                >Issues</router-link
              >
              <router-link
                to="/my-issues"
                active-class="text-gold-600 font-semibold"
                class="text-text hover:text-gold-600 block rounded-lg px-3 py-3 text-base font-medium hover:bg-gold-100 transition-colors"
                @click="mobileMenuOpen = false"
                >My Issues</router-link
              >
            </template>

            <!-- Admin Links -->
            <template v-if="authStore.isAdmin">
              <router-link
                to="/admin/dashboard"
                active-class="text-gold-600 font-semibold"
                class="text-text hover:text-gold-600 block rounded-lg px-3 py-3 text-base font-medium hover:bg-gold-100 transition-colors"
                @click="mobileMenuOpen = false"
                >Dashboard</router-link
              >
              <router-link
                to="/admin/analytics"
                active-class="text-gold-600 font-semibold"
                class="text-text hover:text-gold-600 block rounded-lg px-3 py-3 text-base font-medium hover:bg-gold-100 transition-colors"
                @click="mobileMenuOpen = false"
                >Analytics</router-link
              >
            </template>

            <!-- Staff Links -->
            <template v-if="authStore.isStaff">
              <router-link
                to="/staff/dashboard"
                active-class="text-gold-600 font-semibold"
                class="text-text hover:text-gold-600 block rounded-lg px-3 py-3 text-base font-medium hover:bg-gold-100 transition-colors"
                @click="mobileMenuOpen = false"
                >Staff Dashboard</router-link
              >
              <router-link
                to="/staff/issues"
                active-class="text-gold-600 font-semibold"
                class="text-text hover:text-gold-600 block rounded-lg px-3 py-3 text-base font-medium hover:bg-gold-100 transition-colors"
                @click="mobileMenuOpen = false"
                >Manage Issues</router-link
              >
              <router-link
                to="/staff/reports"
                active-class="text-gold-600 font-semibold"
                class="text-text hover:text-gold-600 block rounded-lg px-3 py-3 text-base font-medium hover:bg-gold-100 transition-colors"
                @click="mobileMenuOpen = false"
                >Reports</router-link
              >
            </template>

            <button
              @click="handleLogout"
              class="text-red-600 hover:bg-red-100 block w-full rounded-lg px-3 py-3 text-left text-base font-medium transition-colors"
            >
              Sign out
            </button>
          </template>
          <template v-else>
            <router-link
              to="/login"
              class="text-text hover:text-gold-600 block rounded-lg px-3 py-3 text-base font-medium hover:bg-gold-100 transition-colors"
              @click="mobileMenuOpen = false"
              >Sign In</router-link
            >
            <router-link
              to="/register"
              class="text-white bg-gradient-to-r from-gold-400 to-gold-600 block rounded-lg px-3 py-3 text-base font-medium transition-colors"
              @click="mobileMenuOpen = false"
              >Get Started</router-link
            >
          </template>
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <main class="grow">
      <slot></slot>
    </main>

    <!-- Footer -->
    <footer class="mt-auto border-t border-gold-200 bg-gradient-to-r from-cream-50 to-cream-100 py-6">
      <div
        class="mx-auto flex w-full max-w-screen-2xl flex-col items-center justify-between gap-6 px-4 sm:px-6 md:flex-row lg:px-8"
      >
        <router-link
          to="/"
          class="flex items-center gap-2 opacity-80 transition-opacity hover:opacity-100"
        >
          <img :src="logoDesktop" alt="CivicConnect" class="h-8 w-auto" />
        </router-link>
        <div class="text-text-light text-sm">
          &copy; {{ new Date().getFullYear() }} CivicConnect. Empowering communities through smart governance.
        </div>
        <div class="flex gap-6">
          <!-- Social placeholders -->
          <!-- Facebook -->
          <a
            href="https://web.facebook.com/yasser.c.ebad"
            class="text-text-lighter hover:text-gold-600 transition-colors"
          >
            <span class="sr-only">Facebook</span>
            <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
              <path
                d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"
              />
            </svg>
          </a>
          <!-- GitHub -->
          <a
            href="https://github.com/DevFusionOrg"
            class="text-text-lighter hover:text-gold-600 transition-colors"
          >
            <span class="sr-only">GitHub</span>
            <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
              <path
                fill-rule="evenodd"
                d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"
                clip-rule="evenodd"
              />
            </svg>
          </a>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useAuthStore } from '../stores/authStore'
import { useRouter } from 'vue-router'
import NotificationBell from '../components/NotificationBell.vue'
import { Bars3Icon } from '@heroicons/vue/24/outline'
import logoDesktop from '@/assets/civic-connect-logo.png'
import logoMobile from '@/assets/civic-connect-logo-mobile.png'

const authStore = useAuthStore()
const router = useRouter()
const mobileMenuOpen = ref(false)

const dashboardRoute = computed(() => {
  if (authStore.isAdmin) return '/admin/dashboard'
  if (authStore.isStaff) return '/staff/dashboard'
  return '/dashboard'
})

const userInitials = computed(() => {
  if (authStore.user?.first_name && authStore.user?.last_name) {
    return (authStore.user.first_name.charAt(0) + authStore.user.last_name.charAt(0)).toUpperCase()
  }
  return 'U'
})

const handleLogout = () => {
  authStore.logout()
  router.push('/login')
  mobileMenuOpen.value = false
}
</script>
