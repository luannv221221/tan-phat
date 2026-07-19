<?php
/**
 * SEED DỮ LIỆU DEMO — chạy CLI:  php database/seed_demo.php  [force]
 *
 * Nạp dữ liệu mẫu cho toàn hệ thống: danh mục xe/phụ tùng, kho + vị trí + tồn
 * (ghi sổ qua model nên tồn kho + KT-6 nhất quán), đối tác, bán hàng (hoá đơn +
 * báo giá + đơn web giữ tồn), CSKH (bảo hành + BB + bản tin + liên hệ + đánh giá),
 * nhân sự, nội dung web. Idempotent: đã seed thì bỏ qua (trừ khi truyền 'force').
 */

if (PHP_SAPI !== 'cli'){ http_response_code(403); die("Chi chay tu CLI.\n"); }

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';
foreach (scandir(__DIR__ . '/../configs') as $f){ if ($f!=='.'&&$f!=='..') require_once __DIR__ . '/../configs/' . $f; }

use App\core\Database;

$M = __DIR__ . '/../app/models/';
foreach (['StocksModel','AccAccountsModel','AccVouchersModel','AccVoucherEntriesModel',
          'GoodsReceiptsModel','SalesInvoicesModel','OrdersModel','OrderItemsModel',
          'StockReservationsModel','QuotationsModel','QuotationItemsModel',
          'WarehouseLocationsModel','WarrantyRequestsModel','WarrantyHandoversModel'] as $m){
    require_once $M . $m . '.php';
}

$db    = new Database();
$stock = new StocksModel();
$acc   = new AccAccountsModel();
$vch   = new AccVouchersModel();
$ent   = new AccVoucherEntriesModel();
$recM  = new GoodsReceiptsModel();
$invM  = new SalesInvoicesModel();
$ordM  = new OrdersModel();
$oiM   = new OrderItemsModel();
$resv  = new StockReservationsModel();
$qM    = new QuotationsModel();
$qiM   = new QuotationItemsModel();

$force = (isset($argv[1]) && $argv[1] === 'force');
$now   = date('Y-m-d H:i:s');

// ---------- helpers ----------
function idOf($db,$t,$col,$val){ $r=$db->table($t)->where($col,'=',$val)->first(); return $r? (int)$r['id'] : 0; }
function slugify_($s){ $s=trim($s); $tr=['à'=>'a','á'=>'a','ả'=>'a','ã'=>'a','ạ'=>'a','ă'=>'a','ắ'=>'a','ằ'=>'a','ẳ'=>'a','ẵ'=>'a','ặ'=>'a','â'=>'a','ấ'=>'a','ầ'=>'a','ẩ'=>'a','ẫ'=>'a','ậ'=>'a','đ'=>'d','è'=>'e','é'=>'e','ẻ'=>'e','ẽ'=>'e','ẹ'=>'e','ê'=>'e','ế'=>'e','ề'=>'e','ể'=>'e','ễ'=>'e','ệ'=>'e','ì'=>'i','í'=>'i','ỉ'=>'i','ĩ'=>'i','ị'=>'i','ò'=>'o','ó'=>'o','ỏ'=>'o','õ'=>'o','ọ'=>'o','ô'=>'o','ố'=>'o','ồ'=>'o','ổ'=>'o','ỗ'=>'o','ộ'=>'o','ơ'=>'o','ớ'=>'o','ờ'=>'o','ở'=>'o','ỡ'=>'o','ợ'=>'o','ù'=>'u','ú'=>'u','ủ'=>'u','ũ'=>'u','ụ'=>'u','ư'=>'u','ứ'=>'u','ừ'=>'u','ử'=>'u','ữ'=>'u','ự'=>'u','ỳ'=>'y','ý'=>'y','ỷ'=>'y','ỹ'=>'y','ỵ'=>'y']; $s=mb_strtolower($s,'UTF-8'); $s=strtr($s,$tr); $s=preg_replace('/[^a-z0-9]+/','-',$s); return trim($s,'-'); }

$log = function($s){ echo $s . "\n"; };

// ---------- guard ----------
if (!$force && idOf($db,'parts','code','PT-0001') > 0){
    $log("Da co du lieu demo (PT-0001). Chay lai voi:  php database/seed_demo.php force");
    exit(0);
}

$log("== SEED DEMO — DB " . _DB . " ==");

// ---------- tài khoản kế toán ----------
$A = [];
foreach (['131','156','331','511','3331','632','711'] as $c){ $r=$acc->findByCode($c); $A[$c]= $r? (int)$r['id'] : 0; }
$whId = idOf($db,'warehouses','code','KHO01');

