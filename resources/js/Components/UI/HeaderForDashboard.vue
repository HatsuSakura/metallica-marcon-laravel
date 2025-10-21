<template>
    <div class="flex flex-col w-full">
    
        <div class="flex flex-row items-center justify-between mb-2">

            <div class="flex flex-row items-center justify-start mb-2 gap-4">
                <Link v-if="props.backLinkRoute && props.backLinkText"
                    class="btn btn-primary btn-outline btn-sm" 
                    :href="route(props.backLinkRoute)"
                >
                    <font-awesome-icon :icon="['fas', 'arrow-left']" class="text-xl"/>
                    Torna a {{ props.backLinkText}}
                </Link>
                <div class="font-medium text-lg">
                    <slot />
                </div>
            </div>
            <div v-if="props.linkRoute">
                <Link
                    class="btn btn-circle btn-primary" 
                    :href="route(props.linkRoute)"
                >
                    <font-awesome-icon :icon="['fas', props.linkIcon]" class="text-xl" />
                    <span v-if="props.linkText" class="ml-2">{{ props.linkText }}</span>
                </Link>
            </div>
        </div>
        <div class="divider"></div>
    
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    backLinkRoute: String,
    backLinkText: String,
    linkRoute: String,
    linkIcon: String,
    linkText: String,
})

</script>

