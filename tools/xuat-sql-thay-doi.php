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
 *   000062  bảng xe của khách (biển số, số km)
 *   000063  nhiều gara — bảng `garages` + cột `garage_id`
 *
 * Ba cái đầu chỉ sửa/thêm DỮ LIỆU; 000062 và 000063 đổi CẤU TRÚC.
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
echo "-- TÂN PHÁT — thay đổi CSDL, tương đương migration 000059 → 000063\n";
echo "-- Sinh tự động lúc $now bằng tools/xuat-sql-thay-doi.php\n";
echo "--\n";
echo "-- Phần 1-3 chỉ sửa và thêm DỮ LIỆU.\n";
echo "-- Phần 4-5 đổi CẤU TRÚC: 2 bảng mới (`member_vehicles`, `garages`) và\n";
echo "-- cột `garage_id` thêm vào 4 bảng đang có.\n";
echo "-- Không có DROP nào. Chạy lại nhiều lần không sinh dòng trùng và không\n";
echo "-- báo lỗi trùng cột — các lệnh ALTER đều có kiểm tra trước.\n";
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
 * 4. Bảng xe của khách (biển số + số km)                    — 000062
 *
 * Khác ba phần trên: đây là thay đổi CẤU TRÚC, không phải dữ liệu.
 * Sinh nguyên văn từ migration chứ không đọc CSDL, vì không có dữ liệu
 * nào để chép lại — mới chỉ là cái bảng rỗng.
 * ------------------------------------------------------------------ */
echo "\n-- ---------------------------------------------------------------------\n";
echo "-- 4. Bảng `member_vehicles` — xe của khách (biển số, số km).\n";
echo "--\n";
echo "-- Một khách nhiều xe nên phải là bảng riêng, không phải hai cột thêm\n";
echo "-- vào `members`. CREATE TABLE IF NOT EXISTS nên chạy lại vô hại.\n";
echo "-- ---------------------------------------------------------------------\n\n";

echo "CREATE TABLE IF NOT EXISTS `member_vehicles` (\n"
   . "    `id`            INT AUTO_INCREMENT PRIMARY KEY,\n"
   . "    `member_id`     INT NOT NULL,\n"
   . "    `bien_so`       VARCHAR(20)  NOT NULL,\n"
   . "    `bien_so_chuan` VARCHAR(20)  NOT NULL,\n"
   . "    `hang_xe`       VARCHAR(60)  DEFAULT NULL,\n"
   . "    `model_xe`      VARCHAR(60)  DEFAULT NULL,\n"
   . "    `nam_sx`        SMALLINT     DEFAULT NULL,\n"
   . "    `mau_xe`        VARCHAR(40)  DEFAULT NULL,\n"
   . "    `so_km`         INT          DEFAULT NULL,\n"
   . "    `ghi_chu`       VARCHAR(255) DEFAULT NULL,\n"
   . "    `create_at`     DATETIME     DEFAULT NULL,\n"
   . "    `update_at`     DATETIME     DEFAULT NULL,\n"
   . "    KEY `idx_mv_member` (`member_id`),\n"
   . "    KEY `idx_mv_bien_so` (`bien_so_chuan`),\n"
   . "    CONSTRAINT `fk_mv_member`\n"
   . "        FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)\n"
   . "        ON DELETE CASCADE ON UPDATE CASCADE\n"
   . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n";

/* ------------------------------------------------------------------ *
 * 5. Nhiều gara — tầng 1                                    — 000063
 *
 * Đây là phần NẶNG NHẤT của file: nó thêm cột vào 4 bảng đang có dữ liệu
 * thật. MySQL không có `ALTER TABLE ... ADD COLUMN IF NOT EXISTS`, mà file
 * này phải chạy lại được nhiều lần (người dùng hay dán lại cho chắc). Nên
 * mỗi lệnh ALTER được bọc trong một khối kiểm tra information_schema rồi
 * PREPARE/EXECUTE — chạy lần hai thì nó thành `SELECT 1` vô hại.
 * ------------------------------------------------------------------ */
