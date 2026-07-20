<div class="card card-outline card-danger">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-chart-line mr-2"></i>{{$page_name}}</h3>
    </div>
    <div class="card-body border-bottom">
        <form method="get" class="form-row align-items-end">
            <div class="form-group col-md-4 mb-2">
                <label class="mb-1 small">Phụ tùng <span class="text-danger">*</span></label>
                <select name="part_id" class="form-control form-control-sm">
                    <option value="0">— Chọn phụ tùng —</option>
                    @foreach ($parts as $p)
                    <option value="{{$p['id']}}" {{$filterPart==$p['id']?'selected':''}}>{{$p['code'].' - '.$p['name']}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-3 mb-2">
                <label class="mb-1 small">Kho</label>
                <select name="warehouse_id" class="form-control form-control-sm">
                    <option value="0">— Tất cả kho —</option>
                    @foreach ($warehouses as $w)
                    <option value="{{$w['id']}}" {{$filterWh==$w['id']?'selected':''}}>{{$w['code'].' - '.$w['name']}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-2 mb-2">
                <label class="mb-1 small">Từ ngày</label>
                <input type="date" name="from" class="form-control form-control-sm" value="{{$filterFrom}}"/>
            </div>
            <div class="form-group col-md-2 mb-2">
                <label class="mb-1 small">Đến ngày</label>
                <input type="date" name="to" class="form-control form-control-sm" value="{{$filterTo}}"/>
            </div>
            <div class="form-group col-md-1 mb-2">
                <button type="submit" class="btn btn-sm btn-danger btn-block"><i class="fas fa-search"></i></button>
            </div>
        </form>
    </div>
</div>

@if (empty($filterPart))
<div class="callout callout-info">Chọn 1 phụ tùng để xem biểu đồ biến động nhập / xuất / tồn theo ngày.</div>
@else
    @if (empty($partRow))
    <div class="callout callout-warning">Không tìm thấy phụ tùng.</div>
    @else
    <div class="row">
        <div class="col-md-3"><div class="info-box"><div class="info-box-content"><span class="info-box-text">Tồn đầu kỳ</span><span class="info-box-number">{{rtrim(rtrim(number_format((float)$opening,3,',','.'),'0'),',')}}</span></div></div></div>
        <div class="col-md-3"><div class="info-box"><div class="info-box-content"><span class="info-box-text text-success">Tổng nhập</span><span class="info-box-number text-success">{{rtrim(rtrim(number_format((float)$sumIn,3,',','.'),'0'),',')}}</span></div></div></div>
        <div class="col-md-3"><div class="info-box"><div class="info-box-content"><span class="info-box-text text-danger">Tổng xuất</span><span class="info-box-number text-danger">{{rtrim(rtrim(number_format((float)$sumOut,3,',','.'),'0'),',')}}</span></div></div></div>
        <div class="col-md-3"><div class="info-box"><div class="info-box-content"><span class="info-box-text">Tồn cuối kỳ</span><span class="info-box-number">{{rtrim(rtrim(number_format((float)($opening+$sumIn-$sumOut),3,',','.'),'0'),',')}}</span></div></div></div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-box mr-2"></i><code>{{$partRow['code']}}</code> — {{$partRow['name']}}</h3></div>
        <div class="card-body">
            @if (empty($rows))
            <p class="text-muted text-center py-4"><i class="fas fa-chart-area fa-2x d-block mb-2"></i> Không có phát sinh trong khoảng đã chọn.</p>
            @else
            <div style="overflow-x:auto">{!! $chartSvg !!}</div>
            @endif
        </div>
    </div>

    @if (!empty($rows))
    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-sm table-hover mb-0">
                <thead><tr>
                    <th>Ngày</th>
                    <th class="text-right text-success">Nhập</th>
                    <th class="text-right text-danger">Xuất</th>
                    <th class="text-right">Tồn cuối ngày</th>
                </tr></thead>
                <tbody>
                @foreach ($rows as $r)
                <tr>
                    <td>{{$r['date']}}</td>
                    <td class="text-right text-success">{{(float)$r['in']>0?rtrim(rtrim(number_format((float)$r['in'],3,',','.'),'0'),','):'—'}}</td>
                    <td class="text-right text-danger">{{(float)$r['out']>0?rtrim(rtrim(number_format((float)$r['out'],3,',','.'),'0'),','):'—'}}</td>
                    <td class="text-right font-weight-bold">{{rtrim(rtrim(number_format((float)$r['balance'],3,',','.'),'0'),',')}}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
    @endif
@endif
