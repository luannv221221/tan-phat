<?php
/**
 * SỬA / MỞ ĐƠN MUA.
 *
 * Gần giống add.php và CỐ Ý chép đôi — xem ghi chú ở add.php (view nạp bằng
 * eval nên include partial không chạy).
 *
 * KHÁC add.php ba chỗ:
 *   - serial đã nhập được đổ sẵn vào đúng dòng hàng
 *   - đơn ĐÃ CHỐT thì khoá hết ô nhập, chỉ còn nút Mở lại
 *   - có khối "Tạo phiếu nhập kho" (chọn kho nhận hàng)
 */
$sel = function($k, $mac = '') use ($old, $item){
    return isset($old[$k]) ? $old[$k] : (isset($item[$k]) ? $item[$k] : $mac);
};

$daChot  = (int) $item['status'] === 1;
$daNhap  = !empty($item['goods_receipt_id']);
$khoaHet = $daChot || $daNhap;

/* Gom serial về từng dòng hàng để JS đổ sẵn */
$snTheoDong = [];
foreach ((array) $serials as $s){
    $k = !empty($s['poi_id']) ? (int) $s['poi_id'] : 0;
    $snTheoDong[$k][] = $s['serial'];
}
$dongJs = [];
foreach ((array) $lines as $l){
    $dongJs[] = [
        'part_id'  => (int) $l['part_id'],
        'quantity' => (float) $l['quantity'],
        'serials'  => isset($snTheoDong[(int) $l['id']]) ? $snTheoDong[(int) $l['id']] : [],
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
            <h3 class="card-title"><i class="fas fa-boxes mr-2"></i>Hàng hoá &amp; serial</h3>
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
                    <th style="width:38%">Hàng hoá</th>
                    <th style="width:110px" class="text-right">Số lượng</th>
                    <th>Serial từng chiếc</th>
                    <?php if (!$khoaHet): ?><th style="width:44px"></th><?php endif; ?>
                </tr></thead>
                <tbody id="dong-mua"></tbody>
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
            <strong>Giá nhập</strong> lấy theo lần nhập gần nhất của từng mặt hàng (đơn mua không có giá);
            soát lại trên phiếu nhập rồi hãy ghi sổ.
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

    function veSerial(oBoc, chiSo, soLuong, dsCo){
        var n = Math.max(0, Math.floor(soLuong || 0));
        var dangCo = oBoc.querySelectorAll('input.o-serial');
        for (var i = dangCo.length; i > n; i--) dangCo[i - 1].parentNode.remove();
        for (var j = dangCo.length; j < n; j++){
            var cum = document.createElement('div');
            cum.className = 'input-group input-group-sm mb-1';
            var pre = document.createElement('div');
            pre.className = 'input-group-prepend';
            pre.innerHTML = '<span class="input-group-text">' + (j + 1) + '</span>';
            var inp = document.createElement('input');
            inp.type = 'text';
            inp.name = 'serial[' + chiSo + '][]';
            inp.className = 'form-control o-serial';
            inp.placeholder = 'Số serial chiếc thứ ' + (j + 1);
            if (dsCo && dsCo[j]) inp.value = dsCo[j];
            if (KHOA) inp.readOnly = true;
            cum.appendChild(pre); cum.appendChild(inp);
            oBoc.appendChild(cum);
        }
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
        qty.className = 'form-control form-control-sm text-right';
        qty.value = r && r.quantity ? parseFloat(r.quantity) : 1;
        if (KHOA) qty.readOnly = true;
        td2.appendChild(qty);

        var td3 = document.createElement('td');
        var boc = document.createElement('div');
        boc.className = 'o-boc-serial';
        td3.appendChild(boc);

        tr.appendChild(td1); tr.appendChild(td2); tr.appendChild(td3);

        if (!KHOA){
            var td4 = document.createElement('td');
            var xoa = document.createElement('button');
            xoa.type = 'button';
            xoa.className = 'btn btn-sm btn-outline-danger';
            xoa.innerHTML = '<i class="fas fa-times"></i>';
            xoa.title = 'Xoá dòng';
            xoa.addEventListener('click', function (){ tr.remove(); });
            td4.appendChild(xoa);
            tr.appendChild(td4);
        }
        tbody.appendChild(tr);

        veSerial(boc, chiSo, parseFloat(qty.value), r && r.serials);
        if (!KHOA) qty.addEventListener('input', function (){ veSerial(boc, chiSo, parseFloat(qty.value), null); });
        if (window.initSearchSelects) window.initSearchSelects(tr);
    }

    var nut = document.getElementById('them-dong');
    if (nut) nut.addEventListener('click', function (){ themDong(null); });

    if (BAN_DAU.length) BAN_DAU.forEach(function (r){ themDong(r); });
    else if (!KHOA) themDong(null);
})();
</script>
