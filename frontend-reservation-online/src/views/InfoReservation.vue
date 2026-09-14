<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import NavBar from '@/layouts/NavBar.vue'
import Footer from '@/layouts/Footer.vue'
import { useReservationsStore } from '@/store/reservations'
import {
  ArrowLeft,
  Calendar,
  Clock,
  MapPin,
  Users,
  Package,
  CheckCircle2,
  Clock3,
  XCircle,
  Ban,
  Check,
  AlertCircle,
  Loader2,
  Sparkles,
  Pencil,
  Building2,
  Phone,
  Mail,
  User as UserIcon,
} from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const reservationsStore = useReservationsStore()

const reservationId = route.params.id
const reservation = ref(null)
const isFetching = ref(true)
const fetchError = ref(null)

// Index de la photo sélectionnée
const selectedPhotoIndex = ref(0)

// Modal d'annulation
const isCancelModalOpen = ref(false)
const isCancelling = ref(false)
const cancelError = ref(null)

const defaultImage =
  'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1200&q=80'

onMounted(async () => {
  await loadReservation()
})

const loadReservation = async () => {
  isFetching.value = true
  fetchError.value = null
  try {
    const data = await reservationsStore.fetchReservation(reservationId)
    reservation.value = data
  } catch (err) {
    fetchError.value =
      reservationsStore.errorMessage ||
      'Impossible de charger les détails de cette réservation.'
  } finally {
    isFetching.value = false
  }
}

// Galerie d'images
const imagesList = computed(() => {
  if (reservation.value?.salle?.images && reservation.value.salle.images.length > 0) {
    return reservation.value.salle.images.map((img) => img.url || img.path || defaultImage)
  }
  return [defaultImage]
})

const activeImage = computed(() => {
  return imagesList.value[selectedPhotoIndex.value] || imagesList.value[0]
})

// Utilitaires de formatage
const formatDateOnly = (dateStr) => {
  if (!dateStr) return 'N/A'
  try {
    return new Intl.DateTimeFormat('fr-FR', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    }).format(new Date(dateStr))
  } catch {
    return dateStr
  }
}

const formatTimeOnly = (dateStr) => {
  if (!dateStr) return ''
  try {
    return new Intl.DateTimeFormat('fr-FR', {
      hour: '2-digit',
      minute: '2-digit',
    }).format(new Date(dateStr))
  } catch {
    return ''
  }
}

// Calcul de la durée
const durationText = computed(() => {
  if (!reservation.value?.date_heure_debut || !reservation.value?.date_heure_fin) return ''
  const debut = new Date(reservation.value.date_heure_debut)
  const fin = new Date(reservation.value.date_heure_fin)
  const diffMs = fin - debut
  if (diffMs <= 0) return '0 min'
  const totalMinutes = Math.round(diffMs / 60000)
  // Moins d'une heure → afficher uniquement en minutes
  if (totalMinutes < 60) return `${totalMinutes} min`
  const hours = Math.floor(totalMinutes / 60)
  const minutes = totalMinutes % 60
  if (minutes === 0) return `${hours} heure${hours > 1 ? 's' : ''}`
  return `${hours}h ${minutes}min`
})

// Modal d'annulation
const openCancelModal = () => {
  cancelError.value = null
  isCancelModalOpen.value = true
}

const closeCancelModal = () => {
  if (isCancelling.value) return
  isCancelModalOpen.value = false
  cancelError.value = null
}

const confirmCancel = async () => {
  isCancelling.value = true
  cancelError.value = null
  try {
    await reservationsStore.cancelReservation(reservationId)
    isCancelling.value = false
    isCancelModalOpen.value = false
    cancelError.value = null
    await loadReservation()
  } catch (err) {
    cancelError.value =
      reservationsStore.errorMessage ||
      "Impossible d'annuler cette réservation."
    isCancelling.value = false
  }
}
</script>

