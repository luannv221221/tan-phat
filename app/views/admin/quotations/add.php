<?php
/* ---------------------------------------------------------------------------
 * Dòng báo giá tách làm HAI TAB: Hàng hoá và Dịch vụ.
 *
 * Cùng đổ xuống bảng `quotation_items` như trước, chỉ khác chỗ nhập. Ô của
 * tab dịch vụ mang tiền tố `sv_` thay vì `line_` — hai bảng dùng chung một
 * tên ô thì thứ tự phần tử phụ thuộc thứ tự DOM, đổi chỗ tab một cái là số
 * lượng nhảy sang mặt hàng khác mà chẳng có lỗi nào báo.
 *
 * Tổng cộng = tiền hàng hoá + tiền dịch vụ, rồi mới tính thuế trên tổng đó.
 * --------------------------------------------------------------------------- */

/* Chia mặt hàng về đúng tab. Ô chọn của tab Dịch vụ CHỈ có dịch vụ và ngược
   lại — để trộn lẫn thì người lập báo giá gõ "thay dầu" ở tab Hàng hoá vẫn ra.

   Làm việc này HAI LẦN, một lần cho mỗi nguồn danh mục:
     tổng  — hàng chung của Tân Phát, giá gốc
     gara  — hàng riêng + hàng tổng gara đã chọn, giá đã áp bảng giá riêng
   Người lập báo giá bấm nút để đổi qua lại; đổi nguồn chỉ đổi DANH SÁCH GỢI Ý,
   dòng đã chọn giữ nguyên. */
$chiaTab = function (array $ds){
    $hang = $dv = [];
    foreach ($ds as $p){
        $row = [
            'id'    => (int) $p['id'],
            'label' => $p['code'] . ' - ' . $p['name'] . (!empty($p['unit_name']) ? ' (' . $p['unit_name'] . ')' : ''),
            'price' => (int) (!empty($p['sale_price']) ? $p['sale_price'] : $p['price']),
        ];
        if ($p['item_type'] === PartsModel::LOAI_DICH_VU) $dv[]   = $row;
        else                                              $hang[] = $row;
    }
    return ['hang' => $hang, 'dichvu' => $dv];
};

$nguonTong = $chiaTab($partsTong);
$nguonGara = $chiaTab($partsGara);

/* Tên MỌI mặt hàng của cả hai nguồn. Cần để giữ lại dòng đã chọn khi người
   dùng đổi sang nguồn không có mặt hàng đó — nếu không thì ô chọn hoá trống và
   bấm Lưu là mất dòng, chẳng có lỗi nào báo. */
$tenHang = [];
foreach ($parts as $p){
    $tenHang[(int) $p['id']] = $p['code'] . ' - ' . $p['name'];
}

// Nguồn mặc định: có danh mục gara thì dùng nó, chưa cấu hình thì về kho tổng.
$nguonMacDinh = !empty($partsGara) ? 'gara' : 'tong';

// Hai biến dưới giữ nguyên tên cũ để phần còn lại của file không phải sửa.
$hangJs   = $nguonMacDinh === 'gara' ? $nguonGara['hang']   : $nguonTong['hang'];
$dichVuJs = $nguonMacDinh === 'gara' ? $nguonGara['dichvu'] : $nguonTong['dichvu'];

// Dòng người dùng vừa nhập (form quay lại vì lỗi) — đọc theo tiền tố của tab.
$doiDong = function($tienTo) use ($old){
    $rows = [];
    if (empty($old[$tienTo . 'part']) || !is_array($old[$tienTo . 'part'])) return $rows;
    foreach ($old[$tienTo . 'part'] as $i => $p){
        $rows[] = [
            'part_id' => (int) $p,
            'qty'     => isset($old[$tienTo . 'qty'][$i])   ? $old[$tienTo . 'qty'][$i]   : '',
            'price'   => isset($old[$tienTo . 'price'][$i]) ? $old[$tienTo . 'price'][$i] : '',
            'disc'    => isset($old[$tienTo . 'disc'][$i])  ? $old[$tienTo . 'disc'][$i]  : '',
            'note'    => isset($old[$tienTo . 'note'][$i])  ? $old[$tienTo . 'note'][$i]  : '',
        ];
    }
    return $rows;
};
$initHang   = $doiDong('line_');
$initDichVu = $doiDong('sv_');

$vatInit = isset($old['vat_rate']) ? $old['vat_rate'] : '10';

