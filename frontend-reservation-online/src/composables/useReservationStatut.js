import { ref } from "vue";
import { parseLocalDate } from "@/helpers/dateHelper";

const maintenant = ref(new Date());

if (typeof window !== "undefined" && !window.__horlogeReservationDemarree) {
  window.__horlogeReservationDemarree = true;
  setInterval(() => {
    maintenant.value = new Date();
  }, 30000); // rafraîchit toutes les 30 secondes
}

export function useReservationStatut() {
  function estEnCours(reservation) {
    if (!reservation || reservation.status !== "confirmee") return false;
    const debut = parseLocalDate(reservation.date_heure_debut);
    const fin = parseLocalDate(reservation.date_heure_fin);
    if (!debut || !fin) return false;
    return maintenant.value >= debut && maintenant.value <= fin;
  }

  function estTerminee(reservation) {
    if (!reservation || reservation.status !== "confirmee") return false;
    const fin = parseLocalDate(reservation.date_heure_fin);
    if (!fin) return false;
    return maintenant.value > fin;
  }

  function tempsRestant(reservation) {
    if (!reservation) return null;
    const fin = parseLocalDate(reservation.date_heure_fin);
    if (!fin) return null;
    const diffMs = fin - maintenant.value;
    if (diffMs <= 0) return null;

    const totalMinutes = Math.floor(diffMs / 60000);
    const heures = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;

    if (heures > 0) {
      return `${heures}h${minutes.toString().padStart(2, "0")} restantes`;
    }
    return `${minutes} min restantes`;
  }

  function estAVenir(reservation) {
    if (!reservation || reservation.status !== "confirmee") return false;
    const debut = parseLocalDate(reservation.date_heure_debut);
    if (!debut) return false;
    return maintenant.value < debut;
  }

  function tempsAvantDebut(reservation) {
    if (!reservation) return null;
    const debut = parseLocalDate(reservation.date_heure_debut);
    if (!debut) return null;
    const diffMs = debut - maintenant.value;
    if (diffMs <= 0) return null;

    const totalMinutes = Math.floor(diffMs / 60000);
    const jours = Math.floor(totalMinutes / (60 * 24));
    const heures = Math.floor((totalMinutes % (60 * 24)) / 60);
    const minutes = totalMinutes % 60;

    if (jours >= 1) {
      return `Dans ${jours} jour${jours > 1 ? "s" : ""}`;
    }
    if (heures >= 1) {
      return `Dans ${heures}h${minutes.toString().padStart(2, "0")}`;
    }
    return `Dans ${minutes} min`;
  }

  function estExpiree(reservation) {
    if (!reservation) return false;
    if (reservation.status === "rejetee") return true;
    const debut = parseLocalDate(reservation.date_heure_debut);
    if (!debut) return false;
    return maintenant.value >= debut;
  }

  function peutEtreConfirmee(reservation) {
    if (!reservation || reservation.status !== "en_attente") return false;
    const debut = parseLocalDate(reservation.date_heure_debut);
    if (!debut) return false;
    return maintenant.value < debut;
  }

  return { estEnCours, estTerminee, estAVenir, tempsRestant, tempsAvantDebut, estExpiree, peutEtreConfirmee, maintenant };
}
