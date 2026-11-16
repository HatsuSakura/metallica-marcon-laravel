<template>
    <VueDatePicker ref="picker" v-bind="$attrs" :ui="{menu:['app-datetime-menu', step !== 0 ? 'date-step-time' : 'date-step-calendar']}" :model-value="modelValue" :uid="inputUid" :name="name"
        :disabled="disabled" :required="required" :placeholder="placeholder" :aria-labels="ariaLabels"
        locale="it" :flow="['calendar','time']" :auto-apply="false" :time-picker-inline="false"
        :start-time="proposedTime" :minutes-increment="5" :minutes-grid-increment="5" :enable-seconds="false"
        :close-on-scroll="false" cancel-text="Annulla" select-text="Conferma" :action-row="{showPreview:false}"
        @open="prepareProposal" @flow-step="step = $event" @time-picker-open="step = 1" @time-picker-close="step = 0" @closed="$emit('closed')"
        @update:model-value="$emit('update:modelValue', $event)">
        <template #dp-input="{value, isMenuOpen, openMenu, closeMenu, onFocus, onBlur}">
            <div class="relative">
                <font-awesome-icon :icon="['fas','calendar-days']" class="date-input-icon" aria-hidden="true" />
                <input :id="`dp-input-${inputUid}`" :name="name" :value="value" :disabled="disabled" :required="required"
                    :placeholder="placeholder" :aria-label="ariaLabels.input" :aria-expanded="isMenuOpen" aria-haspopup="dialog"
                    class="dp__input date-input" readonly autocomplete="off"
                    @click.stop="openMenu()" @keydown.enter.prevent.stop="openMenu()" @keydown.space.prevent.stop="openMenu()"
                    @keydown.esc.stop="closeMenu()" @focus="onFocus" @blur="onBlur" />
            </div>
        </template>

        <template #action-row="{disabled:invalid}">
            <div class="flex w-full justify-between gap-2">
                <AppAction size="sm" variant="cancel" @click="picker.closeMenu()"><font-awesome-icon :icon="['fas','xmark']" aria-hidden="true" />Annulla</AppAction>
                <AppAction size="sm" variant="neutral" class="btn-success" :disabled="invalid" @click="picker.selectDate()"><font-awesome-icon :icon="['fas','check']" aria-hidden="true" />Conferma</AppAction>
            </div>
        </template>
    </VueDatePicker>
</template>
<script setup>
import {computed, getCurrentInstance, nextTick, ref} from 'vue'
import AppAction from './AppAction.vue'
import VueDatePicker from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'
import {floorToFiveMinutes} from '@/utils/dateTimeProposal'
import {parseServerDateTime} from '@/utils/serverDateTime'
defineOptions({inheritAttrs:false})
const props = defineProps({modelValue:[Date,String], uid:String, name:String, disabled:Boolean, required:Boolean, placeholder:String, ariaLabels:{type:Object,default:()=>({input:'Data e ora'})}})
defineEmits(['update:modelValue','closed'])
const generatedUid = `datetime-${getCurrentInstance().uid}`
const inputUid = computed(() => props.uid || generatedUid)
const picker = ref(null)
const step = ref(0)
const clock = () => {const date = floorToFiveMinutes(); return {hours:date.getHours(),minutes:date.getMinutes(),seconds:0}}
const proposedTime = ref(clock())
const prepareProposal = async () => {
    step.value = 0
    proposedTime.value = clock()
    await nextTick()
    // Draft only: opening/cancelling must never change an existing saved value.
    const draft = floorToFiveMinutes(parseServerDateTime(props.modelValue) ?? new Date())
    picker.value?.updateInternalModelValue(draft)
}
</script>
<style scoped>
.date-input { min-height:3rem; font-size:1rem; padding-left:2rem; padding-right:2rem; cursor:pointer; }
.date-input-icon { position:absolute; left:.65rem; top:50%; transform:translateY(-50%); z-index:1; pointer-events:none; }
</style>
<style>
/* Namespaced on the actual menu: also applies when the menu is teleported. */
.app-datetime-menu { --dp-background-color:oklch(var(--b1)); --dp-text-color:oklch(var(--bc)); --dp-border-color:oklch(var(--bc) / .5); --dp-hover-color:oklch(var(--b2)); --dp-hover-text-color:oklch(var(--bc)); --dp-primary-color:oklch(var(--p)); --dp-primary-text-color:oklch(var(--pc)); }
.app-datetime-menu .dp__time_display { font-size:2rem; min-width:3rem; height:3rem; }
.app-datetime-menu.date-step-time .dp__calendar, .app-datetime-menu.date-step-time .dp__month_year_wrap { display:none; }
.app-datetime-menu.date-step-time .dp--tp-wrap > .dp__overlay.dp--overlay-absolute { position:relative; height:14rem; }
</style>
