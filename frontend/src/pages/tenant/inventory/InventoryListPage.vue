<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-6">
      <div class="text-h5 font-weight-bold">Inventar</div>
      <div class="d-flex gap-2">
        <v-btn variant="outlined" prepend-icon="mdi-file-chart" to="/reports" color="secondary">Hisobot</v-btn>
        <v-btn color="primary" prepend-icon="mdi-plus" @click="openDialog()">Yangi mahsulot</v-btn>
      </div>
    </div>

    <v-card rounded="xl">
      <!-- Filters -->
      <div class="d-flex flex-wrap gap-3 pa-4">
        <v-text-field v-model="search" placeholder="Qidirish..." prepend-inner-icon="mdi-magnify" variant="outlined" density="compact" hide-details clearable style="max-width: 280px;" @update:model-value="debouncedLoad" />
        <v-chip-group v-model="statusFilter" @update:model-value="load">
          <v-chip value="" variant="outlined">Barchasi</v-chip>
          <v-chip value="critical" color="error" variant="outlined">Kritik</v-chip>
          <v-chip value="low" color="warning" variant="outlined">Kam</v-chip>
        </v-chip-group>
      </div>

      <v-data-table :headers="headers" :items="items" :loading="loading" density="comfortable">
        <template #item.name="{ item }">
          <div class="font-weight-medium">{{ item.name }}</div>
          <div class="text-caption text-medium-emphasis">{{ item.category }}</div>
        </template>
        <template #item.quantity="{ item }">
          <div class="d-flex align-center gap-2">
            <v-progress-linear
              :model-value="getProgress(item)"
              :color="statusColor(item)"
              height="6"
              rounded
              style="min-width: 60px;"
            />
            <span class="font-weight-bold">{{ item.quantity }}</span>
            <span class="text-caption text-medium-emphasis">{{ item.unit }}</span>
          </div>
        </template>
        <template #item.status="{ item }">
          <v-chip :color="statusColor(item)" size="small" label>{{ statusLabel(item) }}</v-chip>
        </template>
        <template #item.price_per_unit="{ item }">{{ formatMoney(item.price_per_unit) }}</template>
        <template #item.actions="{ item }">
          <v-btn icon="mdi-plus-box-outline" size="small" variant="text" color="success" @click="openStockDialog(item, 'in')" title="Kirim" />
          <v-btn icon="mdi-minus-box-outline" size="small" variant="text" color="warning" @click="openStockDialog(item, 'out')" title="Chiqim" />
          <v-btn icon="mdi-pencil-outline" size="small" variant="text" @click="openDialog(item)" />
        </template>
      </v-data-table>
    </v-card>

    <!-- Item Dialog -->
    <v-dialog v-model="itemDialog" max-width="520">
      <v-card rounded="xl">
        <v-card-title class="pa-4">{{ editItem ? 'Tahrirlash' : 'Yangi mahsulot' }}</v-card-title>
        <v-card-text class="pa-4">
          <v-row>
            <v-col cols="12"><v-text-field v-model="iForm.name" label="Nomi *" variant="outlined" /></v-col>
            <v-col cols="12" sm="6"><v-text-field v-model="iForm.category" label="Kategoriya" variant="outlined" /></v-col>
            <v-col cols="12" sm="6"><v-text-field v-model="iForm.unit" label="Birlik" variant="outlined" placeholder="dona, gramm, ml..." /></v-col>
            <v-col cols="12" sm="6"><v-text-field v-model.number="iForm.quantity" label="Miqdor" type="number" variant="outlined" /></v-col>
            <v-col cols="12" sm="6"><v-text-field v-model.number="iForm.min_quantity" label="Min miqdor" type="number" variant="outlined" /></v-col>
            <v-col cols="12" sm="6"><v-text-field v-model.number="iForm.price_per_unit" label="Birlik narxi" type="number" variant="outlined" /></v-col>
            <v-col cols="12" sm="6"><v-text-field v-model="iForm.supplier" label="Yetkazib beruvchi" variant="outlined" /></v-col>
          </v-row>
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-spacer />
          <v-btn @click="itemDialog = false">Bekor</v-btn>
          <v-btn color="primary" :loading="saving" @click="saveItem">Saqlash</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Stock In/Out Dialog -->
    <v-dialog v-model="stockDialog" max-width="400">
      <v-card rounded="xl">
        <v-card-title class="pa-4">{{ stockType === 'in' ? 'Kirim' : 'Chiqim' }} — {{ stockItem?.name }}</v-card-title>
        <v-card-text class="pa-4">
          <v-text-field v-model.number="stockQty" label="Miqdor *" type="number" variant="outlined" class="mb-3" />
          <v-textarea v-model="stockNotes" label="Izoh" variant="outlined" rows="2" />
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-spacer />
          <v-btn @click="stockDialog = false">Bekor</v-btn>
          <v-btn :color="stockType === 'in' ? 'success' : 'warning'" :loading="saving" @click="doStock">
            {{ stockType === 'in' ? 'Kirim' : 'Chiqim' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color">{{ snackbar.text }}</v-snackbar>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { tenantApi } from '@/plugins/axios'

const items = ref([])
const loading = ref(false)
const saving = ref(false)
const search = ref('')
const statusFilter = ref('')
const itemDialog = ref(false)
const stockDialog = ref(false)
const editItem = ref(null)
const stockItem = ref(null)
const stockType = ref('in')
const stockQty = ref(0)
const stockNotes = ref('')
const snackbar = ref({ show: false, text: '', color: 'success' })

const iForm = reactive({ name: '', category: '', unit: 'dona', quantity: 0, min_quantity: 0, price_per_unit: 0, supplier: '' })

const headers = [
  { title: 'Nomi', key: 'name' },
  { title: 'Miqdor', key: 'quantity', width: 200 },
  { title: 'Holat', key: 'status', width: 100 },
  { title: 'Narx/birlik', key: 'price_per_unit', width: 120 },
  { title: 'Amallar', key: 'actions', sortable: false, width: 130 },
]

function getProgress(item) {
  if (!item.min_quantity) return 100
  return Math.min(100, (item.quantity / (item.min_quantity * 2)) * 100)
}
function statusColor(item) {
  if (item.quantity <= item.min_quantity) return 'error'
  if (item.quantity <= item.min_quantity * 1.5) return 'warning'
  return 'success'
}
function statusLabel(item) {
  if (item.quantity <= item.min_quantity) return 'Kritik'
  if (item.quantity <= item.min_quantity * 1.5) return 'Kam'
  return 'Yetarli'
}
function formatMoney(v) { return v ? Number(v).toLocaleString('uz-UZ') + " so'm" : '0' }

let timer = null
function debouncedLoad() { clearTimeout(timer); timer = setTimeout(load, 400) }

async function load() {
  loading.value = true
  try {
    const params = { per_page: 100 }
    if (search.value) params.search = search.value
    if (statusFilter.value) params.status = statusFilter.value
    const res = await tenantApi.get('/inventory', { params })
    items.value = res.data.data || []
  } finally { loading.value = false }
}

function openDialog(item = null) {
  editItem.value = item
  if (item) Object.assign(iForm, { name: item.name, category: item.category, unit: item.unit, quantity: item.quantity, min_quantity: item.min_quantity, price_per_unit: item.price_per_unit, supplier: item.supplier })
  else Object.assign(iForm, { name: '', category: '', unit: 'dona', quantity: 0, min_quantity: 0, price_per_unit: 0, supplier: '' })
  itemDialog.value = true
}

function openStockDialog(item, type) {
  stockItem.value = item
  stockType.value = type
  stockQty.value = 0
  stockNotes.value = ''
  stockDialog.value = true
}

async function saveItem() {
  saving.value = true
  try {
    if (editItem.value) await tenantApi.put(`/inventory/${editItem.value.id}`, iForm)
    else await tenantApi.post('/inventory', iForm)
    snackbar.value = { show: true, text: 'Saqlandi', color: 'success' }
    itemDialog.value = false
    load()
  } catch {
    snackbar.value = { show: true, text: 'Xatolik', color: 'error' }
  } finally { saving.value = false }
}

async function doStock() {
  if (!stockQty.value) return
  saving.value = true
  try {
    const endpoint = stockType.value === 'in' ? 'stock-in' : 'stock-out'
    await tenantApi.post(`/inventory/${stockItem.value.id}/${endpoint}`, { quantity: stockQty.value, notes: stockNotes.value })
    snackbar.value = { show: true, text: 'Saqlandi', color: 'success' }
    stockDialog.value = false
    load()
  } catch {
    snackbar.value = { show: true, text: 'Xatolik', color: 'error' }
  } finally { saving.value = false }
}

onMounted(load)
</script>
