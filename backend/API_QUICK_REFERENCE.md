# API Quick Reference

## Base URL
```
http://localhost/civic-connect/backend/api/
```

## Core Endpoints

### 👤 Users
| Method | Endpoint | Auth | Purpose |
|--------|----------|------|---------|
| POST | `/users/register` | ❌ | Register new user |
| POST | `/users/verify-email` | ❌ | Verify email with OTP |
| POST | `/users/resend-otp` | ❌ | Resend OTP code |
| POST | `/users/login` | ❌ | Login user |
| POST | `/users/logout` | ✅ | Logout user |
| GET | `/users/{id}` | ❌ | Get user profile |
| PUT | `/users/{id}` | ✅ | Update user profile |

### 📝 Issues
| Method | Endpoint | Auth | Purpose |
|--------|----------|------|---------|
| POST | `/issues` | ✅ | Create issue |
| GET | `/issues` | ❌ | List all issues |
| GET | `/issues/{id}` | ❌ | Get single issue |
| PUT | `/issues/{id}` | ✅ | Update issue |
| DELETE | `/issues/{id}` | ✅ | Delete issue |
| GET | `/users/{id}/issues` | ❌ | Get user's issues |

### 📸 File Upload
| Method | Endpoint | Auth | Purpose |
|--------|----------|------|---------|
| POST | `/upload/issue` | ✅ | Upload issue image |
| ~~POST~~ | ~~`/upload/profile`~~ | ~~✅~~ | ~~Upload profile image~~ (DEPRECATED) |
| PUT | `/issues/{id}/image` | ✅ | Update issue image |
| DELETE | `/files` | ✅ | Delete file |
| GET | `/files/{filename}` | ❌ | Get file info |

### 👍 Upvotes
| Method | Endpoint | Auth | Purpose |
|--------|----------|------|---------|
| POST | `/issues/{id}/upvotes` | ✅ | Upvote issue |
| DELETE | `/issues/{id}/upvotes` | ✅ | Remove upvote |
| GET | `/issues/{id}/upvotes` | ❌ | Get upvotes |

### 💬 Comments
| Method | Endpoint | Auth | Purpose |
|--------|----------|------|---------|
| POST | `/issues/{id}/comments` | ✅ | Add comment |
| GET | `/issues/{id}/comments` | ❌ | Get comments |
| PUT | `/comments/{id}` | ✅ | Update comment |
| DELETE | `/comments/{id}` | ✅ | Delete comment |

---

## Query Parameters

### Filtering & Pagination
```
?page=1                  # Page number (default: 1)
?limit=10               # Results per page (default: 10, max: 100)
```

### Issue Filters
```
?category=infrastructure        # Filter by category
?status=pending_review          # Status: pending_review|in_progress|resolved
?priority=high                  # Priority: low|medium|high
?search=pothole                 # Search in title & description
?sort_by=created_at            # Sort field: created_at|upvote_count|title|priority
?sort_order=DESC               # Sort order: ASC|DESC
```

### Example
```
GET /issues?category=infrastructure&status=pending_review&sort_by=upvote_count&sort_order=DESC&page=1&limit=20
```

---

## Authentication Header

For protected endpoints, include:
```
Authorization: Bearer YOUR_TOKEN_HERE
```

---

## Status Codes

| Code | Meaning | Example |
|------|---------|---------|
| 200 | Success | GET, PUT, DELETE successful |
| 201 | Created | POST successful |
| 400 | Bad Request | Missing/invalid fields |
| 401 | Unauthorized | Invalid/missing token |
| 403 | Forbidden | No permission |
| 404 | Not Found | Resource doesn't exist |
| 405 | Method Not Allowed | Wrong HTTP method |
| 409 | Conflict | Duplicate entry |
| 429 | Rate Limited | Too many requests |
| 500 | Server Error | Database/internal error |

---

## Common Request Bodies

### Register User
```json
{
  "email": "user@example.com",
  "password": "password123",
  "first_name": "John",
  "last_name": "Doe",
  "phone": "+1234567890"
}
```

### Login
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

### Verify Email
```json
{
  "email": "user@example.com",
  "otp_code": "123456"
}
```

