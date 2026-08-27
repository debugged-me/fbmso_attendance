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
                                <!-- <h4 class="page-title">Submit Request</h4> -->
                                <div class="page-title-right">
                                    <ol class="breadcrumb p-0 m-0">
                                        <!-- <li class="breadcrumb-item"><a href="#">Currently login to <b>SY <?php echo $this->session->userdata('sy'); ?> <?php echo $this->session->userdata('semester'); ?></b></a></li> -->
                                    </ol>
                                </div>
                                <div class="clearfix"></div>
                                <hr style="border:0; height:2px; background:linear-gradient(to right, #4285F4 60%, #FBBC05 80%, #34A853 100%); border-radius:1px; margin:20px 0;" />
                            </div>
                        </div>
                    </div>

                    <!-- end page title -->
                    <div class="row">
                        <div class="col-md-12">

                            <div class="card">
                                <div class="card-body table-responsive">
                                    <!--<h4 class="m-t-0 header-title mb-4"><b>REQUEST SUBMISSION</b></h4>-->

                                    <div class="row">
                                        <div class="col-lg-12">
                                            <h3 class="mb-4">My ToDo Lists</h3>

                                            <?php if ($this->session->flashdata('success')): ?>
                                                <div class="alert alert-success"><?= $this->session->flashdata('success'); ?></div>
                                            <?php endif; ?>

                                            <div class="mb-4">
                                                <form name="todo-form" action="<?= site_url('ToDo/add') ?>" method="post" class="mt-3">
                                                    <div class="form-inline">
                                                        <!-- Task Input -->
                                                        <div class="form-group mb-2">
                                                            <label for="todo-input-text" class="mr-2">Task</label>
                                                            <input type="text" id="todo-input-text" name="task" class="form-control" required placeholder="Add new todo" style="width: 500px; height: 40px;">
                                                        </div>

                                                        <!-- Due Date Input with default set to today's date -->
                                                        <div class="form-group mb-2 ml-3">
                                                            <label for="due_date" class="mr-2">Due Date</label>
                                                            <input type="date" id="due_date" name="due_date" class="form-control" required placeholder="Due date" value="<?= date('Y-m-d') ?>">
                                                        </div>

                                                        <!-- Submit Button -->
                                                        <div class="form-group mb-2 ml-3">
                                                            <button class="btn btn-info" type="submit">Add Task</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>

                                            <table class="table mb-0">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Task</th>
                                                        <th>Status</th>
                                                        <th>Created Date</th>
                                                        <th>Due Date</th>
                                                        <th>Completed Date</th>
                                                        <th style="text-align: center;">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (!empty($todos)): ?>
                                                        <?php
                                                        $count = 1;
                                                        $doneCount = 0;
                                                        $pendingCount = 0;
                                                        $today = strtotime(date('Y-m-d')); // Today at 00:00:00
                                                        ?>
                                                        <?php foreach ($todos as $todo): ?>
                                                            <?php
                                                            $dueDate = strtotime(date('Y-m-d', strtotime($todo->due_date))); // Normalize to date only
                                                            $is_overdue = !$todo->is_done && $dueDate < $today;

                                                            if ($todo->is_done) {
                                                                $doneCount++;
                                                            } else {
                                                                $pendingCount++;
                                                            }

                                                            // Determine row color
                                                            $rowClass = $todo->is_done
                                                                ? 'table-success completed-task'
                                                                : ($is_overdue ? 'table-danger' : '');
                                                            ?>
                                                            <tr class="<?= $rowClass ?>">
                                                                <td><?= $count++ ?></td>
                                                                <td data-toggle="tooltip" data-placement="top"
                                                                    title="<?= htmlspecialchars($todo->comment ?? ($is_overdue ? 'This task is overdue' : 'No comment')) ?>">
                                                                    <?= htmlspecialchars($todo->task) ?>
                                                                </td>
                                                                <td>
                                                                    <?= $todo->is_done
                                                                        ? '<span class="badge badge-success">Done</span>'
                                                                        : '<span class="badge badge-warning">Pending</span>' ?>
                                                                </td>
                                                                <td><?= date('M d, Y h:i A', strtotime($todo->created_at)) ?></td>
                                                                <td><?= date('M d, Y', strtotime($todo->due_date)) ?></td>
                                                                <td><?= $todo->completed_at ? date('M d, Y h:i A', strtotime($todo->completed_at)) : 'Not yet done' ?></td>
                                                                <td style="text-align: center;">
                                                                    <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#commentModal<?= $todo->id ?>">
                                                                        Comment
                                                                    </button>

                                                                    <?php if (!$todo->is_done): ?>
                                                                        <a href="<?= site_url('ToDo/mark_done/' . $todo->id) ?>" class="btn btn-sm btn-success">Mark as Done</a>
                                                                        <a href="<?= site_url('ToDo/delete/' . $todo->id) ?>" class="btn btn-sm btn-danger"
                                                                            data-ui-confirm="The task is removed from your list. This cannot be undone."
                                                                            data-ui-confirm-title="Delete this task?"
                                                                            data-ui-confirm-ok="Delete task">Delete</a>
                                                                    <?php else: ?>
                                                                        <a href="<?= site_url('ToDo/mark_undone/' . $todo->id) ?>" class="btn btn-sm btn-warning">Mark as Undone</a>
                                                                    <?php endif; ?>

                                                                    <a href="#" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#editTaskModal<?= $todo->id ?>">Edit</a>
                                                                </td>

                                                            </tr>
                                                        <?php endforeach; ?>

                                                        <!-- Summary row -->
                                                        <tr>
                                                            <td colspan="7" class="text-right">
                                                                <strong>Pending:</strong> <?= $pendingCount ?> |
                                                                <strong>Done:</strong> <?= $doneCount ?>
                                                            </td>
                                                        </tr>
                                                    <?php else: ?>
                                                        <tr>
                                                            <td colspan="7" class="text-center">No tasks found.</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>



                                            <!-- Button to Hide Completed Tasks -->
                                            <div class="mt-4 text-right">
                                                <button id="toggle-completed-btn" class="btn btn-primary" onclick="toggleCompletedTasks()">Hide All Completed Tasks</button>

                                            </div>
                                        </div>
                                    </div>

                                </div>

                            </div>
                        </div>
                    </div>
                </div>
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

    <?php foreach ($todos as $todo): ?>
        <!-- Comment Modal -->
        <div class="modal fade" id="commentModal<?= $todo->id ?>" tabindex="-1" role="dialog" aria-labelledby="commentModalLabel<?= $todo->id ?>" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <form action="<?= site_url('ToDo/add_comment/' . $todo->id) ?>" method="post">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="commentModalLabel<?= $todo->id ?>">Add Comment</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <textarea name="comment" class="form-control" rows="4" placeholder="Enter comment here...">
