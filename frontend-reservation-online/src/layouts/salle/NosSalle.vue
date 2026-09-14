<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useSallesStore } from '@/store/salles'
import {
    Plus,
    MapPin,
    Users,
    Banknote,
    ArrowUpRight,
    Calendar,
    CheckCircle2,
    AlertCircle,
    X,
    Loader2,
} from 'lucide-vue-next'

const router = useRouter()
const sallesStore = useSallesStore()

// Filtre par statut actif (par défaut 'disponible')
const activeStatusFilter = ref('disponible') // 'disponible', 'all', 'indisponible'

// État de connexion
const isConnected = computed(() => !!localStorage.getItem('token'))

// État de la modale de disponibilité (pour utilisateur connecté)
const isDispoModalOpen = ref(false)
const selectedSalleForDispo = ref(null)
const debutDateTime = ref('')
const finDateTime = ref('')
const checkingDispo = ref(false)
const dispoResult = ref(null)
const dispoError = ref(null)

const formatPrice = (val) => {
    if (!val && val !== 0) return '0'
    return new Intl.NumberFormat('fr-FR').format(val)
}

const formatAge = (createdAt) => {
    if (!createdAt) return 'Récent'
    const diffDays = Math.floor((new Date() - new Date(createdAt)) / (1000 * 60 * 60 * 24))
    if (diffDays < 30) return 'Nouveau'
    if (diffDays < 365) return `${Math.floor(diffDays / 30)} mois`
    return `${Math.floor(diffDays / 365)} an${Math.floor(diffDays / 365) > 1 ? 's' : ''}`
}

const defaultPlaceholder = 'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1200&q=80'

// Index d'image actuel pour chaque salle
const roomImageIndexes = ref({})
// Intervalles de carrousel pour chaque salle
const roomIntervals = ref({})
const AUTO_PLAY_DELAY = 4000 // 4 secondes entre chaque image

// Démarrer le carrousel pour une salle spécifique
const startRoomCarousel = (roomId, imagesCount) => {
  if (imagesCount <= 1) return
  
  // Initialiser l'index si pas déjà fait
  if (roomImageIndexes.value[roomId] === undefined) {
    roomImageIndexes.value[roomId] = 0
  }
  
  // Arrêter l'intervalle existant s'il y en a un
  if (roomIntervals.value[roomId]) {
    clearInterval(roomIntervals.value[roomId])
  }
  
  // Démarrer le nouvel intervalle
  roomIntervals.value[roomId] = setInterval(() => {
    roomImageIndexes.value[roomId] = (roomImageIndexes.value[roomId] + 1) % imagesCount
  }, AUTO_PLAY_DELAY)
}

// Arrêter le carrousel pour une salle spécifique
const stopRoomCarousel = (roomId) => {
  if (roomIntervals.value[roomId]) {
    clearInterval(roomIntervals.value[roomId])
    delete roomIntervals.value[roomId]
  }
}

// Obtenir l'image actuelle pour une salle
const getCurrentRoomImage = (salle) => {
  const images = salle.images || []
  if (images.length === 0) return defaultPlaceholder
  
  const index = roomImageIndexes.value[salle.id] || 0
  return images[index].url || images[index].path || defaultPlaceholder
}

// Liste globale mappée
const allRooms = computed(() => {
    return sallesStore.salles.map((salle, index) => {
        const isDispo = !salle.status || salle.status.toLowerCase() === 'disponible'

        return {
            id: salle.id,
            name: salle.nom || `Salle ${index + 1}`,
            images: salle.images || [],
            image: getCurrentRoomImage(salle),
            age: formatAge(salle.created_at),
            status: isDispo ? 'Disponible' : 'Occupée',
            isDisponible: isDispo,
            location: salle.localisation || 'Localisation non spécifiée',
            capacity: salle.capacite || 0,
            price: formatPrice(salle.prix),
            rawPrice: salle.prix,
            raw: salle,
        }
    })
})

// Compteurs pour les boutons de filtre
const countDisponibles = computed(() => allRooms.value.filter(r => r.isDisponible).length)
const countAll = computed(() => allRooms.value.length)
const countOccupes = computed(() => allRooms.value.filter(r => !r.isDisponible).length)

// Salles filtrées selon le filtre actif (par défaut 'disponible')
const rooms = computed(() => {
    if (activeStatusFilter.value === 'disponible') {
        return allRooms.value.filter((r) => r.isDisponible)
    } else if (activeStatusFilter.value === 'indisponible') {
        return allRooms.value.filter((r) => !r.isDisponible)
    }
    return allRooms.value
})

