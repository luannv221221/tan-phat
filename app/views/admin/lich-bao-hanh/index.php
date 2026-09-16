<?php
$badge = ['received' => 'secondary', 'processing' => 'warning'];
/* Link giữ bộ lọc đang chọn, chỉ đổi đúng một tham số */
$__link = function($them) use ($loai, $khoang){
    $p = array_filter(array_merge(['loai' => $loai, 'khoang' => $khoang], $them), function($v){ return $v !== ''; });
    return _WEB_URL . '/admin/lich-bao-hanh' . (!empty($p) ? '?' . http_build_query($p) : '');
};
$__themDuoc = route('admin/warranty/add');
?>
@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
@endif

<div class="card card-outline card-info">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-calendar-check mr-2"></i>{{$page_name}}</h3>
        <div class="card-tools">
            @if ($__themDuoc)
            <a href="{{_WEB_URL.'/admin/warranty/add?loai=bao_hanh'}}" class="btn btn-primary btn-sm"><i class="fas fa-tools mr-1"></i> Lập phiếu bảo hành</a>
            <a href="{{_WEB_URL.'/admin/warranty/add?loai=bao_tri'}}" class="btn btn-info btn-sm"><i class="fas fa-oil-can mr-1"></i> Lập phiếu bảo trì</a>
            @endif
        </div>
    </div>

    <div class="card-body border-bottom py-2">
        <div class="d-flex flex-wrap align-items-center" style="gap:.5rem 1rem">
            <div class="btn-group btn-group-sm" role="group" aria-label="Lọc theo loại">
                <a href="{{$__link(['loai' => ''])}}" class="btn {{$loai==='' ? 'btn-secondary' : 'btn-default'}}">Cả hai loại</a>
                @foreach ($loais as $k => $ten)
                <a href="{{$__link(['loai' => $k])}}" class="btn {{$loai===$k ? 'btn-secondary' : 'btn-default'}}">{{$ten}} <span class="badge badge-light">{{$demLoai[$k] ?? 0}}</span></a>
                @endforeach
            </div>
            <div class="btn-group btn-group-sm" role="group" aria-label="Lọc theo ngày hẹn">
                @foreach ($khoangs as $k => $ten)
                <a href="{{$__link(['khoang' => $k])}}" class="btn {{$khoang===$k ? 'btn-info' : 'btn-default'}}">{{$ten}}</a>
                @endforeach
            </div>
            <span class="text-muted small ml-auto">Phiếu chưa hoàn tất, xếp theo ngày hẹn; phiếu chưa hẹn ngày ở cuối.</span>
        </div>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap mb-0">
            <thead>
                <tr>
                    <th style="width:130px">Số phiếu</th>
                    <th style="width:130px">Hẹn</th>
                    <th>Khách hàng</th>
                    <th>Xe / thiết bị</th>
                    <th style="width:130px">KTV</th>
                    <th style="width:120px" class="text-center">Trạng thái</th>
                    <th style="width:70px" class="text-center">Xem</th>
                </tr>
            </thead>
            <tbody>
            @if (!empty($rows))
                @foreach ($rows as $r)
                <?php
                    $overdue = !empty($r['appointment_date']) && $r['appointment_date'] < $today;
                    $homNay  = !empty($r['appointment_date']) && $r['appointment_date'] === $today;
                    $__bt    = (($r['loai'] ?? '') === 'bao_tri');
                ?>
                <tr class="{{$overdue ? 'table-danger' : ($homNay ? 'table-warning' : '')}}">
                    <td>
                        <code>{{$r['request_no']}}</code>
                        <span class="d-block"><span class="badge badge-{{$__bt ? 'info' : 'primary'}}">{{$__bt ? 'Bảo trì' : 'Bảo hành'}}</span></span>
                    </td>
                    <td>
                        @if (!empty($r['appointment_date']))
                            {{$r['appointment_date']}}
                            @if ($overdue)
                            <span class="badge badge-danger">Quá hạn</span>
                            @endif
                            @if ($homNay)
                            <span class="badge badge-warning">Hôm nay</span>
                            @endif
                        @else
                            <span class="text-muted">Chưa hẹn</span>
                        @endif
                    </td>
                    <td>{{!empty($r['partner_full']) ? $r['partner_full'] : (!empty($r['customer_name']) ? $r['customer_name'] : '—')}}<span class="text-muted small d-block">{{$r['phone']}}</span></td>
                    <td>
                        @if (!empty($r['bien_so']))
                        <span class="font-weight-bold text-uppercase">{{$r['bien_so']}}</span>
                        @endif
                        <span class="{{!empty($r['bien_so']) ? 'text-muted small d-block' : ''}}">{{!empty($r['product_name']) ? $r['product_name'] : (empty($r['bien_so']) ? '—' : '')}}</span>
                    </td>
                    <td>{{!empty($r['technician']) ? $r['technician'] : '—'}}</td>
                    <td class="text-center"><span class="badge badge-{{$badge[$r['status']] ?? 'secondary'}}">{{$statuses[$r['status']] ?? $r['status']}}</span></td>
                    <td class="text-center">
                        @if (route('admin/warranty/edit/'.$r['id']))
                        <a href="{{_WEB_URL.'/admin/warranty/edit/'.$r['id']}}" class="btn btn-info btn-sm"><i class="fas fa-folder-open"></i></a>
                        @endif
                    </td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="7" class="text-center text-muted py-4">
                    <i class="fas fa-check-circle fa-2x d-block mb-2"></i>
                    @if ($dangLoc)
                        Không có phiếu nào khớp bộ lọc. <a href="{{_WEB_URL.'/admin/lich-bao-hanh'}}">Xem tất cả</a>
                    @else
                        Không có phiếu nào đang chờ xử lý
                    @endif
                </td></tr>
            @endif
            </tbody>
        </table>
    </div>
</div>
