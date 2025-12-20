<script setup lang="ts">
import {ref} from 'vue'
import axios from 'axios'

import {useToast} from 'vue-toastification'

const toast = useToast()

toast.success('Order placed successfully')
toast.error('Insufficient balance')

const symbol = ref('BTC')
const side = ref('buy')
const price = ref('')
const amount = ref('')
const loading = ref(false)
const error = ref(null)
const success = ref(null)

async function submitOrder() {
    error.value = null
    success.value = null
    loading.value = true

    try {
        await axios.post('/api/orders', {
            symbol: symbol.value,
            side: side.value,
            price: price.value,
            amount: amount.value,
        })

        success.value = 'Order placed successfully'
        price.value = ''
        amount.value = ''
    } catch (e) {
        error.value = e.response?.data?.message || 'Failed to place order'
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <div class="min-h-screen flex items-center justify-center px-4 bg-gray-50">
        <div class="w-full max-w-3xl lg:max-w-4xl bg-white rounded-2xl shadow-lg p-12">
            <h1 class="text-3xl font-semibold mb-6">Place Limit Order</h1>

            <!-- Alerts -->
            <div v-if="error" class="mb-4 rounded-md bg-red-50 border border-red-200 p-3 text-sm text-red-800">
                {{ error }}
            </div>
            <div v-if="success" class="mb-4 rounded-md bg-green-50 border border-green-200 p-3 text-sm text-green-800">
                {{ success }}
            </div>

            <form @submit.prevent="submitOrder" class="space-y-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <!-- Symbol -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Symbol</label>
                        <select
                            v-model="symbol"
                            required
                            class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-3 text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="BTC">BTC</option>
                            <option value="ETH">ETH</option>
                        </select>
                    </div>

                    <!-- Side -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Side</label>
                        <select
                            v-model="side"
                            required
                            class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-3 text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="buy">Buy</option>
                            <option value="sell">Sell</option>
                        </select>
                    </div>

                    <!-- Price -->
                    <div class="sm:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Price</label>
                        <input
                            v-model="price"
                            type="number"
                            step="0.00000001"
                            required
                            placeholder="Enter price"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-3 text-gray-900 text-base focus:outline-none focus:ring-2 focus:ring-blue-500"/>
                    </div>

                    <!-- Amount -->
                    <div class="sm:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                        <input
                            v-model="amount"
                            type="number"
                            step="0.00000001"
                            required
                            placeholder="Enter amount"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-3 text-gray-900 text-base focus:outline-none focus:ring-2 focus:ring-blue-500"/>
                    </div>
                </div>

                <!-- Submit -->
                <div>
                    <button
                        type="submit"
                        :disabled="loading"
                        :aria-busy="loading"
                        class="w-full flex items-center justify-center gap-2 bg-blue-600 text-white py-4 rounded-xl text-lg font-medium border-2 border-white hover:bg-blue-700 hover:border-blue-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:border-gray-300">
                        <span v-if="loading">Placing...</span>
                        <span v-else class="flex">Place Order</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
