<?php
$s = function($key, $default = '') use ($settings){ return isset($settings[$key]) && $settings[$key] !== null ? $settings[$key] : $default; };
?>
<div class="row"><div class="col-lg-9 mx-auto">
    @if (!empty($msg))
    <div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
    @endif

    <form action="{{_WEB_URL.'/admin/settings/save'}}" method="post" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>

        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-cog mr-2"></i>Thông tin & SEO</h3></div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-6"><label>Tên website</label><input type="text" name="site_name" class="form-control" value="{{$s('site_name')}}"/></div>
                    <div class="form-group col-md-6"><label>Slogan</label><input type="text" name="site_slogan" class="form-control" value="{{$s('site_slogan')}}"/></div>
                </div>
                <div class="form-group">
                    <label>Meta description (mô tả SEO mặc định)</label>
                    <textarea name="meta_description" class="form-control" rows="2">{{$s('meta_description')}}</textarea>
                </div>
                <div class="form-group">
                    <label>Meta keywords</label>
                    <input type="text" name="meta_keywords" class="form-control" value="{{$s('meta_keywords')}}"/>
                </div>
                <div class="form-group mb-0">
                    <label>Ảnh chia sẻ mạng xã hội (OG image)</label>
                    {!! !empty($settings['og_image']) ? '<img src="'.e(media_url($settings['og_image'])).'" style="height:60px;border-radius:4px;display:block;margin-bottom:6px"/>' : '' !!}
                    <input type="file" name="og_image_file" accept="image/*" class="form-control-file"/>
                    <input type="text" name="og_image" class="form-control mt-1" placeholder="hoặc URL ảnh" value="{{$s('og_image')}}"/>
                </div>
            </div>
        </div>

        <div class="card card-outline card-info">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-address-book mr-2"></i>Liên hệ</h3></div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-4"><label>Hotline</label><input type="text" name="hotline" class="form-control" value="{{$s('hotline')}}"/></div>
                    <div class="form-group col-md-4"><label>Email</label><input type="text" name="email" class="form-control" value="{{$s('email')}}"/></div>
                    <div class="form-group col-md-4"><label>Địa chỉ</label><input type="text" name="address" class="form-control" value="{{$s('address')}}"/></div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6"><label>Facebook (URL)</label><input type="text" name="facebook" class="form-control" value="{{$s('facebook')}}"/></div>
                    <div class="form-group col-md-6"><label>Zalo (URL/SĐT)</label><input type="text" name="zalo" class="form-control" value="{{$s('zalo')}}"/></div>
                </div>
            </div>
            <div class="card-footer"><button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu cấu hình</button></div>
        </div>
    </form>
</div></div>
