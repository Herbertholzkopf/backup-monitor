<!-- /public/settings/customers.php -->
<h2>Kundenverwaltung</h2>

<table>
    <tr>
        <th>Name</th>
        <th>Aktionen</th>
    </tr>
    <?php
    $customers = CustomerController::getAllCustomers();
    foreach ($customers as $customer):
    ?>
        <tr>
            <td><?= htmlspecialchars($customer->name) ?></td>
            <td>
                <a href="edit_customer.php?id=<?= $customer->id ?>">Bearbeiten</a>
                <a href="delete_customer.php?id=<?= $customer->id ?>" onclick="return confirm('Sind Sie sicher?')">Löschen</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<form method="post" action="add_customer.php">
    <label for="customer_name">Kundenname:</label>
    <input type="text" name="customer_name" id="customer_name" required>
    <button type="submit">Kunde hinzufügen</button>
</form>