<script setup>
import { onMounted } from "vue";
import { RouterView } from "vue-router";
import SidebarResponsable from "@/components/responsable/SidebarResponsable.vue";
import ToastContainer from "@/components/ui/ToastContainer.vue";
import { useResponsableReservationsStore } from "@/store/responsableReservations";

const store = useResponsableReservationsStore();

onMounted(() => {
  store.fetchAll();
});
</script>

<template>
  <div class="flex min-h-screen bg-slate-50">
    <SidebarResponsable />

    <!-- Contenu principal — décalé à droite sur desktop, plein largeur sur mobile -->
    <main class="flex-1 min-w-0 lg:ml-64 px-4 py-6 sm:px-6 lg:px-8 pt-16 lg:pt-8 max-w-full">
      <RouterView />
    </main>

    <ToastContainer />
  </div>
</template>
