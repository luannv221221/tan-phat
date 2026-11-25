<?php
/**
 * TÊN MIỀN CHÍNH CỦA MỌI CÔNG TY = <mã>.eteksofts.vn — làm luôn Bước 6.4 của
 * deploy/CHUYEN-TEN-MIEN-ETEKSOFTS.md thay vì bấm tay từng công ty.
 *
 * Anh hỏi 25/11/2026, sau khi mở công ty TEST mà test.eteksofts.vn ra "Không
 * tìm thấy công ty": "sao em không làm mặc định khi thêm gara thì nó thành tên
 * miền theo dạng mã.eteksofts.vn luôn".
 *
 * Mặc định ĐÃ CÓ trong code: công ty mở mới nhận `<mã>.<tên miền gốc>`, tên miền
 * gốc suy từ tên CHÍNH của công ty tổng (MoGaraModel::tenMienGoc). Thiếu là ở
 * DỮ LIỆU: tên chính của Tân Phát vẫn là tp01.etek.rikkeiedu.org (000112 cố ý
 * không đổi — lúc đó chưa có DNS `*`). Đo 25/11: DNS `*.eteksofts.vn` đã có, cổng
 * 80 + 443 vào tới ứng dụng, chỉ còn thiếu khai báo.
 *
 * LÀM GÌ (chỉ khi công ty tổng có host `eteksofts.vn` — tức là bản cài này đã
 * chuyển sang eteksofts, migration 000112; bản cài khác thì bỏ qua):
 *   - Với MỌI công ty (kể cả công ty tổng, kể cả công ty đang khoá): khai
 *     `<mã>.eteksofts.vn` nếu chưa có, rồi đặt làm tên CHÍNH.
 *   - Tên cũ (`<mã>.etek.rikkeiedu.org`, `<mã>.etek.eteksofts.vn`, `eteksofts.vn`...)
 *     GIỮ NGUYÊN, đang bật thì vẫn bật — thành tên phụ. Link đã gửi không chết.
 *   - Tên `<mã>.eteksofts.vn` đã thuộc công ty khác, hoặc đang bị TẮT có chủ ý ở
 *     màn Tên miền -> bỏ qua công ty đó, không giành, không bật lại.
 * Sau đó tên miền gốc = eteksofts.vn: công ty mở mới tự nhận `<mã>.eteksofts.vn`
 * làm tên chính, kèm tên phụ trên các đuôi cũ (MoGaraModel::cacDuoiPhu).
 *
 * CHƯA CÓ Ổ KHOÁ: chứng chỉ SSL chưa kê các tên này — mở bằng http:// chạy ngay,
 * https:// báo lỗi chứng chỉ tới khi chạy lại win-acme (Bước 6.3).
 *
 * AN TOÀN KHI CHẠY TRƯỚC HAY SAU KHI ĐẨY CODE: chỉ thêm dòng `garage_domains` và
 * đổi cờ `is_primary`; code cũ đã hiểu đúng cả hai. Chạy lại được.
 *
 * Chạy tay trong phpMyAdmin (nếu không chạy migrate.php) — thay <id>, <mã> từng
 * công ty ở màn Quản lý công ty, hoặc làm bằng màn Tên miền (Bước 6.4):
 *
 *   INSERT IGNORE INTO garage_domains (garage_id, host, is_primary, status, create_at)
 *     VALUES (<id>, '<mã>.eteksofts.vn', 0, 1, NOW());
 *   UPDATE garage_domains SET is_primary = (host = '<mã>.eteksofts.vn') WHERE garage_id = <id>;
 */

use App\core\Migration;

return new class extends Migration {

    const GOC = 'eteksofts.vn';

    /** Mã công ty -> nhãn tên miền: cùng slugify() với MoGaraModel; thiếu thì gột tối thiểu. */
    private function nhan($ma){
        if (function_exists('slugify')) return slugify((string) $ma);
        $s = strtolower(trim((string) $ma));
        if (function_exists('iconv')){
            $t = @iconv('UTF-8', 'ASCII//TRANSLIT', $s);
            if ($t !== false) $s = strtolower($t);
        }
        return trim(preg_replace('~[^a-z0-9]+~', '-', $s), '-');
    }

    public function up(){
        if (!$this->hasTable('garages') || !$this->hasTable('garage_domains')) return;

        $tong = $this->db->firstRaw('SELECT `id` FROM `garages` WHERE `is_master` = 1 LIMIT 1');
        if (empty($tong['id'])){ echo "  Khong co cong ty tong — bo qua.\n"; return; }
        $coGoc = $this->db->firstRaw('SELECT `id` FROM `garage_domains` WHERE `garage_id` = ? AND `host` = ? LIMIT 1',
                                     [(int) $tong['id'], self::GOC]);
        if (empty($coGoc['id'])){
            echo "  Cong ty tong chua co " . self::GOC . " (ban cai khac) — bo qua.\n";
            return;
        }

        $them = 0; $doi = 0; $boQua = [];
        /* Công ty tổng TRƯỚC: tên chính của nó quyết định tên miền gốc. */
        foreach ((array) $this->db->getRaw('SELECT `id`, `code` FROM `garages` ORDER BY `is_master` DESC, `id` ASC') as $g){
            $id   = (int) $g['id'];
            $nhan = $this->nhan($g['code']);
            if ($nhan === ''){ $boQua[] = $g['code'] . ' (ma khong thanh ten mien duoc)'; continue; }
            $host = $nhan . '.' . self::GOC;

            $co = $this->db->firstRaw('SELECT `id`, `garage_id`, `status`, `is_primary` FROM `garage_domains` WHERE `host` = ? LIMIT 1', [$host]);
            if (!empty($co) && (int) $co['garage_id'] !== $id){ $boQua[] = $g['code'] . " ($host thuoc cong ty khac)"; continue; }
            if (!empty($co) && (int) $co['status'] !== 1){ $boQua[] = $g['code'] . " ($host dang tat)"; continue; }

            if (empty($co)){
                $this->db->insert('garage_domains', [
                    'garage_id' => $id, 'host' => $host, 'is_primary' => 0, 'status' => 1,
                    'create_at' => date('Y-m-d H:i:s'),
                ]);
                $them++;
                $co = ['is_primary' => 0];
            }
            if ((int) $co['is_primary'] !== 1){
                $this->db->query('UPDATE `garage_domains` SET `is_primary` = 0 WHERE `garage_id` = ?', [$id]);
                $this->db->query('UPDATE `garage_domains` SET `is_primary` = 1 WHERE `host` = ?', [$host]);
                $doi++;
            }
        }
        echo "  Da khai $them ten <ma>." . self::GOC . ", dat chinh cho $doi cong ty."
           . (!empty($boQua) ? ' Bo qua: ' . implode('; ', $boQua) . '.' : '') . "\n";
    }

    /* Không hoàn tác: không biết trước đây host nào là chính. Đổi lại thì bấm "Đặt
       làm chính" ở màn Tên miền. */
    public function down(){}
};
