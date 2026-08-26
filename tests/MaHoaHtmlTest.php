<?php
/**
 * Test MÃ HOÁ HTML — lưu nguyên văn, escape lúc in.
 *
 * Chạy:  C:\xampp\php\php.exe tests\MaHoaHtmlTest.php
 *
 * BỐI CẢNH
 * core/Request.php trước đây chạy FILTER_SANITIZE_SPECIAL_CHARS lên MỌI ô của
 * mọi biểu mẫu, tức là mã hoá HTML ngay lúc LƯU. View in ra lại escape thêm
 * lần nữa nên `&` hiện lên trang thành `&#38;`, và mỗi lần bấm Lưu lại chồng
 * thêm một lớp:
 *
 *     Phụ tùng & thiết bị  ->  &#38;  ->  &#38;#38;  ->  &#38;#38;#38; ...
 *
 * Nó cũng phá nát trình soạn thảo có định dạng: `<p>` bị lưu thành `&#60;p&#62;`
 * và bài tin hiện ra nguyên đống thẻ dạng chữ.
 *
 * Kiến trúc đúng: LƯU nguyên văn, ESCAPE LÚC IN.
 *
 * ĐÁNH ĐỔI phải nói rõ: bỏ bộ lọc đầu vào nghĩa là ba ô nội dung có định dạng
 * (Giới thiệu / Tin tức / Dự án) lưu HTML thật và in thẳng ra. Đó CHÍNH LÀ điều
 * mong muốn của một trình soạn thảo — không có nó thì bài viết mãi mãi hỏng.
 * Đổi lại, người có quyền đăng tin nhét được HTML tuỳ ý. Đây là đánh đổi bình
 * thường của mọi CMS. Phần dưới chốt danh sách ba chỗ đó lại: thêm chỗ thứ tư
 * là test đỏ, để việc mở thêm một lối in thô luôn phải có người xem lại.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/helpers/functions.php';

echo 'PHP ' . PHP_VERSION . "\n";

$goc = __DIR__ . '/../';

/** Gọi getFields() với một mảng POST giả */
function guiLen(array $post){
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = $post;
    return (new App\core\Request())->getFields();
}

// ---------------------------------------------------------------------------
section('Luu nguyen van, KHONG ma hoa');

$r = guiLen([
    'slogan'  => 'Phụ tùng & thiết bị gara ô tô',
    'kep'     => 'Ống "cao áp" & gioăng',
    'the'     => '<p>Đoạn <b>in đậm</b></p>',
    'nhon'    => "Nam's shop <b>",
]);

ok($r['slogan'] === 'Phụ tùng & thiết bị gara ô tô',
   'Dau & giu nguyen, khong thanh &#38;',
   var_export($r['slogan'], true));
ok($r['kep'] === 'Ống "cao áp" & gioăng',
   'Dau nhay kep giu nguyen, khong thanh &#34;',
   var_export($r['kep'], true));
ok($r['the'] === '<p>Đoạn <b>in đậm</b></p>',
   'The HTML giu nguyen (trinh soan thao co dinh dang phai chay duoc)',
   var_export($r['the'], true));
ok($r['nhon'] === "Nam's shop <b>",
   'Dau nhay don va dau < giu nguyen');

// ---------------------------------------------------------------------------
section('Luu di luu lai KHONG chong lop');

// Đây mới là triệu chứng người dùng nhìn thấy: mỗi lần bấm Lưu lại dày thêm.
$v = 'A & B';
for ($i = 0; $i < 5; $i++){ $v = guiLen(['x' => $v])['x']; }
ok($v === 'A & B', 'Luu 5 lan lien tiep van la "A & B"', var_export($v, true));

// ---------------------------------------------------------------------------
section('Van lam sach nhung thu can lam sach');

$r = guiLen([
    'module'  => 'admin/settings',
    'khoang'  => '   hai dau   ',
    'nul'     => "co\0ky tu NUL",
    'mang'    => ['a & b', '  c  '],
]);
ok(!array_key_exists('module', $r),
   'Bo tham so `module` (dinh tuyen, khong phai du lieu)');
ok($r['khoang'] === 'hai dau', 'Cat khoang trang hai dau');
ok(strpos($r['nul'], "\0") === false, 'Bo ky tu NUL',
   'NUL lot vao la cat cut chuoi o tang C');
ok($r['mang'] === ['a & b', 'c'], 'Mang cung duoc loc tung phan tu');

// ---------------------------------------------------------------------------
section('Khong con bo loc cu trong ma nguon');

$req = codeOnly($goc . 'core/Request.php');
ok(strpos($req, 'FILTER_SANITIZE_SPECIAL_CHARS') === false,
   'core/Request.php khong con FILTER_SANITIZE_SPECIAL_CHARS',
   'Bat lai la moi lan Luu se chong them mot lop ma hoa');
