<?php
$partJs = [];
foreach ($parts as $p){
    $partJs[] = [
        'id'    => (int) $p['id'],
        'label' => $p['code'] . ' - ' . $p['name'] . (!empty($p['unit_name']) ? ' (' . $p['unit_name'] . ')' : ''),
        'price' => (int) (!empty($p['sale_price']) ? $p['sale_price'] : $p['price']),
    ];
}
$initRows = [];
if (!empty($old['line_part']) && is_array($old['line_part'])){
    foreach ($old['line_part'] as $i => $p){
        $initRows[] = ['part_id' => (int) $p,
            'qty'   => isset($old['line_qty'][$i]) ? $old['line_qty'][$i] : '',
            'price' => isset($old['line_price'][$i]) ? $old['line_price'][$i] : '',
            'note'  => isset($old['line_note'][$i]) ? $old['line_note'][$i] : ''];
    }
} else {
    foreach ($items as $it){
        $initRows[] = ['part_id' => (int) $it['part_id'],
            'qty'   => rtrim(rtrim((string) $it['quantity'], '0'), '.'),
            'price' => (int) $it['unit_price'],
            'note'  => $it['note']];
    }
}
$badge = ['draft' => 'secondary', 'sent' => 'info', 'accepted' => 'success', 'rejected' => 'danger'];
$sel = function($field, $default = '') use ($old, $item){ return isset($old[$field]) ? $old[$field] : (isset($item[$field]) ? $item[$field] : $default); };
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
                <input type="text" name="vat_rate" id="vat_rate" class="form-control text-right" value="{{$sel('vat_rate','0')}}"/>
            </div>
            <div class="form-group col-md-4">
                <label>Khách hàng</label>
                <select name="customer_id" class="form-control">
                    <option value="">— Chọn / vãng lai —</option>
                    @foreach ($partners as $pn)
                    <option value="{{$pn['id']}}" {{$sel('customer_id')==$pn['id']?'selected':''}}>{{$pn['code'].' - '.$pn['name']}}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label>Tên khách (nếu vãng lai)</label>
                <input type="text" name="customer_name" class="form-control" value="{{$sel('customer_name')}}"/>
            </div>
            <div class="form-group col-md-6">
                <label>Ghi chú</label>
                <input type="text" name="note" class="form-control" value="{{$sel('note')}}"/>
            </div>
        </div>
    </div></div>

    <div class="card card-outline card-info">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-list-ol mr-2"></i>Dòng hàng</h3>
            <div class="card-tools"><button type="button" id="add-line" class="btn btn-sm btn-info"><i class="fas fa-plus mr-1"></i> Thêm dòng</button></div>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-sm mb-0">
                <thead><tr>
                    <th style="width:34%">Phụ tùng</th>
                    <th style="width:12%" class="text-right">Số lượng</th>
                    <th style="width:16%" class="text-right">Đơn giá</th>
                    <th style="width:16%" class="text-right">Thành tiền</th>
                    <th>Ghi chú</th>
                    <th style="width:44px"></th>
                </tr></thead>
                <tbody id="lines"></tbody>
                <tfoot>
                    <tr><th colspan="3" class="text-right">Cộng chưa thuế</th><th class="text-right"><span id="sub-total">0</span> ₫</th><th colspan="2"></th></tr>
                    <tr><th colspan="3" class="text-right">Thuế GTGT</th><th class="text-right"><span id="tax-total">0</span> ₫</th><th colspan="2"></th></tr>
                    <tr><th colspan="3" class="text-right">Tổng cộng</th><th class="text-right"><span id="grand-total">0</span> ₫</th><th colspan="2"></th></tr>
                </tfoot>
            </table>
        </div>
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
    var PARTS = {!! json_encode($partJs, JSON_HEX_TAG|JSON_UNESCAPED_UNICODE) !!};
    var INIT  = {!! json_encode($initRows, JSON_HEX_TAG|JSON_UNESCAPED_UNICODE) !!};
    var tbody = document.getElementById('lines');
    var subEl = document.getElementById('sub-total'), taxEl = document.getElementById('tax-total'), grEl = document.getElementById('grand-total');
    var vatEl = document.getElementById('vat_rate');
    function fmt(n){ return (n || 0).toLocaleString('vi-VN'); }
    function num(v){ return parseFloat(String(v || '').replace(/[^\d.]/g, '')) || 0; }
    function money(v){ return parseInt(String(v || '').replace(/[^\d]/g, ''), 10) || 0; }
    function recompute(){
        var sub = 0;
        tbody.querySelectorAll('.line-row').forEach(function (r){
            var amt = Math.round(num(r.querySelector('.qty').value) * money(r.querySelector('.price').value));
            r.querySelector('.amt').textContent = fmt(amt); sub += amt;
        });
        var rate = num(vatEl.value); var tax = Math.round(sub * rate / 100);
        subEl.textContent = fmt(sub); taxEl.textContent = fmt(tax); grEl.textContent = fmt(sub + tax);
    }
    function partSelect(selected){
        var s = document.createElement('select'); s.name='line_part[]'; s.className='form-control form-control-sm part-sel';
        var o0 = document.createElement('option'); o0.value=''; o0.textContent='— Chọn phụ tùng —'; s.appendChild(o0);
        PARTS.forEach(function (op){ var o=document.createElement('option'); o.value=op.id; o.textContent=op.label; o.setAttribute('data-price', op.price); if (String(op.id)===String(selected)) o.selected=true; s.appendChild(o); });
        return s;
    }
    function td(child, cls){ var t=document.createElement('td'); if (cls) t.className=cls; if (child) t.appendChild(child); return t; }
    function inp(name, cls, val){ var i=document.createElement('input'); i.type='text'; i.name=name; i.className='form-control form-control-sm '+cls; i.value=(val===0||val)?val:''; return i; }
    function addRow(data){
        data = data || {};
        var tr = document.createElement('tr'); tr.className='line-row';
        var sel = partSelect(data.part_id);
        var price = inp('line_price[]','price text-right', data.price);
        sel.addEventListener('change', function(){ var o=sel.options[sel.selectedIndex]; var p=o?o.getAttribute('data-price'):0; if (p && !money(price.value)) price.value=p; recompute(); });
        tr.appendChild(td(sel));
        var q = inp('line_qty[]','qty text-right', data.qty); q.addEventListener('input', recompute); tr.appendChild(td(q));
        price.addEventListener('input', recompute); tr.appendChild(td(price));
        var amtTd = document.createElement('td'); amtTd.className='text-right align-middle';
        var amtSpan = document.createElement('span'); amtSpan.className='amt'; amtSpan.textContent='0'; amtTd.appendChild(amtSpan); tr.appendChild(amtTd);
        tr.appendChild(td(inp('line_note[]','', data.note)));
        var rm = document.createElement('button'); rm.type='button'; rm.className='btn btn-sm btn-outline-danger rm-row'; rm.innerHTML='&times;';
        tr.appendChild(td(rm,'text-center'));
        tbody.appendChild(tr); recompute();
    }
    document.getElementById('add-line').addEventListener('click', function (){ addRow(); });
    vatEl.addEventListener('input', recompute);
    tbody.addEventListener('click', function (e){ if (e.target && e.target.classList.contains('rm-row')){ var r=e.target.closest('.line-row'); if (r) r.remove(); recompute(); } });
    if (INIT.length){ INIT.forEach(addRow); } else { addRow(); }
})();
</script>
