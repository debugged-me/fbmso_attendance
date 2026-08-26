<?php
$mode     = $mode     ?? 'create';
$row      = $row      ?? null;
$action   = $action   ?? site_url('activities/store');
$btn_text = $btn_text ?? ($mode==='edit' ? 'Update Activity' : 'Save Activity');
$titlebar = $titlebar ?? ($mode==='edit' ? 'Edit Co-Curricular Activity — QR Attendance' : 'Create Co-Curricular Activity — QR Attendance');
$val = function($field, $default='') use ($row) { return htmlspecialchars((string)($row->$field ?? $default), ENT_QUOTES, 'UTF-8'); };
$programNames = array_map(function($o){ return (string)$o->name; }, $programs ?? []);
$currentProgram   = (string)($row->program ?? '');
$isCustomInitial  = $mode==='edit' && $currentProgram!=='' && !in_array($currentProgram, $programNames, true);

/* -------- prefill session windows from $row->meta (JSON) -------- */
$sessionDefaults = [
  'am'  => ['in' => '', 'out' => ''],
  'pm'  => ['in' => '', 'out' => ''],
  'eve' => ['in' => '', 'out' => ''],
];
$sessions = $sessionDefaults;
if (!empty($row->meta)) {
  $meta = json_decode((string)$row->meta, true);
  if (is_array($meta) && !empty($meta['sessions']) && is_array($meta['sessions'])) {
    foreach (['am','pm','eve'] as $k) {
      if (!empty($meta['sessions'][$k]) && is_array($meta['sessions'][$k])) {
        $sessions[$k]['in']  = isset($meta['sessions'][$k]['in'])  ? (string)$meta['sessions'][$k]['in']  : '';
        $sessions[$k]['out'] = isset($meta['sessions'][$k]['out']) ? (string)$meta['sessions'][$k]['out'] : '';
      }
    }
  }
}

