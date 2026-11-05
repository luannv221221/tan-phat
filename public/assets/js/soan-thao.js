/* ============================================================================
 * TRÌNH SOẠN THẢO CÓ ĐỊNH DẠNG — tài liệu khách 05/11/2026.
 *
 * Ảnh mẫu khách gửi là màn "Sửa bài viết" có thanh công cụ CKEditor. Đây là
 * bản tự viết làm đúng những việc người viết bài thật sự dùng, vì:
 *
 *   - KHÔNG THÊM PHỤ THUỘC NGOÀI. Mọi thư viện của dự án đều nằm trong
 *     public/assets, không gọi CDN. Một trình soạn thảo nạp từ CDN là người
 *     khác quyết định mã nào chạy trong màn quản trị của gara — mà màn đó là
 *     nơi có quyền cao nhất.
 *   - CKEditor 4 (đúng bản trong ảnh) đã hết hạn hỗ trợ miễn phí.
 *
 * Cần đúng CKEditor thì thay được: chỗ nối duy nhất là hàm window.oSoanThao()
 * — nó nhận một <textarea> và tự lo phần còn lại.
 *
 * AN TOÀN: phần lọc Ở ĐÂY chỉ để nội dung dán vào trông gọn. CHỐT THẬT nằm ở
 * máy chủ (loc_html_an_toan() trong app/helpers/functions.php). Không bao giờ
 * tin lớp lọc chạy trên trình duyệt — ai cũng tắt được JavaScript rồi gửi
 * thẳng POST.
 * ========================================================================== */
