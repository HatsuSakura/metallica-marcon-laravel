
<template>
<div class="flex gap-1">

    <Link v-for="(link,index) in links" 
    :key="index" class="join-item btn" 
    :href="link.url" 
    :class="{' btn-active': link.active }" 
    v-html="link.label"/>
</div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
defineProps({links: Array})
</script>