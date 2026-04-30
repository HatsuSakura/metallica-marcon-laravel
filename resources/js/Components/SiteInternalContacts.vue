<template>
  <div v-if="contacts && contacts.length">
    <DataTable
      :data="contacts"
      :columns="columns"
      data-paging="false"
      data-filter="false"
      data-info="false"
    >
      <thead>
        <tr>
          <th>ID</th>
          <th>Ruolo</th>
          <th>Nome</th>
          <th>Cognome</th>
          <th>Telefono</th>
          <th>Cellulare</th>
          <th>E-Mail</th>
        </tr>
      </thead>
    </DataTable>
  </div>
  <div v-else class="opacity-70 text-sm">Nessun referente interno registrato.</div>
</template>

<script setup>
import { DataTable } from 'datatables.net-vue3'

defineProps({
  contacts: { type: Array, default: () => [] },
})

const columns = [
  { data: 'id', visible: false },
  { data: 'role' },
  { data: 'name' },
  { data: 'surname' },
  { data: 'phone' },
  { data: 'mobile' },
  { data: 'email' },
]
</script>
