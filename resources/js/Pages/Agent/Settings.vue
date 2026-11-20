<template>
    <RegistryForm :form="form" editing v-slot="{canSubmit, busy}" class="mt-3 max-w-2xl" @submit="save">
        <label class="form-control"><span class="label">Nome</span><input v-model="form.name" name="name" required maxlength="120" class="input input-bordered" /></label>
        <label class="my-3 flex items-center gap-3 text-sm font-semibold">Agente attivo<input v-model="form.is_active" name="is_active" type="checkbox" role="switch" class="toggle toggle-primary" /></label>
        <fieldset><legend class="label">Permessi</legend><label v-for="(label, key) in availableAbilities" :key="key" class="flex items-center gap-3 py-2 text-sm"><input v-model="form.abilities" type="checkbox" :value="key" class="checkbox checkbox-primary" />{{ label }}</label></fieldset>
        <p class="my-3 text-sm">La disabilitazione blocca tutti i token. Rimuovere un permesso lo blocca anche sui token già emessi.</p>
        <AppAction type="submit" variant="confirm" :disabled="!canSubmit || !form.abilities.length || !form.name.trim()" :busy="busy">Salva</AppAction>
    </RegistryForm>
</template>
<script setup>
import {useForm} from '@inertiajs/vue3'
import RegistryForm from '@/Components/Registry/RegistryForm.vue'
import AppAction from '@/Components/UI/AppAction.vue'
const props = defineProps({agent:{type:Object,required:true},availableAbilities:{type:Object,required:true}})
const form = useForm({name:props.agent.name,is_active:props.agent.is_active,abilities:[...props.agent.abilities]})
const save = () => form.put(route('dev.agents.update', props.agent.id), {preserveScroll:true})
</script>
