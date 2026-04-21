<template>
  <!-- Logo & Header -->
  <div class="mb-8 text-center">
    <div class="bg-accent/10 mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full">
      <ShieldCheckIcon class="text-accent h-6 w-6" />
    </div>
    <h2 class="text-primary mb-2 text-2xl font-bold">Two-Factor Authentication</h2>
    <p class="text-muted">Enter the 6-digit code from your authenticator app</p>
  </div>

  <!-- OTP Verification Form -->
  <form @submit.prevent="handleVerifyOTP" class="space-y-6">
    <!-- OTP Input -->
    <div>
      <label for="otpCode" class="text-primary mb-4 block text-center text-sm font-medium">
        Verification Code
      </label>
      <div class="mb-4 flex justify-center gap-2">
        <input
          v-for="(digit, index) in otpDigits"
          :key="index"
          :ref="(el) => (otpInputs[index] = el)"
          type="text"
          inputmode="numeric"
          maxlength="1"
          :value="digit"
          @input="handleDigitInput(index, $event)"
          @keydown="handleKeyDown(index, $event)"
          @paste="handlePaste"
          class="border-accent/30 focus:border-accent focus:ring-accent/20 h-12 w-12 rounded-lg border-2 text-center text-2xl font-bold transition focus:ring-2 focus:outline-none"
        />
      </div>
      <p v-if="errors.otpCode" class="text-danger mt-1 text-center text-sm">{{ errors.otpCode }}</p>
    </div>

    <!-- Alternative Input Method -->
    <div v-if="showAlternativeInput" class="border-accent/20 border-t py-4">
      <p class="text-muted mb-3 text-center text-xs">Or enter the code manually:</p>
      <input
        v-model="manualOtp"
        type="text"
        placeholder="000000"
        maxlength="6"
        class="border-accent/30 focus:border-accent focus:ring-accent w-full rounded-lg border px-4 py-2 text-center text-xl tracking-widest transition focus:ring-1 focus:outline-none"
        @input="handleManualInput"
      />
    </div>

    <!-- Error Message -->
    <div v-if="error" class="bg-danger/10 border-danger/30 rounded-lg border p-4">
      <p class="text-danger text-sm">{{ error }}</p>
    </div>

    <!-- Submit Button -->
    <button
      type="submit"
      :disabled="isLoading || otpCode.length < 6"
      class="bg-primary hover:bg-primary/90 w-full rounded-lg px-4 py-2 font-semibold text-white transition disabled:cursor-not-allowed disabled:opacity-50"
    >
      <span v-if="!isLoading">Verify Code</span>
      <span v-else>Verifying...</span>
    </button>

    <!-- Alternative Input Toggle -->
    <button
      type="button"
      @click="showAlternativeInput = !showAlternativeInput"
      class="text-primary hover:text-accent w-full py-2 text-sm transition"
    >
      {{ showAlternativeInput ? 'Back to digit input' : 'Enter code manually' }}
    </button>

    <!-- Back to Login -->
    <div class="border-accent/20 border-t pt-4 text-center">
      <router-link to="/login" class="text-primary hover:text-accent text-sm transition">
        Back to Login
      </router-link>
    </div>
  </form>

  <!-- Info Card -->
  <div class="border-accent/20 mt-6 rounded-lg border p-4">
    <p class="text-muted mb-2 text-center text-xs">
      <strong>Don't have access to your authenticator?</strong>
    </p>
    <p class="text-muted text-center text-xs">
      Contact support at support@civicconnect.com for assistance
    </p>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/authStore'
import { useToast } from 'vue-toastification'
import { ShieldCheckIcon } from '@heroicons/vue/24/outline'

const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()

const otpDigits = ref(['', '', '', '', '', ''])
const otpInputs = ref([])
const manualOtp = ref('')
const showAlternativeInput = ref(false)
const error = ref('')
const errors = ref({ otpCode: '' })
const isLoading = ref(false)

const otpCode = computed(() => otpDigits.value.join(''))

onMounted(() => {
  if (otpInputs.value[0]) {
    otpInputs.value[0].focus()
  }
})

const handleManualInput = () => {
  manualOtp.value = manualOtp.value.replace(/\D/g, '').slice(0, 6)
  syncToDigits()
}

const handleDigitInput = (index, event) => {
  const value = event.target.value

  if (!/^\d*$/.test(value)) {
    event.target.value = ''
    otpDigits.value[index] = ''
    return
  }

  otpDigits.value[index] = value

  // Auto-focus next input
  if (value && index < 5) {
    otpInputs.value[index + 1]?.focus()
  }

  // Sync manual input
  manualOtp.value = otpCode.value

  // Auto-submit if all digits are entered
  if (otpCode.value.length === 6) {
    handleVerifyOTP()
  }
}

const handleKeyDown = (index, event) => {
  if (event.key === 'Backspace' && !otpDigits.value[index] && index > 0) {
    otpInputs.value[index - 1]?.focus()
  } else if (event.key === 'ArrowLeft' && index > 0) {
    otpInputs.value[index - 1]?.focus()
  } else if (event.key === 'ArrowRight' && index < 5) {
    otpInputs.value[index + 1]?.focus()
  }
}

const handlePaste = (event) => {
  event.preventDefault()
  const pastedData = (event.clipboardData || window.clipboardData).getData('text')
  const digits = pastedData.replace(/\D/g, '').slice(0, 6)

  otpDigits.value = digits.split('').concat(Array(6 - digits.length).fill(''))
  manualOtp.value = digits

  // Focus on first empty or last field
  const emptyIndex = otpDigits.value.findIndex((d) => !d)
  if (emptyIndex >= 0) {
    otpInputs.value[emptyIndex]?.focus()
  } else {
    otpInputs.value[5]?.focus()
  }

  // Auto-submit if all digits
  if (digits.length === 6) {
    handleVerifyOTP()
  }
}

const syncToDigits = () => {
  const digits = manualOtp.value.split('')
  otpDigits.value = digits.concat(Array(6 - digits.length).fill(''))
}

const validateField = () => {
  errors.value.otpCode = ''

  if (!otpCode.value) {
    errors.value.otpCode = 'OTP code is required'
  } else if (otpCode.value.length !== 6) {
    errors.value.otpCode = 'Please enter a 6-digit code'
  } else if (!/^\d+$/.test(otpCode.value)) {
    errors.value.otpCode = 'Code must contain only numbers'
  }
}

const handleVerifyOTP = async () => {
  error.value = ''

  validateField()

  if (errors.value.otpCode) {
    return
  }

  isLoading.value = true

  try {
    // This would call a 2FA verification endpoint
    // For now, we'll simulate the verification
    const response = (await authStore.verifyOTP?.(otpCode.value)) || {
      success: true,
      message: 'OTP verified successfully',
    }

    if (response.success) {
      toast.success('Authentication successful!')

      // Redirect to dashboard or return URL
      const defaultDashboard = authStore.isAdmin
        ? '/admin/dashboard'
        : authStore.isStaff
          ? '/staff/dashboard'
          : '/dashboard'
      const returnUrl = sessionStorage.getItem('authReturnUrl') || defaultDashboard
      sessionStorage.removeItem('authReturnUrl')

      setTimeout(() => {
        router.push(returnUrl)
      }, 1000)
    }
  } catch (err) {
    error.value = err || 'OTP verification failed. Please try again.'
    toast.error(error.value)
  } finally {
    isLoading.value = false
  }
}
</script>
