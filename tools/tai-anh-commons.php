<?php
/**
 * TẢI ẢNH MINH HOẠ TỪ WIKIMEDIA COMMONS cho băng-rôn + danh mục hàng hoá.
 *
 * Chạy:  C:\xampp\php\php.exe tools\tai-anh-commons.php
 *        C:\xampp\php\php.exe tools\tai-anh-commons.php --thu    (chỉ xem, không tải)
 *
 * VÌ SAO LÀ WIKIMEDIA COMMONS
 * Ảnh ở đây là giấy phép tự do (CC / phạm vi công cộng), tra cứu được bằng API
 * mà KHÔNG cần khoá riêng — Pexels và Unsplash đều bắt đăng ký lấy API key.
 * Kho ảnh của Commons cũng có sẵn ảnh chụp thật đúng từng bộ phận ô tô, hợp
 * với việc cần ảnh cho danh mục phụ tùng.
 *
 * Ảnh KHÔNG ghi đè: mặt hàng/danh mục nào đã có ảnh thì bỏ qua, trừ khi chạy
 * kèm --ghi-de. Chạy lại nhiều lần vẫn an toàn.
 *
 * Kèm theo, ghi ra docs/NGUON-ANH.md: tên file, trang gốc, tác giả, giấy phép.
 * Ảnh CC phần lớn buộc ghi công — có file này thì lúc bảo vệ đồ án hỏi tới là
 * có ngay, mà sau này muốn thay ảnh cũng biết đường lần.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

$THU    = in_array('--thu', $argv, true);
$GHI_DE = in_array('--ghi-de', $argv, true);

// Wikimedia đòi User-Agent mô tả được mình là ai, không thì chặn.
const UA = 'TanPhatStorefront/1.0 (do an sinh vien; lien he: rikkeiedu.ai@gmail.com)';

$goc = dirname(__DIR__);

/* -----------------------------------------------------------------------
   Từ khoá tra cứu cho từng danh mục.
   Khoá = slug trong bảng part_categories. Tra bằng tiếng Anh vì Commons gần
   như không có ảnh gắn thẻ tiếng Việt.
   ----------------------------------------------------------------------- */
$TU_KHOA = [
    // KHOÁ = slug trong bảng part_categories. Giá trị = [câu tra, từ cấm riêng].
    //
    // Hai luật rút ra sau mấy lần chạy thử, đừng phá:
    //   1. CHỈ MỘT `intitle:` mỗi câu. Hai cái trở lên, hoặc dùng OR giữa các
    //      intitle, là CirrusSearch trả về rỗng — không báo lỗi gì cả.
    //   2. Từ khoá phụ để ngoài, không bọc intitle.
    //
    // Mục nào để null là Commons KHÔNG có ảnh tự do dùng được (đã soi tay).
    // Commons là kho tư liệu bách khoa chứ không phải kho ảnh thương mại: nó
    // giàu ảnh BỘ PHẬN thật, nhưng gần như không có ảnh cảnh "thợ đang thay
    // dầu", "gara đang bảo dưỡng" — đúng mấy nhóm dịch vụ.

    // Nhóm gốc
    'phu-tung'         => null,   // ra toàn biển hiệu cửa hàng O'Reilly, ảnh tư liệu quân đội
    'thiet-bi'         => null,   // không có ảnh ngang đủ lớn
    'dich-vu'          => ['intitle:mechanic car repair',  'mural|toy|model|fortepan'],

    // Hệ thống phanh
    'he-thong-phanh'   => ['intitle:"brake caliper"',      'bicycle|bike|motorcycle|banjo'],
    'dia-phanh'        => ['intitle:"brake disc"',         'bicycle|bike|motorcycle|train|bogie|series|railway|rail|tōkyū|tokyu'],
    'ma-phanh'         => ['intitle:"brake pad"',          'bicycle|bike|making|ring|tire|wheel'],
    'dau-phanh'        => ['intitle:"brake fluid"',        'bicycle|bike'],

    // Động cơ
    'dong-co'          => ['intitle:"engine bay"',         'toy|aircraft|steam|toolbox'],
    'loc-dau'          => ['intitle:"oil filter"',         'wrench|machine|transformer|motorcycle|valvula'],
    'loc-gio'          => ['intitle:"air filter" car',     'army|navy|soldier|dirty'],
    'bugi'             => ['intitle:"spark plug"',         'advert|sign|flint|wrench|spirit|louis|set from|mascot|cat|office|head office|co\.,ltd|building|factory'],
    'day-curoa'        => ['intitle:"timing belt"',        'drawing|market|coffee|kaffee'],

    // Hệ thống điện
    'he-thong-dien'    => null,   // "wiring harness" chỉ ra sơ đồ và dây máy bay
    'ac-quy'           => ['intitle:"car battery"',        'charger|tester|charging'],
    'den-xe'           => ['intitle:headlamps',            'drawing|museum|geograph|parkend|wilbert|locomotive|rail|removing|steam'],
    'may-phat'         => ['intitle:alternator car',       'tester|regulator|controller|transmission|barrage|diesel'],

    // Hệ thống treo
    'he-thong-treo'    => ['intitle:suspension car',       'bridge|cable|wind|ingenuity|mars|train|stayed|basic|construction|wishbone diagram|principle'],
    'giam-xoc'         => ['intitle:"shock absorber"',     'bicycle|train|museu|steelpr'],
    'lo-xo'            => null,   // "coil spring" ra cây cảnh Lagerstroemia

    // Thiết bị
    'cau-nang'         => ['intitle:"car lift"',           'forklift|elevator|ski|newark|penn|zil|tlt|cable car|funicular'],

    // Dịch vụ
    'thay-dau'         => null,
    'bao-duong'        => ['intitle:mechanic engine',      'aircraft|plane|ship|army|aviation|ch-47'],
    'thay-ma-phanh'    => null,
    'sua-chua'         => null,
];

