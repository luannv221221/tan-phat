<?php

use App\core\Model;
use App\core\Load;
use App\core\Hash;

/**
 * MỞ MỘT GARA MỚI — dựng sẵn bộ khung để gara dùng được ngay.
 *
 * Tạo xong một dòng trong bảng `garages` thì gara đó vẫn chưa làm được gì:
 * chưa có tên miền nên không có website, chưa có kho nên không nhập hàng được,
 * chưa có tài khoản nên không ai đăng nhập được, và trang web thì mang tên của
 * Tân Phát vì chưa khai cấu hình riêng.
 *
 * Trước đây phải đi năm màn khác nhau để khai đủ, và quên một thứ thì tới lúc
 * dùng mới biết. Nay tạo gara là dựng luôn:
 *
 *     1. Tên miền phụ theo mã gara        -> có website riêng
 *     2. Kho mặc định                      -> nhập / xuất hàng được
 *     3. Nhóm khách "Khách lẻ"             -> khai khách được ngay
 *     4. Cấu hình web (tên, hotline, địa chỉ) -> trang không còn mang tên Tân Phát
 *     5. Nhóm quyền riêng (Manager, Staff) -> chủ gara tự phân quyền nhân viên
 *     6. Tài khoản chủ gara (nếu khai)     -> có người đăng nhập
 *
 * CHẠY LẠI KHÔNG SINH BẢN SAO: mỗi bước đều kiểm "đã có chưa" trước. Gara mở
 * dở dang rồi sửa tay thì gọi lại hàm này vẫn an toàn.
 *
 * KHÔNG ném lỗi khi một bước phụ không làm được (ví dụ chưa biết tên miền
 * gốc). Gara đã tạo xong rồi mới tới đây; để một bước phụ làm đổ cả việc là
 * người dùng thấy "lỗi" trong khi gara thật ra đã có. Những gì không dựng được
 * thì trả về trong danh sách `thieu` để màn hình nói lại cho người dùng.
 */
class MoGaraModel extends Model {

    /* Không sở hữu bảng nào — model này chỉ điều phối các model khác. */

    /**
     * @param int   $garaId
     * @param array $chu  ['name' =>, 'email' =>, 'password' =>] — bỏ trống thì không tạo tài khoản
     * @return array ['da' => [việc đã dựng], 'thieu' => [việc không dựng được, kèm lý do]]
     */
    public function dungBoKhung($garaId, array $chu = []){
        $garaId = (int) $garaId;
        $gara   = Load::model('GaragesModel')->getDetail($garaId);
        if (empty($gara['id'])) return ['da' => [], 'thieu' => ['Không tìm thấy gara.']];

        $da = $thieu = [];

        // --- 1. Tên miền phụ ---
        $goc = $this->tenMienGoc();
        if ($goc === ''){
            $thieu[] = 'Chưa đặt được tên miền: hệ thống chưa khai tên miền gốc.';
        } else {
            /* GỘT MÃ GARA THÀNH NHÃN TÊN MIỀN HỢP LỆ.
               Trước đây chỉ `strtolower(trim())`, nên mã có dấu cách, dấu tiếng
               Việt hay gạch dưới sinh ra host sai — "Long Biên" thành
               "long biên.etek..." là địa chỉ không tồn tại. Gara tạo xong nhìn
               như bình thường, tới lúc mở tên miền mới thấy "Không tìm thấy
               gara", mà nhìn vào đâu cũng không ra lý do.
               slugify(): bỏ dấu, hạ chữ thường, dấu cách thành gạch nối. */
            $nhan = slugify((string) $gara['code']);
            if ($nhan === ''){
                $thieu[] = 'Chưa đặt được tên miền: mã gara "' . $gara['code']
                         . '" không gột được thành tên miền (cần có chữ hoặc số).';
                $goc = '';   // bỏ qua bước tên miền
            }
        }

        if ($goc !== ''){
            $host = $nhan . '.' . $goc;
            $D    = Load::model('GarageDomainsModel');
            if (empty($D->theoHost($host))){
                $this->chen('garage_domains', [
                    'garage_id'  => $garaId,
                    'host'       => $host,
                    'is_primary' => 1,
                    'status'     => 1,
                    'create_at'  => date('Y-m-d H:i:s'),
                ]);
                $da[] = 'Tên miền ' . $host;
            }
        }

        Model::trongGara($garaId, function() use ($garaId, $gara, &$da, &$thieu, $chu){

            // --- 2. Kho mặc định ---
            $K  = Load::model('WarehousesModel');
            $co = $K->getDefault();
            if (empty($co['id'])){
                $ma = strtoupper(trim((string) $gara['code'])) . '-KHO';
                $K->add([
                    'code'       => $ma,
                    'name'       => 'Kho ' . $gara['name'],
                    'address'    => !empty($gara['address']) ? $gara['address'] : null,
                    'is_default' => 1,
                    'sort_order' => 0,
                    'status'     => 1,
                    'create_at'  => date('Y-m-d H:i:s'),
                ]);
                $da[] = 'Kho ' . $gara['name'];
            }

            // --- 3. Nhóm khách mặc định ---
            $N = Load::model('CustomerGroupsModel');
            if (empty($N->getActive())){
                $N->add([
                    'name'             => 'Khách lẻ',
                    'discount_percent' => 0,
                    'sort_order'       => 0,
                    'status'           => 1,
                    'create_at'        => date('Y-m-d H:i:s'),
                ]);
                $da[] = 'Nhóm khách "Khách lẻ"';
            }

            // --- 4. Cấu hình web riêng ---
            $kv = ['site_name' => $gara['name']];
            if (!empty($gara['phone']))    $kv['hotline'] = $gara['phone'];
            if (!empty($gara['address']))  $kv['address'] = $gara['address'];
            if (!empty($gara['email']))    $kv['email']   = $gara['email'];
            if (!empty($gara['tax_code'])) $kv['tax_code'] = $gara['tax_code'];
            Load::model('GarageSettingsModel')->saveMany($kv);
            $da[] = 'Cấu hình website mang tên gara';
        });

        // --- 5. Nhóm quyền riêng của gara ---
        $nhom = $this->nhanBanNhom($garaId);
        foreach ($nhom['da'] as $x)    $da[] = $x;
        foreach ($nhom['thieu'] as $x) $thieu[] = $x;

        // --- 6. Tài khoản chủ gara ---
        $ten = isset($chu['name']) ? trim((string) $chu['name']) : '';
        $mail = isset($chu['email']) ? trim((string) $chu['email']) : '';
        $mk  = isset($chu['password']) ? (string) $chu['password'] : '';
        if ($ten !== '' || $mail !== '' || $mk !== ''){
            $loi = $this->taoChuGara($garaId, $ten, $mail, $mk);
            if ($loi === '') $da[] = 'Tài khoản chủ gara ' . $mail;
            else             $thieu[] = $loi;
        }

        return ['da' => $da, 'thieu' => $thieu];
    }

