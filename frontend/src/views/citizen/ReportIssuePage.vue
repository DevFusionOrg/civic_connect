<template>
  <div class="animate-fade-in-up mx-auto max-w-4xl px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
      <router-link
        to="/issues"
        class="text-slate-600 hover:text-gold-700 group mb-4 inline-flex items-center gap-2 font-medium transition-colors"
      >
        <ArrowLeftIcon class="h-4 w-4 transition-transform group-hover:-translate-x-1" />
        Back to Issues
      </router-link>
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-slate-900 mb-2 text-3xl font-bold md:text-4xl">Report an Issue</h1>
          <p class="text-slate-600 text-lg">
            Help improve our community by reporting problems you see.
          </p>
        </div>
      </div>
    </div>

    <!-- Form Card -->
    <div class="shadow-soft overflow-hidden rounded-2xl border border-gold-200/50 bg-white backdrop-blur-sm">
      <form @submit.prevent="submitIssue" class="space-y-8 p-6 md:p-8">
        <!-- Section: Category -->
        <section>
          <h3 class="text-slate-900 mb-4 flex items-center gap-2 text-lg font-bold">
            <span
              class="bg-gold-100 text-gold-700 flex h-6 w-6 items-center justify-center rounded-full text-sm"
              >1</span
            >
            What type of issue is this?
          </h3>
          <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            <button
              v-for="cat in categories"
              :key="cat.value"
              type="button"
              class="group relative cursor-pointer rounded-xl focus:ring-2 focus:ring-gold-400 focus:ring-offset-2 focus:outline-none"
              :aria-pressed="form.category === cat.value"
              @click.prevent="selectCategory(cat.value)"
            >
              <div
                class="group-hover:border-gold-300 rounded-xl border-2 p-4 text-center transition-all duration-200"
                :class="
                  form.category === cat.value
                    ? 'border-gold-400 bg-gold-100/40 shadow-sm'
                    : 'border-gold-100'
                "
              >
                <!-- Icon placeholder (could be dynamic based on category) -->
                <div
                  class="text-gold-600 mb-2 opacity-80 transition-transform duration-200 group-hover:scale-110"
                >
                  <FolderIcon class="mx-auto h-8 w-8" />
                </div>
                <span
                  class="block text-sm font-semibold transition-colors"
                  :class="form.category === cat.value ? 'text-gold-800' : 'text-slate-700'"
                  >{{ cat.label }}</span
                >
              </div>
            </button>
          </div>
          <p v-if="errors.category" class="text-red-600 mt-2 flex items-center gap-1 text-sm">
            <ExclamationCircleIcon class="h-4 w-4" /> {{ errors.category }}
          </p>
        </section>

        <!-- Section: Details -->
        <section class="grid gap-8 md:grid-cols-2">
          <div class="space-y-6">
            <h3 class="text-slate-900 mb-4 flex items-center gap-2 text-lg font-bold">
              <span
                class="bg-gold-100 text-gold-700 flex h-6 w-6 items-center justify-center rounded-full text-sm"
                >2</span
              >
              Describe the problem
            </h3>

            <!-- Title -->
            <div>
              <label for="title" class="text-slate-700 mb-1 block text-sm font-medium"
                >Title <span class="text-red-600">*</span></label
              >
              <input
                type="text"
                id="title"
                v-model="form.title"
                placeholder="e.g., Pothole on Main St"
                class="focus:ring-gold-200 focus:border-gold-400 w-full rounded-lg border border-gold-200 bg-cream-50 px-4 py-2.5 transition-all outline-none focus:bg-white focus:ring-2"
              />
              <p v-if="errors.title" class="text-red-600 mt-1 text-xs">{{ errors.title }}</p>
            </div>

            <!-- Description -->
            <div>
              <label for="description" class="text-slate-700 mb-1 block text-sm font-medium"
                >Description <span class="text-red-600">*</span></label
              >
              <textarea
                id="description"
                v-model="form.description"
                rows="4"
                placeholder="Please describe the issue in detail..."
                class="focus:ring-gold-200 focus:border-gold-400 w-full resize-none rounded-lg border border-gold-200 bg-cream-50 px-4 py-2.5 transition-all outline-none focus:bg-white focus:ring-2"
              ></textarea>
              <p v-if="errors.description" class="text-red-600 mt-1 text-xs">
                {{ errors.description }}
              </p>
            </div>

            <!-- Priority -->
            <div>
              <label class="text-slate-700 mb-2 block text-sm font-medium">Priority Level</label>
              <div class="flex gap-3">
                <label class="inline-flex cursor-pointer items-center">
                  <input type="radio" v-model="form.priority" value="low" class="peer sr-only" />
                  <span
                    class="text-slate-600 peer-checked:text-slate-900 rounded-lg border border-gold-200 px-3 py-1.5 text-sm font-medium transition-colors peer-checked:border-gold-300 peer-checked:bg-gold-100"
                    >Low</span
                  >
                </label>
                <label class="inline-flex cursor-pointer items-center">
                  <input type="radio" v-model="form.priority" value="medium" class="peer sr-only" />
                  <span
                    class="text-slate-600 peer-checked:bg-gold-200 peer-checked:text-gold-900 peer-checked:border-gold-400 rounded-lg border border-gold-200 px-3 py-1.5 text-sm font-medium transition-colors"
                    >Medium</span
                  >
                </label>
                <label class="inline-flex cursor-pointer items-center">
                  <input type="radio" v-model="form.priority" value="high" class="peer sr-only" />
                  <span
                    :class="
                      aiResult.autoFilled && form.priority === 'high'
                        ? 'ring-2 ring-saffron-400 ring-offset-1'
                        : ''
                    "
                    class="text-slate-600 peer-checked:bg-saffron-200 peer-checked:text-saffron-900 peer-checked:border-saffron-400 rounded-lg border border-gold-200 px-3 py-1.5 text-sm font-medium transition-colors"
                    >High</span
                  >
                </label>
              </div>
              <p class="mt-2 inline-block rounded-lg bg-saffron-50 p-2 text-xs text-saffron-700">
                <ExclamationCircleIcon class="mr-1 inline h-3 w-3" />
                High priority is for immediate safety hazards.
              </p>
            </div>
          </div>

          <!-- Image Upload -->
          <div>
            <h3 class="text-slate-900 mb-4 flex items-center gap-2 text-lg font-bold">
              <span
                class="bg-gold-100 text-gold-700 flex h-6 w-6 items-center justify-center rounded-full text-sm"
                >3</span
              >
              Add a photo
            </h3>
            <div
              class="hover:border-gold-300 group relative flex h-[calc(100%-3rem)] cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gold-200 bg-cream-50 p-6 text-center transition-all hover:bg-white"
              @dragover.prevent
              @drop.prevent="handleFileDrop"
              @click="triggerFileInput"
            >
              <input
                type="file"
                ref="fileInput"
                class="hidden"
                accept="image/*"
                @change="handleFileSelect"
              />

              <div v-if="!form.image" class="pointer-events-none flex flex-col items-center">
                <div
                  class="mb-3 rounded-full bg-white p-3 shadow-sm transition-transform group-hover:scale-110"
                >
                  <PhotoIcon class="text-gold-600 h-8 w-8" />
                </div>
                <p class="text-slate-900 text-sm font-semibold">Click to upload or drag & drop</p>
                <p class="text-slate-600 mt-1 text-xs">JPG, PNG, GIF up to 5MB</p>
              </div>

              <div v-else class="flex w-full flex-col items-center">
                <div
                  v-if="imagePreview"
                  class="relative mb-3 h-48 w-full overflow-hidden rounded-lg shadow-sm"
                >
                  <img :src="imagePreview" class="h-full w-full object-cover" />
                  <button
                    @click.stop="removeImage"
                    class="text-red-600 absolute top-2 right-2 rounded-full bg-white/90 p-1.5 shadow-sm transition-colors hover:bg-white"
                  >
                    <TrashIcon class="h-4 w-4" />
                  </button>
                </div>
                <p class="text-green-600 mb-2 flex items-center text-sm font-semibold">
                  <CheckCircleIcon class="mr-1 h-5 w-5" />
                  {{ form.image.name }}
                </p>
                <p v-if="isRunningAiDetection" class="mb-2 text-xs font-medium text-gold-700">
                  Running AI detection...
                </p>
                <div
                  v-if="aiResult.checked"
                  class="w-full rounded-lg border px-3 py-2 text-left text-sm"
                  :class="
                    aiResult.label === 'pothole'
                      ? 'border-green-200 bg-green-50 text-green-700'
                      : 'border-gold-200 bg-cream-50 text-slate-700'
                  "
                >
                  <p class="font-semibold">
                    AI Detected:
                    {{ aiResult.label === 'pothole' ? 'Pothole ✅' : 'No pothole detected' }}
                  </p>
                  <p class="text-xs">Confidence: {{ aiResult.confidenceText }}</p>
                  <p v-if="aiResult.autoFilled" class="text-xs font-medium">
                    Category and priority auto-filled by AI.
                  </p>
                  <div v-if="aiResult.annotatedImageUrl" class="mt-2">
                    <p class="mb-1 text-xs font-medium">Detection Preview</p>
                    <img
                      :src="aiResult.annotatedImageUrl"
                      alt="AI detection preview"
                      class="h-40 w-full rounded-md border border-gold-200 object-cover"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- Section: Map -->
        <section>
          <h3 class="text-text mb-4 flex items-center gap-2 text-lg font-bold">
            <span
              class="bg-primary/10 text-primary flex h-6 w-6 items-center justify-center rounded-full text-sm"
              >4</span
            >
            Location
          </h3>

          <!-- Location Access + Search -->
          <div class="mb-4">
            <button
              type="button"
              class="bg-primary hover:bg-primary/90 mb-3 inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold text-white transition-colors disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="isLocating"
              @click="allowLocationAccess"
            >
              <ArrowPathIcon v-if="isLocating" class="mr-2 h-4 w-4 animate-spin" />
              {{ hasLocationPermission ? 'Refresh Current Location' : 'Allow Access to Location' }}
            </button>

            <p class="text-text-light mb-2 text-xs">
              Location permission is required to report an issue near your current position.
            </p>

            <label for="location" class="text-text mb-1 block text-sm font-medium"
              >Address or Landmark <span class="text-danger">*</span></label
            >
            <div class="relative">
              <input
                type="text"
                id="location"
                v-model="form.location"
                placeholder="e.g., Mall Road, near bus stand"
                class="focus:ring-primary/20 focus:border-primary w-full rounded-lg border-gold-200 bg-cream-50 px-4 py-2.5 transition-all outline-none focus:bg-white focus:ring-2 disabled:cursor-not-allowed disabled:opacity-60"
                :disabled="!hasLocationPermission"
                autocomplete="off"
                @input="handleLocationInput"
                @focus="handleLocationFocus"
                @blur="handleLocationBlur"
              />

              <div
                v-if="showLocationSuggestions"
                class="absolute z-20 mt-1 max-h-64 w-full overflow-y-auto rounded-lg border border-gold-200 bg-white shadow-lg"
              >
                <p v-if="isSearchingLocation" class="text-text-light px-4 py-3 text-sm">
                  Searching locations...
                </p>
                <button
                  v-for="suggestion in locationSuggestions"
                  :key="suggestion.id"
                  type="button"
                  class="hover:bg-primary-light/20 w-full border-b border-gold-100 px-4 py-2 text-left text-sm text-slate-800 last:border-b-0"
                  @mousedown.prevent="selectLocationSuggestion(suggestion)"
                >
                  {{ suggestion.label }}
                </button>
                <p
                  v-if="!isSearchingLocation && locationSuggestions.length === 0"
                  class="text-text-light px-4 py-3 text-sm"
                >
                  No India-based matches found near your location.
                </p>
              </div>
            </div>
            <p v-if="errors.location" class="text-danger mt-1 text-xs">{{ errors.location }}</p>
            <p class="text-text-light mt-1 text-xs">
              <MapPinIcon class="mr-1 inline h-3 w-3" />
              Suggestions are limited to India and filtered around your current location.
            </p>
          </div>

          <div
            v-if="hasLocationPermission"
            class="overflow-hidden rounded-xl border border-gold-200 shadow-sm"
          >
            <div id="map" class="z-0 h-80 w-full"></div>
          </div>
          <div
            v-else
            class="text-slate-600 rounded-xl border border-dashed border-gold-300 bg-cream-50 p-5 text-sm"
          >
            Please allow location access to load the map.
          </div>
          <div class="mt-2 flex items-center justify-between text-sm">
            <p v-if="form.latitude" class="text-green-600 flex items-center font-medium">
              <MapPinIcon class="mr-1 h-4 w-4" />
              Location selected: {{ form.latitude.toFixed(6) }}, {{ form.longitude.toFixed(6) }}
            </p>
            <p
              v-else-if="hasLocationPermission && currentLocation"
              class="text-gold-600 flex items-center font-medium"
            >
              <MapPinIcon class="mr-1 h-4 w-4" />
              You are here: {{ currentLocation.latitude.toFixed(6) }},
              {{ currentLocation.longitude.toFixed(6) }}
            </p>
            <p v-else class="text-slate-600 flex items-center">
              <ExclamationCircleIcon class="mr-1 h-4 w-4" />
              Allow location access to start selecting issue location
            </p>
          </div>
          <p v-if="error && error.includes('location')" class="text-red-600 mt-2 text-sm">
            {{ error }}
          </p>
        </section>

        <!-- Terms & Submit -->
        <div class="border-t border-gold-200 pt-6">
          <div class="mb-6 flex items-start gap-3">
            <input
              id="terms"
              v-model="form.agreeToTerms"
              type="checkbox"
              class="text-gold-600 focus:ring-gold-500 mt-1 rounded border-gold-200"
              required
            />
            <label for="terms" class="text-slate-600 cursor-pointer text-sm select-none">
              I confirm that this information is accurate and will help improve our community
            </label>
          </div>

          <!-- Submit Button -->
          <div class="flex gap-4">
            <button
              type="submit"
              :disabled="isSubmitDisabled"
              class="bg-gold-600 hover:bg-gold-700 flex-1 py-3 rounded-lg text-white font-semibold transition-colors disabled:opacity-60"
            >
              <ArrowPathIcon v-if="isSubmitting" class="mr-2 h-5 w-5 animate-spin" />
              {{ isSubmitting ? 'Submitting Report...' : 'Submit Report' }}
            </button>
            <router-link
              to="/dashboard"
              class="text-slate-700 flex-1 rounded-lg border border-gold-200 bg-white px-4 py-3 text-center font-semibold transition-colors hover:border-gold-300 hover:bg-cream-50"
            >
              Cancel
            </router-link>
          </div>

          <p v-if="submitDisabledReason" class="mt-2 text-xs text-slate-600">
            {{ submitDisabledReason }}
          </p>

          <!-- Error Message -->
          <div
            v-if="error && !error.includes('location')"
            class="bg-red-50/20 border-red-200/50 mt-4 rounded-lg border p-4"
          >
            <p class="text-red-600 flex items-center text-sm">
              <ExclamationCircleIcon class="mr-2 h-5 w-5" />
              {{ error }}
            </p>
          </div>

          <!-- Success Message -->
          <div
            v-if="successMessage"
            class="bg-green-50/20 border-green-200/50 mt-4 rounded-lg border p-4"
          >
            <p class="text-green-600 flex items-center text-sm">
              <CheckCircleIcon class="mr-2 h-5 w-5" />
              {{ successMessage }}
            </p>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onBeforeUnmount, nextTick } from 'vue'
