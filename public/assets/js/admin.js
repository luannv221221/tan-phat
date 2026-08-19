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

        function open() { render(''); input.select(); wrap.classList.add('is-open'); }
        function close() { wrap.classList.remove('is-open'); showSelected(); }

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
