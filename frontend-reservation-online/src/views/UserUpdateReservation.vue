<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import NavBar from '@/layouts/NavBar.vue'
import Footer from '@/layouts/Footer.vue'
import { useSallesStore } from '@/store/salles'
import { useEquipementsStore } from '@/store/equipements'
import { useReservationsStore } from '@/store/reservations'
import { toInputDateTime, toApiDateTime } from '@/helpers/dateHelper'
import {
  ArrowLeft,
  Calendar,
  Clock,
  MapPin,
  Users,
  Package,
  CheckCircle2,
  AlertCircle,
  Loader2,
  Building2,
  Plus,
  Minus,
  Trash2,
  Pencil,
  Save,
  Info,
} from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()

const sallesStore = useSallesStore()
const equipementsStore = useEquipementsStore()
const reservationsStore = useReservationsStore()

const reservationId = route.params.id

const isFetching = ref(true)
const fetchError = ref(null)
const reservation = ref(null)

// Données du formulaire
const selectedSalleId = ref(null)
const debutDateTime = ref('')
const finDateTime = ref('')
const nombrePersonnes = ref(1)
const selectedEquipements = ref([]) // [{ equipement_id, nom, quantity, stock_total }]

// États de vérification & soumission
const checkingDispo = ref(false)
const dispoResult = ref(null)
const dispoError = ref(null)
const submitError = ref(null)
const submitting = computed(() => reservationsStore.submitting)

const defaultImage =
  'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=800&q=80'

// Index de l'image actuelle pour la salle sélectionnée
const selectedSalleImageIndex = ref(0)
const autoPlayInterval = ref(null)
const AUTO_PLAY_DELAY = 4000 // 4 secondes entre chaque image

const startAutoPlay = () => {
  if (!selectedSalle.value) return
  const imgs = selectedSalle.value.images
  if (imgs && imgs.length > 1) {
    autoPlayInterval.value = setInterval(() => {
      selectedSalleImageIndex.value = (selectedSalleImageIndex.value + 1) % imgs.length
    }, AUTO_PLAY_DELAY)
  }
}

const stopAutoPlay = () => {
  if (autoPlayInterval.value) {
    clearInterval(autoPlayInterval.value)
    autoPlayInterval.value = null
  }
}

const salleCoverUrl = computed(() => {
  if (!selectedSalle.value) return defaultImage
  const imgs = selectedSalle.value.images
  if (imgs && imgs.length > 0) {
    const index = selectedSalleImageIndex.value % imgs.length
    return imgs[index].url || imgs[index].path || defaultImage
  }
  return defaultImage
})

onMounted(async () => {
  try {
    isFetching.value = true
    fetchError.value = null

    // Charger les listes de salles et équipements
    await Promise.all([
      sallesStore.fetchSalles(),
      equipementsStore.fetchEquipements(),
      reservationsStore.fetchReservation(reservationId),
    ])

    reservation.value = reservationsStore.currentReservation

    if (reservation.value) {
      selectedSalleId.value = reservation.value.salle_id
      debutDateTime.value = toInputDateTime(reservation.value.date_heure_debut)
      finDateTime.value = toInputDateTime(reservation.value.date_heure_fin)
      nombrePersonnes.value = reservation.value.nombre_personnes || 1

      if (reservation.value.equipements && reservation.value.equipements.length > 0) {
        selectedEquipements.value = reservation.value.equipements.map((eq) => ({
          equipement_id: eq.id,
          nom: eq.nom,
          quantity: eq.pivot?.quantity || eq.quantity || 1,
          stock_total: eq.stock_total || 99,
        }))
      }
    }
  } catch (err) {
    fetchError.value = reservationsStore.errorMessage || 'Impossible de charger la réservation à modifier.'
  } finally {
    isFetching.value = false
  }

  // Démarrer le carrousel automatique
  startAutoPlay()
})

// Quand la salle change, réinitialiser l'index et redémarrer le carrousel
watch(selectedSalleId, () => {
  selectedSalleImageIndex.value = 0
  stopAutoPlay()
  startAutoPlay()
})

onUnmounted(() => {
  // Arrêter le carrousel automatique lors de la destruction du composant
  stopAutoPlay()
})

// Stores computed
const salles = computed(() => sallesStore.salles || [])
const allEquipements = computed(() => equipementsStore.equipements || [])

