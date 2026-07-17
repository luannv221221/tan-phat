<?php

use App\core\Model;

/**
 * Phụ tùng — TASK_86, TASK_87, TASK_93.
 */
class PartsModel extends Model {

    protected $_table   = 'parts';
    protected $_fields  = '*';
    protected $_primary = 'id';

    /** Các cột hay lấy kèm tên danh mục/thương hiệu */
    protected function selectWithJoins(){
        return $this->table($this->_table)
            ->select('`parts`.*, `part_categories`.`name` AS category_name, '
                   . '`product_brands`.`name` AS brand_name, '
                   . '`product_origins`.`name` AS origin_name, '
                   . '`product_units`.`name` AS unit_name')
            ->leftJoinOn('part_categories', 'parts.category_id', 'part_categories.id')
            ->leftJoinOn('product_brands', 'parts.brand_id', 'product_brands.id')
            ->leftJoinOn('product_origins', 'parts.origin_id', 'product_origins.id')
            ->leftJoinOn('product_units', 'parts.unit_id', 'product_units.id');
    }

    /**
     * Danh sách phụ tùng, lọc theo danh mục/thương hiệu + tìm theo từ khoá.
     * TASK_90 (lọc theo danh mục), TASK_91 (tìm kiếm).
     */
    public function getLists($filters = [], $keyword = ''){
        $q = $this->selectWithJoins();

        foreach ($filters as $field => $value){
            $q = $q->where($field, '=', $value);
        }

        if ($keyword !== ''){
            // Tìm theo tên HOẶC mã HOẶC mã OEM — bọc nhóm để không phá điều kiện lọc phía trên.
            $q = $q->where(function($sub) use ($keyword){
                $like = '%' . $keyword . '%';
                $sub->whereLike('parts.name', $like);
                $sub->whereOrLike('parts.code', $like);
                $sub->whereOrLike('parts.oem_code', $like);
            });
        }

        return $q->orderBy('parts.name', 'ASC')->get();
    }

    /**
     * ⭐ TASK_87 — "Chọn xe sẽ lọc ra các phụ tùng".
     *
     * Trả về phụ tùng lắp được cho một ĐỜI XE cụ thể.
     *
     * @param int   $carYearId id trong car_years
     * @param array $filters   lọc thêm, vd ['parts.category_id' => 3]
     */
    public function getByCarYear($carYearId, $filters = []){
        $q = $this->selectWithJoins()
                  ->joinOn('part_fitments', 'parts.id', 'part_fitments.part_id')
                  ->where('part_fitments.car_year_id', '=', $carYearId)
                  ->where('parts.status', '=', 1);

        foreach ($filters as $field => $value){
            $q = $q->where($field, '=', $value);
        }

        return $q->orderBy('parts.name', 'ASC')->get();
    }

    /**
     * ⭐ TASK_93 — Tìm phụ tùng theo model + năm.
     *
     * Khách chọn "Vios đời 2020" chứ không biết car_year_id là gì.
     * Hàm này tự tìm đời xe chứa năm đó rồi lấy phụ tùng.
     *
     * @return array Mảng rỗng nếu model/năm không có đời nào khớp
     */
    public function getByModelAndYear($modelId, $year, $filters = []){
        $year = (int) $year;

        // Tìm đời xe chứa năm này. year_to = NULL nghĩa là còn sản xuất.
        $carYear = $this->table('car_years')
            ->where('model_id', '=', $modelId)
            ->where('year_from', '<=', $year)
            ->where(function($q) use ($year){
                $q->whereNull('year_to');
                $q->orWhere('year_to', '>=', $year);
            })
            ->first();

        if (empty($carYear)) return [];

        return $this->getByCarYear($carYear['id'], $filters);
    }

    /** Phụ tùng lắp cho bất kỳ đời nào của một model */
    public function getByModel($modelId, $filters = []){
        $q = $this->selectWithJoins()
                  ->joinOn('part_fitments', 'parts.id', 'part_fitments.part_id')
                  ->joinOn('car_years', 'part_fitments.car_year_id', 'car_years.id')
                  ->where('car_years.model_id', '=', $modelId)
                  ->where('parts.status', '=', 1);

        foreach ($filters as $field => $value){
            $q = $q->where($field, '=', $value);
        }

        return $q->groupBy('parts.id')->orderBy('parts.name', 'ASC')->get();
    }

    public function getDetail($id){
        return $this->getFirst($id);
    }

    public function findByCode($code){
        return $this->table($this->_table)->where('code', '=', $code)->first();
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
        // part_fitments để ON DELETE CASCADE nên liên kết tự xoá theo.
        return $this->deleteById($id);
    }
}
