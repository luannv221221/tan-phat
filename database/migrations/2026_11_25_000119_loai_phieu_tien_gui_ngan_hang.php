<?php
/**
 * KẾ TOÁN — LOẠI PHIẾU TIỀN GỬI NGÂN HÀNG, để nhập tay tiền ngân hàng vào sổ quỹ.
 *
 * Khách ghi 25/11/2026 ở dòng "Báo cáo nợ xấu": "Thêm sổ quỹ (tiền mặt, tiền
 * NH thì tự nhập)". Phần mềm không nối với ngân hàng nào, nên tiền ngân hàng
 * vào sổ bằng CHÍNH màn Phiếu thu / Phiếu chi đang có — chỉ cần loại phiếu
 * định khoản vào 1121 thay vì 1111. Bảy loại gieo sẵn ở 000096 đều là tiền mặt.
 *
 * Sổ quỹ (thẻ mới ở Báo cáo kế toán) nhận ra quỹ theo TÀI KHOẢN trên phiếu:
 *   nợ  111* = tiền vào quỹ tiền mặt,  có 111* = tiền ra
 *   nợ  112* = tiền vào ngân hàng,     có 112* = tiền ra
 * nên phiếu lập bằng mấy loại dưới đây tự vào sổ ngân hàng, không vào sổ tiền
 * mặt, không cần thêm ô nào trên form.
 *
 * DÙNG CHUNG (`garage_id` NULL), như bộ loại phiếu 000096: mọi gara — kể cả
 * gara mở sau này — thấy ngay ở ô chọn loại phiếu. Gara sửa định khoản hoặc
 * thêm loại của mình ở Kế toán › Danh mục loại phiếu.
 *
 * MÃ BC / BN theo "giấy báo Có / giấy báo Nợ" của ngân hàng — cố ý KHÔNG nối
 * tiếp PT04 / PC05: gara có thể đã tự khai PT04 của mình, trùng mã thì ô chọn
 * hiện hai dòng cùng mã.
 *
 * CỐ Ý KHÔNG CÓ loại "nộp tiền mặt vào ngân hàng" / "rút tiền về quỹ". Sổ quỹ
 * xử lý được (một phiếu, hai quỹ, hai chiều), nhưng thẻ "Thu / chi theo kỳ" và
 * khối dòng tiền ở thẻ Kinh doanh cộng theo phiếu thu / phiếu chi — chuyển
 * tiền nội bộ sẽ bị đếm thành thu hoặc chi thật. Gara cần thì tự khai, biết
 * rằng hai thẻ đó sẽ tính khoản chuyển vào.
 *
 * AN TOÀN KHI CHẠY TRƯỚC KHI ĐẨY CODE: chỉ thêm dòng `acc_voucher_types`, không
 * đổi cấu trúc bảng; code cũ thấy thêm mấy loại phiếu và lập phiếu được ngay
 * (tiền vào tổng thu / chi như mọi phiếu khác). Chạy lại được: mã nào đã có
 * trong bộ dùng chung thì bỏ qua. Tài khoản nào không còn trong danh mục chung
 * thì bỏ qua loại đó và báo ra, không gieo loại phiếu thiếu định khoản.
 *
 * Chạy tay trong phpMyAdmin (nếu không chạy migrate.php) — chạy cả khối:
 *
 *   INSERT INTO acc_voucher_types (code, name, loai, debit_id, credit_id, sort_order, status, garage_id, create_at)
 *   SELECT d.code, d.name, d.loai, tn.id, tc.id, d.so, 1, NULL, NOW()
 *     FROM (SELECT 'BC01' code, 'Thu tiền khách hàng qua ngân hàng' name, 'thu' loai, '1121' no_tk, '131' co_tk, 10 so
 *           UNION ALL SELECT 'BC02', 'Thu khác qua ngân hàng',             'thu', '1121', '711',  11
 *           UNION ALL SELECT 'BN01', 'Chi trả nhà cung cấp qua ngân hàng', 'chi', '331',  '1121', 12
 *           UNION ALL SELECT 'BN02', 'Chi lương qua ngân hàng',            'chi', '334',  '1121', 13
 *           UNION ALL SELECT 'BN03', 'Chi phí qua ngân hàng (kể cả phí ngân hàng)', 'chi', '642', '1121', 14) d
 *     JOIN acc_accounts tn ON tn.code = d.no_tk AND tn.garage_id IS NULL
 *     JOIN acc_accounts tc ON tc.code = d.co_tk AND tc.garage_id IS NULL
 *    WHERE NOT EXISTS (SELECT 1 FROM acc_voucher_types x WHERE x.code = d.code AND x.garage_id IS NULL);
 *
 * Khong doi cau truc CSDL.
 */

