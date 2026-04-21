import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/authStore'
import { checkRouteAccess, getRedirectPath } from './guards'

const routes = [
  // Public routes
  {
    path: '/',
    name: 'home',
    component: () => import('../views/HomePage.vue'),
    meta: { guard: 'PUBLIC', title: 'Home' },
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('../views/auth/RegisterPage.vue'),
    meta: { guard: 'PUBLIC', title: 'Register', layout: 'AuthLayout' },
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('../views/auth/LoginPage.vue'),
    meta: { guard: 'PUBLIC', title: 'Login', layout: 'AuthLayout' },
  },
  {
    path: '/verify-email',
    name: 'verifyEmail',
    component: () => import('../views/auth/VerifyEmailPage.vue'),
    meta: { guard: 'PUBLIC', title: 'Verify Email', layout: 'AuthLayout' },
  },
  {
    path: '/verify-otp',
    name: 'verifyOTP',
    component: () => import('../views/auth/OTPVerificationPage.vue'),
    meta: { guard: 'PUBLIC', title: 'Verify OTP', layout: 'AuthLayout' },
  },
  {
    path: '/forgot-password',
    name: 'forgotPassword',
    component: () => import('../views/auth/ForgotPasswordPage.vue'),
    meta: { guard: 'PUBLIC', title: 'Forgot Password', layout: 'AuthLayout' },
  },

  // Citizen routes (authenticated users)
  {
    path: '/dashboard',
    name: 'citizenDashboard',
    component: () => import('../views/citizen/DashboardPage.vue'),
    meta: { guard: 'CITIZEN', title: 'Dashboard' },
  },
  {
    path: '/issues',
    name: 'issuesList',
    component: () => import('../views/citizen/IssuesListPage.vue'),
    meta: { guard: 'CITIZEN', title: 'Issues' },
  },
  {
    path: '/issues-map',
    name: 'issuesMap',
    component: () => import('../views/citizen/IssuesMapPage.vue'),
    meta: { guard: 'CITIZEN', title: 'Issues Map' },
  },
  {
    path: '/issues/:id',
    name: 'issueDetail',
    component: () => import('../views/citizen/IssueDetailPage.vue'),
    meta: { guard: 'CITIZEN', title: 'Issue Details' },
  },
  {
    path: '/report-issue',
    name: 'reportIssue',
    component: () => import('../views/citizen/ReportIssuePage.vue'),
    meta: { guard: 'CITIZEN', title: 'Report Issue' },
  },
  {
    path: '/my-issues',
    name: 'myIssues',
    component: () => import('../views/citizen/MyIssuesPage.vue'),
    meta: { guard: 'CITIZEN', title: 'My Issues' },
  },
  {
    path: '/profile/:id?',
    name: 'profile',
    component: () => import('../views/citizen/ProfilePage.vue'),
    meta: { guard: 'CITIZEN', title: 'Profile' },
  },
  {
    path: '/edit-profile',
    name: 'editProfile',
    component: () => import('../views/citizen/EditProfilePage.vue'),
    meta: { guard: 'CITIZEN', title: 'Edit Profile' },
  },
  {
    path: '/notifications',
    name: 'notifications',
    component: () => import('../views/citizen/NotificationsPage.vue'),
    meta: { guard: 'CITIZEN', title: 'Notifications' },
  },

  // Staff routes
  {
    path: '/staff/dashboard',
    name: 'staffDashboard',
    component: () => import('../views/staff/DashboardPage.vue'),
    meta: { guard: 'STAFF', title: 'Staff Dashboard' },
  },
  {
    path: '/staff/issues',
    name: 'staffIssues',
    component: () => import('../views/staff/IssuesManagementPage.vue'),
    meta: { guard: 'STAFF', title: 'Manage Issues' },
  },
  {
    path: '/staff/issues/:id',
    name: 'staffIssueDetail',
    component: () => import('../views/staff/IssueDetailPage.vue'),
    meta: { guard: 'STAFF', title: 'Issue Details' },
  },
  {
    path: '/staff/reports',
    name: 'staffReports',
    component: () => import('../views/staff/ReportsPage.vue'),
    meta: { guard: 'STAFF', title: 'Reports' },
  },

  // Admin routes
  {
    path: '/admin/dashboard',
    name: 'adminDashboard',
    component: () => import('../views/admin/DashboardPage.vue'),
    meta: { guard: 'ADMIN', title: 'Admin Dashboard' },
  },
  {
    path: '/admin/users',
    name: 'adminUsers',
    component: () => import('../views/admin/UsersManagementPage.vue'),
    meta: { guard: 'ADMIN', title: 'Manage Users' },
  },
  {
    path: '/admin/staff',
    name: 'adminStaff',
    component: () => import('../views/admin/StaffManagementPage.vue'),
    meta: { guard: 'ADMIN', title: 'Manage Staff' },
  },
  {
    path: '/admin/issues',
    name: 'adminIssues',
    component: () => import('../views/admin/IssuesManagementPage.vue'),
    meta: { guard: 'ADMIN', title: 'Manage Issues' },
  },
  {
    path: '/admin/issues/:id',
    name: 'adminIssueDetail',
    component: () => import('../views/staff/IssueDetailPage.vue'),
    meta: { guard: 'ADMIN', title: 'Issue Details' },
  },
  {
    path: '/admin/audit-logs',
    name: 'adminAuditLogs',
    component: () => import('../views/admin/AuditLogsPage.vue'),
    meta: { guard: 'ADMIN', title: 'Audit Logs' },
  },
  {
    path: '/admin/analytics',
    name: 'adminAnalytics',
    component: () => import('../views/admin/AnalyticsPage.vue'),
    meta: { guard: 'ADMIN', title: 'Analytics' },
  },

  // Error routes
  {
    path: '/unauthorized',
    name: 'unauthorized',
    component: () => import('../views/errors/UnauthorizedPage.vue'),
    meta: { guard: 'PUBLIC', title: 'Unauthorized', layout: 'AuthLayout' }, // Use simple layout for errors
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'notFound',
    component: () => import('../views/errors/NotFoundPage.vue'),
    meta: { guard: 'PUBLIC', title: 'Not Found', layout: 'AuthLayout' },
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition
    }
    return { top: 0 }
  },
})

/**
 * Global before guard for route access control
 */
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()

  // Initialize auth from localStorage if not already done
  if (!authStore.user && authStore.token) {
    authStore.initializeAuth()
  }

  // Keep /dashboard role-aware so admin/staff never land on citizen dashboard.
  if (to.path === '/dashboard' && authStore.isAuthenticated) {
    if (authStore.isAdmin) {
      next('/admin/dashboard')
      return
    }
    if (authStore.isStaff) {
      next('/staff/dashboard')
      return
    }
  }

  const guard = to.meta.guard || 'PUBLIC'
  const hasAccess = checkRouteAccess(guard)

  if (!hasAccess) {
    // User doesn't have access
    const redirectPath = getRedirectPath(guard)

    // If user is logged in but doesn't have permission, show unauthorized
    if (authStore.isAuthenticated && redirectPath === '/login') {
      next('/unauthorized')
    } else {
      next(redirectPath)
    }
  } else {
    next()
  }
})

/**
 * After navigation hook for page title
 */
router.afterEach((to) => {
  document.title = `${to.meta.title || 'Page'} - CivicConnect`
})

export default router
