<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import NavBar from '@/layouts/NavBar.vue'
import Footer from '@/layouts/Footer.vue'
import { useReservationsStore } from '@/store/reservations'
import { parseLocalDate } from '@/helpers/dateHelper'
import {
  Calendar,
  Clock,
  MapPin,
  Users,
  Package,
  ArrowRight,
  Plus,
  Search,
  CheckCircle2,
  Clock3,
  XCircle,
  Ban,
  Check,
  AlertCircle,
  Loader2,
  Sparkles,
  RefreshCw,
  Building2,
} from 'lucide-vue-next'

const reservationsStore = useReservationsStore()

const searchQuery = ref('')
const activeTab = ref('all') // 'all', 'en_attente', 'confirmee', 'terminee', 'annulee'

// État du modal d'annulation
const isCancelModalOpen = ref(false)
const reservationToCancel = ref(null)
const isCancelling = ref(false)
const cancelError = ref(null)

const defaultImage =
  'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=800&q=80'

onMounted(async () => {
  await loadReservations()
})

const loadReservations = async () => {
  try {
    await reservationsStore.fetchMyReservations()
  } catch (err) {
    console.error('Erreur chargement des réservations :', err)
  }
}

const reservations = computed(() => reservationsStore.reservations || [])
const isLoading = computed(() => reservationsStore.loading)
const errorMessage = computed(() => reservationsStore.errorMessage)

// Compteurs par statut
const counts = computed(() => {
  const list = reservations.value
  return {
    all: list.length,
    en_attente: list.filter((r) => r.status === 'en_attente').length,
    confirmee: list.filter((r) => r.status === 'confirmee').length,
    terminee: list.filter((r) => r.status === 'terminee').length,
    annulee: list.filter((r) => r.status === 'annulee' || r.status === 'rejetee').length,
  }
})

// Liste filtrée selon l'onglet et la recherche
const filteredReservations = computed(() => {
  let list = reservations.value

  // Filtre par onglet
  if (activeTab.value === 'en_attente') {
    list = list.filter((r) => r.status === 'en_attente')
  } else if (activeTab.value === 'confirmee') {
    list = list.filter((r) => r.status === 'confirmee')
  } else if (activeTab.value === 'terminee') {
    list = list.filter((r) => r.status === 'terminee')
  } else if (activeTab.value === 'annulee') {
    list = list.filter((r) => r.status === 'annulee' || r.status === 'rejetee')
  }

  // Filtre par recherche textuelle (nom de la salle ou ID)
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase().trim()
    list = list.filter((r) => {
      const salleNom = r.salle?.nom ? r.salle.nom.toLowerCase() : ''
      const salleLoc = r.salle?.localisation ? r.salle.localisation.toLowerCase() : ''
      const idMatch = String(r.id).includes(q)
      return salleNom.includes(q) || salleLoc.includes(q) || idMatch
    })
  }

  return list
})

// Utilitaires de formatage
const formatDate = (dateStr) => {
  const d = parseLocalDate(dateStr)
  if (!d) return 'N/A'
  try {
    return new Intl.DateTimeFormat('fr-FR', {
      weekday: 'short',
      day: 'numeric',
      month: 'short',
      year: 'numeric',
    }).format(d)
  } catch {
    return dateStr
  }
}

const formatTimeRange = (debutStr, finStr) => {
  if (!debutStr || !finStr) return ''
  const debut = parseLocalDate(debutStr)
  const fin = parseLocalDate(finStr)
  if (!debut || !fin) return ''
  try {
    const dStr = new Intl.DateTimeFormat('fr-FR', {
      hour: '2-digit',
      minute: '2-digit',
    }).format(debut)
    const fStr = new Intl.DateTimeFormat('fr-FR', {
      hour: '2-digit',
      minute: '2-digit',
    }).format(fin)
    return `${dStr} - ${fStr}`
  } catch {
    return ''
  }
}

const getSalleImage = (reservation) => {
  if (reservation?.salle?.images && reservation.salle.images.length > 0) {
    const first = reservation.salle.images[0]
    return first.url || first.path || defaultImage
  }
  return defaultImage
}

// Modal d'annulation
const openCancelModal = (reservation) => {
  reservationToCancel.value = reservation
  cancelError.value = null
  isCancelModalOpen.value = true
}

const closeCancelModal = () => {
  if (isCancelling.value) return
  isCancelModalOpen.value = false
  reservationToCancel.value = null
  cancelError.value = null
}

