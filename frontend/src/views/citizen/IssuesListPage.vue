<template>
  <div class="min-h-screen bg-gradient-to-br from-cream-50 to-cream-100 text-slate-800">
    <div class="w-full px-4 py-8 sm:px-6 lg:px-8 xl:px-10">
      <!-- Header -->
      <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="mb-2 text-4xl font-bold text-slate-900">Community Issues</h1>
          <p class="text-slate-600">Browse and upvote issues in your community</p>
        </div>
        <router-link
          to="/report-issue"
          class="flex items-center gap-2 rounded-lg bg-gold-600 px-6 py-3 font-semibold text-white shadow-md transition-all hover:bg-gold-700 hover:shadow-lg"
        >
          <PlusIcon class="h-5 w-5" />
          Report Issue
        </router-link>
      </div>

      <!-- View Toggle and Filters -->
      <div class="mb-8 rounded-xl bg-white/90 p-6 shadow-md backdrop-blur-sm border border-gold-200/50">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
          <!-- View Toggle -->
          <div class="flex gap-2">
            <button
              @click="viewMode = 'list'"
              :class="{
                'bg-gold-600 text-white': viewMode === 'list',
                'bg-gold-100/60 text-gold-700': viewMode !== 'list',
              }"
              class="flex items-center gap-2 rounded-lg px-4 py-2 font-semibold transition-colors"
            >
              <ListBulletIcon class="h-5 w-5" />
              List View
            </button>
            <button
              @click="viewMode = 'map'"
              :class="{
                'bg-gold-600 text-white': viewMode === 'map',
                'bg-gold-100/60 text-gold-700': viewMode !== 'map',
              }"
              class="flex items-center gap-2 rounded-lg px-4 py-2 font-semibold transition-colors"
            >
              <MapIcon class="h-5 w-5" />
              Map View
            </button>
          </div>

          <!-- Filters -->
          <div class="flex flex-col gap-4 sm:flex-row">
            <!-- Status Filter -->
            <select
              v-model="filters.status"
              @change="applyFilters"
              class="rounded-lg border border-gold-200 px-4 py-2 focus:ring-2 focus:ring-gold-500 focus:outline-none bg-cream-50"
            >
              <option value="">All Status</option>
              <option value="pending_review">Pending Review</option>
              <option value="in_progress">In Progress</option>
              <option value="resolved">Resolved</option>
            </select>

            <!-- Category Filter -->
            <select
              v-model="filters.category"
              @change="applyFilters"
              class="rounded-lg border border-gold-200 px-4 py-2 focus:ring-2 focus:ring-gold-500 focus:outline-none bg-cream-50"
            >
              <option value="">All Categories</option>
              <option
                v-for="category in issuesStore.issueCategories"
                :key="category"
                :value="category"
              >
                {{ formatCategory(category) }}
              </option>
            </select>

            <!-- Sort Filter -->
            <select
              v-model="filters.sortBy"
              @change="applyFilters"
              class="rounded-lg border border-gold-200 px-4 py-2 focus:ring-2 focus:ring-gold-500 focus:outline-none bg-cream-50"
            >
              <option value="recent">Most Recent</option>
              <option value="upvotes">Most Upvoted</option>
            </select>

            <!-- Reset Filters -->
            <button
              @click="resetAllFilters"
              class="flex items-center rounded-lg bg-gold-100 px-4 py-2 font-semibold text-gold-700 transition-colors hover:bg-gold-200"
            >
              <ArrowPathIcon class="mr-2 h-5 w-5" />
              Reset
            </button>
          </div>
        </div>
      </div>

      <!-- List View -->
      <div v-if="viewMode === 'list'" class="space-y-4">
        <!-- Loading State -->
        <div v-if="isLoading" class="flex flex-col items-center py-12 text-center">
          <ArrowPathIcon class="h-8 w-8 animate-spin text-gold-600" />
          <p class="mt-4 text-slate-600">Loading issues...</p>
        </div>

        <!-- Empty State -->
        <div
          v-else-if="filteredIssuesStore.length === 0"
          class="flex flex-col items-center rounded-xl bg-white/90 p-12 text-center shadow-md border border-gold-200/50"
        >
          <InboxIcon class="mb-4 block h-12 w-12 text-gold-600/60 opacity-50" />
          <p class="text-lg text-slate-600">No issues found matching your filters.</p>
          <button
            @click="resetAllFilters"
            class="mt-4 font-semibold text-gold-700 hover:underline"
          >
            Clear filters
          </button>
        </div>

        <!-- Issues List -->
        <div v-else class="space-y-4">
          <div
            v-for="issue in filteredIssuesStore"
            :key="issue.id"
            class="rounded-xl border-l-4 bg-white/90 p-6 shadow-md transition-shadow hover:shadow-lg backdrop-blur-sm"
            :class="{
              'border-gold-400': issue.status === 'pending_review',
              'border-saffron-500': issue.status === 'in_progress',
              'border-green-500': issue.status === 'resolved',
            }"
          >
            <div class="flex flex-col gap-6 lg:flex-row lg:items-start">
              <!-- Issue Image -->
              <div
                v-if="issue.image_url"
                class="h-32 w-full shrink-0 cursor-pointer overflow-hidden rounded-lg transition-opacity hover:opacity-90 lg:w-32"
                @click="selectedImage = issue.image_url"
              >
                <img
                  :src="issue.image_url"
                  :alt="issue.title"
                  class="h-full w-full object-cover"
                />
              </div>

              <!-- Issue Details -->
              <div class="grow">
                <div class="mb-2 flex items-start gap-3">
                  <div>
                    <router-link
                      :to="`/issues/${issue.id}`"
                      class="text-xl font-bold text-slate-900 transition-colors hover:text-gold-800"
                    >
                      {{ issue.title }}
                    </router-link>
                  </div>
                  <!-- Status Badge -->
                  <div
                    :class="{
                      'bg-gold-100 text-gold-800': issue.status === 'pending_review',
                      'bg-saffron-100 text-saffron-800': issue.status === 'in_progress',
                      'bg-green-100 text-green-800': issue.status === 'resolved',
                    }"
                    class="rounded-full px-3 py-1 text-xs font-semibold whitespace-nowrap uppercase"
                  >
                    {{ issue.status }}
                  </div>
                </div>

                <!-- Category and Meta -->
                <div class="mb-3 flex flex-wrap items-center gap-3 text-sm text-slate-600">
                  <span class="flex items-center">
                    <TagIcon class="mr-1 h-4 w-4 text-gold-600" />
                    {{ formatCategory(issue.category) }}
                  </span>
                  <span class="flex items-center">
                    <CalendarIcon class="mr-1 h-4 w-4 text-gold-600" />
                    {{ formatDate(issue.created_at) }}
                  </span>
                  <span v-if="issue.location" class="flex max-w-xs items-center truncate">
                    <MapPinIcon class="mr-1 h-4 w-4 shrink-0 text-gold-600" />
                    <span class="truncate">{{ issue.location }}</span>
                  </span>
                  <span
                    class="flex items-center"
                    :class="{
                      'text-saffron-600': issue.priority === 'high',
                      'text-gold-600': issue.priority === 'medium',
                      'text-slate-600': issue.priority === 'low',
                    }"
                  >
                    <ExclamationCircleIcon class="mr-1 h-4 w-4" />
                    {{ issue.priority?.charAt(0).toUpperCase() + issue.priority?.slice(1) }}
                  </span>
                </div>

                <!-- Description -->
                <p class="mb-3 line-clamp-2 text-slate-700">{{ issue.description }}</p>

                <!-- Reporter -->
                <p class="mb-2 flex items-center text-xs text-slate-600">
                  <UserCircleIcon class="mr-1 h-4 w-4" />
                  Reported by
                  <router-link
                    :to="`/profile/${issue.user_id}`"
                    class="ml-1 font-semibold text-slate-900 transition-colors hover:text-gold-800 hover:underline"
                  >
                    {{ issue.user_name }}
                  </router-link>
                </p>

                <!-- Assigned To -->
                <p v-if="issue.assigned_staff_id" class="mb-3 flex items-center text-xs text-slate-600">
                  <UsersIcon class="mr-1 h-4 w-4 text-gold-600" />
                  <span class="font-semibold text-slate-900">Assigned to:</span>
                  <span class="ml-1 flex flex-col">
                    <span class="font-semibold text-gold-700">{{ issue.assigned_staff_first_name }} {{ issue.assigned_staff_last_name }}</span>
                    <span class="text-[0.65rem] text-slate-600">{{ issue.assigned_staff_email }}</span>
                  </span>
                </p>
              </div>

              <!-- Stats and Actions -->
              <div class="flex min-w-fit flex-col gap-4">
                <!-- Stats -->
                <div class="flex flex-col gap-2">
                  <div class="text-center">
                    <button
                      @click="toggleUpvote(issue.id)"
                      :class="{
                        'bg-gold-600 text-white': issue.user_has_upvoted,
                        'bg-gold-100/70 text-gold-700 hover:bg-gold-600 hover:text-white':
                          !issue.user_has_upvoted,
                      }"
                      class="flex w-full items-center justify-center gap-2 rounded-lg px-3 py-2 font-semibold transition-colors"
                    >
                      <HandThumbUpIcon class="h-5 w-5" />
                      Vote ({{ issue.upvote_count || 0 }})
                    </button>
                  </div>
                </div>

                <!-- View Details Link -->
                <router-link
                  :to="`/issues/${issue.id}`"
                  class="w-full rounded-lg bg-slate-700 px-4 py-2 text-center font-semibold text-cream-50 transition-colors hover:bg-slate-800"
                >
                  View Details
                </router-link>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="filteredIssuesStore.length > 0" class="mt-8 flex items-center justify-between">
          <p class="text-slate-600">
            Showing <strong>{{ filteredIssuesStore.length }}</strong> of
            <strong>{{ totalIssuesCount }}</strong> issues
          </p>
        </div>
      </div>

      <!-- Map View -->
      <div v-if="viewMode === 'map'" class="space-y-4 rounded-xl bg-white/90 p-4 shadow-md backdrop-blur-sm border border-gold-200/50">
        <div class="flex flex-col gap-2">
          <button
            v-if="showLocationButton"
            @click="requestCurrentLocation"
            :disabled="isGettingLocation"
            class="w-fit rounded-lg bg-gold-600 px-4 py-2 font-semibold text-white transition-colors hover:bg-gold-700 disabled:cursor-not-allowed disabled:opacity-70"
          >
            {{ isGettingLocation ? 'Getting your location...' : 'Allow access to current location' }}
          </button>
          <p v-if="locationError" class="text-sm text-red-600">{{ locationError }}</p>
        </div>

        <div class="overflow-hidden rounded-xl border border-gold-200">
          <div v-if="isLoading || isGettingLocation" class="flex h-96 items-center justify-center">
            <ArrowPathIcon class="h-8 w-8 animate-spin text-gold-600" />
          </div>
          <div v-else-if="hasUserLocation" id="map-container" class="h-96 md:h-[600px]"></div>
          <div
            v-else
            class="flex h-96 items-center justify-center px-4 text-center text-sm text-slate-600 md:h-[600px]"
          >
            Allow location access to load your current location on the map.
          </div>
        </div>
      </div>
    </div>

    <!-- Image Modal -->
    <ImageModal :image-url="selectedImage" :alt="'Issue Image'" @close="selectedImage = null" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import L from 'leaflet'