// Cấu hình 2 tab — dùng chung cho cả phần tab lẫn phần bảng bên dưới.
$tabs = [
    ['ma' => 'hang',   'nhan' => 'Hàng hoá', 'cot' => 'Hàng hoá', 'icon' => 'fa-boxes'],
    ['ma' => 'dichvu', 'nhan' => 'Dịch vụ',  'cot' => 'Dịch vụ',  'icon' => 'fa-screwdriver-wrench'],
];
?>
<form action="" method="post">
    <?php echo csrf_field(); ?>
    @if (!empty($msg))
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-1"></i> {{$msg}}</div>
    @endif

    <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-file-invoice-dollar mr-2"></i>{{$page_name}}</h3></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label>Ngày <span class="text-danger">*</span></label>
                    <input type="date" name="quote_date" class="form-control" value="{{!empty($old['quote_date'])?$old['quote_date']:$today}}"/>
                    {!! !empty($errors['quote_date'])?'<small class="text-danger">'.e($errors['quote_date']).'</small>':false !!}
                </div>
                <div class="form-group col-md-3">
                    <label>Hiệu lực đến</label>
                    <input type="date" name="valid_until" class="form-control" value="{{!empty($old['valid_until'])?$old['valid_until']:''}}"/>
                </div>
                <div class="form-group col-md-2">
                    <label>Thuế GTGT (%)</label>
                    <input type="number" min="0" max="100" step="any" name="vat_rate" id="vat_rate" class="form-control text-right" value="{{$vatInit}}"/>
                </div>
                <div class="form-group col-md-4">
                    <label>Khách hàng</label>
                    <select name="customer_id" class="form-control js-search" data-placeholder="Gõ tên hoặc mã để tìm...">
                        <option value="">— Chọn / vãng lai —</option>
                        @foreach ($partners as $pn)
                        <option value="{{$pn['id']}}" {{(!empty($old['customer_id']) && $old['customer_id']==$pn['id'])?'selected':''}}>{{$pn['code'].' - '.$pn['name']}}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <?php /* Nút "Chép từ báo giá cũ".
             Gara hay lặp lại đúng một đơn — chi nhánh mới mở đặt y hệt chi
             nhánh cũ, khách cũ đặt lại combo bảo dưỡng. Gõ lại từng dòng là
             việc thừa. Đặt NGAY TRÊN bảng dòng hàng vì nó thay cho việc nhập
             tay ở chính bảng đó. */ ?>
    <div class="d-flex justify-content-end mb-2">
        <button type="button" class="btn btn-outline-info btn-sm" id="btn-chep">
            <i class="fas fa-copy mr-1"></i> Chép từ báo giá cũ
        </button>
    </div>

    <?php /* -----------------------------------------------------------------
       CHỌN NGUỒN DANH MỤC

       Đặt NGAY TRÊN bảng dòng hàng chứ không ở đầu phiếu: nó quyết định ô chọn
       bên dưới có gì, nên phải nằm cạnh thứ nó ảnh hưởng.

       Ẩn hẳn khi gara chưa dựng danh mục riêng — một nút chọn giữa "kho tổng"
       và một danh sách rỗng chỉ làm người dùng bối rối.
       ----------------------------------------------------------------- */ ?>
    <?php if (!empty($partsGara)): ?>
    <div class="card card-outline card-secondary mb-2">
        <div class="card-body py-2 d-flex align-items-center flex-wrap" style="gap:.75rem">
            <span class="text-muted"><i class="fas fa-list-ul mr-1"></i> Lấy hàng từ:</span>

            <div class="btn-group btn-group-sm" role="group" id="chon-nguon">
                <button type="button" class="btn btn-outline-primary <?php echo $nguonMacDinh === 'gara' ? 'active' : ''; ?>"
                        data-nguon="gara">
                    <i class="fas fa-map-pin mr-1"></i>
                    <?php echo e(!empty($garaCuaPhieu['name']) ? $garaCuaPhieu['name'] : 'Gara hiện tại'); ?>
                    <span class="badge badge-light ml-1"><?php echo count($partsGara); ?></span>
                </button>
                <button type="button" class="btn btn-outline-primary <?php echo $nguonMacDinh === 'tong' ? 'active' : ''; ?>"
                        data-nguon="tong">
                    <i class="fas fa-warehouse mr-1"></i> Kho tổng
                    <span class="badge badge-light ml-1"><?php echo count($partsTong); ?></span>
                </button>
            </div>

            <span class="text-muted small" id="nguon-mo-ta"></span>
        </div>
    </div>
    <?php endif; ?>

    <div class="card card-outline card-info">
        <div class="card-header p-0 pt-1 border-bottom-0">
            <ul class="nav nav-tabs" id="line-tabs">
                <?php foreach ($tabs as $i => $t): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $i === 0 ? 'active' : ''; ?>" href="#"
                       data-pane="<?php echo e($t['ma']); ?>">
                        <i class="fas <?php echo e($t['icon']); ?> mr-1"></i><?php echo e($t['nhan']); ?>
                        <span class="badge badge-secondary ml-1" id="dem-<?php echo e($t['ma']); ?>">0</span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <?php foreach ($tabs as $i => $t): ?>
        <div class="pane-dong" id="pane-<?php echo e($t['ma']); ?>" <?php echo $i === 0 ? '' : 'style="display:none"'; ?>>
            <div class="card-body py-2 border-bottom text-right">
                <button type="button" class="btn btn-sm btn-info" id="add-<?php echo e($t['ma']); ?>">
                    <i class="fas fa-plus mr-1"></i> Thêm dòng <?php echo e(mb_strtolower($t['nhan'], 'UTF-8')); ?>
                </button>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-sm mb-0">
                    <thead><tr>
                        <th style="width:30%"><?php echo e($t['cot']); ?></th>
                        <th style="width:11%" class="text-right">Số lượng</th>
                        <th style="width:15%" class="text-right">Đơn giá</th>
                        <th style="width:9%" class="text-right">CK %</th>
                        <th style="width:15%" class="text-right">Thành tiền</th>
                        <th>Ghi chú</th>
                        <th style="width:44px"></th>
                    </tr></thead>
                    <tbody id="lines-<?php echo e($t['ma']); ?>"></tbody>
                    <?php /* Tổng tiền đặt trong <tfoot> của CHÍNH bảng dòng hàng, giống
                             hoá đơn / phiếu nhập / phiếu xuất — cột tiền nhờ thế thẳng
                             hàng với cột "Thành tiền" ở trên.

                             Bản đầu tiên để riêng một .card-footer với
                             <div class="col-md-6 offset-md-6">: offset chừa trống nửa
                             trái, đo được 496 x 236 px nền trắng trơn ngay dưới bảng,
                             nhìn như nội dung nạp hỏng.

                             Vẽ ở CẢ HAI tab (vòng lặp này chạy 2 lần) nên đang mở tab
                             nào cũng thấy đủ tổng — vì vậy dùng class chứ không dùng id,
                             id trùng nhau thì getElementById chỉ thấy bản đầu. */ ?>
                    <tfoot>
                        <tr><th colspan="4" class="text-right font-weight-normal">Tiền hàng hoá</th>
                            <th class="text-right font-weight-normal"><span class="js-tong-hang">0</span> ₫</th>
                            <th colspan="2"></th></tr>
                        <tr><th colspan="4" class="text-right font-weight-normal">Tiền dịch vụ</th>
                            <th class="text-right font-weight-normal"><span class="js-tong-dichvu">0</span> ₫</th>
                            <th colspan="2"></th></tr>
                        <tr><th colspan="4" class="text-right">Cộng chưa thuế</th>
                            <th class="text-right"><span class="js-sub-total">0</span> ₫</th>
                            <th colspan="2"></th></tr>
                        <tr><th colspan="4" class="text-right">Thuế GTGT</th>
                            <th class="text-right"><span class="js-tax-total">0</span> ₫</th>
                            <th colspan="2"></th></tr>
                        <tr><th colspan="4" class="text-right">Tổng cộng</th>
                            <th class="text-right text-danger"><span class="js-grand-total">0</span> ₫</th>
                            <th colspan="2"></th></tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <?php endforeach; ?>

        {!! !empty($errors['lines'])?'<div class="card-body py-2"><small class="text-danger">'.e($errors['lines']).'</small></div>':false !!}
    </div>

    <div class="card"><div class="card-body">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu (nháp)</button>
        <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i> Quay lại</a>
    </div></div>
