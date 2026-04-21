<?php
/**
 * Chatbot API Endpoint
 * Handles chatbot interactions with rule-based responses (no external API required)
 */

header('Content-Type: application/json');
require_once __DIR__ . '/Middleware.php';
Middleware::setCORSHeaders();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

try {
    // Get request body
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['message']) || empty(trim($input['message']))) {
        http_response_code(400);
        echo json_encode(['error' => 'Message is required']);
        exit();
    }

    $userMessage = trim($input['message']);
    $conversationHistory = $input['history'] ?? [];

    // Validate conversation history format
    if (!is_array($conversationHistory)) {
        $conversationHistory = [];
    }

    // Get response from rule-based chatbot
    $response = generateChatbotResponse($userMessage);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $response,
        'timestamp' => date('c')
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred: ' . $e->getMessage()
    ]);
}

/**
 * Generate response based on keyword matching
 */
function generateChatbotResponse($message) {
    $message = strtolower($message);

    // Track my reports / View my issues
    if (strpos($message, 'track') !== false ||
        strpos($message, 'my report') !== false ||
        strpos($message, 'my issue') !== false ||
        strpos($message, 'view') !== false && strpos($message, 'issue') !== false) {
        return "To track your reports:
1. Go to 'My Issues' in your dashboard
2. View all issues you've reported with their status
3. Click any issue for details (comments, upvotes)
4. Get notified when status changes: Pending → In Progress → Resolved

You can track everything from the My Issues page!";
    }

    // Report an issue
    if (strpos($message, 'report') !== false ||
        strpos($message, 'create') !== false ||
        strpos($message, 'submit') !== false && strpos($message, 'issue') !== false) {
        return "To report an issue:
1. Click 'Report Issue' button
2. Fill in: Title, Description, Category
3. Optional: Add photo and location
4. Click Submit

Your issue will be reviewed and tracked!";
    }

    // Issue status / What do statuses mean
    if (strpos($message, 'status') !== false) {
        return "Issue statuses explained:
• Pending Review: Submitted and awaiting review
• In Progress: City officials are working on it
• Resolved: Issue has been fixed

You'll get notified when status changes!";
    }

    // Upvoting
    if (strpos($message, 'upvote') !== false ||
        strpos($message, 'vote') !== false ||
        strpos($message, 'support') !== false && strpos($message, 'issue') !== false) {
        return "About upvoting:
• Other users can upvote issues they support
• Each user can upvote once per issue
• Higher upvotes = higher priority
• Helps push important issues to the top!";
    }

    // How to register / Login
    if (strpos($message, 'register') !== false ||
        strpos($message, 'signup') !== false ||
        strpos($message, 'account') !== false ||
        strpos($message, 'login') !== false) {
        return "Account help:
• New user? Click 'Sign Up' and enter email + password
• Verify your email to activate account
• Then login and start reporting issues
• Recover password? Use 'Forgot Password' link";
    }

    // Issue categories
    if (strpos($message, 'categor') !== false) {
        return "Issue categories include:
• Roads (potholes, cracks)
• Street Lights (broken, dim)
• Trash (garbage, litter)
• Water Drainage (flooding, leaks)
• Parks & Recreation
• Public Safety
• Graffiti & Vandalism
• Noise
• Other issues

Choose the best match for your report!";
    }

    // General help
    if (strpos($message, 'help') !== false ||
        strpos($message, 'what can you do') !== false ||
        strpos($message, 'guide') !== false) {
        return "I can help you with:
• Reporting city issues
• Tracking your reports
• Understanding issue statuses
• Upvoting issues
• Account & login help
• Finding issue categories
• Navigation help

What would you like to know?";
    }

    // Default response
    return "I'm here to help you with CivicConnect! I can assist with reporting issues, tracking your reports, understanding statuses, account questions, and navigation help. What would you like to know?";
}