<?php
/**
 * BIỂU MẪU IN dùng chung cho BÁO GIÁ và HOÁ ĐƠN BÁN.
 *
 * Một mẫu, hai đường ra:
 *   - Mở trên trình duyệt -> bấm In -> "Lưu thành PDF" (Ctrl+P của trình duyệt)
 *   - Thêm ?word=1 -> gửi kèm header application/msword -> tải về file .doc,
 *     mở bằng Word sửa được.
 *
 * VÌ SAO KHÔNG DÙNG THƯ VIỆN PDF (dompdf/mPDF): dự án hiện KHÔNG có gói
 * composer nào (vendor/ chỉ có autoload.php). Thêm vào là gánh 20–30MB, lại
 * phải nhúng riêng font Unicode cho tiếng Việt — lỗi kinh điển của mấy thư
 * viện đó là chữ mất dấu hoặc thành ô vuông. Trình duyệt in tiếng Việt bằng
 * font hệ thống, chuẩn tuyệt đối và không phải cài gì.
 *
 * Logo nhúng base64 (xem logo_in_an()) để file Word mang theo được ảnh khi mở
 * ở máy khác.
 *
 * Biến vào: $ct, $khach, $dong, $tong, $settings, $urlWord, $laWord
 */
$s = function($key, $mac = '') use ($settings){
    return isset($settings[$key]) && $settings[$key] !== '' ? $settings[$key] : $mac;
};
$tien = function($n){ return number_format((float) $n, 0, ',', '.'); };
$ngayVn = function($d){
    if (empty($d)) return '';
    $t = date_create($d);
    return $t ? $t->format('d/m/Y') : $d;
};

$logo = logo_in_an($settings);

// Tách dòng theo nhóm để in có tiểu tổng, đúng như bố cục 2 tab lúc nhập.
$nhomHang = $nhomDichVu = [];
foreach ($dong as $d){
    if (isset($d['item_type']) && $d['item_type'] === PartsModel::LOAI_DICH_VU) $nhomDichVu[] = $d;
    else                                                                        $nhomHang[]   = $d;
}
$stt = 0;
?><!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8"/>
<title><?php echo e($ct['loai'] . ' ' . $ct['so']); ?></title>
<style>
/* Khổ A4. Lề 12mm để máy in phun/laser phổ thông không cắt mất viền. */
@page { size: A4 portrait; margin: 12mm; }

