<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-6">
      <div class="text-h5 font-weight-bold">Bildirishnomalar</div>
      <v-btn variant="outlined" prepend-icon="mdi-check-all" @click="markAllRead">Barchasini o'qildi deb belgilash</v-btn>
    </div>

    <v-card rounded="xl">
      <v-list lines="two">
        <template v-if="loading">
          <v-skeleton-loader v-for="i in 5" :key="i" type="list-item-two-line" />
        </template>

        <template v-else-if="notifications.length">
          <v-list-item
            v-for="n in notifications"
            :key="n.id"
            :class="{ 'bg-primary-lighten-5': !n.read_at }"
            @click="markRead(n)"
          >
            <template #prepend>
              <v-avatar :color="typeColor(n.type)" variant="tonal" size="40">
                <v-icon :icon="typeIcon(n.type)" />
              </v-avatar>
            </template>
            <template #title>
              <span :class="{ 'font-weight-bold': !n.read_at }">{{ n.title }}</span>
            </template>
            <template #subtitle>{{ n.message }}</template>
            <template #append>
              <div class="d-flex flex-column align-end gap-1">
                <span class="text-caption text-medium-emphasis">{{ formatDate(n.created_at) }}</span>
                <v-chip v-if="!n.read_at" color="primary" size="x-small" label>Yangi</v-chip>
              </div>
            </template>
          </v-list-item>
        </template>

        <v-list-item v-else>
          <v-list-item-title class="text-center text-medium-emphasis py-8">
            <v-icon size="48" class="d-block mb-2">mdi-bell-off-outline</v-icon>
            Bildirishnomalar yo'q
          </v-list-item-title>
        </v-list-item>
      </v-list>

      <div class="pa-4 d-flex justify-center" v-if="hasMore">
        <v-btn variant="outlined" :loading="loadingMore" @click="loadMore">Ko'proq yuklash</v-btn>
      </div>
    </v-card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { tenantApi } from '@/plugins/axios'
import { useNotificationStore } from '@/stores/notifications'
import dayjs from 'dayjs'

const notifStore = useNotificationStore()
const notifications = ref([])
const loading = ref(false)
const loadingMore = ref(false)
const page = ref(1)
const hasMore = ref(false)

function typeColor(t) {
  return { appointment: 'primary', payment: 'success', inventory: 'warning', system: 'info' }[t] || 'grey'
}
function typeIcon(t) {
  return { appointment: 'mdi-calendar', payment: 'mdi-cash', inventory: 'mdi-package-variant', system: 'mdi-information' }[t] || 'mdi-bell'
}
function formatDate(d) { return d ? dayjs(d).format('DD.MM.YYYY HH:mm') : '' }

async function load() {
  loading.value = true
  try {
    const res = await tenantApi.get('/notifications', { params: { page: 1, per_page: 20 } })
    notifications.value = res.data.data || []
    hasMore.value = res.data.current_page < res.data.last_page
    page.value = 1
  } finally { loading.value = false }
}

async function loadMore() {
  loadingMore.value = true
  try {
    const res = await tenantApi.get('/notifications', { params: { page: page.value + 1, per_page: 20 } })
    notifications.value.push(...(res.data.data || []))
    page.value++
    hasMore.value = res.data.current_page < res.data.last_page
  } finally { loadingMore.value = false }
}

async function markRead(n) {
  if (n.read_at) return
  await tenantApi.patch(`/notifications/${n.id}/read`)
  n.read_at = new Date().toISOString()
  notifStore.fetchCount()
}

async function markAllRead() {
  await tenantApi.patch('/notifications/read-all')
  notifications.value.forEach(n => { n.read_at = n.read_at || new Date().toISOString() })
  notifStore.fetchCount()
}

onMounted(load)
</script>
