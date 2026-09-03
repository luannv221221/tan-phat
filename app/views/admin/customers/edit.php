<?php
$val = function ($key) use ($item, $old) {
    if (isset($old[$key]) && $old[$key] !== '') return $old[$key];
    return isset($item[$key]) ? $item[$key] : '';
};
$statusOn = isset($old['status']) ? (int) $old['status'] === 1 : (int) $item['status'] === 1;
?>
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-7">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{$page_name}}</h3>
                <div class="card-tools">
                    <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-icon" title="Quay lại"><i class="fas fa-arrow-left"></i></a>
                </div>
            </div>

            <form action="" method="post">
                <?php echo csrf_field(); ?>
                <div class="card-body">

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" value="{{$item['email']}}" disabled/>
                        <small class="form-text text-muted">Email là tên đăng nhập của khách, không sửa ở đây.</small>
                    </div>

                    <div class="form-group">
                        <label>Họ tên <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{$val('name')}}"/>
                        {!! !empty($errors['name'])?'<small class="text-danger">'.e($errors['name']).'</small>':false !!}
                    </div>

                    <div class="form-group">
                        <label>Số điện thoại</label>
                        <input type="tel" inputmode="numeric" maxlength="11" pattern="0[0-9]{9,10}"
                               name="phone" class="form-control" value="{{$val('phone')}}"/>
                        {!! !empty($errors['phone'])?'<small class="text-danger">'.e($errors['phone']).'</small>':false !!}
                    </div>

                    <div class="form-group">
                        <label>Địa chỉ</label>
                        <textarea name="address" class="form-control" rows="2">{{$val('address')}}</textarea>
                    </div>

                    <div class="form-group">
                        <label>Đặt lại mật khẩu</label>
                        <input type="password" name="new_password" class="form-control" autocomplete="new-password"
                               placeholder="Bỏ trống nếu không đổi mật khẩu"/>
                        <small class="form-text text-muted">
                            Mật khẩu lưu dạng bcrypt nên không đọc lại được — chỉ có thể đặt mật khẩu mới rồi báo cho khách.
                        </small>
                        {!! !empty($errors['new_password'])?'<small class="text-danger d-block">'.e($errors['new_password']).'</small>':false !!}
                    </div>

                    <div class="form-group mb-0">
                        <label class="d-block">Trạng thái</label>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" name="status" id="status" value="1" {{$statusOn?'checked':''}}/>
                            <label class="custom-control-label" for="status">Cho phép đăng nhập</label>
                        </div>
                    </div>

                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu</button>
                    <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default">Quay lại</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php /* ---------------------------------------------------------------------
   XE CỦA KHÁCH

   Một khách có thể có NHIỀU xe (Camry + Ranger + CX5...), nên đây là một danh
   sách chứ không phải hai ô thêm vào biểu mẫu khách hàng.

   Đặt NGOÀI thẻ <form> phía trên: HTML không cho lồng form, mà mỗi dòng xe
   cần form riêng để sửa/xoá độc lập. Nhét vào trong thì trình duyệt vứt bỏ
   form con, và nút "Lưu xe" biến thành nút lưu thông tin khách.

   Số km cập nhật mỗi lần xe vào gara — vì vậy mỗi dòng có nút Lưu tại chỗ,
   không bắt mở thêm một màn hình nữa.
   --------------------------------------------------------------------- */ ?>
