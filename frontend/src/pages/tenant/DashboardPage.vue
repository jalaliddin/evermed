<template>
  <div>
    <!-- Page Title -->
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <div class="text-h5 font-weight-bold">Dashboard</div>
        <div class="text-body-2 text-medium-emphasis">{{ today }}</div>
      </div>
      <v-btn color="primary" prepend-icon="mdi-plus" to="/visits/new">Yangi tashrif</v-btn>
    </div>

    <!-- Stats Cards -->
    <v-row class="mb-6">
      <v-col cols="12" sm="6" lg="3" v-for="stat in stats" :key="stat.label">
        <v-card :color="stat.color" variant="tonal" rounded="xl" class="pa-4">
          <div class="d-flex align-center justify-space-between mb-3">
            <v-icon :color="stat.iconColor" size="28">{{ stat.icon }}</v-icon>
            <v-chip :color="stat.color" size="small" label>bugun</v-chip>
          </div>
          <div class="text-h4 font-weight-bold">{{ stat.value }}</div>
          <div class="text-body-2 text-medium-emphasis mt-1">{{ stat.label }}</div>
        </v-card>
      </v-col>
    </v-row>

    <!-- Charts Row -->
    <v-row class="mb-6">
      <v-col cols="12" lg="8">
        <v-card rounded="xl">
          <v-card-title class="pa-4 pb-0">
            <v-icon class="mr-2" color="primary">mdi-chart-line</v-icon>
            Oxirgi 7 kun daromad
          </v-card-title>
          <v-card-text class="pa-4">
            <apexchart
              v-if="revenueChart.series[0].data.length"
              type="area"
              height="240"
              :options="revenueChart.options"
              :series="revenueChart.series"
            />
            <v-skeleton-loader v-else type="image" height="240" />
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" lg="4">
        <v-card rounded="xl" height="100%">
          <v-card-title class="pa-4 pb-0">
            <v-icon class="mr-2" color="secondary">mdi-chart-donut</v-icon>
            Xizmat turlari
          </v-card-title>
          <v-card-text class="pa-4">
            <apexchart
              v-if="serviceChart.series.length"
              type="donut"
              height="240"
              :options="serviceChart.options"
              :series="serviceChart.series"
            />
            <v-skeleton-loader v-else type="image" height="240" />
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Bottom Row -->
    <v-row>
      <!-- Today's Appointments -->
      <v-col cols="12" lg="7">
        <v-card rounded="xl">
          <v-card-title class="pa-4 pb-0 d-flex align-center justify-space-between">
            <div>
              <v-icon class="mr-2">mdi-calendar-today</v-icon>
              Bugungi qabullar
            </div>
            <v-btn variant="text" size="small" to="/appointments">Barchasi</v-btn>
          </v-card-title>
          <v-data-table
            :headers="appointmentHeaders"
            :items="todayAppointments"
            :loading="loading"
            density="compact"
            hide-default-footer
            :items-per-page="8"
          >
            <template #item.status="{ item }">
              <v-chip :color="statusColor(item.status)" size="small" label>
                {{ statusLabel(item.status) }}
              </v-chip>
            </template>
            <template #item.scheduled_at="{ item }">
              {{ formatTime(item.scheduled_at) }}
            </template>
            <template #item.patient="{ item }">
              {{ item.patient?.full_name }}
            </template>
            <template #item.doctor="{ item }">
              Dr. {{ item.doctor?.user?.name }}
            </template>
          </v-data-table>
        </v-card>
      </v-col>

      <!-- Recent Notifications -->
      <v-col cols="12" lg="5">
        <v-card rounded="xl" height="100%">
          <v-card-title class="pa-4 pb-0 d-flex align-center justify-space-between">
            <div>
              <v-icon class="mr-2">mdi-bell-outline</v-icon>
              Bildirishnomalar
            </div>
            <v-btn variant="text" size="small" to="/notifications">Barchasi</v-btn>
          </v-card-title>
          <v-list lines="two" class="pa-2">
            <template v-if="notifications.length">
              <v-list-item
                v-for="notif in notifications"
                :key="notif.id"
                :prepend-icon="notifIcon(notif.type)"
                rounded="lg"
              >
                <v-list-item-title>{{ notif.title }}</v-list-item-title>
                <v-list-item-subtitle>{{ notif.body }}</v-list-item-subtitle>
                <template #append>
                  <v-icon v-if="!notif.is_read" color="primary" size="8">mdi-circle</v-icon>
                </template>
              </v-list-item>
            </template>
            <v-list-item v-else>
              <v-list-item-title class="text-medium-emphasis">Yangi bildirishnomalar yo'q</v-list-item-title>
            </v-list-item>
          </v-list>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { tenantApi } from '@/plugins/axios'
