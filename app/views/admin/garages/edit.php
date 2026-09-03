<?php
$val = function ($key) use ($item, $old) {
    if (isset($old[$key]) && $old[$key] !== '') return $old[$key];
    return isset($item[$key]) ? $item[$key] : '';
};
$batMaster = isset($old['is_master']) ? (int) $old['is_master'] === 1 : (int) $item['is_master'] === 1;
$batStatus = isset($old['status'])    ? (int) $old['status'] === 1    : (int) $item['status'] === 1;
?>
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-7">
        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-edit mr-2"></i>{{$page_name}}</h3>
                <div class="card-tools">
                    <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-icon" title="Quay lại"><i class="fas fa-arrow-left"></i></a>
                </div>
            </div>

            <form action="" method="post">
                <?php echo csrf_field(); ?>
                <div class="card-body">
                    @if (!empty($msg))
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-1"></i> {{$msg}}</div>
                    @endif

                    <?php /* Nói trước gara này đang gánh những gì — người sửa biết
                             mình đang đụng vào dữ liệu sống, không phải một dòng rỗng. */ ?>
                    @if (!empty($dangDung))
                    <div class="alert alert-info py-2">
                        <i class="fas fa-info-circle mr-1"></i> Gara này đang có:
                        <?php
                        $__t = [];
                        foreach ($dangDung as $__nhan => $__n) $__t[] = '<b>' . (int) $__n . '</b> ' . e($__nhan);
                        echo implode(' · ', $__t);
                        ?>
                    </div>
                    @endif

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Mã gara <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="code" value="{{$val('code')}}"/>
                            {!! !empty($errors['code'])?'<small class="text-danger">'.e($errors['code']).'</small>':false !!}
                        </div>
                        <div class="form-group col-md-8">
                            <label>Tên gara <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" value="{{$val('name')}}"/>
                            {!! !empty($errors['name'])?'<small class="text-danger">'.e($errors['name']).'</small>':false !!}
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Địa chỉ</label>
                        <input type="text" class="form-control" name="address" value="{{$val('address')}}"/>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Điện thoại</label>
                            <input type="tel" class="form-control" name="phone" value="{{$val('phone')}}"/>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Thứ tự</label>
                            <input type="number" class="form-control" name="sort_order" value="{{$val('sort_order')}}"/>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" name="is_master" id="is_master" value="1" {{$batMaster?'checked':''}}/>
                            <label class="custom-control-label" for="is_master">Đây là gara tổng</label>
                        </div>
                        <small class="form-text text-muted">
                            Chỉ có một gara tổng. Bật ở đây thì gara đang giữ vai trò đó tự nhường lại;
                            còn nếu đây đang là gara tổng duy nhất thì không bỏ đánh dấu được.
                        </small>
                    </div>

                    <div class="form-group mb-0">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" name="status" id="status" value="1" {{$batStatus?'checked':''}}/>
                            <label class="custom-control-label" for="status">Hoạt động</label>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i> Cập nhật</button>
                    <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i> Quay lại</a>
                </div>
            </form>
        </div>
    </div>
</div>
