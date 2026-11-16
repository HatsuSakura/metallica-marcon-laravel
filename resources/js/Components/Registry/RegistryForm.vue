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
import {computed, nextTick, ref, watch} from 'vue'
import {registrySnapshot} from '@/utils/registryFormState'
const props = defineProps({form: {type: Object, required: true}, editing: Boolean})
const emit = defineEmits(['submit'])
const summary = ref(null)
const element = ref(null)
const fieldElement = field => {
    const control = element.value?.elements.namedItem(field)
    return control?.item ? control.item(0) : control
}
const fieldHref = field => fieldElement(field)?.id ? `#${fieldElement(field).id}` : '#'
const focusField = field => fieldElement(field)?.focus?.()
const baseline = ref(registrySnapshot(props.form.data()))
const canSubmit = computed(() => !props.form.processing && (!props.editing || registrySnapshot(props.form.data()) !== baseline.value))
watch(() => props.form.wasSuccessful, success => { if (success) baseline.value = registrySnapshot(props.form.data()) })
watch(() => props.form.errors, async errors => {
    if (Object.keys(errors || {}).length) { await nextTick(); summary.value?.focus() }
}, {deep: true})
const submit = () => { if (canSubmit.value) emit('submit') }
</script>
