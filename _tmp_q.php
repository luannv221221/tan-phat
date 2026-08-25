<?php
$db = new PDO('mysql:host=127.0.0.1;port=3306;dbname=tanphat_php;charset=utf8mb4',
              'root','123456',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);

$bang = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$tong = 0; $chiTiet = [];

foreach ($bang as $t){
    $cols = $db->query("SHOW COLUMNS FROM `$t`")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c){
        if (!preg_match('/char|text/i', $c['Type'])) continue;
        $f = $c['Field'];
        try {
            $n = $db->query("SELECT COUNT(*) FROM `$t` WHERE `$f` REGEXP '&#[0-9]+;'")->fetchColumn();
        } catch (Exception $e){ continue; }
        if ($n > 0){
            $tong += $n;
            $vd = $db->query("SELECT `$f` FROM `$t` WHERE `$f` REGEXP '&#[0-9]+;' LIMIT 1")->fetchColumn();
            $chiTiet[] = sprintf("%-24s %-18s %3d dong   vd: %s", $t, $f, $n, mb_substr($vd,0,70));
        }
    }
}
echo "=== Cot co chuoi dang &#nn; ===\n";
echo implode("\n", $chiTiet), "\n\nTONG: $tong dong\n";

echo "\n=== Bao nhieu lop long nhau ===\n";
foreach ($db->query("SELECT skey,svalue FROM site_settings")->fetchAll(PDO::FETCH_ASSOC) as $r){
    $v = $r['svalue']; $lop = 0;
    while (($d = html_entity_decode($v, ENT_QUOTES, 'UTF-8')) !== $v){ $v = $d; $lop++; if($lop>9) break; }
    if ($lop > 0) printf("%-20s lop=%d  \"%s\"  ->  \"%s\"\n", $r['skey'], $lop, mb_substr($r['svalue'],0,45), mb_substr($v,0,45));
}
