<template>
  <div class="animate-fade-in-up mx-auto max-w-full px-0 py-0">
    <!-- Header -->
    <div class="border-b border-gold-200 bg-white px-4 py-4 shadow-sm md:px-6">
      <router-link
        to="/issues"
        class="text-text-light hover:text-primary group mb-3 inline-flex items-center gap-2 font-medium transition-colors"
      >
        <ArrowLeftIcon class="h-4 w-4 transition-transform group-hover:-translate-x-1" />
        Back to Issues
      </router-link>
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-text mb-1 text-2xl font-bold md:text-3xl">Issues Map</h1>
          <p class="text-text-light text-sm md:text-base">
            View reported issues on an interactive map with clustering and heatmap analysis
          </p>
        </div>
        <button
          v-if="!hasLocationPermission"
          type="button"
          class="bg-primary hover:bg-primary/90 inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold text-white transition-colors disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="isLocating"
          @click="allowLocationAccess"
        >
          <ArrowPathIcon v-if="isLocating" class="mr-2 h-4 w-4 animate-spin" />
          {{ isLocating ? 'Getting Location...' : 'Allow Access to Location' }}
        </button>
        <button
          v-else
          type="button"
          class="bg-primary hover:bg-primary/90 inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold text-white transition-colors"
          @click="allowLocationAccess"
        >
          <ArrowPathIcon class="mr-2 h-4 w-4" />
          Refresh Location
        </button>
      </div>
    </div>

    <!-- Main Content -->
    <div v-if="hasLocationPermission" class="flex h-[calc(100vh-180px)] gap-0">
      <!-- Map Section -->
      <div class="flex-1 bg-cream-100">
        <div class="relative h-full w-full">
          <div class="absolute left-11 top-3 z-[1000] flex gap-2 rounded-lg bg-white/95 p-2 shadow-md">
            <button
              type="button"
              class="rounded-md px-3 py-1 text-xs font-semibold transition-colors"
              :class="
                mapEngine === 'leaflet'
                  ? 'bg-primary text-white'
                  : 'bg-cream-100 text-slate-700 hover:bg-cream-200'
              "
              @click="mapEngine = 'leaflet'"
            >
              Leaflet
            </button>
            <button
              type="button"
              class="rounded-md px-3 py-1 text-xs font-semibold transition-colors"
              :class="
                mapEngine === 'openlayers'
                  ? 'bg-primary text-white'
                  : 'bg-cream-100 text-slate-700 hover:bg-cream-200'
              "
              @click="mapEngine = 'openlayers'"
            >
              OpenLayers
            </button>
            <button
              type="button"
              class="rounded-md px-3 py-1 text-xs font-semibold transition-colors"
              :class="isHeatmapVisible ? 'bg-gold-100 text-gold-700' : 'bg-slate-100 text-slate-700'"
              @click="toggleHeatmapVisibility"
            >
              Heatmap: {{ isHeatmapVisible ? 'ON' : 'OFF' }}
            </button>
            <select
              v-if="mapEngine === 'openlayers'"
              v-model="openLayersBaseStyle"
              class="rounded-md border border-gold-200 bg-white px-2 py-1 text-xs font-semibold text-slate-700 outline-none"
              title="OpenLayers basemap style"
            >
              <option value="satellite">Satellite (Esri World Imagery)</option>
              <option value="voyager">Modern Streets</option>
            </select>
            <span
              v-if="mapEngine === 'openlayers' && isBasemapTransitioning"
              class="rounded-md bg-gold-50 px-2 py-1 text-xs font-semibold text-gold-700"
            >
              Switching basemap...
            </span>
          </div>

          <div v-if="mapEngine === 'leaflet'" id="map" class="h-full w-full"></div>
          <div v-else id="ol-map" class="h-full w-full"></div>
        </div>
      </div>

      <!-- Sidebar: Issue List & Stats -->
      <div class="w-96 overflow-hidden border-l border-gold-200 bg-white flex flex-col">
        <!-- Controls -->
        <div class="border-b border-gold-200 p-4">
          <div class="mb-3">
            <label class="text-text mb-1 block text-xs font-medium">View Mode</label>
            <div class="flex gap-2">
              <button
                @click="viewMode = 'list'"
                class="flex-1 rounded-lg px-3 py-2 text-xs font-semibold transition-all"
                :class="
                  viewMode === 'list'
                    ? 'bg-primary text-white'
                    : 'bg-cream-100 text-slate-700 hover:bg-cream-200'
                "
              >
                List
              </button>
              <button
                @click="viewMode = 'nearby'"
                class="flex-1 rounded-lg px-3 py-2 text-xs font-semibold transition-all"
                :class="
                  viewMode === 'nearby'
                    ? 'bg-primary text-white'
                    : 'bg-cream-100 text-slate-700 hover:bg-cream-200'
                "
              >
                Nearby
              </button>
              <button
                @click="viewMode = 'stats'"
                class="flex-1 rounded-lg px-3 py-2 text-xs font-semibold transition-all"
                :class="
                  viewMode === 'stats'
                    ? 'bg-primary text-white'
                    : 'bg-cream-100 text-slate-700 hover:bg-cream-200'
                "
              >
                Stats
              </button>
            </div>
          </div>

          <!-- Search/Filter -->
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search issues..."
            class="focus:ring-primary/20 focus:border-primary w-full rounded-lg border-gold-200 bg-cream-50 px-3 py-2 text-xs outline-none focus:bg-white focus:ring-2"
          />
        </div>

        <!-- Content Area -->
        <div class="flex-1 overflow-y-auto">
          <!-- List View -->
          <div v-if="viewMode === 'list'" class="space-y-2 p-4">
            <div v-if="filteredIssues.length === 0" class="text-text-light text-center text-sm py-8">
              No issues found
            </div>
            <button
              v-for="issue in filteredIssues"
              :key="issue.id"
              @click="focusIssueOnMap(issue)"
              class="hover:bg-primary-light/20 w-full border-l-4 border-gold-200 bg-cream-50 p-3 text-left transition-all"
              :class="selectedIssue?.id === issue.id ? 'border-gold-400 bg-gold-50' : ''"
            >
              <p class="text-text mb-1 line-clamp-1 font-semibold text-xs">{{ issue.title }}</p>
              <p class="text-text-light mb-2 line-clamp-1 text-xs">{{ issue.location }}</p>
              <div class="flex items-center justify-between gap-2">
                <span
                  class="inline-block rounded-full bg-cream-200 px-2 py-1 text-xs text-slate-700 capitalize"
                  >{{ issue.category.replace(/_/g, ' ') }}</span
                >
                <span
                  class="text-text-light text-xs"
                  >{{ formatDistance(issue.latitude, issue.longitude) }}</span
                >
              </div>
            </button>
          </div>

          <!-- Nearby View (within 500m) -->
          <div v-else-if="viewMode === 'nearby'" class="space-y-2 p-4">
            <div v-if="nearbyIssues.length === 0" class="text-text-light text-center text-sm py-8">
              No issues within 500m
            </div>
            <div v-else class="text-text-light mb-2 text-xs font-semibold">
              {{ nearbyIssues.length }} issue{{ nearbyIssues.length !== 1 ? 's' : '' }} nearby
            </div>
            <button
              v-for="issue in nearbyIssues"
              :key="issue.id"
              @click="focusIssueOnMap(issue)"
              class="hover:bg-primary-light/20 w-full border-l-4 border-gold-400 bg-gold-50 p-3 text-left transition-all"
            >
              <p class="text-text mb-1 line-clamp-1 font-semibold text-xs">{{ issue.title }}</p>
              <p class="text-text-light mb-2 line-clamp-1 text-xs">{{ issue.location }}</p>
              <div class="flex items-center justify-between gap-2">
                <span
                  class="inline-block rounded-full bg-gold-200 px-2 py-1 text-xs text-gold-700 capitalize"
                  >{{ issue.category.replace(/_/g, ' ') }}</span
                >
                <span class="text-primary text-xs font-semibold">
                  {{ formatDistance(issue.latitude, issue.longitude) }}
                </span>
              </div>
            </button>
          </div>

          <!-- Stats View -->
          <div v-else-if="viewMode === 'stats'" class="p-4">
            <div class="mb-4 space-y-2">
              <div class="rounded-lg bg-cream-50 p-3">
                <p class="text-text-light text-xs">Total Issues</p>
                <p class="text-text text-xl font-bold">{{ allIssues.length }}</p>
              </div>
              <div class="rounded-lg bg-gold-50 p-3">
                <p class="text-text-light text-xs">Nearby (500m)</p>
                <p class="text-primary text-xl font-bold">{{ nearbyIssues.length }}</p>
              </div>
            </div>

            <h3 class="text-text mb-2 text-xs font-bold">Issues by Category</h3>
            <div class="space-y-2">
              <div v-for="(count, category) in categoryStats" :key="category" class="text-xs">
                <div class="mb-1 flex justify-between">
                  <span class="text-text-light">{{ category.replace(/_/g, ' ') }}</span>
                  <span class="text-text font-semibold">{{ count }}</span>
                </div>
                <div class="h-1.5 overflow-hidden rounded-full bg-cream-200">
                  <div
                    class="h-full bg-primary"
                    :style="{ width: (count / allIssues.length) * 100 + '%' }"
                  ></div>
                </div>
              </div>
            </div>

            <h3 class="text-text mb-2 mt-4 text-xs font-bold">Hotspots</h3>
            <div class="space-y-2">
              <div
                v-for="(hotspot, idx) in topHotspots"
                :key="idx"
                class="rounded-lg border border-gold-200 p-2"
              >
                <p class="text-text line-clamp-1 text-xs font-semibold">{{ hotspot.name }}</p>
                <p class="text-text-light text-xs">{{ hotspot.count }} issues</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Current Location Info -->
        <div v-if="currentLocation" class="border-t border-gold-200 bg-gold-50 p-4 text-xs">
          <p class="text-primary mb-1 font-semibold">📍 Your Location</p>
          <p class="text-text-light">
            {{ currentLocation.latitude.toFixed(5) }}, {{ currentLocation.longitude.toFixed(5) }}
          </p>
          <p class="text-text-light mt-1">{{ locationSourceLabel }}</p>
          <p class="text-text-light mt-1" v-if="currentLocationAddress">{{ currentLocationAddress }}</p>
          <p class="text-text-light mt-1" v-else-if="isResolvingAddress">Resolving address...</p>
        </div>
      </div>
    </div>

    <!-- Permission Denied State -->
    <div v-else class="flex items-center justify-center bg-cream-50 py-20">
      <div class="max-w-md rounded-xl bg-white p-8 text-center shadow-sm">
        <MapPinIcon class="text-text-light mx-auto mb-4 h-12 w-12 opacity-50" />
        <h2 class="text-text mb-2 text-xl font-bold">Location Access Required</h2>
        <p class="text-text-light mb-6 text-sm">
          Allow access to your location to view the interactive issues map with clustering and heatmap
          analysis.
        </p>
        <button
          type="button"
          class="bg-primary hover:bg-primary/90 w-full rounded-lg px-4 py-3 text-sm font-semibold text-white transition-colors disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="isLocating"
          @click="allowLocationAccess"
        >
          <ArrowPathIcon v-if="isLocating" class="mr-2 inline h-4 w-4 animate-spin" />
          {{ isLocating ? 'Getting Location...' : 'Allow Access to Location' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick, watch } from 'vue'
import L from 'leaflet'
import 'leaflet.markercluster'
import 'leaflet.heat'
import 'ol/ol.css'
import OLMap from 'ol/Map'
import View from 'ol/View'
import Overlay from 'ol/Overlay'
import TileLayer from 'ol/layer/Tile'
import VectorLayer from 'ol/layer/Vector'
import HeatmapLayer from 'ol/layer/Heatmap'
import XYZ from 'ol/source/XYZ'
import VectorSource from 'ol/source/Vector'
import Feature from 'ol/Feature'
import Point from 'ol/geom/Point'
import CircleGeom from 'ol/geom/Circle'
import { fromLonLat } from 'ol/proj'
import Style from 'ol/style/Style'
import CircleStyle from 'ol/style/Circle'
import Fill from 'ol/style/Fill'
import Stroke from 'ol/style/Stroke'
import { useIssuesStore } from '../../stores/issuesStore'
import { ArrowLeftIcon, MapPinIcon, ArrowPathIcon } from '@heroicons/vue/24/outline'

const issuesStore = useIssuesStore()
const hasLocationPermission = ref(false)
const isLocating = ref(false)
const currentLocation = ref(null)
const currentLocationAddress = ref('')
const isResolvingAddress = ref(false)
const allIssues = ref([])
const selectedIssue = ref(null)
const searchQuery = ref('')
const viewMode = ref('list')
const mapEngine = ref('leaflet')
const isHeatmapVisible = ref(true)
const openLayersBaseStyle = ref('satellite')
const isBasemapTransitioning = ref(false)

let map = null
let currentLocationMarker = null
let currentAccuracyCircle = null
let markerClusterGroup = null
let heatmapLayer = null
let issueMarkers = {}
let olMap = null
let olBaseLayer = null
let olIssueLayer = null
let olHeatmapLayer = null
let olAccuracyLayer = null
let olCurrentLocationOverlay = null
let olIssueFeatureIndex = {}
let basemapTransitionFrame = null
let locationWatchId = null
let reverseGeocodeTimer = null
let lastReverseGeocodeLatLng = null
let weakGpsFallbackApplied = false

const GPS_ACCURACY_TARGET_METERS = 200

const locationSourceLabel = computed(() => {
  if (!currentLocation.value?.source || currentLocation.value.source === 'gps') {
    return 'Source: GPS'
  }

  return 'Source: IP fallback (approximate)'
})

const OPENLAYERS_BASEMAPS = {
  voyager: {
    urls: [
      'https://a.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',
      'https://b.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',
      'https://c.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',
      'https://d.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',
    ],
    attributions: '© OpenStreetMap contributors © CARTO',
    maxZoom: 20,
  },
  satellite: {
    urls: [
      'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
    ],
    attributions: 'Tiles © Esri',
    maxZoom: 18,
  },
}

const filteredIssues = computed(() => {
  return allIssues.value.filter(
    (issue) =>
      issue.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      issue.location.toLowerCase().includes(searchQuery.value.toLowerCase()),
  )
})

const nearbyIssues = computed(() => {
  if (!currentLocation.value) return []
  return allIssues.value.filter((issue) => {
    const distance = L.latLng(currentLocation.value.latitude, currentLocation.value.longitude).distanceTo(
      L.latLng(issue.latitude, issue.longitude),
    )
    return distance <= 500
  })
})

const categoryStats = computed(() => {
  const stats = {}
  allIssues.value.forEach((issue) => {
    stats[issue.category] = (stats[issue.category] || 0) + 1
  })
  return stats
})

const topHotspots = computed(() => {
  const hotspots = {}
  allIssues.value.forEach((issue) => {
    const key = `${issue.latitude.toFixed(3)},${issue.longitude.toFixed(3)}`
    if (!hotspots[key]) {
      hotspots[key] = { name: issue.location, count: 0, lat: issue.latitude, lng: issue.longitude }
    }
    hotspots[key].count++
  })
  return Object.values(hotspots)
    .sort((a, b) => b.count - a.count)
    .slice(0, 5)
})

const formatDistance = (lat, lng) => {
  if (!currentLocation.value) return '—'
  const distance = L.latLng(currentLocation.value.latitude, currentLocation.value.longitude).distanceTo(
    L.latLng(lat, lng),
  )
  if (distance < 1000) return `${Math.round(distance)}m`
  return `${(distance / 1000).toFixed(1)}km`
}

const formatGpsAccuracy = (location) => {
  const safeAccuracy = Math.max(1, Math.round(Number(location?.accuracy) || 0))

  if (location?.source === 'ip') {
    return `Approx. accuracy: ${safeAccuracy}m (IP)`
  }

  return `GPS accuracy: ${safeAccuracy}m`
}

const isGpsAccuracyAcceptable = (location) => {
  const accuracy = Number(location?.accuracy)
  if (!Number.isFinite(accuracy)) return false
  return accuracy <= GPS_ACCURACY_TARGET_METERS
}

const fetchJsonWithTimeout = async (url, timeoutMs = 7000) => {
  const controller = new AbortController()
  const timeoutId = setTimeout(() => controller.abort(), timeoutMs)

  try {
    const response = await fetch(url, {
      signal: controller.signal,
      headers: {
        Accept: 'application/json',
      },
    })

    if (!response.ok) {
      throw new Error(`Request failed with status ${response.status}`)
    }

    return await response.json()
  } finally {
    clearTimeout(timeoutId)
  }
}

const reverseGeocodeLocation = async (latitude, longitude) => {
  const reverseUrl = `https://nominatim.openstreetmap.org/reverse?lat=${encodeURIComponent(
    latitude,
  )}&lon=${encodeURIComponent(longitude)}&format=json&addressdetails=1&zoom=18`

  const data = await fetchJsonWithTimeout(reverseUrl, 8000)
  return data?.display_name || ''
}

const scheduleReverseGeocoding = (location) => {
  if (!location) return

  if (reverseGeocodeTimer) {
    clearTimeout(reverseGeocodeTimer)
    reverseGeocodeTimer = null
  }

  reverseGeocodeTimer = setTimeout(async () => {
    const locationKey = `${location.latitude.toFixed(5)},${location.longitude.toFixed(5)}`
    if (lastReverseGeocodeLatLng === locationKey) return

    try {
      isResolvingAddress.value = true
      const address = await reverseGeocodeLocation(location.latitude, location.longitude)
      currentLocationAddress.value = address || 'Address unavailable'
      lastReverseGeocodeLatLng = locationKey
    } catch {
      currentLocationAddress.value = 'Address unavailable'
    } finally {
      isResolvingAddress.value = false
    }
  }, 350)
}

const getIpFallbackLocation = async () => {
  try {
    const data = await fetchJsonWithTimeout('https://ipapi.co/json/', 7000)
    if (Number.isFinite(Number(data?.latitude)) && Number.isFinite(Number(data?.longitude))) {
      return {
        latitude: Number(data.latitude),
        longitude: Number(data.longitude),
        accuracy: 2500,
        timestamp: Date.now(),
        source: 'ip',
      }
    }
  } catch {
    // Try secondary provider below.
  }

  const data = await fetchJsonWithTimeout('https://ipinfo.io/json', 7000)
  const loc = data?.loc?.split(',') || []
  if (loc.length !== 2) {
    throw new Error('No location from IP providers')
  }

  return {
    latitude: Number(loc[0]),
    longitude: Number(loc[1]),
    accuracy: 3000,
    timestamp: Date.now(),
    source: 'ip',
  }
}

const allowLocationAccess = async () => {
  if (!navigator.geolocation) {
    alert('Geolocation is not supported by your browser.')
    return
  }

  isLocating.value = true

  const resolveReliableLocation = (position) => {
    const latitude = position.coords.latitude
    const longitude = position.coords.longitude
    const accuracy = position.coords.accuracy || 30

    const incoming = {
      latitude,
      longitude,
      accuracy,
      timestamp: position.timestamp || Date.now(),
    }

    const now = Date.now()
    if (now - incoming.timestamp > 30000) {
      return null
    }

    if (!currentLocation.value) {
      return incoming
    }

    const previous = currentLocation.value
    const previousAccuracy = Number.isFinite(previous.accuracy) ? previous.accuracy : 9999
    const incomingAccuracy = Number.isFinite(incoming.accuracy) ? incoming.accuracy : 9999

    const jumpDistance = L.latLng(previous.latitude, previous.longitude).distanceTo(
      L.latLng(incoming.latitude, incoming.longitude),
    )

    const clearlyWorseJump =
      incomingAccuracy > Math.max(previousAccuracy * 1.5, 80) &&
      jumpDistance > Math.max(previousAccuracy * 1.5, 120)

    if (clearlyWorseJump) {
      return null
    }

    const previousWeight = 1 / Math.max(previousAccuracy, 1)
    const incomingWeight = 1 / Math.max(incomingAccuracy, 1)
    const totalWeight = previousWeight + incomingWeight

    return {
      latitude: (previous.latitude * previousWeight + incoming.latitude * incomingWeight) / totalWeight,
      longitude:
        (previous.longitude * previousWeight + incoming.longitude * incomingWeight) / totalWeight,
      accuracy: Math.min(previousAccuracy, incomingAccuracy),
      timestamp: incoming.timestamp,
      source: 'gps',
    }
  }

  const applyIpFallback = async () => {
    try {
      const fallbackLocation = await getIpFallbackLocation()
      weakGpsFallbackApplied = true

      currentLocation.value = fallbackLocation
      hasLocationPermission.value = true
      currentLocationAddress.value = ''

      await nextTick()

      if (!map && !olMap) {
        await initActiveMap()
      } else {
        updateActiveMapForLocation()
      }

      scheduleReverseGeocoding(fallbackLocation)
    } catch {
      hasLocationPermission.value = false
      alert('Unable to detect your location. Please check GPS permission or network.')
    } finally {
      isLocating.value = false
    }
  }

  const applyLocationUpdate = async (position) => {
    const resolvedLocation = resolveReliableLocation(position)
    if (!resolvedLocation) {
      if (!hasLocationPermission.value) {
        isLocating.value = false
      }
      return
    }

    if (!isGpsAccuracyAcceptable(resolvedLocation)) {
      if (!weakGpsFallbackApplied) {
        await applyIpFallback()
      }
      return
    }

    weakGpsFallbackApplied = false

    currentLocation.value = {
      ...resolvedLocation,
      source: 'gps',
    }
    hasLocationPermission.value = true
    currentLocationAddress.value = ''

    await nextTick()

    if (!map && !olMap) {
      await initActiveMap()
    } else {
      updateActiveMapForLocation()
    }

    scheduleReverseGeocoding(currentLocation.value)

    isLocating.value = false
  }

  if (locationWatchId !== null) {
    navigator.geolocation.clearWatch(locationWatchId)
    locationWatchId = null
  }

  weakGpsFallbackApplied = false

  navigator.geolocation.getCurrentPosition(
    applyLocationUpdate,
    async () => {
      await applyIpFallback()
    },
    {
      enableHighAccuracy: true,
      timeout: 15000,
      maximumAge: 0,
    },
  )

  locationWatchId = navigator.geolocation.watchPosition(
    applyLocationUpdate,
    async () => {
      if (!currentLocation.value) {
        await applyIpFallback()
      }
    },
    {
      enableHighAccuracy: true,
      maximumAge: 0,
      timeout: 30000,
    },
  )
}

const initActiveMap = async () => {
  if (mapEngine.value === 'leaflet') {
    await initLeafletMap()
    return
  }

  await initOpenLayersMap()
}

const updateActiveMapForLocation = () => {
  if (mapEngine.value === 'leaflet') {
    updateLeafletMapForLocation()
    return
  }

  updateOpenLayersMapForLocation()
}

const initLeafletMap = async () => {
  if (!currentLocation.value) return

  const { latitude, longitude, accuracy } = currentLocation.value

  destroyLeafletMap()

  map = L.map('map').setView([latitude, longitude], 14)
  map.attributionControl.setPrefix('CivicConnect Issues')

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 19,
    minZoom: 10,
  }).addTo(map)

  // Current location indicator (blue dot)
  currentAccuracyCircle = L.circle([latitude, longitude], {
    radius: Math.max(accuracy, 20),
    color: '#3b82f6',
    weight: 1,
    opacity: 0.35,
    fillColor: '#60a5fa',
    fillOpacity: 0.15,
  }).addTo(map)

  currentLocationMarker = L.circleMarker([latitude, longitude], {
    radius: 8,
    color: '#ffffff',
    weight: 3,
    fillColor: '#2563eb',
    fillOpacity: 1,
  })
    .addTo(map)
    .bindPopup('<strong>Your location</strong>')
    .bindTooltip(formatGpsAccuracy(currentLocation.value), {
      permanent: true,
      direction: 'right',
      offset: [14, 0],
      className: 'gps-accuracy-tooltip',
      opacity: 1,
    })

  // Load issues
  await loadIssuesData()

  // Add markers with clustering
  addClusteredMarkers()

  // Add heatmap
  addHeatmapLayer()
}

