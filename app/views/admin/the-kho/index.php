<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-clipboard-list mr-2"></i>{{$page_name}}</h3>
    </div>

    <div class="card-body border-bottom">
        <form method="get" class="form-row align-items-end">
            <div class="form-group col-md-4 mb-2">
                <label class="mb-1 small">Phụ tùng <span class="text-danger">*</span></label>
                <select name="part_id" class="form-control form-control-sm">
                    <option value="">— Chọn phụ tùng —</option>
                    @foreach ($parts as $p)
                    <option value="{{$p['id']}}" {{$filterPart==$p['id']?'selected':''}}>{{$p['code'].' - '.$p['name']}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-3 mb-2">
                <label class="mb-1 small">Kho</label>
                <select name="warehouse_id" class="form-control form-control-sm">
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
                <button type="submit" class="btn btn-sm btn-info"><i class="fas fa-search"></i></button>
            </div>
        </form>
    </div>

    @if (empty($part))
    <div class="card-body text-center text-muted py-4">
        <i class="fas fa-hand-pointer fa-2x d-block mb-2"></i> Chọn một phụ tùng để xem thẻ kho
    </div>
    @else
    <div class="card-body py-2 border-bottom">
        <b>{{$part['code']}} - {{$part['name']}}</b>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-sm table-hover text-nowrap mb-0">
            <thead>
                <tr>
                    <th style="width:110px">Ngày</th>
                    <th style="width:130px">Chứng từ</th>
                    <th>Diễn giải</th>
                    <th style="width:12%" class="text-right">Nhập</th>
                    <th style="width:12%" class="text-right">Xuất</th>
                    <th style="width:12%" class="text-right">Tồn</th>
                    <th style="width:15%" class="text-right">Giá trị tồn</th>
                </tr>
            </thead>
            <tbody>
                <tr class="bg-light">
                    <td colspan="5" class="font-italic">Tồn đầu kỳ</td>
                    <td class="text-right font-weight-bold">{{rtrim(rtrim(number_format((float)$opening['qty'],3,',','.'),'0'),',')}}</td>
                    <td class="text-right font-weight-bold">{{number_format((float)$opening['value'],0,',','.')}} ₫</td>
                </tr>
            @if (!empty($cards))
                @foreach ($cards as $c)
                <tr>
                    <td>{{$c['move_date']}}</td>
                    <td><code>{{$c['doc_no']}}</code></td>
                    <td class="text-muted">{{!empty($c['note'])?$c['note']:($c['doc_type']=='receipt'?'Nhập kho':'Xuất kho')}}</td>
                    <td class="text-right text-success">{{(float)$c['qty_in']>0?rtrim(rtrim(number_format((float)$c['qty_in'],3,',','.'),'0'),','):''}}</td>
                    <td class="text-right text-danger">{{(float)$c['qty_out']>0?rtrim(rtrim(number_format((float)$c['qty_out'],3,',','.'),'0'),','):''}}</td>
                    <td class="text-right">{{rtrim(rtrim(number_format((float)$c['balance_qty'],3,',','.'),'0'),',')}}</td>
                    <td class="text-right">{{number_format((float)$c['balance_value'],0,',','.')}} ₫</td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="7" class="text-center text-muted py-3">Không có phát sinh trong kỳ</td></tr>
            @endif
            </tbody>
        </table>
    </div>
    @endif
</div>
