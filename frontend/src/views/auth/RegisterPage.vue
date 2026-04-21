<template>
  <!-- Logo & Header -->
  <div class="mb-8 text-center">
    <div class="mb-4 flex items-center justify-center">
      <img :src="logo" alt="CivicConnect" class="h-12 w-auto" />
    </div>
    <h2 class="text-primary mb-2 text-2xl font-bold">Join Our Community</h2>
    <p class="text-muted">Create an account to start reporting and engaging</p>
  </div>

  <!-- Registration Form -->
  <form @submit.prevent="handleRegister" class="space-y-4">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
      <!-- First Name Field -->
      <div>
        <label for="firstName" class="text-primary mb-2 block text-sm font-medium">
          First Name
        </label>
        <input
          id="firstName"
          v-model="formData.firstName"
          type="text"
          name="firstName"
          autocomplete="given-name"
          placeholder="John"
          class="border-accent/30 focus:border-accent focus:ring-accent w-full rounded-lg border px-4 py-2 transition focus:ring-1 focus:outline-none"
          @blur="validateField('firstName')"
        />
        <p v-if="errors.firstName" class="text-danger mt-1 text-sm">{{ errors.firstName }}</p>
      </div>

      <!-- Last Name Field -->
      <div>
        <label for="lastName" class="text-primary mb-2 block text-sm font-medium">
          Last Name
        </label>
        <input
          id="lastName"
          v-model="formData.lastName"
          type="text"
          name="lastName"
          autocomplete="family-name"
          placeholder="Doe"
          class="border-accent/30 focus:border-accent focus:ring-accent w-full rounded-lg border px-4 py-2 transition focus:ring-1 focus:outline-none"
          @blur="validateField('lastName')"
        />
        <p v-if="errors.lastName" class="text-danger mt-1 text-sm">{{ errors.lastName }}</p>
      </div>
    </div>

    <!-- Email Field -->
    <div>
      <label for="email" class="text-primary mb-2 block text-sm font-medium"> Email Address </label>
      <input
        id="email"
        v-model="formData.email"
        type="email"
        name="email"
        autocomplete="email"
        placeholder="you@example.com"
        class="border-accent/30 focus:border-accent focus:ring-accent w-full rounded-lg border px-4 py-2 transition focus:ring-1 focus:outline-none"
        @blur="validateField('email')"
      />
      <p v-if="errors.email" class="text-danger mt-1 text-sm">{{ errors.email }}</p>
    </div>

    <!-- Phone Field (Optional) -->
    <div>
      <label for="phone" class="text-primary mb-2 block text-sm font-medium">
        Phone Number (Optional)
      </label>
      <input
        id="phone"
        v-model="formData.phone"
        type="tel"
        name="phone"
        autocomplete="tel"
        placeholder="09123456789"
        class="border-accent/30 focus:border-accent focus:ring-accent w-full rounded-lg border px-4 py-2 transition focus:ring-1 focus:outline-none"
      />
    </div>

    <!-- Password Field -->
    <div>
      <label for="password" class="text-primary mb-2 block text-sm font-medium"> Password </label>
      <input
        id="password"
        v-model="formData.password"
        type="password"
        name="password"
        autocomplete="new-password"
        placeholder="••••••••"
        class="border-accent/30 focus:border-accent focus:ring-accent w-full rounded-lg border px-4 py-2 transition focus:ring-1 focus:outline-none"
        @blur="validateField('password')"
      />
      <p v-if="errors.password" class="text-danger mt-1 text-sm">{{ errors.password }}</p>
      <p class="text-muted mt-1 text-xs">
        At least 8 characters with uppercase, lowercase, and numbers
      </p>
    </div>

    <!-- Confirm Password Field -->
    <div>
      <label for="confirmPassword" class="text-primary mb-2 block text-sm font-medium">
        Confirm Password
      </label>
      <input
        id="confirmPassword"
        v-model="formData.confirmPassword"
        type="password"
        name="confirmPassword"
        autocomplete="new-password"
        placeholder="••••••••"
        class="border-accent/30 focus:border-accent focus:ring-accent w-full rounded-lg border px-4 py-2 transition focus:ring-1 focus:outline-none"
        @blur="validateField('confirmPassword')"
      />
      <p v-if="errors.confirmPassword" class="text-danger mt-1 text-sm">
        {{ errors.confirmPassword }}
      </p>
    </div>

    <!-- Terms Acceptance -->
    <div class="flex items-start gap-2 pt-2">
      <input
        id="terms"
        v-model="formData.acceptTerms"
        type="checkbox"
        name="terms"
        class="text-primary focus:ring-accent border-accent/30 mt-1 h-4 w-4 cursor-pointer rounded"
      />
      <label for="terms" class="text-muted cursor-pointer text-sm">
        I agree to the
        <a href="#" class="text-primary hover:text-accent transition">Terms of Service</a>
        and
        <a href="#" class="text-primary hover:text-accent transition">Privacy Policy</a>
      </label>
    </div>
    <p v-if="errors.terms" class="text-danger text-sm">{{ errors.terms }}</p>

    <!-- Error Message -->
    <div v-if="error" class="bg-danger/10 border-danger/30 rounded-lg border p-4">
      <p class="text-danger text-sm">{{ error }}</p>
    </div>

    <!-- Submit Button -->
    <button
      type="submit"
      :disabled="isLoading"
      class="bg-primary hover:bg-primary/90 w-full rounded-lg px-4 py-2 font-semibold text-white transition disabled:cursor-not-allowed disabled:opacity-50"
    >
      <span v-if="!isLoading">Create Account</span>
      <span v-else>Creating account...</span>
    </button>

    <!-- Sign In Link -->
    <div class="border-accent/20 border-t pt-4 text-center">
      <p class="text-muted text-sm">
        Already have an account?
        <router-link to="/login" class="text-primary hover:text-accent font-semibold transition">
          Sign in
        </router-link>
      </p>
    </div>
  </form>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/authStore'
