<template>
  <q-page padding>
    <div class="row items-center q-mb-md">
      <div class="text-h5">Organizações</div>
      <q-space />
      <q-btn color="primary" icon="add" label="Nova Organização" @click="openForm()" />
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
          <div class="col-12 col-md-3">
            <q-select
              v-model="filters.is_active"
              label="Status"
              :options="statusOptions"
              emit-value
              map-options
              outlined
              dense
              clearable
              @update:model-value="loadData"
            />
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
          <template #body-cell-is_active="props">
            <q-td :props="props">
              <q-badge :color="props.row.is_active ? 'positive' : 'negative'">
                {{ props.row.is_active ? 'Ativo' : 'Inativo' }}
              </q-badge>
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
      <q-card style="min-width: 400px">
        <q-card-section>
          <div class="text-h6">{{ editingItem ? 'Editar Organização' : 'Nova Organização' }}</div>
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
              v-model="form.slug"
              label="Slug"
              outlined
              hint="Deixe em branco para gerar automaticamente"
            />

            <q-toggle
              v-model="form.is_active"
              label="Ativo"
            />
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
import { organizationService } from 'src/services/organization'

const $q = useQuasar()

const columns = [
  { name: 'name', label: 'Nome', field: 'name', align: 'left', sortable: true },
  { name: 'slug', label: 'Slug', field: 'slug', align: 'left' },
  { name: 'is_active', label: 'Status', field: 'is_active', align: 'center' },
  { name: 'actions', label: 'Ações', field: 'actions', align: 'center' },
]

const statusOptions = [
  { label: 'Ativo', value: true },
  { label: 'Inativo', value: false },
]

const items = ref([])
const loading = ref(false)
const saving = ref(false)
const formDialog = ref(false)
const editingItem = ref(null)

const filters = reactive({
  search: '',
  is_active: null,
})

const pagination = ref({
  page: 1,
  rowsPerPage: 15,
  rowsNumber: 0,
})

const form = reactive({
  name: '',
  slug: '',
  is_active: true,
})

async function loadData() {
  loading.value = true
  try {
    const params = {
      page: pagination.value.page,
      per_page: pagination.value.rowsPerPage,
      search: filters.search,
    }
    if (filters.is_active !== null) {
      params.is_active = filters.is_active
    }
    const response = await organizationService.list(params)
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
    form.slug = item.slug
    form.is_active = item.is_active
  } else {
    form.name = ''
    form.slug = ''
    form.is_active = true
  }
  formDialog.value = true
}

async function save() {
  saving.value = true
  try {
    if (editingItem.value) {
      await organizationService.update(editingItem.value.id, form)
      $q.notify({ type: 'positive', message: 'Organização atualizada com sucesso' })
    } else {
      await organizationService.create(form)
      $q.notify({ type: 'positive', message: 'Organização criada com sucesso' })
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
    message: `Deseja realmente excluir a organização "${item.name}"?`,
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      await organizationService.delete(item.id)
      $q.notify({ type: 'positive', message: 'Organização excluída com sucesso' })
      loadData()
    } catch (error) {
      console.log(error)
    }
  })
}

onMounted(() => {
  loadData()
})
</script>
