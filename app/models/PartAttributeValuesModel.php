<?php

use App\core\Model;

/**
 * TASK_90 — Giá trị thông số kỹ thuật theo từng phụ tùng (EAV).
 */
class PartAttributeValuesModel extends Model {

    protected $_table   = 'part_attribute_values';
    protected $_fields  = '*';
    protected $_primary = 'id';

    /** [attribute_id => value] của 1 phụ tùng — để đổ vào form */
    public function getValuesMap($partId){
        $rows = $this->table($this->_table)
                     ->select('`attribute_id`, `value`')
                     ->where('part_id', '=', $partId)
                     ->get();

        $map = [];
        foreach ($rows ?: [] as $r){
            $map[(int) $r['attribute_id']] = $r['value'];
        }
        return $map;
    }

    /** Thông số kèm tên + đơn vị (để hiển thị chi tiết phụ tùng) */
    public function getByPart($partId){
        return $this->table($this->_table)
            ->select('`part_attribute_values`.`value`, `attributes`.`name`, `attributes`.`unit`')
            ->joinOn('attributes', 'part_attribute_values.attribute_id', 'attributes.id')
            ->where('part_attribute_values.part_id', '=', $partId)
            ->orderBy('attributes.sort_order', 'ASC')
            ->get();
    }

    /**
     * Thay toàn bộ giá trị thông số của 1 phụ tùng.
     *
     * @param array $map [attribute_id => value]; value rỗng thì bỏ (không lưu).
     */
    public function syncForPart($partId, array $map){
        $partId = (int) $partId;

        return $this->transaction(function($db) use ($partId, $map){
            $db->delete('part_attribute_values', '`part_id` = ?', [$partId]);

            $now   = date('Y-m-d H:i:s');
            $count = 0;
            foreach ($map as $attrId => $value){
                $attrId = (int) $attrId;
                $value  = trim((string) $value);
                if ($attrId <= 0 || $value === '') continue;

                $db->insert('part_attribute_values', [
                    'part_id'      => $partId,
                    'attribute_id' => $attrId,
                    'value'        => $value,
                    'create_at'    => $now,
                ]);
                $count++;
            }
            return $count;
        });
    }
}
