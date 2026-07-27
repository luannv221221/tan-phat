<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title><?php echo e((!empty($page_title)) ? $page_title : 'Quản trị hệ thống'); ?> · Tân Phát</title>

    <!-- Bootstrap 4.6 (local) — vẫn cần vì 120 view dùng lưới & component của nó -->
    <link type="text/css" rel="stylesheet" href="<?php echo _WEB_URL.'/public/assets/css/bootstrap.min.css' ?>"/>
    <!-- Theme quản trị (thay hoàn toàn AdminLTE) -->
    <link type="text/css" rel="stylesheet" href="<?php echo _WEB_URL.'/public/assets/css/admin-theme.css' ?>"/>
    <!-- Font Awesome — còn vài view dùng <i class="fas ..."> trong nội dung -->
    <link rel="stylesheet" href="<?php echo _WEB_URL.'/public/assets/vendor/fontawesome/css/all.min.css' ?>"/>
</head>

<body class="adm">
<?php echo icon_sprite(); ?>

<div class="adm-shell">

    <?php $this->render('layouts/admin/sidebar', $content); ?>

    <div class="adm-main">
        <?php $this->render('layouts/admin/header', $content); ?>

        <main class="adm-content">
            <?php $this->render($sub_content, $content); ?>
        </main>
    </div>

</div>

<div class="adm-backdrop"></div>

<script src="<?php echo _WEB_URL.'/public/assets/js/jquery-3.6.0.min.js' ?>"></script>
<script src="<?php echo _WEB_URL.'/public/assets/vendor/popper/popper.min.js' ?>"></script>
<script src="<?php echo _WEB_URL.'/public/assets/js/bootstrap.min.js' ?>"></script>
<script src="<?php echo _WEB_URL.'/public/assets/js/admin.js' ?>"></script>
</body>
</html>
