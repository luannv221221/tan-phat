<?php
/**
 * CẤP QUYỀN THẬT CHO NHÓM Manager VÀ Staff.
 *
 * Trước migration này hai nhóm đó gần như RỖNG: Manager có 3 dòng quyền trên
 * 1 màn hình, Staff có 0. Nghĩa là cấp tài khoản Staff cho thợ xong, họ đăng
 * nhập vào KHÔNG THẤY GÌ — một tài khoản vô dụng.
 *
 * NGUYÊN TẮC CHIA
 *   Staff    người trực tiếp làm việc với khách và xe: lập báo giá, hoá đơn,
 *            tiếp nhận bảo hành, khai khách và xe. XEM được hàng hoá và tồn
 *            kho để biết còn hàng không, nhưng KHÔNG sửa được danh mục.
 *            KHÔNG có quyền `delete` ở đâu cả — sai thì sửa, không xoá. Xoá
 *            một hoá đơn đã ghi sổ là mất dấu vết kế toán.
 *
 *   Manager  Staff + trông coi chi nhánh: xoá được chứng từ, sửa được danh
 *            mục hàng hoá và danh mục của gara, làm phiếu nhập/xuất kho, xem
 *            báo cáo. Có `view` trên module gara để hiện được Ô ĐỔI GARA
 *            trên thanh đầu trang.
 *
 * CẢ HAI ĐỀU KHÔNG ĐƯỢC: Người dùng, Nhóm, Quản lý module, Cấu hình website,
 * Menu, Tin tức, Banner, Dự án, Thư viện. Đó là việc của Admin — nhóm nào
 * cũng sửa được cấu hình thì phân quyền thành trang trí.
 *
 * CHỈ THÊM, KHÔNG XOÁ. Chạy lại không sinh dòng trùng. Ai đã tự chỉnh quyền
 * cho hai nhóm này thì phần chỉnh đó được giữ nguyên — migration chỉ bù vào
 * những dòng còn thiếu.
 *
 * KHÔNG ĐỤNG NHÓM Admin.
 */

use App\core\Migration;

