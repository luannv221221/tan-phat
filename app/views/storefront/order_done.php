<?php
$isBank = ($pay === 'bank_transfer');
/* $taiKhoan = cau_hinh_in_an() của GARA ĐANG BÁN (xem Cart::orderDone), không phải
   Cấu hình chung: gara chưa khai thì các ô ngân hàng rỗng chứ không mang số của Tân Phát. */
$tk     = isset($taiKhoan) && is_array($taiKhoan) ? $taiKhoan : [];
$coTk   = !empty($tk['bank_account']);
$dtGara = !empty($tk['hotline']) ? (string) $tk['hotline'] : '';
?>
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
            <?php if (!empty($qr)): ?>
            <?php /* Mã QR VietQR: cùng chuỗi, cùng cách vẽ với hoá đơn (App\core\VietQr).
                     Chữ bên cạnh đọc từ CHÍNH $qr nên không thể lệch với mã. */ ?>
            <div class="d-flex flex-wrap align-items-start mt-2" style="gap:16px">
                <div class="text-center" style="width:180px">
                    <div class="vietqr" data-vietqr="<?php echo e($qr['chuoi']); ?>" style="width:180px;height:180px;background:#fff;padding:4px"><small class="d-block pt-5 text-muted">Không vẽ được mã QR — chuyển khoản theo thông tin bên cạnh.</small></div>
                    <small class="d-block text-muted mt-1">Quét bằng app ngân hàng. Đang dùng điện thoại: chụp màn hình rồi chọn ảnh trong app.</small>
                </div>
                <div>
                    <div>Ngân hàng: <b><?php echo e($qr['ngan_hang']); ?></b></div>
                    <div>Số tài khoản: <b><?php echo e($qr['so_tk']); ?></b></div>
                    <?php if ($qr['chu_tk'] !== ''): ?><div>Chủ tài khoản: <b><?php echo e($qr['chu_tk']); ?></b></div><?php endif; ?>
                    <div>Số tiền: <b><?php echo e(number_format((float) $qr['so_tien'], 0, ',', '.')); ?> đ</b></div>
                    <div class="mt-1">Nội dung: <b><?php echo e($qr['noi_dung']); ?></b></div>
                </div>
            </div>
            <?php elseif ($coTk): ?>
            <?php /* Gara có số tài khoản nhưng chưa chọn ngân hàng trong danh sách (dữ liệu
                     cũ, tên gõ tay): in chữ như trước, chỉ không có mã QR. */ ?>
            <div class="mt-2">
                <div>Ngân hàng: <b><?php echo e(!empty($tk['bank_name']) ? $tk['bank_name'] : ''); ?></b></div>
                <div>Số tài khoản: <b><?php echo e($tk['bank_account']); ?></b></div>
                <div>Chủ tài khoản: <b><?php echo e(!empty($tk['bank_holder']) ? $tk['bank_holder'] : ''); ?></b></div>
                <div class="mt-1">Nội dung: <b><?php echo e($orderNo); ?></b></div>
            </div>
            <?php else: ?>
            <?php /* Gara chưa khai tài khoản: KHÔNG in số tài khoản của ai cả. In số của
                     Tân Phát (như bản cũ) là khách chuyển nhầm người; in ô trống là khách
                     không biết làm gì tiếp. */ ?>
            <div class="mt-2">
                Gara sẽ liên hệ với bạn để hướng dẫn thanh toán — bạn chưa cần chuyển khoản lúc này.
                <?php if ($dtGara !== ''): ?>
                <br/>Cần gấp, gọi gara: <b><a href="tel:<?php echo e(preg_replace('~[^0-9+]~', '', $dtGara)); ?>"><?php echo e($dtGara); ?></a></b>
                <?php endif; ?>
            </div>
            <?php endif; ?>
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
<?php if ($isBank && !empty($qr)): ?>
<?php /* Vẽ mã QR tại chỗ — y như mẫu in hoá đơn (app/views/admin/print/chung-tu.php):
         qrcode-generator 1.4.4 từ cdnjs, ghim phiên bản + integrity; chỉ vẽ, không gửi
         số tiền / số tài khoản đi đâu. */ ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js"
        integrity="sha384-mZT2gIty7ZDdOGkxfP6joZcYdMW1Jvj9dRlfpTmaJAKKXTqzygtB22k7FLe+KZC1"
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="<?php echo asset('public/assets/js/vietqr.js'); ?>"></script>
<?php endif; ?>