    // ===== Helper =====

    /**
     * Tên miền gốc của hệ thống, suy từ TÊN MIỀN CHÍNH của gara tổng.
     *
     * Gara tổng thường có hai host: tên miền gốc (`etek.rikkeiedu.org`) và tên
     * miền phụ theo mã (`tp01.etek.rikkeiedu.org`, đánh dấu là chính). Suy ra
     * thay vì viết cứng, để đổi tên miền hệ thống chỉ phải sửa dòng trong
     * `garage_domains` chứ không sửa code.
     *
     * CÁCH LÀM: lấy host CHÍNH của gara tổng; nếu nó bắt đầu bằng chính mã gara
     * tổng thì bỏ nhãn đó đi, phần còn lại là gốc.
     *
     *     tp01.etek.rikkeiedu.org  (mã TP01)  ->  etek.rikkeiedu.org
     *     etek.rikkeiedu.org       (không khớp mã) ->  etek.rikkeiedu.org
     *
     * BẢN ĐẦU CHỌN "HOST ÍT DẤU CHẤM NHẤT" — sai. Thêm một host để chạy thử như
     * `tp01.localhost` (1 dấu chấm) là nó thắng `etek.rikkeiedu.org` (2 dấu
     * chấm), và gara mới mở ra nhận tên miền `<mã>.tp01.localhost`. Nhìn thì
     * vẫn "thành công", chỉ có điều địa chỉ đó không ai vào được.
     */
    private function tenMienGoc(){
        $tong = Load::model('GaragesModel')->getMaster();
        if (empty($tong['id'])) return '';

        $ds = (array) Load::model('GarageDomainsModel')->theoGara((int) $tong['id']);
        if (empty($ds)) return '';

        // theoGara() đã sắp is_primary trước, nên dòng đầu là host chính
        $host = (string) $ds[0]['host'];

        $ma = slugify((string) $tong['code']);
        if ($ma !== '' && strpos($host, $ma . '.') === 0){
            return substr($host, strlen($ma) + 1);
        }
        return $host;
    }

