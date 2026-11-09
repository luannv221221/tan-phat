<?php
/**
 * Test BỐN MỤC CUỐI của tài liệu khách 05/11/2026.
 *
 * Chạy:  C:\xampp\php\php.exe tests\TaiLieu0511ConLaiTest.php
 *
 *   1. TUYỂN DỤNG — danh mục bài viết + mục menu, tạo sẵn cho mỗi gara.
 *   2. CHÂN TRANG gara tự soạn (Frontend 7).
 *   3. DIỆN TÍCH XƯỞNG trong Cấu hình gara (Frontend 13).
 *   4. TẢI VỀ — link trực tiếp và link gián tiếp (Yêu cầu công nghệ 7,
 *      kèm Frontend 12 "đăng ký thành viên để download báo giá").
 *
 * BA CHỖ HỎNG ĐƯỢC MÀ KHÔNG AI NHÌN RA NGAY — file này canh đúng chúng:
 *
 *   a. TỆP TẢI VỀ PHẢI NẰM NGOÀI public/.
 *      Để trong public/assets/uploads là ai đoán ra đường dẫn cũng tải thẳng,
 *      và hai thứ "chỉ thành viên" + "đếm lượt tải" thành trang trí. Hỏng
 *      kiểu này không có lỗi nào hiện ra: mọi thứ vẫn chạy, chỉ là không còn
 *      bảo vệ gì.
 *
 *   b. CHÂN TRANG IN BẰNG echo THẲNG (không escape) nên bộ lọc HTML là chốt
 *      chặn duy nhất giữa ô soạn thảo và mọi trang của gara.
 *
 *   c. LAYOUT STOREFRONT KHÔNG QUA BỘ BIÊN DỊCH VIEW. Viết {!! !!} trong đó
 *      thì nó in ra nguyên văn dấu ngoặc — đã dính một lần khi làm mục này.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

use App\core\Model;
use App\core\Load;

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

// ===========================================================================
section('Migration 000105');

$mg = glob($goc . 'database/migrations/*_000105_*.php');
ok(count($mg) === 1, 'Co dung mot migration 000105', 'Tim thay ' . count($mg));
$sql = count($mg) === 1 ? file_get_contents($mg[0]) : '';

ok(strpos($sql, '`downloads`') !== false, 'Migration tao bang downloads');
ok(strpos($sql, 'storage/downloads/') !== false,
   'Ghi chu neu ro tep nam trong storage/, khong phai public/');
ok(strpos($sql, "'modules'") !== false || strpos($sql, '`modules`') !== false,
   'Migration dang ky man Tai ve vao bang modules',
   'Thieu thi man hinh khong len menu va route() tra false — khong ai vao duoc');
ok(strpos($sql, "'galleries'") !== false,
   'Quyen cap theo dung nhom nao dang co galleries',
   'Khong cap thi sau khi trien khai phai tich tay cho tung nhom cua tung gara');
ok(strpos($sql, 'tuyen-dung') !== false, 'Migration tao danh muc Tuyen dung');
ok(preg_match('~status[^,]*,\s*NOW\(\)|0,\s*NOW\(\)~', $sql) === 1
   || strpos($sql, "'_self', 90, 0, NOW()") !== false,
   'Muc menu Tuyen dung tao ra o trang thai TAT',
   'Bat san thi website moi gara moc them mot muc dan vao trang trang');

// ===========================================================================
section('MUC 4 — tep tai ve KHONG duoc nam trong public/');

$codeHelper = codeOnly($goc . 'app/helpers/functions.php');
ok(strpos($codeHelper, 'function upload_tai_lieu') !== false, 'Co ham upload_tai_lieu()');

/* Lay rieng than ham de khang dinh ve CHINH no, khong dinh sang hai ham
   upload khac (upload_image / upload_chung_tu deu ghi vao public/). */
$vi   = strpos($codeHelper, 'function upload_tai_lieu');
$than = $vi !== false ? substr($codeHelper, $vi, 2600) : '';

ok(strpos($than, 'storage/downloads/') !== false,
   'upload_tai_lieu() ghi vao storage/downloads/');
ok(strpos($than, 'public/assets/uploads') === false,
   'upload_tai_lieu() KHONG ghi vao public/',
   'Trong public/ thi web server phuc vu thang, "chi thanh vien" va "dem luot" '
   . 'deu vo nghia — ma khong co loi nao hien ra');
ok(strpos($than, 'move_uploaded_file') !== false, 'Dung move_uploaded_file()');
ok(strpos($than, 'random_bytes') !== false,
   'Ten tep tren dia do minh dat lai',
   'Dung lai ten nguoi dung gui len la nhan ca "..\\..\\web.config"');
ok(strpos($than, 'basename') !== false,
   'Ten goc bi cat bo thanh phan duong dan truoc khi cat giu');

/* storage/ phai bi chan khoi web — day la MONG cua ca thiet ke tren. */
ok(is_file($goc . 'storage/.htaccess')
   && strpos(file_get_contents($goc . 'storage/.htaccess'), 'denied') !== false,
   'storage/ bi .htaccess chan khoi web',
   'Mat file nay la moi tep tai ve lo ra ngoai, bo qua het moi lop kiem');

