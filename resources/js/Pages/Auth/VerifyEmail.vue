<template>
    <div class="flex flex-col gap-2 mb-8">
        <h2 class="font-medium text-lg flex items-center gap-2">
            <font-awesome-icon :icon="['fas', 'triangle-exclamation']" class="text-4xl"/>
            ATTENZIONE
        </h2>
        <p>
            Per visualizzare i contenuti devi essere un <span class="font-medium">utente verificato</span>. 
        </p>
        <p>
            Controlla la tua casella di <span class="font-medium">posta elettronica</span> per verificare il tuo indirizzo email.
        </p>
        <p>
            Se non trovi una <span class="font-medium">mail con un link di verifica</span> richiedine una nuova con il pulsante qui sotto.
        </p>
    </div>

        <Link
            :href="route('verification.send')"
            method="post"
            as="button"
            class="btn btn-primary btn-outline"
        >
            <font-awesome-icon :icon="['fas', 'paper-plane']" class="text-lg"/>    
            Invia email di verifica
        </Link>


</template>

<script setup>
import { Link } from '@inertiajs/vue3';

</script>