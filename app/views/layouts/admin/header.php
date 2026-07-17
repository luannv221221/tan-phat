<header class="bg-light py-2">
    <div class="container">
        <h1 class="text-center">TÂN PHÁT</h1>

        <div class="row">
            <div class="col-8">
                <nav>
                    <ul class="nav">
                        <li class="nav-item">
                            <a class="nav-link active" href="<?php echo _WEB_URL.'/admin'; ?>">Trang chủ</a>
                        </li>
                        <?php
                        if (!empty($listModules)):
                            foreach ($listModules as $item):
                                if (route('admin/'.$item['link'])):
                                ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?php echo e(_WEB_URL.'/admin/'.$item['link']); ?>"><?php echo e($item['name']); ?></a>
                                </li>
                                <?php
                                endif;
                            endforeach;
                        endif;
                        ?>

                    </ul>
                </nav>
            </div>
            <div class="col-4 d-flex justify-content-end">
                <a href="<?php echo _WEB_URL; ?>" target="_blank" class="btn btn-primary mr-2"><i class="fa fa-home"></i> Xem trang chủ</a>
                <div class="dropdown">
                    <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-expanded="false"><i class="fa fa-user" aria-hidden="true"></i>
                        Hi, <?php echo e($infoUser['name']); ?>
                    </button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">

                        <a class="dropdown-item" href="<?php echo _WEB_URL.'/dang-xuat'; ?>">Đăng xuất</a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</header>