const updateLeafletMapForLocation = () => {
  if (!currentLocation.value || !map) return

  const { latitude, longitude, accuracy } = currentLocation.value

  if (currentLocationMarker) map.removeLayer(currentLocationMarker)
  if (currentAccuracyCircle) map.removeLayer(currentAccuracyCircle)

  currentAccuracyCircle = L.circle([latitude, longitude], {
    radius: Math.max(accuracy, 20),
    color: '#3b82f6',
    weight: 1,
    opacity: 0.35,
    fillColor: '#60a5fa',
    fillOpacity: 0.15,
  }).addTo(map)

  currentLocationMarker = L.circleMarker([latitude, longitude], {
    radius: 8,
    color: '#ffffff',
    weight: 3,
    fillColor: '#2563eb',
    fillOpacity: 1,
  })
    .addTo(map)
    .bindPopup('<strong>Your location</strong>')
    .bindTooltip(formatGpsAccuracy(currentLocation.value), {
      permanent: true,
      direction: 'right',
      offset: [14, 0],
      className: 'gps-accuracy-tooltip',
      opacity: 1,
    })

  addClusteredMarkers()
  addHeatmapLayer()

  map.setView([latitude, longitude], 14)
}

const loadIssuesData = async () => {
  try {
    const issues = await issuesStore.fetchIssues({ limit: 1000 })
    allIssues.value = (issues || [])
      .map((issue) => {
        const latitude = Number.parseFloat(issue.latitude)
        const longitude = Number.parseFloat(issue.longitude)
        return {
          ...issue,
          latitude,
          longitude,
        }
      })
      .filter((issue) => Number.isFinite(issue.latitude) && Number.isFinite(issue.longitude))
  } catch (err) {
    console.warn('Issues map fallback to cached store data:', err)
    allIssues.value = (issuesStore.issues || [])
      .map((issue) => {
        const latitude = Number.parseFloat(issue.latitude)
        const longitude = Number.parseFloat(issue.longitude)
        return {
          ...issue,
          latitude,
          longitude,
        }
      })
      .filter((issue) => Number.isFinite(issue.latitude) && Number.isFinite(issue.longitude))
  }
}

