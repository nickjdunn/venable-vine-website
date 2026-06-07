<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireLogin();

$adminTitle = 'Ordering (Coming Soon)';
require ROOT . '/includes/templates/admin-header.php';
?>
<h1>Online Ordering — Phase 2</h1>
<div class="card">
    <p>Online ordering is planned for a future update. The database already includes <code>orders</code> and <code>order_items</code> tables for when you're ready to add pre-orders or payment integration.</p>
    <h3>Planned features</h3>
    <ul>
        <li>Simple pre-order form (pick items + pickup time/location)</li>
        <li>Order management inbox in admin</li>
        <li>Optional Stripe/Square payment integration</li>
    </ul>
    <p>For now, customers can view the menu and find your location on the public site.</p>
</div>
<?php require ROOT . '/includes/templates/admin-footer.php'; ?>
