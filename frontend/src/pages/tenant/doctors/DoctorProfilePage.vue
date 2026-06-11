<template>
  <div v-if="doctor">
    <div class="d-flex align-center mb-6">
      <v-btn icon="mdi-arrow-left" variant="text" class="mr-2" to="/doctors" />
      <div class="text-h5 font-weight-bold">Shifokor profili</div>
      <v-spacer />
      <v-btn variant="outlined" prepend-icon="mdi-pencil" :to="`/doctors/${doctor.id}/edit`">Tahrirlash</v-btn>
    </div>

    <v-row>
      <v-col cols="12" md="4">
        <v-card rounded="xl" class="text-center pa-6 mb-4">
          <v-avatar color="primary" size="88" class="mb-3">
            <v-icon size="44" color="white">mdi-doctor</v-icon>
          </v-avatar>
          <div class="text-h6 font-weight-bold">Dr. {{ doctor.user?.name }}</div>
          <div class="text-body-2 text-medium-emphasis mb-2">{{ doctor.specialization }}</div>
          <v-chip :color="doctor.is_active ? 'success' : 'error'" variant="tonal" size="small">
            {{ doctor.is_active ? 'Aktiv' : 'Inaktiv' }}
          </v-chip>
          <v-list density="compact" class="text-left mt-3">
            <v-list-item prepend-icon="mdi-phone" :subtitle="doctor.user?.phone || '—'" />
            <v-list-item prepend-icon="mdi-door" :subtitle="'Xona: ' + (doctor.room_number || '—')" />
            <v-list-item prepend-icon="mdi-cash" :subtitle="formatMoney(doctor.consultation_price)" />
          </v-list>
        </v-card>

        <!-- Stats -->
        <v-card rounded="xl" class="pa-4">
          <div class="font-weight-bold mb-3">Bu oy statistikasi</div>
          <v-row dense>
            <v-col cols="6">
              <div class="text-center pa-3 rounded-lg" style="background: #F3F4F6;">
                <div class="text-h5 font-weight-bold text-primary">{{ report.stats?.totalPatients || 0 }}</div>
                <div class="text-caption">Bemorlar</div>
              </div>
            </v-col>
            <v-col cols="6">
              <div class="text-center pa-3 rounded-lg" style="background: #F3F4F6;">
                <div class="text-h5 font-weight-bold text-success">{{ formatMillions(report.stats?.totalRevenue) }}</div>
                <div class="text-caption">Daromad</div>
              </div>
            </v-col>
          </v-row>
        </v-card>
      </v-col>

      <v-col cols="12" md="8">
        <v-card rounded="xl">
          <v-tabs v-model="tab" bg-color="transparent" class="px-2 pt-2">
            <v-tab value="appointments">Bugungi qabullar</v-tab>
            <v-tab value="visits">Tashriflar tarixi</v-tab>
          </v-tabs>
          <v-divider />

          <!-- Today's Appointments -->
          <v-window v-model="tab">
            <v-window-item value="appointments">
              <v-data-table
                :headers="apptHeaders"
                :items="appointments"
                :loading="loading"
                density="compact"
                :items-per-page="10"
              >
                <template #item.scheduled_at="{ item }">{{ formatDateTime(item.scheduled_at) }}</template>
                <template #item.patient="{ item }">{{ item.patient?.full_name }}</template>
                <template #item.status="{ item }">
                  <v-chip :color="statusColor(item.status)" size="small" label>{{ statusLabel(item.status) }}</v-chip>
                </template>
              </v-data-table>
            </v-window-item>

            <!-- Visits History -->
            <v-window-item value="visits">
              <div class="pa-3 d-flex gap-2 flex-wrap">
                <v-text-field v-model="visitFrom" type="date" label="Dan" variant="outlined" density="compact" hide-details style="max-width: 160px;" @change="loadVisits" />
                <v-text-field v-model="visitTo" type="date" label="Gacha" variant="outlined" density="compact" hide-details style="max-width: 160px;" @change="loadVisits" />
              </div>
              <v-data-table
                :headers="visitHeaders"
                :items="visits"
                :loading="visitsLoading"
                density="compact"
                :items-per-page="15"
              >
                <template #item.visited_at="{ item }">{{ formatDateTime(item.visited_at) }}</template>
                <template #item.patient="{ item }">{{ item.patient?.full_name }}</template>
                <template #item.total_amount="{ item }">{{ formatMoney(item.total_amount) }}</template>
                <template #item.is_paid="{ item }">
                  <v-chip :color="item.is_paid ? 'success' : 'warning'" size="small" label>
                    {{ item.is_paid ? "To'landi" : "Kutilmoqda" }}
                  </v-chip>
                </template>
                <template #item.actions="{ item }">
                  <v-btn icon="mdi-eye" size="x-small" variant="text" :to="`/visits/${item.id}`" />
                </template>
              </v-data-table>
            </v-window-item>
          </v-window>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { tenantApi } from '@/plugins/axios'
import dayjs from 'dayjs'

const route = useRoute()
const doctor = ref(null)
const report = ref({ stats: {} })
const appointments = ref([])
const loading = ref(false)
const tab = ref('appointments')

const visits = ref([])
const visitsLoading = ref(false)
const visitFrom = ref(dayjs().startOf('month').format('YYYY-MM-DD'))
const visitTo   = ref(dayjs().format('YYYY-MM-DD'))

const apptHeaders = [
  { title: 'Vaqt', key: 'scheduled_at' },
  { title: 'Bemor', key: 'patient' },
  { title: 'Xizmat', key: 'service.name' },
  { title: 'Holat', key: 'status' },
]

const visitHeaders = [
  { title: 'Sana', key: 'visited_at' },
  { title: 'Bemor', key: 'patient' },
  { title: 'Jami', key: 'total_amount' },
  { title: "To'lov", key: 'is_paid' },
  { title: '', key: 'actions', sortable: false },
]

function formatMoney(v) { return v ? Number(v).toLocaleString('uz-UZ') + " so'm" : '0' }
function formatMillions(v) { return v ? (v >= 1000000 ? (v/1000000).toFixed(1)+'M' : (v/1000).toFixed(0)+'K') : '0' }
function formatDateTime(d) { return d ? dayjs(d).format('DD.MM.YYYY HH:mm') : '' }
function statusColor(s) { return {pending:'warning',confirmed:'info',in_progress:'primary',completed:'success',cancelled:'error'}[s]||'grey' }
function statusLabel(s) { return {pending:'Kutilmoqda',confirmed:'Tasdiqlangan',in_progress:'Qabulda',completed:'Tugadi',cancelled:'Bekor'}[s]||s }

async function loadVisits() {
  visitsLoading.value = true
  try {
    const res = await tenantApi.get('/visits', {
      params: { doctor_id: route.params.id, from: visitFrom.value, to: visitTo.value, per_page: 50 }
    })
    visits.value = res.data.data || []
  } catch (e) {
    console.error('Visits load error:', e)
  } finally {
    visitsLoading.value = false
  }
}

async function load() {
  loading.value = true
  const id = route.params.id
  const [dRes, rRes, aRes] = await Promise.all([
    tenantApi.get(`/doctors/${id}`),
    tenantApi.get(`/doctors/${id}/report`),
    tenantApi.get(`/doctors/${id}/appointments?date=${dayjs().format('YYYY-MM-DD')}`),
  ])
  doctor.value = dRes.data
  report.value = rRes.data
  appointments.value = aRes.data.data || []
  loading.value = false
  loadVisits()
}

onMounted(load)
</script>
