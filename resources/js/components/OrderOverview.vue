<script setup lang="ts">
import {ref, onMounted, reactive} from 'vue'
import axios from 'axios'
import {useRouter} from 'vue-router'

const router = useRouter()
const symbol = ref('BTC')

const profile = ref<any>(null)
const orderbook = ref<{ buy: any[]; sell: any[] }>({buy: [], sell: []})
const allOrders = ref<any[]>([])
const loading = ref(true)
const error = ref<string | null>(null)

// Password Update State
const passwordForm = reactive({
    current_password: '',
    password: '',
    password_confirmation: '',
})
const passwordUpdateLoading = ref(false)
const passwordUpdateError = ref<string | null>(null)
const passwordUpdateSuccess = ref<string | null>(null)

async function updatePassword() {
    passwordUpdateLoading.value = true
    passwordUpdateError.value = null
    passwordUpdateSuccess.value = null
    try {
        const {data} = await axios.post('/api/profile/password', passwordForm)
        passwordUpdateSuccess.value = data.message
        passwordForm.current_password = ''
        passwordForm.password = ''
        passwordForm.password_confirmation = ''
    } catch (err: any) {
        passwordUpdateError.value = err.response?.data?.message || 'An unknown error occurred.'
        if (err.response?.data?.errors) {
            // Handle specific validation errors if needed
            const errors = err.response.data.errors
            const firstErrorKey = Object.keys(errors)[0]
            passwordUpdateError.value = errors[firstErrorKey][0]
        }
    } finally {
        passwordUpdateLoading.value = false
    }
}

async function loadProfile() {
    try {
        const {data} = await axios.get('/api/profile')
        profile.value = data
    } catch (err: any) {
        if (err.response?.status === 401) {
            router.push('/login')
            return
        }
        throw err
    }
}

async function loadOrders() {
    try {
        const {data} = await axios.get('/api/orders', {
            params: {symbol: symbol.value},
        })
        orderbook.value = data.orderbook
        allOrders.value = data.orders
    } catch (err: any) {
        if (err.response?.status === 401) {
            router.push('/login')
            return
        }
        throw err
    }
}


async function refreshAll() {
    try {
        await loadProfile()
        await loadOrders()
        error.value = null
    } catch (err: any) {
        error.value = 'Failed to load data. Please try logging in again.'
        console.error(err)
    }
}

function statusLabel(status: number) {
    if (status === 1) return 'Open'
    if (status === 2) return 'Filled'
    if (status === 3) return 'Cancelled'
    return 'Unknown'
}