    /**
     * NHÂN BẢN BỘ NHÓM QUYỀN cho gara mới, kèm nguyên các dòng `permissions`.
     *
     * Từ 08/10/2026 nhóm quyền thuộc về một gara (migration 000093). Gara mở ra
     * mà không có nhóm nào thì chủ gara vào màn Quản lý nhóm thấy bảng trống,
     * và không gán được nhóm nào cho nhân viên mình.
     *
     * NHÓM MẪU LÀ NHÓM CÙNG TÊN CỦA GARA TỔNG, không phải một danh sách quyền
     * viết cứng ở đây: Tân Phát sửa bộ quyền mặc định ở màn Quản lý nhóm là gara
     * mở sau được theo, không phải viết thêm migration. Đổi lại, ĐỔI TÊN nhóm
     * mẫu là lần mở gara sau nhân bản sai — nên màn Quản lý nhóm không cấp
     * quyền `edit` (đổi tên) cho Manager.
     *
     * KHÔNG dùng Model::trongGara() ở đây: nhóm phải ghi `garage_id` tường minh
     * cho gara mới, mà GroupsModel lọc theo gara làm việc — mượn danh nghĩa gara
     * mới rồi đọc nhóm mẫu của gara tổng là không thấy gì.
     */
    private function nhanBanNhom($garaId){
        $garaId = (int) $garaId;
        $da = $thieu = [];

        $tong = Load::model('GaragesModel')->getMaster();
        if (empty($tong['id'])){
            return ['da' => [], 'thieu' => ['Chưa dựng được nhóm quyền: hệ thống chưa có gara tổng.']];
        }
        $tongId = (int) $tong['id'];

        $mau = (array) $this->getRaw(
            'SELECT `id`, `name` FROM `groups` WHERE `garage_id` = ? ORDER BY `name` ASC', [$tongId]);
        if (empty($mau)){
            return ['da' => [], 'thieu' => ['Chưa dựng được nhóm quyền: gara tổng chưa có nhóm nào '
                                          . 'để làm mẫu (chạy migration 000093).']];
        }

        foreach ($mau as $m){
            $co = $this->firstRaw('SELECT `id` FROM `groups` WHERE `name` = ? AND `garage_id` = ? LIMIT 1',
                                  [$m['name'], $garaId]);
            if (!empty($co['id'])) continue;         // chạy lại: đã có, bỏ qua

            $this->chen('groups', [
                'name' => $m['name'], 'garage_id' => $garaId, 'create_at' => date('Y-m-d H:i:s'),
            ]);
            $moiId = (int) $this->lastId();

            foreach ((array) $this->getRaw(
                        'SELECT `module_id`, `role` FROM `permissions` WHERE `group_id` = ?',
                        [(int) $m['id']]) as $p){
                $this->chen('permissions', [
                    'group_id' => $moiId, 'module_id' => (int) $p['module_id'], 'role' => $p['role'],
                ]);
            }
            $da[] = 'Nhóm quyền "' . $m['name'] . '"';
        }

        return ['da' => $da, 'thieu' => $thieu];
    }

    /** Tạo tài khoản chủ gara (nhóm Manager CỦA GARA ĐÓ). Trả về '' nếu xong, hoặc câu báo lỗi. */
    private function taoChuGara($garaId, $ten, $mail, $mk){
        if ($ten === '' || $mail === '' || $mk === ''){
            return 'Chưa tạo tài khoản chủ gara: cần đủ họ tên, email và mật khẩu.';
        }
        if (!filter_var($mail, FILTER_VALIDATE_EMAIL)){
            return 'Chưa tạo tài khoản chủ gara: email không đúng định dạng.';
        }
        if (mb_strlen($mk) < 6){
            return 'Chưa tạo tài khoản chủ gara: mật khẩu cần từ 6 ký tự.';
        }

        /* Email là tên đăng nhập của CẢ HỆ THỐNG, không phải của riêng gara —
           tìm khắp các gara chứ không chỉ gara này. */
        $co = $this->firstRaw('SELECT `id` FROM `users` WHERE `email` = ? LIMIT 1', [$mail]);
        if (!empty($co)) return 'Chưa tạo tài khoản chủ gara: email ' . $mail . ' đã có người dùng.';

        /* NHÓM MANAGER CỦA CHÍNH GARA NÀY, không phải nhóm Manager đầu tiên tìm
           thấy. Từ migration 000093 mỗi gara có nhóm Manager riêng; lấy nhầm
           nhóm của gara khác là chủ gara mới vào màn Quản lý nhóm không thấy
           nhóm nào của mình, mà sửa quyền thì sửa trúng gara kia. */
        $nhom = $this->firstRaw("SELECT `id` FROM `groups` WHERE `name` = 'Manager' AND `garage_id` = ? LIMIT 1",
                                [(int) $garaId]);
        if (empty($nhom['id'])){
            // Chưa chạy migration 000093 (cột `garage_id` còn NULL hết) thì lùi về cách cũ
            $nhom = $this->firstRaw("SELECT `id` FROM `groups` WHERE `name` = 'Manager' LIMIT 1");
        }
        if (empty($nhom['id'])) return 'Chưa tạo tài khoản chủ gara: hệ thống chưa có nhóm Manager.';

        $this->chen('users', [
            'name'      => $ten,
            'email'     => $mail,
            'password'  => Hash::make($mk),
            'group_id'  => (int) $nhom['id'],
            'status'    => 1,
            'garage_id' => (int) $garaId,
            'create_at' => date('Y-m-d H:i:s'),
        ]);
        return '';
    }

    /** Ghi thẳng, không qua model nào — hai bảng này không có model riêng tiện dùng */
    private function chen($bang, array $data){
        return $this->insert($bang, $data);
    }
}
