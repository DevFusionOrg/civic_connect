<template>
  <div class="chatbot-container">
    <!-- Floating Chat Button -->
    <Transition name="bounce">
      <button
        v-if="!chatStore.isOpen"
        @click="chatStore.openChat"
        class="chat-button"
        aria-label="Open chat"
      >
        <ChatBubbleLeftRightIcon class="h-6 w-6" />
        <span class="sr-only">Open chat assistant</span>
      </button>
    </Transition>

    <!-- Chat Window -->
    <Transition name="slide-up">
      <div v-if="chatStore.isOpen" class="chat-window">
        <!-- Header -->
        <div class="chat-header">
          <div class="flex items-center gap-3">
            <div class="chat-avatar">
              <SparklesIcon class="h-5 w-5 text-white" />
            </div>
            <div>
              <h3 class="font-semibold text-white">CivicConnect Assistant</h3>
              <p class="text-xs text-emerald-100">Powered by AI</p>
            </div>
          </div>
          <button
            @click="chatStore.closeChat"
            class="rounded-lg p-1.5 text-white transition-colors hover:bg-white/10"
            aria-label="Close chat"
          >
            <XMarkIcon class="h-5 w-5" />
          </button>
        </div>

        <!-- Messages Area -->
        <div ref="messagesContainer" class="chat-messages">
          <TransitionGroup name="message">
            <div
              v-for="message in chatStore.messages"
              :key="message.id"
              :class="['message', message.role === 'user' ? 'message-user' : 'message-assistant']"
            >
              <div class="message-content">
                <div v-if="message.role === 'assistant'" class="message-icon">
                  <SparklesIcon class="h-4 w-4 text-emerald-600" />
                </div>
                <div class="message-bubble" :class="{ error: message.isError }">
                  <p class="whitespace-pre-wrap">{{ message.content }}</p>
                </div>
              </div>
            </div>
          </TransitionGroup>

          <!-- Typing Indicator -->
          <Transition name="fade">
            <div v-if="chatStore.isLoading" class="message message-assistant">
              <div class="message-content">
                <div class="message-icon">
                  <SparklesIcon class="h-4 w-4 text-emerald-600" />
                </div>
                <div class="typing-indicator">
                  <span></span>
                  <span></span>
                  <span></span>
                </div>
              </div>
            </div>
          </Transition>
        </div>

        <!-- Quick Actions (shown when no messages) -->
        <div v-if="chatStore.messages.length <= 1" class="quick-actions">
          <button
            v-for="action in quickActions"
            :key="action.text"
            @click="handleQuickAction(action.text)"
            class="quick-action-btn"
          >
            {{ action.text }}
          </button>
        </div>

        <!-- Input Area -->
        <div class="chat-input-container">
          <form @submit.prevent="handleSendMessage" class="chat-input-form">
            <input
              v-model="userInput"
              type="text"
              placeholder="Type your message..."
              class="chat-input"
              :disabled="chatStore.isLoading"
            />
            <button
              type="submit"
              :disabled="!userInput.trim() || chatStore.isLoading"
              class="send-button"
              aria-label="Send message"
            >
              <PaperAirplaneIcon class="h-5 w-5" />
            </button>
          </form>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, nextTick, watch } from 'vue'
import { useChatbotStore } from '@/stores/chatbotStore'
import {
  ChatBubbleLeftRightIcon,
  XMarkIcon,
  SparklesIcon,
  PaperAirplaneIcon,
} from '@heroicons/vue/24/outline'

const chatStore = useChatbotStore()
const userInput = ref('')
const messagesContainer = ref(null)

const quickActions = [
  { text: 'How do I report an issue?' },
  { text: 'What are the issue categories?' },
  { text: 'How does upvoting work?' },
  { text: 'How do I track my reports?' },
]

const handleSendMessage = async () => {
  if (!userInput.value.trim()) return

  const message = userInput.value
  userInput.value = ''

  await chatStore.sendMessage(message)
  scrollToBottom()
}

const handleQuickAction = async (question) => {
  await chatStore.sendMessage(question)
  scrollToBottom()
}

const scrollToBottom = () => {
  nextTick(() => {
    if (messagesContainer.value) {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
    }
  })
}

// Auto-scroll when new messages arrive
watch(
  () => chatStore.messages.length,
  () => {
    scrollToBottom()
  },
)
</script>

<style scoped>
.chatbot-container {
  position: fixed;
  bottom: 1.5rem;
  right: 1.5rem;
  z-index: 1000;
}

