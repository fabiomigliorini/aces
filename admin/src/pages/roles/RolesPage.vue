<template>
  <q-page padding>
    <div class="row items-center q-mb-md">
      <div class="text-h5">Perfis de Acesso</div>
      <q-space />
      <q-btn color="primary" icon="add" label="Novo Perfil" @click="openForm()" />
    </div>

    <q-card>
      <q-card-section>
        <div class="row q-col-gutter-md q-mb-md">
          <div class="col-12 col-md-4">
            <q-input
              v-model="filters.search"
              outlined
              dense
              placeholder="Buscar..."
              debounce="300"
              @update:model-value="loadData"
            >
              <template #prepend>
                <q-icon name="search" />
              </template>
            </q-input>
          </div>
        </div>

        <q-table
          :rows="items"
          :columns="columns"
          :loading="loading"
          row-key="id"
          :pagination="pagination"
          @request="onRequest"
          flat
          bordered
        >
          <template #body-cell-organization="props">
            <q-td :props="props">
              {{ props.row.organization?.name || '-' }}
            </q-td>
          </template>

          <template #body-cell-is_admin="props">
            <q-td :props="props">
              <q-badge :color="props.row.is_admin ? 'primary' : 'grey'">
                {{ props.row.is_admin ? 'Admin' : 'Comum' }}
              </q-badge>
            </q-td>
          </template>

          <template #body-cell-permissions="props">
            <q-td :props="props">
              <span v-if="props.row.is_admin" class="text-grey-6">Todas</span>
              <span v-else>{{ props.row.permissions?.length || 0 }} permissões</span>
            </q-td>
          </template>

          <template #body-cell-actions="props">
            <q-td :props="props">
              <q-btn flat round dense icon="edit" color="primary" @click="openForm(props.row)">
                <q-tooltip>Editar</q-tooltip>
              </q-btn>
              <q-btn flat round dense icon="delete" color="negative" @click="confirmDelete(props.row)">
                <q-tooltip>Excluir</q-tooltip>
              </q-btn>
            </q-td>
          </template>
        </q-table>
      </q-card-section>
    </q-card>

    <!-- Form Dialog -->
    <q-dialog v-model="formDialog" persistent>
      <q-card style="min-width: 500px">
        <q-card-section>
          <div class="text-h6">{{ editingItem ? 'Editar Perfil' : 'Novo Perfil' }}</div>
        </q-card-section>

        <q-card-section>
          <q-form @submit.prevent="save" class="q-gutter-md">
            <q-input
              v-model="form.name"
              label="Nome"
              outlined
              :rules="[val => !!val || 'Nome é obrigatório']"
            />

            <q-select
              v-if="!editingItem"
              v-model="form.organization_id"
              label="Organização"
              :options="organizationOptions"
              option-value="id"
              option-label="name"
              emit-value
              map-options
              outlined
              :rules="[val => !!val || 'Organização é obrigatória']"
            />

            <q-toggle
              v-model="form.is_admin"
              label="Administrador (todas as permissões)"
            />

            <div v-if="!form.is_admin">
              <div class="text-subtitle2 q-mb-sm">Permissões</div>
              <div class="row">
                <div v-for="(label, key) in availablePermissions" :key="key" class="col-12 col-md-6">
                  <q-checkbox
                    v-model="form.permissions"
                    :val="key"
                    :label="label"
                    dense
                  />
                </div>
              </div>
            </div>
          </q-form>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancelar" @click="formDialog = false" />
          <q-btn color="primary" label="Salvar" @click="save" :loading="saving" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { roleService } from 'src/services/role'
import { organizationService } from 'src/services/organization'

const $q = useQuasar()

const columns = [
  { name: 'name', label: 'Nome', field: 'name', align: 'left', sortable: true },
  { name: 'organization', label: 'Organização', field: 'organization', align: 'left' },
  { name: 'is_admin', label: 'Tipo', field: 'is_admin', align: 'center' },
  { name: 'permissions', label: 'Permissões', field: 'permissions', align: 'center' },
  { name: 'actions', label: 'Ações', field: 'actions', align: 'center' },
]

const items = ref([])
const loading = ref(false)
const saving = ref(false)
const formDialog = ref(false)
const editingItem = ref(null)
const availablePermissions = ref({})

const filters = reactive({
  search: '',
})

const pagination = ref({
  page: 1,
  rowsPerPage: 15,
  rowsNumber: 0,
})

const form = reactive({
  name: '',
  organization_id: null,
  is_admin: false,
  permissions: [],
})

const organizationOptions = ref([])

async function loadData() {
  loading.value = true
  try {
    const response = await roleService.list({
      page: pagination.value.page,
      per_page: pagination.value.rowsPerPage,
      search: filters.search,
    })
    items.value = response.data.data
    pagination.value.rowsNumber = response.data.total
  } catch (error) {
    console.log(error)
  } finally {
    loading.value = false
  }
}

function onRequest(props) {
  pagination.value.page = props.pagination.page
  pagination.value.rowsPerPage = props.pagination.rowsPerPage
  loadData()
}

function openForm(item = null) {
  editingItem.value = item
  if (item) {
    form.name = item.name
    form.organization_id = item.organization_id
    form.is_admin = item.is_admin
    form.permissions = item.permissions || []
  } else {
    form.name = ''
    form.organization_id = organizationOptions.value[0]?.id || null
    form.is_admin = false
    form.permissions = []
  }
  formDialog.value = true
}

async function save() {
  saving.value = true
  try {
    const data = {
      name: form.name,
      is_admin: form.is_admin,
      permissions: form.is_admin ? [] : form.permissions,
    }

    if (!editingItem.value) {
      data.organization_id = form.organization_id
    }

    if (editingItem.value) {
      await roleService.update(editingItem.value.id, data)
      $q.notify({ type: 'positive', message: 'Perfil atualizado com sucesso' })
    } else {
      await roleService.create(data)
      $q.notify({ type: 'positive', message: 'Perfil criado com sucesso' })
    }
    formDialog.value = false
    loadData()
  } catch (error) {
    console.log(error)
  } finally {
    saving.value = false
  }
}

function confirmDelete(item) {
  $q.dialog({
    title: 'Confirmar exclusão',
    message: `Deseja realmente excluir o perfil "${item.name}"?`,
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      await roleService.delete(item.id)
      $q.notify({ type: 'positive', message: 'Perfil excluído com sucesso' })
      loadData()
    } catch (error) {
      console.log(error)
    }
  })
}

async function loadOptions() {
  try {
    const [orgsRes, permsRes] = await Promise.all([
      organizationService.list(),
      roleService.permissions(),
    ])
    organizationOptions.value = orgsRes.data.data
    availablePermissions.value = permsRes.data.data
  } catch (error) {
    console.log(error)
  }
}

onMounted(() => {
  loadData()
  loadOptions()
})
</script>
