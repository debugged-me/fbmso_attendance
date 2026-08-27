<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Attendance MS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta content="Responsive bootstrap 4 admin template" name="description" />
    <meta content="Coderthemes" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="shortcut icon" href="<?= base_url(); ?>assets/images/Attendance.png">

    <!-- Plugins css-->
    <link href="<?= base_url(); ?>assets/libs/sweetalert2/sweetalert2.min.css" rel="stylesheet" type="text/css" />
    <link href="<?= base_url(); ?>assets/libs/select2/select2.min.css" rel="stylesheet" type="text/css" />

    <!-- App css -->
    <link href="<?= base_url(); ?>assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" id="bootstrap-stylesheet" />
    <link href="<?= base_url(); ?>assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="<?= base_url(); ?>assets/css/app.min.css" rel="stylesheet" type="text/css" id="app-stylesheet" />
    <link rel="stylesheet" href="<?= base_url('assets/css/uniform-page.css?v=30260827'); ?>">

    <script src="<?= base_url(); ?>assets/js/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="<?= base_url('assets/css/mobile-shell.css?v=6'); ?>">
    <meta name="theme-color" content="#1a2942">
    <link rel="manifest" href="<?= base_url('manifest.webmanifest?v=3'); ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="<?= base_url('assets/images/icons/attendance-192.png'); ?>">
    <script src="<?= base_url('assets/js/mobile-shell-early.js?v=5'); ?>"></script>

    <style>
        .card-simple {border:1px solid #e6ecf5;border-radius:14px;box-shadow:0 6px 18px rgba(36,59,83,.06)}
        .section-title{font-weight:800;color:var(--up-muted,#6b7a99);font-size:.68rem;letter-spacing:.18em;text-transform:uppercase;margin:8px 0 14px;display:flex;align-items:center;gap:10px}
        .section-title::before{content:'';width:8px;height:8px;border-radius:50%;background:linear-gradient(135deg,var(--up-blue,#2a4090),var(--up-blue-2,#4266d4));flex-shrink:0}
        .label-req::after{content:" *"; color:#e55353}

        /* CSS grid for tidy alignment */
        .form-grid{
            display:grid;
            grid-template-columns:repeat(4, minmax(0, 1fr));
            grid-column-gap:16px;
            grid-row-gap:14px;
        }
        .span-2{grid-column:span 2}
        .span-3{grid-column:span 3}
        .span-4{grid-column:span 4}
        @media (max-width:1199.98px){ .form-grid{grid-template-columns:repeat(3,1fr)} }
        @media (max-width:991.98px){ .form-grid{grid-template-columns:repeat(2,1fr)} }
        @media (max-width:575.98px){ .form-grid{grid-template-columns:1fr} .span-2,.span-3,.span-4{grid-column:span 1} }

        /* Select2 height match */
        .select2-container .select2-selection--single{height:38px}
        .select2-selection__rendered{line-height:36px}
        .select2-selection__arrow{height:36px}

        .profile-fieldset[disabled] .form-control,
        .profile-fieldset[disabled] .form-control:focus {
            background-color:#f8f9fa;
            color:#243b53;
            opacity:1;
        }
        .profile-fieldset[disabled] select{
            pointer-events:none;
        }
        .profile-readonly-note{
            background:#f1f5f9;
            border-left:4px solid #348cd4;
            color:#1b2a4e;
        }
        .availability-msg {
            display: block;
            min-height: 18px;
            margin-top: 6px;
            font-size: .72rem;
            font-weight: 600;
            line-height: 1.3;
            color: #7288b7;
        }
        .availability-msg.is-ok   { color: #1e8449; }
        .availability-msg.is-bad  { color: #c0392b; }
        .availability-msg.is-muted{ color: #7288b7; }
    </style>

    <script>
        function calculateAge(dateInputId, resultInputId) {
            const v = document.getElementById(dateInputId).value;
            if (!v) { document.getElementById(resultInputId).value=''; return; }
            const b = new Date(v), n = new Date();
            let age = n.getFullYear()-b.getFullYear();
            const m = n.getMonth()-b.getMonth();
            if (m<0 || (m===0 && n.getDate()<b.getDate())) age--;
            document.getElementById(resultInputId).value = isFinite(age)?age:'';
        }
    </script>
</head>
<body>
<?php
$profileData = $data ?? null;
if (is_array($profileData)) {
    $profileData = $profileData[0] ?? null;
}
if (is_array($profileData)) {
    $profileData = (object)$profileData;
}
if (!is_object($profileData)) {
    $profileData = (object)[];
}
$data = $profileData;

$readOnly = !empty($readOnly);
$provinces = (isset($provinces) && is_array($provinces)) ? $provinces : [];
$cities    = (isset($cities) && is_array($cities)) ? $cities : [];
$barangays = (isset($barangays) && is_array($barangays)) ? $barangays : [];
if (empty($barangays) && isset($brgy) && is_array($brgy)) {
    $barangays = $brgy;
}

$pickField = function ($keys) use ($data) {
    foreach ($keys as $key) {
        if (isset($data->$key) && trim((string)$data->$key) !== '') {
            return trim((string)$data->$key);
        }
    }
    return '';
};

$provinceVal = $pickField(['Province', 'province', 'provincePresent']);
$cityVal     = $pickField(['City', 'city', 'CityPresent', 'cityPresent']);
$brgyVal     = $pickField(['Brgy', 'brgy', 'Barangay', 'barangay', 'BrgyPresent', 'brgyPresent']);
$sitioVal    = $pickField(['Sitio', 'sitio', 'SitioPresent', 'sitioPresent']);

// Safe field accessors — prevents PHP warnings when a property is missing
// (e.g. when the record comes from studentsignup instead of studeprofile)
$snVal         = $pickField(['StudentNumber', 'studentnumber']);
$firstNameVal  = $pickField(['FirstName', 'firstname']);
$middleNameVal = $pickField(['MiddleName', 'middlename']);
$lastNameVal   = $pickField(['LastName', 'lastname']);
$nameExtnVal   = $pickField(['nameExtn', 'NameExtn']);
$sexVal        = $pickField(['Sex', 'sex']);
$civilVal      = $pickField(['CivilStatus', 'civilstatus']);
$contactVal    = $pickField(['contactNo', 'ContactNo']);
$birthDateVal  = $pickField(['birthDate', 'BirthDate']);
$ageVal        = $pickField(['Age', 'age']);
?>
<div id="wrapper">
    <?php include('includes/top-nav-bar.php'); ?>
    <?php include('includes/sidebar.php'); ?>

    <div class="content-page">
        <div class="content">

            <?php
                $flashSuccess = $this->session->flashdata('success');
                $flashDanger  = $this->session->flashdata('danger');
                $flashMessage = $this->session->flashdata('message');
            ?>

            <?php if ($flashSuccess): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            <?php if ($flashDanger): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <?= htmlspecialchars($flashDanger, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            <?php if ($flashMessage): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <?= htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <div class="container-fluid">
                <!-- title -->
                <div class="row">

                    <div class="col-md-12">
                        <div class="page-title-box">

                            <h4 class="up-page-title"><?= $readOnly ? 'VIEW PROFILE' : 'UPDATE PROFILE'; ?></h4>
                            <div class="up-page-sub"><?= $readOnly ? 'Viewing student details (read-only).' : 'Update student personal and academic information.'; ?></div>
                            <hr class="up-divider" />
                               <a href="<?= base_url('Page/profileList'); ?>" class="up-btn up-btn-ghost"><i class="mdi mdi-arrow-left"></i> Back</a>
                        </div>


                    </div>
                </div>

                <!-- form -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="up-card">
                            <div class="up-card-body">
                                <form class="parsley-examples" method="post" enctype="multipart/form-data"
                                    data-check-availability-url="<?= htmlspecialchars(site_url('Page/checkSignupAvailability'), ENT_QUOTES, 'UTF-8'); ?>"
                                    data-exclude-student-number="<?= htmlspecialchars($snVal, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php if ($readOnly): ?>
                                        <div class="alert profile-readonly-note py-2 px-3 mb-3">
                                            Viewing student details only. Editing is disabled for administrators.
                                        </div>
                                    <?php endif; ?>
                                    <fieldset class="profile-fieldset" <?= $readOnly ? 'disabled' : ''; ?>>
                                        <h5 class="section-title">Personal Data</h5>

                                        <div class="form-grid">
                                          <!-- Student No -->
                                          <div class="form-group span-2">
                                              <input type="hidden" value="<?= htmlspecialchars($snVal, ENT_QUOTES, 'UTF-8'); ?>" name="oldStudentNo" required>
                                              <label class="label-req">Student No.</label>
                                              <input type="text" class="form-control" value="<?= htmlspecialchars($snVal, ENT_QUOTES, 'UTF-8'); ?>" name="StudentNumber" id="StudentNumber" <?= $readOnly ? 'readonly' : ''; ?> required>
                                              <?php if (!$readOnly): ?>
                                                <span class="availability-msg" id="student-number-status" aria-live="polite"></span>
                                              <?php endif; ?>
                                          </div>

                                          <!-- Names -->
                                          <div class="form-group">
                                              <label class="label-req">First Name</label>
                                              <input type="text" class="form-control" name="FirstName" value="<?= htmlspecialchars($firstNameVal, ENT_QUOTES, 'UTF-8'); ?>" required>
                                          </div>
                                          <div class="form-group">
                                              <label>Middle Name</label>
                                              <input type="text" class="form-control" name="MiddleName" value="<?= htmlspecialchars($middleNameVal, ENT_QUOTES, 'UTF-8'); ?>">
                                          </div>
                                          <div class="form-group">
                                              <label class="label-req">Last Name</label>
                                              <input type="text" class="form-control" name="LastName" value="<?= htmlspecialchars($lastNameVal, ENT_QUOTES, 'UTF-8'); ?>" required>
                                          </div>
                                          <div class="form-group">
                                              <label>Name Extn</label>
                                              <input type="text" class="form-control" name="nameExtn" value="<?= htmlspecialchars($nameExtnVal, ENT_QUOTES, 'UTF-8'); ?>">
                                          </div>

                                          <!-- Sex / Civil / Mobile -->
                                          <div class="form-group">
                                              <label class="label-req">Sex</label>
                                              <select name="Sex" class="form-control" required>
                                                  <option value=""></option>
                                                  <option value="Female" <?= ($sexVal == 'Female') ? 'selected' : ''; ?>>Female</option>
                                                  <option value="Male"   <?= ($sexVal == 'Male') ? 'selected' : ''; ?>>Male</option>
                                              </select>
                                          </div>
                                          <div class="form-group">
                                              <label class="label-req">Civil Status</label>
                                              <select name="CivilStatus" class="form-control" required>
                                                  <option value=""></option>
                                                  <option value="Single"  <?= ($civilVal == 'Single') ? 'selected' : ''; ?>>Single</option>
                                                  <option value="Married" <?= ($civilVal == 'Married') ? 'selected' : ''; ?>>Married</option>
                                              </select>
                                          </div>
                                          <div class="form-group span-2">
                                              <label>Mobile No.</label>
    <input type="text" class="form-control" name="contactNo" value="<?= htmlspecialchars($contactVal, ENT_QUOTES, 'UTF-8'); ?>">
                                          </div>

                                     <!-- Birth Date / Age -->
    <div class="form-group">
        <label class="label-req">Birth Date</label>
        <input type="date" name="birthDate" id="bday" class="form-control"
        onchange="calculateAge('bday','resultBday')" required value="<?= htmlspecialchars($birthDateVal, ENT_QUOTES, 'UTF-8'); ?>">
    </div>
    <div class="form-group">
        <label class="label-req">Age</label>
        <input type="text" name="Age" id="resultBday" class="form-control" readonly required value="<?= htmlspecialchars($ageVal, ENT_QUOTES, 'UTF-8'); ?>">
    </div>


                                          <!-- Address Fields -->
                                          <div class="span-4"><h6 class="section-title">Address</h6></div>
    <div class="form-group">
        <label class="label-req" for="province">Province</label>
        <select id="province" name="Province" class="form-control" required>
            <option value="">Select Province</option>
            <?php foreach ($provinces as $province): ?>
                <option value="<?= htmlspecialchars($province->Province, ENT_QUOTES, 'UTF-8'); ?>" <?= ($province->Province == $provinceVal) ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($province->Province, ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label class="label-req" for="city">City/Municipality</label>
        <select id="city" name="City" class="form-control" required>
            <option value="">Select City/Municipality</option>
            <?php foreach ($cities as $city): ?>
                <option value="<?= htmlspecialchars($city->City, ENT_QUOTES, 'UTF-8'); ?>" <?= ($city->City == $cityVal) ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($city->City, ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label class="label-req" for="barangay">Barangay</label>
        <select id="barangay" name="Brgy" class="form-control" required>
            <option value="">Select Barangay</option>
        <?php foreach ($barangays as $barangay): ?>
            <option value="<?= htmlspecialchars($barangay->Brgy, ENT_QUOTES, 'UTF-8'); ?>" <?= ($barangay->Brgy == $brgyVal) ? 'selected' : ''; ?>>
                <?= htmlspecialchars($barangay->Brgy, ENT_QUOTES, 'UTF-8'); ?>
            </option>
        <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="sitio">Sitio</label>
        <input type="text" id="sitio" class="form-control" name="Sitio" placeholder="Sitio" value="<?= htmlspecialchars($sitioVal, ENT_QUOTES, 'UTF-8'); ?>">
    </div>


                                        </div><!-- /.form-grid -->

                                        <input type="hidden" name="StudentNumber_original" value="<?= htmlspecialchars($snVal, ENT_QUOTES, 'UTF-8'); ?>">
                                    </fieldset>
                                    <?php if (!$readOnly): ?>
                                    <div class="mt-3">
                                        <button type="submit" name="submit" class="up-btn up-btn-primary"><i class="mdi mdi-content-save"></i> Update Profile</button>
                                    </div>
                                    <?php endif; ?>
                                </form>
                            </div><!-- /.card-body -->
                        </div><!-- /.card -->
                    </div>
                </div>
            </div><!-- /.container-fluid -->

        </div><!-- /.content -->

        <?php include('includes/footer.php'); ?>
    </div><!-- /.content-page -->

    <?php include('includes/themecustomizer.php'); ?>
</div><!-- /#wrapper -->

<script src="<?= base_url(); ?>assets/js/vendor.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/moment/moment.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/jquery-scrollto/jquery.scrollTo.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/sweetalert2/sweetalert2.min.js"></script>
<script src="<?= base_url(); ?>assets/js/app.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/select2/select2.min.js"></script>

<!-- Province → City → Barangay chaining -->
<script>
$(function () {
    var isReadOnly = <?= $readOnly ? 'true' : 'false'; ?>;
    if (isReadOnly) {
        $('#province, #city, #barangay').prop('disabled', true);
        return;
    }

    $('#province, #city, #barangay').select2({ width: '100%' });

    function notifyError(message) {
        if (window.UI && typeof window.UI.fire === 'function') {
            window.UI.fire({
                icon: 'error',
                title: 'Error',
                text: message,
                confirmButtonColor: '#348cd4'
            });
        } else {
            window.alert(message);
        }
    }

   const savedProvince = <?= json_encode($provinceVal) ?>;
const savedCity     = <?= json_encode($cityVal) ?>;
const savedBrgy     = <?= json_encode($brgyVal) ?>;

function enable(el, on = true) { $(el).prop('disabled', !on); }

function setSelectValue($el, val, label) {
    if (!val) return;
    if ($el.find('option').filter((_, o) => $(o).val() == val).length === 0) {
        $el.append($('<option/>', { value: val, text: label || val }));
    }
    $el.val(val).trigger('change.select2');
}

function fillOptions($el, items, valueKey, textKey, placeholder) {
    $el.empty();
    if (placeholder) $el.append($('<option/>', { value: '', text: placeholder }));
    (items || []).forEach(function (it) {
        const v = it[valueKey] ?? '';
        const t = it[textKey] ?? v;
        if (v) $el.append($('<option/>', { value: v, text: t }));
    });
    $el.trigger('change.select2');
}

function loadProvinces() {
    $.ajax({
        url: '<?= site_url('Page/get_provinces'); ?>',
        type: 'GET',
        dataType: 'json',
        success: function (data) {
            const $prov = $('#province');
            fillOptions($prov, data, 'Province', 'Province', 'Select Province');

            if (savedProvince) {
                setSelectValue($prov, savedProvince);
                enable('#city', true);
                loadCities(savedProvince, function () {
                    if (savedCity) {
                        setSelectValue($('#city'), savedCity);
                        enable('#barangay', true);
                        loadBarangays(savedCity, function () {
                            if (savedBrgy) setSelectValue($('#barangay'), savedBrgy);
                        });
                    }
                });
            } else {
                enable('#city', false);
                enable('#barangay', false);
            }
        },
        error: function (_x, _s, err) { notifyError("Error loading provinces: " + err); }
    });
}

function loadCities(province, done) {
    $.ajax({
        url: '<?= site_url('Page/get_cities'); ?>',
        type: 'POST',
        dataType: 'json',
        data: { province: province },
        success: function (data) {
            const $city = $('#city');
            fillOptions($city, data, 'City', 'City', 'Select City/Municipality');
            if (typeof done === 'function') done();
        },
        error: function (_x, _s, err) { notifyError("Error loading cities: " + err); }
    });
}

function loadBarangays(city, done) {
    $.ajax({
        url: '<?= site_url('Page/get_barangays'); ?>',
        type: 'POST',
        dataType: 'json',
        data: { city: city },
        success: function (data) {
            const $brgy = $('#barangay');
            fillOptions($brgy, data, 'Brgy', 'Brgy', 'Select Barangay');
            if (typeof done === 'function') done();
        },
        error: function (_x, _s, err) { notifyError("Error loading barangays: " + err); }
    });
}

$('#province').on('change', function () {
    const prov = $(this).val();
    fillOptions($('#city'), [], 'City', 'City', 'Select City/Municipality');
    fillOptions($('#barangay'), [], 'Brgy', 'Brgy', 'Select Barangay');
    enable('#barangay', false);

    if (prov) { enable('#city', true); loadCities(prov); }
    else { enable('#city', false); }
});

$('#city').on('change', function () {
    const city = $(this).val();
    fillOptions($('#barangay'), [], 'Brgy', 'Brgy', 'Select Barangay');
    if (city) { enable('#barangay', true); loadBarangays(city); }
    else { enable('#barangay', false); }
});


    // Ensure disabled selects submit values
    $('form').on('submit', function () {
        ['#city','#barangay'].forEach(function (sel) {
            const $el = $(sel);
            if ($el.is(':disabled')) {
                if (!$el.val()) {
                    const selected = $el.find('option:selected').val() || $el.find('option:first').val();
                    if (selected) $el.val(selected);
                }
                $el.prop('disabled', false);
            }
        });
    });

    loadProvinces();

    // ── StudentNumber availability checker (mirrors Registration flow) ──
    var $form = $('form.parsley-examples');
    var checkUrl = $form.data('check-availability-url') || '';
    var excludeSn = $form.data('exclude-student-number') || '';

    function updateAvailabilityLabel($label, state, text) {
        if (!$label || !$label.length) return;
        $label.removeClass('is-ok is-bad is-muted');
        if (state) $label.addClass(state);
        $label.text(text || '');
    }

    function debounce(fn, wait) {
        var t = null;
        return function () {
            var args = arguments, ctx = this;
            clearTimeout(t);
            t = setTimeout(function () { fn.apply(ctx, args); }, wait);
        };
    }

    var $snInput = $('#StudentNumber');
    var $snStatus = $('#student-number-status');
    if ($snInput.length && $snStatus.length && checkUrl) {
        var runCheck = debounce(function () {
            var v = ($snInput.val() || '').toUpperCase();
            $snInput.val(v);
            if (!v) {
                $snInput.get(0).setCustomValidity('');
                updateAvailabilityLabel($snStatus, '', '');
                return;
            }
            $.post(checkUrl, { field: 'studentnumber', value: v, exclude: excludeSn })
                .done(function (payload) {
                    var data = (typeof payload === 'object') ? payload
                        : (function () { try { return JSON.parse(payload); } catch (e) { return null; } })();
                    if (!data || !data.ok) {
                        updateAvailabilityLabel($snStatus, 'is-muted', '');
                        $snInput.get(0).setCustomValidity('');
                        return;
                    }
                    if (data.exists) {
                        updateAvailabilityLabel($snStatus, 'is-bad', data.message || 'Already exists.');
                        $snInput.get(0).setCustomValidity(data.message || 'Already exists.');
                    } else {
                        updateAvailabilityLabel($snStatus, 'is-ok', data.message || 'Available.');
                        $snInput.get(0).setCustomValidity('');
                    }
                })
                .fail(function () {
                    updateAvailabilityLabel($snStatus, 'is-muted', '');
                    $snInput.get(0).setCustomValidity('');
                });
        }, 300);

        $snInput.on('input blur', runCheck);
        if (($snInput.val() || '').trim() !== '') {
            // Don't show "available" for the unchanged value on load — just clear.
            updateAvailabilityLabel($snStatus, '', '');
        }
    }
});
</script>
<script>
// Flash messages are shown by the shared toast bridge (includes/ui_kit.php).
</script>
</body>
</html>
