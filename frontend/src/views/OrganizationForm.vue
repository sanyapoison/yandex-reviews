<template>
  <v-container>
    <v-card class="mt-15">
      <v-card-title>Добавить организацию</v-card-title>
      <v-card-text>
        <v-text-field v-model="url" label="Yandex URL" />
      </v-card-text>
      <v-card-actions>
        <v-btn color="primary" @click="startParsing">Запустить парсер</v-btn>
      </v-card-actions>
    </v-card>

    <!-- Лог -->
    <v-card class="mt-4">
      <v-card-title>Лог парсинга</v-card-title>
      <v-card-text>
        <v-list>
          <v-list-item
              v-for="(log, index) in logs.slice(-100)"
              :key="index"
          >
            <v-list-item-content>
              <v-list-item-title :class="log.color">
                [{{ log.timestamp }}] {{ log.message }}
              </v-list-item-title>
            </v-list-item-content>
          </v-list-item>

        </v-list>
      </v-card-text>
    </v-card>

    <v-snackbar v-model="snackbar" timeout="3000" color="green">
      {{ snackbarMessage }}
    </v-snackbar>
  </v-container>
</template>

<script setup>

import { ref, onMounted } from "vue";
import io from "socket.io-client";
import api from "../api.js";

const url = ref("");
const logs = ref([]);

const snackbar = ref(false);
const snackbarMessage = ref("");

let socket;

onMounted(() => {
  socket = io("http://127.0.0.1:6001");

  socket.on("connect", () => {
    console.log("Socket connected:", socket.id);
  });

  socket.on("parser.progress", (data) => addLog(data.step, "text-blue"));
  socket.on("parser.success", (data) => addLog(`${data.message}`, "text-green"));
  socket.on("parser.error", (err) => addLog(`Ошибка: ${err.message}`, "text-red"));

  socket.on("laravel.start", (data) => addLog(data.message, "text-blue"));
  socket.on("laravel.info", (data) => addLog(data.message, "text-yellow"));

  socket.on("laravel.finish", (data) => {
    snackbarMessage.value = data.message || "Данные успешно сохранены!";
    snackbar.value = true;
  });

  socket.on("laravel.error", (data) => addLog(`Ошибка Laravel: ${data.message}`, "text-red"));
});


function addLog(message, color = "info") {
  const now = new Date();
  const timestamp = now.toLocaleString("ru-RU", {
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  });

  logs.value.push({ message, color, timestamp });

  if (logs.value.length > 1000) {
    logs.value.splice(0, logs.value.length - 1000); // ограничиваем общий буфер
  }
}


async function startParsing() {
  if (!socket || !socket.id) {
    addLog("Socket ещё не подключен", "red--text");
    return;
  }

  logs.value = [];

  await api.post("/organization/start", {
    yandex_url: url.value,
    socket_id: socket.id,
  });
}

</script>