import { useIssuesStore } from '../../stores/issuesStore'
import ImageModal from '../../components/common/ImageModal.vue'
import {
  ListBulletIcon,
  MapIcon,
  ArrowPathIcon,
  InboxIcon,
  PhotoIcon,
  TagIcon,
  CalendarIcon,
  ExclamationCircleIcon,
  UserCircleIcon,
  UsersIcon,
  HandThumbUpIcon,
  MapPinIcon,
  PlusIcon,
} from '@heroicons/vue/24/solid'

const issuesStore = useIssuesStore()

const viewMode = ref('list')
const isLoading = ref(false)
const selectedImage = ref(null)
const isGettingLocation = ref(false)
const locationError = ref('')
const locationPermissionState = ref('prompt')
const userLocation = ref(null)
let mapInstance = null
let userLocationCircle = null
let issueMarkersLayer = null
let geolocationWatchId = null

const filters = ref({
  status: '',
  category: '',
  sortBy: 'recent',
})

const filteredIssuesStore = computed(() => issuesStore.filteredIssues)
const totalIssuesCount = computed(() => issuesStore.totalCount)
const showLocationButton = computed(() => locationPermissionState.value !== 'granted')
const hasUserLocation = computed(() => Boolean(userLocation.value))

const getStatusColor = (status) => {
  if (status === 'pending_review') return '#d4af37'
  if (status === 'in_progress') return '#ff9933'
  if (status === 'resolved') return '#22c55e'
  return '#ef4444'
}