const addClusteredMarkers = () => {
  if (!map) return
  if (markerClusterGroup) map.removeLayer(markerClusterGroup)

  markerClusterGroup = L.markerClusterGroup({
    maxClusterRadius: 80,
    spiderfyOnMaxZoom: true,
    showCoverageOnHover: true,
    zoomToBoundsOnClick: true,
  })

  allIssues.value.forEach((issue) => {
    const isNearby = nearbyIssues.value.find((i) => i.id === issue.id)
    const color = isNearby ? '#3b82f6' : '#ef4444'

    const marker = L.circleMarker([issue.latitude, issue.longitude], {
      radius: isNearby ? 7 : 5,
      color: color,
      weight: 2,
      opacity: 1,
      fillColor: color,
      fillOpacity: 0.7,
    })

    marker.bindPopup(
      `<div style="width: 200px; font-size: 12px;">
        <strong>${issue.title}</strong><br>
        <small>${issue.location}</small><br>
        <small>Category: ${issue.category}</small><br>
        <small>Distance: ${formatDistance(issue.latitude, issue.longitude)}</small>
      </div>`,
    )

    marker.on('click', () => {
      selectedIssue.value = issue
    })

    markerClusterGroup.addLayer(marker)
    issueMarkers[issue.id] = marker
  })

  map.addLayer(markerClusterGroup)
}

