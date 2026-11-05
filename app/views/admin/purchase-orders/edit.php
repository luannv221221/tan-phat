<?php
/**
 * SỬA / MỞ ĐƠN MUA.
 *
 * Gần giống add.php và CỐ Ý chép đôi — xem ghi chú ở add.php (view nạp bằng
 * eval nên include partial không chạy).
 *
 * KHÁC add.php ba chỗ:
 *   - đơn ĐÃ CHỐT thì khoá hết ô nhập, chỉ còn nút Mở lại
 *   - có khối "Tạo phiếu nhập kho" (chọn kho nhận hàng)
 *   - có nút sang màn Khai serial, kèm số tem đã khai
 *
 * SERIAL KHÔNG NẰM TRONG FORM NÀY nữa (05/11/2026) — xem ghi chú ở add.php.
 * Nhưng lưu đơn vẫn GIỮ NGUYÊN serial đã khai: controller chép chúng sang
 * dòng hàng mới sau khi ghi lại (Purchaseorders::ghiDongVaSerial). Lưu đơn là
 * xoá sạch dòng hàng rồi ghi lại, mà serial neo vào dòng hàng theo CASCADE —
 * không chép lại thì sửa một chữ trên đơn là mất hết tem đã quét.
 */
$sel = function($k, $mac = '') use ($old, $item){
    return isset($old[$k]) ? $old[$k] : (isset($item[$k]) ? $item[$k] : $mac);
};

$daChot  = (int) $item['status'] === 1;
$daNhap  = !empty($item['goods_receipt_id']);
$khoaHet = $daChot || $daNhap;
/* Số ô trống cần trải ở hàng Tổng cộng. Bỏ cột serial (05/11/2026) nên chỉ
   còn phải trải qua ô "xoá dòng" — mà đơn đã chốt thì không có cột đó. Để
   cứng là hàng tổng lệch sang bên. */
$oTrongCuoi = $khoaHet ? 0 : 1;

/* Số tem đã khai, để nút "Khai serial" nói được còn thiếu hay chưa. Đếm ở đây
   chứ không hỏi lại CSDL: $serials đã có sẵn trong tay. */
$soTem = count((array) $serials);
$soCai = 0;
foreach ((array) $lines as $l) $soCai += (int) round((float) $l['quantity']);

/* Câu mô tả dựng Ở ĐÂY chứ không viết bằng @if/@elseif trong phần thân.
   Bộ biên dịch view của dự án chỉ hiểu @if / @else / @endif — KHÔNG có
   @elseif. Viết @elseif thì nó bị bỏ lại thành chữ thường, @endif thừa ra
   một cái, và cả trang chết với "unexpected token endif". PHP -l không bắt
   được vì lỗi nằm trong mã đã biên dịch, không nằm trong file view. */
if ($soTem === 0){
    $chuSerial = 'Chưa khai tem nào (' . $soCai . ' chiếc).';
} elseif ($soTem >= $soCai){
    $chuSerial = 'Đã khai đủ ' . $soTem . '/' . $soCai . ' chiếc.';
} else {
    $chuSerial = 'Mới khai ' . $soTem . '/' . $soCai . ' chiếc.';
}

$dongJs = [];
foreach ((array) $lines as $l){
    $dongJs[] = [
        'part_id'    => (int) $l['part_id'],
        'quantity'   => (float) $l['quantity'],
        'unit_price' => isset($l['unit_price']) ? (float) $l['unit_price'] : 0,
    ];
}

$dsHang = [];
foreach ((array) $parts as $p){
    $dsHang[] = [
        'id'    => (int) $p['id'],
        'label' => $p['code'] . ' - ' . $p['name'] . (!empty($p['unit_name']) ? ' (' . $p['unit_name'] . ')' : ''),
        'img'   => !empty($p['image']) ? _WEB_URL . '/public/assets/uploads/parts/' . $p['image'] : '',
    ];
}
?>
@if (!empty($msg))
<div class="alert alert-success alert-dismissible">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
    <i class="fas fa-check-circle mr-1"></i> {{$msg}}
