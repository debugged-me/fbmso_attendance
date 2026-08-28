<!DOCTYPE html>
<html lang="en">

<?php include('includes/head.php'); ?>

<style>
    .notification-center {
        max-width: 920px;
        margin: 0 auto;
    }

    .notification-center-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 18px;
    }

    .notification-center-header p {
        color: #6b7280;
        margin: 5px 0 0;
    }

    .notification-count {
        background: #eef2ff;
        color: #3347a9;
        border-radius: 999px;
        font-size: .8rem;
        font-weight: 700;
        padding: 7px 12px;
        white-space: nowrap;
    }

    .notification-entry {
        display: flex;
        gap: 14px;
        padding: 18px;
        border-bottom: 1px solid #edf0f5;
    }

    .notification-entry:last-child {
        border-bottom: 0;
    }

    .notification-entry.is-unread {
        background: #fbfcff;
    }

    .notification-entry-icon {
        width: 42px;
        height: 42px;
        flex: 0 0 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #eef2ff;
        color: #3347a9;
        font-size: 1.2rem;
    }

    .notification-entry-body {
        min-width: 0;
        flex: 1 1 auto;
    }

    .notification-entry-title {
        color: #152044;
        font-size: .98rem;
        font-weight: 700;
        margin-bottom: 4px;
    }

    .notification-entry-meta,
    .notification-entry-purpose {
        color: #6b7280;
        font-size: .84rem;
    }

    .notification-entry-purpose {
        margin-top: 5px;
    }

    .notification-entry-action {
        align-self: center;
        white-space: nowrap;
    }

    .notification-empty {
        padding: 62px 24px;
        text-align: center;
    }

    .notification-empty-icon {
        width: 68px;
        height: 68px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #ecfdf3;
        color: #18864b;
        font-size: 2rem;
        margin-bottom: 16px;
    }

    .notification-empty h5 {
        color: #152044;
        margin-bottom: 7px;
    }

    .notification-empty p {
        color: #6b7280;
        margin: 0;
    }

    @media (max-width: 575.98px) {

        .notification-center-header,
        .notification-entry {
            align-items: flex-start;
        }

        .notification-entry {
            flex-wrap: wrap;
        }

        .notification-entry-action {
            width: 100%;
            padding-left: 56px;
        }
    }
</style>

<body>
    <div id="wrapper">
        <?php include('includes/top-nav-bar.php'); ?>
        <?php include('includes/sidebar.php'); ?>

        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                    <div class="page-title-box">
                        <h4 class="page-title">Notifications</h4>
                    </div>



                    <div class="card mb-4">
                        <?php if (empty($requests)): ?>
                            <div class="notification-empty">
                                <span class="notification-empty-icon" aria-hidden="true">
                                    <i class="mdi mdi-check"></i>
                                </span>
                                <h5>You're all caught up</h5>
                                <p>There are no pending document requests right now.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($requests as $request): ?>
                                <div id="request-<?= html_escape($request['id']); ?>"
                                    class="notification-entry<?= $request['unread'] ? ' is-unread' : ''; ?>">
                                    <span class="notification-entry-icon" aria-hidden="true">
                                        <i class="mdi mdi-file-document-outline"></i>
                                    </span>

                                    <div class="notification-entry-body">
                                        <div class="notification-entry-title">
                                            <?= html_escape($request['document_type'] ?: 'Document request'); ?>
                                        </div>
                                        <div class="notification-entry-meta">
                                            Requested by <?= html_escape($request['student']); ?>
                                            &middot;
                                            <?= html_escape($request['request_date_display']); ?>
                                        </div>
                                        <?php if ($request['purpose'] !== ''): ?>
                                            <div class="notification-entry-purpose">
                                                <?= html_escape($request['purpose']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="notification-entry-action">
                                        <a class="btn btn-sm btn-outline-primary"
                                            href="<?= html_escape($request['url']); ?>">
                                            Review request
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
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