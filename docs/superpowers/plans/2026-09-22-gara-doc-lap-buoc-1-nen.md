# Gara độc lập — Bước 1: Nền — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Dựng phần nền cho mô hình "gara độc lập": cột `garage_id` trên mọi bảng riêng gara, gara làm việc lấy theo tài khoản (bỏ ô đổi gara), không có gara thì không vào được trang quản trị, lớp Model gốc biết chặn theo gara, và các màn "chỉ Tân Phát" bị giấu khỏi gara khác.

**Architecture:** Một hàm duy nhất `gara_hien_tai()` quyết định gara làm việc (quản trị = gara của tài khoản; website / dòng lệnh = gara tổng). Lớp `core/Model.php` có cờ `$_theoGara`; bật lên thì đọc / sửa / xoá theo ID và thêm mới tự gắn điều kiện gara. `RoleMiddleware` chặn module có cờ `modules.chi_tan_phat` với tài khoản không thuộc gara tổng — menu trái dùng chung chốt chặn đó qua `route()`. Bước này **chưa bật cờ cho model thật nào**; các bước 2-4 bật cho nhóm của mình.

**Tech Stack:** PHP 8 thuần (MVC tự viết), MySQL 8 local / MySQL 5.7-MariaDB server, test bằng script PHP (`tests/*.php`, helper `ok()/section()/summary()`), HTTP thật qua Apache `http://localhost:88/tan-phat`.

**Spec:** `docs/superpowers/specs/2026-09-22-gara-doc-lap-tren-nen-tang-design.md`

## Global Constraints

- PHP chạy bằng `C:\xampp\php\php.exe` (không có `php` trong PATH). MySQL CLI hỏng — truy vấn qua PDO.
- Mọi bảng / cột mới: `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci` (CollationTest chặn).
- Không có `SHOW COLUMNS ... LIKE ?` (MySQL báo 1064) — lấy hết cột rồi lọc bằng PHP.
- Template engine không có `@elseif`; layout (`app/views/layouts/**`) là PHP thuần, không dùng `@if` / `{{ }}`.
- `Response::redirect()` có `exit`. Flash `old` còn tới lần mở form kế tiếp — test phải GET lại form sau một POST lỗi.
- Test mới phải đăng ký vào `tests/run.php`. Tài khoản tạm dùng email `zz-cl-*@local.test`, gara tạm mã `ZZG*`; dọn sạch cả khi test chết giữa chừng.
- Thông báo cho người dùng viết tiếng Việt có dấu; tên test / nhãn `ok()` viết không dấu (theo nếp các test đang có).
- Migration chạy lại nhiều lần không lỗi; SQL triển khai (`tools/xuat-sql-thay-doi.php`) cũng vậy.
- Commit chỉ trên máy, **không push** khi anh chưa bảo.

## File Structure

| File | Việc |
|---|---|
| `database/migrations/2026_09_22_000076_nen_gara_doc_lap.php` (tạo) | Cột `garage_id` cho 9 bảng, gán dữ liệu cũ, `modules.chi_tan_phat`, cột thông tin gara, đổi tên gara mẫu |
| `app/models/GaragesModel.php` (sửa) | `dangDungODau()` đếm đủ 15 bảng riêng gara |
| `tools/xuat-sql-thay-doi.php` (sửa) | Phần 17 = SQL của 000076 |
| `tools/tao-du-lieu-gara.php`, `app/views/admin/garages/add.php` (sửa) | Tên gara mẫu mới |
| `app/helpers/functions.php` (sửa) | `gara_hien_tai()` viết lại, thêm `la_request_quan_tri()`, `nguoi_dang_nhap_id()`, `la_gara_tong()` |
| `app/views/layouts/admin/header.php`, `routes/web.php`, `app/controllers/admin/Garages.php`, `app/providers/AppServiceProvider.php` (sửa) | Bỏ ô đổi gara |
| `app/controllers/Auth.php`, `app/middlewares/AuthMiddleware.php` (sửa) | Đóng khi thiếu gara |
| `app/controllers/admin/Users.php`, `app/views/admin/users/{add,edit,lists}.php` (sửa) | Tài khoản bắt buộc có gara |
| `core/Model.php` (sửa) | `$_theoGara`, `epGara()`, `garaLoc()`, `dkGara()` |
| `app/middlewares/RoleMiddleware.php` (sửa) | Chặn module `chi_tan_phat` |
| `tests/CachLyGaraTest.php` (tạo) | Test cách ly gara — bước 1 |
| `tests/NhieuGaraTest.php`, `tests/NhanVienGaraTest.php`, `tests/run.php` (sửa) | Theo mô hình mới |

---

### Task 1: Migration 000076 + khung test cách ly

**Files:**
- Create: `database/migrations/2026_09_22_000076_nen_gara_doc_lap.php`
- Create: `tests/CachLyGaraTest.php`
- Modify: `app/models/GaragesModel.php:73-99` (`dangDungODau`)
- Modify: `tools/tao-du-lieu-gara.php:145` (và dòng tạo DMDN), `app/views/admin/garages/add.php:23`
- Modify: `tests/NhanVienGaraTest.php:322`, `tests/run.php`

**Interfaces:**
- Produces: cột `garage_id INT NULL` (FK RESTRICT) trên `partners, customer_groups, vehicles, warranty_requests, warranty_handovers, goods_receipts, goods_issues, stock_takes, warehouse_transfers`; `modules.chi_tan_phat TINYINT(1) NOT NULL DEFAULT 0`; `garages.tax_code / email / logo`. Trong `CachLyGaraTest.php`: biến `$pdo, $mot, $so, $cot, $GA, $GB, $GK, $TP, $goc, $base`, hai mốc chèn `// ==== [CLI] ====` và `// ==== [HTTP] ====`.

- [ ] **Step 1: Viết test (đỏ)**

Tạo `tests/CachLyGaraTest.php`:

```php
<?php
/**
 * Test CÁCH LY GARA — các gara độc lập không thấy dữ liệu của nhau.
 *
 * Chạy:  C:\xampp\php\php.exe tests\CachLyGaraTest.php
 *
 * Thiết kế: docs/superpowers/specs/2026-09-22-gara-doc-lap-tren-nen-tang-design.md
 *
 * Dựng ba gara tạm: ZZGA, ZZGB (đang hoạt động) và ZZGK (bị khoá), cùng các
 * tài khoản tạm `zz-cl-*@local.test`. Dọn sạch khi xong — kể cả khi test chết
 * giữa chừng (register_shutdown_function).
 *
 * Bước 1 (nền) có: migration 000076, gara làm việc theo tài khoản, đóng khi
 * thiếu gara, lớp Model gốc, cờ "chỉ Tân Phát". Các bước sau phủ thêm từng
 * nhóm màn hình vào đây.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc  = __DIR__ . '/../';
$base = 'http://localhost:88/tan-phat';

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

$mot = function($sql, $b = []) use ($pdo){ $st = $pdo->prepare($sql); $st->execute($b); return $st->fetch(PDO::FETCH_ASSOC) ?: []; };
$so  = function($sql, $b = []) use ($pdo){ $st = $pdo->prepare($sql); $st->execute($b); return (int) $st->fetchColumn(); };
$cot = function($bang) use ($pdo){ return $pdo->query("SHOW COLUMNS FROM `$bang`")->fetchAll(PDO::FETCH_COLUMN); };

/* Dọn rác — chạy trước (lần trước chết giữa chừng) và khi kết thúc */
$donSach = function() use ($pdo, $cot){
    $pdo->exec("DELETE t FROM login_tokens t JOIN users u ON u.id = t.user_id WHERE u.email LIKE 'zz-cl-%@local.test'");
    $pdo->exec("DELETE FROM users WHERE email LIKE 'zz-cl-%@local.test'");
    $pdo->exec("DELETE FROM goods_receipts WHERE receipt_no LIKE 'ZZCL-%'");
    $ds = "SELECT id FROM (SELECT id FROM garages WHERE code LIKE 'ZZG%') t";
    foreach (['partners', 'warehouses', 'garage_part_prices'] as $b){
        if (in_array('garage_id', $cot($b), true)) $pdo->exec("DELETE FROM `$b` WHERE garage_id IN ($ds)");
    }
    $pdo->exec("DELETE FROM warehouses WHERE code LIKE 'ZZCL-%'");
    $pdo->exec("DELETE FROM garages WHERE code LIKE 'ZZG%'");
};
$donSach();
register_shutdown_function($donSach);

$taoGara = function($ma, $ten, $trangThai = 1) use ($pdo){
    $pdo->prepare("INSERT INTO garages (code, name, is_master, status, sort_order, create_at)
                   VALUES (?, ?, 0, ?, 99, NOW())")->execute([$ma, $ten, $trangThai]);
    return (int) $pdo->lastInsertId();
};
$TP = $so("SELECT id FROM garages WHERE is_master = 1 ORDER BY id LIMIT 1");
if (!$TP){ echo "\n[SKIP] Chua co gara tong.\n"; exit(summary()); }
$GA = $taoGara('ZZGA', 'ZZ Gara A');
$GB = $taoGara('ZZGB', 'ZZ Gara B');
$GK = $taoGara('ZZGK', 'ZZ Gara Khoa', 0);

// ---------------------------------------------------------------------------
section('Migration 000076 — nen gara doc lap');

$bangMoi = [
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
$bangCu = ['warehouses', 'users', 'quotations', 'sales_invoices', 'receptions'];

$mg = glob($goc . 'database/migrations/*_nen_gara_doc_lap.php');
ok(count($mg) === 1, 'Co migration nen gara doc lap');

if (count($mg) === 1){
    /* Chứng từ kho lấy gara THEO KHO: dựng một kho của gara B và một phiếu
       nhập chưa có gara trên kho đó, chạy lại migration rồi xem phiếu về đâu. */
    $pdo->prepare("INSERT INTO warehouses (code, name, is_default, sort_order, status, garage_id, create_at)
                   VALUES ('ZZCL-KHOB', 'ZZ Kho B', 0, 99, 1, ?, NOW())")->execute([$GB]);
    $khoB = (int) $pdo->lastInsertId();
    if (in_array('garage_id', $cot('goods_receipts'), true)){
        $pdo->prepare("INSERT INTO goods_receipts (receipt_no, warehouse_id, receipt_date, garage_id, create_at)
                       VALUES ('ZZCL-PN1', ?, CURDATE(), NULL, NOW())")->execute([$khoB]);
    }

    $db = new \App\core\Database();
    $m  = require $mg[0];
    $m->setDb($db);
    ob_start();
    try { $m->up(); $loi = ''; } catch (\Throwable $e){ $loi = $e->getMessage(); }
    ob_end_clean();
    ok($loi === '', 'Chay lai migration khong loi (chay nhieu lan duoc)', $loi);

    foreach (array_merge(array_keys($bangMoi), $bangCu) as $b){
        $co = in_array('garage_id', $cot($b), true);
        ok($co, "`$b` co cot garage_id");
        $trong = $co ? $so("SELECT COUNT(*) FROM `$b` WHERE garage_id IS NULL") : -1;
        ok($trong === 0, "Migration gan het dong cua `$b` vao mot gara", "Con $trong dong garage_id IS NULL");
    }

    $pn = $mot("SELECT garage_id FROM goods_receipts WHERE receipt_no = 'ZZCL-PN1'");
    ok(!empty($pn) && (int) $pn['garage_id'] === $GB, 'Phieu nhap lay gara THEO KHO cua no, khong do het ve gara tong',
       'Dang: ' . json_encode($pn));

    $luat = [];
    foreach ($pdo->query("SELECT TABLE_NAME, DELETE_RULE FROM information_schema.REFERENTIAL_CONSTRAINTS
                          WHERE CONSTRAINT_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = 'garages'") as $r){
        $luat[$r['TABLE_NAME']] = $r['DELETE_RULE'];
    }
    foreach ($bangMoi as $b => $fk){
        ok(isset($luat[$b]) && $luat[$b] === 'RESTRICT', "Khoa ngoai `$b` -> garages la RESTRICT",
           'SET NULL thi xoa gara xong chung tu thanh "khong cua ai" — khong ai thay nua');
    }

    $coCo = in_array('chi_tan_phat', $cot('modules'), true);
    ok($coCo, '`modules` co cot chi_tan_phat');
    $dsCo = $coCo ? $pdo->query("SELECT link FROM modules WHERE chi_tan_phat = 1")->fetchAll(PDO::FETCH_COLUMN) : [];
    $mongDoi = ['attributes', 'banners', 'car-body-types', 'car-brands', 'car-colors', 'car-fuels', 'car-models',
                'car-years', 'chat', 'contact-messages', 'du-an', 'galleries', 'garages', 'groups', 'menus', 'modules',
                'news', 'news-categories', 'newsletter', 'orders', 'part-categories', 'product-brands',
                'product-manufacturers', 'product-origins', 'product-units', 'products', 'reviews', 'services',
                'settings', 'thong-ke'];
    sort($dsCo); sort($mongDoi);
    ok($dsCo === $mongDoi, 'Dung ' . count($mongDoi) . ' man chi Tan Phat',
       'Thua: ' . implode(',', array_diff($dsCo, $mongDoi)) . ' | Thieu: ' . implode(',', array_diff($mongDoi, $dsCo)));
    foreach (['customers', 'partners', 'quotations', 'vehicles', 'receptions', 'garage-catalog', 'users'] as $l){
        ok(!in_array($l, $dsCo, true), "`$l` la man cua gara, KHONG phai chi Tan Phat");
    }

    foreach (['tax_code', 'email', 'logo'] as $c){
        ok(in_array($c, $cot('garages'), true), "`garages` co cot $c (in len phieu)");
    }
    ok($so("SELECT COUNT(*) FROM garages WHERE code IN ('DMSG', 'DMDN') AND name LIKE 'Tân Phát%'") === 0,
       'Hai gara mau khong con mang ten "Tan Phat ..." (khoi bi hieu la chi nhanh)');
}

require_once $goc . 'app/models/GaragesModel.php';
$GM = new GaragesModel();
ok($GM->dangDungODau($GA) === [], 'Gara moi chua co gi thi khong bao rang buoc');
if (in_array('garage_id', $cot('partners'), true)){
    $pdo->prepare("INSERT INTO partners (code, name, type, status, garage_id, create_at)
                   VALUES ('ZZCL-KH1', 'ZZ Khach A', 'customer', 1, ?, NOW())")->execute([$GA]);
    $dung = $GM->dangDungODau($GA);
    ok(isset($dung['đối tượng']) && $dung['đối tượng'] === 1,
       'Gara co doi tuong thi dangDungODau() bao ro — khong de khoa ngoai RESTRICT bao loi CSDL kho hieu',
       json_encode($dung, JSON_UNESCAPED_UNICODE));
}

// ==== [CLI] ====

// ==== [HTTP] ====

exit(summary());
```