onMounted(() => {
    sallesStore.fetchSalles()
})

// Démarrer les carrousels pour toutes les salles avec plusieurs images
watch(
  () => sallesStore.salles,
  (salles) => {
    if (salles && salles.length > 0) {
      salles.forEach((salle) => {
        const imagesCount = salle.images?.length || 0
        if (imagesCount > 1) {
          startRoomCarousel(salle.id, imagesCount)
        }
      })
    }
  },
  { immediate: true }
)

onUnmounted(() => {
  // Arrêter tous les carrousels
  Object.keys(roomIntervals.value).forEach((roomId) => {
    stopRoomCarousel(roomId)
  })
})

const handlePlus = (room) => {
    const token = localStorage.getItem('token')
    if (!token) {
        router.push({ name: 'login', query: { redirect: `/reserver?salle_id=${room.id}`, salle_id: room.id } })
        return
    }
    router.push({ name: 'user-create-reservation', query: { salle_id: room.id } })
}

const handleVoir = (room) => {
    router.push({ name: 'info-user-salle', params: { id: room.id } })
}



const openDisponibiliteModal = (room) => {
    selectedSalleForDispo.value = room
    dispoResult.value = null
    dispoError.value = null

    // Initialiser dates par défaut : demain de 09:00 à 12:00
    const now = new Date()
    const tomorrow = new Date(now)
    tomorrow.setDate(tomorrow.getDate() + 1)
    const yyyy = tomorrow.getFullYear()
    const mm = String(tomorrow.getMonth() + 1).padStart(2, '0')
    const dd = String(tomorrow.getDate()).padStart(2, '0')

    debutDateTime.value = `${yyyy}-${mm}-${dd}T09:00`
    finDateTime.value = `${yyyy}-${mm}-${dd}T12:00`

    isDispoModalOpen.value = true
}

const closeDisponibiliteModal = () => {
    isDispoModalOpen.value = false
    selectedSalleForDispo.value = null
    dispoResult.value = null
    dispoError.value = null
}

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
        const formattedDebut = debutDateTime.value.replace('T', ' ') + ':00'
        const formattedFin = finDateTime.value.replace('T', ' ') + ':00'
        const res = await sallesStore.checkDisponibilite(
            selectedSalleForDispo.value.id,
            formattedDebut,
            formattedFin
        )
        dispoResult.value = res
    } catch (err) {
        dispoError.value = err.message || 'Erreur lors de la vérification de disponibilité.'
    } finally {
        checkingDispo.value = false
    }
}

const proceedToReservation = () => {
    const room = selectedSalleForDispo.value
    closeDisponibiliteModal()
    router.push({
        name: 'user-create-reservation',
        query: {
            salle_id: room.id,
            debut: debutDateTime.value,
            fin: finDateTime.value,
        },
    })
}
</script>