<div class="row justify-content-center mt-3">
    <div class="col-md-10">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-car mr-2"></i>Xe của khách</h3>
                <div class="card-tools text-muted small">
                    Ở danh sách khách hàng, tìm được bằng chính biển số này
                </div>
            </div>

            <div class="card-body table-responsive p-0">

                <?php /* Mỗi dòng xe một form riêng, nhưng thẻ <form> KHÔNG đặt trong
                         bảng: trình duyệt xử lý <form> nằm giữa <tr> và <td> theo
                         luật riêng của bảng, thứ gì nhét vào trong đó bị đá ra
                         ngoài bảng. Nên form để trần ở đây, các ô trong <td> nối
                         vào bằng thuộc tính form="fxN" của HTML5.
                         Token CSRF nằm THẲNG trong form — CsrfMiddleware chặn mọi
                         POST không kèm token. */ ?>
                @if (!empty($dsXe))
                    @foreach ($dsXe as $x)
                    <form action="{{_WEB_URL.'/admin/'.$routeBase.'/xe-sua/'.$x['id']}}" method="post" id="fx{{$x['id']}}">
                        <?php echo csrf_field(); ?>
                    </form>
                    @endforeach
                @endif

                <table class="table table-sm mb-0 align-middle">
                    <thead><tr>
                        <th style="width:16%">Biển số <span class="text-danger">*</span></th>
                        <th style="width:14%">Hãng xe</th>
                        <th style="width:14%">Model</th>
                        <th style="width:9%">Năm SX</th>
                        <th style="width:11%">Màu</th>
                        <th style="width:12%" class="text-right">Số km</th>
                        <th>Ghi chú</th>
                        <th style="width:96px" class="text-center">Thao tác</th>
                    </tr></thead>
                    <tbody>
                    @if (!empty($dsXe))
                        @foreach ($dsXe as $x)
                        <tr>
                            <td><input form="fx{{$x['id']}}" name="bien_so" class="form-control form-control-sm text-uppercase font-weight-bold" value="{{$x['bien_so']}}"/></td>
                            <td><input form="fx{{$x['id']}}" name="hang_xe" class="form-control form-control-sm" value="{{$x['hang_xe']}}"/></td>
                            <td><input form="fx{{$x['id']}}" name="model_xe" class="form-control form-control-sm" value="{{$x['model_xe']}}"/></td>
                            <td><input form="fx{{$x['id']}}" name="nam_sx" type="number" min="1950" max="2100" class="form-control form-control-sm" value="{{$x['nam_sx']}}"/></td>
                            <td><input form="fx{{$x['id']}}" name="mau_xe" class="form-control form-control-sm" value="{{$x['mau_xe']}}"/></td>
                            <td><input form="fx{{$x['id']}}" name="so_km" class="form-control form-control-sm text-right" value="{{$x['so_km']}}" placeholder="km"/></td>
                            <td><input form="fx{{$x['id']}}" name="ghi_chu" class="form-control form-control-sm" value="{{$x['ghi_chu']}}"/></td>
                            <td class="text-center">
                                <button form="fx{{$x['id']}}" type="submit" class="btn btn-sm btn-warning" title="Lưu xe này"><i class="fas fa-save"></i></button>
                                <a href="{{_WEB_URL.'/admin/'.$routeBase.'/xe-xoa/'.$x['id']}}"
                                   onclick="return confirm('Xoá xe {{$x['bien_so']}} khỏi khách này?')"
                                   class="btn btn-sm btn-danger" title="Xoá"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        @endforeach
                    @else
                        <tr><td colspan="8" class="text-center text-muted py-3">
                            <i class="fas fa-car fa-lg d-block mb-1"></i> Khách này chưa khai xe nào
                        </td></tr>
                    @endif
                    </tbody>
                </table>
            </div>

            <div class="card-footer">
                <form action="{{_WEB_URL.'/admin/'.$routeBase.'/xe-them/'.$item['id']}}" method="post">
                    <?php echo csrf_field(); ?>
                    <div class="form-row align-items-end">
                        <div class="col-md-2"><label class="small mb-1">Biển số <span class="text-danger">*</span></label>
                            <input name="bien_so" class="form-control form-control-sm text-uppercase" placeholder="30A-123.45" required/></div>
                        <div class="col-md-2"><label class="small mb-1">Hãng xe</label>
                            <input name="hang_xe" class="form-control form-control-sm" placeholder="Toyota"/></div>
                        <div class="col-md-2"><label class="small mb-1">Model</label>
                            <input name="model_xe" class="form-control form-control-sm" placeholder="Camry"/></div>
                        <div class="col-md-1"><label class="small mb-1">Năm SX</label>
                            <input name="nam_sx" type="number" min="1950" max="2100" class="form-control form-control-sm"/></div>
                        <div class="col-md-1"><label class="small mb-1">Màu</label>
                            <input name="mau_xe" class="form-control form-control-sm"/></div>
                        <div class="col-md-2"><label class="small mb-1">Số km vào</label>
                            <input name="so_km" class="form-control form-control-sm text-right" placeholder="120000"/></div>
                        <div class="col-md-2 text-right">
                            <button type="submit" class="btn btn-sm btn-info btn-block">
                                <i class="fas fa-plus mr-1"></i> Thêm xe
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
