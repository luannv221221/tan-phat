<?php
/**
 * DỌN CSDL LOCAL TRƯỚC KHI XUẤT ĐEM LÊN MÁY CHỦ.
 *
 *   Xem trước (không xoá gì):
 *       C:\xampp\php\php.exe deploy\don-truoc-khi-xuat.php
 *
 *   Xoá thật:
 *       C:\xampp\php\php.exe deploy\don-truoc-khi-xuat.php --that
 *
 *   Xoá luôn hai gara demo:
 *       C:\xampp\php\php.exe deploy\don-truoc-khi-xuat.php --that --bo-gara-demo
 *
 * ========================================================================
 *  CHỈ CHẠY Ở MÁY LOCAL. Đừng bao giờ chạy trên máy chủ thật.
 * ========================================================================
 *
 * VÌ SAO CẦN: cách triển khai của dự án là xuất nguyên CSDL ở máy local rồi
 * nhập đè lên máy chủ. Nghĩa là MỌI THỨ trong CSDL local sẽ lên máy chủ — kể cả
 * những thứ chỉ sinh ra để chạy thử.
 *
 * NGUY HIỂM NHẤT là tài khoản mẫu `@gara-mau.test`. Mật khẩu của chúng nằm
 * nguyên văn trong `tools/tao-du-lieu-gara-mau.php` — ai đọc mã nguồn cũng
 * biết. Trong đó có một tài khoản nhóm **Admin** thuộc gara tổng. Để nó lên
 * máy chủ là mở sẵn cửa quản trị cho bất kỳ ai xem được repo.
 *
 * Mặc định script CHỈ IN RA xem sẽ xoá gì. Phải thêm `--that` mới động vào dữ
 * liệu — xoá nhầm ở đây thì mất luôn bản local đang làm dở.
 */

$that     = in_array('--that', $argv, true);
$boDemo   = in_array('--bo-gara-demo', $argv, true);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config.php';

$pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
               _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

echo "CSDL: " . _DB . " @ " . _HOST . "\n";
echo $that ? "CHE DO: XOA THAT\n" : "CHE DO: chi xem truoc (them --that de xoa)\n";
echo str_repeat('-', 62) . "\n";

$tong = 0;

/** In danh sách rồi xoá (hoặc không, tuỳ $that) */
$lam = function($tieuDe, $sqlXem, $sqlXoa, array $bd = []) use ($pdo, $that, &$tong){
    $rows = $pdo->prepare($sqlXem);
    $rows->execute($bd);
    $ds = $rows->fetchAll(PDO::FETCH_COLUMN);

    echo "\n" . $tieuDe . ": " . count($ds) . " dong\n";
    foreach ($ds as $x) echo "    - $x\n";
    if (empty($ds)) return;

    $tong += count($ds);
    if ($that){
        $st = $pdo->prepare($sqlXoa);
        $st->execute($bd);
        echo "    => da xoa\n";
    }
};

/* 1. Tên miền .localhost — chỉ để chạy thử ở máy local. Trên máy chủ chúng vô
      nghĩa, mà lại chiếm mất host (cột `host` là duy nhất toàn bảng). */
$lam('1. Ten mien .localhost',
     "SELECT host FROM garage_domains WHERE host LIKE '%.localhost'",
     "DELETE FROM garage_domains WHERE host LIKE '%.localhost'");

/* 2. Tài khoản mẫu — mật khẩu nằm trong mã nguồn. Đây là mục quan trọng nhất. */
$lam('2. Tai khoan mau (mat khau nam trong ma nguon)',
     "SELECT CONCAT(email, '  [', COALESCE(g.name,'?'), ']') FROM users u
        LEFT JOIN `groups` g ON g.id = u.group_id
       WHERE u.email LIKE '%@gara-mau.test'",
     "DELETE FROM users WHERE email LIKE '%@gara-mau.test'");

/* 3. Dữ liệu thử còn sót mang dấu ZZ — bộ test tự dọn, nhưng lần chạy nào chết
      giữa chừng thì còn lại. */
