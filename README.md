
---

# **CivicConnect – Smart City Issue Reporter**

## **Overview**

CivicConnect is a web-based platform that allows citizens to report city issues such as potholes, trash, broken streetlights, and other public concerns. The system provides a geo-tagging map interface, upvoting features to prioritize reports, and an audit trail to ensure transparency when staff update issue statuses.

---

## **Tech Stack**

### **Frontend**

* **Vue.js** (^3.5.25) — progressive UI framework
* **Vite** (^7.2.4) — fast build tool and dev server
* **Tailwind CSS** (^4.1.18) — utility-first CSS framework
* **Axios** (^1.13.2) — HTTP client
* **Pinia** (^3.0.4) — state management
* **Vue Router** (^4.6.3) — client-side routing
* **Vue Toastification** — toast notifications
* **Heroicons/vue** — icons
* **ESLint + Prettier** — linting and formatting

### **Map**

* Leaflet.js
* OpenStreetMap

### **Backend**

* **PHP** (vanilla)
* **PHPMailer** — email, OTP, verification
* **Dotenv (phpdotenv)** — environment variables
* **Composer** — PHP dependency manager

### **Database**

* MySQL

### **Storage**

* Local storage (`/backend/uploads`) for issue images

---

## **Features**

### **Citizen**

* **Submit Issues**: Report problems with description, category, location (map pin), and photo.
* **My Issues**: View and track status of issues you have reported.
* **Upvote**: Upvote existing issues to increase priority.
* **Track Status**: Receive updates when issue status changes.
* **Security**:
    * Email verification on signup.

    * OTP verification for login (optional/conditional).
    * Captcha protection.
* **Notifications**: 
    * In-app notification center for status updates.
    * Email alerts for issue status changes.
* **AI Chatbot Assistance**: 
    * Interactive chatbot powered by Google Gemini AI.
    * Provides help with reporting issues and navigating the platform.
    * Context-aware responses and guidance.

### **Staff**

* **Dashboard**: View assigned or relevant issues.
* **Issue Management**: Update status (Pending → In Progress → Resolved).
* **Audit Trail**: All actions are automatically logged.
* **Filtering**: Sort/filter issues by status or upvotes.
* **Reports & Analytics**: Access analytics dashboard with issue statistics and trends.

### **Admin**

* **Admin Dashboard**: Overview of system activity.
* **User Management**: Manage users, assign roles (Citizen / Staff).
* **Staff Management**: Create and manage staff accounts.
* **Audit Logs**: Full transparency with efficient audit log viewing.
* **System Stats**: View key metrics.

---

## **Database Tables**

### **users**
`id, email, password_hash, first_name, last_name, phone, location, email_verified, otp_code, role, last_login, created_at`

### **issues**
`id, user_id, title, description, category, location, latitude, longitude, status, priority, image_path, upvote_count, is_anonymous, created_at, resolved_at`

### **upvotes**
`id, issue_id, user_id, created_at`

### **notification**
`id, user_id, issue_id, type, title, message, old_status, new_status, is_read, created_at`


### **audit_trail**
`id, user_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent, created_at`

---

## **Environment Requirements**

* **Node.js**: ^20.19.0 or >=22.12.0
* **npm**: ^11.7.0
* **PHP**: 7.4+ (recommended 8.0+)
* **MySQL**: 5.7+

---

## **VScode Extension Requirements**
* **Vue (official)**
* **Todo+**
* **Eslint**
* **Prettier**
* **Tailwind CSS Intellisense**

---

## **Setup & Development Workflow**

### **1. Fork & Clone Repository**

```bash
# Clone your fork
git clone https://github.com/YOUR_USERNAME/civic-connect.git
cd civic-connect

# Add upstream (if working in a team)
git remote add upstream https://github.com/EbadShelby/civic-connect.git
```

### **2. Database Setup**

1.  Create a MySQL database named `civic_connect`.
2.  Import the schema:
    ```sql
    source database.sql;
    ```
3.  **Create Admin Account**:
    Register a new user via the frontend (register page), then run the migration script to promote them to admin:
    ```bash
    php migrate_admin.php
    ```

### **3. Backend Setup**

```bash
cd backend
cp .env.example .env
composer install
```

Update `.env` with your credentials:
*   Database host, user, password, name
*   SMTP settings for email (Gmail/Mailtrap)

**Run Backend**:
Ensure your web server (Apache/Nginx via XAMPP/MAMP) points to the project root or `backend` folder.
Base URL in frontend config is set to: `http://localhost/civic-connect/backend/api` (Adjust in `frontend/src/stores/` if different).

