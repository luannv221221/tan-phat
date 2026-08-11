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
                        <label>Tên thông số <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" placeholder="VD: Trọng lượng" value="{{!empty($old['name'])?$old['name']:''}}"/>
                        {!! !empty($errors['name'])?'<small class="text-danger">'.e($errors['name']).'</small>':false !!}
                    </div>

                    <div class="form-group">
                        <label>Đường dẫn (slug)</label>
                        <input type="text" class="form-control" name="slug" placeholder="Bỏ trống sẽ tự sinh từ tên" value="{{!empty($old['slug'])?$old['slug']:''}}"/>
                        {!! !empty($errors['slug'])?'<small class="text-danger">'.e($errors['slug']).'</small>':false !!}
                    </div>

                    <div class="form-group">
                        <label>Đơn vị đo</label>
                        <input type="text" class="form-control" name="unit" placeholder="VD: kg, mm, V (bỏ trống nếu không có)" style="max-width:200px" value="{{!empty($old['unit'])?$old['unit']:''}}"/>
                    </div>

                    <div class="form-group">
                        <label class="d-block">Áp dụng cho loại hàng</label>
                        @foreach ($loaiHang as $ma => $ten)
                        <div class="custom-control custom-checkbox custom-control-inline">
                            <input type="checkbox" class="custom-control-input" name="item_types[]"
                                   id="lt_{{$ma}}" value="{{$ma}}"
                                   {{(empty($old['item_types']) || in_array($ma, (array)$old['item_types']))?'checked':''}}/>
                            <label class="custom-control-label" for="lt_{{$ma}}">{{$ten}}</label>
                        </div>
                        @endforeach
                        <small class="form-text text-muted">
                            Thông số này chỉ hiện khi thêm/sửa hàng hoá thuộc loại đã tick.
                            Ví dụ "Thời gian thực hiện" chỉ tick Dịch vụ, "Tải trọng" chỉ tick Thiết bị.
                            Không tick gì thì hiểu là áp cho cả ba.
                        </small>
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
