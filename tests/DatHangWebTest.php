<?php
/**
 * ĐẶT HÀNG TỪ GIAN HÀNG — HỎNG THÌ PHẢI NÓI RA.
 *
 * Anh báo 12/11/2026: "phần đặt hàng từ website, khách test đặt hàng chưa
 * thấy nó lưu vào đâu" và "anh test rồi có đặt hàng được đâu", kèm ảnh màn
 * dmsg.etek.rikkeiedu.org/dat-hang đã điền đủ họ tên, SĐT, tỉnh/phường, địa
 * chỉ, giỏ có "Đèn pha Toyota Vios LED" 2.500.000đ.
 *
 * Dựng lại được y như vậy trên máy (gian hàng gara 2 qua Host: dmsg.localhost):
 * bấm Xác nhận đặt hàng -> 302 quay về /dat-hang, bảng `orders` không thêm
 * dòng nào, VÀ TRANG KHÔNG IN MỘT CHỮ NÀO về lý do.
 *
 * HAI LỖI RIÊNG BIỆT, test này giữ cả hai:
 *
 *   1. placeOrder() đặt bảy khoá lỗi, checkout.php chỉ in năm. Hai khoá vắng
 *      mặt (`stock`, `cart`) lại đúng là hai khoá khách KHÔNG sửa được bằng
 *      cách gõ lại form — nên đúng lúc cần giải thích nhất thì trang im lặng.
 *
 *   2. Cart::add() nhận hàng bằng getDetail() ("có trong bảng `parts` không")
 *      trong khi giỏ hàng dựng dòng bằng chiTietWeb() ("có bán trên gian hàng
 *      này không"). Hai câu hỏi khác nhau -> có mặt hàng thêm vào giỏ thì báo
 *      thành công, mở giỏ ra lại trống.
 *
 * KHÔNG khẳng định "gara phải bán được hàng kho tổng". Chuyện gara chỉ bán
 * trong phạm vi tồn CỦA MÌNH là chốt 04/08/2026, còn nguyên. Test này chỉ đòi
 * một điều: từ chối thì phải nói vì sao.
 */

require_once __DIR__ . '/_helpers.php';

$goc = __DIR__ . '/../';

// ===========================================================================
section('Nguon: moi khoa loi placeOrder() dat deu phai co cho in');

$cart  = codeOnly($goc . 'app/controllers/Cart.php');
$view  = file_get_contents($goc . 'app/views/storefront/checkout.php');

/* CHI XET THAN HAM placeOrder() — day la ham dung sau nut "Xac nhan dat hang"
   va la ham duy nhat do ve man checkout. Quet ca file thi dinh ca `add` (loi
   cua nut Them vao gio, in o man GIO HANG) va bat man dat hang in mot cau no
   khong bao gio nhan duoc. */
$than = function($tu, $den) use ($cart){
    $a = strpos($cart, $tu);
    if ($a === false) return '';
    $b = strpos($cart, $den, $a);
    return substr($cart, $a, ($b === false ? strlen($cart) : $b) - $a);
};

$thanDat = $than('function placeOrder()', 'function orderDone()');
ok($thanDat !== '', 'Tach duoc than ham placeOrder()');

preg_match_all("~\\\$errors\['([a-z_]+)'\]~", $thanDat, $m1);
preg_match_all("~Session::flash\('errors',\s*\['([a-z_]+)'~", $thanDat, $m2);
$khoaDat = array_values(array_unique(array_merge($m1[1], $m2[1])));
sort($khoaDat);

ok(count($khoaDat) >= 6, 'Doc duoc danh sach khoa loi tu Cart.php', implode(', ', $khoaDat));

$thieu = [];
foreach ($khoaDat as $k){
    if (strpos($view, "\$errors['$k']") === false) $thieu[] = $k;
}
ok(empty($thieu),
   'Man dat hang in DU moi khoa loi ma controller co the dat',
   empty($thieu) ? implode(', ', $khoaDat) : 'THIEU cho in: ' . implode(', ', $thieu));

/* Hai khoa nay la hai khoa tung bi bo quen — goi ten rieng de sau nay ai do
   xoa dong in di thi test chi thang vao cho hong, khong chi noi "thieu mot
   khoa nao do". */
