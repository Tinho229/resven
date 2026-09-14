<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import NavBar from '@/layouts/NavBar.vue'
import Footer from '@/layouts/Footer.vue'
import { useSallesStore } from '@/store/salles'
import { toApiDateTime } from '@/helpers/dateHelper'
import {
  ArrowLeft,
  MapPin,
  Users,
  Banknote,
  Calendar,
  CheckCircle2,
  AlertCircle,
  Loader2,
  ArrowUpRight,
  Sparkles,
  ShieldCheck,
  Clock,
  DoorOpen,
} from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const sallesStore = useSallesStore()

const salleId = route.params.id
const salle = ref(null)
const isFetching = ref(true)
const fetchError = ref(null)

// Index de la photo sélectionnée dans la galerie
const selectedPhotoIndex = ref(0)

// Défilement automatique des images
const autoPlayInterval = ref(null)
const AUTO_PLAY_DELAY = 4000 // 4 secondes entre chaque image

const startAutoPlay = () => {
  if (imagesList.value.length > 1) {
    autoPlayInterval.value = setInterval(() => {
      selectedPhotoIndex.value = (selectedPhotoIndex.value + 1) % imagesList.value.length
    }, AUTO_PLAY_DELAY)
  }
}

const stopAutoPlay = () => {
  if (autoPlayInterval.value) {
    clearInterval(autoPlayInterval.value)
    autoPlayInterval.value = null
  }
}

// Test de disponibilité
const debutDateTime = ref('')
const finDateTime = ref('')
const checkingDispo = ref(false)
const dispoResult = ref(null)
const dispoError = ref(null)

const isConnected = computed(() => !!localStorage.getItem('token'))

const defaultPlaceholder =
  'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1200&q=80'

onMounted(async () => {
  try {
    isFetching.value = true
    salle.value = await sallesStore.fetchSalle(salleId)
  } catch (err) {
    fetchError.value = err.message || 'Impossible de charger les détails de cette salle.'
    console.error('Erreur fetchSalle:', err)
  } finally {
    isFetching.value = false
  }

  // Initialiser les dates du créneau par défaut : demain de 09:00 à 12:00
  const tomorrow = new Date()
  tomorrow.setDate(tomorrow.getDate() + 1)
  const yyyy = tomorrow.getFullYear()
  const mm = String(tomorrow.getMonth() + 1).padStart(2, '0')
  const dd = String(tomorrow.getDate()).padStart(2, '0')

  debutDateTime.value = `${yyyy}-${mm}-${dd}T09:00`
  finDateTime.value = `${yyyy}-${mm}-${dd}T12:00`

  // Démarrer le carrousel automatique
  startAutoPlay()
})

onUnmounted(() => {
  // Arrêter le carrousel automatique lors de la destruction du composant
  stopAutoPlay()
})

const imagesList = computed(() => {
  if (salle.value && salle.value.images && salle.value.images.length > 0) {
    return salle.value.images.map((img) => img.url || img.path || defaultPlaceholder)
  }
  return [defaultPlaceholder]
})

const activeImage = computed(() => {
  return imagesList.value[selectedPhotoIndex.value] || imagesList.value[0]
})

const isDisponible = computed(() => {
  return salle.value && (!salle.value.status || salle.value.status.toLowerCase() === 'disponible')
})

const checkCreneau = async () => {
  if (!debutDateTime.value || !finDateTime.value) {
    dispoError.value = 'Veuillez renseigner une date de début et une date de fin.'
    return
  }

  if (new Date(debutDateTime.value) >= new Date(finDateTime.value)) {
    dispoError.value = 'La date de fin doit être postérieure à la date de début.'
    return
  }

  checkingDispo.value = true
  dispoError.value = null
  dispoResult.value = null

  try {
    const formattedDebut = toApiDateTime(debutDateTime.value)
    const formattedFin = toApiDateTime(finDateTime.value)

    const res = await sallesStore.checkDisponibilite(salleId, formattedDebut, formattedFin)
    dispoResult.value = res
  } catch (err) {
    dispoError.value = err.message || 'Erreur lors de la vérification de disponibilité.'
  } finally {
    checkingDispo.value = false
  }
}

const handleReserver = () => {
  const token = localStorage.getItem('token')

  if (!token) {
    router.push({
      name: 'login',
      query: {
        redirect: '/reserver',
        salle_id: salleId,
        debut: debutDateTime.value,
        fin: finDateTime.value,
      },
    })
    return
  }

  router.push({
    name: 'user-create-reservation',
    query: {
      salle_id: salleId,
      debut: debutDateTime.value,
      fin: finDateTime.value,
    },
  })
}


</script>

