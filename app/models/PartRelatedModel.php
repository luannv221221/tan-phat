<?php

use App\core\Model;

/**
 * TASK_81 — Liên kết "phụ kiện đi kèm" giữa các phụ tùng.
 *
 * Có hướng: part_id -> related_part_id. syncForPart() thay toàn bộ danh sách
 * đi kèm của 1 phụ tùng, bọc transaction (như PartFitmentsModel).
 */
class PartRelatedModel extends Model {

    protected $_table   = 'part_related';
    protected $_fields  = '*';
    protected $_primary = 'id';

    /** Danh sách id phụ tùng đi kèm của 1 phụ tùng (để tick sẵn khi sửa) */
    public function getRelatedIds($partId){
        $rows = $this->table($this->_table)
                     ->select('`related_part_id`')
                     ->where('part_id', '=', $partId)
                     ->orderBy('sort_order', 'ASC')
                     ->get();

        return array_map(function($r){ return (int) $r['related_part_id']; }, $rows ?: []);
    }

    /** Phụ tùng đi kèm kèm mã + tên (để hiển thị chip đã chọn) */
    public function getRelatedParts($partId){
        return $this->table($this->_table)
            ->select('`part_related`.`related_part_id` AS id, `parts`.`code`, `parts`.`name`')
            ->joinOn('parts', 'part_related.related_part_id', 'parts.id')
            ->where('part_related.part_id', '=', $partId)
            ->orderBy('part_related.sort_order', 'ASC')
            ->orderBy('parts.name', 'ASC')
            ->get();
    }

    /**
     * Thay toàn bộ danh sách đi kèm của 1 phụ tùng.
     * Tự loại chính nó và các id trùng.
     *
     * @param array $relatedIds id các phụ tùng đi kèm MỚI
     */
    public function syncForPart($partId, array $relatedIds){
        $partId = (int) $partId;

        // Loại chính nó + trùng
        $clean = [];
        foreach ($relatedIds as $rid){
            $rid = (int) $rid;
            if ($rid > 0 && $rid !== $partId){
                $clean[$rid] = true;
            }
        }
        $clean = array_keys($clean);

        return $this->transaction(function($db) use ($partId, $clean){
            $db->delete('part_related', '`part_id` = ?', [$partId]);

            $now   = date('Y-m-d H:i:s');
            $order = 0;
            foreach ($clean as $rid){
                $db->insert('part_related', [
                    'part_id'         => $partId,
                    'related_part_id' => $rid,
                    'sort_order'      => $order++,
                    'create_at'       => $now,
                ]);
            }

            return count($clean);
        });
    }
}
