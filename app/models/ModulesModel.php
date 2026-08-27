<?php
use App\core\Model;

/**
 * Module = một MÀN HÌNH admin có thể phân quyền.
 *
 * Dòng trong bảng này không tạo ra màn hình. Nó chỉ ĐĂNG KÝ một màn hình đã
 * có sẵn để hai thứ sau chạy được:
 *   1. Màn hình đó hiện ra trong bảng phân quyền nhóm (admin > Nhóm > Phân quyền)
 *   2. RoleMiddleware chặn được người không có quyền
 *
 * `link` phải TRÙNG đoạn URL của màn hình: /admin/<link>/... Sai một chữ là
 * RoleMiddleware không khớp được, và khi không khớp thì nó BỎ QUA luôn phần
 * kiểm quyền — nghĩa là ai đăng nhập cũng vào được. Vì vậy màn hình Quản lý
 * module cho CHỌN từ danh sách màn hình có thật, thay vì gõ tay.
 */
class ModulesModel extends Model{
    protected $_table = 'modules'; //Gán tên bảng
    protected $_fields = '*'; //Các field cần lấy khi fetch và fetchAll
    protected $_primary = 'id'; //Trường khoá chính

    /**
     * Thư mục view KHÔNG phải màn hình — không cho đăng ký làm module.
     * `print` là thư mục mẫu in dùng chung cho báo giá/hoá đơn, không có URL riêng.
     */
    private static $khongPhaiManHinh = ['print'];

    public function getLists(){
        return $this->getList();
    }

    /** Kèm số dòng phân quyền đang trỏ tới — để biết module nào đã được dùng */
    public function getListsKemQuyen(){
        return $this->table($this->_table)
            ->select('`modules`.*, '
                   . '(SELECT COUNT(*) FROM `permissions` p WHERE p.`module_id` = `modules`.`id`) AS so_quyen, '
                   . '(SELECT COUNT(DISTINCT p.`group_id`) FROM `permissions` p WHERE p.`module_id` = `modules`.`id`) AS so_nhom')
            ->orderBy('modules.name', 'ASC')
            ->get();
    }

    public function getDetail($id){ return $this->getFirst($id); }

    public function findByLink($link){
        return $this->table($this->_table)->where('link', '=', $link)->first();
    }

    /**
     * Các MÀN HÌNH ADMIN CÓ THẬT trên đĩa (mỗi thư mục app/views/admin/<x> là
     * một màn hình). Đây là nguồn cho ô chọn ở form thêm module.
     */
    public function manHinhTrenDia(){
        $goc = dirname(dirname(__DIR__)) . '/app/views/admin/';
        $ra  = [];
        foreach (glob($goc . '*', GLOB_ONLYDIR) ?: [] as $d){
            $ten = basename($d);
            if (in_array($ten, self::$khongPhaiManHinh, true)) continue;
            $ra[] = $ten;
        }
        sort($ra);
        return $ra;
    }

    /** Màn hình có thật nhưng CHƯA đăng ký module — chính là danh sách cho phép thêm */
    public function manHinhChuaDangKy(){
        $daCo = [];
        foreach ($this->getLists() ?: [] as $m){
            if (!empty($m['link'])) $daCo[strtolower($m['link'])] = true;
        }

        $ra = [];
        foreach ($this->manHinhTrenDia() as $t){
            if (!isset($daCo[strtolower($t)])) $ra[] = $t;
        }
        return $ra;
    }

    /** Module đang trỏ tới màn hình KHÔNG còn trên đĩa — quyền gán cho nó là quyền chết */
    public function moduleMoCoi(){
        $tren = array_map('strtolower', $this->manHinhTrenDia());
        $ra = [];
        foreach ($this->getLists() ?: [] as $m){
            if (empty($m['link'])) { $ra[] = $m; continue; }
            if (!in_array(strtolower($m['link']), $tren, true)) $ra[] = $m;
        }
        return $ra;
    }

    public function add($data){
        $data['create_at'] = date('Y-m-d H:i:s');
        $this->addNew($data);
        return $this->lastId();
    }

    public function edit($data, $id){
        $data['update_at'] = date('Y-m-d H:i:s');
        return $this->updateById($data, $id);
    }

    /**
     * Xoá module KÈM các dòng phân quyền trỏ tới nó.
     *
     * Không xoá kèm thì `permissions` còn lại dòng trỏ tới module_id không tồn
     * tại; bảng phân quyền vẫn tick nhưng chẳng gác gì, mà nhìn vào lại tưởng
     * là đang có quyền.
     */
    public function remove($id){
        $id = (int) $id;
        return $this->transaction(function($db) use ($id){
            $db->delete('permissions', '`module_id` = ?', [$id]);
            $db->delete('modules', '`id` = ?', [$id]);
            return true;
        });
    }
}
