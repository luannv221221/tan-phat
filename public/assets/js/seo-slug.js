/* ============================================================================
 * SEO + ĐƯỜNG DẪN — tài liệu khách gửi 05/11/2026.
 *
 *   "Check seo theo tiêu chuẩn (màu sắc xanh và đỏ)"
 *   "Tự động điền đường dẫn URL theo title"
 *
 * Hai việc nhỏ nhưng dùng ở nhiều màn (bài viết, hàng hoá, danh mục…), nên để
 * một file dùng chung thay vì chép <script> vào từng view.
 *
 * KHÔNG TỰ CHẠY. Mỗi màn tự gọi hàm cho đúng ô của mình — view nào cũng đặt
 * tên ô một kiểu, đoán bằng selector chung là sớm muộn gắn nhầm ô.
 *
 *   window.oSlugTuTieuDe(oTieuDe, oSlug)
 *   window.oDoDaiSeo(oInput, 50, 70)
 *
 * Nạp ở CUỐI view, sau khi các ô đã có trong trang.
 * ========================================================================== */
(function (w, d){
    'use strict';

    /* --------------------------------------------------------------------
     * BỎ DẤU TIẾNG VIỆT — chép đúng bảng của slugify() trong
     * app/helpers/functions.php.
     *
     * PHẢI GIỐNG BẢN PHP. Người dùng thấy ô đường dẫn tự điền ra một chuỗi
     * rồi bấm Lưu; nếu PHP gột theo luật khác thì cái lưu xuống khác cái vừa
     * nhìn thấy, mà không có thông báo nào. Sửa bên này thì sửa cả bên kia.
     * ------------------------------------------------------------------ */
    var BANG = {
        a: 'áàảãạăắằẳẵặâấầẩẫậ',
        e: 'éèẻẽẹêếềểễệ',
        i: 'íìỉĩị',
        o: 'óòỏõọôốồổỗộơớờởỡợ',
        u: 'úùủũụưứừửữự',
        y: 'ýỳỷỹỵ',
        d: 'đ'
    };

    function slugify(s){
        s = String(s == null ? '' : s).toLowerCase().trim();
        for (var ascii in BANG){
            if (!Object.prototype.hasOwnProperty.call(BANG, ascii)) continue;
            var co = BANG[ascii];
            for (var i = 0; i < co.length; i++){
                s = s.split(co.charAt(i)).join(ascii);
            }
        }
        s = s.replace(/[^a-z0-9\s-]/g, '');
        s = s.replace(/[\s-]+/g, '-');
        return s.replace(/^-+|-+$/g, '');
    }
    w.slugifyJs = slugify;

    /* --------------------------------------------------------------------
     * TỰ ĐIỀN ĐƯỜNG DẪN THEO TIÊU ĐỀ
     *
     * CHỈ ĐIỀN KHI NGƯỜI DÙNG CHƯA TỰ GÕ. Đường dẫn là địa chỉ công khai của
     * trang: đã đăng rồi mà nó đổi theo mỗi lần sửa tiêu đề thì link cũ chết,
     * Google phải học lại, ai đã chia sẻ thì gãy. Nên:
     *
     *   - ô đang trống  -> điền theo tiêu đề
     *   - ô có sẵn chữ  -> KHÔNG đụng vào (màn Sửa luôn rơi vào đây)
     *   - người dùng tự gõ vào ô -> thôi theo tiêu đề từ đó trở đi
     *
     * Bỏ qua phím điều hướng để bấm mũi tên trong ô đường dẫn không bị tính
     * là "đã tự gõ".
     * ------------------------------------------------------------------ */
    w.oSlugTuTieuDe = function (oTieuDe, oSlug){
        if (!oTieuDe || !oSlug) return;

        var tuGo = String(oSlug.value || '').trim() !== '';

        oSlug.addEventListener('input', function (){
            tuGo = String(oSlug.value || '').trim() !== '';
        });

        oTieuDe.addEventListener('input', function (){
            if (tuGo) return;
            oSlug.value = slugify(oTieuDe.value);
        });
    };

    /* --------------------------------------------------------------------
     * THANH MÀU ĐỘ DÀI SEO
     *
     * Khách gửi ảnh mẫu: dưới ô meta có một vạch xanh khi đủ dài, đỏ khi
     * chưa. Thêm cả số đếm — vạch đỏ mà không nói thiếu bao nhiêu thì người
     * viết phải gõ mò.
     *
     * BA MỨC, không phải hai:
     *   trống     -> xám, không nói gì (chưa khai thì chưa phải là sai)
     *   trong mức -> xanh
     *   ngoài mức -> đỏ, kèm "ngắn quá" / "dài quá"
     *
     * Để trống KHÔNG phải lỗi: meta để trống thì trang tự lấy tiêu đề và tóm
     * tắt — vẫn đúng. Tô đỏ ô trống là bắt người ta sửa một thứ không hỏng.
     * ------------------------------------------------------------------ */
    w.oDoDaiSeo = function (o, min, max){
        if (!o || o.getAttribute('data-seo-da-gan') === '1') return;
        o.setAttribute('data-seo-da-gan', '1');

        var boc = d.createElement('div');
        boc.className = 'seo-do-dai';
        boc.innerHTML = '<div class="seo-do-dai__vach"><i></i></div>'
                      + '<small class="seo-do-dai__chu text-muted"></small>';
        if (o.parentNode) o.parentNode.insertBefore(boc, o.nextSibling);

        var vach = boc.querySelector('i');
        var chu  = boc.querySelector('.seo-do-dai__chu');

        function ve(){
            var n = String(o.value || '').trim().length;

            /* Phần trăm tính theo MAX để vạch dài ra đều tay khi gõ. Vượt max
               thì ghim 100% — vạch tràn ra ngoài khung trông như hỏng giao diện. */
            var pt = max > 0 ? Math.min(100, Math.round(n * 100 / max)) : 0;
            vach.style.width = pt + '%';

            boc.classList.remove('la-xanh', 'la-do', 'la-trong');

            if (n === 0){
                boc.classList.add('la-trong');
                chu.textContent = 'Để trống — trang sẽ tự lấy tiêu đề / tóm tắt';
                return;
            }
            if (n < min){
                boc.classList.add('la-do');
                chu.textContent = n + '/' + min + '–' + max + ' ký tự — ngắn quá, thêm '
                                + (min - n) + ' ký tự nữa';
                return;
            }
            if (n > max){
                boc.classList.add('la-do');
                chu.textContent = n + '/' + min + '–' + max + ' ký tự — dài quá, bớt '
                                + (n - max) + ' ký tự';
                return;
            }
            boc.classList.add('la-xanh');
            chu.textContent = n + '/' + min + '–' + max + ' ký tự — đạt';
        }

        o.addEventListener('input', ve);
        ve();
    };

})(window, document);