// ===========================================================================
section('MUC 4 — hai loai link khac nhau o dung cho nao');

$cTaive = codeOnly($goc . 'app/controllers/Taive.php');

ok(strpos($cTaive, 'hash_equals') !== false,
   'So token bang hash_equals, khong phai ===',
   '=== tra loi nhanh/cham tuy cho khac nhau — do duoc qua mang va do dan ra token');
ok(preg_match('~require_login.*?dataMember~s', $cTaive) === 1,
   'Link GIAN TIEP co kiem dang nhap');
ok(preg_match('~laTrucTiep.*?themLuot~s', $cTaive) === 1
   || strpos($cTaive, '!$laTrucTiep) $this->__model->themLuot') !== false,
   'Chi link GIAN TIEP moi tinh vao luot tai');
ok(strpos($cTaive, 'ob_end_clean') !== false,
   'Don sach bo dem truoc khi day tep',
   'Mot dong trang lot ra truoc header la tep tai ve hong, ma chi biet khi mo tep');
ok(preg_match('~Content-Disposition~', $cTaive) === 1
   && strpos($cTaive, "filename*=UTF-8''") !== false,
   'Ten tep tieng Viet tra ve dung (filename* theo RFC 5987)');

$cModel = codeOnly($goc . 'app/models/DownloadsModel.php');
ok(strpos($cModel, '_theoGara = true') !== false,
   'DownloadsModel loc theo gara',
   'Thieu la gara nay tai duoc tep cua gara kia');
ok(preg_match('~themLuot.*?dkGara~s', $cModel) === 1,
   'Cong luot tai cung phai kem dieu kien gara');
ok(strpos($cModel, 'random_bytes') !== false, 'Token sinh bang random_bytes');
ok(preg_match('~duongDanDia.*?storage/downloads/~s', $cModel) === 1
   && preg_match("~duongDanDia.*?'\.\.'~s", $cModel) === 1,
   'duongDanDia() chan duong dan di nguoc (../) va chi nhan dung thu muc da dinh');

// ===========================================================================
section('MUC 2 — chan trang gara tu soan');

$cSettings = codeOnly($goc . 'app/controllers/admin/Settings.php');
ok(strpos($cSettings, "'footer_html'") !== false, 'Khoa footer_html co trong danh sach cho phep');
ok(strpos($cSettings, "'dien_tich_xuong'") !== false, 'Khoa dien_tich_xuong co trong danh sach cho phep');
ok(preg_match("~footer_html.*?loc_html_an_toan~s", $cSettings) === 1,
   'Chan trang di qua loc_html_an_toan() TRUOC khi ghi',
   'Day la chot chan duy nhat: noi dung nay in ra moi trang cua gara, khong escape');

$master = file_get_contents($goc . 'app/views/layouts/storefront/master.php');
ok(strpos($master, 'footer__tu-soan') !== false, 'Chan trang co khoi gara tu soan');
ok(strpos($master, '{!! $chanTuSoan !!}') === false,
   'KHONG dung {!! !!} trong layout storefront',
   'File nay khong qua bo bien dich view — viet {!! !!} thi in ra nguyen van dau ngoac. '
   . 'Da dinh mot lan khi lam muc nay.');
ok(preg_match('~chanTuSoan.*?loc_html_an_toan~s', $master) === 1,
   'Loc lai mot lan nua luc in — du lieu cu co the ghi truoc khi co bo loc');

$formSettings = file_get_contents($goc . 'app/views/admin/settings/form.php');
ok(strpos($formSettings, 'name="footer_html"') !== false, 'Man Cau hinh co o chan trang');
ok(strpos($formSettings, 'name="dien_tich_xuong"') !== false, 'Man Cau hinh co o dien tich xuong');
ok(strpos($formSettings, 'soan-thao.js') !== false, 'O chan trang gan trinh soan thao');

// ===========================================================================
section('Chay that tren MySQL');

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

// --- Tuyển dụng: mỗi gara một danh mục, menu tạo ra ở trạng thái tắt -------
$soGara = (int) $pdo->query('SELECT COUNT(*) FROM `garages`')->fetchColumn();
$soDm   = (int) $pdo->query("SELECT COUNT(*) FROM `news_categories` WHERE `name` = 'Tuyển dụng'")->fetchColumn();
ok($soDm === $soGara, 'Moi gara co mot danh muc Tuyen dung',
   $soGara . ' gara nhung ' . $soDm . ' danh muc');

$menuBat = (int) $pdo->query(
    "SELECT COUNT(*) FROM `menus` WHERE `label` = 'Tuyển dụng' AND `status` = 1")->fetchColumn();
ok($menuBat === 0, 'Muc menu Tuyen dung van dang TAT',
   'Bat san thi website moi gara co mot muc dan vao trang trang — trang trang '
   . 'van tra HTTP 200 nen khong log nao bao');