ok(strpos($view, "\$errors['stock']") !== false,
   "Man dat hang in loi 'stock' (khong du hang)");
ok(strpos($view, "\$errors['cart']") !== false,
   "Man dat hang in loi 'cart' (gio hang trong)");

// ===========================================================================
section('Nguon: them vao gio hoi dung cau hoi ma gio hang se hoi lai');

$thanThem = $than('function add()', 'function update()');
ok($thanThem !== '', 'Tach duoc than ham Cart::add()');
ok(strpos($thanThem, 'chiTietWeb') !== false,
   'Cart::add() kiem bang chiTietWeb() (hang co ban tren gian hang nay khong)');
ok(strpos($thanThem, 'getDetail') === false,
   'Cart::add() KHONG con dung getDetail() — hai ham tra loi hai cau khac nhau');
ok(strpos($than('function buildRows(', 'function index()'), 'chiTietWeb') !== false,
   'buildRows() van dung chiTietWeb() — day la cau hoi chuan');

// ===========================================================================
section('Chay that: gian hang gara, hang kho tong, gara khong co ton');

$base = web_goc();
$jar  = tempnam(sys_get_temp_dir(), 'dh');
$HOST = 'dmsg.localhost';           // gara 2 — cung gara voi dmsg.etek.rikkeiedu.org

$http = function($url, $post = null) use (&$jar, $HOST){
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_HEADER => true,
        CURLOPT_TIMEOUT => 30, CURLOPT_HTTPHEADER => ["Host: $HOST"]]);
    if ($post !== null){ curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post)); }
    $r = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $hs   = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    preg_match('~^Location:\s*(.+)$~im', substr((string) $r, 0, $hs), $m);
    return ['code' => $code, 'loc' => isset($m[1]) ? trim($m[1]) : '', 'body' => substr((string) $r, $hs)];
};
$tok = function($b){ return preg_match('~name="_token"[^>]*value="([^"]+)"~', $b, $m) ? $m[1] : ''; };

$r = $http("$base/");
if ($r['code'] === 0){ echo "\n[SKIP] Apache khong chay (localhost:88).\n"; @unlink($jar); exit(summary()); }

require_once $goc . 'vendor/autoload.php';
require_once $goc . 'config.php';
try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){ echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; @unlink($jar); exit(summary()); }

/* Mat hang phai dung CA HAI dieu kien, neu khong phep thu khong chung minh
   duoc gi:
     - CO BAN tren gian hang cua gara 2 (khach bam vao duoc, them gio duoc)
     - gara 2 KHONG CO TON  (nen den buoc chot don moi bi chan)
   Dieu thu nhat khong suy ra duoc bang cau SQL: quy tac "hang nao len web cua
   gara nao" nam trong chiHangLenWeb() cua model. Nen thu that: them vao gio
   roi xem gio co nhan khong — do chinh la cau tra loi. */