const selectedSalle = computed(() => {
  return salles.value.find((s) => s.id === selectedSalleId.value) || reservation.value?.salle || null
})

// Équipements disponibles non encore ajoutés
const availableEquipementsToAdd = computed(() => {
  const selectedIds = selectedEquipements.value.map((e) => e.equipement_id)
  return allEquipements.value.filter((eq) => !selectedIds.includes(eq.id))
})

const isModifiable = computed(() => {
  return reservation.value && (reservation.value.status === 'en_attente' || reservation.value.status === 'confirmee')
})

// Gestion des équipements
const addEquipement = (eq) => {
  if (selectedEquipements.value.some((e) => e.equipement_id === eq.id)) return
  selectedEquipements.value.push({
    equipement_id: eq.id,
    nom: eq.nom,
    quantity: 1,
    stock_total: eq.stock_total || 99,
  })
}

const removeEquipement = (eqId) => {
  selectedEquipements.value = selectedEquipements.value.filter((e) => e.equipement_id !== eqId)
}

const incrementQty = (item) => {
  if (item.quantity < item.stock_total) {
    item.quantity++
  }
}

const decrementQty = (item) => {
  if (item.quantity > 1) {
    item.quantity--
  }
}

// Vérifier la disponibilité de la salle sur ce créneau
const checkCreneau = async () => {
  dispoError.value = null
  dispoResult.value = null

  if (!selectedSalleId.value) {
    dispoError.value = 'Veuillez sélectionner une salle.'
    return
  }
  if (!debutDateTime.value || !finDateTime.value) {
    dispoError.value = 'Veuillez renseigner les dates de début et de fin.'
    return
  }
  if (new Date(debutDateTime.value) >= new Date(finDateTime.value)) {
    dispoError.value = 'La date de fin doit être postérieure à la date de début.'
    return
  }

  checkingDispo.value = true
  try {
    const res = await sallesStore.checkDisponibilite(
      selectedSalleId.value,
      toApiDateTime(debutDateTime.value),
      toApiDateTime(finDateTime.value)
    )
    dispoResult.value = res
  } catch (err) {
    dispoError.value = err.message || 'Erreur lors de la vérification de disponibilité.'
  } finally {
    checkingDispo.value = false
  }
}

// Soumission de la mise à jour
const handleUpdate = async () => {
  submitError.value = null

  if (!selectedSalleId.value) {
    submitError.value = 'Veuillez sélectionner une salle.'
    return
  }
  if (!debutDateTime.value || !finDateTime.value) {
    submitError.value = 'Veuillez indiquer les dates et heures de début et de fin.'
    return
  }
  if (new Date(debutDateTime.value) >= new Date(finDateTime.value)) {
    submitError.value = 'La date de fin doit être postérieure à la date de début.'
    return
  }
  if (!nombrePersonnes.value || nombrePersonnes.value < 1) {
    submitError.value = 'Le nombre de personnes doit être d’au moins 1.'
    return
  }
  if (selectedSalle.value?.capacite && nombrePersonnes.value > selectedSalle.value.capacite) {
    submitError.value = `Cette salle a une capacité maximale de ${selectedSalle.value.capacite} personnes.`
    return
  }

  const payload = {
    salle_id: selectedSalleId.value,
    date_heure_debut: toApiDateTime(debutDateTime.value),
    date_heure_fin: toApiDateTime(finDateTime.value),
    nombre_personnes: Number(nombrePersonnes.value),
    equipements: selectedEquipements.value.map((e) => ({
      equipement_id: e.equipement_id,
      quantity: Number(e.quantity),
    })),
  }

  try {
    await reservationsStore.updateReservation(reservationId, payload)
    router.push({ name: 'user-reservation-details', params: { id: reservationId } })
  } catch (err) {
    submitError.value =
      reservationsStore.errorMessage ||
      Object.values(reservationsStore.validationErrors).flat().join(' — ') ||
      'Une erreur est survenue lors de la mise à jour.'
  }
}
</script>

