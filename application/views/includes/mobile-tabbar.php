<?php
$msLevel = (string)$this->session->userdata('level');
$msTabs = [];

$msAddTab = static function (&$tabs, $route, $label, $icon) {
    $tabs[] = [
        'href' => base_url($route),
        'label' => $label,
        'icon' => $icon,
    ];
};

switch ($msLevel) {
    case 'Student':
    case 'Stude Applicant':
        $msAddTab($msTabs, 'Page/student', 'Home', 'mdi-home-variant-outline');
        $msAddTab($msTabs, 'student/my_qr', 'My QR', 'mdi-qrcode');
        $msAddTab($msTabs, 'Page/studentAccountingRecords', 'Payments', 'mdi-cash-multiple');
        $msAddTab($msTabs, 'Page/studentProfile', 'Profile', 'mdi-account-circle-outline');
        break;

    case 'Admin':
        $msAddTab($msTabs, 'Page/admin', 'Home', 'mdi-home-variant-outline');
        $msAddTab($msTabs, 'AttendanceLogs', 'Attendance', 'mdi-calendar-check-outline');
        $msAddTab($msTabs, 'activities', 'Activities', 'mdi-calendar-star');
        $msAddTab($msTabs, 'Page/announcement', 'News', 'mdi-bullhorn-outline');
        break;

    case 'Registrar':
    case 'Head Registrar':
        $msAddTab($msTabs, 'Page/registrar', 'Home', 'mdi-home-variant-outline');
        $msAddTab($msTabs, 'Page/profileList', 'Students', 'mdi-account-group-outline');
        $msAddTab($msTabs, 'Page/requestSummary', 'Requests', 'mdi-file-document-outline');
        break;

    case 'Encoder':
        $msAddTab($msTabs, 'Page/encoder', 'Home', 'mdi-home-variant-outline');
        $msAddTab($msTabs, 'Page/profileListEncoder', 'Students', 'mdi-account-group-outline');
        break;

    case 'School Admin':
        $msAddTab($msTabs, 'Page/school_admin', 'Home', 'mdi-home-variant-outline');
        $msAddTab($msTabs, 'Masterlist/bySY', 'Masterlist', 'mdi-account-multiple-outline');
        $msAddTab($msTabs, 'Page/er', 'Reports', 'mdi-chart-box-outline');
        break;

    case 'IT':
        $msAddTab($msTabs, 'Page/IT', 'Home', 'mdi-home-variant-outline');
        $msAddTab($msTabs, 'Page/userAccounts', 'Users', 'mdi-account-cog-outline');
        break;

    case 'Super Admin':
        $msAddTab($msTabs, 'Page/superAdmin', 'Home', 'mdi-home-variant-outline');
        $msAddTab($msTabs, 'AttendanceLogs', 'Attendance', 'mdi-calendar-check-outline');
        break;

    case 'Accounting':
        $msAddTab($msTabs, 'Page/accounting', 'Home', 'mdi-home-variant-outline');
        $msAddTab($msTabs, 'Accounting/studeAccounts', 'Accounts', 'mdi-account-outline');
        $msAddTab($msTabs, 'Accounting/Payment', 'Payments', 'mdi-cash-multiple');
        break;
}
?>

<?php if ($msTabs): ?>
<nav class="ms-tabbar" role="navigation" aria-label="Primary mobile navigation">
    <?php foreach ($msTabs as $msTab): ?>
        <a class="ms-tab" href="<?= htmlspecialchars($msTab['href'], ENT_QUOTES, 'UTF-8'); ?>">
            <i class="mdi <?= htmlspecialchars($msTab['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
            <span class="ms-tab-label"><?= htmlspecialchars($msTab['label'], ENT_QUOTES, 'UTF-8'); ?></span>
        </a>
    <?php endforeach; ?>
    <a class="ms-tab" href="#ms-drawer" role="button" data-ms-drawer-toggle aria-label="Open full menu">
        <i class="mdi mdi-menu" aria-hidden="true"></i>
        <span class="ms-tab-label">More</span>
    </a>
</nav>
<?php endif; ?>

<?php unset($msLevel, $msTabs, $msTab, $msAddTab); ?>
