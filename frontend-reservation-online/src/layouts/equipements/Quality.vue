<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'

import {
    Armchair,
    Wifi,
    Wind,
    Monitor,
    Coffee,
    Volume2,
    Tv,
} from 'lucide-vue-next'

/*
|--------------------------------------------------------------------------
| Équipements
|--------------------------------------------------------------------------
*/

const equipments = [
    {
        image: '/images/equipements/equipement-1.png',
        value: '15+',
        title: 'VIDÉOPROJECTEURS',
        description:
            'Des vidéoprojecteurs performants pour vos présentations, formations, conférences et réunions professionnelles.',
    },

    {
        image: '/images/equipements/equipement-2.png',
        value: '10+',
        title: 'ÉQUIPEMENTS AUDIO',
        description:
            'Des microphones et solutions de sonorisation adaptés aux réunions, conférences et différents événements.',
    },

    {
        image: '/images/equipements/equipement-3.png',
        value: '12+',
        title: 'ÉCRANS & AFFICHAGE',
        description:
            'Des écrans et solutions d’affichage pour rendre vos présentations plus claires et plus professionnelles.',
    },

    {
        image: '/images/equipements/equipement-4.png',
        value: '20+',
        title: 'ACCESSOIRES',
        description:
            'Retrouvez également différents accessoires utiles pour compléter votre réservation et améliorer votre expérience.',
    },
]

const activeEquipment = ref(0)

let interval = null

/*
|--------------------------------------------------------------------------
| Changement automatique
|--------------------------------------------------------------------------
*/

onMounted(() => {
    // Préchargement en arrière-plan des images de la section Équipements
    equipments.forEach((eq) => {
        const img = new Image()
        img.src = eq.image
    })

    interval = setInterval(() => {
        activeEquipment.value =
            (activeEquipment.value + 1) % equipments.length
    }, 5000)
})

onBeforeUnmount(() => {
    clearInterval(interval)
})
</script>

