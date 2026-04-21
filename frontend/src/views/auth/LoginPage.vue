<template>
  <!-- Logo & Header -->
  <div class="mb-8 text-center">
    <div class="mb-4 flex items-center justify-center">
      <img :src="logo" alt="CivicConnect" class="h-12 w-auto" />
    </div>
    <h2 class="text-primary mb-2 text-2xl font-bold">Welcome Back</h2>
    <p class="text-muted">Sign in to your account to continue</p>
  </div>

  <!-- Login Form -->
  <form @submit.prevent="handleLogin" class="space-y-6">
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

    <!-- Password Field -->
    <div>
      <div class="mb-2 flex items-center justify-between">
        <label for="password" class="text-primary block text-sm font-medium"> Password </label>
        <router-link
          to="/forgot-password"
          class="text-accent hover:text-primary text-sm transition"
        >
          Forgot password?
        </router-link>
      </div>
      <input
        id="password"
        v-model="formData.password"
        type="password"
        name="password"
        autocomplete="current-password"
        placeholder="••••••••"
        class="border-accent/30 focus:border-accent focus:ring-accent w-full rounded-lg border px-4 py-2 transition focus:ring-1 focus:outline-none"
        @blur="validateField('password')"
      />
      <p v-if="errors.password" class="text-danger mt-1 text-sm">{{ errors.password }}</p>
    </div>

    <!-- Error Message -->
    <div v-if="error" class="bg-danger/10 border-danger/30 rounded-lg border p-4">
      <p class="text-danger text-sm">{{ error }}</p>
    </div>

    <!-- hCaptcha -->
    <div v-if="!isCaptchaBypassed" class="flex flex-col items-center">
      <vue-hcaptcha
        :sitekey="hcaptchaSitekey"
        @verify="onCaptchaVerify"
        @error="onCaptchaError"
        @expired="onCaptchaExpired"
      ></vue-hcaptcha>
      <p v-if="errors.captcha" class="text-danger mt-1 text-sm">{{ errors.captcha }}</p>
    </div>
    <div v-else class="rounded-lg border border-amber-300 bg-amber-50 p-3 text-center text-sm text-amber-800">
      Captcha is bypassed for localhost/development.
    </div>

    <!-- Submit Button -->
    <button
      type="submit"
      :disabled="isLoading"
      class="bg-primary hover:bg-primary/90 w-full rounded-lg px-4 py-2 font-semibold text-white transition disabled:cursor-not-allowed disabled:opacity-50"
    >
      <span v-if="!isLoading">Sign In</span>
      <span v-else>Signing in...</span>
    </button>

    <!-- Sign Up Link -->
    <div class="border-accent/20 border-t pt-4 text-center">
      <p class="text-muted text-sm">
        Don't have an account?
        <router-link to="/register" class="text-primary hover:text-accent font-semibold transition">
          Create one
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
import VueHcaptcha from '@hcaptcha/vue3-hcaptcha'
import logo from '@/assets/civic-connect-logo.png'

const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()

const formData = ref({
  email: '',
  password: '',
})

const errors = ref({
  email: '',
  password: '',
  captcha: '',
})

const error = ref('')
const isLoading = ref(false)
const captchaToken = ref('')
const hcaptchaSitekey = import.meta.env.VITE_HCAPTCHA_SITEKEY
const isLocalhost = ['localhost', '127.0.0.1'].includes(window.location.hostname)
const isCaptchaBypassed = import.meta.env.DEV || isLocalhost

if (isCaptchaBypassed) {
  captchaToken.value = 'local-dev-bypass'
}

const validateField = (field) => {
  errors.value[field] = ''

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
    } else if (formData.value.password.length < 6) {
      errors.value.password = 'Password must be at least 6 characters'
    }
  }
}

const onCaptchaVerify = (token) => {
  captchaToken.value = token
  errors.value.captcha = ''
}

const onCaptchaError = () => {
  errors.value.captcha = 'Captcha verification failed. Please try again.'
  captchaToken.value = ''
}

const onCaptchaExpired = () => {
  errors.value.captcha = 'Captcha expired. Please verify again.'
  captchaToken.value = ''
}

const handleLogin = async () => {
  error.value = ''

  // Validate all fields
  validateField('email')
  validateField('password')

  // Validate captcha
  if (!isCaptchaBypassed && !captchaToken.value) {
    errors.value.captcha = 'Please complete the captcha verification'
    return
  }

  if (errors.value.email || errors.value.password || errors.value.captcha) {
    return
  }

  isLoading.value = true

  try {
    const response = await authStore.login(formData.value.email, formData.value.password)

    if (response.success) {
      toast.success('Login successful!')

      // Redirect based on role
      const redirectPath = authStore.isAdmin
        ? '/admin/dashboard'
        : authStore.isStaff
          ? '/staff/dashboard'
          : '/dashboard'

      router.push(redirectPath)
    }
  } catch (err) {
    error.value = err || 'Login failed. Please try again.'
    toast.error(error.value)
  } finally {
    isLoading.value = false
  }
}
</script>