const escapeHtml = (value) => {
  if (typeof value !== 'string') return ''
  return value
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;')
}

const handleMapContainerClick = (e) => {
  const target = e.target
  if (!(target instanceof HTMLElement)) return

  if (target.classList.contains('view-details-link')) {
    e.preventDefault()
    const issueId = target.getAttribute('data-issue-id')
    if (issueId) {
      import('../../router').then(({ default: router }) => {
        router.push(`/issues/${issueId}`)
      })
    }
  }
}

const renderIssueMarkers = () => {
  if (!mapInstance) return

  if (issueMarkersLayer) {
    issueMarkersLayer.remove()
  }

  issueMarkersLayer = L.layerGroup()

  filteredIssuesStore.value.forEach((issue) => {
    if (!issue.latitude || !issue.longitude) return

    const color = getStatusColor(issue.status)
    const marker = L.circleMarker([issue.latitude, issue.longitude], {
      radius: 7,
      color,
      fillColor: color,
      fillOpacity: 0.85,
      weight: 2,
    })

    const safeTitle = escapeHtml(issue.title || 'Untitled Issue')
    const safeCategory = escapeHtml(formatCategory(issue.category || 'other'))
    const safeDescription = escapeHtml(issue.description || '').slice(0, 120)

    marker.bindPopup(`
      <div class="max-w-xs">
        <h3 class="font-bold text-slate-900">${safeTitle}</h3>
        <p class="text-xs text-gold-600 mt-1">${safeCategory}</p>
        <p class="text-sm mt-2 text-slate-700">${safeDescription}${safeDescription.length >= 120 ? '...' : ''}</p>
        <a href="#" class="text-gold-600 text-sm font-semibold mt-2 inline-block view-details-link" data-issue-id="${issue.id}">View Details</a>
      </div>
    `)

    marker.addTo(issueMarkersLayer)
  })

  issueMarkersLayer.addTo(mapInstance)
}

