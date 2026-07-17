<div class="container py-3">
    <h3>{{$page_name}}</h3>
    <hr>
    @if (route('admin/groups/add'))
    <p><a href="{{_WEB_URL.'/admin/groups/add'}}" class="btn btn-primary">Thêm nhóm</a></p>
    @endif
    @if (!empty($msg))
    <div class="alert alert-success text-center">{{$msg}}</div>
    @endif
    <table class="table table-bordered">
        <thead>
            <tr>
                <th width="5%">STT</th>
                <th>Tên nhóm</th>
                <th width="15%">Phân quyền</th>
                <th width="10%">Sửa</th>
                <th width="10%">Xoá</th>
            </tr>
        </thead>
        <tbody>
            @if (!empty($dataGroups))
                @foreach ($dataGroups as $key => $item)
            <tr>
                <td class="text-center">{{$key+1}}</td>
                <td>{{$item['name']}}</td>
                <td class="text-center">
                    @if (route('admin/groups/permission/'.$item['id']))
                    <a href="{{_WEB_URL.'/admin/groups/permission/'.$item['id']}}" class="btn btn-primary">Phân quyền</a>
                    @endif
                </td>
                <td class="text-center">
                    @if (route('admin/groups/edit/'.$item['id']))
                    <a href="{{_WEB_URL.'/admin/groups/edit/'.$item['id']}}" class="btn btn-warning"><i class="fa fa-edit"></i> </a>
                    @endif
                </td>
                <td class="text-center">
                    @if (route('admin/groups/edit/'.$item['id']))
                    <a onclick="return confirm('Bạn có chắc chắn?')" href="{{_WEB_URL.'/admin/groups/delete/'.$item['id']}}" class="btn btn-danger"><i class="fa fa-trash"></i></a>
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