// ---------- 1) Cấu hình website ----------
$settings = [
    'site_name' => 'Công ty TNHH Phụ tùng Ô tô Tân Phát',
    'site_slogan' => 'Phụ tùng & thiết bị gara ô tô chính hãng',
    'meta_description' => 'Tân Phát - nhà cung cấp phụ tùng và thiết bị gara ô tô chính hãng. Tư vấn tương thích theo hãng, model, đời xe.',
    'meta_keywords' => 'phụ tùng ô tô, thiết bị gara, má phanh, lọc dầu, ắc quy, Tân Phát',
    'hotline' => '1900 6363', 'email' => 'info@tanphat.vn',
    'address' => 'Số 88 Nguyễn Văn Cừ, Long Biên, Hà Nội',
    'facebook' => 'https://facebook.com/tanphat.auto', 'zalo' => '1900 6363',
    'tax_code' => '0101234567',
    'bank_name' => 'Vietcombank - CN Hà Nội', 'bank_account' => '0011000123456',
    'bank_holder' => 'CONG TY TNHH PHU TUNG O TO TAN PHAT',
];
foreach ($settings as $k=>$v){
    if (idOf($db,'site_settings','skey',$k)>0) $db->update('site_settings',['svalue'=>$v,'update_at'=>$now],'`skey` = ?',[$k]);
    else $db->insert('site_settings',['skey'=>$k,'svalue'=>$v,'update_at'=>$now]);
}
$log("- Cau hinh website");

// ---------- 2) Model xe + đời xe ----------
$brand = function($n) use($db){ return idOf($db,'car_brands','name',$n); };
$body  = function($n) use($db){ return idOf($db,'car_body_types','name',$n); };
$models = [
    ['Toyota','Vios','Sedan',[[2014,2018],[2018,2023],[2023,null]]],
    ['Toyota','Camry','Sedan',[[2015,2019],[2019,null]]],
    ['Toyota','Fortuner','SUV',[[2016,2020],[2020,null]]],
    ['Toyota','Innova','MPV',[[2016,2023]]],
    ['Honda','City','Sedan',[[2014,2020],[2020,null]]],
    ['Honda','CR-V','SUV',[[2017,2022],[2022,null]]],
    ['Honda','Civic','Sedan',[[2016,2021],[2021,null]]],
    ['Kia','Morning','Hatchback',[[2015,2020],[2020,null]]],
    ['Kia','Seltos','Crossover',[[2020,null]]],
    ['Hyundai','Accent','Sedan',[[2018,2023],[2023,null]]],
    ['Hyundai','Santa Fe','SUV',[[2019,null]]],
    ['Mazda','Mazda3','Sedan',[[2015,2019],[2019,null]]],
    ['Mazda','CX-5','Crossover',[[2017,2022],[2022,null]]],
    ['Ford','Ranger','Bán tải',[[2015,2022],[2022,null]]],
    ['Ford','Everest','SUV',[[2018,2022],[2022,null]]],
];
$nModel=0;$nYear=0;
foreach ($models as $m){
    $bId=$brand($m[0]); $btId=$body($m[2]);
    if ($bId<=0) continue;
    $slug=slugify_($m[0].' '.$m[1]);
    $mid=idOf($db,'car_models','slug',$slug);
    if ($mid<=0){
        $db->insert('car_models',['brand_id'=>$bId,'body_type_id'=>$btId?:null,'name'=>$m[1],'slug'=>$slug,'sort_order'=>0,'status'=>1,'create_at'=>$now]);
        $mid=idOf($db,'car_models','slug',$slug); $nModel++;
    }
    foreach ($m[3] as $yr){
        $label=$yr[1]? ($yr[0].'-'.$yr[1]) : ($yr[0].'+');
        $ex=$db->table('car_years')->where('model_id','=',$mid)->where('year_from','=',$yr[0])->first();
        if (empty($ex)){
            $db->insert('car_years',['model_id'=>$mid,'year_from'=>$yr[0],'year_to'=>$yr[1],'name'=>$m[1].' '.$label,'status'=>1,'create_at'=>$now]); $nYear++;
        }
    }
}
$log("- Model xe: +$nModel, doi xe: +$nYear");

// ---------- 3) Kho + vị trí ----------
if (idOf($db,'warehouses','code','KHO02')<=0){
    $db->insert('warehouses',['code'=>'KHO02','name'=>'Kho chi nhánh Miền Nam','address'=>'KCN Sóng Thần, Bình Dương','phone'=>'0274 3777 999','is_default'=>0,'sort_order'=>1,'status'=>1,'create_at'=>$now]);
}
// cây vị trí cho KHO01: Khu A/B -> Tầng -> Kệ
$mkLoc = function($code,$name,$parentId,$level,$path) use($db,$whId,$now){
    if (idOf($db,'warehouse_locations','code',$code)>0) return idOf($db,'warehouse_locations','code',$code);
    $db->insert('warehouse_locations',['warehouse_id'=>$whId,'parent_id'=>$parentId?:null,'code'=>$code,'name'=>$name,'level'=>$level,'full_path'=>$path,'sort_order'=>0,'status'=>1,'create_at'=>$now]);
    return idOf($db,'warehouse_locations','code',$code);
};
$khuA=$mkLoc('A','Khu A',0,1,'Khu A');
$aT1=$mkLoc('A-T1','Tầng 1',$khuA,2,'Khu A / Tầng 1');
$aT2=$mkLoc('A-T2','Tầng 2',$khuA,2,'Khu A / Tầng 2');
$mkLoc('A-T1-K1','Kệ 1',$aT1,3,'Khu A / Tầng 1 / Kệ 1');
$mkLoc('A-T1-K2','Kệ 2',$aT1,3,'Khu A / Tầng 1 / Kệ 2');
$mkLoc('A-T2-K1','Kệ 1',$aT2,3,'Khu A / Tầng 2 / Kệ 1');
$khuB=$mkLoc('B','Khu B',0,1,'Khu B');
$mkLoc('B-T1','Tầng 1',$khuB,2,'Khu B / Tầng 1');
$log("- Kho KHO02 + cay vi tri KHO01");

