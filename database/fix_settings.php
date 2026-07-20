<?php
/**
 * SỬA cấu hình website bị lỗi font (mojibake) + dọn key sai.
 * Ghi lại đúng UTF-8 qua Database (kết nối utf8mb4). Chạy:  php database/fix_settings.php
 */
if (PHP_SAPI !== 'cli'){ http_response_code(403); die("CLI only\n"); }
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';
foreach (scandir(__DIR__ . '/../configs') as $f){ if ($f!=='.'&&$f!=='..') require_once __DIR__ . '/../configs/' . $f; }

use App\core\Database;
$db  = new Database();
$now = date('Y-m-d H:i:s');

$vals = [
    'site_name'        => 'Công ty TNHH Phụ tùng Ô tô Tân Phát',
    'site_slogan'      => 'Phụ tùng & thiết bị gara ô tô chính hãng',
    'meta_description' => 'Tân Phát - nhà cung cấp phụ tùng và thiết bị gara ô tô chính hãng. Tư vấn tương thích theo hãng, model, đời xe.',
    'meta_keywords'    => 'phụ tùng ô tô, thiết bị gara, má phanh, lọc dầu, ắc quy, Tân Phát',
    'hotline'          => '1900 6363',
    'email'            => 'info@tanphat.vn',
    'address'          => 'Số 88 Nguyễn Văn Cừ, Long Biên, Hà Nội',
    'facebook'         => 'https://facebook.com/tanphat.auto',
    'zalo'             => '1900 6363',
    'bank_name'        => 'Vietcombank - CN Hà Nội',
    'bank_account'     => '0011000123456',
    'bank_holder'      => 'CONG TY TNHH PHU TUNG O TO TAN PHAT',
    'tax_code'         => '0101234567',
];
foreach ($vals as $k => $v){
    $ex = $db->table('site_settings')->where('skey', '=', $k)->first();
    if (empty($ex)) $db->insert('site_settings', ['skey' => $k, 'svalue' => $v, 'update_at' => $now]);
    else            $db->update('site_settings', ['svalue' => $v, 'update_at' => $now], '`skey` = ?', [$k]);
}
// dọn key sai do seeder cũ tạo nhầm
foreach (['slogan', 'contact_address', 'contact_phone'] as $bad){
    $db->delete('site_settings', '`skey` = ?', [$bad]);
}
echo "Da sua " . count($vals) . " cau hinh + don 3 key sai.\n";
