<?php
$v = function($field, $default = '') use ($old, $item){ if (isset($old[$field])) return $old[$field]; return isset($item[$field]) && $item[$field] !== null ? $item[$field] : $default; };
$isPub = isset($old['is_published']) ? !empty($old['is_published']) : ((int) $item['is_published'] === 1);
?>
@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}</div>
@endif

<div class="row">
    <div class="col-lg-5">
        <div class="card card-outline card-warning">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-edit mr-2"></i>Thông tin album</h3></div>
            <form action="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$item['id']}}" method="post" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <div class="card-body">
                    <div class="form-group">
                        <label>Tên album <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{$v('name')}}"/>
                        {!! !empty($errors['name'])?'<small class="text-danger">'.e($errors['name']).'</small>':false !!}
                    </div>
                    <div class="form-group"><label>Slug</label><input type="text" name="slug" class="form-control" value="{{$v('slug')}}"/></div>
                    <div class="form-group"><label>Mô tả</label><textarea name="description" class="form-control" rows="2">{{$v('description')}}</textarea></div>
                    <div class="form-group">
                        <label>Ảnh bìa</label>
                        {!! !empty($item['cover']) ? '<img src="'.e(media_url($item['cover'])).'" style="height:44px;border-radius:4px;display:block;margin-bottom:6px"/>' : '' !!}
                        <input type="file" name="cover_file" accept="image/*" class="form-control-file"/>
                    </div>
                    <div class="form-row align-items-center">
                        <div class="form-group col-6"><label>Thứ tự</label><input type="number" name="sort_order" class="form-control" value="{{$v('sort_order','0')}}"/></div>
                        <div class="form-group col-6 mt-4"><div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" name="is_published" id="is_published" value="1" {{$isPub?'checked':''}}/>
                            <label class="custom-control-label" for="is_published">Đăng</label>
                        </div></div>
                    </div>
                </div>
                <div class="card-footer"><button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu</button> <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default">Về danh sách</a></div>
            </form>
        </div>

        <div class="card card-outline card-info">
            <div class="card-header"><h3 class="card-title">Thêm ảnh</h3></div>
            <form action="{{_WEB_URL.'/admin/'.$routeBase.'/images/'.$item['id']}}" method="post" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <div class="card-body">
                    <input type="file" name="images[]" accept="image/*" multiple class="form-control-file mb-2"/>
                    <button type="submit" class="btn btn-info btn-sm"><i class="fas fa-upload mr-1"></i> Tải ảnh lên</button>
                </div>
            </form>
        </div>

        <div class="card card-outline card-danger">
            <div class="card-header"><h3 class="card-title">Thêm video (YouTube)</h3></div>
            <form action="{{_WEB_URL.'/admin/'.$routeBase.'/add-video/'.$item['id']}}" method="post">
                <?php echo csrf_field(); ?>
                <div class="card-body">
                    <input type="text" name="video_url" class="form-control mb-2" placeholder="https://youtube.com/watch?v=..."/>
                    <input type="text" name="caption" class="form-control mb-2" placeholder="Chú thích (tuỳ chọn)"/>
                    <button type="submit" class="btn btn-danger btn-sm"><i class="fab fa-youtube mr-1"></i> Thêm video</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title">Nội dung album ({{count($items)}})</h3></div>
            <div class="card-body">
                @if (!empty($items))
                <div class="row">
                    @foreach ($items as $it)
                    <?php
                    $isVideo = ($it['media_type'] === 'video');
                    $yt = $isVideo ? youtube_id($it['video_url']) : '';
                    $thumb = $isVideo
                        ? ($yt !== '' ? 'https://img.youtube.com/vi/' . $yt . '/mqdefault.jpg' : '')
                        : media_url($it['image']);
                    ?>
                    <div class="col-md-4 col-6 mb-3">
                        <div style="border:1px solid #e6e6e6;border-radius:6px;overflow:hidden">
                            <div style="aspect-ratio:1/1;background:#fafafa;display:flex;align-items:center;justify-content:center;position:relative">
                                {!! $thumb !== '' ? '<img src="'.e($thumb).'" style="width:100%;height:100%;object-fit:cover"/>' : '<span class="text-muted">?</span>' !!}
                                {!! $isVideo ? '<span style="position:absolute;font-size:26px;color:#fff;text-shadow:0 0 6px #000">▶</span>' : '' !!}
                            </div>
                            <div class="p-1 text-center">
                                @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
                                <a href="{{_WEB_URL.'/admin/'.$routeBase.'/item-delete/'.$it['id']}}" onclick="return confirm('Xoá mục này?')" class="btn btn-outline-danger btn-sm" style="width:100%"><i class="fas fa-trash"></i></a>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-muted mb-0">Chưa có ảnh/video. Thêm ở cột bên trái.</p>
                @endif
            </div>
        </div>
    </div>
</div>
