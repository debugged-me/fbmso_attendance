<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); // ensure head.php has <meta charset="utf-8"> ?>
<link rel="stylesheet" href="<?= base_url('assets/css/uniform-page.css?v=20260831'); ?>">

<style>
  #vamContent{ display:flex; gap:16px; align-items:flex-start; flex-wrap:nowrap; }
  #vamText{ flex:1; max-height:60vh; overflow:auto; }
  #vamAside{ width:38%; min-width:260px; display:none; }
  #vamAside img{ width:100%; height:auto; border-radius:8px; display:block; object-fit:contain; }

  @media (max-width: 768px){
    #vamContent{ flex-direction:column; flex-wrap:wrap; }
    #vamAside{ width:100%; min-width:0; }
    #vamText{ max-height:none; }
  }

  /* Announcement rows */
  .ann-item{display:flex;align-items:center;gap:14px;background:#fff;border:1px solid #e6ebf5;border-radius:10px;padding:14px 16px;margin-bottom:14px;box-shadow:0 1px 3px rgba(13,27,75,.05);transition:box-shadow .2s ease,transform .2s ease}
  .ann-item:hover{box-shadow:0 10px 24px rgba(13,27,75,.12);transform:translateY(-2px)}
  .ann-thumb{width:110px;min-width:110px}
  .ann-thumb img{width:100%;height:100px;object-fit:cover;border-radius:8px;display:block}
  .ann-body{flex:1}
  .ann-title{margin:0 0 4px;font-weight:700;color:#0d1b4b;font-size:1.05rem;border-left:3px solid #2a4090;padding-left:10px}
  .ann-meta{font-size:.85rem;color:#6b7a99;margin-bottom:6px;padding-left:13px}
  .ann-actions a{margin-left:8px}
  .ann-item.no-image .ann-body{margin-left:0}
  .ann-view{color:#4266d4;font-weight:600;font-size:.9rem;text-decoration:none}
  .ann-view:hover{color:#2a4090;text-decoration:underline}
  .ann-empty{text-align:center;padding:48px 16px;color:#6b7a99}
  .ann-empty .ann-empty-icon{font-size:2.6rem;color:#c7d0e6;margin-bottom:10px}

  /* Mobile: stack announcement items vertically */
  @media (max-width: 767.98px) {
    .ann-item { flex-direction:column; align-items:stretch; gap:10px; padding:12px 14px; }
    .ann-thumb { width:100%; min-width:0; }
    .ann-thumb img { height:140px; }
    .ann-body { width:100%; }
    .ann-title { font-size:.98rem; }
    .ann-meta { font-size:.78rem; }
    .ann-actions { display:flex; gap:8px; }
    .ann-actions a { margin-left:0; flex:1; text-align:center; font-size:.8rem; padding:8px 10px; }
  }

  /* Announcement modals — gradient head, stacked fields, styled picker */
  .ann-modal .modal-content{border:0;border-radius:18px;overflow:hidden;box-shadow:0 24px 60px rgba(13,27,75,.28)}
  .ann-modal .modal-header{background:linear-gradient(135deg,#1a2a6c,#2a4090);color:#fff;border-bottom:0;padding:18px 22px;align-items:center}
  .ann-modal .modal-title{font-weight:700;font-size:1.02rem;display:flex;align-items:center;gap:9px;color:#fff;margin:0}
  .ann-modal .modal-title .mdi{font-size:1.25rem;opacity:.92}
  .ann-modal .modal-header .close{color:#fff;opacity:.75;text-shadow:none;font-size:1.7rem;font-weight:300;padding:0;margin:0 0 0 auto;line-height:1}
  .ann-modal .modal-header .close:hover{opacity:1}
  .ann-modal .modal-body{padding:20px 24px 6px}
  .ann-modal .modal-body .form-group{margin-bottom:16px}
  .ann-modal .modal-body label{font-weight:600;color:#334155;font-size:.85rem;display:flex;align-items:center;gap:6px;margin-bottom:7px}
  .ann-modal .modal-body label .mdi{color:#4266d4;font-size:1rem}
  .ann-modal .ann-opt{color:#94a3b8;font-weight:400}
  .ann-modal .form-control{border:1.5px solid #dbe4f3;border-radius:10px;padding:10px 14px;height:auto;font-size:.92rem;color:#1e293b;transition:border-color .15s,box-shadow .15s}
  .ann-modal .form-control:focus{border-color:#4266d4;box-shadow:0 0 0 3px rgba(66,102,212,.14)}
  .ann-modal textarea.form-control{min-height:110px;resize:vertical}
  .ann-audience-note{display:flex;align-items:center;gap:9px;background:#eef4ff;border:1px solid #d7e3fb;color:#2a4090;font-size:.85rem;border-radius:10px;padding:10px 14px;margin-bottom:16px}
  .ann-audience-note .mdi{font-size:1.15rem}
  .ann-modal .custom-file{height:auto}
  .ann-modal .custom-file-input{height:auto}
  .ann-modal .custom-file-label{position:relative;border:1.5px dashed #c3cfe8;border-radius:10px;padding:10px 14px;height:auto;font-size:.9rem;color:#64748b;background:#f8faff;cursor:pointer;font-weight:400;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;padding-right:90px}
  .ann-modal .custom-file-label::after{content:"Browse";position:absolute;top:50%;bottom:auto;right:7px;transform:translateY(-50%);display:flex;align-items:center;background:#eef2fb;color:#2a4090;border:0;border-radius:7px;font-weight:700;height:auto;line-height:1.4;padding:7px 14px}
  .ann-modal .custom-file-input:focus~.custom-file-label{border-color:#4266d4;box-shadow:0 0 0 3px rgba(66,102,212,.14)}
  .ann-cur-img{display:flex;gap:12px;align-items:center;background:#f8fafc;border:1px solid #e6ebf5;border-radius:10px;padding:10px 12px;margin-bottom:10px}
  .ann-cur-img img{width:96px;height:64px;object-fit:cover;border-radius:8px;display:block}
  .ann-cur-img-name{font-size:.8rem;color:#64748b;word-break:break-all;margin-bottom:6px}
  .ann-modal .modal-footer{border-top:0;background:#f8fafc;padding:14px 24px}
  .ann-modal.fade .modal-dialog{transform:translateY(-12px) scale(.98);transition:transform .2s ease-out}
  .ann-modal.show .modal-dialog{transform:none}
</style>

<body>
<div id="wrapper">
  <?php include('includes/top-nav-bar.php'); ?>
  <?php include('includes/sidebar.php'); ?>

  <?php
  // Tiny sanitizer to allow basic formatting in message (for display only)
  if (!function_exists('ann_sanitize_html')) {
    function ann_sanitize_html($html) {
      $allowed = '<p><br><strong><em><u><span><div><h1><h2><h3><h4><h5><h6>'
               . '<ul><ol><li><blockquote><hr><a><img><b><i>';
      $clean = strip_tags((string)$html, $allowed);
      $clean = preg_replace('/<a\s+/i', '<a rel="noopener noreferrer" target="_blank" ', $clean);
      $clean = preg_replace_callback('/<img[^>]*src="([^"]+)"[^>]*>/i', function($m){
        return (preg_match('#^(https?:|data:image/)#i', $m[1])) ? $m[0] : '';
      }, $clean);
      return $clean;
    }
  }
  ?>

  <div class="content-page">
    <div class="content">
      <div class="container-fluid">

        <div class="row">
          <div class="col-md-12">
            <div class="page-title-box">
              <h4 class="up-page-title">Announcements</h4>
            </div>

            <?php if ($this->session->flashdata('success')): ?>
              <div class="up-flash up-flash-success">
                <?= $this->session->flashdata('success'); ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
              </div>
            <?php endif; ?>
            <?php if ($this->session->flashdata('error')): ?>
              <div class="up-flash up-flash-danger">
                <?= $this->session->flashdata('error'); ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Create Modal -->
        <div class="modal fade ann-modal" id="announcementModal" tabindex="-1" role="dialog" aria-labelledby="announcementModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h4 class="modal-title" id="announcementModalLabel"><i class="mdi mdi-bullhorn-outline"></i> New Announcement</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
              </div>
              <form action="<?= base_url('Announcement/uploadAnnouncement'); ?>" enctype="multipart/form-data" method="POST">
                <div class="modal-body">
                  <div class="ann-audience-note">
                    <i class="mdi mdi-account-group-outline"></i>
                    <span>This will be posted to <strong>all students</strong>.</span>
                  </div>

                  <div class="form-group">
                    <label for="ann_title"><i class="mdi mdi-format-title"></i> Title</label>
                    <input type="text" class="form-control" name="title" id="ann_title" placeholder="e.g. No classes on Monday" required>
                  </div>

                  <div class="form-group">
                    <label for="ann_message"><i class="mdi mdi-text-long"></i> Message</label>
                    <textarea name="message" id="ann_message" class="form-control" rows="4" placeholder="Write the announcement details here…" required></textarea>
                  </div>

                  <div class="form-group">
                    <label for="ann_image"><i class="mdi mdi-image-outline"></i> Attach Image <span class="ann-opt">(optional)</span></label>
                    <div class="custom-file">
                      <input type="file" class="custom-file-input" name="nonoy" id="ann_image" accept=".jpg,.jpeg,.png,.gif">
                      <label class="custom-file-label" for="ann_image">Choose image…</label>
                    </div>
                    <small class="text-muted d-block mt-2">Recommended max: 900×600px. Allowed: jpg, png, gif.</small>
                  </div>

                  <div class="form-group mb-0">
                    <label for="ann_expire"><i class="mdi mdi-calendar-remove-outline"></i> Expire Date</label>
                    <input type="date" name="date_expire" id="ann_expire" class="form-control">
                    <small class="text-muted d-block mt-2">Leave blank to show indefinitely.</small>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="up-btn up-btn-ghost" data-dismiss="modal">Cancel</button>
                  <button type="submit" name="submit" class="up-btn up-btn-primary"><i class="mdi mdi-send-outline"></i> Post Announcement</button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- Edit Modal -->
        <div class="modal fade ann-modal" id="editAnnouncementModal" tabindex="-1" role="dialog" aria-labelledby="editAnnouncementModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <form class="modal-content" action="<?= site_url('Page/updateAnnouncement'); ?>" enctype="multipart/form-data" method="POST">
              <div class="modal-header">
                <h4 class="modal-title" id="editAnnouncementModalLabel"><i class="mdi mdi-pencil-outline"></i> Edit Announcement</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
              </div>

              <div class="modal-body">
                <input type="hidden" name="aID" id="edit_aID">
                <input type="hidden" name="old_image" id="edit_old_image">

                <div class="form-group">
                  <label for="edit_title"><i class="mdi mdi-format-title"></i> Title</label>
                  <input type="text" class="form-control" name="title" id="edit_title" required>
                </div>

                <div class="form-group">
                  <label for="edit_message"><i class="mdi mdi-text-long"></i> Message</label>
                  <textarea name="message" id="edit_message" class="form-control" rows="5" required></textarea>
                </div>

                <div class="form-group">
                  <label><i class="mdi mdi-image-outline"></i> Image</label>
                  <div id="edit_image_wrap" class="ann-cur-img" style="display:none">
                    <img id="edit_preview" src="" alt="Current image">
                    <div>
                      <div class="ann-cur-img-name" id="edit_image_name"></div>
                      <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" id="remove_image" name="remove_image" value="1">
                        <label class="form-check-label" for="remove_image">Remove current image</label>
                      </div>
                    </div>
                  </div>
                  <div class="custom-file">
                    <input type="file" class="custom-file-input" name="nonoy" id="edit_image" accept=".jpg,.jpeg,.png,.gif">
                    <label class="custom-file-label" for="edit_image">Replace with new image…</label>
                  </div>
                  <small class="text-muted d-block mt-2">Allowed: jpg, png, gif. Max 5MB.</small>
                </div>

                <div class="form-group mb-0">
                  <label for="edit_date_expire"><i class="mdi mdi-calendar-remove-outline"></i> Expire Date</label>
                  <input type="date" name="date_expire" id="edit_date_expire" class="form-control">
                  <small class="text-muted d-block mt-2">Leave blank to show indefinitely.</small>
                </div>
              </div>

              <div class="modal-footer">
                <button type="button" class="up-btn up-btn-ghost" data-dismiss="modal">Cancel</button>
                <button type="submit" name="submit" class="up-btn up-btn-primary"><i class="mdi mdi-content-save-outline"></i> Save Changes</button>
              </div>
            </form>
          </div>
        </div>

        <!-- List -->
        <div class="row">
          <div class="col-md-12">
            <div class="up-card">
              <div class="up-card-head">
                <div class="d-flex align-items-center" style="gap:10px;flex-wrap:wrap;">
                  <h4><i class="mdi mdi-bullhorn-outline"></i> Announcement List</h4>
                  <span class="badge badge-purple">
                    SY <?= $this->session->userdata('sy'); ?> <?= $this->session->userdata('semester'); ?>
                  </span>
                </div>
                <div class="pl-actions">
                  <button type="button" class="up-btn up-btn-primary" data-toggle="modal" data-target="#announcementModal">
                    <i class="mdi mdi-plus"></i> Post New Announcement
                  </button>
                </div>
              </div>
              <div class="up-card-body">

                <?php if (!empty($announcement)): ?>
                  <?php foreach ($announcement as $row):
                    $title      = htmlspecialchars($row->title ?? '', ENT_QUOTES, 'UTF-8');
                    $rawMsg     = htmlspecialchars($row->message ?? '', ENT_QUOTES, 'UTF-8');
                    $msgHtml    = ann_sanitize_html(nl2br($row->message ?? ''));
                    $img        = trim($row->image ?? '');
                    $imagePath  = FCPATH . 'upload/announcements/' . $img;
                    $hasImage   = ($img !== '' && file_exists($imagePath));
                    $imageURL   = $hasImage ? base_url('upload/announcements/' . $img) : '';
                    $dateExpire = !empty($row->date_expire) ? date('Y-m-d', strtotime($row->date_expire)) : '';
                  ?>
                    <div class="ann-item <?= $hasImage ? '' : 'no-image' ?>">
                      <?php if ($hasImage): ?>
                        <div class="ann-thumb">
                          <img src="<?= $imageURL; ?>" alt="<?= $title; ?>">
                        </div>
                      <?php endif; ?>

                      <div class="ann-body">
                        <div class="ann-title"><?= $title; ?></div>
                        <div class="ann-meta">
                          Posted on <?= date('F d, Y', strtotime($row->datePosted)); ?>
                          • Audience: <?= htmlspecialchars($row->audience ?? '', ENT_QUOTES, 'UTF-8'); ?>
                          <?php if (!empty($row->date_expire)): ?>
                            • <span class="text-danger">Expires: <?= date('F d, Y', strtotime($row->date_expire)); ?></span>
                          <?php endif; ?>
                        </div>

                        <!-- Hidden container with both sanitized + raw (for edit) -->
                        <div id="ann-<?= $row->aID; ?>" class="d-none"
                             data-id="<?= (int)$row->aID; ?>"
                             data-title="<?= $title; ?>"
                             data-message-raw="<?= $rawMsg; ?>"
                             data-date-expire="<?= $dateExpire; ?>"
                             data-image="<?= $imageURL; ?>"
                             data-image-name="<?= htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>"
                             data-hasimage="<?= $hasImage ? '1' : '0'; ?>">
                          <div class="ann-content"><?= $msgHtml; ?></div>
                        </div>

                        <a href="#" class="ann-view" data-toggle="modal" data-target="#viewAnnouncementModal" data-id="<?= $row->aID; ?>">
                          View Details
                        </a>
                      </div>

                      <div class="ann-actions">
                        <a href="#" class="up-btn up-btn-ghost ann-edit"
                           data-toggle="modal" data-target="#editAnnouncementModal"
                           data-id="<?= (int)$row->aID; ?>">
                          <i class="mdi mdi-pencil"></i> Edit
                        </a>
                        <a href="<?= base_url('Announcement/delete/' . $row->aID); ?>" class="up-btn up-btn-danger"
                           data-ui-confirm="It stops appearing for everyone it was posted to. This cannot be undone."
                           data-ui-confirm-title="Delete this announcement?"
                           data-ui-confirm-ok="Delete announcement">
                          <i class="mdi mdi-delete"></i> Delete
                        </a>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <div class="ann-empty">
                    <div class="ann-empty-icon"><i class="mdi mdi-bullhorn-off-outline"></i></div>
                    <div>No announcements to display at this time.</div>
                  </div>
                <?php endif; ?>

              </div>
            </div>
          </div>
        </div>

        <div style="height:40px;"></div>

      </div>
    </div>

    <?php include('includes/footer.php'); ?>
  </div>
</div>

<!-- View Modal -->
<div class="modal fade ann-modal" id="viewAnnouncementModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="mdi mdi-bullhorn-outline"></i> <span id="vamTitle">Announcement</span></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div id="vamContent" style="display:flex; gap:16px; align-items:flex-start; flex-wrap:wrap;">
          <div id="vamText" style="flex:1; font-size:1rem; line-height:1.6; max-height:60vh; overflow:auto;"></div>
          <aside id="vamAside" style="width:38%; min-width:260px; display:none;">
            <img id="vamImage" src="" alt="Announcement Image" style="width:100%; height:auto; border-radius:8px; object-fit:contain;">
            <div class="text-right mt-2">
              <a id="vamDownload" class="up-btn up-btn-ghost" href="#" download>Download image</a>
            </div>
          </aside>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="up-btn up-btn-ghost" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="<?= base_url(); ?>assets/js/vendor.min.js"></script>
<script src="<?= base_url(); ?>assets/js/app.min.js"></script>

<script>
  // Show the chosen filename on the styled file inputs
  $(document).on('change', '.custom-file-input', function () {
    var name = ($(this).val() || '').split('\\').pop();
    $(this).siblings('.custom-file-label').text(name || 'Choose image…');
  });

  // View modal
  $('#viewAnnouncementModal').on('show.bs.modal', function (e) {
    var t   = $(e.relatedTarget);
    var id  = t.data('id');
    var box = $('#ann-' + id);

    var title  = box.data('title') || '';
    var img    = box.data('image') || '';
    var hasImg = (String(box.data('hasimage')) === '1');
    var html   = box.find('.ann-content').html() || '';

    $('#vamTitle').text(title);
    $('#vamText').html(html);

    if (hasImg && img) {
      $('#vamImage').attr('src', img);
      $('#vamDownload').attr('href', img);
      $('#vamAside').show();
    } else {
      $('#vamImage').attr('src', '');
      $('#vamDownload').attr('href', '#');
      $('#vamAside').hide();
    }
  });

  // Edit modal prefill
  $('#editAnnouncementModal').on('show.bs.modal', function (e) {
    var t  = $(e.relatedTarget);
    var id = t.data('id');
    var box = $('#ann-' + id);
    if (!box.length) return;

    var title      = box.data('title') || '';
    var raw        = box.data('message-raw') || '';
    var dateExpire = box.data('date-expire') || '';
    var imgUrl     = box.data('image') || '';
    var imgName    = box.data('image-name') || '';
    var hasImg     = (String(box.data('hasimage')) === '1');

    $('#edit_aID').val(id);
    $('#edit_title').val(title);
    $('#edit_message').val(raw);
    $('#edit_date_expire').val(dateExpire);
    $('#edit_old_image').val(imgName);
    $('#remove_image').prop('checked', false);

    if (hasImg && imgUrl) {
      $('#edit_image_wrap').show();
      $('#edit_preview').attr('src', imgUrl);
      $('#edit_image_name').text(imgName);
    } else {
      $('#edit_image_wrap').hide();
      $('#edit_preview').attr('src', '');
      $('#edit_image_name').text('');
    }
  });
</script>
</body>
</html>
