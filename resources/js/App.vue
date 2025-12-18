<script setup>
import { ref } from 'vue'

const isAuthenticated = ref(false) // placeholder until auth wiring
</script>

<template>
    <Header />
    <router-view />
</template>
