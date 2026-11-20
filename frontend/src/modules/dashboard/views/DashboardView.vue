<script setup lang="ts">
import { onMounted, ref } from 'vue'
import api from '@/services/api'

interface ServiceProbe {
  available?: boolean
  status?: string
  data?: {
    service: string
    status: string
    dependencies?: Record<string, string>
  }
  error?: string
}

interface DashboardTodayResponse {
  date: string
  correlation_id: string
  services: {
    bff: ServiceProbe
    finance: ServiceProbe
    planner: ServiceProbe
  }
}

const loading = ref(true)
const error = ref<string | null>(null)
const summary = ref<DashboardTodayResponse | null>(null)

async function loadDashboard() {
  loading.value = true
  error.value = null
  try {
    const { data } = await api.get<DashboardTodayResponse>('/api/dashboard/today')
    summary.value = data
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Falha ao conectar no BFF Gateway.'
  } finally {
    loading.value = false
  }
}

onMounted(loadDashboard)
</script>

<template>
  <section class="max-w-5xl mx-auto space-y-6">
    <header class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold">Dashboard — Meu Dia</h2>
        <p class="text-sm text-slate-400">
          Visão consolidada via <code class="text-emerald-400">bff-gateway</code> (Hyperf 3 Parallel Coroutines)
        </p>
      </div>
      <button
        type="button"
        class="px-3.5 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-sm font-medium transition"
        @click="loadDashboard"
      >
        Atualizar
      </button>
    </header>

    <div v-if="loading" class="p-6 rounded-xl border border-slate-800 bg-slate-900/50 text-slate-400">
      Consultando microserviços em paralelo...
    </div>

    <div v-else-if="error" class="p-6 rounded-xl border border-rose-500/40 bg-rose-950/30 text-rose-300">
      {{ error }}
    </div>

    <div v-else-if="summary" class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="p-5 rounded-xl border border-slate-800 bg-slate-900/60">
          <p class="text-xs uppercase tracking-wider text-slate-400">BFF Gateway</p>
          <p class="mt-2 text-lg font-semibold text-emerald-400">
            {{ summary.services.bff.status === 'ok' ? 'Online' : 'Degradado' }}
          </p>
          <p class="mt-1 text-xs text-slate-500">Data UTC: {{ summary.date }}</p>
        </div>

        <div class="p-5 rounded-xl border border-slate-800 bg-slate-900/60">
          <p class="text-xs uppercase tracking-wider text-slate-400">Finance Service</p>
          <p
            class="mt-2 text-lg font-semibold"
            :class="summary.services.finance.available ? 'text-emerald-400' : 'text-rose-400'"
          >
            {{ summary.services.finance.available ? 'Online' : 'Indisponível' }}
          </p>
          <p class="mt-1 text-xs text-slate-500">
            MySQL: {{ summary.services.finance.data?.dependencies?.mysql ?? 'n/a' }}
          </p>
        </div>

        <div class="p-5 rounded-xl border border-slate-800 bg-slate-900/60">
          <p class="text-xs uppercase tracking-wider text-slate-400">Planner Service</p>
          <p
            class="mt-2 text-lg font-semibold"
            :class="summary.services.planner.available ? 'text-emerald-400' : 'text-rose-400'"
          >
            {{ summary.services.planner.available ? 'Online' : 'Indisponível' }}
          </p>
          <p class="mt-1 text-xs text-slate-500">
            MySQL: {{ summary.services.planner.data?.dependencies?.mysql ?? 'n/a' }}
          </p>
        </div>
      </div>

      <p class="text-xs text-slate-500">
        Correlation ID: <code class="text-slate-300">{{ summary.correlation_id }}</code>
      </p>
    </div>
  </section>
</template>
