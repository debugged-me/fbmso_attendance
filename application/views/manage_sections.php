<!DOCTYPE html>
<html lang="en">

<?php include('includes/head.php'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/uniform-page.css?v=20260831'); ?>">
<link href="<?= base_url(); ?>assets/libs/select2/select2.min.css" rel="stylesheet" type="text/css" />

<style>
  .pl-header {
    display:flex; align-items:flex-start; justify-content:space-between;
    gap:18px; flex-wrap:wrap; margin-bottom:16px;
  }
  .pl-header .page-title-box { flex:1 1 auto; margin:0; }
  .pl-header .page-title-box .up-divider { margin:10px 0 0; }
  .pl-header .pl-actions { flex:0 0 auto; align-self:center; display:flex; flex-wrap:wrap; gap:10px; }

  .resp-table thead th {
    background:#f5f7fc; color:#6b7a99; font-size:.72rem; font-weight:800;
    letter-spacing:.1em; text-transform:uppercase; border-bottom:1px solid #e6ebf5 !important;
    padding:14px 16px; white-space:nowrap; border-left:none; border-right:none;
  }
  .resp-table tbody td {
    padding:14px 16px; vertical-align:middle; font-size:.86rem; color:#0d1b4b;
    border-bottom:1px solid #eef1f5 !important; border-left:none; border-right:none;
  }
  .resp-table tbody tr:hover { background:#f8faff !important; }
  .resp-table tbody tr:last-child td { border-bottom:none !important; }

  .dataTables_wrapper .dataTables_info,
  .dataTables_wrapper .dataTables_paginate { padding:14px 18px !important; margin:0 !important; }
  .dataTables_wrapper .dataTables_filter,
  .dataTables_wrapper .dataTables_length { padding:16px 18px 12px !important; margin:0 !important; }
  .dataTables_wrapper .dataTables_filter input {
    border-radius:10px !important; border:1px solid #e6ebf5 !important;
    padding:8px 14px !important; font-size:.86rem !important; margin-left:6px !important;
  }
  .dataTables_wrapper .dataTables_length select {
    border-radius:10px !important; border:1px solid #e6ebf5 !important; padding:6px 10px !important; margin-left:6px !important;
  }
  .dataTables_paginate .paginate_button {
    border-radius:8px !important; min-width:38px; min-height:38px;
    display:inline-flex !important; align-items:center; justify-content:center;
  }
  .dataTables_paginate .paginate_button.current,
  .dataTables_paginate .paginate_button.current:hover {
    background:linear-gradient(135deg,#2a4090,#4266d4) !important; color:#fff !important;
    border-color:#2a4090 !important;
  }

  @media (max-width:767.98px) {
    .pl-header { flex-direction:column; gap:10px; }
    .pl-header .pl-actions { align-self:flex-start; }
  }
</style>

<body>

    <!-- Begin page -->
    <div id="wrapper">

        <!-- Topbar Start -->
        <?php include('includes/top-nav-bar.php'); ?>
        <!-- end Topbar -->

        <!-- Left Sidebar Start -->
        <?php include('includes/sidebar.php'); ?>
        <!-- Left Sidebar End -->

        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                    <?php
                    $flashMsgRaw = $this->session->flashdata('msg');
                    $flashSuccess = $this->session->flashdata('success');
                    $flashError = $this->session->flashdata('error');
                    $flashInfo = $this->session->flashdata('info');
                    $flashMsg = $flashMsgRaw ? strip_tags($flashMsgRaw) : null;
                    ?>

                    <!-- Title + actions on one row -->
                    <div class="pl-header">
                        <div class="page-title-box">
                            <h4 class="up-page-title">Manage Sections</h4>
                            <div class="up-page-sub">Create, edit, and delete course sections.</div>
                            <hr class="up-divider" />
                        </div>
                        <div class="pl-actions">
                            <a href="<?= base_url('Page/admin'); ?>" class="up-btn up-btn-ghost">
                                <i class="mdi mdi-arrow-left"></i> Back to Dashboard
                            </a>
                            <button type="button" class="up-btn up-btn-primary" data-toggle="modal" data-target="#addSectionModal">
                                <i class="mdi mdi-plus"></i> Add Section
                            </button>
                        </div>
                    </div>

                    <!-- start row -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="up-card">
                                <div class="up-card-head">
                                    <h4><i class="mdi mdi-format-list-bulleted"></i> Sections</h4>
                                    <span class="badge badge-purple" style="border-radius:999px;padding:5px 14px;font-size:.76rem;font-weight:700;">SY <?php echo $this->session->userdata('sy'); ?> <?php echo $this->session->userdata('semester'); ?></span>
                                </div>
                                <div class="up-card-body" style="padding:0 !important;">
                                    <div class="table-responsive">
                                        <table id="datatable" class="table table-bordered dt-responsive nowrap resp-table up-rt" style="width:100%;">
                                            <thead>
                                                <tr>
                                                    <th>Course</th>
                                                    <th>Year Level</th>
                                                    <th>Section</th>
                                                    <th style="text-align:center;">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($sections as $section): ?>
                                                    <?php
                                                    $courseCode = trim($section->CourseCode ?? '');
                                                    $courseDesc = trim($section->CourseDescription ?? '');
                                                    $courseLabel = $courseCode !== '' ? $courseCode : ($courseDesc !== '' ? $courseDesc : ($section->courseid ?? ''));
                                                    $courseExtra = ($courseDesc !== '' && strcasecmp($courseDesc, $courseLabel) !== 0) ? $courseDesc : '';
                                                    $sectionName = trim($section->section ?? '');
                                                    ?>
                                                    <tr>
                                                        <td data-label="Course">
                                                            <?= htmlspecialchars($courseLabel, ENT_QUOTES, 'UTF-8') ?>
                                                            <?php if ($courseExtra !== ''): ?>
                                                                <div class="text-muted small"><?= htmlspecialchars($courseExtra, ENT_QUOTES, 'UTF-8') ?></div>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td data-label="Year Level"><?= htmlspecialchars($section->year_level, ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td data-label="Section"><?= htmlspecialchars($sectionName, ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td data-label="Action" class="up-rt-actions" style="text-align:center;">
                                                            <a href="<?= base_url('Page/editSection/' . $section->id); ?>" class="up-btn up-btn-ghost up-btn-sm"><i class="mdi mdi-pencil"></i> Edit</a>
                                                            <a href="<?= base_url('Page/deleteSection/' . $section->id); ?>" class="up-btn up-btn-danger up-btn-sm section-delete-btn"
                                                                data-delete-url="<?= base_url('Page/deleteSection/' . $section->id); ?>"
                                                                data-section-name="<?= htmlspecialchars($sectionName !== '' ? $sectionName : $courseLabel, ENT_QUOTES, 'UTF-8'); ?>"><i class="mdi mdi-delete"></i> Delete</a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- end container-fluid -->
            </div>
        </div>

        <!-- Modal for Add Section -->
        <div class="modal fade" id="addSectionModal" tabindex="-1" aria-labelledby="addSectionModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addSectionModalLabel">Add Section</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="font-size:1.2rem;line-height:1;"><i class="mdi mdi-close"></i></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="<?= base_url('Page/addSection'); ?>">
                            <div class="form-group">
                                <label for="courseid">Course</label>
                                <select name="courseid" class="form-control" required>
                                    <option value="">Select Course</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?= $course->courseid ?>"><?= $course->CourseCode . ' - ' . $course->CourseDescription ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="year_level">Year Level</label>
                                <select name="year_level" class="form-control select2" required>
                                    <option value="">Select Year Level</option>
                                    <?php if (!empty($yearLevels)): ?>
                                        <?php foreach ($yearLevels as $yearLevel): ?>
                                            <option value="<?= $yearLevel->year_level ?>"><?= $yearLevel->year_level ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="section">Section</label>
                                <input type="text" name="section" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Add Section</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Start -->
        <?php include('includes/footer.php'); ?>
        <!-- end Footer -->

    </div>

    <!-- Vendor js -->
    <script src="<?= base_url(); ?>assets/js/vendor.min.js"></script>
    <script src="<?= base_url(); ?>assets/js/app.min.js"></script>

    <script src="<?= base_url(); ?>assets/libs/moment/moment.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/jquery-scrollto/jquery.scrollTo.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/sweetalert2/sweetalert2.min.js"></script>

    <script src="<?= base_url(); ?>assets/libs/datatables/jquery.dataTables.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.buttons.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/buttons.bootstrap4.min.js"></script>
    <script defer src="<?= base_url(); ?>assets/libs/jszip/jszip.min.js"></script>
    <script defer src="<?= base_url(); ?>assets/libs/pdfmake/pdfmake.min.js"></script>
    <script defer src="<?= base_url(); ?>assets/libs/pdfmake/vfs_fonts.js"></script>
    <script defer src="<?= base_url(); ?>assets/libs/datatables/buttons.html5.min.js"></script>
    <script defer src="<?= base_url(); ?>assets/libs/datatables/buttons.print.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>

    <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.responsive.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/responsive.bootstrap4.min.js"></script>

    <script src="<?= base_url(); ?>assets/js/pages/datatables.init.js"></script>

    <script>
        (function() {
            // Flash messages are shown by the shared toast bridge (includes/ui_kit.php).

            function closestByClass(element, className) {
                while (element && element !== document) {
                    if (element.classList && element.classList.contains(className)) {
                        return element;
                    }
                    element = element.parentNode;
                }
                return null;
            }

            document.addEventListener('click', function(event) {
                var trigger = event.target.closest ? event.target.closest('.section-delete-btn') : closestByClass(event.target, 'section-delete-btn');
                if (!trigger) {
                    return;
                }
                event.preventDefault();

                var deleteUrl = trigger.getAttribute('data-delete-url') || trigger.getAttribute('href');
                if (!deleteUrl) {
                    return;
                }
                var sectionName = trigger.getAttribute('data-section-name') || 'this section';
                var message = 'Delete ' + sectionName + '? This cannot be undone.';

                if (window.UI && typeof window.UI.fire === 'function') {
                    window.UI.fire({
                        title: 'Delete section?',
                        text: message,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, delete',
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: '#f1556c',
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
                            if (window.UI && UI.navBusy) UI.navBusy('Deleting ' + sectionName + '…');
                            window.location.href = deleteUrl;
                        }
                    });
                } else if (window.confirm(message)) {
                    window.location.href = deleteUrl;
                }
            });

            var addSectionForm = document.querySelector('#addSectionModal form');
            if (addSectionForm) {
                addSectionForm.addEventListener('submit', function(event) {
                    if (addSectionForm.__submitting) {
                        return;
                    }
                    event.preventDefault();

                    var proceed = function() {
                        addSectionForm.__submitting = true;
                        addSectionForm.submit();
                    };

                    var confirmOptions = {
                        title: 'Add section?',
                        text: 'Please confirm you want to save this section.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, save',
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: '#348cd4',
                        cancelButtonColor: '#6c757d'
                    };

                    if (window.UI && typeof window.UI.fire === 'function') {
                        window.UI.fire(confirmOptions).then(function(result) {
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
                                if (window.UI && UI.navBusy) UI.navBusy('Saving the section…');
                                proceed();
                            }
                        });
                    } else if (window.confirm(confirmOptions.text)) {
                        proceed();
                    }
                });
            }
        })();
    </script>

    <script>
        $(document).ready(function() {
            $('.select2').select2();
        });
    </script>

</body>

</html>