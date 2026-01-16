<template>
  <q-page padding>
    <div class="row items-center q-mb-md">
      <div class="text-h5">Usuários</div>
      <q-space />
      <q-btn color="primary" icon="add" label="Novo Usuário" @click="openForm()" />
    </div>

    <q-card>
      <q-card-section>
        <div class="row q-col-gutter-md q-mb-md">
          <div class="col-12 col-md-4">
            <q-input
              v-model="filters.search"
              outlined
              dense
              placeholder="Buscar por nome ou e-mail..."
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
          <template #body-cell-tenants="props">
            <q-td :props="props">
              <q-badge
                v-for="tenant in props.row.tenants"
                :key="tenant.id"
                color="primary"
                class="q-mr-xs"
              >
                {{ tenant.name }}
              </q-badge>
              <span v-if="!props.row.tenants?.length">-</span>
            </q-td>
          </template>

          <template #body-cell-actions="props">
            <q-td :props="props">
              <q-btn flat round dense icon="edit" color="primary" @click="openForm(props.row)">
                <q-tooltip>Editar</q-tooltip>
              </q-btn>
              <q-btn flat round dense icon="group" color="secondary" @click="openTenants(props.row)">
                <q-tooltip>Unidades</q-tooltip>
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
      <q-card style="min-width: 400px">
        <q-card-section>
          <div class="text-h6">{{ editingItem ? 'Editar Usuário' : 'Novo Usuário' }}</div>
        </q-card-section>

        <q-card-section>
          <q-form @submit.prevent="save" class="q-gutter-md">
            <q-input
              v-model="form.name"
              label="Nome"
              outlined
              :rules="[val => !!val || 'Nome é obrigatório']"
            />

            <q-input
              v-model="form.email"
              label="E-mail"
              type="email"
              outlined
              :rules="[
                val => !!val || 'E-mail é obrigatório',
                val => /.+@.+\..+/.test(val) || 'E-mail inválido'
              ]"
            />

            <q-input
              v-model="form.password"
              label="Senha"
              :type="showPassword ? 'text' : 'password'"
              outlined
              :rules="editingItem ? [] : [val => !!val || 'Senha é obrigatória']"
              :hint="editingItem ? 'Deixe em branco para manter a senha atual' : ''"
            >
              <template #append>
                <q-icon
                  :name="showPassword ? 'visibility_off' : 'visibility'"
                  class="cursor-pointer"
                  @click="showPassword = !showPassword"
                />
              </template>
            </q-input>

            <q-select
              v-if="!editingItem"
              v-model="form.tenant_id"
              label="Unidade"
              :options="tenantOptions"
              option-value="id"
              :option-label="opt => `${opt.name} (${opt.organization?.name || ''})`"
              emit-value
              map-options
              outlined
              :rules="[val => !!val || 'Unidade é obrigatória']"
            />

            <q-select
              v-if="!editingItem"
              v-model="form.role_id"
              label="Perfil"
              :options="roleOptions"
              option-value="id"
              option-label="name"
              emit-value
              map-options
              outlined
              :rules="[val => !!val || 'Perfil é obrigatório']"
            />
          </q-form>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancelar" @click="formDialog = false" />
          <q-btn color="primary" label="Salvar" @click="save" :loading="saving" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Tenants Dialog -->
    <q-dialog v-model="tenantsDialog" persistent>
      <q-card style="min-width: 500px">
        <q-card-section>
          <div class="text-h6">Unidades de {{ selectedUser?.name }}</div>
        </q-card-section>

        <q-card-section>
          <q-list bordered separator>
            <q-item v-for="tenant in userTenants" :key="tenant.id">
              <q-item-section>
                <q-item-label>{{ tenant.name }} <span class="text-grey-6">({{ tenant.organization?.name }})</span></q-item-label>
                <q-item-label caption>{{ tenant.pivot?.role?.name || 'Sem perfil' }}</q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-btn flat round dense icon="delete" color="negative" @click="removeTenant(tenant.id)" />
              </q-item-section>
            </q-item>
            <q-item v-if="!userTenants.length">
              <q-item-section>
                <q-item-label class="text-grey">Nenhuma unidade vinculada</q-item-label>
              </q-item-section>
            </q-item>
          </q-list>

          <div class="q-mt-md">
            <div class="text-subtitle2 q-mb-sm">Adicionar unidade</div>
            <div class="row q-col-gutter-sm">
              <div class="col">
                <q-select
                  v-model="newTenant.tenant_id"
                  label="Unidade"
                  :options="tenantOptions"
                  option-value="id"
                  :option-label="opt => `${opt.name} (${opt.organization?.name || ''})`"
                  emit-value
                  map-options
                  outlined
                  dense
                />
              </div>
              <div class="col">
                <q-select
                  v-model="newTenant.role_id"
                  label="Perfil"
                  :options="roleOptions"
                  option-value="id"
                  option-label="name"
                  emit-value
                  map-options
                  outlined
                  dense
                />
              </div>
              <div class="col-auto">
                <q-btn color="primary" icon="add" @click="addTenant" :disable="!newTenant.tenant_id || !newTenant.role_id" />
              </div>
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Fechar" @click="tenantsDialog = false" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { userService } from 'src/services/user'
import { organizationService } from 'src/services/organization'
import { tenantService } from 'src/services/tenant'
import { roleService } from 'src/services/role'

