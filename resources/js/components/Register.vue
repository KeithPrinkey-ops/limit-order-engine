<script setup lang="ts">
import {ref, reactive} from 'vue'
import {useRouter} from 'vue-router'

const router = useRouter()

const form = reactive({
    name: '',
    email: '',
    password: '',
    password_confirmation: ''
})

const errors = reactive({
    name: null,
    email: null,
    password: null,
    password_confirmation: null,
    general: null
})

const loading = ref(false)


async function register() {
    loading.value = true
    // Clear previous errors
    Object.keys(errors).forEach(k => errors[k] = null)

    try {
        // Get CSRF cookie for Laravel session auth
        await fetch('/sanctum/csrf-cookie', {
            method: 'GET',
            credentials: 'include'
        })

        const res = await fetch('/api/register', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(form)
        })

        if (res.status === 422) {
            const data = await res.json()
            if (data.errors) {
                for (const key of Object.keys(data.errors)) {
                    if (errors.hasOwnProperty(key)) {
                        errors[key] = data.errors[key][0]
                    }
                }
            }
            loading.value = false
            return
        }

        if (!res.ok) {
            const text = await res.text()
            errors.general = text || 'Registration failed'
            loading.value = false
            return
        }

        // Success - redirect to orders page using Vue router
        loading.value = false
        await router.push('/orders')
    } catch (err) {
        errors.general = 'Network error'
        loading.value = false
    }
}


</script>
<template>
    <section class="w-full bg-white">
        <div class="mx-auto max-w-7xl">
            <div class="flex flex-col lg:flex-row">
                <div
                    class="relative w-full bg-cover bg-linear-to-r from-white via-white lg:w-6/12 xl:w-7/12 to-zinc-100">
                    <div
                        class="flex relative flex-col justify-center items-center px-10 my-20 w-full h-full lg:px-16 lg:my-0">
                        <div class="flex flex-col items-start space-y-8 tracking-tight lg:max-w-3xl">
                            <div class="relative">
                                <p class="mb-2 font-medium uppercase text-zinc-700">
                                    Trading Platform
                                </p>
                                <h2 class="text-5xl font-bold text-zinc-900 xl:text-6xl">
                                    Real-time Limit Order Engine
                                </h2>
                            </div>

                            <p class="text-2xl text-zinc-700">
                                Create an account to access wallet balances, place buy and sell
                                orders, and view real-time orderbook updates.
                            </p>

                            <p class="text-lg text-zinc-600">
                                This application demonstrates authenticated trading, order matching,
                                and real-time updates using a Laravel API with a Vue.js frontend.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="w-full bg-white lg:w-6/12 xl:w-5/12">
                    <div class="flex flex-col justify-start items-start p-10 w-full h-full lg:p-16 xl:p-24">
                        <h4 class="w-full text-3xl font-bold">
                            Create Account
                        </h4>

                        <p class="text-lg text-zinc-500">
                            Register to access the trading dashboard, or
                            <a href="/login" class="text-blue-600 underline">sign in</a>
                            if you already have an account.
                        </p>

                        <div class="relative mt-10 space-y-8 w-full">
                            <div class="relative">
                                <label class="font-medium text-zinc-900">Name</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    class="block px-4 py-4 mt-2 w-full text-xl rounded-lg placeholder-zinc-400 bg-zinc-200 focus:outline-none focus:ring-4 focus:ring-blue-600 focus:ring-opacity-50"
                                    placeholder="Enter your full name"/>
                                <p v-if="errors.name" class="text-sm text-red-600 mt-1">{{ errors.name }}</p>

                            </div>

                            <div class="relative">
                                <label class="font-medium text-zinc-900">Email</label>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    class="block px-4 py-4 mt-2 w-full text-xl rounded-lg placeholder-zinc-400 bg-zinc-200 focus:outline-none focus:ring-4 focus:ring-blue-600 focus:ring-opacity-50"
                                    placeholder="Enter your email address"/>
                                <p v-if="errors.email" class="text-sm text-red-600 mt-1">{{ errors.email }}</p>

                            </div>

                            <div class="relative">
                                <label class="font-medium text-zinc-900">Password</label>
                                <input
                                    v-model="form.password"
                                    type="password"
                                    class="block px-4 py-4 mt-2 w-full text-xl rounded-lg placeholder-zinc-400 bg-zinc-200 focus:outline-none focus:ring-4 focus:ring-blue-600 focus:ring-opacity-50"
                                    placeholder="Create a secure password"/>
                                <p v-if="errors.password" class="text-sm text-red-600 mt-1">{{ errors.password }}</p>

                            </div>

                            <div class="relative">
                                <label class="font-medium text-zinc-900">Confirm Password</label>
                                <input
                                    v-model="form.password_confirmation"
                                    type="password"
                                    class="block px-4 py-4 mt-2 w-full text-xl rounded-lg placeholder-zinc-400 bg-zinc-200 focus:outline-none focus:ring-4 focus:ring-blue-600 focus:ring-opacity-50"
                                    placeholder="Confirm your password"/>
                                <p v-if="errors.password_confirmation" class="text-sm text-red-600 mt-1">
                                    {{ errors.password_confirmation }}</p>

                            </div>

                            <div class="relative pt-5">
                                <button
                                    type="button"
                                    @click="register"
                                    :disabled="loading"
                                    class="inline-block px-5 py-4 w-full text-lg font-medium text-center text-white bg-blue-600 rounded-lg transition duration-200 hover:bg-blue-700 ease disabled:opacity-50">
                                    {{ loading ? 'Creating Account…' : 'Create Trading Account' }}
                                </button>
                            </div>

                            <p
                                v-if="errors.general"
                                class="text-sm text-red-600 text-center mt-4">
                                {{ errors.general }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
