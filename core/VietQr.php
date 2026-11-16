<?php

namespace App\core;

/**
 * MÃ QR CHUYỂN KHOẢN theo chuẩn VietQR (NAPAS, dựng trên khung EMVCo).
 *
 * Anh yêu cầu 16/11/2026: khách mở app ngân hàng quét mã trên hoá đơn là ra
 * sẵn ngân hàng, số tài khoản, số tiền của hoá đơn và nội dung là số hoá đơn —
 * không phải gõ tay, không gõ nhầm số tiền.
 *
 * LỚP NÀY CHỈ DỰNG CHUỖI, không vẽ ảnh. Ảnh do mẫu in vẽ tại chỗ bằng JS (xem
 * app/views/admin/print/chung-tu.php). Không gọi img.vietqr.io hay dịch vụ nào
 * khác: chuỗi này mang số tiền của hoá đơn và số tài khoản của gara, gửi ra
 * ngoài để lấy một cái ảnh là đem dữ liệu kinh doanh của gara cho người lạ.
 *
 * Cấu trúc (mỗi trường là TLV: 2 số mã + 2 số độ dài + giá trị):
 *
 *   00 = 01                     phiên bản
 *   01 = 11 | 12                11 mã tĩnh (không số tiền), 12 mã động (có số tiền)
 *   38 = 00 A000000727          GUID của NAPAS
 *        01 [00 BIN][01 số TK]  ngân hàng nhận + số tài khoản
 *        02 QRIBFTTA            dịch vụ: chuyển nhanh 24/7 tới TÀI KHOẢN
 *   53 = 704                    VND
 *   54 = số tiền                chỉ có ở mã động
 *   58 = VN
 *   62 = 08 nội dung            nội dung chuyển khoản
 *   63 = CRC16-CCITT-FALSE      tính trên cả chuỗi, kể cả "6304"
 *
 * Đúng thứ tự trên: bốn chuỗi mẫu công khai (README vietnam-qr-pay,
 * sunary/vietqr) đều xếp như vậy, và tests/VietQrTest.php so từng ký tự.
 */
final class VietQr {

    const GUID    = 'A000000727';
    const DICH_VU = 'QRIBFTTA';

    /** Giới hạn độ dài của thẻ 62/08 trong chuẩn EMVCo */
    const DAI_NOI_DUNG = 25;

