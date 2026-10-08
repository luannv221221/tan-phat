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
 *     5. Tài khoản chủ gara (nếu khai)     -> có người đăng nhập
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
            $host = strtolower(trim((string) $gara['code'])) . '.' . $goc;
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

        // --- 5. Tài khoản chủ gara ---
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
     * Tên miền gốc của hệ thống, suy từ tên miền của GARA TỔNG.
     *
     * Gara tổng thường có hai host: tên miền gốc (etek.rikkeiedu.org) và tên
     * miền phụ theo mã (tp01.etek.rikkeiedu.org). Cái nào ÍT DẤU CHẤM hơn là
     * gốc. Suy ra thay vì viết cứng để đổi tên miền hệ thống không phải sửa
     * code — chỉ sửa dòng trong `garage_domains`.
     */
    private function tenMienGoc(){
        $tong = Load::model('GaragesModel')->getMaster();
        if (empty($tong['id'])) return '';

        $ds = Load::model('GarageDomainsModel')->theoGara((int) $tong['id']);
        $goc = '';
        foreach ((array) $ds as $d){
            $h = (string) $d['host'];
            if ($goc === '' || substr_count($h, '.') < substr_count($goc, '.')) $goc = $h;
        }
        return $goc;
    }

    /** Tạo tài khoản chủ gara (nhóm Manager). Trả về '' nếu xong, hoặc câu báo lỗi. */
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

        $nhom = $this->firstRaw("SELECT `id` FROM `groups` WHERE `name` = 'Manager' LIMIT 1");
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
