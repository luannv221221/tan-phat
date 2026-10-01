/* Ô CHỌN XE theo khách hàng trên chứng từ (báo giá, hoá đơn, phiếu bảo hành).
 *
 * Một khách có nhiều xe, nên chọn khách xong là phải chọn được xe trong danh
 * sách xe CỦA KHÁCH ĐÓ. Biển số CHỈ ĐƯỢC CHỌN, KHÔNG ĐƯỢC GÕ: gõ tay thì sai
 * chính tả là chứng từ không gắn được vào xe nào, và lịch sử xe mất một lần vào
 * xưởng. Xe chưa khai thì bấm nút + khai ngay tại chỗ, khỏi bỏ dở phiếu.
 *
 * Trong view, bọc ô biển số lại như sau:
 *   <div data-xe-khach="customer_id" data-url="<?php echo _WEB_URL; ?>/admin/vehicles">
 *       <select class="form-control js-xe-list d-none"></select>
 *       <button type="button" class="js-xe-them">+</button>
 *       <input type="text" name="bien_so" class="js-xe-go" value="...">
 *       <small class="js-xe-nhac">…</small>
 *   </div>
 * và đặt <small class="js-xe-km"> cạnh ô `so_km` trong cùng form.
 *
 * Ô `bien_so` vẫn là ô DUY NHẤT gửi lên server; ô chọn chỉ điền hộ. Nhờ vậy
 * không phải sửa gì ở tầng lưu, và JS hỏng thì form quay về gõ tay như cách làm
 * cũ thay vì chặn hẳn việc.
 *
 * SỐ KM KHÔNG ĐIỀN HỘ. Chỉ nhắc "xe đang ghi 62.400 km" để người lập gõ số
 * hiện tại — số km trên chứng từ là số đọc trên đồng hồ lúc xe vào, không phải
 * số lần trước.
 */