### Create Issue
```json
{
  "title": "Street light broken",
  "description": "The street light on Main St is not working",
  "category": "infrastructure",
  "location": "Main Street",
  "latitude": 40.7128,
  "longitude": -74.0060,
  "priority": "high",

}
```

### Update Issue
```json
{
  "status": "in_progress",
  "priority": "high"
}
```

### Add Comment
```json
{
  "content": "This needs urgent action!"
}
```

### Update User Profile
```json
{
  "first_name": "Jane",
  "location": "New York, NY",
  "phone": "+9876543210"
}
```

---

## File Upload

### Issue Image (multipart/form-data)
```
Content-Type: multipart/form-data
Authorization: Bearer {token}

Form Field: image
File Types: JPEG, PNG, GIF, WebP
Max Size: 5MB
```

### cURL Example
```bash
curl -X POST http://localhost/civic-connect/backend/api/upload/issue \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "image=@/path/to/image.jpg"
```

---

## Response Format

### Success Response
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { /* ... */ }
}
```

### Error Response
```json
{
  "error": "Error message describing what went wrong"
}
```

### Paginated Response
```json
{
  "success": true,
  "issues": [ /* ... */ ],
  "pagination": {
    "page": 1,
    "limit": 10,
    "total": 45,
    "pages": 5
  }
}
```

---

## Quick Test Script

```bash
#!/bin/bash

BASE_URL="http://localhost/civic-connect/backend/api"

# 1. Register
echo "Registering user..."
REGISTER=$(curl -s -X POST "$BASE_URL/users/register" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@gmail.com",
    "password": "Test123456",
    "first_name": "Test",
    "last_name": "User"
  }')
echo $REGISTER

# 2. Login
echo -e "\n\nLogging in..."
LOGIN=$(curl -s -X POST "$BASE_URL/users/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@gmail.com",
    "password": "Test123456"
  }')
echo $LOGIN

# Extract token (requires jq)
TOKEN=$(echo $LOGIN | jq -r '.token')
echo "Token: $TOKEN"

# 3. Create Issue
echo -e "\n\nCreating issue..."
curl -s -X POST "$BASE_URL/issues" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "title": "Test Issue",
    "description": "This is a test issue",
    "category": "infrastructure",
    "priority": "high"
  }' | jq .

# 4. Get Issues
echo -e "\n\nGetting issues..."
curl -s -X GET "$BASE_URL/issues?limit=5" | jq .
```

---

## Common Errors & Solutions

### Invalid email format
```
Error: "Invalid email format"
Fix: Use valid email like user@example.com
```

### Password too short
```
Error: "Password must be at least 8 characters long"
Fix: Use password with 8+ characters
```

### Email already registered
```
Error: "Email already registered"
Fix: Use different email or login
```

### Unauthorized token
```
Error: "Unauthorized: Authentication token missing or invalid"
Fix: Include valid Bearer token in Authorization header
```

### Issue not found
```
Error: "Issue not found"
Fix: Verify issue ID exists
```

### Cannot modify other user's data
```
Error: "Unauthorized: Cannot update other user's issues"
Fix: Only owner can modify (check user_id)
```

### Rate limit exceeded
```
Error: "Too many login attempts. Please try again later."
Fix: Wait 5 minutes before retrying
```

---

## Performance Tips

- Use pagination for large result sets
- Filter results instead of fetching all
- Cache responses in frontend
- Use compression for images
- Limit search queries
- Batch operations when possible

---

## Security Reminders

✅ Always use HTTPS in production
✅ Keep tokens secure
✅ Validate inputs on frontend
✅ Never expose sensitive data in logs
✅ Use strong passwords (min 8 chars)
✅ Rotate tokens periodically
✅ Use rate limiting
✅ Monitor audit trails

---

## Tools for Testing

- **cURL** - Command line tool
- **Postman** - GUI API client
- **Thunder Client** - VS Code extension
- **Insomnia** - Alternative API client
- **Paw** - macOS API testing

---

## Documentation Files

| File | Purpose |
|------|---------|
| `API_DOCUMENTATION.md` | Complete endpoint specifications |
| `SETUP_AND_TESTING.md` | Setup guide and testing examples |
| `STEP_4_COMPLETION.md` | Implementation summary |
| `API_QUICK_REFERENCE.md` | This file |

---

**Version**: 1.0
**Last Updated**: January 2025
**Status**: ✅ Complete