<template>
    <section
        class="w-full bg-[#F8FAFC]
               px-4 py-14
               sm:px-6
               lg:px-8 lg:py-5"
    >
        <div class="mx-auto max-w-[1400px]">

            <!-- ================================================= -->
            <!-- TITRE -->
            <!-- ================================================= -->

            <div class="mb-10 flex items-end justify-between gap-6">

                <div>


                    <h2
                        class="text-[36px]
                               font-semibold
                               leading-[1.05]
                               tracking-[-1.5px]
                               text-[#0F172A]
                               sm:text-[44px]"
                    >
                        Des salles pensées
                        <span class="font-normal italic">
                            pour vous.
                        </span>
                    </h2>

                    <p
                        class="mt-4 max-w-[620px]
                               text-[14px]
                               leading-[1.7]
                               text-[#64748B]"
                    >
                        Découvrez nos espaces disponibles et choisissez
                        celui qui correspond le mieux à vos besoins.
                    </p>
                </div>



            </div>



<!--

    <div class="flex items-center justify-between rounded-2xl bg-white p-3 shadow-sm border border-neutral-200">
      <div class="flex items-center gap-4">

        <div  class="font-serif text-2xl tracking-wider text-neutral-900 font-normal">
          Salle
        </div>
      </div>

      <div class="hidden items-center gap-8 md:flex">
        <button type="button"
                        @click="activeStatusFilter = 'disponible'" class="text-sm font-medium uppercase tracking-widest text-neutral-800 transition hover:text-neutral-500">
          Disponibles ({{ countDisponibles }})
      </button>
        <a href="#about" class="text-sm font-medium uppercase tracking-widest text-neutral-800 transition hover:text-neutral-500">
          Toutes ({{ countAll }})
        </a>
        <a href="#about" class="text-sm font-medium uppercase tracking-widest text-neutral-800 transition hover:text-neutral-500">
          outes ({{ countAll }})
        </a>
      </div>


    </div>

  </div>

 -->

            <div v-if="isConnected" class="mb-8 flex flex-row-reverse  flex-wrap items-center justify-between gap-4">

                <div class="flex items-center justify-between rounded-2xl bg-white p-3 shadow-sm border border-neutral-200">
                    <button
                        type="button"
                        @click="activeStatusFilter = 'disponible'"
                        :class="[
                            'rounded-full px-4 py-2 text-xs font-semibold transition-all duration-200 cursor-pointer tracking-widest text-neutral-800  hover:text-neutral-500',
                            activeStatusFilter === 'disponible'
                                ?  'bg-[#0F172A] text-white shadow-sm'
                                : 'text-[#64748B] hover:text-[#0F172A] hover:bg-slate-100/70'
                        ]"
                    >
                        Disponibles ({{ countDisponibles }})
                    </button>

                    <button
                        type="button"
                        @click="activeStatusFilter = 'all'"
                        :class="[
                            'rounded-full px-4 py-2 text-xs font-semibold transition-all duration-200 cursor-pointer tracking-widest text-neutral-800  hover:text-neutral-500',
                            activeStatusFilter === 'all'
                                ? 'bg-[#0F172A] text-white shadow-sm'
                                : 'text-[#64748B] hover:text-[#0F172A] hover:bg-slate-100/70'
                        ]"
                    >
                        Toutes ({{ countAll }})
                    </button>

                    
                </div>

                <div v-if="isConnected" class="flex items-center gap-2 text-xs  font-medium tracking-widest text-neutral-800  ">
                    Des salles de luxe
                </div>
            </div>


            <!-- ================================================= -->
            <!-- GRILLE 3 COLONNES -->
            <!-- ================================================= -->

            <div
                class="grid grid-cols-1
                       gap-5
                       md:grid-cols-2
                       xl:grid-cols-3"
            >

                <!-- ================================================= -->
                <!-- CARD -->
                <!-- ================================================= -->

                <article
                    v-for="room in rooms"
                    :key="room.id"
                    class="group relative overflow-hidden
                           rounded-[22px]
                           border border-[#E2E8F0]
                           bg-white
                           shadow-[0_4px_20px_-4px_rgba(15,23,42,0.06)]
                           transition-all duration-300
                           hover:-translate-y-1
                           hover:shadow-[0_12px_35px_-10px_rgba(15,23,42,0.14)]"
                >

                    <!-- ================================================= -->
                    <!-- IMAGE -->
                    <!-- ================================================= -->

                    <div
                        class="relative
                               aspect-[1.2]
                               overflow-hidden
                               bg-[#E2E8F0]"
                    >

                        <img
                            :src="room.image"
                            :alt="room.name"
                            decoding="async"
                            class="h-full w-full
                                   object-cover
                                   transition-opacity
                                   duration-700
                                   ease-in-out
                                   group-hover:scale-[1.035]"
                        />


                        <!-- OVERLAY -->
                        <div
                            class="pointer-events-none
                                   absolute inset-0
                                   bg-gradient-to-t
                                   from-black/20
                                   via-transparent
                                   to-black/5"
                        ></div>


                        <!-- ================================================= -->
                        <!-- BADGE AGE -->
                        <!-- ================================================= -->

                        <div
                            class="absolute left-4 top-4
                                   rounded-full
                                   bg-[#0F172A]/75
                                   px-3.5 py-2
                                   text-[11px]
                                   font-medium
                                   text-white
                                   backdrop-blur-md"
                        >
                            {{ room.age }}
                        </div>


                        <!-- ================================================= -->
                        <!-- ACTIONS -->
                        <!-- ================================================= -->

                        <div
                            class="absolute right-4 top-4
                                   flex flex-col gap-2"
                        >

                            <!-- PLUS -->
                            <button
                                type="button"
                                @click="handlePlus(room)"
                                class="flex h-10 w-10
                                       items-center justify-center
                                       rounded-full
                                       bg-[#0F172A]/75
                                       text-white
                                       backdrop-blur-md
                                       transition-all duration-200
                                       cursor-pointer
                                       active:scale-[0.95]"
                                :aria-label="isConnected ? 'Vérifier la disponibilité et réserver' : 'Se connecter pour réserver'"
                                :title="isConnected ? 'Vérifier la disponibilité' : 'Se connecter pour réserver'"
                            >
                                <Plus
                                    :size="19"
                                    :stroke-width="1.8"
                                />
                            </button>

                        </div>


                        <!-- ================================================= -->
                        <!-- INDICATEUR -->
                        <!-- ================================================= -->

                        <div
                            class="absolute bottom-4
                                   left-1/2
                                   flex -translate-x-1/2
                                   items-center gap-1.5
                                   rounded-full
                                   bg-[#0F172A]/55
                                   px-3 py-1.5
                                   backdrop-blur-md"
                        >

                            <span
                                class="h-1.5 w-1.5
                                       rounded-full
                                       bg-white"
                            ></span>

                            <span
                                class="h-1.5 w-1.5
                                       rounded-full
                                       bg-white/40"
                            ></span>

                            <span
                                class="h-1.5 w-1.5
                                       rounded-full
                                       bg-white/40"
                            ></span>

                            <span
                                class="h-1.5 w-1.5
                                       rounded-full
                                       bg-white/40"
                            ></span>

                        </div>

                    </div>


                    <!-- ================================================= -->
                    <!-- CONTENU -->
                    <!-- ================================================= -->

                    <div class="p-5">

                        <!-- PRIX + STATUS -->

                        <div
                            class="flex items-start
                                   justify-between gap-4"
                        >

                            <div>
                                <p
                                    class="text-[21px]
                                           font-bold
                                           tracking-[-0.6px]
                                           text-[#0F172A]"
                                >
                                    {{ room.price }}
                                    <span
                                        class="ml-1 text-[12px]
                                               font-medium
                                               text-[#64748B]"
                                    >
                                        FCFA
                                    </span>
                                </p>
                            </div>


                            <!-- STATUS -->

                            <span
                                class="inline-flex
                                       shrink-0
                                       rounded-full
                                       px-3 py-1.5
                                       text-[11px]
                                       font-semibold"
                                :class="
                                    room.status === 'Disponible'
                                        ? 'bg-[#EEF2FF] text-[#3730A3]'
                                        : 'bg-[#F1F5F9] text-[#64748B]'
                                "
                            >
                                {{ room.status }}
                            </span>

                        </div>


                        <!-- ================================================= -->
                        <!-- NOM + LOCALISATION -->
                        <!-- ================================================= -->

                        <div class="mt-4">

                            <h3
                                class="text-[18px]
                                       font-semibold
                                       tracking-[-0.3px]
                                       text-[#0F172A]"
                            >
                                {{ room.name }}
                            </h3>


                            <div
                                class="mt-2 flex items-center
                                       gap-2"
                            >

                                <MapPin
                                    :size="15"
                                    :stroke-width="1.8"
                                    class="text-[#4F46E5]"
                                />

                                <span
                                    class="text-[12px]
                                           text-[#64748B]"
                                >
                                    {{ room.location }}
                                </span>

                                <span
                                    class="ml-auto flex
                                           items-center gap-1
                                           text-[12px]
                                           font-medium
                                           text-[#0F172A]"
                                >

                                </span>

                                <button
                                    type="button"
                                    @click="handleVoir(room)"
                                    class="group flex items-center
                                           gap-2
                                           rounded-full
                                           bg-[#0F172A]
                                           py-2 pl-4 pr-2
                                           text-[11px]
                                           font-semibold
                                           text-white
                                           transition-all duration-200
                                           hover:bg-[#020617]
                                           active:scale-[0.97]
                                           cursor-pointer"
                                >

                                    <span>
                                        Voir
                                    </span>

                                    <span
                                        class="flex h-8 w-8
                                               items-center
                                               justify-center
                                               rounded-full
                                               bg-white
                                               text-[#0F172A]
                                               transition-transform
                                               duration-200
                                               group-hover:translate-x-0.5"
                                    >
                                        <ArrowUpRight
                                            :size="15"
                                            :stroke-width="1.8"
                                        />
                                    </span>

                                </button>

                            </div>

                        </div>


                        <!-- ================================================= -->
                        <!-- INFOS BAS -->
                        <!-- ================================================= -->





                        <!-- ================================================= -->
                        <!-- BAS : RESERVER / DISPONIBILITE CONNECTE -->
                        <!-- ================================================= -->

                        <div
                            v-if="isConnected"
                            class="mt-4 pt-3 border-t border-[#F1F5F9] flex items-center justify-between text-xs"
                        >
                            <span class="text-[#64748B] flex items-center gap-1">
                                <Calendar :size="13" class="text-[#4F46E5]" />
                                Disponibilité créneau :
                            </span>
                            <button
                                type="button"
                                @click="openDisponibiliteModal(room)"
                                class="text-[#4F46E5] font-semibold hover:underline cursor-pointer flex items-center gap-1"
                            >
                                <span>Vérifier en direct</span>
                                <ArrowUpRight :size="13" />
                            </button>
                        </div>

                    </div>

                </article>

            </div>

            <!-- ================================================= -->
            <!-- MODALE DE VÉRIFICATION DE DISPONIBILITÉ (CONNECTÉ) -->
            <!-- ================================================= -->
            <Teleport to="body">
                <div
                    v-if="isDispoModalOpen && selectedSalleForDispo"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm transition-all"
                    @click.self="closeDisponibiliteModal"
                >
                    <div class="relative w-full max-w-lg rounded-3xl bg-white p-6 sm:p-8 shadow-2xl transition-all">
                        <!-- Bouton Fermer -->
                        <button
                            type="button"
                            @click="closeDisponibiliteModal"
                            class="absolute right-5 top-5 flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200 hover:text-slate-800 transition cursor-pointer"
                        >
                            <X :size="18" />
                        </button>

                        <div class="flex items-center gap-3 mb-5">
                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#EEF2FF] text-[#4F46E5]">
                                <Calendar :size="22" />
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-[#0F172A]">
                                    {{ selectedSalleForDispo.name }}
                                </h3>
                                <p class="text-xs text-[#64748B]">
                                    Vérification de disponibilité en temps réel
                                </p>
                            </div>
                        </div>

                        <!-- Formulaire Créneau -->
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Date et heure de début
                                </label>
                                <input
                                    v-model="debutDateTime"
                                    type="datetime-local"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs text-slate-800 focus:border-[#4F46E5] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#4F46E5]/10"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Date et heure de fin
                                </label>
                                <input
                                    v-model="finDateTime"
                                    type="datetime-local"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs text-slate-800 focus:border-[#4F46E5] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#4F46E5]/10"
                                />
                            </div>

                            <button
                                type="button"
                                @click="checkCreneau"
                                :disabled="checkingDispo"
                                class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-[#0F172A] py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-slate-800 transition cursor-pointer disabled:opacity-50"
                            >
                                <Loader2 v-if="checkingDispo" :size="15" class="animate-spin" />
                                <span>{{ checkingDispo ? 'Vérification en cours...' : 'Vérifier ce créneau' }}</span>
                            </button>

                            <!-- Message d'erreur -->
                            <div
                                v-if="dispoError"
                                class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs text-rose-700 flex items-start gap-2"
                            >
                                <AlertCircle :size="15" class="shrink-0 mt-0.5" />
                                <span>{{ dispoError }}</span>
                            </div>

                            <!-- Résultat -->
                            <div
                                v-if="dispoResult"
                                class="rounded-2xl p-4 border transition-all"
                                :class="dispoResult.disponible ? 'border-emerald-200 bg-emerald-50/60' : 'border-rose-200 bg-rose-50/60'"
                            >
                                <div class="flex items-center gap-2 font-semibold text-sm" :class="dispoResult.disponible ? 'text-emerald-800' : 'text-rose-800'">
                                    <CheckCircle2 v-if="dispoResult.disponible" :size="18" class="text-emerald-600" />
                                    <AlertCircle v-else :size="18" class="text-rose-600" />
                                    <span>
                                        {{ dispoResult.disponible ? 'Salle disponible sur ce créneau !' : 'Salle déjà occupée sur ce créneau' }}
                                    </span>
                                </div>

                                <p class="mt-1 text-xs" :class="dispoResult.disponible ? 'text-emerald-700' : 'text-rose-700'">
                                    {{ dispoResult.disponible ? 'Aucun conflit détecté. Vous pouvez réserver immédiatement ce créneau.' : 'Veuillez sélectionner un autre horaire ou une autre salle.' }}
                                </p>

                                <button
                                    v-if="dispoResult.disponible"
                                    type="button"
                                    @click="proceedToReservation"
                                    class="mt-3 w-full inline-flex items-center justify-center gap-2 rounded-xl bg-[#4F46E5] py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-[#4338CA] transition cursor-pointer"
                                >
                                    <span>Procéder à la réservation</span>
                                    <ArrowUpRight :size="14" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Teleport>

        </div>
    </section>
</template>
