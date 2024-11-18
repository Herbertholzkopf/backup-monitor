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
            SELECT 
                c.id AS customer_id, 
                c.name AS customer_name, 
                c.number AS customer_number,
                c.note AS customer_note, 
                c.created_at AS customer_created_at,
                b.id AS job_id, 
                b.name AS job_name, 
                b.email AS job_email,
                bt.name AS backup_type, 
                r.status, 
                r.date, 
                r.time,
                r.id AS result_id,
                r.note AS result_note, 
                r.size_mb, 
                r.duration_minutes,
                COUNT(r2.id) as runs_count
            FROM customers c
            LEFT JOIN backup_jobs b ON c.id = b.customer_id
            LEFT JOIN backup_types bt ON b.backup_type_id = bt.id
            LEFT JOIN backup_results r ON b.id = r.backup_job_id
            LEFT JOIN backup_results r2 ON r.date = r2.date AND r.backup_job_id = r2.backup_job_id
            GROUP BY c.id, b.id, r.id, r.date
            ORDER BY c.id, b.id, r.date DESC, r.time DESC
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
                        'id' => $row['result_id'],  // Wichtig für das Speichern von Notizen
                        'status' => $row['status'],
                        'date' => $row['date'],
                        'time' => $row['time'],
                        'note' => $row['result_note'],
                        'size_mb' => $row['size_mb'],
                        'duration_minutes' => $row['duration_minutes'],
                        'runs_count' => $row['runs_count']
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

