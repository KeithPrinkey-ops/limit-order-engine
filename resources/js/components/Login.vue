<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

axios.defaults.withCredentials = true
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'

const router = useRouter()

const form = reactive({
    email: '',
    password: '',
})

const error = ref<string | null>(null)
const loading = ref(false)

async function submit() {
    error.value = null
    loading.value = true

    try {
        // Required for Sanctum session auth
        await fetch('/sanctum/csrf-cookie', {
            method: 'GET',
            credentials: 'include'
        })

        function getCookie(name) {
            return document.cookie
                .split('; ')
                .find(row => row.startsWith(name + '='))
                ?.split('=')[1]
        }

        const csrfToken = decodeURIComponent(getCookie('XSRF-TOKEN'))
        if (csrfToken) {
            form['X-XSRF-TOKEN'] = csrfToken
        }

        const res = await fetch('/login', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(form)
        })

        if (!res.ok) {
             new Error('Invalid credentials')
        }

        await router.push('/orders')

    } catch (e) {
        error.value = 'Invalid email or password'
    } finally {
        loading.value = false
    }
}

</script>
<template>
    <section class="px-8 py-16 w-full bg-zinc-100 xl:px-8">
        <div class="mx-auto max-w-5xl">
            <div class="flex flex-col items-center md:flex-row">
                <div class="space-y-5 w-full md:w-3/5 md:pr-16">
                    <p class="font-medium text-blue-500 uppercase">
                        Trading Platform
                    </p>

                    <h2 class="text-2xl font-extrabold leading-none text-black sm:text-3xl md:text-5xl">
                        Access Your Trading Dashboard
                    </h2>

                    <p class="text-xl text-zinc-600 md:pr-16">
                        Sign in to view wallet balances, place buy and sell orders,
                        and monitor real-time orderbook activity. This interface
                        demonstrates authenticated access to a Laravel-powered
                        trading API with live updates.
                    </p>

                    <p class="text-lg text-zinc-500 md:pr-16">
                        The system uses Laravel Sanctum for session authentication
                        and Vue.js for reactive UI updates.
                    </p>
                </div>

                <div class="mt-16 w-full md:mt-0 md:w-2/5">
                    <div class="overflow-hidden relative z-10 p-8 px-7 py-10 h-auto bg-white rounded-lg border-b-2 shadow-2xl border-zinc-300">
                        <h3 class="mb-6 text-2xl font-medium text-center">
                            Sign in to Your Account
                        </h3>

                        <input
                            type="email"
                            name="email"
                            id="email"
                            class="block px-4 py-3 mb-4 w-full rounded-lg border-2 border-transparent border-zinc-200 focus:ring focus:ring-blue-500 focus:outline-none"
                            placeholder="Email address" />

                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="block px-4 py-3 mb-4 w-full rounded-lg border-2 border-transparent border-zinc-200 focus:ring focus:ring-blue-500 focus:outline-none"
                            placeholder="Password" />

                        <div class="block">
                            <button
                                class="px-3 py-4 w-full font-medium text-white bg-blue-600 rounded-lg transition hover:bg-blue-700">
                                Sign In
                            </button>
                        </div>

                        <p class="mt-4 w-full text-sm text-center text-zinc-500">
                            Don’t have an account?
                            <a href="/register" class="text-blue-500 underline">
                                Create one here
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
