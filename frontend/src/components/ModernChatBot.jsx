import React, { useState, useRef, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { ChatBubbleLeftRightIcon, XMarkIcon } from '@heroicons/react/24/outline';

// Decision tree structure - pure button-driven flow
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
};

const ChatBot = () => {
  const [isOpen, setIsOpen] = useState(false);
  const [messages, setMessages] = useState([]);
  const [isLoading, setIsLoading] = useState(false);
  const [currentState, setCurrentState] = useState('start');
  const messagesEndRef = useRef(null);
  const messagesContainerRef = useRef(null);

  // Auto-scroll to bottom when new messages arrive
  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  };

  useEffect(() => {
    scrollToBottom();
  }, [messages]);

  // Initialize with welcome message
  useEffect(() => {
    if (messages.length === 0 && isOpen) {
      const initialState = decisionTree.start;
      const welcomeMessage = {
        id: Date.now(),
        role: 'assistant',
        content: initialState.message,
        options: initialState.options,
        timestamp: new Date().toISOString(),
      };
      setMessages([welcomeMessage]);
    }
  }, [isOpen, messages.length]);

  const openChat = () => {
    setIsOpen(true);
  };

  const closeChat = () => {
    setIsOpen(false);
    setMessages([]);
    setCurrentState('start');
  };

  const handleButtonClick = (option) => {
    // Add user message
    const userMessage = {
      id: Date.now(),
      role: 'user',
      content: option.label,
      timestamp: new Date().toISOString(),
    };
    setMessages(prev => [...prev, userMessage]);

    // Check if this is a final response or navigation
    if (option.response) {
      // Final response - show message with back button
      setIsLoading(true);
      setTimeout(() => {
        const botResponse = {
          id: Date.now() + 1,
          role: 'assistant',
          content: option.response,
          options: [{ "label": "⬅️ Back to Main Menu", "next": "start", "icon": "⬅️" }],
          timestamp: new Date().toISOString(),
        };
        setMessages(prev => [...prev, botResponse]);
        setIsLoading(false);
      }, 1500);
    } else if (option.next) {
      // Navigate to next state
      const nextState = decisionTree[option.next];
      if (nextState) {
        setCurrentState(option.next);
        setIsLoading(true);
        setTimeout(() => {
          const botMessage = {
            id: Date.now() + 1,
            role: 'assistant',
            content: nextState.message,
            options: nextState.options,
            timestamp: new Date().toISOString(),
          };
          setMessages(prev => [...prev, botMessage]);
          setIsLoading(false);
        }, 1000);
      }
    }
  };

  return (
    <div className="fixed bottom-6 right-6 z-50 font-sans">
      {/* Floating Chat Button */}
      <AnimatePresence>
        {!isOpen && (
          <motion.button
            initial={{ scale: 0, opacity: 0 }}
            animate={{ scale: 1, opacity: 1 }}
            exit={{ scale: 0, opacity: 0 }}
            whileHover={{ scale: 1.1 }}
            whileTap={{ scale: 0.95 }}
            onClick={openChat}
            className="w-16 h-16 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-full shadow-2xl hover:shadow-emerald-500/50 transition-all duration-300 flex items-center justify-center group relative"
            aria-label="Open chat"
          >
            {/* Pulse effect */}
            <div className="absolute inset-0 rounded-full bg-emerald-400 animate-ping opacity-20"></div>
            <ChatBubbleLeftRightIcon className="h-7 w-7 text-white relative z-10" />
            {/* Notification badge */}
            <div className="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full flex items-center justify-center">
              <span className="text-xs text-white font-bold">!</span>
            </div>
          </motion.button>
        )}
      </AnimatePresence>

      {/* Chat Window */}
      <AnimatePresence>
        {isOpen && (
          <motion.div
            initial={{ opacity: 0, y: 20, scale: 0.95 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, y: 20, scale: 0.95 }}
            transition={{ duration: 0.3, ease: "easeOut" }}
            className="absolute bottom-20 right-0 w-96 h-[600px] bg-white/10 backdrop-blur-xl border border-white/20 rounded-3xl shadow-2xl overflow-hidden"
          >
            {/* Animated gradient background */}
            <div className="absolute inset-0 bg-gradient-to-br from-emerald-50/20 via-white/10 to-emerald-100/20"></div>

            {/* Header */}
            <div className="relative z-10 bg-gradient-to-r from-emerald-500 via-emerald-600 to-emerald-700 p-6 text-white">
              {/* Animated gradient overlay */}
              <div className="absolute inset-0 bg-gradient-to-r from-emerald-400/20 via-transparent to-emerald-600/20 animate-pulse"></div>

              <div className="flex items-center justify-between relative z-10">
                <div className="flex items-center gap-3">
                  <motion.div
                    className="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm"
                    whileHover={{ scale: 1.05 }}
                    whileTap={{ scale: 0.95 }}
                  >
                    <span className="text-2xl">🤖</span>
                  </motion.div>
                  <div>
                    <h3 className="font-bold text-lg">CivicConnect Assistant</h3>
                    <p className="text-emerald-100 text-sm">Smart & Helpful</p>
                  </div>
                </div>
                <motion.button
                  whileHover={{ scale: 1.1, rotate: 90 }}
                  whileTap={{ scale: 0.9 }}
                  onClick={closeChat}
                  className="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm hover:bg-white/30 transition-colors"
                  aria-label="Close chat"
                >
                  <XMarkIcon className="h-5 w-5" />
                </motion.button>
              </div>
            </div>

            {/* Messages Area */}
            <div
              ref={messagesContainerRef}
              className="flex-1 p-6 overflow-y-auto space-y-4 relative z-10"
            >
              <AnimatePresence>
                {messages.map((message, index) => (
                  <motion.div
                    key={message.id}
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    exit={{ opacity: 0, y: -20 }}
                    transition={{ duration: 0.3, delay: index * 0.1 }}
                    className={`flex ${message.role === 'user' ? 'justify-end' : 'justify-start'}`}
                  >
                    <div className={`flex gap-3 max-w-[80%] ${message.role === 'user' ? 'flex-row-reverse' : ''}`}>
                      {message.role === 'assistant' && (
                        <motion.div
                          className="w-8 h-8 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-full flex items-center justify-center flex-shrink-0 shadow-lg"
                          whileHover={{ scale: 1.1 }}
                        >
                          <span className="text-sm">🤖</span>
                        </motion.div>
                      )}
                      <div className={`rounded-2xl p-4 shadow-lg ${
                        message.role === 'user'
                          ? 'bg-gradient-to-r from-emerald-500 to-emerald-600 text-white'
                          : 'bg-white/80 backdrop-blur-sm border border-white/20 text-gray-800'
                      }`}>
                        <p className="text-sm leading-relaxed">{message.content}</p>

                        {/* Options */}
                        {message.options && (
                          <motion.div
                            className="mt-4 space-y-2"
                            initial="hidden"
                            animate="visible"
                            variants={{
                              hidden: { opacity: 0 },
                              visible: {
                                opacity: 1,
                                transition: {
                                  staggerChildren: 0.1
                                }
                              }
                            }}
                          >
                            {message.options.map((option, optionIndex) => (
                              <motion.button
                                key={option.label}
                                variants={{
                                  hidden: { opacity: 0, y: 20 },
                                  visible: { opacity: 1, y: 0 }
                                }}
                                whileHover={{
                                  scale: 1.02,
                                  boxShadow: "0 10px 25px rgba(16, 185, 129, 0.2)"
                                }}
                                whileTap={{ scale: 0.98 }}
                                onClick={() => handleButtonClick(option)}
                                disabled={isLoading}
                                className={`w-full p-3 rounded-xl border border-emerald-200/50 bg-gradient-to-r from-white to-emerald-50/50 hover:from-emerald-50 hover:to-emerald-100/50 text-left text-sm font-medium text-emerald-800 transition-all duration-200 shadow-sm hover:shadow-md disabled:opacity-50 disabled:cursor-not-allowed group relative overflow-hidden ${
                                  message.role === 'user' ? 'bg-white/20 text-white border-white/30' : ''
                                }`}
                              >
                                {/* Ripple effect */}
                                <div className="absolute inset-0 bg-emerald-400/20 opacity-0 group-active:opacity-100 transition-opacity duration-150 rounded-xl"></div>

                                <div className="flex items-center gap-3 relative z-10">
                                  <span className="text-lg">{option.icon}</span>
                                  <span className="flex-1">{option.label}</span>
                                </div>
                              </motion.button>
                            ))}
                          </motion.div>
                        )}
                      </div>
                    </div>
                  </motion.div>
                ))}
              </AnimatePresence>

              {/* Typing Indicator */}
              <AnimatePresence>
                {isLoading && (
                  <motion.div
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    exit={{ opacity: 0, y: -20 }}
                    className="flex justify-start"
                  >
                    <div className="flex gap-3 max-w-[80%]">
                      <div className="w-8 h-8 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-full flex items-center justify-center flex-shrink-0 shadow-lg">
                        <span className="text-sm">🤖</span>
                      </div>
                      <div className="bg-white/80 backdrop-blur-sm border border-white/20 rounded-2xl p-4 shadow-lg">
                        <div className="flex gap-1">
                          <motion.div
                            className="w-2 h-2 bg-emerald-500 rounded-full"
                            animate={{ y: [0, -8, 0] }}
                            transition={{ duration: 0.8, repeat: Infinity, delay: 0 }}
                          />
                          <motion.div
                            className="w-2 h-2 bg-emerald-500 rounded-full"
                            animate={{ y: [0, -8, 0] }}
                            transition={{ duration: 0.8, repeat: Infinity, delay: 0.2 }}
                          />
                          <motion.div
                            className="w-2 h-2 bg-emerald-500 rounded-full"
                            animate={{ y: [0, -8, 0] }}
                            transition={{ duration: 0.8, repeat: Infinity, delay: 0.4 }}
                          />
                        </div>
                      </div>
                    </div>
                  </motion.div>
                )}
              </AnimatePresence>

              <div ref={messagesEndRef} />
            </div>
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  );
};

export default ChatBot;