</form>
<?php /* Hộp chọn báo giá để chép. Tự dựng bằng CSS/JS thay vì modal của
         Bootstrap: trang này đang dùng bản Bootstrap 4 của theme admin, mà
         phần JS modal phụ thuộc jQuery nạp ở cuối trang — gọi sớm là hụt.
         Một lớp phủ + một hộp là đủ, không kéo thêm phụ thuộc nào. */ ?>
<div id="chep-phu" style="display:none;position:fixed;inset:0;z-index:1090;background:rgba(15,23,42,.45)"></div>
<div id="chep-hop" style="display:none;position:fixed;z-index:1091;top:50%;left:50%;transform:translate(-50%,-50%);
     width:min(880px,94vw);max-height:86vh;background:#fff;border-radius:10px;box-shadow:0 20px 50px rgba(15,23,42,.35);
     display:none;flex-direction:column;overflow:hidden">

    <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
        <h5 class="mb-0"><i class="fas fa-copy mr-2"></i>Chép từ báo giá cũ</h5>
        <button type="button" class="close" id="chep-dong"><span>&times;</span></button>
    </div>

    <div class="px-3 py-2 border-bottom bg-light">
        <div class="d-flex flex-wrap align-items-center" style="gap:12px">
            <input type="search" id="chep-tim" class="form-control form-control-sm" style="max-width:280px"
                   placeholder="Lọc theo số báo giá hoặc tên khách..."/>
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="chep-gia-moi" checked/>
                <label class="custom-control-label" for="chep-gia-moi">Lấy giá hiện tại</label>
            </div>
            <small class="text-muted">
                Bỏ tick nếu muốn giữ nguyên đơn giá của báo giá cũ.
            </small>
        </div>
    </div>

    <div id="chep-ds" class="p-0" style="overflow:auto;flex:1;min-height:120px">
        <div class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin mr-1"></i> Đang tải...</div>
    </div>

    <div class="px-3 py-2 border-top text-muted small">
        Chép sẽ <b>thay toàn bộ</b> dòng hàng đang có trên phiếu. Phần đầu phiếu (ngày, thuế) giữ nguyên.
    </div>
