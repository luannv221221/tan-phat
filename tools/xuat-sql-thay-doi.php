<?php
/**
 * XUẤT RA SQL cho ba thay đổi CSDL gần đây — dành cho người không muốn chạy
 * `php migrate.php` mà thích dán thẳng vào phpMyAdmin.
 *
 * Chạy:  C:\xampp\php\php.exe tools\xuat-sql-thay-doi.php > deploy\thay-doi-csdl.sql
 *
 * Tương đương ba migration:
 *   000059  gỡ mã hoá HTML bị chồng lớp  (lỗi &#38;#38;)
 *   000060  gán ảnh minh hoạ vào CSDL
 *   000061  đăng ký module "Quản lý module"
 *
 * KHÔNG có lệnh CREATE/ALTER/DROP nào — cả ba chỉ sửa và thêm DỮ LIỆU.
 *
 * Câu lệnh sinh ra đều CHẠY LẠI ĐƯỢC NHIỀU LẦN:
 *   - UPDATE có mệnh đề WHERE đủ hẹp
 *   - INSERT bọc trong `INSERT ... SELECT ... WHERE NOT EXISTS`
 * Chạy hai lần không sinh dòng trùng, không hỏng dữ liệu đang có.
 *
 * LƯU Ý: file này đọc trạng thái HIỆN TẠI của CSDL trên máy đang chạy rồi mới
 * sinh SQL. Nghĩa là nó chép lại kết quả, không phải chép lại quá trình — muốn
 * đúng thì chạy nó trên máy đã migrate xong.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

$db = new PDO(
    'mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
    _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

/** Bọc chuỗi cho an toàn khi nhúng vào SQL */
function q($v){
    global $db;
    return $v === null ? 'NULL' : $db->quote($v);
}

$now = date('Y-m-d H:i:s');

echo "-- =====================================================================\n";
echo "-- TÂN PHÁT — thay đổi CSDL, tương đương migration 000059 / 000060 / 000061\n";
echo "-- Sinh tự động lúc $now bằng tools/xuat-sql-thay-doi.php\n";
echo "--\n";
echo "-- KHÔNG có CREATE/ALTER/DROP — chỉ sửa và thêm dữ liệu.\n";
echo "-- Chạy lại nhiều lần không sinh dòng trùng.\n";
echo "--\n";
echo "-- Cách dùng: phpMyAdmin > chọn CSDL > tab SQL > dán toàn bộ > Thực hiện.\n";
echo "-- =====================================================================\n\n";
echo "SET NAMES utf8mb4;\n\n";

/* ------------------------------------------------------------------ *
 * 000059 — gỡ mã hoá HTML bị chồng lớp
 * ------------------------------------------------------------------ */
echo "-- ---------------------------------------------------------------------\n";
echo "-- 000059 — Gỡ lỗi \"&#38;#38;\"\n";
echo "--\n";
echo "-- Nguyên nhân: bộ lọc đầu vào mã hoá HTML ngay lúc LƯU, mà view in ra\n";
echo "-- lại escape thêm lần nữa. Mỗi lần bấm Lưu chồng thêm một lớp.\n";
echo "-- Bên dưới là giá trị ĐÃ GỠ SẠCH, chép từ máy đã sửa xong.\n";
echo "-- ---------------------------------------------------------------------\n";

$cot = [
    'site_settings'   => ['khoa' => 'skey', 'cot' => ['svalue']],
    'news'            => ['khoa' => 'id',   'cot' => ['title', 'description', 'content']],
    'galleries'       => ['khoa' => 'id',   'cot' => ['name', 'description']],
    'part_categories' => ['khoa' => 'id',   'cot' => ['name', 'description']],
];

