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
    <title>Backup Monitor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/react@17/umd/react.development.js"></script>
    <script src="https://unpkg.com/react-dom@17/umd/react-dom.development.js"></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
</head>
<body class="bg-gray-100 text-gray-900">
    <div id="root"></div>
    <script type="text/babel">
        const Dashboard = () => {
            const [data, setData] = React.useState([]);

            React.useEffect(() => {
                fetch('/api/dashboard')
                    .then(res => res.json())
                    .then(json => {
                        if (json.success) setData(json.data);
                    })
                    .catch(console.error);
            }, []);

            return (
                <div className="p-6">
                    <h1 className="text-2xl font-bold mb-4">Backup Monitor</h1>
                    {data.map(({ customer, jobs }) => (
                        <div key={customer.id} className="mb-6">
                            <h2 className="text-xl font-semibold">{customer.name} ({customer.number})</h2>
                            <p className="text-sm text-gray-600">{customer.note}</p>
                            <div className="mt-4 space-y-4">
                                {jobs.map(({ job_id, job_name, backup_type, results }) => (
                                    <div key={job_id} className="p-4 border rounded-lg">
                                        <h3 className="font-medium">{job_name} ({backup_type})</h3>
                                        <table className="w-full mt-2 text-sm">
                                            <thead>
                                                <tr>
                                                    <th className="text-left">Datum</th>
                                                    <th className="text-left">Uhrzeit</th>
                                                    <th className="text-left">Status</th>
                                                    <th className="text-left">Größe (MB)</th>
                                                    <th className="text-left">Dauer (Min)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {results.map((result, idx) => (
                                                    <tr key={idx} className="border-t">
                                                        <td>{result.date}</td>
                                                        <td>{result.time}</td>
                                                        <td className={getStatusColor(result.status)}>
                                                            {result.status}
                                                        </td>
                                                        <td>{result.size_mb}</td>
                                                        <td>{result.duration_minutes}</td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
            );
        };

        const getStatusColor = (status) => {
            switch (status) {
                case 'success': return 'text-green-500';
                case 'warning': return 'text-yellow-500';
                case 'error': return 'text-red-500';
                default: return 'text-gray-500';
            }
        };

        ReactDOM.render(<Dashboard />, document.getElementById('root'));
    </script>
</body>
</html>