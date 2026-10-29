<?php
/**
 * SỐ CHỨNG TỪ ĐÁNH THEO GARA THÌ CHỈ MỤC DUY NHẤT CŨNG PHẢI THEO GARA.
 *
 * Chạy:  C:\xampp\php\php.exe tests\SoChungTuTheoGaraTest.php
 *
 * SINH RA TỪ MỘT LỖI THẬT (khách báo 26/10/2026): khách đặt hàng trên website
 * của một gara thì đơn KHÔNG về màn Đơn hàng. Tái hiện được bằng cách đặt một
 * đơn qua tên miền của gara: HTTP 500, log ghi
 *
 *     Duplicate entry 'DH-000001' for key 'orders.uq_order_no'
 *
 * Hai vế lệch nhau:
 *   - OrdersModel::nextNo() đếm qua bangGara() -> đánh số TRONG TỪNG GARA, nên
 *     gara thứ hai bắt đầu lại từ DH-000001.
 *   - `orders` lại giữ UNIQUE (`order_no`) từ migration 000024, tức duy nhất
 *     TOÀN HỆ THỐNG.
 *
 * Nên gara nào không giữ số lớn nhất toàn hệ thống thì MỌI đơn web đều chết ở
 * câu INSERT, transaction quay lui, không còn dấu vết gì. Gara tổng vẫn đặt
 * được (số của nó đang là số lớn nhất) nên nhìn bề ngoài tưởng hệ thống chạy.
 *
 * Mọi chứng từ khác đã sửa từ migration 000078 — `orders` bị sót vì lúc đó nó
 * chưa theo gara, mãi tới 000089 mới thêm `garage_id` mà không đụng chỉ mục.
 *
 * NÊN TEST NÀY QUÉT CẢ HỌ, không chỉ `orders`: thêm một bảng chứng từ mới mà
 * quên chỉ mục là đỏ ngay, chứ không đợi một gara nữa đi vào hoạt động.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

use App\core\Model;

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

/** Các chỉ mục DUY NHẤT của một bảng: [tên => [cột, ...]] */
$chiMuc = function($bang) use ($pdo){
    $ra = [];
    try { $rows = $pdo->query("SHOW INDEX FROM `$bang` WHERE Non_unique = 0")->fetchAll(PDO::FETCH_ASSOC); }
    catch (\Throwable $e){ return $ra; }
    foreach ($rows as $r){ $ra[$r['Key_name']][(int) $r['Seq_in_index']] = $r['Column_name']; }
    foreach ($ra as $k => $v){ ksort($v); $ra[$k] = array_values($v); }
    return $ra;
};
$coCot = function($bang, $cot) use ($pdo){
    try { $rows = $pdo->query("SHOW COLUMNS FROM `$bang`")->fetchAll(PDO::FETCH_ASSOC); }
    catch (\Throwable $e){ return false; }
    foreach ($rows as $r){ if ($r['Field'] === $cot) return true; }
    return false;
};

// ---------------------------------------------------------------------------
section('Danh so theo gara thi duy nhat cung phai theo gara');

/* [bảng, cột số, model] — model để đọc xem nextNo() có đếm theo gara không. */
$chungTu = [
    ['orders',         'order_no',      'OrdersModel'],
    ['quotations',     'quote_no',      'QuotationsModel'],
    ['sales_invoices', 'invoice_no',    'SalesInvoicesModel'],
    ['goods_receipts', 'receipt_no',    'GoodsReceiptsModel'],
    ['goods_issues',   'issue_no',      'GoodsIssuesModel'],
    ['stock_takes',    'take_no',       'StockTakesModel'],
    ['receptions',     'reception_no',  'ReceptionsModel'],
    ['acc_vouchers',   'voucher_no',    'AccVouchersModel'],
    ['purchase_orders', 'po_no',        'PurchaseOrdersModel'],
];

