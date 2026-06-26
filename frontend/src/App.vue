<template>
    <v-app>
        <v-app-bar app color="primary" dark v-if="auth.isAuth">
            <v-app-bar-nav-icon v-if="auth.isAuth" @click="drawer = !drawer" />
            <v-toolbar-title>Yandex Reviews</v-toolbar-title>
            <v-spacer />
            <v-btn v-if="auth.isAuth" @click="logout" icon="mdi-logout" text="Выйти"/>
        </v-app-bar>

        <v-navigation-drawer v-model="drawer" app v-if="auth.isAuth" class="w-50">
            <v-list>
                <v-list-item to="/" title="Организации"/>
                <v-list-item to="/add" title="Добавить организацию"/>
            </v-list>
        </v-navigation-drawer>

        <span class="main h-100">
            <router-view />
        </span>
    </v-app>
</template>

<script setup>
import { ref, computed } from 'vue'
import {useAuthStore} from "./store/auth.js";
const auth = useAuthStore()
const drawer = ref(false)

function logout() {
    localStorage.removeItem('token')
    window.location.href = '/login'
}
</script>
