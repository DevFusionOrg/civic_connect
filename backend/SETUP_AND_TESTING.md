# API Setup & Testing Guide

## Quick Start

### 1. Enable Apache Modules (if not already enabled)

```bash
# Enable mod_rewrite for URL routing
sudo a2enmod rewrite

# Enable mod_headers for CORS and security headers
sudo a2enmod headers

# Enable mod_deflate for compression
sudo a2enmod deflate

# Restart Apache
sudo service apache2 restart
```

### 2. Create Upload Directories

```bash
cd /var/www/html/civic-connect/backend/uploads

# Create subdirectories
mkdir -p issues
mkdir -p profiles  # Note: profile uploads are deprecated

# Set proper permissions
chmod 755 issues
chmod 755 profiles
chmod 644 issues/*
chmod 644 profiles/*
```

### 3. Verify Database Setup

Make sure your database is running and `civic_connect` database exists with all tables imported:

```bash
mysql -u root -p civic_connect < /var/www/html/civic-connect/database.sql
```

### 4. Check Configuration

Verify `.env` file exists in `/var/www/html/civic-connect/backend/` with correct settings:

```
APP_ENV=local
APP_URL=http://localhost/civic-connect

DB_HOST=localhost
DB_NAME=civic_connect
DB_USER=root
DB_PASS=

SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-app-password
SMTP_FROM=your-email@gmail.com
SMTP_FROM_NAME=civic-connect
```

---

## Testing the API

### Using Postman
1. Import the collection from [postman_collection.json](./postman_collection.json)
2. Set environment variables for `BASE_URL` and `TOKEN`
3. Run requests

### Using cURL

#### 1. Register a New User
```bash
curl -X POST http://localhost/civic-connect/backend/api/users/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "testuser@example.com",
    "password": "TestPassword123",
    "first_name": "Test",
    "last_name": "User",
    "phone": "+1234567890"
  }'
```

#### 2. Verify Email
Check your email for OTP code, then:
```bash
curl -X POST http://localhost/civic-connect/backend/api/users/verify-email \
  -H "Content-Type: application/json" \
  -d '{
    "email": "testuser@example.com",
    "otp_code": "123456"
  }'
```

#### 3. Login
```bash
curl -X POST http://localhost/civic-connect/backend/api/users/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "testuser@example.com",
    "password": "TestPassword123"
  }'
```

Response will include a `token`. Save this for authenticated requests.

#### 4. Create an Issue
```bash
curl -X POST http://localhost/civic-connect/backend/api/issues \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -d '{
    "title": "Street Light Not Working",
    "description": "The street light on Main Street intersection has been broken for a week and poses a safety hazard.",
    "category": "infrastructure",
    "location": "Main Street & 5th Avenue",
    "latitude": 40.7128,
    "longitude": -74.0060,
    "priority": "high",
    "is_anonymous": false
  }'
```

#### 5. Get All Issues
```bash
curl -X GET "http://localhost/civic-connect/backend/api/issues?category=infrastructure&status=pending_review&sort_by=upvote_count&sort_order=DESC&page=1&limit=10"
```

#### 6. Get Single Issue
```bash
curl -X GET http://localhost/civic-connect/backend/api/issues/1
```

#### 7. Update Issue
```bash
curl -X PUT http://localhost/civic-connect/backend/api/issues/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -d '{
    "status": "in_progress",
    "priority": "high"
  }'
```

#### 8. Upload Issue Image
```bash
curl -X POST http://localhost/civic-connect/backend/api/upload/issue \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -F "image=@/path/to/image.jpg"
```

#### 9. Add Comment
```bash
curl -X POST http://localhost/civic-connect/backend/api/issues/1/comments \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -d '{
    "content": "This issue affects our entire neighborhood. We need urgent action!",
    "is_anonymous": false
  }'
```

#### 10. Upvote Issue
```bash
curl -X POST http://localhost/civic-connect/backend/api/issues/1/upvotes \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

#### 11. Get Issue Comments
```bash
curl -X GET "http://localhost/civic-connect/backend/api/issues/1/comments?page=1&limit=10"
```

#### 12. Get User Profile
```bash
curl -X GET http://localhost/civic-connect/backend/api/users/1
```

#### 13. Update User Profile
```bash
curl -X PUT http://localhost/civic-connect/backend/api/users/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -d '{
    "first_name": "Updated",
    "location": "New York, NY"
  }'