/* Băng-rôn trang chủ — cần ảnh RẤT ngang (tỉ lệ >= 1.7) */
$BANG_RON = [
    ['Phụ tùng chính hãng cho mọi dòng xe', ['intitle:"auto parts"',   'logo|sign|truck']],
    ['Thiết bị gara chuyên nghiệp',          ['intitle:garage car',     'sign|logo|door|house']],
    ['Dịch vụ bảo dưỡng tận tâm',            ['intitle:mechanic car',   'mural|toy|model']],
];

/* Từ cấm dùng chung cho MỌI lượt tra — Commons đầy ảnh quảng cáo cũ, tem thư,
   sơ đồ kỹ thuật và ảnh tư liệu Thư viện Quốc hội Mỹ, lọt vào là hỏng cả trang. */
const CAM_CHUNG = 'advertis|poster|stamp|patent|diagram|schematic|blueprint|magazine|'
                . 'cover|logo|map |coat of arms|1900|191|192|193|194|195|LOC |museum exhibit|'
                . 'DPLA|NARA|nationaal archief';

/* -----------------------------------------------------------------------
   Gọi API Commons: tìm ảnh bitmap, lấy bản thu nhỏ theo chiều rộng mong muốn
   ----------------------------------------------------------------------- */
function goiApi($url){
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT      => UA,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $kq = curl_exec($ch);
    $loi = curl_error($ch);
    curl_close($ch);
    if ($kq === false) throw new RuntimeException('curl: ' . $loi);
    return $kq;
}

/**
 * Tìm ảnh hợp nhất cho một danh mục.
 *
 * BÀI HỌC TỪ BẢN ĐẦU: bản đầu xếp lại toàn bộ kết quả theo tỉ lệ khung hình,
 * tức là VỨT BỎ thứ tự liên quan mà Commons trả về. Kết quả chạy thử: "đĩa
 * phanh" ra ảnh chuông báo cháy, "lọc gió" ra tàu hoả Úc, "dây curoa" ra chiếc
 * Yugo 1987. Nay thứ tự liên quan là chính, tỉ lệ khung hình chỉ là điểm cộng.
 *
 * Hai chốt lọc nữa, đều rút ra từ lần chạy thử:
 *   - intitle: — tên file trên Commons mô tả rất sát nội dung, ép từ khoá nằm
 *     trong TÊN FILE thì độ chính xác lên hẳn so với tìm toàn văn.
 *   - từ cấm — Commons đầy quảng cáo thập niên 1920, tem thư, sơ đồ kỹ thuật.
 *     Tra "spark plug" ra biển hiệu văn phòng NGK và một con mèo tên Spark Plug.
 *
 * @param array $tra      [câu tra intitle, các từ cấm riêng ngăn bằng |]
 * @param float $tiLeToiThieu tỉ lệ ngang/dọc tối thiểu (thẻ 1.2, băng-rôn 1.7)
 * @param array $daDung   thumb url đã dùng cho danh mục khác — tránh trùng ảnh
 */
