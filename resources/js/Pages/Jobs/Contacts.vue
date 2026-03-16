<template>
    <AppLayout title="Job Tracker - Contacts">
        <template #header-actions>
            <div class="flex items-center gap-3">
                <button @click="showAddModal = true"
                        class="btn-primary flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium shadow-sm">
                    <Plus class="w-4 h-4" /> Add Contact
                </button>
            </div>
        </template>

        <div class="max-w-6xl mx-auto space-y-6">
            <!-- Tabs Navigation -->
            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="-mb-px flex space-x-8">
                    <Link :href="route('jobs.index')"
                        class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:border-gray-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                        Board
                    </Link>
                    <Link :href="route('jobs.contacts.index')"
                        class="border-indigo-500 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors"
                        aria-current="page">
                        Contacts
                    </Link>
                </nav>
            </div>

            <!-- Header and Filter -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                    <Users class="w-5 h-5 text-indigo-500" /> My Network
                </h2>
                <div class="w-full sm:w-auto">
                    <div class="relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <Search class="h-4 w-4 text-gray-400" />
                        </div>
                        <input type="text" v-model="search" class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-10 sm:text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md" placeholder="Search by name, company...">
                    </div>
                </div>
            </div>

            <!-- Contacts Grid -->
            <div v-if="filteredContacts.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="contact in filteredContacts" :key="contact.id" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition">
                    <div class="p-5 border-b border-gray-100 dark:border-gray-800 flex justify-between items-start">
                        <div class="min-w-0 pr-4">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 truncate flex items-center gap-2">
                                {{ contact.name }}
                                <a v-if="contact.linkedin_url" :href="contact.linkedin_url" target="_blank" class="text-blue-500 hover:text-blue-700 shrink-0 inline-block focus:outline-none">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z" clip-rule="evenodd" />
                                    </svg>
                                </a>
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 font-medium truncate mb-1">
                                {{ contact.role || 'No Role' }} <span v-if="contact.company || contact.job_application">@ {{ contact.company || contact.job_application?.company_name }}</span>
                            </p>
                            <a v-if="contact.email" :href="`mailto:${contact.email}`" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline inline-flex items-center gap-1"><Mail class="w-3 h-3"/> {{ contact.email }}</a>
                        </div>
                        <Menu as="div" class="relative inline-block text-left shrink-0">
                            <MenuButton class="p-1 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-gray-500 transition-colors">
                                <MoreHorizontal class="w-4 h-4" />
                            </MenuButton>
                            <transition enter-active-class="transition duration-100 ease-out" enter-from-class="transform scale-95 opacity-0" enter-to-class="transform scale-100 opacity-100" leave-active-class="transition duration-75 ease-in" leave-from-class="transform scale-100 opacity-100" leave-to-class="transform scale-95 opacity-0">
                                <MenuItems class="absolute right-0 mt-1 w-32 origin-top-right divide-y divide-gray-100 dark:divide-gray-700 rounded-md bg-white dark:bg-gray-800 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-10">
                                    <div class="px-1 py-1">
                                        <MenuItem v-slot="{ active }">
                                            <button @click="editContact(contact)" :class="[active ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100' : 'text-gray-700 dark:text-gray-300', 'group flex w-full items-center rounded-md px-2 py-2 text-sm']">
                                                <Edit2 class="mr-2 h-4 w-4" aria-hidden="true" /> Edit
                                            </button>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }">
                                            <button @click="deleteContact(contact.id)" :class="[active ? 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400' : 'text-red-600 dark:text-red-400', 'group flex w-full items-center rounded-md px-2 py-2 text-sm mt-1']">
                                                <Trash2 class="mr-2 h-4 w-4" aria-hidden="true" /> Delete
                                            </button>
                                        </MenuItem>
                                    </div>
                                </MenuItems>
                            </transition>
                        </Menu>
                    </div>
                    <div class="p-5 bg-gray-50/50 dark:bg-gray-800/20">
                        <div class="flex items-center gap-2 mb-3">
                            <Calendar class="w-4 h-4 text-gray-400 shrink-0" />
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                Last Contact: <span class="font-medium text-gray-700 dark:text-gray-300">{{ contact.last_contact_date ? formatDate(contact.last_contact_date) : 'Never' }}</span>
                            </span>
                        </div>
                        <div v-if="contact.notes" class="text-sm text-gray-600 dark:text-gray-400 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded p-3 italic">
                            "{{ contact.notes }}"
                        </div>
                    </div>
                </div>
            </div>
            
            <div v-else class="text-center py-16 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 mb-4">
                    <Users class="w-8 h-8" />
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-1">No contacts found</h3>
                <p class="text-gray-500 dark:text-gray-400 max-w-sm mx-auto mb-6">Build your network by adding recruiters, hiring managers, and connections.</p>
                <button @click="showAddModal = true" class="btn-primary inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors cursor-pointer">
                    <Plus class="w-4 h-4" /> Add your first contact
                </button>
            </div>
        </div>

        <!-- Add/Edit Contact Modal -->
        <DialogModal :show="showAddModal" @close="closeModal" maxWidth="lg">
            <template #title>
                {{ contactToEdit ? 'Edit Contact' : 'Add New Contact' }}
            </template>

            <template #content>
                <form @submit.prevent="submit" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Name *" />
                            <TextInput v-model="form.name" type="text" class="mt-1 block w-full" required autofocus />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel value="Role/Title" />
                            <TextInput v-model="form.role" type="text" class="mt-1 block w-full" placeholder="e.g. Recruiter" />
                            <InputError :message="form.errors.role" class="mt-2" />
                        </div>
                    </div>
                    
                    <div>
                        <InputLabel value="Company" />
                        <TextInput v-model="form.company" type="text" class="mt-1 block w-full" placeholder="e.g. Acme Corp" />
                        <div class="text-xs text-gray-500 mt-1">If this contact is specifically tied to an application, add them from the application detail page instead.</div>
                        <InputError :message="form.errors.company" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Email" />
                            <TextInput v-model="form.email" type="email" class="mt-1 block w-full" />
                            <InputError :message="form.errors.email" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel value="LinkedIn URL" />
                            <TextInput v-model="form.linkedin_url" type="url" class="mt-1 block w-full" />
                            <InputError :message="form.errors.linkedin_url" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <InputLabel value="Last Contact Date" />
                        <TextInput v-model="form.last_contact_date" type="date" class="mt-1 block w-full" />
                        <InputError :message="form.errors.last_contact_date" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel value="Notes" />
                        <textarea v-model="form.notes" rows="3" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
                        <InputError :message="form.errors.notes" class="mt-2" />
                    </div>
                </form>
            </template>

            <template #footer>
                <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                <PrimaryButton class="ml-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click="submit">
                    {{ contactToEdit ? 'Save Changes' : 'Add Contact' }}
                </PrimaryButton>
            </template>
        </DialogModal>

    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue'
