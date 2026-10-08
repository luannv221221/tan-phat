<?php
/**
 * PHÂN QUYỀN NHÓM.
 *
 * `$dsModule`    — màn hình được phép cấp cho nhóm này (controller lọc sẵn).
 * `$quyenCuaToi` — null = người đang bấm có toàn quyền, tick gì cũng được.
 *                  Mảng ["<module_id>:<role>", ...] = phạm vi tối đa họ cấp
 *                  được; ô ngoài phạm vi hiện ra dạng KHOÁ.
 *
 * Vì sao khoá ở đây mà vẫn chặn lại ở controller: ô khoá chỉ để người dùng
 * nhìn thấy giới hạn. Chốt thật là Groups::postPermission — ẩn hay disable một
 * checkbox không ngăn được ai tự gửi POST.
 *
 * Ô bị khoá dùng `disabled` nên trình duyệt KHÔNG gửi lên. Với ô đang được
 * tick mà ngoài phạm vi (quyền Tân Phát đã cấp cho nhóm đó, chủ gara không
 * được đụng) thì kèm một `hidden` cùng tên để lưu lại KHÔNG LÀM MẤT nó — không
 * có dòng hidden đó thì mỗi lần chủ gara bấm Phân quyền là nhóm Staff của họ
 * lặng lẽ mất sạch các quyền ngoài tầm.
 */
$laKhoa = function($moduleId, $role) use ($quyenCuaToi){
    return $quyenCuaToi !== null && !in_array($moduleId . ':' . $role, $quyenCuaToi, true);
};
$cacRole = ['view' => 'Xem', 'add' => 'Thêm', 'edit' => 'Sửa', 'delete' => 'Xoá'];
?>
<div class="container py-3">
    <h3>{{$page_name}}</h3>
    <hr>
    <form action="" method="post">
        <?php echo csrf_field(); ?>
        @if (!empty($msg))
        <div class="alert alert-success text-center">{{$msg}}</div>
        @endif
        @if (!empty($msgError))
        <div class="alert alert-danger text-center">{{$msgError}}</div>
        @endif

        @if (!empty($quyenCuaToi))
        <div class="alert alert-info">
            Bạn chỉ cấp được những quyền <strong>nhóm của chính bạn</strong> đang có.
            Ô bị khoá là quyền ngoài tầm của bạn — lưu lại sẽ giữ nguyên, không mất đi.
        </div>
        @endif

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th width="30%">Màn hình</th>
                    <th>Quyền</th>
                </tr>
            </thead>
            <tbody>
                @if (!empty($dsModule))
                    @foreach ($dsModule as $key=>$module)
                <tr>
                    <td>
                        {{$module['name']}}<br>
                        <small class="text-muted">{{$module['link']}}</small>
                    </td>
                    <td>
                        <div class="row">
                            <?php foreach ($cacRole as $role => $nhan):
                                    $dangCo = isRole($module['id'], $role, $permissionData);
                                    $khoa   = $laKhoa($module['id'], $role);
                                    $ten    = 'permission[' . (int) $module['id'] . '][]'; ?>
                            <div class="col-2">
                                <input type="checkbox" name="<?php echo e($ten); ?>" value="<?php echo e($role); ?>"
                                       <?php echo $dangCo ? 'checked' : ''; ?>
                                       <?php echo $khoa ? 'disabled title="Quyền này ngoài tầm của bạn"' : ''; ?>/>
                                <?php echo e($nhan); ?>
                                <?php if ($khoa && $dangCo): ?>
                                <input type="hidden" name="<?php echo e($ten); ?>" value="<?php echo e($role); ?>"/>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>

                            <?php /* `permission` (sửa được bảng phân quyền) chỉ có nghĩa trên
                                     chính màn Quản lý nhóm — RoleMiddleware không gác role này
                                     ở đâu khác. Hiện ô đó trên 57 màn còn lại là mời người dùng
                                     tick một thứ không tác dụng gì. */ ?>
                            @if ($module['link']=='groups')
                            <?php $dangCo = isRole($module['id'], 'permission', $permissionData);
                                  $khoa   = $laKhoa($module['id'], 'permission');
                                  $ten    = 'permission[' . (int) $module['id'] . '][]'; ?>
                            <div class="col-3">
                                <input type="checkbox" name="<?php echo e($ten); ?>" value="permission"
                                       <?php echo $dangCo ? 'checked' : ''; ?>
                                       <?php echo $khoa ? 'disabled title="Quyền này ngoài tầm của bạn"' : ''; ?>/>
                                Phân quyền
                                <?php if ($khoa && $dangCo): ?>
                                <input type="hidden" name="<?php echo e($ten); ?>" value="permission"/>
                                <?php endif; ?>
                            </div>
                            @endif
                        </div>
                    </td>
                </tr>
                    @endforeach
                @else
                <tr><td colspan="2" class="text-center">Không có màn hình nào để phân quyền</td></tr>
                @endif
            </tbody>

        </table>
        <hr>
        <button type="submit" class="btn btn-primary">Phân quyền</button>
        <a href="{{_WEB_URL.'/admin/groups'}}" class="btn btn-secondary">Quay lại</a>
    </form>
</div>
