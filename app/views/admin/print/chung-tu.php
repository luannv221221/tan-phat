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
table.bang-hang th, table.bang-hang td { border: 1px solid #000; padding: 4px 6px; font-size: 11.5pt; }
table.bang-hang th { background: #e8e8e8; text-align: center; font-weight: bold; }
table.bang-hang td.giua { text-align: center; }
table.bang-hang td.phai { text-align: right; }
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
                <div class="dong-nho">
                    <?php if ($s('address') !== ''): ?>Địa chỉ: <?php echo e($s('address')); ?><br/><?php endif; ?>
                    <?php if ($s('hotline') !== ''): ?>Điện thoại: <?php echo e($s('hotline')); ?><?php endif; ?>
                    <?php if ($s('email') !== ''): ?> &nbsp;|&nbsp; Email: <?php echo e($s('email')); ?><?php endif; ?>
                    <?php if ($s('tax_code') !== ''): ?><br/>Mã số thuế: <?php echo e($s('tax_code')); ?><?php endif; ?>
                </div>
            </td>
        </tr>
    </table>

    <h1 class="tieu-de"><?php echo e($ct['loai']); ?></h1>
    <div class="so-ngay">
        Số: <b><?php echo e($ct['so']); ?></b>
        &nbsp;—&nbsp; Ngày <?php echo e($ngayVn($ct['ngay'])); ?>
        <?php if (!empty($ct['hieuLuc'])): ?>
        <br/>Báo giá có hiệu lực đến hết ngày <b><?php echo e($ngayVn($ct['hieuLuc'])); ?></b>
        <?php endif; ?>
    </div>

    <div class="khoi-khach">
        <span class="nhan">Khách hàng:</span>
        <b><?php echo e(!empty($khach['name']) ? $khach['name'] : 'Khách lẻ'); ?></b><br/>
        <?php if (!empty($khach['address'])): ?>
        <span class="nhan">Địa chỉ:</span><?php echo e($khach['address']); ?><br/>
        <?php endif; ?>
        <?php if (!empty($khach['phone'])): ?>
        <span class="nhan">Điện thoại:</span><?php echo e($khach['phone']); ?><br/>
        <?php endif; ?>
        <?php if (!empty($khach['tax_code'])): ?>
        <span class="nhan">Mã số thuế:</span><?php echo e($khach['tax_code']); ?><br/>
        <?php endif; ?>
    </div>

    <table class="bang-hang">
        <thead>
            <tr>
                <th style="width:10mm">TT</th>
                <th style="width:22mm">Mã</th>
                <th>Tên hàng hoá, dịch vụ</th>
                <th style="width:16mm">ĐVT</th>
                <th style="width:16mm">SL</th>
                <th style="width:26mm">Đơn giá</th>
                <th style="width:14mm">CK</th>
                <th style="width:30mm">Thành tiền</th>
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
            <tr class="nhom"><td colspan="8"><?php echo e($g[0]); ?></td></tr>
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
                <td class="phai"><?php echo (float) $d['discount_percent'] > 0
                    ? e(rtrim(rtrim(number_format((float) $d['discount_percent'], 2, '.', ''), '0'), '.')) . '%' : ''; ?></td>
                <td class="phai"><?php echo e($tien($d['amount'])); ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endforeach; ?>

        <?php if (empty($dong)): ?>
            <tr><td colspan="8" class="giua" style="padding:10px">(Chưa có dòng hàng nào)</td></tr>
        <?php endif; ?>

        <?php /* Tách riêng tiền hàng hoá và tiền dịch vụ khi chứng từ có cả hai —
                 gara muốn biết ngay phần vật tư và phần công thợ. */ ?>
        <?php if (!empty($nhomHang) && !empty($nhomDichVu)): ?>
        <tr class="tong"><td colspan="7" class="phai">Tiền hàng hoá</td><td class="phai"><?php echo e($tien($tong['hang'])); ?></td></tr>
        <tr class="tong"><td colspan="7" class="phai">Tiền dịch vụ</td><td class="phai"><?php echo e($tien($tong['dichvu'])); ?></td></tr>
        <?php endif; ?>
        <tr class="tong"><td colspan="7" class="phai">Cộng tiền hàng chưa thuế</td><td class="phai"><?php echo e($tien($tong['subtotal'])); ?></td></tr>
        <tr class="tong"><td colspan="7" class="phai">Thuế GTGT (<?php echo e(rtrim(rtrim(number_format((float) $tong['vat_rate'], 2, '.', ''), '0'), '.')); ?>%)</td><td class="phai"><?php echo e($tien($tong['tax'])); ?></td></tr>
        <tr class="tong-cuoi"><td colspan="7" class="phai">TỔNG CỘNG THANH TOÁN</td><td class="phai"><?php echo e($tien($tong['total'])); ?></td></tr>
        </tbody>
    </table>

    <div class="bang-chu">Số tiền bằng chữ: <b><?php echo e(doc_so_tien($tong['total'])); ?></b></div>

    <?php if (!empty($ct['hienNganHang']) && $s('bank_account') !== ''): ?>
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
