<template>
    <form ref="element" class="min-w-0" @submit.prevent="submit">
        <div v-if="Object.keys(form.errors || {}).length" ref="summary" tabindex="-1" role="alert" class="mb-4 rounded-xl border border-error p-3 text-error">
            <h2 class="font-semibold">Controlla i dati indicati</h2>
            <ul class="list-inside list-disc text-sm"><li v-for="(message, field) in form.errors" :key="field"><a :href="fieldHref(field)" class="underline" @click.prevent="focusField(field)">{{ message }}</a></li></ul>
        </div>
        <fieldset :disabled="form.processing" class="min-w-0" :aria-busy="form.processing || undefined">
            <slot :can-submit="canSubmit" :busy="form.processing" />
        </fieldset>
    </form>
</template>
<script setup>
import {computed, nextTick, ref, watch, onMounted} from 'vue'
import {registrySnapshot} from '@/utils/registryFormState'
const props = defineProps({form: {type: Object, required: true}, editing: Boolean, ignoredFields:{type:Array,default:()=>[]}})
const emit = defineEmits(['submit'])
const summary = ref(null)
const element = ref(null)
const fieldElement = field => {
    const control = element.value?.elements.namedItem(field)
        || [...(element.value?.querySelectorAll('[data-form-field]') || [])].find(node => node.dataset.formField === field)?.querySelector('input,select,textarea,button')
    return control?.item ? control.item(0) : control
}
const fieldHref = field => fieldElement(field)?.id ? `#${fieldElement(field).id}` : '#'
const focusField = field => fieldElement(field)?.focus?.()
const snapshot = () => registrySnapshot(Object.fromEntries(Object.entries(props.form.data()).filter(([key]) => !props.ignoredFields.includes(key))))
const baseline = ref(snapshot())
onMounted(async () => { await nextTick(); baseline.value = snapshot() })
const canSubmit = computed(() => !props.form.processing && (!props.editing || snapshot() !== baseline.value))
watch(() => props.form.wasSuccessful, success => { if (success) baseline.value = snapshot() })
watch(() => props.form.errors, async errors => {
    if (Object.keys(errors || {}).length) { await nextTick(); summary.value?.focus() }
}, {deep: true})
const submit = () => { if (canSubmit.value) emit('submit') }
</script>
