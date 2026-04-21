<template>
  <div class="min-h-screen bg-cream-50">
    <div class="bg-white shadow">
      <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-bold text-slate-900">Notifications</h1>
      </div>
    </div>

    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
      <div class="mb-6 flex items-center justify-between">
        <div class="flex gap-2">
          <button
            @click="filterType = 'all'"
            :class="[
              'rounded-md px-4 py-2 text-sm font-medium transition-colors',
              filterType === 'all'
                ? 'bg-gold-600 text-white shadow-sm'
                : 'border border-gold-200 bg-white text-slate-700 hover:bg-gold-50',
            ]"
          >
            All
          </button>
          <button
            @click="filterType = 'unread'"
            :class="[
              'rounded-md px-4 py-2 text-sm font-medium transition-colors',
              filterType === 'unread'
                ? 'bg-gold-600 text-white shadow-sm'
                : 'border border-gold-200 bg-white text-slate-700 hover:bg-gold-50',
            ]"
          >
            Unread
            <span
              v-if="unreadCount > 0"
              class="ml-2 inline-flex items-center justify-center rounded-full bg-gold-100 px-2 py-0.5 text-xs font-bold text-gold-600"
            >
              {{ unreadCount }}
            </span>
          </button>
        </div>

        <button
          v-if="unreadCount > 0"
          @click="markAllAsRead"
          class="text-sm font-medium text-gold-600 transition-colors hover:text-gold-700"
        >
          Mark all as read
        </button>
      </div>

      <div v-if="isLoading" class="flex items-center justify-center py-12">
        <div
          class="h-8 w-8 animate-spin rounded-full border-4 border-gold-600 border-t-transparent"
        ></div>
      </div>

      <div
        v-else-if="notifications.length === 0"
        class="rounded-lg bg-white p-12 text-center shadow"
      >
        <BellIcon class="mx-auto mb-4 h-16 w-16 text-slate-400" />
        <h3 class="mb-2 text-lg font-medium text-slate-900">No notifications</h3>
        <p class="text-sm text-slate-600">You're all caught up!</p>
      </div>

      <div v-else class="space-y-3">
        <router-link
          v-for="notification in notifications"
          :key="notification.id"
          :to="`/issues/${notification.issue_id}`"
          @click="markAsRead(notification.id)"
          class="block rounded-lg bg-white p-5 shadow transition-all hover:shadow-md"
          :class="{ 'border-l-4 border-gold-600': !notification.is_read }"
        >
          <div class="flex items-start justify-between gap-4">
            <div class="min-w-0 flex-1">
              <div class="mb-2 flex items-center gap-2">
                <h3 class="text-base font-semibold text-slate-900">
                  {{ notification.title }}
                </h3>
                <span
                  v-if="!notification.is_read"
                  class="flex h-2 w-2 rounded-full bg-gold-600"
                ></span>
              </div>
              <p class="mb-3 text-sm text-slate-600">{{ notification.message }}</p>

              <div class="flex items-center gap-4 text-xs text-slate-600">
                <span class="flex items-center gap-1">
                  <ClockIcon class="h-4 w-4" />
                  {{ formatDate(notification.created_at) }}
                </span>
                <span v-if="notification.issue_title" class="flex items-center gap-1 truncate">
                  <DocumentTextIcon class="h-4 w-4" />
                  {{ notification.issue_title }}
                </span>
              </div>
            </div>

            <div class="shrink-0">
              <ChevronRightIcon class="h-5 w-5 text-slate-400" />
            </div>
          </div>
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useNotificationsStore } from '../../stores/notificationsStore'
import { BellIcon, ClockIcon, DocumentTextIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'

const notificationsStore = useNotificationsStore()
const filterType = ref('all')

const notifications = computed(() => notificationsStore.notifications)
const unreadCount = computed(() => notificationsStore.unreadCount)
const isLoading = computed(() => notificationsStore.isLoading)

const fetchNotifications = async () => {
  await notificationsStore.fetchNotifications(filterType.value === 'unread')
}

const markAsRead = async (notificationId) => {
  await notificationsStore.markAsRead(notificationId)
}

const markAllAsRead = async () => {
  await notificationsStore.markAllAsRead()
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleString(undefined, {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

watch(filterType, () => {
  fetchNotifications()
})

onMounted(() => {
  fetchNotifications()
})
</script>
