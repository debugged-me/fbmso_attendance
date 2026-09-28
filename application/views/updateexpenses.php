<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>

<?php
$e = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
$expense = !empty($data) ? $data[0] : null;
$categories = isset($data1) ? $data1 : [];
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
                <h4 class="up-page-title">Edit Expense</h4>
                <div class="up-page-sub">Changes are recorded in the audit trail with the values before and after.</div>
                <hr class="up-divider" />
              </div>
            </div>
          </div>

          <?php if (!$expense): ?>
            <div class="up-flash up-flash-danger">That expense no longer exists.</div>
            <a href="<?= base_url('Accounting/expenses'); ?>" class="up-btn up-btn-ghost"><i class="mdi mdi-arrow-left"></i> Back to Expenses</a>
          <?php else: ?>
          <div class="row">
            <div class="col-12">
              <div class="up-card">
                <div class="up-card-head">
                  <h4><i class="mdi mdi-cash-register"></i> <?= $e($expense->Description); ?></h4>
                </div>
                <div class="up-card-body">
                  <form method="post" action="<?= base_url('Accounting/updateexpenses?expensesid=' . (int)$expense->expensesid); ?>">
                    <div class="form-row">
                      <div class="col-md-6 mb-3">
                        <label for="Description">Description</label>
                        <input type="text" class="form-control" id="Description" name="Description" value="<?= $e($expense->Description); ?>" required>
                      </div>
                      <div class="col-md-6 mb-3">
                        <label for="Amount">Amount</label>
                        <input type="number" class="form-control" id="Amount" name="Amount" value="<?= $e($expense->Amount); ?>" step="0.01" min="0" required>
                      </div>
                    </div>
                    <div class="form-row">
                      <div class="col-md-6 mb-3">
                        <label for="Responsible">Responsible</label>
                        <input type="text" class="form-control" id="Responsible" name="Responsible" value="<?= $e($expense->Responsible); ?>" required>
                      </div>
                      <div class="col-md-6 mb-3">
                        <label for="ExpenseDate">Expense Date</label>
                        <input type="date" class="form-control" id="ExpenseDate" name="ExpenseDate" value="<?= $e($expense->ExpenseDate); ?>" required>
                      </div>
                    </div>
                    <div class="form-row">
                      <div class="col-md-12 mb-3">
                        <label for="Category">Category</label>
                        <select class="form-control" id="Category" name="Category" required>
                          <?php
                          $current = (string)$expense->Category;
                          $listed = false;
                          foreach ($categories as $row):
                            $selected = ((string)$row->Category === $current);
                            $listed = $listed || $selected;
                          ?>
                            <option value="<?= $e($row->Category); ?>" <?= $selected ? 'selected' : ''; ?>><?= $e($row->Category); ?></option>
                          <?php endforeach; ?>
                          <?php if (!$listed && $current !== ''): ?>
                            <option value="<?= $e($current); ?>" selected><?= $e($current); ?> (no longer a category)</option>
                          <?php endif; ?>
                        </select>
                      </div>
                    </div>

                    <div class="d-flex mt-2" style="gap:8px;flex-wrap:wrap;">
                      <button type="submit" name="update" value="1" class="up-btn up-btn-primary">
                        <i class="mdi mdi-content-save"></i> Save Changes
                      </button>
                      <a href="<?= base_url('Accounting/expenses'); ?>" class="up-btn up-btn-ghost">Cancel</a>
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
