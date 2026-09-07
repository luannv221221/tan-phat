<?php
/**
 * GIEO DỮ LIỆU MẪU cho tính năng "nhiều gara" — để xem thử, không phải dữ liệu thật.
 *
 *   C:\xampp\php\php.exe tools\tao-du-lieu-gara.php          -> gieo
 *   C:\xampp\php\php.exe tools\tao-du-lieu-gara.php --xoa    -> xoá sạch, trả nguyên trạng
 *
 * Gieo ra hai gara chi nhánh (gara tổng đã có sẵn từ migration), mỗi gara một
 * danh mục riêng khác nhau, để nhìn thấy ngay:
 *   - ô đổi gara trên thanh đầu trang (chỉ hiện khi có từ 2 gara)
 *   - nút chọn nguồn ở form Lập báo giá, bấm qua lại đổi danh sách hàng
 *   - giá riêng của gara đè giá kho tổng
 *   - hàng riêng của gara A không lọt sang gara B
 *
 * MỌI THỨ GIEO RA ĐỀU CÓ DẤU để --xoa gỡ lại đúng và đủ:
 *   gara            code bắt đầu bằng "DM"
 *   hàng riêng      code bắt đầu bằng "DM..-"
 *   giá riêng       thuộc về các gara đó
 *
 * TRỪ MỘT THỨ: gara TỔNG cũng được gieo danh mục riêng (nếu không thì người
 * đăng nhập bằng tài khoản trụ sở mở form báo giá ra vẫn thấy nút gara ghi
 * "chưa có"). Gara tổng là dữ liệu THẬT, không mang mã "DM", nên --xoa không
 * thể dựa vào mã mà nhận ra. Vì vậy lúc gieo có ghi lại danh sách mặt hàng đã
 * chọn cho gara tổng vào một file nhật ký, và --xoa chỉ gỡ đúng những dòng đó.
 *
 * Không đoán bừa: mất file nhật ký thì --xoa báo ra và BỎ QUA phần gara tổng,
 * chứ không xoá sạch bảng giá — người dùng có thể đã tự chọn hàng cho trụ sở.
 *
 * KHÔNG tạo tài khoản đăng nhập: tài khoản là thứ có mật khẩu, không nên sinh
 * bừa. Muốn thử "nhân viên thuộc gara nào" thì vào Hệ thống → Người dùng, sửa
 * ô Gara của một tài khoản có sẵn.
 *
 * KHÔNG đụng vào dữ liệu đang có, trừ một chỗ: kho "Kho chi nhánh Miền Nam"
 * được chuyển sang gara Sài Gòn cho đúng nghĩa (kho chi nhánh thì thuộc chi
 * nhánh). --xoa trả nó về gara tổng.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/models/GaragesModel.php';
require_once __DIR__ . '/../app/models/GaragePricesModel.php';
require_once __DIR__ . '/../app/models/PartsModel.php';

$db = new PDO(
    'mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
    _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$GA = new GaragesModel();
$GP = new GaragePricesModel();
$P  = new PartsModel();

$xoa = in_array('--xoa', $argv, true);

/* Nhật ký những gì đã gieo vào dữ liệu THẬT (gara tổng) — xem đầu file. */
$fileNhatKy = __DIR__ . '/../deploy/du-lieu-mau-gara.json';

/* ------------------------------------------------------------------ *
 * XOÁ
 * ------------------------------------------------------------------ */
