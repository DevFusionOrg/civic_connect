import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

const API_BASE_URL = 'http://localhost/civic-connect/backend/api'

export const useIssuesStore = defineStore('issues', () => {
  const issues = ref([])
  const currentIssue = ref(null)
  const isLoading = ref(false)
  const error = ref(null)
  const totalCount = ref(0)
  const filters = ref({
    status: null,
    category: null,
    sortBy: 'recent',
  })

  // Computed properties
  const filteredIssues = computed(() => {
    let result = [...issues.value]

    if (filters.value.status) {
      result = result.filter((issue) => issue.status === filters.value.status)
    }

    if (filters.value.category) {
      result = result.filter((issue) => issue.category === filters.value.category)
    }

    // Sort
    if (filters.value.sortBy === 'recent') {
      result.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
    } else if (filters.value.sortBy === 'upvotes') {
      result.sort((a, b) => b.upvote_count - a.upvote_count)
    }

    return result
  })

  const issueCategoryOptions = [
    { value: 'roads', label: 'Road' },
    { value: 'street_lights', label: 'Street Lights' },
    { value: 'trash', label: 'Dustbin / Trash' },
    { value: 'water_drainage', label: 'Water & Drainage' },
    { value: 'parks_recreation', label: 'Parks & Recreation' },
    { value: 'public_safety', label: 'Public Safety' },
    { value: 'graffiti_vandalism', label: 'Graffiti & Vandalism' },
    { value: 'noise', label: 'Noise' },
    { value: 'other', label: 'Other' },
  ]

  const categoryAliases = {
    road: 'roads',
    roads: 'roads',
    streetlight: 'street_lights',
    street_lights: 'street_lights',
    dustbin: 'trash',
    trash: 'trash',
    water: 'water_drainage',
    water_drainage: 'water_drainage',
    park: 'parks_recreation',
    parks_recreation: 'parks_recreation',
    safety: 'public_safety',
    public_safety: 'public_safety',
    graffiti: 'graffiti_vandalism',
    graffiti_vandalism: 'graffiti_vandalism',
    noise: 'noise',
    other: 'other',
  }

  const issueCategories = computed(() => issueCategoryOptions.map((category) => category.value))

  const issueStatuses = computed(() => ['pending_review', 'in_progress', 'resolved'])

  const issuePriorities = computed(() => ['low', 'medium', 'high'])

  const normalizeIssueCategory = (category) => {
    if (!category) return ''

    const normalized = String(category).toLowerCase().trim().replace(/\s+/g, '_')
    return categoryAliases[normalized] || normalized
  }

  const formatIssueCategory = (category) => {
    const normalized = normalizeIssueCategory(category)
    const option = issueCategoryOptions.find((item) => item.value === normalized)
    return option?.label || normalized.replace(/_/g, ' ')
  }

  // Fetch all issues
  const fetchIssues = async (params = {}) => {
    isLoading.value = true
    error.value = null
    try {
      const token = localStorage.getItem('token')
      const headers = {}
      if (token) {
        headers['Authorization'] = `Bearer ${token}`
      }
      const response = await axios.get(`${API_BASE_URL}/issues`, { params, headers })
      issues.value = response.data.issues || []

      // Store total count from pagination metadata
      if (response.data.pagination) {
        totalCount.value = response.data.pagination.total || 0
      } else {
        // If no pagination, use array length (for backward compatibility)
        totalCount.value = issues.value.length
      }

      return issues.value
    } catch (err) {
      error.value = err.response?.data?.error || 'Failed to fetch issues'
      issues.value = [] // Ensure array on error
      totalCount.value = 0
      throw error.value
    } finally {
      isLoading.value = false
    }
  }

  // Fetch single issue
  const fetchIssueById = async (issueId) => {
    isLoading.value = true
    error.value = null
    try {
      const token = localStorage.getItem('token')
      const headers = {}
      if (token) {
        headers['Authorization'] = `Bearer ${token}`
      }

      const response = await axios.get(`${API_BASE_URL}/issues/${issueId}`, { headers })
      currentIssue.value = response.data.issue

      return response.data.issue
    } catch (err) {
      error.value = err.response?.data?.error || 'Failed to fetch issue'
      throw error.value
    } finally {
      isLoading.value = false
    }
  }

  // Create issue
  const createIssue = async (formData) => {
    isLoading.value = true
    error.value = null
    try {
      if (formData instanceof FormData) {
        const category = normalizeIssueCategory(formData.get('category'))
        if (category) {
          formData.set('category', category)
        }
      }

      const token = localStorage.getItem('token')
      const response = await axios.post(`${API_BASE_URL}/issues`, formData, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      })

      const newIssue = response.data.issue || null
      if (newIssue) {
        issues.value.unshift(newIssue)
      }

      return {
        success: true,
        message: response.data.message,
        issue: newIssue,
        issue_id: response.data.issue_id,
        ai_detection: response.data.ai_detection,
        confidence: response.data.confidence,
        ai_auto_filled: response.data.ai_auto_filled,
      }
    } catch (err) {
      error.value = err.response?.data?.error || 'Failed to create issue'
      throw error.value
    } finally {
      isLoading.value = false
    }
  }

  // Detect issue image with AI before full submit
  const detectIssueImageAI = async (file) => {
    error.value = null
    try {
      const token = localStorage.getItem('token')
      if (!token) {
        throw new Error('Authentication required')
      }

      const formData = new FormData()
      formData.append('image', file)

      const response = await axios.post(`${API_BASE_URL}/issues/detect-image`, formData, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      })

      return response.data
    } catch (err) {
      if (err.response?.status === 401 || err.message === 'Authentication required') {
        error.value = 'Please login to use AI detection'
        throw error.value
      }

      error.value = err.response?.data?.error || 'Failed to run AI image detection'
      throw error.value
    }
  }

  // Update issue
  const updateIssue = async (issueId, formData) => {
    isLoading.value = true
    error.value = null
    try {
      const token = localStorage.getItem('token')
      const headers = {}
      if (token) {
        headers['Authorization'] = `Bearer ${token}`
      }

      const response = await axios.put(`${API_BASE_URL}/issues/${issueId}`, formData, { headers })

      const updatedIssue = response.data.issue
      const index = issues.value.findIndex((issue) => issue.id === issueId)
      if (index !== -1) {
        // Update the issue in place to maintain reactivity
        Object.assign(issues.value[index], updatedIssue)
      } else {
        // If not found in array, try to add it
        issues.value.push(updatedIssue)
      }

      if (currentIssue.value?.id === issueId) {
        currentIssue.value = updatedIssue
      }

      return {
        success: true,
        message: response.data.message,
        issue: updatedIssue,
      }
    } catch (err) {
      error.value = err.response?.data?.error || 'Failed to update issue'
      throw error.value
    } finally {
      isLoading.value = false
    }
  }

  // Delete issue
  const deleteIssue = async (issueId) => {
    isLoading.value = true
    error.value = null
    try {
      const response = await axios.delete(`${API_BASE_URL}/issues/${issueId}`)
      issues.value = issues.value.filter((issue) => issue.id !== issueId)

      if (currentIssue.value?.id === issueId) {
        currentIssue.value = null
      }

      return {
        success: true,
        message: response.data.message,
      }
    } catch (err) {
      error.value = err.response?.data?.error || 'Failed to delete issue'
      throw error.value
    } finally {
      isLoading.value = false
    }
  }

  // Fetch user issues
  const fetchUserIssues = async (userId) => {
    isLoading.value = true
    error.value = null
    try {
      const response = await axios.get(`${API_BASE_URL}/users/${userId}/issues`)
      return response.data.issues || []
    } catch (err) {
      error.value = err.response?.data?.error || 'Failed to fetch user issues'
      throw error.value
    } finally {
      isLoading.value = false
    }
  }

  // Upvote issue
  const upvoteIssue = async (issueId) => {
    error.value = null
    try {
      const token = localStorage.getItem('token')
      const response = await axios.post(
        `${API_BASE_URL}/issues/${issueId}/upvotes`,
        {},
        {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        },
      )

      // Update issue in list
      const issue = issues.value.find((i) => i.id === issueId)
      if (issue) {
        issue.upvote_count = response.data.upvote_count
        issue.user_has_upvoted = true
      }

      // Update current issue
      if (currentIssue.value?.id === issueId) {
        currentIssue.value.upvote_count = response.data.upvote_count
        currentIssue.value.user_has_upvoted = true
      }

      return {
        success: true,
        upvoteCount: response.data.upvote_count,
      }
    } catch (err) {
      error.value = err.response?.data?.error || 'Failed to upvote issue'
      throw error.value
    }
  }

  // Remove upvote
  const removeUpvote = async (issueId) => {
    error.value = null
    try {
      const token = localStorage.getItem('token')
      const response = await axios.delete(`${API_BASE_URL}/issues/${issueId}/upvotes`, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      })

      // Update issue in list
      const issue = issues.value.find((i) => i.id === issueId)
      if (issue) {
        issue.upvote_count = response.data.upvote_count
        issue.user_has_upvoted = false
      }

      // Update current issue
      if (currentIssue.value?.id === issueId) {
        currentIssue.value.upvote_count = response.data.upvote_count
        currentIssue.value.user_has_upvoted = false
      }

      return {
        success: true,
        upvoteCount: response.data.upvote_count,
      }
    } catch (err) {
      error.value = err.response?.data?.error || 'Failed to remove upvote'
      throw error.value
    }
  }

  // Set filters
  const setFilters = (newFilters) => {
    filters.value = { ...filters.value, ...newFilters }
  }

  // Reset filters
  const resetFilters = () => {
    filters.value = {
      status: null,
      category: null,
      sortBy: 'recent',
    }
  }

  return {
    issues,
    currentIssue,
    isLoading,
    error,
    totalCount,
    filters,
    filteredIssues,
    issueCategories,
    issueStatuses,
    issuePriorities,
    issueCategoryOptions,
    normalizeIssueCategory,
    formatIssueCategory,
    fetchIssues,
    fetchIssueById,
    createIssue,
    detectIssueImageAI,
    updateIssue,
    deleteIssue,
    fetchUserIssues,
    upvoteIssue,
    removeUpvote,
    setFilters,
    resetFilters,
  }
})
