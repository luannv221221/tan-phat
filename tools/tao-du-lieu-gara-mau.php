<?php
/**
 * GIEO DỮ LIỆU MẪU cho hai GARA ĐỘC LẬP — để xem thử mô hình
 * "kho tổng → gara → khách hàng → xe → phiếu".
 *
 *   C:\xampp\php\php.exe tools\tao-du-lieu-gara-mau.php          -> gieo
 *   C:\xampp\php\php.exe tools\tao-du-lieu-gara-mau.php --xoa    -> xoá sạch
 *
 * Gieo cho `DMSG` (Gara mẫu Sài Gòn) và `DMDN` (Gara mẫu Đà Nẵng), mỗi gara:
 * tài khoản chủ gara + nhân viên, kho riêng và vị trí trong kho, khách hàng có
 * xe đầy đủ (biển số, VIN, số máy, hãng/model/năm), phiếu tiếp nhận, phiếu nhập
 * kho ĐÃ ghi sổ (nên có tồn kho và thẻ kho thật), báo giá, hoá đơn đã ghi sổ,
 * phiếu bảo hành và phiếu bảo trì.
 *
 * Hai gara có số phiếu TRÙNG NHAU (đều bắt đầu BG-000001, HD-000001,
 * PNK-000001, TN-000001) mà không đụng nhau — đó là điểm cần xem.
 *
 * KHÔNG đụng dữ liệu của Tân Phát (gara tổng) và kho tổng: mặt hàng kho tổng chỉ
 * được ĐỌC để làm dòng hàng, không sửa. Kho `KHO02` có sẵn cũng không đụng tới.
 *
 * Gieo qua chính model của app (có ép gara bằng Model::epGara) nên mọi ràng buộc
 * "theo gara" chạy thật: gieo được nghĩa là code chặn đúng.
 *
 * --xoa gỡ theo GARA: xoá mọi dòng thuộc hai gara mẫu ở các bảng nghiệp vụ, trừ
 * mặt hàng riêng và giá riêng có sẵn từ trước (tools/tao-du-lieu-gara.php lo
 * phần đó), và trừ kho `KHO02`.
 *
 * ⚠️ Đây là dữ liệu MẪU. Trước khi xuất CSDL đẩy lên server thật, chạy --xoa.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

use App\core\Hash;
use App\core\Model;

/** Mật khẩu chung cho các tài khoản mẫu — đổi ngay nếu dùng thật */
const MK_MAU = 'Gara@2026';

/** Dấu nhận biết: kho, vị trí kho, mặt hàng riêng do script này gieo */
const DAU = 'MAU-';

