<?php
/**
 * XUẤT RA SQL cho các thay đổi CSDL gần đây — dành cho người không muốn chạy
 * `php migrate.php` mà thích dán thẳng vào phpMyAdmin.
 *
 * Chạy:  C:\xampp\php\php.exe tools\xuat-sql-thay-doi.php > deploy\thay-doi-csdl.sql
 *
 * Tương đương các migration:
 *   000059  gỡ mã hoá HTML bị chồng lớp  (lỗi &#38;#38;)
 *   000060  gán ảnh minh hoạ vào CSDL
 *   000061  đăng ký module "Quản lý module"
 *   000062  bảng xe của khách (biển số, số km)
 *   000063  nhiều gara — bảng `garages` + cột `garage_id`
 *   000064  khách vãng lai không cần email
 *   000065  danh mục riêng của gara
 *   000066  biển số xe + số km trên báo giá và hoá đơn
 *   000067  biển số xe + số km trên phiếu bảo hành
 *   000068  phân quyền cho nhóm Manager và Staff (+ vá lỗ tự nâng quyền)
 *   000070  phiếu bảo trì bên cạnh phiếu bảo hành (cột `loai`, chu kỳ km)
 *   000071  tỉnh / phường cho Đối tượng và Khách hàng (4 cột mỗi bảng)
 *   000072  xe của khách + phiếu tiếp nhận (1 khách nhiều xe, 1 xe nhiều phiếu)
 *   000073  đổi tên màn "Cấu hình website" thành "Cấu hình chung"
 *   000074  đồng bộ collation hai bảng xe / phiếu tiếp nhận về utf8mb4_unicode_ci
 *   000075  collation MẶC ĐỊNH của CSDL -> utf8mb4_unicode_ci
 *   000076  gara độc lập — nền: garage_id cho 9 bảng, màn chỉ Tân Phát, thông tin gara
 *   000077  gara độc lập — khách và xe: email đối tượng, cấu hình riêng gara, màn
 *           Tài khoản website, chuyển khách / xe cũ, "không trùng" theo gara
 *
 * RIÊNG 000069 (Manager tự thêm nhân viên cho gara mình) nằm ở file KHÁC,
 * chạy SAU khi đẩy code:
 *
 *   C:\xampp\php\php.exe tools\xuat-sql-thay-doi.php --sau-khi-day-code > deploy\sau-khi-day-code.sql
 *
 * Ngược với mọi phần khác (dán SQL trước rồi mới đẩy code): 000069 chỉ cấp
 * quyền vào màn Người dùng, chốt chặn thật nằm trong code mới
 * (Users::phamVi). Dán quyền trước khi có code đó thì Manager vào màn Người
 * dùng CŨ — không giới hạn gì — và tạo được tài khoản Admin.
 *
 * 000059-000061 chỉ sửa/thêm DỮ LIỆU; từ 000062 trở đi đổi CẤU TRÚC.
 *
 * THÊM PHẦN MỚI THÌ PHẢI SỬA HAI CHỖ: danh sách trên (chỉ là chú thích) và
 * mảng tên migration ở mục "Đánh dấu đã chạy" cuối file (mới là thứ chạy
 * thật). Sửa một chỗ thì người đọc tin vào chú thích rồi bỏ sót migration.
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

/* --chi-cau-truc: bỏ phần 1-3 (sửa dữ liệu cũ), chỉ xuất cấu trúc.
   Phần 1-3 CHÉP nội dung tin tức / cài đặt / danh mục TỪ MÁY NÀY để đè lên
   máy đích. Trên máy thật thì đó là ghi đè: ai sửa bài viết trên server sẽ
   mất. Cấu trúc thì ngược lại — chỉ thêm bảng và cột, không đụng dữ liệu. */
$chiCauTruc = in_array('--chi-cau-truc', $argv, true);

/* --sau-khi-day-code: CHỈ xuất 000069 rồi dừng. Xem giải thích ở đầu file. */
if (in_array('--sau-khi-day-code', $argv, true)){
    echo "-- =====================================================================\n";
    echo "-- TÂN PHÁT — CHẠY SAU KHI ĐÃ ĐẨY CODE (migration 000069)\n";
    echo "-- Sinh tự động lúc $now bằng tools/xuat-sql-thay-doi.php --sau-khi-day-code\n";
    echo "--\n";
    echo "-- Cho nhóm Manager tự thêm nhân viên cho gara của mình: quyền xem / thêm /\n";
    echo "-- sửa trên màn Người dùng. KHÔNG có quyền xoá.\n";
    echo "--\n";
    echo "-- !! DÁN FILE NÀY SAU KHI CODE MỚI ĐÃ LÊN SERVER !!\n";
    echo "-- Code mới mới có chốt chặn: Manager chỉ cấp được nhóm thấp hơn mình, chỉ\n";
    echo "-- trong gara mình. Dán trước thì Manager vào màn Người dùng CŨ — không\n";
    echo "-- giới hạn gì — và tạo được tài khoản Admin.\n";
    echo "--\n";
    echo "-- Chạy lại nhiều lần không sinh dòng trùng.\n";
    echo "-- =====================================================================\n\n";
    echo "SET NAMES utf8mb4;\n\n";

    $ds = $db->query(
        "SELECT p.`role` FROM `permissions` p
           JOIN `groups` g  ON g.`id` = p.`group_id`
           JOIN `modules` m ON m.`id` = p.`module_id`
          WHERE g.`name` = 'Manager' AND m.`link` = 'users'
          ORDER BY p.`role`"
    )->fetchAll(PDO::FETCH_COLUMN);

    if (empty($ds)){
        echo "-- (may nay chua chay migration 000069 — chua co gi de xuat)\n";
        exit;
    }
    foreach ($ds as $role){
        printf("INSERT INTO `permissions` (`module_id`,`group_id`,`role`)\n"
             . "  SELECT m.`id`, g.`id`, %s\n"
             . "    FROM `modules` m JOIN `groups` g\n"
             . "   WHERE m.`link` = %s AND g.`name` = %s\n"
             . "     AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `permissions`) p\n"
             . "                      WHERE p.`module_id` = m.`id` AND p.`group_id` = g.`id` AND p.`role` = %s);\n",
            q($role), q('users'), q('Manager'), q($role));
    }

    $batch = (int) $db->query("SELECT COALESCE(MAX(batch),0) FROM migrations")->fetchColumn();
    $mg = '2026_09_11_000069_manager_them_nhan_vien_gara';
    echo "\n-- Đánh dấu migration đã chạy (PHẢI có `ran_at`: NOT NULL, không mặc định)\n";
    printf("INSERT INTO `migrations` (`migration`,`batch`,`ran_at`)\n"
         . "  SELECT %s, %d, %s FROM DUAL\n"
         . "  WHERE NOT EXISTS (SELECT 1 FROM `migrations` x WHERE x.`migration` = %s);\n",
        q($mg), $batch, q($now), q($mg));
    echo "\n-- Hết.\n";
    exit;
}

