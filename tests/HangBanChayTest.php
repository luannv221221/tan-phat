<?php
/**
 * Test BÁO CÁO HÀNG BÁN CHẠY.
 *
 * Chạy:  C:\xampp\php\php.exe tests\HangBanChayTest.php
 *
 * Khách ghi 25/11/2026 cạnh dòng "Báo cáo hàng tồn lâu": "Báo cáo hàng bán
 * chạy". Có hàng tồn lâu (đẩy bán / giảm giá) mà không có chiều ngược lại —
 * hàng nào bán nhiều để nhập thêm.
 *
 * Đặt trong màn Báo cáo bán hàng (cùng bộ lọc từ ngày – đến ngày), màn Hàng
 * tồn lâu có lối sang. Chỉ tính hoá đơn ĐÃ GHI SỔ, chỉ của gara đang mở.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/models/SalesInvoicesModel.php';

use App\core\Model;

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

// ---------------------------------------------------------------------------
section('Noi vao man Bao cao ban hang');

$ctl = codeOnly($goc . 'app/controllers/admin/Salesreport.php');
ok(strpos($ctl, 'hangBanChay(') !== false, 'Salesreport goi hangBanChay() voi cung bo loc ngay');
$v = file_get_contents($goc . 'app/views/admin/bao-cao-ban-hang/index.php');
ok(mb_strpos($v, 'Hàng bán chạy') !== false && strpos($v, '$banChay') !== false,
   'Man bao cao co khoi "Hang ban chay"');
/* @if phai dung dau dong — viet chung dong voi chu thi bo dung view khong nhan,
   in ra nguyen chu "@if (...)" (da gap khi lam khoi nay). */
ok(preg_match('~\S[ \t]*@(if|endif|foreach)\b~', $v) === 0, 'Khong co @if / @endif viet chung dong (bo dung view khong nhan)');
$tkl = file_get_contents($goc . 'app/views/admin/ton-kho-lau/index.php');
ok(strpos($tkl, "admin/bao-cao-ban-hang") !== false && mb_strpos($tkl, 'bán chạy') !== false,
   'Man Hang ton lau co loi sang Hang ban chay');

// ---------------------------------------------------------------------------
try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

$M = new SalesInvoicesModel();
ok(method_exists($M, 'hangBanChay'), 'SalesInvoicesModel co hangBanChay()');
if (!method_exists($M, 'hangBanChay')) exit(summary());

section('So lieu khop voi cach tinh doc lap');

/* Tính lại bằng một câu SQL viết tay, so với hàm: cùng gara, chỉ hoá đơn
   đã ghi sổ, gộp theo mặt hàng. */
$tinhTay = function($gara, $tu = null, $den = null) use ($pdo){
    $sql = "SELECT i.part_id, SUM(i.quantity) sl, SUM(i.amount) dt
              FROM sales_invoice_items i JOIN sales_invoices s ON s.id = i.invoice_id
             WHERE s.status = 1 AND s.garage_id = ?";
    $bd = [$gara];
    if ($tu)  { $sql .= ' AND s.invoice_date >= ?'; $bd[] = $tu; }
    if ($den) { $sql .= ' AND s.invoice_date <= ?'; $bd[] = $den; }
    $st = $pdo->prepare($sql . ' GROUP BY i.part_id'); $st->execute($bd);
    $r = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $x) $r[(int) $x['part_id']] = [(float) $x['sl'], (float) $x['dt']];
    return $r;
};

$garas = $pdo->query("SELECT DISTINCT garage_id FROM sales_invoices WHERE status = 1")->fetchAll(PDO::FETCH_COLUMN);
ok(!empty($garas), 'Co hoa don da ghi so de kiem', 'CSDL local chua co hoa don ghi so nao');

foreach (array_slice($garas, 0, 3) as $g){
    $g = (int) $g;
    $kq = Model::trongGara($g, function() use ($M){ return $M->hangBanChay('', '', 1000); });
    $tay = $tinhTay($g);
    $lech = [];
    foreach ($kq as $r){
        $p = (int) $r['part_id'];
        if (!isset($tay[$p]) || abs($tay[$p][0] - (float) $r['so_luong']) > 0.0001
            || abs($tay[$p][1] - (float) $r['doanh_thu']) > 0.5) $lech[] = $p;
    }
    ok(count($kq) === count($tay) && empty($lech),
       "Gara $g: dung mat hang, dung so luong + doanh thu, chi hoa don ghi so cua gara do",
       'ham: ' . count($kq) . ' dong, tinh tay: ' . count($tay) . ', lech: ' . json_encode($lech));

    for ($i = 1; $i < count($kq); $i++){
        if ((float) $kq[$i]['so_luong'] > (float) $kq[$i - 1]['so_luong']){ $lech[] = 'thu-tu'; break; }
    }
    ok(!in_array('thu-tu', $lech, true), "Gara $g: xep tu ban nhieu nhat xuong");
    if (!empty($kq)){
        ok(isset($kq[0]['ten']) && $kq[0]['ten'] !== '' && array_key_exists('lai_gop', $kq[0]),
           "Gara $g: dong co ten hang + lai gop", json_encode($kq[0], JSON_UNESCAPED_UNICODE));
    }
}

/* Lọc theo ngày: kỳ không có hoá đơn nào thì rỗng. */
$g0 = (int) $garas[0];
$rong = Model::trongGara($g0, function() use ($M){ return $M->hangBanChay('1990-01-01', '1990-12-31', 20); });
ok(empty($rong), 'Ky khong co hoa don -> rong');
$gioiHan = Model::trongGara($g0, function() use ($M){ return $M->hangBanChay('', '', 2); });
ok(count($gioiHan) <= 2, 'Gioi han so dong');

exit(summary());