import dayjs from 'dayjs'

const loading = ref(false)
const todayAppointments = ref([])
const notifications = ref([])
const dashStats = ref({})

const today = computed(() => dayjs().format('DD MMMM YYYY, dddd'))

const stats = computed(() => [
  { label: "Bugungi bemorlar", value: dashStats.value.today_patients ?? '-', icon: 'mdi-account-group', color: 'primary', iconColor: 'primary' },
  { label: "Bugungi qabullar", value: dashStats.value.today_appointments ?? '-', icon: 'mdi-calendar-check', color: 'secondary', iconColor: 'secondary' },
  { label: "Bugungi daromad", value: formatMoney(dashStats.value.today_revenue), icon: 'mdi-cash-multiple', color: 'success', iconColor: 'success' },
  { label: "Kam inventar", value: dashStats.value.low_stock_count ?? 0, icon: 'mdi-package-variant-closed-remove', color: dashStats.value.low_stock_count > 0 ? 'error' : 'info', iconColor: dashStats.value.low_stock_count > 0 ? 'error' : 'info' },
])

const revenueChart = ref({
  series: [{ name: "Daromad", data: [] }],
  options: {
    chart: { type: 'area', toolbar: { show: false }, sparkline: { enabled: false } },
    colors: ['#1565C0'],
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05 } },
    stroke: { curve: 'smooth', width: 3 },
    xaxis: { categories: [], labels: { style: { colors: '#90A4AE' } } },
    yaxis: { labels: { formatter: v => (v / 1000).toFixed(0) + 'K' } },
    tooltip: { y: { formatter: v => v.toLocaleString('uz-UZ') + " so'm" } },
    grid: { borderColor: '#F0F0F0' },
  },
})

const serviceChart = ref({
  series: [],
  options: {
    chart: { type: 'donut' },
    colors: ['#1565C0', '#00BFA5', '#FF6F00', '#7B1FA2', '#2E7D32'],
    labels: [],
    legend: { position: 'bottom' },
    plotOptions: { pie: { donut: { size: '65%' } } },
  },
})

const appointmentHeaders = [
  { title: 'Vaqt', key: 'scheduled_at', width: 80 },
  { title: 'Bemor', key: 'patient' },
  { title: 'Shifokor', key: 'doctor' },
  { title: 'Holat', key: 'status', width: 120 },
]

function formatMoney(val) {
  if (!val) return '0'
  if (val >= 1000000) return (val / 1000000).toFixed(1) + 'M'
  if (val >= 1000) return (val / 1000).toFixed(0) + 'K'
  return val
}

function formatTime(dt) { return dt ? dayjs(dt).format('HH:mm') : '' }

function statusColor(s) {
  return { pending: 'warning', confirmed: 'info', in_progress: 'primary', completed: 'success', cancelled: 'error' }[s] || 'grey'
}
function statusLabel(s) {
  return { pending: 'Kutilmoqda', confirmed: 'Tasdiqlangan', in_progress: 'Qabulda', completed: 'Tugadi', cancelled: 'Bekor' }[s] || s
}
function notifIcon(t) {
  return { low_stock: 'mdi-package-variant-closed-remove', payment: 'mdi-cash', appointment: 'mdi-calendar' }[t] || 'mdi-bell'
}

async function loadDashboard() {
  loading.value = true
  try {
    const res = await tenantApi.get('/dashboard')
    const data = res.data

    dashStats.value = data.stats
    todayAppointments.value = data.today_appointments || []
    notifications.value = data.notifications || []

    // Revenue chart
    if (data.revenue_chart?.length) {
      revenueChart.value.series[0].data = data.revenue_chart.map(r => r.revenue || 0)
      revenueChart.value.options = {
        ...revenueChart.value.options,
        xaxis: { categories: data.revenue_chart.map(r => dayjs(r.date).format('DD/MM')) },
      }
    }

    // Services chart
    if (data.services_chart?.length) {
      serviceChart.value.series = data.services_chart.map(s => parseFloat(s.total) || 0)
      serviceChart.value.options = { ...serviceChart.value.options, labels: data.services_chart.map(s => s.name) }
    }
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

onMounted(loadDashboard)
</script>
