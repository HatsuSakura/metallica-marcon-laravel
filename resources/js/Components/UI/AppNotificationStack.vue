<template>
    <div id="messageQueue" class="notification-container">
        <TransitionGroup name="notification" tag="div" class="mt-8 space-y-4 container w-full mx-auto">
            <div v-for="message in messages" :key="message.id" class="alert" :class="tones[message.type]" :role="message.type === 'error' ? 'alert' : 'status'">
                <font-awesome-icon :icon="['fas', icons[message.type]]" class="shrink-0" aria-hidden="true" />
                <div class="min-w-0 flex-1 break-words">
                    {{ message.text }}
                    <button v-if="message.action" type="button" class="btn btn-sm btn-outline mt-2" :disabled="message.action.disabled?.()" @click="$emit('action',message.id)">{{ message.action.label }}</button>
                </div>
                <button type="button" class="btn btn-circle btn-outline h-[42px] min-h-[42px] w-[42px] shrink-0" aria-label="Chiudi messaggio" @click="$emit('dismiss',message.id)"><font-awesome-icon :icon="['fas','xmark']" /></button>
            </div>
        </TransitionGroup>
    </div>
</template>
<script setup>
defineProps({messages:{type:Array,default:()=>[]}})
defineEmits(['action','dismiss'])
const tones = {success:'alert-success',error:'alert-error',warning:'alert-warning',info:'alert-info'}
const icons = {success:'circle-check',error:'circle-xmark',warning:'triangle-exclamation',info:'circle-info'}
</script>
<style scoped>
.notification-container { position: fixed; top: calc(4rem + env(safe-area-inset-top)); width: 100%; z-index: 9999; pointer-events: none; max-height: calc(100dvh - 4rem); overflow-y: auto; }
.notification-container {
    inset-inline: 0;
    box-sizing: border-box;
    padding-left: max(0.75rem, env(safe-area-inset-left));
    padding-right: max(0.75rem, env(safe-area-inset-right));
}
/* DaisyUI stacks alerts below sm: keep icon, content and close on one row. */
.notification-container .alert { display: flex; align-items: center; gap: 0.75rem; padding: 0.375rem 0.75rem; text-align: left; pointer-events: auto; }
.notification-leave-active { transition: opacity 0.5s ease; }
.notification-leave-to { opacity: 0; }
@media (prefers-reduced-motion: reduce) {
    .notification-leave-active { transition: none; }
}
</style>
