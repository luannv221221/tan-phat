<?php
/**
 * CHUYỂN SANG TÊN MIỀN eteksofts.vn (khách yêu cầu 13/11/2026: "khách bắt chuyển
 * sang domain này"; anh đã trỏ DNS xong).
 *
 * ──────────────────────────────────────────────────────────────────────────
 *  TÌNH TRẠNG LÚC LÀM
 * ──────────────────────────────────────────────────────────────────────────
 *
 * Bản ghi DNS anh đã tạo:
 *
 *     @       A      103.74.101.148     -> eteksofts.vn
 *     *.etek  A      103.74.101.148     -> <mã gara>.etek.eteksofts.vn
 *     www     CNAME  eteksofts.vn.
 *
 * Đã đo trực tiếp 13/11: cả ba tên đều ra đúng IP máy chủ, máy chủ nhận request
 * và trả trang "Không tìm thấy gara" — vì bảng `garage_domains` (migration
 * 000084) chưa có dòng nào cho các host mới. Tức là đường đã thông, chỉ còn
 * thiếu khai báo "host này thuộc gara nào". Migration này khai.
 *
 * ──────────────────────────────────────────────────────────────────────────
 *  KHAI GÌ
 * ──────────────────────────────────────────────────────────────────────────
 *
 *   - GARA TỔNG: `eteksofts.vn`. Trang khách đang vào là trang Tân Phát.
 *     `www.eteksofts.vn` KHÔNG cần dòng riêng: GarageDomainsModel::chuanHoaHost()
 *     bỏ "www." trước khi so, nên www tự khớp dòng này.
 *   - MỖI GARA: `<mã>.etek.eteksofts.vn` — đúng hình dạng DNS `*.etek` anh vừa
 *     tạo. Gara nào mở sau cũng nhận địa chỉ dạng này (xem ở dưới).
 *
 * KHÔNG khai `etek.eteksofts.vn` trần: DNS `*.etek` chỉ phủ những tên CÓ THÊM một
 * nhãn phía trước, nên tên trần đó không có bản ghi nào. Khai vào là một địa chỉ
 * trông có vẻ hợp lệ mà không ai mở được.
 *
 * ──────────────────────────────────────────────────────────────────────────
 *  CHỈ THÊM — TUYỆT ĐỐI KHÔNG ĐỔI HOST CHÍNH, KHÔNG GỠ TÊN MIỀN CŨ
 * ──────────────────────────────────────────────────────────────────────────
 *
 * 1. Tên miền cũ (`etek.rikkeiedu.org`, `tp01.etek.rikkeiedu.org`...) vẫn chạy
 *    song song. Link đã gửi cho khách, ghi trong hợp đồng, in trên giấy tờ không
 *    chết ngày hôm nay. Gỡ khi nào anh quyết, ở màn Tên miền.
 *
 * 2. HOST CHÍNH GIỮ NGUYÊN, vì MoGaraModel::tenMienGoc() suy "tên miền gốc" cho
 *    gara mở sau từ host chính của gara tổng. Đổi chính ngay ở đây thì từ giây
 *    đó mọi gara mới nhận `<mã>.etek.eteksofts.vn` — mà địa chỉ đó chưa có chứng
 *    chỉ SSL, chưa ai kiểm tra đã vào được chưa. Đổi chính là một cú bấm có chủ ý
 *    ở màn Tên miền (nút "Đặt làm chính" trên `tp01.etek.eteksofts.vn`), làm SAU
 *    KHI đã mở thử tên miền mới và thấy nó chạy.
 *
 * CHẠY LẠI ĐƯỢC: host nào đã có (kể cả khi thuộc gara khác) thì bỏ qua, không
 * sinh dòng trùng, không giành host của ai. `host` là UNIQUE toàn bảng nên cũng
 * không thể trùng.
 */

use App\core\Migration;

return new class extends Migration {

    /** Tên miền của gara tổng — trang khách vào. */
    const TEN_MIEN_GARA_TONG = 'eteksofts.vn';

    /** Gốc của tên miền phụ theo mã gara — khớp bản ghi DNS `*.etek`. */
    const GOC_TEN_MIEN_PHU = 'etek.eteksofts.vn';

    /** Mã gara -> nhãn tên miền. Dùng slugify() của dự án nếu có, không thì gột tối thiểu. */
    private function nhan($ma){
        if (function_exists('slugify')) return slugify((string) $ma);
        return trim(preg_replace('~[^a-z0-9]+~', '-', strtolower(trim((string) $ma))), '-');
    }

    public function up(){

        if (!$this->hasTable('garage_domains')) return;

        $now  = date('Y-m-d H:i:s');
        $gara = $this->db->table('garages')->select('`id`, `code`, `is_master`')->get();
        $them = 0;

        $khai = function ($garageId, $host) use ($now, &$them) {
            $host = strtolower(trim($host));
            if ($host === '') return;
            $co = $this->db->table('garage_domains')->where('host', '=', $host)->first();
            if (!empty($co)) return;
            $this->db->insert('garage_domains', [
                'garage_id'  => (int) $garageId,
                'host'       => $host,
                'is_primary' => 0,          // KHÔNG BAO GIỜ là chính — xem chú thích đầu file
                'status'     => 1,
                'create_at'  => $now,
            ]);
            $them++;
            echo "  $host -> gara #$garageId\n";
        };

        foreach ((array) $gara as $g){
            $nhan = $this->nhan($g['code']);
            if ($nhan !== '') $khai((int) $g['id'], $nhan . '.' . self::GOC_TEN_MIEN_PHU);

            if (!empty($g['is_master'])) $khai((int) $g['id'], self::TEN_MIEN_GARA_TONG);
        }
        echo "  Da khai $them ten mien.\n";
    }

    public function down(){
        /* Chỉ gỡ đúng những host migration này đã thêm — theo tên, không theo
           "mọi host có eteksofts.vn", để không xoá nhầm tên miền anh tự khai thêm
           ở màn Tên miền sau đó (vd. một gara dùng tên miền riêng đuôi này). */
        if (!$this->hasTable('garage_domains')) return;

        $gara = $this->db->table('garages')->select('`code`')->get();
        $go = [self::TEN_MIEN_GARA_TONG];
        foreach ((array) $gara as $g){
            $nhan = $this->nhan($g['code']);
            if ($nhan !== '') $go[] = $nhan . '.' . self::GOC_TEN_MIEN_PHU;
        }
        foreach ($go as $host){
            $this->db->delete('garage_domains', '`host` = ? AND `is_primary` = 0', [$host]);
        }
        echo "  Da go cac ten mien eteksofts.vn do migration nay them.\n";
    }
};
