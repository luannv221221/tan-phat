<div class="row justify-content-center"><div class="col-md-8 col-lg-6">
    <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-plus-circle mr-2"></i>{{$page_name}}</h3></div>
        <form action="" method="post">
            <?php echo csrf_field(); ?>
            <div class="card-body">
                @if (!empty($msg))
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-1"></i> {{$msg}}</div>
                @endif
                <div class="form-group">
                    <label>Nhãn hiển thị <span class="text-danger">*</span></label>
                    <input type="text" name="label" class="form-control" value="{{!empty($old['label'])?$old['label']:''}}"/>
                    {!! !empty($errors['label'])?'<small class="text-danger">'.e($errors['label']).'</small>':false !!}
                </div>
                <div class="form-group">
                    <label>Liên kết (URL)</label>
                    <input type="text" name="url" class="form-control" placeholder="vd: san-pham hoặc san-pham?promo=1 hoặc https://..." value="{{!empty($old['url'])?$old['url']:''}}"/>
                    <small class="form-text text-muted">Bỏ trống = trang chủ. Đường dẫn nội bộ không cần domain.</small>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Menu cha</label>
                        <select name="parent_id" class="form-control">
                            <option value="">— Menu gốc —</option>
                            @foreach ($roots as $r)
                            <option value="{{$r['id']}}" {{(!empty($old['parent_id']) && $old['parent_id']==$r['id'])?'selected':''}}>{{$r['label']}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Mở trong</label>
                        <select name="target" class="form-control">
                            <option value="_self">Cùng tab</option>
                            <option value="_blank" {{(!empty($old['target']) && $old['target']=='_blank')?'selected':''}}>Tab mới</option>
                        </select>
                    </div>
                </div>
                <div class="form-row align-items-center">
                    <div class="form-group col-md-4"><label>Thứ tự</label><input type="number" name="sort_order" class="form-control" value="{{!empty($old['sort_order'])?$old['sort_order']:'0'}}"/></div>
                    <div class="form-group col-md-4 mt-4"><div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" name="status" id="status" value="1" checked/>
                        <label class="custom-control-label" for="status">Bật</label>
                    </div></div>
                </div>
            </div>
            <div class="card-footer"><button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Thêm</button> <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default">Quay lại</a></div>
        </form>
    </div>
</div></div>