const addHeatmapLayer = () => {
  if (!map) return
  if (heatmapLayer) map.removeLayer(heatmapLayer)

  const heatData = allIssues.value.map((issue) => [
    issue.latitude,
    issue.longitude,
    0.8, // intensity
  ])

  heatmapLayer = L.heatLayer(heatData, {
    radius: 25,
    blur: 15,
    maxZoom: 17,
    gradient: { 0.4: 'blue', 0.65: 'lime', 1.0: 'red' },
  })

  applyLeafletHeatmapVisibility()
}

const applyLeafletHeatmapVisibility = () => {
  if (!map || !heatmapLayer) return

  const hasHeatLayer = map.hasLayer(heatmapLayer)

  if (isHeatmapVisible.value && !hasHeatLayer) {
    map.addLayer(heatmapLayer)
  }

  if (!isHeatmapVisible.value && hasHeatLayer) {
    map.removeLayer(heatmapLayer)
  }
}

const issueStyle = (feature) => {
  const issue = feature.get('issue')
  const isNearby = nearbyIssues.value.some((candidate) => candidate.id === issue.id)
  const color = isNearby ? '#3b82f6' : '#ef4444'

  return new Style({
    image: new CircleStyle({
      radius: isNearby ? 7 : 5,
      fill: new Fill({ color }),
      stroke: new Stroke({ color, width: 2 }),
    }),
  })
}