echo "-- =====================================================================\n";
echo "-- TÂN PHÁT — thay đổi CSDL, tương đương migration 000059 → 000077 (trừ 000069)\n";
echo "-- Sinh tự động lúc $now bằng tools/xuat-sql-thay-doi.php\n";
echo "--\n";
echo "-- Phần 1-3 chỉ sửa và thêm DỮ LIỆU.\n";
echo "-- Phần 4-9 đổi CẤU TRÚC:\n";
echo "--   3 bảng mới: `member_vehicles`, `garages`, `garage_part_prices`\n";
echo "--   `garage_id` thêm vào 5 bảng cũ\n";
echo "--   biển số xe + số km thêm vào báo giá, hoá đơn và phiếu bảo hành\n";
echo "--   `members`.`email` nới cho phép để trống\n";
echo "-- Phần 10 cấp quyền cho nhóm Manager / Staff, kèm một bản vá bảo mật.\n";
echo "-- Phần 11 thêm phiếu bảo trì (cột `loai` trên phiếu bảo hành, chu kỳ km).\n";
echo "-- Phần 12 thêm tỉnh / phường cho Đối tượng và Khách hàng.\n";
echo "-- Phần 13 thêm bảng xe của khách + phiếu tiếp nhận, và cột nối từ báo\n";
echo "--   giá / hoá đơn / phiếu bảo hành về phiếu tiếp nhận và về xe.\n";
echo "-- Phần 17 gara độc lập (nền): garage_id cho 9 bảng, gán dữ liệu cũ vào gara,\n";
echo "--   đánh dấu màn chỉ Tân Phát, cột thông tin gara. Cột mới để NULL được.\n";
echo "-- Phần 18 khách và xe theo gara: màn Tài khoản website, chuyển khách / xe cũ\n";
echo "--   sang đối tượng / xe, số phiếu - biển số không trùng TRONG TỪNG GARA.\n";
echo "-- Quyền Manager tự thêm nhân viên (000069) KHÔNG nằm ở đây — nó ở file\n";
echo "-- deploy/sau-khi-day-code.sql, dán SAU khi đẩy code.\n";
echo "-- Không có DROP nào. Chạy lại nhiều lần không sinh dòng trùng và không\n";
echo "-- báo lỗi trùng cột — các lệnh ALTER đều có kiểm tra trước.\n";
echo "--\n";
echo "-- Cách dùng: phpMyAdmin > chọn CSDL > tab SQL > dán toàn bộ > Thực hiện.\n";
echo "-- =====================================================================\n\n";
echo "SET NAMES utf8mb4;\n\n";

if ($chiCauTruc){
    echo "-- ---------------------------------------------------------------------\n";
    echo "-- CHE DO CHI CAU TRUC — da BO phan 1-3 (sua du lieu cu).\n";
    echo "--\n";
    echo "-- Phan 1-3 chep noi dung tin tuc / cai dat / danh muc TU MAY LOCAL de\n";
    echo "-- de len may dich. Dung tren may that thi ai sua bai viet o do se bi\n";
    echo "-- ghi de bang ban local. File nay bo han phan do.\n";
    echo "--\n";
    echo "-- Con lai chi la CAU TRUC (bang moi, cot moi) + dang ky man hinh moi\n";
    echo "-- vao bang `modules`/`permissions`. Khong dong du lieu nghiep vu nao.\n";
    echo "-- ---------------------------------------------------------------------\n\n";
}

/* ------------------------------------------------------------------ *
 * 000059 — gỡ mã hoá HTML bị chồng lớp
 * ------------------------------------------------------------------ */
if (!$chiCauTruc){
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

} // hết khối `if (!$chiCauTruc)` — phần 1-3 sửa dữ liệu

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
 * 6. Khách vãng lai không cần email                         — 000064
 * ------------------------------------------------------------------ */
echo "\n-- ---------------------------------------------------------------------\n";
echo "-- 6. Khách vãng lai: `members`.`email` để trống được.\n";
echo "--\n";
echo "-- Khách lái xe tới gara đa số không có tài khoản đăng nhập. Bắt nhập\n";
echo "-- email nghĩa là bắt nhân viên bịa ra email giả.\n";
echo "--\n";
echo "-- Khoá UNIQUE giữ nguyên: MySQL cho NHIỀU dòng NULL, nên trăm khách bỏ\n";
echo "-- trống vẫn vào được, mà hai khách cùng một email thật thì vẫn bị chặn.\n";
echo "-- ---------------------------------------------------------------------\n\n";

ddlNeuThieu(
    'email_null',
    "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()"
  . " AND TABLE_NAME = 'members' AND COLUMN_NAME = 'email' AND IS_NULLABLE = 'YES'",
    "ALTER TABLE `members` MODIFY `email` VARCHAR(150) NULL DEFAULT NULL"
);

echo "-- Quyền `add` cho module customers. Thiếu dòng này thì nút \"Thêm khách\n";
echo "-- hàng\" không hiện VÀ RoleMiddleware chặn thẳng URL — code đủ cả mà\n";
echo "-- bấm không vào được.\n";
printf("INSERT INTO `permissions` (`module_id`,`group_id`,`role`)\n"
     . "  SELECT m.`id`, g.`id`, %s\n"
     . "    FROM `modules` m JOIN `groups` g\n"
     . "   WHERE m.`link` = %s AND g.`name` = %s\n"
     . "     AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `permissions`) p\n"
     . "                      WHERE p.`module_id` = m.`id` AND p.`group_id` = g.`id` AND p.`role` = %s);\n",
    q('add'), q('customers'), q('Admin'), q('add'));

/* ------------------------------------------------------------------ *
 * 7. Danh mục riêng của gara                                — 000065
 * ------------------------------------------------------------------ */
echo "\n-- ---------------------------------------------------------------------\n";
echo "-- 7. Danh mục riêng của gara: `parts`.`garage_id` + `garage_part_prices`.\n";
echo "--\n";
echo "-- `parts`.`garage_id` NULL = hàng của danh mục tổng (mọi gara đều thấy);\n";
echo "-- có giá trị = hàng chỉ gara đó có. 18 mặt hàng đang có giữ nguyên NULL.\n";
echo "--\n";
echo "-- Một dòng trong `garage_part_prices` mang hai nghĩa: \"gara này có làm\n";
echo "-- mặt hàng đó\" và \"với giá này\". Giá NULL = lấy theo giá tổng.\n";
echo "--\n";
echo "-- CHÚ Ý ba cách xoá khác nhau, cố ý chứ không phải quên:\n";
echo "--   parts.garage_id            RESTRICT  (SET NULL sẽ đẩy hàng riêng của\n";
echo "--                                        một gara vào danh mục tổng)\n";
echo "--   garage_part_prices.*       CASCADE   (bảng giá vô nghĩa khi gara hoặc\n";
echo "--                                        mặt hàng không còn)\n";
echo "-- ---------------------------------------------------------------------\n\n";

