<template>
<div class="flex flex-col-reverse md:grid md:grid-cols-12 gap-4">
    <Box v-if="listing.images.length" class="md:col-span-7 flex items-center w-full">
        <div class="grid grid-cols-2 gap-1">
            <img v-for="image in listing.images" 
                :key="image.id"
                :src="image.src"
            />
        </div>
    </Box>

    <EmptyState v-else class="md:col-span-7 flex items-center w-full">No images</EmptyState>

    <div class="md:col-span-5 flex flex-col gap-4">
        <Box>
            <template #header>
                Basic Info
            </template>
            <Price :price="listing.price" class="text-2xl text-bold"/>
            <ListingSpace :listing="listing" class="text-lg"/>
            <ListingAddress :listing="listing" class="text-gray-500"/>
        </Box>

        <Box>
            <template #header>
                Monthly Payment
            </template>
            <div>
                <label class="label">Interest rate ({{ interestRate }}%)</label>
                <input 
                    v-model.number="interestRate"
                    type="range" min="0.1" max="30" step="0.1" 
                    class="w-full h-4 bg-gray-200 dark:bg-gray-700 runded-lg appearence-none cursor-pointer"
                />

                <label class="label">Duration ({{ duration }} years)</label>
                <input 
                    v-model.number="duration"
                    type="range" min="3" max="35" step="1" 
                    class="w-full h-4 bg-gray-200 dark:bg-gray-700 runded-lg appearence-none cursor-pointer"
                />

                <div class="text-gray-600 dark:text-gray-300 mt-2">
                    <div class="text-gray-400 ">Your monthly paymet</div>
                    <Price :price="monthlyPayment" class="text-3xl" />
                </div>

                <div class="text-gray-600 dark:text-gray-300 mt-2 text-xl">
                    <div class="flex justify-between">
                        <div>Total Paid</div>
                        <div class="font-medium">
                            <Price :price="totalPaid" />
                        </div>
                    </div>
                </div>
                <div class="text-gray-600 dark:text-gray-300 mt-2 text-xl">
                    <div class="flex justify-between">
                        <div>Total Interest</div>
                        <div class="font-medium">
                            <Price :price="totalInterest" />
                        </div>
                    </div>
                </div>

            </div>
            Make an offer
        </Box>

        <MakeOffer 
            v-if="user && !offerMade"
            :listing-id="listing.id" 
            :price="listing.price"
            @offer-updated="offer = $event"
        ></MakeOffer>
        <OfferMade
            v-if="user && offerMade"
            :offerMade="offerMade"
        >
        </OfferMade>
    </div>
</div>
</template>

<script setup>
    import {ref, computed} from 'vue'
    import { usePage } from '@inertiajs/vue3';
    import { useMonthlyPayment } from '@/Composables/useMonthlyPayment';
    import Price from '@/Components/Price.vue';
    import ListingSpace from '@/Components/ListingSpace.vue';
    import ListingAddress from '@/Components/ListingAddress.vue';
    import Box from '@/Components/UI/Box.vue';
    import MakeOffer from './Show/Components/MakeOffer.vue';
import OfferMade from './Show/Components/OfferMade.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';

const interestRate = ref(2.5);
const duration = ref(25);
const props = defineProps({
    listing: Object,
    offerMade: Object,
})

const offer = ref(props.listing.price)
const { monthlyPayment, totalPaid, totalInterest } = useMonthlyPayment(offer, interestRate, duration );

const page = usePage()
const user = computed(
    () => page.props.user,
)

</script>