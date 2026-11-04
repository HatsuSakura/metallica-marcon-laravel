<template>
    <div class="space-y-3" aria-label="Referenti di sede">
        <div v-for="contact in rows" :key="contact.key" class="min-w-0 text-sm">
            <p class="flex items-start gap-2 leading-6" :title="contact.roleLabel">
                <font-awesome-icon :icon="['fas', contact.roleIcon]" class="mt-1 h-4 w-5 shrink-0" aria-hidden="true" />
                <span class="sr-only">{{ contact.roleLabel }}: </span>
                <span class="break-words font-medium">{{ contact.name }}</span>
            </p>
            <div v-for="number in contact.numbers" :key="number.href" class="mt-1 flex min-h-[42px] min-w-0 items-center justify-between gap-2">
                <p class="min-w-0 break-all">{{ number.display }}</p>
                <a :href="number.href" class="btn btn-success btn-square btn-sm h-[42px] min-h-[42px] w-[42px] min-w-[42px] shrink-0" :aria-label="`Chiama ${contact.name}, ${number.label}: ${number.display}`">
                    <font-awesome-icon :icon="['fas', number.icon]" class="h-6 w-6" aria-hidden="true" />
                </a>
            </div>
            <div v-if="!contact.numbers.length" class="flex min-h-[42px] items-center justify-between gap-2">
                <span class="opacity-70">{{ contact.number }}</span>
                <button type="button" disabled class="btn btn-square btn-sm h-[42px] min-h-[42px] w-[42px] min-w-[42px] shrink-0" :aria-label="`Nessun numero disponibile per ${contact.name}`">
                    <font-awesome-icon :icon="['fas', 'phone']" class="h-6 w-6" aria-hidden="true" />
                </button>
            </div>
        </div>
    </div>
</template>
<script setup>
import {computed} from 'vue'
import {siteContactRows} from '@/utils/siteContactPresentation'
const props = defineProps({contacts:{type:Array,default:()=>[]}})
const rows = computed(() => siteContactRows(props.contacts))
</script>