ok(strpos($req, 'FILTER_SANITIZE_FULL_SPECIAL_CHARS') === false,
   'Va cung khong dung ban FULL_SPECIAL_CHARS');

// ---------------------------------------------------------------------------
section('Bu lai: escape luc IN van con nguyen');

// Bỏ escape đầu vào chỉ an toàn khi đầu ra còn escape. Hai chốt này là chỗ dựa.
$tpl = file_get_contents($goc . 'core/Template.php');
ok(strpos($tpl, 'htmlentities(') !== false,
   'Template compile {{ }} thanh htmlentities()');

ok(e('<b>x</b> & "y"') === '&lt;b&gt;x&lt;/b&gt; &amp; &quot;y&quot;',
   'Helper e() escape ca the, dau & va dau nhay',
   e('<b>x</b> & "y"'));

// ---------------------------------------------------------------------------
section('Cac loi in THO duoc chot danh sach');

// Chỉ ba ô nội dung có định dạng mới được in thô. Thêm chỗ thứ tư là test đỏ.
$choChoPhep = [
    'app/views/storefront/about.php'          => '$about',
    'app/views/storefront/news_detail.php'    => "\$news['content']",
    'app/views/storefront/project_detail.php' => "\$project['content']",
];

$thoLa = [];
foreach (glob($goc . 'app/views/storefront/*.php') as $f){
    $s = file_get_contents($f);
    if (!preg_match_all('~\{!!\s*(.+?)\s*!!\}~s', $s, $m)) continue;
    foreach ($m[1] as $bt){
        $bt = trim(preg_replace('/\s+/', ' ', $bt));
        // bọc e() hoặc biến do view tự dựng (đã escape bên trong) thì bỏ qua
        if (strpos($bt, 'e(') !== false) continue;
        if (preg_match('~^\$(err|pcard|thanhLoc)\b~', $bt)) continue;

        $ten = 'app/views/storefront/' . basename($f);
        if (isset($choChoPhep[$ten]) && $choChoPhep[$ten] === $bt) continue;
        $thoLa[] = $ten . ' -> ' . mb_substr($bt, 0, 40);
    }
}
ok(empty($thoLa),
   'Khong co loi in tho nao ngoai 3 o noi dung co dinh dang',
   implode(' | ', $thoLa));

foreach ($choChoPhep as $f => $bt){
    ok(strpos(file_get_contents($goc . $f), '{!! ' . $bt . ' !!}') !== false,
       'Van con o noi dung co dinh dang: ' . basename($f),
       'Neu doi sang {{ }} thi bai viet hien ra nguyen dong the dang chu');
}

// ---------------------------------------------------------------------------
section('Migration va du lieu da hong');

$mg = glob($goc . 'database/migrations/*_go_ma_hoa_html_bi_chong_lop.php');
ok(!empty($mg), 'Co migration go ma hoa chong lop');

if (!empty($mg)){
    $src = file_get_contents($mg[0]);
    ok(strpos($src, 'html_entity_decode') !== false,
       'Migration dung html_entity_decode');
    ok(preg_match('~while\s*\(\s*\$vong\+\+\s*<\s*\d+~', $src) === 1,
       'Giai ma LAP co chan vong',
       'Khong biet truoc bi chong may lop, nhung cung khong duoc lap vo tan');
    ok(strpos($src, "REGEXP '&#[0-9]+;'") !== false,
       'Chi dung vao dong that su co thuc the so',
       'Chuoi binh thuong co dau & (vd "R&D") khong duoc dung toi');
    ok(strpos($src, 'SHOW COLUMNS FROM `$bang` LIKE ?') === false,
       'KHONG dat tham so vao SHOW COLUMNS',
       'MySQL nem loi cu phap 1064 o cho do; ban dau viet vay roi boc try/catch '
       . 'nen NUOT loi, chay xong bao OK ma sua duoc 0 o');
}

// Kiểm dữ liệu thật (bỏ qua nếu không có MySQL)
try {
    $pdo = new PDO(
        'mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
        _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL — bo qua phan kiem du lieu that.\n";
    exit(summary());
}

$con = 0;
foreach (['site_settings' => ['svalue'], 'news' => ['title', 'content'],
          'galleries' => ['name', 'description'], 'parts' => ['name'],
          'part_categories' => ['name']] as $b => $cs){
    foreach ($cs as $c){
        try { $con += (int) $pdo->query("SELECT COUNT(*) FROM `$b` WHERE `$c` REGEXP '&#[0-9]+;'")->fetchColumn(); }
        catch (\Throwable $e){ /* bảng/cột không có thì thôi */ }
    }
}
ok($con === 0, 'Khong con o du lieu nao bi ma hoa chong lop', "con $con o");

exit(summary());