$ungVien = $pdo->query(
    "SELECT p.id, p.name FROM parts p
      WHERE p.status = 1 AND p.garage_id IS NULL
        AND NOT EXISTS (SELECT 1 FROM stocks s2 JOIN warehouses w2 ON w2.id = s2.warehouse_id
                         WHERE s2.part_id = p.id AND w2.garage_id = 2 AND s2.quantity > 0)
      ORDER BY p.id LIMIT 40")->fetchAll(PDO::FETCH_ASSOC);

$hang = null;
foreach ($ungVien as $uv){
    $t = $tok($http("$base/")['body']);
    $http("$base/gio-hang/them", ['_token' => $t, 'part_id' => $uv['id'], 'qty' => 1]);
    $g = $http("$base/gio-hang");
    if ($g['code'] === 200 && mb_strpos($g['body'], $uv['name']) !== false){ $hang = $uv; break; }
    $http("$base/gio-hang/xoa/" . (int) $uv['id']);   // don gio cho lan thu sau
}

if (empty($hang)){ echo "\n[SKIP] Khong tim duoc hang vua ban tren gian hang gara 2 vua het ton.\n"; @unlink($jar); exit(summary()); }

ok(true, 'Hang CO ban tren gian hang gara 2 nhung gara KHONG co ton',
   '#' . $hang['id'] . ' ' . $hang['name']);

$soTruoc = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();

$r = $http("$base/dat-hang");
ok($r['code'] === 200, 'Vao duoc trang Dat hang', 'HTTP ' . $r['code'] . ' ' . $r['loc']);
$t = $tok($r['body']);

$units = json_decode((string) file_get_contents($goc . 'public/assets/data/vn-administrative.json'), true);
ok(is_array($units) && count($units) > 0,
   'Doc duoc danh sach tinh/phuong (o chon dia chi nap tu file nay)',
   is_array($units) ? count($units) . ' tinh' : 'khong doc duoc');

$tinh = $units[0];
$xa   = $tinh['w'][0];

/* NOI TRUOC KHI BAM, khong phai goi bao sau. */
ok(mb_strpos($r['body'], 'đặt trước') !== false || mb_strpos($r['body'], 'Đặt trước') !== false,
   'Trang Dat hang BAO TRUOC la mat hang nay phai dat truoc');
ok(mb_strpos($r['body'], $hang['name']) !== false,
   'Loi nhac goi dung TEN mat hang phai doi, khong noi chung chung');

$resvTruoc = (int) $pdo->query('SELECT COUNT(*) FROM stock_reservations')->fetchColumn();

$r = $http("$base/dat-hang", ['_token' => $t, 'name' => 'Khach Thu', 'phone' => '0366583569',
    'email' => '', 'province_code' => $tinh['c'], 'ward_code' => $xa['c'],
    'address' => 'So 1 duong Thu', 'note' => '', 'payment_method' => 'bank_transfer']);

$soSau = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();

/* ĐÂY LÀ KHẲNG ĐỊNH CHÍNH — anh chốt 12/11/2026: thieu hang thi van cho dat. */
ok($soSau === $soTruoc + 1,
   'Gara het ton VAN TAO DUOC DON (dat truoc)',
   "so don $soTruoc -> $soSau");

if ($soSau === $soTruoc + 1){
    $don = $pdo->query('SELECT id, order_no FROM orders ORDER BY id DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    $st  = $pdo->prepare('SELECT quantity, preorder_qty FROM order_items WHERE order_id = ? AND part_id = ?');
    $st->execute([(int) $don['id'], (int) $hang['id']]);
    $dongDon = $st->fetch(PDO::FETCH_ASSOC);

    ok(!empty($dongDon), 'Don co dong hang vua dat', $don['order_no']);
    ok(!empty($dongDon) && (float) $dongDon['preorder_qty'] == 1.0,
       'Dong hang ghi ro SO LUONG PHAI DOI (preorder_qty = 1)',
       !empty($dongDon) ? 'quantity=' . $dongDon['quantity'] . ' preorder=' . $dongDon['preorder_qty'] : '');

    /* KHONG DUOC GIU TON PHAN CHUA CO. Giu cai khong co la day
       sellableByPart() xuong so am -> khach sau vao thay het hang trong khi
       kho van con. */
    $st2 = $pdo->prepare('SELECT COALESCE(SUM(quantity),0) FROM stock_reservations WHERE order_id = ? AND part_id = ?');
    $st2->execute([(int) $don['id'], (int) $hang['id']]);
    ok((float) $st2->fetchColumn() == 0.0,
       'KHONG giu ton cho phan dat truoc (gara dang co 0 cai)');

    $resvSau = (int) $pdo->query('SELECT COUNT(*) FROM stock_reservations')->fetchColumn();
    ok($resvSau === $resvTruoc, 'Khong sinh dong giu ton nao thua', "$resvTruoc -> $resvSau");

    /* Don thu la rac trong CSDL that — don di. */
    $pdo->prepare('DELETE FROM order_items WHERE order_id = ?')->execute([(int) $don['id']]);
    $pdo->prepare('DELETE FROM stock_reservations WHERE order_id = ?')->execute([(int) $don['id']]);
    $pdo->prepare('DELETE FROM orders WHERE id = ?')->execute([(int) $don['id']]);
    ok((int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn() === $soTruoc,
       'Da don sach don thu');
}

@unlink($jar);
exit(summary());
