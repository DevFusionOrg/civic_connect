<template>
  <div class="min-h-screen bg-cream-50 pb-12">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b border-gold-200/60">
      <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-slate-900">Analytics & Reports</h1>
            <p class="mt-1 text-sm text-slate-600">
              Comprehensive system analytics and performance metrics
            </p>
          </div>
          <div class="flex items-center gap-3">
            <span class="text-sm text-slate-600">Last updated: {{ lastUpdated }}</span>
            <button
              @click="refreshData"
              class="flex items-center gap-2 rounded-lg bg-gold-100 px-4 py-2 text-sm font-medium text-gold-800 transition-colors hover:bg-gold-200"
              :disabled="loading"
            >
              <ArrowPathIcon class="h-4 w-4" :class="{ 'animate-spin': loading }" />
              Refresh
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="motion-preset-fade mx-auto mt-8 max-w-7xl px-4 sm:px-6 lg:px-8">
      <!-- Loading State -->
      <div
        v-if="loading && !issuesStore.issues.length"
        class="flex h-96 items-center justify-center"
      >
        <div
          class="h-12 w-12 animate-spin rounded-full border-4 border-gold-600 border-t-transparent"
        ></div>
      </div>

      <!-- Content -->
      <div v-else>
        <!-- Summary Statistics Cards -->
        <div class="mb-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
          <!-- Total Users -->
          <div
            class="motion-preset-slide-up-sm motion-delay-75 overflow-hidden rounded-xl bg-white p-6 shadow-sm border border-gold-200/50"
          >
            <div class="flex items-center gap-4">
              <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-gold-100">
                <UsersIcon class="h-6 w-6 text-gold-700" />
              </div>
              <div>
                <p class="text-sm font-medium text-slate-600">Total Users</p>
                <p class="text-2xl font-bold text-slate-900">{{ stats.users_total }}</p>
              </div>
            </div>
          </div>

          <!-- Total Issues -->
          <div
            class="motion-preset-slide-up-sm motion-delay-100 overflow-hidden rounded-xl bg-white p-6 shadow-sm border border-gold-200/50"
          >
            <div class="flex items-center gap-4">
              <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-saffron-100">
                <ClipboardDocumentListIcon class="h-6 w-6 text-saffron-600" />
              </div>
              <div>
                <p class="text-sm font-medium text-slate-600">Total Issues</p>
                <p class="text-2xl font-bold text-slate-900">{{ stats.issues_total }}</p>
              </div>
            </div>
          </div>

          <!-- Resolution Rate -->
          <div
            class="motion-preset-slide-up-sm motion-delay-150 overflow-hidden rounded-xl bg-white p-6 shadow-sm border border-gold-200/50"
          >
            <div class="flex items-center gap-4">
              <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-green-50">
                <ChartBarIcon class="h-6 w-6 text-green-700" />
              </div>
              <div>
                <p class="text-sm font-medium text-slate-600">Resolution Rate</p>
                <p class="text-2xl font-bold text-slate-900">{{ resolutionRate }}%</p>
              </div>
            </div>
          </div>

          <!-- Active Staff -->
          <div
            class="motion-preset-slide-up-sm motion-delay-200 overflow-hidden rounded-xl bg-white p-6 shadow-sm border border-gold-200/50"
          >
            <div class="flex items-center gap-4">
              <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-gold-100">
                <UserGroupIcon class="h-6 w-6 text-gold-700" />
              </div>
              <div>
                <p class="text-sm font-medium text-slate-600">Staff Members</p>
                <p class="text-2xl font-bold text-slate-900">
                  {{ stats.users_by_role?.staff || 0 }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Charts Grid - Row 1 -->
        <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
          <!-- Issues by Status Chart -->
          <div
            class="motion-preset-slide-up-sm motion-delay-300 overflow-hidden rounded-xl bg-white p-6 shadow-sm border border-gold-200/50"
          >
            <h3 class="mb-4 text-lg font-semibold text-slate-900">Issues by Status</h3>
            <div class="flex items-center justify-center" style="height: 300px">
              <Doughnut v-if="statusChartData" :data="statusChartData" :options="doughnutOptions" />
              <p v-else class="text-sm text-slate-600">No data available</p>
            </div>
          </div>

          <!-- Users by Role Chart -->
          <div
            class="motion-preset-slide-up-sm motion-delay-400 overflow-hidden rounded-xl bg-white p-6 shadow-sm border border-gold-200/50"
          >
            <h3 class="mb-4 text-lg font-semibold text-slate-900">Users by Role</h3>
            <div class="flex items-center justify-center" style="height: 300px">
              <Pie v-if="userRoleChartData" :data="userRoleChartData" :options="pieOptions" />
              <p v-else class="text-sm text-slate-600">No data available</p>
            </div>
          </div>
        </div>

        <!-- Charts Grid - Row 2 -->
        <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
          <!-- Issues by Category Chart -->
          <div
            class="motion-preset-slide-up-sm motion-delay-500 overflow-hidden rounded-xl bg-white p-6 shadow-sm border border-gold-200/50"
          >
            <h3 class="mb-4 text-lg font-semibold text-slate-900">Issues by Category</h3>
            <div class="flex items-center justify-center" style="height: 300px">
              <Bar v-if="categoryChartData" :data="categoryChartData" :options="barOptions" />
              <p v-else class="text-sm text-slate-600">No data available</p>
            </div>
          </div>

          <!-- Issues by Priority Chart -->
          <div
            class="motion-preset-slide-up-sm motion-delay-600 overflow-hidden rounded-xl bg-white p-6 shadow-sm border border-gold-200/50"
          >
            <h3 class="mb-4 text-lg font-semibold text-slate-900">Issues by Priority</h3>
            <div class="flex items-center justify-center" style="height: 300px">
              <Doughnut
                v-if="priorityChartData"
                :data="priorityChartData"
                :options="doughnutOptions"
              />
              <p v-else class="text-sm text-slate-600">No data available</p>
            </div>
          </div>
        </div>

        <!-- Issues Timeline Chart - Full Width -->
        <div
          class="motion-preset-slide-up-sm motion-delay-700 overflow-hidden rounded-xl bg-white p-6 shadow-sm border border-gold-200/50"
        >
          <h3 class="mb-4 text-lg font-semibold text-slate-900">Issues Timeline (Last 30 Days)</h3>
          <div class="flex items-center justify-center" style="height: 300px">
            <Line v-if="timelineChartData" :data="timelineChartData" :options="lineOptions" />
            <p v-else class="text-sm text-slate-600">No data available</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useIssuesStore } from '../../stores/issuesStore'
