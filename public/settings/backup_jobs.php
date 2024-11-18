<!-- /public/settings/backup_jobs.php -->
<h2>Backup-Jobs</h2>

<table>
    <tr>
        <th>Job Name</th>
        <th>Zugewiesener Kunde</th>
        <th>Aktionen</th>
    </tr>
    <?php
    $backupJobs = BackupJobController::getAllBackupJobs();
    foreach ($backupJobs as $job):
    ?>
        <tr>
            <td><?= htmlspecialchars($job->name) ?></td>
            <td><?= htmlspecialchars($job->customer_id) ?></td>
            <td>
                <a href="edit_job.php?id=<?= $job->id ?>">Bearbeiten</a>
                <a href="delete_job.php?id=<?= $job->id ?>" onclick="return confirm('Sind Sie sicher?')">Löschen</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<form method="post" action="add_job.php">
    <label for="job_name">Job-Name:</label>
    <input type="text" name="job_name" id="job_name" required>
    <button type="submit">Job hinzufügen</button>
</form>