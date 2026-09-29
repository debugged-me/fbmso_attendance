<?php if ($this->authguard->may('request/ajax_pending_count')): ?>
  <li class="dropdown notification-list req-bell"
    data-count-url="<?= site_url('request/ajax_pending_count'); ?>"
    data-list-url="<?= site_url('request/ajax_pending_list'); ?>"
    data-markseen-url="<?= site_url('request/ajax_mark_seen'); ?>"
    data-index-url="<?= site_url('request'); ?>">

    <a class="nav-link dropdown-toggle waves-effect" data-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
      <i class="mdi mdi-bell-outline noti-icon"></i>
      <span class="badge badge-danger rounded-circle noti-icon-badge req-badge" style="display:none;">0</span>
    </a>
    <div class="dropdown-menu dropdown-menu-right dropdown-lg">
      <div class="dropdown-item noti-title d-flex justify-content-between align-items-center">
        <h5 class="font-16 m-0">Pending Requests</h5>
        <a href="<?= site_url('request'); ?>" class="text-muted small">View all</a>
      </div>

      <div class="slimscroll noti-scroll" style="max-height:320px; overflow:auto;">
        <div class="text-center text-muted p-3 req-empty">No pending requests.</div>
        <div class="req-list"></div>
      </div>
    </div>
  </li>
<?php endif; ?>