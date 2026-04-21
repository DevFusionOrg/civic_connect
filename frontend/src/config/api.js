const DEFAULT_LOCAL_API_URL = 'http://localhost/civic-connect/backend/api'

const isBrowser = typeof window !== 'undefined'
const hostname = isBrowser ? window.location.hostname : ''
const isLocalHost = hostname === 'localhost' || hostname === '127.0.0.1'
const browserOrigin = isBrowser ? window.location.origin : ''

const envApiUrl = import.meta.env.VITE_API_URL?.trim()

export const API_BASE_URL = envApiUrl
  || (isLocalHost ? DEFAULT_LOCAL_API_URL : `${browserOrigin}/api`)

export const getAuthHeaders = () => {
  const token = localStorage.getItem('token')
  return token ? { Authorization: `Bearer ${token}` } : {}
}
