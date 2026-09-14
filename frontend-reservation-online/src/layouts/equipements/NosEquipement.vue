<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useEquipementsStore } from '@/store/equipements'
import {
    Eye,
    MapPin,
    Package,
    Banknote,
    ArrowUpRight,
    Monitor,
    Volume2,
    Wifi,
    Calendar,
    CheckCircle2,
    AlertCircle,
} from 'lucide-vue-next'

const router = useRouter()
const equipementsStore = useEquipementsStore()

// Filtre par statut actif (par défaut 'disponible')
const activeStatusFilter = ref('disponible') // 'disponible', 'all', 'indisponible'

// État de connexion
const isConnected = computed(() => !!localStorage.getItem('token'))

const formatAge = (createdAt) => {
    if (!createdAt) return 'Disponible'
    const diffDays = Math.floor((new Date() - new Date(createdAt)) / (1000 * 60 * 60 * 24))
    if (diffDays < 30) return 'Nouveau'
    if (diffDays < 365) return `${Math.floor(diffDays / 30)} mois`
    return `${Math.floor(diffDays / 365)} an${Math.floor(diffDays / 365) > 1 ? 's' : ''}`
}

const defaultPlaceholder = '/images/equipements/equipement-1.png'

const allEquipments = computed(() => {
    return equipementsStore.equipements.map((eq, index) => {
        const isDispo = !eq.status || eq.status.toLowerCase() === 'disponible'
        const img = eq.image_url || eq.image || defaultPlaceholder

        return {
            id: eq.id,
            name: eq.nom || `Équipement ${index + 1}`,
            image: img,
            age: formatAge(eq.created_at),
            status: isDispo ? 'Disponible' : 'Indisponible',
            isDisponible: isDispo,
            location: 'Disponible sur site',
            quantity: eq.stock_total || 0,
            price: eq.prix ? new Intl.NumberFormat('fr-FR').format(eq.prix) : 'Inclus',
            type: eq.description ? (eq.description.length > 25 ? eq.description.substring(0, 25) + '...' : eq.description) : 'Matériel Pro',
            raw: eq,
        }
    })
})

const countDisponibles = computed(() => allEquipments.value.filter(e => e.isDisponible).length)
const countAll = computed(() => allEquipments.value.length)
const countIndisponibles = computed(() => allEquipments.value.filter(e => !e.isDisponible).length)

const equipments = computed(() => {
    if (activeStatusFilter.value === 'disponible') {
        return allEquipments.value.filter((e) => e.isDisponible)
    } else if (activeStatusFilter.value === 'indisponible') {
        return allEquipments.value.filter((e) => !e.isDisponible)
    }
    return allEquipments.value
})

onMounted(() => {
    equipementsStore.fetchEquipements()
})

const handleDetails = (equipment) => {
    router.push({ name: 'info-user-equipement', params: { id: equipment.id } })
}