</div>


<script>
(function () {
    // JS không tự biết đường dẫn gốc của admin — PHP nhét vào đây.
    var GOC_ADMIN = {!! json_encode(_WEB_URL . '/admin') !!};
    var DU_LIEU = {
        hang:   {!! json_encode($hangJs, JSON_HEX_TAG|JSON_UNESCAPED_UNICODE) !!},
        dichvu: {!! json_encode($dichVuJs, JSON_HEX_TAG|JSON_UNESCAPED_UNICODE) !!}
    };

    // Hai nguồn danh mục + tên mọi mặt hàng của cả hai (để giữ dòng lạc nguồn)
    var NGUON = {
        gara: {!! json_encode($nguonGara, JSON_HEX_TAG|JSON_UNESCAPED_UNICODE) !!},
        tong: {!! json_encode($nguonTong, JSON_HEX_TAG|JSON_UNESCAPED_UNICODE) !!}
    };
    var TEN_HANG     = {!! json_encode((object) $tenHang, JSON_HEX_TAG|JSON_UNESCAPED_UNICODE) !!};
    var nguonDangDung = {!! json_encode($nguonMacDinh) !!};
    var BAN_DAU = {
        hang:   {!! json_encode($initHang, JSON_HEX_TAG|JSON_UNESCAPED_UNICODE) !!},
        dichvu: {!! json_encode($initDichVu, JSON_HEX_TAG|JSON_UNESCAPED_UNICODE) !!}
    };
    var DISC = {!! json_encode((object)$partnerDiscounts, JSON_HEX_TAG|JSON_UNESCAPED_UNICODE) !!};

    var vatEl   = document.getElementById('vat_rate');
    var custSel = document.querySelector('select[name="customer_id"]');

    function fmt(n){ return (n || 0).toLocaleString('vi-VN'); }
    function num(v){ return parseFloat(String(v || '').replace(/[^\d.]/g, '')) || 0; }
    function money(v){ return parseInt(String(v || '').replace(/[^\d]/g, ''), 10) || 0; }
    function groupDisc(){ var v = custSel ? custSel.value : ''; return (v && DISC[v] != null) ? parseFloat(DISC[v]) : 0; }

    var bang = {};

    // Khối tổng vẽ ở cả hai tab nên phải ghi vào MỌI ô cùng tên, không phải một ô.
    function dat(ten, giaTri){
        document.querySelectorAll('.js-' + ten).forEach(function (e){ e.textContent = giaTri; });
    }

    function recompute(){
        var tHang   = bang.hang.tong();
        var tDichVu = bang.dichvu.tong();
        var sub     = tHang + tDichVu;
        var tax     = Math.round(sub * num(vatEl.value) / 100);

        dat('tong-hang',   fmt(tHang));
        dat('tong-dichvu', fmt(tDichVu));
        dat('sub-total',   fmt(sub));
        dat('tax-total',   fmt(tax));
        dat('grand-total', fmt(sub + tax));

        document.getElementById('dem-hang').textContent   = bang.hang.dem();
        document.getElementById('dem-dichvu').textContent = bang.dichvu.dem();
    }

    /* Một bảng dòng hàng. Gọi hai lần với hai bộ dữ liệu + hai tiền tố tên ô.
       Trước đây đoạn này viết thẳng một lần cho một bảng; gói lại thành hàm để
       tab Dịch vụ không phải chép nguyên si rồi sau này sửa sót một bên. */
    function taoBang(ma, tienTo, DS, nhanTrong){
        var tbody = document.getElementById('lines-' + ma);

        function partSelect(selected){
            var s = document.createElement('select');
            s.name = tienTo + 'part[]';
            s.className = 'form-control form-control-sm part-sel js-search';
            s.setAttribute('data-placeholder', nhanTrong);
            napOption(s, selected);
            return s;
        }

        /* Nạp danh sách cho MỘT ô chọn, BỎ những mặt hàng đã có ở dòng khác.

           Chọn trùng một mặt hàng trên hai dòng là lỗi nhập liệu chứ không phải
           nhu cầu — cần nhiều thì tăng số lượng. Lọc thẳng khỏi danh sách thì
           không bấm nhầm được, thay vì cho chọn xong mới báo lỗi.

           GIỮ LẠI mặt hàng chính dòng này đang chọn, kể cả khi nó trùng với
           dòng khác: phiếu lập trước khi có luật này có thể đã có dòng trùng
           (HD-000005 đang có 2 dòng PT-0006). Lọc mất thì mở phiếu ra thấy ô
           trống, lưu một cái là bay luôn dòng đó.

           Ô tìm kiếm phủ bên trên đọc thẳng select.options mỗi lần gõ, nên sửa
           ở đây là nó tự theo, không phải đụng gì thêm. */
        function napOption(sel, dangChon){
            if (dangChon === undefined) dangChon = sel.value;

            var oDongKhac = {};
            tbody.querySelectorAll('.line-row select.part-sel').forEach(function (s){
                if (s !== sel && s.value) oDongKhac[s.value] = true;
            });

            sel.textContent = '';
            var o0 = document.createElement('option');
            o0.value = ''; o0.textContent = nhanTrong; sel.appendChild(o0);

            var thayDangChon = false;
            DS.forEach(function (op){
                if (oDongKhac[op.id] && String(op.id) !== String(dangChon)) return;
                var o = document.createElement('option');
                o.value = op.id; o.textContent = op.label; o.setAttribute('data-price', op.price);
                if (op.img) o.setAttribute('data-img', op.img);
                if (String(op.id) === String(dangChon)){ o.selected = true; thayDangChon = true; }
                sel.appendChild(o);
            });

            /* Mặt hàng dòng này đang chọn KHÔNG có trong nguồn vừa đổi sang.
               Vẫn phải giữ lại nó, gắn nhãn cho người dùng biết: bỏ đi thì ô
               hoá trống, bấm Lưu một cái là mất dòng mà chẳng có lỗi nào báo.
               Người dùng muốn bỏ thì tự bấm nút xoá dòng — đó là quyết định
               của họ, không phải hệ quả ngầm của việc đổi nguồn. */
            if (dangChon && !thayDangChon){
                var oL = document.createElement('option');
                oL.value = dangChon;
                oL.textContent = (TEN_HANG[dangChon] || 'Mặt hàng #' + dangChon) + '  (ngoài nguồn đang chọn)';
                oL.selected = true;
                sel.appendChild(oL);
            }
        }

        // Đổi/xoá một dòng là danh sách của MỌI dòng khác đều đổi theo
        function napLaiTatCa(){
            tbody.querySelectorAll('.line-row select.part-sel').forEach(function (s){ napOption(s); });
        }

        // Hết hàng để chọn thì thôi đẻ dòng trống, không thì ra một dòng rỗng
        // mà mở danh sách ra chẳng có gì.
        function conHangDeChon(){
            var dung = {};
            tbody.querySelectorAll('.line-row select.part-sel').forEach(function (s){ if (s.value) dung[s.value] = true; });
            return DS.some(function (op){ return !dung[op.id]; });
        }
        function td(child, cls){ var t = document.createElement('td'); if (cls) t.className = cls; if (child) t.appendChild(child); return t; }
        function inp(name, cls, val){ var i = document.createElement('input'); i.type = 'text'; i.name = name; i.className = 'form-control form-control-sm ' + cls; i.value = (val === 0 || val) ? val : ''; return i; }

        function addRow(data){
            data = data || {};
            var tr = document.createElement('tr'); tr.className = 'line-row';
            var sel = partSelect(data.part_id);
            var price = oTien(inp(tienTo + 'price[]', 'price text-right', data.price));

            sel.addEventListener('change', function (){
                var o = sel.options[sel.selectedIndex];
                var p = o ? o.getAttribute('data-price') : 0;
                /* Đổi mặt hàng thì giá PHẢI đi theo mặt hàng mới.

                   Bản cũ là `if (p && !money(price.value))` — chỉ điền khi ô giá
                   đang trống, với ý "đừng đè giá người dùng tự gõ". Nhưng nó
                   không phân biệt được giá người dùng gõ với giá do CHÍNH NÓ
                   vừa điền cho mặt hàng TRƯỚC ĐÓ. Hậu quả: chọn Ắc quy
                   (1.380.000) rồi đổi dòng đó sang Bugi thì giá vẫn nằm nguyên
                   1.380.000 — hoá đơn lưu sai giá mà không có gì báo.
                   Đã xảy ra thật trên HD-000006.

                   Chọn mặt hàng là hành động rõ ràng, giá theo đó là đúng.
                   Muốn giá riêng thì gõ đè sau khi đã chọn xong hàng. Mở phiếu
                   cũ ra sửa KHÔNG bị ảnh hưởng: dòng cũ dựng thẳng từ dữ liệu
                   đã lưu, không đi qua sự kiện change này. */
                if (p) price.value = p;
                // Mặt hàng vừa đổi -> mọi ô chọn khác phải bỏ/thêm nó lại
                napLaiTatCa();
                recompute();
                // Chọn xong ở dòng CUỐI thì tự đẻ dòng trống kế tiếp, khỏi bắt
                // người nhập bấm "Thêm dòng" sau mỗi mặt hàng. Chỉ xét dòng cuối:
                // đổi hàng ở một dòng giữa bảng là sửa lại, không phải nhập thêm.
                // Dòng trống thừa lúc lưu không sao: buildLines() bỏ qua dòng
                // chưa chọn hàng hoặc số lượng <= 0.
                if (sel.value && tr === tbody.lastElementChild && conHangDeChon()) addRow();
            });
            tr.appendChild(td(sel));

            var q = inp(tienTo + 'qty[]', 'qty text-right', data.qty); soLuong(q);
            q.addEventListener('input', recompute); tr.appendChild(td(q));

            price.addEventListener('input', recompute); tr.appendChild(td(price));

            var discVal = (data.disc === 0 || data.disc) ? data.disc : '';
            if (discVal === '' || discVal == null){ var gd = groupDisc(); if (gd > 0) discVal = gd; }
            var disc = oPhanTram(inp(tienTo + 'disc[]', 'disc text-right', discVal));
            disc.addEventListener('input', recompute); tr.appendChild(td(disc));

            var amtTd = document.createElement('td'); amtTd.className = 'text-right align-middle';
            var amtSpan = document.createElement('span'); amtSpan.className = 'amt'; amtSpan.textContent = '0';
            amtTd.appendChild(amtSpan); tr.appendChild(amtTd);

            tr.appendChild(td(inp(tienTo + 'note[]', '', data.note)));

            var rm = document.createElement('button'); rm.type = 'button';
            rm.className = 'btn btn-sm btn-outline-danger rm-row'; rm.innerHTML = '&times;';
            tr.appendChild(td(rm, 'text-center'));

            tbody.appendChild(tr); recompute();
        }

        function tong(){
            var s = 0;
            tbody.querySelectorAll('.line-row').forEach(function (r){
                var d = num(r.querySelector('.disc').value); if (d < 0) d = 0; if (d > 100) d = 100;
                var amt = Math.round(num(r.querySelector('.qty').value) * money(r.querySelector('.price').value) * (1 - d / 100));
                r.querySelector('.amt').textContent = fmt(amt); s += amt;
            });
            return s;
        }

        /* Chỉ đếm dòng ĐÃ chọn mặt hàng — dòng trống cuối bảng không phải hàng thật.

           'select.part-sel' chứ không phải '.part-sel': ô tìm kiếm phủ lên trên
           (buildSearchSelect trong admin.js) chép nguyên className của select,
           nên nó cũng mang class part-sel và có value là nhãn đang hiện. Bỏ
           'select.' đi là mỗi dòng đếm thành hai. */
        function dem(){
            var n = 0;
            tbody.querySelectorAll('.line-row select.part-sel').forEach(function (s){ if (s.value) n++; });
            return n;
        }

        document.getElementById('add-' + ma).addEventListener('click', function (){ addRow(); });
        tbody.addEventListener('click', function (e){
            if (e.target && e.target.classList.contains('rm-row')){
                var r = e.target.closest('.line-row'); if (r) r.remove(); napLaiTatCa(); recompute();
            }
        });

        /* xoaHet/napLai lo cho nut "Chep tu bao gia cu": no phai don sach
           bang roi do dong moi vao. napLai de cac o chon dung lai danh sach
           sau khi da them mot loat dong — them tung dong thi dong them TRUOC
           khong biet dong them SAU da giu mat hang nao. */
        function xoaHet(){ tbody.innerHTML = ''; }

        /* Đổi nguồn danh mục. DS là biến của closure nên gán lại ở đây là mọi
           ô chọn dùng danh sách mới ngay, không phải dựng lại cả bảng — dựng
           lại thì mất hết số lượng, chiết khấu, ghi chú người dùng đã gõ. */
        function doiNguon(dsMoi){ DS = dsMoi || []; napLaiTatCa(); }

        return { addRow: addRow, tong: tong, dem: dem, tbody: tbody,
                 xoaHet: xoaHet, napLai: napLaiTatCa, doiNguon: doiNguon };
    }

    bang.hang   = taoBang('hang',   'line_', DU_LIEU.hang,   '— Chọn hàng hoá —');
    bang.dichvu = taoBang('dichvu', 'sv_',   DU_LIEU.dichvu, '— Chọn dịch vụ —');

    /* ------------------------------------------------------------------
       ĐỔI NGUỒN DANH MỤC

       Chỉ đổi DANH SÁCH GỢI Ý. Dòng đã chọn, số lượng, chiết khấu, ghi chú
       giữ nguyên hết — người dùng đang lập dở một phiếu, bấm nhầm nút mà mất
       việc đã gõ thì không ai dám bấm nữa.

       Giá cũng KHÔNG tự đổi theo: giá đang nằm trên dòng có thể là giá người
       dùng tự gõ. Đổi nguồn xong muốn lấy giá mới thì chọn lại mặt hàng ở
       dòng đó — lúc đó giá mới đi theo, đúng như mọi lần chọn mặt hàng khác.
       ------------------------------------------------------------------ */
    var oNguon = document.getElementById('chon-nguon');
    var moTaEl = document.getElementById('nguon-mo-ta');

    function veMoTa(){
        if (!moTaEl) return;
        moTaEl.textContent = nguonDangDung === 'gara'
            ? 'Hàng riêng của gara và hàng gara đã chọn làm, theo giá riêng của gara.'
            : 'Toàn bộ danh mục chung, theo giá gốc.';
    }

    if (oNguon){
        oNguon.addEventListener('click', function (e){
            var nut = e.target.closest('button[data-nguon]');
            if (!nut) return;
            var moi = nut.getAttribute('data-nguon');
            if (moi === nguonDangDung) return;

            nguonDangDung = moi;
            this.querySelectorAll('button[data-nguon]').forEach(function (b){
                b.classList.toggle('active', b.getAttribute('data-nguon') === moi);
            });

            bang.hang.doiNguon(NGUON[moi].hang);
            bang.dichvu.doiNguon(NGUON[moi].dichvu);
            veMoTa();
        });
        veMoTa();
    }

    /* Tab tự xử lý, không nhờ plugin: hai pane đều nằm sẵn trong CÙNG một form
       nên dù đang ẩn vẫn được gửi lên bình thường. Đổi tab chỉ là ẩn/hiện. */
    document.getElementById('line-tabs').addEventListener('click', function (e){
        var a = e.target.closest('a[data-pane]');
        if (!a) return;
        e.preventDefault();
        this.querySelectorAll('a[data-pane]').forEach(function (x){ x.classList.remove('active'); });
        a.classList.add('active');
        document.querySelectorAll('.pane-dong').forEach(function (p){ p.style.display = 'none'; });
        document.getElementById('pane-' + a.getAttribute('data-pane')).style.display = '';
    });

    if (custSel){
        custSel.addEventListener('change', function (){
            var gd = groupDisc();
            document.querySelectorAll('.pane-dong .line-row .disc').forEach(function (d){ d.value = gd > 0 ? gd : ''; });
            recompute();
        });
    }
    vatEl.addEventListener('input', recompute);

    ['hang', 'dichvu'].forEach(function (ma){
        if (BAN_DAU[ma].length) BAN_DAU[ma].forEach(function (r){ bang[ma].addRow(r); });
        else bang[ma].addRow();
    });
    recompute();

    /* ==================================================================
       CHÉP TỪ BÁO GIÁ CŨ

       Gara hay lặp lại đúng một đơn: chi nhánh mới mở đặt y hệt chi nhánh
       cũ, khách cũ đặt lại combo bảo dưỡng. Gõ lại từng dòng là việc thừa.

       Gọi bằng fetch chứ không tải lại trang: phần đầu phiếu (ngày, hiệu
       lực, thuế) người dùng có thể đã gõ dở, tải lại là mất sạch.
       ================================================================== */
    (function chepTuBaoGiaCu(){
        var nut  = document.getElementById('btn-chep');
        var phu  = document.getElementById('chep-phu');
        var hop  = document.getElementById('chep-hop');
        var dsEl = document.getElementById('chep-ds');
        var tim  = document.getElementById('chep-tim');
        var oGiaMoi = document.getElementById('chep-gia-moi');
        if (!nut || !hop) return;

        var DS  = [];
        var GOC = GOC_ADMIN;

        function esc(s){
            return String(s == null ? '' : s)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
        function tien(n){ return (n || 0).toLocaleString('vi-VN') + ' d'; }

        function mo(){
            phu.style.display = 'block';
            hop.style.display = 'flex';
            tim.value = '';
            tai();
            setTimeout(function (){ tim.focus(); }, 50);
        }
        function dong(){ phu.style.display = 'none'; hop.style.display = 'none'; }

        function ve(){
            var tu  = (tim.value || '').toLowerCase().trim();
            var loc = DS.filter(function (q){
                if (!tu) return true;
                return (q.quote_no + ' ' + q.khach).toLowerCase().indexOf(tu) >= 0;
            });

            if (!loc.length){
                dsEl.innerHTML = '<div class="text-center text-muted py-4">'
                    + '<i class="fas fa-inbox fa-2x d-block mb-2"></i>'
                    + (DS.length ? 'Khong co bao gia nao khop' : 'Chua co bao gia nao de chep')
                    + '</div>';
                return;
            }

            var h = '<table class="table table-hover table-sm mb-0"><thead><tr>'
                  + '<th>So bao gia</th><th>Ngay</th><th>Khach hang</th>'
                  + '<th class="text-center">So dong</th><th class="text-right">Tong tien</th>'
                  + '<th style="width:90px"></th></tr></thead><tbody>';

            loc.forEach(function (q){
                // Báo giá 0 dòng chép về cũng chẳng được gì -> không cho bấm
                var trong = q.so_dong === 0;
                h += '<tr' + (q.cua_khach_nay ? ' class="table-info"' : '') + '>'
                   + '<td class="font-weight-bold">' + esc(q.quote_no)
                   + (q.cua_khach_nay ? ' <span class="badge badge-info">khach nay</span>' : '')
                   + '</td>'
                   + '<td>' + esc(q.ngay) + '</td>'
                   + '<td>' + esc(q.khach) + '</td>'
                   + '<td class="text-center">' + q.so_dong + '</td>'
                   + '<td class="text-right">' + tien(q.tong) + '</td>'
                   + '<td class="text-right">'
                   + (trong
                        ? '<span class="text-muted small">trong</span>'
                        : '<button type="button" class="btn btn-sm btn-primary js-chon" data-id="' + q.id + '">Chep</button>')
                   + '</td></tr>';
            });
            dsEl.innerHTML = h + '</tbody></table>';
        }

        function tai(){
            dsEl.innerHTML = '<div class="text-center text-muted py-4">'
                           + '<i class="fas fa-spinner fa-spin mr-1"></i> Dang tai...</div>';
            var kh = custSel ? custSel.value : '';
            fetch(GOC + '/quotations/copy-list' + (kh ? '?customer_id=' + encodeURIComponent(kh) : ''),
                  { credentials: 'same-origin' })
                .then(function (r){ return r.json(); })
                .then(function (j){ DS = (j && j.items) || []; ve(); })
                .catch(function (){
                    dsEl.innerHTML = '<div class="text-center text-danger py-4">'
                                   + 'Khong tai duoc danh sach bao gia</div>';
                });
        }

        function chep(id){
            fetch(GOC + '/quotations/copy-lines/' + id, { credentials: 'same-origin' })
                .then(function (r){ return r.json(); })
                .then(function (j){
                    if (!j || j.error){ alert(j && j.error ? j.error : 'Khong doc duoc bao gia'); return; }

                    var layGiaMoi = oGiaMoi.checked;

                    ['hang', 'dichvu'].forEach(function (ma){
                        bang[ma].xoaHet();
                        (j[ma] || []).forEach(function (d){
                            bang[ma].addRow({
                                part_id: d.part_id,
                                qty:     d.qty,
                                price:   layGiaMoi ? d.gia_moi : d.gia_cu,
                                disc:    d.disc,
                                note:    d.note
                            });
                        });
                        // Tab nào không có dòng nào thì vẫn để lại một dòng trống
                        // để nhập tay, không bỏ bảng rỗng không bấm được gì.
                        if (!(j[ma] || []).length) bang[ma].addRow();
                        bang[ma].napLai();
                    });

                    /* Khách hàng: CHỈ điền khi ô đang bỏ trống. Người lập đã
                       chọn khách rồi thì đó là chủ ý — chép dòng hàng của đơn
                       khách khác về là chuyện bình thường, không được nhân đó
                       mà đổi luôn khách của phiếu. */
                    if (custSel && !custSel.value && j.customer_id){
                        custSel.value = String(j.customer_id);
                        custSel.dispatchEvent(new Event('change', { bubbles: true }));
                    }

                    recompute();
                    dong();

                    /* Báo rõ những gì KHÔNG chép được. Im lặng bỏ bớt dòng là
                       kiểu sai tệ nhất ở đây: người lập tưởng đã chép đủ, gửi
                       cho khách một báo giá thiếu mục. */
                    var bo = j.bo_qua || {};
                    var canhBao = [];
                    if (bo.da_xoa)    canhBao.push(bo.da_xoa + ' dong co mat hang da bi xoa');
                    if (bo.ngung_ban) canhBao.push(bo.ngung_ban + ' dong co mat hang da ngung kinh doanh');
                    if (canhBao.length){
                        alert('Da chep tu ' + j.quote_no + '.\n\nBo qua: ' + canhBao.join(', ') + '.');
                    }
                })
                .catch(function (){ alert('Khong doc duoc bao gia'); });
        }

        nut.addEventListener('click', function (){
            // Chép là THAY SẠCH, nên đang có dòng thì phải hỏi trước
            var dangCo = bang.hang.dem() + bang.dichvu.dem();
            if (dangCo > 0 &&
                !confirm('Phieu dang co ' + dangCo + ' dong.\nChep tu bao gia cu se THAY TOAN BO. Tiep tuc?')) return;
            mo();
        });
        document.getElementById('chep-dong').addEventListener('click', dong);
        phu.addEventListener('click', dong);
        tim.addEventListener('input', ve);
        dsEl.addEventListener('click', function (e){
            var b = e.target.closest('.js-chon');
            if (b) chep(b.getAttribute('data-id'));
        });
        document.addEventListener('keydown', function (e){
            if (e.key === 'Escape' && hop.style.display !== 'none') dong();
        });
    })();

})();
</script>
