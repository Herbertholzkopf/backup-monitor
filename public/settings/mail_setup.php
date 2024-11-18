<!-- /public/settings/mail_setup.php -->
<h2>Mail-Einstellungen</h2>
<form method="post" action="">
    <label for="mail_address">Mail-Adresse:</label>
    <input type="email" name="mail_address" id="mail_address" required>
    <label for="password">Passwort:</label>
    <input type="password" name="password" id="password" required>
    <button type="submit">Speichern</button>
</form>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mailAddress = $_POST['mail_address'] ?? '';
    $password = $_POST['password'] ?? '';
    MailController::saveMailConfig($mailAddress, $password);
    echo "Mail-Konfiguration gespeichert.";
}
?>