<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-4">
      <div class="text-h5 font-weight-bold">Hisobotlar</div>
    </div>

    <!-- Date Filters -->
    <v-card rounded="xl" class="mb-4 pa-4">
      <div class="d-flex flex-wrap gap-3 align-center">
        <v-text-field v-model="dateFrom" type="date" label="Dan" variant="outlined" density="compact" hide-details style="max-width: 160px;" />
        <v-text-field v-model="dateTo" type="date" label="Gacha" variant="outlined" density="compact" hide-details style="max-width: 160px;" />
        <v-btn-group density="compact" variant="outlined">
          <v-btn @click="setRange('today')">Bugun</v-btn>
          <v-btn @click="setRange('week')">Hafta</v-btn>
          <v-btn @click="setRange('month')">Oy</v-btn>
        </v-btn-group>
        <v-btn color="primary" :loading="loading" @click="loadAll">Qidirish</v-btn>
        <v-spacer />
        <v-btn variant="outlined" prepend-icon="mdi-file-excel" :loading="exporting === 'excel'" @click="doExport('excel')">Excel</v-btn>
        <v-btn variant="outlined" prepend-icon="mdi-file-pdf-box" :loading="exporting === 'pdf'" @click="doExport('pdf')">PDF</v-btn>
      </div>
    </v-card>

    <v-tabs v-model="tab" class="mb-4">
      <v-tab value="financial">Moliya</v-tab>
      <v-tab value="visits">Tashriflar</v-tab>
      <v-tab value="doctors">Shifokorlar</v-tab>
      <v-tab value="services">Xizmatlar</v-tab>
      <v-tab value="inventory">Inventar</v-tab>
    </v-tabs>

    <v-window v-model="tab">

      <!-- ── FINANCIAL TAB ── -->
      <v-window-item value="financial">
        <!-- Summary Cards -->
        <v-row class="mb-4">
          <v-col cols="6" sm="3">
            <v-card rounded="xl" color="success" variant="tonal">
              <v-card-text class="pa-4">
                <div class="text-caption mb-1">Daromad</div>
                <div class="text-h5 font-weight-bold">{{ formatMoney(fin.stats?.total_revenue) }}</div>
              </v-card-text>
            </v-card>
          </v-col>
          <v-col cols="6" sm="3">
            <v-card rounded="xl" color="primary" variant="tonal">
              <v-card-text class="pa-4">
                <div class="text-caption mb-1">Bemorlar</div>
                <div class="text-h5 font-weight-bold">{{ fin.stats?.total_patients ?? 0 }}</div>
              </v-card-text>
            </v-card>
          </v-col>
          <v-col cols="6" sm="3">
            <v-card rounded="xl" color="secondary" variant="tonal">
              <v-card-text class="pa-4">
                <div class="text-caption mb-1">Chegirma</div>
                <div class="text-h5 font-weight-bold">{{ formatMoney(fin.stats?.total_discount) }}</div>
              </v-card-text>
            </v-card>
          </v-col>
          <v-col cols="6" sm="3">
            <v-card rounded="xl" color="warning" variant="tonal">
              <v-card-text class="pa-4">
                <div class="text-caption mb-1">O'rtacha/kun</div>
                <div class="text-h5 font-weight-bold">{{ formatMoney(fin.stats?.avg_per_day) }}</div>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>

        <!-- Daily Revenue Chart -->
        <v-card rounded="xl" class="mb-4">
          <v-card-title class="pa-4 pb-0">Kunlik daromad</v-card-title>
          <v-card-text>
            <apexchart
              v-if="finChart.series[0].data.length"
              type="bar"
              height="260"
              :options="finChart.options"
              :series="finChart.series"
            />
            <div v-else class="text-center py-10 text-medium-emphasis">Ma'lumot yo'q</div>
          </v-card-text>
        </v-card>

        <!-- Daily table -->
        <v-card rounded="xl">
          <v-card-title class="pa-4 pb-0">Kunlik tafsilot</v-card-title>
          <v-data-table :headers="dailyHeaders" :items="fin.daily || []" :loading="loading" density="comfortable">
            <template #item.revenue="{ item }">{{ formatMoney(item.revenue) }}</template>
            <template #item.discount="{ item }">{{ formatMoney(item.discount) }}</template>
          </v-data-table>
        </v-card>
      </v-window-item>

      <!-- ── VISITS TAB ── -->
      <v-window-item value="visits">
        <v-row class="mb-4">
          <v-col cols="6" sm="3">
            <v-card rounded="xl" color="primary" variant="tonal">
              <v-card-text class="pa-4">
                <div class="text-caption mb-1">Jami tashriflar</div>
                <div class="text-h5 font-weight-bold">{{ fin.stats?.total_visits ?? 0 }}</div>
              </v-card-text>
            </v-card>
          </v-col>
          <v-col cols="6" sm="3">
            <v-card rounded="xl" color="success" variant="tonal">
              <v-card-text class="pa-4">
                <div class="text-caption mb-1">To'langan</div>
                <div class="text-h5 font-weight-bold">{{ fin.stats?.paid_visits ?? 0 }}</div>
              </v-card-text>
            </v-card>
          </v-col>
          <v-col cols="6" sm="3">
            <v-card rounded="xl" color="warning" variant="tonal">
              <v-card-text class="pa-4">
                <div class="text-caption mb-1">To'lanmagan</div>
                <div class="text-h5 font-weight-bold">{{ fin.stats?.unpaid_visits ?? 0 }}</div>
              </v-card-text>
            </v-card>
          </v-col>
          <v-col cols="6" sm="3">
            <v-card rounded="xl" color="secondary" variant="tonal">
              <v-card-text class="pa-4">
                <div class="text-caption mb-1">Daromad</div>
                <div class="text-h5 font-weight-bold">{{ formatMoney(fin.stats?.total_revenue) }}</div>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>

        <!-- Payment method breakdown -->
        <v-row class="mb-4">
          <v-col cols="12" md="5">
            <v-card rounded="xl">
              <v-card-title class="pa-4 pb-0">To'lov usullari</v-card-title>
              <v-card-text>
                <div v-for="pm in (fin.stats?.payment_breakdown || [])" :key="pm.payment_method" class="d-flex justify-space-between align-center mb-3">
                  <div class="d-flex align-center gap-2">
                    <v-icon :color="pm.payment_method === 'cash' ? 'success' : pm.payment_method === 'card' ? 'primary' : 'secondary'" size="20">
                      {{ pm.payment_method === 'cash' ? 'mdi-cash' : pm.payment_method === 'card' ? 'mdi-credit-card' : 'mdi-shield-check' }}
                    </v-icon>
                    <span>{{ pm.payment_method === 'cash' ? 'Naqd' : pm.payment_method === 'card' ? 'Karta' : "Sug'urta" }}</span>
                  </div>
                  <div class="text-right">
                    <div class="font-weight-bold">{{ formatMoney(pm.total) }}</div>
                    <div class="text-caption text-medium-emphasis">{{ pm.count }} ta tashrif</div>
                  </div>
                </div>
                <div v-if="!fin.stats?.payment_breakdown?.length" class="text-center py-4 text-medium-emphasis text-body-2">Ma'lumot yo'q</div>
              </v-card-text>
            </v-card>
          </v-col>
          <v-col cols="12" md="7">
            <v-card rounded="xl">
              <v-card-title class="pa-4 pb-0">Kunlik tashriflar</v-card-title>
              <v-data-table :headers="dailyVisitHeaders" :items="fin.daily || []" :loading="loading" density="comfortable">
                <template #item.revenue="{ item }">{{ formatMoney(item.revenue) }}</template>
              </v-data-table>
            </v-card>
          </v-col>
        </v-row>

        <!-- Visits list -->
        <v-card rounded="xl">
          <v-card-title class="pa-4 pb-0">Tashriflar ro'yxati</v-card-title>
          <v-data-table :headers="visitListHeaders" :items="visitsList" :loading="loading" density="comfortable" :items-per-page="20">
            <template #item.visited_at="{ item }">{{ formatDate(item.visited_at) }}</template>
            <template #item.patient="{ item }">{{ item.patient?.full_name }}</template>
            <template #item.doctor="{ item }">
              Dr. {{ item.doctor?.user?.name }}
              <span v-if="item.doctor?.specialization" class="text-caption text-medium-emphasis">({{ item.doctor.specialization }})</span>
            </template>
            <template #item.total_amount="{ item }">{{ formatMoney(item.total_amount) }}</template>
            <template #item.is_paid="{ item }">
              <v-chip :color="item.is_paid ? 'success' : 'warning'" size="small" label>
                {{ item.is_paid ? "To'landi" : "Kutilmoqda" }}
              </v-chip>
            </template>
          </v-data-table>
        </v-card>
      </v-window-item>

      <!-- ── DOCTORS TAB ── -->
      <v-window-item value="doctors">
        <v-card rounded="xl">
          <v-card-title class="pa-4 pb-0">Shifokorlar natijalari</v-card-title>
          <v-data-table :headers="docHeaders" :items="doctors" :loading="loading" density="comfortable">
            <template #item.name="{ item }">
              <div class="font-weight-medium">Dr. {{ item.doctor?.user?.name }}</div>
              <div class="text-caption text-medium-emphasis">{{ item.doctor?.specialty }}</div>
            </template>
            <template #item.revenue="{ item }">{{ formatMoney(item.revenue) }}</template>
            <template #item.avg_check="{ item }">{{ formatMoney(item.avg_check) }}</template>
          </v-data-table>
        </v-card>
      </v-window-item>

      <!-- ── SERVICES TAB ── -->
      <v-window-item value="services">
        <v-row>
          <v-col cols="12" md="7">
            <v-card rounded="xl">
              <v-card-title class="pa-4 pb-0">Top xizmatlar</v-card-title>
              <v-data-table :headers="svcHeaders" :items="services" :loading="loading" density="comfortable">
                <template #item.revenue="{ item }">{{ formatMoney(item.revenue) }}</template>
              </v-data-table>
            </v-card>
          </v-col>
          <v-col cols="12" md="5">
            <v-card rounded="xl">
              <v-card-title class="pa-4 pb-0">Kategoriyalar</v-card-title>
              <v-card-text>
                <apexchart
                  v-if="svcCatChart.series.length"
                  type="donut"
                  height="260"
                  :options="svcCatChart.options"
                  :series="svcCatChart.series"
                />
                <div v-else class="text-center py-10 text-medium-emphasis">Ma'lumot yo'q</div>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
      </v-window-item>

      <!-- ── INVENTORY TAB ── -->
      <v-window-item value="inventory">
        <v-row class="mb-4">
          <v-col cols="12" md="6">
            <v-card rounded="xl">
              <v-card-title class="pa-4 pb-0">Eng ko'p ishlatilganlar</v-card-title>
              <v-data-table :headers="topUsedHeaders" :items="topUsed" :loading="loading" density="comfortable" :items-per-page="8">
                <template #item.total_used="{ item }">
                  <span class="font-weight-bold">{{ item.total_used }}</span>
                </template>
              </v-data-table>
            </v-card>
          </v-col>
          <v-col cols="12" md="6">
            <v-card rounded="xl" class="mb-4">
              <v-card-text class="pa-4">
                <div class="d-flex justify-space-around text-center">
                  <div>
                    <div class="text-h4 font-weight-bold text-success">{{ invStats.totalIn }}</div>
                    <div class="text-caption text-medium-emphasis">Jami kirim</div>
                  </div>
                  <v-divider vertical />
                  <div>
                    <div class="text-h4 font-weight-bold text-warning">{{ invStats.totalOut }}</div>
                    <div class="text-caption text-medium-emphasis">Jami chiqim</div>
                  </div>
                  <v-divider vertical />
                  <div>
                    <div class="text-h4 font-weight-bold text-primary">{{ invTransactions.length }}</div>
                    <div class="text-caption text-medium-emphasis">Operatsiya</div>
                  </div>
                </div>
              </v-card-text>
            </v-card>
            <v-card rounded="xl">
              <v-card-title class="pa-4 pb-0">Inventar holati (kritik)</v-card-title>
              <v-data-table :headers="lowStockHeaders" :items="lowStock" :loading="loading" density="compact" :items-per-page="6">
                <template #item.status="{ item }">
                  <v-chip :color="item.quantity <= item.min_quantity ? 'error' : 'warning'" size="small" label>
                    {{ item.quantity <= item.min_quantity ? 'Kritik' : 'Kam' }}
                  </v-chip>
                </template>
              </v-data-table>
            </v-card>
          </v-col>
        </v-row>

        <!-- Transactions table -->
        <v-card rounded="xl">
          <v-card-title class="pa-4 pb-0">Kirim/Chiqim tarixi</v-card-title>
          <v-data-table :headers="txHeaders" :items="invTransactions" :loading="loading" density="comfortable">
            <template #item.type="{ item }">
              <v-chip :color="item.type === 'in' ? 'success' : 'warning'" size="small" label>
                {{ item.type === 'in' ? 'Kirim' : 'Chiqim' }}
              </v-chip>
            </template>
            <template #item.item_name="{ item }">{{ item.item?.name }}</template>
            <template #item.performer="{ item }">{{ item.performer?.name || '—' }}</template>
            <template #item.created_at="{ item }">{{ formatDate(item.created_at) }}</template>
          </v-data-table>
        </v-card>
      </v-window-item>
    </v-window>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color">{{ snackbar.text }}</v-snackbar>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { tenantApi } from '@/plugins/axios'
