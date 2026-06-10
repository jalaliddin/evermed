<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-6">
      <div class="text-h5 font-weight-bold">Hisobotlar</div>
      <div class="d-flex gap-2">
        <v-btn variant="outlined" prepend-icon="mdi-file-excel" :loading="exporting" @click="exportExcel">Excel</v-btn>
        <v-btn variant="outlined" prepend-icon="mdi-file-pdf-box" :loading="exporting" @click="exportPdf">PDF</v-btn>
      </div>
    </div>

    <!-- Filters -->
    <v-card rounded="xl" class="mb-4 pa-4">
      <div class="d-flex flex-wrap gap-3 align-center">
        <v-text-field v-model="dateFrom" type="date" label="Dan" variant="outlined" density="compact" hide-details style="max-width: 160px;" />
        <v-text-field v-model="dateTo" type="date" label="Gacha" variant="outlined" density="compact" hide-details style="max-width: 160px;" />
        <v-btn-group density="compact" variant="outlined">
          <v-btn @click="setRange('today')">Bugun</v-btn>
          <v-btn @click="setRange('week')">Hafta</v-btn>
          <v-btn @click="setRange('month')">Oy</v-btn>
        </v-btn-group>
        <v-btn color="primary" :loading="loading" @click="load">Qidirish</v-btn>
      </div>
    </v-card>

    <!-- Summary Cards -->
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

    <!-- Charts -->
    <v-row class="mb-4">
      <v-col cols="12" md="8">
        <v-card rounded="xl">
          <v-card-title class="pa-4 pb-0">Kunlik daromad</v-card-title>
          <v-card-text>
            <apexchart v-if="revenueChart.series[0].data.length" type="bar" :options="revenueChart.options" :series="revenueChart.series" height="250" />
            <div v-else class="text-center py-8 text-medium-emphasis">Ma'lumot yo'q</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="4">
        <v-card rounded="xl">
          <v-card-title class="pa-4 pb-0">To'lov usullari</v-card-title>
          <v-card-text>
            <apexchart v-if="paymentChart.series.length" type="donut" :options="paymentChart.options" :series="paymentChart.series" height="250" />
            <div v-else class="text-center py-8 text-medium-emphasis">Ma'lumot yo'q</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Top Services -->
    <v-card rounded="xl" class="mb-4">
      <v-card-title class="pa-4 pb-0">Top xizmatlar</v-card-title>
      <v-data-table :headers="svcHeaders" :items="report.topServices || []" :loading="loading" density="compact" :items-per-page="10">
        <template #item.revenue="{ item }">{{ formatMoney(item.revenue) }}</template>
      </v-data-table>
    </v-card>

    <!-- Top Doctors -->
    <v-card rounded="xl">
      <v-card-title class="pa-4 pb-0">Shifokorlar natijalari</v-card-title>
      <v-data-table :headers="docHeaders" :items="report.topDoctors || []" :loading="loading" density="compact" :items-per-page="10">
        <template #item.revenue="{ item }">{{ formatMoney(item.revenue) }}</template>
      </v-data-table>
    </v-card>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color">{{ snackbar.text }}</v-snackbar>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { tenantApi } from '@/plugins/axios'
import dayjs from 'dayjs'
import VueApexCharts from 'vue3-apexcharts'
const apexchart = VueApexCharts

const loading = ref(false)
const exporting = ref(false)
const dateFrom = ref(dayjs().startOf('month').format('YYYY-MM-DD'))
const dateTo = ref(dayjs().format('YYYY-MM-DD'))
const report = ref({})
const snackbar = ref({ show: false, text: '', color: 'success' })

function setRange(r) {
  if (r === 'today') { dateFrom.value = dayjs().format('YYYY-MM-DD'); dateTo.value = dayjs().format('YYYY-MM-DD') }
  else if (r === 'week') { dateFrom.value = dayjs().startOf('week').format('YYYY-MM-DD'); dateTo.value = dayjs().format('YYYY-MM-DD') }
  else if (r === 'month') { dateFrom.value = dayjs().startOf('month').format('YYYY-MM-DD'); dateTo.value = dayjs().format('YYYY-MM-DD') }
  load()
}

