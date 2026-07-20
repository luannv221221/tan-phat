<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-plus-circle mr-2"></i>{{$page_name}}</h3></div>
            <form action="" method="post">
                <?php echo csrf_field(); ?>
                <div class="card-body">
                    @if (!empty($msg))
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-1"></i> {{$msg}}</div>
                    @endif

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Số hiệu <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="code" placeholder="VD: 1111" value="{{!empty($old['code'])?$old['code']:''}}"/>
                            {!! !empty($errors['code'])?'<small class="text-danger">'.e($errors['code']).'</small>':false !!}
                        </div>
                        <div class="form-group col-md-8">
                            <label>Tên tài khoản <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" placeholder="VD: Tiền mặt tại quỹ" value="{{!empty($old['name'])?$old['name']:''}}"/>
                            {!! !empty($errors['name'])?'<small class="text-danger">'.e($errors['name']).'</small>':false !!}
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Tài khoản cha</label>
                        <select name="parent_id" class="form-control">
                            <option value="">— Tài khoản gốc —</option>
                            <?php
                            if (!empty($tree)):
                                foreach ($tree as $t):
                                    if (in_array((int) $t['id'], $excludeIds, true)) continue;
                                    $sel = (!empty($old['parent_id']) && $old['parent_id'] == $t['id']) ? 'selected' : '';
                                    echo '<option value="' . (int) $t['id'] . '" ' . $sel . '>'
                                       . str_repeat('— ', (int) $t['depth']) . e($t['code'] . ' - ' . $t['name']) . '</option>';
                                endforeach;
                            endif;
                            ?>
                        </select>
                        {!! !empty($errors['parent_id'])?'<small class="text-danger d-block">'.e($errors['parent_id']).'</small>':false !!}
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Loại tài khoản</label>
                            <select name="type" class="form-control">
                                @foreach ($types as $k => $label)
                                <option value="{{$k}}" {{(!empty($old['type']) && $old['type']==$k)?'selected':''}}>{{$label}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Thứ tự</label>
                            <input type="number" class="form-control" name="sort_order" value="{{!empty($old['sort_order'])?$old['sort_order']:'0'}}"/>
                        </div>
                        <div class="form-group col-md-3">
                            <label class="d-block">Chi tiết</label>
                            <div class="custom-control custom-switch mt-1">
                                <input type="checkbox" class="custom-control-input" name="is_detail" id="is_detail" value="1" checked/>
                                <label class="custom-control-label" for="is_detail">Hạch toán</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" name="status" id="status" value="1" checked/>
                            <label class="custom-control-label" for="status">Đang dùng</label>
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