(function (w, d){
    'use strict';

    /* Giữ ĐÚNG danh sách của máy chủ. Lệch nhau thì người viết thấy một đằng,
       lưu xong hiện ra một nẻo — mà không có thông báo nào. */
    var THE_CHO = ('p br hr strong b em i u s sub sup mark h2 h3 h4 h5 ul ol li '
                 + 'blockquote pre code a img figure figcaption table thead tbody '
                 + 'tfoot caption tr th td span div').split(' ');
    var THE_CAM = ('script style iframe frame frameset object embed applet form '
                 + 'input button select option textarea link meta base svg math '
                 + 'template noscript').split(' ');

    function urlAnToan(u){
        var s = String(u || '').replace(/[\u0000- ]+/g, '').toLowerCase();
        if (s === '') return false;
        var cam = ['javascript:', 'vbscript:', 'data:', 'file:', 'about:'];
        for (var i = 0; i < cam.length; i++){ if (s.indexOf(cam[i]) === 0) return false; }
        return true;
    }

    /** Dọn một đoạn HTML dán vào — cùng ba mức xử lý với bản PHP */
    function locHtml(html){
        var hop = d.createElement('div');
        hop.innerHTML = String(html || '');

        THE_CAM.forEach(function (t){
            var ds = hop.querySelectorAll(t);
            for (var i = ds.length - 1; i >= 0; i--){ ds[i].parentNode.removeChild(ds[i]); }
        });

        var tatCa = hop.querySelectorAll('*');
        for (var i = tatCa.length - 1; i >= 0; i--){
            var el  = tatCa[i];
            var ten = el.nodeName.toLowerCase();

            if (THE_CHO.indexOf(ten) === -1){
                // Thẻ lạ: nhấc nội dung ra, bỏ thẻ
                while (el.firstChild) el.parentNode.insertBefore(el.firstChild, el);
                el.parentNode.removeChild(el);
                continue;
            }
            for (var j = el.attributes.length - 1; j >= 0; j--){
                var tt = el.attributes[j].name.toLowerCase();
                var gt = el.attributes[j].value;
                var giu = (tt === 'href' || tt === 'src' || tt === 'alt' || tt === 'title'
                        || tt === 'target' || tt === 'rel' || tt === 'colspan'
                        || tt === 'rowspan' || tt === 'width' || tt === 'height');
                if (!giu || ((tt === 'href' || tt === 'src') && !urlAnToan(gt))){
                    el.removeAttribute(el.attributes[j].name);
                }
            }
        }
        return hop.innerHTML;
    }

    /* Thanh công cụ. Mỗi nút: [lệnh, tham số, nhãn, chú giải].
       Nhãn là CHỮ chứ không phải icon — bộ icon của màn quản trị (Lucide)
       không có đủ ký hiệu soạn thảo, mượn icon gần giống là người dùng đoán
       sai chức năng. */
    var NUT = [
        ['bold',              '',      'B',   'Đậm (Ctrl+B)'],
        ['italic',            '',      'I',   'Nghiêng (Ctrl+I)'],
        ['underline',         '',      'U',   'Gạch chân (Ctrl+U)'],
        ['strikeThrough',     '',      'S',   'Gạch ngang'],
        ['|',                 '',      '',    ''],
        ['formatBlock',       'h2',    'H2',  'Tiêu đề lớn'],
        ['formatBlock',       'h3',    'H3',  'Tiêu đề nhỏ'],
        ['formatBlock',       'p',     '¶',   'Đoạn văn thường'],
        ['|',                 '',      '',    ''],
        ['insertUnorderedList', '',    '• —', 'Danh sách chấm'],
        ['insertOrderedList',   '',    '1.',  'Danh sách số'],
        ['formatBlock',       'blockquote', '❝', 'Trích dẫn'],
        ['|',                 '',      '',    ''],
        ['_lienket',          '',      '🔗',  'Chèn liên kết'],
        ['unlink',            '',      '⛓',   'Bỏ liên kết'],
        ['_anh',              '',      '🖼',  'Chèn ảnh theo đường dẫn'],
        ['|',                 '',      '',    ''],
        ['removeFormat',      '',      'Tx',  'Xoá định dạng'],
        ['_ma',               '',      '</>', 'Xem / sửa mã HTML']
    ];

    /**
     * Biến một <textarea> thành ô soạn thảo.
     * Gọi lại trên cùng một ô thì bỏ qua (view nào lỡ gọi hai lần cũng không sao).
     */
    w.oSoanThao = function (oVung){
        if (!oVung || oVung.getAttribute('data-soan-da-gan') === '1') return;
        oVung.setAttribute('data-soan-da-gan', '1');

        var boc = d.createElement('div');
        boc.className = 'soan-thao';

        var thanh = d.createElement('div');
        thanh.className = 'soan-thao__thanh';

        var khung = d.createElement('div');
        khung.className = 'soan-thao__khung';
        khung.setAttribute('contenteditable', 'true');
        khung.innerHTML = oVung.value || '';

        oVung.parentNode.insertBefore(boc, oVung);
        boc.appendChild(thanh);
        boc.appendChild(khung);
        boc.appendChild(oVung);
        oVung.classList.add('soan-thao__nguon');
        oVung.style.display = 'none';

        var dangXemMa = false;

        function dongBo(){ if (!dangXemMa) oVung.value = khung.innerHTML; }

        NUT.forEach(function (n){
            if (n[0] === '|'){
                var v = d.createElement('span');
                v.className = 'soan-thao__vach';
                thanh.appendChild(v);
                return;
            }
            var b = d.createElement('button');
            b.type = 'button';                       // KHÔNG để submit form
            b.className = 'soan-thao__nut';
            b.title = n[3];
            b.textContent = n[2];
            b.addEventListener('mousedown', function (e){
                /* Chặn mousedown chứ không phải click: nhấn chuột xuống là ô
                   soạn thảo mất con trỏ, lệnh định dạng sau đó không biết áp
                   vào đoạn chữ nào. */
                e.preventDefault();
                lam(n[0], n[1]);
            });
            thanh.appendChild(b);
        });

        function lam(lenh, thamSo){
            if (dangXemMa && lenh !== '_ma') return;   // đang sửa mã thì nút khác vô nghĩa
            khung.focus();

            if (lenh === '_lienket'){
                var u = w.prompt('Dán địa chỉ liên kết:', 'https://');
                if (!u) return;
                if (!urlAnToan(u)){ w.alert('Địa chỉ này không dùng được.'); return; }
                d.execCommand('createLink', false, u);
                return;
            }
            if (lenh === '_anh'){
                var a = w.prompt('Dán đường dẫn ảnh:', '');
                if (!a) return;
                if (!urlAnToan(a)){ w.alert('Đường dẫn ảnh này không dùng được.'); return; }
                d.execCommand('insertImage', false, a);
                return;
            }
            if (lenh === '_ma'){
                dangXemMa = !dangXemMa;
                if (dangXemMa){
                    oVung.value = khung.innerHTML;
                    khung.style.display = 'none';
                    oVung.style.display = '';
                } else {
                    khung.innerHTML = locHtml(oVung.value);
                    oVung.style.display = 'none';
                    khung.style.display = '';
                    dongBo();
                }
                boc.classList.toggle('dang-xem-ma', dangXemMa);
                return;
            }
            if (lenh === 'formatBlock'){
                d.execCommand('formatBlock', false, '<' + thamSo + '>');
            } else {
                d.execCommand(lenh, false, thamSo || null);
            }
            dongBo();
        }

        khung.addEventListener('input', dongBo);
        khung.addEventListener('blur', dongBo);

        /* DÁN TỪ NƠI KHÁC — đường phổ biến nhất để rác (và mã) lọt vào bài.
           Lấy phần HTML, lọc, rồi chèn; không có HTML thì chèn chữ thường. */
        khung.addEventListener('paste', function (e){
            var dl = e.clipboardData || w.clipboardData;
            if (!dl) return;
            var html = dl.getData('text/html');
            var chu  = dl.getData('text/plain');
            e.preventDefault();
            if (html){
                d.execCommand('insertHTML', false, locHtml(html));
            } else {
                d.execCommand('insertText', false, chu);
            }
            dongBo();
        });

        /* Form gửi đi khi đang ở chế độ xem mã: lúc đó CHỮ THẬT nằm trong
           textarea, không phải trong khung. Không có nhánh này thì người dùng
           sửa mã rồi bấm Lưu luôn sẽ lưu bản trước khi sửa. */
        var form = oVung.form;
        if (form) form.addEventListener('submit', function (){
            if (!dangXemMa) oVung.value = khung.innerHTML;
        });

        dongBo();
    };

})(window, document);
