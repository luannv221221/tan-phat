<!-- Navbar trên cùng -->
<nav class="main-header navbar navbar-expand navbar-white navbar-light">

    <!-- Bên trái: nút thu/mở sidebar -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="<?php echo _WEB_URL.'/admin'; ?>" class="nav-link">Trang chủ</a>
        </li>
    </ul>

    <!-- Bên phải: xem trang chủ + user -->
    <ul class="navbar-nav ml-auto">
        <li class="nav-item d-none d-sm-inline-block">
            <a href="<?php echo _WEB_URL; ?>" target="_blank" class="nav-link">
                <i class="fas fa-external-link-alt mr-1"></i> Xem trang chủ
            </a>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="fas fa-user-circle mr-1"></i>
                <?php echo e(!empty($infoUser['name']) ? $infoUser['name'] : 'Tài khoản'); ?>
                <i class="fas fa-angle-down ml-1"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right">
                <span class="dropdown-header"><?php echo e(!empty($infoUser['email']) ? $infoUser['email'] : ''); ?></span>
                <div class="dropdown-divider"></div>
                <a href="<?php echo _WEB_URL.'/dang-xuat'; ?>" class="dropdown-item">
                    <i class="fas fa-sign-out-alt mr-2 text-danger"></i> Đăng xuất
                </a>
            </div>
        </li>
    </ul>
</nav>