Đăng ký vào `tests/run.php` — thêm ngay sau dòng `CollationTest.php`:

```php
    'CachLyGaraTest.php'          => 'HE THONG — gara doc lap: cach ly du lieu giua cac gara',
```

- [ ] **Step 2: Chạy test, phải đỏ**

Run: `C:\xampp\php\php.exe tests\CachLyGaraTest.php`
Expected: `[FAIL] Co migration nen gara doc lap` và `[FAIL] ... dangDungODau() bao ro` không chạy (cột chưa có) — tổng có FAIL.

- [ ] **Step 3: Viết migration**

Tạo `database/migrations/2026_09_22_000076_nen_gara_doc_lap.php`:

```php
<?php
/**
 * GARA ĐỘC LẬP — bước 1: nền.
 *
 * Mô hình đổi từ chuỗi chi nhánh sang NỀN TẢNG: mỗi gara là một doanh nghiệp
 * độc lập, không thấy dữ liệu của nhau (xem
 * docs/superpowers/specs/2026-09-22-gara-doc-lap-tren-nen-tang-design.md).
 *
 * Migration này chỉ chuẩn bị chỗ đứng, CHƯA chặn gì:
 *   1. `garage_id` cho 9 bảng riêng gara chưa có cột này
 *   2. Gán dữ liệu cũ vào gara: chứng từ kho theo KHO của nó, còn lại về gara
 *      tổng. Năm bảng đã có cột từ 000063 / 000072 cũng quét lại dòng NULL.
 *   3. `modules.chi_tan_phat` — màn của riêng Tân Phát (website, kho tổng, đơn
 *      web...). Nhóm quyền dùng chung cho mọi gara, nên quyền của nhóm không
 *      đủ để giấu các màn này khỏi gara khác.
 *   4. Thông tin gara để in lên phiếu: mã số thuế, email, logo
 *   5. Đổi tên hai gara mẫu cho khỏi bị hiểu là chi nhánh của Tân Phát
 *
 * CỘT MỚI ĐỂ NULL ĐƯỢC — cố ý. Code hiện tại chưa ghi `garage_id` cho các bảng
 * này; đặt NOT NULL bây giờ là form thêm đối tượng / phiếu nhập... sập ngay.
 * NOT NULL làm ở bước cuối, khi mọi model đã tự điền gara.
 *
 * KHOÁ NGOẠI RESTRICT, không SET NULL như 000063: gara độc lập mà xoá gara rồi
 * để chứng từ lại "không của ai" thì chứng từ đó không ai thấy được nữa. Màn
 * Quản lý gara đếm trước (GaragesModel::dangDungODau) để báo cho dễ hiểu.
 */

use App\core\Migration;

return new class extends Migration {

    /** Bảng riêng gara CHƯA có `garage_id`: bảng => tên khoá ngoại */
    protected $them = [
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

    /** Đã có `garage_id` từ trước — chỉ quét lại dòng còn NULL */
    protected $daCo = ['warehouses', 'users', 'quotations', 'sales_invoices', 'receptions'];

    /** Chứng từ kho lấy gara theo kho của nó: bảng => cột kho */
    protected $theoKho = [
        'goods_receipts'      => 'warehouse_id',
        'goods_issues'        => 'warehouse_id',
        'stock_takes'         => 'warehouse_id',
        'warehouse_transfers' => 'from_warehouse_id',
    ];

    /** Màn của riêng Tân Phát — gara khác không thấy, dù nhóm có quyền */
    protected $chiTanPhat = [
        'attributes', 'banners', 'car-body-types', 'car-brands', 'car-colors', 'car-fuels',
        'car-models', 'car-years', 'chat', 'contact-messages', 'du-an', 'galleries', 'garages',
        'groups', 'menus', 'modules', 'news', 'news-categories', 'newsletter', 'orders',
        'part-categories', 'product-brands', 'product-manufacturers', 'product-origins',
        'product-units', 'products', 'reviews', 'services', 'settings', 'thong-ke',
    ];

    /** mã gara => [tên cũ, tên mới] */
    protected $doiTen = [
        'DMSG' => ['Tân Phát Sài Gòn', 'Gara mẫu Sài Gòn'],
        'DMDN' => ['Tân Phát Đà Nẵng', 'Gara mẫu Đà Nẵng'],
    ];

    public function up(){
        $tong = $this->db->firstRaw("SELECT `id` FROM `garages` WHERE `is_master` = 1 ORDER BY `id` LIMIT 1");
        if (empty($tong['id'])){
            throw new \RuntimeException('Chua co gara tong (is_master = 1) — chay migration 000063 truoc.');
        }
        $tongId = (int) $tong['id'];

        // 1. Cột + khoá ngoại
        foreach ($this->them as $bang => $fk){
            if (!$this->hasTable($bang) || $this->hasColumn($bang, 'garage_id')) continue;
            $this->run("ALTER TABLE `$bang` ADD COLUMN `garage_id` INT DEFAULT NULL");
            $this->run("ALTER TABLE `$bang` ADD KEY `idx_{$bang}_garage` (`garage_id`)");
            $this->run("ALTER TABLE `$bang` ADD CONSTRAINT `$fk` FOREIGN KEY (`garage_id`)
                        REFERENCES `garages` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE");
            echo "  Da them `$bang`.`garage_id`.\n";
        }

        // 2. Gán dữ liệu cũ — chứng từ kho theo kho trước, còn sót thì về gara tổng
        foreach ($this->theoKho as $bang => $cotKho){
            if (!$this->hasColumn($bang, 'garage_id')) continue;
            $this->db->query("UPDATE `$bang` x JOIN `warehouses` w ON w.`id` = x.`$cotKho`
                                 SET x.`garage_id` = w.`garage_id`
                               WHERE x.`garage_id` IS NULL AND w.`garage_id` IS NOT NULL");
        }
        foreach (array_merge(array_keys($this->them), $this->daCo) as $bang){
            if (!$this->hasTable($bang) || !$this->hasColumn($bang, 'garage_id')) continue;
            $this->db->query("UPDATE `$bang` SET `garage_id` = ? WHERE `garage_id` IS NULL", [$tongId]);
        }
        echo "  Da gan du lieu cu vao gara (chung tu kho theo kho, con lai ve gara tong).\n";

        // 3. Màn chỉ Tân Phát
        if (!$this->hasColumn('modules', 'chi_tan_phat')){
            $this->run("ALTER TABLE `modules` ADD COLUMN `chi_tan_phat` TINYINT(1) NOT NULL DEFAULT 0");
        }
        $dau = implode(',', array_fill(0, count($this->chiTanPhat), '?'));
        $this->db->query("UPDATE `modules` SET `chi_tan_phat` = 1 WHERE `link` IN ($dau)", $this->chiTanPhat);
        echo "  Da danh dau " . count($this->chiTanPhat) . " man chi Tan Phat.\n";

        // 4. Thông tin gara để in lên phiếu
        foreach (['tax_code' => 'VARCHAR(30)', 'email' => 'VARCHAR(150)', 'logo' => 'VARCHAR(255)'] as $c => $kieu){
            if (!$this->hasColumn('garages', $c)){
                $this->run("ALTER TABLE `garages` ADD COLUMN `$c` $kieu DEFAULT NULL");
            }
        }

        // 5. Tên gara mẫu — chỉ đổi khi vẫn còn đúng tên cũ, không đè tên ai đã sửa
        foreach ($this->doiTen as $ma => $ten){
            $this->db->query("UPDATE `garages` SET `name` = ? WHERE `code` = ? AND `name` = ?",
                             [$ten[1], $ma, $ten[0]]);
        }
    }

    public function down(){
        foreach ($this->doiTen as $ma => $ten){
            $this->db->query("UPDATE `garages` SET `name` = ? WHERE `code` = ? AND `name` = ?",
                             [$ten[0], $ma, $ten[1]]);
        }
        foreach (['logo', 'email', 'tax_code'] as $c){
            if ($this->hasColumn('garages', $c)) $this->run("ALTER TABLE `garages` DROP COLUMN `$c`");
        }
        if ($this->hasColumn('modules', 'chi_tan_phat')){
            $this->run("ALTER TABLE `modules` DROP COLUMN `chi_tan_phat`");
        }
        foreach ($this->them as $bang => $fk){
            if (!$this->hasTable($bang) || !$this->hasColumn($bang, 'garage_id')) continue;
            try { $this->run("ALTER TABLE `$bang` DROP FOREIGN KEY `$fk`"); } catch (\Throwable $e){}
            try { $this->run("ALTER TABLE `$bang` DROP KEY `idx_{$bang}_garage`"); } catch (\Throwable $e){}
            $this->run("ALTER TABLE `$bang` DROP COLUMN `garage_id`");
        }
        /* Dòng NULL ở 5 bảng $daCo đã được gán về gara tổng: KHÔNG trả lại NULL.
           Trả lại là làm dữ liệu mất chủ, mà giữ nguyên thì vô hại. */
    }

    /** SHOW COLUMNS rồi lọc bằng PHP — `SHOW COLUMNS ... LIKE ?` bị lỗi 1064 */
    protected function hasColumn($bang, $cot){
        try {
            $rows = $this->db->query("SHOW COLUMNS FROM `$bang`")->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e){ return false; }
        foreach ($rows as $r){
            if (isset($r['Field']) && $r['Field'] === $cot) return true;
        }
        return false;
    }
};
```

