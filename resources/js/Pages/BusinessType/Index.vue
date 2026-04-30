<template>
    <div class="mb-4 flex items-center justify-between gap-2">
        <span class="text-xl font-bold">Tipologie Attività</span>
        <Link :href="route('business-type.create')" class="btn btn-primary btn-sm">
            <font-awesome-icon :icon="['fas', 'plus']" />
            Nuova tipologia
        </Link>
    </div>

    <div class="overflow-x-auto">
        <table class="table w-full">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Descrizione</th>
                    <th class="text-right">Clienti</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="bt in businessTypes" :key="bt.id" class="hover">
                    <td class="font-medium">{{ bt.name }}</td>
                    <td class="text-base-content/70">{{ bt.description ?? '—' }}</td>
                    <td class="text-right">{{ bt.customers_count }}</td>
                    <td class="text-right space-x-2">
                        <Link :href="route('business-type.edit', bt.id)" class="btn btn-ghost btn-xs">
                            <font-awesome-icon :icon="['fas', 'pen']" />
                        </Link>
                        <button
                            type="button"
                            class="btn btn-ghost btn-xs text-error"
                            :disabled="bt.customers_count > 0"
                            :title="bt.customers_count > 0 ? 'Usata da clienti — non eliminabile' : 'Elimina'"
                            @click="confirmDelete(bt)"
                        >
                            <font-awesome-icon :icon="['fas', 'trash']" />
                        </button>
                    </td>
                </tr>
                <tr v-if="!businessTypes.length">
                    <td colspan="4" class="text-center opacity-60">Nessuna tipologia registrata.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <dialog ref="deleteModal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg">Elimina tipologia</h3>
            <p class="py-3">Eliminare <strong>{{ target?.name }}</strong>? L'operazione è irreversibile.</p>
            <div class="modal-action">
                <button class="btn" type="button" @click="deleteModal?.close()">Annulla</button>
                <button class="btn btn-error" type="button" @click="doDelete">Elimina</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
    businessTypes: { type: Array, default: () => [] },
});

const deleteModal = ref(null);
const target = ref(null);

const confirmDelete = (bt) => {
    target.value = bt;
    deleteModal.value?.showModal();
};

const doDelete = () => {
    deleteModal.value?.close();
    router.delete(route('business-type.destroy', target.value.id));
};
</script>
