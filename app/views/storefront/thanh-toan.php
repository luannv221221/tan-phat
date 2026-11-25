<?php
/**
 * Trang thanh toán công khai /thanh-toan/<mã> — xem app/controllers/Thanhtoan.php.
 *
 * Biến vào: $hd (null = không tìm thấy), $cf (cau_hinh_in_an của gara lập hoá
 * đơn), $qr (VietQr::choHoaDon, null = gara chưa khai tài khoản / không còn gì
 * phải trả).
 *
 * HTML trọn vẹn, không qua Template::run() (không {{ }}, không @if) — giống mẫu in.
 */
$tenGara = !empty($cf['site_name']) ? $cf['site_name'] : 'Công ty';
$dtGara  = !empty($cf['hotline']) ? (string) $cf['hotline'] : '';
$logo    = !empty($hd) ? logo_in_an($cf) : '';
$tien    = function($n){ return number_format((float) $n, 0, ',', '.'); };
$ngay    = '';
if (!empty($hd['invoice_date'])){ $d = date_create($hd['invoice_date']); $ngay = $d ? $d->format('d/m/Y') : ''; }
?><!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<meta name="robots" content="noindex, nofollow"/>
<meta name="referrer" content="no-referrer"/>
<title><?php echo !empty($hd) ? e('Thanh toán ' . $hd['invoice_no'] . ' — ' . $tenGara) : 'Không tìm thấy hoá đơn'; ?></title>
<style>
:root { --nen: #f2f4f7; --the: #fff; --chu: #1d2433; --phu: #5b6475; --vien: #e3e7ee; --nhan: #0b6bcb; --nhan-chu: #fff; --tot: #0f7b4a; }
* { box-sizing: border-box; }
body { margin: 0; background: var(--nen); color: var(--chu); font: 16px/1.5 -apple-system, "Segoe UI", Roboto, Arial, sans-serif; }
.khung { max-width: 460px; margin: 0 auto; padding: 16px; }
.the { background: var(--the); border-radius: 14px; box-shadow: 0 1px 4px rgba(0,0,0,.08); padding: 20px; }
.gara { display: flex; gap: 12px; align-items: center; padding-bottom: 14px; border-bottom: 1px solid var(--vien); margin-bottom: 16px; }
.gara img { max-height: 44px; max-width: 96px; }
.gara b { display: block; font-size: 17px; }
.gara small { color: var(--phu); }
.nhan-nho { color: var(--phu); font-size: 13px; text-transform: uppercase; letter-spacing: .5px; margin: 0; }
h1 { font-size: 20px; margin: 2px 0 2px; }
.phu { color: var(--phu); font-size: 14px; margin: 0 0 12px; }
.so-tien { font-size: 30px; font-weight: 700; color: var(--tot); margin: 4px 0 16px; }
.o-qr { text-align: center; }
.vietqr { width: 240px; height: 240px; margin: 0 auto; background: #fff; }
.vietqr svg { display: block; width: 100%; height: 100%; shape-rendering: crispEdges; }
.vietqr .qr-loi { display: block; padding-top: 90px; font-size: 13px; color: var(--phu); }
.nut { display: inline-block; border: 0; border-radius: 8px; padding: 10px 16px; font: inherit; font-size: 15px; cursor: pointer; text-decoration: none; }
.nut-chinh { background: var(--nhan); color: var(--nhan-chu); width: 100%; margin-top: 12px; }
.goi-y { font-size: 13px; color: var(--phu); margin: 10px 0 16px; }
dl { margin: 0; }
.dong { display: flex; justify-content: space-between; align-items: center; gap: 8px; padding: 9px 0; border-top: 1px solid var(--vien); }
.dong dt { color: var(--phu); font-size: 14px; margin: 0; }
.dong dd { margin: 0; text-align: right; font-weight: 600; word-break: break-all; }
.chep { background: #eef3fb; color: var(--nhan); border: 0; border-radius: 6px; padding: 4px 10px; font-size: 13px; cursor: pointer; margin-left: 6px; white-space: nowrap; }
.bao { background: #fff7e6; border: 1px solid #ffd591; border-radius: 10px; padding: 14px; }
.bao a { color: var(--nhan); font-weight: 600; }
.chan { text-align: center; color: var(--phu); font-size: 12px; margin-top: 14px; }
</style>
</head>
<body>
<div class="khung">
<div class="the">

<?php if (empty($hd)): ?>
    <p class="nhan-nho">Link thanh toán</p>
    <h1>Không tìm thấy hoá đơn</h1>
    <p class="phu">Link không đúng hoặc hoá đơn đã được công ty huỷ. Vui lòng liên hệ công ty đã gửi link cho bạn.</p>
<?php else: ?>

    <div class="gara">
        <?php if ($logo !== ''): ?><img src="<?php echo e($logo); ?>" alt=""/><?php endif; ?>
        <div>
            <b><?php echo e($tenGara); ?></b>
            <?php if (!empty($cf['address'])): ?><small><?php echo e($cf['address']); ?></small><br/><?php endif; ?>
            <?php if ($dtGara !== ''): ?><small>ĐT: <a href="tel:<?php echo e(preg_replace('~[^0-9+]~', '', $dtGara)); ?>"><?php echo e($dtGara); ?></a></small><?php endif; ?>
        </div>
    </div>

    <p class="nhan-nho">Yêu cầu thanh toán</p>
    <h1>Hoá đơn <?php echo e($hd['invoice_no']); ?></h1>
    <p class="phu">
        <?php if ($ngay !== ''): ?>Ngày <?php echo e($ngay); ?><?php endif; ?>
        <?php if (!empty($hd['bien_so'])): ?> · Xe <?php echo e($hd['bien_so']); ?><?php endif; ?>
    </p>
    <div class="so-tien"><?php echo e($tien($hd['total_amount'])); ?> đ</div>

    <?php if (!empty($qr)): ?>
    <div class="o-qr">
        <div class="vietqr" id="maQr" data-vietqr="<?php echo e($qr['chuoi']); ?>"><span class="qr-loi">Không vẽ được mã QR — chuyển khoản theo thông tin bên dưới.</span></div>
        <button type="button" class="nut nut-chinh" data-tai-qr="#maQr" data-ten="<?php echo e('QR-' . preg_replace('~[^A-Za-z0-9\-]~', '', $hd['invoice_no'])); ?>">Tải ảnh mã QR</button>
    </div>
    <p class="goi-y">
        Mở app ngân hàng → <b>Quét mã QR</b>. Đang xem trên điện thoại: bấm <b>Tải ảnh mã QR</b>,
        rồi trong mục quét QR của app chọn ảnh vừa tải. Số tiền và nội dung đã điền sẵn.
    </p>
    <dl>
        <div class="dong"><dt>Ngân hàng</dt><dd><?php echo e($qr['ngan_hang']); ?></dd></div>
        <div class="dong"><dt>Số tài khoản</dt><dd><?php echo e($qr['so_tk']); ?><button type="button" class="chep" data-chep="<?php echo e($qr['so_tk']); ?>">Chép</button></dd></div>
        <?php if ($qr['chu_tk'] !== ''): ?>
        <div class="dong"><dt>Chủ tài khoản</dt><dd><?php echo e($qr['chu_tk']); ?></dd></div>
        <?php endif; ?>
        <div class="dong"><dt>Số tiền</dt><dd><?php echo e($tien($qr['so_tien'])); ?> đ<button type="button" class="chep" data-chep="<?php echo (int) $qr['so_tien']; ?>">Chép</button></dd></div>
        <div class="dong"><dt>Nội dung</dt><dd><?php echo e($qr['noi_dung']); ?><button type="button" class="chep" data-chep="<?php echo e($qr['noi_dung']); ?>">Chép</button></dd></div>
    </dl>
    <?php else: ?>
    <div class="bao">
        Công ty chưa cài đặt tài khoản nhận chuyển khoản. Vui lòng liên hệ công ty để được hướng dẫn thanh toán<?php
        if ($dtGara !== ''): ?>: <a href="tel:<?php echo e(preg_replace('~[^0-9+]~', '', $dtGara)); ?>"><?php echo e($dtGara); ?></a><?php endif; ?>.
    </div>
    <?php endif; ?>

<?php endif; ?>

</div>
<p class="chan">Trang này chỉ dùng để thanh toán hoá đơn.</p>
</div>

<?php if (!empty($qr)): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js"
        integrity="sha384-mZT2gIty7ZDdOGkxfP6joZcYdMW1Jvj9dRlfpTmaJAKKXTqzygtB22k7FLe+KZC1"
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="<?php echo asset('public/assets/js/vietqr.js'); ?>"></script>
<?php endif; ?>
</body>
</html>