(function () {

    function chuan(s) {
        return String(s || '').toUpperCase().replace(/[^A-Z0-9]/g, '');
    }

    function soVn(n) {
        return (n === null || n === undefined || n === '') ? '' : Number(n).toLocaleString('vi-VN');
    }

    /* ------------------------------------------------ hộp khai xe tại chỗ */

    var hop = null, hopDang = null;
    var duongThemNhanh = '';

    function dungHop() {
        if (hop) return hop;

        hop = document.createElement('div');
        hop.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;z-index:1990;display:none;'
                          + 'background:rgba(0,0,0,.45);align-items:center;justify-content:center;padding:16px';

        /* FORM RIÊNG, đặt ngoài form chứng từ: lồng form trong form thì trình
           duyệt bỏ form trong, và các ô ở đây sẽ bị gửi kèm khi lưu chứng từ. */
        hop.innerHTML =
            '<form style="background:#fff;border-radius:6px;max-width:640px;width:100%;box-shadow:0 8px 32px rgba(0,0,0,.3)">'
          + '<div style="padding:12px 16px;border-bottom:1px solid #dee2e6;font-weight:600">'
          + 'Khai xe cho khách <span data-vt="khach" class="text-muted"></span></div>'
          + '<div style="padding:16px">'
          + '<input type="hidden" name="_token"/><input type="hidden" name="partner_id"/>'
          + '<div class="form-row">'
          + '<div class="form-group col-md-6"><label class="small mb-1">Biển số xe <span class="text-danger">*</span></label>'
          + '<input type="text" name="bien_so" class="form-control text-uppercase" placeholder="VD: 30A-123.45"/></div>'
          + '<div class="form-group col-md-6"><label class="small mb-1">Số km hiện tại</label>'
          + '<input type="text" name="so_km" class="form-control text-right" placeholder="VD: 45.000"/></div>'
          + '</div>'
          + '<div class="form-row">'
          + o_chon('Hãng xe <span class="text-danger">*</span>', 'brand_id', 'hang', '— Chọn hãng —')
          + o_chon('Model <span class="text-danger">*</span>', 'model_id', 'model', '— Chọn hãng trước —')
          + '</div>'
          + '<div class="form-row">'
          + o_chon('Năm sản xuất', 'car_year_id', 'nam', '— Chọn model trước —')
          + o_chon('Màu xe', 'color_id', 'mau', '— Không chọn —')
          + '</div>'
          + '<div class="text-danger small" data-vt="loi" style="display:none"></div>'
          + '<div class="text-muted small">Xe khai ở đây thuộc luôn về khách đang chọn.</div>'
          + '</div>'
          + '<div style="padding:12px 16px;border-top:1px solid #dee2e6;text-align:right">'
          + '<button type="button" class="btn btn-default btn-sm mr-1" data-vt="thoi">Thôi</button>'
          + '<button type="submit" class="btn btn-primary btn-sm" data-vt="luu">Lưu xe</button>'
          + '</div></form>';

        document.body.appendChild(hop);

        hop.querySelector('[data-vt="thoi"]').addEventListener('click', dongHop);
        hop.addEventListener('click', function (e) { if (e.target === hop) dongHop(); });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && hop.style.display !== 'none') dongHop();
        });
        hop.querySelector('form').addEventListener('submit', function (e) {
            e.preventDefault();
            luuXe();
        });

        return hop;
    }

    /* Nút + của hộp này dùng chung cơ chế với them-nhanh.js: hộp dựng sau khi
       trang đã tải, mà them-nhanh.js bắt click ở document nên vẫn chạy. */
    function nutThem(vai, ten) {
        var cha = vai === 'model' ? 'brand_id' : (vai === 'nam' ? 'model_id' : '');
        var day = (vai === 'hang' || vai === 'model') ? ' data-day="1"' : '';
        var nhan = { hang: 'hãng xe', model: 'model', nam: 'năm SX', mau: 'màu xe' }[vai] || 'dòng mới';
        return '<button type="button" class="btn btn-outline-secondary"'
             + ' data-them-nhanh="' + vai + '" data-o="' + ten + '" data-nhan="' + nhan + '"'
             + (cha ? ' data-cha="' + cha + '" data-cha-bat-buoc="1"' : '') + day
             + ' data-url="' + duongThemNhanh + '">'
             + '<i class="fas fa-plus"></i></button>';
    }

    /* Một ô chọn kèm nút + để thêm thẳng vào danh mục */
    function o_chon(nhan, ten, vai, rong) {
        return '<div class="form-group col-md-6"><label class="small mb-1">' + nhan + '</label>'
             + '<div class="input-group">'
             + '<select name="' + ten + '" class="form-control" data-xe="' + vai + '"'
             + (vai === 'model' || vai === 'nam' ? ' disabled' : '') + '>'
             + '<option value="">' + rong + '</option></select>'
             + '<div class="input-group-append">'
             + nutThem(vai, ten)
             + '</div></div></div>';
    }

    function dongHop() { if (hop) hop.style.display = 'none'; }

    function hopLoi(chu) {
        var l = hop.querySelector('[data-vt="loi"]');
        l.textContent = chu || '';
        l.style.display = chu ? '' : 'none';
    }

    /**
     * Mở hộp khai xe. `ct` mang mọi thứ của ô chọn đang gọi: gốc URL, id khách,
     * token, và hàm gọi lại khi lưu xong.
     */
    function moHop(ct) {
        // …/admin/vehicles -> …/admin/them-nhanh/danh-muc
        duongThemNhanh = ct.goc.replace(/\/[^\/]+$/, '/them-nhanh/danh-muc');
        dungHop();
        hopDang = ct;

        var f = hop.querySelector('form');
        f.querySelector('[name="_token"]').value     = ct.token || '';
        f.querySelector('[name="partner_id"]').value = ct.khach || '';
        f.querySelector('[name="bien_so"]').value    = '';
        f.querySelector('[name="so_km"]').value      = '';
        hop.querySelector('[data-vt="khach"]').textContent = ct.tenKhach ? '— ' + ct.tenKhach : '';
        hopLoi('');

        var oHang = f.querySelector('[data-xe="hang"]');
        var oMau  = f.querySelector('[data-xe="mau"]');

        /* data-url cho chuỗi hãng -> model -> năm trong xe-danh-muc.js */
        oHang.setAttribute('data-url', ct.goc);

        napChon(oHang, ct.goc + '/hang', '— Chọn hãng —', function () {
            if (window.XeDanhMuc && window.XeDanhMuc.khoiTao) window.XeDanhMuc.khoiTao(oHang);
        });
        napChon(oMau, ct.goc + '/mau', '— Không chọn —');

        hop.style.display = 'flex';
        f.querySelector('[name="bien_so"]').focus();
    }

    function napChon(sel, duong, nhan, xong) {
        sel.innerHTML = '';
        sel.appendChild(new Option(nhan, ''));
        fetch(duong, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (ds) {
                (Array.isArray(ds) ? ds : []).forEach(function (x) {
                    sel.appendChild(new Option(x.n, x.c));
                });
                if (xong) xong();
            })
            .catch(function () { if (xong) xong(); });
    }

    function luuXe() {
        if (!hopDang) return;

        var f   = hop.querySelector('form');
        var luu = hop.querySelector('[data-vt="luu"]');

        if (!f.querySelector('[name="bien_so"]').value.trim()) { hopLoi('Chưa nhập biển số'); return; }

        luu.disabled = true;
        luu.textContent = 'Đang lưu…';

        fetch(hopDang.goc + '/them-nhanh', {
            method: 'POST', body: new FormData(f), credentials: 'same-origin'
        })
            .then(function (r) { return r.json(); })
            .then(function (kq) {
                luu.disabled = false;
                luu.textContent = 'Lưu xe';
                if (!kq || !kq.ok) { hopLoi((kq && kq.loi) || 'Không lưu được xe'); return; }
                hopDang.xong(kq.ds, kq.bien_so);
                dongHop();
            })
            .catch(function () {
                luu.disabled = false;
                luu.textContent = 'Lưu xe';
                hopLoi('Không gọi được máy chủ');
            });
    }

    /* ------------------------------------------------------ ô chọn xe */

    function khoiTao(oBoc) {
        var form = oBoc.closest('form');
        if (!form) return;

        var oList = oBoc.querySelector('.js-xe-list');
        var oGo   = oBoc.querySelector('.js-xe-go');
        if (!oList || !oGo) return;

        // Ô chọn và nút + nằm chung một cụm input-group — ẩn / hiện cả cụm
        var oCum = oBoc.querySelector('.js-xe-cum') || oList;

        var oNhac = oBoc.querySelector('.js-xe-nhac');
        var oKm   = form.querySelector('.js-xe-km');
        var oThem = oBoc.querySelector('.js-xe-them');
        var nhacGoc = oNhac ? oNhac.innerHTML : '';

        var goc      = (oBoc.getAttribute('data-url') || '').replace(/\/+$/, '');
        var tenKhach = oBoc.getAttribute('data-xe-khach') || 'customer_id';
        var oKhach   = form.querySelector('[name="' + tenKhach + '"]');

        /* Lập từ phiếu tiếp nhận thì xe do phiếu quyết định, server lấy xe của
           phiếu và bỏ qua biển số gõ tay — thêm ô chọn vào chỉ gây hiểu nhầm. */
        var tuPhieu = form.querySelector('[name="reception_id"]');
        if (tuPhieu && tuPhieu.value) return;
        if (!oKhach) return;

        function nhac(html) { if (oNhac) oNhac.innerHTML = html; }

        function nhacKm(km) {
            if (!oKm) return;
            oKm.innerHTML = (km === null || km === undefined)
                ? 'Gõ số km đọc trên đồng hồ lúc xe vào.'
                : 'Xe đang ghi <b>' + soVn(km) + ' km</b> — gõ số km hiện tại.';
        }

        /* JS hỏng hoặc không tải được danh sách thì quay về gõ tay — chặn hẳn
           việc thì người dùng không lập nổi phiếu. */
        function veGoTay() {
            oCum.classList.add('d-none');
            oGo.classList.remove('d-none');
        }

        function dungChon() {
            oCum.classList.remove('d-none');
            oGo.classList.add('d-none');
        }

        function hienThem(co) {
            if (oThem) oThem.classList.toggle('d-none', !co);
        }

        function so(opt) {
            if (!opt) return null;
            var v = opt.getAttribute('data-km');
            return (v === null || v === '') ? null : parseInt(v, 10);
        }

        function veList(ds, chonBienSo) {
            var dangCo = chuan(chonBienSo !== undefined ? chonBienSo : oGo.value);
            oList.innerHTML = '';
            oList.appendChild(new Option('— Không gắn xe —', ''));

            var khop = false;
            ds.forEach(function (x) {
                var o = new Option(x.n, x.c);
                o.setAttribute('data-km', x.km === null || x.km === undefined ? '' : x.km);
                if (dangCo && chuan(x.c) === dangCo) { o.selected = true; khop = true; }
                oList.appendChild(o);
            });

            /* Phiếu cũ mang biển số không thuộc xe nào của khách đang chọn:
               GIỮ LẠI thành một dòng trong danh sách thay vì im lặng xoá —
               sửa một dòng tiền mà mất biển số của phiếu là mất dữ liệu. */
            if (dangCo && !khop) {
                var cu = new Option(oGo.value + ' — không thuộc khách này', oGo.value);
                cu.selected = true;
                oList.appendChild(cu);
                nhac('Biển số cũ của phiếu không thuộc xe nào của khách đang chọn — chọn lại xe, hoặc bấm + khai xe này cho khách.');
            } else {
                nhac(khop ? '' : 'Chọn xe của khách.');
                if (khop) nhacKm(so(oList.options[oList.selectedIndex]));
            }

            dungChon();
            hienThem(true);
            oGo.value = oList.value;
        }

        function nap(chonBienSo) {
            var kh = oKhach.value;
            if (!kh) {
                dungChon();
                oList.innerHTML = '';
                oList.appendChild(new Option('— Chọn khách hàng trước —', ''));
                hienThem(false);
                nhac(nhacGoc);
                nhacKm(null);
                return;
            }

            fetch(goc + '/xe-theo-khach/' + encodeURIComponent(kh), { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (ds) {
                    if (!Array.isArray(ds)) { veGoTay(); return; }
                    if (!ds.length) {
                        dungChon();
                        oList.innerHTML = '';
                        oList.appendChild(new Option('— Khách chưa khai xe nào —', ''));
                        hienThem(true);
                        nhac('Khách này chưa có xe nào — bấm <b>+</b> để khai xe.');
                        nhacKm(null);
                        oGo.value = '';
                        return;
                    }
                    veList(ds, chonBienSo);
                })
                .catch(function () {
                    veGoTay();
                    hienThem(false);
                    nhac('Không tải được danh sách xe của khách — gõ biển số như cũ.');
                });
        }

        oList.addEventListener('change', function () {
            oGo.value = oList.value;
            nhacKm(so(oList.options[oList.selectedIndex]));
            nhac(oList.value ? '' : 'Chứng từ này không gắn với xe nào.');
        });

        /* Đổi khách thì xe của khách cũ không còn nghĩa gì. */
        oKhach.addEventListener('change', function () {
            oGo.value = '';
            nap();
        });

        if (oThem) {
            oThem.addEventListener('click', function () {
                if (!oKhach.value) { alert('Chọn khách hàng trước đã.'); return; }
                var tok = form.querySelector('input[name="_token"]');
                moHop({
                    goc:      goc,
                    khach:    oKhach.value,
                    tenKhach: oKhach.options ? (oKhach.options[oKhach.selectedIndex] || {}).text : '',
                    token:    tok ? tok.value : '',
                    xong:     function (ds, bienSo) { veList(ds || [], bienSo); }
                });
            });
        }

        nap();
    }

    function chay() {
        var ds = document.querySelectorAll('[data-xe-khach]');
        for (var i = 0; i < ds.length; i++) khoiTao(ds[i]);
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', chay);
    else chay();
})();
