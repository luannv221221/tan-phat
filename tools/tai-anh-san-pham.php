<?php
/**
 * TẢI ẢNH SẢN PHẨM từ Wikimedia Commons.
 *
 * Chạy:  C:\xampp\php\php.exe tools\tai-anh-san-pham.php
 *        C:\xampp\php\php.exe tools\tai-anh-san-pham.php --thu   (chỉ xem)
 *
 * Anh em với tools/tai-anh-commons.php (ảnh danh mục + băng-rôn). Xem file đó
 * để biết vì sao chọn Commons và ba cái bẫy khi tra cứu — ở đây không chép lại.
 *
 * KHÁC BIỆT SO VỚI ẢNH DANH MỤC
 * Nhiều mặt hàng dùng CHUNG một loại phụ tùng: ba mặt "má phanh", hai mặt
 * "lọc dầu", hai mặt "bugi". Nếu cứ lấy kết quả đầu thì ba cái má phanh cùng
 * một tấm ảnh — nhìn là biết ngay hàng giả. Nên phải nhớ ảnh đã dùng (kể cả
 * ảnh đã gán cho DANH MỤC) rồi lấy tấm kế tiếp.
 *
 * CHỈ ĐỘNG VÀO ẢNH DEMO
 * Mặt hàng nào đang có ảnh thật do người dùng tải lên thì BỎ QUA hoàn toàn —
 * dữ liệu của khách, không phải chỗ để script dọn. Chỉ thay các dòng trỏ tới
 * `*-demo.svg` (mấy ô xám sinh lúc gieo dữ liệu mẫu).
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

$THU = in_array('--thu', $argv, true);
const UA = 'TanPhatStorefront/1.0 (do an sinh vien; lien he: rikkeiedu.ai@gmail.com)';
// Ha tu 2 xuong 1: sau khi cam het anh mon/gi/ban/sai-loai-xe thi kho anh
// sach cua Commons khong con du 2 tam cho moi mat hang.
const MOI_MAT_HANG = 1;

$goc = dirname(__DIR__);

/* Mỗi nhóm có NHIỀU câu tra, kết quả gộp lại rồi mới chia cho các mặt hàng.
   Một câu là không đủ: nhóm "má phanh" có tới ba mặt hàng mà câu
   intitle:"brake pad" chỉ lọc ra được ba tấm hợp lệ, mặt thứ ba cạn ảnh.
   Một `intitle:` duy nhất mỗi câu — nhiều cái là CirrusSearch trả về rỗng. */
$TRA = [
    'ma-phanh'  => [['intitle:"brake pad"',        'bicycle|bike|making|ring|fongers'],
                    ['intitle:"brake pads"',       'bicycle|bike|making|cantilever|rim brake'],
                    ['intitle:"disc brake" pad',   'bicycle|bike|motorcycle']],
    'dia-phanh' => [['intitle:"brake disc"',       'bicycle|bike|motorcycle|train|bogie|series|railway|rail'],
                    ['intitle:"brake rotor"',      'bicycle|bike|motorcycle']],
    'loc-dau'   => [['intitle:"oil filter"',       'wrench|machine|transformer|motorcycle|valvula'],
                    ['intitle:"oil filters"',      'wrench|machine']],
    'loc-gio'   => [['intitle:"air filter" car',   'army|navy|soldier'],
                    ['intitle:"air filter" engine','army|navy|soldier|aquarium|water']],
    'bugi'      => [['intitle:"spark plug"',       'advert|sign|flint|wrench|spirit|louis|set from|mascot|cat|office|building|factory'],
                    ['intitle:"spark plugs"',      'advert|sign|wrench|office|building']],
    'day-curoa' => [['intitle:"timing belt"',      'drawing|market|coffee|kaffee|bent|broken|damage|valve'],
                    ['intitle:"cam belt"',         'drawing|bent|broken']],
    'ac-quy'    => [['intitle:"car battery"',      'charger|tester'],
                    ['intitle:"automotive battery"','charger|tester']],
    'den-xe'    => [['intitle:headlamp car',       'drawing|museum|geograph|parkend|wilbert|locomotive|rail|steam|great northern|no\. '],
                    ['intitle:headlight automotive','drawing|museum|locomotive|rail|steam|great northern'],
                    ['intitle:"led headlight"',     'drawing|locomotive|rail|bulb only']],
    'may-phat'  => [['intitle:alternator car',     'tester|regulator|controller|transmission|barrage|diesel'],
                    ['intitle:"automotive alternator"', 'tester|regulator']],
    'giam-xoc'  => [['intitle:"shock absorber"',   'bicycle|train|museu|steelpr|taipei|tower|damper|building|hartford|morges|andre'],
                    ['intitle:"shock absorbers"',  'bicycle|train|taipei|tower|damper|building|hartford'],
                    ['intitle:"strut assembly"',   'bicycle|train|bridge']],
    'lo-xo'     => [['intitle:"coil spring" suspension', 'diagram|mattress|lagerstroemia|flower|tree|plant|coach|icf|peckham|tram|railway|rail|truck|bogie|locomotive'],
                    ['intitle:"suspension strut"',      'diagram|bridge|coach|tram|railway|rail|bogie']],
];

