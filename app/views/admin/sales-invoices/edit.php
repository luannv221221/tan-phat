<?php
/* Sửa hoá đơn — hai tab Hàng hoá / Dịch vụ, cùng bố cục với màn hình Lập hoá đơn.
   Xem chú thích đầy đủ ở app/views/admin/sales-invoices/add.php. */

$hangJs = $dichVuJs = [];
foreach ($parts as $p){
    $row = [
        'id'    => (int) $p['id'],
        'label' => $p['code'] . ' - ' . $p['name'] . (!empty($p['unit_name']) ? ' (' . $p['unit_name'] . ')' : ''),
        'price' => (int) (!empty($p['sale_price']) ? $p['sale_price'] : $p['price']),
        'img'   => !empty($p['image']) ? _WEB_URL . '/public/assets/uploads/parts/' . $p['image'] : '',
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
    /* Dòng đã lưu: xếp về đúng tab theo item_type. Hoá đơn cũ (lập trước khi có
       tab) chỉ toàn hàng hoá nên vẫn về tab 1. */
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

$tabs = [
    ['ma' => 'hang',   'nhan' => 'Hàng hoá', 'cot' => 'Hàng hoá', 'icon' => 'fa-boxes'],
    ['ma' => 'dichvu', 'nhan' => 'Dịch vụ',  'cot' => 'Dịch vụ',  'icon' => 'fa-screwdriver-wrench'],
];

$posted = ((int) $item['status'] === 1);
$sel = function($field, $default = '') use ($old, $item){ return isset($old[$field]) ? $old[$field] : (isset($item[$field]) ? $item[$field] : $default); };
$profit = (float) $item['subtotal'] - (float) $item['cost_amount'];
?>

@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}</div>
@endif

<div class="card {{$posted?'card-outline card-primary':'card-outline card-secondary'}}">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-file-invoice mr-2"></i>Hoá đơn <code>{{$item['invoice_no']}}</code></h3>
        <div class="card-tools">
            {!! $posted ? '<span class="badge badge-primary p-2">Đã ghi sổ</span>' : '<span class="badge badge-secondary p-2">Nháp</span>' !!}
        </div>
    </div>
</div>

@if ($posted)
    <div class="card"><div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Ngày</dt><dd class="col-sm-9">{{$item['invoice_date']}}</dd>
            <dt class="col-sm-3">Khách hàng</dt><dd class="col-sm-9">{{!empty($item['customer_name'])?$item['customer_name']:'Khách vãng lai'}}</dd>
            <dt class="col-sm-3">Kho xuất</dt><dd class="col-sm-9">{{!empty($item['warehouse_name']) ? $item['warehouse_name'] : '—'}}</dd>
            <dt class="col-sm-3">Doanh thu chưa thuế</dt><dd class="col-sm-9">{{number_format((float)$item['subtotal'],0,',','.')}} ₫</dd>
            <dt class="col-sm-3">Thuế GTGT ({{rtrim(rtrim(number_format((float)$item['vat_rate'],2,'.',''),'0'),'.')}}%)</dt><dd class="col-sm-9">{{number_format((float)$item['tax_amount'],0,',','.')}} ₫</dd>
            <dt class="col-sm-3">Tổng thanh toán</dt><dd class="col-sm-9 font-weight-bold">{{number_format((float)$item['total_amount'],0,',','.')}} ₫</dd>
            <dt class="col-sm-3">Giá vốn</dt><dd class="col-sm-9">{{number_format((float)$item['cost_amount'],0,',','.')}} ₫</dd>
            <dt class="col-sm-3">Lãi gộp</dt><dd class="col-sm-9 font-weight-bold {{$profit>=0?'text-success':'text-danger'}}">{{number_format($profit,0,',','.')}} ₫</dd>
        </dl>
    </div></div>

    <div class="card card-outline card-info">
        <div class="card-header"><h3 class="card-title">Dòng hàng đã bán</h3></div>
        <div class="card-body table-responsive p-0">
            <table class="table table-sm mb-0">
                <thead><tr><th>Hàng hoá</th><th class="text-right">SL</th><th class="text-right">Đơn giá</th><th class="text-right">CK %</th><th class="text-right">Thành tiền</th><th class="text-right">Giá vốn/đv</th></tr></thead>
                <tbody>
                @foreach ($items as $it)
                <tr>
                    <td><code>{{$it['part_code']}}</code> {{$it['part_name']}}</td>
                    <td class="text-right">{{rtrim(rtrim(number_format((float)$it['quantity'],3,',','.'),'0'),',')}} {{$it['unit_name']}}</td>
                    <td class="text-right">{{number_format((float)$it['unit_price'],0,',','.')}}</td>
                    <td class="text-right">{{(float)$it['discount_percent']>0?rtrim(rtrim(number_format((float)$it['discount_percent'],2,'.',''),'0'),'.').'%':'—'}}</td>
                    <td class="text-right">{{number_format((float)$it['amount'],0,',','.')}}</td>
                    <td class="text-right text-muted">{{number_format((float)$it['unit_cost'],0,',','.')}}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card card-outline card-warning">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-file-code mr-2"></i>Hoá đơn điện tử</h3>
            <div class="card-tools">{!! $item['einvoice_status']==='issued' ? '<span class="badge badge-success p-2">Đã phát hành</span>' : '<span class="badge badge-secondary p-2">Chưa phát hành</span>' !!}</div>
        </div>
        <div class="card-body">
        @if ($item['einvoice_status']==='issued')
            <dl class="row mb-2">
                <dt class="col-sm-3">Ký hiệu</dt><dd class="col-sm-9"><code>{{$item['einvoice_serial']}}</code></dd>
                <dt class="col-sm-3">Mẫu số</dt><dd class="col-sm-9">{{$item['einvoice_form']}}</dd>
                <dt class="col-sm-3">Số hoá đơn</dt><dd class="col-sm-9"><b>{{$item['einvoice_no']}}</b></dd>
                <dt class="col-sm-3">Ngày phát hành</dt><dd class="col-sm-9">{{$item['einvoice_issued_at']}}</dd>
            </dl>
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/einvoice-xml/'.$item['id']}}" class="btn btn-primary"><i class="fas fa-download mr-1"></i> Xuất XML</a>
            @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/einvoice-revoke/'.$item['id']}}" onclick="return confirm('Thu hồi HĐĐT này?')" class="btn btn-outline-danger"><i class="fas fa-undo mr-1"></i> Thu hồi</a>
            @endif
            <p class="text-muted small mt-2 mb-0"><i class="fas fa-info-circle mr-1"></i> XML theo cấu trúc tham khảo (TT78/NĐ123) để nộp phần mềm HĐĐT. Hệ thống không nối trực tiếp nhà cung cấp HĐĐT.</p>
        @else
            @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
            <form action="{{_WEB_URL.'/admin/'.$routeBase.'/einvoice/'.$item['id']}}" method="post" class="form-row align-items-end">
                <?php echo csrf_field(); ?>
                <div class="form-group col-md-3"><label class="small mb-1">Ký hiệu</label><input type="text" name="einvoice_serial" class="form-control form-control-sm" value="{{$eiDefaults['serial']}}"/></div>
                <div class="form-group col-md-2"><label class="small mb-1">Mẫu số</label><input type="text" name="einvoice_form" class="form-control form-control-sm" value="{{$eiDefaults['form']}}"/></div>
                <div class="form-group col-md-3"><label class="small mb-1">Số hoá đơn</label><input type="text" name="einvoice_no" class="form-control form-control-sm" value="{{$eiDefaults['nextNo']}}"/></div>
                <div class="form-group col-md-3"><button class="btn btn-sm btn-warning"><i class="fas fa-stamp mr-1"></i> Phát hành HĐĐT</button></div>
            </form>
            <p class="text-muted small mb-0"><i class="fas fa-info-circle mr-1"></i> Phát hành để gán số hoá đơn điện tử rồi xuất XML nộp phần mềm HĐĐT.</p>
            @else
            <p class="text-muted mb-0">Bạn không có quyền phát hành HĐĐT.</p>
            @endif
        @endif
        </div>
    </div>

    <div class="card"><div class="card-body">
        @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/unpost/'.$item['id']}}" onclick="return confirm('Huỷ ghi sổ sẽ hoàn tồn kho. Tiếp tục?')" class="btn btn-warning"><i class="fas fa-unlock mr-1"></i> Huỷ ghi sổ</a>
        @endif
        <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i> Về danh sách</a>
    </div></div>
@else
    <form action="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$item['id']}}" method="post">
        <?php echo csrf_field(); ?>
        <div class="card"><div class="card-body">
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label>Ngày <span class="text-danger">*</span></label>
                    <input type="date" name="invoice_date" class="form-control" value="{{$sel('invoice_date')}}"/>
                    {!! !empty($errors['invoice_date'])?'<small class="text-danger">'.e($errors['invoice_date']).'</small>':false !!}
                </div>
                <div class="form-group col-md-5">
                    <label>Kho xuất</label>
                    <select name="warehouse_id" class="form-control">
                        <option value="">— Không cần (hoá đơn chỉ có dịch vụ) —</option>
                        @foreach ($warehouses as $w)
                        <option value="{{$w['id']}}" {{$sel('warehouse_id')==$w['id']?'selected':''}}>{{$w['code'].' - '.$w['name']}}</option>
                        @endforeach
                    </select>
                    <small class="form-text text-muted">Có dòng hàng hoá thì bắt buộc chọn; toàn dịch vụ thì để trống.</small>
                    {!! !empty($errors['warehouse_id'])?'<small class="text-danger">'.e($errors['warehouse_id']).'</small>':false !!}
                </div>
                <div class="form-group col-md-2">
                    <label>Thuế GTGT (%)</label>
                    <input type="number" min="0" max="100" step="any" name="vat_rate" id="vat_rate" class="form-control text-right" value="{{$sel('vat_rate','10')}}"/>
                </div>
            </div>
            <div class="form-row">
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
                <?php if ($t['ma'] === 'hang'): ?>
                <div class="alert alert-warning mb-0 rounded-0" id="canh-bao-kho" style="display:none">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    Chưa chọn <b>Kho xuất</b> nên không thêm được dòng hàng hoá — ghi sổ sẽ không biết trừ tồn ở kho nào.
                    Chọn kho ở trên, hoặc chuyển sang tab <b>Dịch vụ</b> nếu hoá đơn này chỉ có công thợ.
                </div>
                <?php endif; ?>
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
                            <th style="width:15%" class="text-right">Đơn giá bán</th>
                            <th style="width:9%" class="text-right">CK %</th>
                            <th style="width:15%" class="text-right">Thành tiền</th>
                            <th>Ghi chú</th>
                            <th style="width:44px"></th>
                        </tr></thead>
                        <tbody id="lines-<?php echo e($t['ma']); ?>"></tbody>
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
                            <tr><th colspan="4" class="text-right">Tổng thanh toán</th>
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
            @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/post/'.$item['id']}}" onclick="return confirm('Ghi sổ hoá đơn này? (lưu trước nếu vừa sửa)')" class="btn btn-success"><i class="fas fa-lock mr-1"></i> Ghi sổ</a>
            @endif
            @if (route('admin/'.$routeBase.'/delete/'.$item['id']))
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/delete/'.$item['id']}}" onclick="return confirm('Xoá hoá đơn nháp này?')" class="btn btn-outline-danger"><i class="fas fa-trash mr-1"></i> Xoá</a>
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
        var whEl    = document.querySelector('select[name="warehouse_id"]');

        function fmt(n){ return (n || 0).toLocaleString('vi-VN'); }
        function num(v){ return parseFloat(String(v || '').replace(/[^\d.]/g, '')) || 0; }
        function money(v){ return parseInt(String(v || '').replace(/[^\d]/g, ''), 10) || 0; }
        function groupDisc(){ var v = custSel ? custSel.value : ''; return (v && DISC[v] != null) ? parseFloat(DISC[v]) : 0; }
        function coKho(){ return !!(whEl && whEl.value !== ''); }

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
                var o0 = document.createElement('option'); o0.value = ''; o0.textContent = nhanTrong; s.appendChild(o0);
                DS.forEach(function (op){
                    var o = document.createElement('option');
                    o.value = op.id; o.textContent = op.label; o.setAttribute('data-price', op.price);
                    if (op.img) o.setAttribute('data-img', op.img);
                    if (String(op.id) === String(selected)) o.selected = true;
                    s.appendChild(o);
                });
                return s;
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
                    if (p && !money(price.value)) price.value = p;
                    recompute();
                    // Chọn xong ở dòng CUỐI thì tự đẻ dòng trống kế tiếp — xem giải
                    // thích đầy đủ ở quotations/add.php.
                    if (sel.value && tr === tbody.lastElementChild) addRow();
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

            /* Chỉ đếm dòng ĐÃ chọn mặt hàng. 'select.part-sel' chứ không phải
               '.part-sel': ô tìm kiếm phủ lên trên chép nguyên className của select
               nên cũng mang class đó — bỏ 'select.' đi là mỗi dòng đếm thành hai. */
            function dem(){
                var n = 0;
                tbody.querySelectorAll('.line-row select.part-sel').forEach(function (s){ if (s.value) n++; });
                return n;
            }

            document.getElementById('add-' + ma).addEventListener('click', function (){ addRow(); });
            tbody.addEventListener('click', function (e){
                if (e.target && e.target.classList.contains('rm-row')){
                    var r = e.target.closest('.line-row'); if (r) r.remove(); recompute();
                }
            });

            return { addRow: addRow, tong: tong, dem: dem, tbody: tbody };
        }

        bang.hang   = taoBang('hang',   'line_', DU_LIEU.hang,   '— Chọn hàng hoá —');
        bang.dichvu = taoBang('dichvu', 'sv_',   DU_LIEU.dichvu, '— Chọn dịch vụ —');

        /* Chưa chọn kho -> chặn THÊM dòng hàng hoá và nói rõ lý do.

           CỐ Ý KHÔNG disable các ô của dòng đã nhập. Ô disabled thì trình duyệt
           KHÔNG gửi lên — đo được: nhập 1 dòng hàng rồi bỏ kho đi, form gửi lên
           0 dòng hàng hoá. Hoá đơn sẽ lưu êm ru và mất hàng, mà chốt "có hàng thì
           phải có kho" phía máy chủ cũng không kêu vì nó có thấy dòng nào đâu.

           Để nguyên cho gửi lên thì controller chặn kèm thông báo, và form quay
           lại vẫn còn đủ những gì đã gõ. Cảnh báo hiện ngay nên người dùng biết
           trước chứ không phải bấm Lưu mới biết. */
        function apLuatKho(){
            var mo   = coKho();
            var canh = document.getElementById('canh-bao-kho');
            var nut  = document.getElementById('add-hang');
            var pane = document.getElementById('pane-hang');

            if (canh) canh.style.display = mo ? 'none' : '';
            if (nut)  nut.disabled = !mo;
            pane.style.opacity = mo ? '' : '.65';
        }

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
        if (whEl) whEl.addEventListener('change', apLuatKho);
        vatEl.addEventListener('input', recompute);

        ['hang', 'dichvu'].forEach(function (ma){
            if (BAN_DAU[ma].length) BAN_DAU[ma].forEach(function (r){ bang[ma].addRow(r); });
            else bang[ma].addRow();
        });
        recompute();
        apLuatKho();
    })();
    </script>
@endif