$db = new PDO(
    'mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
    _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$mot = function($sql, $bind = []) use ($db){ $st = $db->prepare($sql); $st->execute($bind); return $st->fetch(); };
$so  = function($sql, $bind = []) use ($db){ $st = $db->prepare($sql); $st->execute($bind); return (int) $st->fetchColumn(); };

/* Hai gara mẫu phải có sẵn (migration 000076 tạo/đổi tên) */
$gara = [];
foreach (['DMSG', 'DMDN'] as $code){
    $g = $mot("SELECT `id`, `code`, `name` FROM `garages` WHERE `code` = ?", [$code]);
    if (empty($g)){ echo "Chua co gara $code — chay migration truoc.\n"; exit(1); }
    $gara[$code] = $g;
}
$idSG = (int) $gara['DMSG']['id'];
$idDN = (int) $gara['DMDN']['id'];

/* ------------------------------------------------------------------ *
 * XOÁ
 * ------------------------------------------------------------------ */
if (in_array('--xoa', $argv, true)){
    $ids = [$idSG, $idDN];
    $in  = implode(',', $ids);

    /* Thẻ kho và tồn kho đi theo KHO, không có garage_id — xoá theo kho của gara,
       nhưng CHỈ những kho do script này gieo (mã có dấu). Kho KHO02 có sẵn từ
       trước, tồn của nó (nếu có) không phải của script này. */
    $khoMau = $db->query("SELECT `id` FROM `warehouses` WHERE `garage_id` IN ($in) AND `code` LIKE '" . DAU . "%'")->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($khoMau)){
        $inKho = implode(',', array_map('intval', $khoMau));
        foreach (["DELETE FROM `stock_cards` WHERE `warehouse_id` IN ($inKho)" => 'dong the kho',
                  "DELETE FROM `stocks` WHERE `warehouse_id` IN ($inKho)"      => 'dong ton kho',
                  "DELETE FROM `warehouse_locations` WHERE `warehouse_id` IN ($inKho)" => 'vi tri kho'] as $sql => $nhan){
            $n = $db->exec($sql);
            if ($n) echo "  xoa $n $nhan\n";
        }
    }

    /* Chứng từ: bảng con có khoá ngoại CASCADE nên xoá phiếu là xoá dòng hàng */
    foreach (['warranty_handovers' => 'bien ban giao nhan', 'warranty_requests' => 'phieu bao hanh / bao tri',
              'sales_invoices' => 'hoa don', 'quotations' => 'bao gia', 'goods_receipts' => 'phieu nhap',
              'goods_issues' => 'phieu xuat', 'stock_takes' => 'phieu kiem ke', 'warehouse_transfers' => 'phieu chuyen kho',
              'receptions' => 'phieu tiep nhan', 'vehicles' => 'xe', 'partners' => 'khach hang',
              'customer_groups' => 'nhom khach'] as $bang => $nhan){
        $n = $db->exec("DELETE FROM `$bang` WHERE `garage_id` IN ($in)");
        if ($n) echo "  xoa $n $nhan\n";
    }

    /* Mặt hàng riêng và kho: chỉ những dòng có dấu của script này */
    foreach (["DELETE FROM `parts` WHERE `garage_id` IN ($in) AND `code` LIKE '" . DAU . "%'" => 'mat hang rieng',
              "DELETE FROM `warehouses` WHERE `garage_id` IN ($in) AND `code` LIKE '" . DAU . "%'" => 'kho',
              "DELETE FROM `garage_settings` WHERE `garage_id` IN ($in)" => 'cau hinh rieng gara'] as $sql => $nhan){
        $n = $db->exec($sql);
        if ($n) echo "  xoa $n $nhan\n";
    }

    /* Tài khoản mẫu: nhận theo đuôi email */
    $n = $db->exec("DELETE FROM `users` WHERE `email` LIKE '%@gara-mau.test'");
    if ($n) echo "  xoa $n tai khoan mau\n";

    /* Thông tin gara trả về trắng (address / phone là dữ liệu có từ trước, giữ) */
    $db->exec("UPDATE `garages` SET `tax_code` = NULL, `email` = NULL WHERE `id` IN ($in)");

    echo "Da xoa du lieu mau cua 2 gara. Du lieu cua Tan Phat khong bi dung den.\n";
    exit(0);
}

/* ------------------------------------------------------------------ *
 * GIEO
 * ------------------------------------------------------------------ */
if ($so("SELECT COUNT(*) FROM `partners` WHERE `garage_id` IN (?, ?)", [$idSG, $idDN]) > 0){
    echo "Hai gara mau da co khach hang — chay --xoa truoc neu muon gieo lai.\n";
    exit(0);
}

$nhomId = function($ten) use ($mot){
    $g = $mot("SELECT `id` FROM `groups` WHERE `name` = ?", [$ten]);
    if (empty($g)) { echo "Khong tim thay nhom quyen $ten.\n"; exit(1); }
    return (int) $g['id'];
};
$MANAGER = $nhomId('Manager');
$STAFF   = $nhomId('Staff');

/* Mặt hàng kho tổng, tra theo mã — dòng hàng của gara lấy từ đây (tham khảo) */
$hangTong = [];
foreach ($db->query("SELECT `id`, `code`, `name`, `price` FROM `parts` WHERE `garage_id` IS NULL") as $r){
    $hangTong[$r['code']] = $r;
}
$idTong = function($ma) use ($hangTong){
    if (!isset($hangTong[$ma])) { echo "Kho tong khong co mat hang $ma.\n"; exit(1); }
    return (int) $hangTong[$ma]['id'];
};

$ngay = function($luiNgay){ return date('Y-m-d', strtotime("-$luiNgay days")); };

/* ------------------------------------------------------------------ *
 * KỊCH BẢN của từng gara
 * ------------------------------------------------------------------ */