function formatMoney(v) { return v ? Number(v).toLocaleString('uz-UZ') + " so'm" : '0' }

const stats = computed(() => [
  { key: 'revenue', label: 'Daromad', value: formatMoney(report.value.total_revenue), color: 'success' },
  { key: 'visits', label: 'Tashriflar', value: report.value.total_visits || 0, color: 'primary' },
  { key: 'patients', label: 'Bemorlar', value: report.value.unique_patients || 0, color: 'secondary' },
  { key: 'avg', label: "O'rta chek", value: formatMoney(report.value.avg_check), color: 'warning' },
])

const revenueChart = computed(() => ({
  options: {
    chart: { toolbar: { show: false } },
    xaxis: { categories: (report.value.daily || []).map(d => d.date) },
    colors: ['#1565C0'],
  },
  series: [{ name: 'Daromad', data: (report.value.daily || []).map(d => d.revenue) }],
}))

const paymentChart = computed(() => ({
  options: {
    labels: (report.value.paymentMethods || []).map(p => p.method),
    colors: ['#1565C0', '#00BFA5', '#FF6B6B'],
    legend: { position: 'bottom' },
  },
  series: (report.value.paymentMethods || []).map(p => Number(p.total)),
}))

const svcHeaders = [
  { title: 'Xizmat', key: 'name' },
  { title: 'Tashriflar', key: 'visits_count', width: 100 },
  { title: 'Daromad', key: 'revenue', width: 150 },
]
const docHeaders = [
  { title: 'Shifokor', key: 'name' },
  { title: 'Tashriflar', key: 'visits_count', width: 100 },
  { title: 'Daromad', key: 'revenue', width: 150 },
]

async function load() {
  loading.value = true
  try {
    const params = { from: dateFrom.value, to: dateTo.value }
    const [finRes, svcRes, docRes] = await Promise.all([
      tenantApi.get('/reports/financial', { params }),
      tenantApi.get('/reports/services', { params }),
      tenantApi.get('/reports/doctors', { params }),
    ])
    const fin = finRes.data
    report.value = {
      total_revenue: fin.stats?.total_revenue,
      total_visits: fin.daily?.reduce((s, d) => s + (d.patients || 0), 0),
      unique_patients: fin.stats?.total_patients,
      avg_check: fin.stats?.avg_per_day,
      daily: fin.daily || [],
      paymentMethods: [],
      topServices: svcRes.data.services || [],
      topDoctors: (docRes.data.doctors || []).map(d => ({ name: `Dr. ${d.doctor?.user?.name}`, visits_count: d.visits_count, revenue: d.revenue })),
    }
  } finally { loading.value = false }
}

async function exportExcel() {
  exporting.value = true
  try {
    const res = await tenantApi.get('/reports/export', { params: { from: dateFrom.value, to: dateTo.value, type: 'financial', format: 'excel' }, responseType: 'blob' })
    const url = URL.createObjectURL(res.data)
    const a = document.createElement('a'); a.href = url; a.download = `report_${dateFrom.value}_${dateTo.value}.xlsx`; a.click()
    URL.revokeObjectURL(url)
  } catch { snackbar.value = { show: true, text: 'Xatolik', color: 'error' } }
  finally { exporting.value = false }
}

async function exportPdf() {
  exporting.value = true
  try {
    const res = await tenantApi.get('/reports/export', { params: { from: dateFrom.value, to: dateTo.value, type: 'financial', format: 'pdf' }, responseType: 'blob' })
    const url = URL.createObjectURL(res.data)
    window.open(url, '_blank')
    URL.revokeObjectURL(url)
  } catch { snackbar.value = { show: true, text: 'Xatolik', color: 'error' } }
  finally { exporting.value = false }
}

onMounted(load)
</script>
