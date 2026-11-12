/* =========================================================================
 * MENU WEBSITE — CÂY KÉO THẢ + Ô SỬA NGAY BÊN PHẢI
 *
 * Dựng 12/11/2026, theo cách làm việc của màn menu bên khách đang dùng.
 *
 * VIẾT TAY, KHÔNG KÉO THƯ VIỆN VỀ. Cả dự án không dùng CDN và không có
 * jQuery UI; thêm một thư viện kéo thả (và bản đồ nguồn của nó) cho đúng một
 * màn hình là cái giá đắt hơn 150 dòng dưới đây. Dùng HTML5 drag & drop có
 * sẵn trong trình duyệt.
 *
 * KHÔNG TỰ LƯU MỖI LẦN THẢ. Kéo nhầm một mục rồi tự lưu thì muốn về như cũ
 * phải kéo ngược lại cho đúng — mà "đúng" là cái người ta vừa mất. Ở đây chỉ
 * hiện thanh "Thứ tự đã thay đổi" kèm nút Lưu; chưa bấm thì tải lại trang là
 * sạch.
 * ========================================================================= */
(function (w, d){
    'use strict';

    w.oCayMenu = function (tuyChon){

        var goc  = tuyChon && tuyChon.goc ? tuyChon.goc : '';
        var nhan = tuyChon && tuyChon.nhan ? tuyChon.nhan : 'mục';

        var cay    = d.getElementById('cay-menu');
        var form   = d.getElementById('f-menu');
        if (!form) return;

        var oLabel = d.getElementById('o-label');
        var oKieu  = d.getElementById('o-kieu-lien-ket');
        var oParent= d.getElementById('o-parent');
        var oTarget= d.getElementById('o-target');
        var oSort  = d.getElementById('o-sort');
        var oStatus= d.getElementById('o-status');
        var tieuDe = d.getElementById('tieu-de-form');
        var nutLuu = d.getElementById('nut-luu');
        var nutThoi= d.getElementById('nut-thoi');
        var nutThem= d.getElementById('nut-them-moi');

        var khoiKieu = d.querySelectorAll('[data-kieu]');

        /* ---------------------------------------------------------------
         * Bật/tắt ô đích theo kiểu đang chọn.
         * Dùng `disabled` chứ không chỉ ẩn: ba ô chọn đích TRÙNG TÊN
         * `target_id`, ô ẩn mà vẫn bật thì vẫn gửi lên và máy chủ lấy ô cuối
         * cùng -> lưu sai đích.
         * --------------------------------------------------------------- */
        function doiTheoKieu(){
            Array.prototype.forEach.call(khoiKieu, function (k){
                var hop = k.getAttribute('data-kieu') === oKieu.value;
                k.style.display = hop ? '' : 'none';
                Array.prototype.forEach.call(k.querySelectorAll('select, input'), function (o){
                    o.disabled = !hop;
                });
            });
        }
        if (oKieu) oKieu.addEventListener('change', doiTheoKieu);

        function datDichDangChon(giaTri){
            Array.prototype.forEach.call(khoiKieu, function (k){
                var o = k.querySelector('select[name="target_id"]');
                if (o) o.value = giaTri || '';
            });
        }

        /* --------------------------------------------------------------- */
        function veThemMoi(){
            form.action = goc + '/add';
            form.reset();
            if (tieuDe) tieuDe.innerHTML = '<i class="fas fa-plus-circle mr-2"></i>Thêm ' + nhan;
            if (nutLuu) nutLuu.innerHTML = '<i class="fas fa-save mr-1"></i> Thêm';
            if (nutThoi) nutThoi.classList.add('d-none');
            boDanhDau();
            doiTheoKieu();
        }

        function boDanhDau(){
            if (!cay) return;
            Array.prototype.forEach.call(cay.querySelectorAll('.muc-menu'), function (li){
                li.classList.remove('active');
            });
        }

        function moSua(li){
            form.action = goc + '/edit/' + li.getAttribute('data-id');

            oLabel.value  = li.getAttribute('data-label') || '';
            oKieu.value   = li.getAttribute('data-link_type') || 'url';
            oParent.value = li.getAttribute('data-parent') || '';
            oTarget.value = li.getAttribute('data-target') || '_self';
            oSort.value   = li.getAttribute('data-sort_order') || '0';
            oStatus.checked = li.getAttribute('data-status') === '1';

            var oUrl = form.querySelector('[data-kieu="url"] input[name="url"]');
            if (oUrl) oUrl.value = li.getAttribute('data-url') || '';
            datDichDangChon(li.getAttribute('data-target_id'));

            /* KHÔNG CHO TỰ LÀM CHA MÌNH. Máy chủ cũng chặn (buildData), nhưng
               để nguyên trong ô chọn thì người dùng chọn được rồi lưu xong mới
               thấy không có gì đổi — im lặng và khó hiểu. */
            var minh = li.getAttribute('data-id');
            Array.prototype.forEach.call(oParent.options, function (op){
                op.disabled = (op.value === minh);
            });

            if (tieuDe) tieuDe.innerHTML = '<i class="fas fa-edit mr-2"></i>Sửa: ' + li.getAttribute('data-label');
            if (nutLuu) nutLuu.innerHTML = '<i class="fas fa-save mr-1"></i> Cập nhật';
            if (nutThoi) nutThoi.classList.remove('d-none');

            boDanhDau();
            li.classList.add('active');
            doiTheoKieu();
        }

        if (nutThem) nutThem.addEventListener('click', veThemMoi);
        if (nutThoi) nutThoi.addEventListener('click', veThemMoi);

        /* ---------------------------------------------------------------
         * KÉO THẢ
         *
         * Cấp của mục thả được quyết định bởi mục NẰM NGAY TRÊN nó sau khi
         * thả: trên là mục gốc -> thành con của mục đó; trên là mục con ->
         * thành em của mục con ấy (cùng cha); không có gì ở trên -> là gốc.
         *
         * Quy tắc này đọc được bằng mắt ngay trên màn hình, nên không cần
         * kéo ngang để chọn cấp — kéo ngang trên HTML5 DnD rất khó bấm trúng.
         * --------------------------------------------------------------- */
        var dangKeo = null;
        var thanh   = d.getElementById('thanh-thu-tu');
        var oThuTu  = d.getElementById('o-thu-tu');

        function moiMuc(){
            return cay ? Array.prototype.slice.call(cay.querySelectorAll('.muc-menu')) : [];
        }

        if (cay){
            cay.addEventListener('dragstart', function (e){
                var li = e.target.closest ? e.target.closest('.muc-menu') : null;
                if (!li) return;
                dangKeo = li;
                li.style.opacity = '.4';
                if (e.dataTransfer) e.dataTransfer.effectAllowed = 'move';
            });

            cay.addEventListener('dragend', function (){
                if (dangKeo) dangKeo.style.opacity = '';
                dangKeo = null;
                tinhLaiCap();
            });

            cay.addEventListener('dragover', function (e){
                e.preventDefault();
                var li = e.target.closest ? e.target.closest('.muc-menu') : null;
                if (!li || !dangKeo || li === dangKeo) return;

                var o = li.getBoundingClientRect();
                var tren = (e.clientY - o.top) < (o.height / 2);
                cay.insertBefore(dangKeo, tren ? li : li.nextSibling);
            });
        }

        /* Tính lại cấp + cha sau khi thả, rồi dựng mảng gửi lên máy chủ. */
        function tinhLaiCap(){
            var ds = moiMuc();
            if (!ds.length) return;

            var ketQua = [];
            var chaHienTai = '';

            ds.forEach(function (li, i){
                var capCu = li.getAttribute('data-cap') === '1';
                var cha   = '';

                if (i === 0){
                    cha = '';                       // mục đầu luôn là gốc
                } else {
                    var tren = ds[i - 1];
                    var trenLaGoc = tren.getAttribute('data-cap') !== '1';
                    /* Giữ nguyên cấp của mục đang kéo nếu được: người ta kéo để
                       đổi THỨ TỰ là chính, đổi cấp là chuyện hiếm hơn. */
                    if (capCu) cha = trenLaGoc ? tren.getAttribute('data-id') : tren.getAttribute('data-parent');
                    else       cha = '';
                }

                if (cha === null) cha = '';
                li.setAttribute('data-parent', cha);
                li.setAttribute('data-cap', cha ? '1' : '0');
                li.style.paddingLeft = (12 + (cha ? 28 : 0)) + 'px';

                ketQua.push({ id: parseInt(li.getAttribute('data-id'), 10),
                              parent_id: cha ? parseInt(cha, 10) : 0,
                              sort_order: i });
                chaHienTai = cha;
            });

            if (oThuTu) oThuTu.value = JSON.stringify(ketQua);
            if (thanh)  thanh.classList.remove('d-none');
        }

        doiTheoKieu();
    };

})(window, document);
