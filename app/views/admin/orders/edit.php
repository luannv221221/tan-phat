<?php $badge = ['new' => 'warning', 'confirmed' => 'info', 'shipping' => 'primary', 'completed' => 'success', 'cancelled' => 'danger']; ?>
@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
@endif

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-shopping-bag mr-2"></i>Đơn <code>{{$item['order_no']}}</code></h3>
        <div class="card-tools"><span class="badge badge-{{$badge[$item['status']] ?? 'secondary'}} p-2">{{$statuses[$item['status']] ?? $item['status']}}</span></div>
    </div>
    <div class="card-body py-2">
        <span class="mr-2 small text-muted">Chuyển trạng thái:</span>
        @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/set-status/'.$item['id'].'?status=confirmed'}}" class="btn btn-sm btn-outline-info">Xác nhận</a>
        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/set-status/'.$item['id'].'?status=shipping'}}" class="btn btn-sm btn-outline-primary">Đang giao</a>
        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/set-status/'.$item['id'].'?status=completed'}}" class="btn btn-sm btn-outline-success">Hoàn tất</a>
        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/set-status/'.$item['id'].'?status=cancelled'}}" class="btn btn-sm btn-outline-danger">Huỷ</a>
        @endif
    </div>
</div>

<div class="row">
    <div class="col-md-5">
        <div class="card"><div class="hd card-header"><h3 class="card-title">Thông tin khách</h3></div><div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-4">Họ tên</dt><dd class="col-sm-8">{{$item['customer_name']}}</dd>
                <dt class="col-sm-4">Điện thoại</dt><dd class="col-sm-8">{{$item['phone']}}</dd>
                <dt class="col-sm-4">Email</dt><dd class="col-sm-8">{{!empty($item['email'])?$item['email']:'—'}}</dd>
                <dt class="col-sm-4">Địa chỉ</dt><dd class="col-sm-8">{{!empty($item['address'])?$item['address']:'—'}}</dd>
                <dt class="col-sm-4">Thanh toán</dt><dd class="col-sm-8">{{$payments[$item['payment_method']] ?? $item['payment_method']}}</dd>
                <dt class="col-sm-4">Ghi chú</dt><dd class="col-sm-8">{{!empty($item['note'])?$item['note']:'—'}}</dd>
                <dt class="col-sm-4">Ngày đặt</dt><dd class="col-sm-8">{{$item['create_at']}}</dd>
            </dl>
        </div></div>
    </div>
    <div class="col-md-7">
        <div class="card card-outline card-info">
            <div class="card-header"><h3 class="card-title">Sản phẩm</h3></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Sản phẩm</th><th class="text-center">SL</th><th class="text-right">Đơn giá</th><th class="text-right">Thành tiền</th></tr></thead>
                    <tbody>
                    @foreach ($items as $it)
                    <tr>
                        <td><code>{{$it['part_code']}}</code> {{$it['part_name']}}</td>
                        <td class="text-center">{{(int)$it['quantity']}}</td>
                        <td class="text-right">{{number_format((float)$it['unit_price'],0,',','.')}}</td>
                        <td class="text-right">{{number_format((float)$it['amount'],0,',','.')}}</td>
                    </tr>
                    @endforeach
                    </tbody>
                    <tfoot><tr><th colspan="3" class="text-right">Tổng cộng</th><th class="text-right">{{number_format((float)$item['total_amount'],0,',','.')}} ₫</th></tr></tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card"><div class="card-body">
    @if (route('admin/'.$routeBase.'/delete/'.$item['id']))
    <a href="{{_WEB_URL.'/admin/'.$routeBase.'/delete/'.$item['id']}}" onclick="return confirm('Xoá đơn này?')" class="btn btn-outline-danger"><i class="fas fa-trash mr-1"></i> Xoá đơn</a>
    @endif
    <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default">Về danh sách</a>
</div></div>
