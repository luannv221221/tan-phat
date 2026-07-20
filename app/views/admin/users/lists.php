<div class="container py-3">
    <h3>{{$page_name}}</h3>
    <hr>
    @if (route('admin/users/add'))
    <p><a href="{{_WEB_URL.'/admin/users/add'}}" class="btn btn-primary">Thêm người dùng</a></p>
    @endif
    @if (!empty($msg))
    <div class="alert alert-success text-center">{{$msg}}</div>
    @endif

    <form action="" method="get">
        <div class="row">
            <div class="col-3">
                <div class="form-group">
<!--                    <label for="">Trạng thái</label>-->
                    <select name="status" class="form-control">
                        <option value="all">Tất cả trạng thái</option>
                        <option value="0" {{isset(request()->getFields()['status']) && request()->getFields()['status']==0?'selected':false}}>Chưa kích hoạt</option>
                        <option value="1" {{!empty(request()->getFields()['status']) && request()->getFields()['status']==1?'selected':false}}>Kích hoạt</option>
                    </select>
                </div>
            </div>

            <div class="col-3">
                <div class="form-group">
<!--                    <label for="">Nhóm</label>-->
                    <select name="group_id" class="form-control">
                        <option value="0">Tất cả nhóm</option>
                        @if (!empty($groupData))
                            @foreach ($groupData as $item)
                                <option value="{{$item['id']}}" {{!empty(request()->getFields()['group_id']) && request()->getFields()['group_id']==$item['id']?'selected':false}}>{{$item['name']}}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
            </div>

            <div class="col-4">
                <div class="form-group">
<!--                    <label for="">Từ khoá</label>-->
                    <input type="search" name="keyword" placeholder="Từ khoá tìm kiếm..." class="form-control" value="{{!empty(request()->getFields()['keyword'])?request()->getFields()['keyword']:false}}"/>
                </div>
            </div>
            <div class="col-2">
                <button type="submit" class="btn btn-success">Tìm kiếm</button>
                <a href="{{_WEB_URL.'/admin/users'}}" class="btn btn-danger">&times;</a>
            </div>
        </div>
    </form>

    <table class="table table-bordered">
        <thead>
        <tr>
            <th width="5%">STT</th>
            <th>Tên</th>
            <th>Email</th>
            <th>Trạng thái</th>
            <th>Nhóm</th>
            <th width="10%">Sửa</th>
            <th width="10%">Xoá</th>
        </tr>
        </thead>
        <tbody>
        @if (!empty($dataUsers))
        @foreach ($dataUsers as $key => $item)
        <tr>
            <td class="text-center">{{$key+1}}</td>
            <td>{{$item['name']}}</td>
            <td>{{$item['email']}}</td>
            <td>{!!$item['status']==0?'<button class="btn btn-warning btn-sm">Chưa kích hoạt</button>':'<button class="btn btn-success btn-sm">Kích hoạt</button>'!!}</td>
            <td>{{$item['group_name']}}</td>

            <td class="text-center">
                @if (route('admin/users/edit/'.$item['id']))
                <a href="{{_WEB_URL.'/admin/users/edit/'.$item['id']}}" class="btn btn-warning"><i class="fas fa-edit"></i> </a>
                @endif
            </td>
            <td class="text-center">
                @if (route('admin/users/delete/'.$item['id']))
                @if ($item['id']!=$infoUser['id'])
                <a onclick="return confirm('Bạn có chắc chắn?')" href="{{_WEB_URL.'/admin/users/delete/'.$item['id']}}" class="btn btn-danger"><i class="fas fa-trash"></i></a>
                @else
                <a href="#" onclick="alert('Không thể xoá vì người dùng đang hoạt động');return false;" class="btn btn-danger disabled"><i class="fas fa-trash"></i></a>
                @endif
                @endif
            </td>
        </tr>
        @endforeach
        @else
        <tr>
            <td colspan="5" class="text-center">Không có dữ liệu</td>
        </tr>
        @endif
        </tbody>
    </table>
</div>