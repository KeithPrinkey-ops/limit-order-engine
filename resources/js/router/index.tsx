import {createRouter, createWebHistory, RouteRecordRaw} from 'vue-router'

// @ts-ignore
import Login from '../components/Login.vue'
import OrderForm from '../components/OrderForm.vue'
import OrderOverview from '../components/OrderOverview.vue'
import Home from "../components/Home.vue";
import Register from "../components/Register.vue";

const routes: RouteRecordRaw[] = [
    {
        path: '/',
        component: Home
    },
    {
        path: '/login',
        name: 'login',
        component: Login,
    },
    {
        path: '/orders/new',
        name: 'order.create',
        component: OrderForm,
    },
    {
        path: '/orders',
        name: 'orders.index',
        component: OrderOverview,
    },
    {
        path: '/register',
        name: 'register',
        component: Register,
    }
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

export default router
