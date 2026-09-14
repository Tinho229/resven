<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import AppAdmin from '@/components/admin/AppAdmin.vue'
import { useAdminReservationsStore } from '@/store/adminReservations'
import { useAdminSallesStore } from '@/store/adminSalles'
import { parseLocalDate } from '@/helpers/dateHelper'
import {
  CalendarDays,
  Plus,
  ChevronLeft,
  ChevronRight,
  Clock3,
  UserRound,
  RefreshCw,
  Building2,
  Check,
} from 'lucide-vue-next'

const adminReservationsStore = useAdminReservationsStore()
const adminSallesStore = useAdminSallesStore()

// 'Week' ou 'Month'
const activeView = ref('Week')

// Date de référence (navigation temporelle)
const currentDate = ref(new Date())

// Filtre salle optionnel
const selectedSalleId = ref('')

// Popup de détails d'une réservation sélectionnée
const selectedAppointment = ref(null)
const popupPosition = ref({ left: '214px', top: '198px' })

const hours = [
  '08.00',
  '09.00',
  '10.00',
  '11.00',
  '12.00',
  '13.00',
  '14.00',
  '15.00',
  '16.00',
  '17.00',
  '18.00',
  '19.00',
  '20.00',
]

const colorClasses = {
  green: {
    container: 'bg-[#F1F9F3] border-t-[#31B34A]',
    text: 'text-[#26983B]',
    badge: 'bg-[#E5F7E9] text-[#26983B]',
  },
  yellow: {
    container: 'bg-[#FFF9EA] border-t-[#EBC52D]',
    text: 'text-[#D7AE00]',
    badge: 'bg-[#FFF3C8] text-[#D8AE00]',
  },
  red: {
    container: 'bg-[#FFF2F4] border-t-[#E95068]',
    text: 'text-[#D93650]',
    badge: 'bg-[#FFE2E6] text-[#D93650]',
  },
  blue: {
    container: 'bg-[#EFF4FF] border-t-[#1769E0]',
    text: 'text-[#1769E0]',
    badge: 'bg-[#DBEAFE] text-[#1769E0]',
  },
}

onMounted(async () => {
  await loadData()
})

const loadData = async () => {
  try {
    await Promise.all([
      adminReservationsStore.fetchReservations({ all: 'true' }),
      adminSallesStore.fetchSalles({ all: 'true' }),
    ])
  } catch (err) {
    console.error('Erreur chargement agenda:', err)
  }
}

const reservations = computed(() => adminReservationsStore.reservations || [])
const salles = computed(() => adminSallesStore.salles || [])
const isLoading = computed(() => adminReservationsStore.loading || adminSallesStore.loading)

// Navigation
const goToToday = () => {
  currentDate.value = new Date()
  selectedAppointment.value = null
}

const goPrevious = () => {
  const d = new Date(currentDate.value)
  if (activeView.value === 'Week') {
    d.setDate(d.getDate() - 7)
  } else {
    d.setMonth(d.getMonth() - 1)
  }
  currentDate.value = d
  selectedAppointment.value = null
}

const goNext = () => {
  const d = new Date(currentDate.value)
  if (activeView.value === 'Week') {
    d.setDate(d.getDate() + 7)
  } else {
    d.setMonth(d.getMonth() + 1)
  }
  currentDate.value = d
  selectedAppointment.value = null
}

// Titre du mois / année en en-tête (ex: "Septembre, 2026")
const currentMonthYear = computed(() => {
  const d = currentDate.value
  const monthName = new Intl.DateTimeFormat('fr-FR', { month: 'long' }).format(d)
  const capitalized = monthName.charAt(0).toUpperCase() + monthName.slice(1)
  return `${capitalized}, ${d.getFullYear()}`
})

// Détermination de la couleur selon le statut
const getStatusColorKey = (status) => {
  switch (status) {
    case 'confirmee':
      return 'green'
    case 'en_attente':
      return 'yellow'
    case 'rejetee':
      return 'red'
    default:
      return 'blue'
  }
}

