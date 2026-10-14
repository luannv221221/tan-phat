<?php
/**
 * KẾ TOÁN — danh mục tài khoản, danh mục loại phiếu, phiếu thu / phiếu chi.
 *
 * Chạy:  C:\xampp\php\php.exe tests\KeToanTest.php
 *
 * Theo tài liệu khách gửi 14/10/2026 ("gara ô tô.docx"), migration 000096.
 *
 * BẢY CHỖ HỎNG SẼ ÂM THẦM, mỗi chỗ một mục bên dưới:
 *
 *   1. DANH MỤC KHÔNG DÙNG CHUNG ĐƯỢC. Hệ thống tài khoản chuẩn phải thấy từ
 *      mọi gara. Lọc cứng theo gara thì gara mới mở thấy danh mục TRỐNG RỖNG,
 *      mà màn Loại phiếu lại bắt chọn tài khoản nợ và có — tức không lập nổi
 *      một phiếu nào cho tới khi tự gõ lại cả hệ thống tài khoản.
 *
 *   2. GARA SỬA ĐƯỢC DANH MỤC CHUNG. Một gara đổi định khoản của PT01 là mọi
 *      gara đổi theo, và báo cáo của họ sai từ hôm đó.
 *
 *   3. CÂY TÀI KHOẢN THÀNH VÒNG. Chọn chính nó (hoặc con của nó) làm cha thì
 *      getTree() duyệt đệ quy KHÔNG BAO GIỜ DỪNG — trang treo, không phải báo
 *      lỗi.
 *
 *   4. PHIẾU MẤT BẢN CHỤP ĐỊNH KHOẢN. Phiếu phải giữ `debit_code`/`credit_code`
 *      riêng. Tra lại loại phiếu lúc in thì sửa định khoản sang năm sau là in
 *      lại phiếu cũ ra con số khác với tờ đã ký.
 *
 *   5. MÀN THU ĐỤNG ĐƯỢC PHIẾU CHI. Người chỉ được cấp màn Phiếu thu mà gõ
 *      /admin/phieu-thu/edit/<id một phiếu chi> thì sửa được bằng form màn thu.
 *
 *   6. XOÁ ĐƯỢC CHỨNG TỪ TIỀN. Phiếu biến mất thì báo cáo hôm qua khác hôm nay
 *      và không ai giải thích được. Chỉ HUỶ, và phiếu huỷ phải rơi khỏi mọi
 *      phép cộng.
 *
 *   7. SỐ TIỀN KIỂU VIỆT ĐỌC SAI. Ô "1.500.000" ép (float) ra 1.5 — bé đi một
 *      triệu lần mà vẫn là số hợp lệ.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

use App\core\Model;

echo 'PHP ' . PHP_VERSION . "\n";
$goc  = __DIR__ . '/../';
$base = 'http://localhost:88/tan-phat';

// ---------------------------------------------------------------------------
section('Migration + noi day');

$mg = glob($goc . 'database/migrations/*_ke_toan_tai_khoan_va_phieu.php');
ok(count($mg) === 1, 'Co migration nen ke toan');
$src = count($mg) === 1 ? file_get_contents($mg[0]) : '';
foreach (['acc_accounts', 'acc_voucher_types', 'acc_vouchers'] as $b){
    ok(strpos($src, $b) !== false, "Migration tao bang `$b`");
}
/* Dung so cai la tu them viec khach khong hoi — phan he ke toan CU cua du an da
   bi go han o migration 000048 dung vi phinh ra nhu vay. */
ok(!preg_match('~CREATE TABLE[^;]*`(acc_journal|acc_ledger|so_cai|acc_entries)`~i', $src),
   'Migration KHONG dung so cai / but toan (tai lieu khong yeu cau)');

$rt = codeOnly($goc . 'routes/web.php');
foreach (['acc-accounts', 'acc-voucher-types', 'phieu-thu/add', 'phieu-chi/add',
          'phieu-thu/in/(\d+)', 'phieu-thu/huy/(\d+)'] as $r){
    ok(strpos($rt, "'" . $r . "'") !== false, "Co route `$r`");
}
/* CHUNG TU TIEN KHONG CO DUONG XOA — chot 6. */
ok(strpos($rt, "'phieu-thu/delete/(\\d+)'") === false
   && strpos($rt, "'phieu-chi/delete/(\\d+)'") === false,
   'KHONG co route xoa phieu thu / phieu chi',
   'Chung tu tien ma bien mat thi bao cao hom qua khac hom nay');

$sb = file_get_contents($goc . 'app/views/layouts/admin/sidebar.php');
ok(preg_match("~'Kế toán'\s*=>\s*\[[^\]]*'acc-accounts'[^\]]*\]~u", $sb) === 1,
   'Hai danh muc nen nam trong nhom Ke toan tren menu');

