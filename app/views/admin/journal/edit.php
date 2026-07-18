<?php
$accJs = [];
foreach ($accounts as $a){ $accJs[] = ['id' => (int) $a['id'], 'label' => $a['code'] . ' - ' . $a['name']]; }

$initRows = [];
if (!empty($old['line_debit']) && is_array($old['line_debit'])){
    foreach ($old['line_debit'] as $i => $d){
        $initRows[] = [
            'debit'  => (int) $d,
            'credit' => isset($old['line_credit'][$i]) ? (int) $old['line_credit'][$i] : 0,
            'amount' => isset($old['line_amount'][$i]) ? $old['line_amount'][$i] : '',
            'desc'   => isset($old['line_desc'][$i]) ? $old['line_desc'][$i] : '',
        ];
    }
} else {
    foreach ($entries as $en){
        $initRows[] = [
            'debit'  => (int) $en['debit_account_id'],
            'credit' => (int) $en['credit_account_id'],
            'amount' => (int) $en['amount'],
            'desc'   => $en['description'],
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

<div class="card card-outline card-secondary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-file-invoice mr-2"></i>Phiếu kế toán — <code>{{$item['voucher_no']}}</code></h3>
        <div class="card-tools">{!! $posted ? '<span class="badge badge-primary p-2">Đã ghi sổ</span>' : '<span class="badge badge-secondary p-2">Nháp</span>' !!}</div>
    </div>
</div>

@if ($posted)
    <div class="card"><div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Ngày</dt><dd class="col-sm-9">{{$item['voucher_date']}}</dd>
            <dt class="col-sm-3">Đối tượng</dt><dd class="col-sm-9">{{!empty($item['partner_name'])?$item['partner_name']:'—'}}</dd>
            <dt class="col-sm-3">Diễn giải</dt><dd class="col-sm-9">{{!empty($item['reason'])?$item['reason']:'—'}}</dd>
            <dt class="col-sm-3">Tổng tiền</dt><dd class="col-sm-9 font-weight-bold">{{number_format((float)$item['amount'],0,',','.')}} ₫</dd>
        </dl>
    </div></div>
    <div class="card card-outline card-info">
        <div class="card-header"><h3 class="card-title">Định khoản</h3></div>
        <div class="card-body table-responsive p-0">
            <table class="table table-sm mb-0">
                <thead><tr><th>Nợ TK</th><th>Có TK</th><th class="text-right">Số tiền</th><th>Diễn giải</th></tr></thead>
                <tbody>
                @foreach ($entries as $en)
                <tr>
                    <td>{{ isset($accountMap[$en['debit_account_id']]) ? $accountMap[$en['debit_account_id']] : $en['debit_account_id'] }}</td>
                    <td>{{ isset($accountMap[$en['credit_account_id']]) ? $accountMap[$en['credit_account_id']] : $en['credit_account_id'] }}</td>
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
        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/unpost/'.$item['id']}}" onclick="return confirm('Huỷ ghi sổ để sửa?')" class="btn btn-warning"><i class="fas fa-unlock mr-1"></i> Huỷ ghi sổ</a>
        @endif
        <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default">Về danh sách</a>
    </div></div>
@else
    <form action="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$item['id']}}" method="post">
        <?php echo csrf_field(); ?>
        <div class="card"><div class="card-body">
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label>Ngày <span class="text-danger">*</span></label>
                    <input type="date" name="voucher_date" class="form-control" value="{{!empty($old['voucher_date'])?$old['voucher_date']:$item['voucher_date']}}"/>
                    {!! !empty($errors['voucher_date'])?'<small class="text-danger">'.e($errors['voucher_date']).'</small>':false !!}
                </div>
                <div class="form-group col-md-4">
                    <label>Đối tượng</label>
                    <input type="text" name="partner_name" class="form-control" value="{{isset($old['partner_name'])?$old['partner_name']:(!empty($item['partner_name'])?$item['partner_name']:'')}}"/>
                </div>
                <div class="form-group col-md-5">
                    <label>Diễn giải chung</label>
                    <input type="text" name="reason" class="form-control" value="{{isset($old['reason'])?$old['reason']:(!empty($item['reason'])?$item['reason']:'')}}"/>
                </div>
            </div>
        </div></div>

        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list-ol mr-2"></i>Định khoản (Nợ / Có)</h3>
                <div class="card-tools"><button type="button" id="add-line" class="btn btn-sm btn-info"><i class="fas fa-plus mr-1"></i> Thêm dòng</button></div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-sm mb-0">
                    <thead><tr>
                        <th style="width:26%">Nợ TK</th><th style="width:26%">Có TK</th>
                        <th style="width:16%" class="text-right">Số tiền</th><th>Diễn giải</th><th style="width:50px"></th>
                    </tr></thead>
                    <tbody id="lines"></tbody>
                    <tfoot><tr><th colspan="2" class="text-right">Tổng cộng</th><th class="text-right"><span id="lines-total">0</span> ₫</th><th colspan="2"></th></tr></tfoot>
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
        var INIT     = {!! json_encode($initRows, JSON_HEX_TAG|JSON_UNESCAPED_UNICODE) !!};
        var tbody = document.getElementById('lines');
        var totalEl = document.getElementById('lines-total');
        function recompute(){ var t=0; tbody.querySelectorAll('.amt').forEach(function (i){ t+=parseInt((i.value||'').replace(/[^\d]/g,''),10)||0; }); totalEl.textContent=(t||0).toLocaleString('vi-VN'); }
        function sel(name, selected){
            var s=document.createElement('select'); s.name=name; s.className='form-control form-control-sm';
            var o0=document.createElement('option'); o0.value=''; o0.textContent='— Chọn TK —'; s.appendChild(o0);
            ACCOUNTS.forEach(function (op){ var o=document.createElement('option'); o.value=op.id; o.textContent=op.label; if (String(op.id)===String(selected)) o.selected=true; s.appendChild(o); });
            return s;
        }
        function td(c){ var t=document.createElement('td'); if (c) t.appendChild(c); return t; }
        function addRow(d){
            d=d||{};
            var tr=document.createElement('tr'); tr.className='line-row';
            tr.appendChild(td(sel('line_debit[]', d.debit)));
            tr.appendChild(td(sel('line_credit[]', d.credit)));
            var amt=document.createElement('input'); amt.type='text'; amt.name='line_amount[]'; amt.className='form-control form-control-sm text-right amt'; amt.value=d.amount||''; amt.addEventListener('input', recompute);
            tr.appendChild(td(amt));
            var ds=document.createElement('input'); ds.type='text'; ds.name='line_desc[]'; ds.className='form-control form-control-sm'; ds.value=d.desc||'';
            tr.appendChild(td(ds));
            var rm=document.createElement('button'); rm.type='button'; rm.className='btn btn-sm btn-outline-danger rm-row'; rm.innerHTML='&times;';
            var t6=td(rm); t6.className='text-center'; tr.appendChild(t6);
            tbody.appendChild(tr); recompute();
        }
        document.getElementById('add-line').addEventListener('click', function (){ addRow(); });
        tbody.addEventListener('click', function (e){ if (e.target && e.target.classList.contains('rm-row')){ var r=e.target.closest('.line-row'); if (r) r.remove(); recompute(); } });
        if (INIT.length){ INIT.forEach(addRow); } else { addRow(); }
    })();
    </script>
@endif
