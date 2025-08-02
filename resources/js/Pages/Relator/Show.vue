<template>
    <div class="mb-4">
        <Link
            :href="route('relator.listing.index')"
        >
        Go back to Listings
        </Link>
    </div>

    <section class="flex flex-col-reverse md:grid md:grid-cols-12 gap-4">
        <Box v-if="!hasOffers" class="flex md:col-span-7 items-center">
            <div class="w-full text-center font-medium text-gray-500">
                No offers
            </div>
        </Box>
        <div v-else class="md:col-span-7 items-center">
            <Offer 
                v-for="offer in listing.offers"
                :offer="offer" 
                :listing-price="listing.price"
                :is-sold="listing.sold_at != null"
                :key="offer.id" class="mb-4" 
            />
        </div>

        <div class="md:col-span-5">
            <Box >
                <template #header>Basic Info</template>
                <Price :price="listing.price" class="text-2xl font-bold" />
                <ListingSpace :listing="listing" class="text-lg" />
                <ListingAddress :listing="listing" class="text-gray-500" />
            </Box>
        </div>


    </section>
</template>

<script setup>
import ListingAddress from '@/Components/ListingAddress.vue';
import ListingSpace from '@/Components/ListingSpace.vue';
import Price from '@/Components/Price.vue';
import Box from '@/Components/UI/Box.vue';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Offer from '@/Pages/Relator/Show/Components/Offer.vue';

const props = defineProps({listing:Object})
const hasOffers = computed(
    () => props.listing.offers.length,
)
</script>