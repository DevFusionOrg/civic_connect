<template>
  <div class="min-h-screen bg-gradient-to-br from-cream-50 to-cream-100">
    <div class="mx-auto max-w-7xl px-4 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="mb-2 text-4xl font-bold text-slate-900">My Issues</h1>
        <p class="text-slate-600">Track the status of issues you have reported</p>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="flex flex-col items-center py-12 text-center">
        <ArrowPathIcon class="h-8 w-8 animate-spin text-gold-700" />
        <p class="mt-4 text-slate-600">Loading your issues...</p>
      </div>

      <!-- Error State -->
      <div
        v-else-if="error"
        class="rounded-xl border border-red-200 bg-red-50 p-8 text-center shadow-md"
      >
        <p class="flex items-center justify-center text-red-800">
          <ExclamationCircleIcon class="mr-2 h-5 w-5" />
          {{ error }}
        </p>
      </div>

      <!-- Empty State -->
      <div
        v-else-if="myIssues.length === 0"
        class="flex flex-col items-center rounded-xl bg-white p-12 text-center shadow-md"
      >
        <InboxIcon class="mb-4 block h-12 w-12 text-slate-600 opacity-50" />
        <p class="text-lg text-slate-600">You haven't reported any issues yet.</p>
        <router-link to="/report-issue" class="mt-4 font-semibold text-gold-700 hover:underline">
          Report an Issue
        </router-link>
      </div>

      <!-- Issues List -->
      <div v-else class="space-y-6">
        <div
          v-for="issue in myIssues"
          :key="issue.id"
          class="group relative overflow-hidden rounded-2xl bg-white shadow-sm transition-all hover:-translate-y-1 hover:shadow-md"
        >
          <!-- Status Strip/Glow (Unique Design) -->
          <div
            class="absolute top-0 left-0 h-full w-1.5 opacity-80"
            :class="{
              'bg-gold-600 shadow-[0_0_10px_rgba(212,175,55,0.5)]':
                issue.status === 'pending_review',
              'bg-saffron-500 shadow-[0_0_10px_rgba(236,72,153,0.5)]': issue.status === 'in_progress',
              'bg-green-500 shadow-[0_0_10px_rgba(34,197,94,0.5)]': issue.status === 'resolved',
            }"
          ></div>

          <div class="flex flex-col md:flex-row">
            <!-- Issue Image -->
            <div class="relative h-48 w-full md:h-auto md:w-64 md:shrink-0">
              <img
                v-if="issue.image_url"
                :src="issue.image_url"
                :alt="issue.title"
                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
              />
              <div
                v-else
                class="flex h-full w-full items-center justify-center bg-cream-100 text-slate-400"
              >
                <PhotoIcon class="h-12 w-12" />
              </div>

              <!-- Status Badge (Overlaid on Image for mobile/desktop harmony) -->
              <div class="absolute top-3 left-3">
                <span
                  :class="{
                    'bg-gold-100/90 text-gold-800 ring-gold-500/30':
                      issue.status === 'pending_review',
                    'bg-saffron-100/90 text-saffron-800 ring-saffron-500/30':
                      issue.status === 'in_progress',
                    'bg-green-100/90 text-green-800 ring-green-500/30': issue.status === 'resolved',
                  }"
                  class="rounded-full px-3 py-1 text-xs font-bold tracking-wide uppercase shadow-sm ring-1 backdrop-blur-sm"
                >
                  {{ issue.status.replace('_', ' ') }}
                </span>
              </div>
            </div>

            <!-- Issue Details -->
            <div class="flex flex-grow flex-col justify-between p-6">
              <div>
                <div class="mb-2 flex items-start justify-between">
                  <span class="text-xs font-medium text-slate-600">
                    Reported {{ formatDate(issue.created_at) }}
                  </span>
                  <div class="flex items-center gap-1">
                    <span
                      class="flex items-center text-xs font-semibold uppercase"
                      :class="{
                        'text-saffron-600': issue.priority === 'high',
                        'text-gold-600': issue.priority === 'medium',
                        'text-slate-600': issue.priority === 'low',
                      }"
                    >
                      <ExclamationCircleIcon class="mr-1 h-3.5 w-3.5" />
                      {{ issue.priority }}
                    </span>
                  </div>
                </div>

                <router-link
                  :to="`/issues/${issue.id}`"
                  class="group-hover:text-primary mb-2 block text-xl font-bold text-slate-900 transition-colors"
                >
                  {{ issue.title }}
                </router-link>

                <p class="mb-4 line-clamp-2 text-sm leading-relaxed text-slate-700">
                  {{ issue.description }}
                </p>

                <div class="flex flex-wrap items-center gap-4 text-sm">
                  <span class="flex items-center rounded-md bg-gold-100 px-2.5 py-1 text-gold-700">
                    <TagIcon class="mr-1.5 h-3.5 w-3.5" />
                    {{ formatCategory(issue.category) }}
                  </span>
                  <span class="flex items-center font-medium text-slate-600">
                    <HandThumbUpIcon class="mr-1.5 h-4 w-4 text-gold-700" />
                    {{ issue.upvote_count || 0 }}
                    <span class="ml-1 hidden sm:inline">Votes</span>
                  </span>
                </div>
              </div>

              <!-- Action (Mobile Full Width) -->
              <div class="mt-6 md:flex md:justify-end">
                <router-link
                  :to="`/issues/${issue.id}`"
                  class="flex w-full items-center justify-center rounded-xl bg-gold-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:bg-gold-700 hover:shadow md:w-auto"
                >
                  View Status
                  <ArrowRightIcon class="ml-2 h-4 w-4" />
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useIssuesStore } from '../../stores/issuesStore'
import { useAuthStore } from '../../stores/authStore'
import {
  ArrowPathIcon,
  InboxIcon,
  PhotoIcon,
  TagIcon,
  ExclamationCircleIcon,
  HandThumbUpIcon,
  ArrowRightIcon,
} from '@heroicons/vue/24/solid'

const issuesStore = useIssuesStore()
const authStore = useAuthStore()

const myIssues = ref([])
const isLoading = ref(false)
const error = ref(null)

const formatCategory = (category) => {
  return issuesStore.formatIssueCategory(category)
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  const today = new Date()
  const yesterday = new Date(today)
  yesterday.setDate(yesterday.getDate() - 1)

  if (date.toDateString() === today.toDateString()) {
    return 'Today'
  } else if (date.toDateString() === yesterday.toDateString()) {
    return 'Yesterday'
  } else {
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
  }
}

const fetchMyIssues = async () => {
  if (!authStore.user?.id) return

  isLoading.value = true
  error.value = null
  try {
    myIssues.value = await issuesStore.fetchUserIssues(authStore.user.id)
  } catch (err) {
    error.value = 'Failed to load your issues.'
    console.error(err)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchMyIssues()
})
</script>
