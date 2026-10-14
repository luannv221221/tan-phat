<?php
/**
 * KẾ TOÁN — Danh mục tài khoản (cây).
 *
 * KHÔNG phân trang: cây mà cắt trang thì tài khoản con rơi sang trang sau,
 * tách khỏi cha, và thụt lề mất hết ý nghĩa. Hệ thống tài khoản của một gara
 * cỡ vài chục dòng, xem một lượt là đúng.
 */
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
        <h3 class="card-title"><i class="fas fa-sitemap mr-2"></i>{{$page_name}}</h3>
        @if (route('admin/'.$routeBase.'/add'))
        <div class="card-tools">
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/add'}}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus mr-1"></i> Thêm {{$labelOne}}
            </a>
        </div>
        @endif
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap mb-0">
            <thead>
                <tr>
                    <th style="width:160px">Mã TK</th>
                    <th>Tên tài khoản</th>
                    <th style="width:90px" class="text-center">Thứ tự</th>
                    <th style="width:120px" class="text-center">Trạng thái</th>
                    <th style="width:130px" class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            @if (!empty($dataList))
                @foreach ($dataList as $item)
                <tr>
                    <td>
                        <span style="padding-left:{{$item['depth']*22}}px">
                            {!! $item['depth']>0
                                ? '<i class="fas fa-level-up-alt fa-rotate-90 text-muted mr-1"></i>'
                                : '<i class="fas fa-folder text-warning mr-1"></i>' !!}
                            <code>{{$item['code']}}</code>
                        </span>
                    </td>
                    <td>
                        {!! $item['depth']==0 ? '<strong>'.e($item['name']).'</strong>' : e($item['name']) !!}
                        {!! nhan_kho_tong($item, 'Dùng chung', 'Tài khoản chuẩn dùng chung — chỉ Tân Phát sửa được') !!}
                    </td>
                    <td class="text-center">{{$item['sort_order']}}</td>
                    <td class="text-center">
                        {!! $item['status']==1
                            ? '<span class="badge badge-success">Đang dùng</span>'
                            : '<span class="badge badge-secondary">Ngừng</span>' !!}
                    </td>
                    <td class="text-center">
                        <?php /* Dòng của hệ thống tài khoản dùng chung: gara chọn được
                                 khi khai loại phiếu, nhưng không sửa. Ẩn nút cho khỏi
                                 bấm vào rồi mới nhận câu từ chối — chốt thật ở Model. */ ?>
                        @if (dong_cua_gara($item))
                        @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$item['id']}}" class="btn btn-warning btn-sm" title="Sửa"><i class="fas fa-edit"></i></a>
                        @endif
                        @if (route('admin/'.$routeBase.'/delete/'.$item['id']))
                        <a onclick="return confirm('Xoá tài khoản {{$item['code']}}?')" href="{{_WEB_URL.'/admin/'.$routeBase.'/delete/'.$item['id']}}" class="btn btn-danger btn-sm" title="Xoá"><i class="fas fa-trash"></i></a>
                        @endif
                        @else
                        <span class="text-muted" title="Tài khoản dùng chung — chỉ Tân Phát sửa được">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="5" class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-2x d-block mb-2"></i> Chưa có tài khoản nào
                </td></tr>
            @endif
            </tbody>
        </table>
    </div>

    <div class="card-footer text-muted small">
        Hệ thống tài khoản chuẩn do Tân Phát dựng sẵn (Thông tư 133, rút gọn) —
        mọi gara đều dùng được. Gara thêm <strong>tài khoản chi tiết của mình</strong>
        thì chỉ gara mình thấy.
    </div>
</div>
