<script setup lang="ts">
</script>

<template>
    <div class="flex flex-col items-center justify-center py-24">
        <h1 class="text-3xl font-semibold mb-4">
            Welcome to the Exchange
        </h1>

        <p class="text-gray-600 mb-10 text-center max-w-xl">
            This application demonstrates a real-time limit order engine using Laravel,
            Vue 3 (Composition API), Tailwind CSS, and Pusher broadcasting.
        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="p-6 rounded-xl shadow bg-white">
                <h2 class="text-lg font-semibold mb-2">Order Entry</h2>
                <p class="text-sm text-gray-600 mb-4">
                    Place buy or sell limit orders for supported assets.
                </p>
                <a href="/orders/new" class="text-blue-600 font-medium">
                    Go to Order Form →
                </a>
            </div>

            <div class="p-6 rounded-xl shadow bg-white">
                <h2 class="text-lg font-semibold mb-2">Wallet & Orders</h2>
                <p class="text-sm text-gray-600 mb-4">
                    View balances, orders, and real-time updates.
                </p>
                <a href="/orders" class="text-blue-600 font-medium">
                    View Orders & Wallet →
                </a>
            </div>
        </div>
    </div>
</template>