<template>
    <div class="min-h-[80vh] pt-28 bg-[#f6f6f4] text-[#151515]">
        <NavBar />

        <div class="px-4 py-10 sm:px-6 lg:px-10">
            <div class="mx-auto max-w-[1180px]">


                <div
                    v-if="isFetching"
                    class="flex min-h-[420px] items-center justify-center rounded-[14px] bg-white"
                >
                    <div class="flex flex-col items-center gap-3">
                        <Loader2 :size="34" class="animate-spin text-slate-800" />
                        <p class="text-sm text-[#777]">Chargement des détails de la salle...</p>
                    </div>
                </div>

                <div
                    v-else-if="fetchError"
                    class="rounded-[14px] border border-[#eaded9] bg-white p-10 text-center"
                >
                    <AlertCircle :size="42" class="mx-auto mb-3 text-slate-800" />
                    <h2 class="text-xl font-semibold text-[#191919]">Salle introuvable</h2>
                    <p class="mt-2 text-sm text-[#777]">{{ fetchError }}</p>
                    <RouterLink
                        :to="{ name: 'salles' }"
                        class="mt-6 inline-flex items-center gap-2 rounded-[9px] bg-[#191919] px-5 py-3 text-xs font-semibold text-white transition hover:bg-black"
                    >
                        Retourner au catalogue
                    </RouterLink>
                </div>

                <div v-else-if="salle">
                    <section class="overflow-hidden rounded-[15px] border border-[#ecebe7] bg-white" v-scroll-reveal="{ direction: 'up', delay: 80 }">
                        <div class="grid min-h-[465px] grid-cols-1 lg:grid-cols-[1.06fr_0.98fr_1fr]">
                            <!-- IMAGE : même rôle visuel que dans la référence -->
                            <div class="relative min-h-[390px] overflow-hidden bg-[#e9e8e4] lg:min-h-0">
                                <img
                                    :src="activeImage"
                                    :alt="salle.nom"
                                    class="absolute inset-0 h-full w-full object-cover transition-opacity duration-700 ease-in-out"
                                />

                                <div class="absolute inset-0 bg-gradient-to-t from-black/45 via-black/0 to-black/5"></div>

                                <!-- <div class="absolute left-5 top-5">
                                    <div
                                        class="inline-flex items-center gap-1.5 rounded-full border border-white/35 bg-white/15 px-3 py-1.5 text-[11px] font-medium text-white backdrop-blur-md"
                                    >
                                        <Sparkles :size="12" />
                                        <span>Espace Événementiel</span>
                                    </div>
                                </div> -->


                                <div class="absolute bottom-5 left-5 right-5 flex items-end justify-between gap-4">
                                    <div>
                                        <p class="text-[11px] font-medium uppercase tracking-[0.12em] text-white/70">
                                            {{ isDisponible ? 'Disponible' : 'Actuellement occupée' }}
                                        </p>
                                        <p class="mt-1 text-xl font-semibold leading-tight text-white">
                                            {{ salle.nom }}
                                        </p>
                                    </div>

                                    <div
                                        class="shrink-0 rounded-full px-3 py-1.5 text-[10px] font-semibold text-white backdrop-blur-md"
                                        :class="isDisponible ? 'bg-emerald-500/90' : 'bg-rose-500/90'"
                                    >
                                        {{ isDisponible ? 'Disponible' : 'Indisponible' }}
                                    </div>
                                </div>
                            </div>

                            <!-- CENTRE : hiérarchie prix / positionnement de la référence -->
                            <div class="flex flex-col justify-between border-b border-[#ecebe7] px-7 py-9 sm:px-10 lg:border-b-0 lg:border-r lg:border-[#ecebe7]">
                                <div>
                                    <p class="text-[13px] font-medium text-[#7b7b7b]">Votre espace</p>

                                    <h1 class="mt-5 max-w-[320px] font-serif text-[42px] leading-[0.98] tracking-[-0.04em] text-[#191919]">
                                        {{ salle.nom }}
                                    </h1>

                                    <div class="mt-4 flex items-start gap-2 text-[13px] leading-5 text-[#777]">
                                        <MapPin :size="15" class="mt-0.5 shrink-0 text-slate-800" />
                                        <span>{{ salle.localisation || 'Localisation non renseignée' }}</span>
                                    </div>

                                    <div class="mt-10 flex items-end gap-2">
                                        <span class="font-serif text-[44px] leading-none tracking-[-0.04em] text-[#000000]">
                                            Description
                                        </span>
                                    </div>

                                    <p class="mt-3 max-w-[280px] text-[13px] leading-6 text-[#777]">
                                      {{ salle.description || 'Cette salle moderne et fonctionnelle est prête à accueillir vos événements professionnels.' }}

                                    </p>
                                </div>

                                <div class="mt-10">
                                    <button
                                        type="button"
                                        @click="handleReserver"
                                        class="group inline-flex w-full items-center justify-between rounded-[9px] border border-[#1d293d] px-4 py-3 text-[12px] font-medium text-slate-800 transition hover:bg-[#181818] hover:text-white"
                                    >
                                        <span>{{ isConnected ? 'Réserver cette salle' : 'Se connecter pour réserver' }}</span>
                                        <ArrowUpRight :size="16" class="transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5" />
                                    </button>

                                    <div class="mt-2  pt-4">
                                    <RouterLink
                                        :to="{ name: 'salles' }"
                                        class="inline-flex w-full items-center justify-center rounded-[8px] border border-[#deddd9] bg-[#fafaf8] py-2.5 text-[11px] font-semibold text-[#333] transition hover:bg-white"
                                    >
                                        Retour à la liste des salles
                                    </RouterLink>
                                    </div>


                                </div>
                            </div>

                            <!-- DROITE : contenu proche de la liste de features de la référence -->
                            <div class="flex flex-col justify-between px-7 py-9 sm:px-10">
                                <div>
                                    <p class="text-[12px] font-medium text-slate-800">Ce qui est inclus</p>

                                    <div class="mt-6 space-y-5">
                                        <div class="flex gap-3 border-b border-[#efeee9] pb-4">
                                            <CheckCircle2 :size="17" class="mt-0.5 shrink-0 text-slate-800" />
                                            <div>
                                                <p class="text-[13px] font-medium text-[#5d5d5d]">Capacité</p>
                                                <p class="mt-1 text-[15px] font-medium text-[#222]">
                                                    {{ salle.capacite }} personnes
                                                </p>
                                            </div>
                                        </div>

                                        <div class="flex gap-3 border-b border-[#efeee9] pb-4">
                                            <CheckCircle2 :size="17" class="mt-0.5 shrink-0 text-slate-800" />
                                            <div>
                                                <p class="text-[13px] font-medium text-[#5d5d5d]">Disponibilité</p>
                                                <p
                                                    class="mt-1 text-[15px] font-medium capitalize"
                                                    :class="isDisponible ? 'text-[#2f9967]' : 'text-[#c65757]'"
                                                >
                                                    {{ salle.status || (isDisponible ? 'Disponible' : 'Occupée') }}
                                                </p>
                                            </div>
                                        </div>





                                        <div class="flex gap-3">
                                            <CheckCircle2 :size="17" class="mt-0.5 shrink-0 text-slate-800" />
                                            <div>
                                                <p class="text-[13px] font-medium text-[#5d5d5d]">Prix</p>
                                                <p class="mt-1 line-clamp-4 text-[13px] leading-5 text-[#777]">
                                                    {{ salle.prix }}                                         <span class="pb-1 text-[12px] font-medium text-[#8a8a8a]">FCFA</span>

                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Vérification de disponibilité : conserve exactement la logique backend existante -->
                                <div class="mt-9 border-t border-[#ecebe7] pt-6">
                                    <div class="flex items-center gap-2">
                                        <Calendar :size="16" class="text-slate-800" />
                                        <h2 class="text-[12px] font-semibold uppercase tracking-[0.08em] text-[#222]">
                                            Vérifier un créneau
                                        </h2>
                                    </div>

                                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                        <label class="block">
                                            <span class="mb-1.5 block text-[10px] font-medium text-[#777]">Début</span>
                                            <input
                                                v-model="debutDateTime"
                                                type="datetime-local"
                                                class="w-full rounded-[8px] border border-[#deddd9] bg-[#fafaf8] px-3 py-2.5 text-[11px] text-[#333] outline-none transition focus:border-[#181818] focus:bg-white"
                                            />
                                        </label>

                                        <label class="block">
                                            <span class="mb-1.5 block text-[10px] font-medium text-[#777]">Fin</span>
                                            <input
                                                v-model="finDateTime"
                                                type="datetime-local"
                                                class="w-full rounded-[8px] border border-[#deddd9] bg-[#fafaf8] px-3 py-2.5 text-[11px] text-[#333] outline-none transition focus:border-[#181818] focus:bg-white"
                                            />
                                        </label>
                                    </div>

                                    <button
                                        type="button"
                                        @click="checkCreneau"
                                        :disabled="checkingDispo"
                                        class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-[8px] bg-[#181818] py-2.5 text-[11px] font-semibold text-white transition hover:bg-black disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <Loader2 v-if="checkingDispo" :size="14" class="animate-spin" />
                                        <span>{{ checkingDispo ? 'Vérification...' : 'Vérifier la disponibilité' }}</span>
                                    </button>

                                    <div
                                        v-if="dispoError"
                                        class="mt-3 rounded-[8px] border border-rose-200 bg-rose-50 px-3 py-2.5 text-[11px] text-rose-700"
                                    >
                                        <div class="flex items-start gap-2">
                                            <AlertCircle :size="14" class="mt-0.5 shrink-0" />
                                            <span>{{ dispoError }}</span>
                                        </div>
                                    </div>

                                    <div
                                        v-if="dispoResult"
                                        class="mt-3 rounded-[8px] border px-3 py-2.5"
                                        :class="dispoResult.disponible ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50'"
                                    >
                                        <div
                                            class="flex items-center gap-2 text-[11px] font-semibold"
                                            :class="dispoResult.disponible ? 'text-emerald-800' : 'text-rose-800'"
                                        >
                                            <CheckCircle2 v-if="dispoResult.disponible" :size="14" />
                                            <AlertCircle v-else :size="14" />
                                            <span>
                                                {{ dispoResult.disponible ? 'Créneau 100% disponible !' : 'Créneau déjà réservé' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                    </section>
                </div>
            </div>
        </div>

        <Footer />
    </div>
</template>