<template>
    <div class="min-h-screen bg-[#f6f6f4] text-[#151515] flex flex-col justify-between">
        <NavBar />

        <main class="flex-1 pt-28 pb-16 px-4 sm:px-6 lg:px-8 w-full max-w-[1240px] mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-[1.05fr_0.95fr] gap-5 min-h-[640px]">

                <!-- GAUCHE : HERO / CARTE SALLE SÉLECTIONNÉE -->
                <section class="relative min-h-[480px] lg:min-h-full overflow-hidden rounded-[20px] border border-[#ecebe7] bg-[#141515] flex flex-col justify-between p-6 sm:p-8" v-scroll-reveal="{ direction: 'left', delay: 0 }">
                    <img
                        :src="salleCoverUrl"
                        :alt="selectedSalle?.nom || 'Salle'"
                        class="absolute inset-0 h-full w-full object-cover transition-opacity duration-700 ease-in-out"
                    />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-black/30"></div>

                    <!-- En-tête gauche -->
                    <div class="relative z-10 flex items-center justify-between">
                        <RouterLink
                            :to="{ name: 'user-reservation-details', params: { id: reservationId } }"
                            class="inline-flex items-center gap-2 rounded-full border border-white/25 bg-black/40 px-3.5 py-1.5 text-[11px] uppercase tracking-[0.08em] text-white backdrop-blur-md transition hover:bg-white/10"
                        >
                            <ArrowLeft :size="13" />
                            <span>Retour aux détails</span>
                        </RouterLink>
                        <div class="inline-flex items-center gap-1.5 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-[11px] text-white/90 backdrop-blur-md">
                            <Pencil :size="12" />
                            <span>Modification</span>
                        </div>
                    </div>

                    <!-- Pied gauche -->
                    <div class="relative z-10 mt-auto pt-16">
                        <div class="mb-3 flex flex-wrap items-center gap-2">
                            <span class="rounded-full border border-white/20 bg-black/30 px-3 py-1 text-[10px] uppercase tracking-[0.12em] text-white/80 backdrop-blur-sm">
                                Réservation #{{ reservationId }}
                            </span>
                            <span
                                v-if="reservation"
                                class="rounded-full border px-3 py-1 text-[10px] uppercase tracking-[0.12em] backdrop-blur-sm"
                                :class="reservation.status === 'confirmee'
                                    ? 'border-emerald-300/35 bg-emerald-500/20 text-emerald-100'
                                    : reservation.status === 'annulee'
                                        ? 'border-rose-300/35 bg-rose-500/20 text-rose-100'
                                        : 'border-amber-300/35 bg-amber-500/20 text-amber-100'"
                            >
                                {{ reservation.status }}
                            </span>
                        </div>
                        <h1 class="font-serif text-[42px] sm:text-[54px] leading-[0.95] tracking-[-0.03em] text-white">
                            {{ selectedSalle ? selectedSalle.nom : 'MODIFIER LA RÉSERVATION' }}
                        </h1>
                        <div v-if="selectedSalle" class="mt-5 flex flex-wrap items-center gap-x-6 gap-y-2 text-[11px] uppercase tracking-[0.08em] text-white/80">
                            <span class="inline-flex items-center gap-1.5">
                                <MapPin :size="13" class="text-white/70" />
                                {{ selectedSalle.localisation || 'Sur site' }}
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <Users :size="13" class="text-white/70" />
                                {{ selectedSalle.capacite }} personnes max
                            </span>
                        </div>
                        <p v-else class="mt-4 text-xs text-white/60">
                            Choisissez une salle dans le formulaire pour configurer votre événement.
                        </p>
                    </div>
                </section>

                <!-- DROITE : FORMULAIRE -->
                <section class="flex flex-col justify-between rounded-[20px] border border-[#ecebe7] bg-white p-6 sm:p-10 shadow-sm overflow-y-auto" v-scroll-reveal="{ direction: 'right', delay: 120 }">
                    <div class="mx-auto w-full max-w-[540px]">

                        <!-- CHARGEMENT INITIAL -->
                        <div v-if="isFetching" class="flex flex-col items-center justify-center py-16">
                            <Loader2 :size="36" class="animate-spin text-[#181818]" />
                            <p class="mt-4 text-[13px] text-[#777]">Chargement de la réservation...</p>
                        </div>

                        <!-- ERREUR DE CHARGEMENT -->
                        <div v-else-if="fetchError" class="rounded-[12px] border border-rose-200 bg-rose-50 p-6 text-center">
                            <AlertCircle :size="32" class="mx-auto mb-3 text-rose-600" />
                            <h3 class="font-serif text-[18px] text-rose-900">Impossible de charger la réservation</h3>
                            <p class="mt-1 text-[12px] text-rose-700">{{ fetchError }}</p>
                            <div class="mt-5">
                                <RouterLink
                                    :to="{ name: 'user-reservations' }"
                                    class="inline-flex rounded-[8px] border border-gray-300 bg-white px-5 py-2.5 text-[11px] uppercase tracking-[0.08em] text-gray-700 transition hover:bg-gray-50"
                                >
                                    Retour à la liste
                                </RouterLink>
                            </div>
                        </div>

                        <!-- RESERVATION NON MODIFIABLE -->
                        <div v-else-if="!isModifiable" class="rounded-[12px] border border-amber-200 bg-amber-50 p-6 text-center">
                            <AlertCircle :size="32" class="mx-auto mb-3 text-amber-600" />
                            <h3 class="font-serif text-[18px] text-amber-900">Réservation non modifiable</h3>
                            <p class="mt-2 text-[12px] leading-relaxed text-amber-800 max-w-xs mx-auto">
                                Cette réservation a le statut <strong class="text-amber-900">« {{ reservation?.status }} »</strong> et ne peut plus être modifiée.
                            </p>
                            <div class="mt-5">
                                <RouterLink
                                    :to="{ name: 'user-reservation-details', params: { id: reservationId } }"
                                    class="inline-flex rounded-[8px] border border-gray-300 bg-white px-5 py-2.5 text-[11px] uppercase tracking-[0.08em] text-gray-700 transition hover:bg-gray-50"
                                >
                                    Consulter la réservation
                                </RouterLink>
                            </div>
                        </div>

                        <!-- FORMULAIRE DE MODIFICATION -->
                        <form v-else @submit.prevent="handleUpdate" class="space-y-6">

                            <!-- En-tête -->
                            <div class="text-center">
                                <div class="mb-3 flex items-center justify-center gap-2 text-gray-300">
                                    <span class="h-px w-7 bg-gray-200"></span>
                                    <span class="text-[14px]">◇</span>
                                    <span class="h-px w-7 bg-gray-200"></span>
                                </div>
                                <h2 class="font-serif text-[32px] sm:text-[38px] uppercase leading-none tracking-[0.02em] text-[#191919]">
                                    Modification
                                </h2>
                                <p class="mx-auto mt-2.5 max-w-[420px] text-[13px] leading-relaxed text-[#777]">
                                    Ajustez vos dates, la salle ou les équipements de votre réservation.
                                </p>
                            </div>

                            <!-- Alerte si confirmée -->
                            <div
                                v-if="reservation.status === 'confirmee'"
                                class="flex items-start gap-3 rounded-[9px] border border-amber-200 bg-amber-50 p-4 text-[12px] text-amber-800"
                            >
                                <Info :size="16" class="text-amber-600 shrink-0 mt-0.5" />
                                <div>
                                    <p class="font-semibold text-amber-900">Attention</p>
                                    <p class="mt-0.5 leading-relaxed text-amber-800">
                                        Toute modification repositionnera cette réservation sous le statut <strong>« En attente »</strong>.
                                    </p>
                                </div>
                            </div>

                            <!-- SECTION : SALLE -->
                            <div class="space-y-2">
                                <p class="text-[12px] font-semibold uppercase tracking-[0.06em] text-[#555]">Salle</p>
                                <div class="grid grid-cols-1 gap-2.5 max-h-64 overflow-y-auto pr-1">
                                    <label
                                        v-for="salle in salles"
                                        :key="salle.id"
                                        class="flex cursor-pointer items-center gap-3 rounded-[9px] border p-3 transition-all"
                                        :class="selectedSalleId === salle.id
                                            ? 'border-[#181818] bg-gray-50'
                                            : 'border-[#ecebe7] bg-[#fafaf8] hover:border-gray-300'"
                                    >
                                        <input type="radio" :value="salle.id" v-model="selectedSalleId" class="sr-only" />
                                        <div class="h-11 w-11 shrink-0 overflow-hidden rounded-[7px] border border-gray-200 bg-gray-100">
                                            <img
                                                :src="salle.images?.[0]?.url || salle.images?.[0]?.path || defaultImage"
                                                :alt="salle.nom"
                                                class="h-full w-full object-cover"
                                            />
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-[13px] font-medium text-[#191919]">{{ salle.nom }}</p>
                                            <p class="text-[11px] text-[#777] flex items-center gap-1 mt-0.5">
                                                <MapPin :size="10" />
                                                <span class="truncate">{{ salle.localisation }}</span>
                                                <span>•</span>
                                                <Users :size="10" />
                                                <span>{{ salle.capacite }} pers.</span>
                                            </p>
                                        </div>
                                        <span v-if="selectedSalleId === salle.id" class="shrink-0 rounded-full bg-[#181818] p-0.5 text-white">
                                            <CheckCircle2 :size="14" />
                                        </span>
                                    </label>
                                </div>
                            </div>

                            <!-- SECTION : DATES -->
                            <div class="space-y-3">
                                <p class="text-[12px] font-semibold uppercase tracking-[0.06em] text-[#555]">Date & Horaire</p>
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-2 block text-[12px] font-semibold uppercase tracking-[0.04em] text-[#555]">Début</label>
                                        <input
                                            v-model="debutDateTime"
                                            type="datetime-local"
                                            required
                                            class="w-full rounded-[9px] border border-[#deddd9] bg-[#fafaf8] px-4 py-3 text-[13px] text-[#222] outline-none transition focus:border-[#181818] focus:bg-white"
                                        />
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-[12px] font-semibold uppercase tracking-[0.04em] text-[#555]">Fin</label>
                                        <input
                                            v-model="finDateTime"
                                            type="datetime-local"
                                            required
                                            class="w-full rounded-[9px] border border-[#deddd9] bg-[#fafaf8] px-4 py-3 text-[13px] text-[#222] outline-none transition focus:border-[#181818] focus:bg-white"
                                        />
                                    </div>
                                </div>

                                <!-- Vérification disponibilité -->
                                <button
                                    type="button"
                                    @click="checkCreneau"
                                    :disabled="checkingDispo"
                                    class="w-full rounded-[9px] border border-gray-300 bg-white py-3 text-[11px] uppercase tracking-[0.08em] text-[#333] transition hover:bg-gray-50 disabled:opacity-50 cursor-pointer font-medium"
                                >
                                    <span class="inline-flex items-center justify-center gap-2">
                                        <Loader2 v-if="checkingDispo" :size="14" class="animate-spin" />
                                        {{ checkingDispo ? 'Vérification en cours...' : 'Vérifier la disponibilité' }}
                                    </span>
                                </button>

                                <div
                                    v-if="dispoResult"
                                    class="rounded-[9px] border p-3.5 text-[12px]"
                                    :class="dispoResult.disponible ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800'"
                                >
                                    <div class="flex items-center gap-2">
                                        <CheckCircle2 v-if="dispoResult.disponible" :size="16" />
                                        <AlertCircle v-else :size="16" />
                                        <span>{{ dispoResult.disponible ? '✓ Créneau disponible' : '✗ Créneau indisponible' }}</span>
                                    </div>
                                </div>
                                <div v-if="dispoError" class="rounded-[9px] border border-rose-200 bg-rose-50 p-3 text-[12px] text-rose-700">
                                    {{ dispoError }}
                                </div>
                            </div>

                            <!-- SECTION : NOMBRE DE PERSONNES -->
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-[12px] font-semibold uppercase tracking-[0.04em] text-[#555]">Nombre de personnes</label>
                                    <input
                                        v-model.number="nombrePersonnes"
                                        type="number"
                                        min="1"
                                        :max="selectedSalle?.capacite || 500"
                                        class="w-full rounded-[9px] border border-[#deddd9] bg-[#fafaf8] px-4 py-3 text-[13px] text-[#222] outline-none transition focus:border-[#181818] focus:bg-white"
                                    />
                                </div>
                                <div>
                                    <label class="mb-2 block text-[12px] font-semibold uppercase tracking-[0.04em] text-[#777]">Capacité max</label>
                                    <div class="flex h-[47px] items-center rounded-[9px] border border-gray-200 bg-gray-50 px-4 text-[13px] text-[#555]">
                                        {{ selectedSalle?.capacite ? `${selectedSalle.capacite} places` : '—' }}
                                    </div>
                                </div>
                            </div>

                            <!-- SECTION : ÉQUIPEMENTS SÉLECTIONNÉS -->
                            <div class="space-y-2">
                                <p class="text-[12px] font-semibold uppercase tracking-[0.06em] text-[#555]">Équipements réservés</p>

                                <div v-if="selectedEquipements.length > 0" class="rounded-[9px] border border-[#ecebe7] bg-[#fafaf8] p-4">
                                    <p class="mb-2.5 text-[10px] uppercase tracking-[0.12em] text-[#777]">Équipements retenus</p>
                                    <div class="space-y-2">
                                        <div
                                            v-for="item in selectedEquipements"
                                            :key="item.equipement_id"
                                            class="flex items-center gap-3 rounded-[7px] border border-gray-200 bg-white px-3 py-2.5"
                                        >
                                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-[6px] border border-gray-200 text-gray-700 bg-gray-50">
                                                <Package :size="14" />
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-[12px] font-medium text-[#191919]">{{ item.nom }}</p>
                                                <p class="text-[10px] text-[#777]">Stock : {{ item.stock_total }}</p>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <button type="button" @click="decrementQty(item)" class="flex h-6 w-6 items-center justify-center rounded-full border border-gray-300 text-gray-600 hover:bg-gray-100 cursor-pointer">
                                                    <Minus :size="11" />
                                                </button>
                                                <span class="w-5 text-center text-[11px] font-semibold text-[#191919]">{{ item.quantity }}</span>
                                                <button type="button" @click="incrementQty(item)" class="flex h-6 w-6 items-center justify-center rounded-full border border-gray-300 text-gray-600 hover:bg-gray-100 cursor-pointer">
                                                    <Plus :size="11" />
                                                </button>
                                            </div>
                                            <button type="button" @click="removeEquipement(item.equipement_id)" class="flex h-6 w-6 items-center justify-center text-rose-500 hover:text-rose-700 cursor-pointer" title="Retirer">
                                                <Trash2 :size="12" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="rounded-[9px] border border-dashed border-gray-300 bg-gray-50 p-5 text-center text-[12px] text-[#777]">
                                    Aucun équipement supplémentaire sélectionné.
                                </div>
                            </div>

                            <!-- SECTION : AJOUTER DES ÉQUIPEMENTS -->
                            <div v-if="availableEquipementsToAdd.length > 0" class="space-y-2">
                                <p class="text-[12px] font-semibold uppercase tracking-[0.06em] text-[#555]">Ajouter un équipement</p>
                                <div class="space-y-2">
                                    <div
                                        v-for="eq in availableEquipementsToAdd"
                                        :key="eq.id"
                                        class="flex items-center gap-3.5 rounded-[9px] border border-[#ecebe7] bg-[#fafaf8] p-3.5 transition hover:border-gray-300"
                                    >
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[7px] border border-gray-200 bg-white text-gray-700">
                                            <Package :size="16" />
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-[13px] font-medium text-[#191919]">{{ eq.nom }}</p>
                                            <p class="mt-0.5 text-[10px] uppercase tracking-[0.07em] text-[#777]">Stock : {{ eq.stock_total || 'Disponible' }}</p>
                                        </div>
                                        <button type="button" @click="addEquipement(eq)" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-gray-300 text-gray-700 transition hover:bg-gray-100 cursor-pointer" title="Ajouter">
                                            <Plus :size="14" />
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- ERREUR DE SOUMISSION -->
                            <div v-if="submitError" class="rounded-[9px] border border-rose-200 bg-rose-50 p-3.5 text-[12px] text-rose-700">
                                <div class="flex items-start gap-2">
                                    <AlertCircle :size="15" class="mt-0.5 shrink-0" />
                                    <span>{{ submitError }}</span>
                                </div>
                            </div>

                            <!-- ACTIONS -->
                            <div class="grid grid-cols-2 gap-3 pt-2">
                                <RouterLink
                                    :to="{ name: 'user-reservation-details', params: { id: reservationId } }"
                                    class="flex items-center justify-center rounded-[9px] border border-gray-300 bg-white py-3 text-[11px] uppercase tracking-[0.08em] text-[#555] transition hover:bg-gray-50"
                                >
                                    Annuler
                                </RouterLink>
                                <button
                                    type="submit"
                                    :disabled="submitting"
                                    class="rounded-[9px] bg-[#181818] py-3 text-[11px] font-semibold uppercase tracking-[0.08em] text-white transition hover:bg-black disabled:opacity-55 cursor-pointer"
                                >
                                    <span class="inline-flex items-center justify-center gap-2">
                                        <Loader2 v-if="submitting" :size="14" class="animate-spin" />
                                        <Save v-else :size="14" />
                                        {{ submitting ? 'Enregistrement...' : 'Enregistrer' }}
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </section>
            </div>
        </main>

        <Footer />
    </div>
</template>
