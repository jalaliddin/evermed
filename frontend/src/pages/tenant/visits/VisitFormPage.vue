<template>
  <div>
    <div class="d-flex align-center mb-6">
      <v-btn icon="mdi-arrow-left" variant="text" class="mr-2" @click="$router.back()" />
      <div class="text-h5 font-weight-bold">Yangi tashrif</div>
    </div>

    <v-row>
      <v-col cols="12" lg="8">

        <!-- Patient card -->
        <v-card rounded="xl" class="mb-4">
          <v-card-title class="pa-4 pb-2 d-flex align-center justify-space-between">
            <span>Bemor</span>
            <v-btn
              v-if="!newPatientMode && !selectedPatient"
              size="small" variant="tonal" color="primary"
              prepend-icon="mdi-account-plus"
              @click="newPatientMode = true"
            >Yangi bemor</v-btn>
            <v-btn
              v-if="newPatientMode"
              size="small" variant="text" color="grey"
              @click="cancelNewPatient"
            >Bekor</v-btn>
          </v-card-title>
          <v-card-text class="pa-4 pt-2">

            <!-- Selected patient chip -->
            <template v-if="selectedPatient && !newPatientMode">
              <v-chip
                color="primary" variant="tonal" size="large"
                closable @click:close="clearPatient"
                prepend-icon="mdi-account-check"
                class="px-4 py-5"
              >
                <span class="font-weight-bold mr-1">{{ selectedPatient.full_name }}</span>
                <span v-if="selectedPatient.birth_date" class="text-medium-emphasis text-body-2">
                  · {{ formatBirth(selectedPatient.birth_date) }}
                </span>
                <span v-if="selectedPatient.phone" class="text-medium-emphasis text-body-2 ml-1">
                  · {{ selectedPatient.phone }}
                </span>
              </v-chip>
            </template>

            <!-- Search autocomplete -->
            <template v-else-if="!newPatientMode">
              <v-autocomplete
                v-model="form.patient_id"
                :items="patientItems"
                label="Ism yoki tug'ilgan sana bo'yicha qidiring"
                variant="outlined"
                clearable
                hide-no-data
                :loading="patientsLoading"
                no-filter
                @update:search="onPatientSearch"
                @update:model-value="onPatientSelect"
              >
                <template #item="{ props, item }">
                  <v-list-item v-bind="props">
                    <template #subtitle>
                      <span v-if="item.raw.birth_date" class="text-caption">
                        {{ formatBirth(item.raw.birth_date) }}
                      </span>
                      <span v-if="item.raw.phone" class="text-caption ml-2">{{ item.raw.phone }}</span>
                    </template>
                  </v-list-item>
                </template>
                <template #no-data>
                  <v-list-item
                    prepend-icon="mdi-account-plus"
                    title="Yangi bemor sifatida qo'shish"
                    class="text-primary"
                    @click="newPatientMode = true"
                  />
                </template>
                <template #append-item>
                  <v-divider class="my-1" />
                  <v-list-item
                    prepend-icon="mdi-account-plus"
                    title="Yangi bemor qo'shish"
                    class="text-primary text-body-2"
                    @click="newPatientMode = true"
                  />
                </template>
              </v-autocomplete>
            </template>

            <!-- Inline new patient form -->
            <template v-else>
              <v-row dense>
                <v-col cols="12" sm="6">
                  <v-text-field
                    v-model="newPatient.full_name"
                    label="To'liq ismi *"
                    variant="outlined"
                    :error-messages="newPatientErrors.full_name"
                  />
                </v-col>
                <v-col cols="12" sm="6">
                  <v-text-field
                    v-model="newPatient.birth_date"
                    type="date"
                    label="Tug'ilgan sana *"
                    variant="outlined"
                    :max="today"
                    :error-messages="newPatientErrors.birth_date"
                  />
                </v-col>
                <v-col cols="12" sm="6">
                  <v-text-field
                    v-model="newPatient.phone"
                    label="Telefon raqami"
                    variant="outlined"
                    placeholder="+998901234567"
                  />
                </v-col>
                <v-col cols="12" sm="6">
                  <v-select
                    v-model="newPatient.gender"
                    :items="genderItems"
                    label="Jins"
                    variant="outlined"
                  />
                </v-col>
              </v-row>
              <div class="text-caption text-medium-emphasis mt-1">
                <v-icon size="14">mdi-information-outline</v-icon>
                Tug'ilgan sana bemor identifikatori hisoblanadi
              </div>
            </template>

          </v-card-text>
        </v-card>

        <!-- Doctor & visit info -->
        <v-card rounded="xl" class="mb-4">
          <v-card-title class="pa-4 pb-0">Qabul ma'lumotlari</v-card-title>
          <v-card-text class="pa-4">
            <v-row>
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
              <div class="text-body-2 text-medium-emphasis">Xizmatlar:</div>
              <div class="font-weight-bold">{{ formatMoney(svcSubtotal) }}</div>
            </div>
            <div v-if="invSubtotal > 0" class="d-flex justify-end gap-4 mt-1">
              <div class="text-body-2 text-medium-emphasis">Materiallar:</div>
              <div class="font-weight-bold">{{ formatMoney(invSubtotal) }}</div>
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

        <!-- Inventory -->
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
              <v-list-item title="Xizmatlar" :subtitle="formatMoney(svcSubtotal)" />
              <v-list-item v-if="invSubtotal > 0" title="Materiallar" :subtitle="formatMoney(invSubtotal)" />
              <v-list-item v-if="form.discount > 0" title="Chegirma" :subtitle="'-' + formatMoney(form.discount)" />
              <v-divider />
              <v-list-item title="TO'LANADIGAN" :subtitle="formatMoney(totalToPay)" class="font-weight-bold" />
            </v-list>
            <v-select v-model="form.payment_method" :items="paymentMethods" label="To'lov usuli" variant="outlined" class="mb-3" />
          </v-card-text>
        </v-card>

        <!-- Inventory summary -->
        <v-card v-if="form.inventory.length" rounded="xl" class="mb-4" color="secondary" variant="tonal">
          <v-card-text class="pa-3">
            <div class="text-caption text-medium-emphasis mb-2">Sarflangan inventar</div>
            <div v-for="inv in form.inventory" :key="inv.item_id" class="d-flex justify-space-between text-body-2 py-1">
              <span>{{ getItemName(inv.item_id) }} <span class="text-medium-emphasis">×{{ inv.quantity_used }} {{ getUnit(inv.item_id) }}</span></span>
              <span class="font-weight-medium">{{ getInvPrice(inv) > 0 ? formatMoney(getInvPrice(inv)) : '' }}</span>
            </div>
            <v-divider class="my-1" v-if="invSubtotal > 0" />
            <div v-if="invSubtotal > 0" class="d-flex justify-space-between text-body-2 font-weight-bold">
              <span>Inventar jami:</span>
              <span>{{ formatMoney(invSubtotal) }}</span>
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
const saving        = ref(false)
const snackbar      = ref({ show: false, text: '', color: 'success' })
const doctors       = ref([])
const allServices   = ref([])
const allInventory  = ref([])
const invLoading    = ref(false)
const patientsLoading = ref(false)

