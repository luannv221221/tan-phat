<?php

use App\core\Model;

/**
 * KHO-3 — Vị trí lưu trữ trong kho (cây tối đa 5 cấp trong 1 kho).
 *
 * full_path tự dựng từ tên các cấp cha; level = cấp (1..5).
 * QueryBuilder không self-join được nên cây dựng bằng PHP.
 */
class WarehouseLocationsModel extends Model {

    protected $_table   = 'warehouse_locations';
    protected $_fields  = '*';
    protected $_primary = 'id';

    const MAX_LEVEL = 5;

    public function getDetail($id){ return $this->getFirst($id); }

    /** Toàn bộ vị trí (kèm tên kho) — cho danh sách/cây admin */
    public function getLists($warehouseId = 0){
        $q = $this->table($this->_table)
            ->select('`warehouse_locations`.*, `warehouses`.`code` AS warehouse_code, `warehouses`.`name` AS warehouse_name')
            ->joinOn('warehouses', 'warehouse_locations.warehouse_id', 'warehouses.id');
        if ($warehouseId > 0) $q = $q->where('warehouse_locations.warehouse_id', '=', (int) $warehouseId);
        return $q->orderBy('warehouse_locations.warehouse_id', 'ASC')
                 ->orderBy('warehouse_locations.full_path', 'ASC')->get();
    }

    /** Vị trí trong 1 kho, sắp theo cây (full_path) — cho dropdown chọn cha */
    public function getByWarehouse($warehouseId){
        return $this->table($this->_table)
            ->where('warehouse_id', '=', (int) $warehouseId)
            ->orderBy('full_path', 'ASC')->get();
    }

    /** Mọi vị trí đang bật (kèm mã kho) — cho datalist gợi ý trên phiếu nhập */
    public function getActivePaths(){
        return $this->table($this->_table)
            ->select('`warehouse_locations`.`full_path`, `warehouse_locations`.`warehouse_id`, `warehouses`.`code` AS warehouse_code')
            ->joinOn('warehouses', 'warehouse_locations.warehouse_id', 'warehouses.id')
            ->where('warehouse_locations.status', '=', 1)
            ->orderBy('warehouse_locations.warehouse_id', 'ASC')
            ->orderBy('warehouse_locations.full_path', 'ASC')->get();
    }

    /** Vị trí đang bật (id, kho, full_path, code) — cho select trên phiếu nhập */
    public function getActiveList(){
        return $this->table($this->_table)
            ->select('`id`, `warehouse_id`, `code`, `full_path`, `level`')
            ->where('status', '=', 1)
            ->orderBy('warehouse_id', 'ASC')
            ->orderBy('full_path', 'ASC')->get();
    }

    /** Có vị trí đang bật nào trong kho này không? (để biết có bắt buộc chọn) */
    public function countActiveInWarehouse($warehouseId){
        $r = $this->table($this->_table)->select('COUNT(*) AS c')
                  ->where('warehouse_id', '=', (int) $warehouseId)
                  ->where('status', '=', 1)->first();
        return (int) ($r['c'] ?? 0);
    }

    /** Tính level + full_path từ vị trí cha (null = gốc) */
    public function resolvePath($name, $parentId){
        $name = trim($name);
        if (empty($parentId)){
            return ['level' => 1, 'full_path' => $name, 'parent_id' => null];
        }
        $parent = $this->getDetail((int) $parentId);
        if (empty($parent)){
            return ['level' => 1, 'full_path' => $name, 'parent_id' => null];
        }
        $level = (int) $parent['level'] + 1;
        if ($level > self::MAX_LEVEL) $level = self::MAX_LEVEL;
        return [
            'level'     => $level,
            'full_path' => trim($parent['full_path']) . ' / ' . $name,
            'parent_id' => (int) $parent['id'],
        ];
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

    public function remove($id){ return $this->deleteById($id); }

    /** Con trực tiếp (để chặn xoá khi còn nhánh con — FK CASCADE vẫn xoá, nhưng cảnh báo) */
    public function childCount($id){
        $r = $this->table($this->_table)->select('COUNT(*) AS c')
                  ->where('parent_id', '=', (int) $id)->first();
        return (int) ($r['c'] ?? 0);
    }

    /** Cập nhật full_path của các nhánh con khi đổi tên node cha */
    public function reindexChildren($parentId){
        $parent = $this->getDetail((int) $parentId);
        if (empty($parent)) return;
        $children = $this->table($this->_table)->where('parent_id', '=', (int) $parentId)->get();
        foreach ($children ?: [] as $c){
            $level = (int) $parent['level'] + 1;
            if ($level > self::MAX_LEVEL) $level = self::MAX_LEVEL;
            $path  = trim($parent['full_path']) . ' / ' . $c['name'];
            $this->update($this->_table, ['level' => $level, 'full_path' => $path, 'update_at' => date('Y-m-d H:i:s')], '`id` = ?', [(int) $c['id']]);
            $this->reindexChildren((int) $c['id']);
        }
    }
}
