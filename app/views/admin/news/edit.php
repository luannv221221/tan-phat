<?php
$v = function($field, $default = '') use ($old, $item){ if (isset($old[$field])) return $old[$field]; return isset($item[$field]) && $item[$field] !== null ? $item[$field] : $default; };
$isPub = isset($old['is_published']) ? !empty($old['is_published']) : ((int) $item['is_published'] === 1);
?>
<div class="row"><div class="col-lg-9 mx-auto">
    <div class="card card-outline card-warning">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-edit mr-2"></i>{{$page_name}}</h3></div>
        <form action="" method="post" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            <div class="card-body">
                @if (!empty($msg))
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-1"></i> {{$msg}}</div>
                @endif
                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label>Tiêu đề <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="{{$v('title')}}"/>
                        {!! !empty($errors['title'])?'<small class="text-danger">'.e($errors['title']).'</small>':false !!}
                    </div>
                    <div class="form-group col-md-4">
                        <label>Danh mục</label>
                        <div class="input-group">
                            <select name="category_id" class="form-control">
                                <option value="">— Chọn —</option>
                                @foreach ($categories as $c)
                                <option value="{{$c['id']}}" {{$v('category_id')==$c['id']?'selected':''}}>{{$c['name']}}</option>
                                @endforeach
                            </select>
                            <div class="input-group-append">{!! nut_them_nhanh('news-cat', 'category_id', ['nhan' => 'danh mục bài viết', 'vd' => 'VD: Tin xưởng']) !!}</div>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label>Đường dẫn (slug)</label>
                        <input type="text" name="slug" class="form-control" value="{{$v('slug')}}"/>
                        {!! !empty($errors['slug'])?'<small class="text-danger">'.e($errors['slug']).'</small>':false !!}
                    </div>
                    <div class="form-group col-md-4">
                        <label>Ảnh đại diện (tải lên để thay)</label>
                        {!! !empty($item['thumbnail']) ? '<img src="'.e(media_url($item['thumbnail'])).'" style="height:48px;border-radius:4px;margin-bottom:6px"/>' : '' !!}
                        <input type="file" name="thumbnail_file" accept="image/*" class="form-control-file"/>
                        {!! !empty($errors['thumbnail_file'])?'<small class="text-danger d-block">'.e($errors['thumbnail_file']).'</small>':false !!}
                        <input type="text" name="thumbnail" class="form-control mt-1" placeholder="hoặc URL ảnh" value="{{$v('thumbnail')}}"/>
                    </div>
                </div>
                <div class="form-group">
                    <label>Tóm tắt</label>
                    <textarea name="summary" class="form-control" rows="2">{{$v('summary')}}</textarea>
                </div>
                <div class="form-group">
                    <label>Nội dung</label>
                    <textarea name="content" class="form-control" rows="10">{{$v('content')}}</textarea>
                </div>
                <div class="form-row">
                    <?php /* Textarea — xem lý do ở add.php cùng thư mục. */ ?>
                    <div class="form-group col-md-5"><label class="small text-muted">SEO — Meta title</label><textarea name="meta_title" class="form-control form-control-sm" rows="2">{{$v('meta_title')}}</textarea></div>
                    <div class="form-group col-md-7"><label class="small text-muted">SEO — Meta description</label><textarea name="meta_description" class="form-control form-control-sm" rows="2">{{$v('meta_description')}}</textarea></div>
                </div>
                <div class="form-group">
                    <label class="small text-muted">Từ khoá</label>
                    <input type="text" name="tags" class="form-control form-control-sm"
                           placeholder="ngăn bằng dấu phẩy — vd: lọc gió, bảo dưỡng, Toyota"
                           value="{{$v('tags')}}"/>
                </div>
                <div class="form-group mb-0"><div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" name="is_published" id="is_published" value="1" {{$isPub?'checked':''}}/>
                    <label class="custom-control-label" for="is_published">Đăng</label>
                </div></div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu</button>
                @if (route('admin/'.$routeBase.'/delete/'.$item['id']))
                <a href="{{_WEB_URL.'/admin/'.$routeBase.'/delete/'.$item['id']}}" onclick="return confirm('Xoá bài viết này?')" class="btn btn-outline-danger"><i class="fas fa-trash mr-1"></i> Xoá</a>
                @endif
                <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default">Quay lại</a>
            </div>
        </form>
    </div>
</div></div>
<script src="{{asset('public/assets/js/seo-slug.js')}}"></script>
<script src="{{asset('public/assets/js/soan-thao.js')}}"></script>
<script>
/* Ô nội dung thành trình soạn thảo có định dạng; hai ô meta có thanh màu.
   Mức 50–70 và 120–160 là khoảng Google hay cắt chuỗi — hai số khách ghi
   trong tài liệu (65–70 và 150) nằm gọn bên trong. */
(function(){
    if (window.oSoanThao) window.oSoanThao(document.querySelector('[name="content"]'));
    if (window.oDoDaiSeo){
        window.oDoDaiSeo(document.querySelector('[name="meta_title"]'), 50, 70);
        window.oDoDaiSeo(document.querySelector('[name="meta_description"]'), 120, 160);
    }
})();
</script>
<script src="{{asset('public/assets/js/them-nhanh.js')}}"></script>