// Patient state
const patientItems    = ref([])       // autocomplete items [{title, value, raw}]
const selectedPatient = ref(null)     // full patient object once selected
const newPatientMode  = ref(false)
const newPatientErrors = reactive({ full_name: '', birth_date: '' })
const newPatient = reactive({ full_name: '', birth_date: '', phone: '', gender: null })

const today = dayjs().format('YYYY-MM-DD')

const genderItems = [
  { title: 'Erkak', value: 'male' },
  { title: 'Ayol',  value: 'female' },
]

const form = reactive({
  patient_id:     null,
  doctor_id:      null,
  visited_at:     dayjs().format('YYYY-MM-DDTHH:mm'),
  diagnosis:      '',
  prescription:   '',
  discount:       0,
  payment_method: 'cash',
  services:       [{ service_id: null, quantity: 1, price: 0 }],
  inventory:      [],
})

const doctorItems  = computed(() => doctors.value.map(d => ({
  title: `Dr. ${d.user?.name}${d.specialization ? ' — ' + d.specialization : ''}`,
  value: d.id,
})))
const serviceItems = computed(() => allServices.value.map(s => ({
  title: `${s.name} — ${formatMoney(s.price)}`,
  value: s.id,
})))
const inventoryItems = computed(() => allInventory.value.map(i => ({
  title: `${i.name}${i.category ? ' (' + i.category + ')' : ''} — ${i.price_per_unit > 0 ? Number(i.price_per_unit).toLocaleString('uz-UZ') + " so'm/" + i.unit + ' | ' : ''}qoldi: ${i.quantity} ${i.unit}`,
  value: i.id,
})))
const paymentMethods = [
  { title: 'Naqd',      value: 'cash' },
  { title: 'Karta',     value: 'card' },
  { title: "Sug'urta",  value: 'insurance' },
]