- [ ] **Step 4: Sửa `GaragesModel::dangDungODau()`**

Thay khối `foreach ([...] as $bang => $nhan)` và chú thích phía trên hàm bằng:

```php
    /**
     * Gara này còn dữ liệu gì — để báo cho người dùng trước khi xoá.
     *
     * Khoá ngoại của các bảng riêng gara là RESTRICT (000065, 000076): MySQL
     * sẽ từ chối lệnh xoá, nhưng người dùng chỉ thấy một lỗi CSDL khó hiểu.
     * Đếm ở đây để nói thẳng "gara này còn N đối tượng, M phiếu nhập...".
     * Bảng chưa có cột `garage_id` (CSDL chưa migrate) thì bỏ qua.
     */
    public function dangDungODau($id){
        $id  = (int) $id;
        $ket = [];
        foreach ([
            'warehouses'          => 'kho',
            'users'               => 'người dùng',
            'partners'            => 'đối tượng',
            'customer_groups'     => 'nhóm khách',
            'vehicles'            => 'xe',
            'receptions'          => 'phiếu tiếp nhận',
            'quotations'          => 'báo giá',
            'sales_invoices'      => 'hoá đơn',
            'warranty_requests'   => 'phiếu bảo hành / bảo trì',
            'warranty_handovers'  => 'biên bản bàn giao',
            'goods_receipts'      => 'phiếu nhập',
            'goods_issues'        => 'phiếu xuất',
            'stock_takes'         => 'phiếu kiểm kê',
            'warehouse_transfers' => 'phiếu chuyển kho',
            'parts'               => 'hàng riêng',
            'garage_part_prices'  => 'mặt hàng đã chọn',
        ] as $bang => $nhan){
            try {
                $row = $this->table($bang)->select('COUNT(*) AS c')
                            ->where('garage_id', '=', $id)->first();
            } catch (\Throwable $e){
                continue;
            }
            $n = !empty($row['c']) ? (int) $row['c'] : 0;
            if ($n > 0) $ket[$nhan] = $n;
        }
        return $ket;
    }
```

Trong `app/controllers/admin/Garages.php` hàm `delete()`, đổi câu báo lỗi:

```php
            Session::flash('msgError',
                'Không xoá được: gara này đang có ' . implode(', ', $mo)
              . '. Gara đã có dữ liệu thì chỉ khoá được (tắt trạng thái hoạt động), không xoá.');
```

- [ ] **Step 5: Đổi tên gara mẫu ở chỗ khác**

`tools/tao-du-lieu-gara.php`: đổi `'Tân Phát Sài Gòn'` → `'Gara mẫu Sài Gòn'` và `'Tân Phát Đà Nẵng'` → `'Gara mẫu Đà Nẵng'` (mọi chỗ trong file).
`app/views/admin/garages/add.php:23`: `placeholder="VD: Tân Phát Sài Gòn"` → `placeholder="VD: Gara Minh Long"`.
`tests/NhanVienGaraTest.php:322`: `'Tân Phát Sài Gòn'` → `'Gara mẫu Sài Gòn'`.

- [ ] **Step 6: Chạy migration, rồi test phải xanh**

Run: `C:\xampp\php\php.exe migrate.php`
Expected: in `Da them `partners`.`garage_id`.` … `Da danh dau 30 man chi Tan Phat.`

Run: `C:\xampp\php\php.exe tests\CachLyGaraTest.php`
Expected: `PASS: N   FAIL: 0`

- [ ] **Step 7: Thử rollback rồi chạy lại**

Run: `C:\xampp\php\php.exe migrate.php rollback` rồi `C:\xampp\php\php.exe migrate.php`
Expected: không lỗi; chạy lại `tests\CachLyGaraTest.php` vẫn `FAIL: 0`.

- [ ] **Step 8: Commit**

```bash
git add database/migrations/2026_09_22_000076_nen_gara_doc_lap.php tests/CachLyGaraTest.php tests/run.php app/models/GaragesModel.php app/controllers/admin/Garages.php tools/tao-du-lieu-gara.php app/views/admin/garages/add.php tests/NhanVienGaraTest.php
git commit -m "feat(gara-doc-lap): migration nen — garage_id cho 9 bang, man chi Tan Phat"
```

---

### Task 2: SQL triển khai cho 000076

**Files:**
- Modify: `tools/xuat-sql-thay-doi.php` (chú thích đầu file, dòng tiêu đề, thêm phần 17 trước khối "Đánh dấu đã chạy", mảng tên migration)
- Test: `tests/CachLyGaraTest.php` (chèn trước `// ==== [CLI] ====`)

**Interfaces:**
- Consumes: migration 000076 đã chạy trên máy (công cụ đọc trạng thái hiện tại).

- [ ] **Step 1: Viết test (đỏ)** — chèn trước `// ==== [CLI] ====`:

```php
// ---------------------------------------------------------------------------
section('SQL trien khai cho 000076');

$sqlTk = (string) shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($goc . 'tools/xuat-sql-thay-doi.php') . ' --chi-cau-truc');
ok(strpos($sqlTk, "'2026_09_22_000076_nen_gara_doc_lap'") !== false, 'SQL danh dau da chay migration 000076');
foreach ($bangMoi as $b => $fk){
    ok(strpos($sqlTk, "ALTER TABLE `$b` ADD COLUMN `garage_id` INT DEFAULT NULL") !== false
       && strpos($sqlTk, "CONSTRAINT `$fk`") !== false,
       "SQL them garage_id + khoa ngoai cho `$b`");
}
ok(strpos($sqlTk, 'ALTER TABLE `modules` ADD COLUMN `chi_tan_phat`') !== false, 'SQL them cot chi_tan_phat');
ok(preg_match("~UPDATE `modules` SET `chi_tan_phat` = 1 WHERE `link` IN \(([^)]*)\)~", $sqlTk, $mm) === 1
   && substr_count($mm[1], "'") === 2 * 30,
   'SQL bat co cho dung 30 man chi Tan Phat (chep tu may nay)');
ok(strpos($sqlTk, 'UPDATE `goods_receipts` x JOIN `warehouses` w') !== false,
   'SQL gan phieu nhap theo kho truoc khi do ve gara tong');
ok(strpos($sqlTk, 'NOT NULL') === false || strpos($sqlTk, 'MODIFY `garage_id` INT NOT NULL') === false,
   'SQL buoc 1 KHONG dat garage_id NOT NULL', 'Dat som la form cua model chua ghi gara sap');
```

- [ ] **Step 2: Chạy test, phải đỏ**

Run: `C:\xampp\php\php.exe tests\CachLyGaraTest.php`
Expected: FAIL ở các dòng `SQL ...`.

- [ ] **Step 3: Viết phần 17**

Trong `tools/xuat-sql-thay-doi.php`:

(a) Chú thích đầu file, sau dòng `000075`:
```php
 *   000076  gara độc lập — nền: garage_id cho 9 bảng, màn chỉ Tân Phát, thông tin gara
```
(b) Dòng tiêu đề `echo "-- TÂN PHÁT — thay đổi CSDL, tương đương migration 000059 → 000075 (trừ 000069)\n";` đổi `000075` thành `000076`, và thêm sau dòng `echo "-- Phần 13 ...` (dòng thứ hai của phần 13):
```php
echo "-- Phần 17 gara độc lập (nền): garage_id cho 9 bảng, gán dữ liệu cũ vào gara,\n";
echo "--   đánh dấu màn chỉ Tân Phát, cột thông tin gara. Cột mới để NULL được.\n";
```
(c) Ngay trước khối `/* ---... Đánh dấu đã chạy`, thêm:

