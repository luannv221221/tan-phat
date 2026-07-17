<html>
<head>
    <title><?php echo e((!empty($page_title)) ? $page_title : 'Đăng nhập'); ?></title>
    <meta charset="utf-8"/>
    <link type="text/css" rel="stylesheet" href="<?php echo _WEB_URL.'/public/assets/css/bootstrap.min.css' ?>"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css" />
    <link type="text/css" rel="stylesheet" href="<?php echo _WEB_URL.'/public/assets/css/style.css' ?>"/>

</head>
<body>

<?php $this->render($sub_content, $content); ?>

<script type="text/javascript" src="<?php echo _WEB_URL.'/public/assets/js/jquery-3.6.0.min.js' ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN" crossorigin="anonymous"></script>

<script type="text/javascript" src="<?php echo _WEB_URL.'/public/assets/js/bootstrap.min.js' ?>"></script>
</body>
</html>