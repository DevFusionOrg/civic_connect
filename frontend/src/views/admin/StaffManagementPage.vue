<template>
  <div class="min-h-screen bg-cream-50 pb-12">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b border-gold-200/60">
      <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-slate-900">Manage Staff</h1>
            <p class="mt-1 text-sm text-slate-600">Create and manage staff accounts.</p>
          </div>
          <button
            @click="openAddModal"
            class="inline-flex items-center gap-2 rounded-lg bg-gold-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-gold-700"
          >
            <PlusIcon class="h-5 w-5" />
            Add Staff
          </button>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="mx-auto mt-8 max-w-7xl px-4 sm:px-6 lg:px-8">
      <!-- Search -->
      <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div class="relative w-full max-w-md">
          <MagnifyingGlassIcon
            class="absolute top-1/2 left-3 h-5 w-5 -translate-y-1/2 text-gold-400"
          />
          <input
            v-model="searchQuery"
            @input="handleSearch"
            type="text"
            placeholder="Search by name or email..."
            class="w-full rounded-lg border-gold-200 bg-white py-2 pr-4 pl-10 text-sm text-slate-800 focus:border-gold-400 focus:ring-gold-300"
          />
        </div>
      </div>

      <!-- Staff Table -->
      <div class="overflow-hidden rounded-xl bg-white shadow-sm border border-gold-200/50">
        <div v-if="loading" class="flex h-64 items-center justify-center">
          <div
            class="h-8 w-8 animate-spin rounded-full border-4 border-gold-600 border-t-transparent"
          ></div>
        </div>

        <div
          v-else-if="staff.length === 0"
          class="flex h-64 flex-col items-center justify-center py-12 text-center"
        >
          <h3 class="mt-2 text-sm font-semibold text-slate-900">No staff found</h3>
          <p class="mt-1 text-sm text-slate-600">Get started by creating a new staff account.</p>
        </div>

        <table v-else class="min-w-full divide-y divide-gold-100/70">
          <thead class="bg-cream-50">
            <tr>
              <th
                scope="col"
                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-600 uppercase"
              >
                Staff Member
              </th>
              <th
                scope="col"
                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-600 uppercase"
              >
                Status
              </th>
              <th
                scope="col"
                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-600 uppercase"
              >
                Email Verified
              </th>
              <th
                scope="col"
                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-600 uppercase"
              >
                Joined
              </th>
              <th
                scope="col"
                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-600 uppercase"
              >
                Last Login
              </th>
              <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gold-100/70 bg-white">
            <tr
              v-for="member in staff"
              :key="member.id"
              class="group transition-colors hover:bg-gold-50/40"
            >
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gold-100 font-semibold text-gold-700"
                  >
                    {{ member.first_name[0] }}{{ member.last_name[0] }}
                  </div>
                  <div class="ml-4">
                    <div class="font-medium text-slate-900">
                      {{ member.first_name }} {{ member.last_name }}
                    </div>
                    <div class="text-sm text-slate-600">{{ member.email }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                  :class="
                    member.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                  "
                >
                  {{ member.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                  :class="
                    member.email_verified
                      ? 'bg-green-100 text-green-800'
                      : 'bg-saffron-100 text-saffron-800'
                  "
                >
                  {{ member.email_verified ? 'Verified' : 'Pending' }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm whitespace-nowrap text-slate-600">
                {{ formatDate(member.created_at) }}
              </td>
              <td class="px-6 py-4 text-sm whitespace-nowrap text-slate-600">
                {{ member.last_login ? formatDate(member.last_login) : 'Never' }}
              </td>
              <td class="space-x-3 px-6 py-4 text-right text-sm font-medium whitespace-nowrap">
                <button @click="openEditModal(member)" class="text-gold-700 hover:text-gold-900">
                  Edit
                </button>
                <button
                  @click="toggleStatus(member)"
                  :class="
                    member.is_active
                      ? 'text-red-600 hover:text-red-900'
                      : 'text-green-600 hover:text-green-900'
                  "
                >
                  {{ member.is_active ? 'Deactivate' : 'Activate' }}
                </button>
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
                <button
                  v-for="page in pagination.total_pages"
                  :key="page"
                  @click="changePage(page)"
                  :class="[
                    page === pagination.current_page
                      ? 'relative z-10 inline-flex items-center bg-gold-600 px-4 py-2 text-sm font-semibold text-white focus:z-20 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-600'
                      : 'relative inline-flex items-center px-4 py-2 text-sm font-semibold text-slate-900 ring-1 ring-gold-200 ring-inset hover:bg-gold-50 focus:z-20 focus:outline-offset-0',
                  ]"
                >
                  {{ page }}
                </button>
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

    <!-- Add/Edit Staff Modal -->
    <div
      v-if="showModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    >
      <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
        <h3 class="text-lg font-semibold text-slate-900">
          {{ isEditing ? 'Edit Staff Member' : 'Add New Staff Member' }}
        </h3>
        <p class="mt-2 text-sm text-slate-600">
          {{ isEditing ? 'Update staff member details.' : 'Create a new staff account.' }}
        </p>

        <form @submit.prevent="saveStaff" class="mt-6 space-y-4">
          <div>
            <label for="first_name" class="block text-sm font-medium text-slate-700"
              >First Name</label
            >
            <input
              v-model="formData.first_name"
              id="first_name"
              type="text"
              required
              class="mt-1 block w-full rounded-lg border-gold-200 shadow-sm focus:border-gold-400 focus:ring-gold-300"
            />
          </div>

          <div>
            <label for="last_name" class="block text-sm font-medium text-slate-700">Last Name</label>
            <input
              v-model="formData.last_name"
              id="last_name"
              type="text"
              required
              class="mt-1 block w-full rounded-lg border-gold-200 shadow-sm focus:border-gold-400 focus:ring-gold-300"
            />
          </div>

          <div>
            <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
            <input
              v-model="formData.email"
              id="email"
              type="email"
              required
              class="mt-1 block w-full rounded-lg border-gold-200 shadow-sm focus:border-gold-400 focus:ring-gold-300"
            />
          </div>

          <div v-if="!isEditing">
            <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
            <input
              v-model="formData.password"
              id="password"
              type="password"
              required
              minlength="8"
              class="mt-1 block w-full rounded-lg border-gold-200 shadow-sm focus:border-gold-400 focus:ring-gold-300"
            />
            <p class="mt-1 text-xs text-slate-600">Minimum 8 characters</p>
          </div>

          <div v-if="errorMessage" class="rounded-lg bg-red-50 p-3 text-sm text-red-700">
            {{ errorMessage }}
          </div>

          <div class="flex justify-end gap-3 pt-4">
            <button
              type="button"
              @click="closeModal"
              class="rounded-lg px-4 py-2 text-sm font-medium text-slate-700 hover:bg-gold-100"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="rounded-lg bg-gold-600 px-4 py-2 text-sm font-medium text-white hover:bg-gold-700 disabled:opacity-50"
            >
              {{ saving ? 'Saving...' : isEditing ? 'Update Staff' : 'Create Staff' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import {
  PlusIcon,
  MagnifyingGlassIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
} from '@heroicons/vue/24/outline'

// Simple debounce implementation
const debounce = (func, delay) => {
  let timeoutId
  return (...args) => {
    clearTimeout(timeoutId)
    timeoutId = setTimeout(() => func(...args), delay)
  }
}

const staff = ref([])
const loading = ref(true)
const searchQuery = ref('')
const pagination = ref({
  current_page: 1,
  total_pages: 1,
  total_items: 0,
  limit: 20,
})

const showModal = ref(false)
const isEditing = ref(false)
const editingStaff = ref(null)
const saving = ref(false)
const errorMessage = ref('')

const formData = ref({
  first_name: '',
  last_name: '',
  email: '',
  password: '',
})

const fetchStaff = async (page = 1) => {
  loading.value = true
  try {
    const response = await axios.get('http://localhost/civic-connect/backend/api/admin/staff', {
      params: {
        page,
        search: searchQuery.value,
      },
    })

    if (response.data.success) {
      staff.value = response.data.staff
      pagination.value = response.data.pagination
    }
  } catch (error) {
    console.error('Failed to fetch staff:', error)
  } finally {
    loading.value = false
  }
}

const handleSearch = debounce(() => {
  fetchStaff(1)
}, 300)

const changePage = (page) => {
  if (page >= 1 && page <= pagination.value.total_pages) {
    fetchStaff(page)
  }
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

const openAddModal = () => {
  isEditing.value = false
  editingStaff.value = null
  formData.value = {
    first_name: '',
    last_name: '',
    email: '',
    password: '',
  }
  errorMessage.value = ''
  showModal.value = true
}

const openEditModal = (member) => {
  isEditing.value = true
  editingStaff.value = member
  formData.value = {
    first_name: member.first_name,
    last_name: member.last_name,
    email: member.email,
    password: '',
  }
  errorMessage.value = ''
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  isEditing.value = false
  editingStaff.value = null
  errorMessage.value = ''
}

const saveStaff = async () => {
  saving.value = true
  errorMessage.value = ''

  try {
    if (isEditing.value) {
      // Update existing staff
      await axios.put(
        `http://localhost/civic-connect/backend/api/admin/staff/${editingStaff.value.id}`,
        {
          first_name: formData.value.first_name,
          last_name: formData.value.last_name,
          email: formData.value.email,
        },
      )

      // Update local state
      const index = staff.value.findIndex((s) => s.id === editingStaff.value.id)
      if (index !== -1) {
        staff.value[index].first_name = formData.value.first_name
        staff.value[index].last_name = formData.value.last_name
        staff.value[index].email = formData.value.email
      }
    } else {
      // Create new staff
      await axios.post('http://localhost/civic-connect/backend/api/admin/staff', formData.value)

      // Refresh list
      await fetchStaff(pagination.value.current_page)
    }

    closeModal()
  } catch (error) {
    console.error('Failed to save staff:', error)
    // Extract error message from response
    if (error.response?.data?.error) {
      errorMessage.value = error.response.data.error
    } else if (error.response?.status === 409) {
      errorMessage.value = 'This email address is already in use. Please use a different email.'
    } else {
      errorMessage.value = 'Failed to save staff member. Please try again.'
    }
  } finally {
    saving.value = false
  }
}

const toggleStatus = async (member) => {
  const action = member.is_active ? 'deactivate' : 'activate'
  if (!confirm(`Are you sure you want to ${action} this staff member?`)) {
    return
  }

  try {
    const response = await axios.put(
      `http://localhost/civic-connect/backend/api/admin/staff/${member.id}/status`,
    )

    if (response.data.success) {
      // Update local state
      const index = staff.value.findIndex((s) => s.id === member.id)
      if (index !== -1) {
        staff.value[index].is_active = response.data.is_active
      }
    }
  } catch (error) {
    console.error('Failed to toggle status:', error)
    alert('Failed to update status. Please try again.')
  }
}

onMounted(() => {
  fetchStaff()
})
</script>
