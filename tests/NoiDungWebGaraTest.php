<?php
/**
 * Test NỘI DUNG WEBSITE THEO TỪNG GARA — bước 3 của nền tảng nhiều gara.
 *
 * Chạy:  C:\xampp\php\php.exe tests\NoiDungWebGaraTest.php
 *
 * Bước 1 cho mỗi gara một tên miền, bước 2 cho mỗi gara một bộ nhận diện.
 * Nhưng NỘI DUNG vẫn dùng chung: tin tức, banner, menu, thư viện ảnh, dự án —
 * gara nào mở web cũng thấy y hệt nhau, và gara này sửa một bài là trang của
 * gara kia đổi theo.
 *
 * CHỖ HỎNG ĐÃ MẮC THẬT khi làm bước này — và là lý do có test này:
 *
 *   Bật `$_theoGara = true` cho NewsModel rồi tưởng là xong. Nhưng cờ đó chỉ
 *   tự động áp cho các hàm CÓ SẴN của lớp Model (getFirst, updateById,
 *   addNew...). Năm truy vấn tự viết trong NewsModel vẫn dùng
 *   `$this->table($this->_table)` — không qua bộ lọc.
 *
 *   Kết quả: bài viết của gara Sài Gòn hiện trên trang Tân Phát. Không có lỗi
 *   nào báo, và chốt chặn cũ vẫn PASS vì nó chỉ kiểm CỜ CÓ HAY KHÔNG.
 *
 *   Nay CachLyGaraTest bắt luôn cả chuyện đó, còn test này chứng minh bằng
 *   HTTP thật: bài của gara này KHÔNG lọt sang trang gara kia.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc  = __DIR__ . '/../';
$base = 'http://localhost:88/tan-phat';

/** bảng => model giữ nó */
$BANG = [
    'news'            => 'NewsModel.php',
    'news_categories' => 'NewsCategoriesModel.php',
    'banners'         => 'BannersModel.php',
    'menus'           => 'MenusModel.php',
    'galleries'       => 'GalleriesModel.php',
    'site_projects'   => 'ProjectsModel.php',
];

// ---------------------------------------------------------------------------
section('Model noi dung deu loc theo gara');

foreach ($BANG as $bang => $file){
    $src = codeOnly($goc . 'app/models/' . $file);
    ok(preg_match('~\$_theoGara\s*=\s*true~', $src) === 1, "$file bat _theoGara");
    ok(substr_count($src, '$this->table($this->_table)') === 0,
       "$file khong con truy van vong qua bo loc",
       'Bat co ma van dung table($this->_table) thi du lieu van ro');
}

// ---------------------------------------------------------------------------
try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

$donSach = function() use ($pdo){
    $pdo->exec("DELETE FROM news WHERE slug LIKE 'zznd-%'");
    $pdo->exec("DELETE FROM banners WHERE title LIKE 'ZZND %'");
};
$donSach();

// ---------------------------------------------------------------------------
section('CSDL da tach noi dung theo gara');

foreach (array_keys($BANG) as $bang){
    $cot = $pdo->query("SHOW COLUMNS FROM `$bang`")->fetchAll(PDO::FETCH_COLUMN);
    ok(in_array('garage_id', $cot, true), "`$bang` co cot garage_id");
    $trong = (int) $pdo->query("SELECT COUNT(*) FROM `$bang` WHERE garage_id IS NULL")->fetchColumn();
    ok($trong === 0, "`$bang` khong con dong nao thieu gara", "con $trong dong");
}

/* `gallery_items` CỐ Ý không có garage_id: nó luôn thuộc một `galleries`, mà
   bảng cha đã mang gara. Thêm cột ở bảng con là hai nguồn sự thật cho cùng một
   việc, tới lúc lệch nhau không biết tin cái nào. */
$cotCon = $pdo->query("SHOW COLUMNS FROM `gallery_items`")->fetchAll(PDO::FETCH_COLUMN);
ok(!in_array('garage_id', $cotCon, true),
   '`gallery_items` KHONG co garage_id (theo bang cha `galleries`)');

