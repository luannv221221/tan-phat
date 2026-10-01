/* NÚT + CẠNH Ô CHỌN — thêm nhanh một dòng danh mục ngay trên form.
 *
 * Luật của hệ thống: giá trị lặp lại giữa các bản ghi thì phải CHỌN từ danh mục,
 * không gõ tay. Luật đó chỉ sống được nếu thêm vào danh mục nhanh hơn gõ tay —
 * nếu không, người dùng sẽ tìm đường lách. Nút này để thêm tại chỗ rồi chọn
 * luôn, khỏi bỏ dở phiếu đang làm để sang màn Danh mục.
 *
 * Trùng thì MÁY CHỦ dùng lại dòng cũ chứ không tạo bản sao (đối chiếu bằng
 * slug), nên gõ "BỘ LỌC GIÓ" khi đã có "Bộ lọc gió" vẫn ra đúng một dòng.
 * Máy chủ cũng kiểm quyền THÊM ở đúng màn danh mục tương ứng.
 *
 * Trong view, dùng helper PHP nut_them_nhanh() thay vì viết tay:
 *   <div class="input-group">
 *       <select name="unit_id" class="form-control">…</select>
 *       <div class="input-group-append">{!! nut_them_nhanh('part-unit', 'unit_id', ['nhan' => 'đơn vị tính']) !!}</div>
 *   </div>
 *
 * Thuộc tính trên nút:
 *   data-them-nhanh  loại danh mục (máy chủ có danh sách trắng)
 *   data-o           name của ô chọn sẽ được điền
 *   data-cha         name của ô chọn cha (danh mục cây / chuỗi phụ thuộc)
 *   data-nhan        chữ hiện trong hộp ("đơn vị tính")
 *   data-vd          ví dụ trong ô nhập
 *   data-day         "1" = ô này là đầu một chuỗi, thêm xong phải nạp lại ô sau
 *   data-url         đường POST (mặc định <web>/admin/them-nhanh/danh-muc)
 */
