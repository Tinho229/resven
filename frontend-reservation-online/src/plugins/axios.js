import router from "@/router";
import axios from "axios";

const axiosClient = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || "http://127.0.0.1:8000/api",
  headers: {
    "Accept": "application/json",
    "Content-Type": "application/json",
  },
  withCredentials: true,
});

// Intercepteur de requête : Ajout automatique du Bearer Token
axiosClient.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem("token");
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Intercepteur de réponse : Gestion des erreurs globales (401, etc.)
axiosClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response && error.response.status === 401) {
      localStorage.removeItem("token");
      localStorage.removeItem("user");

      // Rediriger vers la page login si on n'y est pas déjà
      if (router.currentRoute.value.name !== "login") {
        router.push({ name: "login" });
      }
    }
    return Promise.reject(error);
  }
);

export default axiosClient;