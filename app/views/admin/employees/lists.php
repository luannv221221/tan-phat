@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}</div>
@endif

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-users mr-2"></i>{{$page_name}}</h3>
        <div class="card-tools">
            @if (route('admin/'.$routeBase.'/add'))
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/add'}}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> Thêm nhân viên</a>
            @endif
        </div>
    </div>
    <div class="card-body border-bottom">
        <form method="get" class="form-row align-items-end">
            <div class="form-group col-md-3 mb-2">
                <label class="mb-1 small">Phòng ban</label>
                <select name="dept" class="form-control form-control-sm">
                    <option value="0">— Tất cả —</option>
                    @foreach ($departments as $d)
                    <option value="{{$d['id']}}" {{$filterDept==$d['id']?'selected':''}}>{{$d['name']}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-2 mb-2">
                <label class="mb-1 small">Trạng thái</label>
                <select name="status" class="form-control form-control-sm">
                    <option value="">— Tất cả —</option>
                    <option value="1" {{$filterStatus==='1'?'selected':''}}>Đang làm</option>
                    <option value="0" {{$filterStatus==='0'?'selected':''}}>Đã nghỉ</option>
                </select>
            </div>
            <div class="form-group col-md-4 mb-2"><label class="mb-1 small">Tìm (tên/mã/SĐT)</label><input type="text" name="q" class="form-control form-control-sm" value="{{$filterKeyword}}"/></div>
            <div class="form-group col-md-3 mb-2"><button type="submit" class="btn btn-sm btn-info"><i class="fas fa-search mr-1"></i> Lọc</button> <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-sm btn-default">Xoá</a></div>
        </form>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap mb-0">
            <thead><tr><th style="width:100px">Mã</th><th>Họ tên</th><th>Phòng ban</th><th>Chức vụ</th><th style="width:120px">Điện thoại</th><th style="width:110px" class="text-center">Trạng thái</th><th style="width:130px" class="text-center">Thao tác</th></tr></thead>
            <tbody>
            @if (!empty($dataList))
                @foreach ($dataList as $item)
                <tr>
                    <td><code>{{$item['code']}}</code></td>
                    <td class="font-weight-bold">{{$item['name']}}</td>
                    <td>{{!empty($item['dept_name'])?$item['dept_name']:'—'}}</td>
                    <td>{{!empty($item['pos_name'])?$item['pos_name']:'—'}}</td>
                    <td>{{!empty($item['phone'])?$item['phone']:'—'}}</td>
                    <td class="text-center">{!! $item['status']==1 ? '<span class="badge badge-success">Đang làm</span>' : '<span class="badge badge-secondary">Đã nghỉ</span>' !!}</td>
                    <td class="text-center">
                        @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$item['id']}}" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                        @endif
                        @if (route('admin/'.$routeBase.'/delete/'.$item['id']))
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/delete/'.$item['id']}}" onclick="return confirm('Xoá nhân viên này?')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></a>
                        @endif
                    </td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="7" class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x d-block mb-2"></i> Chưa có nhân viên</td></tr>
            @endif
            </tbody>
        </table>
    </div>
</div>
