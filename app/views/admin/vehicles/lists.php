<?php $pg = phan_trang((array) $dataList); $dataList = $pg['rows']; ?>
@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}</div>
@endif

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-car mr-2"></i>{{$page_name}}</h3>
        <div class="card-tools">
            <?php /* "3 / 12 xe" khi đang lọc — nhìn là biết danh sách đang bị thu hẹp. */ ?>
            <span class="text-muted small mr-2">
                @if ($dangLoc)
                    {{$pg['total']}} / {{$tongTatCa}} xe
                @else
                    {{$tongTatCa}} xe
                @endif
            </span>
            @if (route('admin/'.$routeBase.'/add'))
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/add'}}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> Thêm xe</a>
            @endif
        </div>
    </div>

    <div class="card-body border-bottom">
        <form method="get" action="{{_WEB_URL.'/admin/'.$routeBase}}" class="form-row align-items-end">
            <div class="form-group col-md-4 mb-2">
                <label class="mb-1 small">Tìm (BIỂN SỐ / số khung / số máy / chủ xe)</label>
                <input type="text" name="q" class="form-control form-control-sm"
                       placeholder="VD: 30A-123.45, RL4ZE..., Anh Hùng" value="{{$loc['q']}}"/>
            </div>
            <div class="form-group col-md-3 mb-2">
                <label class="mb-1 small">Hãng xe</label>
                <select name="brand_id" class="form-control form-control-sm" onchange="this.form.submit()">
                    <option value="">— Tất cả —</option>
                    @if (!empty($hangDs))
                    @foreach ($hangDs as $h)
                    <option value="{{$h['id']}}" {{(int)$loc['brand_id']===(int)$h['id']?'selected':''}}>{{$h['name']}}</option>
                    @endforeach
                    @endif
                </select>
            </div>
            <div class="form-group col-md-2 mb-2">
                <label class="mb-1 small">Trạng thái</label>
                <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
                    <option value="">— Tất cả —</option>
                    <option value="1" {{$loc['status']==='1'?'selected':''}}>Đang dùng</option>
                    <option value="0" {{$loc['status']==='0'?'selected':''}}>Đã ẩn</option>
                </select>
            </div>
            <div class="form-group col-md-3 mb-2">
                <div class="custom-control custom-checkbox mb-1">
                    <?php /* Xe chưa gán chủ: dựng tự động từ biển số trên chứng từ cũ,
                             hoặc khách vãng lai. Lọc riêng ra để còn biết mà gán chủ. */ ?>
                    <input type="checkbox" class="custom-control-input" name="khong_chu" id="khong_chu" value="1"
                           {{!empty($loc['khong_chu'])?'checked':''}} onchange="this.form.submit()"/>
                    <label class="custom-control-label small" for="khong_chu">Chỉ xe chưa gán chủ</label>
                </div>
                <button type="submit" class="btn btn-sm btn-info"><i class="fas fa-search mr-1"></i> Lọc</button>
                @if ($dangLoc)
                <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-sm btn-default">Xoá lọc</a>
                @endif
            </div>
        </form>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap mb-0">
            <thead>
                <tr>
                    <th style="width:140px">Biển số</th>
                    <th>Xe</th>
                    <th style="width:180px">Số khung / số máy</th>
                    <th>Chủ xe</th>
                    <th style="width:110px" class="text-right">Số km</th>
                    <th style="width:110px" class="text-center">Vào xưởng</th>
                    <th style="width:90px" class="text-center">Trạng thái</th>
                    <th style="width:150px" class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            @if (!empty($dataList))
                @foreach ($dataList as $item)
                <?php
                    $tenXe = VehiclesModel::tenXe($item);
                    $soPhieu = isset($demPhieu[(int) $item['id']]) ? (int) $demPhieu[(int) $item['id']] : 0;
                ?>
                <tr>
                    <td><span class="font-weight-bold text-uppercase">{{$item['bien_so']}}</span></td>
                    <td>{{$tenXe !== '' ? $tenXe : '—'}}<span class="text-muted small d-block">{{!empty($item['mau_xe']) ? $item['mau_xe'] : ''}}</span></td>
                    <td class="small text-muted">
                        {{!empty($item['so_khung']) ? $item['so_khung'] : '—'}}
                        <span class="d-block">{{!empty($item['so_may']) ? $item['so_may'] : ''}}</span>
                    </td>
                    <td>
                        @if (!empty($item['chu_ten']))
                            {{$item['chu_ten']}}<span class="text-muted small d-block">{{$item['chu_sdt']}}</span>
                        @else
                            <span class="text-muted">— chưa gán chủ —</span>
                        @endif
                    </td>
                    <td class="text-right">{!! $item['so_km'] !== null ? number_format($item['so_km'], 0, ',', '.') : '<span class="text-muted">—</span>' !!}</td>
                    <td class="text-center">
                        @if ($soPhieu > 0)
                        <span class="badge badge-info">{{$soPhieu}} lần</span>
                        @else
                        <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-center">{!! $item['status']==1 ? '<span class="badge badge-success">Dùng</span>' : '<span class="badge badge-secondary">Ẩn</span>' !!}</td>
                    <td class="text-center text-nowrap">
                        @if (route('admin/receptions/add'))
                        <a href="{{_WEB_URL.'/admin/receptions/add?vehicle_id='.$item['id']}}" class="btn btn-info btn-sm" title="Tiếp nhận xe này"><i class="fas fa-clipboard-check"></i></a>
                        @endif
                        @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$item['id']}}" class="btn btn-warning btn-sm" title="Sửa / lịch sử xe"><i class="fas fa-edit"></i></a>
                        @endif
                        @if (route('admin/'.$routeBase.'/delete/'.$item['id']) && $soPhieu === 0)
                        <a onclick="return confirm('Xoá xe này?')" href="{{_WEB_URL.'/admin/'.$routeBase.'/delete/'.$item['id']}}" class="btn btn-danger btn-sm" title="Xoá"><i class="fas fa-trash"></i></a>
                        @endif
                    </td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="8" class="text-center text-muted py-4">
                    <i class="fas fa-car fa-2x d-block mb-2"></i>
                    @if ($dangLoc)
                        Không có xe nào khớp bộ lọc.
                        <a href="{{_WEB_URL.'/admin/'.$routeBase}}">Xoá lọc</a> để xem tất cả {{$tongTatCa}} xe.
                    @else
                        Chưa có xe nào. Khai xe ở đây, hoặc mở một đối tượng khách rồi thêm xe cho họ.
                    @endif
                </td></tr>
            @endif
            </tbody>
        </table>
    </div>
<?php if ($pg['total'] > 0): ?>
    <div class="card-footer d-flex justify-content-between align-items-center flex-wrap" style="gap:.5rem">
        {!! phan_trang_html($pg) !!}
    </div>
<?php endif; ?>
</div>