return new class extends Migration {

    /* Viết tắt cho gọn: X = xem, T = thêm, S = sửa, D = xoá */
    const XEM  = ['view'];
    const XTS  = ['view', 'add', 'edit'];
    const XTSD = ['view', 'add', 'edit', 'delete'];

    private function quyen(){
        return [
            'Staff' => [
                // Bán hàng — làm được việc, nhưng không xoá được gì
                'quotations'       => self::XTS,
                'sales-invoices'   => self::XTS,
                'partners'         => self::XTS,
                // CSKH
                'customers'        => self::XTS,
                'warranty'         => self::XTS,
                'lich-bao-hanh'    => self::XEM,
                'nhac-bao-tri'     => self::XEM,
                'contact-messages' => ['view', 'edit'],
                /* `chat` chi co view/reply/set-status/delete — KHONG co man them.
                   Va reply/set-status khong khop '/add' hay '/edit/*' nen
                   RoleMiddleware chi doi `view`. Cap them add/edit la rac. */
                'chat'             => self::XEM,
                // Hàng hoá — chỉ TRA CỨU. Thợ cần biết còn hàng không và giá
                // bao nhiêu, không cần sửa danh mục.
                'products'         => self::XEM,
                'services'         => self::XEM,
                'part-categories'  => self::XEM,
                // Kho — chỉ xem tồn
                'ton-kho'          => self::XEM,
                'the-kho'          => self::XEM,
            ],

            'Manager' => [
                // Bán hàng — có thêm quyền xoá
                'quotations'       => self::XTSD,
                'sales-invoices'   => self::XTSD,
                'orders'           => ['view', 'edit'],
                'partners'         => self::XTSD,
                'bao-cao-ban-hang' => self::XEM,
                // CSKH
                'customers'        => self::XTS,
                'customer-groups'  => self::XEM,
                'warranty'         => self::XTSD,
                'lich-bao-hanh'    => self::XEM,
                'nhac-bao-tri'     => self::XEM,
                'contact-messages' => ['view', 'edit'],
                'chat'             => ['view', 'delete'],
                'reviews'          => ['view', 'edit'],
                'bao-cao-cskh'     => self::XEM,
                // Hàng hoá — sửa được
                'products'         => self::XTS,
                'services'         => self::XTS,
                'part-categories'  => self::XTS,
                'product-units'    => self::XEM,
                'product-brands'   => self::XEM,
                // Kho
                'goods-receipts'   => self::XTS,
                'goods-issues'     => self::XTS,
                'transfers'        => self::XTS,
                'stock-takes'      => self::XTS,
                'ton-kho'          => self::XEM,
                'the-kho'          => self::XEM,
                'bien-dong-ton'    => self::XEM,
                'ton-kho-lau'      => self::XEM,
                'warehouses'       => self::XEM,
                // Gara — `view` trên `garages` là thứ làm HIỆN ô đổi gara
                'garages'          => self::XEM,
                'garage-catalog'   => self::XTSD,
                // Thống kê
                'thong-ke'         => self::XEM,
            ],
        ];
    }

    /**
     * Quyền phải GỠ khỏi nhóm: [tên nhóm => [module link => [role, ...]]]
     *
     * Nhóm Manager có sẵn view/add/edit trên module `groups` từ bản dump gốc —
     * không migration nào cấp cả. Nghĩa là Manager mở được Hệ thống → Nhóm →
     * Phân quyền và TỰ CẤP CHO MÌNH mọi quyền, kể cả ngang Admin.
     *
     * Trước đây nó nằm im vì Manager gần như không có quyền gì nên chẳng ai
     * dùng nhóm đó. Migration này biến Manager thành vai trò dùng thật, nên lỗ
     * đó thành rủi ro sống — phải gỡ cùng lúc.
     *
     * Ai sửa được bảng phân quyền thì mọi phân quyền khác chỉ còn là trang trí.
     */
    private function phaiGo(){
        return [
            'Manager' => ['groups' => ['view', 'add', 'edit', 'delete', 'permission']],
            'Staff'   => ['groups' => ['view', 'add', 'edit', 'delete', 'permission']],
        ];
    }

    public function up(){
        $them = 0;
        $thieu = [];

        // Gỡ TRƯỚC khi cấp: nếu vì lý do gì đó bảng cấp ở dưới có nhầm `groups`
        // thì thứ tự này để lộ ra ngay, thay vì gỡ xong lại cấp lại.
        $go = 0;
        foreach ($this->phaiGo() as $tenNhom => $dsModule){
            $nhom = $this->db->table('groups')->where('name', '=', $tenNhom)->first();
            if (empty($nhom)) continue;
            foreach ($dsModule as $link => $roles){
                $m = $this->db->table('modules')->where('link', '=', $link)->first();
                if (empty($m)) continue;
                foreach ($roles as $role){
                    $co = $this->db->table('permissions')
                        ->where('module_id', '=', $m['id'])
                        ->where('group_id', '=', $nhom['id'])
                        ->where('role', '=', $role)->first();
                    if (empty($co)) continue;
                    $this->db->delete('permissions', '`id` = ?', [$co['id']]);
                    $go++;
                    echo "  [GO] $tenNhom khong con quyen `$role` tren man hinh Nhom.\n";
                }
            }
        }
        if ($go === 0) echo "  (khong co quyen nguy hiem nao can go)\n";

        foreach ($this->quyen() as $tenNhom => $dsModule){
            $nhom = $this->db->table('groups')->where('name', '=', $tenNhom)->first();
            if (empty($nhom)){
                echo "  [BO QUA] Khong co nhom \"$tenNhom\".\n";
                continue;
            }

            foreach ($dsModule as $link => $roles){
                $m = $this->db->table('modules')->where('link', '=', $link)->first();
                if (empty($m)){ $thieu[] = $link; continue; }

                foreach ($roles as $role){
                    $co = $this->db->table('permissions')
                        ->where('module_id', '=', $m['id'])
                        ->where('group_id', '=', $nhom['id'])
                        ->where('role', '=', $role)->first();
                    if (!empty($co)) continue;

                    $this->db->insert('permissions', [
                        'module_id' => $m['id'],
                        'group_id'  => $nhom['id'],
                        'role'      => $role,
                    ]);
                    $them++;
                }
            }
        }

        /* Module không tồn tại thì BÁO RA chứ không im lặng bỏ qua: tên viết
           sai ở bảng trên sẽ khiến một màn hình không bao giờ được cấp quyền,
           mà migration vẫn ghi "OK". */
        if (!empty($thieu)){
            echo "  [CANH BAO] Khong tim thay module: " . implode(', ', array_unique($thieu)) . "\n";
        }
        echo "  Da them $them dong quyen cho Manager va Staff.\n";
    }

    public function down(){
        /* Trả lại quyền `groups` đã gỡ — down() phải dựng lại đúng trạng thái
           trước, kể cả khi trạng thái đó là một lỗ hổng. Muốn giữ bản vá thì
           đừng rollback. */
        foreach ($this->phaiGo() as $tenNhom => $dsModule){
            $nhom = $this->db->table('groups')->where('name', '=', $tenNhom)->first();
            if (empty($nhom) || $tenNhom !== 'Manager') continue;   // chỉ Manager từng có
            foreach ($dsModule as $link => $roles){
                $m = $this->db->table('modules')->where('link', '=', $link)->first();
                if (empty($m)) continue;
                foreach (['view', 'add', 'edit'] as $role){         // đúng 3 role vốn có
                    $co = $this->db->table('permissions')
                        ->where('module_id', '=', $m['id'])
                        ->where('group_id', '=', $nhom['id'])
                        ->where('role', '=', $role)->first();
                    if (!empty($co)) continue;
                    $this->db->insert('permissions', [
                        'module_id' => $m['id'], 'group_id' => $nhom['id'], 'role' => $role,
                    ]);
                }
            }
        }

        /* Gỡ ĐÚNG những dòng migration này cấp. Không xoá sạch quyền của hai
           nhóm: người dùng có thể đã tự thêm quyền khác, gỡ hết là phá của họ. */
        $go = 0;
        foreach ($this->quyen() as $tenNhom => $dsModule){
            $nhom = $this->db->table('groups')->where('name', '=', $tenNhom)->first();
            if (empty($nhom)) continue;

            foreach ($dsModule as $link => $roles){
                $m = $this->db->table('modules')->where('link', '=', $link)->first();
                if (empty($m)) continue;
                foreach ($roles as $role){
                    $this->db->delete('permissions',
                        '`module_id` = ? AND `group_id` = ? AND `role` = ?',
                        [$m['id'], $nhom['id'], $role]);
                    $go++;
                }
            }
        }
        echo "  Da go quyen da cap ($go dong).\n";
    }
};
