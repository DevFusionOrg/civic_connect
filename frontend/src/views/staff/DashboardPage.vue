<template>
  <div class="min-h-screen bg-gradient-to-br from-cream-50 via-white to-gold-50 pb-12">
    <!-- Header with Welcome -->
    <div class="border-b border-gold-200/60 bg-white/80 backdrop-blur-sm">
      <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex items-end justify-between gap-4">
          <div>
            <h1 class="text-3xl font-bold text-slate-900">Staff Dashboard</h1>
            <p class="mt-2 text-base text-slate-600">
              Welcome back, <span class="font-semibold text-gold-700">{{ authStore.user?.first_name || 'Staff' }}</span>.
              You have <span class="font-bold text-gold-600">{{ assignedIssuesStats.total }}</span> assigned issues to manage.
            </p>
          </div>
          <button
            @click="refreshData"
            class="flex items-center gap-2 rounded-lg bg-gold-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-gold-700 disabled:opacity-50"
            :disabled="isLoading"
          >
            <ArrowPathIcon class="h-4 w-4" :class="{ 'animate-spin': isLoading }" />
            Refresh
          </button>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="mx-auto mt-8 max-w-7xl px-4 sm:px-6 lg:px-8">
      <!-- Assigned Issues Overview Grid -->
      <div class="mb-8 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
        <!-- Total Assigned -->
        <div class="group overflow-hidden rounded-xl bg-gradient-to-br from-gold-500 to-gold-600 p-6 text-white shadow-md transition-shadow hover:shadow-lg">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gold-100">Total Assigned</p>
              <p class="mt-2 text-3xl font-bold">{{ assignedIssuesStats.total }}</p>
              <p class="mt-1 text-xs text-gold-100">issues to handle</p>
            </div>
            <div class="opacity-20">
              <ClipboardDocumentListIcon class="h-12 w-12" />
            </div>
          </div>
        </div>

        <!-- Pending (Not Started) -->
        <div class="group overflow-hidden rounded-xl bg-gradient-to-br from-gold-500 to-saffron-600 p-6 text-white shadow-md transition-shadow hover:shadow-lg">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gold-100">Pending</p>
              <p class="mt-2 text-3xl font-bold">{{ assignedIssuesStats.pending }}</p>
              <p class="mt-1 text-xs text-gold-100">waiting action</p>
            </div>
            <div class="opacity-20">
              <ExclamationCircleIcon class="h-12 w-12" />
            </div>
          </div>
        </div>

        <!-- In Progress -->
        <div class="group overflow-hidden rounded-xl bg-gradient-to-br from-saffron-500 to-saffron-600 p-6 text-white shadow-md transition-shadow hover:shadow-lg">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-saffron-100">In Progress</p>
              <p class="mt-2 text-3xl font-bold">{{ assignedIssuesStats.in_progress }}</p>
              <p class="mt-1 text-xs text-saffron-100">being handled</p>
            </div>
            <div class="opacity-20">
              <ClockIcon class="h-12 w-12" />
            </div>
          </div>
        </div>

        <!-- Resolved -->
        <div class="group overflow-hidden rounded-xl bg-gradient-to-br from-green-500 to-green-600 p-6 text-white shadow-md transition-shadow hover:shadow-lg">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-green-100">Resolved</p>
              <p class="mt-2 text-3xl font-bold">{{ assignedIssuesStats.resolved }}</p>
              <p class="mt-1 text-xs text-green-100">completed</p>
            </div>
            <div class="opacity-20">
              <CheckCircleIcon class="h-12 w-12" />
            </div>
          </div>
        </div>
      </div>

      <!-- Performance & Analytics Row -->
      <div class="mb-8 grid grid-cols-1 gap-4 md:grid-cols-3">
        <!-- Completion Rate -->
        <div class="rounded-xl bg-white p-6 shadow-md border border-gold-200/50">
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm font-medium text-slate-600">Completion Rate</p>
              <p class="mt-3 text-3xl font-bold text-slate-900">{{ completionRate }}%</p>
              <p class="mt-2 text-xs text-slate-600">
                <span class="text-green-600 font-semibold">{{ assignedIssuesStats.resolved }}</span> of 
                <span>{{ assignedIssuesStats.total }}</span> completed
              </p>
            </div>
            <div class="rounded-lg bg-gradient-to-br from-gold-50 to-green-50 p-3">
              <CheckCircleIcon class="h-8 w-8 text-green-600" />
            </div>
          </div>
          <!-- Mini progress bar -->
          <div class="mt-4 h-2 rounded-full bg-gold-100">
            <div 
              class="h-2 rounded-full bg-gradient-to-r from-gold-500 to-green-500 transition-all" 
              :style="{ width: completionRate + '%' }"
            ></div>
          </div>
        </div>

        <!-- Average Response Time (Mock) -->
        <div class="rounded-xl bg-white p-6 shadow-md border border-gold-200/50">
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm font-medium text-slate-600">Avg Response Time</p>
              <p class="mt-3 text-3xl font-bold text-slate-900">{{ averageResponseTime }}h</p>
              <p class="mt-2 text-xs text-slate-600">
                <span class="text-saffron-600 font-semibold">{{ assignedIssuesStats.in_progress }}</span> issues 
                being actively worked
              </p>
            </div>
            <div class="rounded-lg bg-gradient-to-br from-saffron-50 to-gold-50 p-3">
              <ClockIcon class="h-8 w-8 text-saffron-600" />
            </div>
          </div>
        </div>

        <!-- Pending Actions (Critical) -->
        <div class="rounded-xl bg-white p-6 shadow-md border border-gold-200/50">
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm font-medium text-slate-600">Urgent Action</p>
              <p class="mt-3 text-3xl font-bold text-saffron-600">{{ urgentCount }}</p>
              <p class="mt-2 text-xs text-slate-600">
                <span class="text-saffron-600 font-semibold">high priority</span> issues 
                need attention
              </p>
            </div>
            <div class="rounded-lg bg-gradient-to-br from-gold-50 to-saffron-50 p-3">
              <ExclamationCircleIcon class="h-8 w-8 text-saffron-600" />
            </div>
          </div>
        </div>
      </div>

      <!-- Assigned Issues Section -->
      <div class="rounded-xl bg-white shadow-md border border-gold-200/50">
        <!-- Section Header -->
        <div class="border-b border-gold-200/60 bg-gradient-to-r from-gold-50 to-cream-50 px-6 py-5">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="rounded-lg bg-gold-100 p-2">
                <CheckCircleIcon class="h-5 w-5 text-gold-700" />
              </div>
              <div>
                <h2 class="text-lg font-bold text-slate-900">My Assigned Issues</h2>
                <p class="text-sm text-slate-600">Issues assigned to you for management and resolution</p>
              </div>
            </div>
            <div class="text-right">
              <p class="text-2xl font-bold text-slate-900">{{ assignedIssuesStats.total }}</p>
              <p class="text-xs text-slate-600">total issues</p>
            </div>
          </div>
        </div>

        <!-- Filters Section -->
        <div class="border-b border-gold-200/60 bg-cream-50 px-6 py-4">
          <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-3">
              <!-- Search -->
              <div class="relative">
                <MagnifyingGlassIcon class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gold-400" />
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="Search issues..."
                  class="rounded-lg border border-gold-200 bg-white py-2 pr-4 pl-10 text-sm text-slate-800 focus:border-gold-400 focus:outline-none focus:ring-2 focus:ring-gold-200"
                />
              </div>

              <!-- Category Filter -->
              <select
                v-model="selectedCategory"
                class="rounded-lg border border-gold-200 bg-white py-2 px-3 text-sm text-slate-800 focus:border-gold-400 focus:outline-none focus:ring-2 focus:ring-gold-200"
              >
                <option value="">All Categories</option>
                <option value="infrastructure">Infrastructure</option>
                <option value="public_safety">Public Safety</option>
                <option value="environment">Environment</option>
                <option value="health">Health</option>
                <option value="other">Other</option>
              </select>
            </div>

            <!-- Status Pills -->
            <div class="flex items-center gap-2 flex-wrap">
              <button
                v-for="status in statusOptions"
                :key="status.value"
                @click="activeStatusFilter = status.value"
                :class="[
                  'rounded-full px-3 py-1.5 text-sm font-medium transition-colors',
                  activeStatusFilter === status.value
                    ? 'bg-gold-600 text-white shadow-sm'
                    : 'bg-white text-slate-600 border border-gold-200 hover:bg-gold-50',
                ]"
              >
                {{ status.label }}
              </button>
            </div>
          </div>
        </div>

        <!-- Issues Table/List -->
        <div v-if="isLoading && !filteredAssignedIssues.length" class="flex h-64 items-center justify-center">
          <div class="text-center">
            <div class="inline-flex items-center justify-center rounded-full bg-gold-100 p-3">
              <div class="h-8 w-8 animate-spin rounded-full border-4 border-gold-600 border-t-transparent"></div>
            </div>
            <p class="mt-2 text-sm text-slate-600">Loading your assigned issues...</p>
          </div>
        </div>

        <div v-else-if="filteredAssignedIssues.length === 0" class="flex h-64 flex-col items-center justify-center px-6 py-12 text-center">
          <div class="rounded-full bg-gold-50 p-3">
            <InboxIcon class="h-8 w-8 text-gold-400" />
          </div>
          <h3 class="mt-4 text-lg font-semibold text-slate-900">No issues assigned</h3>
          <p class="mt-2 text-sm text-slate-600">
            <span v-if="assignedIssuesStats.total === 0">You don't have any assigned issues yet. Check back soon!</span>
            <span v-else>No issues match your current filters.</span>
          </p>
        </div>

        <div v-else class="divide-y divide-gold-100/70">
          <div
            v-for="issue in filteredAssignedIssues"
            :key="issue.id"
            class="group flex flex-col gap-3 border-b border-gold-100/70 p-6 transition-colors hover:bg-gold-50/40 last:border-b-0 md:flex-row md:items-center md:justify-between"
          >
            <!-- Left side: Issue Info -->
            <div class="flex-1 min-w-0">
              <div class="flex items-start gap-4">
                <!-- Issue Image/Icon -->
                <div class="flex-shrink-0">
                  <div v-if="issue.image_url" class="h-12 w-12 rounded-lg overflow-hidden">
                    <img :src="issue.image_url" :alt="issue.title" class="h-full w-full object-cover" />
                  </div>
                  <div v-else class="flex h-12 w-12 items-center justify-center rounded-lg bg-gold-100">
                    <PhotoIcon class="h-6 w-6 text-gold-700" />
                  </div>
                </div>

                <!-- Issue Details -->
                <div class="min-w-0 flex-1">
                  <div class="flex items-start justify-between gap-2">
                    <div class="flex-1 min-w-0">
                      <router-link
                        :to="`/staff/issues/${issue.id}`"
                        class="block text-sm font-bold text-slate-900 hover:text-gold-700 truncate transition-colors"
                      >
                        {{ issue.title }}
                      </router-link>
                      <p class="mt-1 line-clamp-1 max-w-2xl text-xs text-slate-600">
                        {{ issue.description }}
                      </p>
                    </div>
                  </div>

                  <!-- Meta Info -->
                  <div class="mt-2 flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center rounded-full bg-gold-100 px-2.5 py-0.5 text-xs font-medium text-gold-800">
                      {{ formatCategory(issue.category) }}
                    </span>
                    <span :class="[getPriorityColor(issue.priority), 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium']">
                      {{ formatPriority(issue.priority) }}
                    </span>
                    <span class="text-xs text-slate-600">
                      📅 {{ formatDate(issue.created_at) }}
                    </span>
                    <span class="text-xs text-slate-600">
                      👍 {{ issue.upvote_count }} votes
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Right side: Status & Actions -->
            <div class="flex items-center justify-between gap-4 md:justify-end md:flex-row">
              <div class="flex items-center gap-3">
                <!-- Status Badge -->
                <span :class="[getStatusColor(issue.status), 'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold min-w-fit']">
                  {{ formatStatus(issue.status) }}
                </span>
              </div>

              <!-- Quick Actions -->
              <div class="flex items-center gap-2">
                <router-link
                  :to="`/staff/issues/${issue.id}`"
                  class="flex items-center gap-1 rounded-lg bg-gold-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-gold-700"
                >
                  <PencilIcon class="h-4 w-4" />
                  <span class="hidden sm:inline">Manage</span>
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer Stats -->
      <div class="mt-8 rounded-xl bg-white px-6 py-4 shadow-md border border-gold-200/50">
        <div class="flex flex-col gap-2 text-xs text-slate-600 sm:flex-row sm:items-center sm:justify-between">
          <div>
            Showing <span class="font-semibold text-slate-900">{{ filteredAssignedIssues.length }}</span> of 
            <span class="font-semibold text-slate-900">{{ assignedIssuesStats.total }}</span> assigned issues
          </div>
          <div class="text-right">
            Last updated: <span class="font-medium text-slate-900">{{ lastUpdated }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useIssuesStore } from '../../stores/issuesStore'
