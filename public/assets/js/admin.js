/* ============================================================
 * Hành vi khung quản trị — thay cho adminlte.min.js đã gỡ.
 *
 * Chỉ đảm nhận 3 việc:
 *   1. Mở/đóng nhóm menu trong sidebar (nhiều nhóm mở cùng lúc được)
 *   2. Thu gọn sidebar, ghi nhớ trong localStorage
 *   3. Mở sidebar dạng phủ trên màn hình nhỏ
 *
 * Dropdown / alert đóng được vẫn do bootstrap.min.js lo.
 * ============================================================ */
(function () {
    'use strict';

    var KEY = 'tp_admin_sidebar_collapsed';

    document.addEventListener('DOMContentLoaded', function () {

        // --- 1. Nhóm menu ---
        document.querySelectorAll('.adm-group > .adm-nav__link').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                // Sidebar đang thu gọn thì bung ra trước, rồi mới mở nhóm
                if (document.body.classList.contains('adm-collapsed')
                    && window.innerWidth > 991) {
                    setCollapsed(false);
                }
                link.parentNode.classList.toggle('is-open');
            });
        });

        // --- 2. Thu gọn sidebar ---
        // Khôi phục trạng thái đã lưu trước khi vẽ xong để tránh nhấp nháy
        var btn = document.querySelector('.adm-collapse-btn');
        if (btn) {
            btn.addEventListener('click', function () {
                setCollapsed(!document.body.classList.contains('adm-collapsed'));
            });
        }

        // --- 4. Ô chọn có tìm kiếm ---
        initSearchSelects(document);

        // Dòng hàng ở hoá đơn/báo giá do JS tạo sau, nên phải bắt cả select
        // được thêm vào về sau.
        if (window.MutationObserver) {
            new MutationObserver(function (muts) {
                muts.forEach(function (m) {
                    Array.prototype.forEach.call(m.addedNodes, function (n) {
                        if (n.nodeType !== 1) return;
                        if (n.matches && n.matches('select.js-search')) buildSearchSelect(n);
                        else initSearchSelects(n);
                    });
                });
            }).observe(document.body, { childList: true, subtree: true });
        }

        // --- 3. Sidebar trên màn hình nhỏ ---
        var burger   = document.querySelector('.adm-topbar__burger');
        var backdrop = document.querySelector('.adm-backdrop');

        if (burger) {
            burger.addEventListener('click', function () {
                document.body.classList.toggle('adm-sidebar-open');
            });
        }
        if (backdrop) {
            backdrop.addEventListener('click', function () {
                document.body.classList.remove('adm-sidebar-open');
            });
        }
    });

    /* ============================================================
     * Ô chọn có tìm kiếm (searchable select)
     *
     * Danh sách khách hàng / phụ tùng dài hàng chục dòng, <select> thuần chỉ
     * cuộn được nên rất khó tìm.
     *
     * CÁCH LÀM: GIỮ NGUYÊN thẻ <select> thật (chỉ ẩn đi), phủ lên trên một ô
     * nhập để lọc. Chọn xong thì gán value vào select rồi bắn sự kiện
     * 'change'. Nhờ vậy:
     *   - form submit y như cũ, không đổi tên trường
     *   - JS sẵn có đọc select[name="customer_id"] hay .part-sel vẫn chạy
     *     (vd tự điền đơn giá, chiết khấu theo nhóm khách)
     *
     * Áp dụng cho select có class `js-search`. Dòng hàng được JS tạo động nên
     * dùng MutationObserver để bắt cả những select thêm sau.
     * ============================================================ */

    function buildSearchSelect(select) {
        if (select.dataset.searchReady === '1') return;
        select.dataset.searchReady = '1';

        var wrap = document.createElement('div');
        wrap.className = 'ss';
        select.parentNode.insertBefore(wrap, select);
        wrap.appendChild(select);
        select.classList.add('ss__native');

        var input = document.createElement('input');
        input.type = 'text';
        input.className = select.className.replace('ss__native', '') + ' ss__input';
        input.setAttribute('autocomplete', 'off');
        input.placeholder = select.getAttribute('data-placeholder') || 'Gõ để tìm...';
        wrap.appendChild(input);

        var list = document.createElement('div');
        list.className = 'ss__list';
        wrap.appendChild(list);

        function label(i) {
            var o = select.options[i];
            return o ? o.textContent.trim() : '';
        }
        function showSelected() {
            input.value = select.selectedIndex > 0 ? label(select.selectedIndex) : '';
        }

        // Có ít nhất 1 option kèm ảnh (data-img) thì mọi dòng đều chừa chỗ ảnh
        // cho thẳng hàng — dòng không có ảnh dùng ô trống.
        var hasImg = !!select.querySelector('option[data-img]');

        function render(filter) {
            var q = (filter || '').toLowerCase().trim();
            list.innerHTML = '';
            var shown = 0;

            for (var i = 0; i < select.options.length; i++) {
                var txt = label(i);
                // Bỏ qua option rỗng (— Chọn —) khi đang gõ tìm
                if (q && txt.toLowerCase().indexOf(q) === -1) continue;
                if (q && select.options[i].value === '') continue;

                var row = document.createElement('div');
                row.className = 'ss__opt' + (i === select.selectedIndex ? ' is-active' : '');

                if (hasImg) {
                    var src = select.options[i].getAttribute('data-img');
                    var thumb;
                    if (src) {
                        thumb = document.createElement('img');
                        thumb.src = src;
                        thumb.alt = '';
                        thumb.loading = 'lazy';
                    } else {
                        thumb = document.createElement('span');
                    }
                    thumb.className = 'ss__thumb';
                    row.appendChild(thumb);
                }

                var text = document.createElement('span');
                text.className = 'ss__txt';
                text.textContent = txt;
                row.appendChild(text);

                row.setAttribute('data-i', i);
                list.appendChild(row);
                shown++;
            }

            if (!shown) {
                var none = document.createElement('div');
                none.className = 'ss__empty';
                none.textContent = 'Không tìm thấy';
                list.appendChild(none);
            }
        }

        /* ĐẶT CHỖ CHO DANH SÁCH — xem ghi chú về ssDangMo ở cuối khối này.
           Danh sách dùng position:fixed nên KHÔNG tự bám theo ô nhập; mỗi lần
           mở, mỗi lần gõ (số dòng đổi -> cao đổi) và mỗi lần cuộn đều phải
           tính lại. Quên một trong ba chỗ là danh sách đứng lại một nơi còn ô
           nhập đi một nẻo. */
        function datCho() {
            var r    = input.getBoundingClientRect();
            var leVw = document.documentElement.clientWidth;
            var le   = 8;  // chừa mép màn hình, không dán sát viền

            /* Trên điện thoại, cột tên hàng hoá hẹp hơn tên hàng -> danh sách
               rộng bằng ô nhập thì tên nào cũng "…". Nới tới 260px (hoặc hết
               bề ngang màn hình nếu màn còn hẹp hơn thế). */
            var rong = Math.max(r.width, Math.min(260, leVw - le * 2));
            var trai = Math.min(r.left, leVw - rong - le);
            if (trai < le) trai = le;

            var duoi = window.innerHeight - r.bottom - le;
            var tren = r.top - le;
            var cao  = Math.min(280, Math.max(duoi, tren));

            list.style.width     = rong + 'px';
            list.style.left      = trai + 'px';
            list.style.maxHeight = (cao > 80 ? cao : 80) + 'px';

            /* LẬT LÊN TRÊN khi dưới không đủ chỗ. Dòng hàng cuối của một phiếu
               dài nằm sát đáy màn hình: mở xuống thì danh sách thòi khỏi màn,
               người dùng lại phải cuộn trang trong lúc danh sách đang mở. */
            if (duoi >= cao || duoi >= tren) {
                list.style.top    = (r.bottom + 2) + 'px';
                list.style.bottom = 'auto';
            } else {
                list.style.top    = 'auto';
                list.style.bottom = (window.innerHeight - r.top + 2) + 'px';
            }
        }

        function open() {
            render('');
            input.select();
            wrap.classList.add('is-open');
            ssDangMo = { wrap: wrap, datCho: datCho };
            datCho();
        }
        function close() {
            wrap.classList.remove('is-open');
            if (ssDangMo && ssDangMo.wrap === wrap) ssDangMo = null;
            showSelected();
        }

        function pick(i) {
            select.selectedIndex = parseInt(i, 10);
            // Bắn 'change' để các handler sẵn có (đơn giá, chiết khấu...) chạy
            select.dispatchEvent(new Event('change', { bubbles: true }));
            close();
        }

        input.addEventListener('focus', open);
        input.addEventListener('input', function () {
            wrap.classList.add('is-open');
            render(input.value);
            datCho();   // lọc xong số dòng đổi -> chiều cao đổi -> phải tính lại
        });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { close(); input.blur(); }
            if (e.key === 'Enter') {
                var first = list.querySelector('.ss__opt');
                if (first) { e.preventDefault(); pick(first.getAttribute('data-i')); }
            }
        });
        list.addEventListener('mousedown', function (e) {
            var opt = e.target.closest('.ss__opt');
            if (opt) { e.preventDefault(); pick(opt.getAttribute('data-i')); }
        });
        document.addEventListener('click', function (e) {
            if (!wrap.contains(e.target)) close();
        });

        showSelected();
    }

    /* Ô CHỌN ĐANG MỞ — nhiều nhất một ô tại một thời điểm.
       Giữ ở đây để CẢ TRANG chỉ cần MỘT bộ lắng nghe cuộn/đổi cỡ, thay vì mỗi
       dòng hàng một bộ. Phiếu hai chục dòng là hai chục ô chọn; gắn listener
       cuộn cho từng ô thì mỗi lần cuộn chạy hai chục hàm, mà mười chín trong
       số đó đang đóng. */
    var ssDangMo = null;

    /* DANH SÁCH DÙNG position:fixed CHỨ KHÔNG PHẢI absolute.
       Bảng dòng hàng nằm trong .table-responsive, mà .table-responsive có
       overflow-x:auto — theo chuẩn CSS, overflow-y "visible" đứng cạnh một
       overflow-x khác "visible" sẽ bị tính thành "auto". Tức khung đó CẮT CẢ
       CHIỀU DỌC, và danh sách absolute bên trong bị xén mất phần thòi ra.
       Trên điện thoại đo được: danh sách cao 233px, khung cuộn cao 108px, chỉ
       còn nhìn thấy 10px — bấm vào ô chọn ra một vệt trắng, tưởng hỏng.
       (Báo 26/10/2026; chỗ này hỏng từ trước, màn hẹp chỉ làm nó lộ ra.)
       fixed thì danh sách tính theo khung nhìn, không khung cha nào cắt được.
       Đổi lại: phải tự đặt chỗ, xem datCho(). */
    window.addEventListener('scroll', function () {
        if (ssDangMo) ssDangMo.datCho();
    }, true);   // capture: cuộn bên trong .table-responsive KHÔNG nổi bọt lên window
    window.addEventListener('resize', function () {
        if (ssDangMo) ssDangMo.datCho();
    });

    function initSearchSelects(root) {
        (root || document).querySelectorAll('select.js-search').forEach(buildSearchSelect);
    }

    function setCollapsed(on) {
        document.body.classList.toggle('adm-collapsed', on);
        try { localStorage.setItem(KEY, on ? '1' : '0'); } catch (e) {}
    }

    // Đặt class ngay lập tức (script nạp ở <head> với defer nên body đã có)
    try {
        if (localStorage.getItem(KEY) === '1' && document.body) {
            document.body.classList.add('adm-collapsed');
        }
    } catch (e) {}
})();

