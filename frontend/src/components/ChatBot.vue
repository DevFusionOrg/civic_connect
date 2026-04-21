<template>
  <div class="chatbot-container fixed bottom-6 right-6 z-50 font-sans">
    <!-- Floating Chat Button -->
    <Transition name="bounce">
      <button
        v-if="!chatStore.isOpen"
        @click="openChat"
        class="chat-button w-16 h-16 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-full shadow-2xl hover:shadow-emerald-500/50 transition-all duration-300 flex items-center justify-center group relative"
        aria-label="Open chat"
      >
        <!-- Pulse effect -->
        <div class="absolute inset-0 rounded-full bg-emerald-400 animate-ping opacity-20"></div>
        <ChatBubbleLeftRightIcon class="h-7 w-7 text-white relative z-10" />
        <!-- Notification badge -->
        <div class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full flex items-center justify-center">
          <span class="text-xs text-white font-bold">!</span>
        </div>
        <span class="sr-only">Open chat assistant</span>
      </button>
    </Transition>

    <!-- Chat Window -->
    <Transition name="slide-up">
      <div
        v-if="chatStore.isOpen"
        class="chat-window absolute bottom-20 right-0 w-96 h-[600px] bg-white/10 backdrop-blur-xl border border-white/20 rounded-3xl shadow-2xl overflow-hidden"
      >
        <!-- Animated gradient background -->
        <div class="absolute inset-0 bg-gradient-to-br from-emerald-50/20 via-white/10 to-emerald-100/20"></div>

        <!-- Header -->
        <div class="relative z-10 bg-gradient-to-r from-emerald-500 via-emerald-600 to-emerald-700 p-6 text-white">
          <!-- Animated gradient overlay -->
          <div class="absolute inset-0 bg-gradient-to-r from-emerald-400/20 via-transparent to-emerald-600/20 animate-pulse"></div>

          <div class="header-content flex items-center justify-between relative z-10">
            <div class="flex items-center gap-3">
              <div class="chat-avatar w-12 h-12 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm transition-transform hover:scale-105">
                <span class="avatar-icon text-2xl">🤖</span>
              </div>
              <div class="header-text">
                <h3 class="font-bold text-lg">CivicConnect Assistant</h3>
                <p class="text-emerald-100 text-sm">Smart & Helpful</p>
              </div>
            </div>
            <button
              @click="closeChat"
              class="close-button w-8 h-8 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm hover:bg-white/30 transition-all duration-200 hover:rotate-90 hover:scale-110"
              aria-label="Close chat"
            >
              ✕
            </button>
          </div>
        </div>

        <!-- Messages Area -->
        <div ref="messagesContainer" class="chat-messages flex-1 p-6 overflow-y-auto space-y-4 relative z-10">
          <TransitionGroup name="message" tag="div">
            <div
              v-for="message in chatStore.messages"
              :key="message.id"
              :class="['message flex', message.role === 'user' ? 'justify-end' : 'justify-start']"
            >
              <div class="message-content flex gap-3 max-w-[80%]" :class="message.role === 'user' ? 'flex-row-reverse' : ''">
                <div v-if="message.role === 'assistant'" class="message-icon flex-shrink-0">
                  <div class="bot-icon w-8 h-8 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-full flex items-center justify-center shadow-lg transition-transform hover:scale-110">
                    <span class="text-sm">🤖</span>
                  </div>
                </div>
                <div class="message-bubble rounded-2xl p-4 shadow-lg" :class="message.role === 'user' ? 'bg-gradient-to-r from-emerald-500 to-emerald-600 text-white' : 'bg-white/80 backdrop-blur-sm border border-white/20 text-gray-800'">
                  <p class="text-sm leading-relaxed">{{ message.content }}</p>

                  <!-- Options -->
                  <TransitionGroup v-if="message.options" name="option" tag="div" class="mt-4 space-y-2">
                    <button
                      v-for="option in message.options"
                      :key="option.label"
                      @click="handleButtonClick(option)"
                      :disabled="chatStore.isLoading"
                      class="option-button w-full p-3 rounded-xl border border-emerald-200/50 bg-gradient-to-r from-white to-emerald-50/50 hover:from-emerald-50 hover:to-emerald-100/50 text-left text-sm font-medium text-emerald-800 transition-all duration-200 shadow-sm hover:shadow-md hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed group relative overflow-hidden"
                      :class="message.role === 'user' ? 'bg-white/20 text-white border-white/30' : ''"
                    >
                      <!-- Ripple effect -->
                      <div class="absolute inset-0 bg-emerald-400/20 opacity-0 group-active:opacity-100 transition-opacity duration-150 rounded-xl"></div>

                      <div class="flex items-center gap-3 relative z-10">
                        <span class="flex-1">{{ option.label }}</span>
                      </div>
                    </button>
                  </TransitionGroup>
                </div>
              </div>
            </div>
          </TransitionGroup>

          <!-- Typing Indicator -->
          <Transition name="fade">
            <div v-if="chatStore.isLoading" class="message flex justify-start">
              <div class="message-content flex gap-3 max-w-[80%]">
                <div class="message-icon flex-shrink-0">
                  <div class="bot-icon w-8 h-8 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-full flex items-center justify-center shadow-lg">
                    <span class="text-sm">🤖</span>
                  </div>
                </div>
                <div class="message-bubble bg-white/80 backdrop-blur-sm border border-white/20 rounded-2xl p-4 shadow-lg">
                  <div class="typing-indicator flex gap-1">
                    <span class="w-2 h-2 bg-emerald-500 rounded-full animate-bounce"></span>
                    <span class="w-2 h-2 bg-emerald-500 rounded-full animate-bounce" style="animation-delay: 0.1s"></span>
                    <span class="w-2 h-2 bg-emerald-500 rounded-full animate-bounce" style="animation-delay: 0.2s"></span>
                  </div>
                </div>
              </div>
            </div>
          </Transition>

          <div ref="messagesEndRef"></div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, nextTick, watch, onMounted } from 'vue'
