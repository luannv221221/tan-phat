@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>{{$msg}}</div>
@endif

<div class="card card-outline card-primary">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-hand-holding-usd mr-2"></i>{{$page_name}}</h3></div>

    <div class="card-body border-bottom">
        <form method="get" class="form-row align-items-end">
            <div class="form-group col-md-4 mb-2">
                <label class="mb-1 small">Đối tượng</label>
                <select name="partner_id" class="form-control form-control-sm">
                    <option value="">— Tổng hợp tất cả —</option>
                    @if (!empty($partners))
                        @foreach ($partners as $p)
                        <option value="{{$p['id']}}" {{$partnerId==$p['id']?'selected':''}}>{{$p['code'].' - '.$p['name']}}</option>
                        @endforeach
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

@if ($mode == 'summary')
    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap mb-0">
            <thead><tr>
                <th style="width:60px" class="text-center">STT</th>
                <th style="width:16%">Mã</th>
                <th>Đối tượng</th>
                <th style="width:18%" class="text-right">Phải thu</th>
                <th style="width:18%" class="text-right">Phải trả</th>
            </tr></thead>
            <tbody>
            @if (!empty($summary))
                @foreach ($summary as $key => $s)
                <tr>
                    <td class="text-center text-muted">{{$key+1}}</td>
                    <td><code>{{$s['partner']['code']}}</code></td>
                    <td class="font-weight-bold">{{$s['partner']['name']}}</td>
                    <td class="text-right text-success">{!! $s['net']>0 ? number_format($s['net'],0,',','.') : '' !!}</td>
                    <td class="text-right text-danger">{!! $s['net']<0 ? number_format(-$s['net'],0,',','.') : '' !!}</td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="5" class="text-center text-muted py-4">Không có công nợ</td></tr>
            @endif
            </tbody>
        </table>
    </div>
    <div class="card-footer text-muted small">Dương = phải thu (khách nợ mình) · Âm = phải trả (mình nợ NCC). Chỉ tính TK 131/331 trên phiếu đã ghi sổ.</div>
@else
    <?php $bal = (float) $opening; ?>
    <div class="card-body">
        <h5 class="mb-1">{{!empty($partner)?$partner['name']:''}} <small class="text-muted">{{!empty($partner)?'('.$partner['code'].')':''}}</small></h5>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap mb-0">
            <thead><tr>
                <th style="width:110px">Ngày</th>
                <th style="width:130px">Số phiếu</th>
                <th>Diễn giải</th>
                <th style="width:16%" class="text-right">Phát sinh</th>
                <th style="width:16%" class="text-right">Số dư</th>
            </tr></thead>
            <tbody>
                <tr class="bg-light">
                    <td colspan="4" class="font-weight-bold">Số dư đầu kỳ</td>
                    <td class="text-right font-weight-bold">{{number_format((float)$opening,0,',','.')}}</td>
                </tr>
            @if (!empty($rows))
                @foreach ($rows as $r)
                <?php $bal += (float) $r['_delta']; ?>
                <tr>
                    <td>{{$r['voucher_date']}}</td>
                    <td><code>{{$r['voucher_no']}}</code></td>
                    <td>{{!empty($r['reason'])?$r['reason']:''}}{{!empty($r['description'])?' — '.$r['description']:''}}</td>
                    <td class="text-right">{!! $r['_delta']>=0 ? '+'.number_format($r['_delta'],0,',','.') : number_format($r['_delta'],0,',','.') !!}</td>
                    <td class="text-right">{{number_format($bal,0,',','.')}}</td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="5" class="text-center text-muted py-3">Không có phát sinh công nợ trong kỳ</td></tr>
            @endif
                <tr class="bg-light">
                    <td colspan="4" class="font-weight-bold">Số dư cuối kỳ</td>
                    <td class="text-right font-weight-bold">{{number_format($bal,0,',','.')}}</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="card-footer text-muted small">Số dư dương = đối tượng còn nợ mình · Âm = mình còn nợ đối tượng.</div>
@endif
</div>
