<template>
    <v-container class="align-content-center justify-items-center h-100 w-50 ">
        <v-card class="align-content-center w-50">
            <v-card-title>Авторизация</v-card-title>
            <v-card-text class="mt-10">
                <v-text-field v-model="email" label="Email" />
                <v-text-field
                    v-model="password"
                    :type="showPassword ? 'text' : 'password'"
                    label="Пароль"
                    :append-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
                    @click:append="showPassword = !showPassword"
                />
            </v-card-text>
            <v-card-actions>
                <v-btn color="primary" @click="login">Войти</v-btn>
            </v-card-actions>
        </v-card>

        <v-snackbar v-model="snackbar" :timeout="3000" location="top center" color="red">
            {{ snackbarMessage }}
        </v-snackbar>
    </v-container>
</template>

<script setup>
import api from '../api.js'
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../store/auth'
const auth = useAuthStore()

const email = ref('')
const password = ref('')
const snackbar = ref(false)
const snackbarMessage = ref('')
const showPassword = ref(false)
const router = useRouter()

async function login() {
    try {
        const res = await api.post('/login', { email: email.value, password: password.value })
        auth.login(res.data.token)

        // переход при успехе
        router.push('/')
    } catch (err) {
        // обработка ошибок
        if (err.response?.data?.message) {
            snackbarMessage.value = err.response.data.message
        } else {
            snackbarMessage.value = 'Ошибка авторизации'
        }
        snackbar.value = true
    }
}
</script>
