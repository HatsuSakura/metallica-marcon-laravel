<template>
    <Box>
        <div>
            <Link :href="route('customer.show', {customer:customer.id})">
                <div class="flex items-center gap-2">
                    <Price :price="customer.price" class="text-2xl text-bold"/>
                    <div class="text-xs text-gray-500">
                        <Price :price="monthlyPayment" /> pm
                    </div>
                </div>
                <CustomerSpace :customer="customer" class="text-lg"/>
                <CustomerAddress :customer="customer" class="text-gray-500"/>
            </Link>
        </div>
    </Box>

</template>

<script setup>

    import { Link } from '@inertiajs/vue3';
    import { useMonthlyPayment } from '@/Composables/useMonthlyPayment';
    import Price from '@/Components/Price.vue';
    import Box from '@/Components/UI/Box.vue';
import CustomerSpace from '@/Components/CustomerSpace.vue';
import CustomerAddress from '@/Components/CustomerAddress.vue';

    const props = defineProps({
        customer: Object,
    })

    const { monthlyPayment } = useMonthlyPayment(props.customer.price, 2.5, 25 );

</script>