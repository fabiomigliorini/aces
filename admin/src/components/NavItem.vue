<template>
  <q-item
    v-if="!children"
    clickable
    v-ripple
    :to="to"
    active-class="bg-grey-3 text-primary"
  >
    <q-item-section avatar>
      <q-icon :name="icon" />
    </q-item-section>
    <q-item-section>{{ title }}</q-item-section>
  </q-item>

  <q-expansion-item
    v-else
    :icon="icon"
    :label="title"
    expand-separator
  >
    <q-item
      v-for="child in children"
      :key="child.title"
      clickable
      v-ripple
      :to="child.to"
      active-class="bg-grey-3 text-primary"
      class="q-pl-lg"
    >
      <q-item-section avatar>
        <q-icon :name="child.icon" />
      </q-item-section>
      <q-item-section>{{ child.title }}</q-item-section>
    </q-item>
  </q-expansion-item>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    required: true,
  },
  icon: {
    type: String,
    default: 'circle',
  },
  to: {
    type: Object,
    default: null,
  },
  children: {
    type: Array,
    default: null,
  },
})
</script>
