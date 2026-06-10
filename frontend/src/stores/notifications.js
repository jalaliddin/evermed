import { defineStore } from 'pinia'
import { ref } from 'vue'
import { tenantApi } from '@/plugins/axios'

export const useNotificationStore = defineStore('notifications', () => {
  const unreadCount = ref(0)
  const notifications = ref([])

  async function fetchUnreadCount() {
    try {
      const res = await tenantApi.get('/notifications/unread-count')
      unreadCount.value = res.data.count
    } catch {}
  }

  async function fetchRecent() {
    try {
      const res = await tenantApi.get('/notifications?per_page=5&is_read=false')
      notifications.value = res.data.data || []
    } catch {}
  }

  async function markAllRead() {
    await tenantApi.patch('/notifications/read-all')
    unreadCount.value = 0
    notifications.value = []
  }

  return { unreadCount, notifications, fetchUnreadCount, fetchRecent, markAllRead }
})
