<?php $pg = phan_trang((array) $dataList); $dataList = $pg['rows']; ?>
@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}</div>
@endif

<?php /* Nói rõ ngay đầu trang module LÀM GÌ. Người mới vào rất dễ tưởng bấm
         "Thêm module" là đẻ ra một màn hình mới. */ ?>
<div class="alert alert-info">
    <i class="fas fa-info-circle mr-1"></i>
    <b>Module là một màn hình quản trị đã được ĐĂNG KÝ để phân quyền.</b>
    Thêm module ở đây <u>không tạo ra màn hình mới</u> — màn hình phải có sẵn trong hệ thống.
    Đăng ký xong thì vào <b>Nhóm &rarr; Phân quyền</b> để cấp quyền Xem / Thêm / Sửa / Xoá cho từng nhóm.
</div>

@if (!empty($moCoi))
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle mr-1"></i>
    <b>{{count($moCoi)}} module đang trỏ tới màn hình không còn tồn tại.</b>
    Quyền cấp cho chúng không gác được gì:
    <?php $tenMoCoi = []; foreach ($moCoi as $m){ $tenMoCoi[] = $m['name'] . ' (' . $m['link'] . ')'; } ?>
    {{implode(', ', $tenMoCoi)}}
</div>
@endif

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-th-large mr-2"></i>{{$page_name}}</h3>
        @if (route('admin/'.$routeBase.'/add'))
        <div class="card-tools">
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/add'}}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> Thêm {{$labelOne}}</a>
        </div>
        @endif
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover mb-0">
            <thead><tr>
                <th style="width:60px" class="text-center">STT</th>
                <th>Tên module</th>
                <th style="width:230px">Màn hình (đường dẫn)</th>
                <th style="width:150px" class="text-center">Đã phân quyền</th>
                <th style="width:130px" class="text-center">Thao tác</th>
            </tr></thead>
            <tbody>
            @if (!empty($dataList))
                @foreach ($dataList as $key => $item)
                <?php
                $soNhom = (int) ($item['so_nhom'] ?? 0);
                $laChinhNo = ($item['link'] === $routeBase);
                ?>
                <tr>
                    <td class="text-center text-muted">{{$pg['from']+$key}}</td>
                    <td class="font-weight-bold">{{$item['name']}}</td>
                    <td><code>/admin/{{$item['link']}}</code></td>
                    <td class="text-center">
                        {!! $soNhom > 0
                            ? '<span class="badge badge-success">'.(int)$soNhom.' nhóm</span>'
                            : '<span class="badge badge-secondary">chưa nhóm nào</span>' !!}
                    </td>
                    <td class="text-center">
                        @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$item['id']}}" class="btn btn-warning btn-sm" title="Sửa"><i class="fas fa-edit"></i></a>
                        @endif
                        <?php /* Không cho gỡ chính màn hình này — gỡ xong là mất đường vào để đăng ký lại. */ ?>
                        @if (!$laChinhNo && route('admin/'.$routeBase.'/delete/'.$item['id']))
                        <a onclick="return confirm('Gỡ module &quot;{{$item['name']}}&quot;?\n\nToàn bộ phân quyền của nó sẽ bị xoá, và màn hình /admin/{{$item['link']}} sẽ KHÔNG còn được kiểm quyền nữa.')"
                           href="{{_WEB_URL.'/admin/'.$routeBase.'/delete/'.$item['id']}}" class="btn btn-danger btn-sm" title="Gỡ đăng ký"><i class="fas fa-trash"></i></a>
                        @endif
                    </td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="5" class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x d-block mb-2"></i> Chưa có module</td></tr>
            @endif
            </tbody>
        </table>
    </div>
<?php if ($pg['total'] > 0): ?>
    <div class="card-footer d-flex justify-content-between align-items-center flex-wrap" style="gap:.5rem">
        {!! phan_trang_html($pg, _WEB_URL.'/admin/'.$routeBase, 'module') !!}
    </div>
<?php endif; ?>
</div>