import dayjs from 'dayjs'

const loading  = ref(false)
const exporting = ref('')
const tab      = ref('financial')
const snackbar = ref({ show: false, text: '', color: 'success' })

const dateFrom = ref(dayjs().startOf('month').format('YYYY-MM-DD'))
const dateTo   = ref(dayjs().format('YYYY-MM-DD'))

// Data
const fin             = ref({ stats: {}, daily: [] })
const doctors         = ref([])
const services        = ref([])
const svcCategories   = ref([])
const invTransactions = ref([])
const topUsed         = ref([])
const lowStock        = ref([])
const visitsList      = ref([])

// Charts
const finChart    = ref({ series: [{ name: 'Daromad', data: [] }], options: buildBarOptions([]) })
const svcCatChart = ref({ series: [], options: buildDonutOptions([]) })

function buildBarOptions(categories) {
  return {
    chart: { toolbar: { show: false } },
    colors: ['#1565C0'],
    xaxis: { categories, labels: { style: { colors: '#90A4AE' } } },
    yaxis: { labels: { formatter: v => (v / 1000).toFixed(0) + 'K' } },
    tooltip: { y: { formatter: v => Number(v).toLocaleString('uz-UZ') + " so'm" } },
  }
}

function buildDonutOptions(labels) {
  return {
    chart: { type: 'donut' },
    colors: ['#1565C0', '#00BFA5', '#FF6F00', '#7B1FA2', '#2E7D32', '#F44336'],
    labels,
    legend: { position: 'bottom' },
    tooltip: { y: { formatter: v => Number(v).toLocaleString('uz-UZ') + " so'm" } },
  }
}

