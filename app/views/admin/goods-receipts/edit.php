<?php
$partJs = [];
foreach ($parts as $p){
    $label = $p['code'] . ' - ' . $p['name'] . (!empty($p['unit_name']) ? ' (' . $p['unit_name'] . ')' : '');
    $partJs[] = ['id' => (int) $p['id'], 'label' => $label];
}
$initRows = [];
if (!empty($old['line_part']) && is_array($old['line_part'])){
    foreach ($old['line_part'] as $i => $p){
        $initRows[] = [
            'part_id'  => (int) $p,
            'qty'      => isset($old['line_qty'][$i]) ? $old['line_qty'][$i] : '',
            'cost'     => isset($old['line_cost'][$i]) ? $old['line_cost'][$i] : '',
            'loc_id'   => isset($old['line_loc_id'][$i]) ? (int) $old['line_loc_id'][$i] : 0,
            'note'     => isset($old['line_note'][$i]) ? $old['line_note'][$i] : '',
        ];
    }
} else {
    foreach ($items as $it){
        $initRows[] = [
            'part_id'  => (int) $it['part_id'],
            'qty'      => rtrim(rtrim((string) $it['quantity'], '0'), '.'),
            'cost'     => (int) $it['unit_cost'],
            'loc_id'   => !empty($it['location_id']) ? (int) $it['location_id'] : 0,
            'note'     => $it['note'],
        ];
    }
}
$locJs = [];
foreach ($locations as $l){
    $locJs[] = ['id' => (int) $l['id'], 'wh' => (int) $l['warehouse_id'], 'path' => $l['full_path']];
}
$posted = ((int) $item['status'] === 1);
$sel = function($field, $default = '') use ($old, $item){
    if (isset($old[$field])) return $old[$field];
    return isset($item[$field]) ? $item[$field] : $default;
};
?>

@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}</div>
@endif

<div class="card {{$posted?'card-outline card-primary':'card-outline card-secondary'}}">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-dolly-flatbed mr-2"></i>{{$types[$item['receipt_type']] ?? 'Phiếu nhập'}} — <code>{{$item['receipt_no']}}</code></h3>
        <div class="card-tools">
            {!! $posted ? '<span class="badge badge-primary p-2">Đã ghi sổ</span>' : '<span class="badge badge-secondary p-2">Nháp</span>' !!}
        </div>
    </div>
</div>

@if ($posted)
    <div class="card"><div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Ngày</dt><dd class="col-sm-9">{{$item['receipt_date']}}</dd>
            <dt class="col-sm-3">Nhà cung cấp</dt><dd class="col-sm-9">{{!empty($item['partner_name'])?$item['partner_name']:'—'}}</dd>
            <dt class="col-sm-3">Diễn giải</dt><dd class="col-sm-9">{{!empty($item['reason'])?$item['reason']:'—'}}</dd>
            <dt class="col-sm-3">Tổng giá trị</dt><dd class="col-sm-9 font-weight-bold">{{number_format((float)$item['total_amount'],0,',','.')}} ₫</dd>
            @if (!empty($voucher))
            <dt class="col-sm-3">Bút toán</dt><dd class="col-sm-9"><code>{{$voucher['voucher_no']}}</code> <span class="text-muted">(Nợ 156 / Có TK đối ứng)</span></dd>
            @endif
        </dl>
    </div></div>

    <div class="card card-outline card-info">
        <div class="card-header"><h3 class="card-title">Dòng hàng đã nhập</h3></div>
        <div class="card-body table-responsive p-0">
            <table class="table table-sm mb-0">
                <thead><tr><th>Phụ tùng</th><th class="text-right">Số lượng</th><th class="text-right">Đơn giá</th><th class="text-right">Thành tiền</th><th>Vị trí</th></tr></thead>
                <tbody>
                @foreach ($items as $it)
                <tr>
                    <td><code>{{$it['part_code']}}</code> {{$it['part_name']}}</td>
                    <td class="text-right">{{rtrim(rtrim(number_format((float)$it['quantity'],3,',','.'),'0'),',')}} {{$it['unit_name']}}</td>
                    <td class="text-right">{{number_format((float)$it['unit_cost'],0,',','.')}}</td>
                    <td class="text-right">{{number_format((float)$it['amount'],0,',','.')}}</td>
                    <td class="text-muted">{{$it['location']}}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card"><div class="card-body">
        @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/unpost/'.$item['id']}}" onclick="return confirm('Huỷ ghi sổ sẽ hoàn tồn kho và xoá bút toán. Tiếp tục?')" class="btn btn-warning"><i class="fas fa-unlock mr-1"></i> Huỷ ghi sổ</a>
        @endif
        <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i> Về danh sách</a>
    </div></div>