import axios from 'axios'
import {
  ClipboardDocumentListIcon,
  ArrowPathIcon,
  ChartBarIcon,
  UsersIcon,
  UserGroupIcon,
} from '@heroicons/vue/24/outline'
import {
  Chart as ChartJS,
  ArcElement,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  Filler,
} from 'chart.js'
import { Doughnut, Bar, Line, Pie } from 'vue-chartjs'

// Register Chart.js components
ChartJS.register(
  ArcElement,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  Filler,
)

const issuesStore = useIssuesStore()
const lastUpdated = ref(new Date().toLocaleTimeString())
const loading = ref(false)

// Admin stats from API
const stats = ref({
  users_total: 0,
  issues_total: 0,
  users_by_role: {},
  issues_by_status: {},
  recent_activity_count: 0,
})

// Computed Statistics
const resolutionRate = computed(() => {
  const issues = issuesStore.issues
  const total = issues.length
  if (total === 0) return 0
  const resolved = issues.filter((i) => i.status === 'resolved').length
  return Math.round((resolved / total) * 100)
})

// Status Chart Data
const statusChartData = computed(() => {
  const issues = issuesStore.issues
  if (!issues.length) return null

  const statusCounts = {
    pending_review: issues.filter((i) => i.status === 'pending_review').length,
    in_progress: issues.filter((i) => i.status === 'in_progress').length,
    resolved: issues.filter((i) => i.status === 'resolved').length,
  }

  return {
    labels: ['Pending Review', 'In Progress', 'Resolved'],
    datasets: [
      {
        data: [statusCounts.pending_review, statusCounts.in_progress, statusCounts.resolved],
        backgroundColor: [
          'rgba(212, 175, 55, 0.82)',
          'rgba(255, 153, 51, 0.82)',
          'rgba(34, 197, 94, 0.8)',
        ],
        borderColor: ['rgba(212, 175, 55, 1)', 'rgba(255, 153, 51, 1)', 'rgba(34, 197, 94, 1)'],
        borderWidth: 2,
      },
    ],
  }
})

