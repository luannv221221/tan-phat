<?php
/** DANH MỤC BẢO HIỂM — xem ghi chú đầu Insurers.php. */
$pg = phan_trang((array) $dataList); $rows = $pg['rows'];
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
        <h3 class="card-title"><i class="fas fa-shield-alt mr-2"></i>{{$page_name}}</h3>
        @if (route('admin/'.$routeBase.'/add'))
        <div class="card-tools">
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/add'}}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus mr-1"></i> Thêm {{$labelOne}}
            </a>
        </div>
        @endif
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th style="width:110px">Mã</th>
                    <th>Tên hãng</th>
                    <th style="width:22%">Liên hệ</th>
                    <th style="width:20%">Tài khoản ngân hàng</th>
                    <th style="width:110px" class="text-center">Trạng thái</th>
                    <th style="width:130px" class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            @if (!empty($rows))
                @foreach ($rows as $item)
                <tr>
                    <td><code>{{$item['code']}}</code></td>
                    <td class="font-weight-bold">
                        {{$item['name']}}{!! nhan_kho_tong($item, 'Dùng chung', 'Hãng do Tân Phát khai sẵn — chỉ Tân Phát sửa được') !!}
                        <?php if (!empty($item['website'])): ?>
                        <br><small><a href="<?php echo e($item['website']); ?>" target="_blank" rel="noopener noreferrer"><?php echo e($item['website']); ?></a></small>
                        <?php endif; ?>
                    </td>
                    <td class="small">
                        <?php if (!empty($item['contact_name']) || !empty($item['contact_phone'])): ?>
                        <?php echo e(!empty($item['contact_name']) ? $item['contact_name'] : ''); ?>
                        <?php if (!empty($item['contact_phone'])): ?>
                        <br><?php echo e($item['contact_phone']); ?>
                        <?php endif; ?>
                        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                    </td>
                    <td class="small">
                        <?php echo !empty($item['bank_account'])
                                 ? e($item['bank_account'])
                                 : '<span class="text-muted">—</span>'; ?>
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
                        <a onclick="return confirm('Xoá {{$item['name']}}?')" href="{{_WEB_URL.'/admin/'.$routeBase.'/delete/'.$item['id']}}" class="btn btn-danger btn-sm" title="Xoá"><i class="fas fa-trash"></i></a>
                        @endif
                        @else
                        <span class="text-muted" title="Hãng dùng chung — chỉ Tân Phát sửa được">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="6" class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-2x d-block mb-2"></i> Chưa có hãng bảo hiểm nào
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
    <div class="card-footer text-muted small">
        Bản dùng chung chỉ có mã và tên. Cần khai <strong>số tài khoản ngân hàng</strong> hay
        <strong>người liên hệ</strong> của riêng gara mình thì thêm một dòng mới —
        thông tin đó khác nhau theo từng gara.
    </div>
</div>
