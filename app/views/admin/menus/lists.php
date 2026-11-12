<?php
/**
 * MENU WEBSITE — CÂY BÊN TRÁI, Ô SỬA BÊN PHẢI.
 *
 * Dựng lại 12/11/2026 theo màn hình bên khách đang dùng (ảnh buổi họp 11/11).
 * Lấy CÁCH LÀM VIỆC của họ, không lấy danh sách ô của họ:
 *
 *   LẤY — cây có thụt cấp, kéo thả đổi thứ tự, bấm một mục là sửa ngay bên
 *         phải. Một menu có 15-30 mục và người ta chỉnh chúng CÙNG LÚC, so
 *         với nhau; mỗi mục một lần tải trang là hỏng mạch việc.
 *
 *   BỎ  — Menu Class / Submenu Class / Sticker / Layout / Menu Type.
 *         Mấy ô đó là của một bộ dựng giao diện: mỗi mục menu tự chọn kiểu
 *         đổ ra (gallery, mega menu...) và tự gắn lớp CSS. Trang khách ở đây
 *         có đúng một giao diện, nên thêm vào là thêm ô không ai điền, mà ô
 *         trống thì lần sau không ai dám xoá vì không biết nó dùng làm gì.
 *
 * BỎ PHÂN TRANG — đây không phải sửa cho đẹp mà là SỬA LỖI. Cây menu bị cắt
 * trang thì mục con rơi sang trang sau, đứng tách khỏi cha nó: nhìn vào
 * không ra cấu trúc, mà cấu trúc chính là thứ màn này tồn tại để sửa.
 */
?>
@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}</div>
@endif

