<?php
/**
 * KẾ TOÁN — NĂM BÁO CÁO, và PHẦN BẢO HIỂM trên báo giá.
 *
 * Chạy:  C:\xampp\php\php.exe tests\BaoCaoVaBaoHiemTest.php
 *
 * Theo tài liệu khách gửi 14/10/2026 ("gara ô tô.docx"), migration 000097 +
 * 000098. Tiếp nối KeToanTest.php (nền kế toán + phiếu thu / phiếu chi).
 *
 * SÁU CHỖ HỎNG SẼ ÂM THẦM — báo cáo sai không làm gãy màn hình, nó chỉ ra một
 * con số khác. Mỗi chỗ một mục bên dưới:
 *
 *   1. CỘNG LÃI GỘP VỚI DÒNG TIỀN. Phiếu thu tiền khách KHÔNG phải doanh thu
 *      mới — nó thu hồi một khoản đã ghi doanh thu lúc xuất hoá đơn. Cộng
 *      "lãi gộp + thu − chi" là đếm doanh thu hai lần: ra số to, đẹp, và sai.
 *
 *   2. CÔNG NỢ TÍNH CẢ CHỨNG TỪ NHÁP. Hoá đơn nháp chưa phải khoản phải thu;
 *      tính vào là gara đi đòi một món khách chưa từng nợ. Phía nhà cung cấp
 *      còn thêm bẫy: `nhap_khac` / `nhap_tra` / `ton_dau_ky` không sinh công
 *      nợ — gộp vào là gara "nợ" chính mình đúng bằng số tồn lúc mở sổ.
 *
 *   3. PHIẾU ĐÃ HUỶ VẪN NẰM TRONG TỔNG. Huỷ phiếu mà số không đổi thì cái nút
 *      Huỷ chỉ là trang trí.
 *
 *   4. GỘP KỲ THEO THỨ TỰ THAY VÌ THEO KHOÁ. Hai truy vấn thu / chi trả về hai
 *      danh sách kỳ khác nhau (tháng có thu chưa chắc có chi). Ghép theo thứ
 *      tự là số chi của tháng 3 nhảy lên dòng tháng 2.
 *
 *   5. BÁO GIÁ LƯU "KHÁCH TRẢ" THÀNH CỘT RIÊNG. Hai nguồn sự thật cho cùng một
 *      con số: sửa dòng hàng thì tổng đổi, cột đã lưu đứng yên.
 *
 *   6. BẢO HIỂM TRẢ VƯỢT TỔNG CỘNG. Gõ thừa một số 0 thì "khách trả" ra âm —
 *      tờ báo giá in ra nhìn như gara nợ lại khách.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/helpers/functions.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

// ---------------------------------------------------------------------------
section('Migration + noi day');

foreach (['man_bao_cao_ke_toan' => '000097', 'bao_hiem_va_bao_gia' => '000098'] as $ten => $so){
    $mg = glob($goc . 'database/migrations/*_' . $ten . '.php');
    ok(count($mg) === 1, "Co migration $so ($ten)");
}

$m98 = glob($goc . 'database/migrations/*_bao_hiem_va_bao_gia.php');
$s98 = count($m98) === 1 ? file_get_contents($m98[0]) : '';
ok(strpos($s98, '`insurers`') !== false, 'Migration tao bang `insurers`');
foreach (['insurer_id', 'bao_hiem_tra'] as $cot){
    ok(strpos($s98, "`$cot`") !== false, "Migration them `quotations`.`$cot`");
}
/* CHOT 5 — khong duoc co cot "khach tra". */
ok(!preg_match('~ADD COLUMN `(khach_tra|customer_pay)`~i', $s98),
   'KHONG luu "khach tra" thanh cot rieng',
   'Khach tra = tong cong - bao hiem tra; luu ca hai la hai nguon su that');

