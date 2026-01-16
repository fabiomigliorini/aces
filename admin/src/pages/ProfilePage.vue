<template>
  <q-page padding>
    <div class="text-h5 q-mb-md">Meu Perfil</div>

    <div class="row q-col-gutter-md">
      <div class="col-12 col-md-4">
        <q-card flat bordered>
          <q-card-section class="text-center">
            <q-avatar size="100px" color="primary" text-color="white">
              {{ userInitials }}
            </q-avatar>
            <div class="text-h6 q-mt-md">{{ authStore.user?.name }}</div>
            <div class="text-body2 text-grey-7">{{ authStore.user?.email }}</div>

            <q-chip
              v-for="role in authStore.roles"
              :key="role"
              color="primary"
              text-color="white"
              size="sm"
              class="q-mt-sm"
            >
              {{ role }}
            </q-chip>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-md-8">
        <q-card flat bordered>
          <q-card-section>
            <div class="text-h6 q-mb-md">Informações Pessoais</div>

            <q-form @submit.prevent="handleUpdateProfile" class="q-gutter-md">
              <q-input
                v-model="form.name"
                label="Nome"
                outlined
                :rules="[(val) => !!val || 'Nome é obrigatório']"
              />

              <q-input
                v-model="form.email"
                type="email"
                label="E-mail"
                outlined
                disable
                hint="E-mail não pode ser alterado"
              />

              <q-btn
                type="submit"
                color="primary"
                :loading="loading"
                label="Salvar alterações"
              />
            </q-form>
          </q-card-section>
        </q-card>

        <q-card flat bordered class="q-mt-md">
          <q-card-section>
            <div class="text-h6 q-mb-md">Alterar Senha</div>

            <q-form @submit.prevent="handleUpdatePassword" class="q-gutter-md">
              <q-input
                v-model="passwordForm.current_password"
                :type="showCurrentPassword ? 'text' : 'password'"
                label="Senha atual"
                outlined
                :rules="[(val) => !!val || 'Senha atual é obrigatória']"
              >
                <template #append>
                  <q-icon
                    :name="showCurrentPassword ? 'visibility_off' : 'visibility'"
                    class="cursor-pointer"
                    @click="showCurrentPassword = !showCurrentPassword"
                  />
                </template>
              </q-input>

              <q-input
                v-model="passwordForm.password"
                :type="showNewPassword ? 'text' : 'password'"
                label="Nova senha"
                outlined
                :rules="[
                  (val) => !!val || 'Nova senha é obrigatória',
                  (val) => val.length >= 8 || 'Mínimo 8 caracteres',
                ]"
              >
                <template #append>
                  <q-icon
                    :name="showNewPassword ? 'visibility_off' : 'visibility'"
                    class="cursor-pointer"
                    @click="showNewPassword = !showNewPassword"
                  />
                </template>
              </q-input>

              <q-input
                v-model="passwordForm.password_confirmation"
                :type="showNewPassword ? 'text' : 'password'"
                label="Confirmar nova senha"
                outlined
                :rules="[
                  (val) => !!val || 'Confirmação é obrigatória',
                  (val) => val === passwordForm.password || 'Senhas não conferem',
                ]"
              />

              <q-btn
                type="submit"
                color="primary"
                :loading="loadingPassword"
                label="Alterar senha"
              />
            </q-form>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useAuthStore } from 'src/stores/auth'
import { useUIStore } from 'src/stores/ui'
import { api } from 'src/services/api'

const authStore = useAuthStore()
const uiStore = useUIStore()

const loading = ref(false)
const loadingPassword = ref(false)
const showCurrentPassword = ref(false)
const showNewPassword = ref(false)

const form = reactive({
  name: '',
  email: '',
})

const passwordForm = reactive({
  current_password: '',
  password: '',
  password_confirmation: '',
})

const userInitials = computed(() => {
  const name = authStore.user?.name || ''
  return name
    .split(' ')
    .map((n) => n[0])
    .join('')
    .substring(0, 2)
    .toUpperCase()
})

onMounted(() => {
  form.name = authStore.user?.name || ''
  form.email = authStore.user?.email || ''
})

async function handleUpdateProfile() {
  loading.value = true

  try {
    await api.put('/api/user/profile', {
      name: form.name,
    })

    await authStore.fetchUser()
    uiStore.notifySuccess('Perfil atualizado com sucesso!')
  } catch (error) {
    console.log(error)
  } finally {
    loading.value = false
  }
}

async function handleUpdatePassword() {
  loadingPassword.value = true

  try {
    await api.put('/api/user/password', {
      current_password: passwordForm.current_password,
      password: passwordForm.password,
      password_confirmation: passwordForm.password_confirmation,
    })

    passwordForm.current_password = ''
    passwordForm.password = ''
    passwordForm.password_confirmation = ''

    uiStore.notifySuccess('Senha alterada com sucesso!')
  } catch (error) {
    console.log(error)
  } finally {
    loadingPassword.value = false
  }
}
</script>