/* Mặt hàng không gắn danh mục thì tra theo mã riêng */
$TRA_THEO_MA = [
    // "Lò xo giảm xóc sau Ranger" không gắn danh mục nào.
    // intitle:"suspension spring" trả về rỗng nên phải đi vòng qua lò xo
    // và cụm giảm xóc — cùng một cụm chi tiết trên xe.
    'PT-0012' => [['intitle:"coil spring" suspension', 'diagram|mattress|lagerstroemia|flower|tree|plant|coach|icf|peckham|tram|railway|rail|truck|bogie|locomotive'],
                  ['intitle:"suspension strut"',       'diagram|bridge|coach|tram|railway|rail|bogie'],
                  ['intitle:"shock absorber" spring',  'bicycle|train|taipei|tower|damper|coach|tram|rail']],
];

/* Tu cam dung chung.
 *
 * Nhom thu hai (hong|mon|sai xe) la BAT BUOC voi anh BAN HANG. Commons la
 * kho tu lieu: no chup phu tung de MO TA, nen day ap "ma phanh da mon",
 * "dia phanh gi set", "loc gio ban", anh cat doi de xem ben trong, va phu
 * tung cua xe dap / xe may / xe buyt / tau hoa. Dat len trang ban hang thi
 * khach nhin vao tuong minh ban do phe lieu. */
const CAM_CHUNG = 'advertis|poster|stamp|patent|diagram|schematic|blueprint|magazine|'
                . 'cover|logo|map |coat of arms|DPLA|NARA|'
                // hong / mon / ban / cat doi -> khong ban duoc
                . 'worn|wornout|rust|dirty|consumed|broken|bent|damag|cutaway|'
                . 'gasket|adapter|scrap|junk|failure|leak|'
                // sai loai xe
                . 'bicycle|bike|v-brake|motorcycle|ducati|panigale|scooter|'
                . 'bus|truck|tram|train|railway|locomotive|aircraft|tractor|'
                // xe co / bao tang / trien lam -> khong phai hang dang ban
                . 'antique|vintage|veteran|museum|motorshow|motor show|oldtimer|classic|'
                // anh dung hinh / CAD chu khong phai anh chup
                . 'cad model|3d model|render|mockup|illustration';

function goiApi($url){
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_USERAGENT => UA,
                            CURLOPT_TIMEOUT => 40, CURLOPT_FOLLOWLOCATION => true]);
    $kq = curl_exec($ch);
    curl_close($ch);
    return $kq;
}