const confirmCancelReservation = async () => {
  if (!reservationToCancel.value) return
  isCancelling.value = true
  cancelError.value = null
  try {
    await reservationsStore.cancelReservation(reservationToCancel.value.id)
    // Reset AVANT closeCancelModal pour ne pas être bloqué par la guard
    isCancelling.value = false
    isCancelModalOpen.value = false
    reservationToCancel.value = null
    cancelError.value = null
    await loadReservations()
  } catch (err) {
    cancelError.value = reservationsStore.errorMessage || "Impossible d'annuler cette réservation."
    isCancelling.value = false
  }
}
</script>

<template>
  <div class="reservations-page min-h-screen bg-[#f6f6f4] text-[#151515]">
    <!-- NAVBAR EXISTANTE : aucune donnée modifiée -->
    <NavBar />

    <main class="pt-28 pb-16 px-4 sm:px-6 lg:px-8">
      <div class="mx-auto max-w-[1180px]">

        <!-- =====================================================
             HERO
        ====================================================== -->
        <section class="hero-grid" v-scroll-reveal="{ direction: 'up', delay: 0 }">

          <!-- GRANDE CARTE IMAGE -->
          <div class="hero-image-card">

            <div
              v-if="filteredReservations.length"
              class="absolute inset-0"
            >
              <img
                :src="getSalleImage(filteredReservations[0])"
                :alt="filteredReservations[0]?.salle?.nom || 'Salle'"
                class="hero-main-image"
              />
            </div>

            <div
              v-else
              class="absolute inset-0 hero-empty-image"
            ></div>

            <div class="hero-image-overlay"></div>

            <!-- petit menu -->
            <div class="absolute top-5 left-5 z-10">
              <div class="hero-menu-button">
                <span></span>
                <span></span>
                <span></span>
              </div>
            </div>

            <!-- numéro -->
            <div
              v-if="filteredReservations.length"
              class="absolute top-5 right-5 z-10 hero-number"
            >
              #{{ filteredReservations[0].id }}
            </div>

            <!-- contenu -->
            <div
              v-if="filteredReservations.length"
              class="absolute bottom-7 left-6 right-6 z-10"
            >
              <!-- statut -->
              <div class="mb-4">
                <span
                  v-if="filteredReservations[0].status === 'confirmee'"
                  class="hero-status confirmed"
                >
                  <CheckCircle2 :size="12" />
                  Confirmée
                </span>

                <span
                  v-else-if="filteredReservations[0].status === 'en_attente'"
                  class="hero-status pending"
                >
                  <Clock3 :size="12" />
                  En attente
                </span>

                <span
                  v-else-if="filteredReservations[0].status === 'terminee'"
                  class="hero-status finished"
                >
                  <Check :size="12" />
                  Terminée
                </span>

                <span
                  v-else-if="filteredReservations[0].status === 'rejetee'"
                  class="hero-status rejected"
                >
                  <XCircle :size="12" />
                  Rejetée
                </span>

                <span
                  v-else
                  class="hero-status finished"
                >
                  <Ban :size="12" />
                  Annulée
                </span>
              </div>

              <p class="eyebrow">
                Votre réservation
              </p>

              <h1 class="hero-title">
                {{ filteredReservations[0].salle?.nom || 'Salle #' + filteredReservations[0].salle_id }}
              </h1>

              <div class="hero-details">
                <span>
                  <MapPin :size="13" />
                  {{ filteredReservations[0].salle?.localisation || 'Localisation standard' }}
                </span>

                <span>
                  <Calendar :size="13" />
                  {{ formatDate(filteredReservations[0].date_heure_debut) }}
                </span>

                <span>
                  <Clock :size="13" />
                  {{
                    formatTimeRange(
                      filteredReservations[0].date_heure_debut,
                      filteredReservations[0].date_heure_fin
                    )
                  }}
                </span>
              </div>
            </div>

          </div>

          <!-- COLONNE DROITE -->
          <div class="hero-right">

            <!-- TITRE -->
            <div class="editorial-card editorial-intro">

              <p class="eyebrow">
                Lodgify — Private spaces
              </p>

              <h2>
                VOS RÉSERVATIONS
                <br />
                <span>REVISITÉES.</span>
              </h2>

              <p>
                Consultez l'historique et le statut en temps réel
                de vos réservations de salles.
              </p>

            </div>

            <!-- STATS -->
            <div class="stats-grid">

              <div class="stat-card">
                <div class="stat-symbol">✦</div>

                <div class="stat-number">
                  {{ counts.all }}
                </div>

                <div class="stat-label">
                  Total
                </div>
              </div>

              <div class="stat-card">
                <div class="stat-symbol green">
                  ✧
                </div>

                <div class="stat-number green-text">
                  {{ counts.confirmee }}
                </div>

                <div class="stat-label">
                  Confirmées
                </div>
              </div>

              <div class="stat-card">
                <div class="stat-symbol">
                  ✦
                </div>

                <div class="stat-number">
                  {{ counts.terminee }}
                </div>

                <div class="stat-label">
                  Terminées
                </div>
              </div>

            </div>

            <!-- STORY -->
            <div class="editorial-card editorial-story">

              <div
                v-if="filteredReservations.length"
                class="story-background"
              >
                <img
                  :src="getSalleImage(filteredReservations[0])"
                  alt=""
                />
              </div>

              <div class="story-overlay"></div>

              <div class="story-content">

                <p class="eyebrow">
                  Your experience
                </p>

                <h3>
                  Réserver.
                  <br />
                  <span>Profiter.</span>
                </h3>

                <p>
                  Retrouvez toutes vos réservations et contrôlez
                  chaque détail de vos événements en quelques clics.
                </p>

              </div>

            </div>

          </div>
        </section>


        <!-- =====================================================
             FILTRES
        ====================================================== -->
        <section class="filters-card" v-scroll-reveal="{ direction: 'up', delay: 100 }">

          <div class="filters-scroll">

            <button
              type="button"
              @click="activeTab = 'all'"
              class="filter-button"
              :class="{ selected: activeTab === 'all' }"
            >
              Toutes
              <span>{{ counts.all }}</span>
            </button>

            <button
              type="button"
              @click="activeTab = 'en_attente'"
              class="filter-button"
              :class="{ selected: activeTab === 'en_attente' }"
            >
              En attente
              <span>{{ counts.en_attente }}</span>
            </button>

            <button
              type="button"
              @click="activeTab = 'confirmee'"
              class="filter-button"
              :class="{ selected: activeTab === 'confirmee' }"
            >
              Confirmées
              <span>{{ counts.confirmee }}</span>
            </button>

            <button
              type="button"
              @click="activeTab = 'terminee'"
              class="filter-button"
              :class="{ selected: activeTab === 'terminee' }"
            >
              Terminées
              <span>{{ counts.terminee }}</span>
            </button>

            <button
              type="button"
              @click="activeTab = 'annulee'"
              class="filter-button"
              :class="{ selected: activeTab === 'annulee' }"
            >
              Annulées
              <span>{{ counts.annulee }}</span>
            </button>

          </div>

          <!-- recherche -->
          <div class="search-container">

            <Search
              :size="14"
              class="search-icon"
            />

            <input
              v-model="searchQuery"
              type="text"
              placeholder="Rechercher une salle..."
              class="search-input"
            />

          </div>

        </section>


        <!-- =====================================================
             LOADING
        ====================================================== -->
        <div
          v-if="isLoading"
          class="state-card"
        >
          <Loader2
            :size="30"
            class="animate-spin text-[#181818]"
          />

          <p>
            Chargement de vos réservations...
          </p>
        </div>


        <!-- =====================================================
             ERREUR
        ====================================================== -->
        <div
          v-else-if="errorMessage && reservations.length === 0"
          class="state-card error-state"
        >
          <AlertCircle
            :size="32"
            class="text-red-500"
          />

          <h3>
            Impossible de charger vos réservations
          </h3>

          <p>
            {{ errorMessage }}
          </p>

          <button
            type="button"
            @click="loadReservations"
            class="white-button"
          >
            <RefreshCw :size="13" />
            Réessayer
          </button>
        </div>


        <!-- =====================================================
             AUCUNE RESERVATION
        ====================================================== -->
        <div
          v-else-if="reservations.length === 0"
          class="state-card empty-state"
        >
          <Building2
            :size="36"
            class="text-gray-300"
          />

          <h3>
            Vous n'avez aucune réservation
          </h3>

          <p>
            Découvrez nos espaces modulables et modernes pour vos réunions,
            séminaires ou conférences.
          </p>

          <div class="empty-actions">

            <RouterLink
              to="/salles"
              class="dark-outline-button"
            >
              Explorer les salles
            </RouterLink>

            <RouterLink
              to="/reserver"
              class="white-button"
            >
              <Plus :size="14" />
              Créer une réservation
            </RouterLink>

          </div>
        </div>


        <!-- =====================================================
             AUCUN RESULTAT FILTRE
        ====================================================== -->
        <div
          v-else-if="filteredReservations.length === 0"
          class="state-card empty-state"
        >
          <Search
            :size="32"
            class="text-gray-300"
          />

          <h3>
            Aucune réservation correspondante
          </h3>

          <p>
            Aucun événement ne correspond à vos filtres actuels.
          </p>

          <button
            type="button"
            @click=";(activeTab = 'all'), (searchQuery = '')"
            class="lime-button"
          >
            Réinitialiser les filtres
          </button>
        </div>


        <!-- =====================================================
             LISTE RESERVATIONS
        ====================================================== -->
        <section
          v-else
          class="reservation-grid"
          v-scroll-reveal="{ direction: 'up', delay: 150 }"
        >

          <article
            v-for="item in filteredReservations"
            :key="item.id"
            class="reservation-card"
          >

            <!-- IMAGE -->
            <div class="reservation-image">

              <img
                :src="getSalleImage(item)"
                :alt="item.salle?.nom || 'Salle'"
              />

              <div class="reservation-image-overlay"></div>

              <!-- statut -->
              <div class="absolute top-4 left-4">

                <span
                  v-if="item.status === 'confirmee'"
                  class="hero-status confirmed"
                >
                  <CheckCircle2 :size="12" />
                  Confirmée
                </span>

                <span
                  v-else-if="item.status === 'en_attente'"
                  class="hero-status pending"
                >
                  <Clock3 :size="12" />
                  En attente
                </span>

                <span
                  v-else-if="item.status === 'terminee'"
                  class="hero-status finished"
                >
                  <Check :size="12" />
                  Terminée
                </span>

                <span
                  v-else-if="item.status === 'rejetee'"
                  class="hero-status rejected"
                >
                  <XCircle :size="12" />
                  Rejetée
                </span>

                <span
                  v-else
                  class="hero-status finished"
                >
                  <Ban :size="12" />
                  Annulée
                </span>

              </div>

              <!-- ID -->


              <!-- titre -->
              <div class="reservation-title">



                <h3>
                  {{ item.salle?.nom || 'Salle #' + item.salle_id }}
                </h3>

                <p class="location">
                  <MapPin :size="12" />
                  {{ item.salle?.localisation || 'Localisation standard' }}
                </p>

              </div>

            </div>


            <!-- CONTENU -->
            <div class="reservation-content">

              <div class="reservation-info-grid">

                <div class="reservation-info">

                  <span>
                    Date
                  </span>

                  <strong>
                    {{ formatDate(item.date_heure_debut) }}
                  </strong>

                </div>

                <div class="reservation-info">

                  <span>
                    Horaires
                  </span>

                  <strong>
                    {{ formatTimeRange(item.date_heure_debut, item.date_heure_fin) }}
                  </strong>

                </div>

                <div class="reservation-info">

                  <span>
                    Participants
                  </span>

                  <strong class="with-icon">
                    <Users :size="13" />
                    {{ item.nombre_personnes }}
                  </strong>

                </div>

                <div class="reservation-info">

                  <span>
                    Équipements
                  </span>

                  <strong class="with-icon">
                    <Package :size="13" />
                    {{ item.equipements?.length || 0 }}
                  </strong>

                </div>

              </div>


              <!-- ACTIONS -->
              <div class="reservation-actions">

                <div class="left-actions">

                  <button
                    v-if="item.status === 'en_attente' || item.status === 'confirmee'"
                    type="button"
                    @click="openCancelModal(item)"
                    class="cancel-link"
                  >
                    Annuler
                  </button>

                  <RouterLink
                    v-if="item.status === 'en_attente' || item.status === 'confirmee'"
                    :to="{
                      name: 'user-update-reservation',
                      params: { id: item.id }
                    }"
                    class="modify-link"
                  >
                    Modifier
                  </RouterLink>

                </div>

                <RouterLink
                  :to="{
                    name: 'user-reservation-details',
                    params: { id: item.id }
                  }"
                  class="details-button"
                >
                  Détails
                  <ArrowRight :size="13" />
                </RouterLink>

              </div>

            </div>

          </article>

        </section>

      </div>
    </main>


    <!-- =====================================================
         MODAL ANNULATION
    ====================================================== -->
    <div
      v-if="isCancelModalOpen"
      class="modal-background"
    >

      <div class="cancel-modal">

        <div class="modal-icon">
          <AlertCircle :size="24" />
        </div>

        <h3>
          Annuler cette réservation ?
        </h3>

        <p>
          Êtes-vous sûr de vouloir annuler la réservation
          #{{ reservationToCancel?.id }} pour l'événement prévu
          à la salle
          <strong>
            {{ reservationToCancel?.salle?.nom }}
          </strong>
          ? Cette action est irréversible.
        </p>

        <div
          v-if="cancelError"
          class="modal-error"
        >
          {{ cancelError }}
        </div>

        <div class="modal-actions">

          <button
            type="button"
            @click="closeCancelModal"
            :disabled="isCancelling"
            class="modal-keep-button"
          >
            Non, conserver
          </button>

          <button
            type="button"
            @click="confirmCancelReservation"
            :disabled="isCancelling"
            class="modal-cancel-button"
          >
            <Loader2
              v-if="isCancelling"
              :size="14"
              class="animate-spin"
            />

            <span>
              {{ isCancelling ? 'Annulation...' : 'Oui, annuler' }}
            </span>
          </button>

        </div>

      </div>

    </div>

    <!-- FOOTER EXISTANT -->
    <Footer />

  </div>