import { useAuthStore } from '../../stores/authStore'
import { API_BASE_URL } from '../../config/api'
import {
  ClipboardDocumentListIcon,
  ExclamationCircleIcon,
  ClockIcon,
  CheckCircleIcon,
  ArrowPathIcon,
  MagnifyingGlassIcon,
  PhotoIcon,
  PencilIcon,
  InboxIcon,
} from '@heroicons/vue/24/outline'

const issuesStore = useIssuesStore()
const authStore = useAuthStore()

// State
const isLoading = ref(false)
const searchQuery = ref('')
const selectedCategory = ref('')
const activeStatusFilter = ref('all')
const lastUpdated = ref(new Date().toLocaleTimeString())
const allAssignedIssues = ref([])

// Status options
const statusOptions = [
  { label: 'All', value: 'all' },
  { label: 'Pending', value: 'pending_review' },
  { label: 'In Progress', value: 'in_progress' },
  { label: 'Resolved', value: 'resolved' },
]

// Computed: Calculate stats for assigned issues
const assignedIssuesStats = computed(() => {
  const issues = allAssignedIssues.value
  return {
    total: issues.length,
    pending: issues.filter(i => i.status === 'pending_review').length,
    in_progress: issues.filter(i => i.status === 'in_progress').length,
    resolved: issues.filter(i => i.status === 'resolved').length,
  }
})