<template>
    <section
        class="w-full bg-[#F8FAFC] px-4 py-16 sm:px-6 lg:px-8 lg:py-16 flex flex-col"
    >

        <!-- ===================================================== -->
        <!-- TITRE -->
        <!-- ===================================================== -->

        <h1
            class="text-center
                   font-[Georgia]
                   text-[42px]
                   font-normal
                   leading-tight
                   tracking-[-1px]
                   mb-10
                   text-[#0F172A]
                   sm:text-[48px]
                   lg:text-[56px]"
        >
            Pourquoi choisir nos équipements ?
        </h1>


        <!-- ===================================================== -->
        <!-- CONTENEUR -->
        <!-- ===================================================== -->

        <div
            class="mx-auto grid w-full max-w-[1400px]
                   grid-cols-1 gap-3
                   lg:grid-cols-[1fr_2fr]"
        >
        <div
                class="flex min-h-[520px]
                       flex-col justify-between
                       rounded-[24px]
                       bg-[#1f1d1d]
                       p-7
                       sm:p-9
                       lg:p-10"
            >

                <!-- ================================================= -->
                <!-- HAUT -->
                <!-- ================================================= -->

                <div>

                    <div
                        class="text-[68px]
                               font-semibold
                               leading-none
                               tracking-[-4px]
                               text-white
                               sm:text-[78px]
                               lg:text-[86px]"
                    >
                        50+
                    </div>


                    <div
                        class="mt-1
                               text-[16px]
                               font-bold
                               uppercase
                               tracking-[-0.3px]
                               text-white"
                    >
                        ÉQUIPEMENTS
                        <br />
                        DISPONIBLES
                    </div>

                </div>


                <!-- ================================================= -->
                <!-- SERVICES -->
                <!-- ================================================= -->

                <div
                    class="space-y-5"
                >

                    <!-- ============================================= -->
                    <!-- VIDEOPROJECTEUR -->
                    <!-- ============================================= -->

                    <div
                        class="flex items-center gap-3"
                    >

                        <div
                            class="flex h-9 w-9
                                   items-center justify-center
                                   rounded-full
                                   bg-white/10
                                   text-white"
                        >
                            <Monitor :size="17" />
                        </div>

                        <div>

                            <p
                                class="text-[11px]
                                       font-semibold
                                       text-white"
                            >
                                Vidéoprojecteurs
                            </p>

                            <p
                                class="mt-0.5 text-[10px]
                                       text-white/45"
                            >
                                Présentations et conférences
                            </p>

                        </div>

                    </div>


                    <!-- ============================================= -->
                    <!-- AUDIO -->
                    <!-- ============================================= -->

                    <div
                        class="flex items-center gap-3"
                    >

                        <div
                            class="flex h-9 w-9
                                   items-center justify-center
                                   rounded-full
                                   bg-white/10
                                   text-white"
                        >
                            <Volume2 :size="17" />
                        </div>

                        <div>

                            <p
                                class="text-[11px]
                                       font-semibold
                                       text-white"
                            >
                                Sonorisation
                            </p>

                            <p
                                class="mt-0.5 text-[10px]
                                       text-white/45"
                            >
                                Microphones et audio
                            </p>

                        </div>

                    </div>


                    <!-- ============================================= -->
                    <!-- ECRANS -->
                    <!-- ============================================= -->

                    <div
                        class="flex items-center gap-3"
                    >

                        <div
                            class="flex h-9 w-9
                                   items-center justify-center
                                   rounded-full
                                   bg-white/10
                                   text-white"
                        >
                            <Tv :size="17" />
                        </div>

                        <div>

                            <p
                                class="text-[11px]
                                       font-semibold
                                       text-white"
                            >
                                Écrans & affichage
                            </p>

                            <p
                                class="mt-0.5 text-[10px]
                                       text-white/45"
                            >
                                Affichage professionnel
                            </p>

                        </div>

                    </div>


                    <!-- ============================================= -->
                    <!-- ACCESSOIRES -->
                    <!-- ============================================= -->

                    <div
                        class="flex items-center gap-3"
                    >

                        <div
                            class="flex h-9 w-9
                                   items-center justify-center
                                   rounded-full
                                   bg-white/10
                                   text-white"
                        >
                            <Coffee :size="17" />
                        </div>

                        <div>

                            <p
                                class="text-[11px]
                                       font-semibold
                                       text-white"
                            >
                                Accessoires
                            </p>

                            <p
                                class="mt-0.5 text-[10px]
                                       text-white/45"
                            >
                                Compléments de réservation
                            </p>

                        </div>

                    </div>

                </div>


                <!-- ================================================= -->
                <!-- DESCRIPTION BAS -->
                <!-- ================================================= -->

                <div>

                    <p
                        class="max-w-[330px]
                               text-[12px]
                               leading-[1.6]
                               text-white/60"
                    >
                        Complétez votre réservation avec des équipements
                        adaptés à vos besoins et profitez d'une expérience
                        professionnelle, pratique et confortable.
                    </p>

                </div>

            </div>
            <!-- ===================================================== -->
            <!-- GRANDE CARTE IMAGE -->
            <!-- ===================================================== -->

            <div
                class="group relative min-h-[520px]
                       overflow-hidden rounded-[24px]
                       bg-[#0F172A]"
            >

                <!-- IMAGE -->

                <Transition name="room-image">

                    <img
                        :key="activeEquipment"
                        :src="equipments[activeEquipment].image"
                        alt="Équipement disponible"
                        decoding="async"
                        class="absolute inset-0
                               h-full w-full
                               object-cover"
                    />

                </Transition>


                <!-- OVERLAY -->

                <div
                    class="absolute inset-0
                           bg-gradient-to-t
                           from-black/80
                           via-black/20
                           to-black/10"
                ></div>


                <!-- CONTENU -->

                <div
                    class="relative z-10 flex min-h-[520px]
                           flex-col justify-end
                           p-7 sm:p-9 lg:p-10"
                >

                    <!-- ICON -->

                    <div
                        class="mb-5 flex h-10 w-10
                               items-center justify-center
                               rounded-full
                               border border-white/20
                               bg-white/10
                               text-white
                               backdrop-blur-md"
                    >

                        <Monitor
                            :size="18"
                            :stroke-width="1.7"
                        />

                    </div>


                    <!-- VALEUR -->

                    <div
                        class="text-[72px]
                               font-semibold
                               leading-[0.85]
                               tracking-[-4px]
                               text-white
                               sm:text-[90px]
                               lg:text-[110px]"
                    >
                        {{ equipments[activeEquipment].value }}
                    </div>


                    <!-- TITRE -->

                    <h2
                        class="mt-2
                               text-[17px]
                               font-bold
                               uppercase
                               tracking-[-0.2px]
                               text-white
                               sm:text-[19px]"
                    >
                        {{ equipments[activeEquipment].title }}
                    </h2>


                    <!-- DESCRIPTION -->

                    <p
                        class="mt-6 max-w-[600px]
                               text-[12px]
                               leading-[1.6]
                               text-white/75
                               sm:text-[13px]"
                    >
                        {{ equipments[activeEquipment].description }}
                    </p>


                    <!-- INDICATEURS -->

                    <div
                        class="mt-7 flex items-center gap-2"
                    >

                        <button
                            v-for="(_, index) in equipments"
                            :key="index"
                            type="button"
                            class="h-[3px]
                                   rounded-full
                                   transition-all duration-300"
                            :class="
                                activeEquipment === index
                                    ? 'w-10 bg-white'
                                    : 'w-4 bg-white/30'
                            "
                            @click="activeEquipment = index"
                        ></button>

                    </div>

                </div>

            </div>






        </div>

    </section>
</template>


<style scoped>

.room-image-enter-active,
.room-image-leave-active {
    transition: opacity 0.8s ease;
}

.room-image-enter-from,
.room-image-leave-to {
    opacity: 0;
}

.room-image-enter-to,
.room-image-leave-from {
    opacity: 1;
}

</style>