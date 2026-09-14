<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import NavBar from '@/layouts/NavBar.vue'
import Footer from '@/layouts/Footer.vue'
import { useSallesStore } from '@/store/salles'
import { useEquipementsStore } from '@/store/equipements'
import { useReservationsStore } from '@/store/reservations'
import { toApiDateTime, formatDateTime, parseLocalDate } from '@/helpers/dateHelper'
import {
    ArrowLeft,
    CheckCircle2,
    AlertCircle,
    Loader2,
    Building2,
    Package,
    ClipboardCheck,
    Calendar,
    Users,
    MapPin,
    Plus,
    Minus,
    Trash2,
    BadgeCheck,
    Clock,
    Sparkles,
} from 'lucide-vue-next'

// ─── Stores ─────────────────────────────────────────────────────────────────
const sallesStore = useSallesStore()
const equipementsStore = useEquipementsStore()
const reservationsStore = useReservationsStore()

// ─── Router ─────────────────────────────────────────────────────────────────
const route = useRoute()
const router = useRouter()

// ─── State ──────────────────────────────────────────────────────────────────
const currentStep = ref(1)
const STEPS = [
    { id: 1, label: 'La salle', icon: Building2 },
    { id: 2, label: 'Équipements', icon: Package },
    { id: 3, label: 'Récapitulatif', icon: ClipboardCheck },
]

// ── Step 1 : Salle & Dates
const selectedSalleId = ref(route.query.salle_id ? Number(route.query.salle_id) : null)
const isFixedSalle = computed(() => !!route.query.salle_id)
const directFetchedSalle = ref(null)
const debutDateTime = ref(route.query.debut || '')
const finDateTime = ref(route.query.fin || '')
const nombrePersonnes = ref(1)
const step1Error = ref(null)
const checkingDispo = ref(false)
const dispoResult = ref(null)

// ── Step 2 : Équipements (optionnel)
const selectedEquipements = ref([]) // [{equipement_id, quantity, nom, stock_total}]

// ── Step 3 : Soumission
const submitting = computed(() => reservationsStore.submitting)
const submitError = ref(null)
const submitSuccess = ref(false)
const newReservationId = ref(null)

// ─── Computed ───────────────────────────────────────────────────────────────
const salles = computed(() => sallesStore.salles || [])
const equipements = computed(() => equipementsStore.equipements || [])

const selectedSalle = computed(() => {
    if (!selectedSalleId.value) return null
    return (
        salles.value.find((s) => Number(s.id) === Number(selectedSalleId.value)) ||
        directFetchedSalle.value ||
        null
    )
})

const defaultPlaceholder =
    'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1200&q=80'

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
    if (!selectedSalle.value) return defaultPlaceholder
    const imgs = selectedSalle.value.images
    if (imgs && imgs.length > 0) {
        const index = selectedSalleImageIndex.value % imgs.length
        return imgs[index].url || imgs[index].path || defaultPlaceholder
    }
    return defaultPlaceholder
})

const formatPrice = (p) =>
    p != null ? new Intl.NumberFormat('fr-FR').format(p) + ' FCFA' : 'Sur demande'

// Durée en heures entre début et fin
const dureHeures = computed(() => {
    if (!debutDateTime.value || !finDateTime.value) return 0
    const diff = parseLocalDate(finDateTime.value) - parseLocalDate(debutDateTime.value)
    return Math.max(0, diff / 1000 / 3600)
})

// Affichage de la durée : minutes si < 1h, sinon heures
const dureLabel = computed(() => {
    if (!debutDateTime.value || !finDateTime.value) return '—'
    const diff = parseLocalDate(finDateTime.value) - parseLocalDate(debutDateTime.value)
    const totalMinutes = Math.round(Math.max(0, diff / 60000))
    if (totalMinutes <= 0) return '0 min'
    if (totalMinutes < 60) return `${totalMinutes} min`
    const hours = Math.floor(totalMinutes / 60)
    const minutes = totalMinutes % 60
    if (minutes === 0) return `${hours} heure${hours > 1 ? 's' : ''}`
    return `${hours}h ${minutes}min`
})

