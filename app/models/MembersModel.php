<?php

use App\core\Model;

/**
 * STOREFRONT — Thành viên website. Mật khẩu bcrypt.
 */
class MembersModel extends Model {

    protected $_table   = 'members';
    protected $_fields  = '*';
    protected $_primary = 'id';

    public function findByEmail($email){
        return $this->table($this->_table)->where('email', '=', $email)->first();
    }

    public function getDetail($id){ return $this->getFirst($id); }

    /** Tạo thành viên mới (băm mật khẩu). @return int id */
    public function register($data){
        $data['password']  = password_hash($data['password'], PASSWORD_BCRYPT);
        $data['status']    = 1;
        $data['create_at'] = date('Y-m-d H:i:s');
        $this->addNew($data);
        return $this->lastId();
    }

    /**
     * Nhân viên tạo hồ sơ khách ngay tại gara (khác register(): khách tự đăng ký).
     *
     * Email và mật khẩu ĐỀU có thể bỏ trống — khách vãng lai không cần tài
     * khoản đăng nhập. Hai chốt quan trọng:
     *
     *   1. Email trống lưu NULL chứ KHÔNG phải chuỗi rỗng. Cột có khoá UNIQUE;
     *      MySQL cho nhiều dòng NULL nhưng chuỗi rỗng thì bằng chính nó, nên
     *      khách vãng lai thứ hai sẽ bị báo trùng email.
     *
     *   2. Không có mật khẩu thì gán một chuỗi băm NGẪU NHIÊN, không để trống.
     *      Ô rỗng hoặc chuỗi rỗng có thể khiến password_verify() khớp ngoài ý
     *      muốn; băm của 32 byte ngẫu nhiên thì không ai gõ trúng.
     *
     * @return int id
     */
    public function adminAdd($data){
        $matKhau = isset($data['password']) ? (string) $data['password'] : '';
        if ($matKhau === '') $matKhau = bin2hex(random_bytes(32));

        $email = isset($data['email']) ? trim((string) $data['email']) : '';

        $this->addNew([
            'email'     => $email !== '' ? $email : null,
            'password'  => password_hash($matKhau, PASSWORD_BCRYPT),
            'name'      => isset($data['name']) ? $data['name'] : '',
            'phone'     => !empty($data['phone']) ? $data['phone'] : null,
            'address'   => !empty($data['address']) ? $data['address'] : null,
            'status'    => isset($data['status']) ? (int) $data['status'] : 1,
            'create_at' => date('Y-m-d H:i:s'),
        ]);
        return $this->lastId();
    }

    /** Khách đã có số điện thoại này chưa — tránh tạo trùng người ở gara */
    public function findByPhone($phone){
        $phone = trim((string) $phone);
        if ($phone === '') return null;
        return $this->table($this->_table)->where('phone', '=', $phone)->first();
    }

    /** Xác thực đăng nhập. @return array|null bản ghi thành viên nếu đúng */
    public function checkLogin($email, $password){
        $m = $this->findByEmail($email);
        if (empty($m) || (int) $m['status'] !== 1) return null;
        if (!password_verify($password, $m['password'])) return null;
        return $m;
    }

    public function updateProfile($data, $id){
        $data['update_at'] = date('Y-m-d H:i:s');
        return $this->updateById($data, $id);
    }

    // ================= Màn hình quản trị (admin/customers) =================

    /**
     * Danh sách khách hàng cho admin, kèm số đơn đã đặt.
     *
     * @param string $keyword lọc theo tên / email / SĐT
     * @param string $status  '' = tất cả, '1' = đang hoạt động, '0' = đã khoá
     */
    public function adminList($keyword = '', $status = '', $limit = 20, $offset = 0){
        $sql = 'SELECT `members`.*,
                       (SELECT COUNT(*) FROM `orders` WHERE `orders`.`member_id` = `members`.`id`) AS order_count
                  FROM `members`';
        list($where, $bind) = $this->adminWhere($keyword, $status);
        $sql .= $where . ' ORDER BY `members`.`id` DESC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;

        return $this->getRaw($sql, $bind);
    }

    public function adminCount($keyword = '', $status = ''){
        list($where, $bind) = $this->adminWhere($keyword, $status);
        $row = $this->firstRaw('SELECT COUNT(*) AS c FROM `members`' . $where, $bind);
        return (int) ($row['c'] ?? 0);
    }

    /** Mệnh đề WHERE dùng chung cho adminList/adminCount. Giá trị luôn qua placeholder. */
    private function adminWhere($keyword, $status){
        $cond = [];
        $bind = [];

        if ($keyword !== ''){
            /* Tìm thêm theo BIỂN SỐ XE.
               CSKH nhận điện thoại thường chỉ có mỗi biển số ("xe 30A-12345 hẹn
               sáng mai"), chưa biết tên chủ. Bắt họ tra tên trước rồi mới ra xe
               là làm ngược quy trình thật.

               Một khách có nhiều xe nên phải dùng EXISTS chứ không JOIN: JOIN sẽ
               nhân dòng, khách 3 xe hiện ra 3 lần trong danh sách.

               So theo cột bien_so_chuan (chỉ chữ + số, viết hoa) nên gõ
               "30a 123 45" hay "30A-123.45" đều ra. */
            $cond[] = '(`name` LIKE ? OR `email` LIKE ? OR `phone` LIKE ?'
                    . ' OR EXISTS (SELECT 1 FROM `member_vehicles` mv'
                    . '             WHERE mv.`member_id` = `members`.`id`'
                    . '               AND mv.`bien_so_chuan` LIKE ?))';
            $like   = '%' . $keyword . '%';
            $bind[] = $like; $bind[] = $like; $bind[] = $like;

            $chuan  = preg_replace('/[^A-Za-z0-9]/', '', $keyword);
            // Từ khoá không còn chữ/số nào (vd gõ mỗi "---") thì cho khớp rỗng,
            // đừng để '%%' biến thành "khớp mọi biển số".
            $bind[] = $chuan === '' ? "\x00" : '%' . strtoupper($chuan) . '%';
        }
        if ($status === '0' || $status === '1'){
            $cond[] = '`status` = ?';
            $bind[] = (int) $status;
        }

        return [$cond ? ' WHERE ' . implode(' AND ', $cond) : '', $bind];
    }

    /** Đổi mật khẩu (tự băm). Bên gọi phải kiểm tra mật khẩu cũ trước. */
    public function updatePassword($plain, $id){
        return $this->updateById([
            'password'  => password_hash($plain, PASSWORD_BCRYPT),
            'update_at' => date('Y-m-d H:i:s'),
        ], $id);
    }
}
