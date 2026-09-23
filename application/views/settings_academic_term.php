<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/uniform-page.css?v=20260831'); ?>">

<body>
    <div id="wrapper">
        <?php include('includes/top-nav-bar.php'); ?>
        <?php include('includes/sidebar.php'); ?>

        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                    <?php
                    $flashSuccess = $this->session->flashdata('success');
                    $flashWarning = $this->session->flashdata('warning');
                    $flashDanger  = $this->session->flashdata('danger');
                    $activeLabel  = trim((string)$active_sem . ' ' . (string)$active_sy);
                    ?>

                    <div class="page-title-box">
                        <h4 class="up-page-title">Academic Term</h4>
                    </div>

                    <?php if (!empty($flashSuccess)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= $flashSuccess; ?>
                            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($flashWarning)): ?>
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <?= $flashWarning; ?>
                            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($flashDanger)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= $flashDanger; ?>
                            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                        </div>
                    <?php endif; ?>

                    <!-- Active term banner -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="up-card mb-0" style="border-left:4px solid var(--up-blue);">
                                <div class="up-card-body py-3 d-flex flex-wrap align-items-center" style="gap:10px;">
                                    <i class="mdi mdi-calendar-check" style="font-size:1.6rem;color:var(--up-blue);"></i>
                                    <div>
                                        <div style="font-size:.78rem;text-transform:uppercase;letter-spacing:.05em;color:var(--up-muted);font-weight:700;">Active Term</div>
                                        <div style="font-size:1.15rem;font-weight:800;color:var(--up-ink);"><?= htmlspecialchars($activeLabel !== '' ? $activeLabel : 'Not set', ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div style="font-size:.78rem;color:var(--up-muted);margin-top:3px;">New enrolment and attendance records use this term. Earlier terms remain unchanged as history.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Known terms -->
                        <div class="col-lg-7 mb-3">
                            <div class="up-card mb-0 h-100">
                                <div class="up-card-head">
                                    <span><i class="mdi mdi-format-list-bulleted"></i> Terms in the enrolment data</span>
                                    <div class="pl-actions">
                                        <a href="<?= base_url('Settings/schoolInfo'); ?>" class="up-btn up-btn-ghost d-md-none">
                                            <i class="mdi mdi-arrow-left"></i> School Information
                                        </a>
                                    </div>
                                </div>
                                <div class="up-card-body" style="padding:0 !important;">
                                    <div class="table-responsive up-rt-host">
                                        <table class="table table-sm up-rt ms-rt-keep mb-0" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>School Year</th>
                                                    <th>Semester</th>
                                                    <th class="text-right">All Enrollees</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($terms)): ?>
                                                    <tr><td colspan="4" class="text-center text-muted py-4">No enrolment data yet.</td></tr>
                                                <?php endif; ?>
                                                <?php foreach ($terms as $t): ?>
                                                    <?php
                                                    $tSem = (string)$t->Semester;
                                                    $tSy  = (string)$t->SY;
                                                    $isActive = ($tSem === (string)$active_sem && $tSy === (string)$active_sy);
                                                    ?>
                                                    <tr>
                                                        <td data-label="School Year" style="font-weight:700;color:var(--up-ink);">
                                                            <?= htmlspecialchars($tSy, ENT_QUOTES, 'UTF-8'); ?>
                                                            <?php if ($isActive): ?>
                                                                <span class="badge badge-success ml-1">Active</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td data-label="Semester"><?= htmlspecialchars($tSem, ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td data-label="All Enrollees" class="text-right"><?= (int)$t->enrollees; ?></td>
                                                        <td data-label="Actions" class="up-rt-actions">
                                                            <div class="action-wrap">
                                                                <?php if (!$isActive): ?>
                                                                    <form method="post" action="<?= base_url('Settings/academicTerm'); ?>"
                                                                        data-ui-confirm="This switches the active term for every user and opens ledger accounts for the term's enrollees."
                                                                        data-ui-confirm-title="Activate <?= htmlspecialchars($tSem . ' ' . $tSy, ENT_QUOTES, 'UTF-8'); ?>?"
                                                                        data-ui-confirm-ok="Activate">
                                                                        <input type="hidden" name="action" value="activate">
                                                                        <input type="hidden" name="sem" value="<?= htmlspecialchars($tSem, ENT_QUOTES, 'UTF-8'); ?>">
                                                                        <input type="hidden" name="sy" value="<?= htmlspecialchars($tSy, ENT_QUOTES, 'UTF-8'); ?>">
                                                                        <button type="submit" class="up-btn up-btn-primary" style="padding:7px 14px;font-size:.78rem;">
                                                                            <i class="mdi mdi-check-circle"></i> Activate
                                                                        </button>
                                                                    </form>
                                                                <?php endif; ?>
                                                                <form method="post" action="<?= base_url('Settings/academicTerm'); ?>"
                                                                    data-ui-confirm="Ledger accounts will be opened for all enrolled students in this term who do not have one yet."
                                                                    data-ui-confirm-title="Open missing accounts for <?= htmlspecialchars($tSem . ' ' . $tSy, ENT_QUOTES, 'UTF-8'); ?>?"
                                                                    data-ui-confirm-ok="Open accounts">
                                                                    <input type="hidden" name="action" value="provision">
                                                                    <input type="hidden" name="sem" value="<?= htmlspecialchars($tSem, ENT_QUOTES, 'UTF-8'); ?>">
                                                                    <input type="hidden" name="sy" value="<?= htmlspecialchars($tSy, ENT_QUOTES, 'UTF-8'); ?>">
                                                                    <button type="submit" class="up-btn up-btn-ghost" style="padding:7px 14px;font-size:.78rem;">
                                                                        <i class="mdi mdi-account-plus"></i> Open Accounts
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Activate a new / arbitrary term -->
                        <div class="col-lg-5 mb-3">
                            <div class="up-card mb-0 h-100">
                                <div class="up-card-head">
                                    <i class="mdi mdi-calendar-plus"></i> Activate a term
                                </div>
                                <div class="up-card-body">
                                    <form method="post" action="<?= base_url('Settings/academicTerm'); ?>"
                                        data-ui-confirm="This switches the active term for every user and opens ledger accounts for its enrollees."
                                        data-ui-confirm-title="Activate this term?"
                                        data-ui-confirm-ok="Activate">
                                        <input type="hidden" name="action" value="activate">
                                        <div class="form-group">
                                            <label for="termSem">Semester</label>
                                            <select class="form-control" id="termSem" name="sem" required>
                                                <?php foreach ($semesters as $s): ?>
                                                    <option value="<?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); ?>" <?= $s === (string)$active_sem ? 'selected' : ''; ?>><?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="termSy">School Year</label>
                                            <select class="form-control" id="termSy" name="sy" required>
                                                <?php foreach ($school_years as $y): ?>
                                                    <option value="<?= htmlspecialchars($y, ENT_QUOTES, 'UTF-8'); ?>" <?= $y === (string)$active_sy ? 'selected' : ''; ?>><?= htmlspecialchars($y, ENT_QUOTES, 'UTF-8'); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <button type="submit" class="up-btn up-btn-primary btn-block">
                                            <i class="mdi mdi-check-circle"></i> Activate Term
                                        </button>
                                        <small class="text-muted d-block mt-2">
                                            Activating a term applies to all users and opens a ledger account for every student already enrolled in it. Students enrolled later are added to the same term automatically.
                                        </small>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <?php include('includes/footer.php'); ?>
        </div>
    </div>

    <?php include('includes/footer_plugins.php'); ?>
    <script src="<?= base_url(); ?>assets/js/app.min.js"></script>
</body>
</html>