// ---------- 4) Phụ tùng ----------
$cat=function($n) use($db){ return idOf($db,'part_categories','name',$n); };
$pb =function($n) use($db){ return idOf($db,'product_brands','name',$n); };
$un =function($n) use($db){ return idOf($db,'product_units','name',$n); };
$org=function($n) use($db){ return idOf($db,'product_origins','name',$n); };
// [code, oem, name, cat, brand, unit, origin, price, sale, warranty]
$parts = [
    ['PT-0001','04465-0D260','Má phanh trước Toyota Vios','Má phanh','Bosch','Bộ','Nhật Bản',650000,590000,6],
    ['PT-0002','04466-33471','Má phanh sau Toyota Camry','Má phanh','Aisin','Bộ','Nhật Bản',720000,680000,6],
    ['PT-0003','43512-06130','Đĩa phanh trước Vios','Đĩa phanh','Bosch','Chiếc','Nhật Bản',980000,920000,12],
    ['PT-0004','90915-YZZD4','Lọc dầu động cơ Toyota','Lọc dầu','Denso','Chiếc','Nhật Bản',120000,99000,3],
    ['PT-0005','17801-0D060','Lọc gió động cơ Vios','Lọc gió','Mann Filter','Chiếc','Đức',180000,159000,3],
    ['PT-0006','SK20R11','Bugi Iridium NGK','Bugi','NGK','Chiếc','Nhật Bản',210000,189000,6],
    ['PT-0007','13568-09210','Dây curoa cam Toyota','Dây curoa','Toyota Genuine','Chiếc','Nhật Bản',850000,800000,12],
    ['PT-0008','28800-N','Ắc quy GS 45Ah','Ắc quy','Bosch','Bình','Việt Nam',1450000,1380000,18],
    ['PT-0009','81150-0D','Đèn pha Toyota Vios LED','Đèn','Toyota Genuine','Chiếc','Thái Lan',2650000,2500000,12],
    ['PT-0010','27060-0','Máy phát điện Honda City','Máy phát','Denso','Chiếc','Nhật Bản',3850000,3650000,12],
    ['PT-0011','48510-','Giảm xóc trước Mazda CX-5','Giảm xóc','Aisin','Chiếc','Nhật Bản',1950000,1850000,12],
    ['PT-0012','48231-','Lò xo giảm xóc sau Ranger','Lò xo','Bosch','Chiếc','Thái Lan',680000,640000,6],
    ['PT-0013','04152-YZZA1','Lọc dầu Honda CR-V','Lọc dầu','Denso','Chiếc','Nhật Bản',135000,115000,3],
    ['PT-0014','17220-5','Lọc gió Honda City','Lọc gió','Mann Filter','Chiếc','Đức',195000,175000,3],
    ['PT-0015','DF6H-11','Bugi NGK Laser Kia','Bugi','NGK','Chiếc','Nhật Bản',230000,209000,6],
    ['PT-0016','45022-','Má phanh trước Ford Ranger','Má phanh','Bosch','Bộ','Đức',890000,840000,6],
];
$nPart=0;
foreach ($parts as $p){
    if (idOf($db,'parts','code',$p[0])>0) continue;
    $db->insert('parts',[
        'code'=>$p[0],'oem_code'=>$p[1],'name'=>$p[2],'slug'=>slugify_($p[2]).'-'.strtolower($p[0]),
        'category_id'=>$cat($p[3])?:null,'brand_id'=>$pb($p[4])?:null,'unit_id'=>$un($p[5])?:null,'origin_id'=>$org($p[6])?:null,
        'price'=>$p[7],'sale_price'=>$p[8],'warranty_month'=>$p[9],
        'description'=>'Phụ tùng chính hãng '.$p[4].', bảo hành '.$p[9].' tháng.','status'=>1,'create_at'=>$now,
    ]);
    $nPart++;
}
$log("- Phu tung: +$nPart");
$pid=function($code) use($db){ return idOf($db,'parts','code',$code); };

// ---------- 5) Đối tác (KH + NCC) ----------
$grp=function($n) use($db){ return idOf($db,'customer_groups','name',$n); };
$partners = [
    ['KH-0001','Garage Thành Công','customer','Garage đối tác','0901234567','12 Trần Phú, Hà Nội','0102030405'],
    ['KH-0002','Đại lý phụ tùng Phú Sơn','customer','Đại lý','0912345678','45 Lê Lợi, Bắc Ninh','0203040506'],
    ['KH-0003','Anh Nguyễn Văn Hùng','customer','Khách lẻ','0987654321','89 Nguyễn Trãi, Hà Nội',null],
    ['KH-0004','Gara Ô tô Minh Phát','customer','Garage đối tác','0934567890','203 Giải Phóng, Hà Nội','0304050607'],
    ['NCC-001','Công ty Bosch Việt Nam','supplier',null,'02838220000','Long Thành, Đồng Nai','0300123456'],
    ['NCC-002','NCC Phụ tùng Miền Bắc','supplier',null,'02439990000','Gia Lâm, Hà Nội','0100987654'],
];
$nPn=0;
foreach ($partners as $p){
    if (idOf($db,'partners','code',$p[0])>0) continue;
    $db->insert('partners',['code'=>$p[0],'name'=>$p[1],'type'=>$p[2],'group_id'=>$p[3]?($grp($p[3])?:null):null,
        'tax_code'=>$p[6],'phone'=>$p[4],'address'=>$p[5],'sort_order'=>0,'status'=>1,'create_at'=>$now]);
    $nPn++;
}
$log("- Doi tac: +$nPn");
$pnId=function($code) use($db){ return idOf($db,'partners','code',$code); };