// ---------------------------------------------------------------------------
section('Sau man noi dung da mo cho moi gara');

foreach (['news', 'news-categories', 'banners', 'menus', 'galleries', 'du-an'] as $link){
    $co = $pdo->query("SELECT chi_tan_phat FROM modules WHERE link = " . $pdo->quote($link))->fetchColumn();
    ok((int) $co === 0, "Man `$link` mo cho moi gara",
       'Con co chi_tan_phat thi gara khac mo ra bi da ve "khong co quyen"');
}

/* Những màn này thì KHÔNG mở: dữ liệu dùng chung toàn hệ thống.
 *
 * 08/10/2026 — `products`, `services`, `part-categories` và `groups` ĐÃ RA KHỎI
 * danh sách này (migration 000093 + 000094). Chúng ra được vì dữ liệu đã chia
 * theo gara, KHÔNG phải vì đổi ý về việc dùng chung:
 *   `parts` + 6 bảng danh mục -> garage_id kiểu chung-và-riêng (NULL = kho
 *       tổng, mọi gara đọc được, chỉ gara tổng sửa)
 *   `groups`                  -> garage_id, mỗi gara một bộ nhóm riêng
 * Danh sách còn lại là những bảng THẬT SỰ chưa chia được: danh mục xe (Toyota
 * Camry 2020 là một với mọi gara) và `garages` (khai gara là việc của người
 * vận hành nền tảng). Chốt đủ nằm ở CachLyGaraTest ($mongDoi). */
foreach (['car-brands', 'car-models', 'car-years', 'garages', 'modules'] as $link){
    $co = $pdo->query("SELECT chi_tan_phat FROM modules WHERE link = " . $pdo->quote($link))->fetchColumn();
    ok((int) $co === 1, "Man `$link` VAN chi cua kho tong",
       'Mo ra la gara sua duoc du lieu dung chung cua moi gara');
}

// ---------------------------------------------------------------------------
section('Chay that: bai cua gara nay KHONG lot sang trang gara kia');

if (!function_exists('curl_init')){ echo "  [SKIP] PHP khong co curl.\n"; $donSach(); exit(summary()); }

$http = function($url, $host){
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25,
                            CURLOPT_HTTPHEADER => ['Host: ' . $host]]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $body === false ? '' : $body];
};

$gTong = (int) $pdo->query("SELECT id FROM garages WHERE is_master = 1")->fetchColumn();
$gPhu  = (int) $pdo->query("SELECT id FROM garages WHERE is_master = 0 AND status = 1 ORDER BY id LIMIT 1")->fetchColumn();
$hostTong = $pdo->query("SELECT host FROM garage_domains WHERE garage_id = $gTong AND is_primary = 1 LIMIT 1")->fetchColumn();
$hostPhu  = $pdo->query("SELECT host FROM garage_domains WHERE garage_id = $gPhu  AND is_primary = 1 LIMIT 1")->fetchColumn();

$r = $http("$base/", $hostTong);
if ($r['code'] === 0){ echo "  [SKIP] Apache khong chay (localhost:88).\n"; $donSach(); exit(summary()); }

