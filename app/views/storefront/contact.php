<?php
$flashType = ''; $flashMsg = '';
if (!empty($flash) && strpos($flash, '|') !== false){
    list($flashType, $flashMsg) = explode('|', $flash, 2);
}
$ov = function($k) use ($old){ return isset($old[$k]) ? $old[$k] : ''; };
?>
<div class="content">
<div class="sf-page">
<div class="container">

    <div class="breadcrumb-sf"><a href="{{_WEB_URL.'/'}}">Trang chủ</a> / Liên hệ</div>
    <h1 class="sf-page-title mb-3">Liên hệ {{!empty($settings['site_name'])?$settings['site_name']:'Tân Phát'}}</h1>

    @if (!empty($flashMsg))
    <div class="alert {{$flashType==='ok'?'alert-success':'alert-danger'}}">{{$flashMsg}}</div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <h3 class="h5">Thông tin liên hệ</h3>
                <p class="text-muted mb-1"><i class="fa fa-phone text-accent"></i> Hotline: <b>{{!empty($settings['hotline'])?$settings['hotline']:'1900 0000'}}</b></p>
                <p class="text-muted mb-1"><i class="fa fa-envelope text-accent"></i> Email: <b>{{!empty($settings['email'])?$settings['email']:'info@tanphat.vn'}}</b></p>
                @if (!empty($settings['address']))
                <p class="text-muted"><i class="fa fa-map-marker text-accent"></i> {{$settings['address']}}</p>
                @endif
                <hr/>
                <h3 class="h5">Nhận bản tin</h3>
                <p class="text-muted small">Đăng ký để nhận thông tin sản phẩm & khuyến mãi mới.</p>
                <form method="post" action="{{_WEB_URL.'/dang-ky-ban-tin'}}">
                    <?php echo csrf_field(); ?>
                    <div class="input-group">
                        <input type="email" name="email" class="form-control" placeholder="Email của bạn" required/>
                        <button type="submit" class="btn btn-primary">Đăng ký</button>
                    </div>
                </form>
            </div></div>
        </div>

        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm"><div class="card-body">
                <h3 class="h5">Gửi yêu cầu / câu hỏi</h3>
                <form method="post" action="{{_WEB_URL.'/lien-he'}}">
                    <?php echo csrf_field(); ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Họ tên <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{$ov('name')}}" required/>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Điện thoại</label>
                            <input type="tel" inputmode="numeric" maxlength="11" pattern="0[0-9]{9,10}" title="Di dong 10 so (0912345678) hoac co dinh 11 so (02438765432)" name="phone" class="form-control" value="{{$ov('phone')}}"/>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control" value="{{$ov('email')}}"/>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tiêu đề</label>
                            <input type="text" name="subject" class="form-control" value="{{$ov('subject')}}"/>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Nội dung <span class="text-danger">*</span></label>
                            <textarea name="message" class="form-control" rows="5" required>{{$ov('message')}}</textarea>
                        </div>
                    </div>
                    <p class="text-muted small mt-2 mb-2">Vui lòng để lại ít nhất số điện thoại hoặc email để chúng tôi phản hồi.</p>
                    <button type="submit" class="btn btn-primary">Gửi liên hệ</button>
                </form>
            </div></div>
        </div>
    </div>

</div>
</div>
</div>
