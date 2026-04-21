import { defineStore } from 'pinia'
import axios from 'axios'

const API_BASE_URL = 'http://localhost/civic-connect/backend/api'

export const useNotificationsStore = defineStore('notifications', {
  state: () => ({
    notifications: [],
    unreadCount: 0,
    isLoading: false,
    error: null,
  }),

  actions: {
    getAuthHeaders() {
      const token = localStorage.getItem('token')
      return token ? { Authorization: `Bearer ${token}` } : {}
    },

    async fetchNotifications(unreadOnly = false) {
      this.isLoading = true
      this.error = null
      try {
        const headers = this.getAuthHeaders()
        const response = await axios.get(`${API_BASE_URL}/notifications`, {
          params: { unread_only: unreadOnly },
          headers,
        })
        this.notifications = response.data.notifications || []
        return response.data
      } catch (error) {
        if (error.response?.status === 401) {
          this.notifications = []
          this.unreadCount = 0
          this.error = null
          return { notifications: [] }
        }

        this.error = error.response?.data?.error || 'Failed to fetch notifications'
        this.notifications = [] // Ensure array on error
        throw error
      } finally {
        this.isLoading = false
      }
    },

    async fetchUnreadCount() {
      try {
        const headers = this.getAuthHeaders()
        const response = await axios.get(`${API_BASE_URL}/notifications/unread-count`, { headers })
        this.unreadCount = response.data.count
        return response.data.count
      } catch (error) {
        if (error.response?.status === 401) {
          this.unreadCount = 0
          this.error = null
          return 0
        }

        console.error('Failed to fetch unread count:', error)
        return 0
      }
    },

    async markAsRead(notificationId) {
      try {
        const headers = this.getAuthHeaders()
        await axios.put(`${API_BASE_URL}/notifications/${notificationId}/read`, {}, { headers })

        // Update local state
        const notification = this.notifications.find((n) => n.id === notificationId)
        if (notification && !notification.is_read) {
          notification.is_read = true
          this.unreadCount = Math.max(0, this.unreadCount - 1)
        }
      } catch (error) {
        if (error.response?.status === 401) {
          this.error = null
          return
        }

        console.error('Failed to mark notification as read:', error)
        throw error
      }
    },

    async markAllAsRead() {
      try {
        const headers = this.getAuthHeaders()
        await axios.put(`${API_BASE_URL}/notifications/mark-all-read`, {}, { headers })

        // Update local state
        this.notifications.forEach((n) => (n.is_read = true))
        this.unreadCount = 0
      } catch (error) {
        if (error.response?.status === 401) {
          this.error = null
          return
        }

        console.error('Failed to mark all notifications as read:', error)
        throw error
      }
    },
  },
})
