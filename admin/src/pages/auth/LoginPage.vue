<template>
  <q-page class="flex flex-center login-page">
    <div class="login-container">
      <div class="text-center q-mb-lg">
        <img src="/logo-aces.png" alt="ACES" class="login-logo q-mb-sm" />
        <div class="text-subtitle1 text-grey-7">Sistema Administrativo</div>
      </div>

      <q-card flat bordered class="login-card">
        <q-card-section>
          <div class="text-h6 q-mb-md">Entrar</div>

          <q-form @submit.prevent="handleLogin" class="q-gutter-md">
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
              label="Senha"
              outlined
              :rules="[(val) => !!val || 'Senha é obrigatória']"
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

            <div class="row items-center justify-between">
              <q-checkbox v-model="form.remember" label="Lembrar-me" />
              <router-link
                :to="{ name: 'forgot-password' }"
                class="text-primary"
              >
                Esqueci minha senha
              </router-link>
            </div>

            <q-btn
              type="submit"
              color="primary"
              class="full-width"
              size="lg"
              :loading="loading"
              label="Entrar"
            />
          </q-form>
        </q-card-section>
      </q-card>
    </div>
  </q-page>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from 'src/stores/auth'
import { useUIStore } from 'src/stores/ui'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const uiStore = useUIStore()

const loading = ref(false)
const showPassword = ref(false)

const form = reactive({
  email: '',
  password: '',
  remember: false,
})

async function handleLogin() {
  loading.value = true

  try {
    await authStore.login({
      email: form.email,
      password: form.password,
      remember: form.remember,
    })

    uiStore.notifySuccess('Login realizado com sucesso!')

    const redirect = route.query.redirect || '/'
    router.push(redirect)
  } catch (error) {
    console.log(error)
  } finally {
    loading.value = false
  }
}
</script>

<style lang="scss" scoped>
.login-page {
  background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ec 100%);
}

.login-container {
  width: 100%;
  max-width: 400px;
  padding: 16px;
}

.login-logo {
  height: 60px;
  width: auto;
}

.login-card {
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}
</style>