const buildIssueFeatures = () => {
  return allIssues.value.map((issue) => {
    const feature = new Feature({
      geometry: new Point(fromLonLat([issue.longitude, issue.latitude])),
      issue,
    })

    return feature
  })
}

const updateOpenLayersData = () => {
  if (!olMap || !currentLocation.value || !olIssueLayer || !olHeatmapLayer) {
    return
  }

  const issueFeatures = buildIssueFeatures()
  olIssueFeatureIndex = {}

  issueFeatures.forEach((feature) => {
    const issue = feature.get('issue')
    olIssueFeatureIndex[issue.id] = feature
  })

  olIssueLayer.setSource(new VectorSource({ features: issueFeatures }))
  olHeatmapLayer.setSource(new VectorSource({ features: issueFeatures }))

  if (olAccuracyLayer && currentLocation.value) {
    const center = fromLonLat([currentLocation.value.longitude, currentLocation.value.latitude])
    const radius = Math.max(Number(currentLocation.value.accuracy) || 30, 20)
    const accuracyFeature = new Feature({
      geometry: new CircleGeom(center, radius),
    })
    olAccuracyLayer.setSource(new VectorSource({ features: [accuracyFeature] }))
  }

  olHeatmapLayer.setVisible(isHeatmapVisible.value)

  updateOpenLayersCurrentLocationOverlay()
}