// User Role Chart Data
const userRoleChartData = computed(() => {
  const roles = stats.value.users_by_role
  if (!roles || Object.keys(roles).length === 0) return null

  const labels = Object.keys(roles).map((role) => role.charAt(0).toUpperCase() + role.slice(1))
  const data = Object.values(roles)

  return {
    labels,
    datasets: [
      {
        data,
        backgroundColor: [
          'rgba(212, 175, 55, 0.82)',
          'rgba(255, 153, 51, 0.82)',
          'rgba(13, 61, 86, 0.82)',
        ],
        borderColor: ['rgba(212, 175, 55, 1)', 'rgba(255, 153, 51, 1)', 'rgba(13, 61, 86, 1)'],
        borderWidth: 2,
      },
    ],
  }
})

// Category Chart Data
const categoryChartData = computed(() => {
  const issues = issuesStore.issues
  if (!issues.length) return null

  const categoryCounts = {}
  issues.forEach((issue) => {
    const category = issue.category || 'unknown'
    categoryCounts[category] = (categoryCounts[category] || 0) + 1
  })

  const sortedCategories = Object.entries(categoryCounts)
    .filter(([, count]) => count > 0)
    .sort((a, b) => b[1] - a[1])

  if (sortedCategories.length === 0) return null

  const labels = sortedCategories.map(([cat]) =>
    cat
      .split('_')
      .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
      .join(' '),
  )

  const data = sortedCategories.map(([, count]) => count)

  return {
    labels,
    datasets: [
      {
        label: 'Number of Issues',
        data,
        backgroundColor: 'rgba(212, 175, 55, 0.85)',
        borderColor: 'rgba(212, 175, 55, 1)',
        borderWidth: 2,
      },
    ],
  }
})

// Priority Chart Data
const priorityChartData = computed(() => {
  const issues = issuesStore.issues
  if (!issues.length) return null

  const priorityCounts = {
    low: issues.filter((i) => i.priority === 'low').length,
    medium: issues.filter((i) => i.priority === 'medium').length,
    high: issues.filter((i) => i.priority === 'high').length,
  }

  return {
    labels: ['Low', 'Medium', 'High'],
    datasets: [
      {
        data: [priorityCounts.low, priorityCounts.medium, priorityCounts.high],
        backgroundColor: [
          'rgba(34, 197, 94, 0.8)',
          'rgba(212, 175, 55, 0.82)',
          'rgba(255, 153, 51, 0.85)',
        ],
        borderColor: ['rgba(34, 197, 94, 1)', 'rgba(212, 175, 55, 1)', 'rgba(255, 153, 51, 1)'],
        borderWidth: 2,
      },
    ],
  }
})

// Timeline Chart Data (Last 30 Days)
const timelineChartData = computed(() => {
  const issues = issuesStore.issues
  if (!issues.length) return null

  const days = []
  const counts = []
  const today = new Date()

  for (let i = 29; i >= 0; i--) {
    const date = new Date(today)
    date.setDate(date.getDate() - i)
    const dateStr = date.toISOString().split('T')[0]
    days.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }))

    const count = issues.filter((issue) => {
      const issueDate = new Date(issue.created_at).toISOString().split('T')[0]
      return issueDate === dateStr
    }).length

    counts.push(count)
  }

  return {
    labels: days,
    datasets: [
      {
        label: 'Issues Created',
        data: counts,
        fill: true,
        backgroundColor: 'rgba(212, 175, 55, 0.14)',
        borderColor: 'rgba(212, 175, 55, 1)',
        borderWidth: 2,
        tension: 0.4,
        pointRadius: 3,
        pointHoverRadius: 5,
        pointBackgroundColor: 'rgba(212, 175, 55, 1)',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
      },
    ],
  }
})

