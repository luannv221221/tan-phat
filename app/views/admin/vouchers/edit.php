<?php
$accJs  = [];
foreach ($accounts as $a){ $accJs[] = ['id' => (int) $a['id'], 'label' => $a['code'] . ' - ' . $a['name']]; }
$costJs = [];
foreach ($costItems as $c){ $costJs[] = ['id' => (int) $c['id'], 'label' => $c['code'] . ' - ' . $c['name']]; }
$projJs = [];
foreach ($projects as $p){ $projJs[] = ['id' => (int) $p['id'], 'label' => $p['code'] . ' - ' . $p['name']]; }

$initRows = [];
if (!empty($old['line_account']) && is_array($old['line_account'])){
    foreach ($old['line_account'] as $i => $acc){
        $initRows[] = [
            'account_id'   => (int) $acc,
            'amount'       => isset($old['line_amount'][$i]) ? $old['line_amount'][$i] : '',
            'description'  => isset($old['line_desc'][$i]) ? $old['line_desc'][$i] : '',
            'cost_item_id' => isset($old['line_cost'][$i]) ? $old['line_cost'][$i] : '',
            'project_id'   => isset($old['line_project'][$i]) ? $old['line_project'][$i] : '',
        ];
    }
} else {
    foreach ($entries as $en){
        $initRows[] = [
            'account_id'   => (int) $en['account_id'],
            'amount'       => (int) $en['amount'],
            'description'  => $en['description'],
            'cost_item_id' => $en['cost_item_id'],
            'project_id'   => $en['project_id'],
        ];
    }
}
$posted = ((int) $item['status'] === 1);
?>

@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}</div>
@endif

<div class="card {{$item['voucher_type']=='thu'?'card-outline card-success':'card-outline card-danger'}}">
    <div class="card-header">
        <h3 class="card-title">
            {!! $item['voucher_type']=='thu' ? '<i class="fas fa-arrow-down text-success mr-2"></i>' : '<i class="fas fa-arrow-up text-danger mr-2"></i>' !!}
            {{$types[$item['voucher_type']]}} — <code>{{$item['voucher_no']}}</code>
        </h3>
        <div class="card-tools">
            {!! $posted ? '<span class="badge badge-primary p-2">Đã ghi sổ</span>' : '<span class="badge badge-secondary p-2">Nháp</span>' !!}
        </div>
    </div>
</div>

@if ($posted)
    <!-- ĐÃ GHI SỔ: chỉ xem -->
    <div class="card">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Ngày</dt><dd class="col-sm-9">{{$item['voucher_date']}}</dd>
                <dt class="col-sm-3">Tài khoản quỹ</dt><dd class="col-sm-9"><code>{{$item['cash_account_id']}}</code></dd>
                <dt class="col-sm-3">Đối tượng</dt><dd class="col-sm-9">{{!empty($item['partner_name'])?$item['partner_name']:'—'}}</dd>
                <dt class="col-sm-3">Lý do</dt><dd class="col-sm-9">{{!empty($item['reason'])?$item['reason']:'—'}}</dd>
                <dt class="col-sm-3">Số tiền</dt><dd class="col-sm-9 font-weight-bold">{{number_format((float)$item['amount'],0,',','.')}} ₫</dd>
            </dl>
        </div>
    </div>
    <div class="card card-outline card-info">
        <div class="card-header"><h3 class="card-title">Định khoản</h3></div>
        <div class="card-body table-responsive p-0">
            <table class="table table-sm mb-0">
                <thead><tr><th>Tài khoản đối ứng</th><th class="text-right">Số tiền</th><th>Diễn giải</th></tr></thead>
                <tbody>
                @foreach ($entries as $en)
                <tr>
                    <td><code>{{$en['account_code']}}</code> {{$en['account_name']}}</td>
                    <td class="text-right">{{number_format((float)$en['amount'],0,',','.')}}</td>
                    <td>{{$en['description']}}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="card"><div class="card-body">
        @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/unpost/'.$item['id']}}" onclick="return confirm('Huỷ ghi sổ để sửa phiếu?')" class="btn btn-warning"><i class="fas fa-unlock mr-1"></i> Huỷ ghi sổ</a>
        @endif
        <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i> Về danh sách</a>
    </div></div>
