<div class="container py-3">
    <h3>{{$page_name}}</h3>
    <hr>
    @if (route('admin/'.$routeBase.'/add'))
    <p><a href="{{_WEB_URL.'/admin/'.$routeBase.'/add'}}" class="btn btn-primary">Thêm {{$labelOne}}</a></p>
    @endif

    @if (!empty($msg))
    <div class="alert alert-success text-center">{{$msg}}</div>
    @endif
    @if (!empty($msgError))
    <div class="alert alert-danger text-center">{{$msgError}}</div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th width="5%">STT</th>
                <th>Tên</th>
                <th width="20%">Đường dẫn (slug)</th>
                @if ($hasHex)
                <th width="10%">Màu</th>
                @endif
                <th width="8%">Thứ tự</th>
                <th width="12%">Trạng thái</th>
                <th width="8%">Sửa</th>
                <th width="8%">Xoá</th>
            </tr>
        </thead>
        <tbody>
            @if (!empty($dataList))
                @foreach ($dataList as $key => $item)
            <tr>
                <td class="text-center">{{$key+1}}</td>
                <td>{{$item['name']}}</td>
                <td><code>{{$item['slug']}}</code></td>
                @if ($hasHex)
                <td class="text-center">
                    {!! !empty($item['hex']) ? '<span style="display:inline-block;width:24px;height:24px;border:1px solid #ccc;background:'.e($item['hex']).'"></span>' : '-' !!}
                </td>
                @endif
                <td class="text-center">{{$item['sort_order']}}</td>
                <td class="text-center">
                    {!! $item['status']==1 ? '<span class="badge badge-success">Hiển thị</span>' : '<span class="badge badge-secondary">Ẩn</span>' !!}
                </td>
                <td class="text-center">
                    @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
                    <a href="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$item['id']}}" class="btn btn-warning btn-sm"><i class="fa fa-edit"></i> Sửa</a>
                    @endif
                </td>
                <td class="text-center">
                    @if (route('admin/'.$routeBase.'/delete/'.$item['id']))
                    <a onclick="return confirm('Bạn có chắc chắn muốn xoá?')" href="{{_WEB_URL.'/admin/'.$routeBase.'/delete/'.$item['id']}}" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i> Xoá</a>
                    @endif
                </td>
            </tr>
            @endforeach
            @else
            <tr>
                <td colspan="8" class="text-center">Chưa có dữ liệu</td>
            </tr>
            @endif
        </tbody>
    </table>
</div>
