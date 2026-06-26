import express from "express";
import { Server } from "socket.io";
import http from "http";

const app = express();
app.use(express.json());

// HTTP сервер для Socket.IO
const server = http.createServer(app);

// Socket.IO сервер (порт 6001 для WebSocket)
const io = new Server(server, {
    cors: {
        origin: "*",
    },
});

// WebSocket часть
io.on("connection", (socket) => {
    console.log("Клиент подключился:", socket.id);

    // прогресс от парсера
    socket.on("parser.progress", ({ socketId, ...data }) => {
        console.log("parser.progress: ", data);
        io.to(socketId).emit("parser.progress", { ...data, color: "info" });
    });

    // успех от парсера
    socket.on("parser.success", ({ socketId, ...data }) => {
        console.log("parser.success: ", data);
        io.to(socketId).emit("parser.success", { ...data, color: "success" });
    });

    // ошибка от парсера
    socket.on("parser.error", ({ socketId, ...data }) => {
        console.log("parser.cerror: ", data);
        io.to(socketId).emit("parser.error", { ...data, color: "error" });
    });
});

// HTTP API для Laravel (порт 6002)
app.post("/emit", (req, res) => {
    const { event, socketId, data } = req.body;

    console.log("Laravel.emit: ", data);

    io.to(socketId).emit(event, data);
    res.json({ status: "ok" });
});

// Запуск серверов
server.listen(6001, () => {
    console.log("✅ Socket.IO сервер запущен на порту 6001");
});

app.listen(6002, () => {
    console.log("✅ HTTP API для Laravel emit запущен на порту 6002");
});