// Équipements déjà sélectionnés
const selectedEquipementIds = computed(() =>
    selectedEquipements.value.map((e) => e.equipement_id)
)

// ─── Step 1 : validation & dispo ───────────────────────────────────────────
const verifierDisponibilite = async () => {
    step1Error.value = null
    dispoResult.value = null

    if (!selectedSalleId.value) {
        step1Error.value = 'Veuillez choisir une salle.'
        return
    }
    if (!debutDateTime.value || !finDateTime.value) {
        step1Error.value = 'Veuillez renseigner les dates de début et de fin.'
        return
    }
    if (new Date(debutDateTime.value) >= new Date(finDateTime.value)) {
        step1Error.value = 'La date de fin doit être après la date de début.'
        return
    }

    checkingDispo.value = true
    try {
        const result = await sallesStore.checkDisponibilite(
            selectedSalleId.value,
            toApiDateTime(debutDateTime.value),
            toApiDateTime(finDateTime.value)
        )
        dispoResult.value = result
    } catch (e) {
        step1Error.value = e.message || 'Erreur lors de la vérification.'
    } finally {
        checkingDispo.value = false
    }
}

// ─── Réactivité Query (arrive depuis Salle Info) ────────────────────────────
watch(
    () => route.query,
    async (query) => {
        if (query.salle_id) {
            selectedSalleId.value = Number(query.salle_id)
            try {
                directFetchedSalle.value = await sallesStore.fetchSalle(selectedSalleId.value)
            } catch (e) {
                console.warn('Erreur chargement direct salle:', e)
            }
        }
        if (query.debut) {
            debutDateTime.value = query.debut
        }
        if (query.fin) {
            finDateTime.value = query.fin
        }
        if (selectedSalleId.value && debutDateTime.value && finDateTime.value) {
            await verifierDisponibilite()
        }
    },
    { deep: true }
)

// Quand la salle change, réinitialiser l'état de dispo et redémarrer le carrousel
watch(selectedSalleId, () => {
    dispoResult.value = null
    step1Error.value = null
    selectedSalleImageIndex.value = 0
    stopAutoPlay()
    startAutoPlay()
})

// ─── Initialisation ─────────────────────────────────────────────────────────
onMounted(async () => {
    // Initialiser les dates si pas déjà fournies
    if (!debutDateTime.value || !finDateTime.value) {
        const tomorrow = new Date()
        tomorrow.setDate(tomorrow.getDate() + 1)
        const y = tomorrow.getFullYear()
        const m = String(tomorrow.getMonth() + 1).padStart(2, '0')
        const d = String(tomorrow.getDate()).padStart(2, '0')
        debutDateTime.value = `${y}-${m}-${d}T09:00`
        finDateTime.value = `${y}-${m}-${d}T12:00`
    }

    try {
        const promises = [
            sallesStore.fetchSalles(),
            equipementsStore.fetchEquipements(),
        ]
        if (selectedSalleId.value) {
            promises.push(sallesStore.fetchSalle(selectedSalleId.value))
        }
        const results = await Promise.all(promises)
        if (selectedSalleId.value && results[2]) {
            directFetchedSalle.value = results[2]
        }
    } catch (e) {
        console.error('Erreur chargement salles/équipements:', e)
    }

    // Si salle_id était déjà dans les queries, lancer la vérification
    if (selectedSalleId.value && debutDateTime.value && finDateTime.value) {
        await verifierDisponibilite()
    }

    // Démarrer le carrousel automatique
    startAutoPlay()
})

onUnmounted(() => {
    // Arrêter le carrousel automatique lors de la destruction du composant
    stopAutoPlay()
})