onMounted(async () => {
    await refreshAll()
    loading.value = false

    if (window.Echo && profile.value?.id) {
        window.Echo
            .private(`private-user.${profile.value.id}`)
            .listen('.OrderMatched', () => {
                refreshAll()
            })
    }
})
</script>
<template>
    <div class="min-h-screen bg-slate-100 px-6 py-10">
        <div class="mx-auto max-w-7xl space-y-10">

            <!-- HEADER -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900">
                        Welcome, {{ profile?.name }}
                    </h1>
                    <p class="text-slate-500 mt-1">
                        Here is your trading dashboard overview.
                    </p>
                </div>
            </div>

            <div v-if="loading" class="text-center text-slate-500 py-20">
                Loading…
            </div>

            <div v-else class="space-y-10">

                <!-- PROFILE & WALLET GRID -->
                <section class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Profile Card -->
                    <div class="lg:col-span-1 bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-slate-800">
                            Your Profile
                        </h2>
                        <div class="mt-4 space-y-4">
                            <div>
                                <label class="text-sm text-slate-500">Name</label>
                                <p class="font-medium text-slate-900">{{ profile.name }}</p>
                            </div>
                            <div>
                                <label class="text-sm text-slate-500">Email</label>
                                <p class="font-medium text-slate-900">{{ profile.email }}</p>
                            </div>

                            <hr class="my-6 border-slate-200">

                            <form @submit.prevent="updatePassword" class="space-y-5">
                                <h3 class="font-semibold text-slate-800">Update Password</h3>

                                <div v-if="passwordUpdateSuccess"
                                     class="p-3 m-5 text-sm text-emerald-700 bg-emerald-50 rounded-lg border border-emerald-200">
                                    {{ passwordUpdateSuccess }}
                                </div>
                                <div v-if="passwordUpdateError"
                                     class="p-3 text-sm text-red-700 bg-red-50 rounded-lg border border-red-200">
                                    {{ passwordUpdateError }}
                                </div>

                                <div>
                                    <label for="current_password"
                                           class="m-2 block text-sm font-medium text-slate-600 mb-1">Current
                                        Password</label>
                                    <input v-model="passwordForm.current_password" type="password" id="current_password"
                                           class="block w-full rounded-lg border-slate-300 bg-slate-50 px-4 py-3 text-slate-800 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="Enter your current password">
                                </div>
                                <div>
                                    <label for="password" class="m-2 block text-sm font-medium text-slate-600 mb-1">New
                                        Password</label>
                                    <input v-model="passwordForm.password" type="password" id="password"
                                           class="block w-full rounded-lg border-slate-300 bg-slate-50 px-4 py-3 text-slate-800 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="Enter a new password">
                                </div>
                                <div>
                                    <label for="password_confirmation"
                                           class="m-2 block text-sm font-medium text-slate-600 mb-1">Confirm New
                                        Password</label>
                                    <input v-model="passwordForm.password_confirmation" type="password"
                                           id="password_confirmation"
                                           class="block w-full rounded-lg border-slate-300 bg-slate-50 px-4 py-3 text-slate-800 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="Confirm your new password">
                                </div>
                                <button type="submit" :disabled="passwordUpdateLoading"
                                        class="mt-5 pt-5 pb-5 w-full px-4 py-3 font-semibold text-white bg-blue-600 rounded-lg shadow-md transition-all hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                    {{ passwordUpdateLoading ? 'Updating...' : 'Update Password' }}
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Wallet Summary -->
                    <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-8">
                        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                            <p class="text-sm text-slate-500">USD Balance</p>
                            <p class="mt-2 text-3xl font-bold text-slate-900">
                                {{ profile.balance }}
                            </p>
                        </div>

                        <div
                            v-for="asset in profile.assets"
                            :key="asset.symbol"
                            class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                            <p class="text-sm text-slate-500">
                                {{ asset.symbol }} Balance
                            </p>
                            <p class="mt-2 text-3xl font-bold text-slate-900">
                                {{ asset.amount }}
                            </p>
                        </div>
                    </div>
                </section>

                <!-- MAIN DASHBOARD GRID -->
                <section class="grid grid-cols-2 lg:grid-cols-4 gap-6 mt-5">

                    <!-- ORDERBOOK -->
                    <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm">
                        <div class="px-6 py-4 border-b border-slate-200">
                            <h2 class="text-xl font-semibold text-slate-800">
                                Orderbook
                            </h2>
                            <p class="text-sm text-slate-500">
                                {{ symbol }}
                            </p>
                        </div>

                        <div class="p-6 grid grid-cols-2 gap-6 text-sm">
                            <!-- BUY -->
                            <div>
                                <h3 class="mb-3 font-semibold text-emerald-600 text-xl">
                                    Buy Orders
                                </h3>
                                <ul class="space-y-2">
                                    <li
                                        v-for="order in orderbook.buy"
                                        :key="order.id"
                                        class="flex justify-between rounded-md bg-emerald-50 px-3 py-2">
                                        <span>{{ order.amount }}</span>
                                        <span class="font-mono">@ {{ order.price }}</span>
                                    </li>
                                    <li
                                        v-if="!orderbook.buy.length"
                                        class="text-slate-400">
                                        No buy orders
                                    </li>
                                </ul>
                            </div>

                            <!-- SELL -->
                            <div>
                                <h3 class="mb-3 font-semibold text-rose-600 text-xl">
                                    Sell Orders
                                </h3>
                                <ul class="space-y-2">
                                    <li
                                        v-for="order in orderbook.sell"
                                        :key="order.id"
                                        class="flex justify-between rounded-md bg-rose-50 px-3 py-2">
                                        <span>{{ order.amount }}</span>
                                        <span class="font-mono">@ {{ order.price }}</span>
                                    </li>
                                    <li
                                        v-if="!orderbook.sell.length"
                                        class="text-slate-400">
                                        No sell orders
                                    </li>
                                </ul>
                            </div>

                        </div>
                    </div>

                    <!-- ORDER HISTORY -->
                    <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm">
                        <div class="px-6 py-4 border-b border-slate-200">
                            <h2 class="text-lg font-semibold text-slate-800">
                                Order History
                            </h2>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="col-span-2 w-full text-md">
                                <thead class="bg-slate-50 border-b">
                                <tr class="text-left text-slate-600">
                                    <th class="px-6 py-3">Symbol</th>
                                    <th class="px-6 py-3">Side</th>
                                    <th class="px-6 py-3">Price</th>
                                    <th class="px-6 py-3">Amount</th>
                                    <th class="px-6 py-3">Status</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr
                                    v-for="order in allOrders"
                                    :key="order.id"
                                    class="border-b last:border-0 hover:bg-slate-50">
                                    <td class="px-6 py-3 font-medium">
                                        {{ order.symbol }}
                                    </td>
                                    <td class="px-6 py-3 capitalize">
                                        {{ order.side }}
                                    </td>
                                    <td class="px-6 py-3">
                                        {{ order.price }}
                                    </td>
                                    <td class="px-6 py-3">
                                        {{ order.amount }}
                                    </td>
                                    <td class="px-6 py-3">
                                            <span
                                                class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium"
                                                :class="{
                                                    'bg-yellow-100 text-yellow-800': order.status === 1,
                                                    'bg-emerald-100 text-emerald-800': order.status === 2,
                                                    'bg-slate-200 text-slate-700': order.status === 3,
                                                }">
                                                {{ statusLabel(order.status) }}
                                            </span>
                                    </td>
                                </tr>

                                <tr v-if="!allOrders.length">
                                    <td
                                        colspan="5"
                                        class="px-6 py-10 text-center text-slate-400">
                                        No orders yet
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </section>

            </div>
        </div>
    </div>
</template>
