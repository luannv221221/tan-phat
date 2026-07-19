<?php
/**
 * SEED ẢNH DEMO cho phụ tùng — tạo ảnh SVG placeholder (không cần ảnh thật) +
 * chèn part_images. Idempotent: phụ tùng đã có ảnh thì bỏ qua.
 *
 *   php database/seed_part_images.php
 * (cũng được require ở cuối seed_demo.php cho lần seed mới)
 */

if (PHP_SAPI !== 'cli'){ http_response_code(403); die("Chi chay tu CLI.\n"); }

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';
foreach (scandir(__DIR__ . '/../configs') as $f){ if ($f!=='.'&&$f!=='..') require_once __DIR__ . '/../configs/' . $f; }

use App\core\Database;

$db  = new Database();
$now = date('Y-m-d H:i:s');
$dir = __DIR__ . '/../public/assets/uploads/parts/';
if (!is_dir($dir)) @mkdir($dir, 0777, true);

/** SVG placeholder: nền gradient theo mã, mã hàng + tên + nhãn góc nhìn */
function demoSvg($code, $name, $label){
    $h  = crc32($code) % 360;
    $h2 = ($h + 40) % 360;
    $esc = function($s){ return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8'); };
    // tách tên thành 2 dòng ngắn
    $name = trim($name);
    $words = explode(' ', $name);
    $l1 = ''; $l2 = '';
    foreach ($words as $w){ if (mb_strlen($l1.' '.$w) <= 20) $l1 = trim($l1.' '.$w); else $l2 = trim($l2.' '.$w); }
    if (mb_strlen($l2) > 22) $l2 = mb_substr($l2, 0, 21) . '…';

    $svg  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $svg .= '<svg xmlns="http://www.w3.org/2000/svg" width="600" height="600" viewBox="0 0 600 600">' . "\n";
    $svg .= '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">'
          . '<stop offset="0" stop-color="hsl(' . $h . ',55%,58%)"/>'
          . '<stop offset="1" stop-color="hsl(' . $h2 . ',60%,42%)"/></linearGradient></defs>' . "\n";
    $svg .= '<rect width="600" height="600" fill="url(#g)"/>' . "\n";
    // bánh răng trang trí
    $svg .= '<g transform="translate(300,232)" fill="none" stroke="#ffffff" stroke-opacity="0.85" stroke-width="10">'
          . '<circle r="78"/><circle r="30"/>';
    for ($i = 0; $i < 8; $i++){
        $a = deg2rad($i * 45);
        $x1 = round(cos($a) * 78, 1); $y1 = round(sin($a) * 78, 1);
        $x2 = round(cos($a) * 104, 1); $y2 = round(sin($a) * 104, 1);
        $svg .= '<line x1="' . $x1 . '" y1="' . $y1 . '" x2="' . $x2 . '" y2="' . $y2 . '"/>';
    }
    $svg .= '</g>' . "\n";
    // chữ
    $svg .= '<text x="300" y="392" text-anchor="middle" font-family="Arial,Helvetica,sans-serif" font-size="30" font-weight="bold" fill="#ffffff">' . $esc($l1) . '</text>' . "\n";
    if ($l2 !== '') $svg .= '<text x="300" y="428" text-anchor="middle" font-family="Arial,Helvetica,sans-serif" font-size="24" fill="#ffffff" fill-opacity="0.95">' . $esc($l2) . '</text>' . "\n";
    $svg .= '<rect x="196" y="460" width="208" height="46" rx="23" fill="#ffffff" fill-opacity="0.9"/>' . "\n";
    $svg .= '<text x="300" y="491" text-anchor="middle" font-family="Arial" font-size="22" font-weight="bold" fill="hsl(' . $h2 . ',60%,32%)">' . $esc($code) . '</text>' . "\n";
    $svg .= '<text x="300" y="548" text-anchor="middle" font-family="Arial" font-size="18" fill="#ffffff" fill-opacity="0.9">' . $esc($label) . '</text>' . "\n";
    $svg .= '</svg>' . "\n";
    return $svg;
}

$labels = ['Ảnh sản phẩm', 'Mặt bên', 'Chi tiết'];
$parts = $db->table('parts')->where('code', 'LIKE', 'PT-%')->orderBy('id', 'ASC')->get();
$done = 0; $skip = 0;
foreach ($parts ?: [] as $p){
    $pid = (int) $p['id'];
    $ex = $db->table('part_images')->where('part_id', '=', $pid)->first();
    if (!empty($ex)){ $skip++; continue; }

    foreach ($labels as $i => $label){
        $file = strtolower($p['code']) . '-' . ($i + 1) . '-demo.svg';
        file_put_contents($dir . $file, demoSvg($p['code'], $p['name'], $label));
        $db->insert('part_images', [
            'part_id'    => $pid,
            'image'      => $file,
            'sort_order' => $i,
            'is_primary' => $i === 0 ? 1 : 0,
            'create_at'  => $now,
        ]);
    }
    $done++;
}
echo "Anh demo: tao cho $done phu tung (bo qua $skip da co anh).\n";