const goStep2 = () => {
    step1Error.value = null
    if (!selectedSalleId.value) {
        step1Error.value = 'Veuillez choisir une salle.'
        return
    }
    if (!debutDateTime.value || !finDateTime.value) {
        step1Error.value = 'Veuillez renseigner les dates de début et de fin.'
        return
    }
    if (new Date(debutDateTime.value) >= new Date(finDateTime.value)) {
        step1Error.value = 'La date de fin doit être postérieure à la date de début.'
        return
    }
    if (!nombrePersonnes.value || nombrePersonnes.value < 1) {
        step1Error.value = 'Le nombre de personnes doit être supérieur à 0.'
        return
    }
    if (selectedSalle.value?.capacite && nombrePersonnes.value > selectedSalle.value.capacite) {
        step1Error.value = `Cette salle a une capacité maximale de ${selectedSalle.value.capacite} personnes.`
        return
    }
    currentStep.value = 2
}

// ─── Step 2 : Équipements ───────────────────────────────────────────────────
const addEquipement = (eq) => {
    if (selectedEquipementIds.value.includes(eq.id)) return
    selectedEquipements.value.push({
        equipement_id: eq.id,
        nom: eq.nom,
        stock_total: eq.stock_total || 1,
        quantity: 1,
    })
}

const removeEquipement = (equipementId) => {
    selectedEquipements.value = selectedEquipements.value.filter(
        (e) => e.equipement_id !== equipementId
    )
}

const incrementQty = (item) => {
    if (item.quantity < item.stock_total) item.quantity++
}

const decrementQty = (item) => {
    if (item.quantity > 1) item.quantity--
}

