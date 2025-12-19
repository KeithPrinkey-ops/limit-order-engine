<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useRouter } from 'vue-router'

const router = useRouter()
const symbol = ref('BTC')

const profile = ref<any>(null)
const orderbook = ref<{ buy: any[]; sell: any[] }>({ buy: [], sell: [] })
const allOrders = ref<any[]>([])
const loading = ref(true)
const error = ref<string | null>(null)

async function loadProfile() {
    try {
        const { data } = await axios.get('/api/profile')
        profile.value = data
    } catch (err: any) {
        if (err.response?.status === 401) {
            router.push('/login')
            return
        }
        throw err // Keep this for non-401 errors
    }
}

async function loadOrders() {
    try {
        const { data } = await axios.get('/api/orders', {
            params: { symbol: symbol.value },
        })
        orderbook.value = data.orderbook
        allOrders.value = data.orders
    } catch (err: any) {
        if (err.response?.status === 401) {
            router.push('/login')
            return
        }
        throw err // Keep this for non-401 errors
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
                        Wallet & Orders
                    </h1>
                    <p class="text-slate-500 mt-1">
                        Account balances, live orderbook, and order history
                    </p>
                </div>
            </div>

            <div v-if="loading" class="text-center text-slate-500 py-20">
                Loading…
            </div>

            <div v-else class="space-y-10">

                <!-- WALLET SUMMARY (TOP KPI ROW) -->
                <section class="grid grid-cols-1 sm:grid-cols-3 gap-8">
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
                </section>

                <!-- MAIN DASHBOARD GRID -->
                <section class="grid grid-cols-1 lg:grid-cols-4 xl:grid-cols-1 gap-6 mt-5">

                    <!-- ORDERBOOK -->
                    <div class="xl:col-span-1 bg-white rounded-xl border border-slate-200 shadow-sm">
                        <div class="px-6 py-4 border-b border-slate-200">
                            <h2 class="text-lg font-semibold text-slate-800">
                                Orderbook
                            </h2>
                            <p class="text-sm text-slate-500">
                                {{ symbol }}
                            </p>
                        </div>

                        <div class="p-6 grid grid-cols-2 gap-6 text-sm">

                            <!-- BUY -->
                            <div>
                                <h3 class="mb-3 font-semibold text-emerald-600">
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
                                <h3 class="mb-3 font-semibold text-rose-600">
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
                    <div class="xl:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm">
                        <div class="px-6 py-4 border-b border-slate-200">
                            <h2 class="text-lg font-semibold text-slate-800">
                                Order History
                            </h2>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
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
