import axios from 'axios'

const api = axios.create({
  baseURL: '/api',
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
})

// Inject auth token and tenant header on every request
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) config.headers.Authorization = `Bearer ${token}`

  const tenantId = localStorage.getItem('tenant_id')
  if (tenantId) config.headers['X-Tenant'] = tenantId

  return config
})

// Handle 401 globally
api.interceptors.response.use(
  (res) => res,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token')
      localStorage.removeItem('tenant_id')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

// Tenant API — uses /api/tenant/* prefix
export const tenantApi = axios.create({
  baseURL: '/api/tenant',
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
})

tenantApi.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) config.headers.Authorization = `Bearer ${token}`

  const tenantId = localStorage.getItem('tenant_id')
  if (tenantId) config.headers['X-Tenant'] = tenantId

  return config
})

tenantApi.interceptors.response.use(
  (res) => res,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export default api