$tieuDe = 'ZZND Bai rieng cua gara phu';
$pdo->prepare("INSERT INTO news (category_id, title, slug, summary, content, is_published, published_at, garage_id, create_at)
               VALUES (NULL, ?, 'zznd-bai-gara-phu', 'tom tat', 'noi dung', 1, NOW(), ?, NOW())")
    ->execute([$tieuDe, $gPhu]);

$r = $http("$base/tin-tuc", $hostPhu);
ok($r['code'] === 200 && strpos($r['body'], $tieuDe) !== false,
   'Trang tin cua gara phu CO bai cua chinh no',
   'Host ' . $hostPhu . ' — HTTP ' . $r['code']);

$r = $http("$base/tin-tuc", $hostTong);
ok($r['code'] === 200 && strpos($r['body'], $tieuDe) === false,
   'Trang tin cua gara TONG khong co bai cua gara phu',
   'Day la loi da mac that: bat $_theoGara nhung truy van tu viet khong qua bo loc');

/* Banner cũng vậy — nó nằm ngay đầu trang chủ, lọt là thấy liền. */
$pdo->prepare("INSERT INTO banners (title, image, link, sort_order, status, garage_id, create_at)
               VALUES ('ZZND Banner gara phu', 'zznd.jpg', '#', 0, 1, ?, NOW())")
    ->execute([$gPhu]);

$r = $http("$base/", $hostTong);
ok($r['code'] === 200 && strpos($r['body'], 'zznd.jpg') === false,
   'Banner cua gara phu khong hien tren trang chu gara tong');

// ---------------------------------------------------------------------------
section('Hang hoa tren web: moi gara mot gian hang');

/* Truoc 07/10/2026 storefront khoa cung `parts.garage_id IS NULL`: web chi ban
   hang kho tong, va HANG RIENG CUA GARA KHONG BAO GIO LEN WEB.

   Nay:
     gara TONG  -> toan bo danh muc kho tong (giu nguyen, trang dang chay that
                   khong doi mat hang nao);
     gara KHAC  -> hang rieng cua no + hang kho tong no DA CHON LAM, dung bang
                   danh sach o man Danh muc cua gara. Web va bao gia phai noi
                   cung mot thu. */
$src = codeOnly($goc . 'app/models/PartsModel.php');
ok(preg_match('~private function chiHangLenWeb~', $src) === 1,
   'Van chot o MOT cho duy nhat (chiHangLenWeb)',
   'Bay noi truy van storefront deu di qua ham nay');
ok(strpos($src, 'garage_part_prices') !== false,
   'chiHangLenWeb co tinh den hang kho tong gara da chon lam');

$demWeb = function($garaId, $laTong) use ($pdo){
    if ($laTong){
        return (int) $pdo->query("SELECT COUNT(*) FROM parts
                                  WHERE status = 1 AND show_on_web = 1 AND garage_id IS NULL")->fetchColumn();
    }
    return (int) $pdo->query("SELECT COUNT(*) FROM parts p
                              LEFT JOIN garage_part_prices gpp
                                     ON gpp.part_id = p.id AND gpp.garage_id = $garaId AND gpp.status = 1
                              WHERE p.status = 1 AND p.show_on_web = 1
                                AND (p.garage_id = $garaId OR (p.garage_id IS NULL AND gpp.id IS NOT NULL))")->fetchColumn();
};

require_once $goc . 'app/models/PartsModel.php';

\App\core\Model::epGara($gTong);
$soTong = count((array) (new PartsModel())->storefront([], 500));
ok($soTong === $demWeb($gTong, true),
   'Gian hang gara tong = toan bo danh muc kho tong',
   'model tra ' . $soTong . ' / mong doi ' . $demWeb($gTong, true));

\App\core\Model::epGara($gPhu);
$soPhu = count((array) (new PartsModel())->storefront([], 500));
ok($soPhu === $demWeb($gPhu, false),
   'Gian hang gara phu = hang rieng + hang kho tong da chon',
   'model tra ' . $soPhu . ' / mong doi ' . $demWeb($gPhu, false));
ok($soPhu > 0 && $soPhu < $soTong,
   'Hai gian hang KHAC nhau',
   'gara tong ' . $soTong . ' mon, gara phu ' . $soPhu . ' mon');
\App\core\Model::epGara(null);

$donSach();
ok((int) $pdo->query("SELECT COUNT(*) FROM news WHERE slug LIKE 'zznd-%'")->fetchColumn() === 0
   && (int) $pdo->query("SELECT COUNT(*) FROM banners WHERE title LIKE 'ZZND %'")->fetchColumn() === 0,
   'Da don sach du lieu test');

exit(summary());