// ---------- 6) Nhập kho (ghi sổ: tồn + KT-6) ----------
$seedReceipt = function($whId,$partnerCode,$partnerName,$date,$lines,$type='nhap_mua')
    use($db,$recM,$stock,$vch,$ent,$A,$pnId,$now){
    $no=$recM->nextNo();
    $total=0.0; foreach($lines as $l){ $total += $l[1]*$l[2]; }
    $partnerId = $partnerCode ? ($pnId($partnerCode)?:null) : null;
    $recId=$recM->add(['receipt_no'=>$no,'receipt_type'=>$type,'warehouse_id'=>$whId,
        'partner_id'=>$partnerId,'partner_name'=>$partnerName,'counter_account_id'=>$A['331']?:null,
        'receipt_date'=>$date,'reason'=>'Nhập mua hàng','total_amount'=>$total,'status'=>1,'created_by'=>1]);
    foreach($lines as $l){
        $amt=round($l[1]*$l[2],2);
        $db->insert('goods_receipt_items',['receipt_id'=>$recId,'part_id'=>$l[0],'quantity'=>$l[1],'unit_cost'=>$l[2],'amount'=>$amt,'location'=>isset($l[3])?$l[3]:null,'location_id'=>null,'note'=>null]);
        $stock->applyIn($whId,$l[0],$l[1],$l[2],'receipt',$recId,$no,$date,null);
    }
    if ($A['156']&&$A['331']){
        $vid=$vch->add(['voucher_no'=>$vch->nextNo('ke_toan'),'voucher_type'=>'ke_toan','voucher_date'=>$date,
            'cash_account_id'=>null,'partner_id'=>$partnerId,'partner_name'=>$partnerName,
            'reason'=>'Tự động từ phiếu nhập '.$no,'amount'=>$total,'status'=>1]);
        $ent->addJournalLine($vid,$A['156'],$A['331'],$total,'Nhập kho '.$no);
        $recM->edit(['acc_voucher_id'=>$vid],$recId);
    }
    return $recId;
};
if ($force || $db->table('goods_receipts')->where('reason','=','Nhập mua hàng')->first()===false || true){
    // nhập lô lớn vào KHO01 để có tồn bán
    $seedReceipt($whId,'NCC-001','Công ty Bosch Việt Nam','2026-03-05',[
        [$pid('PT-0001'),40,420000,'Khu A / Tầng 1 / Kệ 1'],[$pid('PT-0003'),25,650000,'Khu A / Tầng 1 / Kệ 1'],
        [$pid('PT-0008'),30,1050000,'Khu A / Tầng 2 / Kệ 1'],[$pid('PT-0016'),20,600000,'Khu A / Tầng 1 / Kệ 2'],
    ]);
    $seedReceipt($whId,'NCC-002','NCC Phụ tùng Miền Bắc','2026-04-12',[
        [$pid('PT-0004'),120,70000],[$pid('PT-0005'),80,110000],[$pid('PT-0006'),100,130000],
        [$pid('PT-0007'),15,560000],[$pid('PT-0013'),90,80000],[$pid('PT-0014'),70,120000],[$pid('PT-0015'),60,150000],
    ]);
    $seedReceipt($whId,'NCC-001','Công ty Bosch Việt Nam','2026-05-20',[
        [$pid('PT-0002'),22,480000],[$pid('PT-0009'),12,1900000],[$pid('PT-0010'),8,2900000],
        [$pid('PT-0011'),16,1450000],[$pid('PT-0012'),24,470000],
    ]);
    $log("- Nhap kho: 3 phieu (da ghi so + KT-6)");
}

