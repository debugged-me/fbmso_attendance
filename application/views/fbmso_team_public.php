<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>

<link rel="stylesheet" href="<?= base_url('assets/css/request-bell.css'); ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/public.css'); ?>">
<script src="<?= base_url('assets/js/req-bell.js?v=2'); ?>"></script>


<body>
    <div id="wrapper">
        <?php include('includes/top-nav-bar.php'); ?>
        <?php include('includes/sidebar.php'); ?>

        <div class="content-page">
            <div class="content">
                <div class="container-fluid">

                    <!-- Header -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box page-title-box-white">
                                <div>
                                    <h4 class="page-title">
                                        <?= htmlspecialchars($data18[0]->SchoolName ?? '') ?><br>
                                        <small><?= htmlspecialchars($data18[0]->SchoolAddress ?? '') ?></small>
                                    </h4>
                                </div>
                                <div class="page-title-right">
                                    <span class="page-tag">FBMSO Officials</span>
                                </div>
                            </div>
                            <hr class="fbmso-hr">
                        </div>
                    </div>

                    <!-- People (read-only) -->
                    <div class="people-grid">
                        <?php foreach ($people as $p): ?>
                            <?php
                            $id      = (int)$p->id;
                            $full    = (string)($p->bio ?? '');
                            $plain   = trim(strip_tags($full));
                            $isLong  = mb_strlen($plain) > 120;
                            $modalId = 'personViewPublic' . $id;
                            ?>
                            <div class="person-card">
                                <div class="person-avatar">
                                    <div class="thumb">
                                        <img src="<?= base_url('upload/banners/' . ($p->photo ?: 'placeholder.png')) ?>" alt="">
                                    </div>
                                </div>

                                <div class="person-header">
                                    <h6 class="name"><?= html_escape($p->full_name) ?></h6>
                                    <div class="title"><?= html_escape($p->title) ?></div>
                                </div>

                                <div class="person-body">
                                    <div class="bio"><?= nl2br(html_escape($full)) ?></div>
                                    <?php if ($isLong): ?>
                                        <a href="#<?= $modalId ?>" class="see-more" data-toggle="modal">See more</a>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- See more modal -->
                            <div class="modal fade" id="<?= $modalId ?>" tabindex="-1" role="dialog" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header sheet">
                                            <h5 class="modal-title">
                                                <?= html_escape($p->full_name) ?> — <?= html_escape($p->title) ?>
                                            </h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body vscroll">
                                            <div class="row">
                                                <div class="col-md-4 mb-3 mb-md-0">
                                                    <img class="img-fluid rounded" src="<?= base_url('upload/banners/' . ($p->photo ?: 'placeholder.png')) ?>" alt="">
                                                </div>
                                                <div class="col-md-8">
                                                    <div class="bio-full"><?= nl2br(html_escape($full)) ?></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button class="btn btn-secondary" data-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div><!-- /people-grid -->

                </div>
            </div>
            <?php include('includes/footer.php'); ?>
        </div>
    </div>

    <?php include('includes/themecustomizer.php'); ?>
    <script src="<?= base_url('assets/js/vendor.min.js'); ?>"></script>
    <script src="<?= base_url('assets/js/app.min.js'); ?>"></script>
</body>

</html>