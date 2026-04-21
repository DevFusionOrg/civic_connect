import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

const API_BASE_URL = 'http://localhost/civic-connect/backend/api'
const AUTH_BASE_URL = `${API_BASE_URL}/auth`
const USERS_BASE_URL = `${API_BASE_URL}/users`

// Configure axios to send cookies with requests
axios.defaults.withCredentials = true

let isAuthInterceptorConfigured = false

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('token'))
  const isLoading = ref(false)
  const error = ref(null)
  const isAuthenticated = computed(() => !!token.value)
  const userRole = computed(() => user.value?.role || 'citizen')

  const logApiSuccess = (action, response) => {
    console.log(`[authStore] ${action} success`, {
      status: response?.status,
      data: response?.data,
    })
  }

  const logApiError = (action, err) => {
    console.error(`[authStore] ${action} failed`, {
      status: err?.response?.status,
      data: err?.response?.data,
      message: err?.message,
    })
  }

  const postAuthEndpoint = async (action, authPath, usersPath, payload = {}) => {
    try {
      return await axios.post(`${AUTH_BASE_URL}/${authPath}`, payload)
    } catch (err) {
      const status = err?.response?.status
      if (status === 404 && usersPath) {
        console.warn(`[authStore] ${action} fallback to /users/${usersPath}`)
        return axios.post(`${USERS_BASE_URL}/${usersPath}`, payload)
      }
      throw err
    }
  }

  // Role-based access
  const isAdmin = computed(() => user.value?.role === 'admin')
  const isStaff = computed(() => user.value?.role === 'staff')
  const isCitizen = computed(() => user.value?.role === 'citizen')

  // Set axios default authorization header
  const setAuthHeader = (authToken) => {
    if (authToken) {
      axios.defaults.headers.common['Authorization'] = `Bearer ${authToken}`
    } else {
      delete axios.defaults.headers.common['Authorization']
    }
  }

  const clearAuthState = () => {
    user.value = null
    token.value = null
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    setAuthHeader(null)
  }

  const setupAuthInterceptor = () => {
    if (isAuthInterceptorConfigured) return

    axios.interceptors.response.use(
      (response) => response,
      (err) => {
        const status = err?.response?.status
        const requestUrl = err?.config?.url || ''

        // If a protected API request returns 401, clear stale session state.
        if (status === 401 && requestUrl.includes('/backend/api/')) {
          clearAuthState()
        }

        return Promise.reject(err)
      },
    )

    isAuthInterceptorConfigured = true
  }

  // Load user from localStorage on init
  const loadUserFromStorage = () => {
    try {
      const storedUser = localStorage.getItem('user')
      if (storedUser && storedUser !== 'undefined' && storedUser !== 'null') {
        user.value = JSON.parse(storedUser)
      } else if (storedUser === 'undefined' || storedUser === 'null') {
        // Clear corrupted data
        console.warn('Clearing corrupted user data from localStorage')
        localStorage.removeItem('user')
        localStorage.removeItem('token')
      }
    } catch (err) {
      console.error('Failed to load user from localStorage:', err)
      // Clear corrupted data
      localStorage.removeItem('user')
      localStorage.removeItem('token')
    }
  }

  if (token.value) {
    setAuthHeader(token.value)
    loadUserFromStorage()
  }

  setupAuthInterceptor()

  // Register user
  const register = async (formData) => {
    isLoading.value = true
    error.value = null
    try {
      const response = await postAuthEndpoint('register', 'register', 'register', {
        email: formData.email,
        password: formData.password,
        first_name: formData.firstName,
        last_name: formData.lastName,
        phone: formData.phone || null,
      })
      logApiSuccess('register', response)

      return {
        success: true,
        message: response.data.message,
        userId: response.data.user_id,
        email: response.data.email,
      }
    } catch (err) {
      logApiError('register', err)
      error.value = err.response?.data?.error || 'Registration failed'
      throw error.value
    } finally {
      isLoading.value = false
    }
  }

  // Verify email with OTP
  const verifyEmail = async (email, otpCode) => {
    isLoading.value = true
    error.value = null
    try {
      const response = await postAuthEndpoint('verifyEmail', 'verify-email', 'verify-email', {
        email,
        otp_code: otpCode,
      })
      logApiSuccess('verifyEmail', response)

      return {
        success: true,
        message: response.data.message,
        userId: response.data.user_id,
      }
    } catch (err) {
      logApiError('verifyEmail', err)
      error.value = err.response?.data?.error || 'Email verification failed'
      throw error.value
    } finally {
      isLoading.value = false
    }
  }

  // Resend OTP
  const resendOTP = async (email) => {
    isLoading.value = true
    error.value = null
    try {
      const response = await postAuthEndpoint(
        'resendOTP',
        'resend-verification',
        'resend-otp',
        { email },
      )
      logApiSuccess('resendOTP', response)

      return {
        success: true,
        message: response.data.message,
      }
    } catch (err) {
      logApiError('resendOTP', err)
      error.value = err.response?.data?.error || 'Failed to resend OTP'
      throw error.value
    } finally {
      isLoading.value = false
    }
  }

  // Login user
  const login = async (email, password) => {
    isLoading.value = true
    error.value = null
    try {
      const response = await postAuthEndpoint('login', 'login', 'login', {
        email,
        password,
      })
      logApiSuccess('login', response)

      token.value = response.data.token
      user.value = response.data.user

      // Store token in localStorage
      localStorage.setItem('token', response.data.token)
      localStorage.setItem('user', JSON.stringify(response.data.user))

      // Set axios default header
      setAuthHeader(response.data.token)

      return {
        success: true,
        message: 'Login successful',
        user: response.data.user,
      }
    } catch (err) {
      logApiError('login', err)
      error.value = err.response?.data?.error || 'Login failed'
      throw error.value
    } finally {
      isLoading.value = false
    }
  }

  // Logout user
  const logout = async () => {
    try {
      const response = await postAuthEndpoint('logout', 'logout', 'logout')
      logApiSuccess('logout', response)
    } catch (err) {
      logApiError('logout', err)
      // Continue with logout even if API call fails
      console.warn('Logout API error:', err)
    } finally {
      // Clear local state
      clearAuthState()
    }
  }

  // Get user profile
  const fetchUserProfile = async (userId) => {
    isLoading.value = true
    error.value = null
    try {
      const response = await axios.get(`${API_BASE_URL}/users/${userId}`)
      user.value = response.data.user

      return response.data.user
    } catch (err) {
      error.value = err.response?.data?.error || 'Failed to fetch user profile'
      throw error.value
    } finally {
      isLoading.value = false
    }
  }

  // Update user profile
  const updateUserProfile = async (userId, formData) => {
    isLoading.value = true
    error.value = null
    try {
      const response = await axios.put(`${API_BASE_URL}/users/${userId}`, formData)

      // Ensure we have valid user data before updating
      if (response.data.user) {
        user.value = response.data.user
        localStorage.setItem('user', JSON.stringify(response.data.user))
        return response.data.user
      } else {
        throw new Error('No user data returned from server')
      }
    } catch (err) {
      error.value = err.response?.data?.error || err.message || 'Failed to update profile'
      throw error.value
    } finally {
      isLoading.value = false
    }
  }

  // Initialize auth from localStorage
  const initializeAuth = () => {
    const storedToken = localStorage.getItem('token')
    const storedUser = localStorage.getItem('user')

    if (storedToken && storedUser && storedUser !== 'undefined' && storedUser !== 'null') {
      try {
        token.value = storedToken
        user.value = JSON.parse(storedUser)
        setAuthHeader(storedToken)
      } catch (err) {
        console.error('Failed to initialize auth:', err)
        // Clear corrupted data
        clearAuthState()
      }
    }
  }

  return {
    user,
    token,
    isLoading,
    error,
    isAuthenticated,
    userRole,
    isAdmin,
    isStaff,
    isCitizen,
    register,
    verifyEmail,
    resendOTP,
    login,
    logout,
    fetchUserProfile,
    updateUserProfile,
    initializeAuth,
    setAuthHeader,
    clearAuthState,
  }
})