import L from 'leaflet'
import { useIssuesStore } from '../../stores/issuesStore'
import { useRouter } from 'vue-router'
import {
  ArrowLeftIcon,
  ExclamationCircleIcon,
  ArrowPathIcon,
  PhotoIcon,
  MapPinIcon,
  TrashIcon,
  CheckCircleIcon,
  FolderIcon,
} from '@heroicons/vue/24/outline' // Switched to outline for cleaner look

const issuesStore = useIssuesStore()
const router = useRouter()

const categories = issuesStore.issueCategoryOptions

const form = ref({
  title: '',
  description: '',
  category: '',
  priority: 'medium',
  location: '',
  latitude: null,
  longitude: null,
  image: null,
  agreeToTerms: false,
})

const isRoadCategorySelected = computed(
  () => issuesStore.normalizeIssueCategory(form.value.category) === 'roads',
)

const isSubmitDisabled = computed(() => {
  if (isSubmitting.value) return true
  if (isRunningAiDetection.value) return true
  return false
})

const submitDisabledReason = computed(() => {
  if (isSubmitting.value) return 'Submitting your report...'
  if (!form.value.category) return 'Select an issue category to continue.'
  if (!form.value.latitude || !form.value.longitude) return 'Select your issue location on the map to enable submit.'
  if (isRunningAiDetection.value) return 'AI is analyzing your uploaded image. Please wait.'
  if (isRoadCategorySelected.value && !form.value.image) return 'Road issues require a pothole image before submission.'
  if (isRoadCategorySelected.value && form.value.image && !(aiResult.value.checked && aiResult.value.autoFilled)) {
    return 'Upload a clear pothole image and wait for AI verification.'
  }
  return ''
})

