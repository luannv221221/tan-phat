/*
 * MÃ QR CHUYỂN KHOẢN (VietQR) — vẽ tại chỗ + gửi link thanh toán.
 *
 * Chuỗi VietQR luôn dựng ở MÁY CHỦ (App\core\VietQr, có test so với mẫu công
 * khai). File này chỉ VẼ chuỗi đó thành SVG bằng qrcode-generator 1.4.4 (nạp từ
 * cdnjs, có integrity, ngay trước file này) — không gửi số tiền / số tài khoản
 * đi đâu.
 *
 * Dùng ở: mẫu in hoá đơn, trang Đặt hàng thành công, trang thanh toán công khai
 * /thanh-toan/<mã>, danh sách hoá đơn (cột QR + nút Gửi). Một chỗ vẽ cho cả bốn,
 * để không có chuyện một trang vẽ khác trang kia.
 *
 * Đánh dấu trong HTML:
 *   <div data-vietqr="000201...">        vẽ mã vào đây
 *   <button data-chep="chuỗi">            chép chuỗi vào bộ nhớ tạm
 *   <button data-tai-qr="#id" data-ten>   tải mã trong #id về thành ảnh PNG
 *   <button data-link-thanh-toan data-url> mở hộp thoại Gửi thanh toán (admin)
 */
