<script setup>
const logos = [
    {
        src: '/images/partners/partner-1.png',
        name: 'BLOLAB',
    },
    {
        src: '/images/partners/partner-2.png',
        name: 'Ecole229',
    },
]

// Répétition pour éviter les espaces vides
const repeatedLogos = Array.from({ length: 8 }, () => logos).flat()
</script>

<template>
    <section class="w-full bg-white overflow-hidden">

        <!-- =========================
             TITRE
        ========================== -->
        <div class="mx-auto max-w-7xl px-6 lg:px-10 py-12">

            <div class="text-center">

                <h2
                    class="text-3xl md:text-4xl font-semibold tracking-tight text-slate-950"
                >
                    Ils nous font confiance
                </h2>
            </div>

        </div>


        <!-- =========================
             LIGNE DES PARTENAIRES
        ========================== -->

        <div class="w-full border-t border-slate-100">

            <div class="relative w-full overflow-hidden">

                <div class="flex w-max animate-marquee">

                    <!-- GROUPE 1 -->
                    <div class="flex shrink-0">

                        <div
                            v-for="(partner, index) in repeatedLogos"
                            :key="`first-${index}`"
                            class="
                                flex
                                h-[90px]
                                w-[270px]
                                shrink-0
                                items-center
                                justify-center
                                gap-5
                                border-r
                                border-slate-100
                                px-8
                            "
                        >

                            <!-- LOGO -->
                            <img
                                :src="partner.src"
                                :alt="partner.name"
                                decoding="async"
                                class="
                                    max-h-[30px]
                                    max-w-[90px]
                                    w-auto
                                    object-contain
                                    shrink-0
                                "
                            />

                            <!-- NOM -->
                            <span
                                class="
                                    text-sm
                                    font-medium
                                    text-slate-800
                                    whitespace-nowrap
                                "
                            >
                                {{ partner.name }}
                            </span>

                        </div>

                    </div>


                    <!-- GROUPE 2 -->
                    <div class="flex shrink-0">

                        <div
                            v-for="(partner, index) in repeatedLogos"
                            :key="`second-${index}`"
                            class="
                                flex
                                h-[90px]
                                w-[270px]
                                shrink-0
                                items-center
                                justify-center
                                gap-5
                                border-r
                                border-slate-100
                                px-8
                            "
                        >

                            <!-- LOGO -->
                            <img
                                :src="partner.src"
                                :alt="partner.name"
                                decoding="async"
                                class="
                                    max-h-[30px]
                                    max-w-[90px]
                                    w-auto
                                    object-contain
                                    shrink-0
                                "
                            />

                            <!-- NOM -->
                            <span
                                class="
                                    text-sm
                                    font-medium
                                    text-slate-800
                                    whitespace-nowrap
                                "
                            >
                                {{ partner.name }}
                            </span>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- Ligne inférieure -->
        <div class="border-b border-slate-100"></div>

    </section>
</template>


<style scoped>
.animate-marquee {
    animation: marquee 30s linear infinite;
}

@keyframes marquee {
    from {
        transform: translateX(0);
    }

    to {
        transform: translateX(-50%);
    }
}

.animate-marquee:hover {
    animation-play-state: paused;
}


/* =========================
   MOBILE
========================= */

@media (max-width: 640px) {
    .animate-marquee {
        animation-duration: 20s;
    }

    .partner-item {
        width: 220px;
    }
}
</style>