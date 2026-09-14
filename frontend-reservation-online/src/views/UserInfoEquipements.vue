<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import NavBar from '@/layouts/NavBar.vue'
import Footer from '@/layouts/Footer.vue'
import { useEquipementsStore } from '@/store/equipements'
import {
    ArrowLeft,
    Package,
    CheckCircle2,
    AlertCircle,
    Calendar,
    Loader2,
    ArrowUpRight,
    Sparkles,
    ShieldCheck,
    Layers,
} from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const equipementsStore = useEquipementsStore()

const equipementId = route.params.id
const equipement = ref(null)
const isFetching = ref(true)
const fetchError = ref(null)

const isConnected = computed(() => !!localStorage.getItem('token'))

const defaultPlaceholder = '/images/equipements/equipement-1.png'

onMounted(async () => {
    try {
        isFetching.value = true
        equipement.value = await equipementsStore.fetchEquipement(equipementId)
    } catch (err) {
        fetchError.value = err.message || "Impossible de charger les informations de cet équipement."
        console.error("Erreur fetchEquipement:", err)
    } finally {
        isFetching.value = false
    }
})

const isDisponible = computed(() => {
    return equipement.value && (!equipement.value.status || equipement.value.status.toLowerCase() === 'disponible')
})

const formatDate = (dateStr) => {
    if (!dateStr) return 'N/A'
    try {
        return new Intl.DateTimeFormat('fr-FR', {
            day: '2-digit',
            month: 'long',
            year: 'numeric',
        }).format(new Date(dateStr))
    } catch {
        return dateStr
    }
}

const handleReserver = () => {
    const token = localStorage.getItem('token')
    if (!token) {
        router.push({ name: 'login', query: { redirect: 'reservation', equipement_id: equipementId } })
        return
    }
    router.push({ name: 'create-reservation', query: { equipement_id: equipementId } })
}
</script>

