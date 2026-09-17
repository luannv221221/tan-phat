/* Ô chọn Tỉnh / Phường dùng chung cho các form trong quản trị.
 *
 * Cách dùng trong view — hai ô select nằm CÙNG MỘT FORM:
 *   <select name="province_code" data-dia-gioi="tinh"
 *           data-url="<?php echo _WEB_URL; ?>/admin/dia-gioi" data-chon="1"></select>
 *   <select name="ward_code"     data-dia-gioi="xa" data-chon="4"></select>
 *
 * data-chon là giá trị ĐANG LƯU, để mở form Sửa thì chọn sẵn đúng tỉnh/phường.
 * Dữ liệu lấy qua server (server gọi API ngoài rồi nhớ tạm), không gọi thẳng
 * API ngoài từ trình duyệt — xem app/controllers/admin/Diagioi.php.
 *
 * Viết bằng JS thuần, không dùng jQuery: layout quản trị nạp jQuery ở CUỐI
 * trang, còn view nằm ở giữa nên lúc đoạn này chạy thì chưa có $.
 */
(function () {
    function dat(sel, ds, chon, nhan) {
        sel.innerHTML = '';
        sel.appendChild(new Option(nhan, ''));
        ds.forEach(function (x) {
            var o = new Option(x.n, x.c);
            if (String(x.c) === String(chon)) o.selected = true;
            sel.appendChild(o);
        });
    }

    function chiMotDong(sel, nhan) {
        sel.innerHTML = '';
        sel.appendChild(new Option(nhan, ''));
    }

    function khoiTao(oTinh) {
        var form = oTinh.form;
        var oXa = form ? form.querySelector('[data-dia-gioi="xa"]') : null;
        if (!oXa) return;

        var goc = (oTinh.getAttribute('data-url') || '').replace(/\/+$/, '');
        var chonTinh = oTinh.getAttribute('data-chon') || '';
        var chonXa = oXa.getAttribute('data-chon') || '';

        function napXa(tinh, chon) {
            if (!tinh) {
                oXa.disabled = true;
                chiMotDong(oXa, '— Chọn tỉnh trước —');
                return;
            }
            oXa.disabled = true;
            chiMotDong(oXa, 'Đang tải…');
            fetch(goc + '/xa/' + encodeURIComponent(tinh), { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (ds) {
                    if (!Array.isArray(ds) || !ds.length) {
                        chiMotDong(oXa, '— Không có dữ liệu —');
                        return;
                    }
                    oXa.disabled = false;
                    dat(oXa, ds, chon, '— Chọn phường / xã —');
                })
                .catch(function () { chiMotDong(oXa, '— Không tải được —'); });
        }

        oTinh.disabled = true;
        chiMotDong(oTinh, 'Đang tải…');
        fetch(goc + '/tinh', { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (ds) {
                if (!Array.isArray(ds) || !ds.length) {
                    chiMotDong(oTinh, '— Không tải được —');
                    return;
                }
                oTinh.disabled = false;
                dat(oTinh, ds, chonTinh, '— Chọn tỉnh / thành phố —');
                if (chonTinh) napXa(chonTinh, chonXa);
            })
            .catch(function () { chiMotDong(oTinh, '— Không tải được —'); });

        /* Đổi tỉnh thì phường phải nạp lại và BỎ lựa chọn cũ: phường của tỉnh
           khác gửi lên là dữ liệu rác, server sẽ từ chối. */
        oTinh.addEventListener('change', function () { napXa(oTinh.value, ''); });
    }

    function chay() {
        var ds = document.querySelectorAll('[data-dia-gioi="tinh"]');
        for (var i = 0; i < ds.length; i++) khoiTao(ds[i]);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', chay);
    } else {
        chay();
    }
})();