```php
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
```

(d) Mảng tên migration cuối file: thêm `'2026_09_22_000076_nen_gara_doc_lap',` sau dòng `000075`.

- [ ] **Step 4: Chạy test, phải xanh**

Run: `C:\xampp\php\php.exe tests\CachLyGaraTest.php`
Expected: `FAIL: 0`

- [ ] **Step 5: Chạy thử SQL sinh ra lên CSDL local (phải chạy lại được)**

Run:
```
C:\xampp\php\php.exe tools\xuat-sql-thay-doi.php --chi-cau-truc > deploy\thay-doi-csdl-cau-truc.sql
C:\xampp\php\php.exe tools\xuat-sql-thay-doi.php > deploy\thay-doi-csdl.sql
```
Rồi chạy `deploy\thay-doi-csdl-cau-truc.sql` hai lần lên CSDL local bằng script mysqli `multi_query` trong scratchpad (giống `kiem-import.php`: đếm câu, dừng ở câu lỗi đầu tiên).
Expected: cả hai lần "khong loi"; `tests\CachLyGaraTest.php` vẫn `FAIL: 0`; `tests\CollationTest.php` vẫn `FAIL: 0`.

- [ ] **Step 6: Commit**

```bash
git add tools/xuat-sql-thay-doi.php tests/CachLyGaraTest.php
git commit -m "tools(sql): phan 17 — SQL trien khai cho migration 000076"
```

---

### Task 3: Gara làm việc theo tài khoản, bỏ ô đổi gara

**Files:**
- Modify: `app/helpers/functions.php:608-653` (`gara_hien_tai`, `gara_hien_tai_id` + 3 hàm mới)
- Modify: `app/views/layouts/admin/header.php:25-63`
- Modify: `routes/web.php:57-68`
- Modify: `app/controllers/admin/Garages.php` (bỏ `doi()`, `quayVe()`, sửa chú thích đầu lớp, bỏ `use App\core\Session`? — KHÔNG bỏ, các hàm khác còn dùng)
- Modify: `app/providers/AppServiceProvider.php:38-42`
- Modify: `tests/NhieuGaraTest.php:17-19, 57, 78-94, 121-133`
- Test: `tests/CachLyGaraTest.php`

**Interfaces:**
- Produces:
  - `gara_hien_tai(): ?array` — quản trị: gara của tài khoản (đang hoạt động) hoặc `null`; website / dòng lệnh: gara tổng.
  - `gara_hien_tai_id(): ?int`
  - `la_gara_tong(): bool` — gara làm việc là gara tổng.
  - `la_request_quan_tri(): bool` — URL đang là `admin` hoặc `admin/...`.
  - `nguoi_dang_nhap_id(): ?int` — từ session `dataUser`, hoặc suy từ `dataToken`.
  - Trong test: `$http`, `$token`, `$dangNhap($email)` → `[$jar, $csrf]` hoặc `null`, `$MK`, nhóm `$A, $M, $S`, tài khoản `zz-cl-b@local.test` (Manager, gara B), `zz-cl-tp@local.test` (Manager, gara tổng), `zz-cl-ad@local.test` (Admin, gara tổng), `zz-cl-trong@local.test` (Staff, không gara), `zz-cl-khoa@local.test` (Staff, gara khoá), `$jarB`.

- [ ] **Step 1: Viết test (đỏ)**

Chèn trước `// ==== [CLI] ====`:

```php
// ---------------------------------------------------------------------------
section('Gara lam viec — theo tai khoan, khong doi duoc');

$g = gara_hien_tai();
ok(!empty($g) && (int) $g['id'] === $TP, 'Dong lenh: gara lam viec la gara tong');
ok(function_exists('la_gara_tong') && la_gara_tong(), 'la_gara_tong() dung o dong lenh');

/* Không một file nào trong app/ còn đọc / ghi session `garage_id`: ô đổi gara cũ
   ghi vào đó, và phiên nào còn sót giá trị ấy thì vẫn "đổi" được gara. */
$conSession = [];
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($goc . 'app', FilesystemIterator::SKIP_DOTS));
foreach ($rii as $f){
    if ($f->getExtension() !== 'php') continue;
    if (preg_match("~Session::(get|set)\(\s*'garage_id'~", codeOnly($f->getPathname()))) $conSession[] = $f->getFilename();
}
ok(empty($conSession), 'Khong con cho nao doc / ghi session garage_id', implode(', ', $conSession));
ok(strpos(file_get_contents($goc . 'routes/web.php'), 'garages/doi') === false, 'Khong con route doi gara');
ok(!preg_match('~function\s+doi\s*\(~', codeOnly($goc . 'app/controllers/admin/Garages.php')),
   'Controller Garages khong con ham doi()');
ok(strpos(file_get_contents($goc . 'app/views/layouts/admin/header.php'), 'garages/doi') === false,
   'Dau trang khong con o doi gara');
ok(strpos(codeOnly($goc . 'app/providers/AppServiceProvider.php'), 'dsGara') === false,
   'Khong con chia se danh sach gara de doi');
```

Chèn sau `// ==== [HTTP] ====` (khung HTTP dùng chung cho các task sau):

```php
// ---------------------------------------------------------------------------
section('HTTP that — tai khoan tam');

if (!function_exists('curl_init')){ echo "\n[SKIP] PHP khong co curl.\n"; exit(summary()); }

$MK   = 'ZzCachLy#2026';
$hash = \App\core\Hash::make($MK);
$nhom = function($ten) use ($so){ return $so("SELECT id FROM `groups` WHERE name = ?", [$ten]); };
$A = $nhom('Admin'); $M = $nhom('Manager'); $S = $nhom('Staff');
if (!$A || !$M || !$S){ echo "\n[SKIP] Thieu nhom Admin/Manager/Staff.\n"; exit(summary()); }

$taoUser = function($email, $ten, $nhomId, $gara) use ($pdo, $hash){
    $pdo->prepare("INSERT INTO users (name, email, password, group_id, status, garage_id, create_at)
                   VALUES (?, ?, ?, ?, 1, ?, NOW())")->execute([$ten, $email, $hash, $nhomId, $gara]);
    return (int) $pdo->lastInsertId();
};
$taoUser('zz-cl-b@local.test',     'ZZ Quan ly B',     $M, $GB);
$taoUser('zz-cl-tp@local.test',    'ZZ Quan ly TP',    $M, $TP);
$taoUser('zz-cl-ad@local.test',    'ZZ Admin TP',      $A, $TP);
$taoUser('zz-cl-trong@local.test', 'ZZ Chua gan gara', $S, null);
$taoUser('zz-cl-khoa@local.test',  'ZZ Gara bi khoa',  $S, $GK);

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
    return ['code' => (int) $info['http_code'], 'loc' => (string) $info['redirect_url'],
            'body' => $body, 'text' => html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8')];
};
$token = function($html){ return preg_match('~name="_token" value="([^"]+)"~', $html, $m) ? $m[1] : ''; };
$jars  = [];
$dangNhap = function($email) use ($http, $token, $base, $MK, &$jars){
    $jar = tempnam(sys_get_temp_dir(), 'zzcl');
    $jars[] = $jar;
    $r = $http('GET', "$base/dang-nhap", $jar);
    if ($r['code'] === 0) return null;
    $tk = $token($r['body']);
    $http('POST', "$base/dang-nhap", $jar, ['email' => $email, 'password' => $MK, '_token' => $tk]);
    return [$jar, $tk];
};
register_shutdown_function(function() use (&$jars){ foreach ($jars as $j) @unlink($j); });
$dauTrang = function($r){ return preg_match('~<header class="adm-topbar">.*?</header>~s', $r['text'], $m) ? $m[0] : ''; };
$menuTrai = function($r){ return preg_match('~<aside class="adm-sidebar">.*?</aside>~s', $r['body'], $m) ? $m[0] : ''; };

$phien = $dangNhap('zz-cl-b@local.test');
if ($phien === null){ echo "\n[SKIP] Apache khong chay (localhost:88).\n"; exit(summary()); }
list($jarB) = $phien;

// ---------------------------------------------------------------------------
section('HTTP — gara lam viec');

$r = $http('GET', "$base/admin", $jarB);
ok($r['code'] === 200, 'Tai khoan gara B vao duoc trang quan tri (HTTP ' . $r['code'] . ')', $r['loc']);
ok(strpos($dauTrang($r), 'ZZ Gara B') !== false, 'Dau trang hien ten gara cua tai khoan');
ok(strpos($r['body'], 'garages/doi') === false, 'Trang KHONG co link doi gara');

$http('GET', "$base/admin/garages/doi/$GA", $jarB);
$r = $http('GET', "$base/admin", $jarB);
ok(strpos($dauTrang($r), 'ZZ Gara B') !== false && strpos($dauTrang($r), 'ZZ Gara A') === false,
   'Go thang URL doi gara cu van KHONG sang duoc gara A');
```

- [ ] **Step 2: Chạy test, phải đỏ**

Run: `C:\xampp\php\php.exe tests\CachLyGaraTest.php`
Expected: FAIL ở `la_gara_tong()`, `session garage_id`, `route doi gara`, `ham doi()`, `o doi gara`, `dsGara`.

- [ ] **Step 3: Viết lại `gara_hien_tai()` và thêm hàm phụ**

Trong `app/helpers/functions.php`, thay nguyên khối từ chú thích `/** * Gara đang làm việc ...` đến hết `function gara_hien_tai_id(){...}` bằng:

