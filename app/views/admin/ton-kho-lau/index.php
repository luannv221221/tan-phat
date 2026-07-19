<div class="card card-outline card-warning">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-hourglass-half mr-2"></i>{{$page_name}}</h3>
    </div>

    <div class="card-body border-bottom">
        <form method="get" class="form-row align-items-end">
            <div class="form-group col-md-3 mb-2">
                <label class="mb-1 small">Kho</label>
                <select name="warehouse_id" class="form-control form-control-sm">
                    <option value="0">— Tất cả kho —</option>
                    @foreach ($warehouses as $w)
                    <option value="{{$w['id']}}" {{$filterWh==$w['id']?'selected':''}}>{{$w['code'].' - '.$w['name']}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-3 mb-2">
                <label class="mb-1 small">Tồn tối thiểu (ngày)</label>
                <input type="number" name="min_days" min="0" class="form-control form-control-sm" value="{{$filterMin}}"/>
            </div>
            <div class="form-group col-md-3 mb-2">
                <label class="mb-1 small">Tính đến ngày</label>
                <input type="date" name="as_of" class="form-control form-control-sm" value="{{$filterAsOf}}"/>
            </div>
            <div class="form-group col-md-3 mb-2">
                <button type="submit" class="btn btn-sm btn-warning"><i class="fas fa-filter mr-1"></i> Lọc</button>
                <a href="{{_WEB_URL.'/admin/ton-kho-lau'}}" class="btn btn-sm btn-default">Xoá</a>
            </div>
        </form>
        <p class="text-muted small mb-0"><i class="fas fa-info-circle mr-1"></i> "Số ngày tồn" = khoảng cách từ lần nhập/xuất gần nhất tới ngày báo cáo. Hàng để càng lâu càng cần đẩy bán / giảm giá.</p>
    </div>
</div>

<div class="row">
    @foreach ($buckets as $b)
    <?php $s = isset($summary[$b[0]]) ? $summary[$b[0]] : ['qty_rows' => 0, 'value' => 0.0]; ?>
    <div class="col-md">
        <div class="info-box">
            <div class="info-box-content">
                <span class="info-box-text text-truncate">{{$b[0]}}</span>
                <span class="info-box-number">{{$s['qty_rows']}} <small class="text-muted">mã</small></span>
                <span class="text-muted small">{{number_format((float)$s['value'],0,',','.')}} ₫</span>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="card">
    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap mb-0">
            <thead>
                <tr>
                    <th style="width:50px" class="text-center">STT</th>
                    <th style="width:120px">Mã</th>
                    <th>Phụ tùng</th>
                    <th>Kho</th>
                    <th style="width:11%" class="text-right">Tồn</th>
                    <th style="width:12%" class="text-center">Phát sinh cuối</th>
                    <th style="width:9%" class="text-right">Số ngày</th>
                    <th style="width:12%" class="text-center">Dải tuổi</th>
                    <th style="width:14%" class="text-right">Giá trị tồn</th>
                </tr>
            </thead>
            <tbody>
            @if (!empty($rows))
                @foreach ($rows as $key => $r)
                <?php
                    $d = $r['days_idle'];
                    $cls = 'text-muted';
                    if ($d !== null){ $cls = $d > 365 ? 'text-danger font-weight-bold' : ($d > 180 ? 'text-danger' : ($d > 90 ? 'text-warning' : 'text-success')); }
                ?>
                <tr>
                    <td class="text-center text-muted">{{$key+1}}</td>
                    <td><code>{{$r['part_code']}}</code></td>
                    <td class="font-weight-bold">{{$r['part_name']}} <span class="text-muted small">{{!empty($r['unit_name'])?'('.$r['unit_name'].')':''}}</span></td>
                    <td class="text-muted">{{$r['warehouse_name']}}</td>
                    <td class="text-right">{{rtrim(rtrim(number_format((float)$r['quantity'],3,',','.'),'0'),',')}}</td>
                    <td class="text-center text-muted">{{!empty($r['last_move_date'])?$r['last_move_date']:'—'}}</td>
                    <td class="text-right {{$cls}}">{{$d===null?'—':$d}}</td>
                    <td class="text-center"><span class="badge badge-light border">{{$r['bucket']}}</span></td>
                    <td class="text-right font-weight-bold">{{number_format((float)$r['value'],0,',','.')}} ₫</td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="9" class="text-center text-muted py-4"><i class="fas fa-check-circle fa-2x d-block mb-2"></i> Không có hàng tồn quá ngưỡng đã lọc</td></tr>
            @endif
            </tbody>
            @if (!empty($rows))
            <tfoot>
                <tr>
                    <th colspan="8" class="text-right">Tổng giá trị hàng tồn lâu</th>
                    <th class="text-right">{{number_format((float)$totalValue,0,',','.')}} ₫</th>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
