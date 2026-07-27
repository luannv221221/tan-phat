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