/** Ảnh hợp lệ của MỘT câu tra, đã xếp theo thứ tự liên quan của Commons */
function timTheoCau(array $cau, array $daDung){
    list($cauTra, $camRieng) = $cau;
    $url = 'https://commons.wikimedia.org/w/api.php?' . http_build_query([
        'action' => 'query', 'generator' => 'search',
        'gsrsearch' => 'filetype:bitmap ' . $cauTra, 'gsrnamespace' => 6, 'gsrlimit' => 40,
        'prop' => 'imageinfo', 'iiprop' => 'url|mime|size|extmetadata',
        'iiurlwidth' => 1000, 'format' => 'json',
    ]);
    $j = json_decode(goiApi($url), true);
    if (empty($j['query']['pages'])) return [];

    $trang = array_values($j['query']['pages']);
    usort($trang, function($a, $b){ return ($a['index'] ?? 999) <=> ($b['index'] ?? 999); });

    $cam = CAM_CHUNG . ($camRieng !== '' ? '|' . $camRieng : '');
    $ra = [];
    foreach ($trang as $p){
        if (empty($p['imageinfo'][0])) continue;
        $i = $p['imageinfo'][0];
        if ($i['mime'] !== 'image/jpeg') continue;           // thẻ sản phẩm dùng JPEG cho nhẹ
        if (empty($i['thumburl']) || $i['width'] < 800) continue;
        // So trung bang 8 ky tu dau cua md5(URL) — dung thu da nhung vao ten
        // file. Truoc day so bang chinh URL, nhung danh sach cam lai duoc dung
        // tu TEN FILE cua anh danh muc: hai kieu khong bao gio khop, ket qua la
        // 8 mat hang xai dung tam anh cua danh muc chua no.
        if (in_array(substr(md5($i['thumburl']), 0, 8), $daDung, true)) continue;

        $ten = mb_substr($p['title'], 5);
        if (preg_match('~(' . $cam . ')~i', $ten)) continue;
        // Bat MOI nam san xuat 4 chu so tu 1900-1999: ten file kieu "Pontiac
        // Bonneville 1965" la anh xe co, khong phai hang dang ban.
        if (preg_match('~\b(18|19)\d{2}\b~', $ten)) continue;
        if ($i['width'] / max(1, $i['height']) < 1.15) continue;   // thẻ là khung ngang

        $ra[] = ['ten' => $ten, 'thumb' => $i['thumburl'], 'trang' => $i['descriptionurl'] ?? '',
                 'tacGia' => trim(strip_tags($i['extmetadata']['Artist']['value'] ?? '')),
                 'gp' => $i['extmetadata']['LicenseShortName']['value'] ?? '?'];
    }
    return $ra;
}

/**
 * Gộp kết quả của TẤT CẢ câu tra thuộc một nhóm, bỏ trùng.
 *
 * Chạy hết các câu chứ không dừng ở câu đầu có kết quả: nhóm "má phanh" có ba
 * mặt hàng, một câu chỉ lọc ra ba tấm hợp lệ nên mặt thứ ba cạn ảnh. Gộp nhiều
 * câu thì kho ứng viên đủ rộng cho cả nhóm.
 */
function timNhieuAnh(array $cacCau, array $daDung){
    $ra = [];
    $daCo = $daDung;
    foreach ($cacCau as $cau){
        foreach (timTheoCau($cau, $daCo) as $a){
            $h = substr(md5($a['thumb']), 0, 8);
            if (in_array($h, $daCo, true)) continue;
            $daCo[] = $h;
            $ra[]   = $a;
        }
        usleep(300000);   // đừng dội API của Commons
    }
    return $ra;
}

function taiVe($thumbUrl, $tenFile, $goc){
    $du = goiApi($thumbUrl);
    if (!$du || !@getimagesizefromstring($du)) return [null, 0];
    $td = 'public/assets/uploads/parts/' . $tenFile . '.jpg';
    file_put_contents($goc . '/' . $td, $du);
    return [$td, strlen($du)];
}

/* ===================================================================== */
$pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
               _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// Ảnh đã dùng cho DANH MỤC cũng phải tránh — không thì thẻ sản phẩm trùng ảnh
// với chính danh mục chứa nó, nhìn rất lười.
$daDungUrl = [];
foreach ($pdo->query("SELECT image FROM part_categories WHERE image LIKE '%.jpg'")->fetchAll(PDO::FETCH_COLUMN) as $f){
    if (preg_match('~-([0-9a-f]{8})\.jpg$~i', basename($f), $m)) $daDungUrl[] = strtolower($m[1]);
}

$sp = $pdo->query(
    "SELECT p.id, p.code, p.name, p.slug, c.slug AS cat
       FROM parts p LEFT JOIN part_categories c ON c.id = p.category_id
      WHERE p.status = 1 AND p.show_on_web = 1
      ORDER BY p.id"
)->fetchAll(PDO::FETCH_ASSOC);

$nguon = []; $tong = 0; $demHang = 0; $demAnh = 0;

echo $THU ? "== CHE DO THU ==\n\n" : "== TAI ANH SAN PHAM ==\n\n";

