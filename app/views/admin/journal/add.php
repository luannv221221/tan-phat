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
}
?>
<form action="" method="post">
    <?php echo csrf_field(); ?>
    @if (!empty($msg))
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-1"></i> {{$msg}}</div>
    @endif

    <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-file-invoice mr-2"></i>{{$page_name}}</h3></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label>Ngày <span class="text-danger">*</span></label>
                    <input type="date" name="voucher_date" class="form-control" value="{{!empty($old['voucher_date'])?$old['voucher_date']:$today}}"/>
                    {!! !empty($errors['voucher_date'])?'<small class="text-danger">'.e($errors['voucher_date']).'</small>':false !!}
                </div>
                <div class="form-group col-md-4">
                    <label>Đối tượng</label>
                    <input type="text" name="partner_name" class="form-control" value="{{!empty($old['partner_name'])?$old['partner_name']:''}}"/>
                </div>
                <div class="form-group col-md-5">
                    <label>Diễn giải chung</label>
                    <input type="text" name="reason" class="form-control" value="{{!empty($old['reason'])?$old['reason']:''}}"/>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-outline card-info">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-list-ol mr-2"></i>Định khoản (Nợ / Có)</h3>
            <div class="card-tools"><button type="button" id="add-line" class="btn btn-sm btn-info"><i class="fas fa-plus mr-1"></i> Thêm dòng</button></div>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-sm mb-0">
                <thead><tr>
                    <th style="width:26%">Nợ TK</th>
                    <th style="width:26%">Có TK</th>
                    <th style="width:16%" class="text-right">Số tiền</th>
                    <th>Diễn giải</th>
                    <th style="width:50px"></th>
                </tr></thead>
                <tbody id="lines"></tbody>
                <tfoot><tr>
                    <th colspan="2" class="text-right">Tổng cộng</th>
                    <th class="text-right"><span id="lines-total">0</span> ₫</th>
                    <th colspan="2"></th>
                </tr></tfoot>
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
    var ACCOUNTS = {!! json_encode($accJs, JSON_HEX_TAG|JSON_UNESCAPED_UNICODE) !!};
    var INIT     = {!! json_encode($initRows, JSON_HEX_TAG|JSON_UNESCAPED_UNICODE) !!};
    var tbody = document.getElementById('lines');
    var totalEl = document.getElementById('lines-total');

    function recompute(){
        var t = 0;
        tbody.querySelectorAll('.amt').forEach(function (i){ t += parseInt((i.value||'').replace(/[^\d]/g,''),10) || 0; });
        totalEl.textContent = (t||0).toLocaleString('vi-VN');
    }
    function sel(name, selected){
        var s = document.createElement('select'); s.name = name; s.className = 'form-control form-control-sm';
        var o0 = document.createElement('option'); o0.value=''; o0.textContent='— Chọn TK —'; s.appendChild(o0);
        ACCOUNTS.forEach(function (op){ var o=document.createElement('option'); o.value=op.id; o.textContent=op.label; if (String(op.id)===String(selected)) o.selected=true; s.appendChild(o); });
        return s;
    }
    function td(c){ var t=document.createElement('td'); if (c) t.appendChild(c); return t; }
    function addRow(d){
        d = d || {};
        var tr = document.createElement('tr'); tr.className='line-row';
        tr.appendChild(td(sel('line_debit[]', d.debit)));
        tr.appendChild(td(sel('line_credit[]', d.credit)));
        var amt=document.createElement('input'); amt.type='text'; amt.name='line_amount[]'; amt.className='form-control form-control-sm text-right amt'; amt.value=d.amount||''; amt.addEventListener('input', recompute);
        tr.appendChild(td(amt));
        var ds=document.createElement('input'); ds.type='text'; ds.name='line_desc[]'; ds.className='form-control form-control-sm'; ds.value=d.desc||'';
        tr.appendChild(td(ds));
        var rm=document.createElement('button'); rm.type='button'; rm.className='btn btn-sm btn-outline-danger rm-row'; rm.innerHTML='&times;';
        var tr6=td(rm); tr6.className='text-center'; tr.appendChild(tr6);
        tbody.appendChild(tr); recompute();
    }
    document.getElementById('add-line').addEventListener('click', function (){ addRow(); });
    tbody.addEventListener('click', function (e){ if (e.target && e.target.classList.contains('rm-row')){ var r=e.target.closest('.line-row'); if (r) r.remove(); recompute(); } });
    if (INIT.length){ INIT.forEach(addRow); } else { addRow(); }
})();
</script>
