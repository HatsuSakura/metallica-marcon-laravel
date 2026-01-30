<template>
    <div class="mb-4 flex flex-row items-center gap-4">
        <Link
            class="btn btn-ghost"
            :href="route('holder.index')"
        >
            <font-awesome-icon :icon="['fas', 'arrow-left']" class="text-xl"/>
            Torna ad elenco Contenitori
        </Link>
        <div class="text-lg font-medium">
            Crea nuovo contenitore
        </div>
    </div>

    <form @submit.prevent="create">
        <div class="grid grid-cols-12 gap-4">
            <div class="col-span-4">
                <label class="label">Nome</label>
                <input v-model="form.name" type="text" class="input" />
                <div class="input-error" v-if="form.errors.name">
                    {{ form.errors.name }}
                </div>
            </div>

            <div class="col-span-8">
                <label class="label">Descrizione</label>
                <textarea v-model="form.description" class="input"></textarea>
                <div class="input-error" v-if="form.errors.description">
                    {{ form.errors.description }}
                </div>
            </div>

            <div class="col-span-3">
                <label class="label">Volume (m3)</label>
                <input v-model.number="form.volume" type="number" step="0.01" class="input" />
                <div class="input-error" v-if="form.errors.volume">
                    {{ form.errors.volume }}
                </div>
            </div>

            <div class="col-span-6">
                <label class="label">Equivalenza con</label>
                <select v-model="form.equivalent_holder_id" class="select select-bordered w-full">
                    <option :value="null">Nessuna equivalenza</option>
                    <option v-for="holder in props.holders" :key="holder.id" :value="holder.id">
                        {{ holder.name }}
                    </option>
                </select>
                <div class="input-error" v-if="form.errors.equivalent_holder_id">
                    {{ form.errors.equivalent_holder_id }}
                </div>
            </div>

            <div class="col-span-3">
                <label class="label">Unita equivalenti</label>
                <input
                    v-model.number="form.equivalent_units"
                    type="number"
                    step="1"
                    class="input"
                    :disabled="!form.equivalent_holder_id"
                />
                <div class="input-error" v-if="form.errors.equivalent_units">
                    {{ form.errors.equivalent_units }}
                </div>
            </div>

            <div class="col-span-12">
                <button type="submit" class="btn btn-primary">Aggiungi Contenitore</button>
            </div>
        </div>
    </form>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'

const props = defineProps({
    holders: Array,
})

const form = useForm({
    name: null,
    description: null,
    volume: null,
    equivalent_holder_id: null,
    equivalent_units: null,
    is_custom: false,
})

const create = () => form.post(route('holder.store'))
</script>
