<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from 'axios'

const symbol = ref('BTC')

const profile = ref<any>(null)
const orderbook = ref<{ buy: any[]; sell: any[] }>({ buy: [], sell: [] })
const allOrders = ref<any[]>([])
const loading = ref(true)

async function loadProfile() {
    const { data } = await axios.get('/api/profile')
    profile.value = data
}

async function loadOrders() {
    const { data } = await axios.get('/api/orders', {
        params: { symbol: symbol.value },
    })

    // Expected backend response shape:
    // {
    //   orderbook: { buy: [], sell: [] },
    //   orders: []
    // }
    orderbook.value = data.orderbook
    allOrders.value = data.orders
}

async function refreshAll() {
    await loadProfile()
    await loadOrders()
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

    // Mandatory real-time integration
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
    <div class="min-h-screen bg-slate-100 px-6 py-12 grid place-items-center">
        <div class="w-full max-w-6xl mx-auto space-y-12">

            <!-- Header -->
            <div class="text-center">
                <h1 class="text-3xl font-bold text-slate-900">
                    Wallet & Orders
                </h1>
                <p class="text-slate-500 mt-1">
                    Balances, orderbook, and trading history
                </p>
            </div>

            <div v-if="loading" class="text-center text-slate-500">
                Loading…
            </div>

            <div v-else class="space-y-12">

                <!-- TOP ROW: Orderbook + Past Orders -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">

                    <!-- ORDERBOOK CARD -->
                    <section class="bg-white rounded-2xl shadow-sm border border-slate-200">
                        <div class="px-6 py-4 border-b border-slate-200">
                            <h2 class="text-lg font-semibold text-slate-800 text-center">
                                Orderbook
                            </h2>
                            <p class="text-sm text-slate-500 text-center">
                                {{ symbol }}
                            </p>
                        </div>

                        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8 text-sm">
                            <!-- Buy Orders -->
                            <div>
                                <h3 class="font-semibold text-emerald-600 mb-3">
                                    Buy Orders
                                </h3>
                                <ul class="space-y-2">
                                    <li
                                        v-for="order in orderbook.buy"
                                        :key="order.id"
                                        class="flex justify-between rounded-lg bg-emerald-50 px-3 py-2">
                                        <span>{{ order.amount }}</span>
                                        <span class="font-mono">@ {{ order.price }}</span>
                                    </li>
                                    <li v-if="!orderbook.buy.length" class="text-slate-400">
                                        No buy orders
                                    </li>
                                </ul>
                            </div>

                            <!-- Sell Orders -->
                            <div>
                                <h3 class="font-semibold text-rose-600 mb-3">
                                    Sell Orders
                                </h3>
                                <ul class="space-y-2">
                                    <li
                                        v-for="order in orderbook.sell"
                                        :key="order.id"
                                        class="flex justify-between rounded-lg bg-rose-50 px-3 py-2">
                                        <span>{{ order.amount }}</span>
                                        <span class="font-mono">@ {{ order.price }}</span>
                                    </li>
                                    <li v-if="!orderbook.sell.length" class="text-slate-400">
                                        No sell orders
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </section>

                    <!-- PAST ORDERS CARD -->
                    <section class="bg-white rounded-2xl shadow-sm border border-slate-200">
                        <div class="px-6 py-4 border-b border-slate-200">
                            <h2 class="text-lg font-semibold text-slate-800 text-center">
                                All Past Orders
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
                                    <td class="px-6 py-3 font-medium">{{ order.symbol }}</td>
                                    <td class="px-6 py-3 capitalize">{{ order.side }}</td>
                                    <td class="px-6 py-3">{{ order.price }}</td>
                                    <td class="px-6 py-3">{{ order.amount }}</td>
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
                                    <td colspan="5" class="px-6 py-6 text-center text-slate-400">
                                        No orders yet
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>

                <!-- BOTTOM ROW: WALLET CARD -->
                <section class="bg-white rounded-2xl shadow-sm border border-slate-200">
                    <div class="px-6 py-4 border-b border-slate-200 text-center">
                        <h2 class="text-lg font-semibold text-slate-800">
                            Wallet
                        </h2>
                    </div>

                    <div class="p-6 grid grid-cols-1 sm:grid-cols-3 gap-6">
                        <div class="rounded-xl bg-slate-50 p-4">
                            <p class="text-sm text-slate-500">USD Balance</p>
                            <p class="text-2xl font-bold text-slate-900 mt-1">
                                {{ profile.balance }}
                            </p>
                        </div>

                        <div
                            v-for="asset in profile.assets"
                            :key="asset.symbol"
                            class="rounded-xl bg-slate-50 p-4"
                        >
                            <p class="text-sm text-slate-500">
                                {{ asset.symbol }} Balance
                            </p>
                            <p class="text-2xl font-bold text-slate-900 mt-1">
                                {{ asset.amount }}
                            </p>
                        </div>
                    </div>
                </section>

            </div>
        </div>
    </div>
</template>
