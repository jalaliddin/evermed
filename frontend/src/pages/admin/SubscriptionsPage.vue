<template>
  <div>
    <div class="text-h5 font-weight-bold mb-6">Obunalar</div>

    <!-- Summary -->
    <v-row class="mb-4">
      <v-col v-for="stat in stats" :key="stat.key" cols="6" sm="3">
        <v-card rounded="xl" :color="stat.color" variant="tonal">
          <v-card-text class="pa-4">
            <div class="text-caption text-medium-emphasis mb-1">{{ stat.label }}</div>
            <div class="text-h5 font-weight-bold">{{ stat.value }}</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-card rounded="xl">
      <div class="pa-4 d-flex flex-wrap gap-3">
        <v-text-field v-model="search" placeholder="Qidirish..." prepend-inner-icon="mdi-magnify" variant="outlined" density="compact" hide-details clearable style="max-width: 280px;" @update:model-value="debouncedLoad" />
        <v-chip-group v-model="statusFilter" @update:model-value="load">
          <v-chip value="">Barchasi</v-chip>
          <v-chip value="active" color="success" variant="outlined">Aktiv</v-chip>
          <v-chip value="expiring" color="warning" variant="outlined">Tugayapti</v-chip>
          <v-chip value="expired" color="error" variant="outlined">Tugagan</v-chip>
        </v-chip-group>
      </div>

      <v-data-table-server
        :headers="headers"
        :items="subscriptions"
        :items-length="total"
        :loading="loading"
        :items-per-page="15"
        density="comfortable"
        @update:options="onOptions"
      >
        <template #item.tenant="{ item }">
          <router-link :to="`/admin/tenants/${item.tenant_id}`" class="text-decoration-none font-weight-medium">
            {{ item.tenant?.name }}
          </router-link>
        </template>
        <template #item.amount="{ item }">{{ formatMoney(item.amount) }}</template>
        <template #item.starts_at="{ item }">{{ formatDate(item.starts_at) }}</template>
        <template #item.expires_at="{ item }">
          <div :class="{ 'text-error': isExpired(item.expires_at), 'text-warning': isExpiringSoon(item.expires_at) }">
            {{ formatDate(item.expires_at) }}
            <div class="text-caption">{{ daysLeft(item.expires_at) }} kun qoldi</div>
          </div>
        </template>
        <template #item.status="{ item }">
          <v-chip :color="subStatusColor(item)" size="small" label>{{ subStatusLabel(item) }}</v-chip>
        </template>
        <template #item.actions="{ item }">
          <v-btn icon="mdi-calendar-plus" size="small" variant="text" color="primary" title="Uzaytirish" @click="openExtend(item)" />
        </template>
      </v-data-table-server>
    </v-card>

    <!-- Extend Dialog -->
    <v-dialog v-model="extendDialog" max-width="400">
      <v-card rounded="xl">
        <v-card-title class="pa-4">Obuna uzaytirish — {{ extendTarget?.tenant?.name }}</v-card-title>
        <v-card-text class="pa-4">
          <v-text-field v-model.number="extendMonths" label="Oylar soni" type="number" variant="outlined" class="mb-3" min="1" max="24" />
          <v-text-field v-model.number="extendAmount" label="Summa (so'm)" type="number" variant="outlined" />
          <div class="text-caption text-medium-emphasis mt-2">Standart: {{ (extendMonths * 150000).toLocaleString() }} so'm/oy</div>
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-spacer />
          <v-btn @click="extendDialog = false">Bekor</v-btn>
          <v-btn color="primary" :loading="saving" @click="doExtend">Uzaytirish</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color">{{ snackbar.text }}</v-snackbar>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import api from '@/plugins/axios'
import dayjs from 'dayjs'

