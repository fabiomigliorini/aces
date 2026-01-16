<template>
  <q-page padding>
    <div class="text-h5 q-mb-md">Dashboard</div>

    <div class="row q-col-gutter-md">
      <!-- Card de boas-vindas -->
      <div class="col-12">
        <q-card flat bordered>
          <q-card-section>
            <div class="text-h6">
              Bem-vindo, {{ authStore.user?.name }}!
            </div>
            <div class="text-body2 text-grey-7">
              <span v-if="organizationStore.currentName">
                Organização: {{ organizationStore.currentName }}
              </span>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <!-- Cards de estatísticas (exemplo) -->
      <div class="col-12 col-sm-6 col-md-3">
        <q-card flat bordered>
          <q-card-section>
            <div class="row items-center no-wrap">
              <div class="col">
                <div class="text-body2 text-grey-7">Usuários</div>
                <div class="text-h5">--</div>
              </div>
              <div class="col-auto">
                <q-icon name="people" size="40px" color="primary" />
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-sm-6 col-md-3">
        <q-card flat bordered>
          <q-card-section>
            <div class="row items-center no-wrap">
              <div class="col">
                <div class="text-body2 text-grey-7">Produtos</div>
                <div class="text-h5">--</div>
              </div>
              <div class="col-auto">
                <q-icon name="inventory_2" size="40px" color="positive" />
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-sm-6 col-md-3">
        <q-card flat bordered>
          <q-card-section>
            <div class="row items-center no-wrap">
              <div class="col">
                <div class="text-body2 text-grey-7">Pedidos</div>
                <div class="text-h5">--</div>
              </div>
              <div class="col-auto">
                <q-icon name="shopping_cart" size="40px" color="warning" />
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-sm-6 col-md-3">
        <q-card flat bordered>
          <q-card-section>
            <div class="row items-center no-wrap">
              <div class="col">
                <div class="text-body2 text-grey-7">Receita</div>
                <div class="text-h5">--</div>
              </div>
              <div class="col-auto">
                <q-icon name="attach_money" size="40px" color="negative" />
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { useAuthStore } from 'src/stores/auth'
import { useOrganizationStore } from 'src/stores/organization'

const authStore = useAuthStore()
const organizationStore = useOrganizationStore()
</script>