foreach ($chungTu as $ct){
    list($bang, $cot, $model) = $ct;
    if (!$coCot($bang, $cot)) continue;              // bảng chưa có ở bản này
    if (!$coCot($bang, 'garage_id')) continue;       // không theo gara thì không xét

    /* nextNo() của model có đếm theo gara không — bangGara() là dấu hiệu. */
    $f = $goc . 'app/models/' . $model . '.php';
    if (!is_file($f)) continue;
    $src = codeOnly($f);
    if (!preg_match('~function\s+nextNo\s*\([^)]*\)\s*\{(.*?)\n    \}~s', $src, $m)) continue;
    $theoGara = strpos($m[1], 'bangGara()') !== false;
    if (!$theoGara) continue;

    /* Vậy thì chỉ mục duy nhất phủ cột số PHẢI có garage_id đi kèm. */
    $tim = null;
    foreach ($chiMuc($bang) as $ten => $cols){
        if (in_array($cot, $cols, true)){ $tim = [$ten, $cols]; break; }
    }
    ok($tim !== null && in_array('garage_id', $tim[1], true),
       "`$bang`: nextNo() dem theo gara VA chi muc duy nhat co garage_id"
       . ($tim ? ' (' . $tim[0] . ': ' . implode(', ', $tim[1]) . ')' : ' (khong co chi muc duy nhat)'),
       'Lech nhau thi gara thu hai lap phieu la dung 1062 Duplicate entry, '
       . 'transaction quay lui, chung tu bien mat khong dau vet');
}

// ---------------------------------------------------------------------------
section('Chay that: hai gara cung lap DH-000001');

const DAU = 'ZZSC';
$donDep = function() use ($pdo){
    $pdo->exec("DELETE FROM `order_items` WHERE `order_id` IN (SELECT `id` FROM `orders` WHERE `order_no` LIKE '" . DAU . "%')");
    $pdo->exec("DELETE FROM `orders`   WHERE `order_no` LIKE '" . DAU . "%'");
    $pdo->exec("DELETE FROM `garages`  WHERE `code`     LIKE '" . DAU . "%'");
};
$donDep();

