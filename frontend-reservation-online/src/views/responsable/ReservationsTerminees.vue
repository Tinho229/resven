<script setup>
import ReservationsTable from "@/components/responsable/ReservationsTable.vue";
import { useResponsableReservationsStore } from "@/store/responsableReservations";

const store = useResponsableReservationsStore();
</script>

<template>
  <div>
    <div class="mb-6">
      <h1 class="text-xl font-semibold text-slate-900">Réservations terminées</h1>
      <p class="mt-1 text-sm text-slate-500">Historique des événements terminés et clôturés.</p>
    </div>

    <div v-if="store.loading" class="py-20 text-center text-slate-400 text-sm">
      Chargement…
    </div>

    <ReservationsTable
      v-else
      :reservations="store.parStatut('terminee')"
      :action-loading-id="store.actionLoadingId"
      :show-actions="false"
    />
  </div>
</template>
