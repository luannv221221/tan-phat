<?php
/* Sửa báo giá — cùng bố cục 2 tab như màn hình Lập báo giá.
   Xem chú thích đầy đủ ở app/views/admin/quotations/add.php. */

$hangJs = $dichVuJs = [];
foreach ($parts as $p){
    $row = [
        'id'    => (int) $p['id'],
        'label' => $p['code'] . ' - ' . $p['name'] . (!empty($p['unit_name']) ? ' (' . $p['unit_name'] . ')' : ''),
        'price' => (int) (!empty($p['sale_price']) ? $p['sale_price'] : $p['price']),
    ];
    if ($p['item_type'] === PartsModel::LOAI_DICH_VU) $dichVuJs[] = $row;
    else                                              $hangJs[]   = $row;
}

$doiDong = function($tienTo) use ($old){
    $rows = [];
    if (empty($old[$tienTo . 'part']) || !is_array($old[$tienTo . 'part'])) return $rows;
    foreach ($old[$tienTo . 'part'] as $i => $p){
        $rows[] = [
            'part_id' => (int) $p,
            'qty'     => isset($old[$tienTo . 'qty'][$i])   ? $old[$tienTo . 'qty'][$i]   : '',
            'price'   => isset($old[$tienTo . 'price'][$i]) ? $old[$tienTo . 'price'][$i] : '',
            'disc'    => isset($old[$tienTo . 'disc'][$i])  ? $old[$tienTo . 'disc'][$i]  : '',
            'note'    => isset($old[$tienTo . 'note'][$i])  ? $old[$tienTo . 'note'][$i]  : '',
        ];
    }
    return $rows;
};

if (!empty($old)){
    $initHang   = $doiDong('line_');
    $initDichVu = $doiDong('sv_');
} else {
    /* Dòng đã lưu: xếp về đúng tab theo item_type của mặt hàng.
       Báo giá cũ (lập trước khi có tab) chỉ toàn hàng hoá nên vẫn về tab 1 —
       trừ dòng nào trỏ vào mặt hàng nay đã đổi thành dịch vụ, và đó chính là
       chỗ nó nên nằm. */
    $initHang = $initDichVu = [];
    foreach ($items as $it){
        $row = [
            'part_id' => (int) $it['part_id'],
            'qty'     => rtrim(rtrim((string) $it['quantity'], '0'), '.'),
            'price'   => (int) $it['unit_price'],
            'disc'    => rtrim(rtrim((string) $it['discount_percent'], '0'), '.'),
            'note'    => $it['note'],
        ];
        if (isset($it['item_type']) && $it['item_type'] === PartsModel::LOAI_DICH_VU) $initDichVu[] = $row;
        else                                                                          $initHang[]   = $row;
    }
}

$badge = ['draft' => 'secondary', 'sent' => 'info', 'accepted' => 'success', 'rejected' => 'danger'];
$sel = function($field, $default = '') use ($old, $item){ return isset($old[$field]) ? $old[$field] : (isset($item[$field]) ? $item[$field] : $default); };

$tabs = [
    ['ma' => 'hang',   'nhan' => 'Hàng hoá', 'cot' => 'Hàng hoá', 'icon' => 'fa-boxes'],
    ['ma' => 'dichvu', 'nhan' => 'Dịch vụ',  'cot' => 'Dịch vụ',  'icon' => 'fa-screwdriver-wrench'],
];
?>

@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}</div>
@endif

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-file-invoice-dollar mr-2"></i>Báo giá <code>{{$item['quote_no']}}</code></h3>
        <div class="card-tools"><span class="badge badge-{{$badge[$item['status']] ?? 'secondary'}} p-2">{{$statuses[$item['status']] ?? $item['status']}}</span></div>
    </div>
    <div class="card-body py-2">
        <span class="mr-2 small text-muted">Chuyển trạng thái:</span>
        @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/set-status/'.$item['id'].'?status=sent'}}" class="btn btn-sm btn-outline-info">Đã gửi</a>
        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/set-status/'.$item['id'].'?status=accepted'}}" class="btn btn-sm btn-outline-success">Chấp nhận</a>
        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/set-status/'.$item['id'].'?status=rejected'}}" class="btn btn-sm btn-outline-danger">Từ chối</a>
        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/set-status/'.$item['id'].'?status=draft'}}" class="btn btn-sm btn-outline-secondary">Nháp</a>
        @endif
        <span class="mx-2 text-muted">|</span>
        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/print/'.$item['id'].'?in=1'}}" target="_blank" class="btn btn-sm btn-outline-dark"><i class="fas fa-print mr-1"></i> In / Lưu PDF</a>
        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/print/'.$item['id'].'?word=1'}}" class="btn btn-sm btn-outline-dark"><i class="fas fa-file-word mr-1"></i> Tải Word</a>
        @if (route('admin/sales-invoices/add'))
        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/convert/'.$item['id']}}" onclick="return confirm('Tạo hoá đơn bán (nháp) từ báo giá này?')" class="btn btn-sm btn-primary float-right"><i class="fas fa-file-export mr-1"></i> Chuyển thành hoá đơn</a>
        @endif
    </div>