(function () {
    'use strict';

    /* Vẽ mọi [data-vietqr] chưa vẽ. Mức sửa lỗi M, lề trắng 4 ô theo chuẩn QR.
       Không tải được thư viện thì giữ nguyên chữ báo lỗi có sẵn trong ô. */
    function veQr(goc) {
        if (typeof window.qrcode !== 'function') return;
        var o = (goc || document).querySelectorAll('[data-vietqr]');
        for (var i = 0; i < o.length; i++) {
            var chuoi = o[i].getAttribute('data-vietqr');
            if (!chuoi || o[i].getAttribute('data-da-ve') === chuoi) continue;
            try {
                var qr = window.qrcode(0, 'M');
                qr.addData(chuoi);
                qr.make();
                o[i].innerHTML = qr.createSvgTag({ cellSize: 4, scalable: true });
                o[i].setAttribute('data-da-ve', chuoi);
            } catch (e) { /* để nguyên chữ báo */ }
        }
    }

    function baoTam(nut, chu) {
        if (!nut) return;
        var cu = nut.getAttribute('data-chu-cu') || nut.innerHTML;
        nut.setAttribute('data-chu-cu', cu);
        nut.textContent = chu;
        setTimeout(function () { nut.innerHTML = cu; }, 1600);
    }

    /* Chép chữ: Clipboard API khi có (https / localhost), không thì textarea tạm. */
    function chep(chu, nut) {
        var xong = function () { baoTam(nut, 'Đã chép ✓'); };
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(chu).then(xong, function () { chepCu(chu); xong(); });
        } else { chepCu(chu); xong(); }
    }
    function chepCu(chu) {
        var t = document.createElement('textarea');
        t.value = chu; t.setAttribute('readonly', '');
        t.style.position = 'fixed'; t.style.opacity = '0';
        document.body.appendChild(t); t.select();
        try { document.execCommand('copy'); } catch (e) {}
        document.body.removeChild(t);
    }

    /* Tải mã thành ảnh PNG — trên điện thoại khách không quét được màn hình của
       chính mình: lưu ảnh rồi chọn ảnh đó trong mục quét QR của app ngân hàng. */
    function taiAnh(o, ten) {
        var svg = o && o.querySelector('svg');
        if (!svg) return;
        var xml = new XMLSerializer().serializeToString(svg);
        var anh = new Image();
        anh.onload = function () {
            var c = document.createElement('canvas');
            c.width = 720; c.height = 720;
            var g = c.getContext('2d');
            g.fillStyle = '#fff'; g.fillRect(0, 0, c.width, c.height);
            g.imageSmoothingEnabled = false;
            g.drawImage(anh, 0, 0, c.width, c.height);
            var a = document.createElement('a');
            a.href = c.toDataURL('image/png');
            a.download = (ten || 'ma-qr-thanh-toan') + '.png';
            document.body.appendChild(a); a.click(); document.body.removeChild(a);
        };
        anh.src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(xml);
    }

    function dongChu(nhan, giaTri) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(nhan + ': '));
        var b = document.createElement('b');
        b.textContent = giaTri;
        d.appendChild(b);
        return d;
    }
    function soTien(n) { return String(Math.round(n)).replace(/\B(?=(\d{3})+(?!\d))/g, '.'); }

    /* ------------------------------------------------------------------
       HỘP THOẠI "GỬI THANH TOÁN" — danh sách hoá đơn (admin).
       Lấy link qua JSON (tuyến có phân quyền), rồi cho chọn cách gửi:
       chia sẻ (điện thoại: hiện Zalo, Messenger...), chép tin nhắn, mở Zalo
       của khách, SMS. Mọi chữ từ máy chủ đi vào trang bằng textContent.
       ------------------------------------------------------------------ */
    function moHopGui(nut) {
        var hop = document.getElementById('modalGuiTT');
        if (!hop) return;
        var q = function (s) { return hop.querySelector(s); };
        q('.tt-dang-tai').classList.remove('d-none');
        q('.tt-loi').classList.add('d-none');
        q('.tt-noi-dung').classList.add('d-none');
        q('.tt-so').textContent = '';
        if (window.jQuery) window.jQuery(hop).modal('show');

        fetch(nut.getAttribute('data-url'), { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
            .then(function (kq) {
                q('.tt-dang-tai').classList.add('d-none');
                if (!kq.ok || !kq.j || !kq.j.link) {
                    q('.tt-loi').textContent = (kq.j && kq.j.error) ? kq.j.error : 'Không lấy được link thanh toán.';
                    q('.tt-loi').classList.remove('d-none');
                    return;
                }
                var j = kq.j;
                q('.tt-so').textContent = j.so;
                var o = q('.tt-qr');
                o.removeAttribute('data-da-ve');
                o.setAttribute('data-vietqr', j.qr.chuoi);
                veQr(hop);
                var tt = q('.tt-thong-tin');
                tt.innerHTML = '';
                if (j.tenKhach) tt.appendChild(dongChu('Khách', j.tenKhach));
                tt.appendChild(dongChu('Ngân hàng', j.qr.ngan_hang));
                tt.appendChild(dongChu('Số tài khoản', j.qr.so_tk));
                if (j.qr.chu_tk) tt.appendChild(dongChu('Chủ tài khoản', j.qr.chu_tk));
                tt.appendChild(dongChu('Số tiền', soTien(j.qr.so_tien) + ' đ'));
                tt.appendChild(dongChu('Nội dung', j.qr.noi_dung));
                q('.tt-link').value = j.link;
                q('.tt-tin').value = j.tinNhan;
                q('.tt-mo').setAttribute('href', j.link);

                var zalo = q('.tt-zalo'), sms = q('.tt-sms');
                if (j.sdt) {
                    zalo.setAttribute('href', 'https://zalo.me/' + encodeURIComponent(j.sdt));
                    zalo.classList.remove('d-none');
                    sms.setAttribute('data-sdt', j.sdt);
                    sms.classList.remove('d-none');
                } else {
                    zalo.classList.add('d-none');
                    sms.classList.add('d-none');
                }
                if (navigator.share) q('.tt-chia-se').classList.remove('d-none');
                q('.tt-noi-dung').classList.remove('d-none');
            })
            .catch(function () {
                q('.tt-dang-tai').classList.add('d-none');
                q('.tt-loi').textContent = 'Không lấy được link thanh toán — kiểm tra mạng rồi thử lại.';
                q('.tt-loi').classList.remove('d-none');
            });
    }

    document.addEventListener('click', function (e) {
        var nut = e.target.closest ? e.target.closest('[data-chep],[data-tai-qr],[data-link-thanh-toan],.tt-chep-link,.tt-chep-tin,.tt-chia-se,.tt-zalo,.tt-sms') : null;
        if (!nut) return;
        var hop = document.getElementById('modalGuiTT');
        var tin = hop ? hop.querySelector('.tt-tin') : null;

        if (nut.hasAttribute('data-link-thanh-toan')) { e.preventDefault(); moHopGui(nut); return; }
        if (nut.hasAttribute('data-chep')) { e.preventDefault(); chep(nut.getAttribute('data-chep'), nut); return; }
        if (nut.hasAttribute('data-tai-qr')) {
            e.preventDefault();
            taiAnh(document.querySelector(nut.getAttribute('data-tai-qr')), nut.getAttribute('data-ten'));
            return;
        }
        if (nut.classList.contains('tt-chep-link')) { e.preventDefault(); chep(hop.querySelector('.tt-link').value, nut); return; }
        if (nut.classList.contains('tt-chep-tin')) { e.preventDefault(); chep(tin.value, nut); return; }
        if (nut.classList.contains('tt-chia-se')) {
            e.preventDefault();
            if (navigator.share) navigator.share({ text: tin.value }).catch(function () {});
            return;
        }
        /* Zalo không nhận tin nhắn soạn sẵn qua link: chép trước, mở khung chat, dán. */
        if (nut.classList.contains('tt-zalo')) { chep(tin.value, null); return; }
        if (nut.classList.contains('tt-sms')) {
            e.preventDefault();
            window.location.href = 'sms:' + nut.getAttribute('data-sdt') + '?body=' + encodeURIComponent(tin.value);
        }
    });

    window.VietQrVe = veQr;
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function () { veQr(document); });
    else veQr(document);
})();