import { useChatbotStore } from '@/stores/chatbotStore'
import { ChatBubbleLeftRightIcon } from '@heroicons/vue/24/outline'

const chatStore = useChatbotStore()
const messagesContainer = ref(null)
const messagesEndRef = ref(null)

// Enhanced decision tree with icons
const decisionTree = {
  "start": {
    "message": "👋 Hi! I'm your CivicConnect assistant. How can I help you today?",
    "options": [
      { "label": "📝 Report an issue", "next": "report_issue", "icon": "📝" },
      { "label": "📊 Track my reports", "next": "track_reports", "icon": "📊" },
      { "label": "📈 What do statuses mean?", "next": "status_info", "icon": "📈" },
      { "label": "🏷️ Issue categories", "next": "categories_info", "icon": "🏷️" },
      { "label": "👤 Login/Register", "next": "account_help", "icon": "👤" }
    ]
  },
  "report_issue": {
    "message": "What type of issue would you like to report?",
    "options": [
      { "label": "🛣️ Roads & Infrastructure", "next": "roads_infrastructure", "icon": "🛣️" },
      { "label": "💡 Street Lights", "next": "street_lights", "icon": "💡" },
      { "label": "🗑️ Trash & Cleanliness", "next": "trash_cleanliness", "icon": "🗑️" },
      { "label": "🌊 Water & Drainage", "next": "water_drainage", "icon": "🌊" },
      { "label": "🏞️ Parks & Recreation", "next": "parks_recreation", "icon": "🏞️" },
      { "label": "🚔 Public Safety", "next": "public_safety", "icon": "🚔" },
      { "label": "🎨 Graffiti & Vandalism", "next": "graffiti_vandalism", "icon": "🎨" },
      { "label": "🔊 Noise", "next": "noise_issues", "icon": "🔊" },
      { "label": "❓ Other Issues", "next": "other_issues", "icon": "❓" },
      { "label": "⬅️ Back to Main Menu", "next": "start", "icon": "⬅️" }
    ]
  },
  "roads_infrastructure": {
    "message": "Please select the road/infrastructure issue:",
    "options": [
      { "label": "🕳️ Potholes", "response": "For potholes, please take a photo and submit through our issue reporting system. Priority is given to main roads and high-traffic areas.", "icon": "🕳️" },
      { "label": "🛤️ Road Cracks", "response": "Road cracks are typically scheduled for repair during our quarterly maintenance cycles. You can report them for our records.", "icon": "🛤️" },
      { "label": "🚧 Street Repairs", "response": "Emergency street repairs are handled within 24 hours. Non-emergency repairs are scheduled based on priority and available resources.", "icon": "🚧" },
      { "label": "⬅️ Back to Categories", "next": "report_issue", "icon": "⬅️" }
    ]
  },
  "street_lights": {
    "message": "Please select the street light issue:",
    "options": [
      { "label": "💡 Broken Light", "response": "Broken street lights are repaired within 48 hours. Please provide the exact location (intersection or address) for faster service.", "icon": "💡" },
      { "label": "🔅 Dim Light", "response": "Dim street lights are typically replaced during our scheduled maintenance. You can report them for priority replacement.", "icon": "🔅" },
      { "label": "⚡ Flickering Light", "response": "Flickering lights often indicate electrical issues. These are treated as priority repairs and addressed within 24 hours.", "icon": "⚡" },
      { "label": "⬅️ Back to Categories", "next": "report_issue", "icon": "⬅️" }
    ]
  },
  "trash_cleanliness": {
    "message": "Please select the trash/cleanliness issue:",
    "options": [
      { "label": "🗑️ Overflowing Bins", "response": "Overflowing trash bins are collected within 24 hours. For recurring issues, please report with photos.", "icon": "🗑️" },
      { "label": "🗑️ Illegal Dumping", "response": "Illegal dumping is a serious violation. Please provide photos and exact location. We'll investigate within 48 hours.", "icon": "🚫" },
      { "label": "🗑️ Litter Problems", "response": "Litter issues are addressed through our regular cleanup schedule. You can help by reporting specific problem areas.", "icon": "🗑️" },
      { "label": "⬅️ Back to Categories", "next": "report_issue", "icon": "⬅️" }
    ]
  },
  "water_drainage": {
    "message": "Please select the water/drainage issue:",
    "options": [
      { "label": "💧 Leaking Pipe", "response": "For leaking pipes, call our emergency line at 911 immediately or contact the water department at (555) 123-4567.", "icon": "💧" },
      { "label": "🌊 Flooding", "response": "Flooding issues are treated as emergencies. Please call 911 if there's immediate danger, or report through our system.", "icon": "🌊" },
      { "label": "🚰 Low Water Pressure", "response": "Low water pressure can be caused by various factors. Please provide your address and we'll investigate within 48 hours.", "icon": "🚰" },
      { "label": "⬅️ Back to Categories", "next": "report_issue", "icon": "⬅️" }
    ]
  },
  "parks_recreation": {
    "message": "Please select the parks/recreation issue:",
    "options": [
      { "label": "🎪 Broken Equipment", "response": "Park equipment repairs are scheduled based on safety priority. Emergency repairs are handled within 24 hours.", "icon": "🎪" },
      { "label": "🌳 Tree Maintenance", "response": "Tree-related issues are handled by our arborist team. Please provide photos and exact location.", "icon": "🌳" },
      { "label": "🏖️ Park Cleanliness", "response": "Park maintenance is performed regularly. You can report specific cleanliness issues for priority attention.", "icon": "🏖️" },
      { "label": "⬅️ Back to Categories", "next": "report_issue", "icon": "⬅️" }
    ]
  },
  "public_safety": {
    "message": "Please select the public safety issue:",
    "options": [
      { "label": "🚨 Emergency", "response": "For emergencies, call 911 immediately. Do not use this system for life-threatening situations.", "icon": "🚨" },
      { "label": "🚦 Traffic Safety", "response": "Traffic safety concerns are investigated by our traffic engineering team. Please provide detailed information.", "icon": "🚦" },
      { "label": "🚶 Pedestrian Issues", "response": "Pedestrian safety issues are treated as high priority. We'll investigate within 48 hours.", "icon": "🚶" },
      { "label": "⬅️ Back to Categories", "next": "report_issue", "icon": "⬅️" }
    ]
  },
  "graffiti_vandalism": {
    "message": "Please select the graffiti/vandalism issue:",
    "options": [
      { "label": "🎨 New Graffiti", "response": "New graffiti is removed within 48 hours. Please provide photos and exact location.", "icon": "🎨" },
      { "label": "🔨 Property Damage", "response": "Property damage is investigated by our code enforcement team. Please provide detailed photos and location.", "icon": "🔨" },
      { "label": "🏢 Building Vandalism", "response": "Building vandalism is treated as a serious offense. We'll investigate and coordinate with law enforcement.", "icon": "🏢" },
      { "label": "⬅️ Back to Categories", "next": "report_issue", "icon": "⬅️" }
    ]
  },
  "noise_issues": {
    "message": "Please select the noise issue:",
    "options": [
      { "label": "🔊 Excessive Noise", "response": "Noise complaints are investigated during business hours. Please provide time, location, and description of the noise.", "icon": "🔊" },
      { "label": "🎵 Music Disturbance", "response": "Music-related noise complaints are handled by our community services department.", "icon": "🎵" },
      { "label": "🏗️ Construction Noise", "response": "Construction noise is regulated and monitored. Please provide the construction site address.", "icon": "🏗️" },
      { "label": "⬅️ Back to Categories", "next": "report_issue", "icon": "⬅️" }
    ]
  },
  "other_issues": {
    "message": "Please describe your other issue:",
    "options": [
      { "label": "📝 General Concern", "response": "For general concerns, please use our main issue reporting system with a detailed description.", "icon": "📝" },
      { "label": "❓ Need Help Categorizing", "response": "If you're unsure which category fits your issue, please provide more details and we'll help categorize it.", "icon": "❓" },
      { "label": "⬅️ Back to Categories", "next": "report_issue", "icon": "⬅️" }
    ]
  },
  "track_reports": {
    "message": "How would you like to track your reports?",
    "options": [
      { "label": "📱 Check Status Updates", "response": "You can check status updates by logging into your account and visiting 'My Issues'. Status changes are: Pending Review → In Progress → Resolved.", "icon": "📱" },
      { "label": "📧 Get Notifications", "response": "You'll receive email notifications when your issue status changes. Make sure your email preferences are set up in your account.", "icon": "📧" },
      { "label": "📊 View Issue Details", "response": "Click any issue in 'My Issues' to see: Status, Comments, Upvotes, Location, Photos, and Resolution notes.", "icon": "📊" },
      { "label": "⬅️ Back to Main Menu", "next": "start", "icon": "⬅️" }
    ]
  },
  "status_info": {
    "message": "Select a status to learn more:",
    "options": [
      { "label": "⏳ Pending Review", "response": "Your issue has been submitted and is waiting for review by city officials. This typically takes 24-48 hours.", "icon": "⏳" },
      { "label": "🔧 In Progress", "response": "City officials are actively working on fixing your issue. You may see updates on progress and timelines.", "icon": "🔧" },
      { "label": "✅ Resolved", "response": "Your issue has been fixed! Check the details for completion notes and any follow-up information.", "icon": "✅" },
      { "label": "⬅️ Back to Main Menu", "next": "start", "icon": "⬅️" }
    ]
  },
  "categories_info": {
    "message": "Choose a category to learn more:",
    "options": [
      { "label": "🛣️ Roads & Infrastructure", "response": "Includes: Potholes, cracks, road damage, street repairs, sidewalk issues, and infrastructure problems.", "icon": "🛣️" },
      { "label": "💡 Street Lights", "response": "Includes: Broken, dim, or malfunctioning street lights, traffic signals, and lighting issues.", "icon": "💡" },
      { "label": "🗑️ Trash & Cleanliness", "response": "Includes: Garbage, litter, overflowing bins, illegal dumping, and cleanliness concerns.", "icon": "🗑️" },
      { "label": "🌊 Water & Drainage", "response": "Includes: Flooding, leaks, drainage problems, water quality, and water-related infrastructure.", "icon": "🌊" },
      { "label": "🏞️ Parks & Recreation", "response": "Includes: Park maintenance, playground equipment, recreational facilities, and green space issues.", "icon": "🏞️" },
      { "label": "🚔 Public Safety", "response": "Includes: Safety concerns, traffic issues, security problems, and public safety matters.", "icon": "🚔" },
      { "label": "🎨 Graffiti & Vandalism", "response": "Includes: Graffiti removal, property damage, vandalism reports, and defacement issues.", "icon": "🎨" },
      { "label": "🔊 Noise", "response": "Includes: Excessive noise, disturbances, noise complaints, and sound-related issues.", "icon": "🔊" },
      { "label": "❓ Other Issues", "response": "Any other city-related concerns not listed above. We'll help categorize and address them.", "icon": "❓" },
      { "label": "⬅️ Back to Main Menu", "next": "start", "icon": "⬅️" }
    ]
  },
  "account_help": {
    "message": "Account assistance:",
    "options": [
      { "label": "🔐 Create Account", "response": "To create an account, click 'Sign Up' and provide your email and password. You'll receive a verification email.", "icon": "🔐" },
      { "label": "🚪 Login to Account", "response": "Use the 'Login' button and enter your registered email and password. Use 'Forgot Password' if needed.", "icon": "🚪" },
      { "label": "🔑 Reset Password", "response": "Click 'Forgot Password' on the login page and enter your email. Follow the reset link sent to your email.", "icon": "🔑" },
      { "label": "📧 Verify Email", "response": "Check your email for a verification link after registering. Click the link to activate your account.", "icon": "📧" },
      { "label": "⬅️ Back to Main Menu", "next": "start", "icon": "⬅️" }
    ]
  }
}

