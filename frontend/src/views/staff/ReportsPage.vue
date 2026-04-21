<template>
  <div class="min-h-screen bg-cream-50 pb-12">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b border-gold-200/60">
      <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-slate-900">Reports & Analytics</h1>
            <p class="mt-1 text-sm text-slate-600">
              Comprehensive overview of issue statistics and trends
            </p>
          </div>
          <div class="flex items-center gap-3">
            <span class="text-sm text-slate-600">Last updated: {{ lastUpdated }}</span>
            <button
              @click="refreshData"
              class="flex items-center gap-2 rounded-lg bg-gold-100 px-4 py-2 text-sm font-medium text-gold-800 transition-colors hover:bg-gold-200"
              :disabled="issuesStore.isLoading"
            >
              <ArrowPathIcon class="h-4 w-4" :class="{ 'animate-spin': issuesStore.isLoading }" />
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
        v-if="issuesStore.isLoading && !issuesStore.issues.length"
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
          <!-- Total Issues -->
          <div
            class="motion-preset-slide-up-sm motion-delay-75 overflow-hidden rounded-xl bg-white p-6 shadow-sm border border-gold-200/50"
          >
            <div class="flex items-center gap-4">
              <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-gold-100">
                <ClipboardDocumentListIcon class="h-6 w-6 text-gold-700" />
              </div>
              <div>
                <p class="text-sm font-medium text-slate-600">Total Issues</p>
                <p class="text-2xl font-bold text-slate-900">{{ stats.total }}</p>
              </div>
            </div>
          </div>

          <!-- Resolution Rate -->
          <div
            class="motion-preset-slide-up-sm motion-delay-100 overflow-hidden rounded-xl bg-white p-6 shadow-sm border border-gold-200/50"
          >
            <div class="flex items-center gap-4">
              <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-green-50">
                <ChartBarIcon class="h-6 w-6 text-green-700" />
              </div>
              <div>
                <p class="text-sm font-medium text-slate-600">Resolution Rate</p>
                <p class="text-2xl font-bold text-slate-900">{{ stats.resolutionRate }}%</p>
              </div>
            </div>
          </div>

          <!-- Pending Issues -->
          <div
            class="motion-preset-slide-up-sm motion-delay-150 overflow-hidden rounded-xl bg-white p-6 shadow-sm border border-gold-200/50"
          >
            <div class="flex items-center gap-4">
              <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-gold-100">
                <ExclamationCircleIcon class="h-6 w-6 text-gold-700" />
              </div>
              <div>
                <p class="text-sm font-medium text-slate-600">Pending Review</p>
                <p class="text-2xl font-bold text-slate-900">{{ stats.pending_review }}</p>
              </div>
            </div>
          </div>

          <!-- In Progress -->
          <div
            class="motion-preset-slide-up-sm motion-delay-200 overflow-hidden rounded-xl bg-white p-6 shadow-sm border border-gold-200/50"
          >
            <div class="flex items-center gap-4">
              <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-saffron-100">
                <ClockIcon class="h-6 w-6 text-saffron-600" />
              </div>
              <div>
                <p class="text-sm font-medium text-slate-600">In Progress</p>
                <p class="text-2xl font-bold text-slate-900">{{ stats.inProgress }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Charts Grid -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
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

          <!-- Issues by Category Chart -->
          <div
            class="motion-preset-slide-up-sm motion-delay-400 overflow-hidden rounded-xl bg-white p-6 shadow-sm border border-gold-200/50"
          >
            <h3 class="mb-4 text-lg font-semibold text-slate-900">Issues by Category</h3>
            <div class="flex items-center justify-center" style="height: 300px">
              <Bar v-if="categoryChartData" :data="categoryChartData" :options="barOptions" />
              <p v-else class="text-sm text-slate-600">No data available</p>
            </div>
          </div>

          <!-- Issues Timeline Chart -->
          <div
            class="motion-preset-slide-up-sm motion-delay-500 col-span-1 overflow-hidden rounded-xl bg-white p-6 shadow-sm border border-gold-200/50 lg:col-span-2"
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
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useIssuesStore } from '../../stores/issuesStore'
import {
  ClipboardDocumentListIcon,
  ExclamationCircleIcon,
  ClockIcon,
  ArrowPathIcon,
  ChartBarIcon,
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
import { Doughnut, Bar, Line } from 'vue-chartjs'

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

// Computed Statistics
const stats = computed(() => {
  const issues = issuesStore.issues
  const total = issuesStore.totalCount
  const pending_review = issues.filter((i) => i.status === 'pending_review').length
  const inProgress = issues.filter((i) => i.status === 'in_progress').length
  const resolved = issues.filter((i) => i.status === 'resolved').length
  const resolutionRate = total > 0 ? Math.round((resolved / total) * 100) : 0

  return {
    total,
    pending_review,
    inProgress,
    resolved,
    resolutionRate,
  }
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

// Category Chart Data
const categoryChartData = computed(() => {
  const issues = issuesStore.issues
  if (!issues.length) return null

  // Dynamically count categories from actual issues
  const categoryCounts = {}
  issues.forEach((issue) => {
    const category = issue.category || 'unknown'
    categoryCounts[category] = (categoryCounts[category] || 0) + 1
  })

  // Filter out categories with 0 count and sort by count (descending)
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

// Timeline Chart Data (Last 30 Days)
const timelineChartData = computed(() => {
  const issues = issuesStore.issues
  if (!issues.length) return null

  // Generate last 30 days
  const days = []
  const counts = []
  const today = new Date()

  for (let i = 29; i >= 0; i--) {
    const date = new Date(today)
    date.setDate(date.getDate() - i)
    const dateStr = date.toISOString().split('T')[0]
    days.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }))

    // Count issues created on this date
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

const refreshData = async () => {
  // Fetch all issues for accurate reporting (not paginated)
  await issuesStore.fetchIssues({ limit: 100 })
  lastUpdated.value = new Date().toLocaleTimeString()
}

onMounted(() => {
  refreshData()
})
</script>