</template>

<style scoped>
/* =========================================================
   BASE
========================================================= */

.reservations-page {
  min-height: 100vh;
  background: #f6f6f4;
  color: #151515;
  font-family:
    Inter,
    ui-sans-serif,
    system-ui,
    -apple-system,
    BlinkMacSystemFont,
    "Segoe UI",
    sans-serif;
}

/* =========================================================
   TYPOGRAPHIE
========================================================= */

.eyebrow {
  margin: 0 0 10px;

  color: #888888;

  font-size: 8px;
  line-height: 1;
  font-weight: 600;

  text-transform: uppercase;
  letter-spacing: 0.28em;
}

.hero-title,
.editorial-card h2,
.editorial-story h3,
.reservation-title h3,
.state-card h3,
.cancel-modal h3 {
  font-family:
    Georgia,
    "Times New Roman",
    serif;
}

/* =========================================================
   HERO
========================================================= */

.hero-grid {
  display: grid;
  grid-template-columns: 1.03fr 0.97fr;
  gap: 12px;
}

.hero-image-card {
  position: relative;
  min-height: 610px;

  overflow: hidden;

  border: 1px solid #ecebe7;
  border-radius: 24px;

  background: #181818;
}

.hero-main-image {
  width: 100%;
  height: 100%;
  object-fit: cover;

  filter: saturate(0.85);

  transition: transform 0.8s ease;
}

