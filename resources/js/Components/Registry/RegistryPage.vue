<template>
    <AppPage v-bind="$attrs">
        <template v-for="(_, name) in $slots" #[name]="slotProps">
            <slot :name="name" v-bind="slotProps || {}" />
        </template>
    </AppPage>
</template>
<script setup>
import AppPage from '@/Components/UI/AppPage.vue'
defineOptions({inheritAttrs: false})
</script>