const destroyMap = () => {
  if (mapInstance) {
    mapInstance.getContainer().removeEventListener('click', handleMapContainerClick)
    mapInstance.remove()
    mapInstance = null
  }
  userLocationCircle = null
  issueMarkersLayer = null
}

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
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
  }
}

const applyFilters = () => {
  issuesStore.setFilters({
    status: filters.value.status || null,
    category: filters.value.category || null,
    sortBy: filters.value.sortBy,
  })
}

const resetAllFilters = () => {
  filters.value = {
    status: '',
    category: '',
    sortBy: 'recent',
  }
  issuesStore.resetFilters()
}

const toggleUpvote = async (issueId) => {
  try {
    const issue = issuesStore.issues.find((i) => i.id === issueId)
    if (issue?.user_has_upvoted) {
      await issuesStore.removeUpvote(issueId)
    } else {
      await issuesStore.upvoteIssue(issueId)
    }
  } catch (error) {
    console.error('Error toggling upvote:', error)
  }
}

const initMap = () => {
  if (!userLocation.value) return

  const mapContainer = document.getElementById('map-container')
  if (!mapContainer) return

  const { latitude, longitude, accuracy } = userLocation.value

  if (!mapInstance) {
    mapInstance = L.map('map-container').setView([latitude, longitude], 16)

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap contributors',
      maxZoom: 19,
      minZoom: 3,
    }).addTo(mapInstance)

    mapInstance.getContainer().addEventListener('click', handleMapContainerClick)
  }

  if (userLocationCircle) {
    userLocationCircle.setLatLng([latitude, longitude])
    userLocationCircle.setRadius(Math.max(accuracy || 25, 25))
  } else {
    userLocationCircle = L.circle([latitude, longitude], {
      radius: Math.max(accuracy || 25, 25),
      color: '#2563eb',
      fillColor: '#3b82f6',
      fillOpacity: 0.35,
      weight: 2,
    }).addTo(mapInstance)

    userLocationCircle.bindPopup('Your current location').openPopup()
  }

  renderIssueMarkers()
}

