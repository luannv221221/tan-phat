<?php
/**
 * LẬP ĐƠN MUA.
 *
 * Gần giống edit.php và CỐ Ý chép đôi: view ở dự án này nạp bằng
 * file_get_contents() rồi eval(), nên `__DIR__` bên trong trỏ về core/ chứ
 * không phải thư mục view — tách partial rồi include là không tìm thấy file.
 *
 * KHÔNG CÒN Ô SERIAL Ở ĐÂY (05/11/2026). Bản đầu cho serial mọc theo số lượng
 * ngay trong bảng này. Khách xem màn hình rồi yêu cầu đổi:
 *
 *     "Thêm serial theo phiếu mua (chọn phiếu mua) tránh làm từ lúc tạo đơn mua"
 *     "serial cho nhập là kiểu model popup tránh hiển thị nhiều dòng"
 *
 * Khách đúng ở chỗ căn bản: lúc lập đơn hàng chưa về, chưa ai cầm được cái tem
 * để đọc số. Ô serial lúc đó hoặc bỏ trống, hoặc chép từ giấy báo giá — mà số
 * chép sai thì tới lúc bảo hành mới lòi ra.
 *
 * Serial nay khai ở màn riêng: admin/purchase-orders/serial/<id>, mở bằng nút
 * trên danh sách đơn mua và ở màn Sửa đơn.
 */
$gt = function($k, $mac = '') use ($old){ return isset($old[$k]) ? $old[$k] : $mac; };

/* Mặt hàng cho ô chọn — cùng khuôn với báo giá / phiếu nhập */
$dsHang = [];
foreach ((array) $parts as $p){
    $dsHang[] = [
        'id'    => (int) $p['id'],
        'label' => $p['code'] . ' - ' . $p['name'] . (!empty($p['unit_name']) ? ' (' . $p['unit_name'] . ')' : ''),
        'img'   => !empty($p['image']) ? _WEB_URL . '/public/assets/uploads/parts/' . $p['image'] : '',
    ];
}
?>
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
    <i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}
</div>
@endif

