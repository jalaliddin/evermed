import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/plugins/axios'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(JSON.parse(localStorage.getItem('user') || 'null'))
  const token = ref(localStorage.getItem('token') || null)
  const userType = ref(localStorage.getItem('user_type') || null) // 'admin' | 'tenant'
  const tenantId = ref(localStorage.getItem('tenant_id') || null)

  const isLoggedIn = computed(() => !!token.value)
  const isAdmin = computed(() => userType.value === 'admin')
  const isTenant = computed(() => userType.value === 'tenant')
  const role = computed(() => user.value?.role)

  async function login(email, password) {
    const res = await api.post('/auth/login', { email, password })
    const data = res.data

    token.value = data.token
    user.value = data.user
    userType.value = data.type

    localStorage.setItem('token', data.token)
    localStorage.setItem('user', JSON.stringify(data.user))
    localStorage.setItem('user_type', data.type)

    // For tenant users, store tenant_id for API calls
    if (data.type === 'tenant' && data.user?.tenant_id) {
      tenantId.value = data.user.tenant_id
      localStorage.setItem('tenant_id', data.user.tenant_id)
    }

    return data
  }

  async function logout() {
    try { await api.post('/auth/logout') } catch {}
    token.value = null
    user.value = null
    userType.value = null
    tenantId.value = null
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    localStorage.removeItem('user_type')
    localStorage.removeItem('tenant_id')
  }

  async function fetchMe() {
    const res = await api.get('/auth/me')
    user.value = res.data.user
    userType.value = res.data.type
    localStorage.setItem('user', JSON.stringify(res.data.user))
    localStorage.setItem('user_type', res.data.type)
    return res.data
  }

  return { user, token, userType, tenantId, isLoggedIn, isAdmin, isTenant, role, login, logout, fetchMe }
})