$rt = codeOnly($goc . 'routes/web.php');
foreach (['bao-cao-ke-toan', 'insurers', 'insurers/add', 'insurers/edit/(\d+)'] as $r){
    ok(strpos($rt, "'" . $r . "'") !== false, "Co route `$r`");
}
/* Bao cao CHI XEM: co duong ghi la co duong sua so lieu ngoai chung tu. */
foreach (['bao-cao-ke-toan/add', 'bao-cao-ke-toan/edit', 'bao-cao-ke-toan/delete'] as $r){
    ok(strpos($rt, "'" . $r) === false, "Bao cao KHONG co duong ghi `$r`");
}

$sb = file_get_contents($goc . 'app/views/layouts/admin/sidebar.php');
ok(strpos($sb, "'bao-cao-ke-toan'") !== false, 'Bao cao ke toan co tren menu');
ok(strpos($sb, "'insurers'") !== false,        'Danh muc bao hiem co tren menu');

// ---------------------------------------------------------------------------
section('Man bao cao — khong cong lai gop voi dong tien (chot 1)');

$bc = file_get_contents($goc . 'app/controllers/admin/Baocaoketoan.php');

ok(!preg_match('~lai_gop[^;\n]*\+[^;\n]*(chenh|\bthu\b)~u', $bc)
   && !preg_match('~(chenh|\bthu\b)[^;\n]*\+[^;\n]*lai_gop~u', $bc),
   'Khong cong lai gop voi dong tien o controller',
   'Cong vao la dem doanh thu hai lan');

$vw = file_get_contents($goc . 'app/views/admin/bao-cao-ke-toan/index.php');
ok(mb_stripos($vw, 'không') !== false && mb_stripos($vw, 'cộng') !== false,
   'Man hinh co canh bao khong cong hai khoi',
   'Nguoi doc thay hai khoi canh nhau la tu cong lai');

/* Lai gop = doanh thu - gia von, KHONG tru thue: thue GTGT dau ra khong phai
   chi phi cua gara, no thu ho nha nuoc. */
ok(preg_match("~'lai_gop'\s*=>\s*\\\$doanhThu\s*-\s*\\\$giaVon~u", $bc) === 1,
   'Lai gop = doanh thu - gia von (khong tru thue GTGT)');

// ---------------------------------------------------------------------------
section('Chay that tren MySQL');

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

/* Moi dong test mang tien to nay de don sach ke ca khi test chet giua chung */
const DAU = 'ZZBC';

/* ĐÓNG VAI MỘT GARA. Mọi model báo cáo lọc theo gara đang làm việc; chạy ở
   dòng lệnh mà không ép thì garaLoc() trả null — truy vấn quét cả hệ thống và
   con số test đọc được là tổng của mọi gara, tức test không kiểm gì cả. */
$GARA = (int) $pdo->query("SELECT `id` FROM `garages` WHERE `is_master` = 1 ORDER BY `id` LIMIT 1")->fetchColumn();
ok($GARA > 0, "Tim duoc gara de dong vai (#$GARA)");
\App\core\Model::epGara($GARA);

$donDep = function() use ($pdo){
    $pdo->exec("DELETE FROM `acc_vouchers`   WHERE `voucher_no` LIKE '" . DAU . "%'");
    $pdo->exec("DELETE FROM `quotations`     WHERE `quote_no`   LIKE '" . DAU . "%'");
    $pdo->exec("DELETE FROM `sales_invoices` WHERE `invoice_no` LIKE '" . DAU . "%'");
    $pdo->exec("DELETE FROM `goods_receipts` WHERE `receipt_no` LIKE '" . DAU . "%'");
    $pdo->exec("DELETE FROM `insurers`       WHERE `code`       LIKE '" . DAU . "%'");
    $pdo->exec("DELETE FROM `partners`       WHERE `code`       LIKE '" . DAU . "%'");
};
$donDep();

// ---------------------------------------------------------------------------
section('Danh muc bao hiem — Tan Phat khai san, dung chung');

