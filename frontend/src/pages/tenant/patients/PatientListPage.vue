<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-6">
      <div class="text-h5 font-weight-bold">Bemorlar</div>
      <v-btn color="primary" prepend-icon="mdi-plus" to="/patients/new">Yangi bemor</v-btn>
    </div>

    <v-card rounded="xl">
      <!-- Filters -->
      <div class="d-flex flex-wrap gap-3 pa-4">
        <v-text-field
          v-model="search"
          prepend-inner-icon="mdi-magnify"
          placeholder="Ism yoki telefon bo'yicha qidirish..."
          variant="outlined"
          density="compact"
          hide-details
          clearable
          style="max-width: 320px;"
          @update:model-value="debouncedLoad"
        />
        <v-select
          v-model="filterGender"
          :items="genderItems"
          label="Jins"
          variant="outlined"
          density="compact"
          hide-details
          clearable
          style="max-width: 160px;"
          @update:model-value="loadPatients"
        />
        <v-select
          v-model="filterBlood"
          :items="bloodTypes"
          label="Qon guruhi"
          variant="outlined"
          density="compact"
          hide-details
          clearable
          style="max-width: 160px;"
          @update:model-value="loadPatients"
        />
      </div>

      <v-data-table-server
        v-model:items-per-page="perPage"
        :headers="headers"
        :items="patients"
        :items-length="total"
        :loading="loading"
        :page="page"
        @update:page="p => { page = p; loadPatients() }"
        @update:items-per-page="p => { perPage = p; loadPatients() }"
      >
        <template #item.full_name="{ item }">
          <div class="d-flex align-center py-2">
            <v-avatar size="32" color="primary" class="mr-3">
              <span class="text-caption text-white font-weight-bold">{{ item.full_name?.[0] }}</span>
            </v-avatar>
            <div>
              <router-link :to="`/patients/${item.id}`" class="text-decoration-none font-weight-medium">
                {{ item.full_name }}
              </router-link>
              <div class="text-caption text-medium-emphasis">#{{ item.id }}</div>
            </div>
          </div>
        </template>
        <template #item.gender="{ item }">
          <v-chip :color="item.gender === 'male' ? 'blue' : 'pink'" size="small" variant="tonal">
            {{ item.gender === 'male' ? 'Erkak' : 'Ayol' }}
          </v-chip>
        </template>
        <template #item.birth_date="{ item }">
          <span>{{ item.birth_date ? formatDate(item.birth_date) : '—' }}</span>
          <span class="text-caption text-medium-emphasis ml-1" v-if="item.birth_date">({{ age(item.birth_date) }} yosh)</span>
        </template>
        <template #item.blood_type="{ item }">
          <v-chip v-if="item.blood_type" size="small" color="error" variant="tonal">{{ item.blood_type }}</v-chip>
          <span v-else class="text-medium-emphasis">—</span>
        </template>
        <template #item.actions="{ item }">
          <v-btn icon="mdi-eye-outline" variant="text" size="small" :to="`/patients/${item.id}`" />
          <v-btn icon="mdi-pencil-outline" variant="text" size="small" :to="`/patients/${item.id}/edit`" />
          <v-btn icon="mdi-delete-outline" variant="text" size="small" color="error" @click="confirmDelete(item)" />
        </template>
      </v-data-table-server>
    </v-card>

    <!-- Delete Confirm Dialog -->
    <v-dialog v-model="deleteDialog" max-width="400">
      <v-card rounded="xl">
        <v-card-title>Bemorni o'chirish?</v-card-title>
        <v-card-text>
          <strong>{{ deleteTarget?.full_name }}</strong> bemorini o'chirmoqchimisiz?
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn @click="deleteDialog = false">Bekor</v-btn>
          <v-btn color="error" :loading="deleting" @click="doDelete">O'chirish</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color">{{ snackbar.text }}</v-snackbar>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { tenantApi } from '@/plugins/axios'
import dayjs from 'dayjs'

const patients = ref([])
const total = ref(0)
const loading = ref(false)
const page = ref(1)
const perPage = ref(15)
const search = ref('')
const filterGender = ref(null)
const filterBlood = ref(null)
const deleteDialog = ref(false)
const deleteTarget = ref(null)
const deleting = ref(false)
const snackbar = ref({ show: false, text: '', color: 'success' })

let searchTimer = null
function debouncedLoad() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(loadPatients, 400)
}

const headers = [
  { title: 'Ism', key: 'full_name', sortable: false },
  { title: 'Jins', key: 'gender', sortable: false, width: 100 },
  { title: "Tug'ilgan sana", key: 'birth_date', sortable: false },
  { title: 'Telefon', key: 'phone', sortable: false },
  { title: 'Qon guruhi', key: 'blood_type', sortable: false, width: 110 },
  { title: 'Amallar', key: 'actions', sortable: false, width: 120 },
]

const genderItems = [
  { title: 'Erkak', value: 'male' },
  { title: 'Ayol', value: 'female' },
]
const bloodTypes = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-']

function formatDate(d) { return dayjs(d).format('DD.MM.YYYY') }
function age(d) { return dayjs().diff(dayjs(d), 'year') }

async function loadPatients() {
  loading.value = true
  try {
    const params = { page: page.value, per_page: perPage.value }
    if (search.value) params.search = search.value
    if (filterGender.value) params.gender = filterGender.value
    if (filterBlood.value) params.blood_type = filterBlood.value

    const res = await tenantApi.get('/patients', { params })
    patients.value = res.data.data
    total.value = res.data.total
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

function confirmDelete(item) {
  deleteTarget.value = item
  deleteDialog.value = true
}

async function doDelete() {
  deleting.value = true
  try {
    await tenantApi.delete(`/patients/${deleteTarget.value.id}`)
    snackbar.value = { show: true, text: 'Bemor o\'chirildi', color: 'success' }
    deleteDialog.value = false
    loadPatients()
  } catch {
    snackbar.value = { show: true, text: 'Xatolik yuz berdi', color: 'error' }
  } finally {
    deleting.value = false
  }
}

onMounted(loadPatients)
</script>
