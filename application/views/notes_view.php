<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>


<!-- Bootstrap CSS (required by Summernote) -->
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

<!-- Font Awesome (for icons) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

<!-- Summernote CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-bs4.min.css">


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
                                <div class="clearfix"></div>
                                <hr style="border:0; height:2px; background:linear-gradient(to right, #4285F4 60%, #FBBC05 80%, #34A853 100%); border-radius:1px; margin:20px 0;" />
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">

                            <div class="card">
                                <div class="card-body">
                                    <h3 class="mb-4">My Notes</h3>

                                    <?php if ($this->session->flashdata('success')): ?>
                                        <div class="alert alert-success"><?= $this->session->flashdata('success'); ?></div>
                                    <?php endif; ?>

                                    <?php if ($action == 'index'): ?>
                                        <a href="<?= site_url('note/create') ?>" class="btn btn-primary mb-3">Create New Note</a>

                                        <?php if (!empty($notes)): ?>
                                            <?php foreach ($notes as $note): ?>
                                                <div class="card mb-3">
                                                    <div class="card-body">
                                                        <h4><?= htmlspecialchars($note->title) ?></h4>
                                                        <p><?= strip_tags(word_limiter($note->content, 50)) ?></p>
                                                        <small class="text-muted">Created on: <?= date('F j, Y, g:i a', strtotime($note->created_at)) ?></small>
                                                        <div class="mt-2">
                                                            <a href="<?= site_url('note/edit/' . $note->id) ?>" class="btn btn-sm btn-warning">Edit</a>
                                                            <a href="<?= site_url('note/delete/' . $note->id) ?>" class="btn btn-sm btn-danger"
                                                                                data-ui-confirm="This note is removed permanently."
                                                                                data-ui-confirm-title="Delete this note?"
                                                                                data-ui-confirm-ok="Delete note">Delete</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p>No notes found. <a href="<?= site_url('note/create') ?>">Create one</a>.</p>
                                        <?php endif; ?>

                                    <?php elseif ($action == 'create' || $action == 'edit'): ?>
                                        <h4><?= ($action == 'create') ? 'Create New Note' : 'Edit Note' ?></h4>

                                        <form action="<?= ($action == 'create') ? site_url('note/store') : site_url('note/update/' . $note->id) ?>" method="POST">
                                            <div class="mb-3">
                                                <label for="title" class="form-label">Title</label>
                                                <input type="text" class="form-control" id="title" name="title" required value="<?= ($action == 'edit') ? htmlspecialchars($note->title) : '' ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label for="content" class="form-label">Content</label>
                                                <textarea id="content" name="content" class="form-control" rows="6"><?= ($action == 'edit') ? htmlspecialchars($note->content) : '' ?></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-success"><?= ($action == 'create') ? 'Save Note' : 'Update Note' ?></button>
                                            <a href="<?= site_url('note') ?>" class="btn btn-secondary">Back</a>
                                        </form>
                                    <?php endif; ?>

                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>

            <?php include('includes/footer.php'); ?>
        </div>
    </div>

    <?php include('includes/themecustomizer.php'); ?>

    <!-- Scripts -->
    <script src="<?= base_url(); ?>assets/js/vendor.min.js"></script>
    <script src="<?= base_url(); ?>assets/js/app.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/summernote/summernote-bs4.min.js"></script>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>

    <!-- Popper.js and Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <!-- Summernote JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-bs4.min.js"></script>


    <script>
        $(document).ready(function() {
            $('#content').summernote({
                height: 250,
                placeholder: 'Write your note here...',
                toolbar: [
                    ['style', ['bold', 'italic', 'underline', 'clear']],
                    ['font', ['fontsize']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link']],
                    ['view', ['fullscreen', 'codeview']]
                ]
            });
        });
    </script>
</body>

</html>