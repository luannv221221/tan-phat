<?php
/**
 * KẾ TOÁN — SỔ QUỸ (tiền mặt + tiền gửi ngân hàng).
 *
 * Chạy:  C:\xampp\php\php.exe tests\SoQuyTest.php
 *
 * Khách ghi 25/11/2026 ở dòng "Báo cáo nợ xấu": "Thêm sổ quỹ (tiền mặt, tiền NH
 * thì tự nhập)". Không có nguồn dữ liệu ngân hàng nào — tiền NH nhập tay bằng
 * phiếu thu / phiếu chi mang loại phiếu định khoản vào 1121 (migration 000119).
 *
 * SÁU CHỖ HỎNG SẼ ÂM THẦM, mỗi chỗ một mục bên dưới:
 *
 *   1. LẪN QUỸ. Phiếu ngân hàng hiện trong sổ tiền mặt (hoặc ngược lại) thì
 *      tồn quỹ tiền mặt trên sổ lệch với tiền đếm trong két mà nhìn từng dòng
 *      không thấy sai. Quỹ nhận ra theo TÀI KHOẢN trên phiếu (nợ 111* = thu vào
 *      quỹ tiền mặt, có 111* = chi ra), không theo màn Phiếu thu / Phiếu chi.
 *
 *   2. TỒN ĐẦU KỲ SAI. Tồn đầu kỳ = số dư ban đầu + mọi phiếu từ ngày số dư ban
 *      đầu tới TRƯỚC ngày đầu kỳ. Quên phần phiếu trước kỳ là tồn đầu kỳ tháng
 *      nào cũng bằng số dư ngày khai trương. Cộng cả phiếu TRƯỚC ngày số dư ban
 *      đầu là đếm hai lần (số dư ban đầu đã gồm chúng).
 *
 *   3. TỒN SAU PHIẾU CỘNG DỒN SAI THỨ TỰ. Phải theo ngày rồi theo thứ tự lập.
 *
 *   4. LỌT GARA. Sổ quỹ của gara Sài Gòn hiện phiếu của gara Đà Nẵng. Số dư
 *      ban đầu ghi vào gara tổng thay vì gara của người bấm Lưu (bẫy
 *      gara_hien_tai_id() ở dòng lệnh / epGara() trên web).
 *
 *   5. PHIẾU HUỶ VẪN TÍNH. Phiếu không có trạng thái nháp — `status` 0 là đã
 *      huỷ và phải rơi khỏi mọi phép cộng, như các thẻ báo cáo khác.
 *
 *   6. AI CŨNG SỬA ĐƯỢC SỐ DƯ BAN ĐẦU. Đổi số dư ban đầu là đổi tồn quỹ của
 *      mọi kỳ — phải có quyền SỬA cả Phiếu thu lẫn Phiếu chi.
 *
 * DỮ LIỆU TEST: phiếu `ZZSQ…` dồn hết vào năm 2001 — các test khác chạy song
 * song trên cùng CSDL lập phiếu ngày hôm nay, không lọt vào kỳ của test này.
 * Số dư ban đầu của hai gara mẫu được chụp lại trước và trả về sau.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

use App\core\Model;

echo 'PHP ' . PHP_VERSION . "\n";
$goc  = __DIR__ . '/../';
$base = web_goc();

// ---------------------------------------------------------------------------
section('Migration + noi day');

$mg = glob($goc . 'database/migrations/*_000119_*.php');
ok(count($mg) === 1, 'Co migration 000119 (loai phieu tien gui ngan hang)');
$src = count($mg) === 1 ? file_get_contents($mg[0]) : '';
ok(strpos($src, "'1121'") !== false, 'Migration dinh khoan vao TK 1121');
ok(!preg_match('~\b(CREATE|ALTER|DROP)\s+TABLE~i', $src),
   'Migration chi them du lieu, khong doi cau truc bang (chay TRUOC khi day code duoc)');

ok(is_file($goc . 'app/models/SoQuyModel.php'), 'Co app/models/SoQuyModel.php');

$rt = codeOnly($goc . 'routes/web.php');
ok(preg_match("~Route::post\('phieu-thu/so-du-quy',~", $rt) === 1,
   'Co tuyen POST phieu-thu/so-du-quy (luu so du ban dau)');
ok(strpos($rt, "'bao-cao-ke-toan/") === false,
   'Bao cao ke toan van KHONG co duong ghi nao',
   'Module bao-cao-ke-toan chi co quyen `view` — duong ghi o day thi khong ai qua duoc RoleMiddleware');

$vw = is_file($goc . 'app/views/admin/bao-cao-ke-toan/index.php')
    ? file_get_contents($goc . 'app/views/admin/bao-cao-ke-toan/index.php') : '';
ok(strpos($vw, "'quy' =>") !== false && mb_strpos($vw, 'Sổ quỹ') !== false,
   'Man Bao cao ke toan co the "So quy"');

$ctl = is_file($goc . 'app/controllers/admin/Phieuthu.php') ? codeOnly($goc . 'app/controllers/admin/Phieuthu.php') : '';
ok(strpos($ctl, "route('admin/phieu-thu/edit/0')") !== false
   && strpos($ctl, "route('admin/phieu-chi/edit/0')") !== false,
   'Luu so du ban dau doi quyen SUA ca phieu thu lan phieu chi',
   'Middleware chi doi "co quyen ghi nao do" tren phieu-thu — Staff co `add` la lot');

// ---------------------------------------------------------------------------
section('Chay that tren MySQL');

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

if (!is_file($goc . 'app/models/SoQuyModel.php')){
    echo "\n  (chua co SoQuyModel — dung o day)\n"; exit(summary());
}
foreach (['AccVoucherTypesModel', 'AccVouchersModel', 'SoQuyModel'] as $m){
    require_once $goc . 'app/models/' . $m . '.php';
}
$T = new AccVoucherTypesModel(); $V = new AccVouchersModel(); $S = new SoQuyModel();

/* Gara A = gara cua chugara.sg@gara-mau.test (phan HTTP dang nhap bang tai
   khoan do, nen so lieu phai nam o dung gara cua no). Gara B = mot gara khac. */
