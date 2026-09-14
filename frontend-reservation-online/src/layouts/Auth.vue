<template>
  <div class="min-h-screen bg-white">
    <!-- Logo -->
    <header class="absolute left-5 top-5 z-10 sm:left-8 sm:top-8">
      <RouterLink to="/">
        <div class="text-[26px] font-black tracking-[-1.8px] text-black sm:text-[28px]">
          Resven
        </div>
      </RouterLink>
    </header>

    <!-- Zone principale -->
    <main class="grid min-h-screen grid-cols-1 lg:grid-cols-2">

      <!-- Partie gauche : Formulaire centré -->
      <section class="flex items-center justify-center px-4 py-20 sm:px-8 sm:py-24 lg:px-10 lg:py-10">

        <!-- Ici Vue affichera Login.vue ou Register.vue -->
        <div class="w-full max-w-[468px]">
          <slot></slot>
        </div>

      </section>

      <!-- Partie droite : Image masquée sur mobile/tablette (< 1024px), affichée sur desktop -->
      <section class="hidden items-center justify-end py-5 pr-4 lg:flex">

        <div
          class="h-[calc(100vh-40px)] w-full overflow-hidden rounded-bl-[30px] rounded-br-[60px] rounded-tl-[30px] rounded-tr-[30px]"
        >
          <img
            src="/images/image.png"
            alt="reserve"
            loading="eager"
            fetchpriority="high"
            decoding="async"
            class="h-full w-full object-cover"
          />
        </div>

      </section>

    </main>
  </div>
</template>

<script setup>

</script>

<style scoped>

</style>