ddlNeuThieu(
    'c_parts_garage',
    "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()"
  . " AND TABLE_NAME = 'parts' AND COLUMN_NAME = 'garage_id'",
    "ALTER TABLE `parts` ADD COLUMN `garage_id` INT DEFAULT NULL"
);
ddlNeuThieu(
    'i_parts_garage',
    "SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE()"
  . " AND TABLE_NAME = 'parts' AND INDEX_NAME = 'idx_parts_garage'",
    "ALTER TABLE `parts` ADD KEY `idx_parts_garage` (`garage_id`)"
);
ddlNeuThieu(
    'k_parts_garage',
    "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE()"
  . " AND TABLE_NAME = 'parts' AND CONSTRAINT_NAME = 'fk_part_garage'",
    "ALTER TABLE `parts` ADD CONSTRAINT `fk_part_garage` FOREIGN KEY (`garage_id`)"
  . " REFERENCES `garages` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE"
);

echo "CREATE TABLE IF NOT EXISTS `garage_part_prices` (\n"
   . "    `id`         INT AUTO_INCREMENT PRIMARY KEY,\n"
   . "    `garage_id`  INT NOT NULL,\n"
   . "    `part_id`    INT NOT NULL,\n"
   . "    `price`      DECIMAL(15,2) DEFAULT NULL,\n"
   . "    `sale_price` DECIMAL(15,2) DEFAULT NULL,\n"
   . "    `status`     TINYINT(1) NOT NULL DEFAULT 1,\n"
   . "    `create_at`  DATETIME DEFAULT NULL,\n"
   . "    `update_at`  DATETIME DEFAULT NULL,\n"
   . "    UNIQUE KEY `uq_gpp` (`garage_id`, `part_id`),\n"
   . "    KEY `idx_gpp_part` (`part_id`),\n"
   . "    CONSTRAINT `fk_gpp_garage` FOREIGN KEY (`garage_id`)\n"
   . "        REFERENCES `garages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,\n"
   . "    CONSTRAINT `fk_gpp_part` FOREIGN KEY (`part_id`)\n"
   . "        REFERENCES `parts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE\n"
   . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";

echo "-- Đăng ký màn hình \"Danh mục của gara\" + quyền cho nhóm Admin.\n";
printf("INSERT INTO `modules` (`name`,`link`,`create_at`)\n"
     . "  SELECT %s, %s, %s FROM DUAL\n"
     . "  WHERE NOT EXISTS (SELECT 1 FROM (SELECT * FROM `modules`) m WHERE m.`link` = %s);\n\n",
    q('Danh mục của gara'), q('garage-catalog'), q($now), q('garage-catalog'));

foreach (['view', 'add', 'edit', 'delete'] as $role){
    printf("INSERT INTO `permissions` (`module_id`,`group_id`,`role`)\n"
         . "  SELECT m.`id`, g.`id`, %s\n"
         . "    FROM `modules` m JOIN `groups` g\n"
         . "   WHERE m.`link` = %s AND g.`name` = %s\n"
         . "     AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `permissions`) p\n"
         . "                      WHERE p.`module_id` = m.`id` AND p.`group_id` = g.`id` AND p.`role` = %s);\n",
        q($role), q('garage-catalog'), q('Admin'), q($role));
}

/* ------------------------------------------------------------------ *
 * 8. Biển số xe + số km trên chứng từ                       — 000066
 * ------------------------------------------------------------------ */
echo "\n-- ---------------------------------------------------------------------\n";
echo "-- 8. Biển số xe + số km trên báo giá và hoá đơn bán.\n";
echo "--\n";
echo "-- Gara sửa xe thì chứng từ phải nói rõ nó cho CHIẾC XE NÀO.\n";
echo "-- `bien_so` giữ nguyên văn người gõ để in ra; `bien_so_chuan` (chỉ chữ +\n";
echo "-- số, viết hoa) để tra cứu — chỉ mục đặt trên cột chuẩn hoá này.\n";
echo "-- Cả ba cột để trống được: bán lẻ phụ tùng qua quầy thì không có xe nào.\n";
echo "-- ---------------------------------------------------------------------\n\n";

foreach (['quotations', 'sales_invoices'] as $bang){
    echo "-- $bang\n";
    foreach ([
        'bien_so'       => 'VARCHAR(20) DEFAULT NULL',
        'bien_so_chuan' => 'VARCHAR(20) DEFAULT NULL',
        'so_km'         => 'INT DEFAULT NULL',
    ] as $cot => $kieu){
        ddlNeuThieu(
            'c_' . $bang . '_' . $cot,
            "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()"
          . " AND TABLE_NAME = '$bang' AND COLUMN_NAME = '$cot'",
            "ALTER TABLE `$bang` ADD COLUMN `$cot` $kieu"
        );
    }
    ddlNeuThieu(
        'i_' . $bang . '_bs',
        "SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE()"
      . " AND TABLE_NAME = '$bang' AND INDEX_NAME = 'idx_{$bang}_bien_so'",
        "ALTER TABLE `$bang` ADD KEY `idx_{$bang}_bien_so` (`bien_so_chuan`)"
    );
}

/* ------------------------------------------------------------------ *
 * 9. Biển số xe + số km trên phiếu bảo hành                 — 000067
 * ------------------------------------------------------------------ */
echo "\n-- ---------------------------------------------------------------------\n";
echo "-- 9. Biển số xe + số km trên phiếu bảo hành.\n";
echo "--\n";
echo "-- Bảo hành một cái đĩa phanh mà không biết nó lắp trên xe nào thì gần\n";
echo "-- như vô nghĩa. Khi khách quay lại, BIỂN SỐ mới là thứ người ta đọc —\n";
echo "-- không ai nhớ số serial của phụ tùng đã thay sáu tháng trước.\n";
echo "--\n";
echo "-- `serial_no` đang có KHÔNG thay được: đó là serial của PHỤ TÙNG, không\n";
echo "-- phải của XE. Đây là ba cột THÊM, cột cũ giữ nguyên.\n";
echo "-- ---------------------------------------------------------------------\n\n";

foreach ([
    'bien_so'       => 'VARCHAR(20) DEFAULT NULL',
    'bien_so_chuan' => 'VARCHAR(20) DEFAULT NULL',
    'so_km'         => 'INT DEFAULT NULL',
] as $cot => $kieu){
    ddlNeuThieu(
        'c_wr_' . $cot,
        "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()"
      . " AND TABLE_NAME = 'warranty_requests' AND COLUMN_NAME = '$cot'",
        "ALTER TABLE `warranty_requests` ADD COLUMN `$cot` $kieu"
    );
}
ddlNeuThieu(
    'i_wr_bs',
    "SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE()"
  . " AND TABLE_NAME = 'warranty_requests' AND INDEX_NAME = 'idx_wr_bien_so'",
    "ALTER TABLE `warranty_requests` ADD KEY `idx_wr_bien_so` (`bien_so_chuan`)"
);

