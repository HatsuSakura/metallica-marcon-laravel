<template>
    <div class="grid min-w-0 grid-cols-[42px_minmax(0,1fr)_42px] items-center gap-1" role="group" :aria-label="label">
        <button type="button" class="btn btn-square btn-outline h-[42px] min-h-[42px] w-[42px] min-w-[42px] p-0 text-xl" :disabled="disabled || value == null || value <= min" :aria-label="`Diminuisci ${label.toLowerCase()}`" @click="step(-1)">−</button>
        <input :id="inputId" :value="value ?? ''" type="number" inputmode="numeric" :min="min" :max="max ?? undefined" step="1" :required="required" :placeholder="placeholder" :disabled="disabled" :aria-label="label" class="quantity-counter input input-bordered h-[42px] min-h-[42px] w-full min-w-0 px-1 text-center text-lg font-semibold tabular-nums" @input="input" />
        <button type="button" class="btn btn-square btn-outline h-[42px] min-h-[42px] w-[42px] min-w-[42px] p-0 text-xl" :disabled="disabled || (max != null && (value ?? min) >= max)" :aria-label="`Aumenta ${label.toLowerCase()}`" @click="step(1)">+</button>
    </div>
</template>
<script setup>
import {computed} from 'vue'
import {countValue,stepCount} from '@/utils/quantityCounter'
const props = defineProps({modelValue:{type:[Number,String],default:null},label:{type:String,required:true},inputId:String,min:{type:Number,default:0},max:{type:Number,default:null},disabled:Boolean,required:Boolean,placeholder:String})
const emit = defineEmits(['update:modelValue'])
const value = computed(() => countValue(props.modelValue))
const step = direction => emit('update:modelValue',stepCount(value.value,direction,props.min,props.max))
const input = event => {
    const next = countValue(event.target.value,props.min,props.max)
    event.target.value = next == null ? '' : String(next)
    emit('update:modelValue',next)
}
</script>
<style scoped>
.quantity-counter { appearance: textfield; -moz-appearance: textfield; }
.quantity-counter::-webkit-inner-spin-button,
.quantity-counter::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
</style>
