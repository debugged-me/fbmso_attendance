<!DOCTYPE html>
<html lang="en">

<?php include('includes/head.php'); ?>

<?php
$flashSuccess = $this->session->flashdata('success');
$flashDanger  = $this->session->flashdata('danger');
?>

<style>
  .card.announcement-card {
    transition: transform .3s ease, box-shadow .3s ease, border .3s ease;
    border: 1px solid #dee2e6;
    border-radius: 6px
  }

  .card.announcement-card:hover {
    transform: scale(1.03);
    border: 2px solid #007bff;
    box-shadow: 0 8px 20px rgba(0, 123, 255, .2)
  }

  .card.reg-ann {
    border: 1px solid #dee2e6
  }

  .card.reg-ann .card-header {
    background: #17a2b8;
    color: #fff;
    padding: .9rem 1rem
  }

  .card.reg-ann .card-title {
    margin: 0;
    font-weight: 600
  }

  .ann-row img {
    border: 1px solid #ddd;
    background: #fff;
    padding: 4px;
    margin-bottom: .5rem
  }

  .ann-row {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 1.5rem;
    background: #fdfdfd;
    box-shadow: 0 3px 10px rgba(0, 0, 0, .05);
    transition: transform .25s ease, box-shadow .25s ease
  }

  .ann-row:hover {
    transform: scale(1.01);
    box-shadow: 0 6px 18px rgba(0, 0, 0, .1)
  }

  .ann-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: .5rem;
    border-left: 5px solid #007bff;
    padding-left: .75rem
  }

  .ann-meta {
    font-size: .9rem;
    color: #6c757d;
    margin-bottom: .75rem
  }

  .ann-actions a {
    font-weight: 600;
    color: #007bff;
    text-decoration: none
  }

  .ann-actions a:hover {
    text-decoration: underline
  }

  .modal-body img {
    max-width: 100%;
    height: auto;
    border-radius: 6px
  }

  #viewAnnouncementModal .modal-body {
    max-height: 75vh;
    overflow: auto
  }

  .ann-flex {
    display: flex;
    gap: 16px;
    align-items: flex-start;
    flex-wrap: nowrap
  }

  .ann-text {
    flex: 1;
    font-size: 1rem;
    line-height: 1.6;
    max-height: 60vh;
    overflow: auto;
    white-space: pre-wrap
  }

  .ann-aside {
    width: 38%;
    min-width: 260px
  }

  .ann-aside img {
    width: 100%;
    height: auto;
    border-radius: 8px;
    object-fit: contain
  }

  @media (max-width:768px) {
    .ann-flex {
      flex-direction: column
    }

    .ann-aside {
      width: 100%;
      min-width: 0
    }

    .ann-text {
      max-height: none
    }
  }

  .welcome-hero {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: radial-gradient(110% 140% at 10% 10%, #e8f1ff 0%, #f3f7ff 35%, #ffffff 100%);
    border: 1px solid #e6ecf5;
    border-radius: 14px;
    padding: 18px 20px;
    box-shadow: 0 6px 18px rgba(66, 133, 244, .06)
  }

  .wh-kicker {
    font-size: .85rem;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: #6b7b93
  }

  .wh-title {
    font-weight: 800;
    color: #243b53
  }

  .wh-avatar {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 1.4rem;
    color: #1b3a57;
    background: linear-gradient(135deg, #90caf9 0%, #42a5f5 50%, #1e88e5 100%);
    box-shadow: 0 8px 18px rgba(66, 133, 244, .25)
  }

  .quick-actions .qa-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: #fff;
    border: 1px solid #e6ecf5;
    border-radius: 14px;
    padding: 16px 10px;
    text-decoration: none;
    transition: transform .18s ease, box-shadow .18s ease, border .18s ease
  }

  .quick-actions .qa-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 22px rgba(36, 59, 83, .12);
    border-color: #cfe3ff
  }

  .qa-icon {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 8px;
    background: linear-gradient(135deg, #e3f2fd 0%, #e8f0fe 100%);
    font-size: 1.25rem;
    color: #1e88e5
  }

  .qa-label {
    font-weight: 600;
    color: #2c3e50
  }

  .empty-state .empty-emoji {
    font-size: 2.25rem
  }

  .profile-photo-alert {
    border: 2px solid #dc3545;
    border-radius: 14px;
    padding: 18px 22px;
    background: linear-gradient(135deg, #fff5f5, #ffe8e8);
    color: #821414;
    box-shadow: 0 10px 24px rgba(220, 53, 69, .18);
    animation: flashPulse 1.4s ease-in-out infinite;
    display: flex;
    flex-direction: column;
    gap: 12px;
    text-align: center;
  }

  .profile-photo-alert h3 {
    margin: 0;
    font-weight: 800;
    letter-spacing: .08em
  }

  .profile-photo-alert p {
    margin: 0;
    font-size: 1rem
  }

  .profile-photo-alert .btn {
    min-width: 220px
  }

  @keyframes flashPulse {

    0%,
    100% {
      box-shadow: 0 0 0 0 rgba(220, 53, 69, .35);
      background: linear-gradient(135deg, #fff5f5, #ffe8e8)
    }

    50% {
      box-shadow: 0 0 0 6px rgba(220, 53, 69, .05);
      background: linear-gradient(135deg, #ffe8e8, #ffd9d9)
    }
  }
</style>

<body>
  <div id="wrapper">
    <?php include('includes/top-nav-bar.php'); ?>
    <?php include('includes/sidebar.php'); ?>

    <div class="content-page">
      <div class="content">
        <div class="container-fluid">
          <div class="row">
            <div class="col-12">
              <div class="page-title-box">
                <h4 class="page-title">STUDENT'S DASHBOARD</h4>
                <div class="clearfix"></div>
                <hr style="border:0;height:2px;background:linear-gradient(to right,#4285F4 60%,#FBBC05 80%,#34A853 100%);border-radius:1px;margin:20px 0;" />
                <?php
                $this->load->database();

                $studNo = (string)$this->session->userdata('username');
                $displayName = $this->session->userdata('name') ?? $this->session->userdata('fullname');

                if (!$displayName && $studNo) {
                  $candidates = [
                    ['table' => 'studeprofile',    'id_col' => 'StudentNumber', 'select' => 'FirstName AS f, MiddleName AS m, LastName AS l'],
                    ['table' => 'student_profile', 'id_col' => 'StudentNo',     'select' => 'FirstName AS f, MiddleName AS m, LastName AS l'],
                    ['table' => 'students',        'id_col' => 'studeno',       'select' => 'fname AS f, mname AS m, lname AS l'],
                    ['table' => 'o_users',         'id_col' => 'username',      'select' => 'fName AS f, mName AS m, lName AS l'],
                  ];

                  foreach ($candidates as $c) {
                    if ($this->db->table_exists($c['table'])) {
                      $row = $this->db->select($c['select'])
                        ->from($c['table'])
                        ->where($c['id_col'], $studNo)
                        ->limit(1)->get()->row();
                      if ($row) {
                        $f = trim((string)$row->f);
                        $m = trim((string)$row->m);
                        $l = trim((string)$row->l);
                        $name = ($l !== '' ? $l : '');
                        if ($f !== '') $name .= ($name !== '' ? ', ' : '') . $f;
                        if ($m !== '') $name .= ' ' . $m;

                        if ($name !== '') {
                          $displayName = $name;
                          break;
                        }
                      }
                    }
                  }
                }

                if (!$displayName) {
                  $displayName = $studNo;
                }
                $avatarInitial = strtoupper(mb_substr(trim($displayName), 0, 1, 'UTF-8'));

                $changeDpUrl = base_url('Page/changeDP?id=' . urlencode($studNo));

                $avatar = '';
                if ($studNo) {
                  if ($this->db->table_exists('users')) {
                    $row = $this->db->select('avatar')->from('users')->where('username', $studNo)->limit(1)->get()->row();
                    if ($row && isset($row->avatar)) {
                      $avatar = trim((string)$row->avatar);
                    }
                  }
                  if ($avatar === '' && $this->db->table_exists('o_users')) {
                    $row = $this->db->select('avatar')->from('o_users')->where('username', $studNo)->limit(1)->get()->row();
                    if ($row && isset($row->avatar)) {
                      $avatar = trim((string)$row->avatar);
                    }
                  }
                }

                if ($avatar === '' && $this->session->userdata('avatar')) {
                  $avatar = trim((string)$this->session->userdata('avatar'));
                }

                $avatarPath = FCPATH . 'upload/profile/' . $avatar;
                $hasAvatar = $avatar !== '' && strtolower($avatar) !== 'avatar.png' && is_file($avatarPath);

                if ($hasAvatar) {
                  $this->session->set_userdata('avatar', $avatar);
                } else {
                  $this->session->set_userdata('avatar', '');
                }
                ?>
                <?php if (!$hasAvatar): ?>
                  <div class="profile-photo-alert mb-4">
                    <h3>PLEASE ADD PROFILE PHOTO</h3>
                    <p>Upload your picture to personalise your account and continue using the dashboard shortcuts.</p>
                    <div class="d-flex justify-content-center">
                      <a class="btn btn-danger btn-lg font-weight-bold" href="<?= $changeDpUrl; ?>">
                        <i class="ion ion-md-contact mr-2"></i> Upload Profile Photo
                      </a>
                    </div>
                  </div>
                <?php else: ?>
                  <?php
                  $hour = (int)date('G');
                  if ($hour < 12) {
                    $greeting = 'Good morning';
                    $greetEmoji = '☀️';
                  } elseif ($hour < 18) {
                    $greeting = 'Good afternoon';
                    $greetEmoji = '🌤️';
                  } else {
                    $greeting = 'Good evening';
                    $greetEmoji = '🌙';
                  }
                  ?>
                  <div class="welcome-hero mb-3">
                    <div class="wh-text">
                      <div class="wh-kicker"><?= $greeting . ' ' . $greetEmoji; ?></div>
                      <h2 class="wh-title mb-1"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></h2>
                      <p class="mb-0">Here’s what’s happening on your dashboard today.</p>
                    </div>
                  </div>
                <?php endif; ?>

                <?php if (isset($is_flagged) && $is_flagged && isset($flag_details)): ?>
                  <div class="alert alert-warning text-center" data-toggle="modal" data-target="#flagModal"
                    style="cursor:pointer;animation:subtleFlash 1.5s infinite;border:1px solid #ffc107;">
                    <strong>Notice:</strong> Your account has a pending concern. Click here for details.
                  </div>
                  <style>
                    @keyframes subtleFlash {

                      0%,
                      100% {
                        background-color: #fff8e1;
                        color: #856404
                      }

                      50% {
                        background-color: #ffeeba;
                        color: #856404
                      }
                    }
                  </style>

                  <div class="modal fade" id="flagModal" tabindex="-1" role="dialog" aria-labelledby="flagModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                      <div class="modal-content">
                        <div class="modal-header bg-warning">
                          <h5 class="modal-title" id="flagModalLabel">Flagged Account Details</h5>
                          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                          <p><strong>Reason:</strong> <?= $flag_details->flaggedReason; ?></p>
                          <p><strong>Flagged By:</strong> <?= $flag_details->flaggedBy; ?></p>
                          <p><strong>Office:</strong> <?= $flag_details->Office; ?></p>
                          <p><strong>School Year:</strong> <?= $flag_details->SY; ?></p>
                          <p><strong>Semester:</strong> <?= $flag_details->Semester; ?></p>
                        </div>
                        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Close</button></div>
                      </div>
                    </div>
                  </div>
                <?php endif; ?>

              </div>
            </div>
          </div>

          <div class="card reg-ann">
            <div class="card-header">
              <div class="card-widgets">
                <a data-toggle="collapse" href="#adminAnnouncements" role="button" aria-expanded="true" aria-controls="adminAnnouncements">
                  <i class="mdi mdi-minus text-white"></i>
                </a>
              </div>
              <h5 class="card-title mb-0">ANNOUNCEMENTS</h5>
            </div>

            <div id="adminAnnouncements" class="collapse show">
              <div class="card-body">
                <?php if (empty($data)): ?>
                  <div class="empty-state text-center py-5">
                    <div class="empty-emoji mb-2">📣</div>
                    <h5 class="mb-1">No announcements yet</h5>
                    <p class="text-muted mb-0">We’ll post updates here when something comes up.</p>
                  </div>
                <?php else: ?>
                  <?php $i = 0;
                  foreach ($data as $row): ?>
                    <?php
                    $i++;
                    $modalID  = 'annView' . $i;
                    $title    = $row->title ?? 'Announcement';
                    $message  = $row->message ?? $row->description ?? '';
                    $author   = $row->author ?? 'Admin';
                    $posted   = !empty($row->datePosted) ? date('F d, Y', strtotime($row->datePosted)) : '';
                    $audience = $row->audience ?? 'All';
                    $imageURL = !empty($row->image) ? base_url('upload/announcements/' . $row->image) : '';
                    ?>
                    <div class="ann-row">
                      <?php if ($imageURL): ?>
                        <div class="row mb-2">
                          <div class="col-md-3 text-center">
                            <img src="<?= $imageURL; ?>" class="img-fluid rounded" style="max-height:100px" alt="Preview Image">
                          </div>
                          <div class="col-md-9">
                            <div class="ann-title"><i class="mdi mdi-bullhorn-outline mr-1 text-primary"></i>
                              <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                            <div class="ann-meta">Posted on <?= $posted; ?> • Audience: <?= htmlspecialchars($audience, ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="ann-actions"><a href="#" data-toggle="modal" data-target="#<?= $modalID; ?>">View Details</a></div>
                          </div>
                        </div>
                      <?php else: ?>
                        <div class="ann-title"><i class="mdi mdi-bullhorn-outline mr-1 text-primary"></i>
                          <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                        <div class="ann-meta">Posted on <?= $posted; ?> • Audience: <?= htmlspecialchars($audience, ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="ann-actions"><a href="#" data-toggle="modal" data-target="#<?= $modalID; ?>">View Details</a></div>
                      <?php endif; ?>
                    </div>

                    <div class="modal fade" id="<?= $modalID; ?>" tabindex="-1" role="dialog" aria-labelledby="<?= $modalID; ?>Label" aria-hidden="true">
                      <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                        <div class="modal-content">
                          <div class="modal-header text-white">
                            <h5 class="modal-title" id="<?= $modalID; ?>Label"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h5>
                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                          </div>
                          <div class="modal-body">
                            <div class="ann-flex">
                              <div class="ann-text"><?= $message; ?></div>
                              <?php if ($imageURL): ?>
                                <aside class="ann-aside">
                                  <img src="<?= $imageURL; ?>" alt="Announcement Image">
                                  <div class="text-right mt-2">
                                    <a href="<?= $imageURL; ?>" class="btn btn-outline-info btn-sm" download>
                                      <i class="mdi mdi-download"></i> Download Image
                                    </a>
                                  </div>
                                </aside>
                              <?php endif; ?>
                            </div>
                          </div>
                          <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button></div>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>

  </div>

  <?php include('includes/themecustomizer.php'); ?>

  <script src="<?= base_url(); ?>assets/js/vendor.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/moment/moment.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/jquery-scrollto/jquery.scrollTo.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/sweetalert2/sweetalert2.min.js"></script>
  <script defer src="<?= base_url(); ?>assets/libs/fullcalendar/fullcalendar.min.js"></script>
  <script defer src="<?= base_url(); ?>assets/js/pages/calendar.init.js"></script>
  <script src="<?= base_url(); ?>assets/js/pages/jquery.chat.js"></script>
  <script src="<?= base_url(); ?>assets/js/pages/jquery.todo.js"></script>
  <script defer src="<?= base_url(); ?>assets/libs/morris-js/morris.min.js"></script>
  <script defer src="<?= base_url(); ?>assets/libs/raphael/raphael.min.js"></script>
  <script defer src="<?= base_url(); ?>assets/libs/jquery-sparkline/jquery.sparkline.min.js"></script>
  <script defer src="<?= base_url(); ?>assets/js/pages/dashboard.init.js"></script>
  <script src="<?= base_url(); ?>assets/js/app.min.js"></script>
  <script defer src="<?= base_url(); ?>assets/libs/jquery-ui/jquery-ui.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/jquery.dataTables.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.bootstrap4.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.buttons.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/buttons.bootstrap4.min.js"></script>
  <script defer src="<?= base_url(); ?>assets/libs/jszip/jszip.min.js"></script>
  <script defer src="<?= base_url(); ?>assets/libs/pdfmake/pdfmake.min.js"></script>
  <script defer src="<?= base_url(); ?>assets/libs/pdfmake/vfs_fonts.js"></script>
  <script defer src="<?= base_url(); ?>assets/libs/datatables/buttons.html5.min.js"></script>
  <script defer src="<?= base_url(); ?>assets/libs/datatables/buttons.print.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.responsive.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/responsive.bootstrap4.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.keyTable.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.select.min.js"></script>
  <script src="<?= base_url(); ?>assets/js/pages/datatables.init.js"></script>
  <script>
    $('#viewAnnouncementModal').on('show.bs.modal', function(e) {
      var t = $(e.relatedTarget);
      var title = t.data('title') || '';
      var message = t.data('message') || '';
      var img = t.data('image') || '';
      var hasImg = t.data('hasimage') === 1 || t.data('hasimage') === '1';
      $('#vamTitle').text(title);
      $('#vamMessage').html(message);
      if (hasImg && img) {
        $('#vamImage').attr('src', img);
        $('#vamImageWrap').show();
      } else {
        $('#vamImage').attr('src', '');
        $('#vamImageWrap').hide();
      }
    });
  </script>
  <script>
    // Flash messages are shown by the shared toast bridge (includes/ui_kit.php).
  </script>
</body>

</html>