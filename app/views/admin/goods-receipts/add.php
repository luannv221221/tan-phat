<?php
// Dữ liệu dropdown dòng hàng
$partJs = [];
foreach ($parts as $p){
    $label = $p['code'] . ' - ' . $p['name'] . (!empty($p['unit_name']) ? ' (' . $p['unit_name'] . ')' : '');
    $partJs[] = ['id' => (int) $p['id'], 'label' => $label];
}
// Dòng khởi tạo (giữ khi lỗi validate)
$initRows = [];
if (!empty($old['line_part']) && is_array($old['line_part'])){
    foreach ($old['line_part'] as $i => $p){
        $initRows[] = [
            'part_id'  => (int) $p,
            'qty'      => isset($old['line_qty'][$i]) ? $old['line_qty'][$i] : '',
            'cost'     => isset($old['line_cost'][$i]) ? $old['line_cost'][$i] : '',
            'location' => isset($old['line_loc'][$i]) ? $old['line_loc'][$i] : '',
            'note'     => isset($old['line_note'][$i]) ? $old['line_note'][$i] : '',
        ];
    }
}
$selType = !empty($old['type']) ? $old['type'] : 'nhap_mua';
?>
<datalist id="loc-list">
    @foreach ($locations as $loc)
    <option value="{{$loc['full_path']}}">{{$loc['warehouse_code']}}</option>
    @endforeach