const svcSubtotal = computed(() => form.services.reduce((s, sv) => s + (sv.quantity || 0) * (Number(sv.price) || 0), 0))
const invSubtotal = computed(() => form.inventory.reduce((s, inv) => {
  const item = allInventory.value.find(i => i.id === inv.item_id)
  return s + (Number(inv.quantity_used) || 0) * (Number(item?.price_per_unit) || 0)
}, 0))
const subtotal   = computed(() => svcSubtotal.value + invSubtotal.value)
const totalToPay = computed(() => Math.max(0, subtotal.value - (form.discount || 0)))

function formatMoney(v)  { return v ? Number(v).toLocaleString('uz-UZ') + " so'm" : '0' }
function formatBirth(d)  { return d ? dayjs(d).format('DD.MM.YYYY') : '' }

// Patient search
let searchTimer = null
function onPatientSearch(q) {
  clearTimeout(searchTimer)
  if (!q || q.length < 2) {
    patientItems.value = []
    return
  }
  searchTimer = setTimeout(async () => {
    patientsLoading.value = true
    try {
      const res = await tenantApi.get('/patients', { params: { search: q, per_page: 20 } })
      patientItems.value = (res.data.data || []).map(p => ({
        title: p.full_name,
        value: p.id,
        raw:   p,
      }))
    } finally {
      patientsLoading.value = false
    }
  }, 300)
}

function onPatientSelect(id) {
  if (!id) { selectedPatient.value = null; return }
  const found = patientItems.value.find(p => p.value === id)
  if (found) selectedPatient.value = found.raw
}

function clearPatient() {
  form.patient_id   = null
  selectedPatient.value = null
  patientItems.value    = []
}

function cancelNewPatient() {
  newPatientMode.value = false
  newPatient.full_name = ''
  newPatient.birth_date = ''
  newPatient.phone = ''
  newPatient.gender = null
  newPatientErrors.full_name = ''
  newPatientErrors.birth_date = ''
}

function validateNewPatient() {
  newPatientErrors.full_name  = newPatient.full_name.trim()  ? '' : 'Majburiy maydon'
  newPatientErrors.birth_date = newPatient.birth_date.trim() ? '' : 'Majburiy maydon'
  return !newPatientErrors.full_name && !newPatientErrors.birth_date
}

// Inventory helpers
function addService()       { form.services.push({ service_id: null, quantity: 1, price: 0 }) }
function removeService(i)   { form.services.splice(i, 1) }
function addInventory()     { form.inventory.push({ item_id: null, quantity_used: 1 }) }
function removeInventory(i) { form.inventory.splice(i, 1) }

function onServiceSelect(svc) {
  const s = allServices.value.find(x => x.id === svc.service_id)
  if (s) svc.price = parseFloat(s.price)
}
function getUnit(itemId)     { return allInventory.value.find(i => i.id === itemId)?.unit           || '' }
function getItemName(itemId) { return allInventory.value.find(i => i.id === itemId)?.name           || '' }
function getInvPrice(inv)    { return (Number(inv.quantity_used) || 0) * (Number(allInventory.value.find(i => i.id === inv.item_id)?.price_per_unit) || 0) }

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
  // 1. Validate patient
  if (newPatientMode.value) {
    if (!validateNewPatient()) return
  } else if (!form.patient_id) {
    snackbar.value = { show: true, text: 'Bemorni tanlang yoki yangi bemor qo\'shing', color: 'error' }
    return
  }

  if (!form.doctor_id) {
    snackbar.value = { show: true, text: 'Shifokorni tanlang', color: 'error' }
    return
  }
  if (!form.services[0]?.service_id) {
    snackbar.value = { show: true, text: 'Kamida 1 ta xizmat tanlang', color: 'error' }
    return
  }

  saving.value = true
  try {
    // 2. Create new patient if needed
    if (newPatientMode.value) {
      const pRes = await tenantApi.post('/patients', {
        full_name:  newPatient.full_name.trim(),
        birth_date: newPatient.birth_date || null,
        phone:      newPatient.phone || null,
        gender:     newPatient.gender || null,
      })
      form.patient_id = pRes.data.id
    }

    // 3. Create visit
    const payload = {
      patient_id:     form.patient_id,
      doctor_id:      form.doctor_id,
      visited_at:     form.visited_at,
      diagnosis:      form.diagnosis,
      prescription:   form.prescription,
      discount:       form.discount,
      payment_method: form.payment_method,
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

    // 4. Pay
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
  doctors.value      = dRes.data.data || []
  allServices.value  = sRes.data.data || []
  allInventory.value = iRes.data.data || []
})
</script>
