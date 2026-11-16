<template>
    <div class="planning-container min-w-0">
        <div class="planning-columns grid min-w-0 grid-cols-1 gap-4">
            <div class="planning-main min-w-0"><slot name="main" /></div>
            <aside class="planning-sidebar min-w-0"><slot name="sidebar" /></aside>
        </div>
    </div>
</template>
<style scoped>
.planning-container { container-type: inline-size; }
@container (min-width: 64rem) {
    .planning-columns { grid-template-columns: minmax(0,3fr) minmax(17rem,1fr); }
}
</style>
