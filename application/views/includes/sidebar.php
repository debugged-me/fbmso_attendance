<?php
if (!isset($online_settings)) {
    $settingsRow = $this->db->select('show_online_payments')
        ->from('o_srms_settings')->limit(1)->get()->row();
    $online_settings = $settingsRow ?: (object)['show_online_payments' => 1]; // default ON
}
$showOnline = (int)($online_settings->show_online_payments ?? 1);
?>

<style>
    .left-side-menu .metismenu li>ul {
        display: none !important;
    }

    .left-side-menu .metismenu li.mm-open>ul,
    .left-side-menu .metismenu li>ul.mm-show {
        display: block !important;
    }
</style>

<div class="left-side-menu">
    <div class="slimscroll-menu">
        <?php if ($this->session->userdata('level') === 'Super Admin'): ?>
            <?php
            $currentUri = trim(uri_string(), '/');
            $isActive = static function ($prefix) use ($currentUri) {
                return stripos($currentUri, trim($prefix, '/')) === 0 ? 'mm-active active' : '';
            };
            ?>
            <div id="sidebar-menu">
                <ul class="metismenu" id="side-menu">
                    <li class="menu-title">SUPER ADMIN</li>

                    <li class="<?= $isActive('Page/superAdmin'); ?>">
                        <a href="<?= base_url('Page/superAdmin'); ?>" class="waves-effect">
                            <i class="mdi mdi-view-dashboard-outline"></i>
                            <span> Dashboard </span>
                        </a>
                    </li>

                    <li class="<?= $isActive('Settings/schoolInfo'); ?>">
                        <a href="<?= base_url('Settings/schoolInfo?panel=mass_email'); ?>" class="waves-effect">
                            <i class="mdi mdi-email-outline"></i>
                            <span> Mass Email Setup </span>
                        </a>
                    </li>

                    <li class="<?= $isActive('mass-announcement'); ?>">
                        <a href="<?= base_url('mass-announcement'); ?>" class="waves-effect">
                            <i class="mdi mdi-bullhorn-outline"></i>
                            <span> Email Announcement </span>
                        </a>
                    </li>

                    <li class="<?= $isActive('Page/announcement'); ?>">
                        <a href="<?= base_url('Page/announcement?id=' . urlencode((string)$this->session->userdata('username'))); ?>" class="waves-effect">
                            <i class="bi bi-megaphone"></i>
                            <span> Announcement </span>
                        </a>
                    </li>

                    <li class="<?= $isActive('Page/changeDP'); ?>">
                        <a href="<?= base_url('Page/changeDP?id=' . urlencode((string)$this->session->userdata('username'))); ?>" class="waves-effect">
                            <i class="mdi mdi-account-circle-outline"></i>
                            <span> Change Profile Picture </span>
                        </a>
                    </li>

                    <li class="<?= $isActive('Page/changepassword'); ?>">
                        <a href="<?= base_url('Page/changepassword'); ?>" class="waves-effect">
                            <i class="ion bi bi-shield-lock"></i>
                            <span> Change Password </span>
                        </a>
                    </li>

                    <li>
                        <a href="<?= base_url('Login/logout'); ?>" class="waves-effect logout-confirm">
                            <i class="ion bi bi-box-arrow-right"></i>
                            <span> Logout </span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- System Administrator -->
        <?php elseif ($this->session->userdata('level') === 'Admin'): ?>
            <?php
            $currentUri = trim(uri_string(), '/');
            $segment1   = strtolower($this->uri->segment(1) ?? '');
            $segment2   = strtolower($this->uri->segment(2) ?? '');
            ?>
            <div id="sidebar-menu">
                <ul class="metismenu" id="side-menu">

                    <li class="menu-title">ADMINISTRATION</li>
                    <li>
                        <a href="<?= base_url(); ?>Page/admin" class="waves-effect">
                            <i class="bi bi-speedometer2"></i>
                            <span> Dashboard </span>
                        </a>
                    </li>

                    <li>
                        <a href="<?= base_url('Page/profileList'); ?>">
                            <i class="bi bi-person-fill"></i>
                            <span> Registered Students </span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= base_url('AttendanceLogs'); ?>" class="waves-effect">
                            <i class="bi bi-clipboard-check"></i>
                            <span> Attendance Logs </span>
                        </a>
                    </li>

                    <!-- ===== HIDE: Document Request =====
            <li>
                <a href="javascript: void(0);" class="waves-effect">
                    <i class="fas fa-archive"></i>
                    <span> Document Request </span>
                    <span class="menu-arrow"></span>
                </a>
                <ul class="nav-second-level nav" aria-expanded="false">
                    <li><a href="<?= base_url(); ?>Request/document_types">Docs for Request</a></li>
                    <li><a href="<?= base_url(); ?>Request/">Requests</a></li>
                    <li><a href="<?= base_url(); ?>Page/requestSummary">Summary</a></li>
                </ul>
            </li>
            ===== /HIDE ===== -->

                    <!-- ===== HIDE: Upload =====
            <li>
                <a href="javascript: void(0);" class="waves-effect">
                    <i class="ion ion-md-cloud-upload"></i>
                    <span> Upload </span>
                    <span class="menu-arrow"></span>
                </a>
                <ul class="nav-second-level" aria-expanded="false">
                    <li><a href="<?= base_url(); ?>FileUploader/">Students' Profile</a></li>
                    <li><a href="<?= base_url(); ?>FileUploader/teachers">Faculty and Staff</a></li>
                    <li><a href="<?= base_url(); ?>FileUploader/Courses">Courses</a></li>
                    <li><a href="<?= base_url(); ?>FileUploader/subjects">Subjects</a></li>
                </ul>
            </li>
            ===== /HIDE ===== -->

                    <!-- Announcement (keep visible) -->


                    <!-- Activities (QR) (keep visible) -->
                    <?php
                    $flagPath = APPPATH . 'cache' . DIRECTORY_SEPARATOR . 'qr_poster_mode.flag';
                    $posterModeGlobal = (is_file($flagPath) && strtolower(trim(@file_get_contents($flagPath))) === 'on');
                    $isAdmin = ($this->session->userdata('level') === 'Admin');
                    $activitiesOpen = ($segment1 === 'activities');
                    $activitiesExpand = $activitiesOpen ? 'true' : 'false';
                    $activitiesShow = $activitiesOpen ? 'mm-show' : '';
                    $activitiesActive = $activitiesOpen ? 'mm-active' : '';
                    ?>
                    <li class="<?= $activitiesActive; ?>">
                        <a href="javascript:void(0);" class="waves-effect has-arrow" aria-expanded="<?= $activitiesExpand; ?>">
                            <i class="ion bi bi-qr-code-scan"></i>
                            <span> Activities (QR) </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="nav-second-level <?= $activitiesShow; ?>" aria-expanded="<?= $activitiesExpand; ?>">
                            <li class="<?= $activitiesOpen ? 'active' : ''; ?>"><a href="<?= base_url('activities'); ?>">Open Activities</a></li>

                            <?php if ($isAdmin): ?>
                                <li class="pt-1 pb-2 px-3">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <span class="text-muted"> Poster Mode </span>
                                        <div class="custom-control custom-switch m-0">
                                            <input
                                                type="checkbox"
                                                class="custom-control-input"
                                                id="qrPosterSwitch"
                                                aria-label="Toggle Poster Mode"
                                                onchange="location.href='<?= site_url('activities/set_mode/') ?>'+(this.checked?'on':'off')"
                                                <?= $posterModeGlobal ? 'checked' : '' ?>>
                                            <label class="custom-control-label" for="qrPosterSwitch"></label>
                                        </div>
                                    </div>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <li>
                        <a href="<?= base_url(); ?>Page/announcement?id=<?php echo $this->session->userdata('username'); ?>" class="waves-effect">
                            <i class="bi bi-megaphone"></i>
                            <span> Announcement </span>
                        </a>
                    </li>
                    <li class="<?= (stripos($currentUri, 'mass-announcement') === 0 ? 'mm-active active' : ''); ?>">
                        <a href="<?= base_url('mass-announcement'); ?>" class="waves-effect">
                            <i class="mdi mdi-bullhorn-outline"></i>
                            <span> Email Announcement </span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= base_url('reports'); ?>" class="waves-effect">
                            <i class="bi bi-bar-chart-line"></i>
                            <span>Activities Reports </span>
                        </a>
                    </li>
                    <?php
                    $is = static function ($prefix) use ($currentUri) {
                        return stripos($currentUri, trim($prefix, '/')) === 0;
                    };
                    $active = static function ($prefix) use ($is) {
                        return $is($prefix) ? 'mm-active active' : '';
                    };
                    $expensesOpen = (
                        $is('Accounting/expenses') ||
                        $is('Accounting/expensescategory') ||
                        $is('Accounting/expensesReport')
                    );
                    $expensesExpanded = $expensesOpen ? 'true' : 'false';
                    $expensesShow     = $expensesOpen ? 'mm-show' : '';

                    $billingOpen = (
                        stripos($currentUri, 'Accounting/Payment') === 0 ||
                        stripos($currentUri, 'Accounting/receipt') === 0 ||
                        stripos($currentUri, 'Accounting/course_setUp') === 0 ||
                        stripos($currentUri, 'Accounting/collectionReport') === 0 ||
                        stripos($currentUri, 'Accounting/collectionDateRange') === 0 ||
                        stripos($currentUri, 'Accounting/collectionMonthly') === 0 ||
                        stripos($currentUri, 'Accounting/collectionYear') === 0
                    );
                    $billingExpand = $billingOpen ? 'true' : 'false';
                    $billingShow   = $billingOpen ? 'mm-show' : '';
                    $billingActive = $billingOpen ? 'mm-active mm-open' : '';
                    ?>

                    <li class="<?= $billingActive; ?>">
                        <a href="javascript:void(0);" class="waves-effect has-arrow" aria-expanded="<?= $billingExpand; ?>">
                            <i class="ion ion-ios-cash"></i>
                            <span> Accounting </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="nav-second-level <?= $billingShow; ?>" aria-expanded="<?= $billingExpand; ?>">
                            <li><a href="<?= base_url('Accounting/Payment'); ?>">Payment Entry</a></li>
                            <li><a href="<?= base_url('Accounting/course_setUp'); ?>">Fees Setup</a></li>
                            <li><a href="<?= base_url('Accounting/collectionReport'); ?>">Collection Report</a></li>
                        </ul>
                    </li>
                    <!-- School Expenses -->
                    <li class="<?= $expensesShow ? 'mm-active' : '' ?>">
                        <a href="javascript:void(0);" class="waves-effect" aria-expanded="<?= $expensesExpanded; ?>">
                            <i class="fas fa-receipt"></i>
                            <span> School Expenses </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="nav-second-level nav <?= $expensesShow; ?>" aria-expanded="<?= $expensesExpanded; ?>">
                            <li class="<?= $active('Accounting/expenses'); ?>">
                                <a href="<?= base_url('Accounting/expenses'); ?>">Expenses</a>
                            </li>
                            <li class="<?= $active('Accounting/expensescategory'); ?>">
                                <a href="<?= base_url('Accounting/expensescategory'); ?>">Expenses Category</a>
                            </li>
                            <li class="<?= $active('Accounting/expensesReport'); ?>">
                                <a href="<?= base_url('Accounting/expensesReport'); ?>">Expenses Reports</a>
                            </li>
                        </ul>
                    </li>
                    <!-- To Do (keep visible) -->
                    <!-- <li>
                <a href="javascript: void(0);" class="waves-effect">
                    <i class="ion ion-md-paper"></i>
                    <span> To Do </span>
                    <span class="menu-arrow"></span>
                </a>
                <ul class="nav-second-level" aria-expanded="false">
                    <li><a href="<?= base_url(); ?>ToDo/">ToDo</a></li>
                </ul>
            </li> -->

                    <!-- Notes (keep visible) -->
                    <!-- <li>
                <a href="<?= base_url(); ?>Note/" class="waves-effect">
                    <i class="ion ion-md-paper"></i>
                    <span> Notes </span>
                </a>
            </li> -->
                    <?php
                    $manageMatch = static function (array $prefixes) use ($currentUri) {
                        foreach ($prefixes as $prefix) {
                            $prefix = trim($prefix, '/');
                            if ($prefix !== '' && stripos($currentUri, $prefix) === 0) {
                                return true;
                            }
                        }
                        return false;
                    };
                    $manageTargets = [
                        'Settings/Department',
                        'Settings/department',
                        'Page/manageSections',
                        'Page/addSection',
                        'Page/editSection',
                        'Page/userAccounts'
                    ];
                    $manageOpen   = $manageMatch($manageTargets);
                    $manageExpand = $manageOpen ? 'true' : 'false';
                    $manageShow   = $manageOpen ? 'mm-show' : '';
                    $manageActive = $manageOpen ? 'mm-active mm-open' : '';
                    ?>

                    <li class="<?= $manageActive; ?>">
                        <a href="javascript: void(0);" class="waves-effect has-arrow" aria-expanded="<?= $manageExpand; ?>">
                            <i class="ion ion-md-contacts"></i>
                            <span> Manage </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="nav-second-level <?= $manageShow; ?>" aria-expanded="<?= $manageExpand; ?>">
                            <?php
                            $isCourseActive   = stripos($currentUri, trim('Settings/Department', '/')) === 0;
                            $sectionPrefixes  = ['Page/manageSections', 'Page/addSection', 'Page/editSection'];
                            $isSectionActive  = $manageMatch($sectionPrefixes);
                            $isUserActive     = stripos($currentUri, trim('Page/userAccounts', '/')) === 0;

                            $courseLinkClass  = $isCourseActive ? 'active' : '';
                            $sectionLinkClass = $isSectionActive ? 'active' : '';
                            $userLinkClass    = $isUserActive ? 'active' : '';
                            ?>
                            <li>
                                <a class="<?= $courseLinkClass; ?>" href="<?= base_url('Settings/Department'); ?>"> Course </a>
                            </li>
                            <!-- Manage Sections -->
                            <li>
                                <a class="<?= $sectionLinkClass; ?>" href="<?= base_url('Page/manageSections'); ?>"> Sections </a>
                            </li>
                            <li>
                                <a class="<?= $userLinkClass; ?>" href="<?= base_url('Page/userAccounts'); ?>"> Admin Accounts </a>
                            </li>
                        </ul>
                    </li>

                    <li>
                        <a href="javascript: void(0);" class="waves-effect">
                            <i class="ion ion-md-settings"></i>
                            <span> User Settings </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li><a href="<?= base_url('Page/changeDP?id=' . urlencode($this->session->userdata('username'))); ?>">Change Profile Picture</a></li>

                            <li><a href="<?= base_url('Page/changepassword'); ?>">Change Password</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="<?= base_url('FbmsoPersonnels/manage') ?>">
                            <i class="mdi mdi-account-group"></i> <span> FBMSO Officials </span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= base_url('Login/logout'); ?>" class="waves-effect logout-confirm">
                            <i class="ion bi bi-box-arrow-right"></i>
                            <span> Logout </span>
                        </a>
                    </li>
                    <!-- Settings (keep visible) -->
                    <!-- <li>
                <a href="javascript: void(0);" class="waves-effect">
                    <i class="ion ion-md-settings"></i>
                    <span> Settings </span>
                    <span class="menu-arrow"></span>
                </a>
                <ul class="nav-second-level" aria-expanded="false">
                    <li><a href="<?= base_url(); ?>Settings/schoolInfo">School Information</a></li>
                    <li><a href="<?= base_url(); ?>Settings/loginFormBanner">Login Page Image</a></li>
                    <li><a href="<?= base_url(); ?>GradeSetting/index">Grades Settings</a></li>
                    <li>
                        <a href="<?= base_url(); ?>GradeEncoding/index" class="waves-effect">
                            <i class="ion ion-ios-key"></i>
                            <span> Encode Grades </span>
                        </a>
                    </li>
                </ul>
            </li> -->

                    <!-- ===== HIDE: SRMS FAQ =====
            <li>
                <a href="<?= base_url(); ?>FAQ/" target="_blank" class="waves-effect">
                    <i class="ion ion-md-help"></i>
                    <span> SRMS FAQ </span>
                </a>
            </li>
            ===== /HIDE ===== -->

                </ul>
            </div>


        <?php elseif ($this->session->userdata('level') === 'IT'): ?>
            <div id="sidebar-menu">
                <ul class="metismenu" id="side-menu">

                    <li class="menu-title">ADMINISTRATION</li>

                    <li>
                        <a href="<?= base_url(); ?>Page/IT" class="waves-effect">
                            <i class="ion-md-speedometer"></i>
                            <span> Dashboard </span>
                        </a>
                    </li>

                    <li>
                        <a href="javascript: void(0);" class="waves-effect">
                            <i class="ion ion-md-contacts "></i>
                            <span> Manage Users </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li><a href="<?= base_url(); ?>Page/userAccounts">User Accounts</a></li>
                        </ul>
                    </li>

                    <li>
                        <a href="javascript: void(0);" class="waves-effect">
                            <i class="ion ion-md-settings"></i>
                            <span> Settings </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <!-- <li><a href="<?= base_url(); ?>Settings/schoolInfo">School Information</a></li> -->
                            <li><a href="<?= base_url(); ?>Settings/loginFormBanner">Login Page Image</a></li>
                        </ul>
                    </li>

                    <li>
                        <a href="javascript: void(0);" class="waves-effect">
                            <i class="ion ion-md-paper"> </i>
                            <span> To Do </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li><a href="<?= base_url(); ?>ToDo/">ToDo</a></li>
                        </ul>
                    </li>


                    <li>
                        <a href="<?= base_url(); ?>admin/backup_database" class=" waves-effect">
                            <i class="ion ion-md-paper"> </i>
                            <span> Backup Database </span>

                        </a>

                    </li>

                    <li>
                        <a href="<?= base_url(); ?>Note/" class=" waves-effect">
                            <i class="ion ion-md-paper"> </i>
                            <span> Notes </span>

                        </a>

                    </li>

                    <li>
                        <a href="javascript: void(0);" class="waves-effect">
                            <i class="ion ion-md-settings"></i>
                            <span> User Settings </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li><a href="<?= base_url('Page/changepassword'); ?>">Change Password</a></li>
                            <li><a href="<?= base_url('Page/changeDP?id=' . urlencode($this->session->userdata('username'))); ?>">Change Profile Picture</a></li>
                        </ul>
                    </li>

                </ul>

            </div>
            <!-- End Sidebar -->

        <?php elseif ($this->session->userdata('level') === 'Encoder') : ?>
            <div id="sidebar-menu">
                <ul class="metismenu" id="side-menu">

                    <li class="menu-title">Navigation</li>

                    <li>
                        <a href="<?= base_url(); ?>Page/encoder" class="waves-effect">
                            <i class="ion ion-md-home"></i>
                            <span> Dashboard </span>
                        </a>
                    </li>

                    <li>
                        <a href="javascript: void(0);" class="waves-effect">
                            <i class=" ion ion-md-school"></i>
                            <span> Admission </span>
                            <span class="menu-arrow"></span>


                        </a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li><a href="<?= base_url(); ?>Page/profileListEncoder">Student's Profile</a> </li>
                            <!-- <li><a href="<?= base_url(); ?>Masterlist/enrolledList">Enrollment</a></li> -->

                        </ul>
                    </li>


                    <li>
                        <a href="javascript: void(0);" class="waves-effect">
                            <i class=" ion ion-md-list-box "> </i>
                            <span> To Do </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li><a href="<?= base_url(); ?>ToDo/">ToDo</a></li>
                        </ul>
                    </li>

                    <li>
                        <a href="<?= base_url(); ?>Calendar/" target="_blank" class=" waves-effect">
                            <i class=" ion ion-md-calendar "> </i>
                            <span> Calendar </span>

                        </a>

                    </li>

                    <li>
                        <a href="<?= base_url(); ?>Note/" class=" waves-effect">
                            <i class=" ion ion-ios-keypad"> </i>
                            <span> Notes </span>

                        </a>

                    </li>

                </ul>

            </div>
            <!-- End Sidebar -->

        <?php elseif ($this->session->userdata('level') === 'School Admin'): ?>
            <div id="sidebar-menu">
                <ul class="metismenu" id="side-menu">

                    <li class="menu-title">ADMINISTRATION</li>

                    <li>
                        <a href="<?= base_url(); ?>Page/school_admin" class="waves-effect">
                            <i class="ion-md-speedometer"></i>
                            <span> Dashboard </span>
                        </a>
                    </li>

                    <li>
                        <a href="javascript: void(0);" class="waves-effect">
                            <i class="ion ion-md-list-box"></i>
                            <span> Masterlist </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li><a href="<?= base_url(); ?>Masterlist/dailyEnrollees">Enrollees By Date</a></li>
                            <!-- <li><a href="<?= base_url(); ?>Page/masterlistByCourseFiltered">By Course</a></li> -->

                            <!-- <li><a href="<?= base_url(); ?>Page/masterlistBySectionFiltered">By Section</a></li> -->
                            <li><a href="<?= base_url(); ?>Masterlist/bySY">By Semester</a></li>
                            <li><a href="<?= base_url(); ?>Masterlist/subregistration">By Subject</a></li>

                        </ul>
                    </li>
                    <li>
                        <a href="javascript: void(0);" class="waves-effect">
                            <i class="ion ion-md-list-box"></i>
                            <span> Registrar Reports </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <!-- <li><a href="<?= base_url(); ?>Masterlist/slotsMonitoring">Slots Monitoring</a></li> -->
                            <!-- <li><a href="<?= base_url(); ?>Masterlist/grades">Grading Sheets</a></li> -->
                            <!-- <li><a href="<?= base_url(); ?>Masterlist/gradesSummary">Grades Summary</a></li> -->
                            <li><a href="<?= base_url(); ?>Masterlist/crossEnrollees">Cross-Enrollees</a></li>
                            <li><a href="<?= base_url(); ?>Masterlist/fteRecords">Units Summary</a></li>
                            <li><a href="<?= base_url(); ?>Page/er" target="_blank">Enrollment Report</a></li>
                            <li><a href="<?= base_url(); ?>Page/pr" target="_blank">Promotional Report</a></li>

                        </ul>
                    </li>

                    <!-- <li>
                        <a href="javascript: void(0);" class="waves-effect">
                            <i class="ion ion-md-paper"></i>
                            <span> Accounting Reports </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="nav-second-level nav" aria-expanded="false">
                            <li><a href="<?= base_url(); ?>Accounting/collectionReport">Collection Reports</a></li>
                            <li><a href="<?= base_url(); ?>Accounting/collectionYear">Collection Per Year</a></li>

                            <li><a href="<?= base_url(); ?>Accounting/studeAccountsWithBalance">Students With Outstanding Balance</a></li>

                        </ul>
                    </li> -->


                    <li>
                        <a href="javascript: void(0);" class="waves-effect">
                            <i class="ion ion-md-settings"></i>
                            <span> User Settings </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li><a href="<?= base_url('Page/changepassword'); ?>">Change Password</a></li>
                            <li><a href="<?= base_url('Page/changeDP?id=' . urlencode($this->session->userdata('username'))); ?>">Change Profile Picture</a></li>
                        </ul>
                    </li>

                </ul>

            </div>
            <!-- End Sidebar -->

        <?php elseif ($this->session->userdata('level') === 'Head Registrar'): ?>
            <div id="sidebar-menu">
                <ul class="metismenu" id="side-menu">

                    <li class="menu-title">Navigation</li>

                    <li>
                        <a href="<?= base_url(); ?>Page/registrar" class="waves-effect">
                            <i class="ion-md-speedometer"></i>
                            <span> Dashboard </span>
                        </a>
                    </li>
                    <li>
                        <a href="javascript: void(0);" class="waves-effect">
                            <i class=" ion ion-md-school"></i>
                            <span> Admission </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li><a href="<?= base_url(); ?>Page/profileList">Student's Profile</a> </li>
                            <li><a href="<?= base_url(); ?>Page/signUpList">Signup List</a> </li>
                            <li><a href="<?= base_url(); ?>Masterlist/enrolledList">Enrollment</a></li>
                            <li><a href="<?= base_url(); ?>Ren/sub_enlist">Subject Enlistment</a></li>

                            <!-- <li><a href="<?= base_url(); ?>page/forValidation">Online Enrollees <small style="color:red;">[for action]</small></a></li> -->

                        </ul>
                    </li>
                    <li>
                        <a href="javascript: void(0);" class="waves-effect">
                            <i class="ion ion-md-list-box"></i>
                            <span> Masterlist </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li><a href="<?= base_url(); ?>Masterlist/dailyEnrollees">Enrollees By Date</a></li>
                            <!-- <li><a href="<?= base_url(); ?>Page/masterlistByCourseFiltered">By Course</a></li> -->

                            <!-- <li><a href="<?= base_url(); ?>Page/masterlistBySectionFiltered">By Section</a></li> -->
                            <li><a href="<?= base_url(); ?>Masterlist/bySY">By Semester</a></li>
                            <li><a href="<?= base_url(); ?>Masterlist/subregistration">By Subject</a></li>
                            <!-- <li><a href="<?= base_url(); ?>Page/deanList">Dean's Lister</a></li> -->
                            <!-- <li><a href="<?= base_url(); ?>Masterlist/byEnrolledOnlineSem?sy=<?php echo $this->session->userdata('sy'); ?>&sem=<?php echo $this->session->userdata('semester'); ?>">Online Enrollees (By Sem)</a></li> -->
                            <!-- <li><a href="<?= base_url(); ?>Masterlist/byEnrolledOnlineAll">Online Enrollees (All)</a></li> -->
                        </ul>
                    </li>
                    <li>
                        <a href="javascript: void(0);" class="waves-effect">
                            <i class="ion ion-md-list-box"></i>
                            <span> Reports </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <!-- <li><a href="<?= base_url(); ?>Masterlist/slotsMonitoring">Slots Monitoring</a></li> -->

                            <!-- <li><a href="<?= base_url(); ?>Masterlist/gradesSummary">Grades Summary</a></li> -->
                            <li><a href="<?= base_url(); ?>Masterlist/crossEnrollees">Cross-Enrollees</a></li>
                            <li><a href="<?= base_url(); ?>Masterlist/fteRecords">Units Summary</a></li>
                            <li><a href="<?= base_url(); ?>Page/er" target="_blank">Enrollment Report</a></li>
                            <li><a href="<?= base_url(); ?>Page/pr" target="_blank">Promotional Report</a></li>
                            <li>
                                <a href="<?= base_url(); ?>Records/index">
                                    Academic Credentials
                                </a>
                            </li>
                            <!-- <li><a href="<?= base_url(); ?>Page/evaluation" class="waves-effect">Evaluation</a></li> -->

                        </ul>
                    </li>
                    <!-- <li>
                        <a href="javascript: void(0);" class="waves-effect">
                            <i class="fas fa-envelope"></i>
                            <span> TESDA </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="nav-second-level nav" aria-expanded="false">
                            <li><a href="<?= base_url(); ?>Page/scholarship">Dashboard</a></li>
                        </ul>
                    </li> -->
                    <li>
                        <a href="javascript: void(0);" class="waves-effect">
                            <i class="fas fa-archive"></i>
                            <span> Document Request </span>
                            <span class="menu-arrow"></span>
                        </a>

                        <ul class="nav-second-level nav" aria-expanded="false">
                            <!-- <li><a href="<?= base_url(); ?>Page/newRequest">Add New</a></li> -->
                            <li><a href="<?= base_url(); ?>Page/allRequest">Requests</a></li>
                            <li><a href="<?= base_url(); ?>Page/requestSummary">Summary</a></li>
                        </ul>
                    </li>

                    <!-- <li>
  <a href="<?= base_url('records'); ?>">
    <i class="fe-file-text"></i> <span> Academic Credentials</span>
  </a>
