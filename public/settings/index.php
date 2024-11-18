<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Einstellungen</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/react@17/umd/react.development.js"></script>
    <script src="https://unpkg.com/react-dom@17/umd/react-dom.development.js"></script>
    <script src="https://unpkg.com/babel-standalone@6/babel.min.js"></script>
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal">

<div id="settings-root"></div>

<script type="text/babel">
    const { useState } = React;

    function Settings() {
        const [activePage, setActivePage] = useState("mail");

        // Komponenten für die einzelnen Einstellungsseiten
        const renderContent = () => {
            switch (activePage) {
                case "customers":
                    return <Customers />;
                case "backup_jobs":
                    return <BackupJobs />;
                default:
                    return <MailSetup />;
            }
        };

        return (
            <div className="flex h-screen">
                {/* Seitenmenü */}
                <aside className="w-1/4 bg-gray-200 p-4">
                    <ul>
                        <li>
                            <button
                                onClick={() => setActivePage("mail")}
                                className={`block w-full text-left px-4 py-2 rounded-md ${activePage === "mail" ? "bg-blue-500 text-white" : "text-gray-700"}`}
                            >
                                Mail-Einstellungen
                            </button>
                        </li>
                        <li>
                            <button
                                onClick={() => setActivePage("customers")}
                                className={`block w-full text-left px-4 py-2 rounded-md ${activePage === "customers" ? "bg-blue-500 text-white" : "text-gray-700"}`}
                            >
                                Kunden
                            </button>
                        </li>
                        <li>
                            <button
                                onClick={() => setActivePage("backup_jobs")}
                                className={`block w-full text-left px-4 py-2 rounded-md ${activePage === "backup_jobs" ? "bg-blue-500 text-white" : "text-gray-700"}`}
                            >
                                Backup-Jobs
                            </button>
                        </li>
                    </ul>
                </aside>

                {/* Inhalt der aktuellen Seite */}
                <main className="flex-1 p-8 bg-white shadow-md rounded-md m-4">
                    {renderContent()}
                </main>
            </div>
        );
    }

    // Komponenten für die Seiteninhalte

    function MailSetup() {
        return (
            <div>
                <h2 className="text-2xl font-semibold mb-4">Mail-Einstellungen</h2>
                <form method="post" action="">
                    <div className="mb-4">
                        <label htmlFor="mail_address" className="block text-gray-700">Mail-Adresse:</label>
                        <input type="email" name="mail_address" id="mail_address" className="mt-1 p-2 w-full border rounded" required />
                    </div>
                    <div className="mb-4">
                        <label htmlFor="password" className="block text-gray-700">Passwort:</label>
                        <input type="password" name="password" id="password" className="mt-1 p-2 w-full border rounded" required />
                    </div>
                    <button type="submit" className="px-4 py-2 bg-blue-500 text-white rounded">Speichern</button>
                </form>
            </div>
        );
    }

    function Customers() {
        return (
            <div>
                <h2 className="text-2xl font-semibold mb-4">Kundenverwaltung</h2>
                <table className="w-full border-collapse">
                    <thead>
                        <tr>
                            <th className="border p-2 text-left">Name</th>
                            <th className="border p-2 text-left">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        {/* Beispielhafte Daten – Datenabruf kann später integriert werden */}
                        <tr>
                            <td className="border p-2">Beispielkunde 1</td>
                            <td className="border p-2">
                                <button className="text-blue-500 mr-2">Bearbeiten</button>
                                <button className="text-red-500">Löschen</button>
                            </td>
                        </tr>
                        {/* Weitere Kunden... */}
                    </tbody>
                </table>
                <form className="mt-4">
                    <label className="block text-gray-700 mb-2">Kundenname:</label>
                    <input type="text" name="customer_name" className="mt-1 p-2 w-full border rounded mb-2" required />
                    <button type="submit" className="px-4 py-2 bg-blue-500 text-white rounded">Kunde hinzufügen</button>
                </form>
            </div>
        );
    }

    function BackupJobs() {
        return (
            <div>
                <h2 className="text-2xl font-semibold mb-4">Backup-Jobs</h2>
                <table className="w-full border-collapse">
                    <thead>
                        <tr>
                            <th className="border p-2 text-left">Job Name</th>
                            <th className="border p-2 text-left">Zugewiesener Kunde</th>
                            <th className="border p-2 text-left">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        {/* Beispielhafte Daten */}
                        <tr>
                            <td className="border p-2">Job 1</td>
                            <td className="border p-2">Kunde 1</td>
                            <td className="border p-2">
                                <button className="text-blue-500 mr-2">Bearbeiten</button>
                                <button className="text-red-500">Löschen</button>
                            </td>
                        </tr>
                        {/* Weitere Jobs... */}
                    </tbody>
                </table>
                <form className="mt-4">
                    <label className="block text-gray-700 mb-2">Job-Name:</label>
                    <input type="text" name="job_name" className="mt-1 p-2 w-full border rounded mb-2" required />
                    <button type="submit" className="px-4 py-2 bg-blue-500 text-white rounded">Job hinzufügen</button>
                </form>
            </div>
        );
    }

    ReactDOM.render(<Settings />, document.getElementById('settings-root'));
</script>

</body>
</html>