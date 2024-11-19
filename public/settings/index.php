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
        // Hilfsfunktion für API-Aufrufe
        const api = {
            baseUrl: '/settings/api',
            
            async request(endpoint, options = {}) {
                const url = `${this.baseUrl}${endpoint}`;
                console.log('Making API request to:', url);
                
                try {
                    const response = await fetch(url, {
                        ...options,
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            ...options.headers,
                        },
                    });
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        throw new Error(`Expected JSON response but got ${contentType}`);
                    }
                    
                    const data = await response.json();
                    return data;
                } catch (err) {
                    console.error('Request failed:', err);
                    throw err;
                }
            },
            
            async get(endpoint) {
                return this.request(endpoint);
            },
            
            async post(endpoint, body) {
                return this.request(endpoint, {
                    method: 'POST',
                    body: JSON.stringify(body),
                });
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

            React.useEffect(() => {
                const fetchSettings = async () => {
                    try {
                        console.log('Fetching mail settings...');
                        const data = await api.get('/mail');
                        if (data.success) {
                            setFormData(prevData => ({
                                ...prevData,
                                ...data.data
                            }));
                        } else {
                            setError(data.error || 'Fehler beim Laden der Einstellungen');
                        }
                    } catch (err) {
                        console.error('Fetch error:', err);
                        setError(`Fehler beim Laden der Einstellungen: ${err.message}`);
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
                    const response = await api.post('/mail', formData);
                    if (response.success) {
                        setSuccessMessage('Mail-Einstellungen erfolgreich gespeichert!');
                    }
                } catch (err) {
                    setError(`Fehler beim Speichern der Einstellungen: ${err.message}`);
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
                                    className="mt-1 block w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2"
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
                                    className="mt-1 block w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2"
                                    required
                                />
                            </div>
                            
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Protokoll</label>
                                <select
                                    name="protocol"
                                    value={formData.protocol}
                                    onChange={handleChange}
                                    className="mt-1 block w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2"
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
                                    className="mt-1 block w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2"
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
                                    className="mt-1 block w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2"
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
                                    className="mt-1 block w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2"
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

        const Customers = () => (
            <div className="p-6">
                <h2 className="text-2xl font-bold mb-6">Kunden</h2>
                <div>Kunden-Verwaltung wird noch implementiert...</div>
            </div>
        );

        const BackupJobs = () => (
            <div className="p-6">
                <h2 className="text-2xl font-bold mb-6">Backup-Jobs</h2>
                <div>Backup-Jobs-Verwaltung wird noch implementiert...</div>
            </div>
        );

        const Settings = () => {
            const [activeTab, setActiveTab] = React.useState('mail');

            const navItems = [
                { id: 'mail', label: 'Mail-Setup', icon: '📧' },
                { id: 'customers', label: 'Kunden', icon: '👥' },
                { id: 'backup-jobs', label: 'Backup-Jobs', icon: '💾' }
            ];

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
                                {activeTab === 'mail' && <MailSetup />}
                                {activeTab === 'customers' && <Customers />}
                                {activeTab === 'backup-jobs' && <BackupJobs />}
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