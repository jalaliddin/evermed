<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-6">
      <div class="text-h5 font-weight-bold">Shifokorlar</div>
      <v-btn color="primary" prepend-icon="mdi-plus" to="/doctors/new">Yangi shifokor</v-btn>
    </div>

    <v-row>
      <v-col v-for="doc in doctors" :key="doc.id" cols="12" sm="6" lg="4">
        <v-card rounded="xl" hover :to="`/doctors/${doc.id}`">
          <v-card-text class="pa-5">
            <div class="d-flex align-center mb-3">
              <v-avatar color="primary" size="52" class="mr-3">
                <v-icon size="28" color="white">mdi-doctor</v-icon>
              </v-avatar>
              <div class="flex-grow-1">
                <div class="font-weight-bold text-body-1">Dr. {{ doc.user?.name }}</div>
                <div class="text-caption text-medium-emphasis">{{ doc.specialization }}</div>
              </div>
              <v-chip :color="doc.is_active ? 'success' : 'error'" size="small" variant="tonal">
                {{ doc.is_active ? 'Aktiv' : 'Inaktiv' }}
              </v-chip>
            </div>
            <v-divider class="mb-3" />
            <div class="d-flex gap-3">
              <div class="text-center flex-1">
                <div class="text-caption text-medium-emphasis">Xona</div>
                <div class="font-weight-bold">{{ doc.room_number || '—' }}</div>
              </div>
              <v-divider vertical />
              <div class="text-center flex-1">
                <div class="text-caption text-medium-emphasis">Narx</div>
                <div class="font-weight-bold">{{ formatMoney(doc.consultation_price) }}</div>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" v-if="!loading && doctors.length === 0">
        <v-card rounded="xl" class="text-center pa-8">
          <v-icon size="64" color="grey-lighten-2" class="mb-3">mdi-doctor</v-icon>
          <div class="text-h6 text-medium-emphasis mb-2">Shifokorlar yo'q</div>
          <v-btn color="primary" to="/doctors/new">Shifokor qo'shish</v-btn>
        </v-card>
      </v-col>
    </v-row>

    <div class="d-flex justify-center mt-4" v-if="loading">
      <v-progress-circular indeterminate color="primary" />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { tenantApi } from '@/plugins/axios'

const doctors = ref([])
const loading = ref(false)

function formatMoney(v) {
  return v ? Number(v).toLocaleString('uz-UZ') + " so'm" : '0'
}

async function load() {
  loading.value = true
  try {
    const res = await tenantApi.get('/doctors?per_page=50')
    doctors.value = res.data.data || []
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>