$kichBan = [

  'DMSG' => [
    'id'       => $idSG,
    'tax_code' => '0312456789',
    'email'    => 'dichvu@garamau-sg.test',
    'cauHinh'  => ['maintenance_interval_months' => '6', 'maintenance_interval_km' => '5000', 'maintenance_window_days' => '30'],
    'nguoi'    => [
        ['name' => 'Trần Quốc Bảo', 'email' => 'chugara.sg@gara-mau.test', 'group_id' => $MANAGER],
        ['name' => 'Lê Thị Hạnh',   'email' => 'covan.sg@gara-mau.test',   'group_id' => $STAFF],
    ],
    'kho'      => ['code' => DAU . 'KHO-SG', 'name' => 'Kho gara Sài Gòn', 'address' => 'KCN Sóng Thần, Dĩ An, Bình Dương'],
    'viTri'    => [['code' => 'A1', 'name' => 'Kệ A1 — phanh, lọc'], ['code' => 'A2', 'name' => 'Kệ A2 — điện, ắc quy']],
    'hangRieng'=> [
        ['code' => DAU . 'SG-CT01', 'name' => 'Công tháo lắp hộp số (SG)', 'item_type' => 'service', 'unit_id' => 7, 'price' => 1200000],
        ['code' => DAU . 'SG-CT02', 'name' => 'Cân chỉnh thước lái (SG)',  'item_type' => 'service', 'unit_id' => 7, 'price' => 350000],
    ],
    'nhomKhach'=> ['Khách lẻ', 'Khách doanh nghiệp'],
    'khach'    => [
        ['name' => 'Nguyễn Văn Dũng', 'phone' => '0903 112 334', 'address' => '128 Nguyễn Xí, Bình Thạnh, TP.HCM', 'nhom' => 0,
         'xe' => [['bien_so' => '51F-234.56', 'so_khung' => 'RLMVA1CA2KV123456', 'so_may' => '2NR-7712345',
                   'hang_xe' => 'Toyota', 'model_xe' => 'Vios', 'nam_sx' => 2019, 'mau_xe' => 'Trắng', 'so_km' => 62400]]],
        ['name' => 'Công ty TNHH Vận tải Phương Nam', 'phone' => '0287 305 8899', 'tax_code' => '0309887766',
         'address' => '45 Quốc lộ 13, Thủ Đức, TP.HCM', 'nhom' => 1,
         'xe' => [['bien_so' => '51C-678.90', 'so_khung' => 'KMJWA37KBMU778899', 'so_may' => 'D4CB-9911223',
                   'hang_xe' => 'Hyundai', 'model_xe' => 'Solati', 'nam_sx' => 2021, 'mau_xe' => 'Bạc', 'so_km' => 118500],
                  ['bien_so' => '51D-445.67', 'so_khung' => 'MNBLSFE80HW445566', 'so_may' => 'P4AT-5566778',
                   'hang_xe' => 'Ford', 'model_xe' => 'Transit', 'nam_sx' => 2018, 'mau_xe' => 'Trắng', 'so_km' => 203100]]],
        ['name' => 'Trần Thị Mai', 'phone' => '0938 776 221', 'address' => '12 Lê Văn Việt, Thủ Đức, TP.HCM', 'nhom' => 0,
         'xe' => [['bien_so' => '59A-112.33', 'so_khung' => 'MRHGM6640LP112233', 'so_may' => 'L15Z-3344556',
                   'hang_xe' => 'Honda', 'model_xe' => 'City', 'nam_sx' => 2020, 'mau_xe' => 'Đỏ', 'so_km' => 41200]]],
    ],
    /* Nhập kho từ Tân Phát — mã kho tổng => [số lượng, giá nhập] */
    'nhapKho'  => ['PT-0001' => [12, 520000], 'PT-0004' => [30, 96000], 'PT-0005' => [24, 144000],
                   'PT-0008' => [6, 1160000], 'PT-0011' => [4, 1560000]],
    'baoGia'   => [
        ['khach' => 0, 'xe' => 0, 'ngayLui' => 6, 'status' => 'sent', 'vat' => 8,
         'note'  => 'Khách hẹn làm cuối tuần',
         'dong'  => [['PT-0001', 1, 650000], ['PT-0004', 1, 120000], [DAU . 'SG-CT01', 1, 1200000]]],
        ['khach' => 2, 'xe' => 0, 'ngayLui' => 2, 'status' => 'draft', 'vat' => 8,
         'dong'  => [['PT-0005', 1, 180000], [DAU . 'SG-CT02', 1, 350000]]],
    ],
    'hoaDon'   => [
        ['khach' => 0, 'xe' => 0, 'ngayLui' => 5, 'ghiSo' => true, 'vat' => 8,
         'dong'  => [['PT-0001', 1, 650000], ['PT-0004', 1, 120000], [DAU . 'SG-CT01', 1, 1200000]]],
        ['khach' => 1, 'xe' => 0, 'ngayLui' => 3, 'ghiSo' => true, 'vat' => 8,
         'dong'  => [['PT-0008', 1, 1450000], ['PT-0005', 2, 180000]]],
        ['khach' => 2, 'xe' => 0, 'ngayLui' => 1, 'ghiSo' => false, 'vat' => 8,
         'dong'  => [[DAU . 'SG-CT02', 1, 350000]]],
    ],
    'tiepNhan' => [
        ['khach' => 0, 'xe' => 0, 'ngayLui' => 6, 'km' => 62400, 'status' => 'hoan_tat',
         'tinh_trang_xe' => 'Phanh trước kêu, đạp sâu', 'yeu_cau_khach' => 'Kiểm tra phanh, thay dầu', 'co_van' => 'Lê Thị Hạnh'],
        ['khach' => 1, 'xe' => 1, 'ngayLui' => 1, 'km' => 203100, 'status' => 'dang_sua',
         'tinh_trang_xe' => 'Máy phát điện yếu, đèn báo bình', 'yeu_cau_khach' => 'Kiểm tra hệ thống điện', 'co_van' => 'Lê Thị Hạnh'],
    ],
    'baoHanh'  => [
        ['loai' => 'bao_hanh', 'khach' => 0, 'xe' => 0, 'ngayLui' => 20, 'status' => 'done', 'part' => 'PT-0008',
         'issue' => 'Ắc quy sụt điện sau 3 tuần', 'diagnosis' => 'Đổi ắc quy mới theo bảo hành hãng', 'fee' => 0],
        ['loai' => 'bao_tri', 'khach' => 2, 'xe' => 0, 'ngayLui' => 165, 'status' => 'done', 'part' => 'PT-0004',
         'issue' => 'Bảo dưỡng định kỳ 40.000 km', 'diagnosis' => 'Thay dầu, lọc dầu, lọc gió', 'fee' => 850000, 'km' => 40100],
    ],
  ],

  'DMDN' => [
    'id'       => $idDN,
    'tax_code' => '0401778899',
    'email'    => 'xuong@garamau-dn.test',
    'cauHinh'  => ['maintenance_interval_months' => '4', 'maintenance_interval_km' => '4000', 'maintenance_window_days' => '20'],
    'nguoi'    => [
        ['name' => 'Phạm Hữu Thịnh', 'email' => 'chugara.dn@gara-mau.test', 'group_id' => $MANAGER],
        ['name' => 'Võ Minh Tuấn',   'email' => 'covan.dn@gara-mau.test',   'group_id' => $STAFF],
    ],
    'kho'      => ['code' => DAU . 'KHO-DN', 'name' => 'Kho gara Đà Nẵng', 'address' => '215 Nguyễn Hữu Thọ, Hải Châu, Đà Nẵng'],
    'viTri'    => [['code' => 'B1', 'name' => 'Kệ B1 — vật tư nhanh']],
    'hangRieng'=> [
        ['code' => DAU . 'DN-CT01', 'name' => 'Công sơn dặm cản (ĐN)', 'item_type' => 'service', 'unit_id' => 7, 'price' => 650000],
    ],
    'nhomKhach'=> ['Khách quen', 'Xe dịch vụ'],
    'khach'    => [
        ['name' => 'Huỳnh Thị Lan', 'phone' => '0905 447 118', 'address' => '88 Lê Duẩn, Thanh Khê, Đà Nẵng', 'nhom' => 0,
         'xe' => [['bien_so' => '43A-556.78', 'so_khung' => 'MALA751CLLM556677', 'so_may' => 'G4LC-2233445',
                   'hang_xe' => 'Kia', 'model_xe' => 'Morning', 'nam_sx' => 2020, 'mau_xe' => 'Xanh', 'so_km' => 53800]]],
        ['name' => 'Ngô Thanh Phong', 'phone' => '0913 220 776', 'address' => '31 Ngô Quyền, Sơn Trà, Đà Nẵng', 'nhom' => 1,
         'xe' => [['bien_so' => '92A-334.21', 'so_khung' => 'MMBJNKL10LD334455', 'so_may' => '4N15-6677889',
                   'hang_xe' => 'Mitsubishi', 'model_xe' => 'Xpander', 'nam_sx' => 2022, 'mau_xe' => 'Xám', 'so_km' => 28900]]],
    ],
    'nhapKho'  => ['PT-0002' => [8, 575000], 'PT-0013' => [20, 108000], 'PT-0015' => [16, 184000],
                   'PT-0016' => [5, 712000]],
    'baoGia'   => [
        ['khach' => 1, 'xe' => 0, 'ngayLui' => 4, 'status' => 'accepted', 'vat' => 10,
         'dong'  => [['PT-0002', 1, 720000], ['PT-0015', 4, 230000], [DAU . 'DN-CT01', 1, 650000]]],
    ],
    'hoaDon'   => [
        ['khach' => 1, 'xe' => 0, 'ngayLui' => 3, 'ghiSo' => true, 'vat' => 10,
         'dong'  => [['PT-0002', 1, 720000], ['PT-0015', 4, 230000], [DAU . 'DN-CT01', 1, 650000]]],
        ['khach' => 0, 'xe' => 0, 'ngayLui' => 2, 'ghiSo' => false, 'vat' => 10,
         'dong'  => [['PT-0013', 1, 135000]]],
    ],
    'tiepNhan' => [
        ['khach' => 1, 'xe' => 0, 'ngayLui' => 4, 'km' => 28900, 'status' => 'hoan_tat',
         'tinh_trang_xe' => 'Xe rung khi phanh gấp', 'yeu_cau_khach' => 'Thay má phanh, kiểm tra bugi', 'co_van' => 'Võ Minh Tuấn'],
    ],
    'baoHanh'  => [
        ['loai' => 'bao_tri', 'khach' => 0, 'xe' => 0, 'ngayLui' => 130, 'status' => 'done', 'part' => 'PT-0013',
         'issue' => 'Bảo dưỡng 50.000 km', 'diagnosis' => 'Thay dầu + lọc dầu', 'fee' => 620000, 'km' => 50000],
    ],
  ],
];

