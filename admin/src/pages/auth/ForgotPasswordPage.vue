<template>
  <q-page class="flex flex-center bg-grey-2">
    <div class="forgot-container">
      <div class="text-center q-mb-lg">
        <div class="text-h4 text-primary text-weight-bold">ACES</div>
        <div class="text-subtitle2 text-grey-7">Recuperar Senha</div>
      </div>

      <q-card flat bordered class="forgot-card">
        <q-card-section v-if="!emailSent">
          <div class="text-h6 q-mb-sm">Esqueceu sua senha?</div>
          <div class="text-body2 text-grey-7 q-mb-md">
            Digite seu e-mail e enviaremos instruções para redefinir sua senha.
          </div>

          <q-form @submit.prevent="handleForgotPassword" class="q-gutter-md">
            <q-input
              v-model="email"
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

            <q-btn
              type="submit"
              color="primary"
              class="full-width"
              size="lg"
              :loading="loading"
              label="Enviar link"
            />

            <div class="text-center">
              <router-link :to="{ name: 'login' }" class="text-primary">
                Voltar ao login
              </router-link>
            </div>
          </q-form>
        </q-card-section>

        <q-card-section v-else class="text-center">
          <q-icon name="mark_email_read" size="64px" color="positive" />
          <div class="text-h6 q-mt-md">E-mail enviado!</div>
          <div class="text-body2 text-grey-7 q-mt-sm q-mb-lg">
            Verifique sua caixa de entrada e siga as instruções para redefinir
            sua senha.
          </div>
          <router-link :to="{ name: 'login' }" class="text-primary">
            Voltar ao login
          </router-link>
        </q-card-section>
      </q-card>
    </div>
  </q-page>
</template>

<script setup>
import { ref } from 'vue'
import { authService } from 'src/services/auth'

const loading = ref(false)
const emailSent = ref(false)
const email = ref('')

async function handleForgotPassword() {
  loading.value = true

  try {
    await authService.forgotPassword(email.value)
    emailSent.value = true
  } catch (error) {
    console.log(error)
  } finally {
    loading.value = false
  }
}
</script>

<style lang="scss" scoped>
.forgot-container {
  width: 100%;
  max-width: 400px;
  padding: 16px;
}

.forgot-card {
  border-radius: 8px;
}
</style>