// ---------- 7) Hoá đơn bán (ghi sổ: doanh thu/thuế/giá vốn + trừ tồn) ----------
$seedInvoice = function($whId,$custCode,$custName,$date,$vat,$lines,$issueEinvoice=false)
    use($db,$invM,$stock,$vch,$ent,$A,$pnId,$now){
    $no=$invM->nextNo(); $custId=$custCode?($pnId($custCode)?:null):null;
    $invId=$invM->add(['invoice_no'=>$no,'customer_id'=>$custId,'customer_name'=>$custName,'warehouse_id'=>$whId,
        'invoice_date'=>$date,'vat_rate'=>$vat,'subtotal'=>0,'tax_amount'=>0,'total_amount'=>0,'cost_amount'=>0,'status'=>0,'created_by'=>1]);
    $sub=0.0;$cost=0.0;
    foreach($lines as $l){
        $disc=isset($l[3])?$l[3]:0; $amt=round($l[1]*$l[2]*(1-$disc/100),2);
        $avg=$stock->applyOut($whId,$l[0],$l[1],'sale_invoice',$invId,$no,$date,null);
        $ca=round($l[1]*$avg,2);
        $db->insert('sales_invoice_items',['invoice_id'=>$invId,'part_id'=>$l[0],'quantity'=>$l[1],'unit_price'=>$l[2],'discount_percent'=>$disc,'amount'=>$amt,'unit_cost'=>$avg,'cost_amount'=>$ca,'note'=>null]);
        $sub+=$amt;$cost+=$ca;
    }
    $tax=round($sub*$vat/100,2);$tot=$sub+$tax;
    $ei = $issueEinvoice ? ['einvoice_status'=>'issued','einvoice_serial'=>'K26TTP','einvoice_form'=>'1','einvoice_no'=>$invM->nextEinvoiceNo(),'einvoice_issued_at'=>$now] : [];
    if ($A['131']&&$A['511']){
        $vid=$vch->add(['voucher_no'=>$vch->nextNo('ke_toan'),'voucher_type'=>'ke_toan','voucher_date'=>$date,
            'cash_account_id'=>null,'partner_id'=>$custId,'partner_name'=>$custName,'reason'=>'Tự động từ hoá đơn '.$no,'amount'=>$tot,'status'=>1]);
        $ent->addJournalLine($vid,$A['131'],$A['511'],$sub,'Doanh thu '.$no);
        if ($tax>0) $ent->addJournalLine($vid,$A['131'],$A['3331'],$tax,'Thuế GTGT '.$no);
        if ($cost>0) $ent->addJournalLine($vid,$A['632'],$A['156'],$cost,'Giá vốn '.$no);
        $invM->edit(array_merge(['status'=>1,'subtotal'=>$sub,'tax_amount'=>$tax,'total_amount'=>$tot,'cost_amount'=>$cost,'acc_voucher_id'=>$vid],$ei),$invId);
    }
    return $invId;
};
$seedInvoice($whId,'KH-0001','Garage Thành Công','2026-06-10',10,[
    [$pid('PT-0001'),4,590000,8],[$pid('PT-0004'),10,99000,8],[$pid('PT-0006'),8,189000,8],
],true);
$seedInvoice($whId,'KH-0002','Đại lý phụ tùng Phú Sơn','2026-06-25',10,[
    [$pid('PT-0008'),6,1380000,5],[$pid('PT-0003'),3,920000,5],
],true);
$seedInvoice($whId,'KH-0003','Anh Nguyễn Văn Hùng','2026-07-08',10,[
    [$pid('PT-0005'),2,159000,0],[$pid('PT-0015'),4,209000,0],
]);
$log("- Hoa don ban: 3 (2 co HDDT)");

// ---------- 8) Báo giá ----------
$seedQuote=function($custCode,$custName,$date,$vat,$status,$lines) use($db,$qM,$qiM,$pnId,$now){
    $no=$qM->nextNo(); $custId=$custCode?($pnId($custCode)?:null):null;
    $qid=$qM->add(['quote_no'=>$no,'customer_id'=>$custId,'customer_name'=>$custName,'quote_date'=>$date,
        'valid_until'=>date('Y-m-d',strtotime($date.' +15 days')),'vat_rate'=>$vat,'subtotal'=>0,'tax_amount'=>0,'total_amount'=>0,'status'=>$status,'created_by'=>1]);
    $sub=$qiM->syncForQuotation($qid,array_map(function($l){ return ['part_id'=>$l[0],'quantity'=>$l[1],'unit_price'=>$l[2],'discount_percent'=>isset($l[3])?$l[3]:0]; },$lines));
    $tax=round($sub*$vat/100,2);
    $qM->edit(['subtotal'=>$sub,'tax_amount'=>$tax,'total_amount'=>$sub+$tax],$qid);
    return $qid;
};
$seedQuote('KH-0004','Gara Ô tô Minh Phát','2026-07-05',10,'sent',[[$pid('PT-0009'),2,2500000,8],[$pid('PT-0011'),4,1850000,8]]);
$seedQuote('KH-0001','Garage Thành Công','2026-07-12',10,'accepted',[[$pid('PT-0016'),6,840000,8],[$pid('PT-0002'),4,680000,8]]);
$log("- Bao gia: 2");

// ---------- 9) Đơn hàng web + giữ tồn ----------
$seedOrder=function($custName,$phone,$addr,$pay,$status,$lines) use($db,$ordM,$oiM,$resv,$now){
    $no=$ordM->nextNo();
    $rows=[]; $total=0.0;
    foreach($lines as $l){ $amt=$l[1]*$l[2]; $total+=$amt; $rows[]=['part'=>['id'=>$l[0],'name'=>$l[3],'code'=>$l[4]],'qty'=>$l[1],'price'=>$l[2],'amount'=>$amt]; }
    $oid=$ordM->add(['order_no'=>$no,'member_id'=>null,'customer_name'=>$custName,'phone'=>$phone,'email'=>null,'address'=>$addr,'note'=>null,'payment_method'=>$pay,'subtotal'=>0,'total_amount'=>0,'status'=>$status]);
    $t=$oiM->syncForOrder($oid,$rows);
    $ordM->edit(['subtotal'=>$t,'total_amount'=>$t],$oid);
    if ($status==='new'||$status==='confirmed'){
        $resv->reserveForOrder($oid,array_map(function($l){ return ['part_id'=>$l[0],'quantity'=>$l[1]]; },$lines));
    }
    return $oid;
};
$seedOrder('Trần Văn Nam','0977111222','15 Cầu Giấy, Hà Nội','cod','new',[
    [$pid('PT-0004'),3,99000,'Lọc dầu động cơ Toyota','PT-0004'],[$pid('PT-0006'),2,189000,'Bugi Iridium NGK','PT-0006']]);