/* ------------------------------------------------------------------ *
 * Gieo từng gara
 * ------------------------------------------------------------------ */
require_once __DIR__ . '/../app/models/UsersModel.php';
require_once __DIR__ . '/../app/models/GaragesModel.php';
require_once __DIR__ . '/../app/models/GarageSettingsModel.php';
require_once __DIR__ . '/../app/models/WarehousesModel.php';
require_once __DIR__ . '/../app/models/WarehouseLocationsModel.php';
require_once __DIR__ . '/../app/models/PartsModel.php';
require_once __DIR__ . '/../app/models/PartnersModel.php';
require_once __DIR__ . '/../app/models/CustomerGroupsModel.php';
require_once __DIR__ . '/../app/models/VehiclesModel.php';
require_once __DIR__ . '/../app/models/ReceptionsModel.php';
require_once __DIR__ . '/../app/models/QuotationsModel.php';
require_once __DIR__ . '/../app/models/QuotationItemsModel.php';
require_once __DIR__ . '/../app/models/SalesInvoicesModel.php';
require_once __DIR__ . '/../app/models/SalesInvoiceItemsModel.php';
require_once __DIR__ . '/../app/models/GoodsReceiptsModel.php';
require_once __DIR__ . '/../app/models/GoodsReceiptItemsModel.php';
require_once __DIR__ . '/../app/models/StocksModel.php';
require_once __DIR__ . '/../app/models/WarrantyRequestsModel.php';

