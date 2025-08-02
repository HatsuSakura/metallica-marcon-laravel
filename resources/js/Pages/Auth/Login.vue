<template>
    <div class="flex h-screen flex-col">
        <div class="flex flex-1 items-center justify-center p-2">

            <div class="card bg-base-100 w-96 shadow-xl p-4 m-auto">
                <form @submit.prevent="login">
                    <div class="w-1/2 mx-auto">
                        <div class="mt-4">
                            <label for="email" class="label">E-mail</label>
                            <input id="email" v-model="form.email" type="text" class="input">
                            <div class="input-error" v-if="form.errors.email">{{ form.errors.email }}</div>
                        </div>
                        <div class="mt-4">
                            <label for="password" class="label">Password</label>
                            <input id="password" v-model="form.password" type="password" class="input">
                            <div class="input-error" v-if="form.errors.password">{{ form.errors.password }}</div>
                        </div>
                        <div class="mt-8">
                            <button class="btn btn-primary w-full text-lg " type="submit">Login</button>
                            <div class="mt-2 text-center">

                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import {useForm, Link} from '@inertiajs/vue3'


const form = useForm({
    email:null,
    password:null
})

const login = () => form.post(route('login.store'))
</script>
