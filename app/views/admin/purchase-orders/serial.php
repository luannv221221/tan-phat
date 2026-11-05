<?php
/**
 * KHAI SERIAL CHO MỘT ĐƠN MUA — tài liệu khách 05/11/2026.
 *
 *   CRM mục 1: "serial cho nhập là kiểu model popup tránh hiển thị nhiều dòng"
 *   CRM mục 2: "Thêm serial theo phiếu mua (chọn phiếu mua) tránh làm từ lúc
 *               tạo đơn mua"
 *
 * Bảng ngoài chỉ hiện ĐẾM ĐƯỢC BAO NHIÊU; gõ tem thì mở popup. Đơn 10 dòng ×
 * 5 chiếc là 50 ô — trải hết ra màn hình thì cuộn mãi không hết, mà mỗi lần
 * chỉ khai một mặt hàng.
 *
 * MỘT FORM DUY NHẤT bọc cả bảng lẫn popup. Mỗi popup một form riêng thì bấm
 * Lưu ở popup này là mất phần vừa gõ ở popup kia — người dùng không có cách
 * nào biết trước.
 */
$daNhap = !empty($item['goods_receipt_id']);
?>
<div class="row"><div class="col-12">

    @if (!empty($msg))
    <div class="alert alert-success"><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
    @endif
    @if (!empty($msgError))
    <div class="alert alert-warning"><i class="fas fa-exclamation-triangle mr-1"></i> {{$msgError}}</div>
    @endif

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-barcode mr-2"></i>{{$page_name}}</h3>
            <div class="card-tools">
                <a href="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$item['id']}}" class="btn btn-sm btn-default">
                    <i class="fas fa-file-invoice mr-1"></i> Xem đơn
                </a>
            </div>
        </div>

        <div class="card-body pb-0">
            <dl class="row mb-0">
                <dt class="col-sm-2">Nhà cung cấp</dt>
                <dd class="col-sm-4">{{$item['partner_name']}}</dd>
                <dt class="col-sm-2">Ngày mua</dt>
                <dd class="col-sm-4">{{$item['order_date']}}</dd>
            </dl>
        </div>

        <form action="" method="post">
            <?php echo csrf_field(); ?>

            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr>
                        <th>Hàng hoá</th>
                        <th style="width:110px" class="text-right">Số lượng</th>
                        <th style="width:130px" class="text-center">Đã khai</th>
                        <th style="width:150px" class="text-center">Khai serial</th>
                    </tr></thead>
                    <tbody>
                    @foreach ($lines as $d)
                        <?php
                        $poi  = (int) $d['id'];
                        $sl   = (int) round((float) $d['quantity']);
                        $daCo = isset($serials[$poi]) ? $serials[$poi] : [];
                        $dem  = count($daCo);
                        ?>
                        <tr>
                            <td>
                                <b>{{$d['part_name']}}</b>
                                <div class="small text-muted">{{$d['part_code']}}</div>
                            </td>
                            <td class="text-right align-middle">{{$sl}}</td>
                            <td class="text-center align-middle">
                                <?php /* Đếm so với số lượng, không chỉ đếm trơn: "3" một mình
                                         không nói được là xong hay còn thiếu. */ ?>
                                {!! $dem === 0
                                    ? '<span class="badge badge-secondary">chưa khai</span>'
                                    : ($dem >= $sl
                                        ? '<span class="badge badge-success">' . $dem . '/' . $sl . '</span>'
                                        : '<span class="badge badge-warning">' . $dem . '/' . $sl . '</span>') !!}
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                        data-toggle="modal" data-target="#sn-{{$poi}}">
                                    <i class="fas fa-keyboard mr-1"></i> Nhập tem
                                </button>
                            </td>
                        </tr>
                    @endforeach
                    @if (empty($lines))
                        <tr><td colspan="4" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                            Đơn này chưa có dòng hàng nào.
                        </td></tr>
                    @endif
                    </tbody>
                </table>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu serial</button>
                <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default">
                    <i class="fas fa-arrow-left mr-1"></i> Quay lại
                </a>
                <span class="text-muted small ml-2">
                    Serial <strong>không được trùng</strong> trong cùng một gara.
                    Chưa có tem thì để trống — lưu được, bổ sung sau.
                    @if ($daNhap)
                        Đơn này đã nhập kho, nhưng vẫn khai serial được.
                    @endif
                </span>
            </div>

            <?php /* POPUP cho từng dòng. Nằm TRONG form để ô nhập được gửi lên
                     cùng nút Lưu; Bootstrap giữ nguyên DOM khi mở popup nên
                     không có chuyện ô bị nhấc ra khỏi form. */ ?>
            @foreach ($lines as $d)
                <?php
                $poi  = (int) $d['id'];
                $sl   = (int) round((float) $d['quantity']);
                $daCo = isset($serials[$poi]) ? $serials[$poi] : [];
                ?>
                <div class="modal fade" id="sn-{{$poi}}" tabindex="-1" role="dialog">
                    <div class="modal-dialog modal-dialog-scrollable" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="fas fa-barcode mr-1"></i> {{$d['part_name']}}
                                </h5>
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted small">
                                    Số lượng <strong>{{$sl}}</strong> — mỗi chiếc một dòng.
                                    Dùng máy quét thì quét xong ô này nó tự sang ô dưới.
                                </p>
                                @for ($i = 0; $i < $sl; $i++)
                                <div class="input-group input-group-sm mb-1">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">{{$i + 1}}</span>
                                    </div>
                                    <input type="text" name="serial[{{$poi}}][]"
                                           class="form-control o-sn"
                                           placeholder="Số serial chiếc thứ {{$i + 1}}"
                                           value="{{isset($daCo[$i]) ? $daCo[$i] : ''}}"/>
                                </div>
                                @endfor
                                @if ($sl === 0)
                                <div class="alert alert-warning mb-0">
                                    Dòng này số lượng 0 — sửa số lượng ở màn đơn mua trước đã.
                                </div>
                                @endif
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Xong</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </form>
    </div>
