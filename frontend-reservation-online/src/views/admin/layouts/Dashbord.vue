<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import AppAdmin from '@/components/admin/AppAdmin.vue'
import { useAdminDashboardStore } from '@/store/adminDashboard'
import { parseLocalDate } from '@/helpers/dateHelper'
import {
  Users,
  UserCheck,
  Building2,
  CheckCircle2,
  XCircle,
  Calendar,
  Clock,
  ArrowRight,
  TrendingUp,
  Package,
  Image as ImageIcon,
  MapPin,
  Eye,
  Check,
  Clock3,
  Ban,
  X,
  Loader2,
  RefreshCw,
  Sparkles,
  Info,
  Layers,
  Coins,
} from 'lucide-vue-next'

const dashboardStore = useAdminDashboardStore()

// États de sélection pour inspection des caractéristiques
const selectedSalleModal = ref(null)
const selectedEquipementModal = ref(null)
const selectedImageModal = ref(null)

// Onglet actif pour la section secondaire (Salles, Équipements, Galerie)
const activeSubTab = ref('salles') // 'salles', 'equipements', 'galerie'

onMounted(async () => {
  await loadDashboardData()
})

const loadDashboardData = async () => {
  try {
    await dashboardStore.fetchDashboard()
  } catch (err) {
    console.error('Erreur chargement dashboard admin :', err)
  }
}

const cartes = computed(() => dashboardStore.cartes)
const statusCounts = computed(() => dashboardStore.reservations_par_statut)
const reservationsParMois = computed(() => dashboardStore.reservations_par_mois)
const recentReservations = computed(() => dashboardStore.recent_reservations)
const sallesList = computed(() => dashboardStore.salles)
const equipementsList = computed(() => dashboardStore.equipements)
const imagesList = computed(() => dashboardStore.images)
const isLoading = computed(() => dashboardStore.loading)

// Calcul du total des réservations pour les pourcentages du statut
const totalStatusReservations = computed(() => {
  const s = statusCounts.value
  return (
    (s.en_attente || 0) +
    (s.confirmee || 0) +
    (s.rejetee || 0) +
    (s.terminee || 0) +
    (s.annulee || 0)
  )
})

const getStatusPercent = (count) => {
  if (!totalStatusReservations.value) return 0
  return Math.round((count / totalStatusReservations.value) * 100)
}

// Calcul de la valeur maximale par mois pour calibrer le graphique en barres
const maxMonthCount = computed(() => {
  if (!reservationsParMois.value || reservationsParMois.value.length === 0) return 1
  const max = Math.max(...reservationsParMois.value.map((m) => m.total))
  return max > 0 ? max : 1
})