/* -------- availability: manual status + auto-close window -------- */
$statusOptions  = activity_manual_statuses();
$currentStatus  = $mode === 'edit' ? activity_normalize_status($row->status ?? 'open') : 'open';
$autoCfg        = activity_auto_close_settings($row->meta ?? '');
$autoCloseOn    = $mode === 'edit' ? $autoCfg['auto_close'] : true;
$graceMinutes   = $mode === 'edit' ? $autoCfg['grace_minutes'] : ACTIVITY_DEFAULT_GRACE_MINUTES;
$liveState      = $mode === 'edit' ? activity_state($row) : null;
?>
<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>
<body>
<div id="wrapper">
  <?php include('includes/top-nav-bar.php'); ?>
  <?php include('includes/sidebar.php'); ?>

  <style>
    .page-title-box{background:linear-gradient(135deg,#eef2ff 0%,#f5f7ff 60%,#ffffff 100%);border:1px solid #e5e7eb;border-radius:16px;padding:18px 20px;box-shadow:0 6px 14px rgba(31,41,55,.06)}
    .accent-hr{border:0;height:2px;margin:10px 0 0;background:linear-gradient(to right,#4285F4 55%,#A142F4 75%,#34A853 100%);border-radius:2px}
    .card-rich{border:1px solid #eef0f4;border-radius:18px;box-shadow:0 10px 28px rgba(17,24,39,.08)}
    .label-required::after{content:" *";color:#ef4444;font-weight:600}
    .hint{color:#6b7280;font-size:.85rem}
    .pill{display:inline-block;padding:4px 10px;border-radius:999px;font-size:.75rem;background:#f3f4f6;color:#374151;border:1px solid #e5e7eb}
    .preview-banner{background:linear-gradient(135deg,#1e40af 0%,#3b82f6 60%,#38bdf8 100%);color:#fff;border-radius:14px 14px 0 0;padding:14px 16px;box-shadow:inset 0 -1px 0 rgba(255,255,255,.25)}
    .shadow-soft{box-shadow:0 6px 18px rgba(0,0,0,.08)}
    @media (max-width: 991.98px){.page-title{font-size:1.1rem}.card-rich{border-radius:14px}}
    @media (max-width: 767.98px){.preview-banner{border-radius:14px 14px 0 0}.btn{width:100%}.pill{font-size:.72rem}}
    /* Sessions layout */
.sessions-card{border:1px solid #e5e7eb;border-radius:14px;background:#fff}
.session-row{display:grid;grid-template-columns:160px 1fr 1fr;gap:14px;align-items:center;padding:12px 14px}
.session-row:not(:last-child){border-bottom:1px solid #f1f5f9}
.session-label{font-weight:600;color:#111827}
.session-field label{font-size:.8rem;color:#6b7280;margin-bottom:4px;display:block}
.availability-head{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 14px;border-bottom:1px solid #f1f5f9;background:#fafbff;border-radius:14px 14px 0 0}
.availability-head .session-label{font-weight:600}
.custom-control-label{font-weight:400;color:#374151}

/* mobile */
@media (max-width: 768px){
  .session-row{grid-template-columns:1fr;gap:8px}
  .session-label{padding-top:6px}
}

  </style>

  <div class="content-page">
    <div class="content">
      <div class="container-fluid">
        <div class="page-title-box mb-3">
          <div class="d-flex align-items-center justify-content-between flex-wrap">
            <div class="mb-2">
              <h4 class="page-title mb-0"><?= $titlebar ?></h4>
              <div class="mt-1">
                <span class="pill">Step 1 of 2</span>
                <span class="pill">Details &amp; Sessions</span>
              </div>
            </div>
          </div>
          <hr class="accent-hr"/>
        </div>

        <div class="row">
          <div class="col-lg-7">
            <div class="card card-rich mb-4">
              <div class="card-body">
                <form method="post" autocomplete="off" id="activityForm" action="<?= $action ?>">

                  <!-- Title -->
                  <div class="form-group">
                    <label class="label-required">Title</label>
                    <input type="text" name="title" id="title" class="form-control" maxlength="150" value="<?= $mode==='edit' ? $val('title') : '' ?>" required>
                    <small class="hint d-flex justify-content-between"><span>Title of the program/activity</span><span><span id="titleCount">0</span>/150</span></small>
                  </div>

                  <!-- Description -->
                  <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="description" rows="3" class="form-control" maxlength="500"><?= $mode==='edit' ? $val('description') : '' ?></textarea>
                    <small class="hint d-flex justify-content-between"><span>Description</span><span><span id="descCount">0</span>/500</span></small>
                  </div>

                  <!-- Date + Program -->
                  <div class="form-row">
                    <div class="form-group col-md-5">
                      <label class="label-required">Date</label>
                      <input type="date" name="activity_date" id="activity_date" class="form-control" value="<?= $mode==='edit' ? $val('activity_date') : '' ?>" required>
                      <small class="hint">Pick date</small>
                    </div>
                    <div class="form-group col-md-7">
                      <label>Program</label>
                      <select name="program" id="program" class="form-control">
                        <option value="">Select Program</option>
                        <option value="__custom__" <?= $isCustomInitial ? 'selected' : '' ?>>Add New</option>
                        <?php foreach (($programs ?? []) as $p): ?>
                          <?php $sel = ($mode==='edit' && $currentProgram === (string)$p->name) ? 'selected' : ''; ?>
                          <option value="<?= htmlspecialchars($p->name, ENT_QUOTES, 'UTF-8') ?>" <?= $sel ?>>
                            <?= htmlspecialchars($p->name, ENT_QUOTES, 'UTF-8') ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                      <input type="text" name="program_custom" id="program_custom"
                             class="form-control mt-2 <?= $isCustomInitial ? '' : 'd-none' ?>"
                             placeholder="Type program name"
                             value="<?= $isCustomInitial ? htmlspecialchars($currentProgram, ENT_QUOTES, 'UTF-8') : '' ?>">
                      <small class="hint"></small>
                    </div>
                  </div>

                  <!-- Major -->
                  <div class="form-group col-md-7" id="major_wrap">
                    <label>Major</label>
                    <select name="program_major" id="program_major" class="form-control" disabled>
                      <option value="">—</option>
                    </select>
                    <small class="hint">Select a Program first to see its Majors.</small>
                  </div>

                  <!-- <div class="form-row">
                    <div class="form-group col-md-4">
                      <label>&nbsp;</label>
                      <div class="form-control d-flex align-items-center" style="height:auto;">
                        <input type="checkbox" id="all_day" name="all_day" value="1" class="mr-2">
                        <label for="all_day" class="mb-0">All-day activity</label>
                      </div>
                      <small class="hint">If checked, session times are ignored for Start/End.</small>
                    </div>
                  </div> -->

                <div class="sessions-card mb-3">
  <!-- Morning -->
  <div class="session-row">
    <div class="session-label">Morning</div>
    <div class="session-field">
      <label>In (start)</label>
      <input type="time" class="form-control" id="am_in"
             value="<?= htmlspecialchars($sessions['am']['in'], ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="session-field">
      <label>Out (end)</label>
      <input type="time" class="form-control" id="am_out"
             value="<?= htmlspecialchars($sessions['am']['out'], ENT_QUOTES, 'UTF-8') ?>">
    </div>
  </div>

  <!-- Afternoon -->
  <div class="session-row">
    <div class="session-label">Afternoon</div>
    <div class="session-field">
      <label>In (start)</label>
      <input type="time" class="form-control" id="pm_in"
             value="<?= htmlspecialchars($sessions['pm']['in'], ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="session-field">
      <label>Out (end)</label>
      <input type="time" class="form-control" id="pm_out"
             value="<?= htmlspecialchars($sessions['pm']['out'], ENT_QUOTES, 'UTF-8') ?>">
    </div>
  </div>

  <!-- Evening -->
  <div class="session-row">
    <div class="session-label">Evening</div>
    <div class="session-field">
      <label>In (start)</label>
      <input type="time" class="form-control" id="eve_in"
             value="<?= htmlspecialchars($sessions['eve']['in'], ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="session-field">
      <label>Out (end)</label>
      <input type="time" class="form-control" id="eve_out"
             value="<?= htmlspecialchars($sessions['eve']['out'], ENT_QUOTES, 'UTF-8') ?>">
    </div>
  </div>
</div>

                <!-- Availability: manual status + auto-close window -->
                <div class="sessions-card mb-3">
                  <div class="availability-head">
                    <span class="session-label">Check-in availability</span>
                    <?php if ($liveState): ?>
                      <span class="badge badge-pill <?= activity_state_badge_class($liveState['state']) ?> text-uppercase">
                        <?= htmlspecialchars($liveState['label'], ENT_QUOTES, 'UTF-8') ?>
                      </span>
                    <?php endif; ?>
                  </div>

                  <div class="session-row" style="grid-template-columns:160px 1fr 1fr">
                    <div class="session-label">Status</div>
                    <div class="session-field">
                      <label>Manual override</label>
                      <select name="status" id="status" class="form-control">
                        <?php foreach ($statusOptions as $k => $labelText): ?>
                          <option value="<?= $k ?>" <?= $currentStatus === $k ? 'selected' : '' ?>>
                            <?= htmlspecialchars($labelText, ENT_QUOTES, 'UTF-8') ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                      <small class="hint">Only <strong>Open</strong> accepts check-ins.</small>
                    </div>
                    <div class="session-field">
                      <label>Grace period (minutes)</label>
                      <input type="number" class="form-control" name="grace_minutes" id="grace_minutes"
                             min="0" max="1440" step="5" value="<?= (int)$graceMinutes ?>">
                      <small class="hint">Extra time allowed before start and after end.</small>
                    </div>
                  </div>

                  <div class="session-row" style="grid-template-columns:160px 1fr">
                    <div class="session-label">Auto-close</div>
                    <div class="session-field">
                      <div class="custom-control custom-switch">
                        <!-- Unchecked checkboxes are not posted; this fallback loses to the
                             checkbox below whenever it is checked, so 0/1 is always sent. -->
                        <input type="hidden" name="auto_close" value="0">
                        <input type="checkbox" class="custom-control-input" id="auto_close" name="auto_close" value="1"
                               <?= $autoCloseOn ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="auto_close">
                          Close check-ins automatically outside the activity's time window
                        </label>
                      </div>
                      <small class="hint" id="autoCloseHint"></small>
                    </div>
                  </div>
                </div>

                  <!-- Hidden: sessions JSON + derived top times for controller -->
                  <input type="hidden" name="meta" id="meta_json">
                  <input type="hidden" name="start_time" id="derived_start_time" value="">
                  <input type="hidden" name="end_time"   id="derived_end_time" value="">

                  <div class="mt-2 d-flex flex-wrap gap-2">
                    <button class="btn btn-primary shadow-soft mr-2 mb-2" type="submit">
                      <span class="mr-1"><?= $btn_text ?></span>
                    </button>
                    <a class="btn btn-light mb-2" href="<?= site_url('activities'); ?>">Cancel</a>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <!-- Preview -->
          <div class="col-lg-5">
            <div class="card card-rich mb-4">
              <div class="preview-banner">
                <div class="d-flex align-items-center justify-content-between">
                  <strong class="text-white">Activity Preview</strong>
                  <span class="pill" style="background:rgba(255,255,255,.15);color:#fff;border-color:rgba(255,255,255,.25);">QR Attendance</span>
                </div>
              </div>
              <div class="card-body">
                <h5 id="pvTitle" class="mb-1">Untitled Activity</h5>
                <div id="pvProgram" class="mb-2 text-muted">Program: —</div>
                <div class="mb-2">
                  <div class="small text-muted">Schedule</div>
                  <div id="pvSchedule" class="font-weight-600">—</div>
                </div>
                <div class="mb-3">
                  <div class="small text-muted">Description</div>
                  <div id="pvDesc" class="text-secondary">No description yet.</div>
                </div>
                <div class="p-3 rounded" style="background:#f9fafb;border:1px dashed #e5e7eb;">
                  <div class="small text-muted mb-1">QR Attendance (generated after save)</div>
                  <div class="d-flex align-items-center">
                    <div style="width:84px;height:84px;border-radius:8px;background:#e5e7eb;"></div>
                    <div class="ml-3">
                      <div class="font-weight-600">Attendance QR will appear here</div>
                      <div class="text-muted small">Share or project this code for scanning.</div>
                    </div>
                  </div>
                </div>
                <div class="mt-3 d-flex flex-wrap">
                  <span class="pill mr-2 mb-2">Secure check-in</span>
                  <span class="pill mr-2 mb-2">Scan &amp; go</span>
                  <span class="pill mr-2 mb-2">Auto logs</span>
                </div>
              </div>
            </div>

            <div class="card card-rich">
              <div class="card-body">
                <h6 class="mb-2">Tips</h6>
                <ul class="mb-0 text-muted pl-3">
                  <li>Keep the title concise and recognizable.</li>
                  <li>If times aren’t fixed, mark the activity as All-day.</li>
                  <li>Save first to generate the QR code for attendance.</li>
                </ul>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
    <?php include('includes/footer.php'); ?>
  </div>
</div>

<?php include('includes/themecustomizer.php'); ?>
<script src="<?= base_url(); ?>assets/js/vendor.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/moment/moment.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/jquery-scrollto/jquery.scrollTo.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/sweetalert2/sweetalert2.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/fullcalendar/fullcalendar.min.js"></script>
<script src="<?= base_url(); ?>assets/js/pages/calendar.init.js"></script>
<script src="<?= base_url(); ?>assets/js/pages/jquery.chat.js"></script>
<script src="<?= base_url(); ?>assets/js/pages/jquery.todo.js"></script>
<script src="<?= base_url(); ?>assets/libs/morris-js/morris.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/raphael/raphael.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/jquery-sparkline/jquery.sparkline.min.js"></script>
<script src="<?= base_url(); ?>assets/js/pages/dashboard.init.js"></script>
<script src="<?= base_url(); ?>assets/js/app.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/jquery-ui/jquery-ui.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/datatables/jquery.dataTables.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/datatables/dataTables.bootstrap4.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/datatables/dataTables.buttons.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/datatables/buttons.bootstrap4.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/jszip/jszip.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/pdfmake/pdfmake.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/pdfmake/vfs_fonts.js"></script>
<script src="<?= base_url(); ?>assets/libs/datatables/buttons.html5.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/datatables/buttons.print.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/datatables/dataTables.responsive.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/datatables/responsive.bootstrap4.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/datatables/dataTables.keyTable.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/datatables/dataTables.select.min.js"></script>
<script src="<?= base_url(); ?>assets/js/pages/datatables.init.js"></script>

<script>
(function(){
  var sel   = document.getElementById('program');
  var custom= document.getElementById('program_custom');
  var form  = document.getElementById('activityForm');
  var majorWrap   = document.getElementById('major_wrap');
  var programMajor= document.getElementById('program_major');

  if (majorWrap) majorWrap.classList.add('d-none');
  if (programMajor) programMajor.disabled = true;

  function toggleCustom(){
    if(sel.value==='__custom__'){
      custom.classList.remove('d-none');
      custom.setAttribute('required','required');
      custom.focus();
      if (majorWrap) majorWrap.classList.add('d-none');
      if (programMajor) { programMajor.disabled = true; programMajor.innerHTML = '<option value="—">—</option>'; }
    }else{
      custom.classList.add('d-none');
      custom.removeAttribute('required');
      custom.value='';
    }
  }
  
  function loadMajorsForProgram(programName){
    if (!programMajor || !majorWrap) return;
    if (!programName) {
      majorWrap.classList.add('d-none');
      programMajor.disabled = true;
      programMajor.innerHTML = '<option value="">—</option>';
      return;
    }
    majorWrap.classList.remove('d-none');
    programMajor.disabled = true;
    programMajor.innerHTML = '<option value="">Loading…</option>';
    fetch('<?= site_url('activities/majors') ?>?program='+encodeURIComponent(programName), {cache: 'no-store'})
      .then(r => r.json())
      .then(j => {
        programMajor.innerHTML = '';
        var opts = ['<option value="">—</option>'];
        if (j.ok && Array.isArray(j.majors) && j.majors.length){
          j.majors.forEach(function(mj){
            opts.push('<option value="'+ String(mj).replaceAll('"','&quot;') +'">'+ mj +'</option>');
          });
          programMajor.disabled = false;
          majorWrap.classList.remove('d-none');
        } else {
          programMajor.disabled = true;
          opts = ['<option value="">(No majors for this program)</option>'];
        }
        programMajor.innerHTML = opts.join('');
        updatePreview();
      })
      .catch(() => {
        programMajor.innerHTML = '<option value="">(Failed to load majors)</option>';
        programMajor.disabled = true;
      });
  }
  sel.addEventListener('change', function(){
    toggleCustom();
    if (sel.value && sel.value !== '__custom__') {
      loadMajorsForProgram(sel.value);
    } else {
      if (majorWrap) majorWrap.classList.add('d-none');
      if (programMajor) { programMajor.disabled = true; programMajor.innerHTML = '<option value="">—</option>'; }
    }
    updatePreview();
  });

  <?php if ($mode === 'edit'): ?>
  (function(){
    var saved = <?= json_encode((string)($row->program ?? '')) ?>;
    var parts = saved.split(' — ');
    var savedProgram = parts[0] || '';
    var savedMajor   = parts[1] || '';
    var currentProgram = sel.value || '';
    if (savedProgram && currentProgram === savedProgram) {
      loadMajorsForProgram(savedProgram);
      setTimeout(function(){
        if (programMajor && savedMajor) {
          for (var i=0;i<programMajor.options.length;i++){
            if (programMajor.options[i].value === savedMajor) {
              programMajor.selectedIndex = i; break;
            }
          }
        }
        updatePreview();
      }, 350);
    }
  })();
  <?php endif; ?>

  var title = document.getElementById('title');
  var desc  = document.getElementById('description');
  var date  = document.getElementById('activity_date');
  var allDay= document.getElementById('all_day');

  var pvTitle   = document.getElementById('pvTitle');
  var pvProgram = document.getElementById('pvProgram');
  var pvSchedule= document.getElementById('pvSchedule');
  var pvDesc    = document.getElementById('pvDesc');
  var titleCount= document.getElementById('titleCount');
  var descCount = document.getElementById('descCount');

  function fmtDate(iso){
    if(!iso) return '—';
    try{
      var d=new Date(iso+'T00:00:00');
      return d.toLocaleDateString(undefined,{weekday:'short',year:'numeric',month:'short',day:'numeric'});
    }catch(e){return iso;}
  }
  function fmtTime(v){
    if(!v) return '';
    try{
      var d=new Date('1970-01-01T'+v);
      return d.toLocaleTimeString(undefined,{hour:'2-digit',minute:'2-digit'});
    }catch(e){return v;}
  }

  function earliestLatestFromSessions(){
    const idsIn  = ['am_in','pm_in','eve_in'];
    const idsOut = ['am_out','pm_out','eve_out'];
    const ins  = idsIn.map(id => (document.getElementById(id)?.value || '').trim()).filter(Boolean);
    const outs = idsOut.map(id => (document.getElementById(id)?.value || '').trim()).filter(Boolean);
    const earliest = ins.length  ? ins.sort()[0] : null;
    const latest   = outs.length ? outs.sort().reverse()[0] : null;
    return {earliest, latest};
  }
function updatePreview(){
  pvTitle.textContent = title.value.trim() ? title.value.trim() : 'Untitled Activity';
  var programVal = (sel.value==='__custom__') ? (custom.value.trim()||'—') : (sel.value||'—');
  var majorVal   = (programMajor && !programMajor.disabled && programMajor.value) ? (' — '+programMajor.value) : '';
  pvProgram.textContent = 'Program: ' + programVal + majorVal;

  var sched='—';
  if (date.value) {
    if (allDay && allDay.checked) {
      sched = fmtDate(date.value) + ' · All-day';
    } else {
      const b = earliestLatestFromSessions();
      if (b.earliest && b.latest)      sched = fmtDate(date.value)+' · '+fmtTime(b.earliest)+'—'+fmtTime(b.latest);
      else if (b.earliest)             sched = fmtDate(date.value)+' · starts '+fmtTime(b.earliest);
      else if (b.latest)               sched = fmtDate(date.value)+' · until '+fmtTime(b.latest);
      else                              sched = fmtDate(date.value);
    }
  }
  pvSchedule.textContent = sched;

  pvDesc.textContent = desc.value.trim() ? desc.value.trim() : 'No description yet.';
  titleCount.textContent = (title.value||'').length;
  descCount.textContent  = (desc.value||'').length;
}

  function combineProgramMajor(){
    var programVal = (sel.value==='__custom__') ? (custom.value.trim()) : (sel.value||'');
    var majorVal   = (programMajor && !programMajor.disabled && programMajor.value) ? (' — '+programMajor.value) : '';
    if(programVal){
      var hidden = document.createElement('input');
      hidden.type  = 'hidden';
      hidden.name  = 'program';
      hidden.value = programVal + majorVal;
      form.appendChild(hidden);
    }
  }

  function serializeSessionsToMeta(){
    const meta = { sessions: {} };
    const val = id => (document.getElementById(id)?.value || '').trim();
    const am_in = val('am_in'),  am_out = val('am_out');
    const pm_in = val('pm_in'),  pm_out = val('pm_out');
    const ev_in = val('eve_in'), ev_out = val('eve_out');

    if (am_in || am_out) meta.sessions.am  = { in: am_in || null, out: am_out || null };
    if (pm_in || pm_out) meta.sessions.pm  = { in: pm_in || null, out: pm_out || null };
    if (ev_in || ev_out) meta.sessions.eve = { in: ev_in || null, out: ev_out || null };

    document.getElementById('meta_json').value = JSON.stringify(meta);

    // Also derive top Start/End for the controller (no backend change needed)
    const derived = earliestLatestFromSessions();
    const st = document.getElementById('derived_start_time');
    const et = document.getElementById('derived_end_time');
if (!allDay || !allDay.checked) 
  {      st.value = derived.earliest || '';
      et.value = derived.latest   || '';
    } else {
      st.value = '';
      et.value = '';
    }
  }

  if(form){
    form.addEventListener('submit',function(e){
      if(sel.value==='__custom__' && !custom.value.trim()){
        e.preventDefault(); UI.warning('Please type a Program name.'); custom.focus(); return false;
      }
      combineProgramMajor();
      serializeSessionsToMeta();
    });
  }

  [title,desc,date,sel,programMajor,custom,allDay].forEach(function(el){
    if(!el) return; el.addEventListener('input', updatePreview); el.addEventListener('change', updatePreview);
  });

  toggleCustom();
  if (sel.value && sel.value !== '__custom__') {
    loadMajorsForProgram(sel.value);
  }
  updatePreview();

  /* ── Availability: spell out the resulting check-in window ───────────── */
  const statusSel  = document.getElementById('status');
  const autoClose  = document.getElementById('auto_close');
  const graceInput = document.getElementById('grace_minutes');
  const autoHint   = document.getElementById('autoCloseHint');

  function fmtWindow(dateStr, timeStr, offsetMin){
    if(!dateStr) return null;
    const base = new Date(dateStr + 'T' + (timeStr || '00:00') + ':00');
    if (isNaN(base.getTime())) return null;
    base.setMinutes(base.getMinutes() + offsetMin);
    return base.toLocaleString(undefined, {
      month:'short', day:'numeric', year:'numeric', hour:'numeric', minute:'2-digit'
    });
  }

  function updateAvailabilityHint(){
    if(!autoHint) return;

    if (statusSel && statusSel.value !== 'open'){
      autoHint.textContent = 'Status is “' + statusSel.options[statusSel.selectedIndex].text +
        '” — check-ins are blocked regardless of the time window.';
      return;
    }
    if (autoClose && !autoClose.checked){
      autoHint.textContent = 'Off — this activity stays open until someone closes it manually.';
      return;
    }

    const grace  = Math.max(0, parseInt(graceInput && graceInput.value, 10) || 0);
    const bounds = earliestLatestFromSessions();
    const opens  = fmtWindow(date.value, bounds.earliest || '00:00', -grace);
    const closes = fmtWindow(date.value, bounds.latest   || '23:59',  grace);

    autoHint.textContent = (opens && closes)
      ? 'Check-ins accepted ' + opens + ' → ' + closes + ' (includes ' + grace + ' min grace).'
      : 'Pick a date and session times to see the resulting check-in window.';
  }

  [statusSel, autoClose, graceInput, date].forEach(function(el){
    if(!el) return;
    el.addEventListener('change', updateAvailabilityHint);
    el.addEventListener('input',  updateAvailabilityHint);
  });
  ['am_in','am_out','pm_in','pm_out','eve_in','eve_out'].forEach(function(id){
    const el = document.getElementById(id);
    if(!el) return;
    el.addEventListener('change', updateAvailabilityHint);
    el.addEventListener('input',  updateAvailabilityHint);
  });
  updateAvailabilityHint();
})();
</script>

</body>
</html>
