// public/assets/js/app.js

// Dashboard
document.addEventListener('alpine:init', () => {
    Alpine.data('dashboard', () => ({
        stats: {
            total: 0,
            success: 0,
            warnings: 0,
            errors: 0
        },
        customers: [],
        activeTooltip: {
            jobId: null,
            index: null
        },

        init() {
            this.loadDashboardData();
        },

        async loadDashboardData() {
            try {
                const response = await fetch('/api/dashboard');
                const data = await response.json();
                if (data.success) {
                    this.stats = data.stats;
                    this.customers = data.data;
                }
            } catch (error) {
                console.error('Error loading dashboard data:', error);
            }
        },

        getStatusClass(status) {
            return {
                'success': 'bg-green-500',
                'warning': 'bg-yellow-500',
                'error': 'bg-red-500'
            }[status] || 'bg-gray-300';
        },

        getStatusIcon(status) {
            const icons = {
                'success': '<svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>',
                'warning': '<svg class="w-4 h-4 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>',
                'error': '<svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>'
            };
            return icons[status] || '';
        },

        showTooltip(jobId, index) {
            this.activeTooltip = { jobId, index };
        },

        hideTooltip() {
            this.activeTooltip = { jobId: null, index: null };
        },

        formatDate(dateString) {
            return new Date(dateString).toLocaleString('de-DE');
        },

        async updateNote(resultId, note) {
            try {
                const response = await fetch('/api/backup-results/note', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ id: resultId, note })
                });
                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.error);
                }
            } catch (error) {
                console.error('Error updating note:', error);
                alert('Fehler beim Speichern der Notiz');
            }
        }
    }));

    // Customers Management
    Alpine.data('customers', () => ({
        customers: [],
        showCreateModal: false,
        editingCustomer: null,
        form: {
            number: '',
            name: '',
            note: ''
        },

        init() {
            this.loadCustomers();
        },

        async loadCustomers() {
            try {
                const response = await fetch('/api/customers');
                const data = await response.json();
                if (data.success) {
                    this.customers = data.data;
                }
            } catch (error) {
                console.error('Error loading customers:', error);
            }
        },

        editCustomer(customer) {
            this.editingCustomer = customer;
            this.form = { ...customer };
            this.showCreateModal = true;
        },

        async saveCustomer() {
            try {
                const url = this.editingCustomer 
                    ? `/api/customers/${this.editingCustomer.id}`
                    : '/api/customers';
                
                const method = this.editingCustomer ? 'PUT' : 'POST';
                
                const response = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(this.form)
                });

                const data = await response.json();
                if (data.success) {
                    this.showCreateModal = false;
                    this.editingCustomer = null;
                    this.form = { number: '', name: '', note: '' };
                    await this.loadCustomers();
                } else {
                    throw new Error(data.error);
                }
            } catch (error) {
                console.error('Error saving customer:', error);
                alert('Fehler beim Speichern des Kunden');
            }
        },

        async deleteCustomer(id) {
            if (!confirm('Möchten Sie diesen Kunden wirklich löschen?')) {
                return;
            }

            try {
                const response = await fetch(`/api/customers/${id}`, {
                    method: 'DELETE'
                });
                const data = await response.json();
                if (data.success) {
                    await this.loadCustomers();
                } else {
                    throw new Error(data.error);
                }
            } catch (error) {
                console.error('Error deleting customer:', error);
                alert('Fehler beim Löschen des Kunden');
            }
        }
    }));

    // Backup Jobs Management
    Alpine.data('backupJobs', () => ({
        jobs: [],
        showCreateModal: false,
        editingJob: null,
        form: {
            name: '',
            customer_id: '',
            backup_type_id: '',
            email: '',
            search_term1: '',
            search_term2: '',
            search_term3: '',
            note: ''
        },

        init() {
            this.loadJobs();
        },

        async loadJobs() {
            try {
                const response = await fetch('/api/backup-jobs');
                const data = await response.json();
                if (data.success) {
                    this.jobs = data.data;
                }
            } catch (error) {
                console.error('Error loading backup jobs:', error);
            }
        },

        async saveJob() {
            try {
                const url = this.editingJob 
                    ? `/api/backup-jobs/${this.editingJob.id}`
                    : '/api/backup-jobs';
                
                const method = this.editingJob ? 'PUT' : 'POST';
                
                const response = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(this.form)
                });

                const data = await response.json();
                if (data.success) {
                    this.showCreateModal = false;
                    this.editingJob = null;
                    this.form = {
                        name: '',
                        customer_id: '',
                        backup_type_id: '',
                        email: '',
                        search_term1: '',
                        search_term2: '',
                        search_term3: '',
                        note: ''
                    };
                    await this.loadJobs();
                } else {
                    throw new Error(data.error);
                }
            } catch (error) {
                console.error('Error saving backup job:', error);
                alert('Fehler beim Speichern des Backup-Jobs');
            }
        },

        editJob(job) {
            this.editingJob = job;
            this.form = { ...job };
            this.showCreateModal = true;
        },

        async deleteJob(id) {
            if (!confirm('Möchten Sie diesen Backup-Job wirklich löschen?')) {
                return;
            }

            try {
                const response = await fetch(`/api/backup-jobs/${id}`, {
                    method: 'DELETE'
                });
                const data = await response.json();
                if (data.success) {
                    await this.loadJobs();
                } else {
                    throw new Error(data.error);
                }
            } catch (error) {
                console.error('Error deleting backup job:', error);
                alert('Fehler beim Löschen des Backup-Jobs');
            }
        }
    }));
});