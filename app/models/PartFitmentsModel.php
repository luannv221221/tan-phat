<?php

use App\core\Model;

/**
 * Liên kết PHỤ TÙNG <-> ĐỜI XE — trái tim của TASK_86/TASK_87.
 *
 * Nối tới `car_years` chứ không phải `car_models`, vì cùng một model
 * nhưng đời khác nhau lắp phụ tùng khác nhau (Vios 2014-2017 vs Vios 2018+).
 */
class PartFitmentsModel extends Model {

    protected $_table   = 'part_fitments';
    protected $_fields  = '*';
    protected $_primary = 'id';

    /**
     * Gán 1 phụ tùng cho 1 đời xe.
     *
     * UNIQUE(part_id, car_year_id) chặn trùng ở tầng DB.
     * Ở đây kiểm tra trước để trả về false thay vì để exception bắn lên.
     *
     * @return int|false id vừa tạo, hoặc false nếu đã tồn tại
     */
    public function attach($partId, $carYearId, $note = null){

        if ($this->exists($partId, $carYearId)){
            return false;
        }

        $this->addNew([
            'part_id'     => $partId,
            'car_year_id' => $carYearId,
            'note'        => $note,
            'create_at'   => date('Y-m-d H:i:s'),
        ]);

        return $this->lastId();
    }

    /** Gỡ liên kết */
    public function detach($partId, $carYearId){
        return $this->delete(
            $this->_table,
            '`part_id` = ? AND `car_year_id` = ?',
            [$partId, $carYearId]
        );
    }

    public function exists($partId, $carYearId){
        $r = $this->table($this->_table)
                  ->where('part_id', '=', $partId)
                  ->where('car_year_id', '=', $carYearId)
                  ->first();

        return !empty($r);
    }

    /**
     * Gán 1 phụ tùng cho NHIỀU đời xe cùng lúc — dùng cho màn hình sửa phụ tùng.
     *
     * Bọc TRANSACTION: nếu gán 10 đời mà đứt ở đời thứ 7, không được để lại
     * 6 dòng nửa vời. Xem CHUAN_CODE.md mục 4.
     *
     * @param array $carYearIds danh sách id đời xe MỚI (thay thế toàn bộ cái cũ)
     */
    public function syncForPart($partId, array $carYearIds){

        return $this->transaction(function($db) use ($partId, $carYearIds){

            // Xoá hết liên kết cũ của phụ tùng này
            $db->delete('part_fitments', '`part_id` = ?', [$partId]);

            $now = date('Y-m-d H:i:s');
            $count = 0;

            foreach (array_unique($carYearIds) as $yearId){
                $db->insert('part_fitments', [
                    'part_id'     => $partId,
                    'car_year_id' => $yearId,
                    'create_at'   => $now,
                ]);
                $count++;
            }

            return $count;
        });
    }

    /**
     * Các đời xe mà 1 phụ tùng lắp được, kèm tên model + hãng.
     * Dùng ở màn hình chi tiết phụ tùng: "Lắp cho: Toyota Vios 2018-nay".
     */
    public function getCarYearsByPart($partId){
        return $this->table($this->_table)
            ->select('`part_fitments`.*, `car_years`.`year_from`, `car_years`.`year_to`, '
                   . '`car_years`.`name` AS year_name, '
                   . '`car_models`.`name` AS model_name, `car_brands`.`name` AS brand_name')
            ->joinOn('car_years', 'part_fitments.car_year_id', 'car_years.id')
            ->joinOn('car_models', 'car_years.model_id', 'car_models.id')
            ->joinOn('car_brands', 'car_models.brand_id', 'car_brands.id')
            ->where('part_fitments.part_id', '=', $partId)
            ->orderBy('car_brands.name', 'ASC')
            ->orderBy('car_models.name', 'ASC')
            ->orderBy('car_years.year_from', 'DESC')
            ->get();
    }

    /** Số phụ tùng lắp được cho 1 đời xe */
    public function countPartsByCarYear($carYearId){
        $r = $this->table($this->_table)
                  ->select('COUNT(*) AS total')
                  ->where('car_year_id', '=', $carYearId)
                  ->first();

        return (int) ($r['total'] ?? 0);
    }
}