const invStats = computed(() => {
  const totalIn  = invTransactions.value.filter(t => t.type === 'in').reduce((s, t) => s + Number(t.quantity), 0)
  const totalOut = invTransactions.value.filter(t => t.type === 'out').reduce((s, t) => s + Number(t.quantity), 0)
  return { totalIn: totalIn.toFixed(0), totalOut: totalOut.toFixed(0) }
})

// Table headers
const dailyHeaders = [
  { title: 'Sana',     key: 'date',     width: 110 },
  { title: 'Bemorlar', key: 'patients', width: 100 },
  { title: 'Daromad',  key: 'revenue',  width: 150 },
  { title: 'Chegirma', key: 'discount', width: 130 },
]
const docHeaders = [
  { title: 'Shifokor',     key: 'name' },
  { title: 'Tashriflar',   key: 'visits_count', width: 110 },
  { title: 'Daromad',      key: 'revenue',      width: 150 },
  { title: "O'rta chek",   key: 'avg_check',    width: 130 },
]
const svcHeaders = [
  { title: 'Xizmat',     key: 'name' },
  { title: 'Tashriflar', key: 'count',   width: 110 },
  { title: 'Daromad',    key: 'revenue', width: 150 },
]
const txHeaders = [
  { title: 'Sana',       key: 'created_at', width: 130 },
  { title: 'Mahsulot',   key: 'item_name' },
  { title: 'Tur',        key: 'type',       width: 90 },
  { title: 'Miqdor',     key: 'quantity',   width: 90 },
  { title: 'Kim',        key: 'performer',  width: 130 },
]
const topUsedHeaders = [
  { title: 'Mahsulot', key: 'name' },
  { title: "Jami ishlatildi", key: 'total_used', width: 150 },
]
const lowStockHeaders = [
  { title: 'Mahsulot', key: 'name' },
  { title: 'Qoldi',    key: 'quantity', width: 80 },
  { title: 'Min',      key: 'min_quantity', width: 70 },
  { title: 'Holat',    key: 'status',   width: 90 },
]
const dailyVisitHeaders = [
  { title: 'Sana',     key: 'date',     width: 110 },
  { title: 'Bemorlar', key: 'patients', width: 100 },
  { title: 'Daromad',  key: 'revenue',  width: 150 },
  { title: 'Chegirma', key: 'discount', width: 130 },
]
const visitListHeaders = [
  { title: 'Sana',     key: 'visited_at', width: 140 },
  { title: 'Bemor',    key: 'patient' },
  { title: 'Shifokor', key: 'doctor' },
  { title: 'Summa',    key: 'total_amount', width: 140 },
  { title: "To'lov",   key: 'is_paid', width: 110 },
]

