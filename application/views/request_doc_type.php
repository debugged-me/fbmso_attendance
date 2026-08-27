<!DOCTYPE html>
<html lang="en">

<?php include('includes/head.php'); ?>

    <body>

        <!-- Begin page -->
        <div id="wrapper">
            
            <!-- Topbar Start -->
				<?php include('includes/top-nav-bar.php'); ?>
            <!-- end Topbar --> <!-- ========== Left Sidebar Start ========== -->

<!-- Lef Side bar -->
<?php include('includes/sidebar.php'); ?>
<!-- Left Sidebar End -->

            <!-- ============================================================== -->
            <!-- Start Page Content here -->
            <!-- ============================================================== -->

            <div class="content-page">
                <div class="content">

                    <!-- Start Content-->
                    <div class="container-fluid">

                        <!-- start page title -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="page-title-box">
                                    <!-- <h4 class="page-title">Masterlist</h4> -->
                                     <button class="btn btn-success mb-3" data-toggle="modal" data-target="#addTypeModal">Add New</button>
                                    <div class="page-title-right">
                                         <div class="page-title-right">
                                        <ol class="breadcrumb p-0 m-0">
                                            <!-- <li class="breadcrumb-item"><a href="#">Currently login to <b>SY <?php echo $this->session->userdata('sy');?> <?php echo $this->session->userdata('semester');?></b></a></li> -->
                                        </ol>
                                    </div>
                                    </div>
                                    <div class="clearfix"></div>
                                </div>
                            </div>
                        </div>
                        <!-- end page title -->
						<div class="row">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-body table-responsive">
                                      
										<h4 class="m-t-0 header-title mb-4"><strong>Manage Document Types</strong></h4>
									
                                        <?php if ($this->session->flashdata('msg')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong><i class="mdi mdi-check-circle-outline"></i> Success!</strong>
        <?= $this->session->flashdata('msg'); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
<?php endif; ?>

<table class="table table-bordered table-striped mb-0">
  <thead>
    <tr>
      <th>Name</th>
      <th>Description</th>
      <th>Office</th> <!-- NEW -->
      <th>Status</th>
      <th>Action</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($types as $t): ?>
    <tr>
      <td><?= $t->document_name ?></td>
      <td><?= $t->description ?></td>
      <td>
        <span class="badge badge-info"><?= isset($t->office) ? $t->office : 'Registrar' ?></span> <!-- NEW -->
      </td>
      <td>
        <span class="badge badge-<?= $t->is_active ? 'success' : 'secondary' ?>">
          <?= $t->is_active ? 'Active' : 'Inactive' ?>
        </span>
      </td>
      <td>
        <button class="btn btn-sm btn-info" data-toggle="modal" data-target="#editModal<?= $t->id ?>">Edit</button>
        <a href="<?= base_url('request/delete_document_type/' . $t->id) ?>"
           class="btn btn-sm btn-danger"
           data-ui-confirm="Requests already made with this type keep it; it just stops being offered."
           data-ui-confirm-title="Delete this document type?"
           data-ui-confirm-ok="Delete type">Delete</a>
      </td>
    </tr>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal<?= $t->id ?>">
      <div class="modal-dialog">
       <form method="post" action="<?= base_url('request/update_document_type/' . $t->id) ?>">
  <div class="modal-content">
    <div class="modal-header"><h5>Edit Document Type</h5></div>
    <div class="modal-body">
      <div class="form-group">
        <label>Document Name</label>
        <input type="text" name="document_name" value="<?= $t->document_name ?>" class="form-control" required>
      </div>

      <div class="form-group">
        <label>Description</label>
        <textarea name="description" class="form-control"><?= $t->description ?></textarea>
      </div>

      <!-- Office field -->
      <?php if (($user_dept ?? 'Student') === 'Admin'): ?>
        <div class="form-group">
          <label>Office</label>
          <select name="office" class="form-control" required>
            <?php foreach (['Registrar','Accounting','Admin'] as $o): ?>
              <option value="<?= $o ?>" <?= (($t->office ?? 'Registrar') === $o) ? 'selected' : '' ?>>
                <?= $o ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php else: ?>
        <div class="form-group">
          <label>Office</label><br>
          <span class="badge badge-info"><?= html_escape($user_dept ?? 'Registrar') ?></span>
          <input type="hidden" name="office" value="<?= html_escape($user_dept ?? 'Registrar') ?>">
        </div>
      <?php endif; ?>

      <div class="form-check">
        <input type="checkbox" name="is_active" <?= $t->is_active ? 'checked' : '' ?> class="form-check-input" id="active<?= $t->id ?>">
        <label class="form-check-label" for="active<?= $t->id ?>">Active</label>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-primary">Save</button>
      <button class="btn btn-secondary" data-dismiss="modal" type="button">Cancel</button>
    </div>
  </div>
</form>
</div>
</div>
<?php endforeach; ?>
</tbody>
</table>

<!-- Add Modal -->
<div class="modal fade" id="addTypeModal">
  <div class="modal-dialog">
    <form method="post" action="<?= base_url('request/save_document_type') ?>">
      <div class="modal-content">
        <div class="modal-header"><h5>Add Document Type</h5></div>
        <div class="modal-body">
          <div class="form-group">
            <label>Document Name</label>
            <input type="text" name="document_name" class="form-control" required>
          </div>

          <div class="form-group">
            <label>Description</label>
            <textarea name="description" class="form-control"></textarea>
          </div>

          <!-- Office field -->
          <?php if (($user_dept ?? 'Student') === 'Admin'): ?>
            <div class="form-group">
              <label>Office</label>
              <select name="office" class="form-control" required>
                <option value="Registrar">Registrar</option>
                <option value="Accounting">Accounting</option>
                <option value="Admin">Admin</option>
              </select>
            </div>
          <?php else: ?>
            <div class="form-group">
              <label>Office</label><br>
              <span class="badge badge-info"><?= html_escape($user_dept ?? 'Registrar') ?></span>
              <input type="hidden" name="office" value="<?= html_escape($user_dept ?? 'Registrar') ?>">
            </div>
          <?php endif; ?>

          <div class="form-check">
            <input type="checkbox" name="is_active" class="form-check-input" id="addActive" checked>
            <label class="form-check-label" for="addActive">Active</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Add New</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        </div>
      </div>
    </form>
  </div>
</div>

									</div>
									</div>
					
                                </div><!-- /.box-body -->

                        </div>

					

                        </div>
                        <!-- End row -->

                    </div>
                    <!-- end container-fluid -->

                </div>
                <!-- end content -->

                

                <!-- Footer Start -->
					<?php include('includes/footer.php'); ?>
                <!-- end Footer -->

            </div>

            <!-- ============================================================== -->
            <!-- End Page content -->
            <!-- ============================================================== -->

        </div>
        <!-- END wrapper -->

        
        <!-- Right Sidebar -->
			<?php include('includes/themecustomizer.php'); ?>
        <!-- /Right-bar -->


        <!-- Vendor js -->
        <script src="<?= base_url(); ?>assets/js/vendor.min.js"></script>

        <script src="<?= base_url(); ?>assets/libs/moment/moment.min.js"></script>
        <script src="<?= base_url(); ?>assets/libs/jquery-scrollto/jquery.scrollTo.min.js"></script>
        <script src="<?= base_url(); ?>assets/libs/sweetalert2/sweetalert2.min.js"></script>

        <!-- Chat app -->
        <script src="<?= base_url(); ?>assets/js/pages/jquery.chat.js"></script>

        <!-- Todo app -->
        <script src="<?= base_url(); ?>assets/js/pages/jquery.todo.js"></script>

        <!--Morris Chart-->
        <script src="<?= base_url(); ?>assets/libs/morris-js/morris.min.js"></script>
        <script src="<?= base_url(); ?>assets/libs/raphael/raphael.min.js"></script>

        <!-- Sparkline charts -->
        <script src="<?= base_url(); ?>assets/libs/jquery-sparkline/jquery.sparkline.min.js"></script>

        <!-- Dashboard init JS -->
        <script src="<?= base_url(); ?>assets/js/pages/dashboard.init.js"></script>

        <!-- App js -->
        <script src="<?= base_url(); ?>assets/js/app.min.js"></script>

        <!-- Required datatable js -->
        <script src="<?= base_url(); ?>assets/libs/datatables/jquery.dataTables.min.js"></script>
        <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.bootstrap4.min.js"></script>
        <!-- Buttons examples -->
        <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.buttons.min.js"></script>
        <script src="<?= base_url(); ?>assets/libs/datatables/buttons.bootstrap4.min.js"></script>
        <script defer src="<?= base_url(); ?>assets/libs/jszip/jszip.min.js"></script>
        <script defer src="<?= base_url(); ?>assets/libs/pdfmake/pdfmake.min.js"></script>
        <script defer src="<?= base_url(); ?>assets/libs/pdfmake/vfs_fonts.js"></script>
        <script defer src="<?= base_url(); ?>assets/libs/datatables/buttons.html5.min.js"></script>
        <script defer src="<?= base_url(); ?>assets/libs/datatables/buttons.print.min.js"></script>

        <!-- Responsive examples -->
        <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.responsive.min.js"></script>
        <script src="<?= base_url(); ?>assets/libs/datatables/responsive.bootstrap4.min.js"></script>

        <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.keyTable.min.js"></script>
        <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.select.min.js"></script>

        <!-- Datatables init -->
        <script src="<?= base_url(); ?>assets/js/pages/datatables.init.js"></script>
		
    </body>

    <!-- Add Modal -->
<div class="modal fade" id="addTypeModal">
    <div class="modal-dialog">
        <form method="post" action="<?= base_url('request/save_document_type') ?>">
            <div class="modal-content">
                <div class="modal-header"><h5>Add Document Type</h5></div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Document Name</label>
                        <input type="text" name="document_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control"></textarea>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" checked>
                        <label class="form-check-label">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-success">Add</button>
                    <button class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                </div>
            </div>
        </form>
    </div>
</div>
</html>