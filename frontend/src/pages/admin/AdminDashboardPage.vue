<template>
  <div>
    <div class="text-h5 font-weight-bold mb-6">Super Admin Panel</div>

    <v-row class="mb-4">
      <v-col v-for="stat in stats" :key="stat.key" cols="6" sm="3">
        <v-card rounded="xl" :color="stat.color" variant="tonal">
          <v-card-text class="pa-4">
            <div class="d-flex justify-space-between align-start mb-2">
              <v-icon :icon="stat.icon" size="28" />
              <v-chip v-if="stat.badge" :color="stat.badge.color" size="x-small">{{ stat.badge.text }}</v-chip>
            </div>
            <div class="text-h5 font-weight-bold">{{ stat.value }}</div>
            <div class="text-caption text-medium-emphasis">{{ stat.label }}</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-row>
      <v-col cols="12" md="8">
        <v-card rounded="xl">
          <v-card-title class="pa-4 pb-0">So'nggi klinikalar</v-card-title>
          <v-data-table
            :headers="tenantHeaders"
            :items="recentTenants"
            :loading="loading"
            density="comfortable"
            :items-per-page="5"
            hide-default-footer
          >
            <template #item.name="{ item }">
              <div>
                <div class="font-weight-medium">{{ item.name }}</div>
                <div class="text-caption text-medium-emphasis">{{ item.slug }}</div>
              </div>
            </template>
            <template #item.status="{ item }">
              <v-chip :color="item.status === 'active' ? 'success' : 'error'" size="small" label>
                {{ item.status === 'active' ? 'Aktiv' : 'Bloklangan' }}
              </v-chip>
            </template>
            <template #item.plan="{ item }">{{ item.plan || 'basic' }}</template>
            <template #item.created_at="{ item }">{{ formatDate(item.created_at) }}</template>
            <template #item.actions="{ item }">
              <v-btn icon="mdi-eye-outline" size="small" variant="text" :to="`/admin/tenants/${item.id}`" />
            </template>
          </v-data-table>
          <v-card-actions class="pa-4">
            <v-btn variant="text" to="/admin/tenants" append-icon="mdi-arrow-right">Barchasi</v-btn>
          </v-card-actions>
        </v-card>
      </v-col>

      <v-col cols="12" md="4">
        <v-card rounded="xl" class="mb-4">
          <v-card-title class="pa-4 pb-0">Tizim holati</v-card-title>
          <v-list density="compact" class="pa-2">
            <v-list-item v-for="item in systemStatus" :key="item.label" :title="item.label">
              <template #append>
                <v-chip :color="item.ok ? 'success' : 'error'" size="small" label>{{ item.ok ? 'OK' : 'Xato' }}</v-chip>
              </template>
            </v-list-item>
          </v-list>
        </v-card>

        <v-card rounded="xl">
          <v-card-title class="pa-4 pb-0">Oxirgi faollik</v-card-title>
          <v-list density="compact" lines="two" class="pa-2">
            <v-list-item
              v-for="a in recentActivity"
              :key="a.id"
              :title="a.title"
              :subtitle="formatDate(a.created_at)"
              prepend-icon="mdi-circle-small"
            />
          </v-list>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/plugins/axios'
import dayjs from 'dayjs'

const loading = ref(false)
const overview = ref({})
const recentTenants = ref([])
const recentActivity = ref([])

const stats = computed(() => {
  const s = overview.value
  return [
    { key: 'tenants', label: 'Jami klinikalar', value: s.total_tenants || 0, icon: 'mdi-hospital-building', color: 'primary' },
    { key: 'active', label: 'Aktiv', value: s.active || 0, icon: 'mdi-check-circle', color: 'success' },
    { key: 'trial', label: 'Sinov', value: s.trial || 0, icon: 'mdi-clock-outline', color: 'warning' },
    { key: 'revenue', label: 'Oy daromadi', value: (s.monthly_revenue || 0).toLocaleString('uz-UZ'), icon: 'mdi-currency-usd', color: 'secondary' },
  ]
})

const systemStatus = ref([
  { label: 'Databaza', ok: true },
  { label: 'Queue', ok: true },
  { label: 'Storage', ok: true },
])

const tenantHeaders = [
  { title: 'Klinika', key: 'name' },
  { title: 'Holat', key: 'status', width: 100 },
  { title: 'Plan', key: 'plan', width: 100 },
  { title: 'Yaratildi', key: 'created_at', width: 120 },
  { title: '', key: 'actions', sortable: false, width: 60 },
]

function formatDate(d) { return d ? dayjs(d).format('DD.MM.YY') : '' }

async function load() {
  loading.value = true
  try {
    const res = await api.get('/admin/dashboard')
    overview.value = res.data.stats || {}
    recentTenants.value = res.data.recent_tenants || []
  } finally { loading.value = false }
}

onMounted(load)
</script>
