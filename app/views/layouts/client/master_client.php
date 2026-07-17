<html>
<head>
    <title><?php echo e((!empty($page_title)) ? $page_title : 'Công ty Tân Phát'); ?></title>
    <meta charset="utf-8"/>
    <link type="text/css" rel="stylesheet" href="<?php echo _WEB_URL.'/public/assets/css/bootstrap.min.css' ?>"/>
    <link type="text/css" rel="stylesheet" href="<?php echo _WEB_URL.'/public/assets/css/style.css' ?>"/>
</head>
<body>

<?php $this->render('layouts/client/header'); ?>

<div class="container">
    <?php $this->render($sub_content); ?>
</div>

<?php $this->render('layouts/client/footer'); ?>

<script type="text/javascript" src="<?php echo _WEB_URL.'/public/assets/js/jquery-3.6.0.min.js' ?>"></script>

<script type="text/javascript" src="<?php echo _WEB_URL.'/public/assets/js/bootstrap.min.js' ?>"></script>
</body>
</html>