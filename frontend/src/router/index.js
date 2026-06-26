import { createRouter, createWebHistory } from 'vue-router'
import Login from '../views/Login.vue'
import OrganizationForm from '../views/OrganizationForm.vue'
import OrganizationList from '../views/OrganizationList.vue'
import OrganizationReviews from '../views/OrganizationReviews.vue'

const routes = [
    { path: '/login', component: Login },
    { path: '/', component: OrganizationList, meta: { requiresAuth: true } },
    { path: '/add', component: OrganizationForm, meta: { requiresAuth: true } },
    { path: '/organization/:id/reviews', component: OrganizationReviews, meta: { requiresAuth: true } },
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

router.beforeEach((to, from, next) => {
    const token = localStorage.getItem('token')
    if (to.meta.requiresAuth && !token) {
        next('/login')
    } else {
        next()
    }
})

export default router