```php
/**
 * URL đang là trang quản trị (`admin` hoặc `admin/...`) — đọc thẳng
 * $_GET['module'] như App::handleUrl() và menu trái.
 */
function la_request_quan_tri(){
    $m = isset($_GET['module']) ? trim((string) $_GET['module'], '/') : '';
    return $m === 'admin' || strpos($m, 'admin/') === 0;
}

/**
 * Id tài khoản quản trị đang đăng nhập, hoặc null.
 *
 * Không chỉ đọc 'dataUser': AppServiceProvider::boot() chạy TRƯỚC
 * AuthMiddleware — mà 'dataUser' do middleware đặt. Ở request đầu tiên sau khi
 * đăng nhập chỉ có 'dataToken', nên phải suy từ token.
 */
function nguoi_dang_nhap_id(){
    $id = \App\core\Session::get('dataUser');
    if (!empty($id)) return (int) $id;
    $tk = \App\core\Session::get('dataToken');
    if (!empty($tk)){
        $row = \App\core\Load::model('LoginToken')->getToken($tk);
        if (!empty($row['user_id'])) return (int) $row['user_id'];
    }
    return null;
}

/**
 * Gara làm việc của request này — [id, code, name, is_master, ...] hoặc null.
 *
 * GARA ĐỘC LẬP (22/09/2026): mỗi tài khoản thuộc đúng MỘT gara và chỉ làm việc
 * trên gara đó. Không còn ô đổi gara — với các gara độc lập, "đổi gara" chính
 * là xem dữ liệu của doanh nghiệp khác.
 *
 *   Trang quản trị     -> gara ghi trên tài khoản, và gara đó phải đang hoạt
 *                         động. Không có / đang khoá -> null (AuthMiddleware đá ra).
 *   Website, dòng lệnh -> gara tổng: website, giỏ hàng, đơn web là của Tân Phát.
 *
 * KHÔNG đọc session `garage_id` — ô đổi gara cũ ghi vào đó.
 *
 * Chỉ NHỚ kết quả tìm được. Kết quả null không nhớ: ở request khôi phục phiên
 * từ cookie "Ghi nhớ đăng nhập", lúc boot() hỏi thì chưa có phiên, tới lúc
 * AuthMiddleware hỏi thì đã có — nhớ null từ lần đầu là đá oan người dùng ra.
 */
function gara_hien_tai(){
    static $cache = false;
    if ($cache !== false) return $cache;

    $model = \App\core\Load::model('GaragesModel');

    if (PHP_SAPI === 'cli' || !la_request_quan_tri()){
        $master = $model->getMaster();
        if (!empty($master)) $cache = $master;
        return !empty($master) ? $master : null;
    }

    $userId = nguoi_dang_nhap_id();
    if (empty($userId)) return null;

    $u = \App\core\Load::model('UsersModel')->getDetail($userId);
    if (empty($u['garage_id'])) return null;

    $g = $model->getDetail((int) $u['garage_id']);
    if (empty($g) || (int) $g['status'] !== 1) return null;

    return $cache = $g;
}

/** Id của gara đang làm việc, hoặc null — dùng khi lưu chứng từ */
function gara_hien_tai_id(){
    $g = gara_hien_tai();
    return !empty($g['id']) ? (int) $g['id'] : null;
}

/** Gara làm việc là gara tổng (Tân Phát) — mở các màn "chỉ Tân Phát" */
function la_gara_tong(){
    $g = gara_hien_tai();
    return !empty($g) && (int) $g['is_master'] === 1;
}
```

- [ ] **Step 4: Bỏ ô đổi gara**

`app/views/layouts/admin/header.php`: thay từ dòng `<?php` (ngay sau `<div class="adm-topbar__right">`) đến hết `<?php endif; ?>` của khối dropdown gara (dòng 25-63) bằng:

```php
        <?php
        /* Gara của tài khoản — chỉ để đọc.
           Không còn ô đổi gara (22/09/2026): các gara là doanh nghiệp độc lập,
           "đổi gara" chính là xem dữ liệu của gara khác. Muốn làm ở gara khác
           thì dùng tài khoản của gara đó. */
        $garaHienTai = isset($garaHienTai) ? $garaHienTai : null;
        ?>
        <?php if (!empty($garaHienTai['name'])): ?>
        <span class="adm-user-toggle px-2 adm-topbar__gara" title="Gara của tài khoản">
            <?php echo icon('map-pin'); ?>
            <span class="d-none d-md-inline ml-1"><?php echo e($garaHienTai['name']); ?></span>
        </span>
        <?php endif; ?>
```

`routes/web.php`: xoá khối chú thích `/* Đổi gara đang làm việc ...*/` và dòng `Route::get('garages/doi/(\d+)', 'admin/garages/doi/$1');`.

`app/controllers/admin/Garages.php`: xoá hai hàm `doi()` và `quayVe()` (cùng chú thích của `doi()`); thay đoạn chú thích đầu lớp từ `* GARA KHÔNG PHẢI LÀ KHO...` đến hết `* kỹ thuật viên cần biết xe...` bằng:

```php
 * GARA KHÔNG PHẢI LÀ KHO. Một gara có thể có nhiều kho.
 *
 * GARA ĐỘC LẬP (22/09/2026): mỗi gara là một doanh nghiệp riêng, không thấy
 * dữ liệu của nhau. Màn này là của riêng Tân Phát (cờ `chi_tan_phat`): Tân
 * Phát thấy danh sách gara, không thấy dữ liệu bên trong gara.
```

`app/providers/AppServiceProvider.php`: thay khối chú thích + hai dòng `garaHienTai` / `dsGara` bằng:

```php
            /* Gara của tài khoản — thanh đầu trang hiện tên gara. Chia sẻ ở
               đây để màn hình nào cũng có, khỏi phụ thuộc từng controller. */
            $dataShare['content']['garaHienTai'] = gara_hien_tai();
```

- [ ] **Step 5: Sửa `tests/NhieuGaraTest.php` theo mô hình mới**

Chú thích đầu file, thay mục 3 bằng:
```php
 *   3. Đổi gara. Từ 22/09/2026 các gara độc lập — KHÔNG còn ô đổi gara, gara
 *      làm việc luôn là gara của tài khoản (xem CachLyGaraTest).
```
Dòng 57: bỏ `'garages/doi/(\d+)'` khỏi mảng route.
Thay khối dòng 78-94 (từ `/* Soi RIÊNG thân hàm doi()` đến hết khẳng định `HTTP_REFERER`) bằng:
```php
ok(!preg_match('~function\s+doi\s*\(~', $ctl),
   'Controller KHONG con ham doi gara',
   'Gara doc lap: doi gara = xem du lieu cua doanh nghiep khac');
ok(strpos($ctl, "Session::set('garage_id'") === false, 'Controller KHONG ghi gara vao session');
```
Thay khối dòng 121-133 (section `Thanh dau trang`, giữ khẳng định `@if` / `@foreach`) bằng:
```php
$header = file_get_contents($goc . 'app/views/layouts/admin/header.php');
ok(strpos($header, 'garages/doi') === false && strpos($header, 'dsGara') === false,
   'Dau trang KHONG con o doi gara');
ok(strpos($header, "\$garaHienTai['name']") !== false, 'Dau trang hien ten gara cua tai khoan');
ok(strpos($header, '@if') === false && strpos($header, '@foreach') === false,
   'Header viet bang PHP thuan, khong dung cu phap template',
   'Layout KHONG di qua Template::run() nen @if/{{ }} se in ra nguyen van');

$provider = file_get_contents($goc . 'app/providers/AppServiceProvider.php');
ok(strpos($provider, 'garaHienTai') !== false && strpos(codeOnly($goc . 'app/providers/AppServiceProvider.php'), 'dsGara') === false,
   'Chia se gara cua tai khoan cho moi man admin, KHONG chia se danh sach gara de doi');
```

- [ ] **Step 6: Chạy test, phải xanh**

Run: `C:\xampp\php\php.exe tests\CachLyGaraTest.php` rồi `C:\xampp\php\php.exe tests\NhieuGaraTest.php` rồi `C:\xampp\php\php.exe tests\NguonBaoGiaTest.php`
Expected: cả ba `FAIL: 0`.

- [ ] **Step 7: Commit**

```bash
git add app/helpers/functions.php app/views/layouts/admin/header.php routes/web.php app/controllers/admin/Garages.php app/providers/AppServiceProvider.php tests/NhieuGaraTest.php tests/CachLyGaraTest.php
git commit -m "feat(gara-doc-lap): gara lam viec theo tai khoan, bo o doi gara"
```

---

### Task 4: Không có gara thì không vào được trang quản trị

**Files:**
- Modify: `app/controllers/Auth.php:56-58` (`postLogin`)
- Modify: `app/middlewares/AuthMiddleware.php:41-49`
- Modify: `app/controllers/admin/Users.php` (`loiForm`, chú thích `garaTuForm`)
- Modify: `app/views/admin/users/add.php:58-71`, `app/views/admin/users/edit.php:57-71`, `app/views/admin/users/lists.php:63,111`
- Modify: `tests/NhanVienGaraTest.php:300-309, 324`
- Test: `tests/CachLyGaraTest.php`

**Interfaces:**
- Consumes: `gara_hien_tai()` (Task 3).
- Produces: `tai_khoan_co_gara(array $u): bool`; câu báo `'Tài khoản chưa được gán gara, hoặc gara đang bị khoá. Liên hệ quản trị Tân Phát.'` (trang đăng nhập).

- [ ] **Step 1: Viết test (đỏ)** — chèn cuối file, ngay trước `exit(summary());`:

```php
// ---------------------------------------------------------------------------
section('HTTP — khong co gara thi khong vao duoc');

foreach (['zz-cl-trong@local.test' => 'chua gan gara', 'zz-cl-khoa@local.test' => 'thuoc gara dang khoa'] as $email => $ly){
    list($jar) = $dangNhap($email);
    $r = $http('GET', "$base/admin", $jar);
    ok($r['code'] === 302 && strpos($r['loc'], 'dang-nhap') !== false,
       "Tai khoan $ly KHONG vao duoc trang quan tri", 'HTTP ' . $r['code'] . ' ' . $r['loc']);
    $r = $http('GET', "$base/dang-nhap", $jar);
    ok(strpos($r['text'], 'chưa được gán gara') !== false, "Trang dang nhap bao ro ly do ($ly)");
}

/* Khoá gara GIỮA CHỪNG: phiên đang mở phải bị huỷ ở request kế tiếp */
list($jarB2) = $dangNhap('zz-cl-b@local.test');
ok($http('GET', "$base/admin", $jarB2)['code'] === 200, 'Gara B dang hoat dong: vao duoc');
$pdo->prepare("UPDATE garages SET status = 0 WHERE id = ?")->execute([$GB]);
$r = $http('GET', "$base/admin", $jarB2);
ok($r['code'] === 302 && strpos($r['loc'], 'dang-nhap') !== false,
   'Khoa gara giua chung: bi dua ra trang dang nhap ngay request ke tiep', 'HTTP ' . $r['code'] . ' ' . $r['loc']);
$pdo->prepare("UPDATE garages SET status = 1 WHERE id = ?")->execute([$GB]);
$r = $http('GET', "$base/admin", $jarB2);
ok($r['code'] === 302, 'Mo khoa lai thi phien cu van da bi huy — phai dang nhap lai', 'HTTP ' . $r['code']);

/* Admin tạo tài khoản: bắt buộc chọn gara */
list($jarAD) = $dangNhap('zz-cl-ad@local.test');
$them = function($email, $gara) use ($http, $token, $base, $jarAD, $S, $MK){
    $f = $http('GET', "$base/admin/users/add", $jarAD);   // mở form: xoá flash `old`, lấy token
    return $http('POST', "$base/admin/users/add", $jarAD, [
        '_token' => $token($f['body']), 'name' => 'ZZ Tai khoan moi', 'email' => $email,
        'password' => $MK, 'confirm_password' => $MK, 'group_id' => $S, 'status' => 1, 'garage_id' => $gara,
    ]);
};
$them('zz-cl-moi1@local.test', '');
ok($so("SELECT COUNT(*) FROM users WHERE email = 'zz-cl-moi1@local.test'") === 0,
   'Admin KHONG tao duoc tai khoan khong co gara', 'Tai khoan khong gara thi khong dang nhap duoc — tao ra chi de nam do');
$f = $http('GET', "$base/admin/users/add", $jarAD);
ok(strpos($f['text'], 'Chưa chọn gara') !== false, 'Form bao loi "Chua chon gara"');
$them('zz-cl-moi2@local.test', (string) $GB);
$moi = $mot("SELECT garage_id FROM users WHERE email = 'zz-cl-moi2@local.test'");
ok(!empty($moi) && (int) $moi['garage_id'] === $GB, 'Admin tao tai khoan cho gara B: tai khoan thuoc gara B');
```