</datalist>
<form action="" method="post">
    <?php echo csrf_field(); ?>

    @if (!empty($msg))
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-1"></i> {{$msg}}</div>
    @endif

    <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-dolly-flatbed mr-2"></i>{{$page_name}}</h3></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label>Loại phiếu <span class="text-danger">*</span></label>
                    <select name="type" class="form-control">
                        @foreach ($types as $k => $label)
                        <option value="{{$k}}" {{$selType==$k?'selected':''}}>{{$label}}</option>
                        @endforeach
                    </select>
                    {!! !empty($errors['type'])?'<small class="text-danger">'.e($errors['type']).'</small>':false !!}
                </div>
                <div class="form-group col-md-3">
                    <label>Ngày <span class="text-danger">*</span></label>
                    <input type="date" name="receipt_date" class="form-control" value="{{!empty($old['receipt_date'])?$old['receipt_date']:$today}}"/>
                    {!! !empty($errors['receipt_date'])?'<small class="text-danger">'.e($errors['receipt_date']).'</small>':false !!}
                </div>
                <div class="form-group col-md-6">
                    <label>Kho nhập <span class="text-danger">*</span></label>
                    <select name="warehouse_id" class="form-control">
                        <option value="">— Chọn kho —</option>
                        @foreach ($warehouses as $w)
                        <option value="{{$w['id']}}" {{(!empty($old['warehouse_id']) && $old['warehouse_id']==$w['id'])?'selected':''}}>{{$w['code'].' - '.$w['name']}}</option>
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
                        <option value="{{$pn['id']}}" {{(!empty($old['partner_id']) && $old['partner_id']==$pn['id'])?'selected':''}}>{{$pn['code'].' - '.$pn['name']}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label>TK đối ứng (KT-6)</label>
                    <select name="counter_account_id" class="form-control">
                        <option value="">— Mặc định 331 (nhập mua) —</option>
                        @foreach ($accounts as $a)
                        <option value="{{$a['id']}}" {{(!empty($old['counter_account_id']) && $old['counter_account_id']==$a['id'])?'selected':''}}>{{$a['code'].' - '.$a['name']}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label>Diễn giải</label>
                    <input type="text" name="reason" class="form-control" value="{{!empty($old['reason'])?$old['reason']:''}}"/>
                </div>
            </div>
            <p class="text-muted small mb-0"><i class="fas fa-info-circle mr-1"></i> Ghi sổ sẽ định khoản <b>Nợ 156 Hàng hóa / Có TK đối ứng</b> và cập nhật tồn theo bình quân gia quyền.</p>
        </div>
    </div>

    <div class="card card-outline card-info">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-list-ol mr-2"></i>Dòng hàng nhập</h3>
            <div class="card-tools"><button type="button" id="add-line" class="btn btn-sm btn-info"><i class="fas fa-plus mr-1"></i> Thêm dòng</button></div>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th style="width:32%">Phụ tùng</th>
                        <th style="width:12%" class="text-right">Số lượng</th>
                        <th style="width:16%" class="text-right">Đơn giá</th>
                        <th style="width:16%" class="text-right">Thành tiền</th>
                        <th>Vị trí / Ghi chú</th>
                        <th style="width:44px"></th>
                    </tr>
                </thead>
                <tbody id="lines"></tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-right">Tổng cộng</th>
                        <th class="text-right"><span id="lines-total">0</span> ₫</th>
                        <th colspan="2"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        {!! !empty($errors['lines'])?'<div class="card-body py-2"><small class="text-danger">'.e($errors['lines']).'</small></div>':false !!}
    </div>

    <div class="card"><div class="card-body">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu (nháp)</button>
        <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i> Quay lại</a>
    </div></div>
</form>

<script>
(function () {
    var PARTS = {!! json_encode($partJs, JSON_HEX_TAG|JSON_UNESCAPED_UNICODE) !!};
    var INIT  = {!! json_encode($initRows, JSON_HEX_TAG|JSON_UNESCAPED_UNICODE) !!};

    var tbody = document.getElementById('lines');
    var totalEl = document.getElementById('lines-total');
    function fmt(n){ return (n || 0).toLocaleString('vi-VN'); }
    function num(v){ return parseFloat(String(v || '').replace(/[^\d.]/g, '')) || 0; }
    function money(v){ return parseInt(String(v || '').replace(/[^\d]/g, ''), 10) || 0; }

    function recompute(){
        var t = 0;
        tbody.querySelectorAll('.line-row').forEach(function (r){
            var q = num(r.querySelector('.qty').value);
            var c = money(r.querySelector('.cost').value);
            var amt = Math.round(q * c);
            r.querySelector('.amt').textContent = fmt(amt);
            t += amt;
        });
        totalEl.textContent = fmt(t);
    }

    function buildSelect(name, opts, selected){
        var s = document.createElement('select'); s.name = name; s.className = 'form-control form-control-sm';
        var o0 = document.createElement('option'); o0.value=''; o0.textContent='— Chọn phụ tùng —'; s.appendChild(o0);
        opts.forEach(function (op){ var o = document.createElement('option'); o.value=op.id; o.textContent=op.label; if (String(op.id)===String(selected)) o.selected=true; s.appendChild(o); });
        return s;
    }
    function td(child, cls){ var t = document.createElement('td'); if (cls) t.className = cls; if (child) t.appendChild(child); return t; }
    function inp(name, cls, val){ var i = document.createElement('input'); i.type='text'; i.name=name; i.className='form-control form-control-sm '+cls; i.value=val||''; return i; }

    function addRow(data){
        data = data || {};
        var tr = document.createElement('tr'); tr.className = 'line-row';
        tr.appendChild(td(buildSelect('line_part[]', PARTS, data.part_id)));

        var q = inp('line_qty[]', 'qty text-right', data.qty); q.addEventListener('input', recompute);
        tr.appendChild(td(q));
        var c = inp('line_cost[]', 'cost text-right', data.cost); c.addEventListener('input', recompute);
        tr.appendChild(td(c));

        var amtTd = document.createElement('td'); amtTd.className='text-right align-middle';
        var amtSpan = document.createElement('span'); amtSpan.className='amt'; amtSpan.textContent='0'; amtTd.appendChild(amtSpan);
        tr.appendChild(amtTd);

        var wrap = document.createElement('div'); wrap.className='d-flex';
        var loc = inp('line_loc[]', 'mr-1', data.location); loc.placeholder='Vị trí'; loc.style.maxWidth='120px'; loc.setAttribute('list','loc-list');
        var note = inp('line_note[]', '', data.note); note.placeholder='Ghi chú';
        wrap.appendChild(loc); wrap.appendChild(note);
        tr.appendChild(td(wrap));

        var rm = document.createElement('button'); rm.type='button'; rm.className='btn btn-sm btn-outline-danger rm-row'; rm.innerHTML='&times;';
        tr.appendChild(td(rm, 'text-center'));

        tbody.appendChild(tr); recompute();
    }

    document.getElementById('add-line').addEventListener('click', function (){ addRow(); });
    tbody.addEventListener('click', function (e){ if (e.target && e.target.classList.contains('rm-row')){ var r=e.target.closest('.line-row'); if (r) r.remove(); recompute(); } });
    if (INIT.length){ INIT.forEach(addRow); } else { addRow(); }
})();
</script>
