<?php $totalQtyShown = 0; ?>
<div class="card card-outline card-success">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-boxes mr-2"></i>{{$page_name}}</h3>
    </div>

    <div class="card-body border-bottom">
        <form method="get" class="form-row align-items-end">
            <div class="form-group col-md-4 mb-2">
                <label class="mb-1 small">Kho</label>
                <select name="warehouse_id" class="form-control form-control-sm">
                    <option value="0">— Tất cả kho —</option>
                    @foreach ($warehouses as $w)
                    <option value="{{$w['id']}}" {{$filterWh==$w['id']?'selected':''}}>{{$w['code'].' - '.$w['name']}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-5 mb-2">
                <label class="mb-1 small">Tìm phụ tùng (tên / mã / OEM)</label>
                <input type="text" name="keyword" class="form-control form-control-sm" value="{{$filterKeyword}}"/>
            </div>
            <div class="form-group col-md-3 mb-2">
                <button type="submit" class="btn btn-sm btn-info"><i class="fas fa-search mr-1"></i> Lọc</button>
                <a href="{{_WEB_URL.'/admin/ton-kho'}}" class="btn btn-sm btn-default">Xoá</a>
            </div>
        </form>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap mb-0">
            <thead>
                <tr>
                    <th style="width:60px" class="text-center">STT</th>
                    <th style="width:130px">Mã</th>
                    <th>Phụ tùng</th>
                    <th>Kho</th>
                    <th style="width:12%" class="text-right">Tồn</th>
                    <th style="width:15%" class="text-right">Đơn giá BQ</th>
                    <th style="width:16%" class="text-right">Giá trị tồn</th>
                </tr>
            </thead>
            <tbody>
            @if (!empty($rows))
                @foreach ($rows as $key => $r)
                <?php $value = (float) $r['quantity'] * (float) $r['avg_cost']; ?>
                <tr>
                    <td class="text-center text-muted">{{$key+1}}</td>
                    <td><code>{{$r['part_code']}}</code></td>
                    <td class="font-weight-bold">{{$r['part_name']}} <span class="text-muted small">{{!empty($r['unit_name'])?'('.$r['unit_name'].')':''}}</span></td>
                    <td class="text-muted">{{$r['warehouse_name']}}</td>
                    <td class="text-right {{(float)$r['quantity']<=0?'text-danger':''}}">{{rtrim(rtrim(number_format((float)$r['quantity'],3,',','.'),'0'),',')}}</td>
                    <td class="text-right">{{number_format((float)$r['avg_cost'],0,',','.')}}</td>
                    <td class="text-right font-weight-bold">{{number_format($value,0,',','.')}} ₫</td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="7" class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x d-block mb-2"></i> Chưa có tồn kho</td></tr>
            @endif
            </tbody>
            @if (!empty($rows))
            <tfoot>
                <tr>
                    <th colspan="6" class="text-right">Tổng giá trị tồn</th>
                    <th class="text-right">{{number_format((float)$totalValue,0,',','.')}} ₫</th>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