/* ============================================================
   FORM HÀNG HOÁ — ẩn/hiện trường theo Loại (phụ tùng/thiết bị/dịch vụ)
   ============================================================

   Mỗi khối chỉ dành cho một số loại thì gắn:
       <div class="js-theo-loai" data-loai="part equipment"> ... </div>

   Bắt "Thay dầu máy" điền Mã OEM, Xuất xứ, Lắp cho đời xe là vô nghĩa và
   dễ nhập bậy. Khối thông số kỹ thuật lấy data-loai thẳng từ CSDL, nên
   khách tự thêm thông số và tick loại trong admin là form tự đổi theo,
   không cần sửa code.

   LƯU Ý: đây chỉ là lớp giao diện. Server VẪN phải tự xoá các trường không
   thuộc loại đã chọn (xem Products::filterTheoLoai) — ẩn bằng JS không ngăn
   được ai đó gửi thẳng dữ liệu lên. */
(function () {
    var sel = document.querySelector('select[name="item_type"]');
    if (!sel) return;

    var khoi = Array.prototype.slice.call(document.querySelectorAll('.js-theo-loai'));

    function apDung() {
        var loai = sel.value;

        khoi.forEach(function (el) {
            // data-loai ngăn cách bằng dấu phẩy (từ cột SET) hoặc khoảng trắng
            var ds = (el.getAttribute('data-loai') || '').split(/[\s,]+/).filter(Boolean);
            var hop = ds.length === 0 || ds.indexOf(loai) !== -1;
            el.style.display = hop ? '' : 'none';
        });

        // Ẩn hết thông số thì nói ra, đừng để cái thẻ trống trơn
        var thongSo = 0;
        khoi.forEach(function (el) {
            if (el.style.display !== 'none' && el.querySelector('input[name^="attr["]')) thongSo++;
        });
        var bao = document.querySelector('.js-khong-co-thong-so');
        if (bao) bao.style.display = thongSo === 0 ? '' : 'none';
    }

    sel.addEventListener('change', apDung);
    apDung();
})();

