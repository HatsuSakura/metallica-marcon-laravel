<template>
    <section class="space-y-1 text-sm">
        <div class="flex min-w-0 items-center justify-between gap-2">
            <h4 class="min-w-0 break-words font-medium">Note della sede<span v-if="showSiteName" class="font-normal"> · {{ site.name || site.address || 'Sede' }}</span></h4>
            <div v-if="editable" class="flex shrink-0 items-center gap-1">
                <template v-if="editing">
                    <button type="button" class="btn btn-circle btn-outline h-[42px] min-h-[42px] w-[42px]" :disabled="busy" aria-label="Annulla modifica note della sede" title="Annulla" @click="editing = false"><font-awesome-icon :icon="['fas','xmark']" aria-hidden="true" /></button>
                    <button type="submit" :form="formId" class="btn btn-circle btn-primary h-[42px] min-h-[42px] w-[42px]" :disabled="busy || !dirty" aria-label="Salva note della sede" title="Salva"><font-awesome-icon :icon="['fas','floppy-disk']" aria-hidden="true" /></button>
                </template>
                <button v-else type="button" class="btn btn-circle btn-outline h-[42px] min-h-[42px] w-[42px]" :disabled="busy" aria-label="Modifica note della sede" title="Modifica note della sede" @click="open"><font-awesome-icon :icon="['fas','pencil']" aria-hidden="true" /></button>
            </div>
        </div>
        <form v-if="editing && editable" :id="formId" @submit.prevent="save">
            <textarea v-model="draft" rows="3" maxlength="5000" class="textarea textarea-bordered w-full" aria-label="Note della sede" :disabled="busy" />
        </form>
        <p v-else class="whitespace-pre-line break-words">{{ site.notes?.trim() || 'Nessuna nota della sede' }}</p>
    </section>
</template>
<script setup>
import {computed,ref} from 'vue'
const props = defineProps({site:{type:Object,required:true},editable:Boolean,busy:Boolean,showSiteName:Boolean})
const emit = defineEmits(['command'])
const editing = ref(false), draft = ref(''), original = ref(''), version = ref(null)
const formId = computed(() => `journey-site-${props.site.id}-notes`)
const dirty = computed(() => draft.value !== original.value)
const open = () => {
    if (props.busy || !props.editable) return
    original.value = props.site.notes ?? ''
    draft.value = original.value
    version.value = props.site.notes_version
    editing.value = true
}
const save = () => {
    if (props.busy || !props.editable || !dirty.value) return
    emit('command','update_site_notes',{site_id:props.site.id,notes:draft.value,notes_version:version.value},()=>{editing.value=false})
}
</script>