</li> -->



                    <li>
                        <a href="javascript: void(0);" class="waves-effect">
                            <i class=" ion ion-md-settings"></i>
                            <span> Configurations </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li><a href="<?= base_url(); ?>Settings/Department">Courses</a></li>
                            <!-- <li><a href="<?= base_url(); ?>Settings/Sections">Sections</a></li> -->
                            <!-- <li><a href="<?= base_url() ?>Settings/subjects">Subjects</a></li> -->
                            <li><a href="<?= base_url() ?>Settings/classSched">Class Schedules</a></li>
                            <li><a href="<?= base_url() ?>GradesLock/index">Locking Grades Schedules</a></li>
                            <!-- <li><a href="<?= base_url() ?>Settings/subjects">Subjects</a></li> -->
                            <li><a href="<?= base_url(); ?>Settings/rooms">Rooms</a></li>
                            <li><a href="<?= base_url(); ?>Settings/ethnicity">Ethnicity</a></li>
                            <li><a href="<?= base_url(); ?>Settings/religion">Religion</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="<?= base_url(); ?>Page/announcement?id=<?php echo $this->session->userdata('username'); ?>" class="waves-effect">
                            <i class="bi bi-megaphone"></i>
                            <span> Announcement </span>
                        </a>
                    </li>
                    <li>
                        <a href="javascript: void(0);" class="waves-effect">
                            <i class="ion ion-md-paper"> </i>
                            <span> To Do </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li><a href="<?= base_url(); ?>ToDo/">ToDo</a></li>
                        </ul>
                    </li>


                    <!-- <li>
                        <a href="<?= base_url(); ?>Calendar/" target="_blank" class=" waves-effect">
                            <i class="ion ion-md-paper"> </i>
                            <span> Calendar </span>

                        </a>

                    </li> -->

                    <li>
                        <a href="<?= base_url(); ?>Note/" class=" waves-effect">
                            <i class="ion ion-md-paper"> </i>
                            <span> Notes </span>

                        </a>

                    </li>



                    <li>
                        <a href="<?= base_url(); ?>Page/changepassword" class="waves-effect">
                            <i class=" ion bi bi-shield-lock"></i>
                            <span> Change Password </span>
                        </a>
                    </li>

                </ul>

            </div>
            <!-- End Sidebar -->

        <?php elseif ($this->session->userdata('level') === 'Registrar'): ?>
            <div id="sidebar-menu">
                <ul class="metismenu" id="side-menu">

                    <li class="menu-title">Navigation</li>

                    <li>
                        <a href="<?= base_url(); ?>Page/registrar" class="waves-effect">
                            <i class="ion-md-speedometer"></i>
                            <span> Dashboard </span>
                        </a>
                    </li>
                    <li>
                        <a href="javascript: void(0);" class="waves-effect">
                            <i class=" ion ion-md-school"></i>
                            <span> Admission </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li><a href="<?= base_url(); ?>Page/signUpList">Signup List</a> </li>
                            <li><a href="<?= base_url(); ?>Page/profileList">Student's Profile</a> </li>
                            <li><a href="<?= base_url(); ?>Masterlist/enrolledList">Enrollment</a></li>
                            <li><a href="<?= base_url(); ?>Student/enlistment">Subject Enlistment</a></li>
                            <li><a href="<?= base_url(); ?>Student/requestSub">Subject Request</a></li>
                            <!-- <li><a href="<?= base_url(); ?>page/forValidation">Online Enrollees <small style="color:red;">[for action]</small></a></li> -->

                        </ul>
                    </li>

                    <li>
                        <a href="javascript: void(0);" class="waves-effect">
                            <i class=" ion ion-md-school"></i>
                            <span> Grades </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li><a href="<?= base_url(); ?>Masterlist/viewing_grades" title="Enter new grades for students">Encode Grades</a></li>
                            <!-- <li><a href="<?= base_url(); ?>Page/save_grades" title="Edit or adjust existing student grades">Manual Encoding Grades</a></li> -->

                            <li><a href="<?= base_url(); ?>Accreditation/index" title="Enter new grades for students">Encode Grades from Previous School</a></li>
                            <li><a href="<?= base_url(); ?>Masterlist/grades">Grading Sheets</a></li>
                            <li><a href="<?= base_url(); ?>ModifyGrades/index">Modify Grades</a> </li>
                            <li><a href="#">Calculate Average</a></li>

                        </ul>
                    </li>

                    <li>
                        <a href="javascript: void(0);" class="waves-effect">
                            <i class=" ion ion-md-school"></i>
                            <span> Requirements </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li><a href="<?= base_url(); ?>Student/req_list">Requirements List</a> </li>
                            <li><a href="<?= base_url(); ?>Student/pending_uploads">For Approval</a> </li>
                            <li><a href="<?= base_url(); ?>Student/approved_uploads">Approved Uploads</a></li>

                        </ul>
                    </li>

                    <li>
                        <a href="javascript: void(0);" class="waves-effect">
                            <i class="ion ion-md-list-box"></i>
                            <span> Masterlist </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li><a href="<?= base_url(); ?>Masterlist/dailyEnrollees">Enrollees By Date</a></li>
                            <li><a href="<?= base_url(); ?>Page/masterlistByCourse1">By Course</a></li>
                            <!-- <li><a href="<?= base_url(); ?>Page/masterlistBySectionFiltered">By Section</a></li> -->

                            <li><a href="<?= base_url(); ?>Masterlist/bySY">By Semester</a></li>
                            <li><a href="<?= base_url(); ?>Masterlist/subregistration">By Subject</a></li>
                            <li><a href="<?= base_url(); ?>Masterlist/bySYLMS">For LMS Bulk Upload</a></li>
                            <!-- <li><a href="<?= base_url(); ?>Page/deanList">Dean's Lister</a></li> -->
                            <!-- <li><a href="<?= base_url(); ?>Masterlist/byEnrolledOnlineSem?sy=<?php echo $this->session->userdata('sy'); ?>&sem=<?php echo $this->session->userdata('semester'); ?>">Online Enrollees (By Sem)</a></li> -->
                            <!-- <li><a href="<?= base_url(); ?>Masterlist/byEnrolledOnlineAll">Online Enrollees (All)</a></li> -->
                        </ul>
                    </li>
                    <li>
                        <a href="javascript: void(0);" class="waves-effect">
                            <i class="ion ion-md-list-box"></i>
                            <span> Reports </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <!-- <li><a href="<?= base_url(); ?>Masterlist/slotsMonitoring">Slots Monitoring</a></li> -->
                            <!-- <li><a href="<?= base_url(); ?>Masterlist/grades">Grading Sheets</a></li> -->

                            <li><a href="<?= base_url(); ?>Instructor/employeeList">Faculty Loading</a></li>
                            <li><a href="<?= base_url(); ?>Masterlist/crossEnrollees">Cross-Enrollees</a></li>
                            <li><a href="<?= base_url(); ?>Masterlist/fteRecords">Units Summary</a></li>
                            <li><a href="<?= base_url(); ?>Page/erform" target="_blank">Enrollment Report</a></li>
                            <li><a href="<?= base_url(); ?>Page/prform" target="_blank">Promotional Report</a></li>
                            <li><a href="<?= base_url(); ?>ReportGrades/index">Report of Grades</a></li>
                            <li>
                                <a href="<?= base_url(); ?>Records/index">
                                    Academic Credentials
                                </a>
                            </li>
                            <li><a href="<?= base_url(); ?>Student/evaluation" class="waves-effect">Evaluation</a></li>
                            <li><a href="<?= base_url(); ?>Reports/enrollment_summary">Enrollment Summary</a></li>
                        </ul>
                    </li>

                    <li>
                        <a href="javascript: void(0);" class="waves-effect">
                            <i class="mdi mdi-update"></i>
                            <span> Document Request </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li><a href="<?= base_url(); ?>Request/document_types">Docs for Request</a></li>
                            <li><a href="<?= base_url(); ?>Request/">Requests</a></li>
                            <!-- <li><a href="<?= base_url(); ?>Page/allRequest">Requests</a></li> -->
                            <!-- <li><a href="<?= base_url(); ?>Page/requestSummary">Summary</a></li> -->
                        </ul>
                    </li>

                    <li>
                        <a href="javascript: void(0);" class="waves-effect">
                            <i class=" ion ion-md-settings"></i>
                            <span> Configurations </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li><a href="<?= base_url(); ?>Settings/Department">Courses</a></li>
                            <!-- <li><a href="<?= base_url(); ?>Settings/Sections">Sections</a></li> -->
                            <!-- <li><a href="<?= base_url() ?>Settings/subjects">Subjects</a></li> -->
                            <li><a href="<?= base_url() ?>Settings/classSched">Class Schedules</a></li>
                            <li><a href="<?= base_url() ?>GradesLock/index">Locking Grades Schedules</a></li>

                            <li><a href="<?= base_url(); ?>Settings/rooms">Rooms</a></li>

                            <li><a href="<?= base_url(); ?>Settings/ethnicity">Ethnicity</a></li>
                            <li><a href="<?= base_url(); ?>Settings/religion">Religion</a></li>
                            <li><a href="<?= base_url(); ?>Settings/prevschool">Previous Schools</a></li>
                        </ul>
                    </li>

                    <li>
                        <a href="javascript: void(0);" class="waves-effect">
                            <i class="mdi mdi-update"></i>
                            <span> Flag Students </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li><a href="<?= base_url(); ?>Flag/">Flagged Students</a></li>

                        </ul>
                    </li>

                    <li>
                        <a href="javascript: void(0);" class="waves-effect">
                            <i class="mdi mdi-update"></i>
                            <span> Bulk Update </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li><a href="<?= base_url(); ?>Student/update_student_ages">Student Ages</a></li>

                        </ul>
                    </li>
                    <li>
                        <a href="<?= base_url(); ?>Page/announcement?id=<?php echo $this->session->userdata('username'); ?>" class="waves-effect">
                            <i class="bi bi-megaphone"></i>
                            <span> Announcement </span>
                        </a>
                    </li>
                    <!-- <li>
    <a href="<?= base_url('activities'); ?>" class="waves-effect">
        <i class="ion ion-ios-qr-scanner"></i>
        <span> Activities (QR) </span>
    </a>
