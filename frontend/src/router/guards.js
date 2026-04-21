import { useAuthStore } from '../stores/authStore'

/**
 * Route Guards for access control
 * PUBLIC: Anyone can access
 * CITIZEN: Authenticated users (citizen, staff, admin)
 * STAFF: Only staff and admin users
 * ADMIN: Only admin users
 */

export const isPublic = () => true

export const isCitizen = () => {
  const authStore = useAuthStore()
  return authStore.isAuthenticated
}

export const isStaff = () => {
  const authStore = useAuthStore()
  return authStore.isAuthenticated && (authStore.isStaff || authStore.isAdmin)
}

export const isAdmin = () => {
  const authStore = useAuthStore()
  return authStore.isAuthenticated && authStore.isAdmin
}

/**
 * Guards to check access based on route meta
 */
export const createGuards = () => {
  return {
    PUBLIC: {
      check: isPublic,
      redirectTo: null
    },
    CITIZEN: {
      check: isCitizen,
      redirectTo: '/login'
    },
    STAFF: {
      check: isStaff,
      redirectTo: '/login'
    },
    ADMIN: {
      check: isAdmin,
      redirectTo: '/login'
    }
  }
}

/**
 * Navigation guard for checking route access
 */
export const checkRouteAccess = (guard) => {
  if (guard === 'PUBLIC') return true
  if (guard === 'CITIZEN') return isCitizen()
  if (guard === 'STAFF') return isStaff()
  if (guard === 'ADMIN') return isAdmin()
  return false
}

/**
 * Get redirect path based on failed access
 */
export const getRedirectPath = (guard) => {
  const guards = createGuards()
  return guards[guard]?.redirectTo || '/login'
}
