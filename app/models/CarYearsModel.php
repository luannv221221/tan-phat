<?php

use App\core\Model;

/**
 * Đời xe — khoảng năm sản xuất của một model. VD: Vios 2018–2023.
 *
 * Gắn với model chứ không phải danh sách năm rời rạc: "2018" đứng một mình
 * không lọc được phụ tùng, phải là "Vios 2018" mới có nghĩa.
 */
class CarYearsModel extends Model {

    protected $_table   = 'car_years';
    protected $_fields  = '*';
    protected $_primary = 'id';

    /** Đời xe kèm tên model và hãng */
    public function getLists($filters = []){
        $q = $this->table($this->_table)
                  ->select('`car_years`.*, `car_models`.`name` AS model_name, `car_brands`.`name` AS brand_name')
                  ->joinOn('car_models', 'car_years.model_id', 'car_models.id')
                  ->joinOn('car_brands', 'car_models.brand_id', 'car_brands.id');

        foreach ($filters as $field => $value){
            $q = $q->where($field, '=', $value);
        }

        return $q->orderBy('car_brands.name', 'ASC')
                 ->orderBy('car_models.name', 'ASC')
                 ->orderBy('car_years.year_from', 'DESC')
                 ->get();
    }

    public function getByModel($modelId){
        return $this->table($this->_table)
                    ->where('model_id', '=', $modelId)
                    ->orderBy('year_from', 'DESC')
                    ->get();
    }

    /**
     * Tìm đời xe của một model chứa năm cho trước.
     * Dùng khi khách chọn "Vios đời 2020" để lọc phụ tùng.
     *
     * year_to = NULL nghĩa là đời còn đang sản xuất.
     */
    public function findByModelAndYear($modelId, $year){
        $year = (int) $year;

        return $this->table($this->_table)
                    ->where('model_id', '=', $modelId)
                    ->where('year_from', '<=', $year)
                    ->where(function($q) use ($year){
                        $q->whereNull('year_to');
                        $q->orWhere('year_to', '>=', $year);
                    })
                    ->first();
    }

    public function getDetail($id){
        return $this->getFirst($id);
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

    public function remove($id){
        return $this->deleteById($id);
    }
}
