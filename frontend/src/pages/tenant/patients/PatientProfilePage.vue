<template>
  <div v-if="patient">
    <div class="d-flex align-center mb-6">
      <v-btn icon="mdi-arrow-left" variant="text" class="mr-2" to="/patients" />
      <div class="text-h5 font-weight-bold">Bemor profili</div>
      <v-spacer />
      <v-btn variant="outlined" prepend-icon="mdi-pencil" :to="`/patients/${patient.id}/edit`" class="mr-2">Tahrirlash</v-btn>
      <v-btn color="primary" prepend-icon="mdi-calendar-plus" @click="showAppointmentDialog = true">Qabul belgilash</v-btn>
    </div>

    <v-row>
      <!-- Left Panel -->
      <v-col cols="12" md="4" lg="3">
        <v-card rounded="xl" class="mb-4 text-center pa-6">
          <v-avatar size="96" color="primary" class="mb-3">
            <v-img v-if="patient.photo" :src="'/storage/' + patient.photo" cover />
            <span v-else class="text-h5 text-white font-weight-bold">{{ patient.full_name?.[0] }}</span>
          </v-avatar>
          <div class="text-h6 font-weight-bold">{{ patient.full_name }}</div>
          <div class="text-caption text-medium-emphasis mb-3">Bemor #{{ patient.id }}</div>

          <div class="d-flex justify-center gap-2 mb-4">
            <v-chip v-if="patient.gender" :color="patient.gender === 'male' ? 'blue' : 'pink'" size="small" variant="tonal">
              {{ patient.gender === 'male' ? 'Erkak' : 'Ayol' }}
            </v-chip>
            <v-chip v-if="patient.blood_type" color="error" size="small" variant="tonal">{{ patient.blood_type }}</v-chip>
          </div>

          <v-list lines="one" density="compact" class="text-left">
            <v-list-item v-if="patient.phone" prepend-icon="mdi-phone" :subtitle="patient.phone" />
            <v-list-item v-if="patient.birth_date" prepend-icon="mdi-cake" :subtitle="formatDate(patient.birth_date) + ' (' + age(patient.birth_date) + ' yosh)'" />
            <v-list-item v-if="patient.address" prepend-icon="mdi-map-marker" :subtitle="patient.address" />
          </v-list>
        </v-card>

        <!-- Stats -->
        <v-card rounded="xl" class="pa-4">
          <div class="text-body-2 font-weight-bold mb-3">Statistika</div>
          <div class="d-flex justify-space-between py-2 text-body-2">
            <span class="text-medium-emphasis">Jami tashriflar</span>
            <span class="font-weight-bold">{{ patientStats.total_visits || 0 }}</span>
          </div>
          <v-divider />
          <div class="d-flex justify-space-between py-2 text-body-2">
            <span class="text-medium-emphasis">Jami to'lov</span>
            <span class="font-weight-bold">{{ formatMoney(patientStats.total_spent) }}</span>
          </div>
          <v-divider />
          <div class="d-flex justify-space-between py-2 text-body-2">
            <span class="text-medium-emphasis">Oxirgi tashrif</span>
            <span class="font-weight-bold">{{ patientStats.last_visit ? formatDate(patientStats.last_visit) : '—' }}</span>
          </div>
        </v-card>
      </v-col>

      <!-- Right Panel — Tabs -->
      <v-col cols="12" md="8" lg="9">
        <v-card rounded="xl">
          <v-tabs v-model="tab" color="primary">
            <v-tab value="visits">📋 Tashrif tarixi</v-tab>
            <v-tab value="notes">📝 Eslatmalar</v-tab>
          </v-tabs>
          <v-divider />
          <v-window v-model="tab" class="pa-4">
            <!-- Visits Tab -->
            <v-window-item value="visits">
              <v-data-table
                :headers="visitHeaders"
                :items="visits"
                :loading="visitsLoading"
                density="compact"
              >
                <template #item.visited_at="{ item }">{{ formatDate(item.visited_at) }}</template>
                <template #item.doctor="{ item }">Dr. {{ item.doctor?.user?.name }}</template>
                <template #item.paid_amount="{ item }">{{ formatMoney(item.paid_amount) }}</template>
                <template #item.is_paid="{ item }">
                  <v-chip :color="item.is_paid ? 'success' : 'warning'" size="small" label>
                    {{ item.is_paid ? "To'langan" : 'Qarz' }}
                  </v-chip>
                </template>
                <template #item.actions="{ item }">
                  <v-btn icon="mdi-eye-outline" size="small" variant="text" :to="`/visits/${item.id}`" />
                </template>
              </v-data-table>
            </v-window-item>

            <!-- Notes Tab -->
            <v-window-item value="notes">
              <div v-if="patient.notes" class="pa-2">
                <p class="text-body-2" style="white-space: pre-wrap;">{{ patient.notes }}</p>
              </div>
              <div v-else class="text-center pa-8 text-medium-emphasis">
                <v-icon size="48" class="mb-2">mdi-note-off</v-icon>
                <div>Eslatmalar yo'q</div>
              </div>
              <div v-if="patient.allergies" class="mt-4">
                <div class="text-body-2 font-weight-bold mb-2">Allergiyalar:</div>
                <v-chip v-for="al in patient.allergies.split(',')" :key="al" color="error" variant="tonal" size="small" class="mr-1 mb-1">
                  {{ al.trim() }}
                </v-chip>
              </div>
            </v-window-item>
          </v-window>
        </v-card>
      </v-col>
    </v-row>
  </div>
  <v-skeleton-loader v-else type="article" />
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { tenantApi } from '@/plugins/axios'
import dayjs from 'dayjs'

const route = useRoute()
const patient = ref(null)
const patientStats = ref({})
const visits = ref([])
const tab = ref('visits')
const visitsLoading = ref(false)
const showAppointmentDialog = ref(false)

const visitHeaders = [
  { title: 'Sana', key: 'visited_at' },
  { title: 'Shifokor', key: 'doctor' },
  { title: "To'lov", key: 'paid_amount' },
  { title: 'Holat', key: 'is_paid' },
  { title: '', key: 'actions', sortable: false, width: 60 },
]

function formatDate(d) { return d ? dayjs(d).format('DD.MM.YYYY') : '—' }
function age(d) { return d ? dayjs().diff(dayjs(d), 'year') : '' }
function formatMoney(v) {
  if (!v) return '0'
  return Number(v).toLocaleString('uz-UZ') + " so'm"
}

async function load() {
  const [pRes, sRes, vRes] = await Promise.all([
    tenantApi.get(`/patients/${route.params.id}`),
    tenantApi.get(`/patients/${route.params.id}/stats`),
    tenantApi.get(`/patients/${route.params.id}/visits`),
  ])
  patient.value = pRes.data
  patientStats.value = sRes.data
  visits.value = vRes.data.data || []
}

onMounted(load)
</script>