</div>

<form action="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$item['id']}}" method="post">
    <?php echo csrf_field(); ?>
    <div class="card"><div class="card-body">
        <div class="form-row">
            <div class="form-group col-md-3">
                <label>Ngày <span class="text-danger">*</span></label>
                <input type="date" name="quote_date" class="form-control" value="{{$sel('quote_date')}}"/>
                {!! !empty($errors['quote_date'])?'<small class="text-danger">'.e($errors['quote_date']).'</small>':false !!}
            </div>
            <div class="form-group col-md-3">
                <label>Hiệu lực đến</label>
                <input type="date" name="valid_until" class="form-control" value="{{$sel('valid_until')}}"/>
            </div>
            <div class="form-group col-md-2">
                <label>Thuế GTGT (%)</label>
                <input type="number" min="0" max="100" step="any" name="vat_rate" id="vat_rate" class="form-control text-right" value="{{$sel('vat_rate','0')}}"/>
            </div>
            <div class="form-group col-md-4">
                <label>Khách hàng</label>
                <select name="customer_id" class="form-control js-search" data-placeholder="Gõ tên hoặc mã để tìm...">
                    <option value="">— Chọn / vãng lai —</option>
                    @foreach ($partners as $pn)
                    <option value="{{$pn['id']}}" {{$sel('customer_id')==$pn['id']?'selected':''}}>{{$pn['code'].' - '.$pn['name']}}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div></div>

    <div class="card card-outline card-info">
        <div class="card-header p-0 pt-1 border-bottom-0">
            <ul class="nav nav-tabs" id="line-tabs">
                <?php foreach ($tabs as $i => $t): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $i === 0 ? 'active' : ''; ?>" href="#"
                       data-pane="<?php echo e($t['ma']); ?>">
                        <i class="fas <?php echo e($t['icon']); ?> mr-1"></i><?php echo e($t['nhan']); ?>
                        <span class="badge badge-secondary ml-1" id="dem-<?php echo e($t['ma']); ?>">0</span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <?php foreach ($tabs as $i => $t): ?>
        <div class="pane-dong" id="pane-<?php echo e($t['ma']); ?>" <?php echo $i === 0 ? '' : 'style="display:none"'; ?>>
            <div class="card-body py-2 border-bottom text-right">
                <button type="button" class="btn btn-sm btn-info" id="add-<?php echo e($t['ma']); ?>">
                    <i class="fas fa-plus mr-1"></i> Thêm dòng <?php echo e(mb_strtolower($t['nhan'], 'UTF-8')); ?>
                </button>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-sm mb-0">
                    <thead><tr>
                        <th style="width:30%"><?php echo e($t['cot']); ?></th>
                        <th style="width:11%" class="text-right">Số lượng</th>
                        <th style="width:15%" class="text-right">Đơn giá</th>
                        <th style="width:9%" class="text-right">CK %</th>
                        <th style="width:15%" class="text-right">Thành tiền</th>
                        <th>Ghi chú</th>
                        <th style="width:44px"></th>
                    </tr></thead>
                    <tbody id="lines-<?php echo e($t['ma']); ?>"></tbody>
                    <?php /* Tổng tiền đặt trong <tfoot> của CHÍNH bảng dòng hàng, giống
                             hoá đơn / phiếu nhập / phiếu xuất — cột tiền nhờ thế thẳng
                             hàng với cột "Thành tiền" ở trên.

                             Bản đầu tiên để riêng một .card-footer với
                             <div class="col-md-6 offset-md-6">: offset chừa trống nửa
                             trái, đo được 496 x 236 px nền trắng trơn ngay dưới bảng,
                             nhìn như nội dung nạp hỏng.

                             Vẽ ở CẢ HAI tab (vòng lặp này chạy 2 lần) nên đang mở tab
                             nào cũng thấy đủ tổng — vì vậy dùng class chứ không dùng id,
                             id trùng nhau thì getElementById chỉ thấy bản đầu. */ ?>
                    <tfoot>
                        <tr><th colspan="4" class="text-right font-weight-normal">Tiền hàng hoá</th>
                            <th class="text-right font-weight-normal"><span class="js-tong-hang">0</span> ₫</th>
                            <th colspan="2"></th></tr>
                        <tr><th colspan="4" class="text-right font-weight-normal">Tiền dịch vụ</th>
                            <th class="text-right font-weight-normal"><span class="js-tong-dichvu">0</span> ₫</th>
                            <th colspan="2"></th></tr>
                        <tr><th colspan="4" class="text-right">Cộng chưa thuế</th>
                            <th class="text-right"><span class="js-sub-total">0</span> ₫</th>
                            <th colspan="2"></th></tr>
                        <tr><th colspan="4" class="text-right">Thuế GTGT</th>
                            <th class="text-right"><span class="js-tax-total">0</span> ₫</th>
                            <th colspan="2"></th></tr>
                        <tr><th colspan="4" class="text-right">Tổng cộng</th>
                            <th class="text-right text-danger"><span class="js-grand-total">0</span> ₫</th>
                            <th colspan="2"></th></tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <?php endforeach; ?>

        {!! !empty($errors['lines'])?'<div class="card-body py-2"><small class="text-danger">'.e($errors['lines']).'</small></div>':false !!}
    </div>

    <div class="card"><div class="card-body">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu</button>
        @if (route('admin/'.$routeBase.'/delete/'.$item['id']))
        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/delete/'.$item['id']}}" onclick="return confirm('Xoá báo giá này?')" class="btn btn-outline-danger"><i class="fas fa-trash mr-1"></i> Xoá</a>
        @endif
        <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default">Về danh sách</a>
    </div></div>