/* ==========================================================================
   Ô "Hiển thị [20] dòng" ở chân mọi bảng danh sách admin.

   Gắn ở document chứ không gắn từng ô: thanh phân trang do PHP dựng sẵn nên
   lúc nào cũng có mặt từ đầu, nhưng làm thế này thì bảng nào nạp bằng ajax
   sau đó cũng chạy luôn, khỏi phải nhớ gọi lại.
   ========================================================================== */
(function () {
    document.addEventListener('change', function (e) {
        var s = e.target;
        if (!s || !s.classList || !s.classList.contains('js-per-page')) return;

        var p = new URLSearchParams(s.getAttribute('data-qs') || '');
        p.set('per_page', s.value);

        /* Bỏ ?page: đang ở trang 9 của mức 10 dòng, đổi sang mức 100 thì trang 9
           không còn tồn tại. Về trang 1 cho chắc. */
        p.delete('page');

        var qs = p.toString();
        window.location.href = s.getAttribute('data-base') + (qs ? '?' + qs : '');
    });
})();

/* ============================================================
 * MÀN HẸP — BẢNG TRƯỢT NGANG, CÓ DÒNG GỢI Ý "VUỐT ĐỂ XEM THÊM"
 *
 * Lịch sử ba lần, để lần sau không quay lại hai cách đã bị khách từ chối:
 *
 *   12/11/2026  Khách: "các màn có những nút bị mất". Nguyên nhân: bảng dài hơn
 *               màn thì cuộn ngang, cột Thao tác lại là cột CUỐI nên nằm xa
 *               nhất bên phải, mà không có dấu hiệu nào bảo "vuốt sang phải".
 *   12/11       Em GHIM cột thao tác vào mép phải. Sai: 13/11 khách chụp màn Báo
 *               giá "bị che nhé, tất cả mobile đều bị cái chia thế này" — cột
 *               ghim đè lên cột Khách hàng, chữ bị cắt.
 *   13/11       Em đổi sang THẺ XẾP DỌC. Khách không chọn cách đó: gửi lại ảnh
 *               màn trượt cũ và nói muốn làm GIỐNG DẠNG TRƯỢT ĐÓ, chỉ là không
 *               để nó che.
 *
 * NAY: bảng trượt ngang bình thường — không ghim, không thẻ, không cột nào đè
 * cột nào. Phần "nút bị mất" của ngày 12/11 được chữa bằng MỘT DÒNG GỢI Ý nằm
 * ngay dưới bảng ("Vuốt ngang để xem đủ các cột"), chỉ hiện khi bảng thật sự
 * rộng hơn màn và người dùng chưa vuốt tới cuối. Gợi ý chữ chứ không phải phần
 * tử đè lên dữ liệu: nó không che gì được.
 *
 * Gắn bằng JS theo `.table-responsive` thay vì sửa từng view, vì có hàng chục
 * màn dùng khung này — màn viết sau tự được hưởng. Ẩn/hiện bằng thuộc tính
 * `hidden`; CSS (admin-theme.css mục 18) chỉ cho nó hiện ở màn hẹp.
 * ============================================================ */