.hero-image-card:hover .hero-main-image {
  transform: scale(1.035);
}

.hero-empty-image {
  background:
    radial-gradient(circle at 30% 30%, #292b2a 0%, transparent 40%),
    linear-gradient(135deg, #222524, #090a0a);
}

.hero-image-overlay {
  position: absolute;
  inset: 0;

  background:
    linear-gradient(
      to bottom,
      rgba(0, 0, 0, 0.28),
      transparent 30%,
      rgba(0, 0, 0, 0.85) 100%
    );
}

.hero-menu-button {
  width: 42px;
  height: 42px;

  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 12px;

  background: rgba(0, 0, 0, 0.45);

  backdrop-filter: blur(12px);

  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;
}

.hero-menu-button span {
  display: block;
  width: 14px;
  height: 1px;
  background: white;
  opacity: 0.8;
}

.hero-number {
  border: 1px solid rgba(255, 255, 255, 0.25);
  border-radius: 999px;

  background: rgba(0, 0, 0, 0.45);

  padding: 7px 11px;

  color: white;

  font-size: 8px;
  letter-spacing: 0.15em;

  backdrop-filter: blur(10px);
}

.hero-title {
  max-width: 650px;

  margin: 0;

  font-size: clamp(42px, 5vw, 68px);
  line-height: 0.9;
  font-weight: 400;

  letter-spacing: -0.055em;
  color: white;
}

.hero-details {
  display: flex;
  flex-wrap: wrap;
  align-items: center;

  gap: 8px 20px;

  margin-top: 20px;

  color: rgba(255, 255, 255, 0.75);

  font-size: 10px;
}

.hero-details span {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.hero-status {
  display: inline-flex;
  align-items: center;
  gap: 6px;

  padding: 6px 10px;

  border-radius: 999px;

  font-size: 8px;
  font-weight: 800;

  text-transform: uppercase;
  letter-spacing: 0.1em;
}

.hero-status.confirmed {
  color: #065f46;
  background: #d1fae5;
  border: 1px solid #a7f3d0;
}

.hero-status.pending {
  color: #92400e;
  background: #fef3c7;
  border: 1px solid #fde68a;
}

.hero-status.finished {
  color: #374151;
  background: #f3f4f6;
  border: 1px solid #e5e7eb;
}

.hero-status.rejected {
  color: #991b1b;
  background: #fee2e2;
  border: 1px solid #fecaca;
}

/* =========================================================
   HERO DROITE
========================================================= */

.hero-right {
  display: grid;
  grid-template-rows: auto auto 1fr;
  gap: 12px;
}

.editorial-card {
  position: relative;

  overflow: hidden;

  border: 1px solid #ecebe7;
  border-radius: 24px;

  background: #ffffff;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

.editorial-intro {
  min-height: 205px;

  padding: 30px;
}

.editorial-card h2 {
  margin: 0;

  color: #151515;

  font-size: clamp(30px, 3vw, 43px);
  line-height: 0.94;
  font-weight: 400;

  letter-spacing: -0.045em;
}

.editorial-card h2 span {
  color: #888888;
}

.editorial-intro .eyebrow {
  color: #888888;
}

.editorial-intro > p:last-child {
  max-width: 380px;

  margin-top: 23px;

  color: #666666;

  font-size: 11px;
  line-height: 1.7;
}

/* =========================================================
   STATS
========================================================= */

.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}

.stat-card {
  min-height: 140px;

  border: 1px solid #ecebe7;
  border-radius: 20px;

  background: #ffffff;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);

  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;

  text-align: center;
}

.stat-symbol {
  margin-bottom: 8px;

  color: #999999;

  font-size: 18px;
}

.stat-symbol.green {
  color: #10b981;
}

.stat-number {
  color: #151515;

  font-family: Georgia, "Times New Roman", serif;

  font-size: 27px;
  line-height: 1;
}

.stat-number.green-text {
  color: #059669;
}

.stat-label {
  margin-top: 7px;

  color: #777777;

  font-size: 8px;
  font-weight: 600;

  text-transform: uppercase;
  letter-spacing: 0.2em;
}

/* =========================================================
   STORY
========================================================= */

.editorial-story {
  min-height: 250px;
}

.story-background,
.story-background img,
.story-overlay {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
}

.story-background img {
  object-fit: cover;
  opacity: 0.12;
}

.story-overlay {
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.92) 0%, rgba(246, 246, 244, 0.96) 100%);
}

.story-content {
  position: absolute;
  left: 30px;
  right: 30px;
  bottom: 28px;
  z-index: 1;
}

.story-content .eyebrow {
  color: #888888;
}

.story-content h3 {
  margin: 0;
  color: #151515;
  font-size: clamp(26px, 2.5vw, 36px);
  line-height: 0.95;
  font-weight: 400;
  letter-spacing: -0.04em;
}

.story-content h3 span {
  color: #888888;
}

.story-content p {
  max-width: 320px;
  margin-top: 10px;
  color: #666666;
  font-size: 11px;
  line-height: 1.6;
}

/* =========================================================
   FILTRES
========================================================= */

.filters-card {
  margin-top: 12px;
  padding: 10px;

  border: 1px solid #ecebe7;
  border-radius: 20px;

  background: #ffffff;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);

  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.filters-scroll {
  display: flex;
  align-items: center;
  gap: 6px;
  overflow-x: auto;
}

.filter-button {
  display: inline-flex;
  align-items: center;
  gap: 8px;

  height: 38px;
  padding: 0 16px;

  border: 1px solid transparent;
  border-radius: 999px;

  background: transparent;

  color: #666666;

  font-size: 9px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.13em;

  cursor: pointer;
  transition: all 0.2s ease;
}

.filter-button span {
  color: #999999;
}

.filter-button:hover {
  color: #151515;
  background: #f4f3f0;
}

.filter-button.selected {
  color: #ffffff;
  background: #181818;
}

.filter-button.selected span {
  color: rgba(255, 255, 255, 0.7);
}

.search-container {
  position: relative;
  width: 270px;
  flex-shrink: 0;
}

.search-icon {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #888888;
}

.search-input {
  width: 100%;
  height: 40px;
  padding: 0 14px 0 38px;

  border: 1px solid #deddd9;
  border-radius: 12px;
  outline: none;

  background: #fafaf8;
  color: #151515;
  font-size: 11px;
  transition: border-color 0.2s ease;
}

.search-input::placeholder {
  color: #999999;
}

.search-input:focus {
  border-color: #181818;
  background: #ffffff;
}

/* =========================================================
   RESERVATIONS GRID & CARDS
========================================================= */

.reservation-grid {
  margin-top: 12px;
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
}

.reservation-card {
  overflow: hidden;
  border: 1px solid #ecebe7;
  border-radius: 24px;
  background: #ffffff;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
  transition:
    transform 0.3s ease,
    border-color 0.3s ease,
    box-shadow 0.3s ease;
}

.reservation-card:hover {
  transform: translateY(-3px);
  border-color: #deddd9;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.07);
}

/* =========================================================
   IMAGE RESERVATION
========================================================= */

.reservation-image {
  position: relative;
  height: 280px;
  overflow: hidden;
  background: #eae9e5;
}

.reservation-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.7s ease;
}

.reservation-card:hover .reservation-image img {
  transform: scale(1.045);
}

.reservation-image-overlay {
  position: absolute;
  inset: 0;
  background:
    linear-gradient(
      to bottom,
      rgba(0, 0, 0, 0.25),
      transparent 35%,
      rgba(0, 0, 0, 0.85) 100%
    );
}

.reservation-number {
  position: absolute;
  top: 17px;
  right: 17px;
  padding: 7px 11px;
  border: 1px solid rgba(255, 255, 255, 0.25);
  border-radius: 999px;
  background: rgba(0, 0, 0, 0.45);
  color: white;
  font-size: 8px;
  letter-spacing: 0.14em;
  backdrop-filter: blur(8px);
}

.reservation-title {
  position: absolute;
  left: 20px;
  right: 20px;
  bottom: 20px;
}

.reservation-title .eyebrow {
  color: rgba(255, 255, 255, 0.75);
}

.reservation-title h3 {
  margin: 0;
  color: white;
  font-size: 31px;
  line-height: 0.95;
  font-weight: 400;
  letter-spacing: -0.04em;
}

.location {
  display: flex;
  align-items: center;
  gap: 5px;
  margin-top: 9px;
  color: rgba(255, 255, 255, 0.85);
  font-size: 10px;
}

/* =========================================================
   CONTENU RESERVATION
========================================================= */

.reservation-content {
  padding: 18px;
}

.reservation-info-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 7px;
}