    /**
     * Ngân hàng nhận chuyển khoản qua VietQR — mã BIN do NAPAS cấp.
     *
     * Lấy từ https://api.vietqr.io/v2/banks ngày 16/11/2026, giữ những dòng có
     * transferSupported = 1 (ngân hàng nhận được chuyển khoản bằng mã QR), tên
     * chép nguyên văn. Thứ tự cũng giữ như API: ngân hàng lớn đứng đầu, gara
     * tìm nhanh hơn.
     *
     * BỎ BA VÍ ĐIỆN TỬ: MoMo (971025), ZaloPay (971101), Mobifone Money
     * (971032). Mã VietQR của ví dùng số tài khoản riêng do ví cấp (dạng
     * PSP...), không phải số điện thoại — gara chọn ví rồi gõ số điện thoại vào
     * là ra một mã chuyển sai chỗ.
     *
     * KHÔNG CÓ NÚT "+ THÊM NHANH" như các danh mục khác (xem chi-chon-khong-go-tay):
     * BIN do NAPAS cấp, gõ một mã tự đặt vào là in ra một mã QR trỏ tới ngân
     * hàng không tồn tại. Ngân hàng mới thì thêm vào danh sách này.
     *
     * bin => [tên ngắn, tên đầy đủ]
     */
    const NGAN_HANG = [
        '970415' => ['VietinBank', 'Ngân hàng TMCP Công thương Việt Nam'],
        '970436' => ['Vietcombank', 'Ngân hàng TMCP Ngoại Thương Việt Nam'],
        '970418' => ['BIDV', 'Ngân hàng TMCP Đầu tư và Phát triển Việt Nam'],
        '970405' => ['Agribank', 'Ngân hàng Nông nghiệp và Phát triển Nông thôn Việt Nam'],
        '970448' => ['OCB', 'Ngân hàng TMCP Phương Đông'],
        '970422' => ['MBBank', 'Ngân hàng TMCP Quân đội'],
        '970407' => ['Techcombank', 'Ngân hàng TMCP Kỹ thương Việt Nam'],
        '970416' => ['ACB', 'Ngân hàng TMCP Á Châu'],
        '970432' => ['VPBank', 'Ngân hàng TMCP Việt Nam Thịnh Vượng'],
        '970423' => ['TPBank', 'Ngân hàng TMCP Tiên Phong'],
        '970403' => ['Sacombank', 'Ngân hàng TMCP Sài Gòn Tài Lộc'],
        '970437' => ['HDBank', 'Ngân hàng TMCP Phát triển Thành phố Hồ Chí Minh'],
        '970454' => ['VietCapitalBank', 'Ngân hàng TMCP Bản Việt'],
        '970429' => ['SCB', 'Ngân hàng TMCP Sài Gòn'],
        '970441' => ['VIB', 'Ngân hàng TMCP Quốc tế Việt Nam'],
        '970443' => ['SHB', 'Ngân hàng TMCP Sài Gòn - Hà Nội'],
        '970431' => ['Eximbank', 'Ngân hàng TMCP Xuất Nhập khẩu Việt Nam'],
        '970426' => ['MSB', 'Ngân hàng TMCP Hàng Hải'],
        '546034' => ['CAKE', 'TMCP Việt Nam Thịnh Vượng - Ngân hàng số CAKE by VPBank'],
        '546035' => ['Ubank', 'TMCP Việt Nam Thịnh Vượng - Ngân hàng số Ubank by VPBank'],
        '963388' => ['Timo', 'Ngân hàng số Timo by Ban Viet Bank (Timo by Ban Viet Bank)'],
        '970400' => ['SaigonBank', 'Ngân hàng TMCP Sài Gòn Công Thương'],
        '970409' => ['BacABank', 'Ngân hàng TMCP Bắc Á'],
        '971133' => ['PVcomBank Pay', 'Ngân hàng số PVcomBank'],
        '970412' => ['PVcomBank', 'Ngân hàng TMCP Đại Chúng Việt Nam'],
        '970414' => ['MBV', 'Ngân hàng TNHH MTV Việt Nam Hiện Đại'],
        '970419' => ['NCB', 'Ngân hàng TMCP Quốc Dân'],
        '970424' => ['ShinhanBank', 'Ngân hàng TNHH MTV Shinhan Việt Nam'],
        '970425' => ['ABBANK', 'Ngân hàng TMCP An Bình'],
        '970427' => ['VietABank', 'Ngân hàng TMCP Việt Á'],
        '970428' => ['NamABank', 'Ngân hàng TMCP Nam Á'],
        '970430' => ['PGBank', 'Ngân hàng TMCP Thịnh vượng và Phát triển'],
        '970433' => ['VietBank', 'Ngân hàng TMCP Việt Nam Thương Tín'],
        '970438' => ['BaoVietBank', 'Ngân hàng TMCP Bảo Việt'],
        '970440' => ['SeABank', 'Ngân hàng TMCP Đông Nam Á'],
        '970446' => ['COOPBANK', 'Ngân hàng Hợp tác xã Việt Nam'],
        '970449' => ['LPBank', 'Ngân hàng TMCP Lộc Phát Việt Nam'],
        '970452' => ['KienLongBank', 'Ngân hàng TMCP Kiên Long'],
        '668888' => ['KBank', 'Ngân hàng Đại chúng TNHH Kasikornbank'],
        '970434' => ['IndovinaBank', 'Ngân hàng TNHH Indovina'],
        '422589' => ['CIMB', 'Ngân hàng TNHH MTV CIMB Việt Nam'],
        '970444' => ['CBBank', 'Ngân hàng Thương mại trách nhiệm hữu hạn một thành viên Ngoại thương Công nghệ số (Vietcombank Neo Limited)'],
        '970457' => ['Woori', 'Ngân hàng TNHH MTV Woori Việt Nam'],
        '970462' => ['KookminHN', 'Ngân hàng Kookmin - Chi nhánh Hà Nội'],
        '970463' => ['KookminHCM', 'Ngân hàng Kookmin - Chi nhánh Thành phố Hồ Chí Minh'],
    ];