use App\core\Migration;

return new class extends Migration {

    /** [mã, tên, thu/chi, TK nợ, TK có] — tài khoản lấy từ danh mục chung (000096) */
    const LOAI = [
        ['BC01', 'Thu tiền khách hàng qua ngân hàng',            'thu', '1121', '131'],
        ['BC02', 'Thu khác qua ngân hàng',                       'thu', '1121', '711'],
        ['BN01', 'Chi trả nhà cung cấp qua ngân hàng',           'chi', '331',  '1121'],
        ['BN02', 'Chi lương qua ngân hàng',                      'chi', '334',  '1121'],
        ['BN03', 'Chi phí qua ngân hàng (kể cả phí ngân hàng)',  'chi', '642',  '1121'],
    ];

    public function up(){
        $now  = date('Y-m-d H:i:s');
        $them = 0;
        foreach (self::LOAI as $i => $r){
            list($ma, $ten, $loai, $no, $co) = $r;

            $daCo = $this->db->firstRaw(
                "SELECT `id` FROM `acc_voucher_types` WHERE `code` = ? AND `garage_id` IS NULL LIMIT 1", [$ma]);
            if (!empty($daCo['id'])) continue;

            $tkNo = $this->db->firstRaw("SELECT `id` FROM `acc_accounts` WHERE `code` = ? AND `garage_id` IS NULL LIMIT 1", [$no]);
            $tkCo = $this->db->firstRaw("SELECT `id` FROM `acc_accounts` WHERE `code` = ? AND `garage_id` IS NULL LIMIT 1", [$co]);
            if (empty($tkNo['id']) || empty($tkCo['id'])){
                echo "  BO QUA $ma: thieu tai khoan dung chung $no hoac $co trong acc_accounts.\n";
                continue;
            }

            $this->db->insert('acc_voucher_types', [
                'code'       => $ma,
                'name'       => $ten,
                'loai'       => $loai,
                'debit_id'   => (int) $tkNo['id'],
                'credit_id'  => (int) $tkCo['id'],
                'sort_order' => 10 + $i,
                'status'     => 1,
                'garage_id'  => null,      // dùng chung mọi gara
                'create_at'  => $now,
            ]);
            $them++;
        }
        echo "  Da gieo $them loai phieu tien gui ngan hang (dung chung).\n";
    }

    /**
     * Gỡ chỉ những loại CHƯA có phiếu nào trỏ vào. Phiếu đã lập giữ bản chụp
     * định khoản nên không hỏng, nhưng mất tên loại trên danh sách — không đáng.
     */
    public function down(){
        foreach (self::LOAI as $r){
            $this->db->query("DELETE FROM `acc_voucher_types`
                               WHERE `code` = ? AND `garage_id` IS NULL
                                 AND NOT EXISTS (SELECT 1 FROM `acc_vouchers` v WHERE v.`vtype_id` = `acc_voucher_types`.`id`)",
                             [$r[0]]);
        }
        echo "  Da go cac loai phieu ngan hang chua co phieu nao.\n";
    }
};
