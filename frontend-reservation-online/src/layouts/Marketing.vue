<script setup>

import { ref, onMounted, onBeforeUnmount } from 'vue'

import {
    ListChecks,
    MailCheck,
    Blocks,
    Sparkles,
    ArrowUpRight,
} from 'lucide-vue-next'


/**
 * |--------------------------------------------------------------------------
 * | Fonctionnalités
 * |--------------------------------------------------------------------------
 */

const activeFeature = ref(0)

const features = [
    {
        title: 'Des réservations simples et rapides',
        description:
            'Réservez une salle ou un équipement en quelques clics et consultez immédiatement les disponibilités.',
        icon: ListChecks,
    },
    {
        title: 'Des confirmations de réservation',
        description:
            'Après chaque réservation, les informations importantes sont clairement présentées afin de faciliter votre organisation.',
        icon: MailCheck,
    },
    {
        title: 'Salles et équipements disponibles',
        description:
            'Consultez facilement les salles et équipements disponibles selon vos besoins et vos horaires.',
        icon: Blocks,
    },
    {
        title: 'Rappels de réservation',
        description:
            'Recevez des rappels afin de ne jamais oublier une réservation et de mieux organiser vos activités.',
        icon: Sparkles,
    },
]


/**
 * |--------------------------------------------------------------------------
 * | Images du slider
 * |--------------------------------------------------------------------------
 */

const images = [
    '/images/home/salle-1.png',
    '/images/home/salle-2.png',
    '/images/home/salle-3.png',
    '/images/home/salle-4.png',
]

const currentImage = ref(0)

let imageInterval = null


/**
 * |--------------------------------------------------------------------------
 * | Changement automatique toutes les 3 secondes
 * |--------------------------------------------------------------------------
 */

onMounted(() => {
    // Préchargement en arrière-plan des images du slider
    images.forEach((src) => {
        const img = new Image()
        img.src = src
    })

    imageInterval = setInterval(() => {
        currentImage.value =
            (currentImage.value + 1) % images.length
    }, 3000)
})


/**
 * |--------------------------------------------------------------------------
 * | Nettoyage
 * |--------------------------------------------------------------------------
 */

onBeforeUnmount(() => {
    clearInterval(imageInterval)
})

</script>


<template>

    <section
        class="bg-[#F8FAFC] w-full
               px-6 py-8"
    >

        <div
            class="mx-auto grid max-w-[1280px]
                   grid-cols-1
                   gap-10
                   lg:grid-cols-[0.9fr_1fr]
                   lg:gap-16"
        >

            <!-- ================================================= -->
            <!-- COLONNE GAUCHE -->
            <!-- ================================================= -->

            <div>

                <!-- TITRE -->

                <h1
                    class="max-w-[580px]
                           text-[42px]
                           font-semibold
                           leading-[1.06]
                           tracking-[-1.8px]
                           text-[#0F172A]
                           sm:text-[50px]
                           lg:text-[56px]"
                >
                    Réservez vos salles
                    <br />
                    simplement et
                    <br />
                    efficacement
                </h1>


                <!-- ================================================= -->
                <!-- FEATURES -->
                <!-- ================================================= -->

                <div class="mt-8">

                    <div
                        v-for="(feature, index) in features"
                        :key="feature.title"
                        class="border-b border-[#E2E8F0]"
                        @mouseenter="activeFeature = index"
                        @mouseleave="activeFeature = null"
                    >

                        <div
                            class="cursor-pointer py-4"
                        >

                            <!-- LIGNE -->

                            <div
                                class="flex items-center gap-4"
                            >

                                <!-- ICON -->

                                <div
                                    class="flex h-9 w-9
                                           shrink-0
                                           items-center
                                           justify-center
                                           transition-colors
                                           duration-200"
                                    :class="
                                        activeFeature === index
                                            ? 'text-[#4F46E5]'
                                            : 'text-[#64748B]'
                                    "
                                >

                                    <component
                                        :is="feature.icon"
                                        :size="21"
                                        :stroke-width="1.7"
                                    />

                                </div>


                                <!-- TITRE -->

                                <h2
                                    class="flex-1
                                           text-[16px]
                                           font-semibold
                                           transition-colors
                                           duration-200"
                                    :class="
                                        activeFeature === index
                                            ? 'text-[#0F172A]'
                                            : 'text-[#64748B]'
                                    "
                                >
                                    {{ feature.title }}
                                </h2>


                                <!-- ARROW -->

                                <div
                                    class="flex h-9 w-9
                                           shrink-0
                                           items-center
                                           justify-center
                                           rounded-[8px]
                                           border
                                           transition-all
                                           duration-200"
                                    :class="
                                        activeFeature === index
                                            ? 'border-[#4F46E5] bg-[#EEF2FF] text-[#4F46E5]'
                                            : 'border-[#E2E8F0] bg-white text-[#64748B]'
                                    "
                                >

                                    <ArrowUpRight
                                        :size="17"
                                        :stroke-width="1.7"
                                    />

                                </div>

                            </div>


                            <!-- ================================================= -->
                            <!-- DESCRIPTION APPARAÎT AU SURVOL -->
                            <!-- ================================================= -->

                            <div
                                class="grid transition-all
                                       duration-300 ease-out"
                                :class="
                                    activeFeature === index
                                        ? 'grid-rows-[1fr] opacity-100'
                                        : 'grid-rows-[0fr] opacity-0'
                                "
                            >

                                <div class="overflow-hidden">

                                    <p
                                        class="ml-[52px]
                                               max-w-[500px]
                                               pt-3 pr-10
                                               text-[14px]
                                               leading-[1.6]
                                               text-[#64748B]"
                                    >
                                        {{ feature.description }}
                                    </p>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- ================================================= -->
            <!-- COLONNE DROITE : SLIDER IMAGE -->
            <!-- ================================================= -->

            <div
                class="relative
                       h-[450px]
                       overflow-hidden
                       rounded-[28px]
                       bg-[#E2E8F0]
                       lg:h-[550px]"
            >

                <!-- ================================================= -->
                <!-- IMAGES -->
                <!-- ================================================= -->

                <Transition name="image-fade">

                    <img
                        :key="currentImage"
                        :src="images[currentImage]"
                        alt="Salle disponible à la réservation"
                        decoding="async"
                        class="absolute inset-0
                               h-full w-full
                               object-cover"
                    />

                </Transition>


                <!-- VOILE LEGER -->

                <div
                    class="pointer-events-none
                           absolute inset-0
                           bg-gradient-to-t
                           from-[#0F172A]/10
                           via-transparent
                           to-transparent"
                ></div>


                <!-- ================================================= -->
                <!-- INDICATEURS -->
                <!-- ================================================= -->

                <div
                    class="absolute bottom-6
                           left-1/2
                           flex -translate-x-1/2
                           items-center gap-2"
                >

                    <button
                        v-for="(_, index) in images"
                        :key="index"
                        type="button"
                        class="h-1.5 rounded-full
                               transition-all duration-300"
                        :class="
                            currentImage === index
                                ? 'w-7 bg-[#0F172A]'
                                : 'w-1.5 bg-[#64748B]/50'
                        "
                        @click="currentImage = index"
                    ></button>

                </div>

            </div>

        </div>

    </section>

</template>


<style scoped>

.image-fade-enter-active,
.image-fade-leave-active {
    transition: opacity 0.6s ease;
}

.image-fade-enter-from,
.image-fade-leave-to {
    opacity: 0;
}

.image-fade-enter-to,
.image-fade-leave-from {
    opacity: 1;
}

</style>