foreach ($sp as $p){
    // Mặt hàng đang có ảnh THẬT (không phải demo) -> để yên, đó là dữ liệu khách.
    $anhHienCo = $pdo->prepare("SELECT id, image FROM part_images WHERE part_id = ?");
    $anhHienCo->execute([$p['id']]);
    $ds = $anhHienCo->fetchAll(PDO::FETCH_ASSOC);

    $chiToanDemo = !empty($ds);
    foreach ($ds as $a){ if (!preg_match('~-demo\.svg$~i', $a['image'])) $chiToanDemo = false; }

    if (!$chiToanDemo){
        printf("  [DE YEN] %-9s %-32s dang co anh that\n", $p['code'], mb_substr($p['name'], 0, 30));
        continue;
    }

    $tra = $TRA_THEO_MA[$p['code']] ?? ($TRA[$p['cat']] ?? null);
    if (!$tra){
        printf("  [BO QUA] %-9s %-32s chua co cau tra cho nhom \"%s\"\n",
               $p['code'], mb_substr($p['name'], 0, 30), $p['cat'] ?? '-');
        continue;
    }

    $ungVien = timNhieuAnh($tra, $daDungUrl);
    if (empty($ungVien)){
        printf("  [!!    ] %-9s %-32s khong con anh nao chua dung\n", $p['code'], mb_substr($p['name'], 0, 30));
        continue;
    }

    $lay = array_slice($ungVien, 0, MOI_MAT_HANG);
    if ($THU){
        printf("  [THU   ] %-9s %-32s %s\n", $p['code'], mb_substr($p['name'], 0, 30),
               implode(' + ', array_map(function($a){ return mb_substr($a['ten'], 0, 34); }, $lay)));
        foreach ($lay as $a) $daDungUrl[] = substr(md5($a['thumb']), 0, 8);
        continue;
    }

    // Xoá các dòng ảnh demo (chỉ dòng CSDL; file demo dùng chung nhiều mặt hàng
    // nên không đụng tới file trên đĩa).
    $pdo->prepare("DELETE FROM part_images WHERE part_id = ?")->execute([$p['id']]);

    $thu = 0;
    foreach ($lay as $a){
        list($td, $byte) = taiVe($a['thumb'], $p['slug'] . '-' . substr(md5($a['thumb']), 0, 8), $goc);
        if (!$td) continue;
        $thu++;
        $pdo->prepare("INSERT INTO part_images (part_id, image, sort_order, is_primary, create_at) VALUES (?,?,?,?,?)")
            ->execute([$p['id'], basename($td), $thu, $thu === 1 ? 1 : 0, date('Y-m-d H:i:s')]);
        $nguon[] = ['dung' => $p['code'] . ' — ' . $p['name'], 'file' => basename($td)] + $a;
        $daDungUrl[] = substr(md5($a['thumb']), 0, 8);
        $tong += $byte; $demAnh++;
    }
    printf("  [OK    ] %-9s %-32s %d anh\n", $p['code'], mb_substr($p['name'], 0, 30), $thu);
    if ($thu > 0) $demHang++;
    usleep(400000);
}

if ($THU){ echo "\nXong (che do thu).\n"; exit(0); }

/* Nối phần ghi công vào cuối docs/NGUON-ANH.md */
$f = $goc . '/docs/NGUON-ANH.md';
$md = is_file($f) ? file_get_contents($f) : "# Nguồn ảnh minh hoạ\n";
$md .= "\n## Ảnh sản phẩm\n\n";
$md .= "Tải bằng `tools/tai-anh-san-pham.php`. Cùng nguồn Wikimedia Commons.\n\n";
$md .= "| Mặt hàng | File | Tác giả | Giấy phép | Trang gốc |\n|---|---|---|---|---|\n";
foreach ($nguon as $n){
    $md .= sprintf("| %s | `%s` | %s | %s | [Commons](%s) |\n",
        str_replace('|', '/', $n['dung']), $n['file'],
        str_replace('|', '/', mb_substr($n['tacGia'] ?: '(không ghi)', 0, 55)),
        str_replace('|', '/', $n['gp']), $n['trang']);
}
file_put_contents($f, $md);

printf("\nXong: %d mat hang, %d anh, %.1f MB.\n", $demHang, $demAnh, $tong / 1048576);
echo "Ghi cong noi vao docs/NGUON-ANH.md\n";
