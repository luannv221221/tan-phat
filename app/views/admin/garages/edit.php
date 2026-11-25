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

            <form action="" method="post" enctype="multipart/form-data">
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

                    <?php /* In lên đầu báo giá / hoá đơn / biên bản của gara này. Gara
                             độc lập là một doanh nghiệp riêng: phiếu phải mang tên, MST,
                             logo của chính gara, không phải của Tân Phát. */ ?>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Mã số thuế</label>
                            <input type="text" class="form-control" name="tax_code" value="{{$val('tax_code')}}"/>
                        </div>
                        <div class="form-group col-md-8">
                            <label>Email</label>
                            <input type="email" class="form-control" name="email" value="{{$val('email')}}"/>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Logo <span class="text-muted small">(in lên đầu phiếu — bỏ trống thì phiếu không có logo)</span></label>
                        @if (!empty($item['logo']))
                        <div class="mb-1"><img src="{{_WEB_URL.'/'.ltrim($item['logo'], '/')}}" alt="Logo" style="max-height:48px"/></div>
                        @endif
                        <input type="file" class="form-control-file" name="logo_file" accept="image/*"/>
                    </div>

                    @if ($laTong)
                    <p class="text-muted small"><span class="badge badge-info">Gara tổng</span>
                        Nơi giữ danh mục hàng hoá dùng chung cho mọi gara.</p>
                    @endif

                    <div class="form-group mb-0">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" name="status" id="status" value="1" {{$batStatus?'checked':''}}/>
                            <label class="custom-control-label" for="status">Hoạt động</label>
                        </div>
                    </div>
                </div>

                @if (!$laTong)
                <?php /* TÀI KHOẢN CHỦ GARA — màn Thêm có ba ô này, màn Sửa trước đây
                         không có: khai xong là không còn chỗ nào xem lại chủ gara đăng
                         nhập bằng email gì (khách báo 25/11/2026). Mật khẩu đã mã hoá
                         nên không hiện lại được — ô mật khẩu chỉ để ĐẶT MỚI. */
                $chuTen  = isset($old['chu_name'])  ? $old['chu_name']  : (!empty($chu) ? $chu['name']  : '');
                $chuMail = isset($old['chu_email']) ? $old['chu_email'] : (!empty($chu) ? $chu['email'] : '');
                ?>
                <div class="card-body border-top bg-light">
                    <h6 class="mb-1"><i class="fas fa-user-shield mr-1"></i> Tài khoản chủ gara
                        @if (empty($chu))
                        <span class="text-muted small">(chưa có — khai đủ ba ô để tạo)</span>
                        @endif
                    </h6>
                    <p class="text-muted small mb-2">
                        @if (!empty($chu))
                        Đăng nhập bằng email dưới đây, tại địa chỉ riêng của gara.
                        Mật khẩu đã được mã hoá nên không hiện lại được: <b>bỏ trống</b> ô mật khẩu
                        để giữ nguyên, <b>gõ mật khẩu mới</b> khi chủ gara quên.
                        @if ((int) $chu['status'] !== 1)
                        <br><span class="text-danger">Tài khoản này đang bị tắt — bật lại ở màn Người dùng.</span>
                        @endif
                        @else
                        Tạo một tài khoản nhóm <b>Manager</b> thuộc gara này. Để trống cả ba ô thì bỏ qua.
                        @endif
                    </p>
                    <div class="form-row">
                        <div class="form-group col-md-4 mb-0">
                            <label class="mb-1 small">Họ tên</label>
                            <input type="text" class="form-control" name="chu_name" value="{{$chuTen}}" autocomplete="off"/>
                        </div>
                        <div class="form-group col-md-4 mb-0">
                            <label class="mb-1 small">Email đăng nhập</label>
                            <input type="email" class="form-control" name="chu_email" value="{{$chuMail}}" autocomplete="off"/>
                        </div>
                        <div class="form-group col-md-4 mb-0">
                            <label class="mb-1 small">{{!empty($chu) ? 'Mật khẩu mới' : 'Mật khẩu'}} <span class="text-muted">(từ 6 ký tự)</span></label>
                            <input type="password" class="form-control" name="chu_password" autocomplete="new-password"
                                   placeholder="{{!empty($chu) ? 'Giữ nguyên' : ''}}"/>
                        </div>
                    </div>
                </div>
                @endif

                <div class="card-footer">
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i> Cập nhật</button>
                    <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i> Quay lại</a>
                </div>
            </form>
        </div>
    </div>
</div>