    /** Danh sách để vẽ ô chọn: bin => ['ten' => ..., 'ten_day_du' => ...] */
    public static function nganHang(){
        $ra = [];
        foreach (self::NGAN_HANG as $bin => $nh) $ra[(string) $bin] = ['ten' => $nh[0], 'ten_day_du' => $nh[1]];
        return $ra;
    }

    /** Tên ngắn của ngân hàng theo BIN; BIN lạ -> chuỗi rỗng */
    public static function tenNganHang($bin){
        $bin = trim((string) $bin);
        return isset(self::NGAN_HANG[$bin]) ? self::NGAN_HANG[$bin][0] : '';
    }

    public static function tenDayDu($bin){
        $bin = trim((string) $bin);
        return isset(self::NGAN_HANG[$bin]) ? self::NGAN_HANG[$bin][1] : '';
    }

    /**
     * CRC-16/CCITT-FALSE: đa thức 0x1021, khởi đầu 0xFFFF, không đảo bit,
     * không XOR cuối. Trả 4 ký tự hex viết hoa — "0A3F" chứ không phải "A3F":
     * thiếu số 0 đầu là cả chuỗi lệch một ký tự và app ngân hàng báo mã lỗi.
     */
    public static function crc16($s){
        $crc = 0xFFFF;
        $s   = (string) $s;
        for ($i = 0, $n = strlen($s); $i < $n; $i++){
            $crc ^= ord($s[$i]) << 8;
            for ($b = 0; $b < 8; $b++){
                $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) : ($crc << 1);
                $crc &= 0xFFFF;
            }
        }
        return sprintf('%04X', $crc);
    }

    /**
     * Nội dung chuyển khoản an toàn cho mọi app ngân hàng: bỏ dấu tiếng Việt,
     * chỉ giữ chữ, số, dấu cách, gạch ngang; tối đa 25 ký tự.
     *
     * Vì sao chặt vậy: độ dài TLV đếm theo BYTE, chữ "đ" là 2 byte UTF-8 nên để
     * nguyên là lệch độ dài cả chuỗi. Còn "/" và các dấu khác thì một số app
     * ngân hàng tự cắt bỏ khi chuyển — nội dung khách thấy trên app khác nội
     * dung in trên giấy, gara đối soát không khớp.
     */
    public static function noiDung($s){
        $s = self::boDau((string) $s);
        $s = preg_replace('~[^A-Za-z0-9 \-]~', ' ', $s);
        $s = trim(preg_replace('~\s+~', ' ', $s));
        return rtrim(substr($s, 0, self::DAI_NOI_DUNG));
    }

    /**
     * Chuỗi VietQR chuyển tới một tài khoản. null khi thiếu / sai thông tin —
     * thà không in mã còn hơn in một mã chuyển nhầm.
     *
     * @param string     $bin     mã BIN NAPAS, phải có trong NGAN_HANG
     * @param string     $soTk    số tài khoản (dấu cách được bỏ)
     * @param mixed|null $soTien  null / 0 -> mã tĩnh (người chuyển tự gõ số tiền)
     * @param string     $noiDung nội dung; bỏ dấu, giữ ASCII in được, cắt 25 ký tự
     */
    public static function chuoi($bin, $soTk, $soTien = null, $noiDung = ''){
        $bin  = trim((string) $bin);
        $soTk = preg_replace('~\s+~', '', (string) $soTk);

        if (!isset(self::NGAN_HANG[$bin])) return null;
        /* Số tài khoản: chữ và số, tối đa 19 ký tự (giới hạn của NAPAS). Có
           chữ vì vài ngân hàng cấp tài khoản dạng "99MM...". */
        if (!preg_match('~^[A-Za-z0-9]{1,19}$~', $soTk)) return null;

        $tien = null;
        if ($soTien !== null && $soTien !== ''){
            if (!is_numeric($soTien)) return null;
            /* Đồng Việt Nam không có hào: tổng lưu decimal(15,2) đọc ra là
               "1954800.00", in thẳng vào thẻ 54 thì chẳng ai biết app đọc nó
               thành bao nhiêu. Làm tròn về đồng. */
            $tien = (int) round((float) $soTien);
            if ($tien < 0) return null;
            if ($tien === 0) $tien = null;
            elseif (strlen((string) $tien) > 13) return null;
        }

        $nd = trim(preg_replace('~[^\x20-\x7E]~', '', self::boDau((string) $noiDung)));
        $nd = rtrim(substr($nd, 0, self::DAI_NOI_DUNG));

        $taiKhoan = self::the('00', $bin) . self::the('01', $soTk);
        $s  = self::the('00', '01');
        $s .= self::the('01', $tien === null ? '11' : '12');
        $s .= self::the('38', self::the('00', self::GUID) . self::the('01', $taiKhoan) . self::the('02', self::DICH_VU));
        $s .= self::the('53', '704');
        if ($tien !== null) $s .= self::the('54', (string) $tien);
        $s .= self::the('58', 'VN');
        if ($nd !== '') $s .= self::the('62', self::the('08', $nd));
        $s .= '6304';
        return $s . self::crc16($s);
    }

    /**
     * Mã QR cho một hoá đơn, đọc từ cấu hình của GARA LẬP HOÁ ĐƠN (bảng đã qua
     * cau_hinh_in_an()). null = không in mã: gara chưa chọn ngân hàng, chưa có
     * số tài khoản, hoặc hoá đơn không còn gì phải trả.
     *
     * Gara mới chỉ có tên ngân hàng GÕ TAY (dữ liệu trước 16/11/2026) cũng ra
     * null: tên gõ tay không suy ra được BIN cho chắc, mà đoán sai BIN là mã
     * trỏ sang ngân hàng khác. Mẫu in vẫn in chữ như cũ cho trường hợp này.
     *
     * @return array|null ['chuoi','bin','ngan_hang','ngan_hang_day_du','so_tk','chu_tk','so_tien','noi_dung']
     */
    public static function choHoaDon(array $cauHinh, $soTien, $soPhieu){
        $bin  = isset($cauHinh['bank_bin']) ? trim((string) $cauHinh['bank_bin']) : '';
        $soTk = isset($cauHinh['bank_account']) ? preg_replace('~\s+~', '', (string) $cauHinh['bank_account']) : '';
        if ($bin === '' || $soTk === '') return null;

        $tien = (int) round((float) $soTien);
        if ($tien <= 0) return null;

        $nd    = self::noiDung($soPhieu);
        $chuoi = self::chuoi($bin, $soTk, $tien, $nd);
        if ($chuoi === null) return null;

        return [
            'chuoi'            => $chuoi,
            'bin'              => $bin,
            'ngan_hang'        => self::tenNganHang($bin),
            'ngan_hang_day_du' => self::tenDayDu($bin),
            'so_tk'            => $soTk,
            'chu_tk'           => isset($cauHinh['bank_holder']) ? trim((string) $cauHinh['bank_holder']) : '',
            'so_tien'          => $tien,
            'noi_dung'         => $nd,
        ];
    }

    /** Một trường TLV. Giá trị đã là ASCII nên strlen = số ký tự. */
    private static function the($ma, $giaTri){
        return $ma . sprintf('%02d', strlen($giaTri)) . $giaTri;
    }

    /** Bỏ dấu tiếng Việt, GIỮ hoa / thường (slugify() thì hạ hết về chữ thường). */
    private static function boDau($s){
        static $bang = null;
        if ($bang === null){
            $bang = [];
            $nhom = ['a' => 'áàảãạăắằẳẵặâấầẩẫậ', 'e' => 'éèẻẽẹêếềểễệ', 'i' => 'íìỉĩị',
                     'o' => 'óòỏõọôốồổỗộơớờởỡợ', 'u' => 'úùủũụưứừửữự', 'y' => 'ýỳỷỹỵ', 'd' => 'đ'];
            foreach ($nhom as $ascii => $dau){
                foreach (preg_split('//u', $dau, -1, PREG_SPLIT_NO_EMPTY) as $c){
                    $bang[$c] = $ascii;
                    $bang[mb_strtoupper($c, 'UTF-8')] = strtoupper($ascii);
                }
            }
        }
        $s = strtr($s, $bang);
        // Chữ gõ dạng tổ hợp (chữ gốc + dấu rời) — bỏ dấu rời
        // (string): chuỗi không phải UTF-8 hợp lệ thì preg_replace trả null
        return (string) preg_replace('~[\x{0300}-\x{036F}]~u', '', $s);
    }
}
