<nav class="admin-nav">
    <ul>
        <li><a href="<?= APP_URL ?>/pages/admin/dashboard.php" <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'class="active"' : '' ?>>
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a></li>
        <li><a href="<?= APP_URL ?>/pages/admin/stations.php" <?= basename($_SERVER['PHP_SELF']) === 'stations.php' ? 'class="active"' : '' ?>>
            <i class="fas fa-charging-station"></i> Stations
        </a></li>
        <li><a href="<?= APP_URL ?>/pages/admin/users.php" <?= basename($_SERVER['PHP_SELF']) === 'users.php' ? 'class="active"' : '' ?>>
            <i class="fas fa-users"></i> Users
        </a></li>
        <li><a href="<?= APP_URL ?>/pages/admin/bookings.php" <?= basename($_SERVER['PHP_SELF']) === 'bookings.php' ? 'class="active"' : '' ?>>
            <i class="fas fa-calendar-alt"></i> Bookings
        </a></li>
        <li><a href="<?= APP_URL ?>/pages/admin/reports.php" <?= basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'class="active"' : '' ?>>
            <i class="fas fa-chart-bar"></i> Reports
        </a></li>
        <li><a href="<?= APP_URL ?>/pages/admin/maintenance.php" <?= basename($_SERVER['PHP_SELF']) === 'maintenance.php' ? 'class="active"' : '' ?>>
            <i class="fas fa-tools"></i> Maintenance
        </a></li>
    </ul>
</nav>