<?= isset($todo->comment) ? htmlspecialchars($todo->comment) : '' ?>
</textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Comment</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php endforeach; ?>


    <?php foreach ($todos as $todo): ?>
        <!-- Edit Modal -->
        <div class="modal fade" id="editTaskModal<?= $todo->id ?>" tabindex="-1" role="dialog" aria-labelledby="editTaskModalLabel<?= $todo->id ?>" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <form action="<?= site_url('ToDo/edit/' . $todo->id) ?>" method="post">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editTaskModalLabel<?= $todo->id ?>">Edit Task</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="task<?= $todo->id ?>">Task</label>
                                <input type="text" class="form-control" id="task<?= $todo->id ?>" name="task" value="<?= htmlspecialchars($todo->task) ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="due_date<?= $todo->id ?>">Due Date</label>
                                <input type="date" class="form-control" id="due_date<?= $todo->id ?>" name="due_date" value="<?= $todo->due_date ?>" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success">Save Changes</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php endforeach; ?>




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
    <script src="<?= base_url(); ?>assets/libs/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
    <!-- Responsive examples -->
    <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.responsive.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/responsive.bootstrap4.min.js"></script>

    <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.keyTable.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.select.min.js"></script>

    <!-- Datatables init -->
    <script src="<?= base_url(); ?>assets/js/pages/datatables.init.js"></script>

    <!-- Script to hide completed tasks -->
    <script>
        let completedVisible = true;

        function toggleCompletedTasks() {
            const completedTasks = document.querySelectorAll('.completed-task');
            const toggleBtn = document.getElementById('toggle-completed-btn');

            completedTasks.forEach(task => {
                task.style.display = completedVisible ? 'none' : '';
            });

            // Toggle button text
            toggleBtn.textContent = completedVisible ? 'Show All Completed Tasks' : 'Hide All Completed Tasks';

            // Flip the flag
            completedVisible = !completedVisible;
        }
    </script>

    <script>
        $(function() {
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>




</body>

</html>