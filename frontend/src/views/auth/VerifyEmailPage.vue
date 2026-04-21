<template>
  <!-- Logo & Header -->
  <div class="mb-8 text-center">
    <div class="bg-accent/10 mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full">
      <EnvelopeIcon class="text-accent h-6 w-6" />
    </div>
    <h2 class="text-primary mb-2 text-2xl font-bold">Verify Your Email</h2>
    <p class="text-muted">We sent a verification code to your email address</p>
  </div>

  <!-- Verification Form -->
  <form @submit.prevent="handleVerify" class="space-y-6">
    <!-- Email Display -->
    <div class="bg-accent/5 border-accent/20 rounded-lg border p-4">
      <p class="text-muted mb-1 text-sm">Verification code sent to:</p>
      <p class="text-primary font-semibold">{{ displayEmail }}</p>
    </div>

    <!-- OTP Input -->
    <div>
      <label for="otpCode" class="text-primary mb-2 block text-sm font-medium">
        Verification Code
      </label>
      <input
        id="otpCode"
        v-model="formData.otpCode"
        type="text"
        placeholder="Enter 6-digit code"
        maxlength="6"
        class="border-accent/30 focus:border-accent focus:ring-accent w-full rounded-lg border px-4 py-3 text-center text-2xl tracking-widest transition focus:ring-1 focus:outline-none"
        @input="formData.otpCode = formData.otpCode.replace(/\D/g, '').slice(0, 6)"
        @blur="validateField('otpCode')"
      />
      <p v-if="errors.otpCode" class="text-danger mt-1 text-sm">{{ errors.otpCode }}</p>
      <p class="text-muted mt-2 text-center text-xs">Check your email for the verification code</p>
    </div>

    <!-- Error Message -->
    <div v-if="error" class="bg-danger/10 border-danger/30 rounded-lg border p-4">
      <p class="text-danger text-sm">{{ error }}</p>
    </div>

    <!-- Submit Button -->
    <button
      type="submit"
      :disabled="isLoading || formData.otpCode.length < 6"
      class="bg-primary hover:bg-primary/90 w-full rounded-lg px-4 py-2 font-semibold text-white transition disabled:cursor-not-allowed disabled:opacity-50"
    >
      <span v-if="!isLoading">Verify Email</span>
      <span v-else>Verifying...</span>
    </button>

    <!-- Resend Code -->
    <div class="text-center">
      <p class="text-muted mb-2 text-sm">Didn't receive the code?</p>
      <button
        v-if="!isResendDisabled"
        type="button"
        @click="handleResend"
        :disabled="isResending"
        class="text-primary hover:text-accent font-semibold transition disabled:opacity-50"
      >
        <span v-if="!isResending">Resend Code</span>
        <span v-else>Sending...</span>
      </button>
      <p v-else class="text-muted text-sm">
        Resend available in <span class="font-semibold">{{ resendCountdown }}s</span>
      </p>
    </div>

    <!-- Back to Login -->
    <div class="border-accent/20 border-t pt-4 text-center">
      <button
        v-if="isLocalhost"
        type="button"
        @click="handleLocalSkip"
        class="text-amber-700 hover:text-amber-800 mb-2 block w-full text-sm font-semibold transition"
      >
        Skip Verification (Local Dev)
      </button>
      <router-link to="/login" class="text-primary hover:text-accent text-sm transition">
        Back to Login
      </router-link>
    </div>
  </form>

  <!-- Info Card -->
  <div class="border-accent/20 mt-6 rounded-lg border p-4">
    <p class="text-muted text-center text-xs">
      Check your spam folder if you don't see the verification email
    </p>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/authStore'
import { useToast } from 'vue-toastification'
import { EnvelopeIcon } from '@heroicons/vue/24/outline'

const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()

const formData = ref({
  otpCode: '',
})

const errors = ref({
  otpCode: '',
})

const error = ref('')
const isLoading = ref(false)
const isResending = ref(false)
const isResendDisabled = ref(false)
const resendCountdown = ref(0)
const registeredEmail = ref('')
const isLocalhost = ['localhost', '127.0.0.1'].includes(window.location.hostname)

const displayEmail = computed(() => {
  if (!registeredEmail.value) return 'your email'
  const [name, domain] = registeredEmail.value.split('@')
  return name.slice(0, 2) + '*'.repeat(name.length - 2) + '@' + domain
})

onMounted(() => {
  registeredEmail.value = sessionStorage.getItem('registeredEmail') || ''
  if (!registeredEmail.value) {
    // Fallback - could redirect to register if no email found
    toast.warning('Please register first to verify email')
  }
})

const validateField = (field) => {
  errors.value[field] = ''

  if (field === 'otpCode') {
    if (!formData.value.otpCode) {
      errors.value.otpCode = 'Verification code is required'
    } else if (formData.value.otpCode.length !== 6) {
      errors.value.otpCode = 'Please enter a 6-digit code'
    } else if (!/^\d+$/.test(formData.value.otpCode)) {
      errors.value.otpCode = 'Code must contain only numbers'
    }
  }
}

const handleVerify = async () => {
  error.value = ''

  validateField('otpCode')

  if (errors.value.otpCode) {
    return
  }

  isLoading.value = true

  try {
    const response = await authStore.verifyEmail(registeredEmail.value, formData.value.otpCode)

    if (response.success) {
      toast.success('Email verified successfully!')
      sessionStorage.removeItem('registeredEmail')

      // Redirect to login after short delay
      setTimeout(() => {
        router.push('/login')
      }, 1000)
    }
  } catch (err) {
    error.value = err || 'Verification failed. Please try again.'
    toast.error(error.value)
  } finally {
    isLoading.value = false
  }
}

const handleResend = async () => {
  error.value = ''
  isResending.value = true

  try {
    const response = await authStore.resendOTP(registeredEmail.value)

    if (response.success) {
      toast.success('Verification code sent!')

      // Disable resend button for 60 seconds
      isResendDisabled.value = true
      resendCountdown.value = 60

      const countdown = setInterval(() => {
        resendCountdown.value--
        if (resendCountdown.value <= 0) {
          isResendDisabled.value = false
          clearInterval(countdown)
        }
      }, 1000)
    }
  } catch (err) {
    error.value = err || 'Failed to resend code. Please try again.'
    toast.error(error.value)
  } finally {
    isResending.value = false
  }
}

const handleLocalSkip = () => {
  toast.info('Skipping email verification for local development')
  router.push('/login')
}
</script>
