<template>
    <component :is="as" :type="as === 'button' ? type : undefined" class="btn relative min-h-12 shrink-0" :class="[variants[variant], shapes[shape]]" :disabled="as === 'button' && (disabled || busy)" :aria-disabled="disabled || busy ? true : undefined" :aria-busy="busy || undefined" :aria-label="$attrs['aria-label'] || (busy ? 'Operazione in corso' : undefined)" @click.capture="guard">
        <template v-if="busy"><span class="invisible inline-flex items-center gap-2"><slot /></span><span class="loading loading-spinner loading-sm absolute" aria-hidden="true"></span></template>
        <slot v-else />
    </component>
</template>
<script setup>
const variants = {
    function: 'btn-primary btn-outline',
    confirm: 'btn-success',
    neutral: 'btn-outline',
    success: 'btn-success',
    warning: 'btn-warning',
    cancel: 'btn-error btn-outline',
    remove: 'btn-error btn-outline',
    danger: 'btn-error',
}
const shapes = {default: 'h-auto whitespace-normal py-2', circle: 'btn-circle h-12 w-12 min-w-12 p-0', square: 'btn-square h-12 w-12 min-w-12 p-0'}
const props = defineProps({
    as: {type:[String,Object,Function],default:'button'},
    type: {type:String,default:'button'},
    variant: {type:String,default:'neutral',validator:value=>['function','confirm','neutral','success','warning','cancel','remove','danger'].includes(value)},
    shape: {type:String,default:'default',validator:value=>['default','circle','square'].includes(value)},
    disabled: Boolean,
    busy: Boolean,
})
const guard = event => {
    if (props.disabled || props.busy) {event.preventDefault();event.stopImmediatePropagation()}
}
</script>