const imagePreview = ref(null)
const isRunningAiDetection = ref(false)
const aiResult = ref({
  checked: false,
  label: 'none',
  confidenceText: '0%',
  autoFilled: false,
  annotatedImageUrl: null,
})
const isSubmitting = ref(false)
const error = ref('')
const successMessage = ref('')
const fileInput = ref(null)
const locationSuggestions = ref([])
const isSearchingLocation = ref(false)
const showLocationSuggestions = ref(false)
const hasLocationPermission = ref(false)
const isLocating = ref(false)
const currentLocation = ref(null)
const PROXIMITY_RADIUS_METERS = 300
let map = null
let marker = null
let currentLocationMarker = null
let currentAccuracyCircle = null
let locationSearchTimeout = null
let locationSearchAbortController = null
const SEARCH_BOX_DELTA = 0.09

const triggerFileInput = () => {
  fileInput.value.click()
}

const selectCategory = (categoryValue) => {
  form.value.category = categoryValue
  if (errors.value.category) {
    errors.value.category = ''
  }
  if (error.value) {
    error.value = ''
  }
}

const handleFileSelect = (event) => {
  const file = event.target.files[0]
  if (file) {
    validateAndSetImage(file)
  }
}

const handleFileDrop = (event) => {
  const files = event.dataTransfer.files
  if (files.length > 0) {
    validateAndSetImage(files[0])
  }
}

