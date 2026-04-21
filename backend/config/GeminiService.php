<?php
/**
 * Google Gemini AI Service
 * Handles communication with Google Gemini 1.5 Flash API
 */

class GeminiService {
    private $apiKey;
    private $model;
    private $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';
    
    public function __construct() {
        $this->apiKey = $_ENV['GEMINI_API_KEY'] ?? '';
        $this->model = $_ENV['GEMINI_MODEL'] ?? 'gemini-2.5-flash';
        
        if (empty($this->apiKey)) {
            throw new Exception('Gemini API key not configured');
        }
    }
    
    /**
     * Generate a response from Gemini AI
     * 
     * @param string $userMessage The user's message
     * @param array $conversationHistory Previous messages in the conversation
     * @return array Response containing the AI's message
     */
    public function generateResponse($userMessage, $conversationHistory = []) {
        try {
            // Build the conversation context
            $contents = $this->buildContents($userMessage, $conversationHistory);
            
            // Prepare the request
            $url = $this->apiUrl . $this->model . ':generateContent?key=' . $this->apiKey;
            
            $requestBody = [
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => 0.7,
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => 512,
                ],
                'safetySettings' => [
                    [
                        'category' => 'HARM_CATEGORY_HARASSMENT',
                        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                    ],
                    [
                        'category' => 'HARM_CATEGORY_HATE_SPEECH',
                        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                    ],
                    [
                        'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                    ],
                    [
                        'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                    ]
                ]
            ];
            
            // Make the API request
            $response = $this->makeRequest($url, $requestBody);
            
            // Extract the response text
            if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
                return [
                    'success' => true,
                    'message' => $response['candidates'][0]['content']['parts'][0]['text']
                ];
            } else {
                throw new Exception('Invalid response format from Gemini API');
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Build the contents array for the API request
     */
    private function buildContents($userMessage, $conversationHistory) {
        $contents = [];
        
        // Add system instruction as the first user message
        $systemPrompt = $this->getSystemPrompt();
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $systemPrompt]]
        ];
        $contents[] = [
            'role' => 'model',
            'parts' => [['text' => 'I understand. I\'m here to help citizens use CivicConnect to report and track city issues. How can I assist you today?']]
        ];
        
        // Add conversation history
        foreach ($conversationHistory as $message) {
            $contents[] = [
                'role' => $message['role'] === 'user' ? 'user' : 'model',
                'parts' => [['text' => $message['content']]]
            ];
        }
        
        // Add current user message
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $userMessage]]
        ];
        
        return $contents;
    }
    
    /**
     * Get the system prompt that defines the chatbot's behavior
     */
    private function getSystemPrompt() {
        return "You are a helpful AI assistant for CivicConnect, a smart city issue reporting platform. Help citizens report and track city issues like potholes, broken streetlights, trash, and other public concerns.

**About CivicConnect:**
- Citizens report issues with descriptions, photos, and map locations
- Issues can be upvoted to increase priority
- Users track their issues and receive status updates
- Issue statuses: Pending → In Progress → Resolved

**Your Capabilities:**
- Guide users on reporting issues
- Explain issue categories (pothole, streetlight, trash, graffiti, etc.)
- Help with account questions (login, registration, verification)
- Explain upvoting and status updates
- Provide navigation help

**Your Limitations:**
- You CANNOT directly submit issues (direct users to the Report Issue page)
- You CANNOT access user data or change issue statuses
- You provide information and guidance only

**Response Style - CRITICAL:**
- Keep responses SHORT and CONCISE (2-4 sentences max)
- Use bullet points for steps (max 4-5 points)
- Be direct and to the point
- Avoid lengthy explanations
- Only provide essential information
- DO NOT use markdown formatting (no **, *, _, etc.)
- Use symbols and bullet points for formatting

**Common Questions:**
- How do I report an issue?
- What information do I need?
- How do I track my reports?
- What do statuses mean?
- How does upvoting work?

Remember: Be helpful but BRIEF. Citizens want quick answers.";
    }
    
    /**
     * Make HTTP request to Gemini API
     */
    private function makeRequest($url, $data) {
        $ch = curl_init($url);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception('cURL error: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMessage = $errorData['error']['message'] ?? 'Unknown API error';
            throw new Exception('Gemini API error: ' . $errorMessage);
        }
        
        return json_decode($response, true);
    }
}
