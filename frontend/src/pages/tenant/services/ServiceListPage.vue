<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-6">
      <div class="text-h5 font-weight-bold">Xizmatlar</div>
      <div class="d-flex gap-2">
        <v-btn variant="outlined" prepend-icon="mdi-tag-plus" @click="openCategoryDialog()">Kategoriya</v-btn>
        <v-btn color="primary" prepend-icon="mdi-plus" @click="openServiceDialog()">Yangi xizmat</v-btn>
      </div>
    </div>

    <!-- Categories filter -->
    <div class="d-flex flex-wrap gap-2 mb-4">
      <v-chip :variant="!filterCategory ? 'flat' : 'outlined'" color="primary" @click="filterCategory = null; loadServices()">Barchasi</v-chip>
      <v-chip
        v-for="cat in categories"
        :key="cat.id"
        :variant="filterCategory === cat.id ? 'flat' : 'outlined'"
        :color="cat.color || 'primary'"
        @click="filterCategory = cat.id; loadServices()"
      >
        {{ cat.name }}
        <v-avatar end size="18" class="ml-1" rounded>
          <span class="text-caption font-weight-bold">{{ cat.services_count }}</span>
        </v-avatar>
      </v-chip>
    </div>

    <v-card rounded="xl">
      <v-data-table
        :headers="headers"
        :items="services"
        :loading="loading"
        density="comfortable"
      >
        <template #item.name="{ item }">
          <div class="font-weight-medium">{{ item.name }}</div>
          <div class="text-caption text-medium-emphasis">{{ item.description }}</div>
        </template>
        <template #item.category="{ item }">
          <v-chip v-if="item.category" :color="item.category.color || 'primary'" size="small" variant="tonal">
            {{ item.category.name }}
          </v-chip>
        </template>
        <template #item.price="{ item }">
          <span class="font-weight-bold">{{ formatMoney(item.price) }}</span>
        </template>
        <template #item.duration_minutes="{ item }">{{ item.duration_minutes }} min</template>
        <template #item.is_active="{ item }">
          <v-chip :color="item.is_active ? 'success' : 'grey'" size="small" label>
            {{ item.is_active ? 'Aktiv' : 'Inaktiv' }}
          </v-chip>
        </template>
        <template #item.actions="{ item }">
          <v-btn icon="mdi-pencil-outline" size="small" variant="text" @click="openServiceDialog(item)" />
          <v-btn icon="mdi-delete-outline" size="small" variant="text" color="error" @click="deleteService(item)" />
        </template>
      </v-data-table>
    </v-card>

    <!-- Service Dialog -->
    <v-dialog v-model="serviceDialog" max-width="560">
      <v-card rounded="xl">
        <v-card-title class="pa-4">{{ editService ? 'Xizmatni tahrirlash' : 'Yangi xizmat' }}</v-card-title>
        <v-card-text class="pa-4">
          <v-row>
            <v-col cols="12">
              <v-text-field v-model="sForm.name" label="Nomi *" variant="outlined" />
            </v-col>
            <v-col cols="12" sm="6">
              <v-select v-model="sForm.category_id" :items="categoryItems" label="Kategoriya" variant="outlined" clearable />
            </v-col>
            <v-col cols="12" sm="6">
              <v-text-field v-model.number="sForm.price" label="Narx (so'm) *" type="number" variant="outlined" />
            </v-col>
            <v-col cols="12" sm="6">
              <v-text-field v-model.number="sForm.duration_minutes" label="Davomiyligi (daqiqa)" type="number" variant="outlined" />
            </v-col>
            <v-col cols="12" sm="6">
              <v-switch v-model="sForm.is_active" label="Aktiv" color="success" hide-details />
            </v-col>
            <v-col cols="12">
              <v-textarea v-model="sForm.description" label="Tavsif" variant="outlined" rows="2" />
            </v-col>
          </v-row>
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-spacer />
          <v-btn @click="serviceDialog = false">Bekor</v-btn>
          <v-btn color="primary" :loading="saving" @click="saveService">Saqlash</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Category Dialog -->
    <v-dialog v-model="categoryDialog" max-width="400">
      <v-card rounded="xl">
        <v-card-title class="pa-4">{{ editCategory ? 'Kategoriyani tahrirlash' : 'Yangi kategoriya' }}</v-card-title>
        <v-card-text class="pa-4">
          <v-text-field v-model="cForm.name" label="Nomi *" variant="outlined" class="mb-3" />
          <v-text-field v-model="cForm.color" label="Rang (hex)" variant="outlined" :style="`border-left: 4px solid ${cForm.color};`" />
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-spacer />
          <v-btn @click="categoryDialog = false">Bekor</v-btn>
          <v-btn color="primary" :loading="saving" @click="saveCategory">Saqlash</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color">{{ snackbar.text }}</v-snackbar>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { tenantApi } from '@/plugins/axios'

