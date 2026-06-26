<template>
  <v-container>
    <v-card class="mt-15">
      <v-card-title>Отзывы организации</v-card-title>
      <v-card-text>
        <!-- Пустой список -->
        <div v-if="reviews.length === 0" class="text-center text-gray-500 py-10">
          Отзывов пока нет
        </div>

        <!-- Список отзывов -->
        <v-list v-else>
          <v-list-item
              v-for="review in reviews"
              :key="review.id"
              class="flex-wrap"
          >
            <v-list-item-content>
              <v-list-item-title>
                {{ review.author }} — ★ {{ review.rating }}
              </v-list-item-title>
              <v-list-item-subtitle>
                {{ review.date }}
              </v-list-item-subtitle>
              <v-list-item-subtitle>
                {{ review.text }}
              </v-list-item-subtitle>
            </v-list-item-content>
          </v-list-item>
        </v-list>
      </v-card-text>

      <!-- Пагинация -->
      <v-card-actions class="d-flex justify-center flex-wrap">
        <v-pagination
            v-model="page"
            :length="lastPage"
            :total-visible="5"
            class="mb-2"
        />
      </v-card-actions>
    </v-card>
  </v-container>
</template>

<script setup>
import api from '../api.js'
import { ref, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const reviews = ref([])
const page = ref(1)
const lastPage = ref(1)

// следим за изменением страницы
watch(page, () => {
  fetchReviews()
})

async function fetchReviews() {
  try {
    const res = await api.get(`/organization/${route.params.id}/reviews`, {
      params: { page: page.value }
    })
    reviews.value = res.data.data
    lastPage.value = res.data.last_page
  } catch (err) {
    console.error('Ошибка загрузки отзывов:', err)
  }
}

onMounted(fetchReviews)
</script>
