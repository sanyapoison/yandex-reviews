<template>
  <v-container class="h-100">
    <v-card class="mt-15">
      <v-card-title>Организации</v-card-title>
      <v-card-text>
        <!-- Пустой список -->
        <div v-if="organizations.length === 0" class="text-center text-gray-500 py-10">
          Организаций пока нет
        </div>

        <!-- Таблица -->
        <v-data-table
            v-else
            :items="organizations"
            :headers="headers"
            :page="page"
            :items-per-page="itemsPerPage"
            :server-items-length="total"
        >
          <!-- Колонка с отзывами -->
          <template #item.reviews_count="{ item }">
            <router-link :to="`/organization/${item.id}/reviews`" v-if="item.reviews_count">
              {{ item.reviews_count }}
            </router-link>
            <span v-else>
              {{ item.reviews_count }}
            </span>
          </template>

          <!-- Нижняя панель -->
          <template #bottom>
            <div class="d-flex flex-wrap justify-space-between align-center pa-4">
              <!-- Информация о количестве -->
              <div class="mb-2">
                Показано {{ (page - 1) * itemsPerPage + 1 }}
                –
                {{ Math.min(page * itemsPerPage, total) }}
                из {{ total }} организаций
              </div>

              <!-- Пагинация и селект -->
              <div class="d-flex flex-wrap align-center">
                <div class="mr-2 mb-2">Записей на странице:</div>

                <v-select
                    v-model="itemsPerPage"
                    :items="[1, 2, 5, 10, 20, 50]"
                    density="compact"
                    hide-details
                    style="max-width: 100px"
                    class="mb-2"
                />

                <v-pagination
                    v-model="page"
                    :length="Math.ceil(total / itemsPerPage)"
                    class="mb-2"
                />
              </div>
            </div>
          </template>

          <!-- Колонка действий -->
          <template #item.actions="{ item }">
            <v-btn color="error" size="small" @click="deleteOrganization(item.id)">
              Удалить
            </v-btn>
          </template>
        </v-data-table>
      </v-card-text>
    </v-card>
  </v-container>
</template>

<script setup>
import api from '../api.js'
import {ref, watch, onMounted} from 'vue'

const organizations = ref([])
const total = ref(0)
const page = ref(1)
const itemsPerPage = ref(10)

const headers = [
  {title: 'Название', key: 'name'},
  {title: 'Рейтинг', key: 'rating'},
  {title: 'Ссылка', key: 'yandex_url'},
  {title: 'Отзывы', key: 'reviews_count'},
  {title: 'Действия', key: 'actions'},
]

// следим за изменением страницы
watch(page, () => {
  fetchOrganizations()
})

// следим за изменением количества записей
watch(itemsPerPage, () => {
  page.value = 1
  fetchOrganizations()
})

async function fetchOrganizations() {
  try {
    const res = await api.get('/organizations', {
      params: {
        page: page.value,
        per_page: itemsPerPage.value,
      },
    })
    organizations.value = res.data.data
    total.value = res.data.total
  } catch (err) {
    console.error('Ошибка запроса:', err)
  }
}

async function deleteOrganization(id) {
  if (!confirm("Удалить организацию и все её отзывы?")) return
  try {
    await api.delete(`/organizations/${id}`)
    await fetchOrganizations()
  } catch (err) {
    console.error("Ошибка удаления:", err)
  }
}

onMounted(fetchOrganizations)
</script>
