<?php
/* Sửa dịch vụ — cùng bộ ô với màn hình Thêm.
   $v(): ưu tiên giá trị vừa nhập hỏng (old) rồi mới tới giá trị trong CSDL,
   để lúc báo lỗi người dùng không mất những gì đã gõ. */
$v = function($key, $default = '') use ($old, $item){
    if (isset($old[$key])) return $old[$key];
    return isset($item[$key]) && $item[$key] !== null ? $item[$key] : $default;
};
$tick = function($key) use ($old, $item){
    // Có $old nghĩa là form vừa gửi lên: ô không tick thì hidden gửi '0'.
    if (!empty($old)) return !empty($old[$key]);
    return !empty($item[$key]);
};
?>
<div class="row justify-content-center">
    <div class="col-md-10 col-lg-8">
        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-edit mr-2"></i>{{$page_name}}</h3>
                <div class="card-tools"><code>{{$item['code']}}</code></div>
            </div>

            <form action="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$item['id']}}" method="post">
                <?php echo csrf_field(); ?>
                <div class="card-body">
                    @if (!empty($msg))
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-1"></i> {{$msg}}</div>
                    @endif

                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label>Tên dịch vụ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" value="{{$v('name')}}"/>
                            {!! !empty($errors['name'])?'<small class="text-danger">'.e($errors['name']).'</small>':false !!}
                        </div>
                        <div class="form-group col-md-4">
                            <label>Giá <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" min="0" step="1000" class="form-control text-right" name="price" value="{{(int)$v('price',0)}}"/>
                                <div class="input-group-append"><span class="input-group-text">₫</span></div>
                            </div>
                            {!! !empty($errors['price'])?'<small class="text-danger">'.e($errors['price']).'</small>':false !!}
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Mã dịch vụ</label>
                            <input type="text" class="form-control" name="code" value="{{$v('code')}}"/>
                            {!! !empty($errors['code'])?'<small class="text-danger">'.e($errors['code']).'</small>':false !!}
                        </div>
                        <div class="form-group col-md-4">
                            <label>Nhóm dịch vụ</label>
                            <select name="category_id" class="form-control">
                                <option value="">— Không phân nhóm —</option>
                                @foreach ($categories as $c)
                                <option value="{{$c['id']}}" {{$v('category_id')==$c['id']?'selected':''}}>{{str_repeat('— ', (int)$c['depth']).$c['name']}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Đơn vị tính</label>
                            <select name="unit_id" class="form-control">
                                <option value="">— Không có —</option>
                                @foreach ($units as $u)
                                <option value="{{$u['id']}}" {{$v('unit_id')==$u['id']?'selected':''}}>{{$u['name']}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Mô tả</label>
                        <textarea name="description" class="form-control" rows="3">{{$v('description')}}</textarea>
                    </div>

                    <div class="form-row mb-0">
                        <div class="form-group col-md-6 mb-0">
                            <input type="hidden" name="status" value="0"/>
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" name="status" id="status" value="1" {{$tick('status')?'checked':''}}/>
                                <label class="custom-control-label" for="status">Đang nhận làm dịch vụ này</label>
                            </div>
                        </div>
                        <div class="form-group col-md-6 mb-0">
                            <input type="hidden" name="show_on_web" value="0"/>
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" name="show_on_web" id="show_on_web" value="1" {{$tick('show_on_web')?'checked':''}}/>
                                <label class="custom-control-label" for="show_on_web">Hiển thị trên website</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu thay đổi</button>
                    @if (route('admin/'.$routeBase.'/delete/'.$item['id']))
                    <a href="{{_WEB_URL.'/admin/'.$routeBase.'/delete/'.$item['id']}}" onclick="return confirm('Xoá dịch vụ này?')" class="btn btn-outline-danger"><i class="fas fa-trash mr-1"></i> Xoá</a>
                    @endif
                    <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i> Quay lại</a>
                </div>
            </form>
        </div>
    </div>
</div>