(function () {
    'use strict';

    var CHU_GOI_Y = '↔ Vuốt ngang để xem đủ các cột';

    function goiYCua(khung){
        var ke = khung.nextElementSibling;
        if (ke && ke.classList.contains('goi-y-truot')) return ke;
        var d = document.createElement('div');
        d.className = 'goi-y-truot';
        d.hidden = true;
        d.textContent = CHU_GOI_Y;
        khung.parentNode.insertBefore(d, khung.nextSibling);
        return d;
    }

    function capNhat(khung){
        var d = goiYCua(khung);
        var conXa = khung.scrollWidth - khung.clientWidth - khung.scrollLeft;
        /* 4px: sai số làm tròn của trình duyệt khi cuộn tới sát mép phải */
        d.hidden = !(khung.scrollWidth - khung.clientWidth > 4 && conXa > 4);
    }

    function gan(){
        document.querySelectorAll('.table-responsive').forEach(function (khung) {
            if (!khung.querySelector('table')) return;
            if (!khung.__goiYTruot) {
                khung.__goiYTruot = true;
                khung.addEventListener('scroll', function () { capNhat(khung); }, { passive: true });
            }
            capNhat(khung);
        });
    }

    document.addEventListener('DOMContentLoaded', gan);
    window.addEventListener('load', gan);
    window.addEventListener('resize', gan);
})();

