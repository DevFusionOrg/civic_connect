<template>
  <!-- Logo & Header -->
  <div class="mb-8 text-center">
    <div class="bg-accent/10 mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full">
      <KeyIcon class="text-accent h-6 w-6" />
    </div>
    <h2 class="text-primary mb-2 text-2xl font-bold">Reset Password</h2>
    <p class="text-muted">Enter your email to receive password reset instructions</p>
  </div>

  <!-- Password Reset Form -->
  <form @submit.prevent="handleResetRequest" class="space-y-6">
    <!-- Step 1: Email Input -->
    <div v-if="currentStep === 'email'">
      <label for="email" class="text-primary mb-2 block text-sm font-medium"> Email Address </label>
      <input
        id="email"
        v-model="formData.email"
        type="email"
        placeholder="you@example.com"
        class="border-accent/30 focus:border-accent focus:ring-accent w-full rounded-lg border px-4 py-2 transition focus:ring-1 focus:outline-none"
        @blur="validateField('email')"
      />
      <p v-if="errors.email" class="text-danger mt-1 text-sm">{{ errors.email }}</p>
    </div>

    <!-- Step 2: Reset Code Input -->
    <div v-if="currentStep === 'code'">
      <p class="text-muted mb-4 text-sm">
        We sent a reset code to
        <span class="text-primary font-semibold">{{ maskEmail(formData.email) }}</span>
      </p>
      <label for="resetCode" class="text-primary mb-2 block text-sm font-medium">
        Reset Code
      </label>
      <input
        id="resetCode"
        v-model="formData.resetCode"
        type="text"
        placeholder="Enter 6-digit code"
        maxlength="6"
        class="border-accent/30 focus:border-accent focus:ring-accent w-full rounded-lg border px-4 py-3 text-center text-2xl tracking-widest transition focus:ring-1 focus:outline-none"
        @input="formData.resetCode = formData.resetCode.replace(/\D/g, '').slice(0, 6)"
        @blur="validateField('resetCode')"
      />
      <p v-if="errors.resetCode" class="text-danger mt-1 text-sm">{{ errors.resetCode }}</p>
    </div>

    <!-- Step 3: New Password Input -->
    <div v-if="currentStep === 'password'">
      <label for="newPassword" class="text-primary mb-2 block text-sm font-medium">
        New Password
      </label>
      <input
        id="newPassword"
        v-model="formData.newPassword"
        type="password"
        placeholder="••••••••"
        class="border-accent/30 focus:border-accent focus:ring-accent w-full rounded-lg border px-4 py-2 transition focus:ring-1 focus:outline-none"
        @blur="validateField('newPassword')"
      />
      <p v-if="errors.newPassword" class="text-danger mt-1 text-sm">{{ errors.newPassword }}</p>

      <label for="confirmPassword" class="text-primary mt-4 mb-2 block text-sm font-medium">
        Confirm Password
      </label>
      <input
        id="confirmPassword"
        v-model="formData.confirmPassword"
        type="password"
        placeholder="••••••••"
        class="border-accent/30 focus:border-accent focus:ring-accent w-full rounded-lg border px-4 py-2 transition focus:ring-1 focus:outline-none"
        @blur="validateField('confirmPassword')"
      />
      <p v-if="errors.confirmPassword" class="text-danger mt-1 text-sm">
        {{ errors.confirmPassword }}
      </p>
    </div>

    <!-- Error Message -->
    <div v-if="error" class="bg-danger/10 border-danger/30 rounded-lg border p-4">
      <p class="text-danger text-sm">{{ error }}</p>
    </div>

    <!-- Resend Code (Step 2) -->
    <div v-if="currentStep === 'code'" class="text-muted text-center text-sm">
      <p v-if="!isResendDisabled">
        Didn't receive the code?
        <button
          type="button"
          @click="handleResendCode"
          :disabled="isResending"
          class="text-primary hover:text-accent font-semibold transition disabled:opacity-50"
        >
          <span v-if="!isResending">Resend</span>
          <span v-else>Sending...</span>
        </button>
      </p>
      <p v-else class="text-muted">
        Resend available in <span class="font-semibold">{{ resendCountdown }}s</span>
      </p>
    </div>

    <!-- Submit Button -->
    <button
      type="submit"
      :disabled="isLoading"
      class="bg-primary hover:bg-primary/90 w-full rounded-lg px-4 py-2 font-semibold text-white transition disabled:cursor-not-allowed disabled:opacity-50"
    >
      <span v-if="currentStep === 'email' && !isLoading">Send Reset Code</span>
      <span v-else-if="currentStep === 'code' && !isLoading">Verify Code</span>
      <span v-else-if="currentStep === 'password' && !isLoading">Reset Password</span>
      <span v-else>Processing...</span>
    </button>

    <!-- Back Button -->
    <button
      v-if="currentStep !== 'email'"
      type="button"
      @click="goBack"
      class="border-accent/30 text-primary hover:bg-accent/5 w-full rounded-lg border px-4 py-2 font-semibold transition"
    >
      Back
    </button>

    <!-- Back to Login -->
    <div class="border-accent/20 border-t pt-4 text-center">
      <router-link to="/login" class="text-primary hover:text-accent text-sm transition">
        Back to Login
      </router-link>
    </div>
  </form>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import axios from 'axios'
import { KeyIcon } from '@heroicons/vue/24/outline'

const router = useRouter()
const toast = useToast()

const API_BASE_URL = 'http://localhost/civic-connect/backend/api'