$U   = new UsersModel();
$GAR = new GaragesModel();
$GS  = new GarageSettingsModel();
$KHO = new WarehousesModel();
$VT  = new WarehouseLocationsModel();
$PT  = new PartsModel();
$KH  = new PartnersModel();
$NK  = new CustomerGroupsModel();
$XE  = new VehiclesModel();
$TN  = new ReceptionsModel();
$BG  = new QuotationsModel();
$BGD = new QuotationItemsModel();
$HD  = new SalesInvoicesModel();
$HDD = new SalesInvoiceItemsModel();
$PN  = new GoodsReceiptsModel();
$PND = new GoodsReceiptItemsModel();
$TK  = new StocksModel();
$BH  = new WarrantyRequestsModel();

foreach ($kichBan as $code => $k){
    $gid = (int) $k['id'];
    echo "\n=== {$gara[$code]['name']} ($code) ===\n";

    /* Ép gara làm việc: dòng lệnh không có tài khoản đăng nhập nên lớp Model
       gốc không tự biết gara. Từ đây mọi model ghi vào ĐÚNG gara này. */
    Model::epGara($gid);

    // 1. Thông tin gara — ĐẦU PHIẾU IN lấy từ đây (cau_hinh_in_an đọc bảng garages)
    $GAR->edit(['tax_code' => $k['tax_code'], 'email' => $k['email']], $gid);

    /* Cấu hình bảo trì riêng gara: KHÔNG dùng GarageSettingsModel::saveMany() —
       hàm đó lấy gara theo gara_hien_tai_id(), mà ở dòng lệnh hàm này luôn trả
       gara TỔNG (không theo epGara). Ghi thẳng cho đúng gara. */
    foreach ($k['cauHinh'] as $skey => $svalue){
        $st = $db->prepare("INSERT INTO `garage_settings` (`garage_id`, `skey`, `svalue`, `update_at`)
                            VALUES (?, ?, ?, NOW())
                            ON DUPLICATE KEY UPDATE `svalue` = VALUES(`svalue`), `update_at` = NOW()");
        $st->execute([$gid, $skey, $svalue]);
    }
    echo "  thong tin gara + cau hinh bao tri rieng\n";

    // 2. Tài khoản — UsersModel không tự ghi gara, phải truyền tay
    $nguoiLapId = null;
    foreach ($k['nguoi'] as $ng){
        $U->add(['name' => $ng['name'], 'email' => $ng['email'], 'password' => Hash::make(MK_MAU),
                 'group_id' => $ng['group_id'], 'garage_id' => $gid, 'status' => 1,
                 'create_at' => date('Y-m-d H:i:s')]);
        if ($nguoiLapId === null) $nguoiLapId = (int) $U->lastId();   // chủ gara: người lập chứng từ
        echo "  tai khoan {$ng['email']}\n";
    }

    // 3. Kho + vị trí trong kho
    $khoId = (int) $KHO->add(['code' => $k['kho']['code'], 'name' => $k['kho']['name'], 'address' => $k['kho']['address'],
                              'is_default' => 1, 'sort_order' => 0, 'status' => 1]);
    $KHO->clearDefaultExcept($khoId);   // mỗi gara chỉ một kho mặc định
    foreach ($k['viTri'] as $vt){
        $duong = $VT->resolvePath($vt['name'], null);
        $VT->add(array_merge($duong, ['warehouse_id' => $khoId, 'code' => $vt['code'], 'name' => $vt['name'],
                                      'sort_order' => 0, 'status' => 1]));
    }
    echo "  kho {$k['kho']['code']} + " . count($k['viTri']) . " vi tri\n";

    /* 4. Mặt hàng riêng của gara (công thợ). PartsModel KHÔNG bật $_theoGara
       (kho tổng là garage_id NULL) nên phải tự ghi garage_id — thiếu là mặt hàng
       rơi vào kho tổng của Tân Phát. */
    $hangRieng = [];
    foreach ($k['hangRieng'] as $h){
        $hangRieng[$h['code']] = (int) $PT->add([
            'code' => $h['code'], 'name' => $h['name'], 'slug' => $PT->slugTrong($h['name']),
            'item_type' => $h['item_type'], 'unit_id' => $h['unit_id'], 'price' => $h['price'],
            'garage_id' => $gid, 'status' => 1, 'show_on_web' => 0,
        ]);
    }
    echo "  " . count($hangRieng) . " mat hang rieng\n";

    /* Tra id mặt hàng theo mã: kho tổng hoặc hàng riêng của gara này */
    $hang = function($ma) use ($hangRieng, $idTong){
        return isset($hangRieng[$ma]) ? $hangRieng[$ma] : $idTong($ma);
    };

    // 5. Nhóm khách + khách hàng + xe
    $nhom = [];
    foreach ($k['nhomKhach'] as $ten){
        $nhom[] = (int) $NK->add(['name' => $ten, 'status' => 1, 'sort_order' => 0]);
    }
    $khachIds = []; $xeIds = [];
    foreach ($k['khach'] as $i => $kh){
        $kid = (int) $KH->add([
            'code' => $KH->nextCode('KH-'), 'name' => $kh['name'], 'type' => 'customer',
            'group_id' => isset($nhom[$kh['nhom']]) ? $nhom[$kh['nhom']] : null,
            'tax_code' => isset($kh['tax_code']) ? $kh['tax_code'] : null,
            'phone' => $kh['phone'], 'address' => $kh['address'], 'status' => 1, 'sort_order' => 0,
        ]);
        $khachIds[$i] = $kid;
        $xeIds[$i] = [];
        foreach ($kh['xe'] as $xe){
            $xeIds[$i][] = (int) $XE->add(array_merge($xe, [
                'partner_id'    => $kid,
                'bien_so_chuan' => chuan_hoa_bien_so($xe['bien_so']),
                'status'        => 1,
            ]));
        }
    }
    echo "  " . count($khachIds) . " khach hang, " . array_sum(array_map('count', $xeIds)) . " xe\n";

    // 6. Phiếu nhập kho từ Tân Phát, ĐÃ ghi sổ -> có tồn kho và thẻ kho thật
    $dongNhap = [];
    foreach ($k['nhapKho'] as $ma => $sl){
        $dongNhap[] = ['part_id' => $idTong($ma), 'quantity' => $sl[0], 'unit_cost' => $sl[1]];
    }
    $ngayNhap = $ngay(30);
    $pnId = (int) $PN->add(['receipt_no' => $PN->nextNo(), 'receipt_type' => 'nhap_mua', 'warehouse_id' => $khoId,
                            'partner_name' => 'Công ty Tân Phát', 'receipt_date' => $ngayNhap,
                            'reason' => 'Nhập hàng phụ tùng từ Tân Phát', 'total_amount' => 0, 'status' => 0,
                            'created_by' => $nguoiLapId]);
    $tongNhap = $PND->syncForReceipt($pnId, $dongNhap);
    /* Ghi sổ như màn Phiếu nhập: applyIn từng dòng TRONG transaction (động cơ
       tồn kho khoá dòng bằng SELECT ... FOR UPDATE, ngoài transaction là vô hiệu). */
    $soPn = $PN->getDetail($pnId)['receipt_no'];
    $PN->transaction(function() use ($PND, $TK, $pnId, $khoId, $soPn, $ngayNhap, $PN, $tongNhap){
        foreach ($PND->getByReceipt($pnId) as $d){
            $TK->applyIn($khoId, (int) $d['part_id'], (float) $d['quantity'], (float) $d['unit_cost'],
                         'receipt', $pnId, $soPn, $ngayNhap, null);
        }
        $PN->edit(['status' => 1, 'total_amount' => $tongNhap], $pnId);
    });
    echo "  phieu nhap " . $PN->getDetail($pnId)['receipt_no'] . " da ghi so (" . count($dongNhap) . " dong, "
       . number_format($tongNhap, 0, ',', '.') . " d)\n";

    // 7. Phiếu tiếp nhận
    $tnIds = [];
    foreach ($k['tiepNhan'] as $tn){
        $xeId = $xeIds[$tn['khach']][$tn['xe']];
        $tnIds[] = (int) $TN->add([
            'reception_no' => $TN->nextNo(), 'vehicle_id' => $xeId, 'partner_id' => $khachIds[$tn['khach']],
            'ngay_vao' => $ngay($tn['ngayLui']), 'km_vao' => $tn['km'], 'tinh_trang_xe' => $tn['tinh_trang_xe'],
            'yeu_cau_khach' => $tn['yeu_cau_khach'], 'co_van' => $tn['co_van'], 'status' => $tn['status'],
            'created_by' => $nguoiLapId,
        ]);
    }
    echo "  " . count($tnIds) . " phieu tiep nhan\n";

    /* Dựng dòng hàng cho báo giá / hoá đơn từ mã hàng */
    $dungDong = function($ds) use ($hang){
        $ra = [];
        foreach ($ds as $d){
            $ra[] = ['part_id' => $hang($d[0]), 'quantity' => $d[1], 'unit_price' => $d[2],
                     'discount_percent' => 0, 'note' => null];
        }
        return $ra;
    };
    /* Thông tin xe gắn lên chứng từ (biển số, số km, xe, phiếu tiếp nhận) */
    $thongTinXe = function($iKhach, $iXe) use ($k, $xeIds, $tnIds){
        $xe = $k['khach'][$iKhach]['xe'][$iXe];
        return ['vehicle_id' => $xeIds[$iKhach][$iXe], 'bien_so' => $xe['bien_so'],
                'bien_so_chuan' => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $xe['bien_so'])),
                'so_km' => $xe['so_km'], 'reception_id' => !empty($tnIds) ? $tnIds[0] : null];
    };

    // 8. Báo giá
    foreach ($k['baoGia'] as $bg){
        $dong = $dungDong($bg['dong']);
        $id = (int) $BG->add(array_merge($thongTinXe($bg['khach'], $bg['xe']), [
            'quote_no' => $BG->nextNo(), 'customer_id' => $khachIds[$bg['khach']],
            'quote_date' => $ngay($bg['ngayLui']), 'valid_until' => $ngay($bg['ngayLui'] - 15),
            'vat_rate' => $bg['vat'], 'status' => $bg['status'],
            'note' => isset($bg['note']) ? $bg['note'] : null, 'created_by' => $nguoiLapId,
        ]));
        $tong = $BGD->syncForQuotation($id, $dong);
        $thue = round($tong * $bg['vat'] / 100, 2);
        $BG->edit(['subtotal' => $tong, 'tax_amount' => $thue, 'total_amount' => $tong + $thue], $id);
        echo "  bao gia " . $BG->getDetail($id)['quote_no'] . " (" . $bg['status'] . ", "
           . number_format($tong + $thue, 0, ',', '.') . " d)\n";
    }

    // 9. Hoá đơn — cái nào ghiSo thì trừ kho và ghi giá vốn như màn Ghi sổ
    foreach ($k['hoaDon'] as $hd){
        $dong = $dungDong($hd['dong']);
        $id = (int) $HD->add(array_merge($thongTinXe($hd['khach'], $hd['xe']), [
            'invoice_no' => $HD->nextNo(), 'customer_id' => $khachIds[$hd['khach']], 'warehouse_id' => $khoId,
            'invoice_date' => $ngay($hd['ngayLui']), 'vat_rate' => $hd['vat'], 'status' => 0,
            'created_by' => $nguoiLapId,
        ]));
        $tong = $HDD->syncForInvoice($id, $dong);
        $thue = round($tong * $hd['vat'] / 100, 2);
        $HD->edit(['subtotal' => $tong, 'tax_amount' => $thue, 'total_amount' => $tong + $thue], $id);

        if (!empty($hd['ghiSo'])){
            $no    = $HD->getDetail($id)['invoice_no'];
            $ngayH = $ngay($hd['ngayLui']);
            $HD->transaction(function() use ($HDD, $PT, $TK, $HD, $id, $khoId, $no, $ngayH){
                $giaVon = 0.0;
                foreach ($HDD->getByInvoice($id) as $d){
                    $mh = $PT->getDetail((int) $d['part_id']);
                    // Dịch vụ không trừ kho, không có giá vốn — đúng như màn Ghi sổ
                    if (empty($mh) || !PartsModel::coKho($mh['item_type'])){
                        $HDD->setCost((int) $d['id'], 0, 0);
                        continue;
                    }
                    $bq = $TK->applyOut($khoId, (int) $d['part_id'], (float) $d['quantity'],
                                        'sale_invoice', $id, $no, $ngayH, null);
                    $tien = round((float) $d['quantity'] * $bq, 2);
                    $HDD->setCost((int) $d['id'], $bq, $tien);
                    $giaVon += $tien;
                }
                $HD->edit(['status' => 1, 'cost_amount' => $giaVon], $id);
            });
        }
        echo "  hoa don " . $HD->getDetail($id)['invoice_no'] . " ("
           . (!empty($hd['ghiSo']) ? 'da ghi so' : 'nhap') . ", " . number_format($tong + $thue, 0, ',', '.') . " d)\n";
    }

    // 10. Phiếu bảo hành / bảo trì
    foreach ($k['baoHanh'] as $bh){
        $xe = $k['khach'][$bh['khach']]['xe'][$bh['xe']];
        $id = (int) $BH->add([
            'request_no' => $BH->nextNo($bh['loai']), 'loai' => $bh['loai'],
            'partner_id' => $khachIds[$bh['khach']], 'customer_name' => $k['khach'][$bh['khach']]['name'],
            'phone' => $k['khach'][$bh['khach']]['phone'], 'part_id' => $idTong($bh['part']),
            'received_date' => $ngay($bh['ngayLui']), 'completed_date' => $ngay($bh['ngayLui'] - 2),
            'status' => $bh['status'], 'issue' => $bh['issue'], 'diagnosis' => $bh['diagnosis'],
            'fee' => $bh['fee'], 'created_by' => $nguoiLapId, 'vehicle_id' => $xeIds[$bh['khach']][$bh['xe']],
            'bien_so' => $xe['bien_so'], 'bien_so_chuan' => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $xe['bien_so'])),
            'so_km' => isset($bh['km']) ? $bh['km'] : $xe['so_km'],
        ]);
        echo "  phieu " . $bh['loai'] . " " . $BH->getDetail($id)['request_no'] . "\n";
    }

    Model::epGara(null);
}

echo "\n================ XONG ================\n";
/* Ở dòng lệnh không có HTTP_HOST nên _WEB_URL thiếu cổng — nhắc luôn cổng thật */
echo "Dang nhap: " . _WEB_URL . "/admin   (may local Apache cong 88: http://localhost:88/tan-phat/admin)\n";
echo "Mat khau chung cho moi tai khoan mau: " . MK_MAU . "\n";
foreach ($kichBan as $code => $k){
    echo "  {$gara[$code]['name']}:\n";
    foreach ($k['nguoi'] as $ng) echo "    {$ng['email']}  ({$ng['name']})\n";
}
echo "\nDoi tai khoan de thay: hai gara KHONG thay du lieu cua nhau, va so phieu\n";
echo "trung nhau ma khong dung nhau. Xoa het: tools\\tao-du-lieu-gara-mau.php --xoa\n";