const openChat = () => {
  chatStore.openChat()
  // Initialize with welcome message if no messages exist
  if (chatStore.messages.length === 0) {
    const initialState = decisionTree.start
    const welcomeMessage = {
      id: Date.now(),
      role: 'assistant',
      content: initialState.message,
      options: initialState.options,
      timestamp: new Date().toISOString(),
    }
    chatStore.addMessage(welcomeMessage)
  }
}

const closeChat = () => {
  chatStore.closeChat()
}

const handleButtonClick = (option) => {
  // Add user message (the button they clicked)
  const userMessage = {
    id: Date.now(),
    role: 'user',
    content: option.label,
    timestamp: new Date().toISOString(),
  }
  chatStore.addMessage(userMessage)

  // Check if this is a final response or navigation
  if (option.response) {
    // Final response - show message with back button
    chatStore.isLoading = true
    setTimeout(() => {
      const botResponse = {
        id: Date.now() + 1,
        role: 'assistant',
        content: option.response,
        options: [{ "label": "⬅️ Back to Main Menu", "next": "start", "icon": "⬅️" }],
        timestamp: new Date().toISOString(),
      }
      chatStore.addMessage(botResponse)
      chatStore.isLoading = false
    }, 1000)
  } else if (option.next) {
    // Navigate to next state
    const nextState = decisionTree[option.next]
    if (nextState) {
      chatStore.isLoading = true
      setTimeout(() => {
        const botMessage = {
          id: Date.now() + 1,
          role: 'assistant',
          content: nextState.message,
          options: nextState.options,
          timestamp: new Date().toISOString(),
        }
        chatStore.addMessage(botMessage)
        chatStore.isLoading = false
      }, 1000)
    }
  }

  // Auto-scroll to bottom
  nextTick(() => {
    scrollToBottom()
  })
}

