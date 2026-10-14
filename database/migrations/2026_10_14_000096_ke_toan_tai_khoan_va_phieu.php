<?php
/**
 * KẾ TOÁN — NỀN: danh mục tài khoản, danh mục loại phiếu, và phiếu thu / chi.
 *
 * Theo tài liệu khách gửi 14/10/2026 ("gara ô tô.docx"):
 *
 *   Tài khoản   id, matk, name, parent  — CRUD, "theo kiểu đệ quy như menu có
 *               cha con: 1111 (111A, 111B), 1112, 334"
 *   Loại phiếu  id, maphieu, name, tkno, tkco, type (thu/chi) — chọn tài khoản
 *               nợ và tài khoản có từ danh mục tài khoản
 *   Phiếu thu   id, name, đối tượng, loại phiếu, số tiền — "load danh mục khách
 *               hàng, nhà cung cấp, nhân viên… hiểu là thu từ đối tượng nào"
 *
 * BA QUYẾT ĐỊNH ĐÁNG GHI LẠI:
 *
 * 1. KHÔNG DỰNG SỔ CÁI / BÚT TOÁN. Tài liệu chỉ yêu cầu loại phiếu mang cặp
 *    tài khoản nợ–có, và năm báo cáo đều là phép cộng trên phiếu + chứng từ đã
 *    có (hoá đơn bán, phiếu nhập). Dựng thêm một tầng sổ cái là tự thêm việc
 *    khách không hỏi — mà phân hệ kế toán cũ của dự án đã bị gỡ hẳn ở migration
 *    000048 đúng vì phình ra như vậy. Cần sổ cái thật thì dựng sau, trên chính
 *    cặp tài khoản mà phiếu đang mang.
 *
 * 2. DANH MỤC LÀ "CHUNG VÀ RIÊNG" (Model::$_chungVaRieng). `garage_id IS NULL`
 *    là hệ thống tài khoản chuẩn Tân Phát dựng sẵn (111, 1111, 131, 331…), mọi
 *    gara đều dùng được; gara thêm tài khoản chi tiết của mình thì mang
 *    garage_id. Lọc cứng theo gara thì gara mới mở thấy danh mục TRỐNG RỖNG và
 *    phải tự gõ lại cả hệ thống tài khoản trước khi lập được một phiếu.
 *
 * 3. PHIẾU KHÔNG CÓ TRẠNG THÁI NHÁP. Phiếu thu/chi viết ra khi tiền đã đổi tay,
 *    gần như không có bản nháp. Nhưng cũng KHÔNG XOÁ ĐƯỢC — chứng từ tiền mà
 *    biến mất thì báo cáo hôm qua khác hôm nay mà không ai giải thích được.
 *    `status`: 1 = hiệu lực, 0 = đã huỷ (vẫn nằm đó, có dấu vết, báo cáo bỏ qua).
 *
 * BẢN CHỤP, KHÔNG PHẢI THAM CHIẾU: phiếu lưu `debit_code` / `credit_code` và
 * `doi_tuong_ten` ngay lúc lập. Sửa loại phiếu hay đổi tên khách sang năm sau
 * KHÔNG được làm đổi phiếu đã in và đã ký.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        // ================= 1. Danh mục tài khoản =================
        $this->run("
            CREATE TABLE IF NOT EXISTS `acc_accounts` (
                `id`         INT AUTO_INCREMENT PRIMARY KEY,
                `code`       VARCHAR(20)  NOT NULL,
                `name`       VARCHAR(255) NOT NULL,
                /* Cây đệ quy: 111 -> 1111 -> 111A. RESTRICT chứ không CASCADE —
                   xoá 111 mà kéo theo cả nhánh thì mất luôn những tài khoản
                   đang có phiếu trỏ vào, trong im lặng. */
                `parent_id`  INT DEFAULT NULL,
                `sort_order` INT NOT NULL DEFAULT 0,
                `status`     TINYINT(1) NOT NULL DEFAULT 1,
                `garage_id`  INT DEFAULT NULL,
                `create_at`  DATETIME DEFAULT NULL,
                `update_at`  DATETIME DEFAULT NULL,

                /* (garage_id, code): hai gara cùng có tài khoản 1111 chi tiết
                   của mình là chuyện thường. MySQL coi các NULL là khác nhau
                   nên chỉ mục này KHÔNG chặn được hai dòng chung trùng mã —
                   AccAccountsModel::findByCode() kiểm thêm ở tầng mã nguồn,
                   giống hệt cách `parts` đang làm. */
                UNIQUE KEY `uq_acc_accounts` (`garage_id`, `code`),
                KEY `idx_acc_accounts_parent` (`parent_id`),
                KEY `idx_acc_accounts_garage` (`garage_id`),

                CONSTRAINT `fk_acc_account_parent` FOREIGN KEY (`parent_id`)
                    REFERENCES `acc_accounts` (`id`) ON UPDATE CASCADE,
                CONSTRAINT `fk_acc_account_garage` FOREIGN KEY (`garage_id`)
                    REFERENCES `garages` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ================= 2. Danh mục loại phiếu =================
        $this->run("
            CREATE TABLE IF NOT EXISTS `acc_voucher_types` (
                `id`         INT AUTO_INCREMENT PRIMARY KEY,
                `code`       VARCHAR(20)  NOT NULL,
                `name`       VARCHAR(255) NOT NULL,
                /* thu / chi — quyết định phiếu này hiện ở màn Phiếu thu hay Phiếu chi */
                `loai`       VARCHAR(10)  NOT NULL DEFAULT 'thu',
                `debit_id`   INT DEFAULT NULL,
                `credit_id`  INT DEFAULT NULL,
                `sort_order` INT NOT NULL DEFAULT 0,
                `status`     TINYINT(1) NOT NULL DEFAULT 1,
                `garage_id`  INT DEFAULT NULL,
                `create_at`  DATETIME DEFAULT NULL,
                `update_at`  DATETIME DEFAULT NULL,

                UNIQUE KEY `uq_acc_vtypes` (`garage_id`, `code`),
                KEY `idx_acc_vtypes_garage` (`garage_id`),

                /* SET NULL: xoá một tài khoản khỏi danh mục thì loại phiếu mất
                   cặp nợ–có chứ không biến mất. Màn hình báo thiếu để người
                   dùng chọn lại — phiếu ĐÃ LẬP không ảnh hưởng, vì nó giữ bản
                   chụp mã tài khoản. */
                CONSTRAINT `fk_acc_vtype_debit`  FOREIGN KEY (`debit_id`)
                    REFERENCES `acc_accounts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT `fk_acc_vtype_credit` FOREIGN KEY (`credit_id`)
                    REFERENCES `acc_accounts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT `fk_acc_vtype_garage` FOREIGN KEY (`garage_id`)
                    REFERENCES `garages` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ================= 3. Phiếu thu / phiếu chi =================
        $this->run("
            CREATE TABLE IF NOT EXISTS `acc_vouchers` (
                `id`           INT AUTO_INCREMENT PRIMARY KEY,
                `voucher_no`   VARCHAR(50) NOT NULL,
                `loai`         VARCHAR(10) NOT NULL,          -- thu / chi
                `vtype_id`     INT DEFAULT NULL,
                `voucher_date` DATE NOT NULL,

                /* ĐỐI TƯỢNG — tài liệu: 'load danh mục khách hàng, nhà cung cấp,
                   nhân viên… hiểu là thu từ đối tượng nào'. Ba nguồn khác nhau
                   nên không gộp được vào một khoá ngoại:
                     partner -> `partners` (khách hàng / nhà cung cấp)
                     user    -> `users`    (nhân viên)
                     khac    -> người ngoài danh mục, chỉ có tên gõ tay */
                `dt_loai`      VARCHAR(10) NOT NULL DEFAULT 'partner',
                `partner_id`   INT DEFAULT NULL,
                `user_id`      INT DEFAULT NULL,
                /* BẢN CHỤP lúc lập, in thẳng lên phiếu. Đổi tên khách sang năm
                   sau không được làm đổi phiếu đã ký. */
                `dt_ten`       VARCHAR(255) DEFAULT NULL,
                `dt_diachi`    VARCHAR(255) DEFAULT NULL,

                `ly_do`        VARCHAR(255) DEFAULT NULL,
                `amount`       DECIMAL(15,2) NOT NULL DEFAULT 0,

                /* Bản chụp cặp tài khoản, chép từ loại phiếu lúc lập */
                `debit_code`   VARCHAR(20) DEFAULT NULL,
                `credit_code`  VARCHAR(20) DEFAULT NULL,

                `kem_theo`     VARCHAR(100) DEFAULT NULL,     -- 'Kèm theo … chứng từ gốc'
                `note`         VARCHAR(255) DEFAULT NULL,
                /* 1 = hiệu lực, 0 = đã huỷ. Không xoá chứng từ tiền. */
                `status`       TINYINT(1) NOT NULL DEFAULT 1,
                `created_by`   INT DEFAULT NULL,
                `garage_id`    INT NOT NULL,
                `create_at`    DATETIME DEFAULT NULL,
                `update_at`    DATETIME DEFAULT NULL,

                UNIQUE KEY `uq_acc_vouchers_no` (`garage_id`, `voucher_no`),
                KEY `idx_acc_vouchers_ngay` (`garage_id`, `voucher_date`),
                KEY `idx_acc_vouchers_loai` (`garage_id`, `loai`, `status`),
                KEY `idx_acc_vouchers_partner` (`partner_id`),

                CONSTRAINT `fk_acc_voucher_vtype` FOREIGN KEY (`vtype_id`)
                    REFERENCES `acc_voucher_types` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
                /* RESTRICT: còn phiếu thu của một khách thì không xoá được khách
                   đó — công nợ của họ tính từ chính mấy phiếu này. */
                CONSTRAINT `fk_acc_voucher_partner` FOREIGN KEY (`partner_id`)
                    REFERENCES `partners` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
                CONSTRAINT `fk_acc_voucher_user` FOREIGN KEY (`user_id`)
                    REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT `fk_acc_voucher_garage` FOREIGN KEY (`garage_id`)
                    REFERENCES `garages` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        echo "  Da tao 3 bang: acc_accounts, acc_voucher_types, acc_vouchers.\n";

        $this->gieoTaiKhoan();
        $this->gieoLoaiPhieu();
        $this->khaiManHinh();
    }

    public function down(){
        foreach (['acc-vouchers', 'acc-voucher-types', 'acc-accounts'] as $link){
            $m = $this->db->table('modules')->where('link', '=', $link)->first();
            if (empty($m)) continue;
            $this->db->delete('permissions', '`module_id` = ?', [(int) $m['id']]);
            $this->db->delete('modules', '`id` = ?', [(int) $m['id']]);
        }
        /* Thứ tự ngược chiều khoá ngoại: phiếu -> loại phiếu -> tài khoản */
        $this->run("DROP TABLE IF EXISTS `acc_vouchers`");
        $this->run("DROP TABLE IF EXISTS `acc_voucher_types`");
        $this->run("DROP TABLE IF EXISTS `acc_accounts`");
        echo "  Da go phan ke toan (3 bang + 3 man hinh).\n";
    }

    // ===== Gieo dữ liệu dùng chung =====

    /**
     * HỆ THỐNG TÀI KHOẢN DÙNG CHUNG (`garage_id` NULL) — theo Thông tư 133,
     * rút gọn còn những tài khoản một gara thật sự đụng tới.
     *
     * Gieo sẵn chứ không để trống: màn Loại phiếu bắt chọn tài khoản nợ và tài
     * khoản có, nên danh mục rỗng là không lập nổi một loại phiếu nào, và người
     * dùng mở phần mềm ra gặp ngay ba màn trắng không biết bắt đầu từ đâu.
     * Gara vẫn thêm / sửa tài khoản chi tiết của mình được.
     */
    private function gieoTaiKhoan(){
        /* [mã, tên, mã cha] — cha phải đứng trước con trong mảng này */
        $ds = [
            ['111',  'Tiền mặt',                                  null],
            ['1111', 'Tiền Việt Nam',                             '111'],
            ['112',  'Tiền gửi ngân hàng',                        null],
            ['1121', 'Tiền Việt Nam gửi ngân hàng',               '112'],
            ['131',  'Phải thu của khách hàng',                   null],
            ['138',  'Phải thu khác',                             null],
            ['141',  'Tạm ứng',                                   null],
            ['156',  'Hàng hoá',                                  null],
            ['331',  'Phải trả cho người bán',                    null],
            ['333',  'Thuế và các khoản phải nộp Nhà nước',       null],
            ['3331', 'Thuế GTGT phải nộp',                        '333'],
            ['334',  'Phải trả người lao động',                   null],
            ['338',  'Phải trả, phải nộp khác',                   null],
            ['511',  'Doanh thu bán hàng và cung cấp dịch vụ',    null],
            ['632',  'Giá vốn hàng bán',                          null],
            ['642',  'Chi phí quản lý kinh doanh',                null],
            ['711',  'Thu nhập khác',                             null],
            ['811',  'Chi phí khác',                              null],
        ];

        $now = date('Y-m-d H:i:s');
        $id  = [];
        $them = 0;
        foreach ($ds as $i => $r){
            list($ma, $ten, $cha) = $r;
            $co = $this->db->firstRaw(
                "SELECT `id` FROM `acc_accounts` WHERE `code` = ? AND `garage_id` IS NULL LIMIT 1", [$ma]);
            if (!empty($co['id'])){ $id[$ma] = (int) $co['id']; continue; }

            $this->db->insert('acc_accounts', [
                'code'       => $ma,
                'name'       => $ten,
                'parent_id'  => ($cha !== null && isset($id[$cha])) ? $id[$cha] : null,
                'sort_order' => $i,
                'status'     => 1,
                'garage_id'  => null,      // dùng chung mọi gara
                'create_at'  => $now,
            ]);
            $id[$ma] = (int) $this->db->lastId();
            $them++;
        }
        echo "  Da gieo $them tai khoan dung chung (Thong tu 133, rut gon).\n";
    }

    /**
     * LOẠI PHIẾU MẶC ĐỊNH — mỗi loại là một cặp tài khoản nợ–có viết sẵn, để
     * người dùng không phải tự nghĩ ra định khoản trước khi lập được phiếu đầu
     * tiên. Gara sửa hoặc thêm loại của mình được.
     */
    private function gieoLoaiPhieu(){
        $ds = [
            ['PT01', 'Thu tiền khách hàng',        'thu', '1111', '131'],
            ['PT02', 'Thu khác',                   'thu', '1111', '711'],
            ['PT03', 'Thu hoàn tạm ứng',           'thu', '1111', '141'],
            ['PC01', 'Chi trả nhà cung cấp',       'chi', '331',  '1111'],
            ['PC02', 'Chi lương nhân viên',        'chi', '334',  '1111'],
            ['PC03', 'Chi phí quản lý kinh doanh', 'chi', '642',  '1111'],
            ['PC04', 'Chi tạm ứng',                'chi', '141',  '1111'],
        ];

        $now  = date('Y-m-d H:i:s');
        $them = 0;
        foreach ($ds as $i => $r){
            list($ma, $ten, $loai, $no, $co) = $r;
            $daCo = $this->db->firstRaw(
                "SELECT `id` FROM `acc_voucher_types` WHERE `code` = ? AND `garage_id` IS NULL LIMIT 1", [$ma]);
            if (!empty($daCo['id'])) continue;

            $tkNo = $this->db->firstRaw("SELECT `id` FROM `acc_accounts` WHERE `code` = ? AND `garage_id` IS NULL LIMIT 1", [$no]);
            $tkCo = $this->db->firstRaw("SELECT `id` FROM `acc_accounts` WHERE `code` = ? AND `garage_id` IS NULL LIMIT 1", [$co]);

            $this->db->insert('acc_voucher_types', [
                'code'       => $ma,
                'name'       => $ten,
                'loai'       => $loai,
                'debit_id'   => !empty($tkNo['id']) ? (int) $tkNo['id'] : null,
                'credit_id'  => !empty($tkCo['id']) ? (int) $tkCo['id'] : null,
                'sort_order' => $i,
                'status'     => 1,
                'garage_id'  => null,
                'create_at'  => $now,
            ]);
            $them++;
        }
        echo "  Da gieo $them loai phieu dung chung.\n";
    }

    /**
     * Khai ba màn hình mới và cấp quyền.
     *
     * `phieu-thu` / `phieu-chi` ĐÃ CÓ từ migration 000082 (lúc đó mới là chỗ
     * đứng trên menu) — giữ nguyên, nay chúng có nghiệp vụ thật.
     *
     * QUYỀN: Admin và Manager toàn quyền; Staff CHỈ XEM phiếu, không đụng danh
     * mục tài khoản. Định khoản sai là sai toàn bộ báo cáo, và thợ thì không có
     * việc gì ở đó. Gara nào muốn giao cho nhân viên lập phiếu thì tự cấp thêm
     * ở Nhóm › Phân quyền, không cần sửa code.
     *
     * ĐỪNG QUÊN NHÓM `Admin`. Nhóm hệ thống của Tân Phát phải là TẬP CHA của
     * mọi quyền — PhanQuyenNhomTest có một khẳng định đúng cho việc này ("Manager
     * / Staff không có quyền nào mà Admin không có"), và bản đầu của migration
     * này chỉ cấp cho Manager nên test đỏ ngay. Hậu quả thật nếu lọt: người
     * quản trị Tân Phát mở màn Danh mục tài khoản ra thì bị đá về "không có
     * quyền", trong khi chủ gara vào được.
     */
    private function khaiManHinh(){
        $now = date('Y-m-d H:i:s');

        $toanQuyen = ['view', 'add', 'edit', 'delete'];
        $man = [
            'acc-accounts'      => ['Danh mục tài khoản',
                                    ['Admin' => $toanQuyen, 'Manager' => $toanQuyen, 'Staff' => []]],
            'acc-voucher-types' => ['Danh mục loại phiếu',
                                    ['Admin' => $toanQuyen, 'Manager' => $toanQuyen, 'Staff' => []]],
        ];
        /* Hai màn đã có sẵn: chỉ cấp thêm quyền ghi, không khai lại */
        $capThem = [
            'phieu-thu' => ['Admin' => $toanQuyen, 'Manager' => $toanQuyen, 'Staff' => ['view']],
            'phieu-chi' => ['Admin' => $toanQuyen, 'Manager' => $toanQuyen, 'Staff' => ['view']],
        ];

        foreach ($man as $link => $ct){
            $m = $this->db->table('modules')->where('link', '=', $link)->first();
            if (empty($m)){
                $this->db->insert('modules', ['name' => $ct[0], 'link' => $link, 'create_at' => $now]);
                echo "  Da khai man \"" . $ct[0] . "\" (/admin/$link).\n";
            }
            $capThem[$link] = $ct[1];
        }

        $them = 0;
        foreach ($capThem as $link => $quyen){
            $m = $this->db->table('modules')->where('link', '=', $link)->first();
            if (empty($m['id'])) continue;
            $moduleId = (int) $m['id'];

            foreach ($quyen as $tenNhom => $roles){
                /* MỌI nhóm cùng tên, không phải nhóm đầu tiên: từ migration
                   000093 mỗi gara có bộ nhóm riêng. */
                foreach ((array) $this->db->getRaw("SELECT `id` FROM `groups` WHERE `name` = ?", [$tenNhom]) as $n){
                    foreach ($roles as $role){
                        $co = $this->db->firstRaw("SELECT `id` FROM `permissions`
                                                    WHERE `module_id` = ? AND `group_id` = ? AND `role` = ? LIMIT 1",
                                                  [$moduleId, (int) $n['id'], $role]);
                        if (!empty($co['id'])) continue;
                        $this->db->insert('permissions', [
                            'module_id' => $moduleId, 'group_id' => (int) $n['id'], 'role' => $role,
                        ]);
                        $them++;
                    }
                }
            }
        }
        echo "  Da cap $them dong quyen cho cac nhom Manager / Staff.\n";
    }
};
