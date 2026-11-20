<script setup lang="ts">
import { RouterLink, RouterView } from 'vue-router'
import { LayoutDashboard, Wallet, BookOpen, CheckSquare } from 'lucide-vue-next'

const navItems = [
  { to: '/', label: 'Dashboard', icon: LayoutDashboard },
  { to: '/finance', label: 'Finanças', icon: Wallet },
  { to: '/studies', label: 'Estudos', icon: BookOpen },
  { to: '/tasks', label: 'Meu Dia', icon: CheckSquare },
]
</script>

<template>
  <div class="min-h-screen flex bg-slate-950 text-slate-100">
    <aside class="w-60 border-r border-slate-800 bg-slate-900/60 p-5 flex flex-col gap-6">
      <div>
        <h1 class="text-xl font-bold tracking-tight text-emerald-400">Pulse</h1>
        <p class="text-xs text-slate-400">Finanças, Estudos & Meu Dia</p>
      </div>

      <nav class="flex flex-col gap-1">
        <RouterLink
          v-for="item in navItems"
          :key="item.to"
          :to="item.to"
          class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white transition"
          active-class="bg-emerald-500/15 text-emerald-300 border border-emerald-500/30"
        >
          <component :is="item.icon" class="w-4 h-4" />
          {{ item.label }}
        </RouterLink>
      </nav>
    </aside>

    <main class="flex-1 p-8 overflow-y-auto">
      <RouterView />
    </main>
  </div>
</template>
