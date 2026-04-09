<template>
    <section v-if="visible" class="mt-8">
        <div tabindex="0" class="collapse collapse-arrow border border-base-300 bg-base-100">
            <input type="checkbox" />
            <div class="collapse-title text-base font-semibold">
                Storico modifiche
                <span class="ml-2 text-sm font-normal opacity-70">({{ audits.length }})</span>
            </div>
            <div class="collapse-content pt-0">
                <div v-if="audits.length === 0" class="text-sm opacity-70">
                    Nessuna modifica tracciata.
                </div>

                <div v-else class="space-y-3">
                    <div
                        v-for="(audit, index) in audits"
                        :key="audit.id"
                        tabindex="0"
                        class="collapse collapse-arrow rounded-box bg-base-100"
                    >
                        <input type="checkbox" :checked="index === 0" />
                        <div class="collapse-title min-h-0 py-3">
                            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="badge badge-outline">{{ eventLabel(audit.event) }}</span>
                                    <span v-if="audit.subject_label" class="badge badge-ghost">{{ audit.subject_label }}</span>
                                    <span class="text-sm font-medium">{{ audit.user?.name || audit.user?.email || 'Sistema' }}</span>
                                    <span class="text-xs opacity-60">{{ changeCountLabel(audit) }}</span>
                                </div>
                                <div class="text-xs opacity-70">
                                    {{ formatDateTime(audit.created_at) }}
                                </div>
                            </div>
                        </div>
                        <div class="collapse-content pt-0">
                            <div class="space-y-2">
                                <div
                                    v-for="change in changesFor(audit)"
                                    :key="`${audit.id}-${change.field}`"
                                    class="grid grid-cols-1 gap-2 lg:grid-cols-[220px_minmax(0,1fr)_minmax(0,1fr)]"
                                >
                                    <div class="text-sm font-medium">
                                        {{ fieldLabel(change.field) }}
                                    </div>
                                    <div class="rounded bg-base-200 px-2 py-1 text-sm">
                                        <div class="text-xs opacity-60">Prima</div>
                                        <div class="whitespace-pre-wrap break-words">{{ formatValue(change.field, change.oldValue) }}</div>
                                    </div>
                                    <div class="rounded bg-base-200 px-2 py-1 text-sm">
                                        <div class="text-xs opacity-60">Dopo</div>
                                        <div class="whitespace-pre-wrap break-words">{{ formatValue(change.field, change.newValue) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import dayjs from 'dayjs';

const props = defineProps({
    audits: {
        type: Array,
        default: () => [],
    },
    isAdmin: {
        type: Boolean,
        default: false,
    },
    fieldLabels: {
        type: Object,
        default: () => ({}),
    },
});

const visible = props.isAdmin;

const eventLabel = (event) => {
    const labels = {
        created: 'Creato',
        updated: 'Aggiornato',
        deleted: 'Eliminato',
        restored: 'Ripristinato',
    };

    return labels[event] || event || '-';
};

const formatDateTime = (value) => {
    if (!value) return '-';
    return dayjs(value).format('DD/MM/YYYY HH:mm:ss');
};

const fieldLabel = (field) => props.fieldLabels[field] || field;

const changesFor = (audit) => {
    const oldValues = audit?.old_values || {};
    const newValues = audit?.new_values || {};
    const fields = Array.from(new Set([...Object.keys(oldValues), ...Object.keys(newValues)]));

    return fields.map((field) => ({
        field,
        oldValue: oldValues[field] ?? null,
        newValue: newValues[field] ?? null,
    }));
};

const changeCountLabel = (audit) => {
    const count = changesFor(audit).length;
    return `${count} ${count === 1 ? 'campo modificato' : 'campi modificati'}`;
};

const BOOLEAN_FIELDS = new Set([
    'is_urgent',
    'has_crane',
    'is_bulk',
    'adr',
    'has_adr',
    'is_adr_total',
    'has_adr_total_exemption',
    'has_adr_partial_exemption',
    'is_occasional_customer',
    'is_main',
    'has_muletto',
    'has_electric_pallet_truck',
    'has_manual_pallet_truck',
    'has_adr_consultant',
    'is_admin',
    'is_crane_operator',
    'has_trailer',
    'is_front_cargo',
    'is_cargo',
    'is_long',
    'is_custom',
    'is_manual_entry',
    'is_dangerous',
    'is_active',
    'is_override',
    'is_grounded',
    'is_holder_dirty',
    'is_holder_broken',
    'is_warehouse_added',
    'has_non_conformity',
    'has_exploded_children',
    'has_selection',
    'is_crane_eligible',
    'is_machinery_time_manual',
    'is_transshipment',
    'is_not_found',
]);

const formatValue = (field, value) => {
    if (value === null || value === undefined || value === '') return '-';
    if (BOOLEAN_FIELDS.has(field)) {
        if (typeof value === 'boolean') return value ? 'SI' : 'NO';
        if (value === 1 || value === '1' || value === 'true') return 'SI';
        if (value === 0 || value === '0' || value === 'false') return 'NO';
    }
    if (Array.isArray(value)) return value.join(', ');
    if (typeof value === 'object') return JSON.stringify(value);

    if (typeof value === 'string') {
        const asDate = dayjs(value);
        if (asDate.isValid() && /^\d{4}-\d{2}-\d{2}/.test(value)) {
            return asDate.format('DD/MM/YYYY HH:mm:ss');
        }
    }

    return String(value);
};
</script>
