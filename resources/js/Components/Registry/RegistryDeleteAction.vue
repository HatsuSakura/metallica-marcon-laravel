<template>
    <AppAction variant="remove" shape="circle" :disabled="disabled" :aria-label="`Elimina ${label}`" :title="disabled ? disabledReason : `Elimina ${label}`" @click="dialog.showModal()"><font-awesome-icon :icon="['fas', 'trash-can']" aria-hidden="true" /></AppAction>
    <dialog ref="dialog" class="modal" :aria-label="`Elimina ${label}`" @cancel="busy && $event.preventDefault()">
        <div class="modal-box space-y-4">
            <h2 class="break-words text-lg font-semibold">Elimina {{ label }}</h2>
            <p>Confermi l’eliminazione di <strong class="break-words">{{ label }}</strong>?</p>
            <div class="flex flex-wrap justify-end gap-2">
                <AppAction variant="cancel" :disabled="busy" @click="dialog.close()">Annulla</AppAction>
                <AppAction variant="danger" :busy="busy" @click="$emit('confirm')">Elimina</AppAction>
            </div>
        </div>
    </dialog>
</template>
<script setup>
import {ref, watch} from 'vue'
import AppAction from '@/Components/UI/AppAction.vue'
const props = defineProps({label: {type: String, required: true}, disabled: Boolean, disabledReason: String, busy: Boolean})
defineEmits(['confirm'])
const dialog = ref(null)
watch(() => props.busy, (busy, previous) => { if (previous && !busy) dialog.value?.close() })
</script>