// Formatage d'heure (ex: "10.00 - 12.30")
const formatHourRange = (startStr, endStr) => {
  if (!startStr || !endStr) return ''
  const s = parseLocalDate(startStr)
  const e = parseLocalDate(endStr)
  if (!s || !e) return ''
  try {
    const sH = String(s.getHours()).padStart(2, '0') + '.' + String(s.getMinutes()).padStart(2, '0')
    const eH = String(e.getHours()).padStart(2, '0') + '.' + String(e.getMinutes()).padStart(2, '0')
    return `${sH} - ${eH}`
  } catch {
    return ''
  }
}

// Semaine courante (7 jours du Lundi au Dimanche)
const weekDays = computed(() => {
  const curr = new Date(currentDate.value)
  const day = curr.getDay()
  const diffToMonday = day === 0 ? -6 : 1 - day

  const monday = new Date(curr)
  monday.setDate(curr.getDate() + diffToMonday)
  monday.setHours(0, 0, 0, 0)

  const dayNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']
  const todayStr = new Date().toDateString()

  const list = []
  for (let i = 0; i < 7; i++) {
    const d = new Date(monday)
    d.setDate(monday.getDate() + i)

    const dayNum = String(d.getDate()).padStart(2, '0')
    const monthNum = String(d.getMonth() + 1).padStart(2, '0')

    list.push({
      dateObj: d,
      name: dayNames[i],
      date: `${dayNum}-${monthNum}`,
      active: d.toDateString() === todayStr,
    })
  }

  return list
})

// Réservations filtrées par salle si sélectionnée
const filteredReservations = computed(() => {
  let list = reservations.value
  if (selectedSalleId.value) {
    list = list.filter((r) => String(r.salle_id) === String(selectedSalleId.value))
  }
  return list
})

// Obtenir et positionner les réservations d'un jour donné dans la grille horaire (8h à 21h)
const getDayAppointments = (dayDate) => {
  const dayStr = dayDate.toDateString()
  const startHourBase = 8

  return filteredReservations.value
    .filter((r) => {
      if (!r.date_heure_debut) return false
      const s = parseLocalDate(r.date_heure_debut)
      return s && s.toDateString() === dayStr
    })
    .map((r) => {
      const s = parseLocalDate(r.date_heure_debut)
      const e = r.date_heure_fin ? parseLocalDate(r.date_heure_fin) : new Date(s.getTime() + 60 * 60 * 1000)

      const sHour = s.getHours() + s.getMinutes() / 60
      const eHour = e.getHours() + e.getMinutes() / 60

      const clampedStart = Math.max(sHour, startHourBase)
      const clampedEnd = Math.min(Math.max(eHour, clampedStart + 0.5), 21)

      const top = Math.round((clampedStart - startHourBase) * 60)
      const height = Math.max(36, Math.round((clampedEnd - clampedStart) * 60))

      const clientName = r.nom_affiche || r.user?.nom || 'Client'
      const initials = clientName
        .split(' ')
        .map((n) => n.charAt(0))
        .slice(0, 2)
        .join('')
        .toUpperCase()

      return {
        id: `#${r.id}`,
        rawId: r.id,
        title: r.salle?.nom || 'Réservation',
        client: clientName,
        clientInitials: initials || 'CL',
        salle: r.salle?.nom || 'Salle',
        service: r.salle?.nom || 'Location de salle',
        status: (r.status || 'en_attente').toUpperCase(),
        date: new Intl.DateTimeFormat('fr-FR', { month: 'short', day: 'numeric', year: 'numeric' }).format(s),
        time: formatHourRange(r.date_heure_debut, r.date_heure_fin),
        personnes: r.nombre_personnes || 1,
        colorKey: getStatusColorKey(r.status),
        top,
        height,
      }
    })
}

