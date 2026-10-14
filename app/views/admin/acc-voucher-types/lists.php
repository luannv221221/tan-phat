<?php
/**
 * KẾ TOÁN — Danh sách loại phiếu.
 *
 * Cột Định khoản hiện thẳng cặp "Nợ … / Có …" thay vì hai cột rời: người kế
 * toán đọc định khoản theo cặp, tách ra là phải ghép lại bằng mắt.
 */
$pg = phan_trang((array) $dataList); $dataList = $pg['rows'];
?>
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
        <h3 class="card-title"><i class="fas fa-tags mr-2"></i>{{$page_name}}</h3>
        @if (route('admin/'.$routeBase.'/add'))
        <div class="card-tools">
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/add'}}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus mr-1"></i> Thêm {{$labelOne}}
            </a>
        </div>
        @endif
    </div>

    <div class="card-body border-bottom py-2">
        <form method="get" class="form-inline">
            <label class="mr-2 text-muted">Lọc theo loại:</label>
            <select name="loai" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                <option value="">— Tất cả —</option>
                @foreach ($dsLoai as $k => $nhan)
                <option value="{{$k}}" {{$loaiDang==$k?'selected':''}}>{{$nhan}}</option>
                @endforeach
            </select>
            <noscript><button type="submit" class="btn btn-sm btn-default">Lọc</button></noscript>
        </form>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap mb-0">
            <thead>
                <tr>
                    <th style="width:110px">Mã phiếu</th>
                    <th>Tên phiếu</th>
                    <th style="width:110px" class="text-center">Loại</th>
                    <th style="width:28%">Định khoản</th>
                    <th style="width:120px" class="text-center">Trạng thái</th>
                    <th style="width:130px" class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            @if (!empty($dataList))
                @foreach ($dataList as $item)
                <tr>
                    <td><code>{{$item['code']}}</code></td>
                    <td class="font-weight-bold">{{$item['name']}}{!! nhan_kho_tong($item, 'Dùng chung', 'Loại phiếu dùng chung — chỉ Tân Phát sửa được') !!}</td>
                    <td class="text-center">
                        {!! $item['loai']=='thu'
                            ? '<span class="badge badge-success">Thu</span>'
                            : '<span class="badge badge-danger">Chi</span>' !!}
                    </td>
                    <td class="small">
                        <?php /* Thiếu một vế thì phiếu lập ra mang định khoản dở dang —
                                 nói ra ngay ở danh sách, đừng để tới lúc đối chiếu sổ. */ ?>
                        @if (!empty($item['no_code']) && !empty($item['co_code']))
                        Nợ <code>{{$item['no_code']}}</code> / Có <code>{{$item['co_code']}}</code>
                        @else
                        <span class="text-danger"><i class="fas fa-exclamation-triangle mr-1"></i>thiếu tài khoản</span>
                        @endif
                    </td>
                    <td class="text-center">
                        {!! $item['status']==1
                            ? '<span class="badge badge-success">Đang dùng</span>'
                            : '<span class="badge badge-secondary">Ngừng</span>' !!}
                    </td>
                    <td class="text-center">
                        @if (dong_cua_gara($item))
                        @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$item['id']}}" class="btn btn-warning btn-sm" title="Sửa"><i class="fas fa-edit"></i></a>
                        @endif
                        @if (route('admin/'.$routeBase.'/delete/'.$item['id']))
                        <a onclick="return confirm('Xoá loại phiếu {{$item['code']}}?')" href="{{_WEB_URL.'/admin/'.$routeBase.'/delete/'.$item['id']}}" class="btn btn-danger btn-sm" title="Xoá"><i class="fas fa-trash"></i></a>
                        @endif
                        @else
                        <span class="text-muted" title="Loại phiếu dùng chung — chỉ Tân Phát sửa được">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="6" class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-2x d-block mb-2"></i> Chưa có loại phiếu nào
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