function timAnh(array $tra, $rongThumb = 900, $tiLeToiThieu = 1.2, array $daDung = []){
    list($cauTra, $camRieng) = $tra;

    $url = 'https://commons.wikimedia.org/w/api.php?' . http_build_query([
        'action'      => 'query',
        'generator'   => 'search',
        'gsrsearch'   => 'filetype:bitmap ' . $cauTra,
        'gsrnamespace'=> 6,
        'gsrlimit'    => 20,
        'prop'        => 'imageinfo',
        'iiprop'      => 'url|mime|size|extmetadata',
        'iiurlwidth'  => $rongThumb,
        'format'      => 'json',
    ]);

    $j = json_decode(goiApi($url), true);
    if (empty($j['query']['pages'])) return null;

    // API trả về map không theo thứ tự; khoá `index` mới là hạng liên quan.
    $trang = array_values($j['query']['pages']);
    usort($trang, function($a, $b){ return ($a['index'] ?? 999) <=> ($b['index'] ?? 999); });

    $cam = CAM_CHUNG . ($camRieng !== '' ? '|' . $camRieng : '');

    $ungVien = [];
    foreach ($trang as $hang => $p){
        if (empty($p['imageinfo'][0])) continue;
        $i = $p['imageinfo'][0];

        // Chỉ nhận jpg/png. Bỏ svg (gần như luôn là sơ đồ), gif và tif.
        if (!in_array($i['mime'], ['image/jpeg', 'image/png'], true)) continue;
        if (empty($i['thumburl'])) continue;
        if ($i['width'] < 640) continue;
        if (in_array($i['thumburl'], $daDung, true)) continue;

        $ten = mb_substr($p['title'], 5);        // bỏ tiền tố "File:"
        if (preg_match('~(' . $cam . ')~i', $ten)) continue;

        $tiLe = $i['width'] / max(1, $i['height']);

        // CHỈ giữ ảnh ngang, rồi lấy ĐÚNG cái đứng đầu theo thứ tự liên quan.
        // Mọi kiểu chấm điểm thêm đều làm hỏng — đã thử cộng điểm theo tỉ lệ
        // khung hình, nó đẩy ảnh lạc đề lên trên ảnh đúng đề.
        if ($tiLe < $tiLeToiThieu) continue;
        $diem = -$hang;

        $ungVien[] = [
            'title'   => $ten,
            'thumb'   => $i['thumburl'],
            'trang'   => $i['descriptionurl'] ?? '',
            'tacGia'  => trim(strip_tags($i['extmetadata']['Artist']['value'] ?? '')),
            'giayPhep'=> $i['extmetadata']['LicenseShortName']['value'] ?? 'khong ro',
            'tiLe'    => round($tiLe, 2),
            'diem'    => $diem,
        ];
    }
    if (empty($ungVien)) return null;

    usort($ungVien, function($a, $b){ return $b['diem'] <=> $a['diem']; });
    return $ungVien[0];
}

