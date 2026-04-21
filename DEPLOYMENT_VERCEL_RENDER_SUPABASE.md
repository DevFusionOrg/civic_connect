# Civic Connect Cloud Deployment Guide

This guide deploys:
- Frontend: Vercel
- Backend: Render (Docker)
- Database: Supabase (PostgreSQL)

## 1) Supabase Setup

1. Create a new Supabase project.
2. In Supabase, open SQL Editor.
3. Run `database_postgres.sql` from this repository to create all tables and seed staff mappings.
5. In Supabase Project Settings > Database, copy:
- Host
- Port
- Database name
- User
- Password

Notes:
- This codebase now supports PostgreSQL via PDO (`DB_DRIVER=pgsql`).
- Supabase requires SSL (`DB_SSLMODE=require`).

## 2) Backend Setup on Render

1. Create a new Web Service in Render.
2. Connect your repository and set root to `backend`.
3. Build/Start:
- Render will use the Dockerfile in `backend`.
- Exposed port is 80.

4. Add environment variables in Render:
- `FRONTEND_URL=https://your-frontend.vercel.app`
- `BACKEND_PUBLIC_URL=https://your-backend.onrender.com`
- `APP_URL=https://your-frontend.vercel.app`
- `API_BASE_PATH=/api`
- `CORS_ALLOWED_ORIGINS=https://your-frontend.vercel.app,https://*.vercel.app`
- `DB_DRIVER=pgsql`
- `DB_HOST=...`
- `DB_PORT=5432`
- `DB_NAME=postgres`
- `DB_USER=postgres`
- `DB_PASS=...`
- `DB_SSLMODE=require`
- `SMTP_HOST=...`
- `SMTP_USER=...`
- `SMTP_PASS=...`
- `SMTP_PORT=...`
- `SMTP_FROM=...`
- `SMTP_FROM_NAME=Civic Connect`
- `GEMINI_API_KEY=...`
- `GEMINI_MODEL=gemini-2.5-flash`

5. Test backend endpoints:
- `https://your-backend.onrender.com/api/health`
- `https://your-backend.onrender.com/api/stats.php`

## 3) Frontend Setup on Vercel

1. Import project in Vercel with root directory `frontend`.
2. Add env variable:
- `VITE_API_URL=https://your-backend.onrender.com/api`

3. Deploy.
4. Verify app loads and API calls succeed.

## 4) Post-Deploy Verification Checklist

1. Register user
2. Verify email OTP
3. Login
4. Create issue with image
5. Run AI detection endpoint
6. Upvote issue
7. Admin login and open admin dashboard/staff/users/audit logs
8. Staff dashboard assigned issues
9. Notification fetch/unread/read
10. Chatbot request

## 5) Common Problems

1. CORS errors:
- Ensure `FRONTEND_URL` and `CORS_ALLOWED_ORIGINS` include your Vercel domain.
- If using preview deployments, include `https://*.vercel.app`.

2. Preflight `OPTIONS` returns 404:
- This usually means Apache rewrite rules are not active in the backend container.
- Ensure backend is deployed from the current Dockerfile (with `AllowOverride All` and `mod_rewrite` enabled), then redeploy Render.
- After redeploy, `OPTIONS https://your-backend.onrender.com/api/auth/register` should return 204/200 with CORS headers.

2. 401 on admin pages:
- Ensure login token exists and backend receives `Authorization: Bearer <token>`.

3. Supabase connection fails:
- Confirm `DB_DRIVER=pgsql` and `DB_SSLMODE=require`.
- Confirm DB host/user/password are from Supabase connection settings.

4. Broken image URLs:
- Ensure `BACKEND_PUBLIC_URL` matches your Render URL.

## 6) Security

1. Never commit real `.env` credentials.
2. Rotate any exposed credentials immediately.
3. Use provider secrets managers (Render/Vercel env settings).
