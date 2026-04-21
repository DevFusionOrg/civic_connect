import { defineStore } from 'pinia'

export const useChatbotStore = defineStore('chatbot', {
  state: () => ({
    isOpen: false,
    messages: [],
    isLoading: false,
  }),

  actions: {
    openChat() {
      this.isOpen = true
    },

    closeChat() {
      this.isOpen = false
    },

    addMessage(message) {
      this.messages.push(message)
    },

    clearMessages() {
      this.messages = []
    }
  }
})
