<?php $isBank = ($pay === 'bank_transfer'); ?>
<div class="content">
<div class="sf-page">
<div class="container">
    <div class="card border-0 shadow-sm mx-auto" style="max-width:640px"><div class="card-body p-4 p-md-5">
        <div class="text-center" style="font-size:56px">✅</div>
        <h1 class="sf-page-title text-center">Đặt hàng thành công!</h1>
        @if (!empty($orderNo))
        <p class="text-center">Mã đơn hàng: <b><?php echo e($orderNo); ?></b> · Tổng tiền: <b class="text-accent"><?php echo e(number_format((float) $total, 0, ',', '.')); ?> đ</b></p>
        @endif

        @if ($isBank)
        <div class="alert alert-info">
            <b>Hướng dẫn chuyển khoản</b>
            <div class="mt-2">
                <div>Ngân hàng: <b><?php echo e(!empty($settings['bank_name']) ? $settings['bank_name'] : ''); ?></b></div>
                <div>Số tài khoản: <b><?php echo e(!empty($settings['bank_account']) ? $settings['bank_account'] : ''); ?></b></div>
                <div>Chủ tài khoản: <b><?php echo e(!empty($settings['bank_holder']) ? $settings['bank_holder'] : ''); ?></b></div>
                <div class="mt-1">Nội dung: <b><?php echo e($orderNo); ?></b></div>
            </div>
        </div>
        @else
        <div class="alert alert-success">Bạn chọn <b>thanh toán khi nhận hàng (COD)</b>. Nhân viên sẽ liên hệ xác nhận đơn.</div>
        @endif

        <p class="text-muted text-center">Cảm ơn bạn đã đặt hàng. Chúng tôi sẽ liên hệ xác nhận sớm nhất.</p>
        <div class="text-center">
            <a class="btn btn-primary" href="<?php echo _WEB_URL; ?>/san-pham">Tiếp tục mua sắm</a>
            <a class="btn btn-outline-secondary" href="<?php echo _WEB_URL; ?>/">Về trang chủ</a>
        </div>
    </div></div>
</div>
</div>
</div>
