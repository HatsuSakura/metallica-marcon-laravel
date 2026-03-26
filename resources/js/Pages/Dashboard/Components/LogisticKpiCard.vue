<template>
  <section class="card bg-base-100 border border-base-200 shadow-sm">
    <div class="card-body p-4">
      <div class="flex items-start justify-between gap-3">
        <div>
          <div class="text-lg font-semibold">{{ title }}</div>
          <div v-if="subtitle" class="text-sm opacity-70">{{ subtitle }}</div>
        </div>
        <div v-if="$slots.badge" class="shrink-0">
          <slot name="badge" />
        </div>
      </div>

      <div class="mt-4">
        <slot />
      </div>

      <div v-if="$slots.actions" class="mt-4 flex flex-wrap gap-2">
        <slot name="actions" />
      </div>
    </div>
  </section>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    required: true,
  },
  subtitle: {
    type: String,
    default: '',
  },
});
</script>
