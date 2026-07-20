<?php
$bal = (float) $opening;
$sumDr = 0; $sumCr = 0;
?>
<div class="card card-outline card-primary">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-book mr-2"></i>{{$page_name}}</h3></div>

    <div class="card-body border-bottom">
        <form method="get" class="form-row align-items-end">
            <div class="form-group col-md-5 mb-2">
                <label class="mb-1 small">Tài khoản</label>
                <select name="account_id" class="form-control form-control-sm">
                    @if (!empty($accounts))
                        @foreach ($accounts as $a)
                        <option value="{{$a['id']}}" {{$accId==$a['id']?'selected':''}}>{{$a['code'].' - '.$a['name']}}</option>
                        @endforeach
                    @else
                        <option value="">(chưa có tài khoản chi tiết)</option>
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
            <div class="form-group col-md-1 mb-2">
                <button type="submit" class="btn btn-sm btn-info"><i class="fas fa-search"></i></button>
            </div>
        </form>
    </div>

    @if (!empty($account))
    <div class="card-body py-2"><b>{{$account['code']}} — {{$account['name']}}</b></div>
    @endif

    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap mb-0">
            <thead>
                <tr>
                    <th style="width:110px">Ngày</th>
                    <th style="width:130px">Số phiếu</th>
                    <th>Diễn giải</th>
                    <th style="width:18%">TK đối ứng</th>
                    <th style="width:13%" class="text-right">Nợ</th>
                    <th style="width:13%" class="text-right">Có</th>
                    <th style="width:14%" class="text-right">Số dư</th>
                </tr>
            </thead>
            <tbody>
                <tr class="bg-light">
                    <td colspan="6" class="font-weight-bold">Số dư đầu kỳ</td>
                    <td class="text-right font-weight-bold">{{number_format((float)$opening,0,',','.')}}</td>
                </tr>
            @if (!empty($rows))
                @foreach ($rows as $r)
                <?php
                $bal += (float) $r['_dr'] - (float) $r['_cr'];
                $sumDr += (float) $r['_dr']; $sumCr += (float) $r['_cr'];
                $doiUng = $r['_dr'] > 0 ? $r['credit_account_id'] : $r['debit_account_id'];
                ?>
                <tr>
                    <td>{{$r['voucher_date']}}</td>
                    <td><code>{{$r['voucher_no']}}</code></td>
                    <td>{{!empty($r['reason'])?$r['reason']:''}}{{!empty($r['description'])?' — '.$r['description']:''}}</td>
                    <td>{{ isset($accountMap[$doiUng]) ? $accountMap[$doiUng] : $doiUng }}</td>
                    <td class="text-right">{!! $r['_dr']>0 ? number_format($r['_dr'],0,',','.') : '' !!}</td>
                    <td class="text-right">{!! $r['_cr']>0 ? number_format($r['_cr'],0,',','.') : '' !!}</td>
                    <td class="text-right">{{number_format($bal,0,',','.')}}</td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="7" class="text-center text-muted py-3">Không có phát sinh trong kỳ</td></tr>
            @endif
                <tr class="bg-light">
                    <td colspan="4" class="text-right font-weight-bold">Cộng phát sinh</td>
                    <td class="text-right font-weight-bold">{{number_format($sumDr,0,',','.')}}</td>
                    <td class="text-right font-weight-bold">{{number_format($sumCr,0,',','.')}}</td>
                    <td class="text-right font-weight-bold">{{number_format($bal,0,',','.')}}</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="card-footer text-muted small">Số dư quy ước bên Nợ (Nợ − Có). TK nguồn vốn/doanh thu số dư sẽ mang dấu âm — đúng bản chất.</div>
</div>