@else
    <!-- NHÁP: sửa được -->
    <form action="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$item['id']}}" method="post">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="type" value="{{$item['voucher_type']}}"/>

        <div class="card">
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label>Ngày <span class="text-danger">*</span></label>
                        <input type="date" name="voucher_date" class="form-control" value="{{!empty($old['voucher_date'])?$old['voucher_date']:$item['voucher_date']}}"/>
                        {!! !empty($errors['voucher_date'])?'<small class="text-danger">'.e($errors['voucher_date']).'</small>':false !!}
                    </div>
                    <div class="form-group col-md-9">
                        <label>Tài khoản quỹ <span class="text-danger">*</span></label>
                        <select name="cash_account_id" class="form-control">
                            <option value="">— Chọn quỹ —</option>
                            @foreach ($cashAccounts as $ca)
                            <option value="{{$ca['id']}}" {{(isset($old['cash_account_id'])?$old['cash_account_id']:$item['cash_account_id'])==$ca['id']?'selected':''}}>{{$ca['code'].' - '.$ca['name']}}</option>
                            @endforeach
                        </select>
                        {!! !empty($errors['cash_account_id'])?'<small class="text-danger">'.e($errors['cash_account_id']).'</small>':false !!}
                    </div>
                </div>
                <?php $selPartner = isset($old['partner_id']) ? $old['partner_id'] : $item['partner_id']; ?>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Đối tượng</label>
                        <select name="partner_id" class="form-control">
                            <option value="">— Chọn / vãng lai —</option>
                            @foreach ($partners as $pn)
                            <option value="{{$pn['id']}}" {{$selPartner==$pn['id']?'selected':''}}>{{$pn['code'].' - '.$pn['name']}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Tên (nếu vãng lai)</label>
                        <input type="text" name="partner_name" class="form-control" value="{{isset($old['partner_name'])?$old['partner_name']:(!empty($item['partner_name'])?$item['partner_name']:'')}}"/>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Lý do</label>
                        <input type="text" name="reason" class="form-control" value="{{isset($old['reason'])?$old['reason']:(!empty($item['reason'])?$item['reason']:'')}}"/>
                    </div>
                </div>
                <p class="text-muted small mb-0"><i class="fas fa-info-circle mr-1"></i> <b>Phiếu thu</b>: Nợ TK quỹ / Có các TK dưới. <b>Phiếu chi</b>: Nợ các TK dưới / Có TK quỹ.</p>
            </div>
        </div>

        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list-ol mr-2"></i>Định khoản</h3>
                <div class="card-tools"><button type="button" id="add-line" class="btn btn-sm btn-info"><i class="fas fa-plus mr-1"></i> Thêm dòng</button></div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-sm mb-0">
                    <thead><tr>
                        <th style="width:26%">Tài khoản đối ứng</th>
                        <th style="width:16%" class="text-right">Số tiền</th>
                        <th>Diễn giải</th>
                        <th style="width:16%">Mã phí</th>
                        <th style="width:16%">Mã vụ việc</th>
                        <th style="width:50px"></th>
                    </tr></thead>
                    <tbody id="lines"></tbody>
                    <tfoot><tr>
                        <th class="text-right">Tổng cộng</th>
                        <th class="text-right"><span id="lines-total">0</span> ₫</th>
                        <th colspan="4"></th>
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
        var ACCOUNTS = {!! json_encode($accJs, JSON_HEX_TAG|JSON_UNESCAPED_UNICODE) !!};
        var COSTS    = {!! json_encode($costJs, JSON_HEX_TAG|JSON_UNESCAPED_UNICODE) !!};
        var PROJECTS = {!! json_encode($projJs, JSON_HEX_TAG|JSON_UNESCAPED_UNICODE) !!};
        var INIT     = {!! json_encode($initRows, JSON_HEX_TAG|JSON_UNESCAPED_UNICODE) !!};

        var tbody = document.getElementById('lines');
        var totalEl = document.getElementById('lines-total');
        function fmt(n){ return (n || 0).toLocaleString('vi-VN'); }
        function recompute(){
            var t = 0;
            tbody.querySelectorAll('.amt').forEach(function (i){ t += parseInt((i.value || '').replace(/[^\d]/g, ''), 10) || 0; });
            totalEl.textContent = fmt(t);
        }
        function buildSelect(name, opts, selected, placeholder){
            var s = document.createElement('select'); s.name = name; s.className = 'form-control form-control-sm';
            if (placeholder !== null){ var o0 = document.createElement('option'); o0.value=''; o0.textContent=placeholder; s.appendChild(o0); }
            opts.forEach(function (op){ var o = document.createElement('option'); o.value=op.id; o.textContent=op.label; if (String(op.id)===String(selected)) o.selected=true; s.appendChild(o); });
            return s;
        }
        function td(child){ var t = document.createElement('td'); if (child) t.appendChild(child); return t; }
        function addRow(data){
            data = data || {};
            var tr = document.createElement('tr'); tr.className = 'line-row';
            tr.appendChild(td(buildSelect('line_account[]', ACCOUNTS, data.account_id, '— Chọn TK —')));
            var amt = document.createElement('input'); amt.type='text'; amt.name='line_amount[]'; amt.className='form-control form-control-sm text-right amt'; amt.value=data.amount||''; amt.addEventListener('input', recompute);
            tr.appendChild(td(amt));
            var d = document.createElement('input'); d.type='text'; d.name='line_desc[]'; d.className='form-control form-control-sm'; d.value=data.description||'';
            tr.appendChild(td(d));
            tr.appendChild(td(buildSelect('line_cost[]', COSTS, data.cost_item_id, '—')));
            tr.appendChild(td(buildSelect('line_project[]', PROJECTS, data.project_id, '—')));
            var rm = document.createElement('button'); rm.type='button'; rm.className='btn btn-sm btn-outline-danger rm-row'; rm.innerHTML='&times;';
            var tdrm = td(rm); tdrm.className='text-center'; tr.appendChild(tdrm);
            tbody.appendChild(tr); recompute();
        }
        document.getElementById('add-line').addEventListener('click', function (){ addRow(); });
        tbody.addEventListener('click', function (e){ if (e.target && e.target.classList.contains('rm-row')){ var r=e.target.closest('.line-row'); if (r) r.remove(); recompute(); } });
        if (INIT.length){ INIT.forEach(addRow); } else { addRow(); }
    })();
    </script>
@endif
