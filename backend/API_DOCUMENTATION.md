# Civic Connect API Documentation

## Overview
Complete REST API for the Civic Connect platform. Base URL: `http://localhost/civic-connect/backend/api/`

## Authentication
Most endpoints require authentication via bearer token. Include the token in the request header:
```
Authorization: Bearer {token}
```

---

## User Endpoints

### 1. Register User
**POST** `/users/register`

Register a new user account.

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "password123",
  "first_name": "John",
  "last_name": "Doe",
  "phone": "+1234567890"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "User registered successfully. Please verify your email...",
  "user_id": 1,
  "email": "user@example.com"
}
```

---

### 2. Verify Email
**POST** `/users/verify-email`

Verify email address using OTP code sent via email.

**Request Body:**
```json
{
  "email": "user@example.com",
  "otp_code": "123456"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Email verified successfully",
  "user_id": 1
}
```

---

### 3. Resend OTP
**POST** `/users/resend-otp`

Resend OTP code to email address.

**Request Body:**
```json
{
  "email": "user@example.com"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "OTP code sent to your email address"
}
```

---

### 4. Login User
**POST** `/users/login`

Login user and get authentication token.

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Login successful",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "first_name": "John",
    "last_name": "Doe"
  },
  "token": "abc123def456..."
}
```

---

### 5. Logout User
**POST** `/users/logout`

Logout current user (requires authentication).

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

---

### 6. Get User Profile
**GET** `/users/{id}`

Get user profile information.

**Response (200):**
```json
{
  "success": true,
  "user": {
    "id": 1,
    "email": "user@example.com",
    "first_name": "John",
    "last_name": "Doe",
    "phone": "+1234567890",
    "location": "New York, NY",
    "created_at": "2024-01-15 10:30:00"
  }
}
```

---

### 7. Update User Profile
**PUT** `/users/{id}`

Update user profile (requires authentication and ownership).

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "first_name": "Jane",
  "phone": "+9876543210",
  "location": "Boston, MA"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Profile updated successfully"
}
```

---

## Issue Endpoints

### 1. Create Issue
**POST** `/issues`

Create a new civic issue (requires authentication).

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "title": "Pothole on Main Street",
  "description": "Large pothole affecting traffic on Main Street",
  "category": "infrastructure",
  "location": "Main Street, Downtown",
  "latitude": 40.7128,
  "longitude": -74.0060,
  "priority": "high",

}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Issue created successfully",
  "issue_id": 1
}
```

---

### 2. Get All Issues
**GET** `/issues`

List all issues with optional filters and pagination.

**Query Parameters:**
- `page` (default: 1)
- `limit` (default: 10, max: 100)
- `category` - Filter by category
- `status` - Filter by status (pending_review, in_progress, resolved)
- `priority` - Filter by priority (low, medium, high)
- `search` - Search in title and description
- `sort_by` - Sort field (created_at, upvote_count, title, priority)
- `sort_order` - Sort order (ASC, DESC)

**Example:** `GET /issues?category=infrastructure&status=pending_review&sort_by=upvote_count&sort_order=DESC&page=1&limit=20`

**Response (200):**
```json
{
  "success": true,
  "issues": [
    {
      "id": 1,
      "user_id": 1,
      "title": "Pothole on Main Street",
      "description": "Large pothole affecting traffic",
      "category": "infrastructure",
      "location": "Main Street, Downtown",
      "latitude": 40.7128,
      "longitude": -74.0060,
      "status": "pending_review",
      "priority": "high",
      "image_path": "uploads/issues/issue_1.jpg",
      "upvote_count": 25,
      "created_at": "2024-01-15 10:30:00",
      "first_name": "John",
      "last_name": "Doe"
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 10,
    "total": 45,
    "pages": 5
  }
}
```

---

### 3. Get Single Issue
**GET** `/issues/{id}`

Get detailed information about a specific issue.

**Response (200):**
```json
{
  "success": true,
  "issue": {
    "id": 1,
    "user_id": 1,
    "title": "Pothole on Main Street",
    "description": "Large pothole affecting traffic",
    "category": "infrastructure",
    "location": "Main Street, Downtown",
    "latitude": 40.7128,
    "longitude": -74.0060,
    "status": "pending_review",
    "priority": "high",
    "image_path": "uploads/issues/issue_1.jpg",
    "upvote_count": 25,

    "created_at": "2024-01-15 10:30:00",
    "updated_at": "2024-01-15 10:30:00",
    "first_name": "John",
    "last_name": "Doe"
  }
}
```

---

### 4. Update Issue
**PUT** `/issues/{id}`

Update an issue (requires authentication and ownership).

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "title": "Pothole on Main Street - URGENT",
  "status": "in_progress",
  "priority": "high"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Issue updated successfully"
}
```

---

### 5. Delete Issue
**DELETE** `/issues/{id}`

Delete an issue (requires authentication and ownership).

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Issue deleted successfully"
}
```

---

### 6. Get User's Issues
**GET** `/users/{id}/issues`

Get all issues created by a specific user.

**Query Parameters:**
- `page` (default: 1)
- `limit` (default: 10, max: 50)

**Response (200):**
```json
{
  "success": true,
  "issues": [...],
  "pagination": {
    "page": 1,
    "limit": 10,
    "total": 8,
    "pages": 1
  }
}
```

---

## File Upload Endpoints

### 1. Upload Issue Image
**POST** `/upload/issue`

Upload an image for an issue (requires authentication).

**Headers:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Form Data:**
```
image: [binary image file]
```

**Supported Formats:** JPEG, PNG, GIF, WebP (max 5MB)

**Response (201):**
```json
{
  "success": true,
  "message": "File uploaded successfully",
  "file": {
    "path": "uploads/issues/issue_123.jpg",
    "filename": "issue_123.jpg"
  }
}
```