```

---

## Using Thunder Client (VS Code)

1. Install "Thunder Client" extension
2. Create requests with the following format:

**Register:**
- Method: POST
- URL: `http://localhost/civic-connect/backend/api/users/register`
- Body (JSON): Include email, password, first_name, last_name

**Login:**
- Method: POST
- URL: `http://localhost/civic-connect/backend/api/users/login`
- Body (JSON): Include email, password
- Copy token from response

**Create Issue:**
- Method: POST
- URL: `http://localhost/civic-connect/backend/api/issues`
- Headers: Add `Authorization: Bearer {token}`
- Body (JSON): Include title, description, category, etc.

---

## Common Issues & Solutions

### 1. 404 Not Found
- Check that Apache `mod_rewrite` is enabled
- Verify `.htaccess` file exists in `/api/` directory
- Check `RewriteBase` path matches your installation path

### 2. 500 Internal Server Error
- Check PHP error logs: `/var/log/apache2/error.log`
- Verify database connection in `.env`
- Check folder permissions on upload directories

### 3. CORS Issues
- The API includes CORS headers by default
- Make requests from allowed origins
- Check `APP_URL` in `.env`

### 4. Email Not Sending
- Verify SMTP credentials in `.env`
- Check Gmail app-specific password if using Gmail
- Review PHP error logs for PHPMailer exceptions

### 5. File Upload Not Working
- Check upload folder permissions: `chmod 755`
- Verify file size is under 5MB
- Check MIME type (only images allowed)
- Ensure `uploads/` directory exists

### 6. Token Expires
- Tokens are session-based and last for the duration of the PHP session
- For production, implement JWT tokens
- Send login request again to get new token

---

## Database Schema Overview

### Users Table
- Stores user accounts, profiles, and email verification data
- Indexes on email, created_at, is_active for performance

### Issues Table
- Stores civic issues with location and status tracking
- Supports anonymous posting
- Tracks upvote and comment counts for sorting

### Comments Table
- Stores comments on issues
- Supports anonymous comments
- Maintains creation and update timestamps

### Upvotes Table
- Tracks which users upvoted which issues
- Unique constraint prevents duplicate upvotes
- Cascading delete when issue/user deleted

### Audit Trail Table
- Logs all user actions for compliance
- Stores old and new values for updates
- Tracks IP addresses and user agents

---

## API Features

### Authentication
- Session-based token authentication
- OTP verification for email validation
- Rate limiting on login attempts (5 per 300 seconds)

### Data Validation
- Email format validation
- Password strength requirements (min 8 chars)
- Title and description length limits
- File type and size restrictions

### Security
- Password hashing with bcrypt (cost: 12)
- Prepared statements against SQL injection
- CORS headers for cross-origin requests
- Rate limiting on login attempts

### Performance
- Database indexes on frequently queried fields
- Pagination on list endpoints
- File compression with mod_deflate
- Efficient query design with proper JOINs

### Data Integrity
- Foreign key constraints
- Cascade delete for related records
- UNIQUE constraints on upvotes (one per user per issue)
- Timestamps with automatic updates

---

## Next Steps

1. **Frontend Integration:** Connect Vue.js frontend to these API endpoints
2. **Advanced Authentication:** Implement JWT tokens for better security
3. **Rate Limiting:** Add Redis-based rate limiting for production
4. **Caching:** Implement Redis caching for frequently accessed data
5. **Monitoring:** Set up application monitoring and error tracking
6. **Testing:** Create automated test suite with PHPUnit
7. **Documentation:** Generate API docs with Swagger/OpenAPI

---

## File Structure

```
/backend/
├── api/
│   ├── index.php                 # Main router
│   ├── Middleware.php            # Authentication & validation
│   ├── helpers.php               # Utility functions
│   ├── .htaccess                 # URL rewriting rules
│   └── controllers/
│       ├── UserController.php    # User endpoints
│       ├── IssueController.php   # Issue endpoints
│       ├── FileController.php    # Upload endpoints
│       ├── UpvoteController.php  # Upvote endpoints
│       └── CommentController.php # Comment endpoints
├── config/
│   ├── bootstrap.php             # Environment setup
│   ├── database.php              # Database connection
│   └── mailer.php                # Email configuration
├── uploads/
│   ├── issues/                   # Issue images
│   └── profiles/                 # Profile images
├── .env                          # Configuration file
├── composer.json                 # Dependencies
└── API_DOCUMENTATION.md          # This documentation
```

---

**Last Updated:** January 2025
**API Version:** 1.0
**Base URL:** http://localhost/civic-connect/backend/api/