</li> -->

                    <li>
                        <a href="javascript: void(0);" class="waves-effect">
                            <i class="ion ion-md-paper"> </i>
                            <span> To Do </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li><a href="<?= base_url(); ?>ToDo/">ToDo</a></li>
                        </ul>
                    </li>

                    <!-- 
                    <li>
                        <a href="<?= base_url(); ?>Calendar/" target="_blank" class=" waves-effect">
                            <i class="ion ion-md-paper"> </i>
                            <span> Calendar </span>

                        </a>

                    </li> -->

                    <li>
                        <a href="<?= base_url(); ?>Note/" class=" waves-effect">
                            <i class="ion ion-md-paper"> </i>
                            <span> Notes </span>

                        </a>

                    </li>


                    <li>
                        <a href="<?= base_url(); ?>Page/changepassword" class="waves-effect">
                            <i class=" ion bi bi-shield-lock"></i>
                            <span> Change Password </span>
                        </a>
                    </li>

                    <li>
                        <a href="<?= base_url(); ?>FAQ/" target="_blank" class="waves-effect">
                            <i class="ion ion-md-help"></i>
                            <span> SRMS FAQ </span>
                        </a>
                    </li>

                </ul>

            </div>
            <!-- End Sidebar -->

        <?php elseif ($this->session->userdata('level') === 'Accounting'): ?>
            <?php
            // Active/open helpers
            $uri = trim(uri_string(), '/');
            $is = function ($prefix) use ($uri) {
                return (strpos($uri, trim($prefix, '/')) === 0);
            };
            $active = function ($prefix) use ($is) {
                return $is($prefix) ? 'mm-active active' : '';
            };
            $open = function (array $prefixes) use ($is) {
                foreach ($prefixes as $p) {
                    if ($is($p)) return ['true', 'mm-show'];
                }
                return ['false', ''];
            };

            // Expand states per group
            list($paymentsExpanded, $paymentsShow) = $open([
                'Accounting/Payment',
                'Accounting/receipt',
                'Accounting/services',
                'Page/proof_payment_view',
                'Page/onlinePaymentsAll',
                'Page/deniedPayments',
                'Page/voidORs'
            ]);

            list($expensesExpanded, $expensesShow) = $open([
                'Accounting/expenses',
                'Accounting/expensescategory',
                'Accounting/expensesReport'
            ]);

            list($voidExpanded, $voidShow) = $open([
                'Accounting/VoidPayment'
            ]);

            list($docsExpanded, $docsShow) = $open([
                'Request/document_types',
                'Request'
            ]);

            list($configsExpanded, $configsShow) = $open([
                'Accounting/course_setUp'
            ]);

            list($acctRptExpanded, $acctRptShow) = $open([
                'Accounting/studeAccountsWithBalance',
                'Accounting/collectionMonthly',
                'Accounting/collectionYear',
                'Accounting/collectionDateRange'
            ]);

            list($todoExpanded, $todoShow) = $open([
                'ToDo'
            ]);

            // Online payments toggle (safe default = show)
            $showOnline = isset($online_settings->show_online_payments)
                ? (int)$online_settings->show_online_payments
                : 1;
            ?>
            <div id="sidebar-menu">
                <ul class="metismenu" id="side-menu">

                    <li class="menu-title">Navigation</li>

                    <li class="<?= $active('Page/accounting'); ?>">
                        <a href="<?= base_url('Page/accounting'); ?>" class="waves-effect">
                            <i class="ion bi bi-house-door"></i>
                            <span> Dashboard </span>
                        </a>
                    </li>

                    <li class="<?= $active('Accounting/studeAccounts'); ?>">
                        <a href="<?= base_url('Accounting/studeAccounts'); ?>" class="waves-effect">
                            <i class="ion ion-md-analytics"></i>
                            <span> Student's Accounts </span>
                        </a>
                    </li>

                    <!-- Payments -->
                    <li class="<?= $paymentsShow ? 'mm-active' : '' ?>">
                        <a href="javascript:void(0);" class="waves-effect" aria-expanded="<?= $paymentsExpanded; ?>">
                            <i class="ion ion-ios-cash"></i>
                            <span> Payments </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="nav-second-level nav <?= $paymentsShow; ?>" aria-expanded="<?= $paymentsExpanded; ?>">
                            <li class="<?= $active('Accounting/Payment'); ?>">
                                <a href="<?= base_url('Accounting/Payment'); ?>">School Fees</a>
                            </li>
                            <li class="<?= $active('Accounting/services'); ?>">
                                <a href="<?= base_url('Accounting/services'); ?>">Other Fees/Services</a>
                            </li>



                            <li class="<?= $active('Page/onlinePaymentsAll'); ?>">
                                <a href="<?= base_url('Page/onlinePaymentsAll'); ?>">Verified Online Payments</a>
                            </li>
                            <li class="<?= $active('Page/deniedPayments'); ?>">
                                <a href="<?= base_url('Page/deniedPayments'); ?>">Denied Online Payments</a>
                            </li>


                            <li class="<?= $active('Page/voidORs'); ?>">
                                <a href="<?= base_url('Page/voidORs'); ?>">Void O.R.</a>
                            </li>
                        </ul>
                    </li>

                    <!-- School Expenses -->
                    <li class="<?= $expensesShow ? 'mm-active' : '' ?>">
                        <a href="javascript:void(0);" class="waves-effect" aria-expanded="<?= $expensesExpanded; ?>">
                            <i class="ion ion-md-folder-open"></i>
                            <span> School Expenses </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="nav-second-level nav <?= $expensesShow; ?>" aria-expanded="<?= $expensesExpanded; ?>">
                            <li class="<?= $active('Accounting/expenses'); ?>">
                                <a href="<?= base_url('Accounting/expenses'); ?>">Expenses</a>
                            </li>
                            <li class="<?= $active('Accounting/expensescategory'); ?>">
                                <a href="<?= base_url('Accounting/expensescategory'); ?>">Expenses Category</a>
                            </li>
                            <li class="<?= $active('Accounting/expensesReport'); ?>">
                                <a href="<?= base_url('Accounting/expensesReport'); ?>">Expenses Reports</a>
                            </li>
                        </ul>
                    </li>

                    <!-- Void -->
                    <li class="<?= $voidShow ? 'mm-active' : '' ?>">
                        <a href="javascript:void(0);" class="waves-effect" aria-expanded="<?= $voidExpanded; ?>">
                            <i class="ion ion-md-paper"></i>
                            <span> Void </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="nav-second-level nav <?= $voidShow; ?>" aria-expanded="<?= $voidExpanded; ?>">
                            <li class="<?= $active('Accounting/VoidPayment'); ?>">
                                <a href="<?= base_url('Accounting/VoidPayment'); ?>">Void Receipts</a>
                            </li>
                        </ul>
                    </li>

                    <!-- Document Request -->
                    <li class="<?= $docsShow ? 'mm-active' : '' ?>">
                        <a href="javascript:void(0);" class="waves-effect" aria-expanded="<?= $docsExpanded; ?>">
                            <i class="mdi mdi-update"></i>
                            <span> Document Request </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="nav-second-level nav <?= $docsShow; ?>" aria-expanded="<?= $docsExpanded; ?>">
                            <li class="<?= $active('Request/document_types'); ?>">
                                <a href="<?= base_url('Request/document_types'); ?>">Docs for Request</a>
                            </li>
                            <li class="<?= $active('Request'); ?>">
                                <a href="<?= base_url('Request'); ?>">Requests</a>
                            </li>
                        </ul>
                    </li>

                    <!-- Configurations -->
                    <li class="<?= $configsShow ? 'mm-active' : '' ?>">
                        <a href="javascript:void(0);" class="waves-effect" aria-expanded="<?= $configsExpanded; ?>">
                            <i class="ion ion-md-settings"></i>
                            <span> Configurations </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="nav-second-level nav <?= $configsShow; ?>" aria-expanded="<?= $configsExpanded; ?>">
                            <li class="<?= $active('Accounting/course_setUp'); ?>">
                                <a href="<?= base_url('Accounting/course_setUp'); ?>">Fees Setup</a>
                            </li>
                        </ul>
                    </li>

                    <li class="menu-title">Reports</li>

                    <!-- Accounting Reports -->
                    <li class="<?= $acctRptShow ? 'mm-active' : '' ?>">
                        <a href="javascript:void(0);" class="waves-effect" aria-expanded="<?= $acctRptExpanded; ?>">
                            <i class="ion ion-md-paper"></i>
                            <span> Accounting Reports </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="nav-second-level nav <?= $acctRptShow; ?>" aria-expanded="<?= $acctRptExpanded; ?>">
                            <li>
                                <a href="<?= base_url('Accounting/studeAccountsWithBalance'); ?>">Students With Outstanding Balance</a>
                            </li>

                            <li>
                                <a href="<?= base_url('Accounting/fullyPaid'); ?>">Fully Paid Students</a>
                            </li>

                            <li>
                                <a href="javascript:void(0);" aria-expanded="<?= $acctRptExpanded; ?>">Collection Reports
                                    <span class="menu-arrow"></span>
                                </a>
                                <ul class="nav-third-level nav <?= $acctRptShow; ?>">
                                    <li>
                                        <a href="<?= base_url('Accounting/collectionMonthly'); ?>">Monthly</a>
                                    </li>
                                    <li>
                                        <a href="<?= base_url('Accounting/collectionYear'); ?>">Yearly</a>
                                    </li>
                                    <li>
                                        <a href="<?= base_url('Accounting/collectionDateRange'); ?>">By Date Range</a>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </li>

                    <!-- Students' Reports -->
                    <li class="<?= $active('Accounting/accountingStudeReports'); ?>">
                        <a href="<?= base_url('Accounting/accountingStudeReports'); ?>" class="waves-effect">
                            <i class="ion ion-md-folder"></i>
                            <span> Students' Reports </span>
                        </a>
                    </li>
                    <!-- <li class="<?= $active('activities'); ?>">
    <a href="<?= base_url('activities'); ?>" class="waves-effect">
        <i class="ion ion-ios-qr-scanner"></i>
        <span> Activities (QR) </span>
    </a>
