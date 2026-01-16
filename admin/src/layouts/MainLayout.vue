<template>
  <q-layout view="lHh Lpr lFf">
    <!-- Header / Topbar -->
    <q-header elevated class="bg-primary text-white">
      <q-toolbar>
        <q-btn
          flat
          dense
          round
          icon="menu"
          aria-label="Menu"
          @click="uiStore.toggleSidebar"
        />

        <q-toolbar-title class="row items-center">
          <img src="/logo-aces.png" alt="ACES" class="header-logo q-mr-sm" />
          <span class="gt-xs">Admin</span>
        </q-toolbar-title>

        <!-- Organization Selector -->
        <q-select
          v-if="organizationStore.list.length > 1"
          v-model="selectedOrganization"
          :options="organizationStore.organizationOptions"
          dense
          outlined
          dark
          emit-value
          map-options
          class="q-mr-md"
          style="min-width: 200px"
          label="Organização"
          @update:model-value="onOrganizationChange"
        />

        <!-- Loading indicator -->
        <q-spinner-dots v-if="uiStore.isLoading" color="white" size="24px" class="q-mr-md" />

        <!-- User Menu -->
        <q-btn-dropdown flat no-caps>
          <template #label>
            <div class="row items-center no-wrap">
              <q-avatar size="28px" color="white" text-color="primary">
                {{ userInitials }}
              </q-avatar>
              <span class="q-ml-sm gt-sm">{{ authStore.user?.name }}</span>
            </div>
          </template>

          <q-list>
            <q-item clickable v-close-popup :to="{ name: 'profile' }">
              <q-item-section avatar>
                <q-icon name="person" />
              </q-item-section>
              <q-item-section>Meu Perfil</q-item-section>
            </q-item>

            <q-separator />

            <q-item clickable v-close-popup @click="handleLogout">
              <q-item-section avatar>
                <q-icon name="logout" />
              </q-item-section>
              <q-item-section>Sair</q-item-section>
            </q-item>
          </q-list>
        </q-btn-dropdown>
      </q-toolbar>
    </q-header>

    <!-- Sidebar -->
    <q-drawer
      v-model="uiStore.sidebarOpen"
      show-if-above
      bordered
      :mini="uiStore.sidebarMini"
      :width="250"
      :mini-width="60"
    >
      <q-scroll-area class="fit">
        <q-list padding>
          <q-item-label header class="text-grey-8">
            Menu Principal
          </q-item-label>

          <NavItem
            v-for="item in menuItems"
            :key="item.title"
            v-bind="item"
          />
        </q-list>
      </q-scroll-area>

      <!-- Mini toggle -->
      <div class="absolute-bottom q-pa-sm" style="right: 0">
        <q-btn
          flat
          round
          dense
          size="sm"
          :icon="uiStore.sidebarMini ? 'chevron_right' : 'chevron_left'"
          @click="uiStore.toggleSidebarMini"
        />
      </div>
    </q-drawer>

    <!-- Page Container -->
    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from 'src/stores/auth'
import { useUIStore } from 'src/stores/ui'
import { useOrganizationStore } from 'src/stores/organization'
import NavItem from 'src/components/NavItem.vue'

const router = useRouter()
const authStore = useAuthStore()
const uiStore = useUIStore()
const organizationStore = useOrganizationStore()

const selectedOrganization = ref(null)

const userInitials = computed(() => {
  const name = authStore.user?.name || ''
  return name
    .split(' ')
    .map((n) => n[0])
    .join('')
    .substring(0, 2)
    .toUpperCase()
})

const menuItems = [
  {
    title: 'Dashboard',
    icon: 'dashboard',
    to: { name: 'dashboard' },
  },
  {
    title: 'Organizações',
    icon: 'business',
    to: { name: 'organizations' },
  },
  {
    title: 'Unidades',
    icon: 'store',
    to: { name: 'tenants' },
  },
  {
    title: 'Usuários',
    icon: 'people',
    to: { name: 'users' },
  },
  {
    title: 'Perfis',
    icon: 'admin_panel_settings',
    to: { name: 'roles' },
  },
]

async function handleLogout() {
  await authStore.logout()
  organizationStore.$reset()
  router.push({ name: 'login' })
}

async function onOrganizationChange(orgId) {
  if (orgId) {
    await organizationStore.switchOrganization(orgId)
    uiStore.notifySuccess('Organização alterada com sucesso')
  }
}

onMounted(async () => {
  if (organizationStore.list.length === 0) {
    await organizationStore.fetchOrganizations()
    if (organizationStore.current) {
      selectedOrganization.value = organizationStore.currentId
    } else if (organizationStore.list.length > 0) {
      selectedOrganization.value = organizationStore.list[0].id
      organizationStore.setCurrentOrganization(organizationStore.list[0])
    }
  }
})
</script>

<style lang="scss" scoped>
.header-logo {
  height: 32px;
  width: auto;
  filter: brightness(0) invert(1);
}
</style>