// ─── Step 3 : Soumission ────────────────────────────────────────────────────
const submitReservation = async () => {
    submitError.value = null
    const payload = {
        salle_id: selectedSalleId.value,
        date_heure_debut: toApiDateTime(debutDateTime.value),
        date_heure_fin: toApiDateTime(finDateTime.value),
        nombre_personnes: nombrePersonnes.value,
    }

    if (selectedEquipements.value.length > 0) {
        payload.equipements = selectedEquipements.value.map((e) => ({
            equipement_id: e.equipement_id,
            quantity: e.quantity,
        }))
    }

    try {
        const result = await reservationsStore.createReservation(payload)
        newReservationId.value = result?.id ?? null
        submitSuccess.value = true
        if (result?.id) {
            router.push({ name: 'user-reservation-details', params: { id: result.id } })
        } else {
            router.push({ name: 'user-reservations' })
        }
    } catch (e) {
        submitError.value =
            reservationsStore.errorMessage ||
            Object.values(reservationsStore.validationErrors).flat().join(' — ') ||
            'Une erreur est survenue lors de la réservation.'
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
              :to="{ name: 'salles' }"
              class="inline-flex items-center gap-2 rounded-full border border-white/25 bg-black/40 px-3.5 py-1.5 text-[11px] uppercase tracking-[0.08em] text-white backdrop-blur-md transition hover:bg-white/10"
            >
              <ArrowLeft :size="13" />
              <span>Retour aux salles</span>
            </RouterLink>

            <div class="inline-flex items-center gap-1.5 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-[11px] text-white/90 backdrop-blur-md">
              <Sparkles :size="12" />
              <span>Espace Professionnel</span>
            </div>
          </div>

          <!-- Pied gauche -->
          <div class="relative z-10 mt-auto pt-16">
            <div class="mb-3 flex flex-wrap items-center gap-2">
              <span class="rounded-full border border-white/20 bg-black/30 px-3 py-1 text-[10px] uppercase tracking-[0.12em] text-white/80 backdrop-blur-sm">
                Réservation
              </span>
              <span
                v-if="selectedSalle"
                class="rounded-full border px-3 py-1 text-[10px] uppercase tracking-[0.12em] backdrop-blur-sm"
                :class="selectedSalle.status && selectedSalle.status.toLowerCase() !== 'disponible'
                  ? 'border-rose-300/35 bg-rose-500/20 text-rose-100'
                  : 'border-emerald-300/35 bg-emerald-500/20 text-emerald-100'"
              >
                {{ selectedSalle.status || 'Disponible' }}
              </span>
            </div>

            <h1 class="font-serif text-[42px] sm:text-[54px] leading-[0.95] tracking-[-0.03em] text-white">
              {{ selectedSalle ? selectedSalle.nom : 'RÉSERVER UNE SALLE' }}
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
              <span class="text-white font-semibold">
                {{ formatPrice(selectedSalle.prix) }}
              </span>
            </div>
            <p v-else class="mt-4 text-xs text-white/60">
              Choisissez une salle dans le formulaire pour configurer votre événement.
            </p>
          </div>
        </section>

        <!-- DROITE : STEPPER ET FORMULAIRE (FOND BLANC) -->
        <section class="flex flex-col justify-between rounded-[20px] border border-[#ecebe7] bg-white p-6 sm:p-10 shadow-sm" v-scroll-reveal="{ direction: 'right', delay: 120 }">
          <div class="mx-auto w-full max-w-[540px]">
            <!-- Stepper -->
            <div class="mb-8 flex items-center gap-2">
              <template v-for="(step, idx) in STEPS" :key="step.id">
                <button
                  type="button"
                  @click="currentStep >= step.id ? (currentStep = step.id) : null"
                  class="flex items-center gap-2 text-[10px] uppercase tracking-[0.1em] transition"
                  :class="currentStep === step.id ? 'text-[#191919] font-bold' : 'text-[#888] hover:text-[#222]'"
                >
                  <span
                    class="flex h-6 w-6 items-center justify-center rounded-full border text-[9px] font-semibold"
                    :class="
                      currentStep > step.id
                        ? 'border-emerald-600 bg-emerald-600 text-white'
                        : currentStep === step.id
                          ? 'border-[#181818] bg-[#181818] text-white'
                          : 'border-gray-300 text-gray-400 bg-gray-50'
                    "
                  >
                    <CheckCircle2 v-if="currentStep > step.id" :size="11" />
                    <span v-else>{{ step.id }}</span>
                  </span>
                  <span class="hidden sm:inline">{{ step.label }}</span>
                </button>
                <div v-if="idx < STEPS.length - 1" class="h-px flex-1 bg-gray-200"></div>
              </template>
            </div>

            <!-- ÉTAPE 1 : SALLE & DATES -->
            <div v-if="currentStep === 1">
              <div class="text-center">
                <h2 class="font-serif text-[30px] sm:text-[36px] tracking-[-0.03em] text-[#191919]">
                  Réservation
                </h2>
                <p class="mt-1 text-xs text-[#777]">
                  Sélectionnez vos dates et le nombre de participants.
                </p>
              </div>

              <div class="mt-8 space-y-5">
                <!-- Salle fixée -->
                <div v-if="isFixedSalle">
                  <div class="mb-2 flex items-center justify-between">
                    <label class="block text-[12px] font-medium text-[#444]">
                      Salle sélectionnée
                    </label>
                  </div>

                  <div v-if="selectedSalle" class="rounded-[11px] border border-[#ecebe7] bg-[#fafaf8] p-4">
                    <div class="flex items-center gap-3.5 min-w-0">
                      <div class="h-12 w-12 shrink-0 overflow-hidden rounded-[8px] border border-gray-200 bg-gray-100">
                        <img :src="salleCoverUrl" :alt="selectedSalle.nom" class="h-full w-full object-cover" />
                      </div>
                      <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                          <span class="rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200 px-2 py-0.5 text-[9px] uppercase tracking-wider font-bold">
                            Sélectionnée
                          </span>
                          <span class="text-[11px] text-[#191919] font-bold">
                            {{ formatPrice(selectedSalle.prix) }}
                          </span>
                        </div>
                        <p class="mt-1 text-[14px] font-semibold text-[#191919] truncate">
                          {{ selectedSalle.nom }}
                        </p>
                        <p class="text-[11px] text-[#777] flex items-center gap-1.5 mt-0.5 truncate">
                          <MapPin :size="11" />
                          <span>{{ selectedSalle.localisation || 'Sur site' }}</span>
                          <span>•</span>
                          <Users :size="11" />
                          <span>{{ selectedSalle.capacite }} pers. max</span>
                        </p>
                      </div>
                    </div>
                  </div>

                  <div v-else class="flex items-center gap-2 rounded-[11px] border border-gray-200 bg-gray-50 p-4 text-xs text-gray-500">
                    <Loader2 :size="14" class="animate-spin text-gray-800" />
                    <span>Chargement des détails de la salle...</span>
                  </div>
                </div>

                <!-- Sélection libre -->
                <div v-else>
                  <label class="mb-1.5 block text-[12px] font-medium text-[#444]">
                    Choisir une salle
                  </label>
                  <select
                    v-model="selectedSalleId"
                    class="w-full rounded-[9px] border border-[#deddd9] bg-[#fafaf8] px-4 py-3 text-[13px] text-[#222] outline-none transition focus:border-[#181818] focus:bg-white"
                  >
                    <option :value="null">Sélectionnez une salle</option>
                    <option
                      v-for="salle in salles"
                      :key="salle.id"
                      :value="Number(salle.id)"
                    >
                      {{ salle.nom }} — {{ formatPrice(salle.prix) }}
                    </option>
                  </select>
                </div>

                <!-- Début & Fin -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                  <div>
                    <label class="mb-1.5 block text-[12px] font-medium text-[#444]">
                      Début
                    </label>
                    <input
                      v-model="debutDateTime"
                      type="datetime-local"
                      class="w-full rounded-[9px] border border-[#deddd9] bg-[#fafaf8] px-3.5 py-2.5 text-[12px] text-[#222] outline-none transition focus:border-[#181818] focus:bg-white"
                    />
                  </div>

                  <div>
                    <label class="mb-1.5 block text-[12px] font-medium text-[#444]">
                      Fin
                    </label>
                    <input
                      v-model="finDateTime"
                      type="datetime-local"
                      class="w-full rounded-[9px] border border-[#deddd9] bg-[#fafaf8] px-3.5 py-2.5 text-[12px] text-[#222] outline-none transition focus:border-[#181818] focus:bg-white"
                    />
                  </div>
                </div>

                <!-- Personnes & Capacité -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                  <div>
                    <label class="mb-1.5 block text-[12px] font-medium text-[#444]">
                      Nombre de personnes
                    </label>
                    <input
                      v-model.number="nombrePersonnes"
                      type="number"
                      min="1"
                      :max="selectedSalle?.capacite || 999"
                      placeholder="Ex: 5"
                      class="w-full rounded-[9px] border border-[#deddd9] bg-[#fafaf8] px-3.5 py-2.5 text-[12px] text-[#222] outline-none transition focus:border-[#181818] focus:bg-white"
                    />
                  </div>

                  <div>
                    <label class="mb-1.5 block text-[12px] font-medium text-[#777]">
                      Capacité max
                    </label>
                    <div class="flex h-[43px] items-center rounded-[9px] border border-gray-200 bg-gray-50 px-3.5 text-[12px] text-[#555]">
                      {{ selectedSalle?.capacite ? `${selectedSalle.capacite} places` : '—' }}
                    </div>
                  </div>
                </div>

                <!-- Vérifier disponibilité -->
                <div>
                  <button
                    type="button"
                    @click="verifierDisponibilite"
                    :disabled="checkingDispo"
                    class="w-full rounded-[9px] border border-gray-300 bg-white py-2.5 text-[12px] font-semibold text-gray-800 transition hover:bg-gray-50 disabled:opacity-50 cursor-pointer"
                  >
                    <span class="inline-flex items-center justify-center gap-2">
                      <Loader2 v-if="checkingDispo" :size="14" class="animate-spin" />
                      {{ checkingDispo ? 'Vérification en cours...' : 'Vérifier la disponibilité du créneau' }}
                    </span>
                  </button>
                </div>

                <!-- Feedback disponibilité -->
                <div
                  v-if="dispoResult"
                  class="rounded-[9px] border p-3 text-[12px]"
                  :class="dispoResult.disponible ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800'"
                >
                  <div class="flex items-center gap-2">
                    <CheckCircle2 v-if="dispoResult.disponible" :size="16" />
                    <AlertCircle v-else :size="16" />
                    <span>
                      {{ dispoResult.disponible ? 'Créneau 100% disponible ! Vous pouvez continuer.' : 'Ce créneau est déjà réservé. Veuillez choisir une autre plage.' }}
                    </span>
                  </div>
                </div>

                <!-- Erreur Step 1 -->
                <div v-if="step1Error" class="rounded-[9px] border border-rose-200 bg-rose-50 p-3 text-[12px] text-rose-700">
                  <div class="flex items-start gap-2">
                    <AlertCircle :size="15" class="mt-0.5 shrink-0" />
                    <span>{{ step1Error }}</span>
                  </div>
                </div>

                <!-- Bouton continuer -->
                <button
                  type="button"
                  @click="goStep2"
                  class="mt-2 w-full rounded-[9px] bg-[#181818] py-3.5 text-[12px] font-semibold text-white transition hover:bg-black cursor-pointer"
                >
                  Étape suivante : Équipements
                </button>
              </div>
            </div>

            <!-- ÉTAPE 2 : ÉQUIPEMENTS -->
            <div v-else-if="currentStep === 2">
              <div class="text-center">
                <h2 class="font-serif text-[30px] sm:text-[36px] tracking-[-0.03em] text-[#191919]">
                  Équipements
                </h2>
                <p class="mx-auto mt-1 max-w-[420px] text-[12px] text-[#777]">
                  Ajoutez des équipements optionnels selon les besoins de votre événement.
                </p>
              </div>

              <div class="mt-8 space-y-3">
                <div v-if="equipementsStore.loading" class="rounded-[9px] border border-gray-200 bg-gray-50 p-6 text-center text-[12px] text-gray-500">
                  <Loader2 :size="18" class="mx-auto mb-2 animate-spin text-gray-800" />
                  Chargement des équipements...
                </div>

                <div
                  v-else-if="equipements.length === 0"
                  class="rounded-[9px] border border-gray-200 bg-gray-50 p-6 text-center text-[12px] text-gray-500"
                >
                  Aucun équipement disponible pour le moment.
                </div>

                <div
                  v-else
                  v-for="eq in equipements"
                  :key="eq.id"
                  class="flex items-center gap-3.5 rounded-[9px] border p-3.5 transition"
                  :class="selectedEquipementIds.includes(eq.id) ? 'border-[#181818] bg-gray-50' : 'border-[#ecebe7] bg-[#fafaf8]'"
                >
                  <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[7px] border border-gray-200 bg-white text-gray-700">
                    <Package :size="16" />
                  </div>

                  <div class="min-w-0 flex-1">
                    <p class="truncate text-[13px] font-medium text-[#222]">{{ eq.nom }}</p>
                    <p class="mt-0.5 text-[10px] text-[#777]">
                      Stock disponible : {{ eq.stock_total || 'Disponible' }}
                    </p>
                  </div>

                  <button
                    v-if="!selectedEquipementIds.includes(eq.id)"
                    type="button"
                    @click="addEquipement(eq)"
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-gray-300 text-gray-700 hover:bg-gray-100 cursor-pointer"
                    title="Ajouter cet équipement"
                  >
                    <Plus :size="14" />
                  </button>
                  <span v-else class="shrink-0 text-[10px] uppercase font-bold text-emerald-700">
                    Ajouté
                  </span>
                </div>
              </div>

              <!-- Liste des équipements choisis -->
              <div v-if="selectedEquipements.length > 0" class="mt-6 rounded-[9px] border border-[#ecebe7] bg-[#fafaf8] p-4">
                <p class="mb-2.5 text-[11px] font-semibold uppercase tracking-wide text-[#777]">Équipements sélectionnés</p>
                <div class="space-y-2">
                  <div
                    v-for="item in selectedEquipements"
                    :key="item.equipement_id"
                    class="flex items-center gap-3 rounded-[7px] border border-gray-200 bg-white px-3 py-2.5"
                  >
                    <p class="min-w-0 flex-1 truncate text-[12px] font-medium text-[#222]">{{ item.nom }}</p>
                    <div class="flex items-center gap-2">
                      <button
                        type="button"
                        @click="decrementQty(item)"
                        class="flex h-6 w-6 items-center justify-center rounded-full border border-gray-200 text-gray-600 hover:bg-gray-100 cursor-pointer"
                      >
                        <Minus :size="11" />
                      </button>
                      <span class="w-5 text-center text-[12px] font-bold text-[#222]">{{ item.quantity }}</span>
                      <button
                        type="button"
                        @click="incrementQty(item)"
                        class="flex h-6 w-6 items-center justify-center rounded-full border border-gray-200 text-gray-600 hover:bg-gray-100 cursor-pointer"
                      >
                        <Plus :size="11" />
                      </button>
                    </div>
                    <button
                      type="button"
                      @click="removeEquipement(item.equipement_id)"
                      class="flex h-6 w-6 items-center justify-center text-rose-500 hover:text-rose-700 cursor-pointer"
                      title="Retirer"
                    >
                      <Trash2 :size="13" />
                    </button>
                  </div>
                </div>
              </div>

              <div class="mt-7 grid grid-cols-2 gap-3">
                <button
                  type="button"
                  @click="currentStep = 1"
                  class="rounded-[9px] border border-gray-300 bg-white py-3 text-[12px] font-semibold text-gray-700 hover:bg-gray-50 transition cursor-pointer"
                >
                  Retour
                </button>
                <button
                  type="button"
                  @click="currentStep = 3"
                  class="rounded-[9px] bg-[#181818] py-3 text-[12px] font-semibold text-white hover:bg-black transition cursor-pointer"
                >
                  Continuer
                </button>
              </div>
            </div>

            <!-- ÉTAPE 3 : RÉCAPITULATIF & SOUMISSION -->
            <div v-else>
              <div v-if="submitSuccess" class="text-center py-6">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                  <BadgeCheck :size="34" />
                </div>
                <h2 class="mt-5 font-serif text-[30px] sm:text-[36px] tracking-[-0.03em] text-[#191919]">
                  Réservation envoyée !
                </h2>
                <p class="mx-auto mt-3 max-w-[400px] text-[13px] text-[#777] leading-relaxed">
                  Votre demande a été enregistrée avec succès. Notre équipe va examiner votre créneau.
                </p>
                <RouterLink
                  :to="{ name: 'user-reservations' }"
                  class="mt-7 inline-flex rounded-[9px] bg-[#181818] px-6 py-3 text-[12px] font-semibold text-white hover:bg-black transition"
                >
                  Consulter mes réservations
                </RouterLink>
              </div>

              <div v-else>
                <div class="text-center">
                  <h2 class="font-serif text-[30px] sm:text-[36px] tracking-[-0.03em] text-[#191919]">
                    Confirmation
                  </h2>
                  <p class="mx-auto mt-1 max-w-[420px] text-[12px] text-[#777]">
                    Vérifiez le récapitulatif de votre réservation avant de valider.
                  </p>
                </div>

                <div class="mt-7 overflow-hidden rounded-[12px] border border-[#ecebe7] bg-[#fafaf8]">
                  <div class="flex items-stretch border-b border-[#ecebe7]">
                    <div class="h-[96px] w-[96px] shrink-0 overflow-hidden">
                      <img :src="salleCoverUrl" :alt="selectedSalle?.nom" class="h-full w-full object-cover" />
                    </div>
                    <div class="min-w-0 flex-1 p-3.5">
                      <p class="text-[10px] uppercase tracking-wide font-medium text-[#777]">Salle sélectionnée</p>
                      <p class="mt-1 truncate text-[15px] font-semibold text-[#191919]">{{ selectedSalle?.nom || '—' }}</p>
                      <p class="mt-1 flex items-center gap-1.5 truncate text-[11px] text-[#777]">
                        <MapPin :size="12" />
                        {{ selectedSalle?.localisation || 'Sur site' }}
                      </p>
                    </div>
                  </div>

                  <div class="p-4">
                    <dl class="space-y-2.5 text-[12px]">
                      <div class="flex items-center justify-between gap-4">
                        <dt class="inline-flex items-center gap-1.5 text-[#777]">
                          <Calendar :size="13" /> Début
                        </dt>
                        <dd class="text-right font-medium text-[#222]">{{ formatDateTime(debutDateTime) }}</dd>
                      </div>
                      <div class="flex items-center justify-between gap-4">
                        <dt class="inline-flex items-center gap-1.5 text-[#777]">
                          <Calendar :size="13" /> Fin
                        </dt>
                        <dd class="text-right font-medium text-[#222]">{{ formatDateTime(finDateTime) }}</dd>
                      </div>
                      <div class="flex items-center justify-between gap-4">
                        <dt class="inline-flex items-center gap-1.5 text-[#777]">
                          <Users :size="13" /> Personnes
                        </dt>
                        <dd class="text-right font-medium text-[#222]">{{ nombrePersonnes }} personnes</dd>
                      </div>
                      <div class="flex items-center justify-between gap-4">
                        <dt class="inline-flex items-center gap-1.5 text-[#777]">
                          <Clock :size="13" /> Durée
                        </dt>
                        <dd class="text-right font-medium text-[#222]">{{ dureLabel }}</dd>
                      </div>
                    </dl>

                    <div v-if="selectedEquipements.length > 0" class="mt-4 border-t border-[#ecebe7] pt-3">
                      <p class="mb-2 text-[10px] uppercase font-bold tracking-wide text-[#777]">Équipements inclus</p>
                      <div class="space-y-1.5">
                        <div
                          v-for="item in selectedEquipements"
                          :key="item.equipement_id"
                          class="flex items-center justify-between gap-3 rounded-[6px] border border-gray-200 bg-white px-3 py-1.5 text-[12px]"
                        >
                          <span class="truncate text-[#333]">{{ item.nom }}</span>
                          <span class="shrink-0 font-bold text-[#191919]">× {{ item.quantity }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div v-if="submitError" class="mt-4 rounded-[9px] border border-rose-200 bg-rose-50 p-3.5 text-[12px] text-rose-700">
                  <div class="flex items-start gap-2">
                    <AlertCircle :size="15" class="mt-0.5 shrink-0" />
                    <span>{{ submitError }}</span>
                  </div>
                </div>

                <div class="mt-6 grid grid-cols-2 gap-3">
                  <button
                    type="button"
                    @click="currentStep = 2"
                    class="rounded-[9px] border border-gray-300 bg-white py-3 text-[12px] font-semibold text-gray-700 hover:bg-gray-50 transition cursor-pointer"
                  >
                    Retour
                  </button>
                  <button
                    type="button"
                    @click="submitReservation"
                    :disabled="submitting"
                    class="rounded-[9px] bg-[#181818] py-3 text-[12px] font-semibold text-white hover:bg-black transition cursor-pointer disabled:opacity-55"
                  >
                    <span class="inline-flex items-center justify-center gap-2">
                      <Loader2 v-if="submitting" :size="14" class="animate-spin" />
                      {{ submitting ? 'Envoi en cours...' : 'Confirmer la réservation' }}
                    </span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>
    </main>

    <Footer />
  </div>
</template>