echo "\n-- ---------------------------------------------------------------------\n";
echo "-- 5. Nhiều gara — bảng `garages` + cột `garage_id` cho 4 bảng.\n";
echo "--\n";
echo "-- Gara là ĐƠN VỊ, không phải kho: một gara có thể có nhiều kho. Hai kho\n";
echo "-- đang có vẫn là kho của Tân Phát, được gán về gara tổng bên dưới.\n";
echo "--\n";
echo "-- Các lệnh ALTER bọc trong PREPARE/EXECUTE để chạy lại lần hai không báo\n";
echo "-- lỗi \"Duplicate column name\".\n";
echo "-- ---------------------------------------------------------------------\n\n";

echo "CREATE TABLE IF NOT EXISTS `garages` (\n"
   . "    `id`         INT AUTO_INCREMENT PRIMARY KEY,\n"
   . "    `code`       VARCHAR(30)  NOT NULL,\n"
   . "    `name`       VARCHAR(150) NOT NULL,\n"
   . "    `address`    VARCHAR(255) DEFAULT NULL,\n"
   . "    `phone`      VARCHAR(30)  DEFAULT NULL,\n"
   . "    `is_master`  TINYINT(1)   NOT NULL DEFAULT 0,\n"
   . "    `status`     TINYINT(1)   NOT NULL DEFAULT 1,\n"
   . "    `sort_order` INT          NOT NULL DEFAULT 0,\n"
   . "    `create_at`  DATETIME     DEFAULT NULL,\n"
   . "    `update_at`  DATETIME     DEFAULT NULL,\n"
   . "    UNIQUE KEY `uq_garage_code` (`code`)\n"
   . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";

/* Gara tổng — chép đúng dòng đang có trên máy này, hoặc dựng mặc định nếu
   chưa migrate. Phải có trước khi gán dữ liệu cũ về nó. */
