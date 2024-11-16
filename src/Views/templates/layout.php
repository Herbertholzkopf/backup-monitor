// src/Views/templates/layout.php
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup-Monitor<?= isset($title) ? ' - ' . $title : '' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.13.5/cdn.min.js"></script>
</head>
<body class="bg-gray-50">
    <div x-data="{ sidebarOpen: false }">
        <!-- Sidebar -->
        <div class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-lg transform transition-transform duration-300"
             :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }">
            <div class="p-6">
                <h2 class="text-2xl font-bold text-gray-800">Backup-Monitor</h2>
                <nav class="mt-6">
                    <a href="/" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-100">
                        Dashboard
                    </a>
                    <a href="/customers" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-100">
                        Kunden
                    </a>
                    <a href="/backup-jobs" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-100">
                        Backup-Jobs
                    </a>
                    <a href="/settings" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-100">
                        Einstellungen
                    </a>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="min-h-screen lg:pl-64">
            <!-- Top Bar -->
            <header class="bg-white shadow-sm">
                <div class="flex items-center justify-between p-4">
                    <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/>
                        </svg>
                    </button>
                    <div class="text-xl font-semibold"><?= $title ?? 'Dashboard' ?></div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-6">
                <?php $this->renderPartial($this->template); ?>
            </main>
        </div>
    </div>

    <script src="/assets/js/app.js"></script>
</body>
</html>

<?php
// src/Views/templates/dashboard/index.php
?>
<div x-data="dashboard">
    <!-- Status Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500">Gesamt Backups (24h)</h3>
            <p class="text-2xl font-semibold mt-2" x-text="stats.total"></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500">Erfolgreiche Backups</h3>
            <p class="text-2xl font-semibold text-green-600 mt-2" x-text="stats.success"></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500">Warnungen</h3>
            <p class="text-2xl font-semibold text-yellow-600 mt-2" x-text="stats.warnings"></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500">Fehler</h3>
            <p class="text-2xl font-semibold text-red-600 mt-2" x-text="stats.errors"></p>
        </div>
    </div>

    <!-- Customer List -->
    <div class="space-y-6">
        <template x-for="customer in customers" :key="customer.id">
            <div class="bg-white rounded-lg shadow">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-semibold">
                            <span x-text="customer.name"></span>
                            <span class="text-sm text-gray-500" x-text="'#' + customer.number"></span>
                        </h2>
                    </div>

                    <template x-for="job in customer.jobs" :key="job.id">
                        <div class="mb-6 last:mb-0">
                            <div class="flex items-center gap-2 mb-2">
                                <h3 class="font-medium" x-text="job.name"></h3>
                                <span class="text-sm text-gray-500" x-text="'(' + job.backup_type + ')'"></span>
                            </div>

                            <div class="flex gap-1 flex-wrap">
                                <template x-for="(result, index) in job.results" :key="index">
                                    <div class="relative" 
                                         @mouseenter="showTooltip(job.id, index)"
                                         @mouseleave="hideTooltip()">
                                        <div :class="getStatusClass(result.status)"
                                             class="w-8 h-8 rounded cursor-pointer hover:ring-2 hover:ring-blue-400">
                                            <template x-if="result.runs_count > 1">
                                                <div class="absolute -top-2 -right-2 bg-blue-500 text-white rounded-full w-4 h-4 text-xs flex items-center justify-center"
                                                     x-text="result.runs_count"></div>
                                            </template>
                                        </div>

                                        <!-- Tooltip -->
                                        <div x-show="activeTooltip.jobId === job.id && activeTooltip.index === index"
                                             class="absolute z-10 w-64 p-4 bg-white rounded-lg shadow-lg border mt-2 -left-28">
                                            <div class="flex items-center gap-2 mb-2">
                                                <div x-html="getStatusIcon(result.status)"></div>
                                                <span class="font-medium" x-text="formatDate(result.date)"></span>
                                            </div>
                                            <textarea 
                                                x-model="result.note"
                                                @change="updateNote(result.id, result.note)"
                                                class="w-full p-2 text-sm border rounded"
                                                maxlength="256"
                                                rows="3"
                                                placeholder="Notiz hinzufügen..."></textarea>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>
</div>

<?php