$seedOrder('Lê Thị Hoa','0966333444','78 Hai Bà Trưng, Hà Nội','bank_transfer','confirmed',[
    [$pid('PT-0005'),1,159000,'Lọc gió động cơ Vios','PT-0005']]);
$seedOrder('Phạm Quốc Việt','0955666777','230 Láng Hạ, Hà Nội','cod','completed',[
    [$pid('PT-0013'),2,115000,'Lọc dầu Honda CR-V','PT-0013']]);
$log("- Don hang web: 3 (2 dang giu ton)");

// ---------- 10) Bảo hành + BB giao nhận + nhắc bảo trì ----------
$wrM=new WarrantyRequestsModel(); $whoM=new WarrantyHandoversModel();
$seedWarranty=function($cust,$phone,$prod,$serial,$recv,$status,$completed=null,$fee=0,$tech=null) use($db,$now){
    $r=$db->table('warranty_requests')->orderBy('id','DESC')->first();
    $n=0; if(!empty($r)&&preg_match('/(\d+)$/',$r['request_no'],$mm))$n=(int)$mm[1];
    $no='BH-'.str_pad($n+1,6,'0',STR_PAD_LEFT);
    $db->insert('warranty_requests',['request_no'=>$no,'partner_id'=>null,'customer_name'=>$cust,'phone'=>$phone,
        'part_id'=>null,'product_name'=>$prod,'serial_no'=>$serial,'received_date'=>$recv,'appointment_date'=>date('Y-m-d',strtotime($recv.' +3 days')),
        'completed_date'=>$completed,'status'=>$status,'issue'=>'Khách báo lỗi khi vận hành','diagnosis'=>$completed?'Đã kiểm tra & xử lý':null,
        'technician'=>$tech,'fee'=>$fee,'created_by'=>1,'create_at'=>$now]);
    return $db->table('warranty_requests')->where('request_no','=',$no)->first();
};
$w1=$seedWarranty('Garage Thành Công','0901234567','Máy phát điện Honda City','MP-2024-001','2026-07-15','processing',null,0,'KTV Hoàng');
$w2=$seedWarranty('Anh Nguyễn Văn Hùng','0987654321','Ắc quy GS 45Ah','AQ-2025-118','2026-07-10','received');
$w3=$seedWarranty('Đại lý Phú Sơn','0912345678','Giảm xóc Mazda CX-5','GX-2025-077','2026-01-05','done','2026-01-08',350000,'KTV Sơn');
$w4=$seedWarranty('Gara Minh Phát','0934567890','Đèn pha Vios LED','DP-2025-045','2025-11-20','done','2025-11-25',200000,'KTV Hoàng');
// BB giao nhận cho phiếu đang xử lý
if (!empty($w1)){
    foreach ([['receive','2026-07-15','Garage Thành Công','KTV Hoàng','01 máy phát, 01 dây nối','Vỏ trầy nhẹ']] as $h){
        $rr=$db->table('warranty_handovers')->orderBy('id','DESC')->first(); $nn=0; if(!empty($rr)&&preg_match('/(\d+)$/',$rr['handover_no'],$m2))$nn=(int)$m2[1];
        $db->insert('warranty_handovers',['handover_no'=>'BBGN-'.str_pad($nn+1,6,'0',STR_PAD_LEFT),'warranty_id'=>(int)$w1['id'],'type'=>$h[0],'handover_date'=>$h[1],'deliverer'=>$h[2],'receiver'=>$h[3],'accessories'=>$h[4],'condition_note'=>$h[5],'note'=>null,'created_by'=>1,'create_at'=>$now]);
    }
}
$log("- Bao hanh: 4 phieu (2 done -> nhac bao tri) + 1 BB giao nhan");

// ---------- 11) Đánh giá sản phẩm (đã duyệt) ----------
$reviews=[
    ['PT-0001','Nguyễn Minh',5,'Má phanh chính hãng, ăn phanh êm, lắp vừa khít.'],
    ['PT-0004','Trần Hải',5,'Lọc dầu Denso xịn, giá tốt.'],
    ['PT-0008','Lê Văn Bình',4,'Ắc quy khỏe, giao nhanh.'],
    ['PT-0006','Phạm Tuấn',5,'Bugi NGK Iridium nổ máy nhạy hơn hẳn.'],
    ['PT-0009','Đỗ Quang',4,'Đèn LED sáng, nhưng giá hơi cao.'],
];
$nRev=0;
foreach($reviews as $rv){ $piid=idOf($db,'parts','code',$rv[0]); if($piid<=0)continue;
    $db->insert('product_reviews',['part_id'=>$piid,'member_id'=>null,'author_name'=>$rv[1],'rating'=>$rv[2],'comment'=>$rv[3],'status'=>1,'create_at'=>$now]); $nRev++; }
$log("- Danh gia SP: +$nRev (da duyet)");

