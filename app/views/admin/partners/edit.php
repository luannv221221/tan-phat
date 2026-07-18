<div class="row justify-content-center">
    <div class="col-md-10 col-lg-7">
        <div class="card card-outline card-warning">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-edit mr-2"></i>{{$page_name}}</h3></div>
            <form action="" method="post">
                <?php echo csrf_field(); ?>
                <div class="card-body">
                    @if (!empty($msg))
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-1"></i> {{$msg}}</div>
                    @endif
                    <?php $selType = isset($old['type']) ? $old['type'] : $item['type']; ?>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Mã <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="code" value="{{!empty($old['code'])?$old['code']:$item['code']}}"/>
                            {!! !empty($errors['code'])?'<small class="text-danger">'.e($errors['code']).'</small>':false !!}
                        </div>
                        <div class="form-group col-md-8">
                            <label>Tên <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" value="{{!empty($old['name'])?$old['name']:$item['name']}}"/>
                            {!! !empty($errors['name'])?'<small class="text-danger">'.e($errors['name']).'</small>':false !!}
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Loại</label>
                            <select name="type" class="form-control">
                                @foreach ($types as $k => $label)
                                <option value="{{$k}}" {{$selType==$k?'selected':''}}>{{$label}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Mã số thuế</label>
                            <input type="text" class="form-control" name="tax_code" value="{{!empty($old['tax_code'])?$old['tax_code']:(!empty($item['tax_code'])?$item['tax_code']:'')}}"/>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Điện thoại</label>
                            <input type="text" class="form-control" name="phone" value="{{!empty($old['phone'])?$old['phone']:(!empty($item['phone'])?$item['phone']:'')}}"/>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Địa chỉ</label>
                        <input type="text" class="form-control" name="address" value="{{!empty($old['address'])?$old['address']:(!empty($item['address'])?$item['address']:'')}}"/>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label>Thứ tự</label>
                            <input type="number" class="form-control" name="sort_order" value="{{isset($old['sort_order'])?$old['sort_order']:$item['sort_order']}}"/>
                        </div>
                        <div class="form-group col-md-9 align-self-end">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" name="status" id="status" value="1" {{$item['status']==1?'checked':''}}/>
                                <label class="custom-control-label" for="status">Đang dùng</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Cập nhật</button>
                    <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i> Quay lại</a>
                </div>
            </form>
        </div>
    </div>
</div>
