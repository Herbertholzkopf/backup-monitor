<?php
// public/index.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    require_once __DIR__ . '/../vendor/autoload.php';
    $config = require_once __DIR__ . '/../config/database.php';

    try {
        $db = new PDO(
            "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4",
            $config['username'],
            $config['password']
        );
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        throw new Exception("Datenbankverbindung fehlgeschlagen: " . $e->getMessage());
    }

    // Prüfen ob API-Request
    if (strpos($_SERVER['REQUEST_URI'], '/api/') === 0) {
        $router = new App\Router($db);
        $router->handleRequest();
        exit;
    }

    // Ansonsten HTML mit React rendern
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Backup-Monitor</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <script src="https://unpkg.com/react@17/umd/react.development.js"></script>
        <script src="https://unpkg.com/react-dom@17/umd/react-dom.development.js"></script>
        <script src="https://unpkg.com/babel-standalone@6/babel.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/lucide-react@latest/dist/umd/lucide-react.min.js"></script>
    </head>
    <body>
        <div id="root"></div>
        
        <script type="text/babel">
            // Dashboard Component hier einfügen
            const Dashboard = () => {
                const [data, setData] = React.useState({
                    stats: { total: 0, success: 0, warnings: 0, errors: 0 },
                    customers: []
                });
                const [activeTooltip, setActiveTooltip] = React.useState(null);

                React.useEffect(() => {
                    fetchData();
                }, []);

                const fetchData = async () => {
                    try {
                        const response = await fetch('/api/dashboard');
                        const result = await response.json();
                        if (result.success) {
                            setData({
                                stats: result.stats,
                                customers: result.data
                            });
                        }
                    } catch (error) {
                        console.error('Error fetching data:', error);
                    }
                };

                const getStatusColor = (status) => {
                    switch (status) {
                        case 'success': return 'bg-green-500';
                        case 'warning': return 'bg-yellow-500';
                        case 'error': return 'bg-red-500';
                        default: return 'bg-gray-300';
                    }
                };

                return (
                    <div className="min-h-screen bg-gray-50 p-6">
                        {/* Header */}
                        <div className="flex justify-between items-center mb-8">
                            <h1 className="text-3xl font-bold">Backup Monitor</h1>
                            <button className="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                                Einstellungen
                            </button>
                        </div>

                        {/* Stats Overview */}
                        <div className="grid grid-cols-4 gap-4 mb-8">
                            <div className="bg-white p-4 rounded-lg shadow">
                                <div className="text-sm text-gray-500">Gesamt</div>
                                <div className="text-2xl font-bold">{data.stats.total}</div>
                            </div>
                            <div className="bg-white p-4 rounded-lg shadow">
                                <div className="text-sm text-gray-500">Erfolgreich</div>
                                <div className="text-2xl font-bold text-green-600">{data.stats.success}</div>
                            </div>
                            <div className="bg-white p-4 rounded-lg shadow">
                                <div className="text-sm text-gray-500">Warnungen</div>
                                <div className="text-2xl font-bold text-yellow-600">{data.stats.warnings}</div>
                            </div>
                            <div className="bg-white p-4 rounded-lg shadow">
                                <div className="text-sm text-gray-500">Fehler</div>
                                <div className="text-2xl font-bold text-red-600">{data.stats.errors}</div>
                            </div>
                        </div>

                        {/* Customer List */}
                        <div className="space-y-6">
                            {data.customers.map(({customer, jobs}) => (
                                <div key={customer.id} className="bg-white rounded-lg shadow-lg p-6">
                                    <div className="flex items-center gap-2 mb-6">
                                        <h2 className="text-xl font-semibold">{customer.name}</h2>
                                        <span className="text-sm text-gray-500">({customer.number})</span>
                                    </div>

                                    {jobs.map(job => (
                                        <div key={job.job_id} className="mb-6 last:mb-0">
                                            <div className="flex items-center gap-2 mb-2">
                                                <span className="px-3 py-1 bg-gray-100 rounded-full text-sm">
                                                    {job.backup_type}
                                                </span>
                                                <h3 className="font-medium">{job.job_name}</h3>
                                            </div>

                                            <div className="flex gap-1">
                                                {/* Backup Result Squares */}
                                                <div 
                                                    className={`w-8 h-8 rounded cursor-pointer ${getStatusColor(job.status)}`}
                                                    onMouseEnter={() => setActiveTooltip(job.job_id)}
                                                    onMouseLeave={() => setActiveTooltip(null)}
                                                >
                                                    {job.runs_count > 1 && (
                                                        <div className="absolute -top-2 -right-2 bg-blue-500 text-white rounded-full w-4 h-4 text-xs flex items-center justify-center">
                                                            {job.runs_count}
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ))}
                        </div>
                    </div>
                );
            };

            // Render the app
            ReactDOM.render(<Dashboard />, document.getElementById('root'));
        </script>
    </body>
    </html>
    <?php
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    if (ini_get('display_errors')) {
        echo "<h1>Error</h1>";
        echo "<pre>";
        echo "Message: " . htmlspecialchars($e->getMessage()) . "\n\n";
        echo "Stack trace:\n" . htmlspecialchars($e->getTraceAsString());
        echo "</pre>";
    } else {
        echo "<h1>Internal Server Error</h1>";
        error_log($e->getMessage());
        error_log($e->getTraceAsString());
    }
}