// ---------- 12) Nhân sự ----------
$deptId=function($n) use($db){ return idOf($db,'departments','name',$n); };
$posAll=$db->table('positions')->orderBy('id','ASC')->get(); $posIds=array_map(function($p){return (int)$p['id'];},$posAll?:[]);
$ppick=function($i) use($posIds){ return !empty($posIds)?$posIds[$i%count($posIds)]:null; };
$emps=[
    ['NV-001','Nguyễn Văn An','Phòng Kinh doanh','Nam','1988-04-12','0901111001','an.nv@tanphat.vn','2020-01-15',15000000],
    ['NV-002','Trần Thị Bình','Phòng Kinh doanh','Nữ','1992-08-20','0901111002','binh.tt@tanphat.vn','2021-03-01',12000000],
    ['NV-003','Lê Hoàng Cường','Phòng Kho vận','Nam','1990-11-05','0901111003','cuong.lh@tanphat.vn','2019-06-10',13000000],
    ['NV-004','Phạm Thị Dung','Phòng Kế toán','Nữ','1991-02-28','0901111004','dung.pt@tanphat.vn','2020-09-01',14000000],
    ['NV-005','Hoàng Văn Em','Phòng Kỹ thuật','Nam','1987-07-17','0901111005','em.hv@tanphat.vn','2018-02-20',16000000],
    ['NV-006','Vũ Thị Giang','Phòng Kỹ thuật','Nữ','1994-12-03','0901111006','giang.vt@tanphat.vn','2022-05-15',11000000],
    ['NV-007','Đặng Quốc Huy','Phòng Kho vận','Nam','1993-05-25','0901111007','huy.dq@tanphat.vn','2021-11-08',11500000],
    ['NV-008','Bùi Thị Lan','Phòng Kế toán','Nữ','1995-09-14','0901111008','lan.bt@tanphat.vn','2023-01-03',10500000],
];
$nEmp=0;$i=0;
foreach($emps as $e){
    if (idOf($db,'employees','code',$e[0])>0){$i++;continue;}
    $db->insert('employees',['code'=>$e[0],'name'=>$e[1],'department_id'=>$deptId($e[2])?:null,'position_id'=>$ppick($i),
        'gender'=>$e[3],'dob'=>$e[4],'phone'=>$e[5],'email'=>$e[6],'address'=>'Hà Nội','hire_date'=>$e[7],'salary_base'=>$e[8],'status'=>1,'create_at'=>$now]);
    $nEmp++;$i++;
}
$log("- Nhan vien: +$nEmp");
// đơn nghỉ phép
$empId=function($c) use($db){ return idOf($db,'employees','code',$c); };
$leaves=[
    ['NV-002','Nghỉ phép năm','2026-07-22','2026-07-24',3,'Về quê','pending'],
    ['NV-005','Nghỉ ốm','2026-06-10','2026-06-11',2,'Khám bệnh','approved'],
    ['NV-007','Nghỉ phép năm','2026-05-02','2026-05-03',2,'Việc gia đình','approved'],
];
foreach($leaves as $lv){ $eid=$empId($lv[0]); if($eid<=0)continue;
    if ($db->table('leave_requests')->where('employee_id','=',$eid)->where('from_date','=',$lv[2])->first()) continue;
    $db->insert('leave_requests',['employee_id'=>$eid,'leave_type'=>$lv[1],'from_date'=>$lv[2],'to_date'=>$lv[3],'days'=>$lv[4],'reason'=>$lv[5],'status'=>$lv[6],'created_by'=>1,'create_at'=>$now]); }
$log("- Don nghi phep: 3");

