<?php $v = function($k, $mac = '') use ($old){ return isset($old[$k]) ? $old[$k] : $mac; }; ?>
<form action="" method="post">
    <?php echo csrf_field(); ?>
    @if (!empty($msg))
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-1"></i> {{$msg}}</div>
    @endif
    <?php /* Khai xe từ màn Đối tượng: lưu xong quay về đúng khách đó */ ?>
    @if ($ve === 'partner')
    <input type="hidden" name="ve" value="partner"/>
    @endif

    <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-car mr-2"></i>{{$page_name}}</h3></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Chủ xe <span class="text-muted small">(đối tượng khách)</span></label>
                    <select name="partner_id" class="form-control js-search" data-placeholder="Gõ tên hoặc mã để tìm...">
                        <option value="">— Chưa gán chủ / khách vãng lai —</option>
                        @if (!empty($partners))
                        @foreach ($partners as $p)
                        <option value="{{$p['id']}}" {{(int)$v('partner_id')===(int)$p['id']?'selected':''}}>{{$p['code'].' - '.$p['name']}}</option>
                        @endforeach
                        @endif
                    </select>
                    {!! !empty($errors['partner_id'])?'<small class="text-danger">'.e($errors['partner_id']).'</small>':false !!}
                    <small class="form-text text-muted">Một khách có nhiều xe — chọn cùng một khách cho các xe của họ.</small>
                </div>
                <div class="form-group col-md-3">
                    <label>Biển số xe <span class="text-danger">*</span></label>
                    <input type="text" name="bien_so" class="form-control text-uppercase"
                           placeholder="VD: 30A-123.45" value="{{$v('bien_so')}}"/>
                    {!! !empty($errors['bien_so'])?'<small class="text-danger">'.e($errors['bien_so']).'</small>':false !!}
                    <small class="form-text text-muted">Không trùng với xe khác; gõ kiểu nào cũng nhận.</small>
                </div>
                <div class="form-group col-md-3">
                    <label>Số km hiện tại</label>
                    <input type="text" name="so_km" class="form-control text-right" placeholder="VD: 45.000" value="{{$v('so_km')}}"/>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Số khung (VIN)</label>
                    <input type="text" name="so_khung" class="form-control text-uppercase"
                           placeholder="17 ký tự sau kính lái / khung xe" value="{{$v('so_khung')}}"/>
                    {!! !empty($errors['so_khung'])?'<small class="text-danger">'.e($errors['so_khung']).'</small>':false !!}
                    <small class="form-text text-muted">Để trống được; đã ghi thì không được trùng xe khác.</small>
                </div>
                <div class="form-group col-md-6">
                    <label>Số máy</label>
                    <input type="text" name="so_may" class="form-control text-uppercase" value="{{$v('so_may')}}"/>
                </div>
            </div>

            <?php /* Hãng → model → năm lấy từ Danh mục xe. Ô sau tải theo ô trước,
                     và server kiểm lại model có thuộc hãng, năm có thuộc model. */ ?>
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Hãng xe <span class="text-muted small">(danh mục)</span></label>
                    <select name="brand_id" class="form-control" data-xe="hang" data-url="{{_WEB_URL.'/admin/vehicles'}}">
                        <option value="">— Không chọn —</option>
                        @if (!empty($hangDs))
                        @foreach ($hangDs as $h)
                        <option value="{{$h['id']}}" {{(int)$v('brand_id')===(int)$h['id']?'selected':''}}>{{$h['name']}}</option>
                        @endforeach
                        @endif
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label>Model</label>
                    <select name="model_id" class="form-control" data-xe="model" data-chon="{{$v('model_id')}}" disabled>
                        <option value="">— Chọn hãng trước —</option>
                    </select>
                    {!! !empty($errors['model_id'])?'<small class="text-danger">'.e($errors['model_id']).'</small>':false !!}
                </div>
                <div class="form-group col-md-4">
                    <label>Năm sản xuất</label>
                    <select name="car_year_id" class="form-control" data-xe="nam" data-chon="{{$v('car_year_id')}}" disabled>
                        <option value="">— Chọn model trước —</option>
                    </select>
                    {!! !empty($errors['car_year_id'])?'<small class="text-danger">'.e($errors['car_year_id']).'</small>':false !!}
                </div>
            </div>

            <div class="form-row">
                <div class="col-12"><p class="text-muted small mb-1"><i class="fas fa-info-circle mr-1"></i> Xe lạ chưa có trong Danh mục xe thì gõ tay ba ô dưới đây (chọn danh mục rồi thì không cần gõ):</p></div>
                <div class="form-group col-md-4">
                    <label class="small">Hãng (gõ tay)</label>
                    <input type="text" name="hang_xe" class="form-control form-control-sm" value="{{$v('hang_xe')}}"/>
                </div>
                <div class="form-group col-md-4">
                    <label class="small">Model (gõ tay)</label>
                    <input type="text" name="model_xe" class="form-control form-control-sm" value="{{$v('model_xe')}}"/>
                </div>
                <div class="form-group col-md-4">
                    <label class="small">Năm SX (gõ tay)</label>
                    <input type="text" name="nam_sx" class="form-control form-control-sm" placeholder="VD: 2019" value="{{$v('nam_sx')}}"/>
                    {!! !empty($errors['nam_sx'])?'<small class="text-danger">'.e($errors['nam_sx']).'</small>':false !!}
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Phiên bản</label>
                    <input type="text" name="phien_ban" class="form-control" placeholder="VD: 1.5G AT" value="{{$v('phien_ban')}}"/>
                </div>
                <div class="form-group col-md-4">
                    <label>Màu xe</label>
                    <input type="text" name="mau_xe" class="form-control" value="{{$v('mau_xe')}}"/>
                </div>
                <div class="form-group col-md-4 align-self-end">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" name="status" id="status" value="1" checked/>
                        <label class="custom-control-label" for="status">Đang dùng</label>
                    </div>
                </div>
            </div>

            <div class="form-group mb-0">
                <label>Ghi chú</label>
                <input type="text" name="ghi_chu" class="form-control" value="{{$v('ghi_chu')}}"/>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Thêm xe</button>
            <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i> Quay lại</a>
        </div>
    </div>
</form>
<script src="{{asset('public/assets/js/xe-danh-muc.js')}}"></script>