/* ------------------------------------------------------------------ *
 * 10. Phân quyền cho nhóm Manager và Staff                  — 000068
 *
 * Đọc thẳng trạng thái HIỆN TẠI của hai nhóm trên máy này rồi sinh SQL —
 * không chép lại bảng cấp quyền trong migration. Nghĩa là chạy công cụ
 * này SAU khi đã migrate xong thì file luôn khớp với thực tế.
 * ------------------------------------------------------------------ */
echo "\n-- ---------------------------------------------------------------------\n";
echo "-- 10. Phân quyền cho nhóm Manager và Staff.\n";
echo "--\n";
echo "-- Trước đó hai nhóm này gần như rỗng: cấp tài khoản Staff cho thợ xong\n";
echo "-- họ đăng nhập vào không thấy gì.\n";
echo "--\n";
echo "-- KÈM MỘT BẢN VÁ BẢO MẬT: gỡ quyền của Manager/Staff trên màn hình Nhóm.\n";
echo "-- Nhóm Manager có sẵn view/add/edit ở đó từ bản dump gốc, tức là mở được\n";
echo "-- Phân quyền và TỰ CẤP CHO MÌNH mọi quyền. Ai sửa được bảng phân quyền\n";
echo "-- thì mọi phân quyền khác chỉ còn là trang trí.\n";
echo "--\n";
echo "-- KHÔNG đụng nhóm Admin.\n";
echo "-- ---------------------------------------------------------------------\n\n";

echo "-- Gỡ quyền nguy hiểm (chạy TRƯỚC phần cấp)\n";
printf("DELETE p FROM `permissions` p\n"
     . "  JOIN `groups` g ON g.`id` = p.`group_id`\n"
     . "  JOIN `modules` m ON m.`id` = p.`module_id`\n"
     . " WHERE g.`name` IN (%s, %s) AND m.`link` = %s;\n\n",
    q('Manager'), q('Staff'), q('groups'));

$dsQuyen = $db->query(
    "SELECT g.`name` AS nhom, m.`link` AS link, p.`role` AS role
       FROM `permissions` p
       JOIN `groups` g  ON g.`id` = p.`group_id`
       JOIN `modules` m ON m.`id` = p.`module_id`
      WHERE g.`name` IN ('Manager', 'Staff')
        -- Màn Người dùng đi file riêng, chạy SAU khi đẩy code (000069)
        AND m.`link` <> 'users'
      ORDER BY g.`name`, m.`link`, p.`role`"
)->fetchAll(PDO::FETCH_ASSOC);

if (empty($dsQuyen)){
    echo "-- (hai nhom chua co quyen nao tren may nay — bo qua)\n";
} else {
    $nhomTruoc = '';
    foreach ($dsQuyen as $r){
        if ($r['nhom'] !== $nhomTruoc){
            printf("\n-- %s\n", $r['nhom']);
            $nhomTruoc = $r['nhom'];
        }
        printf("INSERT INTO `permissions` (`module_id`,`group_id`,`role`)\n"
             . "  SELECT m.`id`, g.`id`, %s\n"
             . "    FROM `modules` m JOIN `groups` g\n"
             . "   WHERE m.`link` = %s AND g.`name` = %s\n"
             . "     AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `permissions`) p\n"
             . "                      WHERE p.`module_id` = m.`id` AND p.`group_id` = g.`id` AND p.`role` = %s);\n",
            q($r['role']), q($r['link']), q($r['nhom']), q($r['role']));
    }
    printf("\n-- (tong %d dong quyen cho hai nhom)\n", count($dsQuyen));
}

/* ------------------------------------------------------------------ *
 * 11. Phiếu bảo trì bên cạnh phiếu bảo hành                 — 000070
 *
 * Sinh nguyên văn từ migration (không đọc CSDL): cột + chỉ mục có kiểm tra
 * trước, cài đặt chỉ thêm khi thiếu, tên màn hình ghi đè là vô hại.
 * ------------------------------------------------------------------ */
echo "\n-- ---------------------------------------------------------------------\n";
echo "-- 11. Phiếu bảo trì: `warranty_requests`.`loai` ('bao_hanh' | 'bao_tri').\n";
echo "--\n";
echo "-- Mọi phiếu cũ nhận mặc định 'bao_hanh' — giữ đúng nghĩa cũ. Nhắc bảo trì\n";
echo "-- từ nay tính từ phiếu BẢO TRÌ đã xong, theo tháng hoặc km.\n";
echo "-- ---------------------------------------------------------------------\n\n";
ddlNeuThieu('wr_loai',
    "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()"
  . " AND TABLE_NAME = 'warranty_requests' AND COLUMN_NAME = 'loai'",
    "ALTER TABLE `warranty_requests` ADD COLUMN `loai` VARCHAR(10) NOT NULL DEFAULT 'bao_hanh' AFTER `request_no`"
);
ddlNeuThieu('wr_loai_idx',
    "SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE()"
  . " AND TABLE_NAME = 'warranty_requests' AND INDEX_NAME = 'idx_wr_loai_status'",
    "ALTER TABLE `warranty_requests` ADD KEY `idx_wr_loai_status` (`loai`, `status`)"
);
printf("INSERT INTO `site_settings` (`skey`,`svalue`)\n"
     . "  SELECT %s, %s FROM DUAL\n"
     . "  WHERE NOT EXISTS (SELECT 1 FROM `site_settings` x WHERE x.`skey` = %s);\n\n",
    q('maintenance_interval_km'), q('5000'), q('maintenance_interval_km'));
foreach (['warranty' => 'Phiếu bảo hành / bảo trì', 'lich-bao-hanh' => 'Lịch bảo hành / bảo trì'] as $link => $ten){
    printf("UPDATE `modules` SET `name` = %s WHERE `link` = %s;\n", q($ten), q($link));
}

/* ------------------------------------------------------------------ *
 * 12. Tỉnh / phường cho Đối tượng và Khách hàng            — 000071
 *
 * Sinh nguyên văn từ migration (không đọc CSDL): mỗi cột và mỗi chỉ mục đều
 * kiểm tra trước khi thêm, nên dán lại nhiều lần không báo lỗi trùng cột.
 * ------------------------------------------------------------------ */
echo "\n-- ---------------------------------------------------------------------\n";
echo "-- 12. Tỉnh / phường cho `partners` (khách + NCC) và `members` (khách CSKH).\n";
echo "--\n";
echo "-- Bốn cột mỗi bảng, giống bảng `orders` đã có: mã VÀ tên của tỉnh, phường.\n";
echo "-- Lưu cả tên vì đơn vị hành chính còn sáp nhập / đổi tên nữa, và vì API\n";
echo "-- tra cứu bên ngoài có thể chết — địa chỉ đã lưu vẫn phải đọc được.\n";
echo "-- Địa chỉ cũ giữ nguyên, KHÔNG tự tách ra tỉnh/phường.\n";
echo "-- ---------------------------------------------------------------------\n\n";

