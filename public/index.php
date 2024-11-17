<?php
// index.php im public-Ordner

// Fehleranzeige für Entwicklung
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Prüfe die Route
$requestUri = $_SERVER['REQUEST_URI'];

if (strpos($requestUri, '/api/dashboard') === 0) {
    // API-Logik für Dashboard-Daten
    try {
        require_once __DIR__ . '/../vendor/autoload.php';
        $config = require_once __DIR__ . '/../config/database.php';

        // Verbindung zur Datenbank
        $db = new PDO(
            "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4",
            $config['username'],
            $config['password']
        );
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // SQL-Abfrage
        $query = $db->prepare("
            SELECT c.id AS customer_id, c.name AS customer_name, c.number AS customer_number, 
                   c.note AS customer_note, c.created_at AS customer_created_at,
                   b.id AS job_id, b.name AS job_name, b.email AS job_email, 
                   bt.name AS backup_type, r.status, r.date, r.time, 
                   r.note AS result_note, r.size_mb, r.duration_minutes
            FROM customers c
            LEFT JOIN backup_jobs b ON c.id = b.customer_id
            LEFT JOIN backup_types bt ON b.backup_type_id = bt.id
            LEFT JOIN backup_results r ON b.id = r.backup_job_id
            ORDER BY c.id, b.id, r.date DESC
        ");
        $query->execute();
        $rows = $query->fetchAll(PDO::FETCH_ASSOC);

        // Strukturierung der Daten
        $result = [];
        foreach ($rows as $row) {
            $customerId = $row['customer_id'];
            if (!isset($result[$customerId])) {
                $result[$customerId] = [
                    'customer' => [
                        'id' => $row['customer_id'],
                        'name' => $row['customer_name'],
                        'number' => $row['customer_number'],
                        'note' => $row['customer_note'],
                        'created_at' => $row['customer_created_at']
                    ],
                    'jobs' => []
                ];
            }

            if (!empty($row['job_id'])) {
                $jobId = $row['job_id'];
                if (!isset($result[$customerId]['jobs'][$jobId])) {
                    $result[$customerId]['jobs'][$jobId] = [
                        'job_id' => $row['job_id'],
                        'job_name' => $row['job_name'],
                        'backup_type' => $row['backup_type'],
                        'results' => []
                    ];
                }

                if (!empty($row['status'])) {
                    $result[$customerId]['jobs'][$jobId]['results'][] = [
                        'status' => $row['status'],
                        'date' => $row['date'],
                        'time' => $row['time'],
                        'note' => $row['result_note'],
                        'size_mb' => $row['size_mb'],
                        'duration_minutes' => $row['duration_minutes']
                    ];
                }
            }
        }

        // JSON-Antwort zurückgeben
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => array_values($result)]);
    } catch (Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        error_log($e->getMessage());
    }
    exit;
}

// Frontend: React-Anwendung
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
</head>
<body>
    <div id="root"></div>
    
    <script type="text/babel">
        const Dashboard = () => {
            // Initialer State mit Standardwerten
            const [data, setData] = React.useState({
                stats: {
                    total: 0,
                    success: 0,
                    warnings: 0,
                    errors: 0
                },
                customers: []
            });
            const [loading, setLoading] = React.useState(true);
            const [error, setError] = React.useState(null);
            const [activeTooltip, setActiveTooltip] = React.useState(null);

            React.useEffect(() => {
                fetchData();
            }, []);

            const fetchData = async () => {
                try {
                    setLoading(true);
                    const response = await fetch('/api/dashboard');
                    const result = await response.json();
                    
                    if (result.success) {
                        setData({
                            stats: {
                                total: Number(result.stats.total) || 0,
                                success: Number(result.stats.success) || 0,
                                warnings: Number(result.stats.warnings) || 0,
                                errors: Number(result.stats.errors) || 0
                            },
                            customers: result.data || []
                        });
                    } else {
                        setError(result.error || 'Ein Fehler ist aufgetreten');
                    }
                } catch (error) {
                    console.error('Error fetching data:', error);
                    setError('Fehler beim Laden der Daten');
                } finally {
                    setLoading(false);
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

            if (loading) {
                return <div className="min-h-screen bg-gray-50 flex items-center justify-center">
                    <div className="text-xl text-gray-600">Lade Daten...</div>
                </div>;
            }

            if (error) {
                return <div className="min-h-screen bg-gray-50 flex items-center justify-center">
                    <div className="text-xl text-red-600">{error}</div>
                </div>;
            }

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
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
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
                        {data.customers.map((customerData) => (
                            <div key={customerData.customer.id} className="bg-white rounded-lg shadow-lg p-6">
                                <div className="flex items-center gap-2 mb-6">
                                    <h2 className="text-xl font-semibold">{customerData.customer.name}</h2>
                                    <span className="text-sm text-gray-500">({customerData.customer.number})</span>
                                </div>

                                {customerData.jobs?.length > 0 ? (
                                    <div className="space-y-6">
                                        {customerData.jobs.map((job) => (
                                            <div key={job.job_id} className="mb-6 last:mb-0">
                                                <div className="flex items-center gap-2 mb-2">
                                                    <span className="px-3 py-1 bg-gray-100 rounded-full text-sm">
                                                        {job.backup_type}
                                                    </span>
                                                    <h3 className="font-medium">{job.job_name}</h3>
                                                </div>

                                                <div className="flex gap-1">
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
                                ) : (
                                    <div className="text-gray-500">Keine Backup-Jobs vorhanden</div>
                                )}
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