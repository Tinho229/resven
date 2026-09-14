<script setup>
import ReservationsTable from "@/components/responsable/ReservationsTable.vue";
import { useResponsableReservationsStore } from "@/store/responsableReservations";
import { useToast } from "@/composables/useToast";

const store = useResponsableReservationsStore();
const toast = useToast();

async function gererConfirmation(id) {
  try {
    await store.confirmer(id);
    toast.success("Réservation confirmée avec succès.");
  } catch (error) {
    const msg = error?.response?.data?.message || store.errorMessage || "Impossible de confirmer cette réservation.";
    toast.error(msg);
    await store.fetchAll();
  }
}

async function gererRejet(id) {
  try {
    await store.rejeter(id);
    toast.success("Réservation rejetée.");
  } catch {
    toast.error("Impossible de rejeter cette réservation.");
  }
}
</script>

<template>
  <div>
    <div class="mb-6">
      <h1 class="text-xl font-semibold text-slate-900">Réservations en attente</h1>
      <p class="mt-1 text-sm text-slate-500">Confirmez ou rejetez les demandes en attente.</p>
    </div>

    <div v-if="store.errorMessage"
      class="mb-6 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">
      {{ store.errorMessage }}
    </div>

    <div v-if="store.loading" class="py-20 text-center text-slate-400 text-sm">
      Chargement…
    </div>

    <ReservationsTable v-else :reservations="store.parStatut('en_attente')" :action-loading-id="store.actionLoadingId"
      :show-actions="true" @confirmer="gererConfirmation" @rejeter="gererRejet" />
  </div>
</template>