<div class="row">

    <!-- ================= CÂY MENU ================= -->
    <div class="col-lg-7">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-bars mr-2"></i>{{$page_name}}</h3>
                @if (route('admin/'.$routeBase.'/add'))
                <div class="card-tools">
                    <button type="button" id="nut-them-moi" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus mr-1"></i> Thêm {{$labelOne}}
                    </button>
                </div>
                @endif
            </div>

            <?php /* Thanh này chỉ hiện SAU khi kéo thả. Lưu bằng một nút bấm
                     chứ không tự lưu mỗi lần thả: kéo nhầm thì chỉ cần tải lại
                     trang là về như cũ, không phải kéo ngược lại cho đúng. */ ?>
            <div id="thanh-thu-tu" class="alert alert-warning mb-0 py-2 d-none">
                <form action="{{_WEB_URL.'/admin/'.$routeBase.'/sap-xep'}}" method="post" class="d-inline">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="thuTu" id="o-thu-tu" value=""/>
                    <i class="fas fa-arrows-alt-v mr-1"></i> Thứ tự đã thay đổi.
                    <button type="submit" class="btn btn-warning btn-sm ml-2"><i class="fas fa-save mr-1"></i> Lưu thứ tự</button>
                    <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default btn-sm">Huỷ</a>
                </form>
            </div>

            <div class="card-body p-0">
                @if (!empty($dataList))
                <ul class="list-group list-group-flush" id="cay-menu">
                    @foreach ($dataList as $item)
                    <?php $sau = (int) $item['depth'] > 0; ?>
                    <li class="list-group-item py-2 muc-menu"
                        draggable="true"
                        data-id="{{$item['id']}}"
                        data-cap="{{(int)$item['depth']}}"
                        data-parent="{{!empty($item['parent_id']) ? $item['parent_id'] : ''}}"
                        data-label="{{$item['label']}}"
                        data-link_type="{{$item['link_type']}}"
                        data-target_id="{{!empty($item['target_id']) ? $item['target_id'] : ''}}"
                        data-url="{{!empty($item['url']) ? $item['url'] : ''}}"
                        data-target="{{$item['target']}}"
                        data-sort_order="{{$item['sort_order']}}"
                        data-status="{{$item['status']}}"
                        style="cursor:grab; padding-left:{{12 + (int)$item['depth']*28}}px">

                        <div class="d-flex align-items-center">
                            <i class="fas fa-grip-vertical text-muted mr-2" title="Kéo để đổi thứ tự / đổi cấp"></i>
                            {!! $sau ? '<i class="fas fa-level-up-alt fa-rotate-90 text-muted mr-1"></i>' : '' !!}
                            <b class="mr-2">{{$item['label']}}</b>
                            <code class="text-muted small mr-2">{{$item['nhan_dich']}}</code>
                            {!! $item['target']==='_blank' ? '<span class="badge badge-light mr-1">tab mới</span>' : '' !!}
                            {!! $item['status']==1 ? '<span class="badge badge-success">Bật</span>' : '<span class="badge badge-secondary">Ẩn</span>' !!}

                            <span class="ml-auto">
                                @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
                                <button type="button" class="btn btn-warning btn-sm nut-sua" title="Sửa"><i class="fas fa-edit"></i></button>
                                @endif
                                @if (route('admin/'.$routeBase.'/delete/'.$item['id']))
                                <a href="{{_WEB_URL.'/admin/'.$routeBase.'/delete/'.$item['id']}}"
                                   onclick="return confirm('Xoá mục này (và mục con)?')"
                                   class="btn btn-danger btn-sm" title="Xoá"><i class="fas fa-trash"></i></a>
                                @endif
                            </span>
                        </div>
                    </li>
                    @endforeach
                </ul>
                @else
                <div class="text-center text-muted py-5"><i class="fas fa-inbox fa-2x d-block mb-2"></i> Chưa có mục menu</div>
                @endif
            </div>
            <div class="card-footer text-muted small py-2">
                <i class="fas fa-info-circle mr-1"></i>
                Kéo một mục thả vào <b>ngay dưới một mục gốc</b> để biến nó thành mục con. Menu website chỉ hai cấp.
            </div>
        </div>
    </div>

    <!-- ================= Ô SỬA ================= -->
    <div class="col-lg-5">
        <div class="card card-outline card-secondary" style="position:sticky; top:12px">
            <div class="card-header"><h3 class="card-title" id="tieu-de-form"><i class="fas fa-plus-circle mr-2"></i>Thêm {{$labelOne}}</h3></div>

            <?php /* MỘT FORM DUY NHẤT cho cả thêm lẫn sửa — JS chỉ đổi `action`.
                     Hai form riêng thì mỗi lần đổi mục phải đồng bộ hai nơi, và
                     sớm muộn một bên thiếu một ô. */ ?>
            <form action="{{_WEB_URL.'/admin/'.$routeBase.'/add'}}" method="post" id="f-menu">
                <?php echo csrf_field(); ?>
                <div class="card-body">
                    @if (!empty($errors))
                    <div class="alert alert-danger py-2"><i class="fas fa-exclamation-circle mr-1"></i> Vui lòng kiểm tra lại các ô bên dưới</div>
                    @endif

                    <div class="form-group">
                        <label>Nhãn hiển thị <span class="text-danger">*</span></label>
                        <input type="text" name="label" id="o-label" class="form-control" required
                               value="{{!empty($old['label'])?$old['label']:''}}"/>
                        {!! !empty($errors['label'])?'<small class="text-danger">'.e($errors['label']).'</small>':false !!}
                    </div>

                    <?php /* KIỂU LIÊN KẾT — tài liệu khách 05/11/2026, Frontend mục 6
                             ("quan trọng nhất"). Chọn đích thay vì gõ đường dẫn: gõ tay thì
                             đổi đường dẫn một danh mục là menu trỏ vào trang 404, lặng lẽ. */ ?>
                    <div class="form-group">
                        <label>Kiểu liên kết <span class="text-danger">*</span></label>
                        <select name="link_type" id="o-kieu-lien-ket" class="form-control">
                            @foreach ($kieuDs as $ma => $ten)
                            <option value="{{$ma}}">{{$ten}}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" data-kieu="url">
                        <label>Đường dẫn (URL)</label>
                        <input type="text" name="url" class="form-control" placeholder="vd: san-pham hoặc https://..."/>
                        <small class="form-text text-muted">Bỏ trống = trang chủ. Đường dẫn nội bộ không cần domain.</small>
                    </div>

                    <?php /* Ba ô chọn đích dùng CHUNG một tên `target_id`. Chỉ ô ứng với kiểu
                             đang chọn được bật; hai ô kia bị `disabled` nên KHÔNG được gửi lên.
                             Ẩn bằng CSS không đủ: ô ẩn vẫn nằm trong form và vẫn gửi giá trị,
                             ba ô cùng tên thì máy chủ nhận đúng ô cuối cùng — sai đích. */ ?>
                    <div class="form-group" data-kieu="news_cat">
                        <label>Danh mục bài viết</label>
                        <select name="target_id" class="form-control" disabled>
                            <option value="">— Chọn danh mục —</option>
                            @foreach ($dsTin as $c)
                            <option value="{{$c['id']}}">{{$c['name']}}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" data-kieu="part_cat">
                        <label>Danh mục sản phẩm</label>
                        <select name="target_id" class="form-control" disabled>
                            <option value="">— Chọn danh mục —</option>
                            @foreach ($dsSanPham as $c)
                            <option value="{{$c['id']}}">{{$c['name']}}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" data-kieu="gallery">
                        <label>Thư viện ảnh / video</label>
                        <select name="target_id" class="form-control" disabled>
                            <option value="">— Chọn album —</option>
                            @foreach ($dsThuVien as $c)
                            <option value="{{$c['id']}}">{{$c['name']}}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Menu cha</label>
                            <select name="parent_id" id="o-parent" class="form-control">
                                <option value="">— Menu gốc —</option>
                                @foreach ($roots as $r)
                                <option value="{{$r['id']}}">{{$r['label']}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Mở trong</label>
                            <select name="target" id="o-target" class="form-control">
                                <option value="_self">Cùng tab</option>
                                <option value="_blank">Tab mới</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row align-items-center">
                        <div class="form-group col-md-5"><label>Thứ tự</label>
                            <input type="number" name="sort_order" id="o-sort" class="form-control" value="0"/></div>
                        <div class="form-group col-md-7 mt-4"><div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" name="status" id="o-status" value="1" checked/>
                            <label class="custom-control-label" for="o-status">Bật</label>
                        </div></div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary" id="nut-luu"><i class="fas fa-save mr-1"></i> Thêm</button>
                    <button type="button" class="btn btn-default d-none" id="nut-thoi">Thôi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="{{asset('public/assets/js/menu-cay.js')}}"></script>
<script>
(function(){
    if (!window.oCayMenu) return;
    window.oCayMenu({
        goc:   '{{_WEB_URL}}/admin/{{$routeBase}}',
        nhan:  '{{$labelOne}}'
    });
})();
</script>
