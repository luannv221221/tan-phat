/* ==========================================================================
   CÁC Ô NHẬP SỐ trong form quản trị.

   FILE RIÊNG, NẠP TRONG <head> — không gộp vào admin.js được.

   admin.js nằm ở CUỐI <body>, tức là sau toàn bộ nội dung trang. Mà mẫu bảng
   dòng hàng của báo giá / hoá đơn / phiếu kho lại là <script> viết thẳng
   trong view, chạy ngay lúc trình duyệt đọc tới. Để mấy hàm này trong
   admin.js thì lúc view gọi oTien(...) chúng CHƯA tồn tại:

       Uncaught ReferenceError: oTien is not defined

   và bảng dòng hàng không dựng được dòng nào, nút "Thêm dòng" cũng chết —
   đúng 12 màn hình. Lỗi này chỉ lộ ra khi MỞ TRANG THẬT, grep mã nguồn
   không thấy gì.
   ========================================================================== */
(function () {
    'use strict';

    function soHoa(el, min, macDinh, step, max) {
        if (!el) return el;

        el.type = 'number';
        el.min  = String(min);
        el.step = step || 'any';
        if (max !== undefined) el.max = String(max);
        if ((el.value === '' || el.value === null) && macDinh !== null) el.value = String(macDinh);

        // Gõ tay giá trị ngoài khoảng thì kéo về mức hợp lệ khi rời ô.
        // Chặn ở đây cho người dùng thấy ngay; server vẫn tự lọc lại.
        el.addEventListener('blur', function () {
            var v = parseFloat(el.value);
            if (el.value === '' && macDinh === null) return;   // cho phép để trống
            if (!isFinite(v) || v < min) v = (macDinh !== null ? macDinh : min);
            if (max !== undefined && v > max) v = max;
            if (String(v) !== el.value) {
                el.value = String(v);
                el.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });

        return el;
    }

    /* Số lượng bán / nhập / xuất / chuyển: bắt buộc > 0, mặc định 1.
       min là 0.001 chứ không phải 1 để vẫn nhập được 2.5 lít dầu. */
    window.soLuong = function (el) { return soHoa(el, 0.001, 1, 'any'); };

    /* Số ĐẾM khi kiểm kê: 0 là hợp lệ và rất quan trọng — "sổ ghi 5, thực tế
       không còn cái nào". Ép về 1 như số lượng bán là che mất khoản thiếu. */
    window.soDem = function (el) { return soHoa(el, 0, 0, 'any'); };

    /* Tiền (VNĐ): không âm, bước 1 đồng. Để TRỐNG được — đơn giá bỏ trống
       nghĩa là chưa nhập, khác hẳn với 0 đồng. */
    window.oTien = function (el) { return soHoa(el, 0, null, '1'); };

    /* Phần trăm (CK, VAT): 0–100. */
    window.oPhanTram = function (el) { return soHoa(el, 0, null, 'any', 100); };
})();
