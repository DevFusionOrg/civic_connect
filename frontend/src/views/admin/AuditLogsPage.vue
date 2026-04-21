<template>
  <div class="min-h-screen bg-cream-50 pb-12">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b border-gold-200/60">
      <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-bold text-slate-900">Audit Logs</h1>
        <p class="mt-1 text-sm text-slate-600">Track system activity and user actions.</p>
      </div>
    </div>

    <!-- Main Content -->
    <div class="mx-auto mt-8 max-w-7xl px-4 sm:px-6 lg:px-8">
      <!-- Logs Table -->
      <div class="overflow-hidden rounded-xl bg-white shadow-sm border border-gold-200/50">
        <div v-if="loading" class="flex h-64 items-center justify-center">
          <div
            class="h-8 w-8 animate-spin rounded-full border-4 border-gold-600 border-t-transparent"
          ></div>
        </div>

        <table v-else class="min-w-full divide-y divide-gold-100/70">
          <thead class="bg-cream-50">
            <tr>
              <th
                scope="col"
                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-600 uppercase"
              >
                Timestamp
              </th>
              <th
                scope="col"
                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-600 uppercase"
              >
                Action
              </th>
              <th
                scope="col"
                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-600 uppercase"
              >
                User
              </th>
              <th
                scope="col"
                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-600 uppercase"
              >
                Entity
              </th>
              <th
                scope="col"
                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-600 uppercase"
              >
                Details
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gold-100/70 bg-white">
            <tr v-for="log in logs" :key="log.id" class="group transition-colors hover:bg-gold-50/40">
              <td class="px-6 py-4 text-sm whitespace-nowrap text-slate-600">
                {{ formatDate(log.created_at) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  class="inline-flex items-center rounded-md bg-gold-100 px-2.5 py-0.5 font-mono text-xs font-medium text-gold-800"
                >
                  {{ log.action }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div v-if="log.user_id" class="text-sm">
                  <div class="font-medium text-slate-900">
                    {{ log.first_name }} {{ log.last_name }}
                  </div>
                  <div class="text-slate-600">{{ log.user_email }}</div>
                </div>
                <div v-else class="text-sm text-slate-500">System</div>
              </td>
              <td class="px-6 py-4 text-sm whitespace-nowrap text-slate-600">
                {{ log.entity_type }} #{{ log.entity_id }}
              </td>
              <td
                class="max-w-xs truncate px-6 py-4 text-sm text-slate-600"
                :title="formatDetails(log)"
              >
                {{ formatDetails(log) }}
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div
          v-if="pagination.total_pages > 1"
          class="flex items-center justify-between border-t border-gold-200/60 bg-white px-4 py-3 sm:px-6"
        >
          <div class="flex flex-1 justify-between sm:hidden">
            <button
              @click="changePage(pagination.current_page - 1)"
              :disabled="pagination.current_page === 1"
              class="relative inline-flex items-center rounded-md border border-gold-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-gold-50 disabled:opacity-50"
            >
              Previous
            </button>
            <button
              @click="changePage(pagination.current_page + 1)"
              :disabled="pagination.current_page === pagination.total_pages"
              class="relative ml-3 inline-flex items-center rounded-md border border-gold-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-gold-50 disabled:opacity-50"
            >
              Next
            </button>
          </div>
          <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
            <div>
              <p class="text-sm text-slate-700">
                Showing
                <span class="font-medium">{{
                  (pagination.current_page - 1) * pagination.limit + 1
                }}</span>
                to
                <span class="font-medium">{{
                  Math.min(pagination.current_page * pagination.limit, pagination.total_items)
                }}</span>
                of
                <span class="font-medium">{{ pagination.total_items }}</span>
                results
              </p>
            </div>
            <div>
              <nav
                class="isolate inline-flex -space-x-px rounded-md shadow-sm"
                aria-label="Pagination"
              >
                <button
                  @click="changePage(pagination.current_page - 1)"
                  :disabled="pagination.current_page === 1"
                  class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gold-500 ring-1 ring-gold-200 ring-inset hover:bg-gold-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50"
                >
                  <span class="sr-only">Previous</span>
                  <ChevronLeftIcon class="h-5 w-5" aria-hidden="true" />
                </button>
                <div
                  class="border-t border-b border-gold-200 px-4 py-2 text-sm font-medium text-slate-700"
                >
                  Page {{ pagination.current_page }} of {{ pagination.total_pages }}
                </div>
                <button
                  @click="changePage(pagination.current_page + 1)"
                  :disabled="pagination.current_page === pagination.total_pages"
                  class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gold-500 ring-1 ring-gold-200 ring-inset hover:bg-gold-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50"
                >
                  <span class="sr-only">Next</span>
                  <ChevronRightIcon class="h-5 w-5" aria-hidden="true" />
                </button>
              </nav>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'

const logs = ref([])
const loading = ref(true)
const pagination = ref({
  current_page: 1,
  total_pages: 1,
  total_items: 0,
  limit: 50,
})

const fetchLogs = async (page = 1) => {
  loading.value = true
  try {
    const response = await axios.get(
      'http://localhost/civic-connect/backend/api/admin/audit-logs',
      {
        params: { page },
      },
    )

    if (response.data.success) {
      logs.value = response.data.logs
      pagination.value = response.data.pagination
    }
  } catch (error) {
    console.error('Failed to fetch audit logs:', error)
  } finally {
    loading.value = false
  }
}

const changePage = (page) => {
  if (page >= 1 && page <= pagination.value.total_pages) {
    fetchLogs(page)
  }
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString(undefined, {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  })
}

const formatDetails = (log) => {
  if (log.new_values) {
    try {
      const parsed =
        typeof log.new_values === 'string' ? JSON.parse(log.new_values) : log.new_values
      return JSON.stringify(parsed)
    } catch {
      return log.new_values
    }
  }
  return '-'
}

onMounted(() => {
  fetchLogs()
})
</script>