/* Chat Button */
.chat-button {
  width: 3.5rem;
  height: 3.5rem;
  border-radius: 9999px;
  background: linear-gradient(135deg, #047857 0%, #059669 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 10px 25px -5px rgba(4, 120, 87, 0.5);
  transition: all 0.3s ease;
}

.chat-button:hover {
  transform: scale(1.1);
  box-shadow: 0 15px 30px -5px rgba(4, 120, 87, 0.6);
}

.chat-button:active {
  transform: scale(0.95);
}

/* Chat Window */
.chat-window {
  width: min(380px, calc(100vw - 1.5rem));
  height: min(550px, calc(100dvh - 2rem));
  background: white;
  border-radius: 1rem;
  box-shadow: 0 20px 60px -10px rgba(0, 0, 0, 0.3);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.chat-window,
.chat-window * {
  box-sizing: border-box;
}

.chat-header h3 {
  font-size: 15px;
  line-height: 1.2;
}

.chat-header p {
  font-size: 12px;
  line-height: 1.2;
}

@media (max-width: 640px) {
  .chat-window {
    position: fixed;
    bottom: 0;
    right: 0;
    left: 0;
    width: 100%;
    height: 100dvh;
    max-height: 100dvh;
    border-radius: 0;
  }

  .chatbot-container {
    bottom: 1rem;
    right: 1rem;
  }
}

/* Header */
.chat-header {
  background: linear-gradient(135deg, #047857 0%, #059669 100%);
  padding: 1rem 1.25rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.chat-avatar {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 9999px;
  background: rgba(255, 255, 255, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Messages Area */
.chat-messages {
  flex: 1;
  overflow-y: auto;
  padding: 1.25rem;
  background: #f9fafb;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.chat-messages::-webkit-scrollbar {
  width: 6px;
}

.chat-messages::-webkit-scrollbar-thumb {
  background: #d1d5db;
  border-radius: 3px;
}

.message {
  display: flex;
  animation: fadeIn 0.3s ease;
}

.message-user {
  justify-content: flex-end;
}

.message-assistant {
  justify-content: flex-start;
}

.message-content {
  display: flex;
  gap: 0.5rem;
  max-width: 80%;
}

.message-icon {
  width: 1.75rem;
  height: 1.75rem;
  border-radius: 9999px;
  background: #eff6ff;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.message-bubble {
  padding: 0.75rem 1rem;
  border-radius: 1rem;
  font-size: 14px;
  line-height: 1.5;
}

.message-user .message-bubble {
  background: linear-gradient(135deg, #047857 0%, #059669 100%);
  color: white;
  border-bottom-right-radius: 0.25rem;
}

.message-assistant .message-bubble {
  background: white;
  color: #1f2937;
  border-bottom-left-radius: 0.25rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.message-bubble.error {
  background: #fef2f2;
  color: #991b1b;
  border: 1px solid #fecaca;
}

/* Typing Indicator */
.typing-indicator {
  display: flex;
  gap: 0.25rem;
  padding: 0.75rem 1rem;
  background: white;
  border-radius: 1rem;
  border-bottom-left-radius: 0.25rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.typing-indicator span {
  width: 0.5rem;
  height: 0.5rem;
  border-radius: 9999px;
  background: #9ca3af;
  animation: typing 1.4s infinite;
}

.typing-indicator span:nth-child(2) {
  animation-delay: 0.2s;
}

.typing-indicator span:nth-child(3) {
  animation-delay: 0.4s;
}

@keyframes typing {
  0%,
  60%,
  100% {
    transform: translateY(0);
    opacity: 0.7;
  }
  30% {
    transform: translateY(-0.5rem);
    opacity: 1;
  }
}

/* Quick Actions */
.quick-actions {
  padding: 0 1.25rem 1rem;
  background: #f9fafb;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.quick-action-btn {
  padding: 0.625rem 1rem;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  font-size: 13px;
  color: #4b5563;
  text-align: left;
  transition: all 0.2s ease;
}

.quick-action-btn:hover {
  background: #f3f4f6;
  border-color: #047857;
  color: #059669;
}

/* Input Area */
.chat-input-container {
  padding: 1rem 1.25rem;
  background: white;
  border-top: 1px solid #e5e7eb;
}

.chat-input-form {
  display: flex;
  gap: 0.75rem;
}

.chat-input {
  flex: 1;
  padding: 0.75rem 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 0.75rem;
  font-size: 14px;
  outline: none;
  transition: all 0.2s ease;
}

.chat-input:focus {
  border-color: #047857;
  box-shadow: 0 0 0 3px rgba(4, 120, 87, 0.1);
}

.chat-input:disabled {
  background: #f9fafb;
  cursor: not-allowed;
}

.send-button {
  width: 2.75rem;
  height: 2.75rem;
  border-radius: 0.75rem;
  background: linear-gradient(135deg, #047857 0%, #059669 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.send-button:hover:not(:disabled) {
  transform: scale(1.05);
  box-shadow: 0 4px 12px rgba(4, 120, 87, 0.4);
}

.send-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Transitions */
.bounce-enter-active {
  animation: bounce 0.5s ease;
}

.bounce-leave-active {
  animation: bounce 0.3s ease reverse;
}

@keyframes bounce {
  0% {
    transform: scale(0);
  }
  50% {
    transform: scale(1.1);
  }
  100% {
    transform: scale(1);
  }
}

.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.3s ease;
}

.slide-up-enter-from {
  transform: translateY(1rem);
  opacity: 0;
}

.slide-up-leave-to {
  transform: translateY(1rem);
  opacity: 0;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.message-enter-active {
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(0.5rem);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