</form>

<script>
(function () {
    var DU_LIEU = {
        hang:   {!! json_encode($hangJs, JSON_HEX_TAG|JSON_UNESCAPED_UNICODE) !!},
        dichvu: {!! json_encode($dichVuJs, JSON_HEX_TAG|JSON_UNESCAPED_UNICODE) !!}
    };
    var BAN_DAU = {
        hang:   {!! json_encode($initHang, JSON_HEX_TAG|JSON_UNESCAPED_UNICODE) !!},
        dichvu: {!! json_encode($initDichVu, JSON_HEX_TAG|JSON_UNESCAPED_UNICODE) !!}
    };
    var DISC = {!! json_encode((object)$partnerDiscounts, JSON_HEX_TAG|JSON_UNESCAPED_UNICODE) !!};

    var vatEl   = document.getElementById('vat_rate');
    var custSel = document.querySelector('select[name="customer_id"]');

    function fmt(n){ return (n || 0).toLocaleString('vi-VN'); }
    function num(v){ return parseFloat(String(v || '').replace(/[^\d.]/g, '')) || 0; }
    function money(v){ return parseInt(String(v || '').replace(/[^\d]/g, ''), 10) || 0; }
    function groupDisc(){ var v = custSel ? custSel.value : ''; return (v && DISC[v] != null) ? parseFloat(DISC[v]) : 0; }

    var bang = {};

    // Khối tổng vẽ ở cả hai tab nên phải ghi vào MỌI ô cùng tên, không phải một ô.
    function dat(ten, giaTri){
        document.querySelectorAll('.js-' + ten).forEach(function (e){ e.textContent = giaTri; });
    }

    function recompute(){
        var tHang   = bang.hang.tong();
        var tDichVu = bang.dichvu.tong();
        var sub     = tHang + tDichVu;
        var tax     = Math.round(sub * num(vatEl.value) / 100);

        dat('tong-hang',   fmt(tHang));
        dat('tong-dichvu', fmt(tDichVu));
        dat('sub-total',   fmt(sub));
        dat('tax-total',   fmt(tax));
        dat('grand-total', fmt(sub + tax));

        document.getElementById('dem-hang').textContent   = bang.hang.dem();
        document.getElementById('dem-dichvu').textContent = bang.dichvu.dem();
    }

    function taoBang(ma, tienTo, DS, nhanTrong){
        var tbody = document.getElementById('lines-' + ma);

        function partSelect(selected){
            var s = document.createElement('select');
            s.name = tienTo + 'part[]';
            s.className = 'form-control form-control-sm part-sel js-search';
            s.setAttribute('data-placeholder', nhanTrong);
            napOption(s, selected);
            return s;
        }

        /* Nạp danh sách cho MỘT ô chọn, BỎ những mặt hàng đã có ở dòng khác.

           Chọn trùng một mặt hàng trên hai dòng là lỗi nhập liệu chứ không phải
           nhu cầu — cần nhiều thì tăng số lượng. Lọc thẳng khỏi danh sách thì
           không bấm nhầm được, thay vì cho chọn xong mới báo lỗi.

           GIỮ LẠI mặt hàng chính dòng này đang chọn, kể cả khi nó trùng với
           dòng khác: phiếu lập trước khi có luật này có thể đã có dòng trùng
           (HD-000005 đang có 2 dòng PT-0006). Lọc mất thì mở phiếu ra thấy ô
           trống, lưu một cái là bay luôn dòng đó.

           Ô tìm kiếm phủ bên trên đọc thẳng select.options mỗi lần gõ, nên sửa
           ở đây là nó tự theo, không phải đụng gì thêm. */
        function napOption(sel, dangChon){
            if (dangChon === undefined) dangChon = sel.value;

            var oDongKhac = {};
            tbody.querySelectorAll('.line-row select.part-sel').forEach(function (s){
                if (s !== sel && s.value) oDongKhac[s.value] = true;
            });

            sel.textContent = '';
            var o0 = document.createElement('option');
            o0.value = ''; o0.textContent = nhanTrong; sel.appendChild(o0);

            DS.forEach(function (op){
                if (oDongKhac[op.id] && String(op.id) !== String(dangChon)) return;
                var o = document.createElement('option');
                o.value = op.id; o.textContent = op.label; o.setAttribute('data-price', op.price);
                if (op.img) o.setAttribute('data-img', op.img);
                if (String(op.id) === String(dangChon)) o.selected = true;
                sel.appendChild(o);
            });
        }

        // Đổi/xoá một dòng là danh sách của MỌI dòng khác đều đổi theo
        function napLaiTatCa(){
            tbody.querySelectorAll('.line-row select.part-sel').forEach(function (s){ napOption(s); });
        }

        // Hết hàng để chọn thì thôi đẻ dòng trống, không thì ra một dòng rỗng
        // mà mở danh sách ra chẳng có gì.
        function conHangDeChon(){
            var dung = {};
            tbody.querySelectorAll('.line-row select.part-sel').forEach(function (s){ if (s.value) dung[s.value] = true; });
            return DS.some(function (op){ return !dung[op.id]; });
        }
        function td(child, cls){ var t = document.createElement('td'); if (cls) t.className = cls; if (child) t.appendChild(child); return t; }
        function inp(name, cls, val){ var i = document.createElement('input'); i.type = 'text'; i.name = name; i.className = 'form-control form-control-sm ' + cls; i.value = (val === 0 || val) ? val : ''; return i; }

        function addRow(data){
            data = data || {};
            var tr = document.createElement('tr'); tr.className = 'line-row';
            var sel = partSelect(data.part_id);
            var price = oTien(inp(tienTo + 'price[]', 'price text-right', data.price));

            sel.addEventListener('change', function (){
                var o = sel.options[sel.selectedIndex];
                var p = o ? o.getAttribute('data-price') : 0;
                /* Đổi mặt hàng thì giá PHẢI đi theo mặt hàng mới.

                   Bản cũ là `if (p && !money(price.value))` — chỉ điền khi ô giá
                   đang trống, với ý "đừng đè giá người dùng tự gõ". Nhưng nó
                   không phân biệt được giá người dùng gõ với giá do CHÍNH NÓ
                   vừa điền cho mặt hàng TRƯỚC ĐÓ. Hậu quả: chọn Ắc quy
                   (1.380.000) rồi đổi dòng đó sang Bugi thì giá vẫn nằm nguyên
                   1.380.000 — hoá đơn lưu sai giá mà không có gì báo.
                   Đã xảy ra thật trên HD-000006.

                   Chọn mặt hàng là hành động rõ ràng, giá theo đó là đúng.
                   Muốn giá riêng thì gõ đè sau khi đã chọn xong hàng. Mở phiếu
                   cũ ra sửa KHÔNG bị ảnh hưởng: dòng cũ dựng thẳng từ dữ liệu
                   đã lưu, không đi qua sự kiện change này. */
                if (p) price.value = p;
                // Mặt hàng vừa đổi -> mọi ô chọn khác phải bỏ/thêm nó lại
                napLaiTatCa();
                recompute();
                if (sel.value && tr === tbody.lastElementChild && conHangDeChon()) addRow();
            });
            tr.appendChild(td(sel));

            var q = inp(tienTo + 'qty[]', 'qty text-right', data.qty); soLuong(q);
            q.addEventListener('input', recompute); tr.appendChild(td(q));

            price.addEventListener('input', recompute); tr.appendChild(td(price));

            var discVal = (data.disc === 0 || data.disc) ? data.disc : '';
            if (discVal === '' || discVal == null){ var gd = groupDisc(); if (gd > 0) discVal = gd; }
            var disc = oPhanTram(inp(tienTo + 'disc[]', 'disc text-right', discVal));
            disc.addEventListener('input', recompute); tr.appendChild(td(disc));

            var amtTd = document.createElement('td'); amtTd.className = 'text-right align-middle';
            var amtSpan = document.createElement('span'); amtSpan.className = 'amt'; amtSpan.textContent = '0';
            amtTd.appendChild(amtSpan); tr.appendChild(amtTd);

            tr.appendChild(td(inp(tienTo + 'note[]', '', data.note)));

            var rm = document.createElement('button'); rm.type = 'button';
            rm.className = 'btn btn-sm btn-outline-danger rm-row'; rm.innerHTML = '&times;';
            tr.appendChild(td(rm, 'text-center'));

            tbody.appendChild(tr); recompute();
        }

        function tong(){
            var s = 0;
            tbody.querySelectorAll('.line-row').forEach(function (r){
                var d = num(r.querySelector('.disc').value); if (d < 0) d = 0; if (d > 100) d = 100;
                var amt = Math.round(num(r.querySelector('.qty').value) * money(r.querySelector('.price').value) * (1 - d / 100));
                r.querySelector('.amt').textContent = fmt(amt); s += amt;
            });
            return s;
        }

        /* Chỉ đếm dòng ĐÃ chọn mặt hàng — dòng trống cuối bảng không phải hàng thật.

           'select.part-sel' chứ không phải '.part-sel': ô tìm kiếm phủ lên trên
           (buildSearchSelect trong admin.js) chép nguyên className của select,
           nên nó cũng mang class part-sel và có value là nhãn đang hiện. Bỏ
           'select.' đi là mỗi dòng đếm thành hai. */
        function dem(){
            var n = 0;
            tbody.querySelectorAll('.line-row select.part-sel').forEach(function (s){ if (s.value) n++; });
            return n;
        }

        document.getElementById('add-' + ma).addEventListener('click', function (){ addRow(); });
        tbody.addEventListener('click', function (e){
            if (e.target && e.target.classList.contains('rm-row')){
                var r = e.target.closest('.line-row'); if (r) r.remove(); napLaiTatCa(); recompute();
            }
        });

        return { addRow: addRow, tong: tong, dem: dem, tbody: tbody };
    }

    bang.hang   = taoBang('hang',   'line_', DU_LIEU.hang,   '— Chọn hàng hoá —');
    bang.dichvu = taoBang('dichvu', 'sv_',   DU_LIEU.dichvu, '— Chọn dịch vụ —');

    document.getElementById('line-tabs').addEventListener('click', function (e){
        var a = e.target.closest('a[data-pane]');
        if (!a) return;
        e.preventDefault();
        this.querySelectorAll('a[data-pane]').forEach(function (x){ x.classList.remove('active'); });
        a.classList.add('active');
        document.querySelectorAll('.pane-dong').forEach(function (p){ p.style.display = 'none'; });
        document.getElementById('pane-' + a.getAttribute('data-pane')).style.display = '';
    });

    if (custSel){
        custSel.addEventListener('change', function (){
            var gd = groupDisc();
            document.querySelectorAll('.pane-dong .line-row .disc').forEach(function (d){ d.value = gd > 0 ? gd : ''; });
            recompute();
        });
    }
    vatEl.addEventListener('input', recompute);

    ['hang', 'dichvu'].forEach(function (ma){
        if (BAN_DAU[ma].length) BAN_DAU[ma].forEach(function (r){ bang[ma].addRow(r); });
        else bang[ma].addRow();
    });
    recompute();
})();
</script>
