@if (!empty($msg))
<div class="alert alert-success alert-dismissible">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
    {{$msg}}
</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
    {{$msgError}}
</div>
@endif

<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{$page_name}}</h3>
        <div class="card-tools">
            <span class="text-muted small mr-2">
                @if ($dangLoc)
                Khớp {{(int)$total}} / {{(int)$tongTatCa}} khách
                @else
                {{(int)$tongTatCa}} khách hàng
                @endif
            </span>
            @if (route('admin/'.$routeBase.'/add'))
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/add'}}" class="btn btn-primary btn-sm">
                <i class="fas fa-user-plus mr-1"></i> Thêm khách hàng
            </a>
            @endif
        </div>
    </div>

    <div class="card-body">
        <form method="get" action="{{_WEB_URL.'/admin/'.$routeBase}}" class="adm-searchbar">
            <input type="text" name="q" class="form-control" placeholder="Tìm theo tên, mã, SĐT, email, BIỂN SỐ hoặc SỐ KHUNG (VIN)" value="{{$loc['q']}}"/>
            <select name="group" class="form-control" style="max-width:200px" onchange="this.form.submit()">
                <option value="">Mọi nhóm khách</option>
                @if (!empty($dsNhom))
                @foreach ($dsNhom as $g)
                <option value="{{$g['id']}}" {{(string)$loc['group']===(string)$g['id']?'selected':''}}>{{$g['name']}}</option>
                @endforeach
                @endif
            </select>
            <select name="status" class="form-control" style="max-width:170px" onchange="this.form.submit()">
                <option value="">Mọi trạng thái</option>
                <option value="1" {{$loc['status']==='1'?'selected':''}}>Đang giao dịch</option>
                <option value="0" {{$loc['status']==='0'?'selected':''}}>Đã tắt</option>
            </select>
            <button type="submit" class="btn btn-primary">Tìm</button>
            @if ($dangLoc)
            <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default" title="Bỏ lọc">&times;</a>
            @endif
        </form>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap mb-0">
            <thead>
                <tr>
                    <th style="width:60px" class="text-center">STT</th>
                    <th style="width:90px">Mã</th>
                    <th>Họ tên</th>
                    <th style="width:12%">Điện thoại</th>
                    <th style="width:24%">Xe</th>
                    <th style="width:12%">Nhóm</th>
                    <th style="width:110px" class="text-center">Trạng thái</th>
                    <th style="width:120px" class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            @if (!empty($dataList))
                @foreach ($dataList as $key => $item)
                <?php $xe = isset($xeTheoKhach[(int) $item['id']]) ? $xeTheoKhach[(int) $item['id']] : []; ?>
                <tr>
                    <td class="text-center text-muted">{{($page-1)*$perPage + $key + 1}}</td>
                    <td class="text-muted small">{{$item['code']}}</td>
                    <td class="font-weight-bold">{{$item['name']}}</td>
                    <td>{!! !empty($item['phone']) ? e($item['phone']) : '<span class="text-muted">—</span>' !!}</td>
                    <td>
                        @if (!empty($xe))
                            @foreach ($xe as $x)
                            <span class="badge badge-light border mr-1" style="font-size:.8rem"
                                  title="{{!empty($x['so_khung']) ? 'VIN '.$x['so_khung'] : ''}}">{{$x['bien_so']}}</span>
                            @endforeach
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>{!! !empty($item['nhom_ten']) ? e($item['nhom_ten']) : '<span class="text-muted">—</span>' !!}</td>
                    <td class="text-center">
                        {!! (int)$item['status'] === 1
                            ? '<span class="badge badge-success">Đang giao dịch</span>'
                            : '<span class="badge badge-secondary">Đã tắt</span>' !!}
                    </td>
                    <td class="text-center">
                        @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$item['id']}}" class="btn btn-warning btn-sm" title="Hồ sơ + xe của khách"><i class="fas fa-edit"></i></a>
                        @endif
                        @if (route('admin/vehicles/add'))
                        <a href="{{_WEB_URL.'/admin/vehicles/add?ve=customer&partner_id='.$item['id']}}" class="btn btn-info btn-sm" title="Thêm xe cho khách này"><i class="fas fa-car"></i></a>
                        @endif
                    </td>
                </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                        {{$dangLoc ? 'Không có khách nào khớp bộ lọc' : 'Chưa có khách hàng nào'}}
                    </td>
                </tr>
            @endif
            </tbody>
        </table>
    </div>

<?php
/* Chân bảng dùng chung phan_trang_html() — giữ NGUYÊN mọi tham số lọc đang có trên URL. */
$from = $total > 0 ? ($perPage > 0 ? ($page - 1) * $perPage + 1 : 1) : 0;
$to   = $perPage > 0 ? min($page * $perPage, $total) : $total;
if ($total > 0): ?>
    <div class="card-footer d-flex justify-content-between align-items-center flex-wrap" style="gap:.5rem">
        {!! phan_trang_html(['total'=>$total,'perPage'=>$perPage,'page'=>$page,'totalPages'=>$totalPages,'from'=>$from,'to'=>$to], _WEB_URL.'/admin/'.$routeBase, 'khách hàng') !!}
    </div>
<?php endif; ?>
</div>
