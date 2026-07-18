<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-plus-circle mr-2"></i>{{$page_name}}</h3>
            </div>

            <form action="" method="post">
                <?php echo csrf_field(); ?>
                <div class="card-body">
                    @if (!empty($msg))
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-1"></i> {{$msg}}</div>
                    @endif

                    <div class="form-group">
                        <label>Danh mục cha</label>
                        <select name="parent_id" class="form-control">
                            <option value="">— Danh mục gốc (không có cha) —</option>
                            <?php
                            if (!empty($tree)):
                                foreach ($tree as $t):
                                    if (in_array((int) $t['id'], $excludeIds, true)) continue;
                                    $sel = (!empty($old['parent_id']) && $old['parent_id'] == $t['id']) ? 'selected' : '';
                                    echo '<option value="' . (int) $t['id'] . '" ' . $sel . '>'
                                       . str_repeat('— ', (int) $t['depth']) . e($t['name'])
                                       . '</option>';
                                endforeach;
                            endif;
                            ?>
                        </select>
                        <small class="form-text text-muted">Bỏ trống để tạo danh mục gốc.</small>
                        {!! !empty($errors['parent_id'])?'<small class="text-danger d-block">'.e($errors['parent_id']).'</small>':false !!}
                    </div>

                    <div class="form-group">
                        <label>Tên danh mục <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" placeholder="VD: Hệ thống phanh" value="{{!empty($old['name'])?$old['name']:''}}"/>
                        {!! !empty($errors['name'])?'<small class="text-danger">'.e($errors['name']).'</small>':false !!}
                    </div>

                    <div class="form-group">
                        <label>Đường dẫn (slug)</label>
                        <input type="text" class="form-control" name="slug" placeholder="Bỏ trống sẽ tự sinh từ tên" value="{{!empty($old['slug'])?$old['slug']:''}}"/>
                        {!! !empty($errors['slug'])?'<small class="text-danger">'.e($errors['slug']).'</small>':false !!}
                    </div>

                    <div class="form-group">
                        <label>Mô tả</label>
                        <textarea class="form-control" name="description" rows="2" placeholder="Không bắt buộc">{{!empty($old['description'])?$old['description']:''}}</textarea>
                    </div>

                    <div class="form-group">
                        <label>Thứ tự hiển thị</label>
                        <input type="number" class="form-control" name="sort_order" style="max-width:150px" value="{{!empty($old['sort_order'])?$old['sort_order']:'0'}}"/>
                    </div>

                    <div class="form-group mb-0">
                        <label class="d-block">Trạng thái</label>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" name="status" id="status" value="1" checked/>
                            <label class="custom-control-label" for="status">Hiển thị</label>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Thêm mới</button>
                    <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i> Quay lại</a>
                </div>
            </form>
        </div>
    </div>
</div>