const validateAndSetImage = (file) => {
  const validTypes = ['image/jpeg', 'image/png', 'image/gif']
  const maxSize = 5 * 1024 * 1024 // 5MB

  if (!validTypes.includes(file.type)) {
    error.value = 'Please select a valid image format (JPG, PNG, GIF)'
    return
  }

  if (file.size > maxSize) {
    error.value = 'Image size must be less than 5MB'
    return
  }

  form.value.image = file
  aiResult.value = {
    checked: false,
    label: 'none',
    confidenceText: '0%',
    autoFilled: false,
    annotatedImageUrl: null,
  }
  error.value = ''

  // Create preview
  const reader = new FileReader()
  reader.onload = (e) => {
    imagePreview.value = e.target.result
  }
  reader.readAsDataURL(file)

  syncAiDetectionWithForm()
}

const resetAiResult = () => {
  aiResult.value = {
    checked: false,
    label: 'none',
    confidenceText: '0%',
    autoFilled: false,
    annotatedImageUrl: null,
  }
}

const syncAiDetectionWithForm = () => {
  if (!form.value.image || !isRoadCategorySelected.value) {
    resetAiResult()
    return
  }

  runAiDetection(form.value.image)
}

const runAiDetection = async (file) => {
  const token = localStorage.getItem('token')
  if (!token || !isRoadCategorySelected.value) return

  isRunningAiDetection.value = true
  try {
    const detection = await issuesStore.detectIssueImageAI(file)
    const confidence = Number(detection?.confidence || 0)
    const isPothole = detection?.ai_detection === 'pothole'

    aiResult.value = {
      checked: true,
      label: isPothole ? 'pothole' : 'none',
      confidenceText: `${Math.round(confidence * 100)}%`,
      autoFilled: Boolean(detection?.ai_auto_filled),
      annotatedImageUrl: detection?.annotated_image_url || null,
    }

    if (detection?.ai_auto_filled) {
      if (detection?.suggested_category) {
        form.value.category = issuesStore.normalizeIssueCategory(detection.suggested_category)
      }
      if (detection?.suggested_priority) {
        form.value.priority = detection.suggested_priority
      }
    }
  } catch {
    // Keep issue reporting usable even if AI preview fails.
    aiResult.value = {
      checked: true,
      label: 'none',
      confidenceText: '0%',
      autoFilled: false,
      annotatedImageUrl: null,
    }
  } finally {
    isRunningAiDetection.value = false
  }
}