</li> -->

                    To Do
                    <li class="<?= $todoShow ? 'mm-active' : '' ?>">
                        <a href="javascript:void(0);" class="waves-effect" aria-expanded="<?= $todoExpanded; ?>">
                            <i class="ion ion-md-paper"></i>
                            <span> To Do </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="nav-second-level nav <?= $todoShow; ?>" aria-expanded="<?= $todoExpanded; ?>">
                            <li class="<?= $active('ToDo'); ?>">
                                <a href="<?= base_url('ToDo'); ?>">ToDo</a>
                            </li>
                        </ul>
                    </li>

                    <li class="<?= $active('Note'); ?>">
                        <a href="<?= base_url('Note'); ?>" class="waves-effect">
                            <i class="ion ion-md-paper"></i>
                            <span> Notes </span>
                        </a>
                    </li>

                    <li class="<?= $active('Page/changepassword'); ?>">
                        <a href="<?= base_url('Page/changepassword'); ?>" class="waves-effect">
                            <i class=" ion bi bi-shield-lock"></i>
                            <span> Change Password </span>
                        </a>
                    </li>

                    <li>
                        <a href="<?= base_url('FAQ'); ?>" target="_blank" class="waves-effect">
                            <i class="ion ion-md-help"></i>
                            <span> SRMS FAQ </span>
                        </a>
                    </li>

                </ul>
            </div>

        <?php elseif (in_array($this->session->userdata('level'), ['Student', 'Stude Applicant'], true)): ?>
            <div id="sidebar-menu">
                <ul class="metismenu" id="side-menu">
                    <li class="menu-title">Navigation</li>

                    <li>
                        <a href="<?= base_url('Page/student'); ?>" class="waves-effect">
                            <i class="ion-md-speedometer"></i>
                            <span> Dashboard </span>
                        </a>
                    </li>



                    <li>
                        <a href="javascript: void(0);" class="waves-effect">
                            <i class="mdi mdi-account-circle-outline"></i>
                            <span> My Profile </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li><a href="<?= base_url('Page/studentProfile'); ?>">View/Edit Profile</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="<?= base_url('Page/studentAccountingRecords'); ?>" class="waves-effect">
                            <i class="ion ion-ios-cash"></i>
                            <span> My Payment Records </span>
                        </a>
                    </li>

                    <li>
                        <a href="<?= site_url('student/my_qr'); ?>" class="waves-effect" title="My QR Code">
                            <i class="bi bi-qr-code"></i>
                            <span> My QR Code </span>
                        </a>
                    </li>

                    <li>
                        <a href="javascript: void(0);" class="waves-effect">
                            <i class="ion ion-md-settings"></i>
                            <span> User Settings </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li><a href="<?= base_url('Page/changeDP?id=' . urlencode($this->session->userdata('username'))); ?>">Change Profile Picture</a></li>

                            <li><a href="<?= base_url('Page/changepassword'); ?>">Change Password</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="<?= base_url('FbmsoPersonnels'); ?>" class="waves-effect">
                            <i class="mdi mdi-account-group"></i>
                            <span> FBMSO Officials </span>
                        </a>
                    </li>

                    <li>
                        <a href="<?= base_url('Login/logout'); ?>" class="waves-effect logout-confirm">
                            <i class="ion bi bi-box-arrow-right"></i>
                            <span> Logout </span>
                        </a>
                    </li>
                </ul>
            </div>

        <?php else: ?>
            <div id="sidebar-menu">
                <ul class="metismenu" id="side-menu">
                    <li class="menu-title">Navigation</li>
                    <li>
                        <a href="<?= base_url('Page/admin'); ?>" class="waves-effect">
                            <i class="bi bi-house-door"></i>
                            <span> Dashboard </span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= base_url('Login/logout'); ?>" class="waves-effect logout-confirm">
                            <i class="ion bi bi-box-arrow-right"></i>
                            <span> Logout </span>
                        </a>
                    </li>
                </ul>
            </div>

        <?php endif; ?>



        <div class="clearfix"></div>

    </div>
    <!-- Sidebar -left -->

