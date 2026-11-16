<template>
<RegistryPage title="Scheda cliente">

    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <Link class="btn btn-outline" :href="route('customer.index')">
            <font-awesome-icon :icon="['fas', 'arrow-left']" class="text-xl"/>
            Torna a Clienti
        </Link>
        <Link
            class="btn btn-primary btn-outline"
            :href="route('customer.edit', {
                customer: reactiveCustomer.id,
                return_to: route('customer.show', { customer: reactiveCustomer.id }),
            })"
        >
            <font-awesome-icon :icon="['fas', 'pen']" class="text-base" />
            Modifica dati cliente
        </Link>
    </div>

    <section class="flex flex-col md:grid md:grid-cols-12 gap-4 mb-4">

        <!-- TESTATA SCHEDA CLIENTE -->
        <div class="md:col-span-12">
            <Box >

                <div class="text-base-content/75">
                    <span class="text-base-content text-lg font-bold">{{ reactiveCustomer.company_name }}</span>
                    <CustomerSpace :customer="reactiveCustomer" class="text-base-content/75" />
                </div>
                <CustomerAddress :customer="reactiveCustomer" class="text-base-content/75" />

                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-2 text-sm">
                    <div>
                        <span class="text-base-content/75">P.IVA</span><br>
                        <span class="break-words font-medium">{{ reactiveCustomer.vat_number ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="text-base-content/75">Codice Fiscale</span><br>
                        <span class="break-words font-medium">{{ reactiveCustomer.tax_code ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="text-base-content/75">Commerciale</span><br>
                        <span class="break-words font-medium">
                            {{ reactiveCustomer.seller ? `${reactiveCustomer.seller.name} ${reactiveCustomer.seller.surname}` : '—' }}
                        </span>
                    </div>
                    <div>
                        <span class="text-base-content/75">Email commerciale</span><br>
                        <span class="break-words font-medium">{{ reactiveCustomer.sales_email ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="text-base-content/75">Email amministrativa</span><br>
                        <span class="break-words font-medium">{{ reactiveCustomer.administrative_email ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="text-base-content/75">PEC</span><br>
                        <span class="break-words font-medium">{{ reactiveCustomer.certified_email ?? '—' }}</span>
                    </div>
                </div>
            </Box>
        </div>

        <div class="md:col-span-12">
            <Box>
                <template #header>Note Cliente</template>
                <div v-if="reactiveCustomer.notes" class="whitespace-pre-line">
                    {{ reactiveCustomer.notes }}
                </div>
                <div v-else class="opacity-70">
                    Nessuna nota cliente
                </div>
            </Box>
        </div>
    </section>

    <div class="flex flex-wrap items-center justify-between gap-2"><h2 class="text-lg font-semibold">Sedi</h2><AppAction v-if="canCreateSite" variant="function" @click="siteEditor.open()"><font-awesome-icon :icon="['fas','plus']" aria-hidden="true" />Aggiungi sede</AppAction></div>
    <CustomerSiteEditor ref="siteEditor" :customer="reactiveCustomer" />
    <section class="min-w-0" aria-label="Sedi del cliente">
        <div role="tablist" aria-label="Seleziona sede" class="site-tabs tabs tabs-lifted relative z-10 flex flex-nowrap overflow-x-auto overflow-y-hidden">
            <button v-for="site in reactiveCustomer.sites" :key="site.id" :id="`site-tab-${site.id}`" type="button" role="tab" class="tab h-auto min-h-12 shrink-0 gap-2 whitespace-nowrap px-3 py-2 text-sm" :class="{'tab-active font-semibold':selectedSite?.id === site.id}" :aria-selected="selectedSite?.id === site.id" :aria-controls="`site-panel-${site.id}`" :tabindex="selectedSite?.id === site.id ? 0 : -1" @click="selectedSiteId = site.id" @keydown="navigateSites($event,site.id)">
                <font-awesome-icon v-if="site.is_main" :icon="['fas','house']" class="h-4 w-4 shrink-0" aria-hidden="true" />{{ site.name }}<span v-if="site.is_main" class="sr-only"> — sede principale</span>
            </button>
        </div>
        <div v-for="site in reactiveCustomer.sites" :key="site.id" :id="`site-panel-${site.id}`" role="tabpanel" class="rounded-b-xl rounded-tr-xl border border-base-300 bg-base-100" :aria-labelledby="`site-tab-${site.id}`" v-show="selectedSite?.id === site.id">
            <TabSite :site="site" :contact-roles="siteContactRoles" :can-manage-contacts="!!siteContactPermissions[site.id]" :can-delete="!!siteDeletePermissions[site.id]" :customer-sites-count="reactiveCustomer.sites.length" :areas="props.areas" :orders="orders_by_site[site.id] ?? []" @siteUpdated="updateSite" @edit="siteEditor.open(site)" />
        </div>
        <p v-if="!reactiveCustomer.sites.length" class="text-sm">Nessuna sede presente. Usa “Aggiungi sede” per inserirla.</p>
    </section>

    <AuditCollapse
        :audits="props.audits ?? []"
        :is-admin="Boolean($page.props.user?.is_admin)"
        :field-labels="auditFieldLabels"
    />


</RegistryPage>
</template>

<script setup>
import RegistryPage from '@/Components/Registry/RegistryPage.vue'


import AppAction from '@/Components/UI/AppAction.vue'
import CustomerSiteEditor from './Components/CustomerSiteEditor.vue'
import Box from '@/Components/UI/Box.vue';
import { Link } from '@inertiajs/vue3';
import { computed, ref, reactive, toRaw, watch, nextTick } from 'vue';
import CustomerSpace from '@/Components/CustomerSpace.vue';
import CustomerAddress from '@/Components/CustomerAddress.vue';
import SiteSpace from '@/Components/SiteSpace.vue';
import TabSite from './Components/TabSite.vue';
import AuditCollapse from '@/Components/AuditCollapse.vue';


const props = defineProps({
    customer:Object,
    areas: Array,
    orders_by_site: { type: Object, default: () => ({}) },
    siteContactRoles: {type:Array,default:()=>[]},
    siteContactPermissions: {type:Object,default:()=>({})},
    audits: { type: Array, default: () => [] },
    canCreateSite: Boolean,
    siteDeletePermissions: {type:Object,default:()=>({})},
})

const auditFieldLabels = {
    is_occasional_customer: 'Cliente occasionale',
    company_name: 'Ragione sociale',
    vat_number: 'Partita IVA',
    tax_code: 'Codice fiscale',
    seller_id: 'Commerciale',
    legal_address: 'Indirizzo legale',
    sdi_code: 'Codice SDI',
    business_type_id: 'Tipologia attività',
    sales_email: 'Email commerciale',
    administrative_email: 'Email amministrativa',
    certified_email: 'PEC',
    notes: 'Note',
};

const reactiveCustomer = reactive({...props.customer});
const siteEditor = ref(null);
const selectedSiteId = ref(props.customer.sites[0]?.id ?? null);
const selectedSite = computed(() => reactiveCustomer.sites.find(site => site.id === selectedSiteId.value) ?? reactiveCustomer.sites[0]);
const navigateSites = (event, id) => {
    if (!['ArrowLeft','ArrowRight','Home','End'].includes(event.key)) return;
    event.preventDefault();
    const sites = reactiveCustomer.sites;
    const index = sites.findIndex(site => site.id === id);
    const target = event.key === 'Home' ? 0 : event.key === 'End' ? sites.length-1 : (index + (event.key === 'ArrowRight' ? 1 : -1) + sites.length) % sites.length;
    selectedSiteId.value = sites[target].id;
    nextTick(() => {
        const tab = document.getElementById(`site-tab-${selectedSiteId.value}`);
        tab?.focus({preventScroll:true});
        tab?.scrollIntoView({block:'nearest',inline:'nearest'});
    });
};
watch(() => props.customer, customer => Object.assign(reactiveCustomer, customer));

const hasSites = computed(
    () => reactiveCustomer.sites.length,
)

const customerOccasionale = computed(
    () => reactiveCustomer.customerOccasionale_at,
)


// Update function
const updateSite = (updatedSite) => {

    // Find the index of the site to update
    const siteIndex = reactiveCustomer.sites.findIndex((site) => site.id === updatedSite.id);
    if (siteIndex !== -1) {
        // update the array merging modified values to original
        reactiveCustomer.sites[siteIndex] = {
            // E' importantissimo il toRaw per evitare ridondanze cicliche che portano all'errore di INERTIA
            // Uncaught DataCloneError: Failed to execute 'replaceState' on 'History': #<Object> could not be cloned.
            ...toRaw(reactiveCustomer.sites[siteIndex]),
            ...toRaw(updatedSite),
        };
    } else {
        console.warn('Site not found in customer.sites:', updatedSite.id);
    }
};


</script>

<style scoped>
/* Keep the lifted tab joined to the panel while only the tab strip scrolls. */
.site-tabs { margin-bottom: -1px; padding-top: 2px; }
.site-tabs::after { content: ''; flex: 1 0 1rem; }
</style>
