<?php
/**
 * GIEO DỮ LIỆU MẪU cho phiếu bảo trì / lịch / nhắc bảo trì — để xem thử.
 *
 *   C:\xampp\php\php.exe tools\tao-du-lieu-bao-tri.php          -> gieo
 *   C:\xampp\php\php.exe tools\tao-du-lieu-bao-tri.php --xoa    -> xoá sạch
 *
 * Ngày tính LÙI TỪ HÔM NAY, nên chạy lúc nào cũng ra đúng các tình huống:
 *   30A-567.89  bảo trì xong 7 tháng trước                -> QUÁ HẠN theo tháng
 *   51F-246.80  bảo trì xong 50 ngày trước ở 62.000 km,
 *               10 ngày trước vào bảo hành ở 65.000 km    -> SẮP TỚI HẠN theo KM
 *               (75 km/ngày, mốc 67.000 -> còn ~17 ngày)
 *               (tháng thì còn xa — cho thấy "cái nào tới trước")
 *   29A-111.22  bảo trì xong 5 tháng rưỡi trước, đã lập
 *               phiếu bảo trì mới hẹn ngày mai            -> "Đã hẹn", không nhắc
 *   43A-135.79  bảo dưỡng lần đầu, hẹn HÔM NAY            -> hiện ở Lịch
 *
 * Mọi phiếu gieo ra có ghi chú đúng bằng DAU_MAU — --xoa gỡ theo dấu đó,
 * không đụng phiếu thật.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/models/WarrantyRequestsModel.php';

const DAU_MAU = 'DU LIEU MAU BAO TRI';

$db = new PDO(
    'mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
    _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$W = new WarrantyRequestsModel();

$dem = function() use ($db){
    $st = $db->prepare("SELECT COUNT(*) FROM `warranty_requests` WHERE `note` = ?");
    $st->execute([DAU_MAU]);
    return (int) $st->fetchColumn();
};

if (in_array('--xoa', $argv, true)){
    $st = $db->prepare("DELETE h FROM `warranty_handovers` h JOIN `warranty_requests` w ON w.id = h.warranty_id WHERE w.`note` = ?");
    $st->execute([DAU_MAU]);
    $st = $db->prepare("DELETE FROM `warranty_requests` WHERE `note` = ?");
    $st->execute([DAU_MAU]);
    echo "Da xoa " . $st->rowCount() . " phieu mau.\n";
    exit(0);
}

if ($dem() > 0){
    echo "Da co du lieu mau (" . $dem() . " phieu). Chay --xoa truoc neu muon gieo lai.\n";
    exit(0);
}

$ngay = function($soNgay){ return date('Y-m-d', strtotime(($soNgay >= 0 ? '+' : '') . $soNgay . ' days')); };
$ngayThang = function($soThang, $themNgay = 0){ return date('Y-m-d', strtotime("-$soThang months $themNgay days")); };

$phieu = function($loai, array $d) use ($W){
    $no = $W->nextNo($loai);
    $bs = $d['bien_so'] ?? null;
    $id = $W->add(array_merge([
        'request_no'    => $no,
        'loai'          => $loai,
        'status'        => 'received',
        'fee'           => 0,
        'bien_so'       => $bs,
        'bien_so_chuan' => $bs !== null ? chuan_hoa_bien_so($bs) : null,
        'note'          => DAU_MAU,
        'created_by'    => null,
    ], $d));
    echo "  $no  " . ($bs ?: '-') . "  " . ($d['status'] ?? 'received') . "\n";
    return $id;
};

echo "Gieo phieu bao tri mau:\n";

// 1. Quá hạn theo tháng
$phieu('bao_tri', [
    'customer_name' => 'Anh Trần Văn Nam', 'phone' => '0903111222',
    'product_name'  => 'Toyota Vios 2019', 'bien_so' => '30A-567.89', 'so_km' => 45000,
    'received_date' => $ngayThang(7, -1), 'completed_date' => $ngayThang(7),
    'status' => 'done', 'technician' => 'KTV Sơn', 'fee' => 850000,
    'issue' => 'Bảo dưỡng định kỳ', 'diagnosis' => 'Thay dầu máy, lọc dầu, kiểm tra phanh',
]);

// 2. Sắp tới hạn theo km: 62.000 -> 65.000 trong 40 ngày (75 km/ngày), mốc 67.000
//    -> chạm mốc ~27 ngày sau lần ghi, tức còn ~17 ngày; theo tháng thì còn 4 tháng
$phieu('bao_tri', [
    'customer_name' => 'Chị Lê Thu Hà', 'phone' => '0918222333',
    'product_name'  => 'Mazda CX-5 2021', 'bien_so' => '51F-246.80', 'so_km' => 62000,
    'received_date' => $ngay(-50), 'completed_date' => $ngay(-50),
    'status' => 'done', 'technician' => 'KTV Hoàng', 'fee' => 1200000,
    'issue' => 'Bảo dưỡng 60.000 km', 'diagnosis' => 'Thay dầu, lọc gió, nước làm mát',
]);
$phieu('bao_hanh', [
    'customer_name' => 'Chị Lê Thu Hà', 'phone' => '0918222333',
    'product_name'  => 'Ắc quy GS 60Ah', 'bien_so' => '51F-246.80', 'so_km' => 65000,
    'received_date' => $ngay(-10), 'appointment_date' => $ngay(2),
    'status' => 'processing', 'technician' => 'KTV Hoàng',
    'issue' => 'Ắc quy yếu, khó đề buổi sáng',
]);

// 3. Đã hẹn lần mới -> không nhắc
$phieu('bao_tri', [
    'customer_name' => 'Anh Phạm Minh Đức', 'phone' => '0987444555',
    'product_name'  => 'Honda City 2020', 'bien_so' => '29A-111.22', 'so_km' => 30000,
    'received_date' => $ngayThang(5, -15), 'completed_date' => $ngayThang(5, -15),
    'status' => 'done', 'technician' => 'KTV Sơn', 'fee' => 700000,
    'issue' => 'Bảo dưỡng định kỳ', 'diagnosis' => 'Thay dầu máy',
]);
$phieu('bao_tri', [
    'customer_name' => 'Anh Phạm Minh Đức', 'phone' => '0987444555',
    'product_name'  => 'Honda City 2020', 'bien_so' => '29A-111.22',
    'received_date' => $ngay(0), 'appointment_date' => $ngay(1),
    'issue' => 'Bảo dưỡng định kỳ — khách gọi đặt lịch',
]);

// 4. Bảo dưỡng lần đầu, hẹn hôm nay
$phieu('bao_tri', [
    'customer_name' => 'Công ty Vận tải Minh Long', 'phone' => '0236 3888 999',
    'product_name'  => 'Ford Transit 2022', 'bien_so' => '43A-135.79', 'so_km' => 20000,
    'received_date' => $ngay(0), 'appointment_date' => $ngay(0),
    'issue' => 'Bảo dưỡng lần đầu 20.000 km',
]);

echo "Xong: " . $dem() . " phieu mau. Xoa: C:\\xampp\\php\\php.exe tools\\tao-du-lieu-bao-tri.php --xoa\n";
