<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-6">
      <div class="text-h5 font-weight-bold">Foydalanuvchilar</div>
      <v-btn color="primary" prepend-icon="mdi-plus" @click="openDialog()">Yangi foydalanuvchi</v-btn>
    </div>

    <v-card rounded="xl">
      <v-data-table :headers="headers" :items="users" :loading="loading" density="comfortable">
        <template #item.name="{ item }">
          <div class="d-flex align-center gap-2">
            <v-avatar size="32" color="primary" variant="tonal">
              <span class="text-caption">{{ item.name?.charAt(0)?.toUpperCase() }}</span>
            </v-avatar>
            <div>
              <div class="font-weight-medium">{{ item.name }}</div>
              <div class="text-caption text-medium-emphasis">{{ item.email }}</div>
            </div>
          </div>
        </template>
        <template #item.role="{ item }">
          <v-chip :color="roleColor(item.role)" size="small" label>{{ roleLabel(item.role) }}</v-chip>
        </template>
        <template #item.is_active="{ item }">
          <v-switch :model-value="item.is_active" color="success" hide-details density="compact" @change="toggleActive(item)" />
        </template>
        <template #item.actions="{ item }">
          <v-btn icon="mdi-pencil-outline" size="small" variant="text" @click="openDialog(item)" />
          <v-btn icon="mdi-key-outline" size="small" variant="text" color="warning" @click="openPasswordDialog(item)" />
          <v-btn icon="mdi-delete-outline" size="small" variant="text" color="error" @click="deleteUser(item)" />
        </template>
      </v-data-table>
    </v-card>

    <!-- User Form Dialog -->
    <v-dialog v-model="dialog" max-width="480">
      <v-card rounded="xl">
        <v-card-title class="pa-4">{{ editUser ? 'Tahrirlash' : 'Yangi foydalanuvchi' }}</v-card-title>
        <v-card-text class="pa-4">
          <v-text-field v-model="form.name" label="Ism *" variant="outlined" class="mb-3" />
          <v-text-field v-model="form.email" label="Email *" variant="outlined" type="email" class="mb-3" />
          <v-select v-model="form.role" :items="roleItems" label="Rol *" variant="outlined" class="mb-3" />
          <v-text-field v-if="form.phone !== undefined" v-model="form.phone" label="Telefon" variant="outlined" class="mb-3" />
          <v-text-field v-if="!editUser" v-model="form.password" label="Parol *" variant="outlined" type="password" />
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-spacer />
          <v-btn @click="dialog = false">Bekor</v-btn>
          <v-btn color="primary" :loading="saving" @click="saveUser">Saqlash</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Password Dialog -->
    <v-dialog v-model="passwordDialog" max-width="400">
      <v-card rounded="xl">
        <v-card-title class="pa-4">Parolni o'zgartirish</v-card-title>
        <v-card-text class="pa-4">
          <v-text-field v-model="newPassword" label="Yangi parol *" variant="outlined" type="password" />
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-spacer />
          <v-btn @click="passwordDialog = false">Bekor</v-btn>
          <v-btn color="primary" :loading="saving" @click="changePassword">O'zgartirish</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color">{{ snackbar.text }}</v-snackbar>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { tenantApi } from '@/plugins/axios'

const users = ref([])
const loading = ref(false)
const saving = ref(false)
const dialog = ref(false)
const passwordDialog = ref(false)
const editUser = ref(null)
const passwordTarget = ref(null)
const newPassword = ref('')
const snackbar = ref({ show: false, text: '', color: 'success' })

const form = reactive({ name: '', email: '', role: 'receptionist', phone: '', password: '' })

const headers = [
  { title: 'Foydalanuvchi', key: 'name' },
  { title: 'Rol', key: 'role', width: 140 },
  { title: 'Aktiv', key: 'is_active', width: 80 },
  { title: 'Amallar', key: 'actions', sortable: false, width: 120 },
]

const roleItems = [
  { title: 'Admin', value: 'admin' },
  { title: 'Shifokor', value: 'doctor' },
  { title: 'Hamshira', value: 'nurse' },
  { title: 'Kassir', value: 'cashier' },
  { title: 'Registrator', value: 'receptionist' },
]

function roleColor(r) { return { admin: 'error', doctor: 'primary', nurse: 'info', cashier: 'success', receptionist: 'secondary' }[r] || 'grey' }
function roleLabel(r) { return { admin: 'Admin', doctor: 'Shifokor', nurse: 'Hamshira', cashier: 'Kassir', receptionist: 'Registrator' }[r] || r }

async function load() {
  loading.value = true
  try {
    const res = await tenantApi.get('/settings/users')
    users.value = res.data.data || res.data
  } finally { loading.value = false }
}

function openDialog(user = null) {
  editUser.value = user
  if (user) Object.assign(form, { name: user.name, email: user.email, role: user.role, phone: user.phone || '', password: '' })
  else Object.assign(form, { name: '', email: '', role: 'receptionist', phone: '', password: '' })
  dialog.value = true
}

function openPasswordDialog(user) {
  passwordTarget.value = user
  newPassword.value = ''
  passwordDialog.value = true
}

async function saveUser() {
  saving.value = true
  try {
    if (editUser.value) await tenantApi.put(`/settings/users/${editUser.value.id}`, { name: form.name, email: form.email, role: form.role, phone: form.phone })
    else await tenantApi.post('/settings/users', form)
    snackbar.value = { show: true, text: 'Saqlandi', color: 'success' }
    dialog.value = false
    load()
  } catch {
    snackbar.value = { show: true, text: 'Xatolik', color: 'error' }
  } finally { saving.value = false }
}

async function changePassword() {
  if (!newPassword.value || newPassword.value.length < 6) {
    snackbar.value = { show: true, text: 'Kamida 6 ta belgi', color: 'error' }
    return
  }
  saving.value = true
  try {
    await tenantApi.put(`/settings/users/${passwordTarget.value.id}`, { password: newPassword.value })
    snackbar.value = { show: true, text: 'Parol o\'zgartirildi', color: 'success' }
    passwordDialog.value = false
  } catch {
    snackbar.value = { show: true, text: 'Xatolik', color: 'error' }
  } finally { saving.value = false }
}

async function toggleActive(user) {
  await tenantApi.put(`/settings/users/${user.id}`, { is_active: !user.is_active })
  user.is_active = !user.is_active
}

async function deleteUser(user) {
  if (!confirm(`"${user.name}" ni o'chirasizmi?`)) return
  await tenantApi.delete(`/settings/users/${user.id}`)
  load()
}

onMounted(load)
</script>
