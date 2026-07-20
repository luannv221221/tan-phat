<?php

use App\core\Model;

/**
 * Model xe — Vios, Morning... Thuộc 1 hãng, có thể gắn 1 kiểu dáng.
 */
class CarModelsModel extends Model {

    protected $_table   = 'car_models';
    protected $_fields  = '*';
    protected $_primary = 'id';

    /**
     * Danh sách model kèm tên hãng và kiểu dáng.
     *
     * @param array $filters vd: ['car_models.brand_id' => 5]
     * @param string $keyword tìm theo tên model
     */
    public function getLists($filters = [], $keyword = ''){

        // joinOn() bọc backtick tự động — bắt buộc dùng, xem CHUAN_CODE.md mục 2.
        $q = $this->table($this->_table)
                  ->select('`car_models`.*, `car_brands`.`name` AS brand_name, `car_body_types`.`name` AS body_type_name')
                  ->joinOn('car_brands', 'car_models.brand_id', 'car_brands.id')
                  ->leftJoinOn('car_body_types', 'car_models.body_type_id', 'car_body_types.id');

        foreach ($filters as $field => $value){
            $q = $q->where($field, '=', $value);
        }

        if ($keyword !== ''){
            $q = $q->whereLike('car_models.name', '%' . $keyword . '%');
        }

        return $q->orderBy('car_brands.name', 'ASC')
                 ->orderBy('car_models.sort_order', 'ASC')
                 ->orderBy('car_models.name', 'ASC')
                 ->get();
    }

    /** Model theo hãng — dùng cho dropdown phụ thuộc trên website */
    public function getByBrand($brandId, $onlyActive = true){
        $q = $this->table($this->_table)->where('brand_id', '=', $brandId);

        if ($onlyActive){
            $q = $q->where('status', '=', 1);
        }

        return $q->orderBy('sort_order', 'ASC')->orderBy('name', 'ASC')->get();
    }

    public function getDetail($id){
        return $this->getFirst($id);
    }

    /**
     * Tìm model theo slug TRONG 1 hãng.
     * UNIQUE KEY là (brand_id, slug) nên slug chỉ cần duy nhất trong cùng hãng —
     * "vios" của Toyota và "vios" của hãng khác không đụng nhau.
     */
    public function findBySlugInBrand($brandId, $slug){
        return $this->table($this->_table)
                    ->where('brand_id', '=', $brandId)
                    ->where('slug', '=', $slug)
                    ->first();
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
     * Xoá model.
     * `car_years` để ON DELETE CASCADE nên đời xe con sẽ tự xoá theo —
     * đó là chủ ý: đời xe không có nghĩa nếu tách khỏi model.
     */
    public function remove($id){
        return $this->deleteById($id);
    }

    public function countYears($modelId){
        $r = $this->table('car_years')
                  ->select('COUNT(*) AS total')
                  ->where('model_id', '=', $modelId)
                  ->first();

        return (int) ($r['total'] ?? 0);
    }
}
