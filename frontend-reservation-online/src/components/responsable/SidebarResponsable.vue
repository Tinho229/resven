<script setup>
import { ref } from "vue";
import { RouterLink, useRoute } from "vue-router";
import { useAuthStore } from "@/store/auth";
import { useResponsableReservationsStore } from "@/store/responsableReservations";
import { LayoutDashboard, Clock, CheckCircle2, XCircle, LogOut, Building2, Menu, X } from "lucide-vue-next";

const authStore = useAuthStore();
const store = useResponsableReservationsStore();
const route = useRoute();

const sidebarOuverte = ref(false);

function toggleSidebar() {
  sidebarOuverte.value = !sidebarOuverte.value;
}
function fermerSidebar() {
  sidebarOuverte.value = false;
}

function handleLogout() {
  authStore.logout();
}

const liens = [
  { nom: "responsable-home", label: "Réservations", icon: LayoutDashboard },
  { nom: "responsable-en-attente", label: "En attente", icon: Clock, compteurCle: "en_attente" },
  { nom: "responsable-confirmees", label: "Confirmées", icon: CheckCircle2, compteurCle: "confirmee" },
  { nom: "responsable-rejetees", label: "Rejetées", icon: XCircle, compteurCle: "rejetee" },
];
</script>

<template>
  <!-- Bouton hamburger (mobile) -->
  <button
    type="button"
    class="fixed top-4 left-4 z-50 flex lg:hidden items-center justify-center w-9 h-9 rounded-lg bg-white border border-slate-200 shadow-sm text-slate-600 hover:bg-slate-50 transition"
    @click="toggleSidebar"
  >
    <X v-if="sidebarOuverte" class="w-5 h-5" />
    <Menu v-else class="w-5 h-5" />
  </button>

  <!-- Overlay sombre (mobile) -->
  <Transition name="overlay-fade">
    <div
      v-if="sidebarOuverte"
      class="fixed inset-0 z-30 bg-black/40 backdrop-blur-sm lg:hidden"
      @click="fermerSidebar"
    />
  </Transition>

  <!-- Sidebar -->
  <aside
    class="fixed top-0 left-0 z-40 h-full w-64 bg-white border-r border-slate-200 flex flex-col transition-transform duration-300 ease-in-out"
    :class="sidebarOuverte ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
  >
    <!-- Logo -->
    <div class="h-16 flex items-center gap-2.5 px-6 border-b border-slate-200 shrink-0">
      <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-blue-600">
        <Building2 class="w-4 h-4 text-white" />
      </div>
      <span class="font-bold text-slate-900 text-[15px]">RapidRéservation</span>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
      <RouterLink
        v-for="lien in liens"
        :key="lien.nom"
        :to="{ name: lien.nom }"
        class="flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium transition"
        :class="route.name === lien.nom
          ? 'text-blue-600 bg-blue-50 font-semibold'
          : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
        @click="fermerSidebar"
      >
        <span class="flex items-center gap-3">
          <component :is="lien.icon" class="w-4 h-4 shrink-0" />
          {{ lien.label }}
        </span>
        <span
          v-if="lien.compteurCle && store.compteurs[lien.compteurCle] > 0"
          class="text-[11px] font-semibold px-1.5 py-0.5 rounded-full min-w-[20px] text-center"
          :class="lien.nom === 'responsable-en-attente'
            ? 'bg-amber-100 text-amber-700'
            : 'bg-slate-100 text-slate-600'"
        >
          {{ store.compteurs[lien.compteurCle] }}
        </span>
      </RouterLink>
    </nav>

    <!-- Profil + Déconnexion -->
    <div class="border-t border-slate-200 p-4 shrink-0">
      <div class="flex items-center gap-3 mb-3">
        <div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold text-sm shrink-0">
          {{ authStore.currentUser?.nom?.charAt(0)?.toUpperCase() || "R" }}
        </div>
        <div class="flex flex-col min-w-0">
          <span class="text-sm font-semibold text-slate-900 truncate">
            {{ authStore.currentUser?.nom || "Responsable" }}
          </span>
          <span class="text-xs text-slate-500 capitalize">{{ authStore.userRole }}</span>
        </div>
      </div>

      <button
        type="button"
        class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-red-600 border border-red-200 hover:bg-red-50 transition"
        @click="handleLogout"
      >
        <LogOut class="w-4 h-4" />
        Déconnexion
      </button>
    </div>
  </aside>
</template>

<style scoped>
.overlay-fade-enter-active,
.overlay-fade-leave-active {
  transition: opacity 0.25s ease;
}
.overlay-fade-enter-from,
.overlay-fade-leave-to {
  opacity: 0;
}
</style>
