<?php
// /public/settings/index.php

// Fehleranzeige für Entwicklung
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Wenn es ein API-Request ist
if (strpos($_SERVER['REQUEST_URI'], '/api/settings') === 0) {
    require_once __DIR__ . '/../../vendor/autoload.php';
    $config = require_once __DIR__ . '/../../config/database.php';
    
    // Datenbankverbindung
    $db = new PDO(
        "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4",
        $config['username'],
        $config['password']
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Router initialisieren
    $router = new \App\Router($db);
    
    // Routes definieren
    $router->addRoute('GET', '/api/settings/customers', 'SettingsController', 'getCustomers');
    $router->addRoute('POST', '/api/settings/customers', 'SettingsController', 'createCustomer');
    $router->addRoute('PUT', '/api/settings/customers/{id}', 'SettingsController', 'updateCustomer');
    $router->addRoute('DELETE', '/api/settings/customers/{id}', 'SettingsController', 'deleteCustomer');

    $router->addRoute('GET', '/api/settings/backup-jobs', 'SettingsController', 'getBackupJobs');
    $router->addRoute('POST', '/api/settings/backup-jobs', 'SettingsController', 'createBackupJob');
    $router->addRoute('PUT', '/api/settings/backup-jobs/{id}', 'SettingsController', 'updateBackupJob');
    $router->addRoute('DELETE', '/api/settings/backup-jobs/{id}', 'SettingsController', 'deleteBackupJob');

    $router->addRoute('GET', '/api/settings/backup-types', 'SettingsController', 'getBackupTypes');

    $router->addRoute('GET', '/api/settings/mail', 'SettingsController', 'getMailSettings');
    $router->addRoute('POST', '/api/settings/mail', 'SettingsController', 'updateMailSettings');

    // Request verarbeiten
    $router->handleRequest();
    exit;
}


?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup-Monitor - Einstellungen</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/react@17/umd/react.development.js"></script>
    <script src="https://unpkg.com/react-dom@17/umd/react-dom.development.js"></script>
    <script src="https://unpkg.com/babel-standalone@6/babel.min.js"></script>
</head>
<body>
    <div id="root"></div>
    
    <script type="text/babel">
        const Settings = () => {
            const [activeTab, setActiveTab] = React.useState('mail');
            const [loading, setLoading] = React.useState(false);
            const [error, setError] = React.useState(null);

            // Navigation Items
            const navItems = [
                { id: 'mail', label: 'Mail-Setup', icon: '📧' },
                { id: 'customers', label: 'Kunden', icon: '👥' },
                { id: 'backup-jobs', label: 'Backup-Jobs', icon: '💾' }
            ];

            // Komponenten für die verschiedenen Tabs
            const renderContent = () => {
                switch (activeTab) {
                    case 'mail':
                        return <MailSetup />;
                    case 'customers':
                        return <Customers />;
                    case 'backup-jobs':
                        return <BackupJobs />;
                    default:
                        return <div>404 - Tab nicht gefunden</div>;
                }
            };

            const MailSetup = () => {
                const [formData, setFormData] = React.useState({
                    server: '',
                    port: '',
                    username: '',
                    password: '',
                    protocol: 'imap',
                    encryption: 'ssl'
                });
                const [loading, setLoading] = React.useState(true);
                const [saving, setSaving] = React.useState(false);
                const [error, setError] = React.useState(null);
                const [successMessage, setSuccessMessage] = React.useState(null);

                // Lade bestehende Einstellungen
                React.useEffect(() => {
                    const fetchSettings = async () => {
                        try {
                            const response = await fetch('/api/settings/mail');
                            const data = await response.json();
                            
                            if (data.success) {
                                setFormData(prevData => ({
                                    ...prevData,
                                    ...data.data
                                }));
                            } else {
                                setError(data.error || 'Fehler beim Laden der Einstellungen');
                            }
                        } catch (err) {
                            setError('Fehler beim Laden der Einstellungen');
                        } finally {
                            setLoading(false);
                        }
                    };

                    fetchSettings();
                }, []);

                const handleChange = (e) => {
                    const { name, value } = e.target;
                    setFormData(prev => ({
                        ...prev,
                        [name]: value
                    }));
                };

                const handleSubmit = async (e) => {
                    e.preventDefault();
                    setSaving(true);
                    setError(null);
                    setSuccessMessage(null);

                    try {
                        const response = await fetch('/api/settings/mail', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(formData)
                        });

                        const data = await response.json();

                        if (data.success) {
                            setSuccessMessage('Mail-Einstellungen erfolgreich gespeichert!');
                        } else {
                            setError(data.error || 'Fehler beim Speichern der Einstellungen');
                        }
                    } catch (err) {
                        setError('Fehler beim Speichern der Einstellungen');
                    } finally {
                        setSaving(false);
                    }
                };

                if (loading) {
                    return (
                        <div className="p-6">
                            <div className="flex items-center justify-center">
                                <div className="text-gray-600">Lade Einstellungen...</div>
                            </div>
                        </div>
                    );
                }

                return (
                    <div className="p-6">
                        <h2 className="text-2xl font-bold mb-6">Mail-Setup</h2>
                        
                        {error && (
                            <div className="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                                {error}
                            </div>
                        )}
                        
                        {successMessage && (
                            <div className="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                                {successMessage}
                            </div>
                        )}

                        <div className="max-w-2xl">
                            <form onSubmit={handleSubmit} className="space-y-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Mail Server</label>
                                    <input
                                        type="text"
                                        name="server"
                                        value={formData.server}
                                        onChange={handleChange}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        required
                                    />
                                </div>
                                
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Port</label>
                                    <input
                                        type="number"
                                        name="port"
                                        value={formData.port}
                                        onChange={handleChange}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        required
                                    />
                                </div>
                                
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Protokoll</label>
                                    <select
                                        name="protocol"
                                        value={formData.protocol}
                                        onChange={handleChange}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    >
                                        <option value="imap">IMAP</option>
                                        <option value="pop3">POP3</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Verschlüsselung</label>
                                    <select
                                        name="encryption"
                                        value={formData.encryption}
                                        onChange={handleChange}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    >
                                        <option value="ssl">SSL</option>
                                        <option value="tls">TLS</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Benutzername</label>
                                    <input
                                        type="text"
                                        name="username"
                                        value={formData.username}
                                        onChange={handleChange}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        required
                                    />
                                </div>
                                
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Passwort</label>
                                    <input
                                        type="password"
                                        name="password"
                                        value={formData.password}
                                        onChange={handleChange}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        required
                                    />
                                </div>
                                
                                <div className="pt-4">
                                    <button
                                        type="submit"
                                        disabled={saving}
                                        className={`${
                                            saving 
                                                ? 'bg-gray-400 cursor-not-allowed' 
                                                : 'bg-blue-500 hover:bg-blue-600'
                                        } text-white px-4 py-2 rounded-md`}
                                    >
                                        {saving ? 'Speichert...' : 'Speichern'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                );
            };

            const Customers = () => {
                return (
                    <div className="p-6">
                        <h2 className="text-2xl font-bold mb-6">Kunden</h2>
                        <div className="mb-4">
                            <button className="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600">
                                + Neuer Kunde
                            </button>
                        </div>
                        <div className="bg-white rounded-lg shadow">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nummer</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notiz</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aktionen</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {/* Beispiel-Kunde */}
                                    <tr>
                                        <td className="px-6 py-4 whitespace-nowrap">Musterfirma GmbH</td>
                                        <td className="px-6 py-4 whitespace-nowrap">KD-001</td>
                                        <td className="px-6 py-4">Beispiel-Kunde</td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <button className="text-blue-600 hover:text-blue-900 mr-2">Bearbeiten</button>
                                            <button className="text-red-600 hover:text-red-900">Löschen</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                );
            };

            const BackupJobs = () => {
                return (
                    <div className="p-6">
                        <h2 className="text-2xl font-bold mb-6">Backup-Jobs</h2>
                        <div className="mb-4">
                            <button className="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600">
                                + Neuer Backup-Job
                            </button>
                        </div>
                        <div className="bg-white rounded-lg shadow">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kunde</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Backup-Typ</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">E-Mail</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aktionen</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {/* Beispiel-Job */}
                                    <tr>
                                        <td className="px-6 py-4 whitespace-nowrap">Server-Backup</td>
                                        <td className="px-6 py-4 whitespace-nowrap">Musterfirma GmbH</td>
                                        <td className="px-6 py-4 whitespace-nowrap">Veeam Backup</td>
                                        <td className="px-6 py-4 whitespace-nowrap">backup@musterfirma.de</td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <button className="text-blue-600 hover:text-blue-900 mr-2">Bearbeiten</button>
                                            <button className="text-red-600 hover:text-red-900">Löschen</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                );
            };

            return (
                <div className="min-h-screen bg-gray-100">
                    {/* Header */}
                    <header className="bg-white shadow">
                        <div className="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 flex justify-between items-center">
                            <h1 className="text-3xl font-bold text-gray-900">Einstellungen</h1>
                            <a href="/" className="text-blue-500 hover:text-blue-700">
                                Zurück zum Dashboard
                            </a>
                        </div>
                    </header>

                    {/* Main Content */}
                    <div className="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
                        <div className="flex gap-6">
                            {/* Navigation */}
                            <nav className="w-64 bg-white rounded-lg shadow">
                                <ul className="p-2">
                                    {navItems.map(item => (
                                        <li key={item.id}>
                                            <button
                                                onClick={() => setActiveTab(item.id)}
                                                className={`w-full text-left px-4 py-2 rounded-md mb-1 flex items-center gap-2
                                                    ${activeTab === item.id 
                                                        ? 'bg-blue-500 text-white' 
                                                        : 'hover:bg-gray-100'}`}
                                            >
                                                <span>{item.icon}</span>
                                                {item.label}
                                            </button>
                                        </li>
                                    ))}
                                </ul>
                            </nav>

                            {/* Content */}
                            <div className="flex-1 bg-white rounded-lg shadow">
                                {renderContent()}
                            </div>
                        </div>
                    </div>
                </div>
            );
        };

        ReactDOM.render(<Settings />, document.getElementById('root'));
    </script>
</body>
</html>