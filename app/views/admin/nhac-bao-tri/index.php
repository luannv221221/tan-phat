@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}</div>
@endif

<div class="row">
    <div class="col-md-4"><div class="info-box"><span class="info-box-icon bg-danger"><i class="fas fa-exclamation-triangle"></i></span><div class="info-box-content"><span class="info-box-text">Quá hạn bảo trì</span><span class="info-box-number">{{$cntOverdue}}</span></div></div></div>
    <div class="col-md-4"><div class="info-box"><span class="info-box-icon bg-warning"><i class="fas fa-bell"></i></span><div class="info-box-content"><span class="info-box-text">Sắp tới hạn ({{$window}} ngày)</span><span class="info-box-number">{{$cntDue}}</span></div></div></div>
    <div class="col-md-4">
        <div class="card card-outline card-secondary mb-0">
            <div class="card-body py-2">
                <form method="post" action="{{_WEB_URL.'/admin/'.$routeBase.'/save-config'}}" class="form-inline">
                    <?php echo csrf_field(); ?>
                    <span class="small mr-1">Chu kỳ</span>
                    <input type="number" name="interval" min="1" value="{{$interval}}" class="form-control form-control-sm mr-1" style="width:64px"/>
                    <span class="small mr-1">tháng · nhắc trước</span>
                    <input type="number" name="window" min="0" value="{{$window}}" class="form-control form-control-sm mr-1" style="width:64px"/>
                    <span class="small mr-2">ngày</span>
                    <button class="btn btn-sm btn-secondary">Lưu</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-calendar-check mr-2"></i>{{$page_name}}</h3>
        <div class="card-tools btn-group">
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'?mode=due'}}" class="btn btn-xs {{$mode==='due'?'btn-warning':'btn-default'}}">Cần nhắc</a>
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'?mode=overdue'}}" class="btn btn-xs {{$mode==='overdue'?'btn-danger':'btn-default'}}">Quá hạn</a>
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'?mode=all'}}" class="btn btn-xs {{$mode==='all'?'btn-primary':'btn-default'}}">Tất cả</a>
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover mb-0">
            <thead><tr>
                <th style="width:110px">Phiếu</th>
                <th>Khách hàng</th>
                <th>Thiết bị</th>
                <th style="width:110px">Hoàn tất</th>
                <th style="width:120px">Bảo trì kế tiếp</th>
                <th style="width:130px" class="text-center">Tình trạng</th>
                <th style="width:100px" class="text-center">Đã nhắc</th>
                <th style="width:120px" class="text-center">Thao tác</th>
            </tr></thead>
            <tbody>
            @if (!empty($rows))
                @foreach ($rows as $r)
                <?php
                    $kh = !empty($r['customer_name']) ? $r['customer_name'] : (!empty($r['partner_full']) ? $r['partner_full'] : '—');
                    $phone = !empty($r['phone']) ? $r['phone'] : (!empty($r['partner_phone']) ? $r['partner_phone'] : '');
                    $tb = !empty($r['product_name']) ? $r['product_name'] : '—';
                    $d = $r['days_until'];
                ?>
                <tr>
                    <td><code>{{$r['request_no']}}</code></td>
                    <td>{{$kh}}<div class="text-muted small">{{$phone}}</div></td>
                    <td class="text-muted">{{$tb}}{{!empty($r['serial_no'])?' · '.$r['serial_no']:''}}</td>
                    <td class="text-muted">{{$r['completed_date']}}</td>
                    <td class="font-weight-bold">{{$r['next_date']}}</td>
                    <td class="text-center">
                        @if ($r['is_overdue'])
                        <span class="badge badge-danger">Quá hạn {{abs($d)}} ngày</span>
                        @else
                        <span class="badge badge-warning">Còn {{$d}} ngày</span>
                        @endif
                    </td>
                    <td class="text-center">{!! !empty($r['reminded_at']) ? '<span class="badge badge-success" title="'.e($r['reminded_at']).'">Rồi</span>' : '<span class="text-muted">—</span>' !!}</td>
                    <td class="text-center">
                        @if (route('admin/'.$routeBase.'/edit/'.$r['id']))
                            @if (!empty($r['reminded_at']))
                            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/unremind/'.$r['id']}}" class="btn btn-sm btn-outline-secondary" title="Bỏ đánh dấu"><i class="fas fa-undo"></i></a>
                            @else
                            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/mark/'.$r['id']}}" class="btn btn-sm btn-outline-success" title="Đã gọi nhắc">Đã nhắc</a>
                            @endif
                        @endif
                        <a href="{{_WEB_URL.'/admin/warranty/edit/'.$r['id']}}" class="btn btn-sm btn-outline-primary" title="Mở phiếu"><i class="fas fa-external-link-alt"></i></a>
                    </td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="8" class="text-center text-muted py-4"><i class="fas fa-check-circle fa-2x d-block mb-2"></i> Không có phiếu nào trong nhóm này</td></tr>
            @endif
            </tbody>
        </table>
    </div>
</div>
<p class="text-muted small"><i class="fas fa-info-circle mr-1"></i> Ngày bảo trì kế tiếp = ngày hoàn tất phiếu bảo hành + {{$interval}} tháng. Đổi chu kỳ ở ô góc trên phải.</p>