const createOpenLayersBaseSource = () => {
  const selected = OPENLAYERS_BASEMAPS[openLayersBaseStyle.value] || OPENLAYERS_BASEMAPS.voyager

  return new XYZ({
    urls: selected.urls,
    attributions: selected.attributions,
    crossOrigin: 'anonymous',
    transition: 250,
    maxZoom: selected.maxZoom,
  })
}

const switchOpenLayersBasemapWithAnimation = () => {
  if (!olMap || !olBaseLayer) return

  const newLayer = new TileLayer({
    source: createOpenLayersBaseSource(),
    opacity: 0,
  })

  olMap.getLayers().insertAt(0, newLayer)

  const oldLayer = olBaseLayer
  olBaseLayer = newLayer
  isBasemapTransitioning.value = true

  if (basemapTransitionFrame) {
    cancelAnimationFrame(basemapTransitionFrame)
    basemapTransitionFrame = null
  }

  const duration = 420
  const start = performance.now()

  const animate = (now) => {
    const progress = Math.min((now - start) / duration, 1)
    oldLayer.setOpacity(1 - progress)
    newLayer.setOpacity(progress)

    if (progress < 1) {
      basemapTransitionFrame = requestAnimationFrame(animate)
      return
    }

    olMap.removeLayer(oldLayer)
    isBasemapTransitioning.value = false
    basemapTransitionFrame = null
  }

  basemapTransitionFrame = requestAnimationFrame(animate)
}