.reservation-info {
  min-height: 68px;
  padding: 11px 12px;
  border: 1px solid #ecebe7;
  border-radius: 13px;
  background: #fafaf8;
}

.reservation-info span {
  display: block;
  color: #777777;
  font-size: 8px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.18em;
}

.reservation-info strong {
  display: block;
  margin-top: 6px;
  color: #151515;
  font-family: Georgia, "Times New Roman", serif;
  font-size: 14px;
  font-weight: 400;
  line-height: 1.15;
}

.reservation-info strong.with-icon {
  display: flex;
  align-items: center;
  gap: 6px;
}

/* =========================================================
   ACTIONS
========================================================= */

.reservation-actions {
  margin-top: 16px;
  padding-top: 14px;
  border-top: 1px solid #ecebe7;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.left-actions {
  display: flex;
  align-items: center;
  gap: 15px;
}

.cancel-link,
.modify-link {
  padding: 0;
  border: 0;
  background: transparent;
  font-size: 9px;
  text-transform: uppercase;
  letter-spacing: 0.14em;
  cursor: pointer;
  font-weight: 600;
}

.cancel-link {
  color: #dc2626;
}

.cancel-link:hover {
  color: #b91c1c;
}

.modify-link {
  color: #555555;
}

.modify-link:hover {
  color: #151515;
}

