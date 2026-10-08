<?php
use App\core\Model;

/**
 * NHÓM QUYỀN — từ 08/10/2026 mỗi gara có bộ nhóm riêng (migration 000093).
 *
 *     garage_id IS NULL  -> NHÓM HỆ THỐNG: Tân Phát với tư cách người vận hành
 *                           nền tảng. `Admin` là nhóm này. Gara khác không
 *                           thấy, không sửa, không gán được cho ai.
 *     garage_id = X      -> nhóm của gara X.
 *
 * KHÔNG dùng cờ `$_theoGara` của lớp cha được: cờ đó lọc đúng một điều kiện
 * `garage_id = <gara làm việc>`, mà ở đây gara tổng phải thấy CẢ nhóm hệ thống
 * (garage_id IS NULL) lẫn nhóm của mọi gara — nó là người vận hành, hỏng ở gara
 * nào cũng phải vào xem được. Nên lọc tay bằng dkNhom().
 */
class GroupsModel extends Model{
    protected $_table = 'groups'; //Gán tên bảng
    protected $_fields = '*'; //Các field cần lấy khi fetch và fetchAll
    protected $_primary = 'id'; //Trường khoá chính

    /**
     * Điều kiện "nhóm này tôi được thấy": [sql, bindings].
     *
     *   gara tổng  -> không lọc (thấy hết, kể cả nhóm hệ thống)
     *   gara khác  -> chỉ nhóm của chính nó
     *   dòng lệnh chưa ép gara -> không lọc (migrate, gieo dữ liệu)
     */
    private function dkNhom(){
        $g = self::garaLoc();
        if ($g === null) return ['', []];
        if ($g === 0)    return ['1 = 0', []];
        if ($this->laGaraTongLoc()) return ['', []];
        return ['`groups`.`garage_id` = ?', [(int) $g]];
    }

    /** Điều kiện "nhóm này tôi SỞ HỮU, được sửa quyền" — gara tổng không sở hữu nhóm của gara khác */
    private function dkSoHuu(){
        $g = self::garaLoc();
        if ($g === null) return ['', []];
        if ($g === 0)    return ['1 = 0', []];
        if ($this->laGaraTongLoc()){
            // Gara tổng sở hữu nhóm hệ thống + nhóm của chính nó
            return ['(`groups`.`garage_id` IS NULL OR `groups`.`garage_id` = ?)', [(int) $g]];
        }
        return ['`groups`.`garage_id` = ?', [(int) $g]];
    }

    /** Ghép điều kiện vào $where cho getList/getFirst */
    private function voi($dk, $where, array $bd){
        list($sql, $b) = $dk;
        if ($sql === '') return [$where, $bd];
        $where = $where !== '' ? '(' . $where . ') AND ' . $sql : $sql;
        return [$where, array_merge($bd, $b)];
    }

    /** Nhóm gara làm việc ĐƯỢC THẤY, kèm tên gara để phân biệt bốn nhóm cùng tên "Manager" */
    public function getLists(){
        list($where, $bd) = $this->voi($this->dkNhom(), '', []);
        $sql = 'SELECT `groups`.*, `garages`.`name` AS garage_name, `garages`.`code` AS garage_code
                  FROM `groups` LEFT JOIN `garages` ON `garages`.`id` = `groups`.`garage_id`';
        if ($where !== '') $sql .= ' WHERE ' . $where;
        $sql .= ' ORDER BY `groups`.`garage_id` IS NULL DESC, `garages`.`name` ASC, `groups`.`name` ASC';
        return $this->getRaw($sql, $bd);
    }

    /** Nhóm của ĐÚNG một gara, không kèm nhóm hệ thống — cho việc nhân bản */
    public function theoGara($garaId){
        return $this->getRaw('SELECT * FROM `groups` WHERE `garage_id` = ? ORDER BY `name` ASC',
                             [(int) $garaId]);
    }

    public function add($data){
        /* Nhóm tạo từ giao diện LUÔN thuộc gara của người tạo. Nhóm hệ thống
           (garage_id NULL) chỉ migration sinh ra được — không có cửa nào cho
           một gara tạo ra nhóm đứng ngoài mọi gara. */
        $g = self::garaLoc();
        if ($g !== null && $g > 0) $data['garage_id'] = (int) $g;
        return $this->addNew($data);
    }

    public function edit($data, $id){
        unset($data['garage_id']);          // không chuyển nhóm sang gara khác
        list($where, $bd) = $this->voi($this->dkSoHuu(), '`groups`.`id` = ?', [(int) $id]);
        return $this->update($this->_table, $data, $where, $bd);
    }

    public function remove($id){
        list($where, $bd) = $this->voi($this->dkSoHuu(), '`groups`.`id` = ?', [(int) $id]);
        return $this->delete($this->_table, $where, $bd);
    }

