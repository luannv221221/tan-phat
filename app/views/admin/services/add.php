<?php
/* Form dịch vụ — CỐ TÌNH gọn hơn form hàng hoá.
   Bắt buộc đúng 2 ô: Tên và Giá. Mọi ô còn lại bỏ trống vẫn lưu được.
   Không có mã OEM / thương hiệu / hãng / xuất xứ / lắp cho đời xe / thông số
   kỹ thuật / ảnh — dịch vụ không mang mấy thuộc tính đó. */
$o = function($key, $default = '') use ($old){ return isset($old[$key]) ? $old[$key] : $default; };
?>
<div class="row justify-content-center">
    <div class="col-md-10 col-lg-8">
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

                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label>Tên dịch vụ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" placeholder="Ví dụ: Thay dầu máy" value="{{$o('name')}}"/>
                            {!! !empty($errors['name'])?'<small class="text-danger">'.e($errors['name']).'</small>':false !!}
                        </div>
                        <div class="form-group col-md-4">
                            <label>Giá <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" min="0" step="1000" class="form-control text-right" name="price" value="{{$o('price')}}"/>
                                <div class="input-group-append"><span class="input-group-text">₫</span></div>
                            </div>
                            {!! !empty($errors['price'])?'<small class="text-danger">'.e($errors['price']).'</small>':false !!}
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Mã dịch vụ</label>
                            <input type="text" class="form-control" name="code" placeholder="{{$maGoiY}}" value="{{$o('code')}}"/>
                            <small class="form-text text-muted">Bỏ trống sẽ tự đặt <code>{{$maGoiY}}</code>.</small>
                            {!! !empty($errors['code'])?'<small class="text-danger">'.e($errors['code']).'</small>':false !!}
                        </div>
                        <div class="form-group col-md-4">
                            <label>Nhóm dịch vụ</label>
                            <select name="category_id" class="form-control">
                                <option value="">— Không phân nhóm —</option>
                                @foreach ($categories as $c)
                                <option value="{{$c['id']}}" {{$o('category_id')==$c['id']?'selected':''}}>{{str_repeat('— ', (int)$c['depth']).$c['name']}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Đơn vị tính</label>
                            <select name="unit_id" class="form-control">
                                <option value="">— Không có —</option>
                                @foreach ($units as $u)
                                <option value="{{$u['id']}}" {{$o('unit_id')==$u['id']?'selected':''}}>{{$u['name']}}</option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Dịch vụ hay dùng: Lần, Giờ, Gói.</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Mô tả</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Nội dung công việc, phạm vi bảo hành...">{{$o('description')}}</textarea>
                    </div>

                    <div class="form-row mb-0">
                        <div class="form-group col-md-6 mb-0">
                            <?php /* Ô tick không tick thì trình duyệt không gửi gì. Input hidden cùng
                                     tên đứng trước để lúc nào cũng có giá trị gửi lên. */ ?>
                            <input type="hidden" name="status" value="0"/>
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" name="status" id="status" value="1" {{$o('status','1')?'checked':''}}/>
                                <label class="custom-control-label" for="status">Đang nhận làm dịch vụ này</label>
                            </div>
                        </div>
                        <div class="form-group col-md-6 mb-0">
                            <input type="hidden" name="show_on_web" value="0"/>
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" name="show_on_web" id="show_on_web" value="1" {{$o('show_on_web')?'checked':''}}/>
                                <label class="custom-control-label" for="show_on_web">Hiển thị trên website</label>
                            </div>
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
