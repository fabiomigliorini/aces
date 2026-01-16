<template>
  <q-page class="flex flex-center bg-grey-2">
    <div class="reset-container">
      <div class="text-center q-mb-lg">
        <div class="text-h4 text-primary text-weight-bold">ACES</div>
        <div class="text-subtitle2 text-grey-7">Redefinir Senha</div>
      </div>

      <q-card flat bordered class="reset-card">
        <q-card-section>
          <div class="text-h6 q-mb-md">Nova senha</div>

          <q-form @submit.prevent="handleResetPassword" class="q-gutter-md">
            <q-input
              v-model="form.email"
              type="email"
              label="E-mail"
              outlined
              :rules="[
                (val) => !!val || 'E-mail é obrigatório',
                (val) => /.+@.+\..+/.test(val) || 'E-mail inválido',
              ]"
            >
              <template #prepend>
                <q-icon name="email" />
              </template>
            </q-input>

            <q-input
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              label="Nova senha"
              outlined
              :rules="[
                (val) => !!val || 'Senha é obrigatória',
                (val) => val.length >= 8 || 'Mínimo 8 caracteres',
              ]"
            >
              <template #prepend>
                <q-icon name="lock" />
              </template>
              <template #append>
                <q-icon
                  :name="showPassword ? 'visibility_off' : 'visibility'"
                  class="cursor-pointer"
                  @click="showPassword = !showPassword"
                />
              </template>
            </q-input>

            <q-input
              v-model="form.password_confirmation"
              :type="showPassword ? 'text' : 'password'"
              label="Confirmar senha"
              outlined
              :rules="[
                (val) => !!val || 'Confirmação é obrigatória',
                (val) => val === form.password || 'Senhas não conferem',
              ]"
            >
              <template #prepend>
                <q-icon name="lock" />
              </template>
            </q-input>

            <q-btn
              type="submit"
              color="primary"
              class="full-width"
              size="lg"
              :loading="loading"
              label="Redefinir senha"
            />

            <div class="text-center">
              <router-link :to="{ name: 'login' }" class="text-primary">
                Voltar ao login
              </router-link>
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </div>
  </q-page>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { authService } from 'src/services/auth'
import { useUIStore } from 'src/stores/ui'

const router = useRouter()
const route = useRoute()
const uiStore = useUIStore()

const loading = ref(false)
const showPassword = ref(false)

const form = reactive({
  email: '',
  password: '',
  password_confirmation: '',
  token: '',
})

onMounted(() => {
  form.token = route.query.token || ''
  form.email = route.query.email || ''
})

async function handleResetPassword() {
  loading.value = true

  try {
    await authService.resetPassword({
      email: form.email,
      password: form.password,
      password_confirmation: form.password_confirmation,
      token: form.token,
    })

    uiStore.notifySuccess('Senha redefinida com sucesso!')
    router.push({ name: 'login' })
  } catch (error) {
    console.log(error)
  } finally {
    loading.value = false
  }
}
</script>

<style lang="scss" scoped>
.reset-container {
  width: 100%;
  max-width: 400px;
  padding: 16px;
}

.reset-card {
  border-radius: 8px;
}
</style>
