<?php if ((string)$this->session->userdata('level') === 'Auditor'): ?>
    <div class="up-flash up-flash-info" role="status" style="margin-bottom:18px;">
        <i class="mdi mdi-eye-outline" aria-hidden="true"></i>
        <strong>Read-only Auditor access.</strong> You can review and print accounting records, but you cannot add, edit, delete, or send transactions.
    </div>
<?php endif; ?>