import { Plus, Users, Search, Mail, Calendar, MoreHorizontal, Edit2, Trash2 } from 'lucide-vue-next'
import DialogModal from '@/Components/DialogModal.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import InputError from '@/Components/InputError.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'

const props = defineProps({
    contacts: Array
})

const search = ref('')
const showAddModal = ref(false)
const contactToEdit = ref(null)

const filteredContacts = computed(() => {
    if (!props.contacts) return []
    let list = props.contacts
    if (search.value) {
        const q = search.value.toLowerCase()
        list = list.filter(c => 
            (c.name && c.name.toLowerCase().includes(q)) || 
            (c.company && c.company.toLowerCase().includes(q)) ||
            (c.role && c.role.toLowerCase().includes(q)) ||
            (c.job_application && c.job_application.company_name && c.job_application.company_name.toLowerCase().includes(q))
        )
    }
    return list
})

const form = useForm({
    name: '',
    role: '',
    company: '',
    email: '',
    linkedin_url: '',
    last_contact_date: '',
    notes: ''
})

const editContact = (contact) => {
    contactToEdit.value = contact
    form.name = contact.name
    form.role = contact.role || ''
    form.company = contact.company || ''
    form.email = contact.email || ''
    form.linkedin_url = contact.linkedin_url || ''
    form.last_contact_date = contact.last_contact_date ? contact.last_contact_date.split('T')[0] : ''
    form.notes = contact.notes || ''
    showAddModal.value = true
}

const deleteContact = (id) => {
    if (confirm('Delete this contact?')) {
        router.delete(route('jobs.contacts.destroy', id), { preserveScroll: true })
    }
}

const submit = () => {
    if (contactToEdit.value) {
        form.patch(route('jobs.contacts.update', contactToEdit.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal()
        })
    } else {
        form.post(route('jobs.contacts.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal()
        })
    }
}

const closeModal = () => {
    showAddModal.value = false
    contactToEdit.value = null
    form.reset()
    form.clearErrors()
}

const formatDate = (dateString) => {
    if (!dateString) return ''
    const d = new Date(dateString)
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}
</script>