</div>

<script>
    (function() {
        function runAccordion() {
            var menu = document.getElementById('side-menu');
            if (!menu) {
                return;
            }

            function getDirectChildByTag(parent, tagName) {
                tagName = tagName.toUpperCase();
                for (var i = 0; i < parent.children.length; i++) {
                    if (parent.children[i].tagName === tagName) {
                        return parent.children[i];
                    }
                }
                return null;
            }

            function setExpandedState(li, expanded) {
                var toggle = getDirectChildByTag(li, 'A');
                if (toggle) {
                    toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                }
            }

            function collapseItem(li) {
                var submenu = getDirectChildByTag(li, 'UL');
                if (!submenu) {
                    return;
                }
                submenu.classList.remove('mm-show');
                submenu.style.display = 'none';
                submenu.style.height = '';
                submenu.style.overflow = '';
                li.classList.remove('mm-open');
                if (!li.hasAttribute('data-keep-active')) {
                    li.classList.remove('mm-active');
                }
                setExpandedState(li, false);
            }

            function expandItem(li) {
                var submenu = getDirectChildByTag(li, 'UL');
                if (!submenu) {
                    return;
                }
                submenu.classList.add('mm-show');
                submenu.style.display = 'block';
                submenu.style.height = '';
                submenu.style.overflow = '';
                li.classList.add('mm-open');
                li.classList.add('mm-active');
                setExpandedState(li, true);
            }

            function collapseSiblings(li) {
                var parent = li.parentNode;
                if (!parent) {
                    return;
                }
                for (var i = 0; i < parent.children.length; i++) {
                    var sibling = parent.children[i];
                    if (sibling === li || sibling.tagName !== 'LI') {
                        continue;
                    }
                    sibling.removeAttribute('data-keep-active');
                    collapseItem(sibling);
                }
            }

            function openParentChain(li) {
                var current = li.parentNode;
                while (current && current !== menu) {
                    if (current.tagName === 'UL') {
                        var parentLi = current.parentNode;
                        if (parentLi && parentLi.tagName === 'LI') {
                            expandItem(parentLi);
                            parentLi.setAttribute('data-keep-active', '1');
                        }
                    }
                    current = current.parentNode;
                }
            }

            var toggleTargets = [];
            var allLis = menu.getElementsByTagName('li');
            for (var idx = 0; idx < allLis.length; idx++) {
                var li = allLis[idx];
                var submenu = getDirectChildByTag(li, 'UL');
                var toggleLink = getDirectChildByTag(li, 'A');
                if (!submenu || !toggleLink) {
                    continue;
                }

                li.removeAttribute('data-keep-active');

                var record = {
                    li: li,
                    link: toggleLink,
                    submenu: submenu,
                    shouldOpen: li.classList.contains('mm-active') || li.classList.contains('mm-open') || submenu.classList.contains('mm-show')
                };

                toggleTargets.push(record);
            }

            for (var j = 0; j < toggleTargets.length; j++) {
                collapseItem(toggleTargets[j].li);
            }

            for (var k = 0; k < toggleTargets.length; k++) {
                if (toggleTargets[k].shouldOpen) {
                    expandItem(toggleTargets[k].li);
                    toggleTargets[k].li.setAttribute('data-keep-active', '1');
                    openParentChain(toggleTargets[k].li);
                }
            }

            for (var m = 0; m < toggleTargets.length; m++) {
                (function(item) {
                    if (item.link.getAttribute('data-toggle-bound') === '1') {
                        return;
                    }
                    item.link.setAttribute('data-toggle-bound', '1');

                    item.link.addEventListener('click', function(event) {
                        var submenu = getDirectChildByTag(item.li, 'UL');
                        if (!submenu) {
                            return;
                        }
                        event.preventDefault();

                        var isOpen = submenu.classList.contains('mm-show') || item.li.classList.contains('mm-open');
                        if (isOpen) {
                            item.li.removeAttribute('data-keep-active');
                            collapseItem(item.li);
                        } else {
                            collapseSiblings(item.li);
                            expandItem(item.li);
                            item.li.setAttribute('data-keep-active', '1');
                            openParentChain(item.li);
                        }
                    });
                })(toggleTargets[m]);
            }
        }

        function scheduleRun() {
            runAccordion();
            setTimeout(runAccordion, 50);
            setTimeout(runAccordion, 200);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', scheduleRun);
        } else {
            scheduleRun();
        }

        window.addEventListener('load', scheduleRun);
    })();
