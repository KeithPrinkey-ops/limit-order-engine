import './bootstrap'
import { createApp } from 'vue'
import App from './App.vue'
import '../css/app.css'
import router from './router';
import 'vue-toastification/dist/index.css'

createApp(App).use(router).mount('#app')