### **4. Frontend Setup**

```bash
cd frontend
npm install
npm run dev
```

---

## **Folder Structure**

```
civic-connect/
├─ frontend/                # Vue 3 + Vite App
│  ├─ src/
│  │  ├─ views/             # Page components (Citizen, Staff, Admin)
│  │  ├─ stores/            # Pinia state stores
│  │  └─ router/            # Vue router config
│  └─ package.json
│
├─ backend/                 # PHP API
│  ├─ api/
│  │  └─ controllers/       # Logic for Issues, Users, Staff
│  ├─ config/               # DB & Mailer config
│  ├─ uploads/              # Stored images
│  └─ composer.json
│
├─ database.sql             # SQL Schema
├─ migrate_admin.php        # Utility to create admin
└─ README.md
```

---

## **License**

**MIT License** — free to use, modify, and distribute for academic or personal projects.

---

---

## **API Reference**

### **Authentication**

*   `POST /api/auth/register` - Register new user with email verification.
*   `POST /api/auth/login` - Login user.
*   `POST /api/auth/verify-email` - Verify email with OTP code.
*   `POST /api/auth/resend-verification` - Resend verification OTP.
*   `POST /api/auth/logout` - Logout current user.
*   `POST /api/auth/forgot-password` - Request password reset.
*   `POST /api/auth/verify-reset-code` - Verify password reset code.
*   `POST /api/auth/reset-password` - Reset password with code.

Legacy compatibility routes are also supported under `/api/users/*` for auth actions.

### **Issues**

*   `GET /api/issues` - Fetch all issues (supports filtering by status, category, user).
*   `GET /api/issues/{id}` - Get specific issue details.
*   `POST /api/issues` - Create new issue (with image upload).
*   `PUT /api/issues/{id}` - Update issue details.
*   `PUT /api/issues/{id}/status` - Update issue status (Staff/Admin only).
*   `DELETE /api/issues/{id}` - Delete issue (Admin only).
*   `GET /api/issues/my-issues` - Get issues reported by current user.

### **Upvotes**

*   `POST /api/upvotes` - Upvote an issue.
*   `DELETE /api/upvotes/{id}` - Remove upvote.
*   `GET /api/upvotes/check` - Check if user has upvoted an issue.

### **Users**

*   `GET /api/users` - Get all users (Admin only).
*   `GET /api/users/{id}` - Get specific user details.
*   `PUT /api/users/{id}` - Update user profile.
*   `DELETE /api/users/{id}` - Delete user (Admin only).
*   `GET /api/users/profile` - Get current user's profile.
*   `PUT /api/users/profile` - Update current user's profile.

### **Staff Management**

*   `GET /api/staff` - Get all staff members (Admin only).
*   `POST /api/staff` - Create new staff account (Admin only).
*   `PUT /api/staff/{id}` - Update staff account (Admin only).
*   `DELETE /api/staff/{id}` - Delete staff account (Admin only).

### **Admin & Analytics**

*   `GET /api/stats` - Get system statistics (issues, users, resolution rate).
*   `GET /api/analytics` - Get detailed analytics (Admin/Staff only).
*   `GET /api/audit` - Get audit trail logs (Admin only).

### **Notifications**

*   `GET /api/notifications` - Fetch list of notifications (Supports pagination).
*   `GET /api/notifications/unread-count` - Get count of unread notifications.
*   `PUT /api/notifications/{id}/read` - Mark a specific notification as read.
*   `PUT /api/notifications/mark-all-read` - Mark all notifications as read.

### **Chatbot**

*   `POST /api/chatbot` - Send message to AI chatbot and get response.

---

## **Local Dev Troubleshooting**

### **Auth endpoint 404 on localhost**

Frontend uses `/api/auth/*` and automatically falls back to `/api/users/*` if a local route returns 404.

### **SMTP warning / mixed JSON response**

If SMTP env variables are missing, registration still completes, but email sending fails gracefully.
Set these in backend `.env` to enable email sending:

* `SMTP_HOST`
* `SMTP_USER`
* `SMTP_PASS`
* `SMTP_PORT`
* `SMTP_FROM`
* `SMTP_FROM_NAME`

Note: `POST /api/auth/resend-verification` requires SMTP to be configured. Without SMTP, this endpoint will return an error by design.

### **Bypass email verification locally**

To unblock local login without verifying OTP, set in backend `.env`:

* `BYPASS_EMAIL_VERIFICATION_LOCAL=true` (default behavior for localhost)
* Optional global override: `BYPASS_EMAIL_VERIFICATION=true`

Do not enable these bypass flags in production.
