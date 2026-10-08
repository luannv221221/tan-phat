<?php $pg = phan_trang((array) $dataList); $dataList = $pg['rows']; ?>
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
        <h3 class="card-title"><i class="fas fa-store mr-2"></i>{{$page_name}}</h3>
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
                    <th style="width:60px" class="text-center">STT</th>
                    <th style="width:110px">Mã gara</th>
                    <th>Tên gara</th>
                    <th>Địa chỉ</th>
                    <th style="width:120px">Điện thoại</th>
                    <th>Tên miền</th>
                    <th>Đang có</th>
                    <th style="width:110px" class="text-center">Gara tổng</th>
                    <th style="width:110px" class="text-center">Trạng thái</th>
                    <th style="width:130px" class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            @if (!empty($dataList))
                @foreach ($dataList as $key => $item)
                <tr>
                    <td class="text-center text-muted">{{$key+1}}</td>
                    <td><code>{{$item['code']}}</code></td>
                    <td class="font-weight-bold">{{$item['name']}}</td>
                    <td class="text-muted">{{!empty($item['address'])?$item['address']:'—'}}</td>
                    <td>{{!empty($item['phone'])?$item['phone']:'—'}}</td>
                    <?php /* TÊN MIỀN: host chính trước, số host còn lại gộp thành một
                             nhãn. Gara KHÔNG có tên miền nào thì website của nó không
                             ai vào được — cột trống ở đây là chỗ duy nhất nói ra điều
                             đó, nên tô đỏ chứ không để dấu gạch cho qua. */ ?>
                    <td class="small">
                        <?php
                        $__tm = isset($tenMien[(int) $item['id']]) ? (array) $tenMien[(int) $item['id']] : [];
                        if (empty($__tm)){
                            echo '<span class="text-danger"><i class="fas fa-exclamation-triangle mr-1"></i>chưa khai</span>';
                        } else {
                            $__c = $__tm[0];
                            echo '<code>' . e($__c['host']) . '</code>';
                            if ((int) $__c['status'] !== 1) echo ' <span class="badge badge-secondary">tắt</span>';
                            if (count($__tm) > 1) echo ' <span class="text-muted">+' . (count($__tm) - 1) . '</span>';
                        }
                        ?>
                    </td>
                    <?php /* Cho thấy trước gara nào còn ràng buộc — người bấm Xoá
                             biết mình đang đụng vào cái gì, thay vì bấm xong mới
                             nhận thông báo từ chối. */ ?>
                    <td class="small text-muted">
                        <?php
                        $__d = isset($dangDung[(int) $item['id']]) ? $dangDung[(int) $item['id']] : [];
                        $__t = [];
                        foreach ($__d as $__nhan => $__n) $__t[] = $__n . ' ' . $__nhan;
                        echo $__t ? e(implode(' · ', $__t)) : '<span class="text-black-50">trống</span>';
                        ?>
                    </td>
                    <td class="text-center">
                        {!! $item['is_master']==1 ? '<span class="badge badge-info">Gara tổng</span>' : '' !!}
                    </td>
                    <td class="text-center">
                        {!! $item['status']==1 ? '<span class="badge badge-success">Hoạt động</span>' : '<span class="badge badge-secondary">Ẩn</span>' !!}
                    </td>
                    <td class="text-center">
                        @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/ten-mien/'.$item['id']}}" class="btn btn-info btn-sm" title="Tên miền của gara này"><i class="fas fa-globe"></i></a>
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$item['id']}}" class="btn btn-warning btn-sm" title="Sửa"><i class="fas fa-edit"></i></a>
                        @endif
                        <?php /* Gara đã có dữ liệu thì chỉ KHOÁ được, không xoá: xoá đi là
                                 khách, xe, chứng từ của gara đó không còn ai thấy. Khoá là
                                 nhân viên gara không đăng nhập được nữa, dữ liệu còn nguyên. */ ?>
                        @if ($item['is_master']!=1 && !empty($__d) && route('admin/'.$routeBase.'/edit/'.$item['id']))
                            @if ($item['status']==1)
                            <a onclick="return confirm('Khoá gara {{$item['name']}}? Nhân viên gara sẽ không đăng nhập được nữa, dữ liệu vẫn giữ nguyên.')" href="{{_WEB_URL.'/admin/'.$routeBase.'/toggle/'.$item['id']}}" class="btn btn-secondary btn-sm" title="Khoá gara"><i class="fas fa-lock"></i></a>
                            @else
                            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/toggle/'.$item['id']}}" class="btn btn-success btn-sm" title="Mở khoá gara"><i class="fas fa-lock-open"></i></a>
                            @endif
                        @endif
                        @if (route('admin/'.$routeBase.'/delete/'.$item['id']) && $item['is_master']!=1 && empty($__d))
                        <a onclick="return confirm('Bạn có chắc chắn muốn xoá gara {{$item['name']}}?')" href="{{_WEB_URL.'/admin/'.$routeBase.'/delete/'.$item['id']}}" class="btn btn-danger btn-sm" title="Xoá"><i class="fas fa-trash"></i></a>
                        @endif
                    </td>
                </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="10" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x d-block mb-2"></i> Chưa có gara nào
                    </td>
                </tr>
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