.details-button {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 10px 16px;
  border-radius: 10px;
  background: #181818;
  color: #ffffff;
  font-size: 9px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  transition: all 0.2s ease;
}

.details-button:hover {
  background: #000000;
}

/* =========================================================
   ETATS
========================================================= */

.state-card {
  min-height: 350px;
  margin-top: 12px;
  border: 1px solid #ecebe7;
  border-radius: 24px;
  background: #ffffff;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 40px;
}

.state-card p {
  margin-top: 13px;
  color: #666666;
  font-size: 12px;
  line-height: 1.6;
}

.empty-state h3,
.error-state h3 {
  margin-top: 18px;
  font-family: Georgia, "Times New Roman", serif;
  font-size: 30px;
  font-weight: 400;
  color: #151515;
}

.empty-state > p {
  max-width: 450px;
}

.empty-actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: center;
  gap: 9px;
  margin-top: 24px;
}

.white-button,
.dark-outline-button,
.lime-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  min-height: 40px;
  padding: 0 16px;
  border-radius: 10px;
  font-size: 9px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  cursor: pointer;
  transition: all 0.2s ease;
}

.white-button {
  border: 0;
  background: #181818;
  color: #ffffff;
}

.white-button:hover {
  background: #000000;
}

.dark-outline-button {
  border: 1px solid #deddd9;
  background: #ffffff;
  color: #444444;
}

