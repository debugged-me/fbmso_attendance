<!-- . vendor.min.js includes jQuery 3.4.1 + Bootstrap 4 + Popper — no need for separate CDN loads -->
<script src="<?= base_url(); ?>assets/js/vendor.min.js"></script>
<script src="<?= base_url(); ?>assets/js/app.min.js"></script>

<!-- . Other Libraries -->
<script src="<?= base_url(); ?>assets/libs/moment/moment.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/jquery-scrollto/jquery.scrollTo.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/sweetalert2/sweetalert2.min.js"></script>
<script src="<?= base_url(); ?>assets/js/sweetalert.min.js"></script>

<!-- . DataTables -->
<script src="<?= base_url(); ?>assets/libs/datatables/jquery.dataTables.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/datatables/dataTables.bootstrap4.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/datatables/dataTables.buttons.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/datatables/buttons.bootstrap4.min.js"></script>
<!-- Export libraries deferred — 2 MB+ of pdfmake/vfs_fonts/jszip loads in parallel without blocking render.
     Deferred scripts execute in order before DOMContentLoaded, so buttons.html5 has pdfmake/jszip ready. -->
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

<!-- . Plugins -->
<script src="<?= base_url(); ?>assets/libs/bootstrap-tagsinput/bootstrap-tagsinput.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/switchery/switchery.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/select2/select2.min.js"></script>
<script src="<?= base_url(); ?>assets/js/pages/form-advanced.init.js"></script>

<script>
  window.APP = window.APP || {};
  APP.baseUrl = '<?= base_url(); ?>';
  APP.req = {
    count: '<?= base_url('request/ajax_pending_count'); ?>',
    list: '<?= base_url('request/ajax_pending_list'); ?>',
    markSeen: '<?= base_url('request/ajax_mark_seen'); ?>',
    index: '<?= base_url('request'); ?>'
  };
</script>


<!-- . Bootstrap Dropdown Activation -->
<script>
  $(document).ready(function() {
    $('.dropdown-toggle').dropdown(); // Make sure dropdowns work
  });
</script>