// Sélectionner un créneau pour afficher la popup de détails
const selectAppointment = (appt, event) => {
  if (event && event.currentTarget) {
    const rect = event.currentTarget.getBoundingClientRect()
    const parentRect = event.currentTarget.closest('.calendar-container')?.getBoundingClientRect() || { left: 0, top: 0 }

    let left = rect.left - parentRect.left + rect.width + 8
    if (left + 220 > 850) {
      left = Math.max(10, rect.left - parentRect.left - 210)
    }

    const top = Math.max(10, rect.top - parentRect.top)
    popupPosition.value = {
      left: `${left}px`,
      top: `${top}px`,
    }
  }
  selectedAppointment.value = appt
}

// Vue Mois (grille de 35 cases)
const monthDays = computed(() => {
  const curr = new Date(currentDate.value)
  const year = curr.getFullYear()
  const month = curr.getMonth()

  const firstDay = new Date(year, month, 1)
  const day = firstDay.getDay()
  const diffToMonday = day === 0 ? -6 : 1 - day

  const start = new Date(firstDay)
  start.setDate(firstDay.getDate() + diffToMonday)

  const grid = []
  const iter = new Date(start)
  const todayStr = new Date().toDateString()

  for (let i = 0; i < 35; i++) {
    const isCurrentMonth = iter.getMonth() === month
    const isToday = iter.toDateString() === todayStr
    const appts = getDayAppointments(iter)

    grid.push({
      dateObj: new Date(iter),
      dayNumber: iter.getDate(),
      isCurrentMonth,
      isToday,
      appointments: appts,
    })

    iter.setDate(iter.getDate() + 1)
  }

  return grid
})
</script>

