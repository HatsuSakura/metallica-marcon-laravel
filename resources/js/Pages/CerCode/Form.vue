<template>
    <RegistryPage :title="cerCode ? `Modifica CER ${cerCode.code}` : 'Nuovo codice CER'">
        <template #back><AppAction :as="Link" :href="route('cer-code.index')"><font-awesome-icon :icon="['fas', 'arrow-left']" aria-hidden="true" />Codici CER</AppAction></template>
        <RegistryForm :form="form" :editing="!!cerCode" v-slot="{canSubmit, busy}" class="max-w-2xl" @submit="submit">
            <div class="space-y-4">
                <div>
                    <label for="cer-code" class="label">Codice CER <span aria-hidden="true">*</span></label>
                    <input id="cer-code" v-model="form.code" name="code" required inputmode="numeric" class="input input-bordered" maxlength="12" :aria-invalid="!!form.errors.code" aria-describedby="cer-code-help cer-code-error" />
                    <p id="cer-code-help" class="mt-1 text-sm">Sei cifre, anche separate da spazi. Indica la pericolosità con il selettore dedicato.</p>
                    <p id="cer-code-error" class="text-sm text-error" v-if="form.errors.code">{{ form.errors.code }}</p>
                </div>
                <div>
                    <label for="cer-description" class="label">Descrizione <span aria-hidden="true">*</span></label>
                    <textarea id="cer-description" v-model="form.description" name="description" required maxlength="255" rows="3" class="textarea textarea-bordered" :aria-invalid="!!form.errors.description" :aria-describedby="form.errors.description ? 'cer-description-error' : undefined" />
                    <p id="cer-description-error" class="text-sm text-error" v-if="form.errors.description">{{ form.errors.description }}</p>
                </div>
                <div class="flex flex-wrap gap-x-8 gap-y-4">
                    <div><label class="flex items-center gap-3" for="cer-dangerous"><span class="text-sm font-semibold">Pericoloso</span><input id="cer-dangerous" v-model="form.is_dangerous" name="is_dangerous" type="checkbox" role="switch" class="toggle toggle-error" :aria-invalid="!!form.errors.is_dangerous" /></label><p v-if="form.errors.is_dangerous" class="text-sm text-error">{{ form.errors.is_dangerous }}</p></div>
                    <div><label class="flex items-center gap-3" for="cer-active"><span class="text-sm font-semibold">Attivo</span><input id="cer-active" v-model="form.is_active" name="is_active" type="checkbox" role="switch" class="toggle toggle-primary" :aria-invalid="!!form.errors.is_active" /></label><p v-if="form.errors.is_active" class="text-sm text-error">{{ form.errors.is_active }}</p></div>
                </div>
                <p class="text-sm">Un CER disattivato resta negli ordini esistenti e non è selezionabile per nuovi materiali. Puoi riattivarlo da questa schermata.</p>
                <div class="flex flex-wrap justify-between gap-3 pt-2">
                    <AppAction :as="Link" :href="route('cer-code.index')" variant="cancel"><font-awesome-icon :icon="['fas', 'xmark']" aria-hidden="true" />Annulla</AppAction>
                    <AppAction type="submit" variant="confirm" :disabled="!canSubmit || !valid" :busy="busy"><font-awesome-icon :icon="['fas', 'floppy-disk']" aria-hidden="true" />Salva</AppAction>
                </div>
            </div>
        </RegistryForm>
    </RegistryPage>
</template>
<script setup>
import {computed} from 'vue'
import {Link, useForm} from '@inertiajs/vue3'
import RegistryPage from '@/Components/Registry/RegistryPage.vue'
import RegistryForm from '@/Components/Registry/RegistryForm.vue'
import AppAction from '@/Components/UI/AppAction.vue'
const props = defineProps({cerCode: {type: Object, default: null}})
const form = useForm({code: props.cerCode?.code ?? '', description: props.cerCode?.description ?? '', is_dangerous: Boolean(props.cerCode?.is_dangerous), is_active: props.cerCode?.is_active ?? true})
const valid = computed(() => /^\d{6}$/.test(form.code.replace(/[\s*]/g, '')) && form.description.trim().length > 0)
const submit = () => {
    if (!valid.value) return
    if (props.cerCode) form.put(route('cer-code.update', props.cerCode.id))
    else form.post(route('cer-code.store'))
}
</script>