</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
    <i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}
</div>
@endif

<?php if ($daNhap): ?>
<div class="alert alert-info py-2">
    <i class="fas fa-dolly-flatbed mr-1"></i>
    Đơn này đã sinh phiếu nhập kho
    <a href="{{_WEB_URL.'/admin/goods-receipts/edit/'.(int)$item['goods_receipt_id']}}"><b>{{$item['pnk_no']}}</b></a>.
    Tồn kho đổi khi phiếu nhập đó được <b>ghi sổ</b>, không phải lúc khai đơn mua.
</div>
<?php elseif ($daChot): ?>
<div class="alert alert-success py-2">
    <i class="fas fa-check-circle mr-1"></i>
    Đơn đã chốt — giờ tạo được phiếu nhập kho. Muốn sửa lại nội dung thì bấm <b>Mở lại</b>.
</div>
<?php endif; ?>

<form action="" method="post" enctype="multipart/form-data">
    <?php echo csrf_field(); ?>

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-file-invoice-dollar mr-2"></i>{{$page_name}}</h3>
            <div class="card-tools">
                {!! (int)$item['status'] === 1
                    ? '<span class="badge badge-success">Đã chốt</span>'
                    : '<span class="badge badge-secondary">Nháp</span>' !!}
            </div>
        </div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group col-md-5">
                    <label>Nhà cung cấp <span class="text-danger">*</span></label>
                    <select name="partner_id" class="form-control js-search" data-placeholder="Gõ tên hoặc mã để tìm..."
                            <?php echo $khoaHet ? 'disabled' : ''; ?>>
                        <option value="">— Chọn nhà cung cấp —</option>
                        <?php foreach ((array) $suppliers as $s): ?>
                        <option value="<?php echo (int) $s['id']; ?>" <?php echo ($sel('partner_id') == $s['id']) ? 'selected' : ''; ?>>
                            <?php echo e($s['code'] . ' - ' . $s['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    {!! !empty($errors['partner_id'])?'<small class="text-danger">'.e($errors['partner_id']).'</small>':false !!}
                </div>
                <div class="form-group col-md-3">
                    <label>Ngày mua <span class="text-danger">*</span></label>
                    <input type="date" name="order_date" class="form-control"
                           value="<?php echo e($sel('order_date')); ?>" <?php echo $khoaHet ? 'readonly' : ''; ?>/>
                    {!! !empty($errors['order_date'])?'<small class="text-danger">'.e($errors['order_date']).'</small>':false !!}
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-3">
                    <label>Số hoá đơn NCC</label>
                    <input type="text" name="invoice_no" class="form-control"
                           value="<?php echo e($sel('invoice_no')); ?>" <?php echo $khoaHet ? 'readonly' : ''; ?>/>
                </div>
                <div class="form-group col-md-5">
                    <label>Link hoá đơn</label>
                    <input type="text" name="invoice_url" class="form-control"
                           value="<?php echo e($sel('invoice_url')); ?>" <?php echo $khoaHet ? 'readonly' : ''; ?>/>
                    {!! !empty($errors['invoice_url'])?'<small class="text-danger">'.e($errors['invoice_url']).'</small>':false !!}
                </div>
                <div class="form-group col-md-4">
                    <label>Bản chụp hoá đơn</label>
                    <?php if (!empty($item['invoice_file'])): ?>
                    <div class="mb-1">
                        <a href="<?php echo e(media_url($item['invoice_file'])); ?>" target="_blank" rel="noopener noreferrer">
                            <i class="fas fa-paperclip mr-1"></i>Xem bản đã tải lên</a>
                    </div>
                    <?php endif; ?>
                    <?php if (!$khoaHet): ?>
                    <input type="file" name="invoice_file" class="form-control-file" accept=".pdf,image/*"/>
                    <small class="form-text text-muted">Chọn file mới để thay; để trống thì giữ bản cũ.</small>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label>Ghi chú</label>
                <input type="text" name="note" class="form-control"
                       value="<?php echo e($sel('note')); ?>" <?php echo $khoaHet ? 'readonly' : ''; ?>/>
            </div>
        </div>
    </div>

    <div class="card card-outline card-info">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-boxes mr-2"></i>Hàng hoá</h3>
            <?php if (!$khoaHet): ?>
            <div class="card-tools">
                <button type="button" class="btn btn-sm btn-info" id="them-dong">
                    <i class="fas fa-plus mr-1"></i> Thêm dòng
                </button>
            </div>
            <?php endif; ?>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-sm mb-0 bang-dong-mua">
                <thead><tr>
                    <?php /* Ô hàng hoá rộng hẳn ra sau khi bỏ cột serial — khách 05/11/2026:
                             "Tăng chiều dài hàng hóa, icon to hơn". */ ?>
                    <th>Hàng hoá</th>
                    <th style="width:110px" class="text-right">Số lượng</th>
                    <th style="width:150px" class="text-right">Đơn giá</th>
                    <th style="width:140px" class="text-right">Thành tiền</th>
                    <?php if (!$khoaHet): ?><th style="width:56px"></th><?php endif; ?>
                </tr></thead>
                <tbody id="dong-mua"></tbody>
                <tfoot><tr>
                    <th colspan="3" class="text-right">Tổng cộng</th>
                    <th class="text-right"><span id="tong-tien">0</span> đ</th>
                    <?php /* Viết bằng PHP thuần chứ không dùng @if: bộ biên dịch view
                             đòi `@if (...)` phải nằm CUỐI DÒNG, nên `@if (...)<th>@endif`
                             trên một dòng thì @if bị bỏ qua còn @endif vẫn biên dịch —
                             cả trang chết với "unexpected token endif". PHP -l không bắt
                             được vì lỗi nằm ở mã đã biên dịch, không ở file này. */ ?>
                    <?php if ($oTrongCuoi > 0): ?>
                        <th colspan="<?php echo (int) $oTrongCuoi; ?>"></th>
                    <?php endif; ?>
                </tr></tfoot>
            </table>
        </div>
    </div>

    <?php if (!$khoaHet): ?>
    <div class="card"><div class="card-body">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu</button>
        <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i> Quay lại</a>
    </div></div>
    <?php endif; ?>
</form>

<?php /* CÁC NÚT ĐỔI TRẠNG THÁI NẰM NGOÀI FORM TRÊN — mỗi nút một đường dẫn
         riêng, không gửi kèm nội dung đang gõ dở. Để chung một form thì bấm
         "Chốt" cũng lưu luôn phần chưa soát xong. */ ?>
<div class="card"><div class="card-body d-flex flex-wrap align-items-center" style="gap:.75rem">
    <?php if (!$daChot && !$daNhap): ?>
        @if (route('admin/'.$routeBase.'/chot/'.(int)$item['id']))
        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/chot/'.(int)$item['id']}}"
           onclick="return confirm('Chốt đơn mua {{$item['po_no']}}? Chốt rồi phải Mở lại mới sửa được.')"
           class="btn btn-success"><i class="fas fa-check mr-1"></i> Chốt đơn</a>
        @endif
        <span class="text-muted small">Chốt xong mới tạo được phiếu nhập kho.</span>
    <?php elseif ($daChot && !$daNhap): ?>
        @if (route('admin/'.$routeBase.'/mo-lai/'.(int)$item['id']))
        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/mo-lai/'.(int)$item['id']}}"
           class="btn btn-outline-secondary"><i class="fas fa-undo mr-1"></i> Mở lại</a>
        @endif
    <?php endif; ?>

    <?php /* KHAI SERIAL — hiện ở MỌI trạng thái, kể cả đơn đã chốt và đã nhập
             kho. Chốt đơn là chốt mua gì, giá bao nhiêu; serial là thứ chỉ
             biết khi hàng về tới nơi, tức là sau đó. Khoá theo trạng thái đơn
             thì đúng lúc cầm được cái tem lại không khai được. */ ?>
    @if (route('admin/'.$routeBase.'/serial/'.(int)$item['id']))
    <a href="{{_WEB_URL.'/admin/'.$routeBase.'/serial/'.(int)$item['id']}}"
       class="btn btn-outline-primary"><i class="fas fa-barcode mr-1"></i> Khai serial</a>
    <span class="text-muted small">{{$chuSerial}}</span>
    @endif
</div></div>

<?php /* TẠO PHIẾU NHẬP KHO — đường nhập kho thứ hai (tài liệu 29/10/2026). */ ?>
<?php if ($daChot && !$daNhap && route('admin/'.$routeBase.'/tao-phieu-nhap/'.(int)$item['id'])): ?>
<div class="card card-outline card-success">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-dolly-flatbed mr-2"></i>Tạo phiếu nhập kho</h3></div>
    <form action="{{_WEB_URL.'/admin/'.$routeBase.'/tao-phieu-nhap/'.(int)$item['id']}}" method="post">
        <?php echo csrf_field(); ?>
        <div class="card-body">
            <div class="form-row align-items-end">
                <div class="form-group col-md-5 mb-2">
                    <label>Kho nhận hàng <span class="text-danger">*</span></label>
                    <select name="warehouse_id" class="form-control">
                        <option value="">— Chọn kho —</option>
                        <?php foreach ((array) $warehouses as $w): ?>
                        <option value="<?php echo (int) $w['id']; ?>"><?php echo e($w['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-7 mb-2">
                    <button type="submit" class="btn btn-success"
                            onclick="return confirm('Tạo phiếu nhập kho từ đơn {{$item['po_no']}}? Chỉ tạo được một lần.')">
                        <i class="fas fa-dolly-flatbed mr-1"></i> Tạo phiếu nhập kho
                    </button>
                </div>
            </div>
        </div>
        <div class="card-footer text-muted small">
            Phiếu nhập sinh ra ở trạng thái <strong>nháp</strong> — tồn kho chỉ đổi khi phiếu đó được ghi sổ.
            <strong>Giá nhập</strong> lấy đúng đơn giá đã khai trên đơn mua; dòng nào để trống giá thì
            lấy theo lần nhập gần nhất của mặt hàng đó. Soát lại trên phiếu nhập rồi hãy ghi sổ.
        </div>
    </form>
</div>
<?php endif; ?>

<script>
(function(){
    'use strict';
    var DS = {!! json_encode($dsHang, JSON_HEX_TAG|JSON_UNESCAPED_UNICODE) !!};
    var BAN_DAU = {!! json_encode($dongJs, JSON_HEX_TAG|JSON_UNESCAPED_UNICODE) !!};
    var KHOA = {!! json_encode((bool) $khoaHet) !!};
    var tbody = document.getElementById('dong-mua');
    var oTong = document.getElementById('tong-tien');

    /* Tiền Việt tính theo đồng, không phần lẻ — bỏ mọi ký tự không phải chữ số,
       đúng kiểu màn Phiếu nhập kho đang làm. */
    function soTien(v){ return parseInt(String(v || '').replace(/[^\d]/g, ''), 10) || 0; }
    function soLuongO(v){ return parseFloat(String(v || '').replace(/[^\d.]/g, '')) || 0; }

    /* Ô đơn giá TRỐNG khác hẳn đơn giá 0: trống là "NCC chưa báo giá", và lúc
       tạo phiếu nhập dòng đó mới rơi về giá nhập gần nhất. Nên thành tiền hiện
       dấu — chứ không hiện 0, để người đọc biết là chưa khai. */
    function tinhLai(){
        var t = 0;
        Array.prototype.forEach.call(tbody.querySelectorAll('tr'), function (r){
            var oSl = r.querySelector('.o-sl'), oGia = r.querySelector('.o-gia'), oTt = r.querySelector('.o-tt');
            if (!oSl || !oGia || !oTt) return;
            var g  = soTien(oGia.value);
            var tt = Math.round(soLuongO(oSl.value) * g);
            oTt.textContent = g > 0 ? tt.toLocaleString('vi-VN') : '—';
            t += tt;
        });
        oTong.textContent = t.toLocaleString('vi-VN');
    }
    var soDong = 0;   // chỉ tăng — xem ghi chú ở add.php

    function oChon(chon){
        var s = document.createElement('select');
        s.name = 'part[]';
        s.className = 'form-control form-control-sm' + (KHOA ? '' : ' js-search');
        s.setAttribute('data-placeholder', '— Chọn hàng hoá —');
        if (KHOA) s.disabled = true;
        var o0 = document.createElement('option');
        o0.value = ''; o0.textContent = '— Chọn hàng hoá —';
        s.appendChild(o0);
        DS.forEach(function (op){
            var o = document.createElement('option');
            o.value = op.id; o.textContent = op.label;
            if (op.img) o.setAttribute('data-img', op.img);
            if (String(op.id) === String(chon)) o.selected = true;
            s.appendChild(o);
        });
        return s;
    }

    function themDong(r){
        var chiSo = soDong++;
        var tr = document.createElement('tr');

        var td1 = document.createElement('td');
        td1.appendChild(oChon(r && r.part_id));

        var td2 = document.createElement('td');
        var qty = document.createElement('input');
        qty.type = 'number'; qty.min = '0'; qty.step = '1';
        qty.name = 'qty[]';
        qty.className = 'form-control form-control-sm text-right o-sl';
        qty.value = r && r.quantity ? parseFloat(r.quantity) : 1;
        if (KHOA) qty.readOnly = true;
        td2.appendChild(qty);

        var tdG = document.createElement('td');
        var gia = document.createElement('input');
        gia.name = 'price[]';
        gia.className = 'form-control form-control-sm text-right o-gia';
        gia.placeholder = 'Chưa báo giá';
        gia.value = (r && parseFloat(r.unit_price) > 0) ? parseInt(r.unit_price, 10) : '';
        /* oTien() biến ô này thành ô số không âm, bước 1 đồng, VẪN ĐỂ TRỐNG
           ĐƯỢC — xem so-input.js, nạp trong <head> nên luôn có sẵn. */
        if (window.oTien) window.oTien(gia);
        if (KHOA) gia.readOnly = true;
        tdG.appendChild(gia);

        var tdT = document.createElement('td');
        tdT.className = 'text-right align-middle';
        var oTt = document.createElement('span');
        oTt.className = 'o-tt';
        oTt.textContent = '—';
        tdT.appendChild(oTt);

        tr.appendChild(td1); tr.appendChild(td2); tr.appendChild(tdG);
        tr.appendChild(tdT);

        if (!KHOA){
            var td4 = document.createElement('td');
            var xoa = document.createElement('button');
            xoa.type = 'button';
            xoa.className = 'btn btn-sm btn-outline-danger nut-xoa-dong';
            xoa.innerHTML = '<i class="fas fa-times"></i>';
            xoa.title = 'Xoá dòng';
            xoa.addEventListener('click', function (){ tr.remove(); tinhLai(); });
            td4.appendChild(xoa);
            tr.appendChild(td4);
        }
        tbody.appendChild(tr);

        if (!KHOA){
            qty.addEventListener('input', tinhLai);
            gia.addEventListener('input', tinhLai);
        }
        tinhLai();
        if (window.initSearchSelects) window.initSearchSelects(tr);
    }

    var nut = document.getElementById('them-dong');
    if (nut) nut.addEventListener('click', function (){ themDong(null); });

    if (BAN_DAU.length) BAN_DAU.forEach(function (r){ themDong(r); });
    else if (!KHOA) themDong(null);
})();
</script>