const postAuthEndpoint = async (authPath, usersPath, payload) => {
  try {
    return await axios.post(`${API_BASE_URL}/auth/${authPath}`, payload)
  } catch (err) {
    if (err?.response?.status === 404 && usersPath) {
      console.warn(`[ForgotPasswordPage] fallback to /users/${usersPath}`)
      return axios.post(`${API_BASE_URL}/users/${usersPath}`, payload)
    }
    throw err
  }
}

const currentStep = ref('email') // email, code, password
const formData = ref({
  email: '',
  resetCode: '',
  newPassword: '',
  confirmPassword: '',
})

const errors = ref({
  email: '',
  resetCode: '',
  newPassword: '',
  confirmPassword: '',
})

const error = ref('')
const isLoading = ref(false)
const isResending = ref(false)
const isResendDisabled = ref(false)
const resendCountdown = ref(0)

const maskEmail = (email) => {
  const [name, domain] = email.split('@')
  return name.slice(0, 2) + '*'.repeat(Math.max(0, name.length - 2)) + '@' + domain
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

  if (field === 'resetCode') {
    if (!formData.value.resetCode) {
      errors.value.resetCode = 'Reset code is required'
    } else if (formData.value.resetCode.length !== 6) {
      errors.value.resetCode = 'Please enter a 6-digit code'
    }
  }

  if (field === 'newPassword') {
    if (!formData.value.newPassword) {
      errors.value.newPassword = 'Password is required'
    } else if (formData.value.newPassword.length < 8) {
      errors.value.newPassword = 'Password must be at least 8 characters'
    } else if (!/(?=.*[a-z])/.test(formData.value.newPassword)) {
      errors.value.newPassword = 'Password must contain lowercase letters'
    } else if (!/(?=.*[A-Z])/.test(formData.value.newPassword)) {
      errors.value.newPassword = 'Password must contain uppercase letters'
    } else if (!/(?=.*\d)/.test(formData.value.newPassword)) {
      errors.value.newPassword = 'Password must contain numbers'
    }
  }

  if (field === 'confirmPassword') {
    if (!formData.value.confirmPassword) {
      errors.value.confirmPassword = 'Please confirm your password'
    } else if (formData.value.confirmPassword !== formData.value.newPassword) {
      errors.value.confirmPassword = 'Passwords do not match'
    }
  }
}

const handleResetRequest = async () => {
  error.value = ''

  if (currentStep.value === 'email') {
    validateField('email')
    if (errors.value.email) return

    isLoading.value = true
    try {
      // Call API to send reset code
      const response = await postAuthEndpoint('forgot-password', 'forgot-password', {
        email: formData.value.email,
      })

      toast.success(response.data.message || 'Reset code sent to your email!')
      currentStep.value = 'code'
      isResendDisabled.value = true
      resendCountdown.value = 60

      const countdown = setInterval(() => {
        resendCountdown.value--
        if (resendCountdown.value <= 0) {
          isResendDisabled.value = false
          clearInterval(countdown)
        }
      }, 1000)
    } catch (err) {
      error.value = err.response?.data?.error || 'Failed to send reset code'
      toast.error(error.value)
    } finally {
      isLoading.value = false
    }
  } else if (currentStep.value === 'code') {
    validateField('resetCode')
    if (errors.value.resetCode) return

    isLoading.value = true
    try {
      // Call API to verify reset code
      const response = await postAuthEndpoint('verify-reset-code', 'verify-reset-code', {
        email: formData.value.email,
        reset_code: formData.value.resetCode,
      })

      toast.success(response.data.message || 'Code verified!')
      currentStep.value = 'password'
    } catch (err) {
      error.value = err.response?.data?.error || 'Invalid reset code'
      toast.error(error.value)
    } finally {
      isLoading.value = false
    }
  } else if (currentStep.value === 'password') {
    validateField('newPassword')
    validateField('confirmPassword')
    if (errors.value.newPassword || errors.value.confirmPassword) return

    isLoading.value = true
    try {
      // Call API to reset password
      const response = await postAuthEndpoint('reset-password', 'reset-password', {
        email: formData.value.email,
        reset_code: formData.value.resetCode,
        new_password: formData.value.newPassword,
      })

      toast.success(response.data.message || 'Password reset successfully!')
      router.push('/login')
    } catch (err) {
      error.value = err.response?.data?.error || 'Failed to reset password'
      toast.error(error.value)
    } finally {
      isLoading.value = false
    }
  }
}

const handleResendCode = async () => {
  isResending.value = true
  try {
    // Call API to resend reset code
    const response = await postAuthEndpoint('forgot-password', 'forgot-password', {
      email: formData.value.email,
    })

    toast.success(response.data.message || 'Reset code sent again!')
    isResendDisabled.value = true
    resendCountdown.value = 60

    const countdown = setInterval(() => {
      resendCountdown.value--
      if (resendCountdown.value <= 0) {
        isResendDisabled.value = false
        clearInterval(countdown)
      }
    }, 1000)
  } catch (err) {
    error.value = err.response?.data?.error || 'Failed to resend reset code'
    toast.error(error.value)
  } finally {
    isResending.value = false
  }
}

const goBack = () => {
  if (currentStep.value === 'code') {
    currentStep.value = 'email'
    formData.value.resetCode = ''
  } else if (currentStep.value === 'password') {
    currentStep.value = 'code'
    formData.value.newPassword = ''
    formData.value.confirmPassword = ''
  }
  error.value = ''
}
</script>