ok(!file_exists($goc . 'app/views/admin/ke-toan/sap-co.php'),
   'Da bo trang giu cho "dang xay dung"',
   'Hai man da co nghiep vu that, de lai trang do la noi doi nguoi dung');

// ---------------------------------------------------------------------------
section('Chay that tren MySQL');

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

foreach (['AccAccountsModel', 'AccVoucherTypesModel', 'AccVouchersModel'] as $m){
    require_once $goc . 'app/models/' . $m . '.php';
}
$A = new AccAccountsModel(); $T = new AccVoucherTypesModel(); $V = new AccVouchersModel();

$tongId = (int) $pdo->query("SELECT id FROM garages WHERE is_master = 1 ORDER BY id LIMIT 1")->fetchColumn();
$dsKhac = $pdo->query("SELECT id, code FROM garages WHERE is_master = 0 AND status = 1 ORDER BY id LIMIT 2")
              ->fetchAll(PDO::FETCH_ASSOC);
if ($tongId === 0 || count($dsKhac) < 2){
    echo "\n[SKIP] Can gara tong + hai gara khac.\n"; exit(summary());
}
$gA = (int) $dsKhac[0]['id']; $gB = (int) $dsKhac[1]['id'];

$donSach = function() use ($pdo){
    $pdo->exec("DELETE FROM acc_vouchers WHERE voucher_no LIKE 'ZZKT%' OR ly_do LIKE 'ZZKT%'");
    $pdo->exec("DELETE FROM acc_voucher_types WHERE code LIKE 'ZZKT%'");
    /* Con truoc cha: khoa ngoai parent_id khong co ON DELETE */
    for ($i = 0; $i < 5; $i++){
        $n = $pdo->exec("DELETE FROM acc_accounts WHERE code LIKE 'ZZKT%'
                          AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM acc_accounts) c
                                           WHERE c.parent_id = acc_accounts.id)");
        if (!$n) break;
    }
};
$donSach();
register_shutdown_function($donSach);

// --- 1. Danh mục dùng chung ---
section('1. He thong tai khoan chuan dung chung moi gara');

Model::epGara(null);
$chuan = $A->getTree();
ok(count($chuan) >= 15, 'Migration gieo san he thong tai khoan chuan', 'Dang co ' . count($chuan) . ' tai khoan');
$ma = array_column($chuan, 'code');
foreach (['111', '1111', '131', '331', '334'] as $c){
    ok(in_array($c, $ma, true), "Co tai khoan $c");
}
/* Tai lieu neu vi du "1111 (111A, 111B)" — cay phai that su co cap. */
$sau = [];
foreach ($chuan as $r) $sau[$r['code']] = (int) $r['depth'];
ok(isset($sau['1111']) && $sau['1111'] === 1 && isset($sau['111']) && $sau['111'] === 0,
   'Cay co cap: 1111 nam duoi 111', json_encode($sau));

ok(count($T->getLists()) >= 5, 'Migration gieo san bo loai phieu');
ok(count($T->getLists('thu', true)) >= 1 && count($T->getLists('chi', true)) >= 1,
   'Co ca loai thu lan loai chi');

Model::epGara($gA);
ok(count($A->getTree()) === count($chuan),
   'Gara khac THAY DU he thong tai khoan chuan',
   'Loc cung theo gara thi gara moi mo thay danh muc trong rong, khong lap noi phieu nao');
ok(count($T->getLists('thu', true)) >= 1, 'Gara khac thay loai phieu dung chung');

// --- 2. Gara không sửa được danh mục chung ---
section('2. Gara KHONG sua duoc danh muc dung chung');

$tk111 = $A->findByCode('111');
ok(!empty($tk111), 'Gara A tra duoc tai khoan 111 cua danh muc chung');
$A->edit(['name' => 'ZZKT BI SUA TRAI PHEP'], (int) $tk111['id']);
ok($pdo->query("SELECT name FROM acc_accounts WHERE id = " . (int) $tk111['id'])->fetchColumn() !== 'ZZKT BI SUA TRAI PHEP',
   'Gara A SUA tai khoan dung chung -> khong an gi');

$vtPT01 = $T->findByCode('PT01');
ok(!empty($vtPT01), 'Gara A tra duoc loai phieu PT01');
$T->edit(['name' => 'ZZKT BI SUA'], (int) $vtPT01['id']);
ok($pdo->query("SELECT name FROM acc_voucher_types WHERE id = " . (int) $vtPT01['id'])->fetchColumn() !== 'ZZKT BI SUA',
   'Gara A SUA loai phieu dung chung -> khong an gi',
   'Mot gara doi dinh khoan cua PT01 la moi gara doi theo');

/* Tai khoan RIENG cua gara thi sua duoc, va gara khac khong thay. */
$idRieng = $A->add(['code' => 'ZZKT1', 'name' => 'ZZKT Tai khoan rieng cua A',
                    'parent_id' => null, 'sort_order' => 99, 'status' => 1]);