$gieo = (int) $pdo->query("SELECT COUNT(*) FROM `insurers` WHERE `garage_id` IS NULL")->fetchColumn();
ok($gieo >= 8, "Co $gieo hang dung chung (mong >= 8)");

/* Ban gieo san CHI CO ma va ten: so tai khoan ngan hang khac nhau theo tung
   gara, dien bua vao ban dung chung la gara chuyen nham tien vi tin phan mem. */
$coSTK = (int) $pdo->query("SELECT COUNT(*) FROM `insurers`
                             WHERE `garage_id` IS NULL
                               AND `bank_account` IS NOT NULL AND `bank_account` <> ''")->fetchColumn();
ok($coSTK === 0, 'Ban dung chung KHONG dien san so tai khoan ngan hang',
   'So tai khoan khac nhau theo tung gara — dien bua la gara chuyen nham tien');

// ---------------------------------------------------------------------------
section('Cong no — chi chung tu DA GHI SO (chot 2)');

$ngay = date('Y-m-d');
$pdo->exec("INSERT INTO `partners` (`code`,`name`,`type`,`status`,`garage_id`,`create_at`)
            VALUES ('" . DAU . "KH','"  . DAU . " Khach thu','customer',1,$GARA,NOW()),
                   ('" . DAU . "NCC','" . DAU . " NCC thu','supplier',1,$GARA,NOW())");
$kh  = (int) $pdo->query("SELECT `id` FROM `partners` WHERE `code` = '" . DAU . "KH'")->fetchColumn();
$ncc = (int) $pdo->query("SELECT `id` FROM `partners` WHERE `code` = '" . DAU . "NCC'")->fetchColumn();
$kho = (int) $pdo->query("SELECT `id` FROM `warehouses` WHERE `garage_id` = $GARA ORDER BY `id` LIMIT 1")->fetchColumn();
ok($kho > 0, "Gara co san mot kho de gan phieu nhap (#$kho)");

/* Hai hoa don cho cung mot khach: MOT ghi so, MOT con nhap. */
$hd = $pdo->prepare("INSERT INTO `sales_invoices`
        (`invoice_no`,`invoice_date`,`customer_id`,`subtotal`,`tax_amount`,`total_amount`,
         `cost_amount`,`status`,`garage_id`,`create_at`)
        VALUES (?,?,?,?,?,?,?,?,?,NOW())");
$hd->execute([DAU . 'HD1', $ngay, $kh, 1000000, 100000, 1100000, 600000, 1, $GARA]); // da ghi so
$hd->execute([DAU . 'HD2', $ngay, $kh,  500000,  50000,  550000, 300000, 0, $GARA]); // con nhap

$pn = $pdo->prepare("INSERT INTO `goods_receipts`
        (`receipt_no`,`receipt_date`,`partner_id`,`receipt_type`,`total_amount`,`status`,
         `warehouse_id`,`garage_id`,`create_at`)
        VALUES (?,?,?,?,?,?,?,?,NOW())");
$pn->execute([DAU . 'PN1', $ngay, $ncc, 'nhap_mua',   800000, 1, $kho, $GARA]); // sinh cong no
$pn->execute([DAU . 'PN2', $ngay, $ncc, 'nhap_khac',  900000, 1, $kho, $GARA]); // KHONG sinh
$pn->execute([DAU . 'PN3', $ngay, $ncc, 'ton_dau_ky', 700000, 1, $kho, $GARA]); // KHONG sinh
$pn->execute([DAU . 'PN4', $ngay, $ncc, 'nhap_mua',   400000, 0, $kho, $GARA]); // con nhap

$inv = \App\core\Load::model('SalesInvoicesModel');
$grc = \App\core\Load::model('GoodsReceiptsModel');

$ban = $inv->tienBanTheoKhach($ngay);
ok(isset($ban[$kh]) && abs($ban[$kh]['tien'] - 1100000) < 0.01,
   'Tien ban chi tinh hoa don DA GHI SO: ' . (isset($ban[$kh]) ? $ban[$kh]['tien'] : 'khong thay'),
   'Hoa don nhap chua phai khoan phai thu');

$mua = $grc->tienMuaTheoNcc($ngay);
ok(isset($mua[$ncc]) && abs($mua[$ncc]['tien'] - 800000) < 0.01,
   'Tien mua chi tinh `nhap_mua` da ghi so: ' . (isset($mua[$ncc]) ? $mua[$ncc]['tien'] : 'khong thay'),
   'nhap_khac / ton_dau_ky khong sinh cong no — gop vao la gara "no" chinh minh');

// ---------------------------------------------------------------------------
section('Phieu da huy roi khoi moi phep cong (chot 3)');

$lt = $pdo->query("SELECT `id` FROM `acc_voucher_types` WHERE `loai` = 'thu' LIMIT 1")->fetchColumn();
ok(!empty($lt), 'Co san mot loai phieu thu de dung lam du lieu thu');

if (!empty($lt)){
    $pv = $pdo->prepare("INSERT INTO `acc_vouchers`
            (`voucher_no`,`voucher_date`,`loai`,`vtype_id`,`partner_id`,`dt_ten`,
             `amount`,`status`,`garage_id`,`create_at`)
            VALUES (?,?,'thu',?,?,?,?,?,?,NOW())");
    $pv->execute([DAU . 'PT1', $ngay, $lt, $kh, DAU . ' Khach thu', 300000, 1, $GARA]); // hieu luc
    $pv->execute([DAU . 'PT2', $ngay, $lt, $kh, DAU . ' Khach thu', 999000, 0, $GARA]); // da huy

    $vch = \App\core\Load::model('AccVouchersModel');

    $tongDt = $vch->tongTheoDoiTuong('thu', $ngay);
    ok(isset($tongDt[$kh]) && abs($tongDt[$kh] - 300000) < 0.01,
       'Tong theo doi tuong bo qua phieu da huy: ' . (isset($tongDt[$kh]) ? $tongDt[$kh] : 'khong thay'));

    /* Cong no cua khach nay = 1.100.000 (da ghi so) - 300.000 (da thu) */
    $con = (isset($ban[$kh]) ? $ban[$kh]['tien'] : 0) - (isset($tongDt[$kh]) ? $tongDt[$kh] : 0);
    ok(abs($con - 800000) < 0.01, "Khach con no 800.000 (tinh ra $con)");
}

// ---------------------------------------------------------------------------
section('Gom ky theo KHOA chu khong theo thu tu (chot 4)');

/* Dung lai thuat toan cua controller tren hai danh sach ky LECH NHAU: thang 02
   chi co chi, thang 03 chi co thu. Ghep theo thu tu thi so chi cua thang 02
   nhay len dong thang 01 va ca bang lech mot o. */
$gom = function(array $thu, array $chi){
    $ra = [];
    foreach ($thu as $r) $ra[$r['ky']] = ['ky' => $r['ky'], 'thu' => (float) $r['s'], 'chi' => 0.0];
    foreach ($chi as $r){
        if (!isset($ra[$r['ky']])) $ra[$r['ky']] = ['ky' => $r['ky'], 'thu' => 0.0, 'chi' => 0.0];
        $ra[$r['ky']]['chi'] = (float) $r['s'];
    }
    ksort($ra);
    return array_values($ra);
};
$bang = $gom(
    [['ky' => '2026-01', 's' => 10], ['ky' => '2026-03', 's' => 30]],
    [['ky' => '2026-01', 's' => 1],  ['ky' => '2026-02', 's' => 2]]);

ok(count($bang) === 3, 'Gop ra du 3 ky (01, 02, 03)');
ok($bang[0]['ky'] === '2026-01' && $bang[0]['thu'] == 10 && $bang[0]['chi'] == 1, 'Thang 01: thu 10 / chi 1');
ok($bang[1]['ky'] === '2026-02' && $bang[1]['thu'] == 0  && $bang[1]['chi'] == 2, 'Thang 02: chi 2, khong co thu');
ok($bang[2]['ky'] === '2026-03' && $bang[2]['thu'] == 30 && $bang[2]['chi'] == 0, 'Thang 03: thu 30, khong co chi');

/* Cach gom ky phai la danh sach TRANG — DATE_FORMAT nhan chuoi di tu URL vao. */
$vm = file_get_contents($goc . 'app/models/AccVouchersModel.php');
ok(preg_match('~DATE_FORMAT\([^)]*\$\w+~', $vm) !== 1,
   'KHONG noi thang bien vao DATE_FORMAT',
   '?theo= di thang vao SQL la lo hong injection');

// ---------------------------------------------------------------------------
section('Bao gia — bao hiem tra / khach tra (chot 5)');

/* Bao gia 1.100.000, bao hiem chiu 800.000 -> khach tra 300.000 */
$pdo->exec("INSERT INTO `insurers` (`code`,`name`,`sort_order`,`status`,`garage_id`,`create_at`)
            VALUES ('" . DAU . "BH','" . DAU . " Hang thu',0,1,NULL,NOW())");
$bhId = (int) $pdo->query("SELECT `id` FROM `insurers` WHERE `code` = '" . DAU . "BH'")->fetchColumn();

$bg = $pdo->prepare("INSERT INTO `quotations`
        (`quote_no`,`quote_date`,`customer_id`,`subtotal`,`vat_rate`,`tax_amount`,`total_amount`,
         `insurer_id`,`bao_hiem_tra`,`status`,`garage_id`,`create_at`)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())");
$bg->execute([DAU . 'BG1', $ngay, $kh, 1000000, 10, 100000, 1100000, $bhId, 800000, 0, $GARA]);

$row = $pdo->query("SELECT * FROM `quotations` WHERE `quote_no` = '" . DAU . "BG1'")->fetch(PDO::FETCH_ASSOC);
ok(!empty($row), 'Luu duoc bao gia co bao hiem');
ok(!empty($row) && abs((float) $row['bao_hiem_tra'] - 800000) < 0.01, 'Cot `bao_hiem_tra` giu dung 800.000');
ok(!empty($row) && !array_key_exists('khach_tra', $row), 'Bang `quotations` KHONG co cot `khach_tra`');

/* Xoa hang bao hiem thi bao gia cu MAT TEN HANG chu khong bien mat. */
$pdo->exec("DELETE FROM `insurers` WHERE `id` = $bhId");
$conBg = $pdo->query("SELECT `insurer_id` FROM `quotations` WHERE `quote_no` = '" . DAU . "BG1'")->fetch(PDO::FETCH_ASSOC);
ok(!empty($conBg) && $conBg['insurer_id'] === null,
   'Xoa hang bao hiem: bao gia con nguyen, `insurer_id` ve NULL',
   'ON DELETE SET NULL — RESTRICT thi khong bao gio don duoc danh muc');

// ---------------------------------------------------------------------------
section('Bieu mau in — tach khach tra, kep lai bang tong (chot 6)');

/* Mẫu in đọc hằng PartsModel::LOAI_DICH_VU để tách nhóm hàng / dịch vụ. Model
   của dự án không nằm trong autoload của composer (vendor/ chỉ có autoload.php
   rỗng), nên phải nạp tay trước khi gọi in_chung_tu(). */
\App\core\Load::model('PartsModel');

$inCt = function($total, $baoHiem, $ten = null){
    ob_start();
    in_chung_tu(
        ['loai' => 'BÁO GIÁ', 'so' => DAU . 'BG1', 'ngay' => date('Y-m-d'),
         'subtotal' => $total, 'vatRate' => 0, 'tax' => 0, 'total' => $total,
         'baoHiem' => $baoHiem, 'baoHiemTen' => $ten,
         'hienNganHang' => false, 'nhanKy' => ['KHÁCH HÀNG', 'ĐẠI DIỆN BÊN BÁN']],
        ['name' => DAU . ' Khach thu'],
        [['item_type' => 'part', 'amount' => $total, 'quantity' => 1, 'unit' => 'cai',
          'unit_price' => $total, 'part_code' => 'VT01', 'part_name' => 'Vat tu',
          'discount_percent' => 0]],
        cau_hinh_in_an(null), '', false);
    return ob_get_clean();
};

$htmlCo = $inCt(1100000, 800000, 'Bảo hiểm Bảo Việt');
ok(mb_strpos($htmlCo, 'Bảo hiểm trả') !== false,        'Co bao hiem: in dong "Bao hiem tra"');
ok(mb_strpos($htmlCo, 'KHÁCH HÀNG PHẢI TRẢ') !== false, 'Co bao hiem: in dong "Khach hang phai tra"');
ok(mb_strpos($htmlCo, 'Bảo hiểm Bảo Việt') !== false,   'In kem ten hang bao hiem');
ok(mb_strpos($htmlCo, '300.000') !== false,             'Khach tra = 1.100.000 - 800.000 = 300.000');
/* So tien bang chu phai doc so KHACH tra, khong phai tong cong. */
ok(mb_strpos($htmlCo, doc_so_tien(300000)) !== false,
   'So tien bang chu doc so KHACH tra',
   'Doc tong cong la khach tuong minh phai tra ca phan hang chiu');

$htmlKhong = $inCt(1100000, 0);
ok(mb_strpos($htmlKhong, 'Bảo hiểm trả') === false,
   'Khong co bao hiem: KHONG in dong "Bao hiem tra"',
   'Moi phieu deu mang them mot dong "Bao hiem tra 0" la rac giay');
ok(mb_strpos($htmlKhong, doc_so_tien(1100000)) !== false,
   'Khong co bao hiem: bang chu van doc tong cong');

/* CHOT 6 — go thua mot so 0. */
$htmlVuot = $inCt(1100000, 99000000);
ok(!preg_match('~>\s*-[\d.]+\s*<~', $htmlVuot),
   'Bao hiem tra vuot tong: khach tra KHONG ra so am',
   'In ra "khach tra -2.000.000" la nhin nhu gara no lai khach');
ok(mb_strpos($htmlVuot, doc_so_tien(0)) !== false,
   'Bao hiem tra vuot tong: khach tra bi kep ve 0');

// ---------------------------------------------------------------------------
section('Man lap bao gia — o chon hang + o so tien');

foreach (['add', 'edit'] as $man){
    $v = file_get_contents($goc . 'app/views/admin/quotations/' . $man . '.php');
    ok(strpos($v, 'name="insurer_id"') !== false,   "quotations/$man.php: co o CHON hang bao hiem");
    ok(strpos($v, 'name="bao_hiem_tra"') !== false, "quotations/$man.php: co o nhap bao hiem tra");
    /* Tien phai la type="number" — go "abc" ma van troi la loi da bi bao mot
       lan roi, xem ItemTypeTest. */
    ok(preg_match('~type="number"[^>]*name="bao_hiem_tra"~', $v) === 1
       || preg_match('~name="bao_hiem_tra"[^>]*type="number"~', $v) === 1,
       "quotations/$man.php: o bao hiem tra la type=\"number\"");
    /* Hai dong tong phai an khi khong co bao hiem — JS bat/tat. */
    ok(strpos($v, 'js-dong-bh') !== false,   "quotations/$man.php: co hai dong tong bao hiem");
    ok(strpos($v, 'js-khach-tra') !== false, "quotations/$man.php: co o hien 'khach tra'");
    ok(preg_match('~Math\.min\(money\(bhEl~', $v) === 1,
       "quotations/$man.php: JS kep bao hiem tra lai bang tong",
       'Khong kep thi o "khach tra" tren man hinh ra so am');
}

$donDep();
\App\core\Model::epGara(null);
exit(summary());
