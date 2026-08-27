<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Attendance Portal — Birthday Celebrants</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Responsive bootstrap 4 admin template" name="description" />
    <meta content="Coderthemes" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="shortcut icon" href="<?= base_url(); ?>upload\banners\logo1.png">
    <link href="<?= base_url(); ?>assets/libs/sweetalert2/sweetalert2.min.css" rel="stylesheet" type="text/css" />

    <!-- App css -->
    <link href="<?= base_url(); ?>assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" id="bootstrap-stylesheet" />
    <link href="<?= base_url(); ?>assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="<?= base_url(); ?>assets/css/app.min.css" rel="stylesheet" type="text/css" id="app-stylesheet" />

    <!-- DataTables css -->
    <link href="<?= base_url(); ?>assets/libs/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="<?= base_url(); ?>assets/libs/datatables/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="<?= base_url(); ?>assets/libs/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="<?= base_url(); ?>assets/libs/datatables/select.bootstrap4.min.css" rel="stylesheet" type="text/css" />

    <style>
        .avatar-sm {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid rgba(0, 0, 0, .08);
        }

        .table thead th {
            white-space: nowrap;
        }

        .dt-buttons .btn {
            margin-right: 6px;
        }
    </style>
</head>

<body>
    <div id="wrapper">
        <?php include('includes/top-nav-bar.php'); ?>
        <?php include('includes/sidebar.php'); ?>

        <div class="content-page">
            <div class="content">
                <div class="container-fluid">

                    <div class="row">
                        <div class="col-md-12">
                            <div class="page-title-box">
                                <h4 class="page-title">🎂 Birthday Celebrants — This Month</h4>
                                <div class="page-title-right">
                                    <ol class="breadcrumb p-0 m-0">
                                        <li class="breadcrumb-item">
                                            <a href="#">Currently login to <b>SY <?= $this->session->userdata('sy'); ?> <?= $this->session->userdata('semester'); ?></b></a>
                                        </li>
                                    </ol>
                                </div>
                                <div class="clearfix"></div>
                                <hr style="border:0; height:2px; background:linear-gradient(to right, #4285F4 60%, #FBBC05 80%, #34A853 100%); border-radius:1px; margin:20px 0;" />
                            </div>
                        </div>
                    </div>

                    <?php $students = $students ?? []; ?>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body table-responsive">
                                    <h5 class="mb-3">List of celebrants for <span class="text-primary"><?= date('F Y'); ?></span></h5>

                                    <?php if (empty($students)): ?>
                                        <div class="alert alert-secondary mb-0">
                                            No birthday celebrants this month.
                                        </div>
                                    <?php else: ?>
                                        <table id="datatable-bday-month" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%;">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Student No.</th>
                                                    <th>Full Name</th>
                                                    <th>Sex</th>
                                                    <th>Birth Date</th>
                                                    <th>Age</th>
                                                    <th>Year Level</th>
                                                    <th>Section</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $i = 1;
                                                foreach ($students as $r):
                                                    $full = trim("{$r->LastName}, {$r->FirstName} " . trim(($r->MiddleName ?? '') . ' ' . ($r->nameExtn ?? '')));
                                                    $img  = base_url('upload/profile/' . ($r->imagePath ?: ''));
                                                    $age  = (int)($r->AgeNow ?? $r->age ?? 0);
                                                    $bday = $r->birthDate ? date('M d, Y', strtotime($r->birthDate)) : '';
                                                    $day  = $r->birthDate ? date('d', strtotime($r->birthDate)) : '';
                                                ?>
                                                    <tr>
                                                        <td><?= $i++; ?></td>

                                                        <td><?= html_escape($r->StudentNumber); ?></td>
                                                        <td><?= html_escape($full); ?></td>
                                                        <td><?= html_escape($r->Sex ?? ''); ?></td>
                                                        <td><?= $bday ?: '—'; ?></td>
                                                        <td><?= $age ?: '—'; ?></td>
                                                        <td><?= html_escape($r->yearLevel ?? ''); ?></td>
                                                        <td><?= html_escape($r->section ?? ''); ?></td>
                                                        <td><?= html_escape($r->email ?? ''); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php endif; ?>

                                </div>
                            </div>
                        </div>
                    </div>

                </div> <!-- container-fluid -->
            </div> <!-- content -->

            <?php include('includes/footer.php'); ?>
        </div> <!-- content-page -->
    </div> <!-- wrapper -->

    <?php include('includes/themecustomizer.php'); ?>

    <!-- Vendor js -->
    <script src="<?= base_url(); ?>assets/js/vendor.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/moment/moment.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/jquery-scrollto/jquery.scrollTo.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/sweetalert2/sweetalert2.min.js"></script>
    <script src="<?= base_url(); ?>assets/js/pages/jquery.chat.js"></script>
    <script src="<?= base_url(); ?>assets/js/pages/jquery.todo.js"></script>
    <script src="<?= base_url(); ?>assets/libs/morris-js/morris.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/raphael/raphael.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/jquery-sparkline/jquery.sparkline.min.js"></script>
    <script src="<?= base_url(); ?>assets/js/pages/dashboard.init.js"></script>
    <script src="<?= base_url(); ?>assets/js/app.min.js"></script>

    <!-- DataTables -->
    <script src="<?= base_url(); ?>assets/libs/datatables/jquery.dataTables.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.buttons.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/buttons.bootstrap4.min.js"></script>
    <script defer src="<?= base_url(); ?>assets/libs/jszip/jszip.min.js"></script>
    <script defer src="<?= base_url(); ?>assets/libs/pdfmake/pdfmake.min.js"></script>
    <script defer src="<?= base_url(); ?>assets/libs/pdfmake/vfs_fonts.js"></script>
    <script defer src="<?= base_url(); ?>assets/libs/datatables/buttons.html5.min.js"></script>
    <script defer src="<?= base_url(); ?>assets/libs/datatables/buttons.print.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.responsive.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/responsive.bootstrap4.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.keyTable.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.select.min.js"></script>

    <script>
        $(function() {
            var dt = $('#datatable-bday-month').DataTable({
                pageLength: 25,
                responsive: true,
                order: [
                    [2, 'asc'],
                    [4, 'asc']
                ], // day then name
                columnDefs: [{
                        targets: [1],
                        orderable: false,
                        searchable: false
                    }, // photo
                    {
                        targets: [0],
                        searchable: false
                    }, // row #
                ],
                dom: 'Bfrtip',
                buttons: [{
                        extend: 'excelHtml5',
                        title: 'Birthday_Celebrants_This_Month'
                    },
                    {
                        extend: 'print',
                        title: 'Birthday Celebrants — This Month'
                    }
                ]
            });
        });
    </script>
</body>

</html>