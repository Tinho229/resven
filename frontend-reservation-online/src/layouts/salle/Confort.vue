<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import {
    Armchair,
    Wifi,
    Wind,
    Monitor,
    Coffee,
} from 'lucide-vue-next'

/*
|--------------------------------------------------------------------------
| Images
|--------------------------------------------------------------------------
*/

const rooms = [
    {
        image: '/images/salles/confort-1.png',
        percentage: '100%',
        title: 'CONFORT GARANTI',
        description:
            'Nos salles sont aménagées pour offrir un environnement confortable, calme et adapté à vos réunions, formations et événements.',
    },
    {
        image: '/images/salles/confort-2.png',
        percentage: '24/7',
        title: 'ESPACE DISPONIBLE',
        description:
            'Des espaces soigneusement préparés pour vous permettre de travailler efficacement dans un cadre professionnel et agréable.',
    },
    {
        image: '/images/salles/confort-3.png',
        percentage: '4K',
        title: 'ÉQUIPEMENTS MODERNES',
        description:
            'Profitez de salles équipées avec les outils nécessaires pour vos présentations, réunions et différentes activités.',
    },
]

const activeRoom = ref(0)

let interval = null

/*
|--------------------------------------------------------------------------
| Changement automatique
|--------------------------------------------------------------------------
*/

onMounted(() => {
    // Préchargement en arrière-plan des images de la section Confort
    rooms.forEach((r) => {
        const img = new Image()
        img.src = r.image
    })

    interval = setInterval(() => {
        activeRoom.value =
            (activeRoom.value + 1) % rooms.length
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
        Pourquoi choisir nos Salles ?
    </h1>
        <div
            class="mx-auto grid w-full max-w-[1400px]
                   grid-cols-1 gap-3
                   lg:grid-cols-[2fr_1fr]"
        >

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
                        :key="activeRoom"
                        :src="rooms[activeRoom].image"
                        alt="Confort de nos salles"
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
                        <Armchair
                            :size="18"
                            :stroke-width="1.7"
                        />
                    </div>


                    <!-- CHIFFRE -->
                    <div
                        class="text-[72px]
                               font-semibold
                               leading-[0.85]
                               tracking-[-4px]
                               text-white
                               sm:text-[90px]
                               lg:text-[110px]"
                    >
                        {{ rooms[activeRoom].percentage }}
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
                        {{ rooms[activeRoom].title }}
                    </h2>


                    <!-- DESCRIPTION -->
                    <p
                        class="mt-6 max-w-[600px]
                               text-[12px]
                               leading-[1.6]
                               text-white/75
                               sm:text-[13px]"
                    >
                        {{ rooms[activeRoom].description }}
                    </p>


                    <!-- INDICATEURS -->
                    <div
                        class="mt-7 flex items-center gap-2"
                    >
                        <button
                            v-for="(_, index) in rooms"
                            :key="index"
                            type="button"
                            class="h-[3px]
                                   rounded-full
                                   transition-all duration-300"
                            :class="
                                activeRoom === index
                                    ? 'w-10 bg-white'
                                    : 'w-4 bg-white/30'
                            "
                            @click="activeRoom = index"
                        ></button>
                    </div>

                </div>
            </div>


            <!-- ===================================================== -->
            <!-- PETITE CARTE DROITE -->
            <!-- ===================================================== -->

            <div
                class="flex min-h-[520px]
                       flex-col justify-between
                       rounded-[24px]
                       bg-[#1f1d1d]
                       p-7
                       sm:p-9
                       lg:p-10"
            >

                <!-- HAUT -->
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
                        25+
                    </div>


                    <div
                        class="mt-1
                               text-[16px]
                               font-bold
                               uppercase
                               tracking-[-0.3px]
                               text-white"
                    >
                        PERSONNES
                        <br />
                        PAR SALLE
                    </div>

                </div>


                <!-- SERVICES -->
                <div
                    class="space-y-5"
                >

                    <!-- CONFORT -->
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
                            <Armchair :size="17" />
                        </div>

                        <div>
                            <p
                                class="text-[11px]
                                       font-semibold
                                       text-white"
                            >
                                Confort optimal
                            </p>

                            <p
                                class="mt-0.5 text-[10px]
                                       text-white/45"
                            >
                                Mobilier adapté
                            </p>
                        </div>
                    </div>


                    <!-- WIFI -->
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
                            <Wifi :size="17" />
                        </div>

                        <div>
                            <p
                                class="text-[11px]
                                       font-semibold
                                       text-white"
                            >
                                Wi-Fi haut débit
                            </p>

                            <p
                                class="mt-0.5 text-[10px]
                                       text-white/45"
                            >
                                Connexion stable
                            </p>
                        </div>
                    </div>


                    <!-- CLIMATISATION -->
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
                            <Wind :size="17" />
                        </div>

                        <div>
                            <p
                                class="text-[11px]
                                       font-semibold
                                       text-white"
                            >
                                Climatisation
                            </p>

                            <p
                                class="mt-0.5 text-[10px]
                                       text-white/45"
                            >
                                Température agréable
                            </p>
                        </div>
                    </div>


                    <!-- EQUIPEMENTS -->
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
                                Équipements modernes
                            </p>

                            <p
                                class="mt-0.5 text-[10px]
                                       text-white/45"
                            >
                                Présentation et réunion
                            </p>
                        </div>
                    </div>

                </div>


                <!-- DESCRIPTION BAS -->
                <div>
                    <p
                        class="max-w-[330px]
                               text-[12px]
                               leading-[1.6]
                               text-white/60"
                    >
                        Nous créons des espaces pensés pour
                        votre confort afin que chaque réunion,
                        formation ou événement se déroule
                        dans les meilleures conditions.
                    </p>
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
