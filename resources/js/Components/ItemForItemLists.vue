<template>

    <div class="flex flex-row items-center gap-2">
        <font-awesome-icon :icon="['fas', 'scroll']" class="text-2xl"/>            
        <font-awesome-icon :icon="['fas', 'arrow-right']" />

        <div class="flex flex-col">
            <div>
                {{ item.holder_quantity }} x {{ item.holder.name }} = {{ item.weight_declared }} Kg di 
                <span :class="item.cer_code.is_dangerous? 'text-error': ''">{{ item.cer_code.code }} </span>
                
            </div>
            <div>
                {{ item.cer_code.description }}
            </div>
            <div>
                {{ item.description }}
            </div>
        </div>


    </div>

</template>

<script setup>

const props = defineProps({
    item: Object,
})
</script>