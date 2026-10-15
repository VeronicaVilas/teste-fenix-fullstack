<script setup>
import { RouterLink } from 'vue-router'
</script>

<template>
  <section>
    <h1>Provas</h1>
    <RouterLink :to="{ name: 'teacher-exam-create' }">Nova prova</RouterLink>
  </section>
</template>
