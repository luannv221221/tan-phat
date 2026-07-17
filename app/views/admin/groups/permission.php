<div class="container py-3">
    <h3>{{$page_name}}</h3>
    <hr>
    <form action="" method="post">
        <?php echo csrf_field(); ?>
        @if (!empty($msg))
        <div class="alert alert-success text-center">{{$msg}}</div>
        @endif
        <table class="table table-borered">
            <thead>
                <tr>
                    <th width="15%">Module</th>
                    <th>Quyền</th>
                </tr>
            </thead>
            <tbody>
                @if (!empty($listModules))
                    @foreach ($listModules as $key=>$module)
                <tr>
                    <td>{{$module['name']}}</td>
                    <td>
                        <div class="row">
                            <div class="col-2">
                                <input type="checkbox" name=permission[{{$module['id']}}][]" value="view" {{isRole($module['id'], 'view', $permissionData)?'checked':false}}/> Xem
                            </div>
                            <div class="col-2">
                                <input type="checkbox" name=permission[{{$module['id']}}][]" value="add" {{isRole($module['id'], 'add', $permissionData)?'checked':false}}/> Thêm
                            </div>
                            <div class="col-2">
                                <input type="checkbox" name=permission[{{$module['id']}}][]" value="edit" {{isRole($module['id'], 'edit', $permissionData)?'checked':false}}/> Sửa
                            </div>
                            <div class="col-2">
                                <input name=permission[{{$module['id']}}][]" type="checkbox" value="delete"  {{isRole($module['id'], 'delete', $permissionData)?'checked':false}}/> Xoá
                            </div>

                            @if ($module['link']=='groups')
                            <div class="col-2">
                                <input name=permission[{{$module['id']}}][]" type="checkbox" value="permission"  {{isRole($module['id'], 'permission', $permissionData)?'checked':false}}/> Phân quyền
                            </div>
                            @endif
                        </div>
                    </td>
                </tr>
                    @endforeach
                @endif
            </tbody>

        </table>
        <hr>
        <button type="submit" class="btn btn-primary">Phân quyền</button>
    </form>
</div>