// Utilitaires de formatage
const formatDate = (dateStr) => {
  const d = parseLocalDate(dateStr)
  if (!d) return 'N/A'
  try {
    return new Intl.DateTimeFormat('fr-FR', {
      day: '2-digit',
      month: '2-digit',
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

const defaultSalleImage =
  'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=800&q=80'
</script>

<template>
  <AppAdmin>
    <div class="mx-auto max-w-7xl space-y-8 pb-12">
      <!-- EN-TÊTE DU DASHBOARD -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <div class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-3.5 py-1 text-xs font-semibold text-[#4F46E5] mb-2">
            <Sparkles :size="13" />
            <span>Vue d'ensemble & Analyses</span>
          </div>
          <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-[#0F172A]">
            Tableau de Bord
          </h1>
          <p class="mt-1 text-xs sm:text-sm text-slate-500">
            Supervisez les utilisateurs, les réservations et la disponibilité des salles en temps réel.
          </p>
        </div>

        <button
          type="button"
          @click="loadDashboardData"
          :disabled="isLoading"
          class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-xs hover:bg-slate-50 transition cursor-pointer self-start sm:self-auto disabled:opacity-50"
        >
          <RefreshCw :size="14" :class="{ 'animate-spin': isLoading }" />
          <span>Actualiser les métriques</span>
        </button>
      </div>

      <!-- CHARGEMENT INITIAL -->
      <div
        v-if="isLoading && !cartes.total_salles && !cartes.total_reservations"
        class="flex flex-col items-center justify-center rounded-3xl border border-slate-200/80 bg-white p-16 shadow-xs"
      >
        <Loader2 :size="36" class="animate-spin text-[#4F46E5]" />
        <p class="mt-4 text-sm font-semibold text-slate-600">
          Chargement des données du tableau de bord...
        </p>
      </div>

      <template v-else>
        <!-- ========================================================================= -->
        <!-- 1. LES CARTES STATISTIQUES DU HAUT                                        -->
        <!-- ========================================================================= -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
          <!-- Carte 1 : Utilisateurs -->
          <div
            class="relative overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs hover:shadow-md transition-shadow"
          >
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold uppercase tracking-wider text-slate-400">
                Utilisateurs
              </span>
              <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                <Users :size="20" />
              </div>
            </div>
            <p class="mt-3 text-3xl font-black text-[#0F172A]">
              {{ cartes.total_utilisateurs ?? 0 }}
            </p>
            <p class="mt-1 text-[11px] text-slate-400">
              Clients inscrits (Total : {{ cartes.total_users_all ?? 0 }})
            </p>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-blue-500"></div>
          </div>

          <!-- Carte 2 : Responsables -->
          <div
            class="relative overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs hover:shadow-md transition-shadow"
          >
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold uppercase tracking-wider text-slate-400">
                Responsables
              </span>
              <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-purple-50 text-purple-600">
                <UserCheck :size="20" />
              </div>
            </div>
            <p class="mt-3 text-3xl font-black text-[#0F172A]">
              {{ cartes.total_responsables ?? 0 }}
            </p>
            <p class="mt-1 text-[11px] text-slate-400">
              Gestionnaires de salles actifs
            </p>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-purple-500"></div>
          </div>

          <!-- Carte 3 : Salles -->
          <div
            class="relative overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs hover:shadow-md transition-shadow"
          >
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold uppercase tracking-wider text-slate-400">
                Salles
              </span>
              <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                <Building2 :size="20" />
              </div>
            </div>
            <p class="mt-3 text-3xl font-black text-[#0F172A]">
              {{ cartes.total_salles ?? 0 }}
            </p>
            <p class="mt-1 text-[11px] text-slate-400">
              Total des salles enregistrées
            </p>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-[#4F46E5]"></div>
          </div>

          <!-- Carte 4 : Salles Disponibles -->
          <div
            class="relative overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs hover:shadow-md transition-shadow"
          >
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold uppercase tracking-wider text-emerald-700">
                Salles Disponibles
              </span>
              <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                <CheckCircle2 :size="20" />
              </div>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
              <p class="text-3xl font-black text-emerald-600">
                {{ cartes.salles_disponibles ?? 0 }}
              </p>
              <span class="text-xs text-rose-500 font-semibold">
                / {{ cartes.salles_indisponibles ?? 0 }} indispo
              </span>
            </div>
            <p class="mt-1 text-[11px] text-slate-400">
              Prêtes à accueillir des réservations
            </p>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-emerald-500"></div>
          </div>

          <!-- Carte 5 : Réservations -->
          <div
            class="relative overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs hover:shadow-md transition-shadow"
          >
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold uppercase tracking-wider text-slate-400">
                Réservations
              </span>
              <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-amber-50 text-amber-600">
                <Calendar :size="20" />
              </div>
            </div>
            <p class="mt-3 text-3xl font-black text-[#0F172A]">
              {{ cartes.total_reservations ?? 0 }}
            </p>
            <p class="mt-1 text-[11px] text-slate-400">
              Toutes réservations confondues
            </p>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-amber-500"></div>
          </div>
        </div>

        <!-- ========================================================================= -->
        <!-- 2. STATISTIQUES DES RÉSERVATIONS : PAR STATUT & PAR MOIS                  -->
        <!-- ========================================================================= -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
          <!-- Graphique 1 : Répartition par Statut (5 colonnes) -->
          <div class="lg:col-span-5 rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-7 shadow-xs">
            <div class="flex items-center justify-between mb-6">
              <div>
                <h3 class="text-base font-bold text-[#0F172A]">
                  Réservations par statut
                </h3>
                <p class="text-xs text-slate-400">Répartition en temps réel</p>
              </div>
              <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700">
                Total : {{ totalStatusReservations }}
              </span>
            </div>

            <!-- Barres proportionnelles des statuts -->
            <div class="space-y-4">
              <!-- En attente -->
              <div>
                <div class="flex items-center justify-between text-xs font-semibold mb-1.5">
                  <span class="flex items-center gap-1.5 text-amber-700">
                    <span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                    En attente
                  </span>
                  <span class="text-slate-800">
                    {{ statusCounts.en_attente ?? 0 }}
                    <span class="text-slate-400 font-normal">({{ getStatusPercent(statusCounts.en_attente) }}%)</span>
                  </span>
                </div>
                <div class="h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
                  <div
                    class="h-full rounded-full bg-amber-500 transition-all duration-500"
                    :style="{ width: getStatusPercent(statusCounts.en_attente) + '%' }"
                  ></div>
                </div>
              </div>

              <!-- Confirmées -->
              <div>
                <div class="flex items-center justify-between text-xs font-semibold mb-1.5">
                  <span class="flex items-center gap-1.5 text-emerald-700">
                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                    Confirmées
                  </span>
                  <span class="text-slate-800">
                    {{ statusCounts.confirmee ?? 0 }}
                    <span class="text-slate-400 font-normal">({{ getStatusPercent(statusCounts.confirmee) }}%)</span>
                  </span>
                </div>
                <div class="h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
                  <div
                    class="h-full rounded-full bg-emerald-500 transition-all duration-500"
                    :style="{ width: getStatusPercent(statusCounts.confirmee) + '%' }"
                  ></div>
                </div>
              </div>

              <!-- Rejetées -->
              <div>
                <div class="flex items-center justify-between text-xs font-semibold mb-1.5">
                  <span class="flex items-center gap-1.5 text-rose-700">
                    <span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span>
                    Rejetées
                  </span>
                  <span class="text-slate-800">
                    {{ statusCounts.rejetee ?? 0 }}
                    <span class="text-slate-400 font-normal">({{ getStatusPercent(statusCounts.rejetee) }}%)</span>
                  </span>
                </div>
                <div class="h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
                  <div
                    class="h-full rounded-full bg-rose-500 transition-all duration-500"
                    :style="{ width: getStatusPercent(statusCounts.rejetee) + '%' }"
                  ></div>
                </div>
              </div>

              <!-- Terminées -->
              <div>
                <div class="flex items-center justify-between text-xs font-semibold mb-1.5">
                  <span class="flex items-center gap-1.5 text-slate-700">
                    <span class="h-2.5 w-2.5 rounded-full bg-slate-800"></span>
                    Terminées
                  </span>
                  <span class="text-slate-800">
                    {{ statusCounts.terminee ?? 0 }}
                    <span class="text-slate-400 font-normal">({{ getStatusPercent(statusCounts.terminee) }}%)</span>
                  </span>
                </div>
                <div class="h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
                  <div
                    class="h-full rounded-full bg-slate-800 transition-all duration-500"
                    :style="{ width: getStatusPercent(statusCounts.terminee) + '%' }"
                  ></div>
                </div>
              </div>

              <!-- Annulées -->
              <div v-if="statusCounts.annulee > 0">
                <div class="flex items-center justify-between text-xs font-semibold mb-1.5">
                  <span class="flex items-center gap-1.5 text-slate-500">
                    <span class="h-2.5 w-2.5 rounded-full bg-slate-400"></span>
                    Annulées
                  </span>
                  <span class="text-slate-800">
                    {{ statusCounts.annulee ?? 0 }}
                    <span class="text-slate-400 font-normal">({{ getStatusPercent(statusCounts.annulee) }}%)</span>
                  </span>
                </div>
                <div class="h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
                  <div
                    class="h-full rounded-full bg-slate-400 transition-all duration-500"
                    :style="{ width: getStatusPercent(statusCounts.annulee) + '%' }"
                  ></div>
                </div>
              </div>
            </div>

            <div class="mt-6 rounded-2xl bg-indigo-50/60 p-3.5 text-xs text-[#3730A3] flex items-center gap-2.5">
              <TrendingUp :size="16" class="shrink-0" />
              <span>
                {{ statusCounts.confirmee ?? 0 }} réservation(s) validée(s) et prête(s) à se dérouler.
              </span>
            </div>
          </div>

          <!-- Graphique 2 : Réservations par Mois (7 colonnes) -->
          <div class="lg:col-span-7 rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-7 shadow-xs">
            <div class="flex items-center justify-between mb-6">
              <div>
                <h3 class="text-base font-bold text-[#0F172A]">
                  Réservations par mois
                </h3>
                <p class="text-xs text-slate-400">Évolution de l'activité sur l'année</p>
              </div>
              <span class="text-xs font-semibold text-[#4F46E5]">
                Année en cours
              </span>
            </div>

            <!-- Histogramme en Barres CSS/SVG -->
            <div class="h-56 flex items-end justify-between gap-1.5 sm:gap-3 pt-6 pb-2 px-2">
              <div
                v-for="item in reservationsParMois"
                :key="item.mois_num"
                class="group relative flex-1 flex flex-col items-center h-full justify-end"
              >
                <!-- Tooltip au survol -->
                <div
                  class="absolute -top-9 z-10 hidden group-hover:flex items-center justify-center rounded-lg bg-slate-900 px-2 py-1 text-[11px] font-bold text-white shadow-md whitespace-nowrap"
                >
                  {{ item.mois }} : {{ item.total }}
                </div>

                <!-- Barre -->
                <div
                  class="w-full rounded-t-xl transition-all duration-300 group-hover:brightness-95"
                  :class="
                    item.total > 0
                      ? 'bg-gradient-to-t from-[#4F46E5] to-indigo-400 shadow-xs'
                      : 'bg-slate-100'
                  "
                  :style="{
                    height: item.total > 0
                      ? Math.max(12, (item.total / maxMonthCount) * 100) + '%'
                      : '6px',
                  }"
                ></div>

                <!-- Label mois abrégé -->
                <span class="mt-2 text-[10px] font-semibold text-slate-400 truncate max-w-full">
                  {{ item.mois.substring(0, 3) }}
                </span>
              </div>
            </div>

            <!-- Légende sous l'histogramme -->
            <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
              <span>Moyenne active</span>
              <span class="font-bold text-slate-800">
                Pic d'activité : {{ maxMonthCount }} réservation(s) / mois
              </span>
            </div>
          </div>
        </div>

        <!-- ========================================================================= -->
        <!-- 3. RÉSERVATIONS RÉCENTES                                                   -->
        <!-- ========================================================================= -->
        <div class="rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-7 shadow-xs">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
              <h3 class="text-base font-bold text-[#0F172A] flex items-center gap-2">
                <Calendar :size="18" class="text-[#4F46E5]" />
                <span>Dernières réservations</span>
              </h3>
              <p class="text-xs text-slate-400">
                Aperçu des demandes et événements les plus récents
              </p>
            </div>

            <!-- Bouton demandé : Voir toutes les réservations → -->
            <RouterLink
              :to="{ name: 'admin-reservations' }"
              class="inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-4 py-2.5 text-xs font-bold text-white hover:bg-[#4F46E5] transition cursor-pointer self-start sm:self-auto"
            >
              <span>Voir toutes les réservations</span>
              <ArrowRight :size="14" />
            </RouterLink>
          </div>

          <!-- Tableau des réservations récentes -->
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead>
                <tr class="border-b border-slate-100 text-slate-400 uppercase font-semibold">
                  <th class="pb-3 pr-4">Client</th>
                  <th class="pb-3 px-4">Salle</th>
                  <th class="pb-3 px-4">Date</th>
                  <th class="pb-3 px-4">Heure</th>
                  <th class="pb-3 px-4">Statut</th>
                  <th class="pb-3 pl-4 text-right">Action</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr
                  v-for="res in recentReservations"
                  :key="res.id"
                  class="hover:bg-slate-50/70 transition-colors"
                >
                  <!-- Client -->
                  <td class="py-3.5 pr-4 font-bold text-slate-900">
                    {{ res.client_nom }}
                    <span
                      v-if="res.client_telephone"
                      class="block text-[11px] font-normal text-slate-400"
                    >
                      {{ res.client_telephone }}
                    </span>
                  </td>

                  <!-- Salle -->
                  <td class="py-3.5 px-4 font-medium text-slate-700">
                    {{ res.salle_nom }}
                  </td>

                  <!-- Date -->
                  <td class="py-3.5 px-4 font-medium text-slate-600">
                    {{ formatDate(res.date_heure_debut) }}
                  </td>

                  <!-- Heure -->
                  <td class="py-3.5 px-4 font-medium text-slate-600">
                    {{ formatTimeRange(res.date_heure_debut, res.date_heure_fin) }}
                  </td>

                  <!-- Statut -->
                  <td class="py-3.5 px-4">
                    <span
                      v-if="res.status === 'confirmee'"
                      class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-bold text-emerald-800"
                    >
                      <CheckCircle2 :size="12" />
                      <span>Confirmée</span>
                    </span>
                    <span
                      v-else-if="res.status === 'en_attente'"
                      class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-[11px] font-bold text-amber-800"
                    >
                      <Clock3 :size="12" />
                      <span>En attente</span>
                    </span>
                    <span
                      v-else-if="res.status === 'terminee'"
                      class="inline-flex items-center gap-1 rounded-full bg-slate-200 px-2.5 py-0.5 text-[11px] font-bold text-slate-800"
                    >
                      <Check :size="12" />
                      <span>Terminée</span>
                    </span>
                    <span
                      v-else-if="res.status === 'rejetee'"
                      class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2.5 py-0.5 text-[11px] font-bold text-rose-800"
                    >
                      <XCircle :size="12" />
                      <span>Rejetée</span>
                    </span>
                    <span
                      v-else
                      class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-bold text-slate-600"
                    >
                      <Ban :size="12" />
                      <span>Annulée</span>
                    </span>
                  </td>

                  <!-- Action -->
                  <td class="py-3.5 pl-4 text-right">
                    <RouterLink
                      :to="{ name: 'info-reservation', params: { id: res.id } }"
                      class="inline-flex items-center gap-1 text-xs font-bold text-[#4F46E5] hover:text-[#4338CA] hover:underline"
                    >
                      <span>Fiche</span>
                      <ArrowRight :size="13" />
                    </RouterLink>
                  </td>
                </tr>

                <tr v-if="!recentReservations || recentReservations.length === 0">
                  <td colspan="6" class="py-8 text-center text-xs text-slate-400">
                    Aucune réservation enregistrée pour le moment.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ========================================================================= -->
        <!-- 4. ÉTAT DES SALLES & INSPECTION INTERACTIVE (SALLES, EQUIPEMENTS, GALERIE) -->
        <!-- ========================================================================= -->
        <div class="rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-7 shadow-xs">
          <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
              <h3 class="text-base font-bold text-[#0F172A] flex items-center gap-2">
                <Building2 :size="18" class="text-[#4F46E5]" />
                <span>État des salles, Équipements & Galerie</span>
              </h3>
              <p class="text-xs text-slate-400">
                Sélectionnez un élément pour consulter immédiatement ses caractéristiques
              </p>
            </div>

            <!-- Onglets de sélection -->
            <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-2xl">
              <button
                type="button"
                @click="activeSubTab = 'salles'"
                class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer"
                :class="
                  activeSubTab === 'salles'
                    ? 'bg-white text-[#0F172A] shadow-xs'
                    : 'text-slate-500 hover:text-slate-900'
                "
              >
                🏢 Salles ({{ cartes.total_salles }})
              </button>
              <button
                type="button"
                @click="activeSubTab = 'equipements'"
                class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer"
                :class="
                  activeSubTab === 'equipements'
                    ? 'bg-white text-[#0F172A] shadow-xs'
                    : 'text-slate-500 hover:text-slate-900'
                "
              >
                📦 Équipements ({{ cartes.total_equipements }})
              </button>
              <button
                type="button"
                @click="activeSubTab = 'galerie'"
                class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer"
                :class="
                  activeSubTab === 'galerie'
                    ? 'bg-white text-[#0F172A] shadow-xs'
                    : 'text-slate-500 hover:text-slate-900'
                "
              >
                🖼️ Galerie ({{ cartes.total_images }})
              </button>
            </div>
          </div>

          <!-- SOUS-SECTION 1 : SALLES -->
          <div v-if="activeSubTab === 'salles'" class="space-y-6">
            <!-- Compteurs d'état demandés -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div class="rounded-2xl border border-emerald-200 bg-emerald-50/50 p-4">
                <span class="text-xs font-bold text-emerald-800 flex items-center gap-1.5">
                  <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                  🟢 Disponibles
                </span>
                <p class="mt-1 text-2xl font-black text-emerald-700">
                  {{ cartes.salles_disponibles }}
                </p>
              </div>

              <div class="rounded-2xl border border-rose-200 bg-rose-50/50 p-4">
                <span class="text-xs font-bold text-rose-800 flex items-center gap-1.5">
                  <span class="h-2 w-2 rounded-full bg-rose-500"></span>
                  🔴 Indisponibles
                </span>
                <p class="mt-1 text-2xl font-black text-rose-700">
                  {{ cartes.salles_indisponibles }}
                </p>
              </div>

              <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                  📊 Total Salles
                </span>
                <p class="mt-1 text-2xl font-black text-slate-800">
                  {{ cartes.total_salles }}
                </p>
              </div>
            </div>

            <!-- Liste des salles cliquables -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
              <div
                v-for="salle in sallesList"
                :key="salle.id"
                @click="selectedSalleModal = salle"
                class="group cursor-pointer rounded-2xl border border-slate-200/80 bg-slate-50/50 p-4 hover:border-indigo-300 hover:bg-white transition-all shadow-xs"
              >
                <div class="flex items-start justify-between gap-3">
                  <div class="flex-1">
                    <h4 class="text-sm font-bold text-slate-900 group-hover:text-[#4F46E5] transition-colors">
                      {{ salle.nom }}
                    </h4>
                    <p class="text-xs text-slate-400 flex items-center gap-1 mt-0.5">
                      <MapPin :size="12" />
                      <span class="truncate">{{ salle.localisation }}</span>
                    </p>
                  </div>

                  <span
                    class="rounded-full px-2.5 py-0.5 text-[10px] font-bold shrink-0"
                    :class="
                      salle.status === 'disponible'
                        ? 'bg-emerald-100 text-emerald-800'
                        : 'bg-rose-100 text-rose-800'
                    "
                  >
                    {{ salle.status === 'disponible' ? 'Disponible' : 'Occupée / Indisponible' }}
                  </span>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-200/60 flex items-center justify-between text-xs text-slate-500">
                  <span>Capacité : <strong>{{ salle.capacite }} pers.</strong></span>
                  <span class="text-[#4F46E5] font-bold group-hover:underline flex items-center gap-1">
                    Caractéristiques <Eye :size="12" />
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- SOUS-SECTION 2 : ÉQUIPEMENTS -->
          <div v-else-if="activeSubTab === 'equipements'" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
              <div
                v-for="eq in equipementsList"
                :key="eq.id"
                @click="selectedEquipementModal = eq"
                class="group cursor-pointer rounded-2xl border border-slate-200/80 bg-slate-50/50 p-4 hover:border-indigo-300 hover:bg-white transition-all shadow-xs"
              >
                <div class="flex items-center gap-3">
                  <div class="h-12 w-12 rounded-xl bg-indigo-50 text-[#4F46E5] flex items-center justify-center shrink-0">
                    <Package :size="20" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <h4 class="text-sm font-bold text-slate-900 truncate group-hover:text-[#4F46E5]">
                      {{ eq.nom }}
                    </h4>
                    <p class="text-xs text-slate-400">
                      Stock total : <strong>{{ eq.stock_total }} unités</strong>
                    </p>
                  </div>
                  <span
                    class="rounded-full px-2 py-0.5 text-[10px] font-bold"
                    :class="eq.status === 'disponible' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                  >
                    {{ eq.status }}
                  </span>
                </div>

                <div class="mt-3 pt-2.5 border-t border-slate-200/60 flex justify-end">
                  <span class="text-xs text-[#4F46E5] font-bold flex items-center gap-1">
                    Voir caractéristiques <Eye :size="12" />
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- SOUS-SECTION 3 : GALERIE D'IMAGES -->
          <div v-else class="space-y-4">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
              <div
                v-for="img in imagesList"
                :key="img.id"
                @click="selectedImageModal = img"
                class="group relative h-36 rounded-2xl overflow-hidden bg-slate-100 cursor-pointer shadow-xs border border-slate-200"
              >
                <img
                  :src="img.url || img.path || defaultSalleImage"
                  :alt="img.nom || 'Photo'"
                  class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-300"
                />
                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-2.5 text-white">
                  <p class="text-xs font-bold truncate">{{ img.nom || img.salle?.nom || 'Image' }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- ========================================================================= -->
      <!-- MODAL CARACTÉRISTIQUES D'UNE SALLE                                        -->
      <!-- ========================================================================= -->
      <div
        v-if="selectedSalleModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
      >
        <div class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl border border-slate-100 animate-in fade-in zoom-in-95 duration-200">
          <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
              <span class="text-[11px] font-bold text-[#4F46E5] uppercase">Fiche Caractéristiques</span>
              <h3 class="text-lg font-black text-[#0F172A]">{{ selectedSalleModal.nom }}</h3>
            </div>
            <button
              type="button"
              @click="selectedSalleModal = null"
              class="p-1 rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-600"
            >
              <X :size="20" />
            </button>
          </div>

          <div class="mt-4 space-y-3.5 text-xs">
            <div class="h-44 w-full rounded-2xl bg-slate-100 overflow-hidden">
              <img
                :src="selectedSalleModal.images?.[0]?.url || selectedSalleModal.images?.[0]?.path || defaultSalleImage"
                :alt="selectedSalleModal.nom"
                class="h-full w-full object-cover"
              />
            </div>

            <div class="grid grid-cols-2 gap-2">
              <div class="rounded-xl bg-slate-50 p-3">
                <span class="text-slate-400">Localisation</span>
                <p class="font-bold text-slate-900">{{ selectedSalleModal.localisation }}</p>
              </div>
              <div class="rounded-xl bg-slate-50 p-3">
                <span class="text-slate-400">Capacité</span>
                <p class="font-bold text-slate-900">{{ selectedSalleModal.capacite }} personnes</p>
              </div>
              <div class="rounded-xl bg-slate-50 p-3">
                <span class="text-slate-400">Tarif horaire</span>
                <p class="font-bold text-[#4F46E5]">
                  {{ selectedSalleModal.prix_par_heure ? selectedSalleModal.prix_par_heure + ' FCFA / h' : 'Standard' }}
                </p>
              </div>
              <div class="rounded-xl bg-slate-50 p-3">
                <span class="text-slate-400">Statut</span>
                <p class="font-bold capitalize" :class="selectedSalleModal.status === 'disponible' ? 'text-emerald-600' : 'text-rose-600'">
                  {{ selectedSalleModal.status }}
                </p>
              </div>
            </div>

            <p class="text-slate-600 leading-relaxed pt-2">
              {{ selectedSalleModal.description || 'Aucune description spécifique renseignée pour cette salle.' }}
            </p>
          </div>

          <div class="mt-6 flex justify-end gap-2">
            <RouterLink
              :to="{ name: 'info-salle', params: { id: selectedSalleModal.id } }"
              class="rounded-xl bg-[#4F46E5] px-4 py-2 text-xs font-bold text-white hover:bg-[#4338CA] transition"
            >
              Gérer la salle
            </RouterLink>
            <button
              type="button"
              @click="selectedSalleModal = null"
              class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
            >
              Fermer
            </button>
          </div>
        </div>
      </div>

      <!-- ========================================================================= -->
      <!-- MODAL CARACTÉRISTIQUES D'UN ÉQUIPEMENT                                    -->
      <!-- ========================================================================= -->
      <div
        v-if="selectedEquipementModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
      >
        <div class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl border border-slate-100 animate-in fade-in zoom-in-95 duration-200">
          <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
              <span class="text-[11px] font-bold text-[#4F46E5] uppercase">Caractéristiques Équipement</span>
              <h3 class="text-lg font-black text-[#0F172A]">{{ selectedEquipementModal.nom }}</h3>
            </div>
            <button
              type="button"
              @click="selectedEquipementModal = null"
              class="p-1 rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-600"
            >
              <X :size="20" />
            </button>
          </div>

          <div class="mt-4 space-y-3 text-xs">
            <div class="rounded-xl bg-slate-50 p-3 flex justify-between">
              <span class="text-slate-400">Stock total disponible</span>
              <span class="font-bold text-slate-900">{{ selectedEquipementModal.stock_total }} unités</span>
            </div>
            <div class="rounded-xl bg-slate-50 p-3 flex justify-between">
              <span class="text-slate-400">Statut matériel</span>
              <span class="font-bold capitalize text-emerald-600">{{ selectedEquipementModal.status }}</span>
            </div>
            <p class="text-slate-600 pt-2 leading-relaxed">
              {{ selectedEquipementModal.description || 'Équipement informatique et logistique pour réunions.' }}
            </p>
          </div>

          <div class="mt-6 flex justify-end gap-2">
            <RouterLink
              :to="{ name: 'info-equipment', params: { id: selectedEquipementModal.id } }"
              class="rounded-xl bg-[#4F46E5] px-4 py-2 text-xs font-bold text-white hover:bg-[#4338CA] transition"
            >
              Fiche complète
            </RouterLink>
            <button
              type="button"
              @click="selectedEquipementModal = null"
              class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
            >
              Fermer
            </button>
          </div>
        </div>
      </div>

      <!-- ========================================================================= -->
      <!-- MODAL CARACTÉRISTIQUES D'UNE IMAGE                                        -->
      <!-- ========================================================================= -->
      <div
        v-if="selectedImageModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
      >
        <div class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl border border-slate-100 animate-in fade-in zoom-in-95 duration-200">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-sm font-bold text-[#0F172A]">Détail de l'image</h3>
            <button
              type="button"
              @click="selectedImageModal = null"
              class="p-1 rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-600"
            >
              <X :size="20" />
            </button>
          </div>

          <div class="mt-4 space-y-3 text-xs">
            <div class="h-64 w-full rounded-2xl bg-slate-100 overflow-hidden">
              <img
                :src="selectedImageModal.url || selectedImageModal.path"
                :alt="selectedImageModal.nom"
                class="h-full w-full object-contain"
              />
            </div>
            <div class="rounded-xl bg-slate-50 p-3">
              <p><strong>Désignation :</strong> {{ selectedImageModal.designation || 'Image de galerie' }}</p>
              <p class="mt-1"><strong>Salle rattachée :</strong> {{ selectedImageModal.salle?.nom || 'Générale' }}</p>
            </div>
          </div>

          <div class="mt-5 flex justify-end">
            <button
              type="button"
              @click="selectedImageModal = null"
              class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
            >
              Fermer
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppAdmin>
</template>