ok((int) $pdo->query("SELECT garage_id FROM acc_accounts WHERE id = $idRieng")->fetchColumn() === $gA,
   'Tai khoan gara A them -> mang garage_id cua A');
ok(!empty($A->getDetail($idRieng)), 'Gara A thay tai khoan cua chinh minh');

Model::epGara($gB);
ok(empty($A->getDetail($idRieng)), 'Gara B KHONG thay tai khoan rieng cua gara A');
ok(count($A->getTree()) === count($chuan), 'Gara B chi thay phan dung chung');

// --- 3. Cây tài khoản không thành vòng ---
section('3. Cay tai khoan khong thanh vong');

Model::epGara($gA);
$con = $A->add(['code' => 'ZZKT2', 'name' => 'ZZKT Con cua ZZKT1',
                'parent_id' => $idRieng, 'sort_order' => 99, 'status' => 1]);
$hd = $A->hauDue($idRieng);
ok(in_array($con, $hd, true), 'hauDue() tim ra tai khoan con');
ok(!in_array($idRieng, $A->hauDue($con), true), 'hauDue() cua con KHONG chua cha');

$ctl = codeOnly($goc . 'app/controllers/admin/Accaccounts.php');
ok(strpos($ctl, 'hauDue') !== false,
   'Controller chan chon chinh no / con cua no lam cha',
   'Cay thanh vong thi getTree() de quy KHONG BAO GIO DUNG — trang treo, khong bao loi');

/* Cay van duyet het duoc sau khi them hai cap — tuc khong co vong. */
$cay = $A->getTree();
$maCay = array_column($cay, 'code');
ok(in_array('ZZKT1', $maCay, true) && in_array('ZZKT2', $maCay, true),
   'Cay duyet het, khong treo o nhanh moi them');

// --- 4 + 7. Phiếu giữ bản chụp định khoản, đọc số kiểu Việt ---
section('4+7. Phieu giu ban chup dinh khoan, doc so kieu Viet');

require_once $goc . 'app/controllers/admin/AccVoucherBase.php';
$rc    = new ReflectionClass('AccVoucherBase');
$doTien = $rc->getMethod('doTien'); $doTien->setAccessible(true);
/* Lop truu tuong khong tao the hien duoc; dung mot lop con that, va
   newInstanceWithoutConstructor de khoi keo ca tang framework vao. */
require_once $goc . 'app/controllers/admin/Phieuthu.php';
$pt = (new ReflectionClass('Phieuthu'))->newInstanceWithoutConstructor();
$tien = function($s) use ($doTien, $pt){ return $doTien->invoke($pt, $s); };

ok($tien('1.500.000') === 1500000.0, '"1.500.000" -> 1500000',
   'Ep (float) thang ra 1.5 — be di mot trieu lan ma van la so hop le');
ok($tien('8,000,000') === 8000000.0, '"8,000,000" -> 8000000');
ok($tien('250000') === 250000.0, 'So tron');
ok($tien('') === 0.0 && $tien('abc') === 0.0, 'O trong / chu -> 0 (nguoi goi tu chan)');

$vt = $T->chiTietKemTk((int) $vtPT01['id']);
ok(!empty($vt['no_code']) && !empty($vt['co_code']), 'Loai phieu tra ve ca ma TK no va co');

$khach = $pdo->query("SELECT id FROM partners WHERE garage_id = $gA AND status = 1 ORDER BY id LIMIT 1")->fetchColumn();
$soPT  = $V->nextNo('thu');
$idPT  = $V->add([
    'voucher_no' => $soPT, 'loai' => 'thu', 'vtype_id' => (int) $vtPT01['id'],
    'voucher_date' => date('Y-m-d'), 'dt_loai' => 'partner',
    'partner_id' => $khach ? (int) $khach : null, 'user_id' => null,
    'dt_ten' => 'ZZKT Nguoi nop tien', 'dt_diachi' => null, 'ly_do' => 'ZZKT thu thu',
    'amount' => 1500000, 'debit_code' => $vt['no_code'], 'credit_code' => $vt['co_code'],
    'kem_theo' => '01', 'note' => null, 'status' => 1, 'created_by' => null,
]);
$p = $pdo->query("SELECT * FROM acc_vouchers WHERE id = $idPT")->fetch(PDO::FETCH_ASSOC);
ok(!empty($p) && (int) $p['garage_id'] === $gA, 'Phieu mang gara cua nguoi lap');
ok($p['debit_code'] === $vt['no_code'] && $p['credit_code'] === $vt['co_code'],
   'Phieu luu BAN CHUP cap tai khoan no / co');

