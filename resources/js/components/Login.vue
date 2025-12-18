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
            throw new Error('Invalid credentials')
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
    <div class="min-h-screen flex items-center justify-center bg-gray-100">
        <div class="w-full max-w-md bg-white rounded-xl shadow p-8">
            <h1 class="text-2xl font-semibold mb-6 text-center">
                Sign In
            </h1>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Email</label>
                    <input
                        v-model="form.email"
                        type="email"
                        required
                        class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Password</label>
                    <input
                        v-model="form.password"
                        type="password"
                        required
                        class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                    />
                </div>

                <p v-if="error" class="text-sm text-red-600">
                    {{ error }}
                </p>

                <button
                    type="submit"
                    :disabled="loading"
                    class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 disabled:opacity-50"
                >
                    {{ loading ? 'Signing in…' : 'Login' }}
                </button>
            </form>
        </div>
    </div>
</template>
