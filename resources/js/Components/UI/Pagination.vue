<template>
    <nav class="flex flex-wrap gap-1" aria-label="Paginazione">
        <template v-for="(link,index) in links" :key="index">
            <button v-if="link.url === null" type="button" class="btn" disabled v-html="link.label" />
            <Link v-else class="btn" :href="link.url" :class="{'btn-active':link.active}" :aria-current="link.active ? 'page' : undefined" v-html="link.label" />
        </template>
    </nav>
</template>
<script setup>
import {Link} from '@inertiajs/vue3'
defineProps({links:Array})
</script>