foreach (['partners', 'members'] as $bangDg){
    foreach ([
        'province_code' => 'INT DEFAULT NULL',
        'province_name' => 'VARCHAR(150) DEFAULT NULL',
        'ward_code'     => 'INT DEFAULT NULL',
        'ward_name'     => 'VARCHAR(150) DEFAULT NULL',
    ] as $cotDg => $kieuDg){
        ddlNeuThieu(
            'dg_' . $bangDg . '_' . $cotDg,
            "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()"
          . " AND TABLE_NAME = '$bangDg' AND COLUMN_NAME = '$cotDg'",
            "ALTER TABLE `$bangDg` ADD COLUMN `$cotDg` $kieuDg"
        );
    }
    ddlNeuThieu(
        'dg_' . $bangDg . '_idx',
        "SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE()"
      . " AND TABLE_NAME = '$bangDg' AND INDEX_NAME = 'idx_{$bangDg}_province'",
        "ALTER TABLE `$bangDg` ADD KEY `idx_{$bangDg}_province` (`province_code`)"
    );
}
/* ------------------------------------------------------------------ *
 * 13. Xe của khách + phiếu tiếp nhận                       — 000072
 *
 * Phần NẶNG nhất của file: hai bảng mới có khoá ngoại, bốn cột nối thêm vào
 * ba bảng chứng từ đang có dữ liệu thật, và hai màn hình mới cần đăng ký.
 * Mọi câu đều kiểm tra trước khi thêm nên dán lại nhiều lần không báo lỗi.
 *
 * Dữ liệu cũ: dán xong thì các báo giá / hoá đơn / phiếu bảo hành ĐÃ CÓ biển
 * số vẫn chưa nối vào xe (cột `vehicle_id` còn trống) — phần dựng xe từ biển
 * số cũ nằm trong migration PHP, không sinh ra SQL ở đây vì nó phụ thuộc dữ
 * liệu từng máy. Chạy `php migrate.php` thì có; dán SQL tay thì vào màn
 * "Xe của khách" khai xe rồi sửa lại chứng từ nếu cần.
 * ------------------------------------------------------------------ */
echo "\n-- ---------------------------------------------------------------------\n";
echo "-- 13. Xe của khách (`vehicles`) + phiếu tiếp nhận (`receptions`).\n";
echo "--\n";
echo "-- Mô hình: 1 khách (partners) -> nhiều XE -> mỗi xe nhiều PHIẾU TIẾP NHẬN\n";
echo "-- -> mỗi phiếu nhiều chứng từ. Trước đây biển số là chữ gõ tay rời rạc\n";
echo "-- trên từng chứng từ, không có bản ghi xe nào nối chúng lại.\n";
echo "--\n";
echo "-- Biển số (đã chuẩn hoá) là DUY NHẤT; số khung để trống được nhưng đã ghi\n";
echo "-- thì không trùng. Xoá khách thì xe còn lại (SET NULL); xoá xe còn phiếu\n";
echo "-- thì bị chặn (RESTRICT).\n";
echo "-- ---------------------------------------------------------------------\n\n";