<form action="" method="post" enctype="multipart/form-data">
    <?php echo csrf_field(); ?>

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-file-invoice-dollar mr-2"></i>{{$page_name}}</h3>
            <div class="card-tools"><span class="badge badge-light border">Số đơn: {{$nextNo}}</span></div>
        </div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group col-md-5">
                    <label>Nhà cung cấp <span class="text-danger">*</span></label>
                    <select name="partner_id" class="form-control js-search" data-placeholder="Gõ tên hoặc mã để tìm...">
                        <option value="">— Chọn nhà cung cấp —</option>
                        <?php foreach ((array) $suppliers as $s): ?>
                        <option value="<?php echo (int) $s['id']; ?>" <?php echo ($gt('partner_id') == $s['id']) ? 'selected' : ''; ?>>
                            <?php echo e($s['code'] . ' - ' . $s['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    {!! !empty($errors['partner_id'])?'<small class="text-danger">'.e($errors['partner_id']).'</small>':false !!}
                    <small class="form-text text-muted">Khai thêm ở <em>Bán hàng › Đối tượng</em> (chọn vai Nhà cung cấp).</small>
                </div>
                <div class="form-group col-md-3">
                    <label>Ngày mua <span class="text-danger">*</span></label>
                    <input type="date" name="order_date" class="form-control"
                           value="<?php echo e($gt('order_date', date('Y-m-d'))); ?>"/>
                    {!! !empty($errors['order_date'])?'<small class="text-danger">'.e($errors['order_date']).'</small>':false !!}
                </div>
            </div>

            <?php /* HOÁ ĐƠN CỦA NHÀ CUNG CẤP — tài liệu ghi "link hóa đơn" và
                     "upload (hóa đơn)". Hai thứ khác nhau nên hai ô: có nơi gửi
                     link hoá đơn điện tử, có nơi đưa bản chụp. */ ?>
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label>Số hoá đơn NCC</label>
                    <input type="text" name="invoice_no" class="form-control"
                           value="<?php echo e($gt('invoice_no')); ?>" placeholder="VD: 0000123"/>
                </div>
                <div class="form-group col-md-5">
                    <label>Link hoá đơn</label>
                    <input type="text" name="invoice_url" class="form-control"
                           value="<?php echo e($gt('invoice_url')); ?>" placeholder="https://..."/>
                    {!! !empty($errors['invoice_url'])?'<small class="text-danger">'.e($errors['invoice_url']).'</small>':false !!}
                </div>
                <div class="form-group col-md-4">
                    <label>Bản chụp hoá đơn</label>
                    <input type="file" name="invoice_file" class="form-control-file" accept=".pdf,image/*"/>
                    {!! !empty($errors['invoice_file'])?'<small class="text-danger">'.e($errors['invoice_file']).'</small>':false !!}
                    <small class="form-text text-muted">PDF hoặc ảnh, tối đa 8MB.</small>
                </div>
            </div>

            <div class="form-group">
                <label>Ghi chú</label>
                <input type="text" name="note" class="form-control" value="<?php echo e($gt('note')); ?>"/>
            </div>
            <?php /* THUẾ GTGT — tờ hoá đơn đỏ của NCC dán ngay bên trên luôn có
                     con số này. Mặc định 0 chứ không phải 10: nhiều gara mua của
                     hộ kinh doanh không xuất hoá đơn GTGT, đặt sẵn 10% là mỗi đơn
                     phải nhớ xoá đi. */ ?>
            <div class="form-group">
                <label>Thuế GTGT (%)</label>
                <input type="number" name="vat_rate" class="form-control" min="0" max="100" step="0.01"
                       style="max-width:160px" value="<?php echo e($gt('vat_rate', '0')); ?>"/>
                <small class="form-text text-muted">
                    Để 0 nếu nhà cung cấp không xuất hoá đơn GTGT.
                    Thuế <b>không</b> tính vào giá vốn khi nhập kho — nó là khoản được khấu trừ.
                </small>
            </div>
        </div>
    </div>

    <div class="card card-outline card-info">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-boxes mr-2"></i>Hàng hoá</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-sm btn-info" id="them-dong">
                    <i class="fas fa-plus mr-1"></i> Thêm dòng
                </button>
            </div>
        </div>
        <div id="bao-gop" class="alert alert-info py-2 px-3 mb-0 small" style="display:none;border-radius:0" role="status"></div>
        <div class="card-body table-responsive p-0">
            <table class="table table-sm mb-0 bang-dong-mua">
                <thead><tr>
                    <?php /* Ô hàng hoá rộng hẳn ra sau khi bỏ cột serial — khách 05/11/2026:
                             "Tăng chiều dài hàng hóa, icon to hơn". Ảnh khách chụp có dòng
                             "— Chọn hàng ho" bị cắt giữa chữ. */ ?>
                    <th>Hàng hoá</th>
                    <th style="width:110px" class="text-right">Số lượng</th>
                    <th style="width:150px" class="text-right">Đơn giá</th>
                    <th style="width:90px" class="text-right">CK %</th>
                    <th style="width:140px" class="text-right">Thành tiền</th>
                    <th style="width:56px"></th>
                </tr></thead>
                <tbody id="dong-mua"></tbody>
                <tfoot>
                    <tr><th colspan="4" class="text-right font-weight-normal">Cộng chưa thuế</th>
                        <th class="text-right font-weight-normal"><span id="tong-truoc-thue">0</span> đ</th>
                        <th></th></tr>
                    <tr><th colspan="4" class="text-right font-weight-normal">Thuế GTGT (<span id="nhan-vat">0</span>%)</th>
                        <th class="text-right font-weight-normal"><span id="tien-thue">0</span> đ</th>
                        <th></th></tr>
                    <tr><th colspan="4" class="text-right">Tổng cộng</th>
                        <th class="text-right"><span id="tong-tien">0</span> đ</th>
                        <th></th></tr>
                </tfoot>
            </table>
        </div>
        <div class="card-footer text-muted small">
            <i class="fas fa-barcode mr-1"></i>
            <strong>Serial khai sau</strong>, ở màn riêng — lưu đơn xong là có nút
            “Khai serial”. Lúc lập đơn hàng chưa về, chưa ai cầm được cái tem để đọc số.
            <br>
            <strong>Đơn giá</strong> để trống cũng lưu được — NCC chưa báo giá thì sau bổ sung.
            Lúc tạo phiếu nhập kho, dòng đã khai giá lấy đúng giá đó, dòng để trống
            lấy theo <strong>lần nhập gần nhất</strong> của mặt hàng.
        </div>
    </div>

    <div class="card"><div class="card-body">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu (nháp)</button>
        <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i> Quay lại</a>
    </div></div>
</form>

<script>
(function(){
    'use strict';
    var DS = {!! json_encode($dsHang, JSON_HEX_TAG|JSON_UNESCAPED_UNICODE) !!};
    var BAN_DAU = {!! json_encode(array_values((array) $lines), JSON_HEX_TAG|JSON_UNESCAPED_UNICODE) !!};
    var tbody = document.getElementById('dong-mua');
    var oTong = document.getElementById('tong-tien');

    /* SỐ TIỀN ĐỌC TỪ Ô đúng kiểu màn Phiếu nhập kho đang làm: bỏ mọi ký tự
       không phải chữ số. Tiền Việt tính theo đồng, không có phần lẻ. */
    function soTien(v){ return parseInt(String(v || '').replace(/[^\d]/g, ''), 10) || 0; }
    function soLuongO(v){ return parseFloat(String(v || '').replace(/[^\d.]/g, '')) || 0; }

    /* Ô đơn giá TRỐNG khác hẳn đơn giá 0: trống là "NCC chưa báo giá" và luác
       tạo phiếu nhập sẽ rơi về giá nhập gần nhất. Nên thành tiền hiện dấu —
       chứ không hiện số 0, để người nhìn biết là chưa khai. */
    /* PHẦN TRĂM giữ lại dấu chấm (khác soTien ngay trên): "7.5" là con số có
       thật. Kẹp 0–100 giống hệt phía máy chủ — hai nơi phải ra cùng một số,
       nếu không thì màn hình hiện một đằng, CSDL lưu một nẻo. */
    function phanTram(v){
        var n = parseFloat(String(v || '').replace(/[^\d.]/g, '')) || 0;
        return Math.max(0, Math.min(100, n));
    }

    function tinhLai(){
        var t = 0;
        Array.prototype.forEach.call(tbody.querySelectorAll('tr'), function (r){
            var oSl = r.querySelector('.o-sl'), oGia = r.querySelector('.o-gia'),
                oCk = r.querySelector('.o-ck'), oTt = r.querySelector('.o-tt');
            if (!oSl || !oGia || !oTt) return;
            var g  = soTien(oGia.value);
            var ck = oCk ? phanTram(oCk.value) : 0;
            var tt = Math.round(soLuongO(oSl.value) * g * (1 - ck / 100));
            oTt.textContent = g > 0 ? tt.toLocaleString('vi-VN') : '—';
            t += tt;
        });

        /* Làm tròn thuế ĐÚNG CÁCH máy chủ làm (round trên tổng chưa thuế, không
           cộng dồn thuế từng dòng) — lệch cách là màn hình và CSDL chênh nhau
           vài đồng, đủ để người dùng mất tin vào cả con số. */
        var oVat = document.querySelector('[name="vat_rate"]');
        var vat  = oVat ? phanTram(oVat.value) : 0;
        var thue = Math.round(t * vat / 100);

        var oTruoc = document.getElementById('tong-truoc-thue');
        var oThue  = document.getElementById('tien-thue');
        var oNhan  = document.getElementById('nhan-vat');
        if (oTruoc) oTruoc.textContent = t.toLocaleString('vi-VN');
        if (oThue)  oThue.textContent  = thue.toLocaleString('vi-VN');
        if (oNhan)  oNhan.textContent  = String(vat).replace(/\.0+$/, '');
        oTong.textContent = (t + thue).toLocaleString('vi-VN');
    }

    /* Đổi thuế suất thì tính lại ngay, không đợi gõ vào dòng hàng */
    (function(){
        var o = document.querySelector('[name="vat_rate"]');
        if (o) o.addEventListener('input', tinhLai);
    })();

    /* CHỌN TRÙNG MỘT MẶT HÀNG -> CỘNG VÀO SỐ LƯỢNG DÒNG ĐÃ CÓ, KHÔNG ĐẺ DÒNG THỨ HAI.

       Khách 13/11/2026: "chọn 2 sản phẩm giống nhau phải tăng số lượng đấy chứ
       lại thành 2 dòng". Hai dòng cùng một mặt hàng là một đơn mua khó đọc (cùng
       một cái bugi nằm ở hai chỗ) và lúc nhập kho lại sinh hai dòng phiếu cho
       một mặt hàng.

       Dòng vừa chọn (tr) là dòng BỊ GỘP: số lượng của nó cộng vào dòng cũ rồi nó
       biến mất. Không đụng tới dòng cũ nào đã lưu trước khi có luật này — luật
       chỉ chạy khi người dùng ĐỔI ô chọn, còn dòng dựng từ dữ liệu có sẵn không
       đi qua sự kiện đó, nên mở đơn cũ ra không bị dồn dòng sau lưng. Đơn giá /
       chiết khấu giữ của dòng cũ; riêng khi dòng cũ để trống mà dòng mới đã gõ
       thì lấy của dòng mới, để không mất con số người dùng vừa nhập. */
    function gopNeuTrung(tr, sel){
        if (!sel.value) return;
        var dongCu = null, thuTu = 0, i = 0;
        Array.prototype.forEach.call(tbody.querySelectorAll('tr'), function (r){
            i++;
            if (r === tr || dongCu) return;
            var s = r.querySelector('select[name="part[]"]');
            if (s && s.value === sel.value){ dongCu = r; thuTu = i; }
        });
        if (!dongCu) return;

        var oCu = dongCu.querySelector('.o-sl'), oMoi = tr.querySelector('.o-sl');
        var them = soLuongO(oMoi && oMoi.value) || 1;
        var nay  = Math.round((soLuongO(oCu.value) + them) * 1000) / 1000;
        oCu.value = String(nay);

        var gCu = dongCu.querySelector('.o-gia'), gMoi = tr.querySelector('.o-gia');
        if (gCu && gMoi && gCu.value === '' && gMoi.value !== '') gCu.value = gMoi.value;
        var kCu = dongCu.querySelector('.o-ck'), kMoi = tr.querySelector('.o-ck');
        if (kCu && kMoi && kCu.value === '' && kMoi.value !== '') kCu.value = kMoi.value;

        var ten = sel.options[sel.selectedIndex] ? sel.options[sel.selectedIndex].textContent : '';
        tr.remove();
        tinhLai();

        dongCu.style.transition = 'background-color .3s';
        dongCu.style.backgroundColor = '#fff3cd';
        setTimeout(function (){ dongCu.style.backgroundColor = ''; }, 2200);
        if (dongCu.scrollIntoView) dongCu.scrollIntoView({ block: 'nearest' });

        var bao = document.getElementById('bao-gop');
        if (bao){
            bao.textContent = ten + ' đã có ở dòng ' + thuTu + ' — đã cộng thêm ' + them + ' vào số lượng (nay là ' + nay + ').';
            bao.style.display = 'block';
            clearTimeout(bao.__an);
            bao.__an = setTimeout(function (){ bao.style.display = 'none'; }, 6000);
        }
    }

    function oChon(chon){
        var s = document.createElement('select');
        s.name = 'part[]';
        s.className = 'form-control form-control-sm js-search';
        s.setAttribute('data-placeholder', '— Chọn hàng hoá —');
        var o0 = document.createElement('option');
        o0.value = ''; o0.textContent = '— Chọn hàng hoá —';
        s.appendChild(o0);
        DS.forEach(function (op){
            var o = document.createElement('option');
            o.value = op.id; o.textContent = op.label;
            if (op.img) o.setAttribute('data-img', op.img);
            if (String(op.id) === String(chon)) o.selected = true;
            s.appendChild(o);
        });
        return s;
    }

    function themDong(r){
        var tr = document.createElement('tr');

        var td1 = document.createElement('td');
        var sel = oChon(r && r.part_id);
        td1.appendChild(sel);

        var td2 = document.createElement('td');
        var qty = document.createElement('input');
        qty.type = 'number'; qty.min = '0'; qty.step = '1';
        qty.name = 'qty[]';
        qty.className = 'form-control form-control-sm text-right o-sl';
        qty.value = r && r.quantity ? parseFloat(r.quantity) : 1;
        td2.appendChild(qty);

        var tdG = document.createElement('td');
        var gia = document.createElement('input');
        gia.name = 'price[]';
        gia.className = 'form-control form-control-sm text-right o-gia';
        gia.placeholder = 'Chưa báo giá';
        gia.value = (r && parseFloat(r.unit_price) > 0) ? parseInt(r.unit_price, 10) : '';
        /* oTien() biến nó thành ô số không âm, bước 1 đồng, VẪN ĐỂ TRỐNG ĐƯỢC
           — xem so-input.js, file đó nạp trong <head> nên luôn có sẵn. */
        if (window.oTien) window.oTien(gia);
        tdG.appendChild(gia);

        var tdC = document.createElement('td');
        var ck = document.createElement('input');
        ck.type = 'number'; ck.min = '0'; ck.max = '100'; ck.step = '0.01';
        ck.name = 'disc[]';
        ck.className = 'form-control form-control-sm text-right o-ck';
        ck.placeholder = '0';
        /* KHÔNG dùng oTien(): đó là ô tiền (bỏ dấu chấm, bước 1 đồng), gắn vào
           đây thì gõ 7.5% ra 75%. */
        ck.value = (r && parseFloat(r.discount_percent) > 0) ? parseFloat(r.discount_percent) : '';
        tdC.appendChild(ck);

        var tdT = document.createElement('td');
        tdT.className = 'text-right align-middle';
        var oTt = document.createElement('span');
        oTt.className = 'o-tt';
        oTt.textContent = '—';
        tdT.appendChild(oTt);

        var td4 = document.createElement('td');
        var xoa = document.createElement('button');
        xoa.type = 'button';
        xoa.className = 'btn btn-sm btn-outline-danger nut-xoa-dong';
        xoa.innerHTML = '<i class="fas fa-times"></i>';
        xoa.title = 'Xoá dòng';
        xoa.addEventListener('click', function (){ tr.remove(); tinhLai(); });
        td4.appendChild(xoa);

        tr.appendChild(td1); tr.appendChild(td2); tr.appendChild(tdG);
        tr.appendChild(tdC); tr.appendChild(tdT); tr.appendChild(td4);
        tbody.appendChild(tr);

        qty.addEventListener('input', tinhLai);
        gia.addEventListener('input', tinhLai);
        ck.addEventListener('input', tinhLai);
        sel.addEventListener('change', function (){ gopNeuTrung(tr, sel); });
        tinhLai();

        /* Ô chọn mới thêm phải được gắn ô tìm kiếm — admin.js theo dõi DOM nên
           tự lo, nhưng gọi thẳng cho chắc nếu hàm có sẵn. */
        if (window.initSearchSelects) window.initSearchSelects(tr);
    }

    document.getElementById('them-dong').addEventListener('click', function (){ themDong(null); });

    if (BAN_DAU.length) BAN_DAU.forEach(function (r){ themDong(r); });
    else themDong(null);
})();
</script>