const services = ref([])
const categories = ref([])
const loading = ref(false)
const saving = ref(false)
const filterCategory = ref(null)
const serviceDialog = ref(false)
const categoryDialog = ref(false)
const editService = ref(null)
const editCategory = ref(null)
const snackbar = ref({ show: false, text: '', color: 'success' })

const sForm = reactive({ name: '', category_id: null, price: 0, duration_minutes: 30, description: '', is_active: true })
const cForm = reactive({ name: '', color: '#1565C0', icon: '' })

const headers = [
  { title: 'Nomi', key: 'name' },
  { title: 'Kategoriya', key: 'category' },
  { title: 'Narx', key: 'price', width: 140 },
  { title: 'Davomiylik', key: 'duration_minutes', width: 120 },
  { title: 'Holat', key: 'is_active', width: 100 },
  { title: 'Amallar', key: 'actions', sortable: false, width: 100 },
]

const categoryItems = computed(() => categories.value.map(c => ({ title: c.name, value: c.id })))

function formatMoney(v) { return v ? Number(v).toLocaleString('uz-UZ') + " so'm" : '0' }

function openServiceDialog(item = null) {
  editService.value = item
  if (item) {
    Object.assign(sForm, { name: item.name, category_id: item.category_id, price: item.price, duration_minutes: item.duration_minutes, description: item.description, is_active: item.is_active })
  } else {
    Object.assign(sForm, { name: '', category_id: null, price: 0, duration_minutes: 30, description: '', is_active: true })
  }
  serviceDialog.value = true
}

function openCategoryDialog(item = null) {
  editCategory.value = item
  if (item) Object.assign(cForm, { name: item.name, color: item.color || '#1565C0' })
  else Object.assign(cForm, { name: '', color: '#1565C0' })
  categoryDialog.value = true
}

async function loadServices() {
  loading.value = true
  try {
    const params = { per_page: 100 }
    if (filterCategory.value) params.category_id = filterCategory.value
    const res = await tenantApi.get('/services', { params })
    services.value = res.data.data || []
  } finally { loading.value = false }
}

async function loadCategories() {
  const res = await tenantApi.get('/service-categories')
  categories.value = res.data
}

async function saveService() {
  saving.value = true
  try {
    if (editService.value) {
      await tenantApi.put(`/services/${editService.value.id}`, sForm)
    } else {
      await tenantApi.post('/services', sForm)
    }
    snackbar.value = { show: true, text: 'Saqlandi', color: 'success' }
    serviceDialog.value = false
    loadServices()
  } catch {
    snackbar.value = { show: true, text: 'Xatolik', color: 'error' }
  } finally { saving.value = false }
}

async function saveCategory() {
  saving.value = true
  try {
    if (editCategory.value) {
      await tenantApi.put(`/service-categories/${editCategory.value.id}`, cForm)
    } else {
      await tenantApi.post('/service-categories', cForm)
    }
    snackbar.value = { show: true, text: 'Saqlandi', color: 'success' }
    categoryDialog.value = false
    loadCategories()
    loadServices()
  } catch {
    snackbar.value = { show: true, text: 'Xatolik', color: 'error' }
  } finally { saving.value = false }
}

async function deleteService(item) {
  if (!confirm(`"${item.name}" xizmatini o'chirasizmi?`)) return
  await tenantApi.delete(`/services/${item.id}`)
  loadServices()
}

onMounted(async () => {
  await loadCategories()
  loadServices()
})
</script>
