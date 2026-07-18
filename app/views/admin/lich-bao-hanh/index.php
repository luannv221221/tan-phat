<?php $badge = ['received' => 'secondary', 'processing' => 'warning']; ?>
<div class="card card-outline card-info">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-calendar-check mr-2"></i>{{$page_name}}</h3>
        <div class="card-tools text-muted small">Phiếu chưa hoàn tất, sắp theo ngày hẹn trả</div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap mb-0">
            <thead>
                <tr>
                    <th style="width:120px">Số phiếu</th>
                    <th style="width:120px">Hẹn trả</th>
                    <th>Khách hàng</th>
                    <th>Thiết bị / SP</th>
                    <th style="width:130px">KTV</th>
                    <th style="width:120px" class="text-center">Trạng thái</th>
                    <th style="width:70px" class="text-center">Xem</th>
                </tr>
            </thead>
            <tbody>
            @if (!empty($rows))
                @foreach ($rows as $r)
                <?php $overdue = !empty($r['appointment_date']) && $r['appointment_date'] < $today; ?>
                <tr class="{{$overdue?'table-danger':''}}">
                    <td><code>{{$r['request_no']}}</code></td>
                    <td>{!! !empty($r['appointment_date']) ? e($r['appointment_date']).($overdue?' <span class="badge badge-danger">Quá hạn</span>':'') : '<span class="text-muted">—</span>' !!}</td>
                    <td>{{!empty($r['partner_full']) ? $r['partner_full'] : (!empty($r['customer_name']) ? $r['customer_name'] : '—')}}<span class="text-muted small d-block">{{$r['phone']}}</span></td>
                    <td>{{!empty($r['product_name']) ? $r['product_name'] : '—'}}</td>
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
                <tr><td colspan="7" class="text-center text-muted py-4"><i class="fas fa-check-circle fa-2x d-block mb-2"></i> Không có phiếu nào đang chờ xử lý</td></tr>
            @endif
            </tbody>
        </table>
    </div>
</div>