const handleVoirTous = () => {
    const token = localStorage.getItem('token')
    if (!token) {
        router.push({ name: 'login' })
        return
    }
    activeStatusFilter.value = 'all'
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
                        Des équipements pensés

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
                        Découvrez nos équipements disponibles et
                        choisissez ceux qui correspondent le mieux
                        à vos besoins.
                    </p>

                </div>


                <!-- VOIR TOUS -->

                <button
                    type="button"
                    @click="handleVoirTous"
                    class="hidden shrink-0 items-center gap-2
                           rounded-full
                           border border-[#E2E8F0]
                           bg-white
                           px-5 py-3
                           text-[13px]
                           font-medium
                           text-[#0F172A]
                           shadow-[0_4px_20px_-4px_rgba(15,23,42,0.06)]
                           transition-all duration-200
                           hover:border-[#4F46E5]
                           hover:bg-[#EEF2FF]
                           hover:text-[#3730A3]
                           sm:flex
                           cursor-pointer"
                >
                    Voir tous les équipements

                    <ArrowUpRight
                        :size="16"
                        :stroke-width="1.8"
                    />
                </button>

            </div>

            <!-- ================================================= -->
            <!-- BARRE DE FILTRE PAR STATUT (Visible uniquement quand connecté) -->
            <!-- ================================================= -->

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
                Nos Equipements
            </div>
            </div>


            <!-- ================================================= -->
            <!-- GRILLE -->
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
                    v-for="equipment in equipments"
                    :key="equipment.id"
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
                            :src="equipment.image"
                            :alt="equipment.name"
                            decoding="async"
                            class="h-full w-full
                                   object-cover
                                   transition-transform
                                   duration-700
                                   ease-out
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
                        <!-- BADGE -->
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
                            {{ equipment.age }}
                        </div>


                        <!-- ================================================= -->
                        <!-- ACTIONS -->
                        <!-- ================================================= -->

                        <div
                            class="absolute right-4 top-4
                                   flex flex-col gap-2"
                        >

                            <!-- DETAILS (OEIL) -->

                            <button
                                type="button"
                                @click="handleDetails(equipment)"
                                class="flex h-10 w-10
                                       items-center justify-center
                                       rounded-full
                                       bg-[#0F172A]/75
                                       text-white
                                       backdrop-blur-md
                                       transition-all duration-200
                                       hover:bg-[#4F46E5]
                                       active:scale-[0.95]
                                       cursor-pointer"
                                aria-label="Voir les détails de l'équipement"
                                title="Voir les détails de l'équipement"
                            >
                                <Eye
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
                                    {{ equipment.price }}

                                    <span
                                        v-if="equipment.price !== 'Inclus'"
                                        class="ml-1
                                               text-[12px]
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
                                    equipment.status === 'Disponible'
                                        ? 'bg-[#EEF2FF] text-[#3730A3]'
                                        : 'bg-[#F1F5F9] text-[#64748B]'
                                "
                            >
                                {{ equipment.status }}
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
                                {{ equipment.name }}
                            </h3>



                        </div>


                        <!-- ================================================= -->
                        <!-- SEPARATEUR -->
                        <!-- ================================================= -->

                        <div
                            class="my-5 h-px
                                   bg-[#E2E8F0]"
                        ></div>


                        <!-- ================================================= -->
                        <!-- INFORMATIONS -->
                        <!-- ================================================= -->

                        <div
                            class="grid grid-cols-2
                                   gap-3"
                        >

                            <!-- QUANTITE -->

                            <div
                                class="flex items-center gap-3"
                            >

                                <div
                                    class="flex h-9 w-9
                                           items-center
                                           justify-center
                                           rounded-[9px]
                                           bg-[#EEF2FF]
                                           text-[#4F46E5]"
                                >
                                    <Package
                                        :size="16"
                                        :stroke-width="1.8"
                                    />
                                </div>

                                <div>

                                    <p
                                        class="text-[11px]
                                               font-medium
                                               text-[#0F172A]"
                                    >
                                        Quantité
                                    </p>

                                    <p
                                        class="mt-0.5
                                               text-[11px]
                                               text-[#64748B]"
                                    >
                                        {{ equipment.quantity }}
                                        disponibles
                                    </p>

                                </div>

                            </div>


                            <!-- TYPE -->

                            <div
                                class="flex items-center gap-3"
                            >

                                <div
                                    class="flex h-9 w-9
                                           items-center
                                           justify-center
                                           rounded-[9px]
                                           bg-[#EEF2FF]
                                           text-[#4F46E5]"
                                >
                                    <Monitor
                                        :size="16"
                                        :stroke-width="1.8"
                                    />
                                </div>

                                <div>

                                    <p
                                        class="text-[11px]
                                               font-medium
                                               text-[#0F172A]"
                                    >
                                        Type
                                    </p>

                                    <p
                                        class="mt-0.5
                                               text-[11px]
                                               text-[#64748B]"
                                    >
                                        {{ equipment.type }}
                                    </p>

                                </div>

                            </div>

                        </div>




                    </div>

                </article>

            </div>

        </div>
    </section>
</template>