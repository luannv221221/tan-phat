<?php $__km = function($n){ return number_format((int) $n, 0, ',', '.'); }; ?>
@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}</div>
@endif

<div class="row">
    <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-danger"><i class="fas fa-exclamation-triangle"></i></span><div class="info-box-content"><span class="info-box-text">Quá hạn bảo trì</span><span class="info-box-number">{{$cntOverdue}}</span></div></div></div>
    <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-warning"><i class="fas fa-bell"></i></span><div class="info-box-content"><span class="info-box-text">Sắp tới hạn ({{$window}} ngày)</span><span class="info-box-number">{{$cntDue}}</span></div></div></div>
    <div class="col-md-6">
        <div class="card card-outline card-secondary mb-3">
            <div class="card-body py-2">
                <form method="post" action="{{_WEB_URL.'/admin/'.$routeBase.'/save-config'}}" class="form-inline">
                    <?php echo csrf_field(); ?>
                    <span class="small mr-1">Chu kỳ</span>
                    <input type="number" name="interval" min="1" value="{{$interval}}" class="form-control form-control-sm mr-1" style="width:60px"/>
                    <span class="small mr-1">tháng hoặc</span>
                    <input type="number" name="km" min="0" step="500" value="{{$kmCfg}}" class="form-control form-control-sm mr-1" style="width:84px"/>
                    <span class="small mr-1">km · nhắc trước</span>
                    <input type="number" name="window" min="0" value="{{$window}}" class="form-control form-control-sm mr-1" style="width:60px"/>
                    <span class="small mr-2">ngày</span>
                    <button class="btn btn-sm btn-secondary">Lưu</button>
                </form>
                <small class="text-muted">Để 0 km là chỉ nhắc theo tháng.</small>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-oil-can mr-2"></i>{{$page_name}}</h3>
        <div class="card-tools btn-group">
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'?mode=due'}}" class="btn btn-xs {{$mode==='due'?'btn-warning':'btn-default'}}">Cần nhắc</a>
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'?mode=overdue'}}" class="btn btn-xs {{$mode==='overdue'?'btn-danger':'btn-default'}}">Quá hạn</a>
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'?mode=all'}}" class="btn btn-xs {{$mode==='all'?'btn-primary':'btn-default'}}">Tất cả</a>
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover mb-0">
            <thead><tr>
                <th style="width:110px">Lần bảo trì cuối</th>
                <th>Khách hàng</th>
                <th>Xe / thiết bị</th>
                <th style="width:120px">Hoàn tất</th>
                <th style="width:110px">Theo tháng</th>
                <th style="width:190px">Theo km</th>
                <th style="width:150px">Hạn</th>
                <th style="width:80px" class="text-center">Đã nhắc</th>
                <th style="width:150px" class="text-center">Thao tác</th>
            </tr></thead>
            <tbody>
            @if (!empty($rows))
                @foreach ($rows as $r)
                <?php
                    $h     = $r['han'];
                    $kh    = !empty($r['customer_name']) ? $r['customer_name'] : (!empty($r['partner_full']) ? $r['partner_full'] : '—');
                    $phone = !empty($r['phone']) ? $r['phone'] : (!empty($r['partner_phone']) ? $r['partner_phone'] : '');
                    $d     = $r['days_until'];
                ?>
                <tr>
                    <td><code>{{$r['request_no']}}</code></td>
                    <td>{{$kh}}<div class="text-muted small">{{$phone}}</div></td>
                    <td>
                        @if (!empty($r['bien_so']))
                        <span class="font-weight-bold text-uppercase">{{$r['bien_so']}}</span>
                        @endif
                        <div class="text-muted small">{{!empty($r['product_name']) ? $r['product_name'] : ''}}{{!empty($r['serial_no']) ? ' · '.$r['serial_no'] : ''}}</div>
                    </td>
                    <td class="text-muted">
                        {{$r['completed_date']}}
                        @if (!empty($r['so_km']))
                        <div class="small">{{$__km($r['so_km'])}} km</div>
                        @endif
                    </td>
                    <td class="{{$h['ly_do']==='thang' ? 'font-weight-bold' : 'text-muted'}}">{{$h['theo_thang']}}</td>
                    <td class="small">
                        @if ($h['moc_km'] === null)
                            <span class="text-muted">— không ghi số km</span>
                        @else
                            Mốc <b>{{$__km($h['moc_km'])}} km</b>
                            @if (!empty($h['km_gan_nhat']) && $h['km_gan_nhat']['ngay'] !== $r['completed_date'])
                            <div class="text-muted">Ghi gần nhất {{$__km($h['km_gan_nhat']['km'])}} km ({{$h['km_gan_nhat']['ngay']}})</div>
                            @endif
                            @if ($h['theo_km'] !== null)
                            <div class="{{$h['ly_do']==='km' ? 'font-weight-bold' : 'text-muted'}}">≈ {{$h['theo_km']}}{{$h['km_moi_ngay'] !== null ? ' · ~'.$__km(round($h['km_moi_ngay'])).' km/ngày' : ''}}</div>
                            @else
                            <div class="text-muted">Chưa đủ số liệu để ước</div>
                            @endif
                        @endif
                    </td>
                    <td>
                        <b>{{$h['han']}}</b> <span class="text-muted small">(theo {{$h['ly_do']==='km' ? 'km' : 'tháng'}})</span>
                        <div>
                        <?php /* Template không có @elseif — ba nhánh viết PHP thuần */ ?>
                        <?php if (!empty($r['da_hen'])): ?>
                            <span class="badge badge-info">Đã hẹn: <?php echo e($r['da_hen']['request_no']); ?></span>
                        <?php elseif ($r['is_overdue']): ?>
                            <span class="badge badge-danger">Quá hạn <?php echo abs((int) $d); ?> ngày</span>
                        <?php else: ?>
                            <span class="badge badge-<?php echo $r['is_due'] ? 'warning' : 'light'; ?>">Còn <?php echo (int) $d; ?> ngày</span>
                        <?php endif; ?>
                        </div>
                    </td>
                    <td class="text-center">{!! !empty($r['reminded_at']) ? '<span class="badge badge-success" title="'.e($r['reminded_at']).'">Rồi</span>' : '<span class="text-muted">—</span>' !!}</td>
                    <td class="text-center text-nowrap">
                        @if (route('admin/'.$routeBase.'/edit/'.$r['id']))
                            @if (!empty($r['reminded_at']))
                            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/unremind/'.$r['id']}}" class="btn btn-sm btn-outline-secondary" title="Bỏ đánh dấu"><i class="fas fa-undo"></i></a>
                            @else
                            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/mark/'.$r['id']}}" class="btn btn-sm btn-outline-success" title="Đã gọi nhắc">Đã nhắc</a>
                            @endif
                        @endif
                        @if (empty($r['da_hen']) && route('admin/warranty/add'))
                        <a href="{{_WEB_URL.'/admin/warranty/add?loai=bao_tri&tu='.$r['id']}}" class="btn btn-sm btn-info" title="Lập phiếu bảo trì kế tiếp, điền sẵn khách và xe"><i class="fas fa-plus"></i> BT</a>
                        @endif
                        <a href="{{_WEB_URL.'/admin/warranty/edit/'.$r['id']}}" class="btn btn-sm btn-outline-primary" title="Mở phiếu"><i class="fas fa-external-link-alt"></i></a>
                    </td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="9" class="text-center text-muted py-4"><i class="fas fa-check-circle fa-2x d-block mb-2"></i> Không có xe / thiết bị nào trong nhóm này</td></tr>
            @endif
            </tbody>
        </table>
    </div>
</div>
<p class="text-muted small"><i class="fas fa-info-circle mr-1"></i>
    Hạn = mốc nào tới trước: ngày hoàn tất lần bảo trì cuối + {{$interval}} tháng, hoặc ngày xe ước chạm mốc km
    (km lúc bảo trì + {{$__km($kmCfg)}} km). Tốc độ chạy ước từ các lần ghi số km của cùng biển số trên báo giá, hoá đơn, phiếu.
    Xe đã có phiếu bảo trì mới đang mở thì coi như đã hẹn, không cần nhắc. Chỉ phiếu <b>bảo trì</b> sinh lời nhắc — phiếu bảo hành thì không.
</p>
