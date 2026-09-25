/* Ô CHỌN XE theo khách hàng trên chứng từ (báo giá, hoá đơn, phiếu bảo hành).
 *
 * Một khách có nhiều xe, nên chọn khách xong là phải chọn được xe trong danh
 * sách xe CỦA KHÁCH ĐÓ — gõ tay biển số thì sai chính tả là chứng từ không gắn
 * được vào xe nào, và lịch sử xe mất một lần vào xưởng.
 *
 * Trong view, bọc ô biển số lại như sau:
 *   <div data-xe-khach="customer_id" data-url="<?php echo _WEB_URL; ?>/admin/vehicles">
 *       <select class="form-control js-xe-list d-none"></select>
 *       <input type="text" name="bien_so" class="js-xe-go" value="...">
 *       <small class="js-xe-nhac">…</small>
 *   </div>
 * và đặt <small class="js-xe-km"> cạnh ô `so_km` trong cùng form.
 *
 * Ô GÕ TAY vẫn là ô duy nhất gửi lên server (name="bien_so"): ô chọn chỉ điền
 * hộ. Nhờ vậy không cần sửa gì ở tầng lưu, và JS lỗi thì form vẫn dùng được
 * như cũ (ô gõ tay hiện sẵn, đúng cách làm trước đây).
 *
 * SỐ KM KHÔNG ĐIỀN HỘ. Chỉ nhắc "xe đang ghi 62.400 km" để người lập gõ số
 * hiện tại — số km trên chứng từ là số đọc trên đồng hồ lúc xe vào, không phải
 * số lần trước.
 */
(function () {
    var KHAC = '__khac__';

    function chuan(s) {
        return String(s || '').toUpperCase().replace(/[^A-Z0-9]/g, '');
    }

    function soVn(n) {
        return (n === null || n === undefined || n === '') ? '' : Number(n).toLocaleString('vi-VN');
    }

    function khoiTao(oBoc) {
        var form = oBoc.closest('form');
        if (!form) return;

        var oList = oBoc.querySelector('.js-xe-list');
        var oGo   = oBoc.querySelector('.js-xe-go');
        if (!oList || !oGo) return;

        var oNhac = oBoc.querySelector('.js-xe-nhac');
        var oKm   = form.querySelector('.js-xe-km');
        var nhacGoc = oNhac ? oNhac.innerHTML : '';

        var goc     = (oBoc.getAttribute('data-url') || '').replace(/\/+$/, '');
        var tenKhach = oBoc.getAttribute('data-xe-khach') || 'customer_id';
        var oKhach  = form.querySelector('[name="' + tenKhach + '"]');

        /* Lập từ phiếu tiếp nhận thì xe do phiếu quyết định, server lấy xe của
           phiếu và bỏ qua biển số gõ tay — thêm ô chọn vào chỉ gây hiểu nhầm. */
        var tuPhieu = form.querySelector('[name="reception_id"]');
        if (tuPhieu && tuPhieu.value) return;
        if (!oKhach) return;

        function nhac(html) {
            if (oNhac) oNhac.innerHTML = html;
        }

        function nhacKm(km) {
            if (!oKm) return;
            oKm.innerHTML = (km === null || km === undefined)
                ? 'Gõ số km đọc trên đồng hồ lúc xe vào.'
                : 'Xe đang ghi <b>' + soVn(km) + ' km</b> — gõ số km hiện tại.';
        }

        function moGoTay() {
            oList.classList.add('d-none');
            oGo.classList.remove('d-none');
        }

        function dungGoTay() {
            oList.classList.remove('d-none');
            oGo.classList.add('d-none');
        }

        function veList(ds) {
            var dangCo = chuan(oGo.value);
            oList.innerHTML = '';
            oList.appendChild(new Option('— Không gắn xe —', ''));

            var khop = false;
            ds.forEach(function (x) {
                var o = new Option(x.n, x.c);
                o.setAttribute('data-km', x.km === null || x.km === undefined ? '' : x.km);
                if (dangCo && chuan(x.c) === dangCo) { o.selected = true; khop = true; }
                oList.appendChild(o);
            });
            oList.appendChild(new Option('Xe khác — gõ biển số…', KHAC));

            if (dangCo && !khop) {
                oList.value = KHAC;
                moGoTay();
                nhac('Biển số này không thuộc xe nào của khách đang chọn.');
            } else {
                dungGoTay();
                nhac(khop ? '' : 'Chọn xe của khách, hoặc "Xe khác" để gõ biển số.');
                if (khop) nhacKm(so(oList.options[oList.selectedIndex]));
            }
        }

        function so(opt) {
            if (!opt) return null;
            var v = opt.getAttribute('data-km');
            return (v === null || v === '') ? null : parseInt(v, 10);
        }

        function nap() {
            var kh = oKhach.value;
            if (!kh) {
                moGoTay();
                nhac(nhacGoc);
                nhacKm(null);
                return;
            }
            fetch(goc + '/xe-theo-khach/' + encodeURIComponent(kh), { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (ds) {
                    if (!Array.isArray(ds) || !ds.length) {
                        moGoTay();
                        nhac('Khách này chưa khai xe nào — gõ biển số, hoặc khai xe ở màn <b>Xe của khách</b>.');
                        nhacKm(null);
                        return;
                    }
                    veList(ds);
                })
                .catch(function () {
                    moGoTay();
                    nhac('Không tải được danh sách xe của khách — gõ biển số như cũ.');
                });
        }

        oList.addEventListener('change', function () {
            if (oList.value === KHAC) {
                moGoTay();
                oGo.focus();
                nhac('Gõ biển số của xe.');
                nhacKm(null);
                return;
            }
            oGo.value = oList.value;
            nhacKm(so(oList.options[oList.selectedIndex]));
            nhac(oList.value ? '' : 'Chứng từ này không gắn với xe nào.');
        });

        /* Đổi khách thì xe của khách cũ không còn nghĩa gì. */
        oKhach.addEventListener('change', function () {
            oGo.value = '';
            nap();
        });

        nap();
    }

    function chay() {
        var ds = document.querySelectorAll('[data-xe-khach]');
        for (var i = 0; i < ds.length; i++) khoiTao(ds[i]);
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', chay);
    else chay();
})();
