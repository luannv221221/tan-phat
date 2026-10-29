<?php
/**
 * LẬP ĐƠN MUA.
 *
 * Gần giống edit.php và CỐ Ý chép đôi: view ở dự án này nạp bằng
 * file_get_contents() rồi eval(), nên `__DIR__` bên trong trỏ về core/ chứ
 * không phải thư mục view — tách partial rồi include là không tìm thấy file.
 *
 * SERIAL MỌC THEO SỐ LƯỢNG: gõ số lượng 5 thì dòng đó hiện ra 5 ô serial, đúng
 * như tài liệu ("dựa vào số lượng sẽ cho phép thêm số serial tương ứng"). Việc
 * này làm bằng JS ở cuối file; ô serial đặt tên `serial[<chỉ số dòng>][]` để
 * server gắn đúng serial vào đúng dòng hàng.
 */
$gt = function($k, $mac = '') use ($old){ return isset($old[$k]) ? $old[$k] : $mac; };

/* Mặt hàng cho ô chọn — cùng khuôn với báo giá / phiếu nhập */
$dsHang = [];
foreach ((array) $parts as $p){
    $dsHang[] = [
        'id'    => (int) $p['id'],
        'label' => $p['code'] . ' - ' . $p['name'] . (!empty($p['unit_name']) ? ' (' . $p['unit_name'] . ')' : ''),
        'img'   => !empty($p['image']) ? _WEB_URL . '/public/assets/uploads/parts/' . $p['image'] : '',
    ];
}
?>
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
    <i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}
</div>
@endif

