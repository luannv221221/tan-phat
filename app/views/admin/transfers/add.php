<?php
$partJs = [];
foreach ($parts as $p){
    $partJs[] = ['id' => (int) $p['id'], 'label' => $p['code'] . ' - ' . $p['name'] . (!empty($p['unit_name']) ? ' (' . $p['unit_name'] . ')' : '')];
}
$initRows = [];
if (!empty($old['line_part']) && is_array($old['line_part'])){
    foreach ($old['line_part'] as $i => $p){
        $initRows[] = ['part_id' => (int) $p, 'qty' => isset($old['line_qty'][$i]) ? $old['line_qty'][$i] : '', 'note' => isset($old['line_note'][$i]) ? $old['line_note'][$i] : ''];
    }
}
?>
<form action="" method="post">
    <?php echo csrf_field(); ?>
    @if (!empty($msg))
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-1"></i> {{$msg}}</div>
    @endif

    <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-exchange-alt mr-2"></i>{{$page_name}}</h3></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label>Ngày <span class="text-danger">*</span></label>
                    <input type="date" name="transfer_date" class="form-control" value="{{!empty($old['transfer_date'])?$old['transfer_date']:$today}}"/>
                    {!! !empty($errors['transfer_date'])?'<small class="text-danger">'.e($errors['transfer_date']).'</small>':false !!}
                </div>
                <div class="form-group col-md-4">
                    <label>Từ kho (nguồn) <span class="text-danger">*</span></label>
                    <select name="from_warehouse_id" class="form-control">
                        <option value="">— Chọn kho —</option>
                        @foreach ($warehouses as $w)
                        <option value="{{$w['id']}}" {{(!empty($old['from_warehouse_id']) && $old['from_warehouse_id']==$w['id'])?'selected':''}}>{{$w['code'].' - '.$w['name']}}</option>
                        @endforeach
                    </select>
                    {!! !empty($errors['from_warehouse_id'])?'<small class="text-danger">'.e($errors['from_warehouse_id']).'</small>':false !!}
                </div>
                <div class="form-group col-md-4">
                    <label>Đến kho (đích) <span class="text-danger">*</span></label>
                    <select name="to_warehouse_id" class="form-control">
                        <option value="">— Chọn kho —</option>
                        @foreach ($warehouses as $w)
                        <option value="{{$w['id']}}" {{(!empty($old['to_warehouse_id']) && $old['to_warehouse_id']==$w['id'])?'selected':''}}>{{$w['code'].' - '.$w['name']}}</option>
                        @endforeach
                    </select>
                    {!! !empty($errors['to_warehouse_id'])?'<small class="text-danger">'.e($errors['to_warehouse_id']).'</small>':false !!}
                </div>
            </div>
            <div class="form-group mb-0">
                <label>Lý do</label>
                <input type="text" name="reason" class="form-control" value="{{!empty($old['reason'])?$old['reason']:''}}"/>
            </div>
            <p class="text-muted small mb-0 mt-2"><i class="fas fa-info-circle mr-1"></i> Ghi sổ sẽ chuyển tồn theo giá vốn bình quân từ kho nguồn sang kho đích (không sinh bút toán).</p>
        </div>
    </div>

    <div class="card card-outline card-info">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-list-ol mr-2"></i>Dòng hàng chuyển</h3>
            <div class="card-tools"><button type="button" id="add-line" class="btn btn-sm btn-info"><i class="fas fa-plus mr-1"></i> Thêm dòng</button></div>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-sm mb-0">
                <thead><tr><th style="width:45%">Phụ tùng</th><th style="width:18%" class="text-right">Số lượng</th><th>Ghi chú</th><th style="width:44px"></th></tr></thead>
                <tbody id="lines"></tbody>
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
        tr.appendChild(td(inp('line_qty[]','text-right',data.qty)));
        tr.appendChild(td(inp('line_note[]','',data.note)));
        var rm = document.createElement('button'); rm.type='button'; rm.className='btn btn-sm btn-outline-danger rm-row'; rm.innerHTML='&times;';
        tr.appendChild(td(rm,'text-center'));
        tbody.appendChild(tr);
    }
    document.getElementById('add-line').addEventListener('click', function (){ addRow(); });
    tbody.addEventListener('click', function (e){ if (e.target && e.target.classList.contains('rm-row')){ var r=e.target.closest('.line-row'); if (r) r.remove(); } });
    if (INIT.length){ INIT.forEach(addRow); } else { addRow(); }
})();
</script>
