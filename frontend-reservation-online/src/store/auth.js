import { handleError } from "@/helpers/errorHelper";
import axiosClient from "@/plugins/axios";
import router from "@/router";
import { defineStore } from "pinia";

let savedUser = null;
try {
  const item = localStorage.getItem("user");
  if (item && item !== "undefined") {
    savedUser = JSON.parse(item);
  }
} catch (e) {
  console.error("Error parsing user from localStorage", e);
  localStorage.removeItem("user");
}

export const useAuthStore = defineStore("auth", {
  state: () => ({
    user: savedUser,
    token: localStorage.getItem("token") || null,
    loading: false,
    errors: {},
    errorMessage: null,
    successMessage: null,
  }),

  getters: {
    isAuthenticated: (state) => !!state.token && !!state.user,
    currentUser: (state) => state.user,
    userRole: (state) => state.user?.role || "user",
    isAdmin: (state) => state.user?.role === "admin",
    isResponsable: (state) => state.user?.role === "responsable",
    isUser: (state) => state.user?.role === "user",
  },

  actions: {
    clearErrors() {
      this.errors = {};
      this.errorMessage = null;
      this.successMessage = null;
    },

    redirectUserByRole(role) {
      if (role === "admin") {
        router.push({ name: "admin-users" });
      } else if (role === "responsable") {
        router.push({ name: "responsable-home" });
      } else {
        router.push({ name: "home" });
      }
    },

    async login(payload) {
      // Réinitialisation complète avant la requête
      this.errors = {};
      this.errorMessage = null;
      this.successMessage = null;
      this.clearErrors();
      this.loading = true;

      try {
        const response = await axiosClient.post("/login", payload);
        const { user, token } = response.data.data;

        this.token = token;
        this.user = user;
        this.successMessage = response.data.message || "Connexion réussie !";

        localStorage.setItem("token", token);
        localStorage.setItem("user", JSON.stringify(user));

        this.redirectUserByRole(user.role);
        return response.data;
      } catch (error) {
        const errorData = handleError(error);
        this.errorMessage = errorData.message;
        this.errors = errorData.errors || {};
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async register(payload) {
      this.clearErrors();
      this.loading = true;

      try {
        
        const response = await axiosClient.post("/register", payload);
        const data = response.data.data;

        this.successMessage = response.data.message || "Compte créé avec succès !";

        // Si l'API renvoie directement token et user
        if (data && data.token && data.user) {
          this.token = data.token;
          this.user = data.user;
          localStorage.setItem("token", data.token);
          localStorage.setItem("user", JSON.stringify(data.user));
          this.redirectUserByRole(data.user.role);
        } else {
          // Sinon redirection vers la page de login
          router.push({ name: "login" });
        }

        return response.data;
      } catch (error) {
        const errorData = handleError(error);
        this.errorMessage = errorData.message;
        this.errors = errorData.errors || {};
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async logout() {
      this.loading = true;

      try {
        if (this.token) {
          await axiosClient.post("/logout");
        }
      } catch (error) {
        console.error("Erreur lors de la déconnexion :", error);
      } finally {
        this.token = null;
        this.user = null;
        this.clearErrors();
        localStorage.removeItem("token");
        localStorage.removeItem("user");
        this.loading = false;
        router.push({ name: "login" });
      }
    },

    async fetchUser() {
      if (!this.token) return null;

      try {
        const response = await axiosClient.get("/user");
        const userData = response.data.data || response.data;
        this.user = userData;
        localStorage.setItem("user", JSON.stringify(userData));
        return userData;
      } catch (error) {
        console.error("Session expirée ou invalide :", error);
        this.logout();
        return null;
      }
    },
  },
});