<script setup>
import { ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useAuthStore } from '@/store/auth'
import { Menu, X } from 'lucide-vue-next'
import UserProfileModal from '@/components/UserProfileModal.vue'

const authStore = useAuthStore()
const route = useRoute()

const isMenuOpen = ref(false)
const isProfileModalOpen = ref(false)

const openProfileModal = () => {
  isProfileModalOpen.value = true
}

const openProfileModalFromMobile = () => {
  isMenuOpen.value = false
  isProfileModalOpen.value = true
}

const handleLogout = () => {
  authStore.logout()
  isMenuOpen.value = false
}

const closeMenu = () => {
  isMenuOpen.value = false
}

// Ferme le menu à chaque changement de route
watch(() => route.path, () => {
  isMenuOpen.value = false
})
</script>

<template>
  <div class="fixed inset-x-0 top-4 z-50 flex justify-center px-4">
    <nav class="w-full max-w-[1110px] rounded-full border border-white/40 bg-white/60 shadow-[0_8px_32px_rgba(15,23,42,0.08)] backdrop-blur-xl">
      <div class="mx-auto flex h-[64px] items-center justify-between px-5 sm:px-6">

        <!-- Logo -->
        <RouterLink
          to="/"
          class="font-bricolage text-[22px] sm:text-[24px] font-black tracking-[-1.5px] text-black flex-shrink-0"
          @click="closeMenu"
        >
        Resven
        </RouterLink>

        <!-- Navigation desktop -->
        <div class="hidden md:flex items-center gap-1">
          <!-- Utilisateur connecté -->
          <template v-if="authStore.isAuthenticated && authStore.isUser">
            <RouterLink to="/salles"
              class="flex cursor-pointer items-center gap-1 rounded-full px-3 py-2 text-[13px] font-semibold text-black transition hover:bg-white/70"
              :class="{ 'bg-white/70': $route.path.startsWith('/salles') }">Salles</RouterLink>
            <RouterLink to="/equipements"
              class="flex cursor-pointer items-center gap-1 rounded-full px-3 py-2 text-[13px] font-semibold text-black transition hover:bg-white/70"
              :class="{ 'bg-white/70': $route.path.startsWith('/equipements') }">Équipements</RouterLink>
            <RouterLink to="/reservations"
              class="flex cursor-pointer items-center gap-1 rounded-full px-3 py-2 text-[13px] font-semibold text-black transition hover:bg-white/70"
              :class="{ 'bg-white/90': $route.path.startsWith('/reservations') }">Réservations</RouterLink>
            <button
              type="button"
              @click="openProfileModal"
              class="flex cursor-pointer items-center gap-1 rounded-full px-3 py-2 text-[13px] font-semibold text-black transition hover:bg-white/70"
              :class="{ 'bg-white/90': isProfileModalOpen }"
            >
              Mon profil
            </button>
          </template>

          <!-- Admin / Responsable -->
          <template v-else-if="authStore.isAuthenticated && (authStore.isAdmin || authStore.isResponsable)">
            <RouterLink to="/"
              class="flex cursor-pointer items-center gap-1 rounded-full px-3 py-2 text-[13px] font-semibold text-black transition hover:bg-white/70">Accueil</RouterLink>
            <RouterLink to="/salles"
              class="flex cursor-pointer items-center gap-1 rounded-full px-3 py-2 text-[13px] font-semibold text-black transition hover:bg-white/70">Salles</RouterLink>
            <RouterLink to="/equipements"
              class="flex cursor-pointer items-center gap-1 rounded-full px-3 py-2 text-[13px] font-semibold text-black transition hover:bg-white/70">Équipements</RouterLink>
            <button
              type="button"
              @click="openProfileModal"
              class="flex cursor-pointer items-center gap-1 rounded-full px-3 py-2 text-[13px] font-semibold text-black transition hover:bg-white/70"
              :class="{ 'bg-white/90': isProfileModalOpen }"
            >
              Mon profil
            </button>
          </template>

          <!-- Visiteur -->
          <template v-else>
            <RouterLink to="/"
              class="flex cursor-pointer items-center gap-1 rounded-full px-3 py-2 text-[13px] font-semibold text-black transition hover:bg-white/70">Accueil</RouterLink>
            <RouterLink to="/salles"
              class="flex cursor-pointer items-center gap-1 rounded-full px-3 py-2 text-[13px] font-semibold text-black transition hover:bg-white/70">Salles</RouterLink>
            <RouterLink to="/equipements"
              class="flex cursor-pointer items-center gap-1 rounded-full px-3 py-2 text-[13px] font-semibold text-black transition hover:bg-white/70">Équipements</RouterLink>
          </template>
        </div>

        <!-- Actions Auth desktop -->
        <div class="hidden md:flex items-center gap-3">
          <template v-if="authStore.isAuthenticated">
            <div class="flex items-center gap-3">
              <button
                type="button"
                @click="openProfileModal"
                class="group flex items-center gap-2.5 text-right transition hover:opacity-85 cursor-pointer"
                title="Consulter mon profil"
              >
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-tr from-amber-400 to-amber-200 text-[12px] font-black text-[#111] shadow-sm border border-black/10 transition group-hover:scale-105">
                  {{ (authStore.currentUser?.nom || 'U').charAt(0).toUpperCase() }}
                </div>
                <div class="flex flex-col text-left">
                  <span class="text-[13px] font-bold text-black leading-tight group-hover:underline decoration-black/30 underline-offset-2">
                    {{ authStore.currentUser?.nom || 'Utilisateur' }}
                  </span>
                </div>
              </button>
              <button @click="handleLogout"
                class="cursor-pointer rounded-full border border-gray-200/70 bg-white/50 px-4 py-2 text-[13px] font-bold text-red-600 transition hover:bg-red-50 hover:border-red-200">
                Déconnexion
              </button>
            </div>
          </template>
          <template v-else>
            <RouterLink to="/auth/login"
              class="rounded-full bg-white/70 px-4 py-2 text-[13px] font-bold text-black transition hover:bg-white">
              Se connecter
            </RouterLink>
            <RouterLink to="/auth/register"
              class="rounded-full bg-[#111111] px-4 py-2 text-[13px] font-bold text-white transition hover:bg-black">
              S'inscrire
            </RouterLink>
          </template>
        </div>

        <!-- Burger button mobile -->
        <button
          @click="isMenuOpen = !isMenuOpen"
          class="md:hidden flex h-10 w-10 items-center justify-center rounded-full bg-white/60 text-black border border-white/50 transition hover:bg-white"
          :aria-label="isMenuOpen ? 'Fermer le menu' : 'Ouvrir le menu'"
        >
          <X v-if="isMenuOpen" :size="20" />
          <Menu v-else :size="20" />
        </button>

      </div>
    </nav>

    <!-- Drawer mobile -->
    <Transition name="drawer">
      <div
        v-if="isMenuOpen"
        class="md:hidden absolute top-[72px] left-0 right-0 mx-4 rounded-[20px] border border-white/40 bg-white/90 shadow-[0_16px_48px_rgba(15,23,42,0.12)] backdrop-blur-xl overflow-hidden"
      >
        <div class="p-4 flex flex-col gap-1">

          <!-- Utilisateur connecté -->
          <template v-if="authStore.isAuthenticated && authStore.isUser">
            <!-- Profil en-tête cliquable -->
            <button
              type="button"
              @click="openProfileModalFromMobile"
              class="flex w-full items-center gap-3 px-3 py-2.5 mb-1 rounded-2xl bg-white/70 border border-gray-200/80 text-left transition hover:bg-white cursor-pointer"
            >
              <div class="h-9 w-9 rounded-full bg-gradient-to-tr from-amber-400 to-amber-200 flex items-center justify-center text-[#111] text-[13px] font-black shrink-0 border border-black/10">
                {{ (authStore.currentUser?.nom || 'U')[0].toUpperCase() }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-[13px] font-bold text-black leading-tight truncate">{{ authStore.currentUser?.nom || 'Utilisateur' }}</p>
                <p class="text-[11px] text-gray-500 capitalize">{{ authStore.userRole }} • Voir profil</p>
              </div>
            </button>

            <RouterLink to="/salles" @click="closeMenu"
              class="flex items-center rounded-xl px-4 py-3 text-[14px] font-semibold text-black transition hover:bg-white/70"
              :class="{ 'bg-white/80': $route.path.startsWith('/salles') }">Salles</RouterLink>
            <RouterLink to="/equipements" @click="closeMenu"
              class="flex items-center rounded-xl px-4 py-3 text-[14px] font-semibold text-black transition hover:bg-white/70"
              :class="{ 'bg-white/80': $route.path.startsWith('/equipements') }">Équipements</RouterLink>
            <RouterLink to="/reservations" @click="closeMenu"
              class="flex items-center rounded-xl px-4 py-3 text-[14px] font-semibold text-black transition hover:bg-white/70"
              :class="{ 'bg-white/80': $route.path.startsWith('/reservations') }">Réservations</RouterLink>
            <button
              type="button"
              @click="openProfileModalFromMobile"
              class="flex w-full items-center rounded-xl px-4 py-3 text-[14px] font-semibold text-black transition hover:bg-white/70 text-left cursor-pointer"
            >
              Mon profil
            </button>

            <div class="mt-2 border-t border-gray-100 pt-2">
              <button @click="handleLogout"
                class="w-full rounded-xl px-4 py-3 text-left text-[14px] font-bold text-red-600 transition hover:bg-red-50 cursor-pointer">
                Déconnexion
              </button>
            </div>
          </template>

          <!-- Admin / Responsable -->
          <template v-else-if="authStore.isAuthenticated && (authStore.isAdmin || authStore.isResponsable)">
            <button
              type="button"
              @click="openProfileModalFromMobile"
              class="flex w-full items-center gap-3 px-3 py-2.5 mb-1 rounded-2xl bg-white/70 border border-gray-200/80 text-left transition hover:bg-white cursor-pointer"
            >
              <div class="h-9 w-9 rounded-full bg-gradient-to-tr from-amber-400 to-amber-200 flex items-center justify-center text-[#111] text-[13px] font-black shrink-0 border border-black/10">
                {{ (authStore.currentUser?.nom || 'A')[0].toUpperCase() }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-[13px] font-bold text-black truncate">{{ authStore.currentUser?.nom || 'Admin' }}</p>
                <p class="text-[11px] text-gray-500 capitalize">{{ authStore.userRole }} • Voir profil</p>
              </div>
            </button>
            <RouterLink to="/" @click="closeMenu" class="flex items-center rounded-xl px-4 py-3 text-[14px] font-semibold text-black hover:bg-white/70">Accueil</RouterLink>
            <RouterLink to="/salles" @click="closeMenu" class="flex items-center rounded-xl px-4 py-3 text-[14px] font-semibold text-black hover:bg-white/70">Salles</RouterLink>
            <RouterLink to="/equipements" @click="closeMenu" class="flex items-center rounded-xl px-4 py-3 text-[14px] font-semibold text-black hover:bg-white/70">Équipements</RouterLink>
            <button
              type="button"
              @click="openProfileModalFromMobile"
              class="flex w-full items-center rounded-xl px-4 py-3 text-[14px] font-semibold text-black hover:bg-white/70 text-left cursor-pointer"
            >
              Mon profil
            </button>
            <div class="mt-2 border-t border-gray-100 pt-2">
              <button @click="handleLogout" class="w-full rounded-xl px-4 py-3 text-left text-[14px] font-bold text-red-600 hover:bg-red-50 cursor-pointer">Déconnexion</button>
            </div>
          </template>

          <!-- Visiteur -->
          <template v-else>
            <RouterLink to="/" @click="closeMenu" class="flex items-center rounded-xl px-4 py-3 text-[14px] font-semibold text-black hover:bg-white/70">Accueil</RouterLink>
            <RouterLink to="/salles" @click="closeMenu" class="flex items-center rounded-xl px-4 py-3 text-[14px] font-semibold text-black hover:bg-white/70">Salles</RouterLink>
            <RouterLink to="/equipements" @click="closeMenu" class="flex items-center rounded-xl px-4 py-3 text-[14px] font-semibold text-black hover:bg-white/70">Équipements</RouterLink>
            <div class="mt-2 border-t border-gray-100 pt-2 flex flex-col gap-2">
              <RouterLink to="/auth/login" @click="closeMenu"
                class="w-full rounded-xl bg-white/60 px-4 py-3 text-center text-[14px] font-bold text-black border border-gray-200 hover:bg-white transition">
                Se connecter
              </RouterLink>
              <RouterLink to="/auth/register" @click="closeMenu"
                class="w-full rounded-xl bg-[#111111] px-4 py-3 text-center text-[14px] font-bold text-white hover:bg-black transition">
                S'inscrire
              </RouterLink>
            </div>
          </template>

        </div>
      </div>
    </Transition>

    <!-- MODAL PROFIL UTILISATEUR -->
    <UserProfileModal
      :is-open="isProfileModalOpen"
      @close="isProfileModalOpen = false"
      @update:is-open="isProfileModalOpen = $event"
    />
  </div>
</template>


<style scoped>

/* ================================================================
   FILET DE SECURITE - indépendant de Tailwind
   Garantit l'effet verre dépoli et le centrage même si les
   classes Tailwind ne sont pas correctement générées.
================================================================ */

.fixed {
    position: fixed;
    left: 0;
    right: 0;
    top: 16px;
    z-index: 50;
    display: flex;
    justify-content: center;
    padding-left: 1rem;
    padding-right: 1rem;
}

nav {
    width: 100%;
    max-width: 1110px;
    border-radius: 9999px;
    border: 1px solid rgba(255, 255, 255, 0.4);
    background-color: rgba(255, 255, 255, 0.6);
    box-shadow: 0 8px 32px rgba(15, 23, 42, 0.08);
    -webkit-backdrop-filter: blur(20px) saturate(150%);
    backdrop-filter: blur(20px) saturate(150%);
}

/* ================================================================
   ANIMATION DRAWER MOBILE
================================================================ */

.drawer-enter-active {
    transition: opacity 0.22s ease, transform 0.22s cubic-bezier(0.22, 1, 0.36, 1);
}

.drawer-leave-active {
    transition: opacity 0.18s ease, transform 0.18s ease;
}

.drawer-enter-from,
.drawer-leave-to {
    opacity: 0;
    transform: translateY(-8px) scale(0.98);
}
</style>
