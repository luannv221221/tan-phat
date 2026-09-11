<?php
use App\core\Model;
class GroupsModel extends Model{
    protected $_table = 'groups'; //Gán tên bảng
    protected $_fields = '*'; //Các field cần lấy khi fetch và fetchAll
    protected $_primary = 'id'; //Trường khoá chính

    public function getLists(){
        return $this->getList();
    }

    public function add($data){
        return $this->addNew($data);
    }

    public function edit($data, $id){
        return $this->updateById($data, $id);
    }

    public function remove($id){
        return $this->deleteById($id);
    }

    public function getDetail($id){
        return $this->getFirst($id);
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
     * Nhóm TOÀN QUYỀN quản lý người dùng = nhóm sửa được bảng phân quyền
     * (role `permission` trên module `groups`).
     *
     * Vì sao lấy mốc này: ai sửa được bảng phân quyền thì tự cấp cho mình được
     * mọi thứ, giới hạn họ ở màn Người dùng chẳng chặn được gì. Ai KHÔNG sửa
     * được thì không bao giờ được tạo ra người mạnh hơn mình.
     * Không so tên nhóm "Admin": đổi tên nhóm là hỏng.
     */
    public function laToanQuyen($groupId){
        $r = $this->table('permissions')
            ->joinOn('modules', 'permissions.module_id', 'modules.id')
            ->select('permissions.id')
            ->where('permissions.group_id', '=', (int) $groupId)
            ->where('modules.link', '=', 'groups')
            ->where('permissions.role', '=', 'permission')
            ->first();
        return !empty($r);
    }

    /**
     * Những nhóm mà người thuộc nhóm $groupId được phép gán cho tài khoản khác.
     *
     *   Toàn quyền  -> mọi nhóm.
     *   Còn lại     -> nhóm có quyền là TẬP CON THỰC SỰ, KHÔNG RỖNG của quyền
     *                  nhóm mình. Dữ liệu hiện tại: Manager -> chỉ Staff.
     *
     * - Tập con: không trao được quyền mình không có.
     * - Thực sự: loại chính nhóm mình và nhóm ngang quyền — Manager không tạo
     *   ra Manager khác, không tự sửa được tài khoản của mình.
     * - Không rỗng: nhóm KHÔNG có dòng quyền nào thì RoleMiddleware bỏ qua toàn
     *   bộ kiểm tra (nhánh `!empty($permissionData)`), tức là vào được MỌI màn
     *   hình. Nhóm rỗng là nhóm mạnh nhất chứ không phải yếu nhất.
     *
     * Không so theo tên: Admin nới quyền Staff vượt Manager thì Staff tự biến
     * khỏi danh sách của Manager — đúng là phải thế.
     */
    public function nhomGiaoDuoc($groupId){
        $tatCa = (array) $this->getLists();
        if ($this->laToanQuyen($groupId)) return $tatCa;

        $cuaToi = $this->quyenCua($groupId);
        $kq = [];
        foreach ($tatCa as $g){
            if ((int) $g['id'] === (int) $groupId) continue;
            $cuaHo = $this->quyenCua($g['id']);
            if (empty($cuaHo)) continue;
            if (count(array_diff($cuaHo, $cuaToi)) > 0) continue;
            if (count($cuaHo) >= count($cuaToi)) continue;
            $kq[] = $g;
        }
        return $kq;
    }

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