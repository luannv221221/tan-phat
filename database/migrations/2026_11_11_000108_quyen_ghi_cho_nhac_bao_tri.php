<?php
/**
 * VÁ DỮ LIỆU QUYỀN SAU KHI SIẾT PHÂN QUYỀN TUYẾN POST (11/11/2026).
 *
 *   1. Mở quyền SỬA màn "Nhắc bảo trì" cho nhóm đang được xem nó.
 *   2. Trả lại quyền XEM cho các màn trước đây vào được nhờ lỗi khớp tên module.
 *
 * ──────────────────────────────────────────────────────────────────────────
 *  VÌ SAO CÓ MIGRATION NÀY (11/11/2026)
 * ──────────────────────────────────────────────────────────────────────────
 *
 * Cùng ngày, RoleMiddleware được vá để một request GHI (POST) vào màn của một
 * module phải có quyền GHI trên module đó — trước đây chỉ cần quyền `view`,
 * nên 17 tuyến POST ghi dữ liệu thật đi qua được bằng quyền ĐỌC. Xem
 * core/Route.php::post() và app/middlewares/RoleMiddleware.php.
 *
 * Bản vá đó làm đúng, nhưng nó lộ ra một chỗ THIẾU DỮ LIỆU QUYỀN:
 * `admin/nhac-bao-tri/save-config` là tuyến GHI DUY NHẤT của màn Nhắc bảo trì,
 * mà KHÔNG MỘT NHÓM NÀO của gara có quyền `edit` trên module đó — cả Manager
 * lẫn Staff đều chỉ có `view`. Vá xong thì màn Nhắc bảo trì thành CHỈ ĐỌC với
 * mọi gara, chỉ Admin của Tân Phát sửa được.
 *
 * BA LÝ DO CẤP, không phải một:
 *
 *   1. CHÍNH CONTROLLER ĐÃ ĐÒI `edit` TỪ TRƯỚC. Baotri::saveConfig() mở đầu
 *      bằng `if (!route('admin/nhac-bao-tri/edit/0'))`. Ý định của người viết
 *      là "sửa cấu hình này cần quyền edit"; câu đó chưa bao giờ chạy vì không
 *      có tuyến POST nào tên `nhac-bao-tri/edit/0` nên route() luôn trả về
 *      "cho qua". Nay middleware làm thật điều controller đã nói, nên phần
 *      thiếu là DỮ LIỆU QUYỀN, không phải cái chốt.
 *
 *   2. CHU KỲ BẢO TRÌ LÀ CẤU HÌNH RIÊNG CỦA TỪNG GARA (ghi vào
 *      `garage_settings`, không đụng cấu hình chung). Gara sửa chu kỳ của chính
 *      mình không ảnh hưởng gara khác — đây là nền tảng mỗi gara tự chủ, khoá
 *      lại là bắt họ gọi điện cho Tân Phát để đổi một con số của riêng họ.
 *      tests/CachLyGaraTest.php mục 8 giữ đúng hành vi đó.
 *
 *   3. MÀN NÀY KHÔNG CÓ VIỆC GÌ KHÁC ĐỂ LÀM. Nhóm nào được cho `view` màn Nhắc
 *      bảo trì là được cho để DÙNG màn đó, mà toàn bộ công dụng của nó là đặt
 *      chu kỳ. `view` không kèm `edit` ở đây là một màn hình vô nghĩa.
 *
 * CHỈ CẤP ĐÚNG MỘT MODULE NÀY. Năm module còn lại mà bản vá chặn thêm
 * (products/import, purchase-orders, dat-hang-kho-tong, chat, garages) thì
 * KHÔNG cấp — ở đó controller cũng đòi quyền mạnh hơn và việc làm thì nặng
 * thật (import hàng hoá, sinh phiếu nhập kho, đặt hàng kho tổng, trả lời khách).
 * Chặn là đúng ý. Gara nào muốn mở cho nhóm Staff thì tích một ô ở màn
 * Nhóm > Phân quyền — đó là quyết định của chủ gara, không phải của migration.
 *
 * CHẠY LẠI ĐƯỢC: dòng quyền nào có rồi thì bỏ qua.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){

        if (!$this->hasTable('permissions') || !$this->hasTable('modules')) return;

        $m = $this->db->query(
            'SELECT `id` FROM `modules` WHERE `link` = ? LIMIT 1', ['nhac-bao-tri']
        )->fetch(\PDO::FETCH_ASSOC);

        $mid = !empty($m) ? (int) $m['id'] : 0;
        if ($mid <= 0) return;

        /* Đọc hết ra PHP rồi mới ghi: MySQL không cho vừa SELECT vừa INSERT
           trên cùng một bảng trong một câu lệnh. */
        $dong = (array) $this->db->query(
            'SELECT `group_id`, `role` FROM `permissions` WHERE `module_id` = ?', [$mid]
        )->fetchAll(\PDO::FETCH_ASSOC);

        $coView = [];   // nhóm đang được XEM màn này
        $coEdit = [];   // nhóm đã có quyền SỬA rồi
        foreach ($dong as $r){
            $gid = (int) $r['group_id'];
            if ($r['role'] === 'view') $coView[$gid] = true;
            if ($r['role'] === 'edit') $coEdit[$gid] = true;
        }

        /* CHÉP THEO NHÓM NÀO ĐANG CÓ `view`, không tự nghĩ ra danh sách nhóm.
           Nhờ thế gara nào cố ý không cho một nhóm thấy màn Nhắc bảo trì thì
           vẫn giữ nguyên ý đó — không nhóm nào tự nhiên mọc thêm quyền. */
        foreach (array_keys($coView) as $gid){
            if (!empty($coEdit[$gid])) continue;

            $this->db->query(
                'INSERT INTO `permissions` (`module_id`, `group_id`, `role`) VALUES (?, ?, ?)',
                [$mid, $gid, 'edit']
            );
        }

        $this->traLaiQuyenDocBiTrum();
    }

    /**
     * TRẢ LẠI QUYỀN XEM CHO NHỮNG MÀN TRƯỚC ĐÂY VÀO ĐƯỢC NHỜ MỘT LỖI KHỚP TÊN.
     *
     * Cùng ngày, RoleMiddleware::khopModule() được vá để nhận diện module theo
     * BIÊN đường dẫn. Trước đó nó hỏi Request::is('admin/<link>/*', ...), mà
     * `/*` ở đó là lượng từ biểu thức chính quy ("không hoặc nhiều dấu gạch
     * chéo") chứ không phải dấu sao đường dẫn — nên module có link là TIỀN TỐ
     * của link khác thì trùm luôn module kia:
     *     'news'    trùm 'news-categories', 'newsletter'
     *     'ton-kho' trùm 'ton-kho-dau-ky',  'ton-kho-lau'
     *
     * Hệ quả: suốt thời gian đó, ai có `view` trên 'ton-kho' là MỞ ĐƯỢC cả màn
     * Tồn đầu kỳ và Tồn kho lâu, dù bảng quyền của hai màn đó không có tên họ.
     * Người cấu hình nhóm tích 'ton-kho' rồi bỏ trống hai màn kia — và không ai
     * phát hiện, vì hai màn vẫn mở bình thường.
     *
     * Vá xong thì nhóm Staff mất quyền XEM hai màn đang dùng hằng ngày. Bản vá
     * là vá lỗ phân quyền GHI, không phải để cắt quyền ĐỌC của ai; nên ở đây
     * ghi thẳng vào bảng quyền đúng cái quyền ĐỌC mà họ vốn đã có. Từ nay bảng
     * quyền nói đúng điều hệ thống làm.
     *
     * CHỈ CẤP `view`, KHÔNG CẤP QUYỀN GHI. Nhập tồn đầu kỳ là việc của người
     * quản lý kho (nhóm Manager đã có `add`), không phải của cố vấn dịch vụ.
     *
     * TỰ TÌM CÁC CẶP TỪ CSDL, không ghi cứng bốn cặp ở trên: danh sách module
     * trên máy chủ thật có thể khác máy local (module bật/tắt theo gara).
     */
    protected function traLaiQuyenDocBiTrum(){

        $mods = (array) $this->db->query('SELECT `id`, `link` FROM `modules`')
                                 ->fetchAll(\PDO::FETCH_ASSOC);

        /* Nhóm nào đang có `view` trên module nào */
        $view = [];
        foreach ((array) $this->db->query(
                    "SELECT `module_id`, `group_id` FROM `permissions` WHERE `role` = 'view'"
                 )->fetchAll(\PDO::FETCH_ASSOC) as $r){
            $view[(int) $r['module_id']][(int) $r['group_id']] = true;
        }

        foreach ($mods as $ngan){
            foreach ($mods as $dai){

                /* $ngan->link phải là TIỀN TỐ THẬT của $dai->link */
                if ($ngan['link'] === $dai['link']) continue;
                if (strpos($dai['link'], $ngan['link']) !== 0) continue;

                $idNgan = (int) $ngan['id'];
                $idDai  = (int) $dai['id'];

                if (empty($view[$idNgan])) continue;

                foreach (array_keys($view[$idNgan]) as $gid){
                    if (!empty($view[$idDai][$gid])) continue;   // đã có rồi

                    $this->db->query(
                        'INSERT INTO `permissions` (`module_id`, `group_id`, `role`) VALUES (?, ?, ?)',
                        [$idDai, $gid, 'view']
                    );

                    $view[$idDai][$gid] = true;                  // chạy lại được
                }
            }
        }
    }

    public function down(){

        /* KHÔNG THU LẠI — cố ý, cùng lý do như migration 000107.
           Đến lúc này không còn phân biệt được dòng `edit` nào do migration
           thêm với dòng nào chủ gara tự tích sau đó ở màn Nhóm > Phân quyền.
           Thu hết là cướp mất quyền người ta tự cấp. Thừa một dòng quyền trên
           một màn mà nhóm đó vốn đã nhìn thấy là cái giá rẻ hơn nhiều. */
    }
};