const scrollToBottom = () => {
  if (messagesEndRef.value) {
    messagesEndRef.value.scrollIntoView({ behavior: 'smooth' })
  }
}

// Watch for new messages to auto-scroll
watch(() => chatStore.messages.length, () => {
  nextTick(() => {
    scrollToBottom()
  })
})
</script>

<style scoped>
.chatbot-container {
  position: fixed;
  bottom: 20px;
  right: 20px;
  z-index: 1000;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Chat Button */
.chat-button {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: linear-gradient(135deg, #10b981, #059669);
  border: none;
  color: white;
  cursor: pointer;
  box-shadow: 0 4px 20px rgba(16, 185, 129, 0.3);
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.chat-button:hover {
  transform: scale(1.05);
  box-shadow: 0 6px 25px rgba(16, 185, 129, 0.4);
}

/* Chat Window */
.chat-window {
  position: absolute;
  bottom: 80px;
  right: 0;
  width: 380px;
  height: 600px;
  background: white;
  border-radius: 20px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  animation: slideUp 0.3s ease-out;
}

@media (max-width: 480px) {
  .chat-window {
    width: calc(100vw - 40px);
    height: calc(100vh - 120px);
    bottom: 80px;
    right: -10px;
  }
}

/* Header */
.chat-header {
  background: linear-gradient(135deg, #10b981, #059669);
  padding: 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  color: white;
}

.header-content {
  display: flex;
  align-items: center;
  gap: 12px;
}

.chat-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
}

.avatar-icon {
  font-size: 20px;
}

.header-text h3 {
  font-size: 16px;
  font-weight: 600;
  margin: 0;
  color: white;
}

.header-text p {
  font-size: 12px;
  margin: 4px 0 0 0;
  opacity: 0.9;
}

.close-button {
  background: none;
  border: none;
  color: white;
  font-size: 20px;
  cursor: pointer;
  padding: 4px;
  border-radius: 6px;
  transition: background-color 0.2s;
}

.close-button:hover {
  background: rgba(255, 255, 255, 0.1);
}

/* Messages Area */
.chat-messages {
  flex: 1;
  padding: 20px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

/* Messages */
.message {
  display: flex;
  margin-bottom: 12px;
}

.message-user {
  justify-content: flex-end;
}

.message-assistant {
  justify-content: flex-start;
}

.message-content {
  max-width: 80%;
  display: flex;
  gap: 8px;
}

.message-user .message-content {
  flex-direction: row-reverse;
}

.message-icon {
  flex-shrink: 0;
  margin-top: 4px;
}

.bot-icon {
  font-size: 16px;
  color: #10b981;
}

.message-bubble {
  padding: 12px 16px;
  border-radius: 18px;
  position: relative;
  word-wrap: break-word;
}

.message-assistant .message-bubble {
  background: #f0fdf4;
  border: 1px solid #dcfce7;
  color: #14532d;
}

.message-user .message-bubble {
  background: linear-gradient(135deg, #10b981, #059669);
  color: white;
}

/* Message Buttons */
.message-buttons {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-top: 12px;
}

.option-button {
  padding: 10px 16px;
  border: 2px solid #10b981;
  background: white;
  color: #10b981;
  border-radius: 12px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s ease;
  text-align: left;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.option-button:hover:not(:disabled) {
  background: #10b981;
  color: white;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.option-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Typing Indicator */
.typing-indicator {
  display: flex;
  gap: 4px;
  padding: 8px 0;
}

.typing-indicator span {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #10b981;
  animation: typing 1.4s infinite;
}

.typing-indicator span:nth-child(2) {
  animation-delay: 0.2s;
}

.typing-indicator span:nth-child(3) {
  animation-delay: 0.4s;
}

@keyframes typing {
  0%, 60%, 100% {
    transform: translateY(0);
    opacity: 0.4;
  }
  30% {
    transform: translateY(-10px);
    opacity: 1;
  }
}

/* Transitions */
.bounce-enter-active,
.bounce-leave-active {
  transition: all 0.3s ease;
}

.bounce-enter-from,
.bounce-leave-to {
  transform: scale(0);
  opacity: 0;
}

.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.3s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
  transform: translateY(20px);
  opacity: 0;
}

.message-enter-active,
.message-leave-active {
  transition: all 0.3s ease;
}

.message-enter-from,
.message-leave-to {
  transform: translateY(20px);
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
</style>
