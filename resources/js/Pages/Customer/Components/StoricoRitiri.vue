<template>

<h2 class="text-center mb-4 text-xl">Storico Ritiri</h2>

<ul v-if="props.withdraws.length" class="timeline timeline-vertical timeline-snap-icon">
    <li v-for="withdraw in props.withdraws" :key="withdraw.id">
        <div class="timeline-start">
            {{ formatWithdrawDate(withdraw) }}
            <span v-if="withdraw.is_manual_entry" class="text-2xl">
                <font-awesome-icon :icon="['fas', 'hand']" />
            </span>
            <span v-else>
                <font-awesome-icon :icon="['fas', 'truck-ramp-box']" />
            </span>
        </div>

        <div class="timeline-middle">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                class="h-5 w-5">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                    clip-rule="evenodd" />
            </svg>
        </div>
        <div class="timeline-end timeline-box flex items-center space-x-4">

            <div>
                <div class="radial-progress" :style="{ '--value': withdraw.residue_percentage ?? 0 }"
                    role="progressbar">{{ withdraw.residue_percentage ?? 0 }}%</div>
            </div>
            <div class="flex flex-col items-start space-y-0">
                <div class="flex flex-col items-center space-y-0">
                    <div class="text-2xl">
                        <font-awesome-icon :icon="['fas', 'user']" />
                    </div>
                    <div>
                        {{ withdraw.driver?.name ?? '-' }}
                    </div>
                </div>
                <div class="flex flex-col items-center space-y-0">
                    <div class="text-2xl">
                        <font-awesome-icon :icon="['fas', 'truck']" />
                    </div>
                    <div>
                        {{ withdraw.vehicle?.name ?? '-' }}
                    </div>
                </div>
            </div>

            <div class="flex flex-col items-start space-y-1">
                <div class="flex">
                    <Link :href="withdrawEditUrl(withdraw)"
                        class="btn btn-circle btn-primary">
                    <font-awesome-icon :icon="['fas', 'pencil']" class="h-5 w-5"
                        stroke="currentColor" />
                    </Link>
                </div>
                <div class="flex">
                    <button type="button"
                        @click="deleteWithdraw(withdraw)"
                        class="btn btn-circle btn-error">
                    <font-awesome-icon :icon="['fas', 'trash-can']" class="h-5 w-5"
                        stroke="currentColor" />
                    </button>
                </div>
            </div>

        </div>
        <hr />
    </li>
</ul>
<EmptyState v-else>Nessun ritiro in archivio per questo Cliente</EmptyState>


</template>

<script setup>
import EmptyState from '@/Components/UI/EmptyState.vue';
import { Link, router } from '@inertiajs/vue3';
import dayjs from 'dayjs';


const props = defineProps({
    site: Object,
    withdraws: Array,
})

const formatWithdrawDate = (withdraw) => {
    const raw = withdraw?.withdrawn_at ?? null;
    return raw ? dayjs(raw).format('YYYY-MM-DD') : '-';
};

const withdrawEditUrl = (withdraw) => route('withdraw.edit', {
    withdraw: withdraw.id,
});

const deleteWithdraw = (withdraw) => {
    if (!confirm('Confermi la cancellazione del ritiro?')) {
        return;
    }

    router.delete(route('withdraw.destroy', { withdraw: withdraw.id }), {
        preserveScroll: true,
        preserveState: false,
        onSuccess: () => {
            router.reload({
                only: ['customer', 'orders_by_site'],
                preserveScroll: true,
                preserveState: false,
            });
        },
    });
};
</script>