.dark-outline-button:hover {
  color: #151515;
  background: #fafaf8;
  border-color: #181818;
}

.lime-button {
  margin-top: 22px;
  border: 0;
  background: #181818;
  color: #ffffff;
}

.lime-button:hover {
  background: #000000;
}

/* =========================================================
   MODAL
========================================================= */

.modal-background {
  position: fixed;
  inset: 0;
  z-index: 100;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  background: rgba(0, 0, 0, 0.45);
  backdrop-filter: blur(6px);
}

.cancel-modal {
  width: 100%;
  max-width: 440px;
  padding: 28px;
  border: 1px solid #ecebe7;
  border-radius: 24px;
  background: #ffffff;
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
}

.modal-icon {
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 13px;
  background: #fee2e2;
  color: #dc2626;
}

.cancel-modal h3 {
  margin-top: 18px;
  font-size: 26px;
  line-height: 1.1;
  font-weight: 500;
  color: #151515;
}

.cancel-modal > p {
  margin-top: 13px;
  color: #666666;
  font-size: 12px;
  line-height: 1.7;
}

.cancel-modal > p strong {
  color: #151515;
}

.modal-error {
  margin-top: 15px;
  padding: 12px;
  border: 1px solid #fecaca;
  border-radius: 11px;
  background: #fee2e2;
  color: #991b1b;
  font-size: 11px;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 24px;
}

