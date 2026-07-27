<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title><?php echo e((!empty($page_title)) ? $page_title : 'Đăng nhập'); ?> · Tân Phát</title>

    <link type="text/css" rel="stylesheet" href="<?php echo _WEB_URL.'/public/assets/css/bootstrap.min.css' ?>"/>
    <link type="text/css" rel="stylesheet" href="<?php echo _WEB_URL.'/public/assets/css/admin-theme.css' ?>"/>
</head>
<body class="adm adm-auth">
<?php echo icon_sprite(); ?>

<header class="auth-topbar">
    <?php echo icon('shield'); ?>
    <span>Đăng nhập</span>
</header>

<?php $this->render($sub_content, $content); ?>

<script src="<?php echo _WEB_URL.'/public/assets/js/jquery-3.6.0.min.js' ?>"></script>
<script src="<?php echo _WEB_URL.'/public/assets/vendor/popper/popper.min.js' ?>"></script>
<script src="<?php echo _WEB_URL.'/public/assets/js/bootstrap.min.js' ?>"></script>
</body>
</html>