// Chart Options
const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom',
      labels: {
        padding: 15,
        font: {
          size: 12,
          family: "'Inter', sans-serif",
        },
      },
    },
    tooltip: {
      backgroundColor: 'rgba(0, 0, 0, 0.8)',
      padding: 12,
      titleFont: {
        size: 14,
        family: "'Inter', sans-serif",
      },
      bodyFont: {
        size: 13,
        family: "'Inter', sans-serif",
      },
      cornerRadius: 8,
    },
  },
}

const pieOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom',
      labels: {
        padding: 15,
        font: {
          size: 12,
          family: "'Inter', sans-serif",
        },
      },
    },
    tooltip: {
      backgroundColor: 'rgba(0, 0, 0, 0.8)',
      padding: 12,
      titleFont: {
        size: 14,
        family: "'Inter', sans-serif",
      },
      bodyFont: {
        size: 13,
        family: "'Inter', sans-serif",
      },
      cornerRadius: 8,
    },
  },
}

const barOptions = {
  responsive: true,
  maintainAspectRatio: false,
  indexAxis: 'y',
  plugins: {
    legend: {
      display: false,
    },
    tooltip: {
      backgroundColor: 'rgba(0, 0, 0, 0.8)',
      padding: 12,
      titleFont: {
        size: 14,
        family: "'Inter', sans-serif",
      },
      bodyFont: {
        size: 13,
        family: "'Inter', sans-serif",
      },
      cornerRadius: 8,
    },
  },
  scales: {
    x: {
      beginAtZero: true,
      ticks: {
        precision: 0,
        font: {
          size: 11,
          family: "'Inter', sans-serif",
        },
      },
      grid: {
        color: 'rgba(0, 0, 0, 0.05)',
      },
    },
    y: {
      ticks: {
        font: {
          size: 11,
          family: "'Inter', sans-serif",
        },
      },
      grid: {
        display: false,
      },
    },
  },
}

const lineOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false,
    },
    tooltip: {
      backgroundColor: 'rgba(0, 0, 0, 0.8)',
      padding: 12,
      titleFont: {
        size: 14,
        family: "'Inter', sans-serif",
      },
      bodyFont: {
        size: 13,
        family: "'Inter', sans-serif",
      },
      cornerRadius: 8,
    },
  },
  scales: {
    x: {
      ticks: {
        maxRotation: 45,
        minRotation: 45,
        font: {
          size: 10,
          family: "'Inter', sans-serif",
        },
      },
      grid: {
        color: 'rgba(0, 0, 0, 0.05)',
      },
    },
    y: {
      beginAtZero: true,
      ticks: {
        precision: 0,
        font: {
          size: 11,
          family: "'Inter', sans-serif",
        },
      },
      grid: {
        color: 'rgba(0, 0, 0, 0.05)',
      },
    },
  },
}

const fetchAdminStats = async () => {
  try {
    const token = localStorage.getItem('token')
    const response = await axios.get('http://localhost/civic-connect/backend/api/admin/stats', {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
    if (response.data.success) {
      stats.value = response.data.stats
    }
  } catch (err) {
    console.error('Error fetching admin stats:', err)
  }
}

const refreshData = async () => {
  loading.value = true
  // Fetch all issues for accurate analytics (not paginated)
  await Promise.all([issuesStore.fetchIssues({ limit: 100 }), fetchAdminStats()])
  lastUpdated.value = new Date().toLocaleTimeString()
  loading.value = false
}

onMounted(() => {
  refreshData()
})
</script>
