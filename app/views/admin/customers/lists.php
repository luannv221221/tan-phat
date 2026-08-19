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
        <div class="card-tools text-muted small">{{(int)$total}} khách hàng</div>
    </div>

    <div class="card-body">
        <form method="get" action="{{_WEB_URL.'/admin/'.$routeBase}}" class="adm-searchbar">
            <input type="text" name="keyword" class="form-control" placeholder="Tìm theo tên, email hoặc số điện thoại" value="{{$keyword}}"/>
            <select name="status" class="form-control" style="max-width:180px" onchange="this.form.submit()">
                <option value="">Mọi trạng thái</option>
                <option value="1" {{$filterSt==='1'?'selected':''}}>Đang hoạt động</option>
                <option value="0" {{$filterSt==='0'?'selected':''}}>Đã khoá</option>
            </select>
            <button type="submit" class="btn btn-primary">Tìm</button>
        </form>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap mb-0">
            <thead>
                <tr>
                    <th style="width:60px" class="text-center">STT</th>
                    <th>Họ tên</th>
                    <th style="width:22%">Email</th>
                    <th style="width:14%">Điện thoại</th>
                    <th style="width:90px" class="text-center">Số đơn</th>
                    <th style="width:110px" class="text-center">Trạng thái</th>
                    <th style="width:160px">Ngày đăng ký</th>
                    <th style="width:150px" class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            @if (!empty($dataList))
                @foreach ($dataList as $key => $item)
                <tr>
                    <td class="text-center text-muted">{{($page-1)*$perPage + $key + 1}}</td>
                    <td class="font-weight-bold">{{$item['name']}}</td>
                    <td>{{$item['email']}}</td>
                    <td>{!! !empty($item['phone']) ? e($item['phone']) : '<span class="text-muted">—</span>' !!}</td>
                    <td class="text-center">{{(int)$item['order_count']}}</td>
                    <td class="text-center">
                        {!! (int)$item['status'] === 1
                            ? '<span class="badge badge-success">Hoạt động</span>'
                            : '<span class="badge badge-secondary">Đã khoá</span>' !!}
                    </td>
                    <td class="text-muted small">{{$item['create_at']}}</td>
                    <td class="text-center">
                        @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$item['id']}}" class="btn btn-warning btn-sm" title="Sửa"><i class="fas fa-edit"></i></a>
                        @endif
                        @if (route('admin/'.$routeBase.'/toggle/'.$item['id']))
                            @if ((int)$item['status'] === 1)
                            <a onclick="return confirm('Khoá tài khoản này? Khách sẽ không đăng nhập được nữa.')"
                               href="{{_WEB_URL.'/admin/'.$routeBase.'/toggle/'.$item['id']}}"
                               class="btn btn-danger btn-sm" title="Khoá"><i class="fas fa-lock"></i></a>
                            @else
                            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/toggle/'.$item['id']}}"
                               class="btn btn-success btn-sm" title="Mở khoá"><i class="fas fa-lock-open"></i></a>
                            @endif
                        @endif
                    </td>
                </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x d-block mb-2"></i> Chưa có khách hàng nào
                    </td>
                </tr>
            @endif
            </tbody>
        </table>
    </div>

<?php
/* Chân bảng dùng chung phan_trang_html() như mọi danh sách khác. Bản cũ tự vẽ
   một dãy nút số, và tự ghép ?keyword=&status= — thêm bộ lọc mới là quên sửa;
   hàm dùng chung giữ NGUYÊN mọi tham số đang có trên URL. */
$from = $total > 0 ? ($perPage > 0 ? ($page - 1) * $perPage + 1 : 1) : 0;
$to   = $perPage > 0 ? min($page * $perPage, $total) : $total;
if ($total > 0): ?>
    <div class="card-footer d-flex justify-content-between align-items-center flex-wrap" style="gap:.5rem">
        {!! phan_trang_html(['total'=>$total,'perPage'=>$perPage,'page'=>$page,'totalPages'=>$totalPages,'from'=>$from,'to'=>$to], _WEB_URL.'/admin/'.$routeBase, 'khách hàng') !!}
    </div>
<?php endif; ?>
</div>
