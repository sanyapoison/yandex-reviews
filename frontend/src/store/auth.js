import { defineStore } from 'pinia'

export const useAuthStore = defineStore('auth', {
    state: () => ({
        isAuth: !!localStorage.getItem('token'),
        token: localStorage.getItem('token'),
    }),
    actions: {
        login(token) {
            this.token = token
            this.isAuth = true
            localStorage.setItem('token', token)
        },
        logout() {
            this.token = null
            this.isAuth = false
            localStorage.removeItem('token')
        },
    },
})