const $q = useQuasar()

const columns = [
  { name: 'name', label: 'Nome', field: 'name', align: 'left', sortable: true },
  { name: 'email', label: 'E-mail', field: 'email', align: 'left', sortable: true },
  { name: 'tenants', label: 'Unidades', field: 'tenants', align: 'left' },
  { name: 'actions', label: 'Ações', field: 'actions', align: 'center' },
]

const items = ref([])
const loading = ref(false)
const saving = ref(false)
const formDialog = ref(false)
const tenantsDialog = ref(false)
const editingItem = ref(null)
const selectedUser = ref(null)
const userTenants = ref([])
const showPassword = ref(false)

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
  email: '',
  password: '',
  tenant_id: null,
  role_id: null,
})

const newTenant = reactive({
  tenant_id: null,
  role_id: null,
})

const organizationOptions = ref([])
const tenantOptions = ref([])
const roleOptions = ref([])

async function loadData() {
  loading.value = true
  try {
    const response = await userService.list({
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
    form.email = item.email
    form.password = ''
    form.tenant_id = null
    form.role_id = null
  } else {
    form.name = ''
    form.email = ''
    form.password = ''
    form.tenant_id = tenantOptions.value[0]?.id || null
    form.role_id = roleOptions.value[0]?.id || null
  }
  formDialog.value = true
}

async function save() {
  saving.value = true
  try {
    const data = { ...form }
    if (!data.password) delete data.password

    if (editingItem.value) {
      await userService.update(editingItem.value.id, data)
      $q.notify({ type: 'positive', message: 'Usuário atualizado com sucesso' })
    } else {
      await userService.create(data)
      $q.notify({ type: 'positive', message: 'Usuário criado com sucesso' })
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
    message: `Deseja realmente excluir o usuário "${item.name}"?`,
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      await userService.delete(item.id)
      $q.notify({ type: 'positive', message: 'Usuário excluído com sucesso' })
      loadData()
    } catch (error) {
      console.log(error)
    }
  })
}

async function openTenants(user) {
  selectedUser.value = user
  try {
    const response = await userService.getTenants(user.id)
    userTenants.value = response.data.data
  } catch (error) {
    console.log(error)
  }
  tenantsDialog.value = true
}

async function addTenant() {
  try {
    await userService.attachTenant(selectedUser.value.id, {
      tenant_id: newTenant.tenant_id,
      role_id: newTenant.role_id,
    })
    $q.notify({ type: 'positive', message: 'Unidade vinculada com sucesso' })
    newTenant.tenant_id = null
    newTenant.role_id = null
    const response = await userService.getTenants(selectedUser.value.id)
    userTenants.value = response.data.data
    loadData()
  } catch (error) {
    console.log(error)
  }
}

async function removeTenant(tenantId) {
  try {
    await userService.detachTenant(selectedUser.value.id, tenantId)
    $q.notify({ type: 'positive', message: 'Vínculo removido com sucesso' })
    const response = await userService.getTenants(selectedUser.value.id)
    userTenants.value = response.data.data
    loadData()
  } catch (error) {
    console.log(error)
  }
}

async function loadOptions() {
  try {
    const [orgsRes, tenantsRes, rolesRes] = await Promise.all([
      organizationService.list(),
      tenantService.list(),
      roleService.list(),
    ])
    organizationOptions.value = orgsRes.data.data
    tenantOptions.value = tenantsRes.data.data
    roleOptions.value = rolesRes.data.data
  } catch (error) {
    console.log(error)
  }
}

onMounted(() => {
  loadData()
  loadOptions()
})
</script>
