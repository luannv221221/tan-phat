<?php
/**
 * BIỂU MẪU IN — PHIẾU THU / PHIẾU CHI.
 *
 * Bố cục theo đúng ảnh minh hoạ số 2 của tài liệu khách gửi 14/10/2026:
 * tiêu đề giữa trang, khối Số / Nợ / Có nằm bên phải, rồi họ tên người nộp
 * tiền, địa chỉ, lý do, số tiền, bằng chữ, kèm theo — và năm ô chữ ký ở chân.
 *
 * Hai đường ra như mẫu báo giá / hoá đơn (xem chung-tu.php):
 *   - Mở trên trình duyệt -> bấm In -> "Lưu thành PDF"
 *   - Thêm ?word=1 -> tải về file .doc, mở bằng Word sửa được
 * KHÔNG dùng thư viện PDF: dự án không có gói composer nào, mà mấy thư viện đó
 * hay làm chữ tiếng Việt mất dấu hoặc thành ô vuông.
 *
 * Biến vào: $item (phiếu), $settings (cấu hình in của gara), $loaiChu,
 *           $urlWord, $laWord
 */
$s = function($key, $mac = '') use ($settings){
    return isset($settings[$key]) && $settings[$key] !== '' ? $settings[$key] : $mac;
};
$tien = function($n){ return number_format((float) $n, 0, ',', '.'); };

$ngay = date_create($item['voucher_date']);
$logo = logo_in_an($settings);
$laThu = $item['loai'] === 'thu';
?><!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8"/>
<title><?php echo e($loaiChu . ' ' . $item['voucher_no']); ?></title>
<style>
@page { size: A4 portrait; margin: 12mm; }