$demSua = 0;
foreach ($cot as $bang => $ct){
    foreach ($ct['cot'] as $c){
        try {
            /* Những dòng TỪNG bị hỏng giờ đã sạch nên không REGEXP '&#\d+;' ra
               được nữa. Phải lọc theo DẤU VẾT còn lại:

                 %&%   ô có dấu &      (vd khẩu hiệu "Phụ tùng & thiết bị")
                 %<%   ô có thẻ HTML   (vd bài tin, `<p>` từng bị hoá &#60;p&#62;)

               Lọc thiếu vế `<` là bỏ sót nội dung bài viết — đúng chỗ hỏng nặng
               nhất của lỗi này. Bản đầu của file này chỉ lọc `&` và đã sót thật. */
            $rows = $db->query("SELECT `{$ct['khoa']}` AS k, `$c` AS v FROM `$bang`
                                 WHERE `$c` LIKE '%&%' OR `$c` LIKE '%<%'")
                       ->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e){ continue; }

        foreach ($rows as $r){
            if ($r['v'] === null || $r['v'] === '') continue;
            printf("UPDATE `%s` SET `%s` = %s WHERE `%s` = %s;\n",
                $bang, $c, q($r['v']), $ct['khoa'], q((string) $r['k']));
            $demSua++;
        }
    }
}
if ($demSua === 0) echo "-- (khong con o nao chua dau & — bo qua)\n";
echo "\n";

/* ------------------------------------------------------------------ *
 * 000060 — gán ảnh minh hoạ
 * ------------------------------------------------------------------ */
echo "-- ---------------------------------------------------------------------\n";
echo "-- 000060 — Gán ảnh minh hoạ (danh mục / băng-rôn / sản phẩm)\n";
echo "--\n";
echo "-- File ảnh đã nằm trong repo (có ngoại lệ riêng trong .gitignore).\n";
echo "-- Phần dưới chỉ nối các dòng CSDL tới đúng tên file đó.\n";
echo "-- ---------------------------------------------------------------------\n";

echo "\n-- Danh mục: chỉ điền vào ô ảnh đang TRỐNG, ai đã thay ảnh khác thì giữ nguyên\n";
foreach ($db->query("SELECT slug, image FROM part_categories WHERE image IS NOT NULL AND image <> '' ORDER BY slug")->fetchAll(PDO::FETCH_ASSOC) as $r){
    printf("UPDATE `part_categories` SET `image` = %s WHERE `slug` = %s AND (`image` IS NULL OR `image` = '');\n",
        q($r['image']), q($r['slug']));
}

echo "\n-- Băng-rôn: chỉ thêm khi chưa có dòng nào trỏ tới đúng file đó\n";
foreach ($db->query("SELECT title, image, link, sort_order, status FROM banners ORDER BY sort_order")->fetchAll(PDO::FETCH_ASSOC) as $r){
    printf("INSERT INTO `banners` (`title`,`image`,`link`,`sort_order`,`status`,`create_at`)\n"
         . "  SELECT %s, %s, %s, %d, %d, %s FROM DUAL\n"
         . "  WHERE NOT EXISTS (SELECT 1 FROM `banners` b WHERE b.`image` = %s);\n",
        q($r['title']), q($r['image']), q((string) $r['link']), (int) $r['sort_order'],
        (int) $r['status'], q($now), q($r['image']));
}

echo "\n-- Ảnh sản phẩm: chỉ thay các dòng ảnh demo (*-demo.svg).\n";
echo "-- Ảnh thật do người dùng tự tải lên KHÔNG bị đụng tới.\n";
$sp = $db->query(
    "SELECT p.code, i.image, i.sort_order, i.is_primary
       FROM part_images i JOIN parts p ON p.id = i.part_id
      WHERE i.image NOT LIKE '%demo.svg'
      ORDER BY p.code, i.sort_order"
)->fetchAll(PDO::FETCH_ASSOC);

$gom = [];
foreach ($sp as $r){ $gom[$r['code']][] = $r; }

foreach ($gom as $code => $anh){
    printf("\n-- %s\n", $code);
    // Chỉ xoá khi mặt hàng đó ĐANG toàn ảnh demo
    printf("DELETE i FROM `part_images` i JOIN `parts` p ON p.`id` = i.`part_id`\n"
         . "  WHERE p.`code` = %s\n"
         . "    AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `part_images`) x\n"
         . "                     WHERE x.`part_id` = p.`id` AND x.`image` NOT LIKE '%%demo.svg');\n",
        q($code));

    foreach ($anh as $a){
        printf("INSERT INTO `part_images` (`part_id`,`image`,`sort_order`,`is_primary`,`create_at`)\n"
             . "  SELECT p.`id`, %s, %d, %d, %s FROM `parts` p WHERE p.`code` = %s\n"
             . "    AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `part_images`) x\n"
             . "                     WHERE x.`part_id` = p.`id` AND x.`image` = %s);\n",
            q($a['image']), (int) $a['sort_order'], (int) $a['is_primary'], q($now),
            q($code), q($a['image']));
    }
}

/* ------------------------------------------------------------------ *
 * 000061 — đăng ký module "Quản lý module"
 * ------------------------------------------------------------------ */
echo "\n-- ---------------------------------------------------------------------\n";
echo "-- 000061 — Đăng ký module \"Quản lý module\"\n";
echo "--\n";
echo "-- Thiếu dòng này thì mục \"Quản lý module\" KHÔNG hiện trong menu trái\n";
echo "-- (menu dựng từ bảng `modules`), và RoleMiddleware cũng không gác được\n";
echo "-- màn hình đó.\n";
echo "-- ---------------------------------------------------------------------\n";

printf("INSERT INTO `modules` (`name`,`link`,`create_at`)\n"
     . "  SELECT %s, %s, %s FROM DUAL\n"
     . "  WHERE NOT EXISTS (SELECT 1 FROM `modules` m WHERE m.`link` = %s);\n\n",
    q('Quản lý module'), q('modules'), q($now), q('modules'));

foreach (['view', 'add', 'edit', 'delete'] as $role){
    printf("INSERT INTO `permissions` (`module_id`,`group_id`,`role`)\n"
         . "  SELECT m.`id`, g.`id`, %s\n"
         . "    FROM `modules` m JOIN `groups` g\n"
         . "   WHERE m.`link` = %s AND g.`name` = %s\n"
         . "     AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `permissions`) p\n"
         . "                      WHERE p.`module_id` = m.`id` AND p.`group_id` = g.`id` AND p.`role` = %s);\n",
        q($role), q('modules'), q('Admin'), q($role));
}

/* ------------------------------------------------------------------ *
 * Đánh dấu đã chạy — để sau này lỡ gọi migrate.php cũng không chạy lại
 * ------------------------------------------------------------------ */
echo "\n-- ---------------------------------------------------------------------\n";
echo "-- Đánh dấu ba migration là ĐÃ CHẠY.\n";
echo "--\n";
echo "-- Cần thiết: chạy SQL bằng tay thì bảng `migrations` không biết, nên nếu\n";
echo "-- sau này có ai gọi `php migrate.php` nó sẽ chạy lại ba cái này. Cả ba đều\n";
echo "-- vô hại khi chạy lại, nhưng ghi nhận cho đúng vẫn hơn.\n";
echo "-- ---------------------------------------------------------------------\n";

$batch = (int) $db->query("SELECT COALESCE(MAX(batch),0) FROM migrations")->fetchColumn();
foreach ([
    '2026_08_26_000059_go_ma_hoa_html_bi_chong_lop',
    '2026_08_26_000060_gan_anh_minh_hoa_vao_csdl',
    '2026_08_26_000061_them_module_quan_ly_module',
] as $mg){
    printf("INSERT INTO `migrations` (`migration`,`batch`)\n"
         . "  SELECT %s, %d FROM DUAL\n"
         . "  WHERE NOT EXISTS (SELECT 1 FROM `migrations` x WHERE x.`migration` = %s);\n",
        q($mg), $batch, q($mg));
}

echo "\n-- Hết.\n";