<template>
  <AppAdmin>
    <div class="mx-auto max-w-7xl pb-10">
      <section
        class="w-full rounded-2xl border border-[#EEF1F5] bg-white px-3.5 sm:px-6 py-4 sm:py-5 shadow-[0_4px_20px_-4px_rgba(15,23,42,0.05)]"
      >
        <!-- HEADER AGENDA (STYLE DEMANDÉ) -->
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
          <!-- MOIS / ANNÉE & NAVIGATION -->
          <div class="flex items-center gap-3">
            <h2 class="text-[18px] font-semibold text-[#111827]">
              {{ currentMonthYear }}
            </h2>

            <div class="flex items-center gap-1">
              <button
                type="button"
                @click="goPrevious"
                class="flex h-[27px] w-[27px] items-center justify-center rounded-[7px] border border-[#DDE5EF] text-[#6B7280] transition hover:bg-[#F0F4FA] hover:text-[#111827]"
                title="Période précédente"
              >
                <ChevronLeft :size="14" />
              </button>

              <button
                type="button"
                @click="goToToday"
                class="h-[27px] rounded-[7px] border border-[#DDE5EF] px-2 text-[10px] font-medium text-[#6B7280] transition hover:bg-[#F0F4FA] hover:text-[#111827]"
              >
                Aujourd'hui
              </button>

              <button
                type="button"
                @click="goNext"
                class="flex h-[27px] w-[27px] items-center justify-center rounded-[7px] border border-[#DDE5EF] text-[#6B7280] transition hover:bg-[#F0F4FA] hover:text-[#111827]"
                title="Période suivante"
              >
                <ChevronRight :size="14" />
              </button>
            </div>
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <!-- FILTRE SALLE DISCRET -->
            <div class="flex items-center gap-1.5">
              <select
                v-model="selectedSalleId"
                class="h-[27px] rounded-[7px] border border-[#DDE5EF] bg-white px-2 text-[10px] font-medium text-[#6B7280] outline-none transition hover:bg-[#F0F4FA]"
              >
                <option value="">Toutes les salles</option>
                <option v-for="s in salles" :key="s.id" :value="s.id">
                  {{ s.nom }}
                </option>
              </select>
            </div>

            <!-- WEEK / MONTH SWITCHER (STYLE DEMANDÉ) -->
            <div
              class="flex h-[27px] overflow-hidden rounded-[7px] border border-[#DDE5EF] bg-white"
            >
              <button
                type="button"
                @click="activeView = 'Week'; selectedAppointment = null"
                class="w-[64px] text-[10px] font-medium transition cursor-pointer"
                :class="
                  activeView === 'Week'
                    ? 'bg-white text-[#1769E0] shadow-xs'
                    : 'bg-[#F0F4FA] text-[#6B7280]'
                "
              >
                Week
              </button>

              <button
                type="button"
                @click="activeView = 'Month'; selectedAppointment = null"
                class="w-[64px] text-[10px] font-medium transition cursor-pointer"
                :class="
                  activeView === 'Month'
                    ? 'bg-white text-[#1769E0] shadow-xs'
                    : 'bg-[#F0F4FA] text-[#6B7280]'
                "
              >
                Month
              </button>
            </div>

            <!-- BOUTON ADD NEW (STYLE DEMANDÉ) -->
            <RouterLink
              :to="{ name: 'create-reservation' }"
              class="flex h-[27px] items-center gap-1 rounded-[7px] bg-[#1769E0] px-3 text-[10px] font-medium text-white shadow-xs transition hover:bg-[#095BCB]"
            >
              <Plus :size="12" />
              Add New
            </RouterLink>

            <!-- BOUTON REFRESH -->
            <button
              type="button"
              @click="loadData"
              :disabled="isLoading"
              class="flex h-[27px] w-[27px] items-center justify-center rounded-[7px] border border-[#DDE5EF] text-[#6B7280] hover:bg-[#F0F4FA]"
              title="Rafraîchir"
            >
              <RefreshCw :size="12" :class="{ 'animate-spin': isLoading }" />
            </button>
          </div>
        </div>

        <!-- ========================================================================= -->
        <!-- AGENDA VUE SEMAINE (EXACTEMENT SELON LE DESIGN FOURNI)                   -->
        <!-- ========================================================================= -->
        <div
          v-if="activeView === 'Week'"
          class="calendar-container relative w-full overflow-x-auto"
          @click="selectedAppointment = null"
        >
          <div class="min-w-[700px]">
            <!-- DAYS HEADER -->
            <div class="grid grid-cols-[48px_repeat(7,minmax(0,1fr))]">
              <!-- CALENDAR ICON -->
              <div
                class="flex h-[46px] items-center justify-center bg-[#EFF4FF]"
              >
                <CalendarDays
                  :size="17"
                  :stroke-width="1.7"
                  class="text-[#1769E0]"
                />
              </div>

              <!-- 7 JOURS -->
              <div
                v-for="day in weekDays"
                :key="day.name"
                class="flex h-[46px] flex-col items-center justify-center border-b border-[#EEF1F5]"
              >
                <span
                  class="text-[10px] font-semibold"
                  :class="day.active ? 'text-[#1769E0]' : 'text-[#111827]'"
                >
                  {{ day.name }}
                </span>

                <span
                  class="mt-[2px] text-[9px]"
                  :class="
                    day.active
                      ? 'font-semibold text-[#1769E0]'
                      : 'text-[#9CA3AF]'
                  "
                >
                  {{ day.date }}
                </span>
              </div>
            </div>

            <!-- CALENDAR BODY -->
            <div class="grid grid-cols-[48px_repeat(7,minmax(0,1fr))]">
              <!-- HOURS COLUMN -->
              <div class="relative">
                <div
                  v-for="hour in hours"
                  :key="hour"
                  class="relative flex h-[60px] items-start justify-center"
                >
                  <span
                    class="absolute -top-[5px] left-0 text-[10px] font-medium text-[#8994A4]"
                  >
                    {{ hour }}
                  </span>

                  <!-- small ticks -->
                  <div
                    class="absolute right-0 top-0 w-[12px] border-t border-[#D9DEE6]"
                  />
                </div>
              </div>

              <!-- DAYS COLUMNS -->
              <div
                v-for="day in weekDays"
                :key="day.name"
                class="relative border-l border-[#EDF0F4]"
                :style="{ height: `${hours.length * 60}px` }"
              >
                <!-- horizontal lines (chaque heure) -->
                <div
                  v-for="index in hours.length"
                  :key="index"
                  class="absolute left-0 right-0 border-t border-[#EDF0F4]"
                  :style="{ top: `${(index - 1) * 60}px` }"
                />

                <!-- half-hour lines (toutes les 30 min) -->
                <div
                  v-for="index in hours.length"
                  :key="'half-' + index"
                  class="absolute left-0 right-0 border-t border-[#F5F6F8]"
                  :style="{ top: `${(index - 1) * 60 + 30}px` }"
                />

                <!-- APPOINTMENTS DU JOUR -->
                <template
                  v-for="appointment in getDayAppointments(day.dateObj)"
                  :key="appointment.id"
                >
                  <div
                    @click.stop="selectAppointment(appointment, $event)"
                    class="absolute left-0 right-0 mx-[2px] overflow-hidden border-t-[2px] px-[7px] py-[7px] transition hover:shadow-md cursor-pointer z-10 rounded-[2px]"
                    :class="colorClasses[appointment.colorKey].container"
                    :style="{
                      top: appointment.top + 'px',
                      height: appointment.height + 'px',
                    }"
                  >
                    <div
                      class="text-[8px] font-medium"
                      :class="colorClasses[appointment.colorKey].text"
                    >
                      {{ appointment.id }}
                    </div>

                    <div
                      class="mt-[2px] text-[9px] font-semibold truncate"
                      :class="colorClasses[appointment.colorKey].text"
                    >
                      {{ appointment.title }}
                    </div>

                    <div
                      v-if="appointment.height > 50"
                      class="mt-[1px] text-[8px] text-slate-500 truncate"
                    >
                      {{ appointment.client }}
                    </div>

                    <!-- AVATAR / INITIALS -->
                    <div
                      class="absolute bottom-[6px] right-[6px] flex h-[16px] w-[16px] items-center justify-center overflow-hidden rounded-full border border-white bg-[#1769E0] text-[7px] font-bold text-white shadow-xs"
                    >
                      {{ appointment.clientInitials }}
                    </div>
                  </div>
                </template>
              </div>
            </div>

            <!-- DETAIL POPUP (DESIGN EXACT FOURNI) -->
            <div
              v-if="selectedAppointment"
              @click.stop
              class="absolute z-30 w-[185px] rounded-[7px] border border-[#DCE1E8] bg-white p-[11px] shadow-[0_5px_20px_rgba(0,0,0,0.14)] animate-in fade-in zoom-in-95 duration-150"
              :style="popupPosition"
            >
              <!-- CLIENT -->
              <div class="flex items-center justify-between gap-[7px]">
                <div class="flex items-center gap-[7px]">
                  <div
                    class="flex h-[18px] w-[18px] items-center justify-center rounded-full bg-[#EFF4FF] text-[8px] font-bold text-[#1769E0]"
                  >
                    {{ selectedAppointment.clientInitials }}
                  </div>

                  <div>
                    <div
                      class="text-[10px] font-semibold leading-[11px] text-[#172033]"
                    >
                      {{ selectedAppointment.client }}
                    </div>

                    <div class="text-[7px] text-[#A0A8B4]">
                      {{ selectedAppointment.id }}
                    </div>
                  </div>
                </div>

                <button
                  type="button"
                  @click="selectedAppointment = null"
                  class="text-[10px] text-slate-400 hover:text-slate-700"
                >
                  ✕
                </button>
              </div>

              <!-- SERVICE / SALLE -->
              <div class="mt-[12px] grid grid-cols-[45px_1fr] gap-y-[5px]">
                <span class="text-[7px] text-[#A0A8B4]">
                  Service
                </span>

                <span class="text-[7px] font-medium text-[#596273] truncate">
                  {{ selectedAppointment.service }}
                </span>

                <span class="text-[7px] text-[#A0A8B4]">
                  Status
                </span>

                <span
                  class="inline-flex w-fit rounded-full px-[8px] py-[2px] text-[6px] font-semibold"
                  :class="colorClasses[selectedAppointment.colorKey].badge"
                >
                  {{ selectedAppointment.status }}
                </span>
              </div>

              <!-- DATE & TIME -->
              <div class="mt-[10px] flex items-center gap-[6px]">
                <Clock3
                  :size="13"
                  :stroke-width="1.5"
                  class="text-[#687484] shrink-0"
                />

                <span class="text-[7px] text-[#687484]">
                  {{ selectedAppointment.date }}
                  &nbsp;&nbsp;
                  {{ selectedAppointment.time }}
                </span>
              </div>

              <!-- PARTICIPANTS -->
              <div class="mt-[9px] flex items-center gap-[6px]">
                <UserRound
                  :size="13"
                  :stroke-width="1.5"
                  class="text-[#687484] shrink-0"
                />

                <span class="text-[7px] text-[#687484]">
                  {{ selectedAppointment.personnes }} personne(s)
                </span>
              </div>

              <!-- LIEN VERS DOSSIER -->
              <RouterLink
                :to="{ name: 'info-reservation', params: { id: selectedAppointment.rawId } }"
                class="mt-[10px] flex h-[25px] w-full items-center justify-center rounded-[5px] bg-[#1769E0] text-[8px] font-semibold text-white shadow-xs transition hover:bg-[#095BCB]"
              >
                Voir la réservation
              </RouterLink>
            </div>
          </div>
        </div>

        <!-- ========================================================================= -->
        <!-- AGENDA VUE MOIS (HARMONISÉE AVEC LE MÊME DESIGN ÉPURÉ)                   -->
        <!-- ========================================================================= -->
        <div v-else class="w-full overflow-hidden rounded-xl border border-[#EEF1F5]">
          <!-- EN-TÊTE DES JOURS -->
          <div class="grid grid-cols-7 border-b border-[#EEF1F5] bg-[#F8FAFC] text-center text-[10px] font-semibold text-[#111827]">
            <div class="py-2.5">Lun.</div>
            <div class="py-2.5">Mar.</div>
            <div class="py-2.5">Mer.</div>
            <div class="py-2.5">Jeu.</div>
            <div class="py-2.5">Ven.</div>
            <div class="py-2.5">Sam.</div>
            <div class="py-2.5">Dim.</div>
          </div>

          <!-- GRILLE DU MOIS (35 CASES) -->
          <div class="grid grid-cols-7 divide-x divide-y divide-[#EEF1F5] bg-white">
            <div
              v-for="(cell, i) in monthDays"
              :key="i"
              class="min-h-[90px] p-2 flex flex-col justify-between transition hover:bg-[#F9FBFC]"
              :class="!cell.isCurrentMonth ? 'opacity-40 bg-slate-50/50' : ''"
            >
              <div class="flex items-center justify-between">
                <span
                  class="flex h-5 w-5 items-center justify-center rounded-full text-[10px] font-semibold"
                  :class="cell.isToday ? 'bg-[#1769E0] text-white' : 'text-[#111827]'"
                >
                  {{ cell.dayNumber }}
                </span>

                <span v-if="cell.appointments.length > 0" class="text-[8px] font-medium text-[#1769E0]">
                  {{ cell.appointments.length }} rés.
                </span>
              </div>

              <!-- LISTE DES RÉSERVATIONS DANS LA CASE -->
              <div class="mt-1 space-y-1">
                <div
                  v-for="app in cell.appointments.slice(0, 2)"
                  :key="app.id"
                  @click="selectAppointment(app, $event)"
                  class="cursor-pointer truncate rounded-[3px] border-t-[2px] px-1.5 py-0.5 text-[8px] font-medium transition hover:opacity-90"
                  :class="[colorClasses[app.colorKey].container, colorClasses[app.colorKey].text]"
                  :title="app.title + ' - ' + app.client"
                >
                  {{ app.title }}
                </div>
                <div
                  v-if="cell.appointments.length > 2"
                  class="text-[7px] text-[#A0A8B4] font-semibold"
                >
                  +{{ cell.appointments.length - 2 }} autre(s)
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </AppAdmin>
</template>