if (strpos($requestUri, '/api/backup-results/note') === 0) {
    try {
        require_once __DIR__ . '/../vendor/autoload.php';
        $config = require_once __DIR__ . '/../config/database.php';
        
        // JSON-Daten lesen
        $jsonData = file_get_contents('php://input');
        $data = json_decode($jsonData, true);
        
        if (!isset($data['id']) || !isset($data['note'])) {
            throw new Exception("ID und Notiz sind erforderlich");
        }

        $db = new PDO(
            "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4",
            $config['username'],
            $config['password']
        );
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $db->prepare("UPDATE backup_results SET note = :note WHERE id = :id");
        $success = $stmt->execute([
            'id' => $data['id'],
            'note' => $data['note']
        ]);

        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
        
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
            const [data, setData] = React.useState(null);
            const [loading, setLoading] = React.useState(true);
            const [error, setError] = React.useState(null);
            const [activeTooltip, setActiveTooltip] = React.useState(null);
            const [isTooltipLocked, setIsTooltipLocked] = React.useState(false);
            const [notesTexts, setNotesTexts] = React.useState({});

            const groupResultsByDate = (results) => {
                const grouped = {};
                
                results.forEach(result => {
                    if (!grouped[result.date]) {
                        grouped[result.date] = {
                            results: [],
                            status: result.status,
                            time: result.time
                        };
                    }
                    
                    grouped[result.date].results.push(result);
                    
                    // Aktualisiere Status basierend auf der späteren Zeit
                    if (result.time > grouped[result.date].time) {
                        grouped[result.date].status = result.status;
                        grouped[result.date].time = result.time;
                    }
                });
                
                return Object.values(grouped);
            };

            const calculateStats = (customers) => {
                let total = 0;
                let success = 0;
                let warnings = 0;
                let errors = 0;

                customers.forEach(customer => {
                    Object.values(customer.jobs || {}).forEach(job => {
                        if (job.results) {
                            const groupedResults = groupResultsByDate(job.results);
                            total += groupedResults.length;
                            groupedResults.forEach(group => {
                                if (group.status === 'success') success++;
                                if (group.status === 'warning') warnings++;
                                if (group.status === 'error') errors++;
                            });
                        }
                    });
                });

                return { total, success, warnings, errors };
            };

            const fetchData = async () => {
                try {
                    setLoading(true);
                    setError(null);
                    
                    const response = await fetch('/api/dashboard');
                    const result = await response.json();
                    console.log('API response:', result);
                    
                    if (result.success) {
                        // Berechne die Stats aus den Daten
                        const stats = calculateStats(result.data);
                        setData({
                            ...result,
                            stats
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

            const saveNote = async (resultId, note) => {
                try {
                    const response = await fetch('/api/backup-results/note', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: resultId, note })
                    });
                    if (response.ok) {
                        fetchData();
                    }
                } catch (error) {
                    console.error('Error saving note:', error);
                }
            };

            const handleNoteChange = (resultId, text) => {
                setNotesTexts(prev => ({
                    ...prev,
                    [resultId]: text
                }));
            };

            React.useEffect(() => {
                const handleClickOutside = (event) => {
                    if (!event.target.closest('.tooltip-content') && !event.target.closest('.status-square')) {
                        setIsTooltipLocked(false);
                        setActiveTooltip(null);
                    }
                };

                document.addEventListener('mousedown', handleClickOutside);
                return () => {
                    document.removeEventListener('mousedown', handleClickOutside);
                };
            }, []);

            React.useEffect(() => {
                fetchData();
            }, []);

            const getStatusColor = (status) => {
                switch (status) {
                    case 'success': return 'bg-green-500';
                    case 'warning': return 'bg-yellow-500';
                    case 'error': return 'bg-red-500';
                    default: return 'bg-gray-300';
                }
            };

            if (loading) return (
                <div className="min-h-screen bg-gray-50 flex items-center justify-center">
                    <div className="text-xl text-gray-600">Lade Daten...</div>
                </div>
            );

            if (error) return (
                <div className="min-h-screen bg-gray-50 flex items-center justify-center">
                    <div className="text-xl text-red-600">{error}</div>
                </div>
            );

            if (!data || !data.stats) return (
                <div className="min-h-screen bg-gray-50 flex items-center justify-center">
                    <div className="text-xl text-gray-600">Keine Daten verfügbar</div>
                </div>
            );

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
                        {data.data && data.data.map((customerData) => (
                            <div key={customerData.customer.id} className="bg-white rounded-lg shadow-lg p-6">
                                <div className="flex items-center gap-2 mb-6">
                                    <h2 className="text-xl font-semibold">{customerData.customer.name}</h2>
                                    <span className="text-sm text-gray-500">({customerData.customer.number})</span>
                                </div>

                                {Object.values(customerData.jobs || {}).map((job) => (
                                    <div key={job.job_id} className="mb-6 last:mb-0">
                                        <div className="flex items-center gap-2 mb-2">
                                            <span className="px-3 py-1 bg-gray-100 rounded-full text-sm">
                                                {job.backup_type}
                                            </span>
                                            <h3 className="font-medium">{job.job_name}</h3>
                                        </div>

                                        <div className="flex gap-1">
                                            {job.results && groupResultsByDate(job.results).map((groupedResult, index) => {
                                                const isNearEnd = index % 8 >= 5;
                                                
                                                return (
                                                    <div key={index} className="relative">
                                                        <div 
                                                            className={`w-8 h-8 rounded cursor-pointer status-square ${getStatusColor(groupedResult.status)}`}
                                                            onClick={() => {
                                                                setIsTooltipLocked(true);
                                                                setActiveTooltip(`${job.job_id}-${index}`);
                                                            }}
                                                        >
                                                            {groupedResult.results.length > 1 && (
                                                                <div className="absolute -top-2 -right-2 bg-blue-500 text-white rounded-full w-4 h-4 text-xs flex items-center justify-center">
                                                                    {groupedResult.results.length}
                                                                </div>
                                                            )}
                                                        </div>

                                                        {activeTooltip === `${job.job_id}-${index}` && (
                                                            <div 
                                                                className={`absolute z-50 w-72 bg-white rounded-lg shadow-xl border p-4 mt-2 tooltip-content ${isNearEnd ? '-left-64' : 'left-0'}`}
                                                            >
                                                                {groupedResult.results.map((result, resultIndex) => (
                                                                    <div key={resultIndex} className={`space-y-2 ${resultIndex > 0 ? 'mt-4 pt-4 border-t' : ''}`}>
                                                                        <div className="flex justify-between">
                                                                            <span className="font-semibold">Datum:</span>
                                                                            <span>{result.date}</span>
                                                                        </div>
                                                                        <div className="flex justify-between">
                                                                            <span className="font-semibold">Zeit:</span>
                                                                            <span>{result.time}</span>
                                                                        </div>
                                                                        <div className="flex justify-between">
                                                                            <span className="font-semibold">Status:</span>
                                                                            <span className={
                                                                                result.status === 'success' ? 'text-green-600' :
                                                                                result.status === 'warning' ? 'text-yellow-600' :
                                                                                'text-red-600'
                                                                            }>
                                                                                {result.status === 'success' ? 'Erfolgreich' :
                                                                                result.status === 'warning' ? 'Warnung' : 'Fehler'}
                                                                            </span>
                                                                        </div>
                                                                        {result.size_mb && (
                                                                            <div className="flex justify-between">
                                                                                <span className="font-semibold">Größe:</span>
                                                                                <span>{parseFloat(result.size_mb).toFixed(2)} MB</span>
                                                                            </div>
                                                                        )}
                                                                        {result.duration_minutes && (
                                                                            <div className="flex justify-between">
                                                                                <span className="font-semibold">Dauer:</span>
                                                                                <span>{result.duration_minutes} min</span>
                                                                            </div>
                                                                        )}
                                                                        <div className="pt-2">
                                                                            <textarea
                                                                                className="w-full p-2 text-sm border rounded"
                                                                                value={notesTexts[result.id] || result.note || ''}
                                                                                onChange={(e) => handleNoteChange(result.id, e.target.value)}
                                                                                placeholder="Notiz..."
                                                                            />
                                                                            <button 
                                                                                className="mt-2 px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600"
                                                                                onClick={() => {
                                                                                    saveNote(result.id, notesTexts[result.id]);
                                                                                }}
                                                                            >
                                                                                Speichern
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                ))}
                                                            </div>
                                                        )}
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ))}
                    </div>
                </div>
            );
        };

        ReactDOM.render(<Dashboard />, document.getElementById('root'));
    </script>
</body>
</html>