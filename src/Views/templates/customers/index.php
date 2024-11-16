// src/Views/templates/customers/index.php
<div x-data="customers">
    <!-- Header mit Add Button -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold">Kunden</h1>
        <button @click="showCreateModal = true" 
                class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
            Kunde hinzufügen
        </button>
    </div>

    <!-- Kundenliste -->
    <div class="bg-white rounded-lg shadow">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Kundennummer
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Name
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Backup-Jobs
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Aktionen
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <template x-for="customer in customers" :key="customer.id">
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap" x-text="customer.number"></td>
                        <td class="px-6 py-4 whitespace-nowrap" x-text="customer.name"></td>
                        <td class="px-6 py-4 whitespace-nowrap" x-text="customer.jobs?.length || 0"></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <button @click="editCustomer(customer)"
                                    class="text-blue-600 hover:text-blue-900 mr-3">
                                Bearbeiten
                            </button>
                            <button @click="deleteCustomer(customer.id)"
                                    class="text-red-600 hover:text-red-900">
                                Löschen
                            </button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <!-- Create/Edit Modal -->
    <div x-show="showCreateModal" 
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h2 class="text-xl font-semibold mb-4" x-text="editingCustomer ? 'Kunde bearbeiten' : 'Neuer Kunde'"></h2>
            
            <form @submit.prevent="saveCustomer">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Kundennummer</label>
                        <input type="text" x-model="form.number" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" x-model="form.name" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Notiz</label>
                        <textarea x-model="form.note"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                  rows="3"></textarea>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" @click="showCreateModal = false"
                            class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">
                        Abbrechen
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                        Speichern
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>