$slugTrung = (int) $pdo->query(
    "SELECT COUNT(*) - COUNT(DISTINCT `slug`) FROM `news_categories`")->fetchColumn();
ok($slugTrung === 0, 'Khong co slug danh muc nao bi trung',
   'Chi muc uq_news_cat_slug la DUY NHAT TOAN HE THONG, khong theo tung gara');

// --- Module + quyền --------------------------------------------------------
$mid = (int) $pdo->query("SELECT `id` FROM `modules` WHERE `link` = 'downloads'")->fetchColumn();
ok($mid > 0, 'Man Tai ve da dang ky trong bang modules');

$qDl = (int) $pdo->query("SELECT COUNT(*) FROM `permissions` WHERE `module_id` = " . $mid)->fetchColumn();
$qGl = (int) $pdo->query(
    "SELECT COUNT(*) FROM `permissions` p JOIN `modules` m ON m.`id` = p.`module_id`
      WHERE m.`link` = 'galleries'")->fetchColumn();
ok($qDl > 0 && $qDl === $qGl,
   'Quyen Tai ve cap dung bang quyen Thu vien (' . $qDl . ' dong)',
   'downloads=' . $qDl . ' nhung galleries=' . $qGl);

// --- Hai loại link, chạy thật ---------------------------------------------
$gara = (int) $pdo->query('SELECT `id` FROM `garages` WHERE `status` = 1 ORDER BY `id` LIMIT 1')->fetchColumn();
if ($gara <= 0){ echo "  [SKIP] Khong co gara nao.\n"; exit(summary()); }

$DAU  = 'zzt0911-';
$tep  = __DIR__ . '/../storage/downloads/' . $DAU . 'thu.txt';
@mkdir(dirname($tep), 0755, true);
file_put_contents($tep, 'noi dung thu');

$kq = Model::trongGara($gara, function() use ($DAU){
    $DL = Load::model('DownloadsModel');
    $id = (int) $DL->add([
        'title'         => 'ZZTEST tai lieu',
        'slug'          => $DAU . 'tep',
        'file_path'     => 'storage/downloads/' . $DAU . 'thu.txt',
        'file_name'     => 'thu.txt',
        'file_size'     => 12,
        'mime'          => 'text/plain',
        'token'         => DownloadsModel::sinhToken(),
        'require_login' => 1,
        'is_published'  => 1,
    ]);
    $dong = $DL->theoSlug($DAU . 'tep');
    $DL->themLuot($id);
    $sau  = $DL->theoSlug($DAU . 'tep');
    return ['id' => $id, 'dong' => $dong, 'sau' => $sau];
});

ok(!empty($kq['dong']), 'Tim duoc tep theo slug');
ok(!empty($kq['dong']) && strlen((string) $kq['dong']['token']) === 32,
   'Token dai dung 32 ky tu (khop VARCHAR(32))');
ok(!empty($kq['sau']) && (int) $kq['sau']['hits'] === 1, 'themLuot() cong dung mot luot');

/* Gara KHÁC không được thấy, và không được cộng lượt cho tệp không phải của mình */
$garaKhac = (int) $pdo->query(
    'SELECT `id` FROM `garages` WHERE `id` <> ' . $gara . ' ORDER BY `id` LIMIT 1')->fetchColumn();
if ($garaKhac > 0){
    $lo = Model::trongGara($garaKhac, function() use ($DAU, $kq){
        $DL = Load::model('DownloadsModel');
        $DL->themLuot($kq['id']);
        return $DL->theoSlug($DAU . 'tep');
    });
    ok(empty($lo), 'Gara khac KHONG tim thay tep cua gara nay');

    $hits = (int) $pdo->query('SELECT `hits` FROM `downloads` WHERE `id` = ' . (int) $kq['id'])->fetchColumn();
    ok($hits === 1, 'Gara khac KHONG cong duoc luot cho tep nay', 'hits = ' . $hits);
}

/* duongDanDia(): chỉ nhận đúng thư mục, chặn đường đi ngược */
ok(DownloadsModel::duongDanDia('storage/downloads/a.txt') !== '', 'Nhan duong dan hop le');
ok(DownloadsModel::duongDanDia('public/assets/uploads/a.txt') === '',
   'Tu choi duong dan ngoai storage/downloads/');
ok(DownloadsModel::duongDanDia('storage/downloads/../../.env') === '',
   'Chan duong dan di nguoc len (../)',
   'Thieu la doc duoc .env qua duong tai ve');
ok(DownloadsModel::duongDanDia('') === '', 'Duong dan rong tra ve rong');

// --- Dọn sạch --------------------------------------------------------------
$pdo->exec('DELETE FROM `downloads` WHERE `slug` LIKE ' . $pdo->quote($DAU . '%'));
@unlink($tep);
$con = (int) $pdo->query('SELECT COUNT(*) FROM `downloads` WHERE `slug` LIKE '
       . $pdo->quote($DAU . '%'))->fetchColumn();
ok($con === 0 && !is_file($tep), 'Da don sach du lieu thu');

exit(summary());
