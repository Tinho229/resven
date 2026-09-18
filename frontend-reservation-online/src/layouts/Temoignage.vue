<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'

const containerRef = ref(null)
const scrollProgress = ref(0)

// Répartition des témoignages sur 3 lignes
const line1 = [
  {
    name: 'Hugo',
    role: 'Responsable d’établissement',
    quote: 'La réservation des salles est devenue beaucoup plus simple. Nous pouvons vérifier les disponibilités et organiser nos activités rapidement.',
    avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80',
    dark: false,
  },
  {
    name: 'Fabrice',
    role: 'Responsable administratif',
    quote: 'Avant, il fallait passer par plusieurs personnes pour savoir si une salle était disponible. Maintenant, tout est centralisé.',
    avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&auto=format&fit=crop&q=80',
    dark: true,
  },
  {
    name: 'Sophie',
    role: 'Responsable de formation',
    quote: 'La plateforme nous fait gagner beaucoup de temps dans l’organisation de nos formations et réunions.',
    avatar: 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=150&auto=format&fit=crop&q=80',
    dark: false,
  },
]

const line2 = [
  {
    name: 'Nicolas',
    role: 'Responsable de salle',
    quote: 'Nous avons maintenant une meilleure visibilité sur les réservations et les créneaux disponibles.',
    avatar: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150&auto=format&fit=crop&q=80',
    dark: true,
  },
  {
    name: 'Billy',
    role: 'Formateur',
    quote: 'Je peux réserver une salle adaptée à mon activité sans avoir à me déplacer ou appeler plusieurs personnes.',
    avatar: 'https://images.unsplash.com/photo-1492562080023-ab3db95bfbce?w=150&auto=format&fit=crop&q=80',
    dark: false,
  },
  {
    name: 'Camille',
    role: 'Organisatrice d’événements',
    quote: 'La gestion des salles et des équipements est beaucoup plus pratique lorsque toutes les informations sont réunies au même endroit.',
    avatar: 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=150&auto=format&fit=crop&q=80',
    dark: true,
  },
]

const line3 = [
  {
    name: 'Thibault',
    role: 'Responsable d’entreprise',
    quote: 'Nous pouvons facilement réserver une salle pour nos réunions et connaître les équipements disponibles à l’avance.',
    avatar: 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=150&auto=format&fit=crop&q=80',
    dark: false,
  },
  {
    name: 'Marc',
    role: 'Responsable informatique',
    quote: 'Le suivi des réservations et des équipements nous permet d’éviter les conflits de planning.',
    avatar: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=150&auto=format&fit=crop&q=80',
    dark: true,
  },
  {
    name: 'Julie',
    role: 'Assistante administrative',
    quote: 'La réservation en ligne est rapide et claire. On sait immédiatement quelles salles sont disponibles.',
    avatar: 'https://images.unsplash.com/photo-1580489944761-15a19d654956?w=150&auto=format&fit=crop&q=80',
    dark: false,
  },
]

const handleScroll = () => {
  if (!containerRef.value) return
  const rect = containerRef.value.getBoundingClientRect()
  const windowHeight = window.innerHeight
  const totalScrollableDistance = rect.height - windowHeight

  let progress = -rect.top / totalScrollableDistance
  progress = Math.max(0, Math.min(1, progress))

  scrollProgress.value = progress
}

onMounted(() => {
  window.addEventListener('scroll', handleScroll)
})

onBeforeUnmount(() => {
  window.removeEventListener('scroll', handleScroll)
})
</script>