<template>
  <div class="min-h-[80vh] bg-[#f6f6f4] text-[#151515]">
    <!-- NAVBAR -->
    <NavBar />

    <main class="px-4 pt-28 py-10 sm:px-6 lg:px-10">
      <div class="mx-auto max-w-[1180px]">
        <!-- NAVIGATION RETOUR -->
        <div class="mb-7">
          <RouterLink
            :to="{ name: 'user-reservations' }"
            class="inline-flex items-center gap-2 text-[12px] font-medium text-[#777] transition hover:text-[#222]"
          >
            <ArrowLeft :size="14" />
            <span>Retour à mes réservations</span>
          </RouterLink>
        </div>

        <!-- CHARGEMENT -->
        <div
          v-if="isFetching"
          class="flex min-h-[420px] items-center justify-center rounded-[14px] bg-white"
        >
          <div class="flex flex-col items-center gap-3">
            <Loader2 :size="34" class="animate-spin text-slate-800" />
            <p class="text-sm text-[#777]">Chargement des détails de votre réservation...</p>
          </div>
        </div>

        <!-- ERREUR -->
        <div
          v-else-if="fetchError"
          class="rounded-[14px] border border-[#eaded9] bg-white p-10 text-center"
        >
          <AlertCircle :size="42" class="mx-auto mb-3 text-slate-800" />
          <h2 class="text-xl font-semibold text-[#191919]">Réservation introuvable</h2>
          <p class="mt-2 text-sm text-[#777]">{{ fetchError }}</p>
          <RouterLink
            :to="{ name: 'user-reservations' }"
            class="mt-6 inline-flex items-center gap-2 rounded-[9px] bg-[#191919] px-5 py-3 text-xs font-semibold text-white transition hover:bg-black"
          >
            Retourner à mes réservations
          </RouterLink>
        </div>

        <!-- CONTENU DE LA RÉSERVATION -->
        <div v-else-if="reservation" v-scroll-reveal="{ direction: 'up', delay: 80 }">
          <section class="overflow-hidden rounded-[15px] border border-[#ecebe7] bg-white">
            <div class="grid min-h-[465px] grid-cols-1 lg:grid-cols-[1.06fr_0.98fr_1fr]">
              <!-- COLONNE 1 (GAUCHE) : IMAGE DE LA SALLE RÉSERVÉE -->
              <div class="relative min-h-[390px] overflow-hidden bg-[#e9e8e4] lg:min-h-0">
                <img
                  :src="activeImage"
                  :alt="reservation.salle?.nom || 'Salle'"
                  class="absolute inset-0 h-full w-full object-cover"
                />

                <div
                  class="absolute inset-0 bg-gradient-to-t from-black/55 via-black/10 to-black/10"
                ></div>

                <!-- Badge flottant haut gauche -->
                <div class="absolute left-5 top-5">
                  <div
                    class="inline-flex items-center gap-1.5 rounded-full border border-white/35 bg-white/15 px-3 py-1.5 text-[11px] font-medium text-white backdrop-blur-md"
                  >
                    <Sparkles :size="12" />
                    <span>Réservation #{{ reservation.id }}</span>
                  </div>
                </div>

                <!-- Informations bas de photo -->
                <div class="absolute bottom-5 left-5 right-5 flex items-end justify-between gap-4">
                  <div>
                    <p class="text-[11px] font-medium uppercase tracking-[0.12em] text-white/70">
                      Enregistrée le {{ formatDateOnly(reservation.created_at) }}
                    </p>
                    <p class="mt-1 text-xl font-semibold leading-tight text-white">
                      {{ reservation.salle?.nom || 'Salle #' + reservation.salle_id }}
                    </p>
                  </div>

                  <!-- Badge statut -->
                  <div
                    class="shrink-0 rounded-full px-3 py-1.5 text-[10px] font-semibold text-white backdrop-blur-md"
                    :class="{
                      'bg-emerald-500/90': reservation.status === 'confirmee',
                      'bg-amber-500/90': reservation.status === 'en_attente',
                      'bg-slate-700/90': reservation.status === 'terminee',
                      'bg-rose-500/90': reservation.status === 'rejetee',
                      'bg-gray-500/90': reservation.status === 'annulee',
                    }"
                  >
                    {{
                      reservation.status === 'confirmee'
                        ? 'Confirmée'
                        : reservation.status === 'en_attente'
                        ? 'En attente'
                        : reservation.status === 'terminee'
                        ? 'Terminée'
                        : reservation.status === 'rejetee'
                        ? 'Rejetée'
                        : 'Annulée'
                    }}
                  </div>
                </div>
              </div>

              <!-- COLONNE 2 (CENTRE) : STATUT, DESCRIPTIF & ACTIONS -->
              <div
                class="flex flex-col justify-between border-b border-[#ecebe7] px-7 py-9 sm:px-10 lg:border-b-0 lg:border-r lg:border-[#ecebe7]"
              >
                <div>
                  <p class="text-[13px] font-medium text-[#7b7b7b]">Votre réservation</p>

                  <h1
                    class="mt-5 max-w-[320px] font-serif text-[42px] leading-[0.98] tracking-[-0.04em] text-[#191919]"
                  >
                    {{ reservation.salle?.nom || 'Espace Événementiel' }}
                  </h1>

                  <div class="mt-4 flex items-start gap-2 text-[13px] leading-5 text-[#777]">
                    <MapPin :size="15" class="mt-0.5 shrink-0 text-slate-800" />
                    <span>{{ reservation.salle?.localisation || 'Localisation non renseignée' }}</span>
                  </div>

                  <div class="mt-8 flex items-end gap-2">
                    <span
                      class="font-serif text-[38px] leading-none tracking-[-0.04em] text-[#000000]"
                    >
                      {{
                        reservation.status === 'confirmee'
                          ? 'Validée'
                          : reservation.status === 'en_attente'
                          ? 'En cours'
                          : reservation.status === 'terminee'
                          ? 'Clôturée'
                          : 'Inactive'
                      }}
                    </span>
                  </div>

                  <p class="mt-3 max-w-[280px] text-[13px] leading-6 text-[#777]">
                    {{
                      reservation.status === 'confirmee'
                        ? 'Votre réservation a été validée par notre équipe. Votre créneau et vos équipements sont garantis.'
                        : reservation.status === 'en_attente'
                        ? 'Votre demande est en cours de traitement. Vous recevrez une confirmation dès validation.'
                        : reservation.status === 'terminee'
                        ? 'Cet événement s’est déroulé avec succès. Merci pour votre confiance !'
                        : 'Cette réservation a été annulée ou rejetée. Le créneau horaire est libéré.'
                    }}
                  </p>
                </div>

                <!-- Boutons d'action -->
                <div class="mt-8 space-y-2.5">
                  <RouterLink
                    v-if="reservation.status === 'en_attente' || reservation.status === 'confirmee'"
                    :to="{ name: 'user-update-reservation', params: { id: reservation.id } }"
                    class="group inline-flex w-full items-center justify-between rounded-[9px] border border-[#1d293d] px-4 py-3 text-[12px] font-medium text-slate-800 transition hover:bg-[#181818] hover:text-white"
                  >
                    <span>Modifier cette réservation</span>
                    <Pencil :size="15" />
                  </RouterLink>

                  <button
                    v-if="reservation.status === 'en_attente' || reservation.status === 'confirmee'"
                    type="button"
                    @click="openCancelModal"
                    class="inline-flex w-full items-center justify-center rounded-[9px] border border-rose-200 bg-rose-50 px-4 py-2.5 text-[12px] font-semibold text-rose-700 transition hover:bg-rose-100 cursor-pointer"
                  >
                    Annuler la réservation
                  </button>
                </div>
              </div>

              <!-- COLONNE 3 (DROITE) : RÉCAPITULATIF & INFORMATIONS -->
              <div class="flex flex-col justify-between px-7 py-9 sm:px-10">
                <div>
                  <p class="text-[12px] font-medium text-slate-800">Récapitulatif de l'événement</p>

                  <div class="mt-6 space-y-5">
                    <!-- Date et créneau -->
                    <div class="flex gap-3 border-b border-[#efeee9] pb-4">
                      <CheckCircle2 :size="17" class="mt-0.5 shrink-0 text-slate-800" />
                      <div>
                        <p class="text-[13px] font-medium text-[#5d5d5d]">Créneau horaire</p>
                        <p class="mt-1 text-[14px] font-medium text-[#222] capitalize">
                          {{ formatDateOnly(reservation.date_heure_debut) }}
                        </p>
                        <p class="text-[12px] text-[#777]">
                          {{ formatTimeOnly(reservation.date_heure_debut) }} –
                          {{ formatTimeOnly(reservation.date_heure_fin) }} ({{ durationText }})
                        </p>
                      </div>
                    </div>

                    <!-- Participants -->
                    <div class="flex gap-3 border-b border-[#efeee9] pb-4">
                      <CheckCircle2 :size="17" class="mt-0.5 shrink-0 text-slate-800" />
                      <div>
                        <p class="text-[13px] font-medium text-[#5d5d5d]">Participants</p>
                        <p class="mt-1 text-[15px] font-medium text-[#222]">
                          {{ reservation.nombre_personnes }} personnes
                        </p>
                      </div>
                    </div>

                    <!-- Équipements réservés -->
                    <div class="flex gap-3 border-b border-[#efeee9] pb-4">
                      <CheckCircle2 :size="17" class="mt-0.5 shrink-0 text-slate-800" />
                      <div>
                        <p class="text-[13px] font-medium text-[#5d5d5d]">Équipements associés</p>
                        <p
                          v-if="reservation.equipements && reservation.equipements.length > 0"
                          class="mt-1 text-[13px] leading-5 text-[#333]"
                        >
                          <span
                            v-for="(eq, idx) in reservation.equipements"
                            :key="eq.id"
                            class="inline-block mr-1"
                          >
                            {{ eq.nom }} (×{{ eq.pivot?.quantity || eq.quantity || 1 }}){{
                              idx < reservation.equipements.length - 1 ? ',' : ''
                            }}
                          </span>
                        </p>
                        <p v-else class="mt-1 text-[13px] text-[#777]">
                          Aucun équipement additionnel
                        </p>
                      </div>
                    </div>

                    <!-- Demandeur / Client -->
                    <div class="flex gap-3">
                      <CheckCircle2 :size="17" class="mt-0.5 shrink-0 text-slate-800" />
                      <div>
                        <p class="text-[13px] font-medium text-[#5d5d5d]">Client & Contact</p>
                        <p class="mt-1 text-[14px] font-medium text-[#222]">
                          {{ reservation.nom_affiche || reservation.user?.nom || 'Client' }}
                        </p>
                        <p
                          v-if="reservation.telephone_affiche || reservation.user?.telephone"
                          class="text-[12px] text-[#777]"
                        >
                          {{ reservation.telephone_affiche || reservation.user?.telephone }}
                        </p>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Lien voir fiche salle -->
                <div class="mt-9 border-t border-[#ecebe7] pt-5">
                  <RouterLink
                    v-if="reservation.salle_id"
                    :to="{ name: 'info-user-salle', params: { id: reservation.salle_id } }"
                    class="inline-flex items-center gap-1.5 text-[12px] font-semibold text-slate-800 hover:underline"
                  >
                    <span>Consulter la fiche complète de la salle</span>
                    <ArrowLeft :size="13" class="rotate-180" />
                  </RouterLink>
                </div>
              </div>
            </div>

            <!-- Miniatures photos si disponibles -->
            <div
              v-if="imagesList.length > 1"
              class="flex gap-3 border-t border-[#ecebe7] bg-[#fafaf8] px-5 py-4"
            >
              <button
                v-for="(img, idx) in imagesList"
                :key="'thumb-' + idx"
                type="button"
                @click="selectedPhotoIndex = idx"
                class="h-14 w-20 shrink-0 overflow-hidden rounded-[7px] border transition-all cursor-pointer"
                :class="
                  selectedPhotoIndex === idx
                    ? 'border-[#181818] opacity-100'
                    : 'border-transparent opacity-55 hover:opacity-100'
                "
              >
                <img :src="img" :alt="`Aperçu ${idx + 1}`" class="h-full w-full object-cover" />
              </button>
            </div>
          </section>
        </div>
      </div>
    </main>

    <!-- MODAL DE CONFIRMATION D'ANNULATION -->
    <div
      v-if="isCancelModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
    >
      <div
        class="w-full max-w-md rounded-[16px] bg-white p-6 sm:p-7 shadow-2xl border border-gray-200"
      >
        <div
          class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 mb-4 border border-rose-100"
        >
          <AlertCircle :size="24" />
        </div>

        <h3 class="text-lg font-bold text-[#191919]">Annuler cette réservation ?</h3>
        <p class="mt-2 text-xs text-[#777] leading-relaxed">
          Êtes-vous sûr de vouloir annuler la réservation #{{ reservation?.id }} prévue pour le
          <strong class="text-[#191919]">{{ formatDateOnly(reservation?.date_heure_debut) }}</strong> ?
          Cette action est irréversible.
        </p>

        <div
          v-if="cancelError"
          class="mt-3 rounded-lg border border-rose-200 bg-rose-50 p-3 text-xs text-rose-700"
        >
          {{ cancelError }}
        </div>

        <div class="mt-6 flex justify-end gap-3">
          <button
            type="button"
            @click="closeCancelModal"
            :disabled="isCancelling"
            class="rounded-[9px] border border-gray-300 px-4 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition cursor-pointer"
          >
            Non, conserver
          </button>

          <button
            type="button"
            @click="confirmCancel"
            :disabled="isCancelling"
            class="inline-flex items-center gap-1.5 rounded-[9px] bg-rose-600 px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-rose-700 transition cursor-pointer disabled:opacity-60"
          >
            <Loader2 v-if="isCancelling" :size="14" class="animate-spin" />
            <span>{{ isCancelling ? 'Annulation...' : 'Oui, annuler' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- FOOTER -->
    <Footer />
  </div>
</template>
