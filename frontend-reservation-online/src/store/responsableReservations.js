import { defineStore } from "pinia";
import reservationService from "@/services/reservationService";
import { handleError } from "@/helpers/errorHelper";
import { parseLocalDate } from "@/helpers/dateHelper";

export const useResponsableReservationsStore = defineStore("responsableReservations", {
  state: () => ({
    reservations: [],
    loading: false,
    actionLoadingId: null, // évite le double-clic sur une ligne précise
    errorMessage: null,
    creationLoading: false,
    creationErrors: {},
  }),

  getters: {
    parStatut: (state) => (statut) => {
      if (statut === "toutes") return state.reservations;
      const now = new Date();

      if (statut === "terminee") {
        return state.reservations.filter((r) => {
          if (r.status === "terminee") return true;
          if (r.status === "confirmee") {
            const fin = parseLocalDate(r.date_heure_fin);
            return fin && now > fin;
          }
          return false;
        });
      }

      if (statut === "confirmee") {
        return state.reservations.filter((r) => {
          if (r.status !== "confirmee") return false;
          const fin = parseLocalDate(r.date_heure_fin);
          return !fin || now <= fin;
        });
      }

      if (statut === "en_attente") {
        return state.reservations.filter((r) => {
          if (r.status !== "en_attente") return false;
          const debut = parseLocalDate(r.date_heure_debut);
          return !debut || now < debut;
        });
      }

      if (statut === "rejetee") {
        return state.reservations.filter((r) => {
          if (r.status === "rejetee") return true;
          if (r.status === "en_attente") {
            const debut = parseLocalDate(r.date_heure_debut);
            return debut && now >= debut;
          }
          return false;
        });
      }

      return state.reservations.filter((r) => r.status === statut);
    },

    compteurs: (state) => {
      const now = new Date();
      const isFinished = (r) => {
        if (r.status === "terminee") return true;
        if (r.status === "confirmee") {
          const fin = parseLocalDate(r.date_heure_fin);
          return fin && now > fin;
        }
        return false;
      };

      const isPending = (r) => {
        if (r.status !== "en_attente") return false;
        const debut = parseLocalDate(r.date_heure_debut);
        return !debut || now < debut;
      };

      const isConfirmedActive = (r) => {
        return r.status === "confirmee" && !isFinished(r);
      };

      return {
        en_attente: state.reservations.filter((r) => isPending(r)).length,
        confirmee: state.reservations.filter((r) => isConfirmedActive(r)).length,
        terminee: state.reservations.filter((r) => isFinished(r)).length,
        rejetee: state.reservations.filter((r) => r.status === "rejetee" || (r.status === "en_attente" && !isPending(r))).length,
        total: state.reservations.length,
      };
    },
  },

  actions: {
    async fetchAll() {
      this.loading = true;
      this.errorMessage = null;
      try {
        const response = await reservationService.fetchAll();
        this.reservations = response.data.data;
      } catch (error) {
        this.errorMessage = handleError(error).message;
      } finally {
        this.loading = false;
      }
    },

    async confirmer(id) {
      this.actionLoadingId = id;
      try {
        const response = await reservationService.confirmer(id);
        this.remplacerReservation(response.data.data);
      } catch (error) {
        this.errorMessage = handleError(error).message;
        throw error;
      } finally {
        this.actionLoadingId = null;
      }
    },

    async rejeter(id) {
      this.actionLoadingId = id;
      try {
        const response = await reservationService.rejeter(id);
        this.remplacerReservation(response.data.data);
      } catch (error) {
        this.errorMessage = handleError(error).message;
        throw error;
      } finally {
        this.actionLoadingId = null;
      }
    },

    async creerManuelle(payload) {
      this.creationLoading = true;
      this.creationErrors = {};
      try {
        const response = await reservationService.creerManuelle(payload);
        this.reservations.unshift(response.data.data);
        return true;
      } catch (error) {
        const errorData = handleError(error);
        this.creationErrors = errorData.errors;
        this.errorMessage = errorData.message;
        return false;
      } finally {
        this.creationLoading = false;
      }
    },

    remplacerReservation(updated) {
      const index = this.reservations.findIndex((r) => r.id === updated.id);
      if (index !== -1) this.reservations[index] = updated;
    },

    async annuler(id) {
      this.actionLoadingId = id;
      try {
        const response = await reservationService.annuler(id);
        this.remplacerReservation(response.data.data);
      } catch (error) {
        this.errorMessage = handleError(error).message;
        throw error;
      } finally {
        this.actionLoadingId = null;
      }
    },

    async terminer(id) {
      this.actionLoadingId = id;
      try {
        const response = await reservationService.terminer(id);
        this.remplacerReservation(response.data.data);
      } catch (error) {
        this.errorMessage = handleError(error).message;
        throw error;
      } finally {
        this.actionLoadingId = null;
      }
    },
  },
});
