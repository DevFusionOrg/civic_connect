<template>
  <div class="min-h-screen bg-gradient-to-br from-cream-50 to-cream-100">
    <div class="mx-auto max-w-2xl px-4 py-8">
      <!-- Back Link -->
      <router-link
        to="/profile"
        class="mb-6 inline-flex items-center gap-2 font-semibold text-gold-700 hover:underline"
      >
        <ArrowLeftIcon class="h-5 w-5" />
        Back to Profile
      </router-link>

      <div class="overflow-hidden rounded-xl bg-white shadow-md">
        <div class="border-b border-gold-200 bg-cream-50 px-8 py-6">
          <h1 class="text-2xl font-bold text-slate-900">Edit Profile</h1>
          <p class="mt-1 text-sm text-slate-600">Update your personal information</p>
        </div>

        <div class="p-8">
          <form @submit.prevent="onSubmit" class="flex flex-col gap-6">
            <!-- First Name -->
            <div>
              <label for="firstName" class="mb-2 block font-semibold text-slate-900"
                >First Name</label
              >
              <input
                name="firstName"
                id="firstName"
                type="text"
                v-model="formData.firstName"
                class="w-full rounded-lg border border-gold-200 bg-white px-4 py-2 text-slate-800 focus:ring-2 focus:ring-gold-200 focus:outline-none"
                :class="{ 'border-red-500': errors.firstName }"
                @blur="validateField('firstName')"
              />
              <p v-if="errors.firstName" class="mt-1 text-sm text-red-500">
                {{ errors.firstName }}
              </p>
            </div>

            <!-- Last Name -->
            <div>
              <label for="lastName" class="mb-2 block font-semibold text-slate-900"
                >Last Name</label
              >
              <input
                name="lastName"
                id="lastName"
                type="text"
                v-model="formData.lastName"
                class="w-full rounded-lg border border-gold-200 bg-white px-4 py-2 text-slate-800 focus:ring-2 focus:ring-gold-200 focus:outline-none"
                :class="{ 'border-red-500': errors.lastName }"
                @blur="validateField('lastName')"
              />
              <p v-if="errors.lastName" class="mt-1 text-sm text-red-500">{{ errors.lastName }}</p>
            </div>

            <!-- Phone -->
            <div>
              <label for="phone" class="mb-2 block font-semibold text-slate-900"
                >Phone Number</label
              >
              <input
                name="phone"
                id="phone"
                type="tel"
                v-model="formData.phone"
                class="w-full rounded-lg border border-gold-200 bg-white px-4 py-2 text-slate-800 focus:ring-2 focus:ring-gold-200 focus:outline-none"
                :class="{ 'border-red-500': errors.phone }"
                @blur="validateField('phone')"
              />
              <p v-if="errors.phone" class="mt-1 text-sm text-red-500">{{ errors.phone }}</p>
            </div>

            <!-- Location -->
            <div>
              <label for="location" class="mb-2 block font-semibold text-slate-900">Location</label>
              <input
                name="location"
                id="location"
                type="text"
                v-model="formData.location"
                placeholder="e.g., New York, NY"
                class="w-full rounded-lg border border-gold-200 bg-white px-4 py-2 text-slate-800 focus:ring-2 focus:ring-gold-200 focus:outline-none"
              />
              <p class="mt-1 text-xs text-slate-600">Your city or general area (optional)</p>
            </div>

            <!-- Email (Read Only) -->
            <div>
              <label class="mb-2 block font-semibold text-slate-900">Email Address</label>
              <div
                class="w-full rounded-lg border border-gold-200 bg-cream-50 px-4 py-2 text-slate-600"
              >
                {{ authStore.user?.email }}
              </div>
              <p class="mt-1 text-xs text-slate-600">Email cannot be changed directly.</p>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-4 border-t border-gold-200 pt-6">
              <router-link
                to="/profile"
                class="rounded-lg px-6 py-2 font-semibold text-slate-600 hover:bg-gold-100"
              >
                Cancel
              </router-link>
              <button
                type="submit"
                :disabled="isSubmitting"
                class="flex items-center justify-center rounded-lg bg-gold-600 px-8 py-2 font-bold text-white transition-transform hover:scale-105 hover:bg-gold-700 disabled:opacity-50 disabled:hover:scale-100"
              >
                <ArrowPathIcon v-if="isSubmitting" class="mr-2 h-5 w-5 animate-spin" />
                {{ isSubmitting ? 'Saving...' : 'Save Changes' }}
              </button>
            </div>
          </form>

          <!-- Error Message -->
          <div v-if="error" class="mt-6 rounded-lg bg-red-50 p-4 text-red-800">
            {{ error }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/authStore'
import { useToast } from 'vue-toastification'
import { ArrowLeftIcon, ArrowPathIcon } from '@heroicons/vue/24/solid'

const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()

const isSubmitting = ref(false)
const error = ref('')

const formData = ref({
  firstName: '',
  lastName: '',
  phone: '',
  location: '',
})

const errors = ref({
  firstName: '',
  lastName: '',
  phone: '',
})

// Initialize form data
onMounted(() => {
  if (authStore.user) {
    formData.value = {
      firstName: authStore.user.first_name,
      lastName: authStore.user.last_name,
      phone: authStore.user.phone || '',
      location: authStore.user.location || '',
    }
  }
})

const validateField = (field) => {
  errors.value[field] = ''

  if (field === 'firstName') {
    if (!formData.value.firstName || formData.value.firstName.trim() === '') {
      errors.value.firstName = 'First name is required'
    }
  }

  if (field === 'lastName') {
    if (!formData.value.lastName || formData.value.lastName.trim() === '') {
      errors.value.lastName = 'Last name is required'
    }
  }

  // Phone is optional, so no validation needed
}

const onSubmit = async () => {
  isSubmitting.value = true
  error.value = ''

  // Validate all required fields
  validateField('firstName')
  validateField('lastName')

  if (errors.value.firstName || errors.value.lastName) {
    isSubmitting.value = false
    return
  }

  try {
    await authStore.updateUserProfile(authStore.user.id, {
      first_name: formData.value.firstName,
      last_name: formData.value.lastName,
      phone: formData.value.phone,
      location: formData.value.location,
    })

    toast.success('Profile updated successfully')
    router.push('/profile')
  } catch (err) {
    error.value = err.message || 'Failed to update profile'
    toast.error(error.value)
  } finally {
    isSubmitting.value = false
  }
}
</script>