<template>
  <div ref="containerRef" class="relative h-[100vh] bg-white text-slate-900">
    <div class="sticky top-0 h-[90vh]  w-full overflow-hidden flex flex-col justify-between py-8">

      <!-- Lignes de grille verticales -->
      <div class="absolute inset-0 grid grid-cols-6 lg:grid-cols-12 pointer-events-none opacity-30">
        <div v-for="n in 12" :key="n" class="border-r border-slate-200 h-full"></div>
      </div>

      <!-- En-tête -->
      <div class="relative z-10 max-w-7xl mx-auto w-full px-8">
        <h2 class="text-center text-4xl sm:text-6xl font-black text-slate-950 tracking-tight uppercase">
          Vos témoignages
        </h2>
      </div>

      <!-- 3 LIGNES DE TÉMOIGNAGES -->
      <div class="relative z-10 w-full overflow-hidden  space-y-4 ">

        <!-- Ligne 1 -->
        <div
          class="flex items-center gap-4 px-8 transition-transform duration-75 ease-out w-max"
          :style="{ transform: `translateX(-${scrollProgress * 45}%)` }"
        >
          <div
            v-for="(item, index) in line1"
            :key="'l1-' + index"
            class="flex items-center gap-4 p-4 pr-8 rounded-full shadow-lg min-w-[380px] max-w-[460px] transition-all duration-300 hover:scale-[1.02]"
            :class="[
              item.dark
                ? 'bg-[#0B0C10] border border-slate-800 text-white shadow-xl'
                : 'bg-white border border-slate-200/80 text-slate-900 shadow-slate-100'
            ]"
          >
            <img
              :src="item.avatar"
              :alt="item.name"
              class="w-12 h-12 rounded-full object-cover shrink-0 border-2"
              :class="item.dark ? 'border-slate-700' : 'border-slate-200'"
            />
            <div class="flex flex-col justify-center space-y-0.5">
              <p
                class="text-xs font-semibold leading-relaxed"
                :class="item.dark ? 'text-slate-100' : 'text-slate-800'"
              >
                " {{ item.quote }} "
              </p>
              <p
                class="text-[11px] font-medium"
                :class="item.dark ? 'text-slate-400' : 'text-slate-500'"
              >
                {{ item.name }}, <span class="opacity-80">{{ item.role }}</span>
              </p>
            </div>
          </div>
        </div>

        <!-- Ligne 2 (Décalée) -->
        <div
          class="flex items-center gap-4 px-8 transition-transform duration-75 ease-out w-max pl-24"
          :style="{ transform: `translateX(-${scrollProgress * 60}%)` }"
        >
          <div
            v-for="(item, index) in line2"
            :key="'l2-' + index"
            class="flex items-center gap-4 p-4 pr-8 rounded-full shadow-lg min-w-[380px] max-w-[460px] transition-all duration-300 hover:scale-[1.02]"
            :class="[
              item.dark
                ? 'bg-[#0B0C10] border border-slate-800 text-white shadow-xl'
                : 'bg-white border border-slate-200/80 text-slate-900 shadow-slate-100'
            ]"
          >
            <img
              :src="item.avatar"
              :alt="item.name"
              class="w-12 h-12 rounded-full object-cover shrink-0 border-2"
              :class="item.dark ? 'border-slate-700' : 'border-slate-200'"
            />
            <div class="flex flex-col justify-center space-y-0.5">
              <p
                class="text-xs font-semibold leading-relaxed"
                :class="item.dark ? 'text-slate-100' : 'text-slate-800'"
              >
                " {{ item.quote }} "
              </p>
              <p
                class="text-[11px] font-medium"
                :class="item.dark ? 'text-slate-400' : 'text-slate-500'"
              >
                {{ item.name }}, <span class="opacity-80">{{ item.role }}</span>
              </p>
            </div>
          </div>
        </div>

        <!-- Ligne 3 -->
        <div
          class="flex items-center gap-4 px-8 transition-transform duration-75 ease-out w-max"
          :style="{ transform: `translateX(-${scrollProgress * 50}%)` }"
        >
          <div
            v-for="(item, index) in line3"
            :key="'l3-' + index"
            class="flex items-center gap-4 p-4 pr-8 rounded-full shadow-lg min-w-[380px] max-w-[460px] transition-all duration-300 hover:scale-[1.02]"
            :class="[
              item.dark
                ? 'bg-[#0B0C10] border border-slate-800 text-white shadow-xl'
                : 'bg-white border border-slate-200/80 text-slate-900 shadow-slate-100'
            ]"
          >
            <img
              :src="item.avatar"
              :alt="item.name"
              class="w-12 h-12 rounded-full object-cover shrink-0 border-2"
              :class="item.dark ? 'border-slate-700' : 'border-slate-200'"
            />
            <div class="flex flex-col justify-center space-y-0.5">
              <p
                class="text-xs font-semibold leading-relaxed"
                :class="item.dark ? 'text-slate-100' : 'text-slate-800'"
              >
                " {{ item.quote }} "
              </p>
              <p
                class="text-[11px] font-medium"
                :class="item.dark ? 'text-slate-400' : 'text-slate-500'"
              >
                {{ item.name }}, <span class="opacity-80">{{ item.role }}</span>
              </p>
            </div>
          </div>
        </div>

      </div>

      <!-- Espacement bas neutre -->
      <div class="h-4"></div>

    </div>
  </div>
</template>
