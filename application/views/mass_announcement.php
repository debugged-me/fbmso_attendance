<!DOCTYPE html>
<html lang="en">

<?php include('includes/head.php'); ?>
<link href="<?= base_url(); ?>assets/libs/summernote/summernote-bs4.css" rel="stylesheet" type="text/css" />
<link rel="stylesheet" href="<?= base_url('assets/css/uniform-page.css?v=2026092802'); ?>">

<style>
    .ma-stats { display:flex; gap:0; }
    .ma-stat { flex:1; padding:18px 22px; }
    .ma-stat + .ma-stat { border-left:1px solid var(--up-line,#e6ebf5); }
    .ma-stat-num { font-size:1.6rem; font-weight:800; color:var(--up-ink,#0d1b4b); line-height:1.1; }
    .ma-stat-label { font-size:.7rem; font-weight:800; letter-spacing:.14em; text-transform:uppercase; color:var(--up-muted,#6b7a99); margin-top:4px; }

    .ma-modal .modal-content{border:0;border-radius:18px;overflow:hidden;box-shadow:0 24px 60px rgba(13,27,75,.28)}
    .ma-modal .modal-header{background:linear-gradient(135deg,#1a2a6c,#2a4090);color:#fff;border-bottom:0;padding:18px 22px;align-items:center}
    .ma-modal .modal-title{font-weight:700;font-size:1.02rem;display:flex;align-items:center;gap:9px;color:#fff;margin:0}
    .ma-modal .modal-title .mdi{font-size:1.25rem;opacity:.92}
    .ma-modal .modal-header .close{color:#fff;opacity:.75;text-shadow:none;font-size:1.7rem;font-weight:300;padding:0;margin:0 0 0 auto;line-height:1}
    .ma-modal .modal-header .close:hover{opacity:1}
    .ma-modal .modal-body{padding:20px 24px}

    .announcement-history-mobile-item {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        border-radius: 10px;
    }

    .announcement-history-mobile-item .small.text-muted {
        font-size: 11px;
        letter-spacing: .3px;
        text-transform: uppercase;
    }

    .announcement-history-mobile-item .font-weight-semibold {
        font-weight: 600;
        word-break: break-word;
    }

    @media (max-width: 767.98px) {
        .up-card-body,
        .card-body {
            padding: 16px;
        }

        .ma-stats { flex-direction:column; }
        .ma-stat + .ma-stat { border-left:0; border-top:1px solid var(--up-line,#e6ebf5); }

        .header-title {
            font-size: 16px;
        }

        .btn-block {
            display: block;
            width: 100%;
        }

        #announcementHistoryCollapse .up-card-body {
            padding: 14px;
        }

        #announcementMessageModal .modal-dialog {
            margin: 10px;
        }

        #announcementMessageBody {
            font-size: 14px;
            line-height: 1.5;
            word-break: break-word;
        }

        .page-title-box {
            display: block !important;
        }

        .page-title-box .up-page-title {
            font-size: 20px;
        }

        .up-btn {
            width: 100%;
            justify-content: center;
            margin-top: 8px;
        }

        .form-group label {
            font-weight: 600;
            font-size: 13px;
        }

        .note-editor.note-frame .note-editing-area .note-editable {
            min-height: 180px;
        }
    }
</style>

<body>
    <?php
    $openPanel           = (string) ($open_panel ?? '');
    $openHistory         = ($openPanel === 'history');
    $currentTargetType   = (string) ($target_type ?? 'all');
    $massEmailSettings   = (array) ($email_settings ?? []);
    $emailReady          = !empty($email_ready);
    $currentSy           = trim((string) ($current_sy ?? ''));
    $currentSemester     = trim((string) ($current_semester ?? ''));
    $emailTransport      = strtoupper(str_replace('_', ' ', (string) ($massEmailSettings['transport'] ?? 'brevo_api')));

    if (!in_array($currentTargetType, ['all', 'year', 'section', 'individual'], true)) {
        $currentTargetType = 'all';
    }

    $selectedStudentOptionText = '';

    if (!empty($selected_student_option)) {
        $optionStudentNumber = trim((string) ($selected_student_option->StudentNumber ?? ''));
        $optionLastName      = trim((string) ($selected_student_option->LastName ?? ''));
        $optionFirstName     = trim((string) ($selected_student_option->FirstName ?? ''));
        $optionMiddleName    = trim((string) ($selected_student_option->MiddleName ?? ''));
        $optionYearLevel     = trim((string) ($selected_student_option->YearLevel ?? ''));
        $optionSection       = trim((string) ($selected_student_option->Section ?? ''));

        $optionName = trim($optionLastName . ', ' . $optionFirstName);

        if ($optionMiddleName !== '') {
            $optionName .= ' ' . strtoupper(substr($optionMiddleName, 0, 1)) . '.';
        }

        $optionName = trim($optionName, ', ');

        $optionParts = [];

        if ($optionStudentNumber !== '') {
            $optionParts[] = $optionStudentNumber;
        }

        if ($optionName !== '') {
            $optionParts[] = $optionName;
        }

        $optionMeta = trim($optionYearLevel . ($optionYearLevel !== '' && $optionSection !== '' ? ' - ' : '') . $optionSection);

        if ($optionMeta !== '') {
            $optionParts[] = $optionMeta;
        }

        $selectedStudentOptionText = implode(' - ', $optionParts);
    }
    ?>

    <div id="wrapper">
        <?php include('includes/top-nav-bar.php'); ?>
        <?php include('includes/sidebar.php'); ?>

        <div class="content-page">
            <div class="content">
                <div class="container-fluid">

                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-flex flex-wrap align-items-center justify-content-between">
                                <div>
                                    <h4 class="up-page-title"><i class="mdi mdi-bullhorn-outline"></i> Mass Announcement</h4>
                                    <div class="up-page-sub">Send an email announcement to enrolled students.</div>
                                    <hr class="up-divider" style="margin-bottom:0">
                                </div>

                                <?php if (!empty($can_manage_mass_email)): ?>
                                    <div class="up-header-actions">
                                        <a href="<?= site_url('Settings/schoolInfo?panel=mass_email'); ?>" class="up-btn up-btn-ghost">
                                            <i class="mdi mdi-email-cog-outline"></i> Email Setup
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <?php if ($this->session->flashdata('success')): ?>
                        <div class="up-flash up-flash-success" role="alert">
                            <?= $this->session->flashdata('success'); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <?php if ($this->session->flashdata('warning')): ?>
                        <div class="up-flash up-flash-info" role="alert">
                            <?= $this->session->flashdata('warning'); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <?php if ($this->session->flashdata('danger')): ?>
                        <div class="up-flash up-flash-danger" role="alert">
                            <?= $this->session->flashdata('danger'); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <div class="row mb-3">
                        <div class="col-lg-8">
                            <div class="up-card ma-stats mb-3 mb-lg-0">
                                <div class="ma-stat">
                                    <div class="ma-stat-num"><?= number_format((int) ($current_term_student_count ?? 0)); ?></div>
                                    <div class="ma-stat-label">Enrolled students</div>
                                </div>
                                <div class="ma-stat">
                                    <div class="ma-stat-num"><?= number_format((int) ($current_term_email_count ?? 0)); ?></div>
                                    <div class="ma-stat-label">Students with email</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4 d-flex flex-wrap justify-content-lg-end align-items-start">
                            <button
                                type="button"
                                class="up-btn up-btn-ghost"
                                data-toggle="collapse"
                                data-target="#announcementHistoryCollapse"
                                aria-expanded="<?= $openHistory ? 'true' : 'false'; ?>"
                                aria-controls="announcementHistoryCollapse">
                                <i class="mdi mdi-history"></i> View History
                            </button>
                        </div>
                    </div>

                    <div class="up-card">
                        <div class="up-card-head">
                            <h5><i class="mdi mdi-send-outline"></i> Send Announcement to Enrolled Students</h5>
                        </div>
                        <div class="up-card-body">
                            <?php if (!$emailReady): ?>
                                <div class="up-flash up-flash-info">
                                    <i class="mdi mdi-alert-outline"></i>
                                    Mass announcement sending is disabled until the mass email setup is completed.
                                </div>
                            <?php endif; ?>

                            <form method="post" action="<?= site_url('mass-announcement/send'); ?>">
                                <div class="form-row">
                                    <div class="form-group col-md-3">
                                        <label for="target_type">Send To</label>
                                        <select name="target_type" id="target_type" class="form-control">
                                            <option value="all" <?= $currentTargetType === 'all' ? 'selected' : ''; ?>>
                                                All Enrolled Students
                                            </option>
                                            <option value="year" <?= $currentTargetType === 'year' ? 'selected' : ''; ?>>
                                                By Year Level
                                            </option>
                                            <option value="section" <?= $currentTargetType === 'section' ? 'selected' : ''; ?>>
                                                By Section
                                            </option>
                                            <option value="individual" <?= $currentTargetType === 'individual' ? 'selected' : ''; ?>>
                                                Individual Student
                                            </option>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-9">
                                        <label for="subject">Subject Of Announcement</label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            id="subject"
                                            name="subject"
                                            value="<?= html_escape((string) ($subject ?? '')); ?>"
                                            required
                                            maxlength="255">
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-md-3" id="year-level-group">
                                        <label for="year_level">Year Level</label>
                                        <select name="year_level" id="year_level" class="form-control">
                                            <option value="">Select Year Level</option>
                                            <?php foreach ((array) $year_levels as $level): ?>
                                                <option
                                                    value="<?= html_escape($level); ?>"
                                                    <?= ((string) $selected_year_level === (string) $level) ? 'selected' : ''; ?>>
                                                    <?= html_escape($level); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-3" id="section-group">
                                        <label for="section">Section</label>
                                        <select
                                            name="section"
                                            id="section"
                                            class="form-control"
                                            data-selected="<?= html_escape((string) ($selected_section ?? '')); ?>">
                                            <option value="">Select Section</option>
                                            <?php foreach ((array) $sections as $sec): ?>
                                                <option
                                                    value="<?= html_escape($sec); ?>"
                                                    <?= ((string) ($selected_section ?? '') === (string) $sec) ? 'selected' : ''; ?>>
                                                    <?= html_escape($sec); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-6" id="student-group">
                                        <label for="student_number">Student (Search)</label>
                                        <select name="student_number" id="student_number" class="form-control">
                                            <?php if ((string) ($selected_student_number ?? '') !== ''): ?>
                                                <option value="<?= html_escape((string) $selected_student_number); ?>" selected>
                                                    <?= html_escape($selectedStudentOptionText !== '' ? $selectedStudentOptionText : (string) $selected_student_number); ?>
                                                </option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="mass-message">Message</label>
                                    <textarea id="mass-message" name="message" class="form-control" required><?= html_escape((string) ($message ?? '')); ?></textarea>
                                </div>

                                <button
                                    type="submit"
                                    class="up-btn up-btn-primary"
                                    <?= !$emailReady ? 'disabled' : ''; ?>
                                    data-ui-confirm="Emails go out immediately to everyone in the selected audience. There is no recall."
                                    data-ui-confirm-title="Send this announcement now?"
                                    data-ui-confirm-ok="Send it"
                                    data-ui-confirm-variant="primary"
                                    data-ui-confirm-icon="question">
                                    <i class="mdi mdi-send-outline"></i> Send Mass Announcement
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="collapse <?= $openHistory ? 'show' : ''; ?>" id="announcementHistoryCollapse">
                        <div class="up-card mt-3">
                            <div class="up-card-head">
                                <h5><i class="mdi mdi-history"></i> Announcement History</h5>
                            </div>
                            <div class="up-card-body">

                                <?php if (!empty($announcement_history)): ?>

                                    <div class="table-responsive d-none d-md-block">
                                        <table class="table table-bordered table-striped mb-0">
                                            <thead>
                                                <tr>
                                                    <th style="width: 180px;">Date</th>
                                                    <th>Subject</th>
                                                    <th style="width: 110px;">Recipients</th>
                                                    <th style="width: 180px;">Message</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($announcement_history as $row): ?>
                                                    <?php
                                                    $messageHtml    = (string) ($row->message ?? '');
                                                    $messageText    = trim(strip_tags($messageHtml));
                                                    $messagePreview = (strlen($messageText) > 70) ? (substr($messageText, 0, 67) . '...') : $messageText;
                                                    ?>
                                                    <tr>
                                                        <td><?= html_escape(date('M d, Y h:i A', strtotime((string) ($row->created_at ?? 'now')))); ?></td>
                                                        <td><?= html_escape((string) ($row->subject ?? '')); ?></td>
                                                        <td><?= (int) ($row->recipient_count ?? 0); ?></td>
                                                        <td>
                                                            <button
                                                                type="button"
                                                                class="up-btn up-btn-ghost view-ann-msg"
                                                                data-toggle="modal"
                                                                data-target="#announcementMessageModal"
                                                                data-subject="<?= html_escape((string) ($row->subject ?? '')); ?>"
                                                                data-message="<?= html_escape($messageHtml); ?>">
                                                                <i class="mdi mdi-eye-outline"></i> View
                                                            </button>

                                                            <div class="small text-muted mt-1">
                                                                <?= html_escape($messagePreview); ?>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="d-block d-md-none">
                                        <?php foreach ($announcement_history as $row): ?>
                                            <?php
                                            $messageHtml    = (string) ($row->message ?? '');
                                            $messageText    = trim(strip_tags($messageHtml));
                                            $messagePreview = (strlen($messageText) > 90) ? (substr($messageText, 0, 87) . '...') : $messageText;
                                            ?>
                                            <div class="announcement-history-mobile-item border rounded p-3 mb-3 bg-white">
                                                <div class="mb-2">
                                                    <div class="small text-muted">Date</div>
                                                    <div class="font-weight-semibold">
                                                        <?= html_escape(date('M d, Y h:i A', strtotime((string) ($row->created_at ?? 'now')))); ?>
                                                    </div>
                                                </div>

                                                <div class="mb-2">
                                                    <div class="small text-muted">Subject</div>
                                                    <div class="font-weight-semibold">
                                                        <?= html_escape((string) ($row->subject ?? '')); ?>
                                                    </div>
                                                </div>

                                                <div class="mb-2">
                                                    <div class="small text-muted">Recipients</div>
                                                    <div><?= (int) ($row->recipient_count ?? 0); ?></div>
                                                </div>

                                                <div class="mb-2">
                                                    <div class="small text-muted">Preview</div>
                                                    <div class="text-muted">
                                                        <?= html_escape($messagePreview); ?>
                                                    </div>
                                                </div>

                                                <div class="mt-3">
                                                    <button
                                                        type="button"
                                                        class="up-btn up-btn-ghost btn-block view-ann-msg"
                                                        data-toggle="modal"
                                                        data-target="#announcementMessageModal"
                                                        data-subject="<?= html_escape((string) ($row->subject ?? '')); ?>"
                                                        data-message="<?= html_escape($messageHtml); ?>">
                                                        View Full Message
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                <?php else: ?>
                                    <div class="text-center text-muted py-3">
                                        No sent announcements yet.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <?php include('includes/footer.php'); ?>
        </div>
    </div>

    <div class="modal fade ma-modal" id="announcementMessageModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="mdi mdi-bullhorn-outline"></i> <span id="announcementMessageTitle">Announcement Message</span></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="announcementMessageBody"></div>
            </div>
        </div>
    </div>

    <script src="<?= base_url(); ?>assets/js/vendor.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/select2/select2.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/summernote/summernote-bs4.min.js"></script>
    <script src="<?= base_url(); ?>assets/js/app.min.js"></script>

    <script>
        $(function() {
            var $targetType = $('#target_type');
            var $yearLevel = $('#year_level');
            var $section = $('#section');
            var $student = $('#student_number');
            var $yearGroup = $('#year-level-group');
            var $sectionGroup = $('#section-group');
            var $studentGroup = $('#student-group');

            var sectionUrl = '<?= site_url('mass-announcement/sections'); ?>';
            var studentsUrl = '<?= site_url('mass-announcement/students'); ?>';

            $section.select2({
                width: '100%',
                placeholder: 'Select section',
                allowClear: true
            });

            $student.select2({
                width: '100%',
                placeholder: 'Search student number or name',
                allowClear: true,
                minimumInputLength: 1,
                ajax: {
                    url: studentsUrl,
                    dataType: 'json',
                    delay: 200,
                    cache: true,
                    data: function(params) {
                        return {
                            q: params.term || '',
                            year_level: $yearLevel.val() || '',
                            section: $section.val() || ''
                        };
                    },
                    processResults: function(data) {
                        return data;
                    }
                }
            });

            function renderTargetMode() {
                var mode = ($targetType.val() || 'all').toLowerCase();
                var useYear = (mode === 'year' || mode === 'section');
                var useSection = (mode === 'section');
                var useStudent = (mode === 'individual');

                $yearGroup.toggle(useYear);
                $sectionGroup.toggle(useSection);
                $studentGroup.toggle(useStudent);

                $yearLevel.prop('disabled', !useYear).prop('required', useYear);
                $section.prop('disabled', !useSection).prop('required', useSection);
                $student.prop('disabled', !useStudent).prop('required', useStudent);

                if (!useYear) {
                    $yearLevel.val('');
                }

                if (!useSection) {
                    $section.val('').trigger('change');
                }

                if (!useStudent) {
                    $student.val(null).trigger('change');
                }
            }

            function reloadSections(keepSelected) {
                var selected = keepSelected ?
                    ($section.data('selected') || $section.val() || '') :
                    '';

                $.getJSON(sectionUrl, {
                        year_level: $yearLevel.val() || ''
                    })
                    .done(function(data) {
                        var items = (data && data.results) ? data.results : [];

                        $section.empty().append('<option value=""></option>');

                        $.each(items, function(_, item) {
                            if (!item || typeof item.id === 'undefined') {
                                return;
                            }

                            var option = new Option(item.text, item.id, false, false);
                            $section.append(option);
                        });

                        if (selected !== '') {
                            $section.val(selected);
                        } else {
                            $section.val('');
                        }

                        $section.trigger('change');
                        $section.data('selected', '');
                    })
                    .fail(function() {
                        $section.empty().append('<option value=""></option>').trigger('change');
                    });
            }

            $targetType.on('change', function() {
                renderTargetMode();

                if (($targetType.val() || '').toLowerCase() === 'section') {
                    reloadSections(true);
                }
            });

            $yearLevel.on('change', function() {
                if (($targetType.val() || '').toLowerCase() === 'section') {
                    reloadSections(false);
                }
            });

            renderTargetMode();

            if (($targetType.val() || '').toLowerCase() === 'section') {
                reloadSections(true);
            }

            $('#mass-message').summernote({
                height: 260,
                placeholder: 'Write your announcement message...',
                toolbar: [
                    ['style', ['bold', 'italic', 'underline', 'clear']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link']],
                    ['view', ['codeview']]
                ]
            });

            $(document).on('click', '.view-ann-msg', function() {
                var subject = $(this).data('subject') || 'Announcement Message';
                var message = $(this).data('message') || '';

                $('#announcementMessageTitle').text(subject);
                $('#announcementMessageBody').html(message);
            });
        });
    </script>
</body>

</html>