</div></div>

<script>
/* MÁY QUÉT MÃ VẠCH gửi nội dung rồi gửi luôn phím Enter. Trong một form,
   Enter = gửi form — quét cái tem đầu tiên là trang nhảy đi mất, 19 ô còn lại
   bỏ trống. Nên Enter ở ô serial được đổi thành "nhảy xuống ô dưới", đúng
   việc người dùng đang muốn làm. */
document.addEventListener('keydown', function (e){
    if (e.key !== 'Enter') return;
    var o = e.target;
    if (!o || !o.classList || !o.classList.contains('o-sn')) return;

    e.preventDefault();
    var trong = o.closest('.modal-body');
    if (!trong) return;
    var ds = Array.prototype.slice.call(trong.querySelectorAll('.o-sn'));
    var k  = ds.indexOf(o);
    if (k > -1 && ds[k + 1]) ds[k + 1].focus();
    else o.blur();
});

/* Mở popup thì đặt con trỏ vào ô TRỐNG ĐẦU TIÊN, không phải ô số 1: khai tiếp
   một dòng đã khai dở mà phải tự bấm xuống ô thứ 7 là thừa một thao tác mỗi lần.

   PHẢI GẮN BẰNG jQUERY. `shown.bs.modal` là sự kiện Bootstrap bắn ra qua
   jQuery, không phải sự kiện DOM thật — addEventListener không nghe thấy nó.
   Và phải chờ DOMContentLoaded: view chạy giữa <body>, lúc đó thẻ <script>
   nạp jQuery ở cuối trang còn chưa tới lượt. */
document.addEventListener('DOMContentLoaded', function (){
    if (!window.jQuery) return;
    window.jQuery(document).on('shown.bs.modal', '.modal', function (){
        var ds = this.querySelectorAll('.o-sn');
        for (var i = 0; i < ds.length; i++){
            if (!ds[i].value){ ds[i].focus(); return; }
        }
        if (ds.length) ds[ds.length - 1].focus();
    });
});
</script>
