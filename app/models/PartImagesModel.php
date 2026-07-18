<?php

use App\core\Model;

/**
 * TASK_77 — Ảnh của phụ tùng.
 *
 * Ảnh đại diện (is_primary=1) hiển thị ở danh sách/website; các ảnh còn lại
 * là slide chi tiết. Luôn đảm bảo có tối đa 1 ảnh primary cho mỗi phụ tùng.
 */
class PartImagesModel extends Model {

    protected $_table   = 'part_images';
    protected $_fields  = '*';
    protected $_primary = 'id';

    /** Ảnh của 1 phụ tùng: primary trước, rồi theo thứ tự */
    public function getByPart($partId){
        return $this->table($this->_table)
                    ->where('part_id', '=', $partId)
                    ->orderBy('is_primary', 'DESC')
                    ->orderBy('sort_order', 'ASC')
                    ->orderBy('id', 'ASC')
                    ->get();
    }

    public function getDetail($id){
        return $this->getFirst($id);
    }

    public function countByPart($partId){
        $r = $this->table($this->_table)
                  ->select('COUNT(*) AS total')
                  ->where('part_id', '=', $partId)
                  ->first();
        return (int) ($r['total'] ?? 0);
    }

    /** Thêm 1 ảnh vào cuối. Ảnh đầu tiên của phụ tùng tự thành primary. */
    public function add($partId, $filename){
        $isPrimary = $this->countByPart($partId) === 0 ? 1 : 0;

        $this->addNew([
            'part_id'    => $partId,
            'image'      => $filename,
            'sort_order' => $this->nextSort($partId),
            'is_primary' => $isPrimary,
            'create_at'  => date('Y-m-d H:i:s'),
        ]);

        return $this->lastId();
    }

    private function nextSort($partId){
        $r = $this->table($this->_table)
                  ->select('MAX(sort_order) AS mx')
                  ->where('part_id', '=', $partId)
                  ->first();
        return (int) ($r['mx'] ?? 0) + 1;
    }

    /**
     * Xoá 1 ảnh. Nếu xoá đúng ảnh primary thì đôn ảnh còn lại (cũ nhất) lên primary.
     * @return string|null tên file để controller xoá vật lý
     */
    public function remove($id){
        $img = $this->getDetail($id);
        if (empty($img)) return null;

        $this->deleteById($id);

        if ((int) $img['is_primary'] === 1){
            $next = $this->table($this->_table)
                         ->where('part_id', '=', $img['part_id'])
                         ->orderBy('sort_order', 'ASC')
                         ->first();
            if (!empty($next)){
                $this->updateById(['is_primary' => 1], $next['id']);
            }
        }

        return $img['image'];
    }

    /** Đặt 1 ảnh làm đại diện, bỏ primary các ảnh khác cùng phụ tùng */
    public function setPrimary($id){
        $img = $this->getDetail($id);
        if (empty($img)) return false;

        // Bỏ primary toàn bộ ảnh của phụ tùng này rồi bật cho ảnh được chọn
        $this->update($this->_table, ['is_primary' => 0], '`part_id` = ?', [$img['part_id']]);
        $this->updateById(['is_primary' => 1], $id);

        return true;
    }
}