// Computed: Completion rate percentage
const completionRate = computed(() => {
  const stats = assignedIssuesStats.value
  return stats.total === 0 ? 0 : Math.round((stats.resolved / stats.total) * 100)
})

// Computed: Average response time (mock - in real scenario would be calculated from timestamps)
const averageResponseTime = computed(() => {
  const inProgress = assignedIssuesStats.value.in_progress
  return inProgress > 0 ? (inProgress * 2) : 1 // Mock calculation
})

// Computed: Urgent/critical issues count
const urgentCount = computed(() => {
  return allAssignedIssues.value.filter(i => i.priority === 'critical' || i.priority === 'high').length
})

// Computed: Filtered assigned issues based on search and filters
const filteredAssignedIssues = computed(() => {
  let result = [...allAssignedIssues.value]

  // Status filter
  if (activeStatusFilter.value !== 'all') {
    result = result.filter(i => i.status === activeStatusFilter.value)
  }

  // Category filter
  if (selectedCategory.value) {
    result = result.filter(i => i.category === selectedCategory.value)
  }

  // Search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(i => 
      i.title.toLowerCase().includes(query) || 
      i.description.toLowerCase().includes(query)
    )
  }

  // Sort by priority (critical first) then by creation date
  return result.sort((a, b) => {
    const priorityOrder = { critical: 0, high: 1, medium: 2, low: 3 }
    const aPriority = priorityOrder[a.priority] || 4
    const bPriority = priorityOrder[b.priority] || 4
    
    if (aPriority !== bPriority) {
      return aPriority - bPriority
    }
    
    return new Date(b.created_at) - new Date(a.created_at)
  })
})