if ($xoa){
    $ids = $db->query("SELECT id FROM `garages` WHERE `code` LIKE 'DM%'")->fetchAll(PDO::FETCH_COLUMN);
    if (empty($ids)){
        echo "Khong co du lieu mau nao de xoa.\n";
        exit(0);
    }
    $holes = implode(',', array_fill(0, count($ids), '?'));

    // Kho mượn phải trả TRƯỚC khi xoá gara: khoá ngoại của warehouses là
    // SET NULL, xoá thẳng thì kho thành vô chủ chứ không quay về gara tổng.
    $master = $GA->getMaster();
    $st = $db->prepare("UPDATE `warehouses` SET `garage_id` = ? WHERE `garage_id` IN ($holes)");
    $st->execute(array_merge([(int) $master['id']], $ids));
    echo "Da tra " . $st->rowCount() . " kho ve gara tong.\n";

    foreach ([
        "DELETE FROM `garage_part_prices` WHERE `garage_id` IN ($holes)" => 'dong gia rieng',
        "DELETE FROM `parts` WHERE `garage_id` IN ($holes)"              => 'mat hang rieng',
    ] as $sql => $nhan){
        $st = $db->prepare($sql); $st->execute($ids);
        echo "Da xoa " . $st->rowCount() . " $nhan.\n";
    }

    $st = $db->prepare("DELETE FROM `garages` WHERE id IN ($holes)");
    $st->execute($ids);
    echo "Da xoa " . $st->rowCount() . " gara mau.\n";

    /* Gara TỔNG: chỉ gỡ đúng những mặt hàng nhật ký ghi là do lần gieo tạo ra.
       Không có nhật ký thì thà bỏ qua còn hơn xoá nhầm thứ người dùng tự chọn. */
    if (!is_file($fileNhatKy)){
        echo "\nKHONG THAY file nhat ky (" . basename($fileNhatKy) . ").\n";
        echo "Bo qua phan danh muc cua gara TONG — go tay o Hang hoa -> Danh muc cua gara.\n";
    } else {
        $nk = json_decode(file_get_contents($fileNhatKy), true);
        $gid = isset($nk['gara_tong_id']) ? (int) $nk['gara_tong_id'] : 0;
        $pid = isset($nk['gara_tong_parts']) && is_array($nk['gara_tong_parts'])
             ? array_map('intval', $nk['gara_tong_parts']) : [];

        if ($gid > 0 && !empty($pid)){
            $h2 = implode(',', array_fill(0, count($pid), '?'));
            $st = $db->prepare("DELETE FROM `garage_part_prices`
                                 WHERE `garage_id` = ? AND `part_id` IN ($h2)");
            $st->execute(array_merge([$gid], $pid));
            echo "Da xoa " . $st->rowCount() . " dong danh muc cua gara tong.\n";
        }
        @unlink($fileNhatKy);
    }

    echo "\nDa tra ve nguyen trang.\n";
    exit(0);
}

/* ------------------------------------------------------------------ *
 * GIEO
 * ------------------------------------------------------------------ */
$master = $GA->getMaster();
if (empty($master)){
    echo "Chua co gara tong. Chay `php migrate.php` truoc da.\n";
    exit(1);
}
echo "Gara tong: {$master['code']} — {$master['name']}\n\n";

$tong = $P->theoNguon(PartsModel::NGUON_TONG);
if (count($tong) < 4){
    echo "Danh muc tong chi co " . count($tong) . " mat hang, chua du de chia.\n";
    exit(1);
}

/** Thêm gara nếu chưa có, trả về id */
function themGara(GaragesModel $GA, $code, $ten, $diaChi, $dienThoai, $thuTu){
    $co = $GA->findByCode($code);
    if (!empty($co)){
        echo "  $code — $ten (da co, dung lai)\n";
        return (int) $co['id'];
    }
    $id = $GA->add([
        'code' => $code, 'name' => $ten, 'address' => $diaChi, 'phone' => $dienThoai,
        'is_master' => 0, 'status' => 1, 'sort_order' => $thuTu,
    ]);
    echo "  $code — $ten (moi tao)\n";
    return (int) $id;
}

echo "1. Tao gara chi nhanh\n";
$sg = themGara($GA, 'DMSG', 'Tân Phát Sài Gòn',
                'KCN Sóng Thần, Dĩ An, Bình Dương', '0274 3777 999', 10);
$dn = themGara($GA, 'DMDN', 'Tân Phát Đà Nẵng',
                '215 Nguyễn Hữu Thọ, Hải Châu, Đà Nẵng', '0236 3888 777', 11);

/* Kho chi nhánh về đúng chi nhánh. Chỉ đụng kho có mã KHO02 và đang thuộc
   gara tổng — ai đã tự gán kho đó đi đâu rồi thì giữ nguyên ý của họ. */
echo "\n2. Gan kho chi nhanh cho gara Sai Gon\n";
$st = $db->prepare("UPDATE `warehouses` SET `garage_id` = ?
                     WHERE `code` = 'KHO02' AND `garage_id` = ?");
$st->execute([$sg, (int) $master['id']]);
echo "  " . ($st->rowCount() ? "Kho chi nhanh Mien Nam -> Tan Phat Sai Gon" : "(khong co gi de doi)") . "\n";

/* ---- Danh mục riêng ---------------------------------------------------
   Hai gara chọn hai tập khác nhau, và giá khác nhau, thì bấm qua lại giữa
   hai nguồn mới thấy rõ tác dụng. Sài Gòn lấy nhiều và hạ giá; Đà Nẵng lấy
   ít, giữ giá tổng. */
echo "\n3. Dung danh muc rieng\n";

$capGia = function($garaId, array $hang, $tenGara) use ($GP){
    $them = 0;
    foreach ($hang as $pid => $gia){
        $GP->datGia($garaId, $pid, $gia);
        $them++;
    }
    echo "  $tenGara: chon $them mat hang tu kho tong\n";
};

/* Gara tổng cũng phải có danh mục riêng, nếu không thì người đăng nhập bằng
   tài khoản của trụ sở mở form báo giá ra vẫn thấy nút gara ghi "chưa có" —
   đúng cảnh không xem được gì. Trụ sở bán gần hết danh mục, giá gốc. */
$hangTP = [];
foreach (array_slice($tong, 0, 12) as $r) $hangTP[(int) $r['id']] = '';

/* Ghi nhật ký TRƯỚC khi gieo: gara tổng là dữ liệu thật, --xoa phải biết chính
   xác dòng nào do lần gieo này tạo ra thì mới dám gỡ. Chỉ ghi những mặt hàng
   gara tổng CHƯA có, để chạy lần hai không nhận vơ thứ đã có sẵn. */
$daCo    = $GP->theoGara((int) $master['id']);
$moiThem = array_values(array_diff(array_keys($hangTP), array_keys($daCo)));

$cu = is_file($fileNhatKy) ? json_decode(file_get_contents($fileNhatKy), true) : [];
$goc = isset($cu['gara_tong_parts']) && is_array($cu['gara_tong_parts']) ? $cu['gara_tong_parts'] : [];

@mkdir(dirname($fileNhatKy), 0777, true);
file_put_contents($fileNhatKy, json_encode([
    'gieo_luc'        => date('Y-m-d H:i:s'),
    'gara_tong_id'    => (int) $master['id'],
    'gara_tong_parts' => array_values(array_unique(array_merge($goc, $moiThem))),
], JSON_PRETTY_PRINT));

$capGia((int) $master['id'], $hangTP, 'Tan Phat (tru so)');

// Sài Gòn: 6 mặt hàng đầu, hạ 10% so với giá tổng cho 3 cái đầu
$hangSG = [];
foreach (array_slice($tong, 0, 6) as $i => $r){
    $goc = (float) (!empty($r['sale_price']) ? $r['sale_price'] : $r['price']);
    $hangSG[(int) $r['id']] = $i < 3 ? (string) ((int) round($goc * 0.9 / 1000) * 1000) : '';
}
$capGia($sg, $hangSG, 'Tan Phat Sai Gon');

// Đà Nẵng: 3 mặt hàng, tất cả theo giá tổng
$hangDN = [];
foreach (array_slice($tong, 2, 3) as $r) $hangDN[(int) $r['id']] = '';
$capGia($dn, $hangDN, 'Tan Phat Da Nang');

/* ---- Hàng riêng -------------------------------------------------------
   Phần lớn thứ gara tự thêm là công thợ — đúng thứ mỗi chi nhánh một giá. */
echo "\n4. Them hang rieng cua tung gara\n";

$donVi = function($ten) use ($db){
    $st = $db->prepare("SELECT id FROM `part_units` WHERE `name` = ? LIMIT 1");
    $st->execute([$ten]);
    $id = $st->fetchColumn();
    return $id ? (int) $id : null;
};
$lan = $donVi('Lần');
$gio = $donVi('Giờ');

$themRieng = function($garaId, $maGara, $ten, $loai, $gia, $unit)
              use ($P, $db){
    $st = $db->prepare("SELECT id FROM `parts` WHERE `garage_id` = ? AND `name` = ? LIMIT 1");
    $st->execute([$garaId, $ten]);
    if ($st->fetchColumn()){ echo "     $ten (da co)\n"; return; }

    $P->add([
        'code'        => $P->nextCode($maGara . '-'),
        'name'        => $ten,
        'slug'        => $P->slugTrong($ten),
        'item_type'   => $loai,
        'unit_id'     => $unit,
        'price'       => $gia,
        'garage_id'   => $garaId,
        'status'      => 1,
        // Hàng riêng không lên website chung — xem Garagecatalog::postThemRieng()
        'show_on_web' => 0,
    ]);
    echo "     $ten — " . number_format($gia) . " d\n";
};

echo "  Tan Phat Sai Gon:\n";
$themRieng($sg, 'DMSG', 'Công thợ thay dầu (SG)',      'service', 150000, $lan);
$themRieng($sg, 'DMSG', 'Vệ sinh kim phun (SG)',       'service', 450000, $lan);
$themRieng($sg, 'DMSG', 'Rửa xe + hút bụi khoang máy', 'service', 120000, $lan);

echo "  Tan Phat Da Nang:\n";
$themRieng($dn, 'DMDN', 'Công thợ thay dầu (ĐN)',   'service', 130000, $lan);
$themRieng($dn, 'DMDN', 'Kiểm tra điều hoà (ĐN)',   'service', 200000, $gio);

/* ---- Tổng kết ---------------------------------------------------------
   printf('%-26s') đếm BYTE, mà tiếng Việt có dấu là 2 byte mỗi chữ — dùng
   thẳng thì cột lệch hẳn đi. Đệm theo số KÝ TỰ. */
$dem = function($s, $rong){
    $thieu = $rong - mb_strlen($s, 'UTF-8');
    return $s . str_repeat(' ', max(0, $thieu));
};

echo "\n" . str_repeat('=', 64) . "\n";
echo $dem('GARA', 24) . $dem('HANG RIENG', 12) . $dem('DA CHON', 10) . "DANH MUC GARA\n";
echo str_repeat('-', 64) . "\n";
foreach ($GA->getActive() as $g){
    $gid = (int) $g['id'];
    echo $dem(mb_substr($g['name'], 0, 22) . ((int) $g['is_master'] === 1 ? ' *' : ''), 24)
       . $dem((string) $P->demHangRieng($gid), 12)
       . $dem((string) $GP->demTheoGara($gid), 10)
       . count($P->theoNguon(PartsModel::NGUON_GARA, $gid)) . " mat hang\n";
}
echo str_repeat('=', 64) . "\n";
echo "* = gara tong\n";
printf("Danh muc tong (dung chung): %d mat hang\n\n", count($tong));

echo "Xem thu o day:\n";
echo "  - O doi gara: goc tren ben phai moi man admin (gio da co 3 gara)\n";
echo "  - Hang hoa -> Danh muc cua gara: doi gara roi xem danh muc doi theo\n";
echo "  - Ban hang -> Bao gia -> Lap bao gia: nut chon nguon [gara] / [Kho tong]\n\n";
echo "Xoa het di:  C:\\xampp\\php\\php.exe tools\\tao-du-lieu-gara.php --xoa\n";
