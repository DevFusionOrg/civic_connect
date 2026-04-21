<template>
  <div class="relative" ref="dropdownContainer">
    <button
      @click="toggleDropdown"
      class="relative rounded-full bg-white p-1 text-gray-400 transition-colors hover:text-gray-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
    >
      <span class="sr-only">View notifications</span>
      <BellIcon class="h-6 w-6" />
      <span
        v-if="unreadCount > 0"
        class="absolute -top-1 -right-1 flex h-5 w-5 animate-pulse items-center justify-center rounded-full bg-red-500 text-xs font-bold text-white"
      >
        {{ unreadCount > 9 ? '9+' : unreadCount }}
      </span>
    </button>

    <!-- Dropdown -->
    <transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-if="isOpen"
        class="ring-opacity-5 absolute right-0 z-50 mt-2 w-80 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black focus:outline-none"
      >
        <div class="border-b border-gray-200 px-4 py-3">
          <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-900">Notifications</h3>
            <button
              v-if="unreadCount > 0"
              @click="handleMarkAllAsRead"
              class="text-xs font-medium text-blue-600 hover:text-blue-700"
            >
              Mark all read
            </button>
          </div>
        </div>

        <div class="max-h-96 overflow-y-auto">
          <div v-if="isLoading" class="p-4 text-center">
            <div
              class="inline-block h-6 w-6 animate-spin rounded-full border-4 border-blue-600 border-t-transparent"
            ></div>
          </div>

          <div v-else-if="notifications.length === 0" class="p-8 text-center text-sm text-gray-500">
            <BellIcon class="mx-auto mb-2 h-12 w-12 text-gray-300" />
            <p>No notifications</p>
          </div>

          <div v-else>
            <router-link
              v-for="notification in notifications"
              :key="notification.id"
              :to="`/issues/${notification.issue_id}`"
              @click="handleMarkAsRead(notification.id)"
              class="block border-b border-gray-100 px-4 py-3 transition-colors last:border-b-0 hover:bg-gray-50"
              :class="{ 'bg-blue-50': !notification.is_read }"
            >
              <div class="flex items-start gap-3">
                <div class="min-w-0 flex-1">
                  <p class="truncate text-sm font-medium text-gray-900">
                    {{ notification.title }}
                  </p>
                  <p class="mt-1 line-clamp-2 text-sm text-gray-600">
                    {{ notification.message }}
                  </p>
                  <p class="mt-1 text-xs text-gray-400">
                    {{ formatDate(notification.created_at) }}
                  </p>
                </div>
                <div v-if="!notification.is_read" class="mt-1 flex-shrink-0">
                  <span class="flex h-2 w-2 rounded-full bg-blue-600"></span>
                </div>
              </div>
            </router-link>
          </div>
        </div>

        <div class="border-t border-gray-200 p-2">
          <router-link
            to="/notifications"
            @click="closeDropdown"
            class="block w-full rounded-md px-3 py-2 text-center text-sm font-medium text-blue-600 transition-colors hover:bg-gray-50"
          >
            View all notifications
          </router-link>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '../stores/authStore'
import { useNotificationsStore } from '../stores/notificationsStore'
import { BellIcon } from '@heroicons/vue/24/outline'

const authStore = useAuthStore()
const notificationsStore = useNotificationsStore()
const isOpen = ref(false)
const dropdownContainer = ref(null)

const isAuthenticated = computed(() => authStore.isAuthenticated)
const unreadCount = computed(() => notificationsStore.unreadCount)
const notifications = computed(() => (notificationsStore.notifications || []).slice(0, 5))
const isLoading = computed(() => notificationsStore.isLoading)

let pollInterval = null

const stopPolling = () => {
  if (pollInterval) {
    clearInterval(pollInterval)
    pollInterval = null
  }
}

const startPolling = () => {
  stopPolling()
  if (!isAuthenticated.value) return

  notificationsStore.fetchUnreadCount()
  pollInterval = setInterval(() => {
    notificationsStore.fetchUnreadCount()
  }, 30000)
}

const toggleDropdown = async () => {
  if (!isAuthenticated.value) return

  isOpen.value = !isOpen.value
  if (isOpen.value) {
    await notificationsStore.fetchNotifications()
  }
}

const closeDropdown = () => {
  isOpen.value = false
}

const handleMarkAsRead = async (notificationId) => {
  await notificationsStore.markAsRead(notificationId)
  closeDropdown()
}

const handleMarkAllAsRead = async () => {
  await notificationsStore.markAllAsRead()
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now - date
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMs / 3600000)
  const diffDays = Math.floor(diffMs / 86400000)

  if (diffMins < 1) return 'Just now'
  if (diffMins < 60) return `${diffMins}m ago`
  if (diffHours < 24) return `${diffHours}h ago`
  if (diffDays < 7) return `${diffDays}d ago`

  return date.toLocaleDateString()
}

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
  if (dropdownContainer.value && !dropdownContainer.value.contains(event.target)) {
    closeDropdown()
  }
}

// Poll for new notifications every 30 seconds
onMounted(() => {
  startPolling()

  document.addEventListener('click', handleClickOutside)
})

watch(isAuthenticated, (authenticated) => {
  if (!authenticated) {
    isOpen.value = false
    notificationsStore.notifications = []
    notificationsStore.unreadCount = 0
  }

  startPolling()
})

onUnmounted(() => {
  stopPolling()
  document.removeEventListener('click', handleClickOutside)
})
</script>
