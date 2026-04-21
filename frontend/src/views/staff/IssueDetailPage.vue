<template>
  <div class="min-h-screen bg-cream-50 pb-12">
    <!-- Header -->
    <div class="bg-white shadow border-b border-gold-200/60">
      <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <router-link
              :to="backRoute"
              class="flex items-center text-slate-600 hover:text-slate-800"
            >
              <ArrowLeftIcon class="h-5 w-5" />
            </router-link>
            <h1 class="text-2xl font-bold text-slate-900">Manage Issue #{{ issue?.id }}</h1>
          </div>
          <div class="flex items-center gap-3">
            <span
              v-if="issue"
              :class="[
                getStatusColor(issue.status),
                'inline-flex items-center rounded-full px-3 py-1 text-sm font-medium',
              ]"
            >
              {{ formatStatus(issue.status) }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <div v-if="isLoading" class="flex h-64 items-center justify-center">
      <div
        class="h-8 w-8 animate-spin rounded-full border-4 border-gold-600 border-t-transparent"
      ></div>
    </div>

    <div v-else-if="error" class="mx-auto mt-8 max-w-3xl px-4">
      <div class="rounded-md bg-red-50 p-4">
        <div class="flex">
          <div class="shrink-0">
            <ExclamationCircleIcon class="h-5 w-5 text-red-400" />
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">Error loading issue</h3>
            <div class="mt-2 text-sm text-red-700">
              <p>{{ error }}</p>
            </div>
            <div class="mt-4">
              <router-link
                :to="backRoute"
                class="text-sm font-medium text-red-800 hover:text-red-900"
                >Back to Dashboard</router-link
              >
            </div>
          </div>
        </div>
      </div>
    </div>

    <div
      v-else-if="issue"
      class="mx-auto mt-8 grid max-w-7xl grid-cols-1 gap-6 px-4 lg:grid-cols-3"
    >
      <!-- Main Column -->
      <div class="space-y-6 lg:col-span-2">
        <!-- Issue Info -->
        <div class="overflow-hidden rounded-lg bg-white shadow border border-gold-200/50">
          <div class="p-6">
            <div class="flex items-center justify-between">
              <span
                class="inline-flex items-center rounded-full bg-gold-100 px-2.5 py-0.5 text-xs font-medium text-gold-800"
              >
                {{ formatCategory(issue.category) }}
              </span>
              <span class="text-sm text-slate-600">{{ formatDate(issue.created_at) }}</span>
            </div>
            <h2 class="mt-4 text-xl font-bold text-slate-900">{{ issue.title }}</h2>
            <p class="mt-4 whitespace-pre-wrap text-slate-700">{{ issue.description }}</p>

            <div v-if="issue.image_url" class="mt-6">
              <img
                :src="issue.image_url"
                alt="Issue Image"
                class="h-96 w-full rounded-lg object-cover"
              />
            </div>
          </div>
        </div>

        <!-- Location (Leaflet Map) -->
        <div
          v-if="issue.latitude && issue.longitude"
          class="overflow-hidden rounded-lg bg-white shadow border border-gold-200/50"
        >
          <div class="p-6">
            <h3 class="text-lg font-medium text-slate-900">Location</h3>
            <div v-if="issue.location" class="mt-4 mb-4">
              <p class="flex items-start text-slate-700">
                <MapPinIcon class="mt-0.5 mr-2 h-5 w-5 shrink-0 text-gold-700" />
                <span class="font-semibold">{{ issue.location }}</span>
              </p>
              <p class="mt-1 ml-7 text-sm text-slate-600">
                Coordinates: {{ issue.latitude.toFixed(6) }}, {{ issue.longitude.toFixed(6) }}
              </p>
            </div>
            <div id="staff-issue-map" class="mt-4 h-64 rounded-lg bg-gold-50"></div>
          </div>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="space-y-6">
        <!-- Status Management -->
        <div class="overflow-hidden rounded-lg bg-white shadow border border-gold-200/50">
          <div class="p-6">
            <h3 class="text-lg font-medium text-slate-900">Update Status</h3>
            <div class="mt-4 space-y-3">
              <p class="mb-2 text-sm text-slate-600">
                Current status: <span class="font-medium">{{ formatStatus(issue.status) }}</span>
              </p>

              <div class="grid grid-cols-1 gap-2">
                <button
                  v-for="status in availableStatuses"
                  :key="status.value"
                  @click="updateStatus(status.value)"
                  :disabled="updatingStatus !== null || issue.status === status.value"
                  class="flex items-center justify-center rounded-md border px-4 py-2 text-sm font-medium transition-colors"
                  :class="[
                    issue.status === status.value
                      ? 'cursor-not-allowed border-gold-200 bg-gold-100 text-gold-500'
                      : updatingStatus !== null
                        ? 'cursor-not-allowed border-gold-200 bg-gold-50 text-gold-500'
                        : 'border-gold-200 bg-white text-slate-700 hover:bg-gold-50',
                  ]"
                >
                  <div
                    v-if="updatingStatus === status.value"
                    class="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-gold-300 border-t-gold-600"
                  ></div>
                  <component
                    v-else
                    :is="status.icon"
                    class="mr-2 h-4 w-4"
                    :class="status.colorClass"
                  />
                  Mark as {{ status.label }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Issue Details Sidebar -->
        <div class="overflow-hidden rounded-lg bg-white shadow border border-gold-200/50">
          <div class="p-6">
            <h3 class="text-lg font-medium text-slate-900">Details</h3>
            <dl class="mt-4 space-y-4">
              <div>
                <dt class="text-sm font-medium text-slate-600">Priority</dt>
                <dd class="mt-1 flex items-center text-sm text-slate-900 capitalize">
                  <ExclamationCircleIcon class="mr-1.5 h-4 w-4 text-gold-500" />
                  {{ issue.priority }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-slate-600">Upvotes</dt>
                <dd class="mt-1 flex items-center text-sm text-slate-900">
                  <HandThumbUpIcon class="mr-1.5 h-4 w-4 text-gold-500" />
                  {{ issue.upvote_count }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-slate-600">Reporter</dt>
                <dd class="mt-1 text-sm">
                  <router-link
                    :to="`/profile/${issue.user_id}`"
                    class="font-medium text-gold-700 transition-colors hover:text-gold-900 hover:underline"
                  >
                    {{ issue.user_name }}
                  </router-link>
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-slate-600">Assigned Staff</dt>
                <dd class="mt-1 text-sm text-slate-900">
                  {{ issue.assigned_staff_name || 'Not assigned' }}
                  <span v-if="issue.assigned_staff_email" class="block text-xs text-slate-600">
                    {{ issue.assigned_staff_email }}
                  </span>
                </dd>
              </div>
            </dl>
          </div>
        </div>

        <div v-if="authStore.isAdmin" class="overflow-hidden rounded-lg bg-white shadow border border-gold-200/50">
          <div class="p-6">
            <h3 class="text-lg font-medium text-slate-900">Assign to Staff</h3>
            <p class="mt-1 text-sm text-slate-600">
              Use advanced search and dropdown selection to assign this issue.
            </p>

            <div class="mt-4 space-y-3">
              <input
                v-model="staffSearch"
                type="text"
                placeholder="Search by first name, last name, or email"
                class="w-full rounded-md border border-gold-200 px-3 py-2 text-sm focus:border-gold-400 focus:outline-none focus:ring-2 focus:ring-gold-200"
              />

              <div
                v-if="filteredStaffOptions.length > 0"
                class="rounded-md border border-gold-200 bg-white"
              >
                <select
                  v-model="selectedStaffId"
                  @change="syncSelectedStaffById"
                  class="max-h-44 w-full rounded-md border-0 px-3 py-2 text-sm focus:outline-none"
                  size="6"
                >
                  <option
                    v-for="member in filteredStaffOptions"
                    :key="member.id"
                    :value="String(member.id)"
                  >
                    {{ member.first_name }} {{ member.last_name }} ({{ member.email }})
                  </option>
                </select>
              </div>

              <div
                v-if="staffSuggestions.length > 0"
                class="max-h-44 overflow-y-auto rounded-md border border-gold-200"
              >
                <div class="border-b border-gold-100 px-3 py-2 text-xs font-semibold tracking-wide text-slate-600 uppercase">
                  Suggestions
                </div>
                <button
                  v-for="member in staffSuggestions"
                  :key="member.id"
                  type="button"
                  @click="selectStaff(member)"
                  class="flex w-full items-start justify-between border-b border-gold-100 px-3 py-2 text-left text-sm transition-colors last:border-b-0 hover:bg-gold-50"
                >
                  <span>
                    <span class="font-medium text-slate-900">
                      {{ member.first_name }} {{ member.last_name }}
                    </span>
                    <span class="block text-xs text-slate-600">{{ member.email }}</span>
                  </span>
                </button>
              </div>

              <p
                v-else-if="staffSearch.trim() && !isSearchingStaff"
                class="text-xs text-slate-600"
              >
                No staff suggestions found.
              </p>

              <p v-if="isSearchingStaff" class="text-xs text-slate-600">Loading staff list...</p>

              <div v-if="selectedStaff" class="rounded-md bg-gold-100 px-3 py-2 text-sm text-gold-800">
                Selected: {{ selectedStaff.first_name }} {{ selectedStaff.last_name }}
                <span class="block text-xs text-gold-700">{{ selectedStaff.email }}</span>
              </div>

              <button
                type="button"
                @click="assignStaff"
                :disabled="!selectedStaff || isAssigningStaff"
                class="w-full rounded-md bg-gold-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-gold-700 disabled:cursor-not-allowed disabled:opacity-50"
              >
                {{ isAssigningStaff ? 'Assigning...' : 'Assign Issue' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useIssuesStore } from '../../stores/issuesStore'
import { useAuthStore } from '../../stores/authStore'
import { useToast } from 'vue-toastification'
import L from 'leaflet'
import axios from 'axios'
import { API_BASE_URL } from '@/config/api'
import {
  ArrowLeftIcon,
  ExclamationCircleIcon,
  HandThumbUpIcon,
  CheckCircleIcon,
  ClockIcon,
  InboxIcon,
  MapPinIcon,
} from '@heroicons/vue/24/outline'

const route = useRoute()
const issuesStore = useIssuesStore()
const authStore = useAuthStore()
const toast = useToast()

const issue = ref(null)
const isLoading = ref(false)
const updatingStatus = ref(null)
const error = ref('')
const staffSearch = ref('')
const allStaff = ref([])
const selectedStaffId = ref('')
const selectedStaff = ref(null)
const isSearchingStaff = ref(false)
const isAssigningStaff = ref(false)

const backRoute = computed(() => (authStore.isAdmin ? '/admin/issues' : '/staff/dashboard'))

const filteredStaffOptions = computed(() => {
  if (!staffSearch.value.trim()) {
    return allStaff.value
  }

  const query = staffSearch.value.trim().toLowerCase()
  const tokens = query.split(/\s+/).filter(Boolean)

  return allStaff.value.filter((member) => {
    const first = (member.first_name || '').toLowerCase()
    const last = (member.last_name || '').toLowerCase()
    const email = (member.email || '').toLowerCase()
    const fullName = `${first} ${last}`.trim()

    return tokens.every(
      (token) => first.includes(token) || last.includes(token) || fullName.includes(token) || email.includes(token),
    )
  })
})

const staffSuggestions = computed(() => {
  if (!staffSearch.value.trim()) return []
  return filteredStaffOptions.value.slice(0, 5)
})

const availableStatuses = [
  {
    value: 'pending_review',
    label: 'Pending Review',
    icon: InboxIcon,
    colorClass: 'text-gold-600',
  },
  { value: 'in_progress', label: 'In Progress', icon: ClockIcon, colorClass: 'text-saffron-600' },
  { value: 'resolved', label: 'Resolved', icon: CheckCircleIcon, colorClass: 'text-green-500' },
]

const formatCategory = (category) => {
  return issuesStore.formatIssueCategory(category)
}

const formatStatus = (status) => {
  switch (status) {
    case 'pending_review':
      return 'Pending Review'
    case 'in_progress':
      return 'In Progress'
    case 'resolved':
      return 'Resolved'
    default:
      return status
  }
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
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const updateStatus = async (newStatus) => {
  if (!issue.value) return

  updatingStatus.value = newStatus
  try {
    await issuesStore.updateIssue(issue.value.id, { status: newStatus })
    issue.value.status = newStatus
    toast.success(`Issue marked as ${formatStatus(newStatus)}`)
  } catch (err) {
    toast.error('Failed to update status')
    console.error(err)
  } finally {
    updatingStatus.value = null
  }
}

const initMap = () => {
  if (issue.value?.latitude && issue.value?.longitude) {
    const mapContainer = document.getElementById('staff-issue-map')
    if (mapContainer && !mapContainer.hasChildNodes()) {
      const map = L.map('staff-issue-map').setView(
        [issue.value.latitude, issue.value.longitude],
        15,
      )

      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19,
      }).addTo(map)

      L.marker([issue.value.latitude, issue.value.longitude])
        .addTo(map)
        .bindPopup(issue.value.title)
        .openPopup()
    }
  }
}

const fetchIssue = async () => {
  isLoading.value = true
  error.value = ''
  try {
    issue.value = await issuesStore.fetchIssueById(route.params.id)
    setTimeout(initMap, 100)
  } catch (err) {
    error.value = 'Failed to load issue details.'
    console.error(err)
  } finally {
    isLoading.value = false
  }
}

const fetchAllStaff = async () => {
  if (!authStore.isAdmin) return

  isSearchingStaff.value = true
  try {
    const token = localStorage.getItem('token')
    const headers = {}
    if (token) {
      headers.Authorization = `Bearer ${token}`
    }

    let currentPage = 1
    let totalPages = 1
    const merged = []

    while (currentPage <= totalPages) {
      const response = await axios.get(`${API_BASE_URL}/admin/staff`, {
        headers,
        params: {
          page: currentPage,
          limit: 100,
        },
      })

      const staffPage = response.data?.staff || []
      merged.push(...staffPage)
      totalPages = response.data?.pagination?.total_pages || 1
      currentPage += 1
    }

    allStaff.value = merged

    if (issue.value?.assigned_staff_id) {
      const assigned = merged.find((member) => String(member.id) === String(issue.value.assigned_staff_id))
      if (assigned) {
        selectedStaff.value = assigned
        selectedStaffId.value = String(assigned.id)
      }
    }
  } catch (err) {
    console.error('Failed to load staff list:', err)
    allStaff.value = []
  } finally {
    isSearchingStaff.value = false
  }
}

const syncSelectedStaffById = () => {
  if (!selectedStaffId.value) {
    selectedStaff.value = null
    return
  }

  const matched = allStaff.value.find((member) => String(member.id) === selectedStaffId.value)
  if (matched) {
    selectedStaff.value = matched
  }
}

const selectStaff = (member) => {
  selectedStaff.value = member
  selectedStaffId.value = String(member.id)
}

const assignStaff = async () => {
  if (!issue.value || !selectedStaff.value) return

  isAssigningStaff.value = true
  try {
    await issuesStore.updateIssue(issue.value.id, { assigned_staff_id: selectedStaff.value.id })
    issue.value = await issuesStore.fetchIssueById(route.params.id)
    toast.success(
      `Issue assigned to ${selectedStaff.value.first_name} ${selectedStaff.value.last_name}`,
    )
  } catch (err) {
    console.error(err)
    toast.error('Failed to assign issue to staff')
  } finally {
    isAssigningStaff.value = false
  }
}

onMounted(() => {
  fetchIssue()
  fetchAllStaff()
})
</script>
