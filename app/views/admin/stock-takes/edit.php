<?php
$partJs = [];
foreach ($parts as $p){ $partJs[] = ['id' => (int) $p['id'], 'label' => $p['code'] . ' - ' . $p['name'] . (!empty($p['unit_name']) ? ' (' . $p['unit_name'] . ')' : '')]; }
$initRows = [];
if (!empty($old['line_part']) && is_array($old['line_part'])){
    foreach ($old['line_part'] as $i => $p){ $initRows[] = ['part_id' => (int) $p, 'actual' => isset($old['line_actual'][$i]) ? $old['line_actual'][$i] : '', 'note' => isset($old['line_note'][$i]) ? $old['line_note'][$i] : '']; }
} else {
    foreach ($items as $it){ $initRows[] = ['part_id' => (int) $it['part_id'], 'actual' => rtrim(rtrim((string) $it['actual_qty'], '0'), '.'), 'note' => $it['note']]; }
}
$posted = ((int) $item['status'] === 1);
$sel = function($field, $default = '') use ($old, $item){ return isset($old[$field]) ? $old[$field] : (isset($item[$field]) ? $item[$field] : $default); };
$fmtQ = function($n){ return rtrim(rtrim(number_format((float) $n, 3, ',', '.'), '0'), ','); };
?>
@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}</div>
@endif

<div class="card {{$posted?'card-outline card-primary':'card-outline card-secondary'}}">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-clipboard-check mr-2"></i>Kiểm kê <code>{{$item['take_no']}}</code></h3>
        <div class="card-tools">{!! $posted ? '<span class="badge badge-primary p-2">Đã chốt</span>' : '<span class="badge badge-secondary p-2">Nháp</span>' !!}</div>
    </div>
</div>

@if ($posted)
    <div class="card"><div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Ngày</dt><dd class="col-sm-9">{{$item['take_date']}}</dd>
            <dt class="col-sm-3">Lý do</dt><dd class="col-sm-9">{{!empty($item['reason'])?$item['reason']:'—'}}</dd>
            <dt class="col-sm-3">Giá trị thừa</dt><dd class="col-sm-9 text-success">{{number_format((float)$item['surplus_value'],0,',','.')}} ₫</dd>
            <dt class="col-sm-3">Giá trị thiếu</dt><dd class="col-sm-9 text-danger">{{number_format((float)$item['shortage_value'],0,',','.')}} ₫</dd>
            @if (!empty($voucher))
            <dt class="col-sm-3">Bút toán</dt><dd class="col-sm-9"><code>{{$voucher['voucher_no']}}</code> <span class="text-muted">(thừa Nợ156/Có711 · thiếu Nợ632/Có156)</span></dd>
            @endif
        </dl>
    </div></div>
    <div class="card card-outline card-info">
        <div class="card-header"><h3 class="card-title">Kết quả kiểm kê</h3></div>
        <div class="card-body table-responsive p-0">
            <table class="table table-sm mb-0">
                <thead><tr><th>Phụ tùng</th><th class="text-right">Tồn sổ</th><th class="text-right">Thực tế</th><th class="text-right">Chênh lệch</th><th class="text-right">Giá vốn/đv</th><th class="text-right">Giá trị lệch</th></tr></thead>
                <tbody>
                @foreach ($items as $it)
                <?php $d = (float) $it['diff_qty']; ?>
                <tr>
                    <td><code>{{$it['part_code']}}</code> {{$it['part_name']}}</td>
                    <td class="text-right">{{$fmtQ($it['book_qty'])}}</td>
                    <td class="text-right">{{$fmtQ($it['actual_qty'])}}</td>
                    <td class="text-right {{$d>0?'text-success':($d<0?'text-danger':'')}}">{{($d>0?'+':'').$fmtQ($it['diff_qty'])}}</td>
                    <td class="text-right">{{number_format((float)$it['unit_cost'],0,',','.')}}</td>
                    <td class="text-right">{{number_format((float)$it['diff_value'],0,',','.')}}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="card"><div class="card-body">
        @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/unpost/'.$item['id']}}" onclick="return confirm('Huỷ chốt sẽ hoàn tồn kho và xoá bút toán. Tiếp tục?')" class="btn btn-warning"><i class="fas fa-unlock mr-1"></i> Huỷ chốt</a>
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
                    <input type="date" name="take_date" class="form-control" value="{{$sel('take_date')}}"/>
                    {!! !empty($errors['take_date'])?'<small class="text-danger">'.e($errors['take_date']).'</small>':false !!}
                </div>
                <div class="form-group col-md-5">
                    <label>Kho kiểm kê <span class="text-danger">*</span></label>
                    <select name="warehouse_id" class="form-control">
                        <option value="">— Chọn kho —</option>
                        @foreach ($warehouses as $w)
                        <option value="{{$w['id']}}" {{$sel('warehouse_id')==$w['id']?'selected':''}}>{{$w['code'].' - '.$w['name']}}</option>
                        @endforeach
                    </select>
                    {!! !empty($errors['warehouse_id'])?'<small class="text-danger">'.e($errors['warehouse_id']).'</small>':false !!}
                </div>
                <div class="form-group col-md-4"><label>Lý do</label><input type="text" name="reason" class="form-control" value="{{$sel('reason')}}"/></div>
            </div>
        </div></div>

        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list-ol mr-2"></i>Phụ tùng kiểm kê</h3>
                <div class="card-tools"><button type="button" id="add-line" class="btn btn-sm btn-info"><i class="fas fa-plus mr-1"></i> Thêm dòng</button></div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th style="width:45%">Phụ tùng</th><th style="width:20%" class="text-right">SL thực tế</th><th>Ghi chú</th><th style="width:44px"></th></tr></thead>
                    <tbody id="lines"></tbody>
                </table>
            </div>
            {!! !empty($errors['lines'])?'<div class="card-body py-2"><small class="text-danger">'.e($errors['lines']).'</small></div>':false !!}
        </div>

        <div class="card"><div class="card-body">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu</button>
            @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/post/'.$item['id']}}" onclick="return confirm('Chốt kiểm kê? Sẽ điều chỉnh tồn kho theo số thực tế.')" class="btn btn-success"><i class="fas fa-lock mr-1"></i> Chốt kiểm kê</a>
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
            tr.appendChild(td(inp('line_actual[]','text-right',data.actual)));
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
@endif
