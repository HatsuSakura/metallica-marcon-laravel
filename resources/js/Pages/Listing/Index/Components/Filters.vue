<template>
<form @submit.prevent="filter">
    <div class="mb-8 mt-4 flex flex-wrap gap-2">

        <div class="flex flex-nowrap items-center">
              <input v-model.number="filterForm.priceFrom" type="text" placeholder="Price From" class="input-filter-l"/>
              <input v-model.number="filterForm.priceTo" type="text" placeholder="Price To" class="input-filter-r"/>
        </div>

        <div class="flex flex-nowrap items-center">
              <select name="" id="" class="input-filter-l" v-model="filterForm.beds">
                <option :value="null">Beds</option>
                <option v-for="n in 5" :key="n" :value="n">{{ n }}</option>
                <option>6+</option>
              </select>
              <select name="" id="" class="input-filter-r" v-model="filterForm.baths">
                <option :value="null">Baths</option>
                <option v-for="n in 5" :key="n" :value="n">{{ n }}</option>
                <option>6+</option>
              </select>
        </div>

        <div class="flex flex-nowrap items-center">
              <input v-model.number="filterForm.areaFrom" type="text"  placeholder="Area From" class="input-filter-l"/>
              <input v-model.number="filterForm.areaTo" type="text"  placeholder="Area To" class="input-filter-r"/>
        </div>

        <button type="submit" class="btn-normal">Filter</button>
        <button type="reset" @click="clear" class="btn-normal">Clear</button>

    </div>
</form>
</template>

<script setup>
import {useForm} from '@inertiajs/vue3'


// defined here and also in index page, beacuse we need to "import" data filkters from URL everytime someone paste the url with filter data inside
const props = defineProps({
  filters: Object,
})

const filterForm = useForm({
  priceFrom: props.filters.priceFrom ?? null,
  priceTo: props.filters.priceTo ?? null,
  beds: props.filters.beds ?? null,
  baths: props.filters.baths ?? null,
  areaFrom: props.filters.areaFrom ?? null,
  areaTo: props.filters.areaTo ?? null,
})

const filter = () => {
  filterForm.get(
    route('listing.index'),
    {
      preserveState: true,
      preserveScroll: true,
    },
  )
}

const clear = () => {
  filterForm.priceFrom = null
  filterForm.priceTo = null
  filterForm.beds = null
  filterForm.baths = null
  filterForm.areaFrom = null
  filterForm.areaTo = null
  filter()
}

</script>