<template>
    <div class="min-h-[80vh] bg-[#f6f6f4] text-[#151515]">
        <NavBar />

        <div class="px-4 py-10 sm:px-6 lg:px-10">
            <div class="mx-auto max-w-[1180px]">


                <div
                    v-if="isFetching"
                    class="flex min-h-[420px] items-center justify-center rounded-[14px] bg-white"
                >
                    <div class="flex flex-col items-center gap-3">
                        <Loader2 :size="34" class="animate-spin text-slate-800" />
                        <p class="text-sm text-[#777]">Chargement des détails de l'équipement...</p>
                    </div>
                </div>

                <div
                    v-else-if="fetchError"
                    class="rounded-[14px] border border-[#eaded9] bg-white p-10 text-center"
                >
                    <AlertCircle :size="42" class="mx-auto mb-3 text-slate-800" />
                    <h2 class="text-xl font-semibold text-[#191919]">Équipement introuvable</h2>
                    <p class="mt-2 text-sm text-[#777]">{{ fetchError }}</p>
                    <RouterLink
                        :to="{ name: 'equipements' }"
                        class="mt-6 inline-flex items-center gap-2 rounded-[9px] bg-[#191919] px-5 py-3 text-xs font-semibold text-white transition hover:bg-black"
                    >
                        Retourner au catalogue
                    </RouterLink>
                </div>

                <div v-else-if="equipement">
                    <section class="overflow-hidden rounded-[15px] border border-[#ecebe7] bg-white" v-scroll-reveal="{ direction: 'up', delay: 80 }">
                        <div class="grid min-h-[465px] grid-cols-1 lg:grid-cols-[1.06fr_0.98fr_1fr]">
                            <!-- IMAGE -->
                            <div class="relative min-h-[390px] overflow-hidden bg-[#e9e8e4] lg:min-h-0">
                                <img
                                    :src="equipement.image_url || equipement.image || defaultPlaceholder"
                                    :alt="equipement.nom"
                                    class="absolute inset-0 h-full w-full object-cover"
                                />

                                <div class="absolute inset-0 bg-gradient-to-t from-black/45 via-black/0 to-black/5"></div>

                                <div class="absolute left-5 top-5">
                                    <div
                                        class="inline-flex items-center gap-1.5 rounded-full border border-white/35 bg-white/15 px-3 py-1.5 text-[11px] font-medium text-white backdrop-blur-md"
                                    >
                                        <Sparkles :size="12" />
                                        <span>Fiche Matériel</span>
                                    </div>


                                </div>

                                <div class="absolute bottom-5 left-5 right-5 flex items-end justify-between gap-4">
                                    <div>
                                        <p class="text-[11px] font-medium uppercase tracking-[0.12em] text-white/70">
                                            {{ isDisponible ? 'Disponible' : 'Actuellement indisponible' }}
                                        </p>
                                        <p class="mt-1 text-xl font-semibold leading-tight text-white">
                                            {{ equipement.nom }}
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

                            <!-- CENTRE -->
                            <div class="flex flex-col justify-between border-b border-[#ecebe7] px-7 py-9 sm:px-10 lg:border-b-0 lg:border-r lg:border-[#ecebe7]">
                                <div>
                                    <p class="text-[13px] font-medium text-[#7b7b7b]">Votre matériel</p>

                                    <h1 class="mt-5 max-w-[320px] font-serif text-[42px] leading-[0.98] tracking-[-0.04em] text-[#191919]">
                                        {{ equipement.nom }}
                                    </h1>

                                    <div class="mt-4 flex items-start gap-2 text-[13px] leading-5 text-[#777]">
                                        <Calendar :size="15" class="mt-0.5 shrink-0 text-slate-800" />
                                        <span>Ajouté le {{ formatDate(equipement.created_at) }}</span>
                                    </div>

                                    <div class="mt-10 flex items-end gap-2">
                                        <span class="font-serif text-[44px] leading-none tracking-[-0.04em] text-[#000000]">
                                            Description
                                        </span>
                                    </div>

                                    <p class="mt-3 max-w-[280px] text-[13px] leading-6 text-[#777]">
                                        {{ equipement.description || "Aucune description détaillée n'est renseignée pour cet équipement." }}
                                    </p>
                                </div>

                                <div class="mt-10">
                                    <button
                                        type="button"
                                        @click="handleReserver"
                                        class="group inline-flex w-full items-center justify-between rounded-[9px] border border-[#1d293d] px-4 py-3 text-[12px] font-medium text-slate-800 transition hover:bg-[#181818] hover:text-white"
                                    >
                                        <span>{{ isConnected ? 'Réserver cet équipement' : 'Se connecter pour réserver' }}</span>
                                        <ArrowUpRight :size="16" class="transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5" />
                                    </button>
                                </div>
                            </div>

                            <!-- DROITE -->
                            <div class="flex flex-col justify-between px-7 py-9 sm:px-10">
                                <div>
                                    <p class="text-[12px] font-medium text-slate-800">Ce qui est inclus</p>

                                    <div class="mt-6 space-y-5">
                                        <div class="flex gap-3 border-b border-[#efeee9] pb-4">
                                            <Package :size="17" class="mt-0.5 shrink-0 text-slate-800" />
                                            <div>
                                                <p class="text-[13px] font-medium text-[#5d5d5d]">Quantité en stock</p>
                                                <p class="mt-1 text-[15px] font-medium text-[#222]">
                                                    {{ equipement.stock_total || 0 }} unités
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
                                                    {{ equipement.status || (isDisponible ? 'Disponible' : 'Indisponible') }}
                                                </p>
                                            </div>
                                        </div>

                                        <div class="flex gap-3">
                                            <ShieldCheck :size="17" class="mt-0.5 shrink-0 text-slate-800" />
                                            <div>
                                                <p class="text-[13px] font-medium text-[#5d5d5d]">État du matériel</p>
                                                <p class="mt-1 text-[15px] font-medium text-[#222]">
                                                    Certifié conforme
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions bas de colonne -->
                                <div class="mt-9 border-t border-[#ecebe7] pt-6">
                                    <RouterLink
                                        :to="{ name: 'equipements' }"
                                        class="inline-flex w-full items-center justify-center rounded-[8px] border border-[#deddd9] bg-[#fafaf8] py-2.5 text-[11px] font-semibold text-[#333] transition hover:bg-white"
                                    >
                                        Retour à la liste
                                    </RouterLink>
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