@else
    <form action="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$item['id']}}" method="post">
        <?php echo csrf_field(); ?>
        <div class="card"><div class="card-body">
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label>Loại phiếu <span class="text-danger">*</span></label>
                    <select name="type" class="form-control">
                        @foreach ($types as $k => $label)
                        <option value="{{$k}}" {{$sel('receipt_type')==$k?'selected':''}}>{{$label}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label>Ngày <span class="text-danger">*</span></label>
                    <input type="date" name="receipt_date" class="form-control" value="{{$sel('receipt_date')}}"/>
                    {!! !empty($errors['receipt_date'])?'<small class="text-danger">'.e($errors['receipt_date']).'</small>':false !!}
                </div>
                <div class="form-group col-md-6">
                    <label>Kho nhập <span class="text-danger">*</span></label>
                    <select name="warehouse_id" class="form-control">
                        <option value="">— Chọn kho —</option>
                        @foreach ($warehouses as $w)
                        <option value="{{$w['id']}}" {{$sel('warehouse_id')==$w['id']?'selected':''}}>{{$w['code'].' - '.$w['name']}}</option>
                        @endforeach
                    </select>
                    {!! !empty($errors['warehouse_id'])?'<small class="text-danger">'.e($errors['warehouse_id']).'</small>':false !!}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Nhà cung cấp</label>
                    <select name="partner_id" class="form-control">
                        <option value="">— Chọn / vãng lai —</option>
                        @foreach ($partners as $pn)
                        <option value="{{$pn['id']}}" {{$sel('partner_id')==$pn['id']?'selected':''}}>{{$pn['code'].' - '.$pn['name']}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label>TK đối ứng (KT-6)</label>
                    <select name="counter_account_id" class="form-control">
                        <option value="">— Mặc định 331 (nhập mua) —</option>
                        @foreach ($accounts as $a)
                        <option value="{{$a['id']}}" {{$sel('counter_account_id')==$a['id']?'selected':''}}>{{$a['code'].' - '.$a['name']}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label>Diễn giải</label>
                    <input type="text" name="reason" class="form-control" value="{{$sel('reason')}}"/>
                </div>
            </div>
        </div></div>

        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list-ol mr-2"></i>Dòng hàng nhập</h3>
                <div class="card-tools"><button type="button" id="add-line" class="btn btn-sm btn-info"><i class="fas fa-plus mr-1"></i> Thêm dòng</button></div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-sm mb-0">
                    <thead><tr>
                        <th style="width:32%">Phụ tùng</th>
                        <th style="width:12%" class="text-right">Số lượng</th>
                        <th style="width:16%" class="text-right">Đơn giá</th>
                        <th style="width:16%" class="text-right">Thành tiền</th>
                        <th>Vị trí / Ghi chú</th>
                        <th style="width:44px"></th>
                    </tr></thead>
                    <tbody id="lines"></tbody>
                    <tfoot><tr>
                        <th colspan="3" class="text-right">Tổng cộng</th>
                        <th class="text-right"><span id="lines-total">0</span> ₫</th>
                        <th colspan="2"></th>
                    </tr></tfoot>
                </table>
            </div>
            {!! !empty($errors['lines'])?'<div class="card-body py-2"><small class="text-danger">'.e($errors['lines']).'</small></div>':false !!}
        </div>

        <div class="card"><div class="card-body">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu</button>
            @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/post/'.$item['id']}}" onclick="return confirm('Ghi sổ phiếu này? (lưu trước nếu vừa sửa)')" class="btn btn-success"><i class="fas fa-lock mr-1"></i> Ghi sổ</a>
            @endif
            @if (route('admin/'.$routeBase.'/delete/'.$item['id']))
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/delete/'.$item['id']}}" onclick="return confirm('Xoá phiếu nháp này?')" class="btn btn-outline-danger"><i class="fas fa-trash mr-1"></i> Xoá</a>
            @endif
            <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default">Về danh sách</a>
        </div></div>
    </form>

    <script>
    (function () {
        var PARTS = {!! json_encode($partJs, JSON_HEX_TAG|JSON_UNESCAPED_UNICODE) !!};
        var INIT  = {!! json_encode($initRows, JSON_HEX_TAG|JSON_UNESCAPED_UNICODE) !!};
        var LOCS  = {!! json_encode($locJs, JSON_HEX_TAG|JSON_UNESCAPED_UNICODE) !!};
        var whSelect = document.querySelector('select[name="warehouse_id"]');
        function currentWh(){ return whSelect ? whSelect.value : ''; }
        function locsOfWh(){ var wh = currentWh(); return LOCS.filter(function(l){ return String(l.wh)===String(wh); }); }
        function fillLocSelect(sel, selected){
            var opts = locsOfWh(); sel.innerHTML='';
            var o0 = document.createElement('option'); o0.value=''; o0.textContent = opts.length ? '— Chọn vị trí —' : '(kho chưa khai báo vị trí)'; sel.appendChild(o0);
            opts.forEach(function (l){ var o=document.createElement('option'); o.value=l.id; o.textContent=l.path; if (String(l.id)===String(selected)) o.selected=true; sel.appendChild(o); });
            sel.required = opts.length > 0;
        }
        var tbody = document.getElementById('lines');
        var totalEl = document.getElementById('lines-total');
        function fmt(n){ return (n || 0).toLocaleString('vi-VN'); }
        function num(v){ return parseFloat(String(v || '').replace(/[^\d.]/g, '')) || 0; }
        function money(v){ return parseInt(String(v || '').replace(/[^\d]/g, ''), 10) || 0; }
        function recompute(){
            var t = 0;
            tbody.querySelectorAll('.line-row').forEach(function (r){
                var amt = Math.round(num(r.querySelector('.qty').value) * money(r.querySelector('.cost').value));
                r.querySelector('.amt').textContent = fmt(amt); t += amt;
            });
            totalEl.textContent = fmt(t);
        }
        function buildSelect(name, opts, selected){
            var s = document.createElement('select'); s.name=name; s.className='form-control form-control-sm';
            var o0 = document.createElement('option'); o0.value=''; o0.textContent='— Chọn phụ tùng —'; s.appendChild(o0);
            opts.forEach(function (op){ var o=document.createElement('option'); o.value=op.id; o.textContent=op.label; if (String(op.id)===String(selected)) o.selected=true; s.appendChild(o); });
            return s;
        }
        function td(child, cls){ var t=document.createElement('td'); if (cls) t.className=cls; if (child) t.appendChild(child); return t; }
        function inp(name, cls, val){ var i=document.createElement('input'); i.type='text'; i.name=name; i.className='form-control form-control-sm '+cls; i.value=val||''; return i; }
        function addRow(data){
            data = data || {};
            var tr = document.createElement('tr'); tr.className='line-row';
            tr.appendChild(td(buildSelect('line_part[]', PARTS, data.part_id)));
            var q = inp('line_qty[]','qty text-right',data.qty); q.addEventListener('input', recompute); tr.appendChild(td(q));
            var c = inp('line_cost[]','cost text-right',data.cost); c.addEventListener('input', recompute); tr.appendChild(td(c));
            var amtTd = document.createElement('td'); amtTd.className='text-right align-middle';
            var amtSpan = document.createElement('span'); amtSpan.className='amt'; amtSpan.textContent='0'; amtTd.appendChild(amtSpan); tr.appendChild(amtTd);
            var wrap = document.createElement('div'); wrap.className='d-flex';
            var loc = document.createElement('select'); loc.name='line_loc_id[]'; loc.className='form-control form-control-sm loc-sel mr-1'; loc.style.maxWidth='150px'; fillLocSelect(loc, data.loc_id);
            var note = inp('line_note[]','',data.note); note.placeholder='Ghi chú';
            wrap.appendChild(loc); wrap.appendChild(note); tr.appendChild(td(wrap));
            var rm = document.createElement('button'); rm.type='button'; rm.className='btn btn-sm btn-outline-danger rm-row'; rm.innerHTML='&times;';
            tr.appendChild(td(rm,'text-center'));
            tbody.appendChild(tr); recompute();
        }
        document.getElementById('add-line').addEventListener('click', function (){ addRow(); });
        tbody.addEventListener('click', function (e){ if (e.target && e.target.classList.contains('rm-row')){ var r=e.target.closest('.line-row'); if (r) r.remove(); recompute(); } });
        if (whSelect){ whSelect.addEventListener('change', function (){ tbody.querySelectorAll('.loc-sel').forEach(function (sel){ fillLocSelect(sel, sel.value); }); }); }
        if (INIT.length){ INIT.forEach(addRow); } else { addRow(); }
    })();
    </script>
@endif
