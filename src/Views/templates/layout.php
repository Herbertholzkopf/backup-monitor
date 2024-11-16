// src/Views/templates/layout.php
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