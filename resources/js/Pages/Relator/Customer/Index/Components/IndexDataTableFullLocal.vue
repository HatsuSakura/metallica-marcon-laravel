<template>
    
    <div class="bg-white text-gray-900 overflow-hidden shadow-sm sm:rounded-lg">
                <DataTable
                    :data="props.customers"
                    :columns="columns"
                >
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Ragione Sociale</th>
                            <th>Partita IVA</th>
                            <th>Codice Fiscale</th>
                        </tr>
                    </thead>
                </DataTable>
        </div>


</template>

<script setup>

const props = defineProps({ 
    customers: Object,
})

const columns = [
    { data: 'id' },
    { data: 'ragioneSociale' },
    { data: 'partitaIva' },
    { data: 'codiceFiscale' },
];

</script>

<style scoped>
select[name^="DataTables"] {
    padding-right: 16px !important;
}

/*
[att=value] - The attribute has to have the exact value specified.
[att~=value] - The attribute’s value needs to be a whitespace separated list of words (for example, class=”title featured home”), and one of the words is exactly the specified value.
[att|=value] - The attribute’s value is exactly “value” or starts with the word “value” and is immediately followed by “-“, so it would be “value-“.
[att^=value] - The attribute’s value starts with the specified value.
[att$=value] - The attribute’s value ends with the specified value.
[att*=value] - The attribute’s value contains the specified value.

input[name^="go"] { color: red }
input[name*="Description"] {
    width: 200px;
}

input[name="DataTables_Table_0_length"] {
  background: red;
}
*/
</style>