foreach ([
    'partners' => 'name', 'vehicles' => 'bien_so', 'news' => 'title',
    'banners'  => 'title', 'contact_messages' => 'name',
] as $bang => $cot){
    $lam("3. Du lieu thu trong `$bang`",
         "SELECT `$cot` FROM `$bang` WHERE `$cot` LIKE 'ZZ%'",
         "DELETE FROM `$bang` WHERE `$cot` LIKE 'ZZ%'");
}

/* 4. Gara demo — CHỈ xoá khi được yêu cầu. Có người muốn giữ để trình diễn
      tính năng nhiều gara cho khách xem, nên không tự tiện xoá. */
if ($boDemo){
    $ds = $pdo->query("SELECT id, code, name FROM garages WHERE is_master = 0")->fetchAll(PDO::FETCH_ASSOC);
    echo "\n4. Gara demo: " . count($ds) . " gara\n";
    foreach ($ds as $g){
        echo "    - {$g['code']} {$g['name']}\n";
        if (!$that) continue;
        $id = (int) $g['id'];
        /* Xoá theo chiều con -> cha. Khoá ngoại tới `garages` là RESTRICT với
           chứng từ, nên phải dọn chứng từ trước, không thì DELETE đổ. */
        foreach ([
            "DELETE FROM goods_receipt_items WHERE receipt_id IN (SELECT id FROM goods_receipts WHERE garage_id=$id)",
            "DELETE FROM goods_issue_items   WHERE issue_id   IN (SELECT id FROM goods_issues   WHERE garage_id=$id)",
            "DELETE FROM quotation_items     WHERE quotation_id IN (SELECT id FROM quotations   WHERE garage_id=$id)",
            "DELETE FROM sales_invoice_items WHERE invoice_id IN (SELECT id FROM sales_invoices WHERE garage_id=$id)",
            "DELETE FROM stocks WHERE warehouse_id IN (SELECT id FROM warehouses WHERE garage_id=$id)",
            "DELETE FROM stock_cards WHERE warehouse_id IN (SELECT id FROM warehouses WHERE garage_id=$id)",
            "DELETE FROM goods_receipts WHERE garage_id=$id",
            "DELETE FROM goods_issues   WHERE garage_id=$id",
            "DELETE FROM quotations     WHERE garage_id=$id",
            "DELETE FROM sales_invoices WHERE garage_id=$id",
            "DELETE FROM warranty_requests WHERE garage_id=$id",
            "DELETE FROM receptions     WHERE garage_id=$id",
            "DELETE FROM vehicles       WHERE garage_id=$id",
            "DELETE FROM partners       WHERE garage_id=$id",
            "DELETE FROM customer_groups WHERE garage_id=$id",
            "DELETE FROM parts          WHERE garage_id=$id",
            "DELETE FROM garage_part_prices WHERE garage_id=$id",
            "DELETE FROM warehouses     WHERE garage_id=$id",
            "DELETE FROM users          WHERE garage_id=$id",
            "DELETE FROM garage_settings WHERE garage_id=$id",
            "DELETE FROM garage_domains WHERE garage_id=$id",
            "DELETE FROM garages WHERE id=$id",
        ] as $sql){
            try { $pdo->exec($sql); } catch (\Throwable $e){ echo "      (bo qua: " . $e->getMessage() . ")\n"; }
        }
        echo "      => da xoa\n";
    }
    $tong += count($ds);
} else {
    $n = (int) $pdo->query("SELECT COUNT(*) FROM garages WHERE is_master = 0")->fetchColumn();
    echo "\n4. Gara demo: $n gara — GIU LAI (them --bo-gara-demo neu muon xoa)\n";
}

echo "\n" . str_repeat('-', 62) . "\n";
if (!$that){
    echo "Chua xoa gi ca. Chay lai voi --that neu danh sach tren dung.\n";
} else {
    echo "Da don xong. Buoc tiep theo:\n";
    echo "  C:\\xampp\\php\\php.exe tools\\xuat-csdl.php --ra=deploy\\len-may-chu.sql\n";
}

/* Nhắc lại ở cuối, vì đây là chỗ dễ quên nhất sau khi dọn. */
echo "\nNHO: don xong thi tai khoan mau khong dang nhap duoc o may local nua.\n";
echo "     Can chay thu tiep thi tao lai bang: php tools\\tao-du-lieu-gara-mau.php\n";
