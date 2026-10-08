<?php
/**
 * DANH SÁCH NHÓM QUYỀN.
 *
 * Từ 08/10/2026 mỗi gara có bộ nhóm riêng, nên có thể có BỐN dòng cùng tên
 * "Manager" — cột Gara là thứ duy nhất phân biệt được chúng, bỏ cột đó là màn
 * này vô nghĩa với gara tổng.
 *
 * Nút Sửa / Xoá / Phân quyền ẩn theo `$item['sua_duoc']` do controller tính,
 * KHÔNG theo route(): route() chỉ biết "nhóm có quyền vào màn này không", nó
 * không biết dòng này có phải nhóm của gara mình. Chốt thật nằm ở controller
 * (Groups::layNhom) — ẩn nút ở đây chỉ để khỏi bấm vào rồi nhận câu từ chối.
 */
?>
<div class="container py-3">
    <h3>{{$page_name}}</h3>
    <hr>
    @if (route('admin/groups/add'))
    <p><a href="{{_WEB_URL.'/admin/groups/add'}}" class="btn btn-primary">Thêm nhóm</a></p>
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
                <th>Tên nhóm</th>
                <th width="20%">Gara</th>
                <th width="8%">Người</th>
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
                <td>
                    {{$item['name']}}
                    @if ($item['id']==$nhomCuaToi)
                    <span class="badge badge-info">nhóm của bạn</span>
                    @endif
                </td>
                <td>
                    @if (!empty($item['garage_name']))
                    {{$item['garage_name']}}
                    @else
                    <span class="text-muted">Hệ thống (Tân Phát)</span>
                    @endif
                </td>
                <td class="text-center">{{$item['so_nguoi']}}</td>
                <td class="text-center">
                    @if (!empty($item['sua_duoc']))
                    <a href="{{_WEB_URL.'/admin/groups/permission/'.$item['id']}}" class="btn btn-primary">Phân quyền</a>
                    @endif
                </td>
                <td class="text-center">
                    @if (!empty($item['sua_duoc']) && route('admin/groups/edit/'.$item['id']))
                    <a href="{{_WEB_URL.'/admin/groups/edit/'.$item['id']}}" class="btn btn-warning"><i class="fas fa-edit"></i> </a>
                    @endif
                </td>
                <td class="text-center">
                    @if (!empty($item['sua_duoc']) && empty($item['so_nguoi']) && route('admin/groups/delete/'.$item['id']))
                    <a onclick="return confirm('Bạn có chắc chắn?')" href="{{_WEB_URL.'/admin/groups/delete/'.$item['id']}}" class="btn btn-danger"><i class="fas fa-trash"></i></a>
                    @endif
                </td>
            </tr>
            @endforeach
            @else
            <tr>
                <td colspan="7" class="text-center">Không có dữ liệu</td>
            </tr>
            @endif
        </tbody>
    </table>

    @if (empty($toanQuyen))
    <p class="text-muted">
        Bạn chỉ thấy và sửa được nhóm của gara mình, và chỉ cấp được những quyền
        nhóm của chính bạn đang có. Nhóm của bạn thì Tân Phát sửa.
    </p>
    @endif
</div>
