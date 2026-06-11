<template>
  <div>
    <div class="d-flex align-center mb-6">
      <v-btn icon="mdi-arrow-left" variant="text" class="mr-2" @click="$router.back()" />
      <div class="text-h5 font-weight-bold">Yangi tashrif</div>
    </div>

    <v-row>
      <v-col cols="12" lg="8">
        <!-- Patient & Doctor -->
        <v-card rounded="xl" class="mb-4">
          <v-card-title class="pa-4 pb-0">Asosiy ma'lumotlar</v-card-title>
          <v-card-text class="pa-4">
            <v-row>
              <v-col cols="12" sm="6">
                <v-autocomplete
                  v-model="form.patient_id"
                  :items="patientItems"
                  label="Bemor *"
                  variant="outlined"
                  @update:search="searchPatients"
                  :loading="patientsLoading"
                  no-data-text="Qidiring..."
                />
              </v-col>
              <v-col cols="12" sm="6">
                <v-select v-model="form.doctor_id" :items="doctorItems" label="Shifokor *" variant="outlined" />
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field v-model="form.visited_at" type="datetime-local" label="Tashrif sanasi" variant="outlined" />
              </v-col>
              <v-col cols="12" sm="6">
                <v-select v-model="form.payment_method" :items="paymentMethods" label="To'lov usuli" variant="outlined" />
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>

        <!-- Services -->
        <v-card rounded="xl" class="mb-4">
          <v-card-title class="pa-4 pb-0 d-flex justify-space-between">
            Ko'rsatilgan xizmatlar
            <v-btn size="small" color="primary" prepend-icon="mdi-plus" @click="addService">Xizmat qo'shish</v-btn>
          </v-card-title>
          <v-card-text class="pa-4">
            <div v-for="(svc, i) in form.services" :key="i" class="d-flex gap-2 mb-3 align-center">
              <v-select
                v-model="svc.service_id"
                :items="serviceItems"
                label="Xizmat"
                variant="outlined"
                density="compact"
                hide-details
                class="flex-grow-1"
                @update:model-value="onServiceSelect(svc)"
              />
              <v-text-field v-model.number="svc.quantity" label="Soni" type="number" variant="outlined" density="compact" hide-details style="max-width: 80px;" />
              <v-text-field v-model.number="svc.price" label="Narx" type="number" variant="outlined" density="compact" hide-details style="max-width: 120px;" />
              <div class="font-weight-bold text-no-wrap" style="min-width: 100px;">{{ formatMoney(svc.quantity * svc.price) }}</div>
              <v-btn icon="mdi-close" size="small" variant="text" color="error" @click="removeService(i)" />
            </div>
            <v-divider class="my-3" />
            <div class="d-flex justify-end gap-4">
              <div class="text-body-2 text-medium-emphasis">Jami:</div>
              <div class="font-weight-bold">{{ formatMoney(subtotal) }}</div>
            </div>
            <div class="d-flex align-center gap-4 mt-2 justify-end">
              <div class="text-body-2 text-medium-emphasis">Chegirma (so'm):</div>
              <v-text-field v-model.number="form.discount" type="number" variant="outlined" density="compact" hide-details style="max-width: 140px;" />
            </div>
            <div class="d-flex justify-end gap-4 mt-2">
              <div class="text-h6 font-weight-bold text-primary">JAMI: {{ formatMoney(totalToPay) }}</div>
            </div>
          </v-card-text>
        </v-card>

        <!-- Inventory Used -->
        <v-card rounded="xl" class="mb-4">
          <v-card-title class="pa-4 pb-0 d-flex justify-space-between">
            <span><v-icon class="mr-2" size="20">mdi-package-variant</v-icon>Ishlatilgan inventar</span>
            <v-btn size="small" variant="tonal" color="secondary" prepend-icon="mdi-plus" @click="addInventory">Qo'shish</v-btn>
          </v-card-title>
          <v-card-text class="pa-4">
            <div v-if="form.inventory.length === 0" class="text-center text-medium-emphasis py-2 text-body-2">
              Inventar ishlatilmagan
            </div>
            <div v-for="(inv, i) in form.inventory" :key="i" class="d-flex gap-2 mb-3 align-center">
              <v-autocomplete
                v-model="inv.item_id"
                :items="inventoryItems"
                label="Mahsulot"
                variant="outlined"
                density="compact"
                hide-details
                class="flex-grow-1"
                :loading="invLoading"
                @update:search="searchInventory"
                no-data-text="Qidiring..."
              />
              <v-text-field v-model.number="inv.quantity_used" label="Miqdor" type="number" variant="outlined" density="compact" hide-details style="max-width: 110px;" :suffix="getUnit(inv.item_id)" />
              <v-btn icon="mdi-close" size="small" variant="text" color="error" @click="removeInventory(i)" />
            </div>
          </v-card-text>
        </v-card>

        <!-- Diagnosis -->
        <v-card rounded="xl" class="mb-4">
          <v-card-title class="pa-4 pb-0">Diagnoz va retsept</v-card-title>
          <v-card-text class="pa-4">
            <v-textarea v-model="form.diagnosis" label="Diagnoz" variant="outlined" rows="3" class="mb-3" />
            <v-textarea v-model="form.prescription" label="Retsept / Tavsiyalar" variant="outlined" rows="3" />
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Right: Payment Summary -->
      <v-col cols="12" lg="4">
        <v-card rounded="xl" class="mb-4">
          <v-card-title class="pa-4 pb-0">To'lov</v-card-title>
          <v-card-text class="pa-4">
            <v-list lines="one" density="compact" class="mb-3">
              <v-list-item title="Jami xizmatlar" :subtitle="formatMoney(subtotal)" />
              <v-list-item title="Chegirma" :subtitle="'-' + formatMoney(form.discount)" />
              <v-divider />
              <v-list-item title="TO'LANADIGAN" :subtitle="formatMoney(totalToPay)" class="font-weight-bold" />
            </v-list>
            <v-select v-model="form.payment_method" :items="paymentMethods" label="To'lov usuli" variant="outlined" class="mb-3" />
          </v-card-text>
        </v-card>

        <!-- Inventory summary on right -->
        <v-card v-if="form.inventory.length" rounded="xl" class="mb-4" color="secondary" variant="tonal">
          <v-card-text class="pa-3">
            <div class="text-caption text-medium-emphasis mb-1">Inventar</div>
            <div v-for="inv in form.inventory" :key="inv.item_id" class="text-body-2">
              {{ getItemName(inv.item_id) }}: <strong>{{ inv.quantity_used }} {{ getUnit(inv.item_id) }}</strong>
            </div>
          </v-card-text>
        </v-card>

        <div class="d-flex flex-column gap-2">
          <v-btn color="primary" size="large" block :loading="saving" @click="saveVisit(false)">
            Saqlash
          </v-btn>
          <v-btn color="secondary" size="large" block :loading="saving" @click="saveVisit(true)">
            <v-icon class="mr-2">mdi-printer</v-icon>
            Saqlash + Chek chiqarish
          </v-btn>
        </div>
      </v-col>
    </v-row>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color">{{ snackbar.text }}</v-snackbar>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { tenantApi } from '@/plugins/axios'
import dayjs from 'dayjs'

const router = useRouter()
const saving       = ref(false)
const snackbar     = ref({ show: false, text: '', color: 'success' })
const doctors      = ref([])
const allServices  = ref([])
const allInventory = ref([])
const invLoading   = ref(false)
const patientItems = ref([])
const patientsLoading = ref(false)

const form = reactive({
  patient_id: null,
  doctor_id: null,
  visited_at: dayjs().format('YYYY-MM-DDTHH:mm'),
  diagnosis: '',
  prescription: '',
  discount: 0,
  payment_method: 'cash',
  services: [{ service_id: null, quantity: 1, price: 0 }],
  inventory: [],
})

const doctorItems   = computed(() => doctors.value.map(d => ({ title: `Dr. ${d.user?.name}${d.specialization ? ' — ' + d.specialization : ''}`, value: d.id })))
const serviceItems  = computed(() => allServices.value.map(s => ({ title: `${s.name} — ${formatMoney(s.price)}`, value: s.id })))
const inventoryItems = computed(() => allInventory.value.map(i => ({
  title: `${i.name}${i.category ? ' (' + i.category + ')' : ''} — qoldi: ${i.quantity} ${i.unit}`,
  value: i.id,
})))

const paymentMethods = [
  { title: 'Naqd',     value: 'cash' },
  { title: 'Karta',    value: 'card' },
  { title: "Sug'urta", value: 'insurance' },
]

const subtotal   = computed(() => form.services.reduce((s, sv) => s + (sv.quantity || 0) * (sv.price || 0), 0))
const totalToPay = computed(() => Math.max(0, subtotal.value - (form.discount || 0)))

function formatMoney(v) { return v ? Number(v).toLocaleString('uz-UZ') + " so'm" : '0' }

function addService()   { form.services.push({ service_id: null, quantity: 1, price: 0 }) }
function removeService(i) { form.services.splice(i, 1) }
function addInventory() { form.inventory.push({ item_id: null, quantity_used: 1 }) }
function removeInventory(i) { form.inventory.splice(i, 1) }

function onServiceSelect(svc) {
  const s = allServices.value.find(x => x.id === svc.service_id)
  if (s) svc.price = parseFloat(s.price)
}

function getUnit(itemId) {
  return allInventory.value.find(i => i.id === itemId)?.unit || ''
}
function getItemName(itemId) {
  return allInventory.value.find(i => i.id === itemId)?.name || ''
}

let searchTimer = null
async function searchPatients(q) {
  if (!q || q.length < 2) return
  clearTimeout(searchTimer)
  searchTimer = setTimeout(async () => {
    patientsLoading.value = true
    const res = await tenantApi.get('/patients', { params: { search: q, per_page: 20 } })
    patientItems.value = (res.data.data || []).map(p => ({ title: p.full_name + (p.phone ? ` (${p.phone})` : ''), value: p.id }))
    patientsLoading.value = false
  }, 300)
}

let invTimer = null
async function searchInventory(q) {
  clearTimeout(invTimer)
  invTimer = setTimeout(async () => {
    invLoading.value = true
    const res = await tenantApi.get('/inventory', { params: { search: q || '', per_page: 50 } })
    allInventory.value = res.data.data || []
    invLoading.value = false
  }, 300)
}

async function saveVisit(printReceipt) {
  if (!form.patient_id || !form.doctor_id) {
    snackbar.value = { show: true, text: 'Bemor va shifokorni tanlang', color: 'error' }
    return
  }
  if (!form.services[0]?.service_id) {
    snackbar.value = { show: true, text: 'Kamida 1 ta xizmat tanlang', color: 'error' }
    return
  }

  saving.value = true
  try {
    const payload = {
      ...form,
      services: form.services.filter(s => s.service_id).map(s => ({
        service_id: s.service_id,
        quantity:   s.quantity || 1,
        price:      s.price || 0,
      })),
      inventory: form.inventory.filter(i => i.item_id && i.quantity_used > 0).map(i => ({
        item_id:       i.item_id,
        quantity_used: i.quantity_used,
      })),
    }

    const res = await tenantApi.post('/visits', payload)
    const visitId = res.data.id

    await tenantApi.post(`/visits/${visitId}/pay`, {
      paid_amount:    totalToPay.value,
      payment_method: form.payment_method,
    })

    snackbar.value = { show: true, text: 'Tashrif saqlandi', color: 'success' }

    if (printReceipt) {
      const tenantId = localStorage.getItem('tenant_id')
      window.open(`/api/tenant/visits/${visitId}/receipt-preview?tenant=${tenantId}`, '_blank')
    }

    setTimeout(() => router.push(`/visits/${visitId}`), 600)
  } catch (e) {
    console.error(e)
    snackbar.value = { show: true, text: e.response?.data?.message || 'Xatolik yuz berdi', color: 'error' }
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  const [dRes, sRes, iRes] = await Promise.all([
    tenantApi.get('/doctors?per_page=50'),
    tenantApi.get('/services?per_page=100&is_active=true'),
    tenantApi.get('/inventory?per_page=100'),
  ])
  doctors.value    = dRes.data.data || []
  allServices.value = sRes.data.data || []
  allInventory.value = iRes.data.data || []
})
</script>
