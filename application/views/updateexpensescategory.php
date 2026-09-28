<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>

<?php
$e = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
$category = !empty($data) ? $data[0] : null;
?>

<body>
  <div id="wrapper">
    <?php include('includes/top-nav-bar.php'); ?>
    <?php include('includes/sidebar.php'); ?>

    <link rel="stylesheet" href="<?= base_url('assets/css/uniform-page.css?v=2026092802'); ?>">

    <div class="content-page">
      <div class="content">
        <div class="container-fluid">

          <div class="row">
            <div class="col-12">
              <div class="page-title-box">
                <h4 class="up-page-title">Edit Expense Category</h4>
                <div class="up-page-sub">Changes are recorded in the audit trail with the values before and after.</div>
                <hr class="up-divider" />
              </div>
            </div>
          </div>

          <?php if (!$category): ?>
            <div class="up-flash up-flash-danger">That category no longer exists.</div>
            <a href="<?= base_url('Accounting/expensescategory'); ?>" class="up-btn up-btn-ghost"><i class="mdi mdi-arrow-left"></i> Back to Categories</a>
          <?php else: ?>
          <div class="row">
            <div class="col-12">
              <div class="up-card">
                <div class="up-card-head">
                  <h4><i class="mdi mdi-shape-outline"></i> <?= $e($category->Category); ?></h4>
                </div>
                <div class="up-card-body">
                  <form method="post" action="<?= base_url('Accounting/updateexpensescategory?categoryID=' . (int)$category->categoryID); ?>">
                    <div class="form-group">
                      <label for="Category">Category name</label>
                      <input type="text" class="form-control" id="Category" name="Category" value="<?= $e($category->Category); ?>" required>
                      <div class="up-hint">Expenses already filed under this category keep the old name.</div>
                    </div>

                    <div class="d-flex mt-2" style="gap:8px;flex-wrap:wrap;">
                      <button type="submit" name="update" value="1" class="up-btn up-btn-primary">
                        <i class="mdi mdi-content-save"></i> Save Changes
                      </button>
                      <a href="<?= base_url('Accounting/expensescategory'); ?>" class="up-btn up-btn-ghost">Cancel</a>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>
          <?php endif; ?>

        </div>
      </div>

      <?php include('includes/footer.php'); ?>
    </div>
  </div>

  <?php include('includes/themecustomizer.php'); ?>
  <script src="<?= base_url(); ?>assets/js/vendor.min.js"></script>
  <script src="<?= base_url(); ?>assets/js/app.min.js"></script>
</body>
</html>