body { font-family: "Times New Roman", Times, serif; font-size: 13pt; color: #000;
       margin: 0; padding: 12mm; background: #f0f0f0; }
/* border-box: 210mm là bề ngang GIẤY, đã gồm cả lề trong. Để content-box thì
   khung xem trên màn hình rộng thành 230mm, nhìn không đúng khổ thật. */
.to-in { background: #fff; box-sizing: border-box; width: 210mm; max-width: 100%;
         margin: 0 auto; padding: 10mm; box-shadow: 0 1px 6px rgba(0,0,0,.15); }

/* Thanh công cụ chỉ để bấm trên màn hình, in ra không được có */
.thanh-cong-cu { max-width: 210mm; margin: 0 auto 10px; font-family: Arial, sans-serif; font-size: 11pt; }
.thanh-cong-cu a, .thanh-cong-cu button {
    display: inline-block; padding: 8px 16px; margin-right: 6px; border: 0; border-radius: 4px;
    background: #2f6fbf; color: #fff; text-decoration: none; cursor: pointer; font-size: 11pt; }
.thanh-cong-cu .phu { background: #6c757d; }
.thanh-cong-cu .nhac-qr { margin-top: 8px; padding: 8px 10px; background: #fff3cd; border: 1px solid #ffe08a;
                          border-radius: 4px; color: #664d03; line-height: 1.5; }
.thanh-cong-cu .nhac-qr a { display: inline; background: none; color: #0d6efd; padding: 0; margin: 0;
                            text-decoration: underline; border: 0; }

table { width: 100%; border-collapse: collapse; }
.dau-trang td { vertical-align: top; border: 0; padding: 0; }
.dau-trang img { max-height: 22mm; max-width: 60mm; }
.ten-cty { font-size: 14pt; font-weight: bold; text-transform: uppercase; }
.dong-nho { font-size: 11pt; line-height: 1.5; }

h1.tieu-de { text-align: center; font-size: 20pt; margin: 8mm 0 2mm; text-transform: uppercase; letter-spacing: 1px; }
.so-ngay { text-align: center; font-size: 11pt; margin-bottom: 6mm; }

.khoi-khach { margin-bottom: 4mm; line-height: 1.7; }
.khoi-khach .nhan { display: inline-block; min-width: 34mm; }

table.bang-hang { border: 1px solid #000; margin-bottom: 4mm; }
table.bang-hang th, table.bang-hang td { border: 1px solid #000; padding: 4px 5px; font-size: 11.5pt; }
table.bang-hang th { background: #e8e8e8; text-align: center; font-weight: bold; }
table.bang-hang td.giua { text-align: center; }
/* Số không bẻ dòng: "1.380.000" bẻ đôi là đọc nhầm số. */
table.bang-hang td.phai { text-align: right; white-space: nowrap; }
tr.nhom td { background: #f4f4f4; font-weight: bold; font-style: italic; }
tr.tong td { font-weight: bold; }
tr.tong-cuoi td { font-weight: bold; font-size: 13.5pt; }

.bang-chu { margin-bottom: 4mm; font-style: italic; }
.khoi-nh { margin-bottom: 6mm; font-size: 11.5pt; line-height: 1.6; }
.ky-ten { margin-top: 8mm; }
.ky-ten td { text-align: center; vertical-align: top; border: 0; font-size: 11.5pt; }
.ky-ten .chuc { font-weight: bold; }
.ky-ten .ghi { font-style: italic; font-size: 10.5pt; }
.ky-ten .cho-ky { height: 24mm; }

/* Khối chuyển khoản có mã QR: mã bên trái, chữ bên phải. 32mm ở khổ A4 là
   ~0,6mm mỗi ô với chuỗi VietQR thường gặp — camera điện thoại đọc thoải mái
   ở khoảng cách cầm tay, kể cả máy in phun. */
table.khoi-ck { width: auto; margin-bottom: 6mm; page-break-inside: avoid; }
table.khoi-ck td { border: 0; vertical-align: top; padding: 0; font-size: 11.5pt; line-height: 1.6; }
table.khoi-ck td.o-qr { width: 34mm; padding-right: 5mm; text-align: center; }
.vietqr { width: 32mm; height: 32mm; margin: 0 auto; }
.vietqr svg { display: block; width: 32mm; height: 32mm; }
.vietqr .qr-loi { display: block; padding-top: 8mm; font-size: 9pt; font-style: italic; color: #555; }
.nhan-qr { font-size: 9pt; font-style: italic; margin-top: 1mm; }

@media print {
    body { background: #fff; padding: 0; font-size: 12pt; }
    .to-in { padding: 0; width: auto; max-width: none; box-shadow: none; }
    .thanh-cong-cu { display: none !important; }
    /* Bảng dài quá một trang thì lặp lại tiêu đề cột ở trang sau */
    table.bang-hang thead { display: table-header-group; }
    tr { page-break-inside: avoid; }
}
</style>
</head>
<body>

<?php if (empty($laWord)): ?>
<div class="thanh-cong-cu">
    <button type="button" onclick="window.print()">In / Lưu thành PDF</button>
    <a class="phu" href="<?php echo e($urlWord); ?>">Tải file Word</a>
    <a class="phu" href="javascript:window.close()">Đóng</a>
    <span style="margin-left:10px;color:#555">Muốn ra PDF: bấm <b>In</b> rồi chọn máy in <b>“Lưu thành PDF”</b>.</span>
    <?php /* Vì sao hoá đơn không có mã QR — in_chung_tu() quyết $nhacQr. Nằm
             trong thanh công cụ nên ẩn khi in, không ra giấy. */ ?>
    <?php if (!empty($nhacQr)): ?>
    <div class="nhac-qr">
        <b>Chưa có mã QR chuyển khoản.</b>
        <?php if ($nhacQr === 'chon-lai'): ?>
        Ngân hàng của gara đang ghi tay — vào <b>Cấu hình</b>, chọn lại ngân hàng từ danh sách rồi Lưu.
        <?php else: ?>
        Gara chưa khai tài khoản nhận tiền — vào <b>Cấu hình</b>, chọn ngân hàng, nhập số tài khoản, chủ tài khoản rồi Lưu.
        <?php endif; ?>
        <?php if (!empty($urlCauHinh)): ?>
        <a href="<?php echo e($urlCauHinh); ?>" target="_blank" rel="noopener">Mở Cấu hình</a>
        <?php else: ?>
        (Nhờ chủ gara làm.)
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="to-in">

    <table class="dau-trang">
        <tr>
            <td style="width:62mm">
                <?php if ($logo !== ''): ?><img src="<?php echo e($logo); ?>" alt=""/><?php endif; ?>
            </td>
            <td>
                <div class="ten-cty"><?php echo e($s('site_name', 'Tân Phát')); ?></div>
                <?php /* Ghép từ những phần CÓ — khách báo 25/11/2026 bản in "thừa dòng":
                         gara không khai điện thoại / email thì bản cũ để một dòng trống
                         giữa địa chỉ và mã số thuế, hoặc dòng mở đầu bằng " | Email". */
                $__lh = [];
                if ($s('hotline') !== '') $__lh[] = 'Điện thoại: ' . $s('hotline');
                if ($s('email') !== '')   $__lh[] = 'Email: ' . $s('email');
                $__dau = [];
                if ($s('address') !== '')  $__dau[] = 'Địa chỉ: ' . $s('address');
                if (!empty($__lh))         $__dau[] = implode(" \u{00A0}|\u{00A0} ", $__lh);
                if ($s('tax_code') !== '') $__dau[] = 'Mã số thuế: ' . $s('tax_code');
                ?>
                <div class="dong-nho"><?php foreach ($__dau as $__i => $__x): ?><?php if ($__i > 0): ?><br/><?php endif; ?><?php echo e($__x); ?><?php endforeach; ?></div>
            </td>
        </tr>
    </table>

    <h1 class="tieu-de"><?php echo e($ct['loai']); ?></h1>
    <div class="so-ngay">
        Số: <b><?php echo e($ct['so']); ?></b>
        &nbsp;—&nbsp; Ngày <?php echo e($ngayVn($ct['ngay'])); ?>
        <?php /* XE CỦA PHIẾU. In ngay dưới số chứng từ, trước cả thông tin
                 khách: gara cầm tờ giấy này lên là phải biết ngay nó của xe
                 nào. Cả hai để trống được (bán lẻ phụ tùng qua quầy) nên chỉ
                 vẽ khi thực sự có. */ ?>
        <?php if (!empty($ct['bienSo']) || !empty($ct['soKm'])): ?>
        <br/>
        <?php if (!empty($ct['bienSo'])): ?>
            Biển số xe: <b><?php echo e($ct['bienSo']); ?></b>
        <?php endif; ?>
        <?php if (!empty($ct['bienSo']) && !empty($ct['soKm'])): ?>&nbsp;—&nbsp;<?php endif; ?>
        <?php if (!empty($ct['soKm'])): ?>
            Số km: <b><?php echo e(number_format((float) $ct['soKm'], 0, ',', '.')); ?></b>
        <?php endif; ?>
        <?php endif; ?>
        <?php if (!empty($ct['hieuLuc'])): ?>
        <br/>Báo giá có hiệu lực đến hết ngày <b><?php echo e($ngayVn($ct['hieuLuc'])); ?></b>
        <?php endif; ?>
    </div>

    <div class="khoi-khach">
        <span class="nhan">Khách hàng:</span>
        <b><?php echo e(!empty($khach['name']) ? $khach['name'] : 'Khách lẻ'); ?></b><br/>
        <?php /* Địa chỉ đầy đủ: số nhà, phường/xã, tỉnh/thành. Khách cũ chưa có
                 tỉnh/phường thì in đúng `address` như trước, không dấu phẩy thừa. */ ?>
        <?php $__diaChi = dia_chi_day_du($khach); ?>
        <?php if ($__diaChi !== ''): ?>
        <span class="nhan">Địa chỉ:</span><?php echo e($__diaChi); ?><br/>
        <?php endif; ?>
        <?php if (!empty($khach['phone'])): ?>
        <span class="nhan">Điện thoại:</span><?php echo e($khach['phone']); ?><br/>
        <?php endif; ?>
        <?php if (!empty($khach['tax_code'])): ?>
        <span class="nhan">Mã số thuế:</span><?php echo e($khach['tax_code']); ?><br/>
        <?php endif; ?>
    </div>

    <?php /* CỘT TÊN PHẢI RỘNG NHẤT — khách báo 25/11/2026 "bản in thừa dòng". Bản
             cũ đặt cứng 7 cột còn lại 134mm; khổ A4 trừ lề còn 186mm nên cột Tên chỉ
             ~26mm, tên ngắn như "Bugi Iridium NGK" cũng bẻ hai dòng. Nay các cột
             số gọn lại (số đã không bẻ dòng), cột Tên lấy phần còn lại ~65mm.
             Cột CK chỉ in khi có dòng chiết khấu — 10mm trắng ở mọi phiếu là phí. */
    $coCk = false;
    foreach ($dong as $__d) if ((float) $__d['discount_percent'] > 0){ $coCk = true; break; }
    $soCot = $coCk ? 8 : 7;
    ?>
    <table class="bang-hang">
        <thead>
            <tr>
                <th style="width:8mm">TT</th>
                <th style="width:18mm">Mã</th>
                <th>Tên hàng hoá, dịch vụ</th>
                <th style="width:12mm">ĐVT</th>
                <th style="width:10mm">SL</th>
                <th style="width:21mm">Đơn giá</th>
                <?php if ($coCk): ?><th style="width:10mm">CK</th><?php endif; ?>
                <th style="width:24mm">Thành tiền</th>
            </tr>
        </thead>
        <tbody>
        <?php
        // In lần lượt hai nhóm. Nhóm nào rỗng thì bỏ hẳn cả dòng tiêu đề nhóm,
        // không in "Dịch vụ" rồi để trống bên dưới.
        foreach ([['Hàng hoá', $nhomHang, 'hang'], ['Dịch vụ', $nhomDichVu, 'dichvu']] as $g):
            if (empty($g[1])) continue;
            // Chỉ hiện tiêu đề nhóm khi chứng từ có CẢ HAI loại — chỉ có một
            // loại thì thêm dòng nhóm chỉ tổ tốn giấy.
            $hienNhom = !empty($nhomHang) && !empty($nhomDichVu);
        ?>
            <?php if ($hienNhom): ?>
            <tr class="nhom"><td colspan="<?php echo (int) $soCot; ?>"><?php echo e($g[0]); ?></td></tr>
            <?php endif; ?>
            <?php foreach ($g[1] as $d): $stt++; ?>
            <tr>
                <td class="giua"><?php echo (int) $stt; ?></td>
                <td><?php echo e($d['part_code']); ?></td>
                <td><?php echo e($d['part_name']); ?><?php
                    echo !empty($d['note']) ? '<br/><i style="font-size:10.5pt">' . e($d['note']) . '</i>' : ''; ?></td>
                <td class="giua"><?php echo e(!empty($d['unit_name']) ? $d['unit_name'] : ''); ?></td>
                <td class="phai"><?php echo e(rtrim(rtrim(number_format((float) $d['quantity'], 3, ',', '.'), '0'), ',')); ?></td>
                <td class="phai"><?php echo e($tien($d['unit_price'])); ?></td>
                <?php if ($coCk): ?><td class="phai"><?php echo (float) $d['discount_percent'] > 0
                    ? e(rtrim(rtrim(number_format((float) $d['discount_percent'], 2, '.', ''), '0'), '.')) . '%' : ''; ?></td><?php endif; ?>
                <td class="phai"><?php echo e($tien($d['amount'])); ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endforeach; ?>

        <?php if (empty($dong)): ?>
            <tr><td colspan="<?php echo (int) $soCot; ?>" class="giua" style="padding:10px">(Chưa có dòng hàng nào)</td></tr>
        <?php endif; ?>

        <?php /* Tách riêng tiền hàng hoá và tiền dịch vụ khi chứng từ có cả hai —
                 gara muốn biết ngay phần vật tư và phần công thợ. */ ?>
        <?php if (!empty($nhomHang) && !empty($nhomDichVu)): ?>
        <tr class="tong"><td colspan="<?php echo (int) $soCot - 1; ?>" class="phai">Tiền hàng hoá</td><td class="phai"><?php echo e($tien($tong['hang'])); ?></td></tr>
        <tr class="tong"><td colspan="<?php echo (int) $soCot - 1; ?>" class="phai">Tiền dịch vụ</td><td class="phai"><?php echo e($tien($tong['dichvu'])); ?></td></tr>
        <?php endif; ?>
        <?php /* Thuế 0% thì hai dòng này chỉ lặp lại đúng số tổng — bỏ (khách báo
                 "thừa dòng" 25/11/2026). Có thuế thì in như cũ. */ ?>
        <?php /* VAT từng dòng (25/11/2026): nhiều mức thuế suất thì in mỗi mức một
                 dòng kèm thuế của mức đó — xem in_chung_tu(). */ ?>
        <?php if (count($tong['mucThue']) > 1): ?>
        <tr class="tong"><td colspan="<?php echo (int) $soCot - 1; ?>" class="phai">Cộng tiền hàng chưa thuế</td><td class="phai"><?php echo e($tien($tong['subtotal'])); ?></td></tr>
        <?php foreach ($tong['mucThue'] as $__muc): ?>
        <tr class="tong"><td colspan="<?php echo (int) $soCot - 1; ?>" class="phai">Thuế GTGT <?php echo e(vat_chu($__muc['rate'])); ?>%</td><td class="phai"><?php echo e($tien($__muc['thue'])); ?></td></tr>
        <?php endforeach; ?>
        <?php elseif ((float) $tong['vat_rate'] != 0 || (float) $tong['tax'] != 0): ?>
        <tr class="tong"><td colspan="<?php echo (int) $soCot - 1; ?>" class="phai">Cộng tiền hàng chưa thuế</td><td class="phai"><?php echo e($tien($tong['subtotal'])); ?></td></tr>
        <tr class="tong"><td colspan="<?php echo (int) $soCot - 1; ?>" class="phai">Thuế GTGT (<?php echo e(vat_chu($tong['vat_rate'])); ?>%)</td><td class="phai"><?php echo e($tien($tong['tax'])); ?></td></tr>
        <?php endif; ?>
        <tr class="tong-cuoi"><td colspan="<?php echo (int) $soCot - 1; ?>" class="phai">TỔNG CỘNG THANH TOÁN</td><td class="phai"><?php echo e($tien($tong['total'])); ?></td></tr>
        <?php /* Hai dòng dưới chỉ in khi chứng từ có bảo hiểm trả — xem in_chung_tu(). */ ?>
        <?php if ($tong['baoHiem'] > 0): ?>
        <tr class="tong"><td colspan="<?php echo (int) $soCot - 1; ?>" class="phai">Bảo hiểm trả<?php echo !empty($ct['baoHiemTen']) ? ' (' . e($ct['baoHiemTen']) . ')' : ''; ?></td><td class="phai"><?php echo e($tien($tong['baoHiem'])); ?></td></tr>
        <tr class="tong-cuoi"><td colspan="<?php echo (int) $soCot - 1; ?>" class="phai">KHÁCH HÀNG PHẢI TRẢ</td><td class="phai"><?php echo e($tien($tong['khachTra'])); ?></td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <?php /* Có bảo hiểm thì đọc số KHÁCH phải trả — đó mới là số khách đưa;
             đọc tổng cộng là khách tưởng mình phải trả cả phần hãng chịu. */ ?>
    <div class="bang-chu">Số tiền bằng chữ: <b><?php echo e(doc_so_tien($tong['khachTra'])); ?></b></div>

    <?php /* MÃ QR CHUYỂN KHOẢN (VietQR, 16/11/2026). $qr do in_chung_tu() dựng
             từ tài khoản của GARA LẬP PHIẾU; null khi gara chưa chọn ngân hàng /
             chưa có số TK — lúc đó rơi xuống khối chữ cũ bên dưới.
             Chữ cạnh mã đọc từ CHÍNH $qr (cùng nguồn với chuỗi trong mã), để giấy
             không thể ghi một số tiền mà mã chuyển số khác.
             Bản Word không chạy JS nên không vẽ được mã: chỉ in phần chữ. */ ?>
    <?php if (!empty($qr)): ?>
    <table class="khoi-ck">
        <tr>
            <?php if (empty($laWord)): ?>
            <td class="o-qr">
                <div class="vietqr" data-vietqr="<?php echo e($qr['chuoi']); ?>"><span class="qr-loi">Không vẽ được mã QR — chuyển khoản theo thông tin bên cạnh.</span></div>
                <div class="nhan-qr">Quét bằng app ngân hàng</div>
            </td>
            <?php endif; ?>
            <td class="chu-ck">
                <b>Thông tin chuyển khoản</b><br/>
                Ngân hàng: <b><?php echo e($qr['ngan_hang']); ?></b> — <?php echo e($qr['ngan_hang_day_du']); ?><br/>
                Số tài khoản: <b><?php echo e($qr['so_tk']); ?></b><br/>
                <?php if ($qr['chu_tk'] !== ''): ?>Chủ tài khoản: <b><?php echo e($qr['chu_tk']); ?></b><br/><?php endif; ?>
                Số tiền: <b><?php echo e($tien($qr['so_tien'])); ?> đ</b><br/>
                Nội dung: <b><?php echo e($qr['noi_dung']); ?></b>
            </td>
        </tr>
    </table>
    <?php elseif (!empty($ct['hienNganHang']) && $s('bank_account') !== ''): ?>
    <div class="khoi-nh">
        <b>Thông tin chuyển khoản</b><br/>
        Ngân hàng: <?php echo e($s('bank_name')); ?><br/>
        Số tài khoản: <b><?php echo e($s('bank_account')); ?></b><br/>
        Chủ tài khoản: <?php echo e($s('bank_holder')); ?><br/>
        Nội dung: <?php echo e($ct['so']); ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($ct['ghiChu'])): ?>
    <div class="khoi-nh">Ghi chú: <?php echo e($ct['ghiChu']); ?></div>
    <?php endif; ?>

    <table class="ky-ten">
        <tr>
            <td style="width:50%">
                <div class="chuc"><?php echo e($ct['nhanKy'][0]); ?></div>
                <div class="ghi">(Ký, ghi rõ họ tên)</div>
                <div class="cho-ky"></div>
            </td>
            <td style="width:50%">
                <div class="chuc"><?php echo e($ct['nhanKy'][1]); ?></div>
                <div class="ghi">(Ký, đóng dấu, ghi rõ họ tên)</div>
                <div class="cho-ky"></div>
            </td>
        </tr>
    </table>

</div>

<?php if (empty($laWord) && !empty($qr)): ?>
<?php /* VẼ MÃ QR TẠI CHỖ — qrcode-generator 1.4.4 (Kazuhiko Arase, MIT) từ cdnjs,
         ghim phiên bản + integrity: cdnjs có bị tráo file thì trình duyệt từ
         chối chạy, chứ không chạy một đoạn mã lạ đọc được số tài khoản và số
         tiền. Chuỗi VietQR dựng ở máy chủ (App\core\VietQr); thư viện chỉ vẽ,
         không gửi gì đi đâu — khác img.vietqr.io là phải gửi số tiền + số TK
         cho họ để lấy ảnh về.
         SVG chứ không phải ảnh điểm: in ra giấy nét ở mọi cỡ, máy in nào cũng
         quét được. Mức sửa lỗi M, lề trắng 4 ô theo chuẩn QR.
         Chạy TRƯỚC đoạn tự mở hộp thoại In bên dưới (thẻ <script src> chặn
         trang tới khi tải xong), nên lúc in mã đã có trên giấy. */ ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js"
        integrity="sha384-mZT2gIty7ZDdOGkxfP6joZcYdMW1Jvj9dRlfpTmaJAKKXTqzygtB22k7FLe+KZC1"
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<?php /* Vẽ: public/assets/js/vietqr.js — một chỗ vẽ chung cho mẫu in, trang đặt hàng,
         trang thanh toán công khai và danh sách hoá đơn. Không tải được thư viện thì
         giữ dòng chữ báo, phần chữ bên cạnh vẫn đủ để chuyển khoản. */ ?>
<script src="<?php echo asset('public/assets/js/vietqr.js'); ?>"></script>
<?php endif; ?>

<?php if (empty($laWord) && !empty($ct['tuMoHopIn'])): ?>
<script>
/* Mở sẵn hộp thoại In. Đợi ảnh logo vẽ xong rồi mới gọi, không thì một số
   trình duyệt in ra thiếu logo. Logo là data URI nên thường đã sẵn sàng,
   window.onload vẫn là chỗ chắc chắn nhất. */
window.addEventListener('load', function () { window.print(); });
</script>
<?php endif; ?>

</body>
</html>