(function () {

    var hop = null;

    function dungHop() {
        if (hop) return hop;

        hop = document.createElement('div');
        hop.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;z-index:2000;display:none;'
                          + 'background:rgba(0,0,0,.45);align-items:center;justify-content:center;padding:16px';
        hop.innerHTML =
            '<div style="background:#fff;border-radius:6px;max-width:420px;width:100%;box-shadow:0 8px 32px rgba(0,0,0,.3)">'
          + '<div style="padding:12px 16px;border-bottom:1px solid #dee2e6;font-weight:600" data-vt="tieu"></div>'
          + '<div style="padding:16px">'
          + '<label style="font-size:.875rem;margin-bottom:4px" data-vt="nhan"></label>'
          + '<input type="text" class="form-control" data-vt="o"/>'
          + '<div class="text-danger small mt-1" data-vt="loi" style="display:none"></div>'
          + '<div class="text-muted small mt-2">Đã có sẵn thì hệ thống dùng lại dòng cũ, không tạo dòng trùng.</div>'
          + '</div>'
          + '<div style="padding:12px 16px;border-top:1px solid #dee2e6;text-align:right">'
          + '<button type="button" class="btn btn-default btn-sm mr-1" data-vt="thoi">Thôi</button>'
          + '<button type="button" class="btn btn-primary btn-sm" data-vt="luu">Thêm</button>'
          + '</div></div>';
        document.body.appendChild(hop);

        hop.querySelector('[data-vt="thoi"]').addEventListener('click', dong);
        hop.addEventListener('click', function (e) { if (e.target === hop) dong(); });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && hop.style.display !== 'none') dong();
        });

        return hop;
    }

    function dong() { if (hop) hop.style.display = 'none'; }

    function o(vt) { return hop.querySelector('[data-vt="' + vt + '"]'); }

    function baoLoi(chu) {
        var l = o('loi');
        l.textContent = chu || '';
        l.style.display = chu ? '' : 'none';
    }

    function goc(nut) {
        var u = nut.getAttribute('data-url');
        if (u) return u.replace(/\/+$/, '');
        var m = document.querySelector('[data-them-nhanh-url]');
        if (m) return m.getAttribute('data-them-nhanh-url').replace(/\/+$/, '');
        // Lùi: dựng từ đường đang mở (…/admin/<gì đó>) — giữ được cả khi view quên data-url
        return location.pathname.replace(/(\/admin)(\/.*)?$/, '$1') + '/them-nhanh/danh-muc';
    }

    function mo(nut) {
        var loai = nut.getAttribute('data-them-nhanh');
        var tenO = nut.getAttribute('data-o');
        var nhan = nut.getAttribute('data-nhan') || 'dòng mới';
        var form = nut.form || (nut.closest ? nut.closest('form') : null);
        if (!loai || !tenO || !form) return;

        var dich = form.querySelector('[name="' + tenO + '"]');
        if (!dich) return;

        /* Danh mục cây / chuỗi phụ thuộc: chưa chọn ô cha thì không biết thêm
           dòng mới vào đâu. */
        var cha = 0;
        var tenCha = nut.getAttribute('data-cha');
        if (tenCha){
            var oCha = form.querySelector('[name="' + tenCha + '"]');
            cha = oCha ? oCha.value : '';
            var batBuoc = nut.getAttribute('data-cha-bat-buoc') === '1';
            if (!cha && batBuoc){
                alert('Chọn ' + (nut.getAttribute('data-nhan-cha') || 'mục cha') + ' trước đã.');
                return;
            }
        }

        dungHop();
        o('tieu').textContent = 'Thêm ' + nhan;
        o('nhan').textContent = 'Tên ' + nhan;
        o('o').value = '';
        o('o').placeholder = nut.getAttribute('data-vd') || '';
        baoLoi('');
        hop.style.display = 'flex';
        o('o').focus();

        // Thay nút Thêm bằng bản sao để bỏ handler của lần mở trước
        var cu  = o('luu');
        var luu = cu.cloneNode(true);
        cu.parentNode.replaceChild(luu, cu);

        function gui() {
            var ten = o('o').value.trim();
            if (!ten) { baoLoi('Chưa nhập tên ' + nhan); return; }

            luu.disabled = true;
            luu.textContent = 'Đang thêm…';

            var d = new FormData();
            d.append('loai', loai);
            d.append('ten', ten);
            d.append('cha', cha || 0);
            var tok = form.querySelector('input[name="_token"]');
            if (tok) d.append('_token', tok.value);

            fetch(goc(nut), { method: 'POST', body: d, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (kq) {
                    luu.disabled = false;
                    luu.textContent = 'Thêm';
                    if (!kq || !kq.ok) { baoLoi((kq && kq.loi) || 'Không thêm được'); return; }
                    gan(dich, kq, nut.getAttribute('data-day') === '1');
                    dong();
                })
                .catch(function () {
                    luu.disabled = false;
                    luu.textContent = 'Thêm';
                    baoLoi('Không gọi được máy chủ');
                });
        }

        luu.addEventListener('click', gui);
        o('o').onkeydown = function (e) { if (e.key === 'Enter') { e.preventDefault(); gui(); } };
    }

    /* Gắn dòng vừa thêm vào ô chọn rồi chọn luôn nó */
    function gan(dich, kq, day) {
        var da = null, i;
        for (i = 0; i < dich.options.length; i++)
            if (String(dich.options[i].value) === String(kq.c)) da = dich.options[i];

        if (!da) {
            da = new Option(kq.n, kq.c);
            dich.appendChild(da);
        }
        dich.disabled = false;
        dich.value = kq.c;

        /* Ô này là đầu một chuỗi (hãng -> model -> năm): báo cho ô sau nạp lại. */
        if (day) dich.dispatchEvent(new Event('change', { bubbles: true }));
    }

    /* Bắt ở document chứ không gắn vào từng nút: hộp "thêm xe nhanh" và các ô
       chọn sinh sau khi trang đã tải cũng phải chạy được. */
    document.addEventListener('click', function (e) {
        var nut = e.target.closest ? e.target.closest('[data-them-nhanh]') : null;
        if (nut) { e.preventDefault(); mo(nut); }
    });
})();