- [ ] **Step 2: Chạy test, phải đỏ**

Run: `C:\xampp\php\php.exe tests\CachLyGaraTest.php`
Expected: FAIL ở `KHONG vao duoc`, `bao ro ly do`, `Khoa gara giua chung`, `KHONG tao duoc tai khoan khong co gara`.

- [ ] **Step 3: Thêm `tai_khoan_co_gara()`** — trong `app/helpers/functions.php`, ngay sau `la_gara_tong()`:

```php
/**
 * Tài khoản $u có gara đang hoạt động không. Không có thì không vào được trang
 * quản trị: mọi màn nghiệp vụ lọc theo gara, không gara là không có gì để xem.
 */
function tai_khoan_co_gara($u){
    if (empty($u['garage_id'])) return false;
    $g = \App\core\Load::model('GaragesModel')->getDetail((int) $u['garage_id']);
    return !empty($g) && (int) $g['status'] === 1;
}
```

- [ ] **Step 4: Chặn lúc đăng nhập** — `app/controllers/Auth.php`, ngay sau `$dataUser = $this->__userModel->checkLogin($email, $password);`:

```php
            /* Gara độc lập: tài khoản chưa gán gara, hoặc gara đang khoá, thì
               không cho vào. Kiểm SAU khi đúng mật khẩu — kiểm trước là để lộ
               cho người đoán mò biết email nào có tồn tại. */
            if (!empty($dataUser) && !tai_khoan_co_gara($dataUser)){
                Session::flash('msg', 'Tài khoản chưa được gán gara, hoặc gara đang bị khoá. Liên hệ quản trị Tân Phát.');
                $this->__response->redirect();
            }
```

- [ ] **Step 5: Đá ra giữa chừng** — `app/middlewares/AuthMiddleware.php`, trong nhánh `if (Request::is('admin/*'))`, ngay sau khối `if (!Session::get('dataUser')) {...}`:

```php
            /* Đóng khi thiếu gara: tài khoản mất gara, hoặc gara bị khoá giữa
               chừng, thì đá ra ngay chứ không để đi tiếp với "gara = null" —
               lớp Model gốc gặp null sẽ trả rỗng, người dùng chỉ thấy các màn
               trống trơn mà không hiểu vì sao. */
            if (empty(gara_hien_tai())){
                $this->huyPhien();
                Session::flash('msg', 'Tài khoản chưa được gán gara, hoặc gara đang bị khoá. Liên hệ quản trị Tân Phát.');
                $response->redirect('dang-nhap');
            }
```

và thêm hàm vào lớp (trước `setActivity()`):

```php
    /** Huỷ phiên như Đăng xuất: token, session, cookie ghi nhớ */
    private function huyPhien(){
        $tokenId = Session::get('dataToken');
        if (!empty($tokenId)) Load::model('LoginToken')->remove($tokenId);
        Session::remove('dataToken');
        Session::remove('dataUser');
        \App\core\Cookie::remove(\LoginToken::REMEMBER_COOKIE);
    }
```

- [ ] **Step 6: Tài khoản bắt buộc có gara** — `app/controllers/admin/Users.php`, trong `loiForm()` ngay trước `return $errors;`:

```php
        /* Admin chọn gara trên form: bắt buộc, và phải là gara đang hoạt động.
           Tài khoản không gara thì không đăng nhập được (AuthMiddleware). */
        if ($pv['toan_quyen']){
            $gara = $this->garaTuForm();
            $g    = $gara !== null ? $this->model('GaragesModel')->getDetail($gara) : null;
            if (empty($g) || (int) $g['status'] !== 1) $errors['garage_id'] = 'Chưa chọn gara cho tài khoản';
        }
```

Chú thích của `garaTuForm()` thay bằng:
```php
    /**
     * Gara chọn trên form, hoặc null nếu để trống. loiForm() báo lỗi khi null:
     * gara độc lập — tài khoản không gara thì không đăng nhập được.
     */
```

`app/views/admin/users/add.php` và `edit.php`, trong khối `@if ($toanQuyen)` của ô Gara: `<option value="">— Chưa gán gara —</option>` → `<option value="">— Chọn gara —</option>`; thay đoạn `<small>...</small>` bằng:
```php
            {!! !empty($errors['garage_id'])?'<span style="color:red">'.$errors['garage_id'].'</span>':false !!}
            <small class="form-text text-muted">
                Mỗi tài khoản thuộc đúng một gara và chỉ thấy dữ liệu của gara đó. Không có gara thì không đăng nhập được.
            </small>
```

`app/views/admin/users/lists.php:63`: `Chưa gán gara (tính là gara tổng)` → `Chưa gán gara (không đăng nhập được)`; dòng 111: `— chưa gán (gara tổng)` → `— chưa gán (không đăng nhập được)`.

- [ ] **Step 7: Sửa `tests/NhanVienGaraTest.php`**

Thay khối dòng 300-309 (`/* --- Manager chua gan gara ...` đến `khong mo duoc form them');`) bằng:
```php
/* --- Manager chua gan gara: gara doc lap — KHONG vao duoc trang quan tri --- */
list($jarTrong, ) = $dangNhap('zz-ql-trong@local.test');
$r = $http('GET', "$base/admin/users", $jarTrong);
ok($r['code'] === 302 && strpos($r['loc'], 'dang-nhap') !== false,
   'Manager chua gan gara -> khong vao duoc trang quan tri',
   'HTTP ' . $r['code'] . ($r['loc'] ? ' -> ' . $r['loc'] : ''));
ok(strpos($r['body'], 'zz-nv-trong@local.test') === false && strpos($r['body'], 'zz-nv-sg@local.test') === false,
   'Manager chua gan gara -> khong thay tai khoan nao');
```
Dòng 324: `'chưa gán (gara tổng)'` → `'chưa gán (không đăng nhập được)'`, nhãn `'Tai khoan chua gan gara ghi ro "chua gan (khong dang nhap duoc)"'`.

- [ ] **Step 8: Chạy test, phải xanh**

Run: `C:\xampp\php\php.exe tests\CachLyGaraTest.php`, `C:\xampp\php\php.exe tests\NhanVienGaraTest.php`, `C:\xampp\php\php.exe tests\RememberLoginTest.php`, `C:\xampp\php\php.exe tests\SecurityTest.php`
Expected: cả bốn `FAIL: 0`.

- [ ] **Step 9: Commit**

```bash
git add app/helpers/functions.php app/controllers/Auth.php app/middlewares/AuthMiddleware.php app/controllers/admin/Users.php app/views/admin/users tests/NhanVienGaraTest.php tests/CachLyGaraTest.php
git commit -m "feat(gara-doc-lap): khong co gara (hoac gara khoa) thi khong vao duoc trang quan tri"
```

---

### Task 5: Lớp Model gốc biết chặn theo gara

**Files:**
- Modify: `core/Model.php`
- Test: `tests/CachLyGaraTest.php` (chèn trước `// ==== [CLI] ====`)

**Interfaces:**
- Produces (dùng ở bước 2-4):
  - `protected $_theoGara = false;` — model bảng riêng gara đặt `true`.
  - `Model::epGara(?int $id): void` — chỉ có tác dụng ở dòng lệnh; `null` = bỏ ép; `0` = giả lập "không xác định được gara".
  - `Model::garaLoc(): ?int` — `>0` lọc theo gara đó; `0` đóng; `null` không lọc (dòng lệnh chưa ép).
  - `$this->dkGara(string $bi = ''): array` — `[sql, bindings]` cho truy vấn tự viết, vd `dkGara('q')` → ``["`q`.`garage_id` = ?", [5]]``; không lọc → `['1 = 1', []]`; đóng → `['1 = 0', []]`.

- [ ] **Step 1: Viết test (đỏ)**