const createOpenLayersLocationOverlay = () => {
  const container = document.createElement('div')
  container.className = 'ol-current-location-marker'
  container.innerHTML = `
    <span class="ol-current-location-pulse"></span>
    <span class="ol-current-location-core"></span>
    <span class="ol-current-location-badge"></span>
  `

  return new Overlay({
    element: container,
    positioning: 'center-center',
    stopEvent: false,
    offset: [0, 0],
  })
}

const updateOpenLayersCurrentLocationOverlay = () => {
  if (!olCurrentLocationOverlay || !currentLocation.value) return

  const overlayElement = olCurrentLocationOverlay.getElement()
  const accuracyBadge = overlayElement?.querySelector('.ol-current-location-badge')
  if (accuracyBadge) {
    accuracyBadge.textContent = formatGpsAccuracy(currentLocation.value)
  }

  olCurrentLocationOverlay.setPosition(
    fromLonLat([currentLocation.value.longitude, currentLocation.value.latitude]),
  )
}

const initOpenLayersMap = async () => {
  if (!currentLocation.value) return

  const { latitude, longitude } = currentLocation.value
  destroyOpenLayersMap()

  await loadIssuesData()

  olIssueLayer = new VectorLayer({
    source: new VectorSource(),
    style: issueStyle,
  })

  olHeatmapLayer = new HeatmapLayer({
    source: new VectorSource(),
    blur: 15,
    radius: 25,
    visible: isHeatmapVisible.value,
    weight: () => 0.8,
  })

  olAccuracyLayer = new VectorLayer({
    source: new VectorSource(),
    style: new Style({
      fill: new Fill({ color: 'rgba(96, 165, 250, 0.15)' }),
      stroke: new Stroke({ color: '#3b82f6', width: 1 }),
    }),
  })

  olBaseLayer = new TileLayer({
    source: createOpenLayersBaseSource(),
  })

  olCurrentLocationOverlay = createOpenLayersLocationOverlay()

  olMap = new OLMap({
    target: 'ol-map',
    layers: [
      olBaseLayer,
      olAccuracyLayer,
      olHeatmapLayer,
      olIssueLayer,
    ],
    overlays: [olCurrentLocationOverlay],
    view: new View({
      center: fromLonLat([longitude, latitude]),
      zoom: 14,
      minZoom: 10,
      maxZoom: (OPENLAYERS_BASEMAPS[openLayersBaseStyle.value] || OPENLAYERS_BASEMAPS.voyager).maxZoom,
    }),
  })

  updateOpenLayersCurrentLocationOverlay()

  olMap.on('singleclick', (event) => {
    let clickedIssue = null

    olMap.forEachFeatureAtPixel(event.pixel, (feature, layer) => {
      if (layer === olIssueLayer) {
        clickedIssue = feature.get('issue')
      }
    })

    if (clickedIssue) {
      selectedIssue.value = clickedIssue
    }
  })

  updateOpenLayersData()
}

const updateOpenLayersMapForLocation = () => {
  if (!olMap || !currentLocation.value) return

  const { latitude, longitude } = currentLocation.value
  olMap.getView().setCenter(fromLonLat([longitude, latitude]))
  olMap.getView().setZoom(Math.min(14, olMap.getView().getMaxZoom() || 14))
  updateOpenLayersData()
  updateOpenLayersCurrentLocationOverlay()
}

const toggleHeatmapVisibility = () => {
  isHeatmapVisible.value = !isHeatmapVisible.value
  applyHeatmapVisibility()
}

