/* Chuỗi chọn HÃNG -> MODEL -> NĂM lấy từ danh mục xe.
 *
 * Trong view, ba ô select đặt trong cùng một form:
 *   <select name="brand_id"    data-xe="hang" data-url="<?php echo _WEB_URL; ?>/admin/vehicles"></select>
 *   <select name="model_id"    data-xe="model"    data-chon="12"></select>
 *   <select name="car_year_id" data-xe="nam"      data-chon="34"></select>
 *
 * Model tải theo hãng, năm tải theo model — không đổ cả 16 model của 7 hãng ra
 * rồi lọc ở trình duyệt, vì danh mục còn lớn lên.
 * Xe lạ chưa có trong danh mục thì gõ tay ở ba ô chữ bên dưới (hang_xe,
 * model_xe, nam_sx) — chọn danh mục và gõ tay không dùng cùng lúc.
 */
(function () {
    function motDong(sel, nhan) {
        sel.innerHTML = '';
        sel.appendChild(new Option(nhan, ''));
    }

    function dat(sel, ds, chon, nhan) {
        sel.innerHTML = '';
        sel.appendChild(new Option(nhan, ''));
        ds.forEach(function (x) {
            var o = new Option(x.n, x.c);
            if (String(x.c) === String(chon)) o.selected = true;
            sel.appendChild(o);
        });
    }

    function khoiTao(oHang) {
        var form = oHang.form;
        if (!form) return;
        var oModel = form.querySelector('[data-xe="model"]');
        var oNam = form.querySelector('[data-xe="nam"]');
        if (!oModel || !oNam) return;

        var goc = (oHang.getAttribute('data-url') || '').replace(/\/+$/, '');
        var chonModel = oModel.getAttribute('data-chon') || '';
        var chonNam = oNam.getAttribute('data-chon') || '';

        function nap(sel, duong, chon, nhanCo, nhanKhong) {
            if (!duong) { sel.disabled = true; motDong(sel, nhanKhong); return; }
            sel.disabled = true;
            motDong(sel, 'Đang tải…');
            fetch(goc + duong, { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (ds) {
                    if (!Array.isArray(ds) || !ds.length) { motDong(sel, '— Không có trong danh mục —'); return; }
                    sel.disabled = false;
                    dat(sel, ds, chon, nhanCo);
                })
                .catch(function () { motDong(sel, '— Không tải được —'); });
        }

        function napModel(hang, chon) {
            nap(oModel, hang ? '/models/' + encodeURIComponent(hang) : '', chon,
                '— Chọn model —', '— Chọn hãng trước —');
        }
        function napNam(model, chon) {
            nap(oNam, model ? '/years/' + encodeURIComponent(model) : '', chon,
                '— Chọn năm —', '— Chọn model trước —');
        }

        if (oHang.value) {
            napModel(oHang.value, chonModel);
            if (chonModel) napNam(chonModel, chonNam);
        } else {
            motDong(oModel, '— Chọn hãng trước —'); oModel.disabled = true;
            motDong(oNam, '— Chọn model trước —'); oNam.disabled = true;
        }

        /* Đổi hãng thì model và năm phải nạp lại và BỎ lựa chọn cũ: model của
           hãng khác gửi lên là dữ liệu rác. */
        oHang.addEventListener('change', function () {
            napModel(oHang.value, '');
            napNam('', '');
        });
        oModel.addEventListener('change', function () { napNam(oModel.value, ''); });
    }

    function chay() {
        var ds = document.querySelectorAll('[data-xe="hang"]');
        for (var i = 0; i < ds.length; i++) khoiTao(ds[i]);
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', chay);
    else chay();
})();