</script>

<script>
    (function() {
        if (window.__logoutConfirmInitialized) {
            return;
        }
        window.__logoutConfirmInitialized = true;

        function handleLogoutClick(event) {
            event.preventDefault();
            var targetUrl = this.getAttribute('href');
            var proceed = function() {
                window.location.href = targetUrl;
            };

            if (window.Swal && typeof window.Swal.fire === 'function') {
                window.Swal.fire({
                    title: 'Log out?',
                    text: 'You will be logged out of your account.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, log out',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#348cd4',
                    cancelButtonColor: '#6c757d'
                }).then(function(result) {
                    var confirmed = false;
                    if (result) {
                        if (typeof result.isConfirmed !== 'undefined') {
                            confirmed = result.isConfirmed;
                        } else if (typeof result.value !== 'undefined') {
                            confirmed = !!result.value;
                        } else if (result === true) {
                            confirmed = true;
                        }
                    }
                    if (confirmed) {
                        proceed();
                    }
                });
            } else if (window.confirm('Are you sure you want to log out?')) {
                proceed();
            }
        }

        function bindLogoutConfirm() {
            var links = document.querySelectorAll('.logout-confirm');
            if (!links.length) {
                return;
            }
            Array.prototype.forEach.call(links, function(link) {
                if (link.__logoutConfirmBound) {
                    return;
                }
                link.__logoutConfirmBound = true;
                link.addEventListener('click', handleLogoutClick);
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bindLogoutConfirm);
        } else {
            bindLogoutConfirm();
        }
    })();
</script>