import { useToast } from 'vue-toastification'
import logo from '@/assets/civic-connect-logo.png'

const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()

const formData = ref({
  firstName: '',
  lastName: '',
  email: '',
  phone: '',
  password: '',
  confirmPassword: '',
  acceptTerms: false,
})

const errors = ref({
  firstName: '',
  lastName: '',
  email: '',
  password: '',
  confirmPassword: '',
  terms: '',
})

const error = ref('')
const isLoading = ref(false)

const validateField = (field) => {
  errors.value[field] = ''

  if (field === 'firstName') {
    if (!formData.value.firstName) {
      errors.value.firstName = 'First name is required'
    } else if (formData.value.firstName.length < 2) {
      errors.value.firstName = 'First name must be at least 2 characters'
    }
  }

  if (field === 'lastName') {
    if (!formData.value.lastName) {
      errors.value.lastName = 'Last name is required'
    } else if (formData.value.lastName.length < 2) {
      errors.value.lastName = 'Last name must be at least 2 characters'
    }
  }

  if (field === 'email') {
    if (!formData.value.email) {
      errors.value.email = 'Email is required'
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.value.email)) {
      errors.value.email = 'Please enter a valid email'
    }
  }

  if (field === 'password') {
    if (!formData.value.password) {
      errors.value.password = 'Password is required'
    } else if (formData.value.password.length < 8) {
      errors.value.password = 'Password must be at least 8 characters'
    } else if (!/(?=.*[a-z])/.test(formData.value.password)) {
      errors.value.password = 'Password must contain lowercase letters'
    } else if (!/(?=.*[A-Z])/.test(formData.value.password)) {
      errors.value.password = 'Password must contain uppercase letters'
    } else if (!/(?=.*\d)/.test(formData.value.password)) {
      errors.value.password = 'Password must contain numbers'
    }
  }

  if (field === 'confirmPassword') {
    if (!formData.value.confirmPassword) {
      errors.value.confirmPassword = 'Please confirm your password'
    } else if (formData.value.confirmPassword !== formData.value.password) {
      errors.value.confirmPassword = 'Passwords do not match'
    }
  }
}

const handleRegister = async () => {
  error.value = ''
  errors.value.terms = ''

  // Validate all fields
  validateField('firstName')
  validateField('lastName')
  validateField('email')
  validateField('password')
  validateField('confirmPassword')

  if (!formData.value.acceptTerms) {
    errors.value.terms = 'You must accept the terms and conditions'
  }

  if (
    errors.value.firstName ||
    errors.value.lastName ||
    errors.value.email ||
    errors.value.password ||
    errors.value.confirmPassword ||
    errors.value.terms ||
  ) {
    return
  }

  isLoading.value = true

  try {
    const response = await authStore.register({
      firstName: formData.value.firstName,
      lastName: formData.value.lastName,
      email: formData.value.email,
      phone: formData.value.phone || null,
      password: formData.value.password,
    })

    if (response.success) {
      toast.success('Account created! You are now signed in.')

      const redirectPath = authStore.isAdmin
        ? '/admin/dashboard'
        : authStore.isStaff
          ? '/staff/dashboard'
          : '/dashboard'

      router.push(redirectPath)
    }
  } catch (err) {
    error.value = err || 'Registration failed. Please try again.'
    toast.error(error.value)
  } finally {
    isLoading.value = false
  }
}
</script>