    /** Chi tiết nhóm — chỉ nhóm được thấy. Kèm tên gara để màn hình nói rõ của ai. */
    public function getDetail($id){
        list($where, $bd) = $this->voi($this->dkNhom(), '`groups`.`id` = ?', [(int) $id]);
        return $this->firstRaw(
            'SELECT `groups`.*, `garages`.`name` AS garage_name, `garages`.`code` AS garage_code,
                    `garages`.`is_master` AS garage_is_master
               FROM `groups` LEFT JOIN `garages` ON `garages`.`id` = `groups`.`garage_id`
              WHERE ' . $where, $bd);
    }

    /** Nhóm $id có thuộc quyền SỬA của gara làm việc không */
    public function soHuu($id){
        list($where, $bd) = $this->voi($this->dkSoHuu(), '`groups`.`id` = ?', [(int) $id]);
        return !empty($this->firstRaw('SELECT `id` FROM `groups` WHERE ' . $where, $bd));
    }

    /** Số tài khoản đang thuộc nhóm — xoá nhóm còn người là làm họ mất nhóm */
    public function soNguoi($id){
        $r = $this->firstRaw('SELECT COUNT(*) AS n FROM `users` WHERE `group_id` = ?', [(int) $id]);
        return (int) (isset($r['n']) ? $r['n'] : 0);
    }

    /**
     * Quyền của một nhóm, dạng ["<module_id>:<role>", ...] — ghép thành chuỗi
     * để so tập con bằng array_diff cho gọn.
     */
    public function quyenCua($groupId){
        $rows = $this->table('permissions')
            ->select('module_id, role')
            ->where('group_id', '=', (int) $groupId)
            ->get();
        $r = [];
        foreach ((array) $rows as $x) $r[] = $x['module_id'] . ':' . $x['role'];
        return array_values(array_unique($r));
    }

    /**
     * Nhóm TOÀN QUYỀN quản lý người dùng = nhóm HỆ THỐNG sửa được bảng phân
     * quyền (role `permission` trên module `groups`, và `garage_id IS NULL`).
     *
     * Vì sao lấy mốc "sửa được bảng phân quyền": ai sửa được bảng đó thì tự cấp
     * cho mình được mọi thứ, giới hạn họ ở màn Người dùng chẳng chặn được gì.
     * Ai KHÔNG sửa được thì không bao giờ được tạo ra người mạnh hơn mình.
     * Không so tên nhóm "Admin": đổi tên nhóm là hỏng.
     *
     * VÌ SAO THÊM "NHÓM HỆ THỐNG" (08/10/2026): từ migration 000093, mỗi gara
     * có bộ nhóm riêng và nhóm Manager của gara ĐƯỢC cấp role `permission` trên
     * `groups` để chủ gara tự phân quyền cho nhân viên mình. Thiếu điều kiện
     * garage_id IS NULL thì chính việc cấp quyền đó biến mọi Manager thành toàn
     * quyền — gán được nhóm Admin cho nhân viên, tức tự nâng quyền trong một
     * bước. Toàn quyền là chuyện của người vận hành nền tảng, không phải của
     * một gara trên nền tảng đó.
     */
    public function laToanQuyen($groupId){
        $r = $this->table('permissions')
            ->joinOn('modules', 'permissions.module_id', 'modules.id')
            ->joinOn('groups', 'permissions.group_id', 'groups.id')
            ->select('permissions.id')
            ->where('permissions.group_id', '=', (int) $groupId)
            ->whereNull('groups.garage_id')
            ->where('modules.link', '=', 'groups')
            ->where('permissions.role', '=', 'permission')
            ->first();
        return !empty($r);
    }

    /**
     * Những nhóm mà người thuộc nhóm $groupId được phép gán cho tài khoản khác.
     *
     *   Toàn quyền  -> mọi nhóm.
     *   Còn lại     -> nhóm CÙNG GARA, có quyền là TẬP CON THỰC SỰ, KHÔNG RỖNG
     *                  của quyền nhóm mình. Dữ liệu hiện tại: Manager của gara
     *                  X -> chỉ Staff của gara X.
     *
     * - Cùng gara: nhóm của gara khác không phải thứ mình gán cho ai được, và
     *   nhóm hệ thống (Admin) thì càng không.
     * - Tập con: không trao được quyền mình không có.
     * - Thực sự: loại chính nhóm mình và nhóm ngang quyền — Manager không tạo
     *   ra Manager khác, không tự sửa được tài khoản của mình.
     * - Không rỗng: nhóm KHÔNG có dòng quyền nào thì xưa nay RoleMiddleware bỏ
     *   qua toàn bộ kiểm tra, tức là vào được MỌI màn hình. Chỗ đó đã vá
     *   (08/10/2026) nhưng vẫn giữ điều kiện này: gán người vào một nhóm rỗng
     *   là gán vào nhóm không vào được đâu cả.
     *
     * Không so theo tên: Admin nới quyền Staff vượt Manager thì Staff tự biến
     * khỏi danh sách của Manager — đúng là phải thế.
     */
    public function nhomGiaoDuoc($groupId){
        if ($this->laToanQuyen($groupId)) return (array) $this->getLists();

        $garaToi = $this->garaCua($groupId);
        if ($garaToi === null) return [];   // nhóm hệ thống mà không toàn quyền: không gán được gì

        $cuaToi = $this->quyenCua($groupId);
        $kq = [];
        foreach ((array) $this->theoGara($garaToi) as $g){
            if ((int) $g['id'] === (int) $groupId) continue;
            $cuaHo = $this->quyenCua($g['id']);
            if (empty($cuaHo)) continue;
            if (count(array_diff($cuaHo, $cuaToi)) > 0) continue;
            if (count($cuaHo) >= count($cuaToi)) continue;
            $kq[] = $g;
        }
        return $kq;
    }

    /**
     * Người thuộc nhóm $cuaToi có được SỬA BẢNG QUYỀN của nhóm $id không.
     *
     * Dùng CÙNG MỘT LUẬT với nhomGiaoDuoc(): chỉ đụng được nhóm YẾU HƠN MÌNH,
     * cùng gara. Lý do phải chặt tới mức này, không chỉ "không tự sửa nhóm
     * mình": chủ gara cấp được `permission` trên màn Nhóm cho nhân viên (đó là
     * quyền chủ gara đang có, nên bộ lọc "chỉ tick quyền mình có" cho qua).
     * Lúc đó nhân viên mở nhóm Manager ra — không nâng được quyền cho mình, vì
     * chỉ tick được trong tầm của mình, NHƯNG BỎ TICK thì được: hạ nhóm Manager
     * xuống bằng nhóm Staff, tức khoá chính chủ gara ra khỏi việc của họ.
     *
     *   Toàn quyền     -> mọi nhóm
     *   Nhóm của mình  -> không (đây là chốt chống leo thang quan trọng nhất)
     *   Khác gara      -> không
     *   Nhóm RỖNG cùng gara -> được. Rỗng là yếu hơn mọi thứ, và nhóm vừa tạo
     *                     thì rỗng — không cho sửa là tạo ra nhóm không bao giờ
     *                     cấp được quyền.
     *   Còn lại        -> quyền phải là TẬP CON THỰC SỰ của quyền nhóm mình
     */
    public function suaQuyenDuoc($cuaToi, $id){
        $cuaToi = (int) $cuaToi; $id = (int) $id;
        if ($cuaToi <= 0 || $id <= 0) return false;
        if ($this->laToanQuyen($cuaToi)) return true;
        if ($id === $cuaToi) return false;

        /* SO TRỰC TIẾP GARA CỦA HAI NHÓM, không hỏi phiên đăng nhập.
           Bản đầu gọi soHuu($id) — mà soHuu() lọc theo GARA ĐANG LÀM VIỆC, và
           ở dòng lệnh (bộ test, công cụ) thì không có gara nào đang làm việc
           nên nó trả về true với mọi nhóm. Hàm này nói "nhóm A được sửa nhóm B
           không", một câu hỏi về hai nhóm — kéo phiên đăng nhập vào là câu trả
           lời đổi theo nơi gọi. */
        $a = $this->garaCua($cuaToi);
        $b = $this->garaCua($id);
        if ($a === null || $b === null || $a !== $b) return false;

        $toi = $this->quyenCua($cuaToi);
        $ho  = $this->quyenCua($id);
        if (empty($ho)) return true;
        if (count(array_diff($ho, $toi)) > 0) return false;
        return count($ho) < count($toi);
    }

    /** Gara của nhóm $id, hoặc null khi là nhóm hệ thống / không có nhóm đó */
    public function garaCua($id){
        $r = $this->firstRaw('SELECT `garage_id` FROM `groups` WHERE `id` = ?', [(int) $id]);
        if (empty($r) || $r['garage_id'] === null || $r['garage_id'] === '') return null;
        return (int) $r['garage_id'];
    }

    /**
     * Nhóm của tài khoản $userId — KHÔNG lọc theo gara.
     *
     * RoleMiddleware gọi hàm này để biết người đang đăng nhập có quyền gì. Lọc
     * theo gara ở đây là tự bắn vào chân: tài khoản Admin của Tân Phát thuộc
     * nhóm hệ thống (garage_id IS NULL), lọc `garage_id = 1` là nó mất sạch
     * quyền. Mà lọc cũng vô nghĩa — tra theo id tài khoản thì nhóm trả về đúng
     * là nhóm của chính người đó.
     */
    public function getGroupByUser($userId){
        // joinOn() bọc backtick tự động.
        // Bản cũ: join($this->_table, 'users.group_id=groups.id')
        // => sinh ra `groups`.id không backtick. `GROUPS` là TỪ KHOÁ DÀNH RIÊNG
        //    của MySQL 8.0 (window function) nên câu này lỗi cú pháp trên MySQL 8.
        $data = $this->table('users')
            ->joinOn($this->_table, 'users.group_id', $this->_table . '.id')
            ->select('users.group_id')
            ->where('users.id', '=', $userId)
            ->first();
        return $data;
    }
}