const removeImage = () => {
  form.value.image = null
  imagePreview.value = null
  resetAiResult()
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

watch(
  () => [form.value.category, form.value.image],
  () => {
    syncAiDetectionWithForm()
  },
)

const errors = ref({})

const setMapBoundaryForCurrentLocation = (lat, lng) => {
  if (!map) return
  map.attributionControl.setPrefix('Current Location')
  map.setView([lat, lng], 16)
  map.setMaxBounds([
    [lat - SEARCH_BOX_DELTA, lng - SEARCH_BOX_DELTA],
    [lat + SEARCH_BOX_DELTA, lng + SEARCH_BOX_DELTA],
  ])
}

const updateCurrentLocationIndicator = (lat, lng, accuracy = 30) => {
  if (!map) return

  if (currentLocationMarker) {
    map.removeLayer(currentLocationMarker)
  }
  if (currentAccuracyCircle) {
    map.removeLayer(currentAccuracyCircle)
  }

  currentAccuracyCircle = L.circle([lat, lng], {
    radius: Math.max(accuracy, 20),
    color: '#3b82f6',
    weight: 1,
    opacity: 0.35,
    fillColor: '#60a5fa',
    fillOpacity: 0.15,
  }).addTo(map)

  currentLocationMarker = L.circleMarker([lat, lng], {
    radius: 8,
    color: '#ffffff',
    weight: 3,
    fillColor: '#2563eb',
    fillOpacity: 1,
  }).addTo(map)
    .bindPopup('<strong>Your current location</strong>')
}

const placeMarker = (lat, lng, popupText = 'Selected Location') => {
  if (!map) return

  form.value.latitude = lat
  form.value.longitude = lng

  if (marker) {
    map.removeLayer(marker)
  }

  marker = L.marker([lat, lng]).addTo(map).bindPopup(`<strong>${popupText}</strong>`).openPopup()
  map.panTo([lat, lng])
}

const isPointNearCurrentLocation = (lat, lng) => {
  if (!currentLocation.value) return false

  const distanceInMeters = L.latLng(currentLocation.value.latitude, currentLocation.value.longitude).distanceTo(
    L.latLng(lat, lng),
  )

  return distanceInMeters <= PROXIMITY_RADIUS_METERS
}

const rankAndFilterSuggestions = (results, query) => {
  const normalizedQuery = query.toLowerCase().trim()
  const queryParts = normalizedQuery.split(/\s+/).filter(Boolean)

  const dedupe = new Set()
  const filtered = results
    .filter((entry) => {
      const countryCode = entry?.address?.country_code || ''
      return countryCode.toLowerCase() === 'in'
    })
    .map((entry) => {
      const label = entry.display_name || ''
      const lowerLabel = label.toLowerCase()
      const queryMatchScore = queryParts.reduce((score, part) => {
        return lowerLabel.includes(part) ? score + 1 : score
      }, 0)
      const importance = Number(entry.importance || 0)
      const distance = currentLocation.value
        ? L.latLng(currentLocation.value.latitude, currentLocation.value.longitude).distanceTo(
            L.latLng(Number(entry.lat), Number(entry.lon)),
          )
        : Number.MAX_SAFE_INTEGER

      return {
        id: entry.place_id,
        label,
        latitude: Number(entry.lat),
        longitude: Number(entry.lon),
        score: queryMatchScore + importance - distance / PROXIMITY_RADIUS_METERS,
      }
    })
    .filter((entry) => {
      if (!Number.isFinite(entry.latitude) || !Number.isFinite(entry.longitude)) return false
      if (!isPointNearCurrentLocation(entry.latitude, entry.longitude)) return false
      if (dedupe.has(entry.label)) return false

      dedupe.add(entry.label)
      return true
    })
    .sort((a, b) => b.score - a.score)

  return filtered.slice(0, 8)
}

const fetchLocationSuggestions = async (query) => {
  if (!currentLocation.value) return

  const { latitude, longitude } = currentLocation.value
  const minLat = latitude - SEARCH_BOX_DELTA
  const maxLat = latitude + SEARCH_BOX_DELTA
  const minLng = longitude - SEARCH_BOX_DELTA
  const maxLng = longitude + SEARCH_BOX_DELTA
  const viewBox = `${minLng},${maxLat},${maxLng},${minLat}`

  if (locationSearchAbortController) {
    locationSearchAbortController.abort()
  }

  locationSearchAbortController = new AbortController()
  isSearchingLocation.value = true

  try {
    const searchQuery = `${query}, India`
    const url =
      `https://nominatim.openstreetmap.org/search?format=jsonv2&addressdetails=1&countrycodes=in&bounded=1&limit=20&viewbox=${encodeURIComponent(viewBox)}&q=${encodeURIComponent(searchQuery)}`

    const response = await fetch(url, {
      signal: locationSearchAbortController.signal,
      headers: {
        Accept: 'application/json',
      },
    })

    if (!response.ok) {
      throw new Error('Location service unavailable')
    }

    const data = await response.json()
    locationSuggestions.value = rankAndFilterSuggestions(Array.isArray(data) ? data : [], query)
    showLocationSuggestions.value = true
  } catch (fetchError) {
    if (fetchError.name !== 'AbortError') {
      locationSuggestions.value = []
    }
  } finally {
    isSearchingLocation.value = false
  }
}

const handleLocationInput = () => {
  if (!hasLocationPermission.value || !currentLocation.value) {
    error.value = 'Please allow location access first.'
    return
  }

  error.value = ''
  const query = form.value.location.trim()

  if (locationSearchTimeout) {
    clearTimeout(locationSearchTimeout)
  }

  if (query.length < 3) {
    locationSuggestions.value = []
    showLocationSuggestions.value = false
    return
  }

  locationSearchTimeout = setTimeout(() => {
    fetchLocationSuggestions(query)
  }, 300)
}

const handleLocationFocus = () => {
  if (locationSuggestions.value.length > 0 || isSearchingLocation.value) {
    showLocationSuggestions.value = true
  }
}

const handleLocationBlur = () => {
  setTimeout(() => {
    showLocationSuggestions.value = false
  }, 120)
}

const selectLocationSuggestion = (suggestion) => {
  if (!isPointNearCurrentLocation(suggestion.latitude, suggestion.longitude)) {
    error.value = 'Please select a location within 300 meters of your current positon.'
    return
  }

  form.value.location = suggestion.label
  showLocationSuggestions.value = false
  error.value = ''
  placeMarker(suggestion.latitude, suggestion.longitude, 'Selected Location')
  if (map) {
    map.setView([suggestion.latitude, suggestion.longitude], 16)
  }
}

const reverseGeocode = async (lat, lng) => {
  try {
    const url =
      `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lng)}&zoom=18&addressdetails=1`
    const response = await fetch(url)
    if (!response.ok) return

    const data = await response.json()
    const countryCode = data?.address?.country_code || ''
    if (countryCode.toLowerCase() === 'in' && data?.display_name) {
      form.value.location = data.display_name
    }
  } catch {
    // Reverse geocoding is best-effort and should not block form usage.
  }
}

const allowLocationAccess = async () => {
  if (!navigator.geolocation) {
    error.value = 'Geolocation is not supported by your browser.'
    return
  }

  isLocating.value = true
  error.value = ''

  navigator.geolocation.getCurrentPosition(
    async (position) => {
      const latitude = position.coords.latitude
      const longitude = position.coords.longitude
      const accuracy = position.coords.accuracy || 30

      currentLocation.value = { latitude, longitude, accuracy }
      hasLocationPermission.value = true

      await nextTick()

      if (!map) {
        initMap()
      } else {
        setMapBoundaryForCurrentLocation(latitude, longitude)
        updateCurrentLocationIndicator(latitude, longitude, accuracy)
      }

      placeMarker(latitude, longitude, 'Selected Issue Location')
      await reverseGeocode(latitude, longitude)
      isLocating.value = false
    },
    () => {
      isLocating.value = false
      hasLocationPermission.value = false
      error.value = 'Location permission denied. Please allow access to continue.'
    },
    {
      enableHighAccuracy: true,
      timeout: 15000,
      maximumAge: 0,
    },
  )
}

const validateForm = () => {
  errors.value = {}
  let isValid = true

  if (!form.value.category) {
    errors.value.category = 'Please select a category'
    isValid = false
  }
  if (!form.value.title) {
    errors.value.title = 'Title is required'
    isValid = false
  }
  if (!form.value.description) {
    errors.value.description = 'Description is required'
    isValid = false
  }
  if (!form.value.location || form.value.location.trim() === '') {
    errors.value.location = 'Location address or landmark is required'
    isValid = false
  }

  if (!hasLocationPermission.value || !currentLocation.value) {
    errors.value.location = 'Allow location access to continue'
    isValid = false
  }

  if (
    form.value.latitude &&
    form.value.longitude &&
    !isPointNearCurrentLocation(form.value.latitude, form.value.longitude)
  ) {
    errors.value.location = 'Please select a location within 300 meters of your current positon.'
    isValid = false
  }

  return isValid
}

const initMap = () => {
  if (!currentLocation.value) return

  const { latitude, longitude, accuracy } = currentLocation.value

  map = L.map('map').setView([latitude, longitude], 16)
  map.attributionControl.setPrefix('Current Location')

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 19,
    minZoom: 11,
  }).addTo(map)

  setMapBoundaryForCurrentLocation(latitude, longitude)
  updateCurrentLocationIndicator(latitude, longitude, accuracy)

  map.on('click', async (event) => {
    const { lat, lng } = event.latlng

    if (!isPointNearCurrentLocation(lat, lng)) {
      error.value = 'Please select a location within 300 meters of your current positon.'
      return
    }

    error.value = ''
    placeMarker(lat, lng)
    await reverseGeocode(lat, lng)
  })
}

