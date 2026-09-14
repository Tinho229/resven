<script setup>
import ReservationsTable from "@/components/responsable/ReservationsTable.vue";
import { useResponsableReservationsStore } from "@/store/responsableReservations";
import { useToast } from "@/composables/useToast";

const store = useResponsableReservationsStore();
const toast = useToast();

async function gererAnnulation(id) {
  try {
    await store.annuler(id);
    toast.success("Réservation annulée.");
  } catch {
    toast.error("Impossible d'annuler cette réservation.");
  }
}

async function gererCloture(id) {
  try {
    await store.terminer(id);
    toast.success("Réservation marquée comme terminée.");
  } catch {
    toast.error("Impossible de clôturer cette réservation.");
  }
}
</script>

<template>
  <div>
    <div class="mb-6">
      <h1 class="text-xl font-semibold text-slate-900">Réservations confirmées</h1>
      <p class="mt-1 text-sm text-slate-500">Historique des réservations validées en cours ou à venir.</p>
    </div>

    <div v-if="store.loading" class="py-20 text-center text-slate-400 text-sm">
      Chargement…
    </div>

    <ReservationsTable v-else :reservations="store.parStatut('confirmee')" :action-loading-id="store.actionLoadingId"
      :show-actions="true" @annuler="gererAnnulation" @terminer="gererCloture" />
  </div>
</template>
