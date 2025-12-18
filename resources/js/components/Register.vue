<script setup lang="ts">
import { ref, reactive } from 'vue'

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
  general: null
})

const loading = ref(false)

async function register(e) {
  e.preventDefault()
  loading.value = true
  // clear previous errors
  Object.keys(errors).forEach(k => errors[k] = null)

  try {
    // Get CSRF cookie for Laravel session auth / Sanctum
      await fetch('/sanctum/csrf-cookie', {
      method: 'GET',
      credentials: 'include'
    })


    const res = await fetch('/register', {
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
      // Laravel returns errors object: { errors: { field: [msg, ...] } }
      if (data.errors) {
        for (const key of Object.keys(data.errors)) {
          if (errors.hasOwnProperty(key)) errors[key] = data.errors[key][0]
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

    // success - redirect to intended page (adjust path as needed)
    window.location.href = '/home'
  } catch (err) {
    errors.general = 'Network error'
    loading.value = false
  }
}
</script>

<template>
  <form @submit="register" novalidate>
    <div>
      <label for="name">Name</label>
      <input id="name" v-model="form.name" type="text" autocomplete="name" required />
      <div v-if="errors.name" style="color:#c53030;font-size:0.9em">{{ errors.name }}</div>
    </div>

    <div>
      <label for="email">Email</label>
      <input id="email" v-model="form.email" type="email" autocomplete="email" required />
      <div v-if="errors.email" style="color:#c53030;font-size:0.9em">{{ errors.email }}</div>
    </div>

    <div>
      <label for="password">Password</label>
      <input id="password" v-model="form.password" type="password" autocomplete="new-password" required />
      <div v-if="errors.password" style="color:#c53030;font-size:0.9em">{{ errors.password }}</div>
    </div>

    <div>
      <label for="password_confirmation">Confirm Password</label>
      <input id="password_confirmation" v-model="form.password_confirmation" type="password" autocomplete="new-password" required />
    </div>

    <div v-if="errors.general" style="color:#c53030;margin-top:0.5rem">{{ errors.general }}</div>

    <button type="submit" :disabled="loading">
      <span v-if="loading">Registering...</span>
      <span v-else>Register</span>
    </button>
  </form>
</template>

<style scoped>
form { max-width:420px; margin:0 auto; display:flex; flex-direction:column; gap:0.75rem; }
label { display:block; font-weight:600; margin-bottom:0.25rem; }
input { width:100%; padding:0.5rem; border:1px solid #ddd; border-radius:4px; }
button { padding:0.6rem 1rem; border:none; background:#2563eb; color:white; border-radius:4px; cursor:pointer; }
button:disabled { opacity:0.6; cursor:not-allowed; }
</style>