.modal-keep-button,
.modal-cancel-button {
  min-height: 40px;
  padding: 0 16px;
  border-radius: 10px;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  cursor: pointer;
}

.modal-keep-button {
  border: 1px solid #deddd9;
  background: #ffffff;
  color: #555555;
}

.modal-keep-button:hover {
  color: #151515;
  background: #fafaf8;
}

.modal-cancel-button {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  border: 0;
  background: #dc2626;
  color: white;
}

.modal-cancel-button:hover {
  background: #b91c1c;
}

/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 900px) {
  .hero-grid {
    grid-template-columns: 1fr;
  }

  .hero-image-card {
    min-height: 500px;
  }

  .hero-right {
    grid-template-rows: auto;
  }
}

@media (max-width: 768px) {
  .filters-card {
    flex-direction: column;
    align-items: stretch;
  }

  .search-container {
    width: 100%;
  }

  .reservation-grid {
    grid-template-columns: 1fr;
  }

  .stats-grid {
    gap: 7px;
  }

  .stat-card {
    min-height: 115px;
  }

  .stat-number {
    font-size: 23px;
  }
}

@media (max-width: 600px) {
  .hero-image-card {
    min-height: 450px;
  }

  .hero-title {
    font-size: 42px;
  }

  .hero-details {
    flex-direction: column;
    align-items: flex-start;
    gap: 7px;
  }

  .editorial-intro {
    padding: 24px;
  }

  .editorial-card h2 {
    font-size: 32px;
  }

  .story-content {
    left: 24px;
    right: 24px;
    bottom: 24px;
  }

  .story-content h3 {
    font-size: 32px;
  }

  .reservation-image {
    height: 250px;
  }

  .reservation-title h3 {
    font-size: 27px;
  }

  .reservation-info-grid {
    gap: 5px;
  }

  .reservation-info {
    padding: 10px;
  }

  .reservation-actions {
    align-items: flex-start;
  }

  .modal-actions {
    flex-direction: column-reverse;
  }

  .modal-keep-button,
  .modal-cancel-button {
    width: 100%;
  }
}
</style>