body { font-family: "Times New Roman", Times, serif; font-size: 13pt; color: #000;
       margin: 0; padding: 12mm; background: #f0f0f0; }
.to-in { background: #fff; box-sizing: border-box; width: 210mm; max-width: 100%;
         margin: 0 auto; padding: 10mm; box-shadow: 0 1px 6px rgba(0,0,0,.15); }

.thanh-cong-cu { max-width: 210mm; margin: 0 auto 10px; font-family: Arial, sans-serif; font-size: 11pt; }
.thanh-cong-cu a, .thanh-cong-cu button {
    display: inline-block; padding: 8px 16px; margin-right: 6px; border: 0; border-radius: 4px;
    background: #2f6fbf; color: #fff; text-decoration: none; cursor: pointer; font-size: 11pt; }
.thanh-cong-cu .phu { background: #6c757d; }

table { width: 100%; border-collapse: collapse; }
.dau td { vertical-align: top; padding: 0; }
.don-vi { font-size: 12pt; line-height: 1.5; }
.don-vi .ten { font-weight: bold; text-transform: uppercase; }

.tieu-de { text-align: center; margin: 6mm 0 2mm; }
.tieu-de h1 { font-size: 20pt; margin: 0 0 2mm; letter-spacing: 1px; }
.tieu-de .ngay { font-size: 12pt; font-style: italic; }

/* Khối Số / Nợ / Có bên phải, đúng như ảnh của khách */
.so-no-co { font-size: 12pt; line-height: 1.6; }
.so-no-co td { padding: 0 0 0 6px; }
.so-no-co .nhan { font-weight: bold; white-space: nowrap; width: 1%; }

.dong { margin: 0 0 2.5mm; font-size: 13pt; }
.dong .nhan { display: inline-block; min-width: 52mm; font-weight: bold; }
.dong .gt { border-bottom: 1px dotted #999; padding: 0 2px; }
.bang-chu { font-style: italic; }

.chu-ky { margin-top: 10mm; }
.chu-ky td { text-align: center; vertical-align: top; font-size: 12pt; padding: 0 2mm; }
.chu-ky .ten { font-weight: bold; }
.chu-ky .ghi { font-style: italic; font-size: 11pt; }
.chu-ky .cho-ky { height: 22mm; }

.da-huy { color: #b00; border: 2px solid #b00; display: inline-block;
          padding: 2px 10px; font-weight: bold; letter-spacing: 2px; }

@media print {
    body { background: #fff; padding: 0; }
    .to-in { width: auto; box-shadow: none; padding: 0; }
    .thanh-cong-cu { display: none; }
}
</style>
</head>
<body>

<?php if (empty($laWord)): ?>
<div class="thanh-cong-cu">
    <button onclick="window.print()">In / Lưu PDF</button>
    <a class="phu" href="<?php echo e($urlWord); ?>">Tải file Word</a>
</div>
<?php endif; ?>

<div class="to-in">

    <table class="dau">
        <tr>
            <td style="width:62%">
                <div class="don-vi">
                    <?php if ($logo !== ''): ?>
                    <div style="margin-bottom:2mm"><img src="<?php echo e($logo); ?>" alt="" style="max-height:18mm"></div>
                    <?php endif; ?>
                    <div class="ten"><?php echo e($s('site_name', 'Gara ô tô')); ?></div>
                    <?php if ($s('address') !== ''): ?>
                    <div>Địa chỉ: <?php echo e($s('address')); ?></div>
                    <?php endif; ?>
                    <?php if ($s('tax_code') !== ''): ?>
                    <div>MST: <?php echo e($s('tax_code')); ?></div>
                    <?php endif; ?>
                </div>
            </td>
            <td style="width:38%">
                <?php /* Số / Nợ / Có — khối bên phải tiêu đề, đúng ảnh của khách.
                         Nợ và Có là BẢN CHỤP lưu trên phiếu, không tra lại danh
                         mục: in lại phiếu cũ sau khi đổi định khoản của loại
                         phiếu vẫn phải ra đúng con số đã ký. */ ?>
                <table class="so-no-co">
                    <tr><td class="nhan">Số:</td><td><?php echo e($item['voucher_no']); ?></td></tr>
                    <tr><td class="nhan">Nợ:</td><td><?php echo e(!empty($item['debit_code']) ? $item['debit_code'] : '…'); ?></td></tr>
                    <tr><td class="nhan">Có:</td><td><?php echo e(!empty($item['credit_code']) ? $item['credit_code'] : '…'); ?></td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="tieu-de">
        <h1><?php echo e($loaiChu); ?></h1>
        <div class="ngay">
            Ngày <?php echo e($ngay ? $ngay->format('d') : '…'); ?>
            tháng <?php echo e($ngay ? $ngay->format('m') : '…'); ?>
            năm <?php echo e($ngay ? $ngay->format('Y') : '…'); ?>
        </div>
        <?php if ((int) $item['status'] !== 1): ?>
        <div style="margin-top:3mm"><span class="da-huy">ĐÃ HUỶ</span></div>
        <?php endif; ?>
    </div>

    <div class="dong">
        <span class="nhan">Họ và tên người <?php echo $laThu ? 'nộp' : 'nhận'; ?> tiền:</span>
        <span class="gt"><?php echo e(!empty($item['dt_ten']) ? $item['dt_ten'] : ''); ?></span>
    </div>
    <div class="dong">
        <span class="nhan">Địa chỉ:</span>
        <span class="gt"><?php echo e(!empty($item['dt_diachi']) ? $item['dt_diachi'] : ''); ?></span>
    </div>
    <div class="dong">
        <span class="nhan">Lý do <?php echo $laThu ? 'thu' : 'chi'; ?>:</span>
        <span class="gt"><?php echo e(!empty($item['ly_do']) ? $item['ly_do']
                                     : (!empty($item['vtype_name']) ? $item['vtype_name'] : '')); ?></span>
    </div>
    <div class="dong">
        <span class="nhan">Số tiền:</span>
        <span class="gt"><strong><?php echo e($tien($item['amount'])); ?> đ</strong></span>
    </div>
    <div class="dong bang-chu">
        <span class="nhan">Bằng chữ:</span>
        <span class="gt"><?php echo e(doc_so_tien($item['amount'])); ?></span>
    </div>
    <div class="dong">
        <span class="nhan">Kèm theo:</span>
        <span class="gt"><?php echo e(!empty($item['kem_theo']) ? $item['kem_theo'] : ''); ?></span>
        <span style="margin-left:10mm">chứng từ gốc.</span>
    </div>

    <?php /* NĂM Ô CHỮ KÝ theo đúng ảnh của khách. Ô "Người nộp tiền" đổi thành
             "Người nhận tiền" ở phiếu chi — in sai vai người ký là phiếu không
             dùng được. */ ?>
    <table class="chu-ky">
        <tr>
            <td class="ten">Giám đốc</td>
            <td class="ten">Kế toán trưởng</td>
            <td class="ten">Người <?php echo $laThu ? 'nộp' : 'nhận'; ?> tiền</td>
            <td class="ten">Người lập phiếu</td>
            <td class="ten">Thủ quỹ</td>
        </tr>
        <tr>
            <td class="ghi">(Ký, họ tên, đóng dấu)</td>
            <td class="ghi">(Ký, họ tên)</td>
            <td class="ghi">(Ký, họ tên)</td>
            <td class="ghi">(Ký, họ tên)</td>
            <td class="ghi">(Ký, họ tên)</td>
        </tr>
        <tr><td class="cho-ky"></td><td></td><td></td><td></td><td></td></tr>
    </table>

</div>
</body>
</html>
