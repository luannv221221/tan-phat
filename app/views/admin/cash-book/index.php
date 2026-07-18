<?php
// Tính số dư luỹ kế
$bal = (float) $opening;
$sumThu = 0; $sumChi = 0;
$displayRows = [];
foreach ($rows as $r){
    $thu = $r['voucher_type'] === 'thu' ? (float) $r['amount'] : 0;
    $chi = $r['voucher_type'] === 'chi' ? (float) $r['amount'] : 0;
    $bal += $thu - $chi;
    $sumThu += $thu; $sumChi += $chi;
    $r['_thu'] = $thu; $r['_chi'] = $chi; $r['_bal'] = $bal;
    $displayRows[] = $r;
}
$closing = $bal;
?>
@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>{{$msg}}</div>
@endif

<div class="card card-outline card-primary">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-book mr-2"></i>{{$page_name}}</h3></div>

    <div class="card-body border-bottom">
        <form method="get" class="form-row align-items-end">
            <div class="form-group col-md-4 mb-2">
                <label class="mb-1 small">Quỹ / Tài khoản</label>
                <select name="cash_account_id" class="form-control form-control-sm">
                    @if (!empty($cashAccounts))
                        @foreach ($cashAccounts as $ca)
                        <option value="{{$ca['id']}}" {{$cashId==$ca['id']?'selected':''}}>{{$ca['code'].' - '.$ca['name']}}</option>
                        @endforeach
                    @else
                        <option value="">(chưa có TK quỹ 111/112)</option>
                    @endif
                </select>
            </div>
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
                    <th style="width:120px">Số phiếu</th>
                    <th>Diễn giải</th>
                    <th style="width:14%" class="text-right">Thu</th>
                    <th style="width:14%" class="text-right">Chi</th>
                    <th style="width:16%" class="text-right">Số dư</th>
                </tr>
            </thead>
            <tbody>
                <tr class="bg-light">
                    <td colspan="5" class="font-weight-bold">Số dư đầu kỳ</td>
                    <td class="text-right font-weight-bold">{{number_format((float)$opening,0,',','.')}}</td>
                </tr>
            @if (!empty($displayRows))
                @foreach ($displayRows as $r)
                <tr>
                    <td>{{$r['voucher_date']}}</td>
                    <td><code>{{$r['voucher_no']}}</code></td>
                    <td>{{!empty($r['partner_name'])?$r['partner_name']:''}}{!! !empty($r['reason']) ? ' <span class="text-muted">— '.e($r['reason']).'</span>' : '' !!}</td>
                    <td class="text-right">{!! $r['_thu']>0 ? number_format($r['_thu'],0,',','.') : '' !!}</td>
                    <td class="text-right">{!! $r['_chi']>0 ? number_format($r['_chi'],0,',','.') : '' !!}</td>
                    <td class="text-right">{{number_format($r['_bal'],0,',','.')}}</td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="6" class="text-center text-muted py-3">Không có phát sinh trong kỳ</td></tr>
            @endif
                <tr class="bg-light">
                    <td colspan="3" class="font-weight-bold">Cộng phát sinh</td>
                    <td class="text-right font-weight-bold">{{number_format($sumThu,0,',','.')}}</td>
                    <td class="text-right font-weight-bold">{{number_format($sumChi,0,',','.')}}</td>
                    <td class="text-right font-weight-bold">{{number_format($closing,0,',','.')}}</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="card-footer text-muted small">Chỉ tính các phiếu <b>đã ghi sổ</b>. Số dư cuối kỳ: <b>{{number_format($closing,0,',','.')}} ₫</b></div>
</div>