const submitIssue = async () => {
  if (!validateForm()) return

  const token = localStorage.getItem('token')
  if (!token) {
    error.value = 'You must be logged in to report an issue.'
    setTimeout(() => router.push('/login'), 2000)
    return
  }

  if (!form.value.latitude || !form.value.longitude) {
    error.value = 'Please pin the location on the map.'
    return
  }

  if (!hasLocationPermission.value || !currentLocation.value) {
    error.value = 'Please allow location access first.'
    return
  }

  if (isRoadCategorySelected.value) {
    if (!form.value.image) {
      error.value = 'Please upload a pothole photo for road issues.'
      return
    }

    if (isRunningAiDetection.value) {
      error.value = 'AI is still analyzing the pothole photo. Please wait.'
      return
    }

    if (!aiResult.value.checked || !aiResult.value.autoFilled) {
      error.value = 'Please upload a clear pothole photo and wait for AI verification before submitting.'
      return
    }
  }

  if (!isPointNearCurrentLocation(form.value.latitude, form.value.longitude)) {
    error.value = 'Please select a location within 300 meters of your current positon.'
    return
  }

  // Re-check terms here just in case HTML5 validation fails
  if (!form.value.agreeToTerms) {
    error.value = 'You must agree to the terms.'
    return
  }

  isSubmitting.value = true
  error.value = ''
  successMessage.value = ''

  try {
    const formData = new FormData()
    formData.append('title', form.value.title)
    formData.append('description', form.value.description)
    formData.append('category', form.value.category)
    formData.append('priority', form.value.priority)
    formData.append('location', form.value.location)
    formData.append('latitude', form.value.latitude)
    formData.append('longitude', form.value.longitude)

    if (form.value.image) {
      formData.append('image', form.value.image)
    }

    const result = await issuesStore.createIssue(formData)

    const confidence = Number(result?.confidence || 0)
    const confidenceText = `${Math.round(confidence * 100)}%`
    const isPothole = result?.ai_detection === 'pothole'

    aiResult.value = {
      checked: true,
      label: isPothole ? 'pothole' : 'none',
      confidenceText,
      autoFilled: Boolean(result?.ai_auto_filled),
    }

    if (result?.ai_auto_filled) {
      form.value.category = issuesStore.normalizeIssueCategory('roads')
      form.value.priority = 'high'
      successMessage.value = `Issue reported. AI detected pothole (${confidenceText}) and auto-filled category/priority.`
    } else {
      successMessage.value = `Issue reported successfully. AI confidence: ${confidenceText}. Redirecting...`
    }

    setTimeout(() => {
      router.push('/dashboard')
    }, 2500)
  } catch (err) {
    if (err.toString().includes('401')) {
      error.value = 'Session expired. Please login again.'
      setTimeout(() => router.push('/login'), 2000)
    } else {
      error.value = err.message || 'Failed to report issue. Please try again.'
    }
  } finally {
    isSubmitting.value = false
  }
}

onBeforeUnmount(() => {
  if (locationSearchTimeout) {
    clearTimeout(locationSearchTimeout)
  }

  if (locationSearchAbortController) {
    locationSearchAbortController.abort()
  }

  if (map) {
    map.remove()
    map = null
  }

  currentLocationMarker = null
  currentAccuracyCircle = null
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
</style>
