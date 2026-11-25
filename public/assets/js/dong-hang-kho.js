/* ============================================================================
 * DÒNG HÀNG CỦA PHIẾU KHO — nhập kho, xuất kho, chuyển kho, kiểm kê.
 *
 * Khách ghi 25/11/2026 (chữ đỏ, việc mới): "Sửa các phiếu nhập kho, xuất
 * kho… liên quan hàng hóa trên mobile nhất quán giống bán hàng, mua hàng".
 * Đo trên điện thoại: ô chọn hàng của phiếu kho bị bóp còn ~80px, không gõ tìm
 * được, không có ảnh; chọn trùng mặt hàng thì ra hai dòng; nút xoá dòng 24px.
 * Đơn mua / báo giá đã làm xong cả mấy việc đó từ 05–13/11.
 *
 * File này lo phần CHUNG cho cả 8 màn (thêm + sửa của 4 loại phiếu), để không
 * phải chép một hàm gộp dòng 40 dòng vào 8 chỗ:
 *
 * Ô chọn hàng gõ tìm được + có ảnh thì mỗi màn tự dựng (class js-search,
 * data-img — admin.js tự dựng khung tìm, y như đơn mua). File này lo:
 *
 *   Gộp dòng trùng: chọn một mặt hàng đã có ở dòng khác thì CỘNG số lượng vào
 *        dòng cũ và bỏ dòng vừa chọn (luật khách chốt 13/11 cho đơn mua / báo
 *        giá / hoá đơn). Lắng nghe chung trên tbody#lines nên không phải sửa
 *        từng màn. Chỉ chạy khi người dùng ĐỔI ô chọn — mở phiếu cũ có sẵn hai
 *        dòng cùng mặt hàng thì không bị dồn sau lưng.
 *
 * Ô số lượng: `line_qty[]` (nhập / xuất / chuyển) hoặc `line_actual[]` (kiểm
 * kê — đếm một mặt hàng ở hai chỗ thì cộng lại là đúng số thực đếm).
 * ========================================================================== */
(function (w, d){
    'use strict';

    /* Ô số lượng là <input type="number"> (soLuong / soDem trong so-input.js):
       dấu chấm là dấu THẬP PHÂN ("2.5" lít), không phải dấu nghìn. */
    function so(v){
        var n = parseFloat(String(v == null ? '' : v).replace(',', '.'));
        return isFinite(n) ? n : 0;
    }

    function oSoLuong(tr){
        return tr.querySelector('input[name="line_qty[]"], input[name="line_actual[]"]');
    }

    function bao(tbody, chu){
        var bang = tbody.closest('table');
        var khung = bang ? bang.closest('.card-body') : null;
        if (!khung) return;
        var el = khung.parentNode.querySelector('.bao-gop-kho');
        if (!el){
            el = d.createElement('div');
            el.className = 'alert alert-info py-2 px-3 mb-0 small bao-gop-kho';
            el.setAttribute('role', 'status');
            el.style.borderRadius = '0';
            khung.parentNode.insertBefore(el, khung);
        }
        el.textContent = chu;
        el.style.display = 'block';
        clearTimeout(el.__an);
        el.__an = setTimeout(function (){ el.style.display = 'none'; }, 6000);
    }

    function gopNeuTrung(tbody, tr, sel){
        if (!sel.value) return;
        var dongCu = null, thuTu = 0, i = 0;
        Array.prototype.forEach.call(tbody.querySelectorAll('tr.line-row'), function (r){
            i++;
            if (r === tr || dongCu) return;
            var s = r.querySelector('select[name="line_part[]"]');
            if (s && s.value === sel.value){ dongCu = r; thuTu = i; }
        });
        if (!dongCu) return;

        var oCu = oSoLuong(dongCu), oMoi = oSoLuong(tr);
        if (!oCu) return;
        var them = oMoi && oMoi.value !== '' ? so(oMoi.value) : 1;
        if (them <= 0 && oMoi && oMoi.name === 'line_qty[]') them = 1;
        var nay = Math.round((so(oCu.value) + them) * 1000) / 1000;
        oCu.value = String(nay);

        /* Đơn giá (phiếu nhập): dòng cũ để trống mà dòng mới đã gõ thì lấy của
           dòng mới — không mất con số người dùng vừa nhập. */
        var gCu = dongCu.querySelector('input[name="line_cost[]"]'),
            gMoi = tr.querySelector('input[name="line_cost[]"]');
        if (gCu && gMoi && gCu.value === '' && gMoi.value !== '') gCu.value = gMoi.value;

        var ten = sel.options[sel.selectedIndex] ? sel.options[sel.selectedIndex].textContent : '';
        tr.remove();
        oCu.dispatchEvent(new Event('input', { bubbles: true }));   // tổng tiền tính lại

        dongCu.style.transition = 'background-color .3s';
        dongCu.style.backgroundColor = '#fff3cd';
        setTimeout(function (){ dongCu.style.backgroundColor = ''; }, 2200);
        if (dongCu.scrollIntoView) dongCu.scrollIntoView({ block: 'nearest' });

        bao(tbody, ten + ' đã có ở dòng ' + thuTu + ' — đã cộng thêm ' + them
                 + ' vào số lượng (nay là ' + nay + ').');
    }

    d.addEventListener('change', function (e){
        var sel = e.target;
        if (!sel || sel.name !== 'line_part[]' || !sel.closest) return;
        var tbody = sel.closest('tbody#lines');
        var tr = sel.closest('tr.line-row');
        if (tbody && tr) gopNeuTrung(tbody, tr, sel);
    });

})(window, document);
