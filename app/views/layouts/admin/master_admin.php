<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title><?php echo e((!empty($page_title)) ? $page_title : 'Quản trị hệ thống'); ?> · Tân Phát</title>

    <!-- Font Awesome 6 (icon) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
    <!-- Bootstrap 4.6 (local) -->
    <link type="text/css" rel="stylesheet" href="<?php echo _WEB_URL.'/public/assets/css/bootstrap.min.css' ?>"/>
    <!-- AdminLTE 3.2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css"/>
    <!-- Tùy biến riêng (nạp cuối để đè) -->
    <link type="text/css" rel="stylesheet" href="<?php echo _WEB_URL.'/public/assets/css/style.css' ?>"/>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <?php $this->render('layouts/admin/header', $content);  // navbar trên cùng ?>

    <?php $this->render('layouts/admin/sidebar', $content); // menu dọc bên trái ?>

    <div class="content-wrapper">
        <section class="content pt-3">
            <div class="container-fluid">
                <?php $this->render($sub_content, $content); ?>
            </div>
        </section>
    </div>

    <?php $this->render('layouts/admin/footer', $content); ?>

</div>

<script src="<?php echo _WEB_URL.'/public/assets/js/jquery-3.6.0.min.js' ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="<?php echo _WEB_URL.'/public/assets/js/bootstrap.min.js' ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/js/adminlte.min.js"></script>
</body>
</html>