function formatMoney(v) { return v ? Number(v).toLocaleString('uz-UZ') + " so'm" : '0' }
function formatDate(d)  { return d ? dayjs(d).format('DD.MM.YYYY HH:mm') : '' }

function setRange(r) {
  if (r === 'today') { dateFrom.value = dayjs().format('YYYY-MM-DD'); dateTo.value = dayjs().format('YYYY-MM-DD') }
  else if (r === 'week')  { dateFrom.value = dayjs().startOf('week').format('YYYY-MM-DD'); dateTo.value = dayjs().format('YYYY-MM-DD') }
  else if (r === 'month') { dateFrom.value = dayjs().startOf('month').format('YYYY-MM-DD'); dateTo.value = dayjs().format('YYYY-MM-DD') }
  loadAll()
}

async function loadAll() {
  loading.value = true
  const params = { from: dateFrom.value, to: dateTo.value }
  try {
    const [finRes, docRes, svcRes, invRes, lowRes] = await Promise.all([
      tenantApi.get('/reports/financial', { params }),
      tenantApi.get('/reports/doctors',   { params }),
      tenantApi.get('/reports/services',  { params }),
      tenantApi.get('/reports/inventory', { params }),
      tenantApi.get('/inventory/low-stock'),
    ])

    // Financial
    fin.value = finRes.data
    if (fin.value.daily?.length) {
      finChart.value = {
        series:  [{ name: 'Daromad', data: fin.value.daily.map(d => Number(d.revenue) || 0) }],
        options: buildBarOptions(fin.value.daily.map(d => d.date)),
      }
    }

    // Doctors
    doctors.value = docRes.data.doctors || []

    // Services
    services.value     = svcRes.data.services   || []
    svcCategories.value = svcRes.data.categories || []
    if (svcCategories.value.length) {
      svcCatChart.value = {
        series:  svcCategories.value.map(c => Number(c.revenue) || 0),
        options: buildDonutOptions(svcCategories.value.map(c => c.name)),
      }
    }

    // Inventory
    invTransactions.value = invRes.data.transactions?.data || invRes.data.transactions || []
    topUsed.value         = invRes.data.topUsed || []
    lowStock.value        = lowRes.data || []

  } catch (e) {
    console.error('Reports load error:', e)
    snackbar.value = { show: true, text: 'Ma\'lumot yuklanmadi', color: 'error' }
  } finally {
    loading.value = false
  }

  // Load visits separately so a failure here doesn't break other tabs
  try {
    const visRes = await tenantApi.get('/visits', { params: { from: dateFrom.value, to: dateTo.value, per_page: 100 } })
    visitsList.value = visRes.data.data || []
  } catch (e) {
    console.error('Visits load error:', e)
  }
}

async function doExport(format) {
  const typeMap = { financial: 'financial', doctors: 'doctors', services: 'services', inventory: 'inventory' }
  const type = typeMap[tab.value] || 'financial'
  exporting.value = format
  try {
    const res = await tenantApi.get('/reports/export', {
      params: { from: dateFrom.value, to: dateTo.value, type, format },
      responseType: 'blob',
    })
    const ext = format === 'excel' ? 'xlsx' : 'pdf'
    const url = URL.createObjectURL(res.data)
    const a   = document.createElement('a')
    a.href     = url
    a.download = `hisobot_${type}_${dateFrom.value}_${dateTo.value}.${ext}`
    a.click()
    URL.revokeObjectURL(url)
  } catch {
    snackbar.value = { show: true, text: 'Export xatoligi', color: 'error' }
  } finally {
    exporting.value = ''
  }
}

onMounted(loadAll)
</script>