/* ============================================================
 * NÚT TĂNG / GIẢM SỐ LƯỢNG TRÊN ĐIỆN THOẠI
 *
 * Khách 13/11/2026: "Bán hàng (Số lượng) ==> thiếu số tăng giảm ở mobile".
 * Ô số trên máy tính có mũi tên tăng/giảm của trình duyệt; trên điện thoại
 * KHÔNG có — muốn đổi từ 1 lên 2 phải bấm vào ô, mở bàn phím, xoá, gõ lại.
 *
 * Gắn cho mọi ô số lượng của dòng hàng (tên ô kết thúc bằng `qty[]`: `qty[]`
 * của đơn mua, `line_qty[]` của phiếu kho, `<tiền tố>qty[]` của báo giá / hoá
 * đơn). Theo TÊN Ô chứ không sửa từng view: các view đó dựng dòng bằng JS,
 * theo dõi DOM thì dòng thêm sau cũng tự có nút, và màn viết sau cũng vậy.
 *
 * Chỉ HIỆN ở màn hẹp (CSS mục 18). Trên máy tính bọc khung vào nhưng
 * `display: contents` và ẩn hai nút, nên không đổi một điểm ảnh nào.
 *
 * Bước 1 đơn vị. KHÔNG giảm xuống dưới 1: số lượng 0 hay âm không có nghĩa
 * trong một dòng hàng, còn muốn bỏ dòng thì đã có nút xoá. Ô đang là số lẻ nhỏ
 * hơn 1 (0.5 lít) thì nút giảm không đổi nó. Sau khi đổi, bắn sự kiện `input`
 * để tổng tiền tính lại — các view đang nghe đúng sự kiện đó khi gõ.
 *
 * Không gắn cho ô chỉ đọc / vô hiệu (đơn đã khoá): nút bấm được mà ô không đổi
 * là một nút nói dối.
 * ============================================================ */
(function () {
    'use strict';

    var CHON = 'input[name$="qty[]"]';

    function lam3(n) { return Math.round(n * 1000) / 1000; }

    function buoc(o, huong) {
        if (o.readOnly || o.disabled) return;
        var n = parseFloat(String(o.value).replace(',', '.')) || 0;
        var kq = huong > 0 ? n + 1 : n - 1;
        if (huong < 0 && kq < 1) kq = Math.min(n, 1);
        var max = parseFloat(o.max);
        if (isFinite(max) && kq > max) kq = max;
        o.value = String(lam3(kq));
        o.dispatchEvent(new Event('input', { bubbles: true }));
        o.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function nut(chu, lop, nhan, huong, o) {
        var b = document.createElement('button');
        b.type = 'button';
        b.className = 'btn btn-outline-secondary sl-nut ' + lop;
        b.textContent = chu;
        b.setAttribute('aria-label', nhan);
        b.addEventListener('click', function () { buoc(o, huong); });
        return b;
    }

    function gan(o) {
        if (o.__soLuongNut || o.readOnly || o.disabled || !o.parentNode) return;
        o.__soLuongNut = true;
        var khung = document.createElement('div');
        khung.className = 'so-luong-nhom';
        o.parentNode.insertBefore(khung, o);
        khung.appendChild(nut('−', 'sl-giam', 'Giảm 1', -1, o));
        khung.appendChild(o);
        khung.appendChild(nut('+', 'sl-tang', 'Tăng 1', 1, o));
    }

    function quet(goc) {
        if (!goc || goc.nodeType !== 1) return;
        if (goc.matches && goc.matches(CHON)) gan(goc);
        if (goc.querySelectorAll) goc.querySelectorAll(CHON).forEach(gan);
    }

    function batDau() {
        quet(document.body);
        if (window.MutationObserver) {
            new MutationObserver(function (muts) {
                muts.forEach(function (m) { m.addedNodes.forEach(quet); });
            }).observe(document.body, { childList: true, subtree: true });
        }
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', batDau);
    else batDau();
})();
