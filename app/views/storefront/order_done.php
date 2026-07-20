<?php
$isBank = ($pay === 'bank_transfer');
?>
<div class="card" style="margin:30px auto;max-width:640px"><div class="bd" style="padding:40px">
    <div class="tc" style="font-size:56px">✅</div>
    <h1 class="page-title tc">Đặt hàng thành công!</h1>
    @if (!empty($orderNo))
    <p class="tc">Mã đơn hàng: <b><?php echo e($orderNo); ?></b> · Tổng tiền: <b style="color:#c0392b"><?php echo e(number_format((float) $total, 0, ',', '.')); ?> ₫</b></p>
    @endif

    @if ($isBank)
    <div class="alert alert-info mt">
        <b>Hướng dẫn chuyển khoản</b>
        <div class="mt" style="margin-top:8px">
            <div>Ngân hàng: <b><?php echo e(!empty($settings['bank_name']) ? $settings['bank_name'] : ''); ?></b></div>
            <div>Số tài khoản: <b><?php echo e(!empty($settings['bank_account']) ? $settings['bank_account'] : ''); ?></b></div>
            <div>Chủ tài khoản: <b><?php echo e(!empty($settings['bank_holder']) ? $settings['bank_holder'] : ''); ?></b></div>
            <div class="mt" style="margin-top:6px">Nội dung: <b><?php echo e($orderNo); ?></b></div>
        </div>
    </div>
    @else
    <div class="alert alert-ok mt">Bạn chọn <b>thanh toán khi nhận hàng (COD)</b>. Nhân viên sẽ liên hệ xác nhận đơn.</div>
    @endif

    <p class="muted tc">Cảm ơn bạn đã đặt hàng tại Tân Phát. Chúng tôi sẽ liên hệ xác nhận sớm nhất.</p>
    <div class="tc">
        <a class="btn btn-brand" href="<?php echo _WEB_URL; ?>/san-pham">Tiếp tục mua sắm</a>
        <a class="btn" href="<?php echo _WEB_URL; ?>/">Về trang chủ</a>
    </div>
</div></div>