$pdo->exec("INSERT INTO `garages` (`code`,`name`,`is_master`,`status`,`create_at`)
            VALUES ('" . DAU . "A','" . DAU . " Gara A',0,1,NOW()),
                   ('" . DAU . "B','" . DAU . " Gara B',0,1,NOW())");
$gA = (int) $pdo->query("SELECT `id` FROM `garages` WHERE `code` = '" . DAU . "A'")->fetchColumn();
$gB = (int) $pdo->query("SELECT `id` FROM `garages` WHERE `code` = '" . DAU . "B'")->fetchColumn();
ok($gA > 0 && $gB > 0, "Tao duoc hai gara thu (#$gA, #$gB)");

/* CÙNG MỘT SỐ, HAI GARA. Đây chính là cảnh sinh ra lỗi: mỗi gara tự đánh số
   từ đầu nên số trùng nhau là chuyện BÌNH THƯỜNG, phải chèn được cả hai. */
$them = function($gara, $so) use ($pdo){
    try {
        $pdo->prepare("INSERT INTO `orders`
            (`order_no`,`customer_name`,`phone`,`address`,`province_code`,`province_name`,
             `ward_code`,`ward_name`,`payment_method`,`subtotal`,`total_amount`,`status`,
             `garage_id`,`create_at`)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())")
            ->execute([$so, DAU . ' Khach', '0900000000', 'So 1', 1, 'Ha Noi', 1, 'Phuong 1',
                       'bank_transfer', 1000, 1000, 'new', $gara]);
        return '';
    } catch (\PDOException $e){ return $e->getMessage(); }
};

$loiA = $them($gA, DAU . '-000001');
ok($loiA === '', 'Gara A lap duoc ' . DAU . '-000001', $loiA);

$loiB = $them($gB, DAU . '-000001');
ok($loiB === '',
   'Gara B lap duoc ' . DAU . '-000001 (CUNG so, khac gara)',
   'Day la loi khach bao 26/10/2026: ' . $loiB);

/* Trong CÙNG một gara thi van phai chan trung — khong duoc noi tay qua rong. */
$loiTrung = $them($gA, DAU . '-000001');
ok($loiTrung !== '',
   'Van chan trung so TRONG CUNG mot gara',
   'Mot gara co hai don cung so la doi chieu giay to khong con tin duoc');

// ---------------------------------------------------------------------------
section('nextNo() cua hai gara doc lap nhau');

if (method_exists('\App\core\Model', 'epGara')){
    $om = \App\core\Load::model('OrdersModel');

    /* GARA C: CHƯA CÓ ĐƠN NÀO. Không hỏi lại gara A hay B — mục trên vừa chèn
       đơn cho cả hai, nên nextNo() của chúng ra DH-000002 là ĐÚNG. (Bản test
       đầu tiên hỏi gara B và chờ DH-000001; nó chỉ xanh vì gara B lúc đó đang
       trống do chính cái lỗi này làm hỏng câu chèn — một khẳng định sống nhờ
       bug, sửa bug là đỏ.) */
    $pdo->exec("INSERT INTO `garages` (`code`,`name`,`is_master`,`status`,`create_at`)
                VALUES ('" . DAU . "C','" . DAU . " Gara C',0,1,NOW())");
    $gC = (int) $pdo->query("SELECT `id` FROM `garages` WHERE `code` = '" . DAU . "C'")->fetchColumn();

    Model::epGara($gC);
    $soC = $om->nextNo();
    Model::epGara($gA);
    $soA = $om->nextNo();
    Model::epGara(null);

    ok(preg_match('~^DH-\d{6}$~', $soA) === 1, "nextNo() cua gara A dung dang: $soA");

    /* Gara mới mở phải bắt đầu từ số ĐẦU TIÊN, không nối tiếp số của gara
       khác. Đây là chỗ chứng minh việc đánh số là theo gara — và cũng là lý do
       chỉ mục duy nhất bắt buộc phải kèm garage_id. */
    ok($soC === 'DH-000001',
       'Gara moi mo bat dau tu DH-000001, khong noi tiep so cua gara khac',
       "Dang ra: $soC");
}

// ---------------------------------------------------------------------------
section('Trang "Dat hang thanh cong" phai in dung tong tien');

/* Tim thay trong lúc dò lỗi trên: `$total` được gán BÊN TRONG closure của
   transaction() mà không có trong `use`, nên ở chỗ flash nó không tồn tại —
   PHP 8 bao "Undefined variable $total" va trang bao thanh cong in "0 d".
   Khach vua dat xong nhin thay don 0 dong.

   Khong keo bien ra khoi closure ma doc `total_amount` cua CHINH DON vua ghi:
   do la con so da nam trong CSDL, cung la con so di vao hoa don sau nay. */
$cart = codeOnly($goc . 'app/controllers/Cart.php');
if (preg_match('~Session::flash\(\s*.lastOrderTotal.\s*,(.*?)\);~s', $cart, $mT)){
    ok(strpos($mT[1], '$total') === false,
       'Tong tien KHONG lay tu bien trong closure',
       'Dang lay: ' . trim($mT[1]));
    ok(strpos($mT[1], '$order') !== false,
       'Tong tien lay tu dong don vua ghi (`total_amount`)');
} else {
    ok(false, 'Doc duoc cho flash lastOrderTotal o Cart.php');
}

/* Bien gan trong closure ma dung o ngoai: quet ca ham placeOrder() de lan sau
   co them bien nao nua thi do ngay. */
if (preg_match('~public function placeOrder\(\)\{(.*?)\n    \}~s', $cart, $mP)){
    $than = $mP[1];
    $hong = [];
    if (preg_match('~transaction\(function[^)]*\)\s*use\s*\(([^)]*)\)~s', $than, $mU)){
        $trongUse = $mU[1];
        /* Bien duoc gan BEN TRONG closure (sau dau `{` cua transaction) */
        $vtMo = strpos($than, 'transaction(function');
        $sauClosure = substr($than, strpos($than, '});', $vtMo));
        preg_match_all('~\$([a-zA-Z_]\w*)\s*=~', substr($than, $vtMo, strpos($than, '});', $vtMo) - $vtMo), $mG);
        foreach (array_unique($mG[1]) as $bien){
            if (strpos($trongUse, '$' . $bien) !== false) continue;   // co trong use -> khong sao
            if (preg_match('~\$' . $bien . '\b~', $sauClosure)) $hong[] = '$' . $bien;
        }
    }
    ok(empty($hong),
       'Khong con bien nao gan trong closure roi dung o ngoai',
       'Dang co: ' . implode(', ', $hong) . ' — PHP 8 bao Undefined variable, '
       . 'ma trang van hien ra nen khong ai de y');
}

$donDep();
exit(summary());
