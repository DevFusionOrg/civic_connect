import React, { useState, useRef, useEffect } from 'react';
import './ChatBot.css';

const ChatBot = () => {
  const [messages, setMessages] = useState([]);
  const [currentState, setCurrentState] = useState('start');
  const [isTyping, setIsTyping] = useState(false);
  const messagesEndRef = useRef(null);

  // Decision tree structure - pure button-driven flow
  const decisionTree = {
    "start": {
      "message": "👋 Hi! I'm your CivicConnect assistant. How can I help you today?",
      "options": [
        { "label": "📝 Report an issue", "next": "report_issue" },
        { "label": "📊 Track my reports", "next": "track_reports" },
        { "label": "📈 What do statuses mean?", "next": "status_info" },
        { "label": "🏷️ Issue categories", "next": "categories_info" },
        { "label": "👤 Login/Register", "next": "account_help" }
      ]
    },
    "report_issue": {
      "message": "What type of issue would you like to report?",
      "options": [
        { "label": "🛣️ Roads & Infrastructure", "next": "roads_infrastructure" },
        { "label": "💡 Street Lights", "next": "street_lights" },
        { "label": "🗑️ Trash & Cleanliness", "next": "trash_cleanliness" },
        { "label": "🌊 Water & Drainage", "next": "water_drainage" },
        { "label": "🏞️ Parks & Recreation", "next": "parks_recreation" },
        { "label": "🚔 Public Safety", "next": "public_safety" },
        { "label": "🎨 Graffiti & Vandalism", "next": "graffiti_vandalism" },
        { "label": "🔊 Noise", "next": "noise_issues" },
        { "label": "❓ Other Issues", "next": "other_issues" },
        { "label": "⬅️ Back to Main Menu", "next": "start" }
      ]
    },
    "roads_infrastructure": {
      "message": "Please select the road/infrastructure issue:",
      "options": [
        { "label": "🕳️ Potholes", "response": "For potholes, please take a photo and submit through our issue reporting system. Priority is given to main roads and high-traffic areas." },
        { "label": "🛤️ Road Cracks", "response": "Road cracks are typically scheduled for repair during our quarterly maintenance cycles. You can report them for our records." },
        { "label": "🚧 Street Repairs", "response": "Emergency street repairs are handled within 24 hours. Non-emergency repairs are scheduled based on priority and available resources." },
        { "label": "⬅️ Back to Categories", "next": "report_issue" }
      ]
    },
    "street_lights": {
      "message": "Please select the street light issue:",
      "options": [
        { "label": "💡 Broken Light", "response": "Broken street lights are repaired within 48 hours. Please provide the exact location (intersection or address) for faster service." },
        { "label": "🔅 Dim Light", "response": "Dim street lights are typically replaced during our scheduled maintenance. You can report them for priority replacement." },
        { "label": "⚡ Flickering Light", "response": "Flickering lights often indicate electrical issues. These are treated as priority repairs and addressed within 24 hours." },
        { "label": "⬅️ Back to Categories", "next": "report_issue" }
      ]
    },
    "trash_cleanliness": {
      "message": "Please select the trash/cleanliness issue:",
      "options": [
        { "label": "🗑️ Overflowing Bins", "response": "Overflowing trash bins are collected within 24 hours. For recurring issues, please report with photos." },
        { "label": "🗑️ Illegal Dumping", "response": "Illegal dumping is a serious violation. Please provide photos and exact location. We'll investigate within 48 hours." },
        { "label": "🗑️ Litter Problems", "response": "Litter issues are addressed through our regular cleanup schedule. You can help by reporting specific problem areas." },
        { "label": "⬅️ Back to Categories", "next": "report_issue" }
      ]
    },
    "water_drainage": {
      "message": "Please select the water/drainage issue:",
      "options": [
        { "label": "💧 Leaking Pipe", "response": "For leaking pipes, call our emergency line at 911 immediately or contact the water department at (555) 123-4567." },
        { "label": "🌊 Flooding", "response": "Flooding issues are treated as emergencies. Please call 911 if there's immediate danger, or report through our system." },
        { "label": "🚰 Low Water Pressure", "response": "Low water pressure can be caused by various factors. Please provide your address and we'll investigate within 48 hours." },
        { "label": "⬅️ Back to Categories", "next": "report_issue" }
      ]
    },
    "parks_recreation": {
      "message": "Please select the parks/recreation issue:",
      "options": [
        { "label": "🎪 Broken Equipment", "response": "Park equipment repairs are scheduled based on safety priority. Emergency repairs are handled within 24 hours." },
        { "label": "🌳 Tree Maintenance", "response": "Tree-related issues are handled by our arborist team. Please provide photos and exact location." },
        { "label": "🏖️ Park Cleanliness", "response": "Park maintenance is performed regularly. You can report specific cleanliness issues for priority attention." },
        { "label": "⬅️ Back to Categories", "next": "report_issue" }
      ]
    },
    "public_safety": {
      "message": "Please select the public safety issue:",
      "options": [
        { "label": "🚨 Emergency", "response": "For emergencies, call 911 immediately. Do not use this system for life-threatening situations." },
        { "label": "🚦 Traffic Safety", "response": "Traffic safety concerns are investigated by our traffic engineering team. Please provide detailed information." },
        { "label": "🚶 Pedestrian Issues", "response": "Pedestrian safety issues are treated as high priority. We'll investigate within 48 hours." },
        { "label": "⬅️ Back to Categories", "next": "report_issue" }
      ]
    },
    "graffiti_vandalism": {
      "message": "Please select the graffiti/vandalism issue:",
      "options": [
        { "label": "🎨 New Graffiti", "response": "New graffiti is removed within 48 hours. Please provide photos and exact location." },
        { "label": "🔨 Property Damage", "response": "Property damage is investigated by our code enforcement team. Please provide detailed photos and location." },
        { "label": "🏢 Building Vandalism", "response": "Building vandalism is treated as a serious offense. We'll investigate and coordinate with law enforcement." },
        { "label": "⬅️ Back to Categories", "next": "report_issue" }
      ]
    },
    "noise_issues": {
      "message": "Please select the noise issue:",
      "options": [
        { "label": "🔊 Excessive Noise", "response": "Noise complaints are investigated during business hours. Please provide time, location, and description of the noise." },
        { "label": "🎵 Music Disturbance", "response": "Music-related noise complaints are handled by our community services department." },
        { "label": "🏗️ Construction Noise", "response": "Construction noise is regulated and monitored. Please provide the construction site address." },
        { "label": "⬅️ Back to Categories", "next": "report_issue" }
      ]
    },
    "other_issues": {
      "message": "Please describe your other issue:",
      "options": [
        { "label": "📝 General Concern", "response": "For general concerns, please use our main issue reporting system with a detailed description." },
        { "label": "❓ Need Help Categorizing", "response": "If you're unsure which category fits your issue, please provide more details and we'll help categorize it." },
        { "label": "⬅️ Back to Categories", "next": "report_issue" }
      ]
    },
    "track_reports": {
      "message": "How would you like to track your reports?",
      "options": [
        { "label": "📱 Check Status Updates", "response": "You can check status updates by logging into your account and visiting 'My Issues'. Status changes are: Pending Review → In Progress → Resolved." },
        { "label": "📧 Get Notifications", "response": "You'll receive email notifications when your issue status changes. Make sure your email preferences are set up in your account." },
        { "label": "📊 View Issue Details", "response": "Click any issue in 'My Issues' to see: Status, Comments, Upvotes, Location, Photos, and Resolution notes." },
        { "label": "⬅️ Back to Main Menu", "next": "start" }
      ]
    },
    "status_info": {
      "message": "Select a status to learn more:",
      "options": [
        { "label": "⏳ Pending Review", "response": "Your issue has been submitted and is waiting for review by city officials. This typically takes 24-48 hours." },
        { "label": "🔧 In Progress", "response": "City officials are actively working on fixing your issue. You may see updates on progress and timelines." },
        { "label": "✅ Resolved", "response": "Your issue has been fixed! Check the details for completion notes and any follow-up information." },
        { "label": "⬅️ Back to Main Menu", "next": "start" }
      ]
    },
    "categories_info": {
      "message": "Choose a category to learn more:",
      "options": [
        { "label": "🛣️ Roads & Infrastructure", "response": "Includes: Potholes, cracks, road damage, street repairs, sidewalk issues, and infrastructure problems." },
        { "label": "💡 Street Lights", "response": "Includes: Broken, dim, or malfunctioning street lights, traffic signals, and lighting issues." },
        { "label": "🗑️ Trash & Cleanliness", "response": "Includes: Garbage, litter, overflowing bins, illegal dumping, and cleanliness concerns." },
        { "label": "🌊 Water & Drainage", "response": "Includes: Flooding, leaks, drainage problems, water quality, and water-related infrastructure." },
        { "label": "🏞️ Parks & Recreation", "response": "Includes: Park maintenance, playground equipment, recreational facilities, and green space issues." },
        { "label": "🚔 Public Safety", "response": "Includes: Safety concerns, traffic issues, security problems, and public safety matters." },
        { "label": "🎨 Graffiti & Vandalism", "response": "Includes: Graffiti removal, property damage, vandalism reports, and defacement issues." },
        { "label": "🔊 Noise", "response": "Includes: Excessive noise, disturbances, noise complaints, and sound-related issues." },
        { "label": "❓ Other Issues", "response": "Any other city-related concerns not listed above. We'll help categorize and address them." },
        { "label": "⬅️ Back to Main Menu", "next": "start" }
      ]
    },
    "account_help": {
      "message": "Account assistance:",
      "options": [
        { "label": "🔐 Create Account", "response": "To create an account, click 'Sign Up' and provide your email and password. You'll receive a verification email." },
        { "label": "🚪 Login to Account", "response": "Use the 'Login' button and enter your registered email and password. Use 'Forgot Password' if needed." },
        { "label": "🔑 Reset Password", "response": "Click 'Forgot Password' on the login page and enter your email. Follow the reset link sent to your email." },
        { "label": "📧 Verify Email", "response": "Check your email for a verification link after registering. Click the link to activate your account." },
        { "label": "⬅️ Back to Main Menu", "next": "start" }
      ]
    }
  };
      "message": "Please select the noise issue:",
      "options": [
        { "label": "🔊 Excessive Noise", "response": "Noise complaints are investigated during business hours. Please provide time, location, and description of the noise." },
        { "label": "🎵 Music Disturbance", "response": "Music-related noise complaints are handled by our community services department." },
        { "label": "🏗️ Construction Noise", "response": "Construction noise is regulated and monitored. Please provide the construction site address." },
        { "label": "⬅️ Back to Categories", "next": "report_issue" }
      ]
    },
    "other_issues": {
      "message": "Please describe your other issue:",
      "options": [
        { "label": "📝 General Concern", "response": "For general concerns, please use our main issue reporting system with a detailed description." },
        { "label": "❓ Need Help Categorizing", "response": "If you're unsure which category fits your issue, please provide more details and we'll help categorize it." },
        { "label": "⬅️ Back to Categories", "next": "report_issue" }
      ]
    },
    "track_reports": {
      "message": "How would you like to track your reports?",
      "options": [
        { "label": "📱 Check Status Updates", "response": "You can check status updates by logging into your account and visiting 'My Issues'. Status changes are: Pending Review → In Progress → Resolved." },
        { "label": "📧 Get Notifications", "response": "You'll receive email notifications when your issue status changes. Make sure your email preferences are set up in your account." },
        { "label": "📊 View Issue Details", "response": "Click any issue in 'My Issues' to see: Status, Comments, Upvotes, Location, Photos, and Resolution notes." },
        { "label": "⬅️ Back to Main Menu", "next": "start" }
      ]
    },
    "status_info": {
      "message": "Select a status to learn more:",
      "options": [
        { "label": "⏳ Pending Review", "response": "Your issue has been submitted and is waiting for review by city officials. This typically takes 24-48 hours." },
        { "label": "🔧 In Progress", "response": "City officials are actively working on fixing your issue. You may see updates on progress and timelines." },
        { "label": "✅ Resolved", "response": "Your issue has been fixed! Check the details for completion notes and any follow-up information." },
        { "label": "⬅️ Back to Main Menu", "next": "start" }
      ]
    },
    "categories_info": {
      "message": "Choose a category to learn more:",
      "options": [
        { "label": "🛣️ Roads & Infrastructure", "response": "Includes: Potholes, cracks, road damage, street repairs, sidewalk issues, and infrastructure problems." },
        { "label": "💡 Street Lights", "response": "Includes: Broken, dim, or malfunctioning street lights, traffic signals, and lighting issues." },
        { "label": "🗑️ Trash & Cleanliness", "response": "Includes: Garbage, litter, overflowing bins, illegal dumping, and cleanliness concerns." },
        { "label": "🌊 Water & Drainage", "response": "Includes: Flooding, leaks, drainage problems, water quality, and water-related infrastructure." },
        { "label": "🏞️ Parks & Recreation", "response": "Includes: Park maintenance, playground equipment, recreational facilities, and green space issues." },
        { "label": "🚔 Public Safety", "response": "Includes: Safety concerns, traffic issues, security problems, and public safety matters." },
        { "label": "🎨 Graffiti & Vandalism", "response": "Includes: Graffiti removal, property damage, vandalism reports, and defacement issues." },
        { "label": "🔊 Noise", "response": "Includes: Excessive noise, disturbances, noise complaints, and sound-related issues." },
        { "label": "❓ Other Issues", "response": "Any other city-related concerns not listed above. We'll help categorize and address them." },
        { "label": "⬅️ Back to Main Menu", "next": "start" }
      ]
    },
    "account_help": {
      "message": "Account assistance:",
      "options": [
        { "label": "🔐 Create Account", "response": "To create an account, click 'Sign Up' and provide your email and password. You'll receive a verification email." },
        { "label": "🚪 Login to Account", "response": "Use the 'Login' button and enter your registered email and password. Use 'Forgot Password' if needed." },
        { "label": "🔑 Reset Password", "response": "Click 'Forgot Password' on the login page and enter your email. Follow the reset link sent to your email." },
        { "label": "📧 Verify Email", "response": "Check your email for a verification link after registering. Click the link to activate your account." },
        { "label": "⬅️ Back to Main Menu", "next": "start" }
      ]
    }
  };

  // Initialize with welcome message and initial buttons
  useEffect(() => {
    if (messages.length === 0) {
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
  }, []);

  // Auto-scroll to bottom when new messages are added
  useEffect(() => {
    scrollToBottom();
  }, [messages]);

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  };

  const handleButtonClick = (option) => {
    // Add user message (the button they clicked)
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
      setIsTyping(true);
      setTimeout(() => {
        const botResponse = {
          id: Date.now() + 1,
          role: 'assistant',
          content: option.response,
          options: [{ "label": "⬅️ Back to Main Menu", "next": "start" }],
          timestamp: new Date().toISOString(),
        };
        setMessages(prev => [...prev, botResponse]);
        setCurrentState('final');
        setIsTyping(false);
      }, 1000);
    } else if (option.next) {
      // Navigate to next state
      const nextState = decisionTree[option.next];
      if (nextState) {
        setIsTyping(true);
        setTimeout(() => {
          const botMessage = {
            id: Date.now() + 1,
            role: 'assistant',
            content: nextState.message,
            options: nextState.options,
            timestamp: new Date().toISOString(),
          };
          setMessages(prev => [...prev, botMessage]);
          setCurrentState(option.next);
          setIsTyping(false);
        }, 1000);
      }
    }

    scrollToBottom();
  };

  return (
    <div className="chatbot-container">
      <div className="chat-window">
        {/* Header */}
        <div className="chat-header">
          <div className="header-content">
            <div className="chat-avatar">
              <span className="avatar-icon">🤖</span>
            </div>
            <div className="header-text">
              <h3>CivicConnect Assistant</h3>
              <p>Smart & Helpful</p>
            </div>
          </div>
          <button className="close-button" onClick={() => console.log('Close chat')}>
            ✕
          </button>
        </div>

        {/* Messages Area */}
        <div className="chat-messages">
          {messages.map((message) => (
            <div
              key={message.id}
              className={`message ${message.role === 'user' ? 'message-user' : 'message-assistant'}`}
            >
              <div className="message-content">
                {message.role === 'assistant' && (
                  <div className="message-icon">
                    <span className="bot-icon">🤖</span>
                  </div>
                )}
                <div className="message-bubble">
                  <p>{message.content}</p>
                  {message.options && (
                    <div className="message-buttons">
                      {message.options.map((option, index) => (
                        <button
                          key={index}
                          className="option-button"
                          onClick={() => handleButtonClick(option)}
                          disabled={isTyping}
                        >
                          {option.label}
                        </button>
                      ))}
                    </div>
                  )}
                </div>
              </div>
            </div>
          ))}

          {/* Typing Indicator */}
          {isTyping && (
            <div className="message message-assistant">
              <div className="message-content">
                <div className="message-icon">
                  <span className="bot-icon">🤖</span>
                </div>
                <div className="typing-indicator">
                  <span></span>
                  <span></span>
                  <span></span>
                </div>
              </div>
            </div>
          )}

          <div ref={messagesEndRef} />
        </div>
      </div>
    </div>
  );
};

export default ChatBot;