```php
// ---------------------------------------------------------------------------
section('Lop Model goc — chan theo gara');

/* Model thử trên bảng `warehouses` (đã có garage_id). Bước 1 chưa bật cờ cho
   model thật nào, nên thử cơ chế bằng một lớp tạm. */
$khoModel = new class extends \App\core\Model {
    protected $_table = 'warehouses'; protected $_fields = '*'; protected $_primary = 'id';
    protected $_theoGara = true;
};
$pdo->prepare("INSERT INTO warehouses (code, name, is_default, sort_order, status, garage_id, create_at)
               VALUES ('ZZCL-KHOA', 'ZZ Kho A', 0, 99, 1, ?, NOW())")->execute([$GA]);
$khoA = (int) $pdo->lastInsertId();
$khoB = $so("SELECT id FROM warehouses WHERE code = 'ZZCL-KHOB'");
$tenKho = function($id) use ($mot){ $r = $mot("SELECT name, garage_id FROM warehouses WHERE id = ?", [$id]); return $r; };

ok(method_exists('\App\core\Model', 'epGara'), 'Model co epGara() de test / cong cu dong lenh ep gara');
if (method_exists('\App\core\Model', 'epGara')){
    \App\core\Model::epGara($GB);

    ok(empty($khoModel->getFirst($khoA)), 'getFirst() theo ID cua gara A tu gara B: KHONG thay');
    ok(!empty($khoModel->getFirst($khoB)), 'getFirst() ID cua chinh gara B: thay');
    $ma = array_column($khoModel->getList(), 'code');
    ok(in_array('ZZCL-KHOB', $ma, true) && !in_array('ZZCL-KHOA', $ma, true), 'getList() chi ra kho cua gara B');
    $ma = array_column($khoModel->getList('`code` LIKE ?', ['ZZCL-%']), 'code');
    ok($ma === ['ZZCL-KHOB'], 'getList() co dieu kien rieng: van ghep dieu kien gara (dung thu tu bind)', json_encode($ma));
    $ma = array_column($khoModel->getLimit(1000), 'code');
    ok(in_array('ZZCL-KHOB', $ma, true) && !in_array('ZZCL-KHOA', $ma, true), 'getLimit() chi ra kho cua gara B');

    $khoModel->updateById(['name' => 'ZZ bi sua'], $khoA);
    ok($tenKho($khoA)['name'] === 'ZZ Kho A', 'updateById() vao ID cua gara A: KHONG sua duoc');
    $khoModel->deleteById($khoA);
    ok(!empty($tenKho($khoA)), 'deleteById() vao ID cua gara A: KHONG xoa duoc');

    $khoModel->updateById(['garage_id' => $GA, 'name' => 'ZZ Kho B2'], $khoB);
    $b = $tenKho($khoB);
    ok($b['name'] === 'ZZ Kho B2' && (int) $b['garage_id'] === $GB,
       'updateById() khong chuyen duoc du lieu sang gara khac (bo garage_id tren form)');

    $khoModel->addNew(['code' => 'ZZCL-KHOM', 'name' => 'ZZ Kho moi', 'is_default' => 0, 'sort_order' => 99,
                       'status' => 1, 'garage_id' => $GA, 'create_at' => date('Y-m-d H:i:s')]);
    $moi = $mot("SELECT garage_id FROM warehouses WHERE code = 'ZZCL-KHOM'");
    ok(!empty($moi) && (int) $moi['garage_id'] === $GB, 'addNew() luon ghi gara lam viec, bo qua garage_id form gui len');

    ok($khoModel->dkGara('w') === ['`w`.`garage_id` = ?', [$GB]], 'dkGara(alias) cho truy van tu viet');

    \App\core\Model::epGara(0);   // giả lập web không xác định được gara
    ok(empty($khoModel->getFirst($khoB)) && $khoModel->getList() === [], 'Khong xac dinh duoc gara: KHONG tra dong nao');
    $nem = false;
    try { $khoModel->addNew(['code' => 'ZZCL-KHOX', 'name' => 'x']); } catch (\RuntimeException $e){ $nem = true; }
    ok($nem, 'Khong xac dinh duoc gara: addNew() tu choi ghi');
    ok($khoModel->dkGara() === ['1 = 0', []], 'dkGara() dong khi khong xac dinh duoc gara');

    \App\core\Model::epGara(null);   // dòng lệnh chưa ép: migrate, gieo dữ liệu, xuất SQL
    ok(!empty($khoModel->getFirst($khoA)), 'Dong lenh chua ep gara: khong chan (migrate / cong cu van chay)');
    ok($khoModel->dkGara('w') === ['1 = 1', []], 'dkGara() khong loc khi dong lenh chua ep gara');

    $thuong = new class extends \App\core\Model {
        protected $_table = 'warehouses'; protected $_fields = '*'; protected $_primary = 'id';
    };
    \App\core\Model::epGara($GB);
    ok(!empty($thuong->getFirst($khoA)), 'Model KHONG bat _theoGara thi khong bi anh huong');
    \App\core\Model::epGara(null);
}
```

- [ ] **Step 2: Chạy test, phải đỏ**

Run: `C:\xampp\php\php.exe tests\CachLyGaraTest.php`
Expected: `[FAIL] Model co epGara() ...`

- [ ] **Step 3: Viết phần chặn trong `core/Model.php`**

Thêm thuộc tính sau `protected $_primary;`:

```php
    /**
     * Bảng này là dữ liệu RIÊNG của từng gara (có cột `garage_id`).
     *
     * Bật lên thì getList / getLimit / getFirst / updateById / deleteById tự
     * thêm điều kiện "thuộc gara đang làm việc", addNew tự ghi gara đó — gara B
     * gõ tay /edit/<id của gara A> nhận "không tìm thấy", không phụ thuộc việc
     * controller có nhớ kiểm hay không.
     *
     * Truy vấn tự viết (getRaw, table()->...) KHÔNG được lớp này chặn: dùng
     * dkGara() để thêm điều kiện.
     */
    protected $_theoGara = false;

    /** Gara ép từ ngoài — chỉ có tác dụng ở dòng lệnh (test, công cụ) */
    private static $__garaEp = null;
```

Thêm các hàm (sau `assertConfigured()`):

```php
    /** Ép gara cho dòng lệnh. null = bỏ ép; 0 = giả lập "không xác định được gara". */
    public static function epGara($id){
        self::$__garaEp = $id === null ? null : (int) $id;
    }

    /**
     * Gara để lọc:
     *   > 0  -> lọc theo gara đó
     *   0    -> ĐÓNG: không khớp dòng nào (web mà không xác định được gara)
     *   null -> không lọc (dòng lệnh chưa ép gara: migrate, gieo dữ liệu, xuất SQL)
     */
    public static function garaLoc(){
        if (PHP_SAPI === 'cli') return self::$__garaEp;
        $id = function_exists('gara_hien_tai_id') ? gara_hien_tai_id() : null;
        return $id ? (int) $id : 0;
    }

    /**
     * Điều kiện gara cho truy vấn tự viết: [sql, bindings].
     *   dkGara('q') -> ["`q`.`garage_id` = ?", [5]]
     * Không lọc -> ['1 = 1', []]; đóng -> ['1 = 0', []].
     */
    public function dkGara($bi = ''){
        $g = self::garaLoc();
        if ($g === null) return ['1 = 1', []];
        if ($g === 0)    return ['1 = 0', []];
        $cot = ($bi !== '' ? $this->wrapField($bi) . '.' : '') . '`garage_id`';
        return [$cot . ' = ?', [$g]];
    }

    /** Ghép điều kiện gara vào $where của các hàm có sẵn (bind của gara đứng SAU) */
    private function voiGara($where, array $bindings){
        if (!$this->_theoGara) return [$where, $bindings];
        list($dk, $b) = $this->dkGara($this->_table);
        $where = $where !== '' ? '(' . $where . ') AND ' . $dk : $dk;
        return [$where, array_merge($bindings, $b)];
    }
```

Sửa năm hàm có sẵn:

```php
    public function getList($where='', array $bindings = []){
        $this->assertConfigured();
        list($where, $bindings) = $this->voiGara((string) $where, $bindings);

        $sql = "SELECT $this->_fields FROM ".$this->wrapField($this->_table);

        if (!empty($where)){
            $sql .= ' WHERE '.$where;
        }

        return $this->getRaw($sql, $bindings);
    }

    public function getLimit($limit, $start=0, $where='', array $bindings = []){
        $this->assertConfigured();
        list($where, $bindings) = $this->voiGara((string) $where, $bindings);

        $limit = (int)$limit;
        $start = (int)$start;

        $sql = "SELECT $this->_fields FROM ".$this->wrapField($this->_table);

        if (!empty($where)){
            $sql .= ' WHERE '.$where;
        }

        $sql .= " LIMIT $start, $limit";

        return $this->getRaw($sql, $bindings);
    }

    public function getFirst($id){
        $this->assertConfigured(true);
        list($where, $bindings) = $this->voiGara($this->wrapField($this->_primary).' = ?', [$id]);

        $sql = "SELECT $this->_fields FROM ".$this->wrapField($this->_table).' WHERE '.$where;

        return $this->firstRaw($sql, $bindings);
    }

    public function addNew($data){
        if ($this->_theoGara){
            $g = self::garaLoc();
            if ($g === 0){
                throw new \RuntimeException('Khong xac dinh duoc gara lam viec — khong ghi du lieu.');
            }
            if ($g !== null) $data['garage_id'] = $g;
        }
        return $this->insert($this->_table, $data);
    }

    public function updateById($data, $id){
        $this->assertConfigured(true);

        /* Không cho form chuyển dữ liệu sang gara khác */
        if ($this->_theoGara && self::garaLoc() !== null) unset($data['garage_id']);

        list($where, $bindings) = $this->voiGara($this->wrapField($this->_primary).' = ?', [$id]);

        return $this->update($this->_table, $data, $where, $bindings);
    }

    public function deleteById($id){
        $this->assertConfigured(true);
        list($where, $bindings) = $this->voiGara($this->wrapField($this->_primary).' = ?', [$id]);

        return $this->delete($this->_table, $where, $bindings);
    }
```

(giữ nguyên các docblock đang có phía trên mỗi hàm).

- [ ] **Step 4: Chạy test, phải xanh**

Run: `C:\xampp\php\php.exe tests\CachLyGaraTest.php` rồi `C:\xampp\php\php.exe tests\QueryBuilderTest.php` rồi `C:\xampp\php\php.exe tests\ModelsSmokeTest.php`
Expected: cả ba `FAIL: 0`.

- [ ] **Step 5: Thử phá — test phải bắt được**

Tạm sửa `voiGara()` dòng đầu thành `return [$where, $bindings];` → chạy `tests\CachLyGaraTest.php` → Expected: FAIL ở `getFirst() theo ID cua gara A`, `updateById()`, `deleteById()`. Tạm bỏ dòng `if ($g !== null) $data['garage_id'] = $g;` → Expected: FAIL ở `addNew() luon ghi gara lam viec`. Trả lại code, chạy lại → `FAIL: 0`.

- [ ] **Step 6: Commit**

```bash
git add core/Model.php tests/CachLyGaraTest.php
git commit -m "feat(core): lop Model goc chan doc / sua / xoa / them theo gara (_theoGara)"
```

---

### Task 6: Cờ "chỉ Tân Phát" trong RoleMiddleware và menu

**Files:**
- Modify: `app/middlewares/RoleMiddleware.php:29-47`
- Test: `tests/CachLyGaraTest.php`

**Interfaces:**
- Consumes: `modules.chi_tan_phat` (Task 1), `la_gara_tong()` (Task 3).

- [ ] **Step 1: Viết test (đỏ)**

Chèn trước `// ==== [CLI] ====`:

```php
// ---------------------------------------------------------------------------
section('Man rieng gara — danh sach phai du');

/* Mọi module KHÔNG có cờ chi_tan_phat là màn riêng của gara. Số bên phải là
   bước thi công sẽ phủ màn đó vào test dò rò rỉ. Thêm module mới mà quên ghi
   vào đây là đỏ — không thì màn mới lọt khỏi test cách ly. */
$manRiengGara = [
    'bao-cao-ban-hang' => 5, 'bao-cao-cskh' => 5, 'bien-dong-ton' => 4, 'customer-groups' => 2,
    'customers' => 2, 'garage-catalog' => 3, 'goods-issues' => 4, 'goods-receipts' => 4,
    'lich-bao-hanh' => 2, 'nhac-bao-tri' => 2, 'partners' => 2, 'quotations' => 3, 'receptions' => 2,
    'sales-invoices' => 3, 'stock-takes' => 4, 'the-kho' => 4, 'ton-kho' => 4, 'ton-kho-lau' => 4,
    'transfers' => 4, 'users' => 5, 'vehicles' => 2, 'warehouse-locations' => 4, 'warehouses' => 4,
    'warranty' => 2,
];
if (in_array('chi_tan_phat', $cot('modules'), true)){
    $that = $pdo->query("SELECT link FROM modules WHERE chi_tan_phat = 0")->fetchAll(PDO::FETCH_COLUMN);
    $ds   = array_keys($manRiengGara);
    sort($that); sort($ds);
    ok($that === $ds, 'Moi man rieng gara deu co ten trong danh sach test',
       'Chua co trong test: ' . implode(',', array_diff($that, $ds)) . ' | Khong con la man gara: ' . implode(',', array_diff($ds, $that)));
}
```

Chèn cuối section `HTTP — gara lam viec` (trước section `HTTP — khong co gara ...`):

```php
// ---------------------------------------------------------------------------
section('HTTP — man chi Tan Phat');

$r  = $http('GET', "$base/admin", $jarB);
$mn = $menuTrai($r);
ok(strpos($mn, "$base/admin/quotations\"") !== false, 'Menu gara B co Bao gia');
ok(strpos($mn, "$base/admin/customers\"") !== false, 'Menu gara B co Khach hang');
/* Chọn đúng những màn nhóm Manager ĐANG có quyền xem — không phải màn mà
   Manager vốn không có quyền, kẻo test xanh vì lý do khác. */
foreach (['orders', 'garages', 'products', 'reviews', 'part-categories', 'chat', 'contact-messages'] as $l){
    ok(strpos($mn, "$base/admin/$l\"") === false, "Menu gara B KHONG co `$l` (chi Tan Phat)");
}
foreach (['orders', 'garages', 'products'] as $l){
    $r = $http('GET', "$base/admin/$l", $jarB);
    ok($r['code'] === 302 && strpos($r['loc'], 'khong-co-quyen') !== false,
       "Go thang /admin/$l tu gara B bi chan", 'HTTP ' . $r['code'] . ' ' . $r['loc']);
}
$r = $http('GET', "$base/admin/garages/edit/$GA", $jarB);
ok($r['code'] === 302 && strpos($r['loc'], 'khong-co-quyen') !== false, 'Gara B KHONG mo duoc trang sua gara A');

list($jarTP) = $dangNhap('zz-cl-tp@local.test');
$mn = $menuTrai($http('GET', "$base/admin", $jarTP));
ok(strpos($mn, "$base/admin/orders\"") !== false, 'Manager cua Tan Phat VAN thay Don hang web');
ok($http('GET', "$base/admin/orders", $jarTP)['code'] === 200, 'Manager cua Tan Phat VAN mo duoc Don hang web');
```

- [ ] **Step 2: Chạy test, phải đỏ**

Run: `C:\xampp\php\php.exe tests\CachLyGaraTest.php`
Expected: FAIL ở `Menu gara B KHONG co ...` và `Go thang /admin/... bi chan`.

- [ ] **Step 3: Chặn trong `RoleMiddleware`**

Thay đoạn từ `$groupData = $groupModel->getGroupByUser($userId);` đến hết vòng `foreach ($moduleLists ...)` (tức là đến trước dòng `if (!empty($currentModuleId) && !empty($permissionData)){`) bằng:

```php
        $groupData = $groupModel->getGroupByUser($userId);

        $moduleLists = $moduleModel->getLists();
        $currentModuleId = 0;
        $currentLink = '';
        $currentModule = null;
        if (!empty($moduleLists)){
            foreach ($moduleLists as $item){
                if (Request::is('admin/'.$item['link'].'/*', $this->path)){
                    $currentModuleId = $item['id'];
                    $currentLink = $item['link'];
                    $currentModule = $item;
                    break;
                }
            }
        }

        /* Màn của riêng Tân Phát (website, kho tổng, đơn web...). Nhóm quyền
           dùng chung cho mọi gara, nên Manager của gara khác cũng "có quyền" —
           phải chặn theo GARA của tài khoản, TRƯỚC cả phần kiểm quyền nhóm, và
           kể cả với nhóm không có dòng quyền nào. Menu trái hỏi qua route()
           nên cũng tự ẩn các màn này. */
        if (!empty($currentModule['chi_tan_phat']) && !la_gara_tong()){
            if (empty($this->path)){
                $response->redirect('admin/khong-co-quyen');
            }
            return false;
        }

        if (!empty($groupData)){
            $groupId = $groupData['group_id'];

            $permissionData = $permissionModel->getPermission($groupId);
```

(phần còn lại của hàm giữ nguyên — khối `if (!empty($currentModuleId) && !empty($permissionData)){ ... }` vẫn nằm trong `if (!empty($groupData))`.)

- [ ] **Step 4: Chạy test, phải xanh**

Run: `C:\xampp\php\php.exe tests\CachLyGaraTest.php` rồi `C:\xampp\php\php.exe tests\PhanQuyenNhomTest.php` rồi `C:\xampp\php\php.exe tests\QuanLyModuleTest.php`
Expected: cả ba `FAIL: 0`.

- [ ] **Step 5: Commit**

```bash
git add app/middlewares/RoleMiddleware.php tests/CachLyGaraTest.php
git commit -m "feat(phan-quyen): man chi Tan Phat — gara khac khong thay trong menu, go URL cung bi chan"
```

---

### Task 7: Chốt chặn "quên bật cờ" + chạy toàn bộ

**Files:**
- Test: `tests/CachLyGaraTest.php` (chèn trước `// ==== [CLI] ====`)

- [ ] **Step 1: Viết chốt chặn**

```php
// ---------------------------------------------------------------------------
section('Cho quen — bang co garage_id thi model phai bat _theoGara');

/* Bảng có cột `garage_id` mà model của nó chưa bật cờ là bảng KHÔNG được chặn.
   `$chuaLam` ghi bước sẽ bật; bật rồi thì phải xoá khỏi danh sách (test bắt cả
   chiều đó), nên danh sách chỉ ngắn dần. */
$ngoaiLe = ['users' => 'dang nhap tim khap cac gara; chan tay o Users::phamVi',
            'parts' => 'NULL = kho tong, loc bang dieu kien rieng'];
$chuaLam = [
    'partners' => 2, 'customer_groups' => 2, 'vehicles' => 2, 'receptions' => 2,
    'warranty_requests' => 2, 'warranty_handovers' => 2,
    'quotations' => 3, 'sales_invoices' => 3, 'garage_part_prices' => 3,
    'warehouses' => 4, 'goods_receipts' => 4, 'goods_issues' => 4, 'stock_takes' => 4, 'warehouse_transfers' => 4,
];
$modelCua = [];
foreach (glob($goc . 'app/models/*.php') as $f){
    $src = codeOnly($f);
    if (preg_match('~\$_table\s*=\s*[\'"]([a-z_]+)[\'"]~', $src, $mm)){
        $modelCua[$mm[1]][] = ['file' => basename($f), 'bat' => (bool) preg_match('~\$_theoGara\s*=\s*true~', $src)];
    }
}
$bangCoGara = $pdo->query("SELECT TABLE_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()
                           AND COLUMN_NAME = 'garage_id' ORDER BY TABLE_NAME")->fetchAll(PDO::FETCH_COLUMN);
foreach ($bangCoGara as $b){
    if (isset($ngoaiLe[$b]) || $b === 'garages') continue;
    $ms  = isset($modelCua[$b]) ? $modelCua[$b] : [];
    $bat = !empty($ms) && !in_array(false, array_column($ms, 'bat'), true);
    if (isset($chuaLam[$b])){
        ok(!$bat, "`$b` chua bat _theoGara (buoc {$chuaLam[$b]}) — bat roi thi xoa khoi \$chuaLam");
        continue;
    }
    ok($bat, "Model cua `$b` bat _theoGara",
       empty($ms) ? 'Khong tim thay model nao co $_table = ' . $b : 'Chua bat: ' . implode(', ', array_column($ms, 'file')));
}
```

- [ ] **Step 2: Chạy test** — Run: `C:\xampp\php\php.exe tests\CachLyGaraTest.php` — Expected: `FAIL: 0`.

- [ ] **Step 3: Thử phá chốt chặn** — tạm xoá `'partners' => 2,` khỏi `$chuaLam` → chạy → Expected: `[FAIL] Model cua `partners` bat _theoGara`. Trả lại.

- [ ] **Step 4: Chạy toàn bộ**

Run: `C:\xampp\php\php.exe tests\run.php`
Expected: `TONG KET: PASS <≥1925 + số khẳng định mới> | FAIL 0`, không có khối BỎ QUA.

- [ ] **Step 5: Mở thử bằng trình duyệt** — đăng nhập tài khoản thật của anh (Admin, gara Tân Phát) ở `http://localhost:88/tan-phat/dang-nhap`: đầu trang hiện "Tân Phát", menu còn đủ các màn cũ, mở được Báo giá / Đơn hàng web / Quản lý gara.

- [ ] **Step 6: Commit**

```bash
git add tests/CachLyGaraTest.php
git commit -m "test(gara-doc-lap): chot chan bang co garage_id ma model chua bat _theoGara"
```

---

## Self-Review

- **Spec coverage (bước 1):** cột gara + gán dữ liệu cũ (Task 1), SQL triển khai (Task 2), hàm gara làm việc duy nhất + bỏ ô đổi gara + website / dòng lệnh = gara tổng (Task 3), đóng khi thiếu gara (Task 4), lớp Model gốc (Task 5), cờ `chi_tan_phat` cho menu + `RoleMiddleware` (Task 6), tự bắt chỗ quên (Task 6 danh sách màn + Task 7 bảng/model). Thông tin gara trên form / phiếu in, số chứng từ theo gara, kiểm ID tham chiếu: bước 2-4 (ngoài kế hoạch này).
- **Placeholder:** không có TBD / "tương tự Task N".
- **Tên thống nhất:** `gara_hien_tai`, `gara_hien_tai_id`, `la_gara_tong`, `la_request_quan_tri`, `nguoi_dang_nhap_id`, `tai_khoan_co_gara`, `Model::epGara`, `Model::garaLoc`, `dkGara`, `voiGara`, `$_theoGara` — dùng đúng ở mọi task.