const stopLocationTracking = () => {
  if (geolocationWatchId !== null && navigator.geolocation) {
    navigator.geolocation.clearWatch(geolocationWatchId)
    geolocationWatchId = null
  }
}

const checkLocationPermission = async () => {
  if (!navigator.permissions?.query) {
    return
  }

  try {
    const permissionStatus = await navigator.permissions.query({ name: 'geolocation' })
    locationPermissionState.value = permissionStatus.state
    permissionStatus.onchange = () => {
      locationPermissionState.value = permissionStatus.state
    }
  } catch (error) {
    console.error('Error checking location permission:', error)
  }
}

const requestCurrentLocation = async () => {
  if (!navigator.geolocation) {
    locationError.value = 'Geolocation is not supported by your browser.'
    return
  }

  if (geolocationWatchId !== null) {
    return
  }

  isGettingLocation.value = true
  locationError.value = ''

  geolocationWatchId = navigator.geolocation.watchPosition(
    async (position) => {
      const { latitude, longitude, accuracy } = position.coords
      userLocation.value = { latitude, longitude, accuracy }
      locationPermissionState.value = 'granted'
      isGettingLocation.value = false
      await nextTick()
      initMap()
    },
    (error) => {
      if (error.code === error.PERMISSION_DENIED) {
        locationPermissionState.value = 'denied'
        locationError.value = 'Location access was denied. Please allow it in your browser settings.'
      } else if (error.code === error.POSITION_UNAVAILABLE) {
        locationError.value = 'Your location is currently unavailable.'
      } else if (error.code === error.TIMEOUT) {
        locationError.value = 'Location request timed out. Please try again.'
      } else {
        locationError.value = 'Could not fetch your location. Please try again.'
      }
      stopLocationTracking()
      isGettingLocation.value = false
    },
    {
      enableHighAccuracy: true,
      timeout: 10000,
      maximumAge: 0,
    },
  )
}

const fetchIssues = async () => {
  isLoading.value = true
  try {
    // Fetch all issues by requesting a large limit
    await issuesStore.fetchIssues({ limit: 1000 })
    applyFilters()
  } catch (error) {
    console.error('Error fetching issues:', error)
  } finally {
    isLoading.value = false
  }
}

watch(
  () => viewMode.value,
  async (newMode) => {
    if (newMode === 'map') {
      await checkLocationPermission()

      setTimeout(() => {
        if (hasUserLocation.value) {
          initMap()
        }

        if (locationPermissionState.value === 'granted') {
          requestCurrentLocation()
        }
      }, 100)
    } else {
      stopLocationTracking()
      destroyMap()
    }
  },
)

watch(
  filteredIssuesStore,
  () => {
    if (viewMode.value === 'map') {
      renderIssueMarkers()
    }
  },
  { deep: true },
)

onMounted(() => {
  fetchIssues()
})

onUnmounted(() => {
  stopLocationTracking()
  destroyMap()
})
</script>