echo <<<'SQL_VEHICLES'
CREATE TABLE IF NOT EXISTS `vehicles` (
  `id`            INT NOT NULL AUTO_INCREMENT,
  `partner_id`    INT DEFAULT NULL,
  `bien_so`       VARCHAR(20) NOT NULL,
  `bien_so_chuan` VARCHAR(20) NOT NULL,
  `so_khung`      VARCHAR(30) DEFAULT NULL,
  `so_may`        VARCHAR(30) DEFAULT NULL,
  `brand_id`      INT DEFAULT NULL,
  `model_id`      INT DEFAULT NULL,
  `car_year_id`   INT DEFAULT NULL,
  `hang_xe`       VARCHAR(60) DEFAULT NULL,
  `model_xe`      VARCHAR(60) DEFAULT NULL,
  `nam_sx`        SMALLINT DEFAULT NULL,
  `phien_ban`     VARCHAR(60) DEFAULT NULL,
  `mau_xe`        VARCHAR(40) DEFAULT NULL,
  `so_km`         INT DEFAULT NULL,
  `ghi_chu`       VARCHAR(255) DEFAULT NULL,
  `status`        TINYINT(1) NOT NULL DEFAULT 1,
  `create_at`     DATETIME DEFAULT NULL,
  `update_at`     DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_vehicles_bien_so` (`bien_so_chuan`),
  UNIQUE KEY `uq_vehicles_so_khung` (`so_khung`),
  KEY `idx_vehicles_partner` (`partner_id`),
  KEY `idx_vehicles_model` (`model_id`),
  CONSTRAINT `fk_vehicles_partner` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_vehicles_brand`   FOREIGN KEY (`brand_id`) REFERENCES `car_brands` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_vehicles_model`   FOREIGN KEY (`model_id`) REFERENCES `car_models` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_vehicles_year`    FOREIGN KEY (`car_year_id`) REFERENCES `car_years` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `receptions` (
  `id`            INT NOT NULL AUTO_INCREMENT,
  `reception_no`  VARCHAR(50) NOT NULL,
  `vehicle_id`    INT NOT NULL,
  `partner_id`    INT DEFAULT NULL,
  `garage_id`     INT DEFAULT NULL,
  `ngay_vao`      DATE NOT NULL,
  `ngay_ra`       DATE DEFAULT NULL,
  `km_vao`        INT DEFAULT NULL,
  `km_ra`         INT DEFAULT NULL,
  `tinh_trang_xe` TEXT,
  `yeu_cau_khach` TEXT,
  `co_van_id`     INT DEFAULT NULL,
  `co_van`        VARCHAR(150) DEFAULT NULL,
  `status`        VARCHAR(20) NOT NULL DEFAULT 'tiep_nhan',
  `note`          VARCHAR(255) DEFAULT NULL,
  `created_by`    INT DEFAULT NULL,
  `create_at`     DATETIME DEFAULT NULL,
  `update_at`     DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_receptions_no` (`reception_no`),
  KEY `idx_receptions_vehicle` (`vehicle_id`),
  KEY `idx_receptions_status` (`status`, `ngay_vao`),
  CONSTRAINT `fk_receptions_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_receptions_partner` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_receptions_garage`  FOREIGN KEY (`garage_id`) REFERENCES `garages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_receptions_covan`   FOREIGN KEY (`co_van_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SQL_VEHICLES;

/* Cột nối + khoá ngoại trên ba bảng chứng từ, và members.partner_id */
$noiCot = [
    'quotations'        => ['vehicle_id', 'reception_id'],
    'sales_invoices'    => ['vehicle_id', 'reception_id'],
    'warranty_requests' => ['vehicle_id', 'reception_id'],
    'members'           => ['partner_id'],
];
$noiFk = [
    'quotations'        => ['vehicle_id' => 'vehicles', 'reception_id' => 'receptions'],
    'sales_invoices'    => ['vehicle_id' => 'vehicles', 'reception_id' => 'receptions'],
    'warranty_requests' => ['vehicle_id' => 'vehicles', 'reception_id' => 'receptions'],
    'members'           => ['partner_id' => 'partners'],
];
foreach ($noiCot as $bangNoi => $cotDs){
    foreach ($cotDs as $cotNoi){
        ddlNeuThieu(
            'xe_' . $bangNoi . '_' . $cotNoi,
            "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()"
          . " AND TABLE_NAME = '$bangNoi' AND COLUMN_NAME = '$cotNoi'",
            "ALTER TABLE `$bangNoi` ADD COLUMN `$cotNoi` INT DEFAULT NULL"
        );
        ddlNeuThieu(
            'xe_' . $bangNoi . '_' . $cotNoi . '_idx',
            "SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE()"
          . " AND TABLE_NAME = '$bangNoi' AND INDEX_NAME = 'idx_{$bangNoi}_{$cotNoi}'",
            "ALTER TABLE `$bangNoi` ADD KEY `idx_{$bangNoi}_{$cotNoi}` (`$cotNoi`)"
        );
    }
}
foreach ($noiFk as $bangNoi => $map){
    foreach ($map as $cotNoi => $bangDich){
        $ten = 'fk_' . $bangNoi . '_' . str_replace('_id', '', $cotNoi);
        ddlNeuThieu(
            'xe_' . $bangNoi . '_' . $cotNoi . '_fk',
            "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE()"
          . " AND TABLE_NAME = '$bangNoi' AND CONSTRAINT_NAME = '$ten'",
            "ALTER TABLE `$bangNoi` ADD CONSTRAINT `$ten` FOREIGN KEY (`$cotNoi`)"
          . " REFERENCES `$bangDich` (`id`) ON DELETE SET NULL"
        );
    }
}

/* Hai màn hình mới + quyền — đọc trạng thái HIỆN TẠI của máy này */
echo "-- Khai hai man hinh moi va cap quyen (doc tu may nay)\n";
foreach (['vehicles' => 'Xe của khách', 'receptions' => 'Phiếu tiếp nhận'] as $linkXe => $tenXe){
    printf("INSERT INTO `modules` (`name`,`link`,`create_at`)\n"
         . "  SELECT %s, %s, %s FROM DUAL\n"
         . "  WHERE NOT EXISTS (SELECT 1 FROM `modules` x WHERE x.`link` = %s);\n",
        q($tenXe), q($linkXe), q($now), q($linkXe));
}
$dsQuyenXe = $db->query(
    "SELECT g.`name` AS nhom, m.`link` AS link, p.`role` AS role
       FROM `permissions` p
       JOIN `groups` g  ON g.`id` = p.`group_id`
       JOIN `modules` m ON m.`id` = p.`module_id`
      WHERE m.`link` IN ('vehicles', 'receptions')
      ORDER BY m.`link`, g.`name`, p.`role`"
)->fetchAll(PDO::FETCH_ASSOC);
foreach ($dsQuyenXe as $r){
    printf("INSERT INTO `permissions` (`module_id`,`group_id`,`role`)\n"
         . "  SELECT m.`id`, g.`id`, %s\n"
         . "    FROM `modules` m JOIN `groups` g\n"
         . "   WHERE m.`link` = %s AND g.`name` = %s\n"
         . "     AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `permissions`) p\n"
         . "                      WHERE p.`module_id` = m.`id` AND p.`group_id` = g.`id` AND p.`role` = %s);\n",
        q($r['role']), q($r['link']), q($r['nhom']), q($r['role']));
}
printf("\n-- (tong %d dong quyen cho hai man hinh moi)\n\n", count($dsQuyenXe));
/* ------------------------------------------------------------------ *
 * 14. Đổi tên màn "Cấu hình website" -> "Cấu hình chung"   — 000073
 * ------------------------------------------------------------------ */
echo "\n-- 14. Màn admin/settings giữ hotline, mã số thuế, ngân hàng... — không riêng\n";
echo "-- website nữa, nên đổi tên hiển thị. Đường dẫn và quyền giữ nguyên.\n";
printf("UPDATE `modules` SET `name` = %s WHERE `link` = %s;\n\n", q('Cấu hình chung'), q('settings'));

/* ------------------------------------------------------------------ *
 * 15. Đồng bộ collation bảng xe / phiếu tiếp nhận          — 000074
 *
 * Phòng khi bảng đã lỡ được tạo với collation khác (vd. MariaDB tự gán
 * utf8mb4_general_ci): chỉ chuyển khi CHƯA là utf8mb4_unicode_ci.
 * ------------------------------------------------------------------ */
echo "\n-- 15. Đồng bộ collation bảng xe / phiếu tiếp nhận với các bảng còn lại.\n";
echo "-- Lệch collation thì so chuỗi giữa hai bảng báo 'Illegal mix of collations'.\n\n";
foreach (['vehicles', 'receptions'] as $bangCo){
    ddlNeuThieu(
        'col_' . $bangCo,
        "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()"
      . " AND TABLE_NAME = '$bangCo' AND TABLE_COLLATION = 'utf8mb4_unicode_ci'",
        "ALTER TABLE `$bangCo` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
    );
}

/* 16. Collation mặc định của CSDL — 000075. Không ghi tên CSDL: câu áp cho
   CSDL đang chọn trong phpMyAdmin, tên CSDL trên server có thể khác local. */
echo "
-- 16. Collation mac dinh cua CSDL: bang tao sau khong ghi collation se nhan cai nay.
";
echo "ALTER DATABASE CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

";

/* ------------------------------------------------------------------ *
 * 17. Gara độc lập — nền                                     — 000076
 *
 * Cột mới để NULL được: code của các bước sau mới tự ghi gara, đặt NOT NULL
 * bây giờ là form thêm đối tượng / phiếu nhập trên server sập.
 * Danh sách màn chỉ Tân Phát CHÉP từ máy này (đã migrate), không viết cứng.
 * ------------------------------------------------------------------ */
echo "\n-- 17. Gara doc lap — nen (000076)\n\n";

$bang17 = [
    'partners'            => 'fk_partner_garage',
    'customer_groups'     => 'fk_cgroup_garage',
    'vehicles'            => 'fk_vehicle_garage',
    'warranty_requests'   => 'fk_warranty_garage',
    'warranty_handovers'  => 'fk_handover_garage',
    'goods_receipts'      => 'fk_receipt_garage',
    'goods_issues'        => 'fk_issue_garage',
    'stock_takes'         => 'fk_take_garage',
    'warehouse_transfers' => 'fk_transfer_garage',
];
foreach ($bang17 as $bang => $fk){
    ddlNeuThieu(
        'g17_' . $bang,
        "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()"
      . " AND TABLE_NAME = '$bang' AND COLUMN_NAME = 'garage_id'",
        "ALTER TABLE `$bang` ADD COLUMN `garage_id` INT DEFAULT NULL, ADD KEY `idx_{$bang}_garage` (`garage_id`),"
      . " ADD CONSTRAINT `$fk` FOREIGN KEY (`garage_id`) REFERENCES `garages` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE"
    );
}

echo "-- Chung tu kho lay gara THEO KHO cua no\n";
foreach (['goods_receipts' => 'warehouse_id', 'goods_issues' => 'warehouse_id',
          'stock_takes' => 'warehouse_id', 'warehouse_transfers' => 'from_warehouse_id'] as $bang => $cotKho){
    echo "UPDATE `$bang` x JOIN `warehouses` w ON w.`id` = x.`$cotKho` SET x.`garage_id` = w.`garage_id`"
       . " WHERE x.`garage_id` IS NULL AND w.`garage_id` IS NOT NULL;\n";
}
echo "\n-- Con lai ve gara tong\n";
foreach (array_merge(array_keys($bang17), ['warehouses', 'users', 'quotations', 'sales_invoices', 'receptions']) as $bang){
    echo "UPDATE `$bang` SET `garage_id` = (SELECT g.`id` FROM `garages` g WHERE g.`is_master` = 1 ORDER BY g.`id` LIMIT 1)"
       . " WHERE `garage_id` IS NULL;\n";
}

echo "\n-- Man chi Tan Phat\n";
ddlNeuThieu(
    'g17_chi_tp',
    "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()"
  . " AND TABLE_NAME = 'modules' AND COLUMN_NAME = 'chi_tan_phat'",
    "ALTER TABLE `modules` ADD COLUMN `chi_tan_phat` TINYINT(1) NOT NULL DEFAULT 0"
);
$dsChiTp = $db->query("SELECT `link` FROM `modules` WHERE `chi_tan_phat` = 1 ORDER BY `link`")->fetchAll(PDO::FETCH_COLUMN);
if (!empty($dsChiTp)){
    echo "UPDATE `modules` SET `chi_tan_phat` = 1 WHERE `link` IN (" . implode(', ', array_map('q', $dsChiTp)) . ");\n\n";
}

echo "-- Thong tin gara de in len phieu\n";
foreach (['tax_code' => 'VARCHAR(30)', 'email' => 'VARCHAR(150)', 'logo' => 'VARCHAR(255)'] as $c => $kieu){
    ddlNeuThieu(
        'g17_gara_' . $c,
        "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()"
      . " AND TABLE_NAME = 'garages' AND COLUMN_NAME = '$c'",
        "ALTER TABLE `garages` ADD COLUMN `$c` $kieu DEFAULT NULL"
    );
}

echo "-- Ten gara mau — chi doi khi con dung ten cu\n";
foreach (['DMSG' => ['Tân Phát Sài Gòn', 'Gara mẫu Sài Gòn'], 'DMDN' => ['Tân Phát Đà Nẵng', 'Gara mẫu Đà Nẵng']] as $ma => $ten){
    printf("UPDATE `garages` SET `name` = %s WHERE `code` = %s AND `name` = %s;\n", q($ten[1]), q($ma), q($ten[0]));
}
echo "\n";

/* ------------------------------------------------------------------ *
 * 18. Gara độc lập — khách và xe                             — 000077
 *
 * Chuyển khách / xe cũ bằng SQL: mã khách dựng từ id tài khoản (KH-M00012)
 * nên chạy lại vẫn ra đúng mã đó, không tạo trùng. (migrate.php đánh mã
 * KH-0001... liền số — hai cách cho hai mã khác nhau, cùng đúng.)
 * ------------------------------------------------------------------ */
echo "\n-- 18. Gara doc lap — khach va xe (000077)\n\n";
$tongSql = "(SELECT g.`id` FROM `garages` g WHERE g.`is_master` = 1 ORDER BY g.`id` LIMIT 1)";

ddlNeuThieu('g18_email',
    "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'partners' AND COLUMN_NAME = 'email'",
    "ALTER TABLE `partners` ADD COLUMN `email` VARCHAR(150) DEFAULT NULL AFTER `phone`");

echo "CREATE TABLE IF NOT EXISTS `garage_settings` (\n"
   . "  `id` INT AUTO_INCREMENT PRIMARY KEY,\n"
   . "  `garage_id` INT NOT NULL,\n"
   . "  `skey` VARCHAR(100) NOT NULL,\n"
   . "  `svalue` TEXT DEFAULT NULL,\n"
   . "  `update_at` DATETIME DEFAULT NULL,\n"
   . "  UNIQUE KEY `uq_gs_key` (`garage_id`, `skey`),\n"
   . "  CONSTRAINT `fk_gs_garage` FOREIGN KEY (`garage_id`) REFERENCES `garages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE\n"
   . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";

echo "-- Man Tai khoan website (chi Tan Phat) + quyen, chep tu may nay\n";
printf("INSERT INTO `modules` (`name`, `link`, `chi_tan_phat`, `create_at`)\n"
     . "  SELECT %s, 'tai-khoan-web', 1, %s FROM DUAL\n"
     . "  WHERE NOT EXISTS (SELECT 1 FROM `modules` x WHERE x.`link` = 'tai-khoan-web');\n",
     q('Tài khoản website'), q($now));
echo "UPDATE `modules` SET `chi_tan_phat` = 1 WHERE `link` = 'tai-khoan-web';\n";
foreach ($db->query("SELECT g.`name` AS nhom, p.`role` FROM `permissions` p
                       JOIN `groups` g ON g.`id` = p.`group_id` JOIN `modules` m ON m.`id` = p.`module_id`
                      WHERE m.`link` = 'tai-khoan-web' ORDER BY g.`name`, p.`role`")->fetchAll(PDO::FETCH_ASSOC) as $r){
    printf("INSERT INTO `permissions` (`module_id`,`group_id`,`role`)\n"
         . "  SELECT m.`id`, g.`id`, %s FROM `modules` m JOIN `groups` g\n"
         . "   WHERE m.`link` = 'tai-khoan-web' AND g.`name` = %s\n"
         . "     AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `permissions`) p\n"
         . "                      WHERE p.`module_id` = m.`id` AND p.`group_id` = g.`id` AND p.`role` = %s);\n",
        q($r['role']), q($r['nhom']), q($r['role']));
}

echo "\n-- Khach vang lai cu (tai khoan khong email, hoac co xe o bang cu) -> doi tuong loai khach\n";
echo "INSERT INTO `partners` (`code`, `name`, `type`, `phone`, `email`, `address`, `province_code`, `province_name`,\n"
   . "                        `ward_code`, `ward_name`, `status`, `sort_order`, `garage_id`, `create_at`)\n"
   . "  SELECT CONCAT('KH-M', LPAD(m.`id`, 5, '0')), IF(m.`name` IS NULL OR m.`name` = '', CONCAT('Khach ', m.`id`), m.`name`),\n"
   . "         'customer', NULLIF(m.`phone`, ''), NULLIF(m.`email`, ''), NULLIF(m.`address`, ''), m.`province_code`, m.`province_name`,\n"
   . "         m.`ward_code`, m.`ward_name`, m.`status`, 0, $tongSql, " . q($now) . "\n"
   . "    FROM `members` m\n"
   . "   WHERE m.`partner_id` IS NULL\n"
   . "     AND (m.`email` IS NULL OR m.`email` = '' OR EXISTS (SELECT 1 FROM `member_vehicles` v WHERE v.`member_id` = m.`id`))\n"
   . "     AND NOT EXISTS (SELECT 1 FROM (SELECT `code`, `garage_id` FROM `partners`) p\n"
   . "                      WHERE p.`code` = CONCAT('KH-M', LPAD(m.`id`, 5, '0')) AND p.`garage_id` = $tongSql);\n";
echo "UPDATE `members` m JOIN `partners` p ON p.`code` = CONCAT('KH-M', LPAD(m.`id`, 5, '0')) AND p.`garage_id` = $tongSql\n"
   . "   SET m.`partner_id` = p.`id` WHERE m.`partner_id` IS NULL;\n";
echo "INSERT INTO `vehicles` (`partner_id`, `bien_so`, `bien_so_chuan`, `hang_xe`, `model_xe`, `nam_sx`, `mau_xe`, `so_km`,\n"
   . "                        `ghi_chu`, `status`, `garage_id`, `create_at`)\n"
   . "  SELECT m.`partner_id`, v.`bien_so`, v.`bien_so_chuan`, NULLIF(v.`hang_xe`, ''), NULLIF(v.`model_xe`, ''), v.`nam_sx`,\n"
   . "         NULLIF(v.`mau_xe`, ''), v.`so_km`, NULLIF(v.`ghi_chu`, ''), 1, $tongSql, " . q($now) . "\n"
   . "    FROM `member_vehicles` v JOIN `members` m ON m.`id` = v.`member_id`\n"
   . "   WHERE v.`bien_so_chuan` <> ''\n"
   /* Hai tài khoản cùng khai một biển số: chỉ lấy dòng đầu — biển số không
      được trùng trong một gara. Không dùng GROUP BY: cột không gộp làm
      ONLY_FULL_GROUP_BY (mặc định MySQL 5.7+) báo lỗi. */
   . "     AND v.`id` = (SELECT MIN(v2.`id`) FROM `member_vehicles` v2 WHERE v2.`bien_so_chuan` = v.`bien_so_chuan`)\n"
   . "     AND NOT EXISTS (SELECT 1 FROM (SELECT `garage_id`, `bien_so_chuan` FROM `vehicles`) x\n"
   . "                      WHERE x.`garage_id` = $tongSql AND x.`bien_so_chuan` = v.`bien_so_chuan`);\n\n";

echo "-- Khong trung: tinh TRONG TUNG GARA. Them chi muc moi truoc, bo chi muc cu sau.\n";
foreach ([
    ['partners',           'uq_partners_code',     'uq_partners_gara_code',     '`garage_id`, `code`'],
    ['vehicles',           'uq_vehicles_bien_so',  'uq_vehicles_gara_bien_so',  '`garage_id`, `bien_so_chuan`'],
    ['vehicles',           'uq_vehicles_so_khung', 'uq_vehicles_gara_so_khung', '`garage_id`, `so_khung`'],
    ['receptions',         'uq_receptions_no',     'uq_receptions_gara_no',     '`garage_id`, `reception_no`'],
    ['warranty_requests',  'uq_warranty_no',       'uq_warranty_gara_no',       '`garage_id`, `request_no`'],
    ['warranty_handovers', 'uq_handover_no',       'uq_handover_gara_no',       '`garage_id`, `handover_no`'],
] as $d){
    list($bang, $cu, $moi, $cotMoi) = $d;
    $coIdx = function($ten) use ($bang){
        return "SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE()"
             . " AND TABLE_NAME = '$bang' AND INDEX_NAME = '$ten'";
    };
    ddlNeuThieu('g18_' . $moi, $coIdx($moi), "ALTER TABLE `$bang` ADD UNIQUE KEY `$moi` ($cotMoi)");
    // Bỏ chỉ mục cũ: chạy khi CÒN (đếm > 0) — đảo điều kiện của ddlNeuThieu
    printf("SET @%s = (SELECT IF((%s) = 0, 'SELECT 1', %s));\n"
         . "PREPARE st_%s FROM @%s; EXECUTE st_%s; DEALLOCATE PREPARE st_%s;\n\n",
        'g18x_' . $cu, $coIdx($cu), q("ALTER TABLE `$bang` DROP INDEX `$cu`"),
        'g18x_' . $cu, 'g18x_' . $cu, 'g18x_' . $cu, 'g18x_' . $cu);
}

/* ------------------------------------------------------------------ *
 * Đánh dấu đã chạy — để sau này lỡ gọi migrate.php cũng không chạy lại
 * ------------------------------------------------------------------ */
echo "\n-- ---------------------------------------------------------------------\n";
echo "-- Đánh dấu các migration là ĐÃ CHẠY.\n";
echo "--\n";
echo "-- Cần thiết: chạy SQL bằng tay thì bảng `migrations` không biết, nên nếu\n";
echo "-- sau này có ai gọi `php migrate.php` nó sẽ chạy lại. Chạy lại đều vô\n";
echo "-- hại, nhưng ghi nhận cho đúng vẫn hơn.\n";
echo "-- ---------------------------------------------------------------------\n";

$batch = (int) $db->query("SELECT COALESCE(MAX(batch),0) FROM migrations")->fetchColumn();
foreach ([
    '2026_08_26_000059_go_ma_hoa_html_bi_chong_lop',
    '2026_08_26_000060_gan_anh_minh_hoa_vao_csdl',
    '2026_08_26_000061_them_module_quan_ly_module',
    '2026_08_27_000062_them_bang_xe_cua_khach',
    '2026_09_03_000063_them_bang_gara',
    '2026_09_03_000064_khach_vang_lai_khong_can_email',
    '2026_09_03_000065_danh_muc_rieng_cua_gara',
    '2026_09_09_000066_bien_so_so_km_tren_chung_tu',
    '2026_09_09_000067_bien_so_so_km_tren_bao_hanh',
    '2026_09_10_000068_cap_quyen_manager_va_staff',
    '2026_09_15_000070_phieu_bao_tri',
    '2026_09_16_000071_tinh_phuong_cho_doi_tuong',
    '2026_09_16_000072_xe_va_phieu_tiep_nhan',
    '2026_09_17_000073_doi_ten_cau_hinh_chung',
    '2026_09_17_000074_dong_bo_collation_xe_va_phieu',
    '2026_09_17_000075_collation_mac_dinh_csdl',
    '2026_09_22_000076_nen_gara_doc_lap',
    '2026_09_22_000077_khach_va_xe_theo_gara',
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
