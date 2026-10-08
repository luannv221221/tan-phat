<?php
use App\core\Model;

/**
 * Quyền của một nhóm trên một màn hình: (group_id, module_id, role).
 *
 * KHÔNG có `garage_id` ở đây, và cố ý: bảng này khoá theo `group_id`, mà từ
 * 08/10/2026 mỗi nhóm đã thuộc về một gara (`groups.garage_id`, migration
 * 000093). Thêm cột gara nữa là hai nguồn sự thật cho cùng một câu hỏi, rồi
 * sớm muộn lệch nhau. Lọc theo gara làm ở GroupsModel.
 */
class PermissionsModel extends Model{
    protected $_table = 'permissions'; //Gán tên bảng
    protected $_fields = '*'; //Các field cần lấy khi fetch và fetchAll
    protected $_primary = 'id'; //Trường khoá chính

    /**
     * Các `role` hợp lệ. RoleMiddleware chỉ gác đúng năm cái này — một giá trị
     * lạ lọt vào bảng thì không gác gì, mà nhìn vào bảng phân quyền lại tưởng
     * là đang có quyền.
     *
     * `permission` chỉ có nghĩa trên module `groups` (được sửa bảng phân quyền).
     */
    const ROLES = ['view', 'add', 'edit', 'delete', 'permission'];

    public function add($data){
        return $this->addNew($data);
    }

    public function remove($id){
        return $this->delete($this->_table, '`group_id` = ?', [$id]);
    }

    public function getPermission($groupId){
        return $this->getList('`group_id` = ?', [$groupId]);
    }

    /**
     * Đặt LẠI toàn bộ quyền của một nhóm, trong MỘT giao dịch.
     *
     * @param int   $groupId
     * @param array $dong    [[module_id, role], ...] — đã lọc hợp lệ ở nơi gọi
     *
     * Vì sao một giao dịch: bản cũ xoá hết rồi chèn lại trong vòng lặp ngoài
     * giao dịch. Chèn đổ ở dòng thứ ba là nhóm còn hai quyền, hoặc rỗng hẳn —
     * mà nhóm rỗng xưa nay là nhóm vào được mọi màn. Mất quyền thì người dùng
     * biết ngay, chứ "bị thêm quyền vì lưu lỗi" thì không ai nhận ra.
     */
    public function luuChoNhom($groupId, array $dong){
        $groupId = (int) $groupId;
        return $this->transaction(function($db) use ($groupId, $dong){
            $db->delete('permissions', '`group_id` = ?', [$groupId]);
            foreach ($dong as $d){
                $db->insert('permissions', [
                    'module_id' => (int) $d[0], 'group_id' => $groupId, 'role' => (string) $d[1],
                ]);
            }
            return true;
        });
    }
}