/* Doi dinh khoan cua loai phieu KHONG duoc lam doi phieu da lap. */
$pdo->exec("UPDATE acc_voucher_types SET debit_id = NULL WHERE id = " . (int) $vtPT01['id']);
$sau = $pdo->query("SELECT debit_code FROM acc_vouchers WHERE id = $idPT")->fetchColumn();
ok($sau === $vt['no_code'],
   'Doi dinh khoan cua loai phieu KHONG lam doi phieu da lap',
   'In lai phieu cu phai ra dung con so tren to da ky');
$pdo->prepare("UPDATE acc_voucher_types SET debit_id = (SELECT id FROM (SELECT id FROM acc_accounts WHERE code = ? AND garage_id IS NULL LIMIT 1) x) WHERE id = ?")
    ->execute([$vt['no_code'], (int) $vtPT01['id']]);

// --- 5. Màn thu không đụng được phiếu chi ---
section('5. Man thu khong dung duoc phieu chi');

$ab = codeOnly($goc . 'app/controllers/admin/AccVoucherBase.php');
ok(preg_match("~\\\$item\['loai'\]\s*!==\s*\\\$this->loai~", $ab) === 1,
   'layPhieu() kiem ca chieu thu/chi, khong chi id',
   'Thieu no thi /admin/phieu-thu/edit/<id phieu chi> mo ra va sua duoc');

// --- 6. Chỉ huỷ, không xoá; phiếu huỷ rơi khỏi mọi phép cộng ---
section('6. Chi huy, khong xoa — va phieu huy roi khoi bao cao');

ok(!method_exists($V, 'remove') && !method_exists($V, 'deleteById2'),
   'AccVouchersModel khong co ham xoa rieng');
ok(strpos(codeOnly($goc . 'app/models/AccVouchersModel.php'), 'doiTrangThai') !== false,
   'Co doiTrangThai() de huy / bo huy');

$tu = date('Y-m-01'); $den = date('Y-m-t');
$truoc = $V->tong('thu', $tu, $den);
ok($truoc >= 1500000.0, 'Phieu hieu luc duoc tinh vao tong thu', "Tong = $truoc");

$V->doiTrangThai($idPT, 0);
$sauHuy = $V->tong('thu', $tu, $den);
ok(abs(($truoc - $sauHuy) - 1500000.0) < 0.01,
   'Huy phieu -> tong thu giam dung so tien cua no',
   "Truoc $truoc, sau $sauHuy");
ok((int) $pdo->query("SELECT COUNT(*) FROM acc_vouchers WHERE id = $idPT")->fetchColumn() === 1,
   'Phieu da huy VAN CON trong CSDL (co dau vet)');

$ds = $V->getLists('thu', ['tu' => $tu, 'den' => $den]);
$soDaHuy = 0;
foreach ($ds as $r) if ((int) $r['id'] === $idPT) $soDaHuy++;
ok($soDaHuy === 0, 'Danh sach mac dinh KHONG hien phieu da huy');
$ds = $V->getLists('thu', ['tu' => $tu, 'den' => $den, 'status' => 0]);
$co = false;
foreach ($ds as $r) if ((int) $r['id'] === $idPT) $co = true;
ok($co, 'Loc "Da huy" thi van xem lai duoc');

$V->doiTrangThai($idPT, 1);

// --- Số liệu cho báo cáo ---
section('So lieu cho bao cao (buoc sau)');

$theoKy = $V->tongTheoKy('thu', 'thang', $tu, $den);
ok(!empty($theoKy) && isset($theoKy[0]['ky'], $theoKy[0]['s']),
   'tongTheoKy() tra ve ky + tong', json_encode($theoKy));
ok(preg_match('~^\d{4}-\d{2}$~', $theoKy[0]['ky']) === 1,
   'Gom theo THANG ra dinh dang YYYY-MM', $theoKy[0]['ky']);
$theoNgay = $V->tongTheoKy('thu', 'ngay', $tu, $den);
ok(!empty($theoNgay) && preg_match('~^\d{4}-\d{2}-\d{2}$~', $theoNgay[0]['ky']) === 1,
   'Gom theo NGAY ra dinh dang YYYY-MM-DD');

if ($khach){
    $map = $V->tongTheoDoiTuong('thu');
    ok(isset($map[(int) $khach]) && $map[(int) $khach] >= 1500000.0,
       'tongTheoDoiTuong() cong dung theo tung khach (de tinh cong no)',
       json_encode($map));
}

Model::epGara(null);
$donSach();
ok((int) $pdo->query("SELECT COUNT(*) FROM acc_accounts WHERE code LIKE 'ZZKT%'")->fetchColumn() === 0
   && (int) $pdo->query("SELECT COUNT(*) FROM acc_vouchers WHERE voucher_no LIKE 'ZZKT%' OR ly_do LIKE 'ZZKT%'")->fetchColumn() === 0,
   'Da don sach du lieu test');

echo "\n";
exit(summary());
