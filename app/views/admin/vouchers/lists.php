@if (!empty($msg))
<div class="alert alert-success alert-dismissible">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
    <i class="fas fa-check-circle mr-1"></i> {{$msg}}
</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
    <i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}
</div>
@endif

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-receipt mr-2"></i>{{$page_name}}</h3>
        <div class="card-tools">
            @if (route('admin/'.$routeBase.'/add'))
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/add'}}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus mr-1"></i> Lập phiếu
            </a>
            @endif
        </div>
    </div>

    <div class="card-body border-bottom">
        <form method="get" class="form-row align-items-end">
            <div class="form-group col-md-3 mb-2">
                <label class="mb-1 small">Loại phiếu</label>
                <select name="type" class="form-control form-control-sm">
                    <option value="">— Tất cả —</option>
                    @foreach ($types as $k => $label)
                    <option value="{{$k}}" {{$filterType==$k?'selected':''}}>{{$label}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-3 mb-2">
                <label class="mb-1 small">Từ ngày</label>
                <input type="date" name="from" class="form-control form-control-sm" value="{{$filterFrom}}"/>
            </div>
            <div class="form-group col-md-3 mb-2">
                <label class="mb-1 small">Đến ngày</label>
                <input type="date" name="to" class="form-control form-control-sm" value="{{$filterTo}}"/>
            </div>
            <div class="form-group col-md-3 mb-2">
                <button type="submit" class="btn btn-sm btn-info"><i class="fas fa-search mr-1"></i> Lọc</button>
                <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-sm btn-default">Xoá</a>
            </div>
        </form>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap mb-0">
            <thead>
                <tr>
                    <th style="width:120px">Số phiếu</th>
                    <th style="width:110px">Ngày</th>
                    <th style="width:90px" class="text-center">Loại</th>
                    <th>Đối tượng / Lý do</th>
                    <th style="width:14%" class="text-right">Số tiền</th>
                    <th style="width:12%">TK quỹ</th>
                    <th style="width:110px" class="text-center">Trạng thái</th>
                    <th style="width:90px" class="text-center">Xem</th>
                </tr>
            </thead>
            <tbody>
            @if (!empty($dataList))
                @foreach ($dataList as $item)
                <tr>
                    <td><code>{{$item['voucher_no']}}</code></td>
                    <td>{{$item['voucher_date']}}</td>
                    <td class="text-center">
                        {!! $item['voucher_type']=='thu' ? '<span class="badge badge-success">Thu</span>' : '<span class="badge badge-danger">Chi</span>' !!}
                    </td>
                    <td>
                        {{!empty($item['partner_name']) ? $item['partner_name'] : ''}}
                        {!! !empty($item['reason']) ? '<span class="text-muted">— '.e($item['reason']).'</span>' : '' !!}
                    </td>
                    <td class="text-right font-weight-bold">{{number_format((float)$item['amount'],0,',','.')}} ₫</td>
                    <td><code>{{$item['cash_code']}}</code></td>
                    <td class="text-center">
                        {!! $item['status']==1 ? '<span class="badge badge-primary">Đã ghi sổ</span>' : '<span class="badge badge-secondary">Nháp</span>' !!}
                    </td>
                    <td class="text-center">
                        @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$item['id']}}" class="btn btn-info btn-sm" title="Mở"><i class="fas fa-folder-open"></i></a>
                        @endif
                    </td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="8" class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x d-block mb-2"></i> Chưa có phiếu nào</td></tr>
            @endif
            </tbody>
        </table>
    </div>
</div>