<form action="" method="post" enctype="multipart/form-data">
    <?php echo csrf_field(); ?>

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-file-invoice-dollar mr-2"></i>{{$page_name}}</h3>
            <div class="card-tools"><span class="badge badge-light border">Số đơn: {{$nextNo}}</span></div>
        </div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group col-md-5">
                    <label>Nhà cung cấp <span class="text-danger">*</span></label>
                    <select name="partner_id" class="form-control js-search" data-placeholder="Gõ tên hoặc mã để tìm...">
                        <option value="">— Chọn nhà cung cấp —</option>
                        <?php foreach ((array) $suppliers as $s): ?>
                        <option value="<?php echo (int) $s['id']; ?>" <?php echo ($gt('partner_id') == $s['id']) ? 'selected' : ''; ?>>
                            <?php echo e($s['code'] . ' - ' . $s['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    {!! !empty($errors['partner_id'])?'<small class="text-danger">'.e($errors['partner_id']).'</small>':false !!}
                    <small class="form-text text-muted">Khai thêm ở <em>Bán hàng › Đối tượng</em> (chọn vai Nhà cung cấp).</small>
                </div>
                <div class="form-group col-md-3">
                    <label>Ngày mua <span class="text-danger">*</span></label>
                    <input type="date" name="order_date" class="form-control"
                           value="<?php echo e($gt('order_date', date('Y-m-d'))); ?>"/>
                    {!! !empty($errors['order_date'])?'<small class="text-danger">'.e($errors['order_date']).'</small>':false !!}
                </div>
            </div>

            <?php /* HOÁ ĐƠN CỦA NHÀ CUNG CẤP — tài liệu ghi "link hóa đơn" và
                     "upload (hóa đơn)". Hai thứ khác nhau nên hai ô: có nơi gửi
                     link hoá đơn điện tử, có nơi đưa bản chụp. */ ?>
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label>Số hoá đơn NCC</label>
                    <input type="text" name="invoice_no" class="form-control"
                           value="<?php echo e($gt('invoice_no')); ?>" placeholder="VD: 0000123"/>
                </div>
                <div class="form-group col-md-5">
                    <label>Link hoá đơn</label>
                    <input type="text" name="invoice_url" class="form-control"
                           value="<?php echo e($gt('invoice_url')); ?>" placeholder="https://..."/>
                    {!! !empty($errors['invoice_url'])?'<small class="text-danger">'.e($errors['invoice_url']).'</small>':false !!}
                </div>
                <div class="form-group col-md-4">
                    <label>Bản chụp hoá đơn</label>
                    <input type="file" name="invoice_file" class="form-control-file" accept=".pdf,image/*"/>
                    {!! !empty($errors['invoice_file'])?'<small class="text-danger">'.e($errors['invoice_file']).'</small>':false !!}
                    <small class="form-text text-muted">PDF hoặc ảnh, tối đa 8MB.</small>
                </div>
            </div>

            <div class="form-group">
                <label>Ghi chú</label>
                <input type="text" name="note" class="form-control" value="<?php echo e($gt('note')); ?>"/>
            </div>
        </div>
    </div>

    <div class="card card-outline card-info">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-boxes mr-2"></i>Hàng hoá &amp; serial</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-sm btn-info" id="them-dong">
                    <i class="fas fa-plus mr-1"></i> Thêm dòng
                </button>
            </div>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-sm mb-0 bang-dong-mua">
                <thead><tr>
                    <th style="width:38%">Hàng hoá</th>
                    <th style="width:110px" class="text-right">Số lượng</th>
                    <th>Serial từng chiếc</th>
                    <th style="width:44px"></th>
                </tr></thead>
                <tbody id="dong-mua"></tbody>
            </table>
        </div>
        <div class="card-footer text-muted small">
            Gõ <strong>số lượng</strong> thì ô serial tự mọc ra đúng bấy nhiêu dòng.
            Chưa có tem thì để trống — lưu được, bổ sung sau.
            Serial <strong>không được trùng</strong> trong cùng một gara.
        </div>
    </div>

    <div class="card"><div class="card-body">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu (nháp)</button>
        <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i> Quay lại</a>
    </div></div>
</form>

<script>
(function(){
    'use strict';
    var DS = {!! json_encode($dsHang, JSON_HEX_TAG|JSON_UNESCAPED_UNICODE) !!};
    var BAN_DAU = {!! json_encode(array_values((array) $lines), JSON_HEX_TAG|JSON_UNESCAPED_UNICODE) !!};
    var tbody = document.getElementById('dong-mua');

    /* Chỉ số dòng CHỈ TĂNG, không đánh lại sau khi xoá.
       Tên ô là `serial[<chỉ số>][]`, mà server gắn serial vào dòng hàng theo
       THỨ TỰ các dòng còn lại. Đánh lại số sau mỗi lần xoá thì hai dòng có thể
       cùng mang một chỉ số trong chớp mắt giữa hai thao tác, và serial nhảy
       sang mặt hàng khác — không có lỗi nào báo. */
    var soDong = 0;

    function oChon(chon){
        var s = document.createElement('select');
        s.name = 'part[]';
        s.className = 'form-control form-control-sm js-search';
        s.setAttribute('data-placeholder', '— Chọn hàng hoá —');
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

    /* Số ô serial phải KHỚP số lượng. Giảm số lượng thì bỏ bớt ô TỪ CUỐI lên,
       giữ nguyên những tem đã gõ ở trên — xoá từ đầu là mất đúng cái vừa nhập. */
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
        td2.appendChild(qty);

        var td3 = document.createElement('td');
        var boc = document.createElement('div');
        boc.className = 'o-boc-serial';
        td3.appendChild(boc);

        var td4 = document.createElement('td');
        var xoa = document.createElement('button');
        xoa.type = 'button';
        xoa.className = 'btn btn-sm btn-outline-danger';
        xoa.innerHTML = '<i class="fas fa-times"></i>';
        xoa.title = 'Xoá dòng';
        xoa.addEventListener('click', function (){ tr.remove(); });
        td4.appendChild(xoa);

        tr.appendChild(td1); tr.appendChild(td2); tr.appendChild(td3); tr.appendChild(td4);
        tbody.appendChild(tr);

        veSerial(boc, chiSo, parseFloat(qty.value), r && r.serials);
        qty.addEventListener('input', function (){ veSerial(boc, chiSo, parseFloat(qty.value), null); });

        /* Ô chọn mới thêm phải được gắn ô tìm kiếm — admin.js theo dõi DOM nên
           tự lo, nhưng gọi thẳng cho chắc nếu hàm có sẵn. */
        if (window.initSearchSelects) window.initSearchSelects(tr);
    }

    document.getElementById('them-dong').addEventListener('click', function (){ themDong(null); });

    if (BAN_DAU.length) BAN_DAU.forEach(function (r){ themDong(r); });
    else themDong(null);
})();
</script>
