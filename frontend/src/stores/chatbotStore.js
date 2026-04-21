import { defineStore } from 'pinia'
import axios from 'axios'

export const useChatbotStore = defineStore('chatbot', {
  state: () => ({
    messages: [],
    isOpen: false,
    isLoading: false,
    error: null,
  }),

  getters: {
    conversationHistory: (state) => {
      return state.messages.map((msg) => ({
        role: msg.role,
        content: msg.content,
      }))
    },
  },

  actions: {
    toggleChat() {
      this.isOpen = !this.isOpen
      if (this.isOpen && this.messages.length === 0) {
        this.addWelcomeMessage()
      }
    },

    openChat() {
      this.isOpen = true
      if (this.messages.length === 0) {
        this.addWelcomeMessage()
      }
    },

    closeChat() {
      this.isOpen = false
    },

    addWelcomeMessage() {
      this.messages.push({
        id: Date.now(),
        role: 'assistant',
        content:
          "Hello! 👋 I'm here to help you with CivicConnect. I can assist you with:\n\n• Reporting city issues\n• Understanding issue statuses\n• Navigating the platform\n• Account questions\n\nHow can I help you today?",
        timestamp: new Date().toISOString(),
      })
    },

    async sendMessage(userMessage) {
      if (!userMessage || !userMessage.trim()) {
        return
      }

      // Add user message to chat
      const userMsg = {
        id: Date.now(),
        role: 'user',
        content: userMessage.trim(),
        timestamp: new Date().toISOString(),
      }
      this.messages.push(userMsg)

      // Set loading state
      this.isLoading = true
      this.error = null

      try {
        // Prepare conversation history (exclude welcome message)
        const history = this.messages
          .filter((msg) => msg.content !== userMsg.content)
          .slice(-10) // Keep last 10 messages
          .map((msg) => ({
            role: msg.role === 'user' ? 'user' : 'assistant',
            content: msg.content,
          }))

        // Call chatbot API
        const response = await axios.post(
          'http://localhost/civic-connect/backend/api/chatbot.php',
          {
            message: userMessage.trim(),
            history: history,
          },
        )

        // Add AI response to chat
        if (response.data.success) {
          this.messages.push({
            id: Date.now() + 1,
            role: 'assistant',
            content: response.data.message,
            timestamp: response.data.timestamp,
          })
        } else {
          throw new Error(response.data.error || 'Failed to get response')
        }
      } catch (error) {
        console.error('Chatbot error:', error)
        this.error = error.response?.data?.error || error.message || 'Failed to send message'

        // Add error message to chat
        this.messages.push({
          id: Date.now() + 1,
          role: 'assistant',
          content:
            "I'm sorry, I'm having trouble responding right now. Please try again in a moment.",
          timestamp: new Date().toISOString(),
          isError: true,
        })
      } finally {
        this.isLoading = false
      }
    },

    clearChat() {
      this.messages = []
      this.error = null
      this.addWelcomeMessage()
    },

    // Quick action helpers
    async askQuickQuestion(question) {
      this.openChat()
      await this.sendMessage(question)
    },
  },

  persist: {
    enabled: false, // Don't persist chat history for privacy
  },
})
