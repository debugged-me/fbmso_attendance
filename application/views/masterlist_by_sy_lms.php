<!DOCTYPE html>
<html lang="en">

<?php include('includes/head.php'); ?>

<body class="masterlist-page">

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
                                <h4 class="page-title">Masterlist By Semester For LMS Bulk Uploading<br />
                                    <span class="badge badge-primary mb-3"><?php echo $this->session->userdata('semester'); ?>, SY <?php echo $this->session->userdata('sy'); ?></span>
                                </h4>
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

                    <div class="row">
                        <div class="col-sm-12">
                            <div class="card-box">

                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h5 class="mb-0">Student Credentials</h5>
                                    <button type="button" id="exportCsvBtn" class="btn btn-success btn-sm">Export CSV</button>
                                </div>

                                <table class="table" id="studentsTable">
                                    <thead>
                                        <tr>
                                            <th>Student No.</th>
                                            <th>First Name</th>
                                            <th>Last Name</th>
                                            <th>Email</th>
                                            <th>Password</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // --- keep your existing data query ---
                                        // Password generator (min length 10, Moodle-friendly)
                                        function generate_moodle_password($length = 10)
                                        {
                                            $length = max(10, (int)$length);
                                            $upper    = chr(random_int(65, 90));
                                            $lower    = chr(random_int(97, 122));
                                            $digit    = chr(random_int(48, 57));
                                            $specials = '!@#$%^&*()-_=+[]{}:,.?';
                                            $special  = $specials[random_int(0, strlen($specials) - 1)];
                                            $pool     = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789' . $specials;
                                            $remaining = '';
                                            for ($i = 0; $i < $length - 4; $i++) {
                                                $remaining .= $pool[random_int(0, strlen($pool) - 1)];
                                            }
                                            return str_shuffle($upper . $lower . $digit . $special . $remaining);
                                        }

                                        foreach ($data as $row) {
                                            $password = generate_moodle_password(10);
                                            echo "<tr>";
                                            echo "<td>" . htmlspecialchars($row->StudentNumber) . "</td>";
                                            echo "<td>" . htmlspecialchars($row->FirstName) . "</td>";
                                            echo "<td>" . htmlspecialchars($row->LastName) . "</td>";
                                            echo "<td>" . htmlspecialchars($row->email) . "</td>";
                                            echo "<td>" . htmlspecialchars($password) . "</td>";
                                            echo "</tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>

                                <script>
                                    (function() {
                                        const btn = document.getElementById('exportCsvBtn');
                                        const table = document.getElementById('studentsTable');

                                        function cellText(td) {
                                            return (td?.innerText || '').replace(/\r?\n|\r/g, ' ').trim();
                                        }

                                        function escapeCsv(text) {
                                            return /[",\n]/.test(text) ? '"' + text.replace(/"/g, '""') + '"' : text;
                                        }

                                        btn.addEventListener('click', function() {
                                            const tbody = table.querySelector('tbody');
                                            const rows = Array.from(tbody.querySelectorAll('tr'));

                                            // Required CSV headers (lowercase as requested)
                                            const header = ['username', 'firstname', 'lastname', 'email', 'password'];

                                            const dataLines = rows.map(tr => {
                                                const tds = Array.from(tr.querySelectorAll('td'));
                                                // Map table columns -> CSV columns:
                                                // 0: StudentNumber -> username
                                                // 1: FirstName     -> firstname
                                                // 2: LastName      -> lastname
                                                // 3: email         -> email
                                                // 4: Password      -> password
                                                const cols = [0, 1, 2, 3, 4].map(i => escapeCsv(cellText(tds[i])));
                                                return cols.join(',');
                                            });

                                            const csv = [header.join(','), ...dataLines].join('\r\n');

                                            // Download with UTF-8 BOM for Excel
                                            const blob = new Blob(["\uFEFF" + csv], {
                                                type: 'text/csv;charset=utf-8;'
                                            });
                                            const url = URL.createObjectURL(blob);
                                            const a = document.createElement('a');
                                            const ts = new Date().toISOString().replace(/[-:T]/g, '').slice(0, 15);
                                            a.href = url;
                                            a.download = `students_${ts}.csv`;
                                            document.body.appendChild(a);
                                            a.click();
                                            URL.revokeObjectURL(url);
                                            a.remove();
                                        });
                                    })();
                                </script>




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
    <script src="<?= base_url(); ?>assets/js/pages/dashboard.init.js?v=2"></script>

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
    <!-- Vendor js -->
    <script src="<?= base_url(); ?>assets/js/vendor.min.js"></script>

    <!-- Responsive Table js -->
    <script src="<?= base_url(); ?>assets/libs/rwd-table/rwd-table.min.js"></script>

    <!-- Init js -->
    <script src="<?= base_url(); ?>assets/js/pages/responsive-table.init.js"></script>

    <!-- App js -->
    <script src="<?= base_url(); ?>assets/js/app.min.js"></script>
</body>

</html>