const applyHeatmapVisibility = () => {
  applyLeafletHeatmapVisibility()

  if (olHeatmapLayer) {
    olHeatmapLayer.setVisible(isHeatmapVisible.value)
  }
}

const focusIssueOnMap = (issue) => {
  selectedIssue.value = issue

  if (mapEngine.value === 'leaflet') {
    if (!map) return
    map.setView([issue.latitude, issue.longitude], 17)

    if (issueMarkers[issue.id]) {
      issueMarkers[issue.id].openPopup()
    }
    return
  }

  if (!olMap) return

  const feature = olIssueFeatureIndex[issue.id]
  const coordinates = feature?.getGeometry()?.getCoordinates()
  if (coordinates) {
    olMap.getView().animate({ center: coordinates, zoom: 17, duration: 350 })
  }
}

const destroyLeafletMap = () => {
  if (map) {
    map.remove()
    map = null
  }

  markerClusterGroup = null
  heatmapLayer = null
  currentLocationMarker = null
  currentAccuracyCircle = null
  issueMarkers = {}
}

const destroyOpenLayersMap = () => {
  if (basemapTransitionFrame) {
    cancelAnimationFrame(basemapTransitionFrame)
    basemapTransitionFrame = null
  }

  if (olMap) {
    olMap.setTarget(null)
    olMap = null
  }

  isBasemapTransitioning.value = false
  olBaseLayer = null
  olAccuracyLayer = null
  olIssueLayer = null
  olHeatmapLayer = null
  olCurrentLocationOverlay = null
  olIssueFeatureIndex = {}
}

watch(mapEngine, async () => {
  if (!hasLocationPermission.value || !currentLocation.value) return

  await nextTick()

  if (mapEngine.value === 'leaflet') {
    destroyOpenLayersMap()
    await initLeafletMap()
    return
  }

  destroyLeafletMap()
  await initOpenLayersMap()
})

watch(isHeatmapVisible, () => {
  applyHeatmapVisibility()
})

watch(openLayersBaseStyle, () => {
  if (olBaseLayer) {
    const view = olMap?.getView()
    if (view) {
      view.setMaxZoom((OPENLAYERS_BASEMAPS[openLayersBaseStyle.value] || OPENLAYERS_BASEMAPS.voyager).maxZoom)
      const nextZoom = Math.min(view.getZoom() || 14, view.getMaxZoom() || 14)
      view.setZoom(nextZoom)
    }

    switchOpenLayersBasemapWithAnimation()
  }
})

onMounted(() => {
  // Component mounted but waiting for location permission
})

onBeforeUnmount(() => {
  if (locationWatchId !== null && navigator.geolocation) {
    navigator.geolocation.clearWatch(locationWatchId)
    locationWatchId = null
  }

  if (reverseGeocodeTimer) {
    clearTimeout(reverseGeocodeTimer)
    reverseGeocodeTimer = null
  }

  destroyLeafletMap()
  destroyOpenLayersMap()
})
</script>

<style scoped>
.animate-fade-in-up {
  animation: fadeInUp 0.5s ease-out forwards;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

#map {
  position: relative;
  z-index: 1;
}

#ol-map {
  position: relative;
  z-index: 1;
}

:deep(.ol-current-location-marker) {
  position: relative;
  width: 24px;
  height: 24px;
}

:deep(.ol-current-location-pulse) {
  position: absolute;
  inset: 0;
  border-radius: 9999px;
  background: rgba(37, 99, 235, 0.24);
  animation: locationPulse 1.8s ease-out infinite;
}

:deep(.ol-current-location-core) {
  position: absolute;
  left: 50%;
  top: 50%;
  width: 10px;
  height: 10px;
  transform: translate(-50%, -50%);
  border-radius: 9999px;
  background: #2563eb;
  border: 2px solid #ffffff;
  box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.18);
}

:deep(.ol-current-location-badge) {
  position: absolute;
  left: calc(100% + 8px);
  top: 50%;
  transform: translateY(-50%);
  white-space: nowrap;
  border-radius: 9999px;
  border: 1px solid rgba(147, 197, 253, 0.9);
  background: rgba(239, 246, 255, 0.95);
  color: #1d4ed8;
  font-size: 11px;
  font-weight: 700;
  line-height: 1;
  padding: 5px 8px;
  box-shadow: 0 2px 8px rgba(37, 99, 235, 0.14);
}

:deep(.gps-accuracy-tooltip) {
  background: rgba(239, 246, 255, 0.97);
  border: 1px solid rgba(147, 197, 253, 0.9);
  border-radius: 9999px;
  color: #1d4ed8;
  font-size: 11px;
  font-weight: 700;
  line-height: 1;
  padding: 6px 9px;
  box-shadow: 0 2px 8px rgba(37, 99, 235, 0.14);
}

:deep(.gps-accuracy-tooltip::before) {
  border-right-color: rgba(147, 197, 253, 0.9);
}

@keyframes locationPulse {
  0% {
    transform: scale(0.7);
    opacity: 0.85;
  }
  70% {
    transform: scale(1.6);
    opacity: 0.12;
  }
  100% {
    transform: scale(1.8);
    opacity: 0;
  }
}

:deep(.leaflet-container) {
  font-family: inherit;
}
</style>
