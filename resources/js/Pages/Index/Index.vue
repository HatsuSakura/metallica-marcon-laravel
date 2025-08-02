<template>
  <div>
    Index
    <Link href="/hello">
      Show Page
    </Link>
    <div>
      the message is {{ message }}
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
defineProps(['message'])
</script>