---

### 2. Upload Profile Image (DEPRECATED)
**POST** `/upload/profile`

> **⚠️ DEPRECATED**: Profile image uploads are no longer supported. This endpoint has been disabled.

Upload profile image (requires authentication).

**Headers:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Form Data:**
```
image: [binary image file]
```

**Response (201):**
```json
{
  "success": true,
  "message": "Profile image uploaded successfully",
  "file": {
    "path": "uploads/profiles/user_123.jpg",
    "filename": "user_123.jpg"
  }
}
```

---

### 3. Update Issue Image
**PUT** `/issues/{id}/image`

Replace the image for an issue (requires authentication and ownership).

**Headers:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Form Data:**
```
image: [binary image file]
```

**Response (200):**
```json
{
  "success": true,
  "message": "Issue image updated successfully",
  "file": {
    "path": "uploads/issues/issue_456.jpg",
    "filename": "issue_456.jpg"
  }
}
```

---

### 4. Delete File
**DELETE** `/files`

Delete an uploaded file (requires authentication and ownership).

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "filepath": "uploads/issues/issue_123.jpg",
  "issue_id": 1
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "File deleted successfully"
}
```

---

### 5. Get File Info
**GET** `/files/{filename}`

Get information about a file.

**Response (200):**
```json
{
  "success": true,
  "file": {
    "name": "issue_123.jpg",
    "size": 245632,
    "type": "image/jpeg",
    "created": 1705324200
  }
}
```

---

## Upvote Endpoints

### 1. Upvote Issue
**POST** `/issues/{id}/upvotes`

Upvote an issue (requires authentication).

**Headers:**
```
Authorization: Bearer {token}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Issue upvoted successfully"
}
```

---

### 2. Remove Upvote
**DELETE** `/issues/{id}/upvotes`

Remove upvote from an issue (requires authentication).

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Upvote removed successfully"
}
```

---

### 3. Get Issue Upvotes
**GET** `/issues/{id}/upvotes`

Get list of users who upvoted an issue.

**Query Parameters:**
- `page` (default: 1)
- `limit` (default: 10, max: 50)

**Response (200):**
```json
{
  "success": true,
  "upvotes": [
    {
      "upvote_id": 1,
      "user_id": 5,
      "first_name": "Jane",
      "last_name": "Smith",
      "created_at": "2024-01-15 10:35:00"
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 10,
    "total": 25,
    "pages": 3
  }
}
```

---

## Comment Endpoints

### 1. Add Comment
**POST** `/issues/{id}/comments`

Add a comment to an issue (requires authentication).

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "content": "This is a serious issue that needs immediate attention!",
  "is_anonymous": false
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Comment added successfully",
  "comment_id": 1
}
```

---

### 2. Get Issue Comments
**GET** `/issues/{id}/comments`

Get all comments for an issue.

**Query Parameters:**
- `page` (default: 1)
- `limit` (default: 10, max: 50)

**Response (200):**
```json
{
  "success": true,
  "comments": [
    {
      "id": 1,
      "issue_id": 1,
      "user_id": 2,
      "content": "This is a serious issue that needs immediate attention!",
      "is_anonymous": false,
      "created_at": "2024-01-15 10:35:00",
      "updated_at": "2024-01-15 10:35:00",
      "first_name": "Jane",
      "last_name": "Smith"
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 10,
    "total": 5,
    "pages": 1
  }
}
```

---

### 3. Update Comment
**PUT** `/comments/{id}`

Update a comment (requires authentication and ownership).

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "content": "Updated comment content"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Comment updated successfully"
}
```

---

### 4. Delete Comment
**DELETE** `/comments/{id}`

Delete a comment (requires authentication and ownership).

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Comment deleted successfully"
}
```

---

## Error Responses

### Bad Request (400)
```json
{
  "error": "Missing required fields: field_name"
}
```

### Unauthorized (401)
```json
{
  "error": "Unauthorized: Authentication token missing or invalid"
}
```

### Forbidden (403)
```json
{
  "error": "Unauthorized: Cannot update other user's issues"
}
```

### Not Found (404)
```json
{
  "error": "Issue not found"
}
```

### Conflict (409)
```json
{
  "error": "Already upvoted this issue"
}
```

### Rate Limited (429)
```json
{
  "error": "Too many login attempts. Please try again later."
}
```

### Server Error (500)
```json
{
  "error": "Database error: [error details]"
}
```

---

## Testing with cURL

### Register User
```bash
curl -X POST http://localhost/civic-connect/backend/api/users/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123",
    "first_name": "John",
    "last_name": "Doe"
  }'
```

### Login
```bash
curl -X POST http://localhost/civic-connect/backend/api/users/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123"
  }'
```

### Create Issue (with token)
```bash
curl -X POST http://localhost/civic-connect/backend/api/issues \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -d '{
    "title": "Issue Title",
    "description": "Issue description",
    "category": "infrastructure",
    "priority": "high"
  }'
```

### Get Issues
```bash
curl -X GET "http://localhost/civic-connect/backend/api/issues?category=infrastructure&status=pending_review&sort_by=upvote_count&sort_order=DESC"
```

### Upload Image
```bash
curl -X POST http://localhost/civic-connect/backend/api/upload/issue \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -F "image=@/path/to/image.jpg"
```

---

## Notes
- All timestamps are in UTC format: `YYYY-MM-DD HH:MM:SS`
- Pagination starts at page 1
- Anonymous issues/comments hide user information
- File uploads are limited to 5MB
- OTP codes expire after 10 minutes
- Password must be at least 8 characters
- Usernames and titles must meet minimum length requirements