const subscriptions = ref([])
const total = ref(0)
const overview = ref({})
const loading = ref(false)
const saving = ref(false)
const search = ref('')
const statusFilter = ref('')
const extendDialog = ref(false)
const extendTarget = ref(null)
const extendMonths = ref(1)
const extendAmount = ref(150000)
const snackbar = ref({ show: false, text: '', color: 'success' })
let currentOptions = { page: 1, itemsPerPage: 15 }

watch(extendMonths, v => { extendAmount.value = v * 150000 })

const stats = computed(() => [
  { key: 'active', label: 'Aktiv', value: overview.value.active || 0, color: 'success' },
  { key: 'expiring', label: 'Tugayapti (7 kun)', value: overview.value.expiring_soon || 0, color: 'warning' },
  { key: 'expired', label: 'Tugagan', value: overview.value.expired || 0, color: 'error' },
  { key: 'revenue', label: 'Bu oy daromad', value: formatMoney(overview.value.monthly_revenue), color: 'primary' },
])

const headers = [
  { title: 'Klinika', key: 'tenant' },
  { title: 'Boshlanish', key: 'starts_at', width: 120 },
  { title: 'Tugash', key: 'expires_at', width: 160 },
  { title: 'Summa', key: 'amount', width: 140 },
  { title: 'Holat', key: 'status', width: 110 },
  { title: '', key: 'actions', sortable: false, width: 60 },
]

function formatDate(d) { return d ? dayjs(d).format('DD.MM.YYYY') : '' }
function formatMoney(v) { return v ? Number(v).toLocaleString('uz-UZ') + " so'm" : '0' }
function daysLeft(d) { return d ? Math.max(0, dayjs(d).diff(dayjs(), 'day')) : 0 }
function isExpired(d) { return d && dayjs(d).isBefore(dayjs()) }
function isExpiringSoon(d) { return !isExpired(d) && daysLeft(d) <= 7 }

function subStatusColor(item) {
  if (isExpired(item.expires_at)) return 'error'
  if (isExpiringSoon(item.expires_at)) return 'warning'
  return 'success'
}
function subStatusLabel(item) {
  if (isExpired(item.expires_at)) return 'Tugagan'
  if (isExpiringSoon(item.expires_at)) return 'Tugayapti'
  return 'Aktiv'
}

let timer = null
function debouncedLoad() { clearTimeout(timer); timer = setTimeout(() => load(currentOptions), 400) }
function onOptions(opts) { currentOptions = opts; load(opts) }

async function load(opts = { page: 1, itemsPerPage: 15 }) {
  loading.value = true
  try {
    const params = { page: opts.page, per_page: opts.itemsPerPage, search: search.value }
    if (statusFilter.value) params.status = statusFilter.value
    const [sRes, dashRes] = await Promise.all([
      api.get('/admin/subscriptions', { params }),
      api.get('/admin/dashboard'),
    ])
    subscriptions.value = sRes.data.data || []
    total.value = sRes.data.total || 0
    const s = dashRes.data.stats || {}
    overview.value = {
      active: s.active || 0,
      expiring_soon: 0,
      expired: s.suspended || 0,
      monthly_revenue: s.monthly_revenue || 0,
    }
  } finally { loading.value = false }
}

function openExtend(item) {
  extendTarget.value = item
  extendMonths.value = 1
  extendAmount.value = 150000
  extendDialog.value = true
}

async function doExtend() {
  saving.value = true
  try {
    const startsAt = new Date().toISOString().split('T')[0]
    const endsAt = new Date(Date.now() + extendMonths.value * 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
    await api.post('/admin/subscriptions', {
      tenant_id: extendTarget.value.tenant_id,
      starts_at: startsAt,
      ends_at: endsAt,
      amount: extendAmount.value,
    })
    snackbar.value = { show: true, text: 'Uzaytirildi', color: 'success' }
    extendDialog.value = false
    load(currentOptions)
  } catch {
    snackbar.value = { show: true, text: 'Xatolik', color: 'error' }
  } finally { saving.value = false }
}

onMounted(() => load(currentOptions))
</script>
