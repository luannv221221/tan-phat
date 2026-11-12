@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}</div>
@endif

<?php
/* Mỗi lối tắt là một ô tick; bộ đếm và việc khoá các ô còn lại khi đủ số lượng
   do JS ở cuối trang lo. JS chỉ là tiện — máy chủ vẫn từ chối quá số lượng
   (Loitat::postLuu), nên tắt JS thì chỉ mất sự tiện chứ không mất an toàn. */
?>
<div class="row justify-content-center"><div class="col-lg-9">

    <form action="{{_WEB_URL.'/admin/loi-tat'}}" method="post" id="f-loi-tat">
        <?php echo csrf_field(); ?>

        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-star mr-2"></i>{{$page_name}}</h3>
                <div class="card-tools">
                    <span class="badge badge-primary" style="font-size:13px">
                        Đã chọn <b id="dem-da-chon">{{count($dangGhim)}}</b> / {{$toiDa}}
                    </span>
                </div>
            </div>

            <div class="card-body">
                <p class="text-muted">
                    Những nút bạn tích sẽ hiện ở <b>thanh trên cùng</b>, cạnh tên trang, để bấm một cái là tới.
                    Chỉ hiện những màn bạn có quyền vào. Mỗi người tự đặt cho tài khoản của mình —
                    không ảnh hưởng tới ai khác.
                    @if (empty($daTuyChinh))
                    <br><i class="fas fa-info-circle mr-1"></i>
                    Bạn chưa tuỳ chỉnh nên đang dùng bộ mặc định: <b>Bán hàng, Mua hàng, Chăm sóc</b>.
                    @endif
                </p>

                @foreach ($nhom as $tenNhom => $muc)
                <h6 class="mt-3 mb-2 text-uppercase text-muted" style="font-size:12px; letter-spacing:.04em">{{$tenNhom}}</h6>
                <div class="form-row">
                    @foreach ($muc as $khoa => $tt)
                    <div class="col-md-6 mb-1">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input o-loi-tat" name="loi_tat[]"
                                   id="lt-{{md5($khoa)}}" value="{{$khoa}}" {{isset($dangGhim[$khoa]) ? 'checked' : ''}}/>
                            <label class="custom-control-label" for="lt-{{md5($khoa)}}">
                                {!! icon($tt['icon']) !!}
                                {{$tt['dai']}}
                                @if ($tt['ngan'] !== $tt['dai'])
                                <small class="text-muted">· hiện là “{{$tt['ngan']}}”</small>
                                @endif
                            </label>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endforeach
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu lối tắt</button>
                <a href="{{_WEB_URL.'/admin'}}" class="btn btn-default">Quay lại</a>
            </div>
        </div>
    </form>

    <?php /* Form RIÊNG cho "về mặc định": lồng hai form vào nhau là sai chuẩn
             HTML, và gộp chung một form thì nút này gửi theo cả các ô đang tích. */ ?>
    <form action="{{_WEB_URL.'/admin/loi-tat/mac-dinh'}}" method="post" class="text-right"
          onsubmit="return confirm('Về bộ lối tắt mặc định (Bán hàng, Mua hàng, Chăm sóc)?')">
        <?php echo csrf_field(); ?>
        <button type="submit" class="btn btn-link btn-sm text-muted"><i class="fas fa-undo mr-1"></i> Về bộ mặc định</button>
    </form>
</div></div>

<script>
/* Bộ đếm "x / N" và khoá các ô còn lại khi đủ số lượng. Chỉ để tiện: máy chủ
   vẫn là bên từ chối quá số lượng. */
(function(){
    var toiDa = {{$toiDa}};
    var cac   = document.querySelectorAll('.o-loi-tat');
    var dem   = document.getElementById('dem-da-chon');

    function capNhat(){
        var n = 0;
        Array.prototype.forEach.call(cac, function(o){ if (o.checked) n++; });
        if (dem) dem.textContent = n;
        Array.prototype.forEach.call(cac, function(o){
            /* Chỉ khoá ô CHƯA tích. Ô đã tích luôn bấm bỏ được, nếu không thì
               đã đủ số lượng là không còn cách nào bỏ bớt để chọn cái khác. */
            o.disabled = !o.checked && n >= toiDa;
        });
    }
    Array.prototype.forEach.call(cac, function(o){ o.addEventListener('change', capNhat); });
    capNhat();
})();
</script>