// ---------- 13) Nội dung web: tin tức + dự án + thư viện ----------
$ncAll=$db->table('news_categories')->orderBy('id','ASC')->get();
$ncId = !empty($ncAll)? (int)$ncAll[0]['id'] : null;
$news=[
    ['Tân Phát khai trương kho chi nhánh Miền Nam','Mở rộng mạng lưới phân phối phụ tùng chính hãng tại phía Nam.','<p>Nhằm phục vụ khách hàng khu vực phía Nam tốt hơn, Tân Phát chính thức khai trương kho chi nhánh tại KCN Sóng Thần, Bình Dương.</p>'],
    ['Cách nhận biết má phanh cần thay thế','5 dấu hiệu cho thấy đã đến lúc thay má phanh ô tô.','<p>Tiếng kêu ken két, phanh ăn kém, đèn báo phanh sáng... là những dấu hiệu cần kiểm tra ngay hệ thống phanh.</p>'],
    ['Chương trình khuyến mãi lọc dầu tháng 7','Giảm đến 20% cho các loại lọc dầu chính hãng.','<p>Từ 01/07 đến 31/07, mua lọc dầu Denso/Bosch được giảm giá và tặng công thay dầu.</p>'],
    ['Hướng dẫn bảo dưỡng xe mùa mưa','Những lưu ý quan trọng để xe vận hành an toàn mùa mưa.','<p>Kiểm tra gạt mưa, lốp, phanh và hệ thống điện trước mỗi chuyến đi trong mùa mưa.</p>'],
    ['Phân biệt bugi Iridium và bugi thường','Ưu điểm của bugi Iridium so với bugi truyền thống.','<p>Bugi Iridium bền hơn, đánh lửa mạnh và tiết kiệm nhiên liệu hơn bugi thường.</p>'],
    ['Tân Phát đạt chứng nhận đại lý chính hãng Bosch','Cột mốc quan trọng khẳng định uy tín.','<p>Tân Phát vinh dự trở thành đại lý uỷ quyền chính hãng của Bosch tại Việt Nam.</p>'],
];
$nNews=0;
foreach($news as $k=>$a){
    $slug=slugify_($a[0]);
    if (idOf($db,'news','slug',$slug)>0) continue;
    $db->insert('news',['category_id'=>$ncId,'title'=>$a[0],'slug'=>$slug,'meta_title'=>$a[0],'meta_description'=>$a[1],
        'summary'=>$a[1],'content'=>$a[2],'thumbnail'=>null,'is_published'=>1,'published_at'=>date('Y-m-d H:i:s',strtotime('-'.($k*5+2).' days')),
        'view_count'=>rand(20,500),'created_by'=>1,'create_at'=>$now]); $nNews++;
}
$log("- Tin tuc: +$nNews");
$projects=[
    ['Cung cấp phụ tùng cho chuỗi Gara ABC','Chuỗi Gara ABC','Hà Nội','Hợp đồng cung cấp phụ tùng dài hạn cho 12 gara.'],
    ['Trang bị thiết bị cho xưởng dịch vụ Toyota','Toyota Long Biên','Hà Nội','Cung cấp và lắp đặt thiết bị nâng hạ, máy chẩn đoán.'],
    ['Dự án phụ tùng đội xe doanh nghiệp','Công ty Vận tải Minh Anh','Bắc Ninh','Bảo dưỡng định kỳ đội xe 50 chiếc.'],
    ['Cung ứng ắc quy cho đại lý miền Bắc','Đại lý Phú Sơn','Bắc Ninh','Phân phối ắc quy GS chính hãng khu vực miền Bắc.'],
];
$nProj=0;
foreach($projects as $k=>$p){
    $slug=slugify_($p[0]);
    if (idOf($db,'projects','slug',$slug)>0) continue;
    $db->insert('projects',['name'=>$p[0],'slug'=>$slug,'meta_title'=>$p[0],'meta_description'=>$p[3],'client'=>$p[1],'location'=>$p[2],
        'summary'=>$p[3],'content'=>'<p>'.$p[3].'</p>','thumbnail'=>null,'completed_at'=>date('Y-m-d',strtotime('-'.($k*30+15).' days')),
        'is_published'=>1,'sort_order'=>$k,'created_by'=>1,'create_at'=>$now]); $nProj++;
}
$log("- Du an: +$nProj");
// thư viện ảnh (album, chưa có ảnh thật)
foreach ([['Hình ảnh kho hàng','Kho phụ tùng Tân Phát'],['Hoạt động công ty','Sự kiện & đội ngũ Tân Phát']] as $g){
    $slug=slugify_($g[0]); if (idOf($db,'galleries','slug',$slug)>0) continue;
    $db->insert('galleries',['name'=>$g[0],'slug'=>$slug,'description'=>$g[1],'cover'=>null,'is_published'=>1,'sort_order'=>0,'create_at'=>$now]);
    $gid=idOf($db,'galleries','slug',$slug);
    // 1 video youtube demo mỗi album
    $db->insert('gallery_items',['gallery_id'=>$gid,'media_type'=>'video','image'=>null,'video_url'=>'dQw4w9WgXcQ','caption'=>'Video giới thiệu','sort_order'=>0,'create_at'=>$now]);
}
$log("- Thu vien: 2 album");

// ---------- 14) Bản tin + liên hệ ----------
$subs=['nguyenvana@gmail.com','tranthib@gmail.com','lehoangc@yahoo.com','phamd@gmail.com','garagexyz@gmail.com','khachle@gmail.com'];
$nSub=0; foreach($subs as $e){ if(idOf($db,'newsletter_subscribers','email',$e)>0)continue; $db->insert('newsletter_subscribers',['email'=>$e,'status'=>1,'source'=>'storefront','create_at'=>$now]); $nSub++; }
$contacts=[
    ['Nguyễn Văn Khách','0912000111','khach1@gmail.com','Hỏi giá má phanh Vios','Cho mình hỏi giá má phanh trước Vios đời 2020 còn hàng không?','new'],
    ['Trần Thị Mai','0912000222',null,'Tư vấn lọc gió','Xe Honda City 2019 dùng loại lọc gió nào ạ?','new'],
    ['Gara Hoàng Long','0912000333','hoanglong@gara.vn','Đặt hàng số lượng lớn','Bên mình muốn đặt 50 bộ má phanh, báo giá sỉ giúp.','handled'],
    ['Lê Quốc Bảo','0912000444','bao.le@gmail.com','Khiếu nại bảo hành','Ắc quy mua tháng trước bị yếu, nhờ kiểm tra bảo hành.','handled'],
];
$nCt=0; foreach($contacts as $c){ if($db->table('contact_messages')->where('name','=',$c[0])->where('subject','=',$c[3])->first())continue;
    $db->insert('contact_messages',['name'=>$c[0],'email'=>$c[2],'phone'=>$c[1],'subject'=>$c[3],'message'=>$c[4],'status'=>$c[5],'ip'=>'127.0.0.1','create_at'=>$now]); $nCt++; }
$log("- Ban tin: +$nSub | Lien he: +$nCt");

// ---------- 15) Ảnh demo cho phụ tùng (SVG placeholder) ----------
require __DIR__ . '/seed_part_images.php';

$log("== HOAN TAT SEED DEMO ==");