$gr = [];
try {
    $gr = $db->query("SELECT `code`,`name`,`address`,`phone` FROM `garages` WHERE `is_master` = 1")
             ->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (\Throwable $e){}
$gCode = !empty($gr['code']) ? $gr['code'] : 'TP01';
$gName = !empty($gr['name']) ? $gr['name'] : 'Tân Phát';

echo "-- Gara tổng. Chỉ thêm khi chưa có gara tổng nào.\n";
printf("INSERT INTO `garages` (`code`,`name`,`address`,`phone`,`is_master`,`status`,`sort_order`,`create_at`)\n"
     . "  SELECT %s, %s, %s, %s, 1, 1, 0, %s FROM DUAL\n"
     . "  WHERE NOT EXISTS (SELECT 1 FROM (SELECT * FROM `garages`) g WHERE g.`is_master` = 1);\n\n",
    q($gCode), q($gName), q(isset($gr['address']) ? $gr['address'] : null),
    q(isset($gr['phone']) ? $gr['phone'] : null), q($now));

/**
 * Sinh một lệnh DDL chỉ chạy khi ĐIỀU KIỆN đếm được bằng 0.
 *
 * `$dem` là câu SELECT COUNT(*) trên information_schema. Bằng 0 nghĩa là thứ
 * đó chưa có -> chạy $ddl; khác 0 -> chạy `SELECT 1` cho xong chuyện.
 * Mỗi khối dùng tên biến riêng ($bien) để dán liền nhau không đụng nhau.
 */
function ddlNeuThieu($bien, $dem, $ddl){
    printf("SET @%s = (SELECT IF((%s) > 0, 'SELECT 1', %s));\n"
         . "PREPARE st_%s FROM @%s; EXECUTE st_%s; DEALLOCATE PREPARE st_%s;\n\n",
        $bien, $dem, "'" . str_replace("'", "''", $ddl) . "'",
        $bien, $bien, $bien, $bien);
}

$bang4 = [
    'warehouses'     => 'fk_wh_garage',
    'users'          => 'fk_user_garage',
    'quotations'     => 'fk_quote_garage',
    'sales_invoices' => 'fk_inv_garage',
];

foreach ($bang4 as $bang => $fk){
    echo "-- $bang\n";

    ddlNeuThieu(
        'c_' . $bang,
        "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()"
      . " AND TABLE_NAME = '$bang' AND COLUMN_NAME = 'garage_id'",
        "ALTER TABLE `$bang` ADD COLUMN `garage_id` INT DEFAULT NULL"
    );

    ddlNeuThieu(
        'i_' . $bang,
        "SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE()"
      . " AND TABLE_NAME = '$bang' AND INDEX_NAME = 'idx_{$bang}_garage'",
        "ALTER TABLE `$bang` ADD KEY `idx_{$bang}_garage` (`garage_id`)"
    );

    /* ON DELETE SET NULL, KHÔNG phải CASCADE: xoá một gara mà kéo theo cả báo
       giá và hoá đơn của nó là mất dữ liệu lịch sử trong im lặng. */
    ddlNeuThieu(
        'k_' . $bang,
        "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE()"
      . " AND TABLE_NAME = '$bang' AND CONSTRAINT_NAME = '$fk'",
        "ALTER TABLE `$bang` ADD CONSTRAINT `$fk` FOREIGN KEY (`garage_id`)"
      . " REFERENCES `garages` (`id`) ON DELETE SET NULL ON UPDATE CASCADE"
    );

    // Dữ liệu cũ về gara tổng. Chỉ đụng dòng đang trống -> chạy lại không đè.
    printf("UPDATE `%s` SET `garage_id` = (SELECT `id` FROM (SELECT `id` FROM `garages` WHERE `is_master` = 1 LIMIT 1) m)\n"
         . "  WHERE `garage_id` IS NULL;\n\n", $bang);
}

echo "-- Đăng ký màn hình \"Quản lý gara\" + quyền cho nhóm Admin.\n";
echo "-- Thiếu phần này thì menu trái không hiện mục đó và RoleMiddleware\n";
echo "-- không gác được màn hình.\n";
printf("INSERT INTO `modules` (`name`,`link`,`create_at`)\n"
     . "  SELECT %s, %s, %s FROM DUAL\n"
     . "  WHERE NOT EXISTS (SELECT 1 FROM (SELECT * FROM `modules`) m WHERE m.`link` = %s);\n\n",
    q('Quản lý gara'), q('garages'), q($now), q('garages'));

foreach (['view', 'add', 'edit', 'delete'] as $role){
    printf("INSERT INTO `permissions` (`module_id`,`group_id`,`role`)\n"
         . "  SELECT m.`id`, g.`id`, %s\n"
         . "    FROM `modules` m JOIN `groups` g\n"
         . "   WHERE m.`link` = %s AND g.`name` = %s\n"
         . "     AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `permissions`) p\n"
         . "                      WHERE p.`module_id` = m.`id` AND p.`group_id` = g.`id` AND p.`role` = %s);\n",
        q($role), q('garages'), q('Admin'), q($role));
}

/* ------------------------------------------------------------------ *
 * Đánh dấu đã chạy — để sau này lỡ gọi migrate.php cũng không chạy lại
 * ------------------------------------------------------------------ */
echo "\n-- ---------------------------------------------------------------------\n";
echo "-- Đánh dấu năm migration là ĐÃ CHẠY.\n";
echo "--\n";
echo "-- Cần thiết: chạy SQL bằng tay thì bảng `migrations` không biết, nên nếu\n";
echo "-- sau này có ai gọi `php migrate.php` nó sẽ chạy lại. Cả bốn đều\n";
echo "-- vô hại khi chạy lại, nhưng ghi nhận cho đúng vẫn hơn.\n";
echo "-- ---------------------------------------------------------------------\n";

$batch = (int) $db->query("SELECT COALESCE(MAX(batch),0) FROM migrations")->fetchColumn();
foreach ([
    '2026_08_26_000059_go_ma_hoa_html_bi_chong_lop',
    '2026_08_26_000060_gan_anh_minh_hoa_vao_csdl',
    '2026_08_26_000061_them_module_quan_ly_module',
    '2026_08_27_000062_them_bang_xe_cua_khach',
    '2026_09_03_000063_them_bang_gara',
] as $mg){
    /* PHẢI có `ran_at`: cột đó NOT NULL và KHÔNG có giá trị mặc định, thiếu là
       MySQL báo lỗi 1364. Trên máy đã migrate thì mấy dòng này đã tồn tại nên
       INSERT không chạy và lỗi không bao giờ lộ ra — đúng máy production (chưa
       có dòng nào) mới sập. */
    printf("INSERT INTO `migrations` (`migration`,`batch`,`ran_at`)\n"
         . "  SELECT %s, %d, %s FROM DUAL\n"
         . "  WHERE NOT EXISTS (SELECT 1 FROM `migrations` x WHERE x.`migration` = %s);\n",
        q($mg), $batch, q($now), q($mg));
}

echo "\n-- Hết.\n";