/** Tải một ảnh về đĩa. Trả về [đường dẫn tương đối, số byte]. */
function taiVe($thumbUrl, $thuMuc, $tenFile, $goc){
    $duoi = 'jpg';
    if (preg_match('~\.(png)(\?|$)~i', $thumbUrl)) $duoi = 'png';

    $tuongDoi = 'public/assets/uploads/' . $thuMuc . '/' . $tenFile . '.' . $duoi;
    $tuyetDoi = $goc . '/' . $tuongDoi;

    $ch = curl_init($thumbUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT      => UA,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $du = curl_exec($ch);
    $ma = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($du === false || $ma !== 200) return [null, 0];

    // Kiểm tra đúng là ảnh thật rồi mới ghi — Commons thỉnh thoảng trả trang
    // lỗi HTML với mã 200.
    $info = @getimagesizefromstring($du);
    if ($info === false) return [null, 0];

    file_put_contents($tuyetDoi, $du);
    return [$tuongDoi, strlen($du)];
}

/* ======================================================================= */

$pdo = new PDO(
    'mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
    _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$nguon  = [];   // để ghi file ghi công
$daDung = [];   // ảnh đã lấy — hai danh mục cạnh nhau mà cùng một ảnh thì lộ ngay
$tongByte = 0;
$demTai = 0;
$demBoQua = 0;

echo $THU ? "== CHE DO THU (khong tai, khong ghi CSDL) ==\n\n" : "== TAI ANH ==\n\n";

/* ---- Danh mục ---- */
$dsCat = $pdo->query("SELECT id, name, slug, image FROM part_categories ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

foreach ($dsCat as $c){
    $slug = $c['slug'];
    if (empty($TU_KHOA[$slug])){
        printf("  [BO QUA] %-22s Commons khong co anh tu do dung duoc\n", mb_substr($c['name'], 0, 20));
        $demBoQua++;
        continue;
    }
    if (!empty($c['image']) && !$GHI_DE){
        printf("  [DA CO ] %-22s %s\n", mb_substr($c['name'], 0, 20), $c['image']);
        $demBoQua++;
        continue;
    }

    $anh = timAnh($TU_KHOA[$slug], 1000, 1.2, $daDung);
    if (!$anh){
        printf("  [!! ] %-22s khong tim thay anh cho \"%s\"\n", mb_substr($c['name'], 0, 20), $TU_KHOA[$slug]);
        continue;
    }

    if ($THU){
        printf("  [THU ] %-22s %-56s %.2f\n", mb_substr($c['name'], 0, 20), mb_substr($anh['title'], 0, 54), $anh['tiLe']);
        $daDung[] = $anh['thumb'];
        continue;
    }

    list($duongDan, $byte) = taiVe($anh['thumb'], 'categories', $slug . '-' . substr(md5($anh['thumb']), 0, 8), $goc);
    if (!$duongDan){
        printf("  [!! ] %-22s tai that bai\n", mb_substr($c['name'], 0, 20));
        continue;
    }

    $pdo->prepare("UPDATE part_categories SET image = ? WHERE id = ?")->execute([$duongDan, $c['id']]);
    printf("  [OK  ] %-22s %6.1f KB  %s\n", mb_substr($c['name'], 0, 20), $byte / 1024, basename($duongDan));

    $nguon[]  = ['dung' => 'Danh mục: ' . $c['name'], 'file' => basename($duongDan)] + $anh;
    $daDung[] = $anh['thumb'];
    $tongByte += $byte;
    $demTai++;
    usleep(400000);   // đừng dội API của Commons
}

/* ---- Băng-rôn ---- */
echo "\n-- Bang-ron --\n";
$coBanner = (int) $pdo->query("SELECT COUNT(*) FROM banners")->fetchColumn();

foreach ($BANG_RON as $i => $br){
    list($tieuDe, $tuKhoa) = $br;

    $anh = timAnh($tuKhoa, 1600, 1.7, $daDung);   // băng-rôn là dải rất ngang
    if (!$anh){ printf("  [!! ] %-38s khong tim thay anh\n", mb_substr($tieuDe, 0, 36)); continue; }

    if ($THU){
        printf("  [THU ] %-38s %s\n", mb_substr($tieuDe, 0, 36), mb_substr($anh['title'], 5, 60));
        continue;
    }

    list($duongDan, $byte) = taiVe($anh['thumb'], 'banners', 'bn-' . ($i + 1) . '-' . substr(md5($anh['thumb']), 0, 8), $goc);
    if (!$duongDan){ printf("  [!! ] %-38s tai that bai\n", mb_substr($tieuDe, 0, 36)); continue; }

    $pdo->prepare(
        "INSERT INTO banners (title, image, link, sort_order, status, create_at) VALUES (?,?,?,?,1,?)"
    )->execute([$tieuDe, $duongDan, '', $i + 1, date('Y-m-d H:i:s')]);

    printf("  [OK  ] %-38s %6.1f KB  %s\n", mb_substr($tieuDe, 0, 36), $byte / 1024, basename($duongDan));
    $nguon[]  = ['dung' => 'Băng-rôn: ' . $tieuDe, 'file' => basename($duongDan)] + $anh;
    $daDung[] = $anh['thumb'];
    $tongByte += $byte;
    $demTai++;
    usleep(400000);
}

if ($THU){ echo "\nXong (che do thu).\n"; exit(0); }

/* ---- Ghi file ghi công ---- */
$md  = "# Nguồn ảnh minh hoạ\n\n";
$md .= "Toàn bộ ảnh dưới đây tải từ **Wikimedia Commons** bằng `tools/tai-anh-commons.php`.\n";
$md .= "Đây là ảnh giấy phép tự do (CC / phạm vi công cộng). Phần lớn giấy phép CC\n";
$md .= "buộc GHI CÔNG tác giả — bảng này chính là chỗ ghi công đó.\n\n";
$md .= "Sinh tự động, đừng sửa tay: chạy lại script là ghi đè.\n\n";
$md .= "| Dùng ở đâu | File | Tác giả | Giấy phép | Trang gốc |\n";
$md .= "|---|---|---|---|---|\n";
foreach ($nguon as $n){
    $md .= sprintf("| %s | `%s` | %s | %s | [Commons](%s) |\n",
        str_replace('|', '/', $n['dung']),
        $n['file'],
        str_replace('|', '/', mb_substr($n['tacGia'] ?: '(không ghi)', 0, 60)),
        str_replace('|', '/', $n['giayPhep']),
        $n['trang']);
}
file_put_contents($goc . '/docs/NGUON-ANH.md', $md);

printf("\nXong: tai %d anh, tong %.1f MB, bo qua %d.\n", $demTai, $tongByte / 1048576, $demBoQua);
echo "Ghi cong: docs/NGUON-ANH.md\n";
if ($coBanner > 0) echo "LUU Y: bang banners da co $coBanner dong tu truoc (cai ten \"ssss\"), vao admin xoa neu khong dung.\n";