$gA = (int) $pdo->query("SELECT garage_id FROM users WHERE email = 'chugara.sg@gara-mau.test'")->fetchColumn();
$gB = (int) $pdo->query("SELECT id FROM garages WHERE is_master = 0 AND status = 1 AND id <> " . $gA
                        . " ORDER BY id LIMIT 1")->fetchColumn();
$gTong = (int) $pdo->query("SELECT id FROM garages WHERE is_master = 1 ORDER BY id LIMIT 1")->fetchColumn();
if ($gA === 0 || $gB === 0 || $gTong === 0){
    echo "\n[SKIP] Can gara tong + gara cua chugara.sg + mot gara khac (chay tools/tao-du-lieu-gara-mau.php).\n";
    exit(summary());
}

/* Chup lai so du ban dau that cua hai gara, tra ve nguyen trang luc ket thuc */
$KHOA = ['so_quy_tien_mat_so_du', 'so_quy_tien_mat_ngay', 'so_quy_ngan_hang_so_du', 'so_quy_ngan_hang_ngay'];
$inKhoa = "'" . implode("','", $KHOA) . "'";
$chup = $pdo->query("SELECT garage_id, skey, svalue, update_at FROM garage_settings
                      WHERE skey IN ($inKhoa) AND garage_id IN ($gA, $gB, $gTong)")->fetchAll(PDO::FETCH_ASSOC);

$donSach = function() use ($pdo, $chup, $inKhoa, $gA, $gB, $gTong){
    $pdo->exec("DELETE FROM acc_vouchers WHERE voucher_no LIKE 'ZZSQ%'");
    $pdo->exec("DELETE FROM acc_accounts WHERE code LIKE 'ZZSQ%'");
    $pdo->exec("DELETE FROM garage_settings WHERE skey IN ($inKhoa) AND garage_id IN ($gA, $gB, $gTong)");
    $ins = $pdo->prepare("INSERT INTO garage_settings (garage_id, skey, svalue, update_at) VALUES (?, ?, ?, ?)");
    foreach ($chup as $r) $ins->execute([$r['garage_id'], $r['skey'], $r['svalue'], $r['update_at']]);
};
$pdo->exec("DELETE FROM acc_vouchers WHERE voucher_no LIKE 'ZZSQ%'");
$pdo->exec("DELETE FROM acc_accounts WHERE code LIKE 'ZZSQ%'");
$pdo->exec("DELETE FROM garage_settings WHERE skey IN ($inKhoa) AND garage_id IN ($gA, $gB, $gTong)");
register_shutdown_function($donSach);

// --- Loại phiếu ngân hàng (migration 000119) ---
section('Loai phieu tien gui ngan hang — dung chung moi gara');

$vtTheoMa = function($ma) use ($pdo){
    $st = $pdo->prepare("SELECT v.id, v.loai, v.status, a.code AS no_code, b.code AS co_code
                           FROM acc_voucher_types v
                           LEFT JOIN acc_accounts a ON a.id = v.debit_id
                           LEFT JOIN acc_accounts b ON b.id = v.credit_id
                          WHERE v.code = ? AND v.garage_id IS NULL LIMIT 1");
    $st->execute([$ma]);
    return $st->fetch(PDO::FETCH_ASSOC);
};
$vtPT01 = $vtTheoMa('PT01'); $vtPC01 = $vtTheoMa('PC01');
$vtBC01 = $vtTheoMa('BC01'); $vtBN01 = $vtTheoMa('BN01');

ok(!empty($vtPT01) && !empty($vtPC01), 'Co san PT01 / PC01 (tien mat, migration 000096)');
ok(!empty($vtBC01) && $vtBC01['loai'] === 'thu' && $vtBC01['no_code'] === '1121' && (int) $vtBC01['status'] === 1,
   'BC01 la loai PHIEU THU, No 1121 (tien vao ngan hang)', json_encode($vtBC01));
ok(!empty($vtBN01) && $vtBN01['loai'] === 'chi' && $vtBN01['co_code'] === '1121' && (int) $vtBN01['status'] === 1,
   'BN01 la loai PHIEU CHI, Co 1121 (tien ra khoi ngan hang)', json_encode($vtBN01));

$nhLoai = $pdo->query("SELECT code, loai FROM acc_voucher_types
                        WHERE garage_id IS NULL AND (code LIKE 'BC%' OR code LIKE 'BN%')")->fetchAll(PDO::FETCH_KEY_PAIR);
$thieuDk = (int) $pdo->query("SELECT COUNT(*) FROM acc_voucher_types
                               WHERE garage_id IS NULL AND (code LIKE 'BC%' OR code LIKE 'BN%')
                                 AND (debit_id IS NULL OR credit_id IS NULL)")->fetchColumn();
ok($thieuDk === 0, 'Moi loai phieu ngan hang deu du cap no - co (lap phieu khong bi chan)');

/* Gara A thay duoc loai phieu ngan hang o o chon cua man Phieu thu / Phieu chi */
$oThu = Model::trongGara($gA, function() use ($T){ return $T->getLists('thu', true); });
$oChi = Model::trongGara($gA, function() use ($T){ return $T->getLists('chi', true); });
ok(in_array('BC01', array_column((array) $oThu, 'code'), true), 'Gara A thay BC01 o man Phieu thu');
ok(in_array('BN01', array_column((array) $oChi, 'code'), true), 'Gara A thay BN01 o man Phieu chi');
ok(!in_array('BN01', array_column((array) $oThu, 'code'), true), 'BN01 (chi) KHONG hien o man Phieu thu');

if (empty($vtPT01) || empty($vtPC01) || empty($vtBC01) || empty($vtBN01)){
    echo "\n  (thieu loai phieu — chua chay migration 000119? dung o day)\n"; exit(summary());
}

// --- Dựng dữ liệu ---
/* Lap phieu DUOI DANH NGHIA gara $g — Model::trongGara, khong epGara (epGara
   khong co tac dung tren web, va o day ta muon dung dung con duong cua web). */
$lap = function($g, $so, $ngay, $vt, $tien, $trangThai = 1, $no = null, $co = null) use ($V){
    return Model::trongGara($g, function() use ($V, $so, $ngay, $vt, $tien, $trangThai, $no, $co){
        return $V->add([
            'voucher_no' => $so, 'loai' => $vt['loai'], 'vtype_id' => (int) $vt['id'],
            'voucher_date' => $ngay, 'dt_loai' => 'khac', 'partner_id' => null, 'user_id' => null,
            'dt_ten' => 'ZZSQ Doi tuong ' . $so, 'dt_diachi' => null, 'ly_do' => 'ZZSQ ly do ' . $so,
            'amount' => $tien,
            'debit_code'  => $no !== null ? $no : $vt['no_code'],
            'credit_code' => $co !== null ? $co : $vt['co_code'],
            'kem_theo' => null, 'note' => null, 'status' => $trangThai, 'created_by' => null,
        ]);
    });
};

/* Gara A */
$lap($gA, 'ZZSQ-T0',  '2001-01-10', $vtPT01, 100000);      // TRUOC ngay so du ban dau -> da nam trong so du
$lap($gA, 'ZZSQ-T1',  '2001-02-05', $vtPT01, 500000);      // truoc ky
$lap($gA, 'ZZSQ-C2',  '2001-02-20', $vtPC01, 200000);      // truoc ky
$lap($gA, 'ZZSQ-T3',  '2001-03-02', $vtPT01, 300000);      // trong ky, tien mat
$lap($gA, 'ZZSQ-C4',  '2001-03-05', $vtPC01, 150000);      // trong ky, tien mat
$lap($gA, 'ZZSQ-NT5', '2001-03-05', $vtBC01, 700000);      // trong ky, NGAN HANG thu
$lap($gA, 'ZZSQ-NC6', '2001-03-10', $vtBN01, 250000);      // trong ky, NGAN HANG chi
$lap($gA, 'ZZSQ-H7',  '2001-03-12', $vtPT01, 999000, 0);   // DA HUY
$lap($gA, 'ZZSQ-T8',  '2001-04-02', $vtPT01, 50000);       // sau ky
/* Gara B — cung ngay, cung quy, so tien de nhan ra */
$lap($gB, 'ZZSQ-B9',  '2001-03-03', $vtPT01, 12345000);
$lap($gB, 'ZZSQ-BN',  '2001-03-04', $vtBC01, 6789000);

$soPhieu = function($so){ return array_column((array) $so['dong'], 'voucher_no'); };
$soQuy = function($g, $quy, $tu, $den) use ($S){
    return Model::trongGara($g, function() use ($S, $quy, $tu, $den){ return $S->so($quy, $tu, $den); });
};
$gan = function($a, $b){ return abs((float) $a - (float) $b) < 0.005; };

// --- 2. Số dư ban đầu: ghi đúng gara ---
section('2+4. So du ban dau: ghi vao DUNG gara, doc rieng tung gara');

ok($S->luuSoDuBanDau('tien_mat', 5000000, '2001-01-01') === false,
   'Dong lenh chua chon gara -> TU CHOI ghi (khong ghi bua vao gara nao)');

$ghi = Model::trongGara($gA, function() use ($S){ return $S->luuSoDuBanDau('tien_mat', 1000000, '2001-02-01'); });
ok($ghi === true, 'Luu so du ban dau tien mat cua gara A');
$dong = $pdo->query("SELECT garage_id, svalue FROM garage_settings WHERE skey = 'so_quy_tien_mat_so_du'
                      AND garage_id IN ($gA, $gB, $gTong)")->fetchAll(PDO::FETCH_KEY_PAIR);
ok(isset($dong[$gA]) && $gan($dong[$gA], 1000000) && count($dong) === 1,
   'Dong `garage_settings` mang garage_id cua gara A — khong phai gara tong', json_encode($dong));

$sdA = Model::trongGara($gA, function() use ($S){ return $S->soDuBanDau('tien_mat'); });
$sdB = Model::trongGara($gB, function() use ($S){ return $S->soDuBanDau('tien_mat'); });
ok($gan($sdA['so_du'], 1000000) && $sdA['ngay'] === '2001-02-01', 'Gara A doc lai dung so du + ngay', json_encode($sdA));
ok($gan($sdB['so_du'], 0) && $sdB['ngay'] === null, 'Gara B KHONG thay so du cua gara A', json_encode($sdB));

ok(Model::trongGara($gA, function() use ($S){ return $S->luuSoDuBanDau('tien_mat', 1, '01/02/2001'); }) === false,
   'Ngay sai dinh dang -> tu choi');
ok(Model::trongGara($gA, function() use ($S){ return $S->luuSoDuBanDau('vang_bac', 1, '2001-02-01'); }) === false,
   'Quy la -> tu choi');
$sdA = Model::trongGara($gA, function() use ($S){ return $S->soDuBanDau('tien_mat'); });
ok($gan($sdA['so_du'], 1000000) && $sdA['ngay'] === '2001-02-01', 'Lan ghi bi tu choi KHONG lam doi so du cu');

// --- 1 + 2 + 3 + 5. Sổ tiền mặt ---
section('1+2+3+5. So tien mat thang 03/2001 cua gara A');

$tm = $soQuy($gA, 'tien_mat', '2001-03-01', '2001-03-31');
ok($soPhieu($tm) === ['ZZSQ-T3', 'ZZSQ-C4'],
   'Chi hai phieu tien mat trong ky, dung thu tu ngay', json_encode($soPhieu($tm)));
ok(!in_array('ZZSQ-NT5', $soPhieu($tm), true) && !in_array('ZZSQ-NC6', $soPhieu($tm), true),
   'Phieu NGAN HANG KHONG nam trong so tien mat');
ok(!in_array('ZZSQ-H7', $soPhieu($tm), true), 'Phieu DA HUY KHONG nam trong so');
ok(!in_array('ZZSQ-B9', $soPhieu($tm), true), 'Phieu cua gara B KHONG nam trong so cua gara A');

/* 1.000.000 (ban dau 01/02) + 500.000 (05/02) - 200.000 (20/02) = 1.300.000.
   ZZSQ-T0 (10/01) truoc ngay so du ban dau -> khong cong lan nua. */
ok($gan($tm['dau_ky'], 1300000), 'Ton dau ky = so du ban dau + thu - chi tu ngay ban dau toi truoc ky = 1.300.000',
   'Dang ra: ' . $tm['dau_ky']);
ok(isset($tm['dong'][0], $tm['dong'][1])
   && $gan($tm['dong'][0]['thu'], 300000) && $gan($tm['dong'][0]['chi'], 0) && $gan($tm['dong'][0]['ton'], 1600000)
   && $gan($tm['dong'][1]['thu'], 0) && $gan($tm['dong'][1]['chi'], 150000) && $gan($tm['dong'][1]['ton'], 1450000),
   'Ton sau tung phieu: 1.600.000 roi 1.450.000', json_encode($tm['dong']));
ok($gan($tm['tong_thu'], 300000) && $gan($tm['tong_chi'], 150000) && $gan($tm['cuoi_ky'], 1450000),
   'Cong thu 300.000 / chi 150.000 / ton cuoi ky 1.450.000',
   json_encode(['thu' => $tm['tong_thu'], 'chi' => $tm['tong_chi'], 'cuoi' => $tm['cuoi_ky']]));
ok($tm['dong'][0]['dt_ten'] === 'ZZSQ Doi tuong ZZSQ-T3' && $tm['dong'][0]['ly_do'] === 'ZZSQ ly do ZZSQ-T3'
   && $tm['dong'][0]['voucher_date'] === '2001-03-02',
   'Moi dong co ngay, dien giai, doi tuong');

/* Huy mot phieu TRUOC ky -> ton dau ky doi theo */
$pdo->exec("UPDATE acc_vouchers SET status = 0 WHERE voucher_no = 'ZZSQ-C2' AND garage_id = $gA");
$tmHuy = $soQuy($gA, 'tien_mat', '2001-03-01', '2001-03-31');
ok($gan($tmHuy['dau_ky'], 1500000), 'Huy phieu chi truoc ky -> ton dau ky tang dung 200.000', 'Dang ra: ' . $tmHuy['dau_ky']);
$pdo->exec("UPDATE acc_vouchers SET status = 1 WHERE voucher_no = 'ZZSQ-C2' AND garage_id = $gA");

// --- Kỳ chồm qua ngày số dư ban đầu ---
section('2. Ky bat dau TRUOC ngay so du ban dau');

$truoc = $soQuy($gA, 'tien_mat', '2001-01-01', '2001-02-28');
ok($soPhieu($truoc) === ['ZZSQ-T1', 'ZZSQ-C2'],
   'Chi tinh tu ngay so du ban dau — phieu truoc do (ZZSQ-T0) da nam trong so du', json_encode($soPhieu($truoc)));
ok($gan($truoc['dau_ky'], 1000000) && $truoc['tu_thuc'] === '2001-02-01' && $gan($truoc['cuoi_ky'], 1300000),
   'Ton dau = so du ban dau, so bat dau tu 01/02/2001, ton cuoi 1.300.000',
   json_encode(['dau' => $truoc['dau_ky'], 'tu_thuc' => $truoc['tu_thuc'], 'cuoi' => $truoc['cuoi_ky']]));

$hoanToanTruoc = $soQuy($gA, 'tien_mat', '2001-01-01', '2001-01-31');
ok(!empty($hoanToanTruoc['truoc_ngay_dau']) && empty($hoanToanTruoc['dong']),
   'Ky nam HAN truoc ngay so du ban dau -> bao ro, khong bay so lieu doan mo');

// --- 1. Sổ ngân hàng ---
section('1. So tien gui ngan hang thang 03/2001 cua gara A (chua khai so du ban dau)');

$nh = $soQuy($gA, 'ngan_hang', '2001-03-01', '2001-03-31');
ok($soPhieu($nh) === ['ZZSQ-NT5', 'ZZSQ-NC6'], 'Chi hai phieu ngan hang', json_encode($soPhieu($nh)));
ok(!array_intersect(['ZZSQ-T3', 'ZZSQ-C4', 'ZZSQ-T1'], $soPhieu($nh)), 'Phieu TIEN MAT KHONG nam trong so ngan hang');
ok(!in_array('ZZSQ-BN', $soPhieu($nh), true), 'Phieu ngan hang cua gara B KHONG nam trong so cua gara A');
ok($gan($nh['dau_ky'], 0) && $gan($nh['dong'][0]['ton'], 700000) && $gan($nh['dong'][1]['ton'], 450000)
   && $gan($nh['cuoi_ky'], 450000),
   'Chua khai so du ban dau -> bat dau tu 0: ton 700.000 roi 450.000', json_encode($nh));

/* Tai khoan con cua gara khai DUOI 112 ma ma khong bat dau bang 112 (vd "VCB")
   van la tien gui ngan hang. */
$tk112 = (int) $pdo->query("SELECT id FROM acc_accounts WHERE code = '112' AND garage_id IS NULL LIMIT 1")->fetchColumn();
$pdo->prepare("INSERT INTO acc_accounts (code, name, parent_id, sort_order, status, garage_id, create_at)
               VALUES ('ZZSQVCB', 'ZZSQ Vietcombank', ?, 0, 1, ?, NOW())")->execute([$tk112 ?: null, $gA]);
$lap($gA, 'ZZSQ-VCB', '2001-03-15', $vtBC01, 40000, 1, 'ZZSQVCB', '131');
$nh2 = $soQuy($gA, 'ngan_hang', '2001-03-01', '2001-03-31');
ok(in_array('ZZSQ-VCB', $soPhieu($nh2), true) && $gan($nh2['cuoi_ky'], 490000),
   'Tai khoan con cua gara khai duoi 112 (ma ZZSQVCB) van vao so ngan hang', json_encode($soPhieu($nh2)));
$nhB = $soQuy($gB, 'ngan_hang', '2001-03-01', '2001-03-31');
ok($soPhieu($nhB) === ['ZZSQ-BN'], 'So ngan hang gara B chi co phieu cua gara B', json_encode($soPhieu($nhB)));

// --- 4. Gara B ---
section('4. So tien mat gara B');

$tmB = $soQuy($gB, 'tien_mat', '2001-03-01', '2001-03-31');
ok($soPhieu($tmB) === ['ZZSQ-B9'] && $gan($tmB['dau_ky'], 0) && $gan($tmB['cuoi_ky'], 12345000),
   'Gara B chi thay phieu cua minh, ton dau 0 (chua khai so du)', json_encode($tmB));

// --- HTTP ---
section('6. Qua HTTP: man hinh + quyen sua so du ban dau');

$MK   = 'Gara@2026';     // MK_MAU trong tools/tao-du-lieu-gara-mau.php — chi co o may local
$jars = [];
register_shutdown_function(function() use (&$jars){ foreach ($jars as $j) @unlink($j); });

$http = function($method, $url, $jar, $data = null){
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 20,
    ]);
    if ($method === 'POST'){
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    $raw  = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    $body = $raw === false ? '' : substr($raw, (int) $info['header_size']);
    return ['code' => (int) $info['http_code'], 'loc' => (string) $info['redirect_url'], 'text' => $body];
};
$token = function($h){ return preg_match('~name="_token" value="([^"]+)"~', $h, $m) ? $m[1] : ''; };
$dangNhap = function($email) use ($http, $token, $base, $MK, &$jars){
    $jar = tempnam(sys_get_temp_dir(), 'zzsq'); $jars[] = $jar;
    $r = $http('GET', "$base/dang-nhap", $jar);
    if ($r['code'] === 0) return null;
    $http('POST', "$base/dang-nhap", $jar, ['email' => $email, 'password' => $MK, '_token' => $token($r['text'])]);
    return $jar;
};

$jarM = $dangNhap('chugara.sg@gara-mau.test');
if ($jarM === null){
    echo "\n[SKIP] Apache khong chay (localhost:88) — bo qua phan goi HTTP that.\n";
    exit(summary());
}

$urlTm = "$base/admin/bao-cao-ke-toan?tab=quy&quy=tien_mat&tu=2001-03-01&den=2001-03-31";
$urlNh = "$base/admin/bao-cao-ke-toan?tab=quy&quy=ngan_hang&tu=2001-03-01&den=2001-03-31";

$r = $http('GET', $urlTm, $jarM);
ok($r['code'] === 200 && strpos($r['text'], 'ZZSQ-T3') !== false && strpos($r['text'], 'ZZSQ-C4') !== false,
   'MANAGER mo the So quy tien mat: thay hai phieu tien mat', 'HTTP ' . $r['code'] . ' -> ' . $r['loc']);
ok(strpos($r['text'], '1.300.000') !== false && strpos($r['text'], '1.450.000') !== false,
   'Man hinh in ton dau ky 1.300.000 va ton cuoi ky 1.450.000');
ok(strpos($r['text'], 'ZZSQ-NT5') === false && strpos($r['text'], 'ZZSQ-B9') === false
   && strpos($r['text'], 'ZZSQ-H7') === false,
   'Man tien mat KHONG co phieu ngan hang / phieu gara B / phieu da huy');
ok(strpos($r['text'], 'so-du-quy') !== false && $token($r['text']) !== '',
   'Manager (co quyen sua phieu thu + chi) thay form so du ban dau');
ok(strpos($r['text'], 'Fatal error') === false && strpos($r['text'], 'Warning:') === false
   && strpos($r['text'], 'Notice:') === false,
   'Khong co loi PHP tren trang');

$r = $http('GET', $urlNh, $jarM);
ok($r['code'] === 200 && strpos($r['text'], 'ZZSQ-NT5') !== false && strpos($r['text'], 'ZZSQ-NC6') !== false
   && strpos($r['text'], 'ZZSQ-T3') === false && strpos($r['text'], 'ZZSQ-BN') === false,
   'The So quy ngan hang: chi phieu ngan hang cua gara minh');

/* Manager luu so du ban dau ngan hang qua web -> vao gara A, khong vao gara tong */
$tk = $token($r['text']);
$kq = $http('POST', "$base/admin/phieu-thu/so-du-quy", $jarM,
            ['_token' => $tk, 'quy' => 'ngan_hang', 'so_du' => '2.000.000', 'ngay' => '2001-01-01',
             'tu' => '2001-03-01', 'den' => '2001-03-31']);
$luu = $pdo->query("SELECT garage_id, svalue FROM garage_settings WHERE skey = 'so_quy_ngan_hang_so_du'
                     AND garage_id IN ($gA, $gB, $gTong)")->fetchAll(PDO::FETCH_KEY_PAIR);
ok(strpos($kq['loc'], 'bao-cao-ke-toan') !== false && isset($luu[$gA]) && $gan($luu[$gA], 2000000)
   && count($luu) === 1,
   'MANAGER luu so du ban dau ngan hang qua web: vao DUNG gara cua minh, quay ve so quy',
   'HTTP ' . $kq['code'] . ' -> ' . $kq['loc'] . ' | ' . json_encode($luu));

$r = $http('GET', $urlNh, $jarM);
ok(strpos($r['text'], '2.000.000') !== false && strpos($r['text'], '2.490.000') !== false,
   'Sau khi luu: ton dau 2.000.000, ton cuoi 2.490.000 tren man hinh');

/* Staff: co `add` tren phieu thu (du qua RoleMiddleware) nhung KHONG co `edit` */
$jarS = $dangNhap('covan.sg@gara-mau.test');
/* Token lay o trang CO FORM (trang danh sach khong co o `_token`): thieu token
   thi CsrfMiddleware tra 500 truoc khi toi phan quyen, va khang dinh duoi
   khong con do quyen nua. */
$tkS  = $token($http('GET', "$base/admin/phieu-thu/add", $jarS)['text']);
ok($tkS !== '', 'Staff lay duoc token CSRF (de lan POST duoi that su toi phan quyen)');
$kqS  = $http('POST', "$base/admin/phieu-thu/so-du-quy", $jarS,
              ['_token' => $tkS, 'quy' => 'ngan_hang', 'so_du' => '9.999.999', 'ngay' => '2001-01-01']);
$sau  = $pdo->query("SELECT svalue FROM garage_settings WHERE skey = 'so_quy_ngan_hang_so_du' AND garage_id = $gA")->fetchColumn();
ok(strpos($kqS['loc'], 'khong-co-quyen') !== false && $gan($sau, 2000000),
   'STAFF (khong co quyen sua phieu): bi chan, so du KHONG doi',
   'HTTP ' . $kqS['code'] . ' -> ' . $kqS['loc'] . ' | so du: ' . $sau);

/* Chua dang nhap */
$jarAn = tempnam(sys_get_temp_dir(), 'zzsq'); $jars[] = $jarAn;
$tkAn  = $token($http('GET', "$base/dang-nhap", $jarAn)['text']);
$kqAn  = $http('POST', "$base/admin/phieu-thu/so-du-quy", $jarAn,
               ['_token' => $tkAn, 'quy' => 'ngan_hang', 'so_du' => '1', 'ngay' => '2001-01-01']);
$sau  = $pdo->query("SELECT svalue FROM garage_settings WHERE skey = 'so_quy_ngan_hang_so_du' AND garage_id = $gA")->fetchColumn();
ok($gan($sau, 2000000) && $kqAn['loc'] !== '' && strpos($kqAn['loc'], 'bao-cao-ke-toan') === false,
   'CHUA DANG NHAP: bi chan, so du KHONG doi', 'HTTP ' . $kqAn['code'] . ' -> ' . $kqAn['loc']);

/* Xoa so du ban dau: de trong ngay */
$tk = $token($http('GET', $urlNh, $jarM)['text']);
$http('POST', "$base/admin/phieu-thu/so-du-quy", $jarM,
      ['_token' => $tk, 'quy' => 'ngan_hang', 'so_du' => '', 'ngay' => '', 'tu' => '2001-03-01', 'den' => '2001-03-31']);
$conDong = (int) $pdo->query("SELECT COUNT(*) FROM garage_settings WHERE garage_id = $gA
                               AND skey IN ('so_quy_ngan_hang_so_du', 'so_quy_ngan_hang_ngay')")->fetchColumn();
ok($conDong === 0, 'De trong ngay roi Luu = bo so du ban dau (xoa dong rieng cua gara)');

// ---------------------------------------------------------------------------
$donSach();
ok((int) $pdo->query("SELECT COUNT(*) FROM acc_vouchers WHERE voucher_no LIKE 'ZZSQ%'")->fetchColumn() === 0
   && (int) $pdo->query("SELECT COUNT(*) FROM acc_accounts WHERE code LIKE 'ZZSQ%'")->fetchColumn() === 0,
   'Da don sach du lieu test');

echo "\n";
exit(summary());
