<template>
  <Box
    padding="px-3 py-2 sm:px-4"
    v-for="customer in customersArr"
    :key="customer.id"
    :class="{ 'border-dashed': customer.deleted_at }"
  >
    <div class="flex min-w-0 flex-col gap-2 lg:flex-row lg:items-center">
      <div class="min-w-0 flex-1 space-y-1" :class="{ 'opacity-75': customer.deleted_at }">
        <div
          v-if="customer.deleted_at"
          class="badge badge-neutral badge-outline mb-2"
        >
          cancellato
        </div>

        <div>
          <h2 class="m-0 break-words text-base font-semibold leading-6">
            {{ customer.company_name }}
          </h2>
        </div>
        <CustomerSpace :customer="customer" :vat="true" />
        <CustomerAddress :customer="customer" class="text-sm leading-5" />
      </div>

      <section class="shrink-0" aria-label="Azioni cliente">
        <div class="flex flex-wrap items-center gap-2 text-base-content lg:flex-nowrap">
          <div class="flex flex-wrap items-center gap-2 lg:flex-nowrap">
            <span
              v-if="customer.can && customer.can.createOrder"
              class="badge badge-outline"
              :class="customer.open_orders_count > 0 ? 'badge-warning' : 'badge-success'"
            >
              {{ customer.open_orders_count }} / {{ customer.total_orders_count }}
            </span>

            <div class="tooltip" data-tip="Crea ordine">
              <button
                v-if="customer.can && customer.can.createOrder"
                class="btn btn-circle btn-primary btn-outline"
                type="button"
                aria-label="Crea ordine" @click="onCreateOrderClick(customer)"
              >
                <font-awesome-icon :icon="['fas', 'clipboard-list']" class="h-5 w-5 stroke-current" />
              </button>
            </div>

            <div class="tooltip" data-tip="Crea ritiro">
              <button
                class="btn btn-circle btn-primary btn-outline"
                type="button"
                aria-label="Crea ritiro" @click="onCreateWithdrawClick(customer)"
              >
                <font-awesome-icon :icon="['fas', 'cart-plus']" class="h-5 w-5 stroke-current" />
              </button>
            </div>
          </div>

          <div class="tooltip" data-tip="Scheda cliente">
            <a
              class="btn btn-circle btn-outline btn-primary"
              :href="route('customer.show', { customer: customer.id })"
             aria-label="Apri scheda cliente">
              <font-awesome-icon :icon="['fas', 'file-lines']" class="h-5 w-5 stroke-current" />
            </a>
          </div>


          <div class="tooltip" data-tip="Modifica cliente">
            <Link
              class="btn btn-circle btn-primary btn-outline"
              :href="route('customer.edit', { customer: customer.id })"
             aria-label="Modifica cliente">
              <font-awesome-icon :icon="['fas', 'pencil']" class="h-5 w-5 stroke-current" />
            </Link>
          </div>

          <div v-if="!customer.deleted_at" class="tooltip" data-tip="Elimina cliente">
            <RegistryDeleteAction :label="customer.company_name" :busy="deleting === route('customer.destroy', {customer:customer.id})" @confirm="removeRecord(route('customer.destroy', {customer:customer.id}))" />
          </div>
          <div v-else class="tooltip" data-tip="Ripristina cliente">
          <Link
            class="btn btn-circle btn-outline btn-primary"
            :href="route('customer.restore', { customer: customer.id })"
            as="button"
            method="put"
           aria-label="Ripristina cliente">
            <font-awesome-icon :icon="['fas', 'trash-can-arrow-up']" />
          </Link>
          </div>

        </div>
      </section>
    </div>
  </Box>

  <dialog ref="siteModal" class="modal">
    <div class="modal-box">
      <h3 class="font-bold text-lg">Seleziona la sede</h3>
      <p class="py-2 text-sm text-base-content/70">
        Il cliente ha piu sedi. Scegli quella a cui associare
        {{ sitePickAction === 'withdraw' ? ' il ritiro.' : " l'ordine." }}
      </p>

      <div class="mt-3 flex flex-col gap-2">
        <button
          v-for="s in (customerForSitePick?.sites || [])"
          :key="s.id"
          class="btn btn-outline w-full justify-between"
          type="button"
          @click="goActionForSite(s.id)"
        >
          <span>{{ s.name }}</span>
          <span v-if="s.is_main" class="badge badge-primary">Principale</span>
        </button>
      </div>

      <div class="modal-action">
        <button class="btn btn-error btn-outline" type="button" @click="siteModal?.close()">Annulla</button>
      </div>
    </div>

    <form method="dialog" class="modal-backdrop">
      <button>close</button>
    </form>
  </dialog>
</template>

<script setup>
import RegistryDeleteAction from '@/Components/Registry/RegistryDeleteAction.vue'
import {useRegistryDelete} from '@/Composables/useRegistryDelete'
const {deleting, removeRecord} = useRegistryDelete()

import { ref, computed, nextTick } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import Box from '@/Components/UI/Box.vue'
import CustomerSpace from '@/Components/CustomerSpace.vue'
import CustomerAddress from '@/Components/CustomerAddress.vue'

const props = defineProps({
  customers: { type: [Object, Array], required: true },
})

const customersArr = computed(() =>
  Array.isArray(props.customers?.data) ? props.customers.data : props.customers
)

const siteModal = ref(null)
const customerForSitePick = ref(null)
const sitePickAction = ref('order')

function onCreateOrderClick(cust) {
  sitePickAction.value = 'order'
  openSitePickerForCustomer(cust)
}

function onCreateWithdrawClick(cust) {
  sitePickAction.value = 'withdraw'
  openSitePickerForCustomer(cust)
}

function openSitePickerForCustomer(cust) {
  customerForSitePick.value = cust
  const sites = cust?.sites || []
  const count = cust?.sites_count ?? sites.length

  if (!count) {
    alert('Questo cliente non ha sedi configurate.')
    return
  }

  if (count === 1) {
    const onlySite = sites[0]
    if (!onlySite) {
      alert('Sede non disponibile.')
      return
    }
    goActionForSite(onlySite.id)
    return
  }

  nextTick(() => siteModal.value?.showModal?.())
}

function goActionForSite(siteId) {
  siteModal.value?.close?.()
  const customerId = customerForSitePick.value?.id

  if (sitePickAction.value === 'withdraw') {
    router.get(route('withdraw.create', { site: siteId, customer: customerId }))
    return
  }

  router.get(route('order.create', { site: siteId }))
}
</script>
