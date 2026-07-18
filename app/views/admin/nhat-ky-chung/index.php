<div class="card card-outline card-primary">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-book-open mr-2"></i>{{$page_name}}</h3></div>

    <div class="card-body border-bottom">
        <form method="get" class="form-row align-items-end">
            <div class="form-group col-md-3 mb-2">
                <label class="mb-1 small">Từ ngày</label>
                <input type="date" name="from" class="form-control form-control-sm" value="{{$from}}"/>
            </div>
            <div class="form-group col-md-3 mb-2">
                <label class="mb-1 small">Đến ngày</label>
                <input type="date" name="to" class="form-control form-control-sm" value="{{$to}}"/>
            </div>
            <div class="form-group col-md-2 mb-2">
                <button type="submit" class="btn btn-sm btn-info"><i class="fas fa-search mr-1"></i> Xem</button>
            </div>
        </form>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap mb-0">
            <thead>
                <tr>
                    <th style="width:110px">Ngày</th>
                    <th style="width:130px">Số phiếu</th>
                    <th>Diễn giải</th>
                    <th style="width:16%">Nợ TK</th>
                    <th style="width:16%">Có TK</th>
                    <th style="width:14%" class="text-right">Số tiền</th>
                </tr>
            </thead>
            <tbody>
            @if (!empty($rows))
                @foreach ($rows as $r)
                <tr>
                    <td>{{$r['voucher_date']}}</td>
                    <td><code>{{$r['voucher_no']}}</code></td>
                    <td>{{!empty($r['reason'])?$r['reason']:''}}{{!empty($r['description'])?' — '.$r['description']:''}}</td>
                    <td>{{ isset($accountMap[$r['debit_account_id']]) ? $accountMap[$r['debit_account_id']] : $r['debit_account_id'] }}</td>
                    <td>{{ isset($accountMap[$r['credit_account_id']]) ? $accountMap[$r['credit_account_id']] : $r['credit_account_id'] }}</td>
                    <td class="text-right">{{number_format((float)$r['amount'],0,',','.')}}</td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="6" class="text-center text-muted py-4">Không có bút toán trong kỳ</td></tr>
            @endif
                <tr class="bg-light">
                    <td colspan="5" class="text-right font-weight-bold">Tổng phát sinh</td>
                    <td class="text-right font-weight-bold">{{number_format((float)$total,0,',','.')}}</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="card-footer text-muted small">Chỉ gồm phiếu <b>đã ghi sổ</b> (thu/chi/kế toán quy về Nợ/Có).</div>
</div>
