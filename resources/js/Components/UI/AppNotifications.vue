<template><AppNotificationStack :messages="messages" @dismiss="id => store.dispatch('flash/removeMessage',id)" @action="id => store.dispatch('flash/runAction',id)" /></template>
<script setup>
import {computed,watch} from 'vue'
import AppNotificationStack from './AppNotificationStack.vue'
import {useStore} from 'vuex'
import {usePage} from '@inertiajs/vue3'
const store = useStore()
const page = usePage()
const icons = {success:'circle-check',error:'circle-xmark',warning:'triangle-exclamation',info:'circle-info'}
const messages = computed(() => store.state.flash.messageQueue)
// Observe each Inertia response, including consecutive identical flash texts.
watch(() => page.props, props => {
    for (const type of Object.keys(icons)) {
        if (props?.flash?.[type]) store.dispatch('flash/queueMessage',{type,text:props.flash[type]})
    }
},{immediate:true})
</script>
