<template>
    <details class="disclosure-card group rounded-2xl border border-base-300 bg-base-100">
        <summary class="flex min-h-12 cursor-pointer list-none items-center gap-2 rounded-2xl p-3 focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary">
            <div class="min-w-0 flex-1"><slot name="summary" /></div>
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full border border-base-content/30 bg-base-200" aria-hidden="true">
                <svg class="h-6 w-6 transition-transform group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m6 9 6 6 6-6" /></svg>
            </span>
        </summary>
        <div class="space-y-2 px-3 pb-3"><slot /></div>
    </details>
</template>
<style scoped>
.disclosure-card > summary::-webkit-details-marker { display: none; }
</style>