// Formatting helpers
const formatCategory = (category) => {
  return issuesStore.formatIssueCategory(category)
}

const formatStatus = (status) => {
  const statusMap = {
    'pending_review': 'Pending Review',
    'in_progress': 'In Progress',
    'resolved': 'Resolved'
  }
  return statusMap[status] || status
}

const getStatusColor = (status) => {
  switch (status) {
    case 'pending_review':
      return 'bg-gold-100 text-gold-800'
    case 'in_progress':
      return 'bg-saffron-100 text-saffron-800'
    case 'resolved':
      return 'bg-green-100 text-green-800'
    default:
      return 'bg-cream-100 text-slate-700'
  }
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

const formatPriority = (priority) => {
  const priorityMap = {
    'low': 'Low Priority',
    'medium': 'Medium Priority',
    'high': 'High Priority',
    'critical': 'Critical'
  }
  return priorityMap[priority] || priority
}

const getPriorityColor = (priority) => {
  switch (priority) {
    case 'low':
      return 'bg-gold-100 text-gold-800'
    case 'medium':
      return 'bg-saffron-100 text-saffron-800'
    case 'high':
      return 'bg-saffron-200 text-saffron-900'
    case 'critical':
      return 'bg-red-100 text-red-800'
    default:
      return 'bg-cream-100 text-slate-700'
  }
}

const refreshData = async () => {
  await loadAssignedIssues()
  lastUpdated.value = new Date().toLocaleTimeString()
}

const loadAssignedIssues = async () => {
  isLoading.value = true
  try {
    const response = await fetch(
      `${API_BASE_URL}/issues?assigned_to_me=true&limit=100`,
      {
        headers: {
          'Authorization': `Bearer ${authStore.token}`
        }
      }
    )
    
    if (response.ok) {
      const data = await response.json()
      allAssignedIssues.value = data.issues || []
    }
  } catch (error) {
    console.error('Failed to load assigned issues:', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  refreshData()
})

</script>
