<?php

use App\core\Model;

/**
 * CSKH — Phiếu yêu cầu bảo hành / sửa chữa.
 * Luồng: received (tiếp nhận) -> processing (đang xử lý) -> done / cancelled.
 */
class WarrantyRequestsModel extends Model {

    protected $_table   = 'warranty_requests';
    protected $_fields  = '*';
    protected $_primary = 'id';

    public static $statuses = [
        'received'   => 'Tiếp nhận',
        'processing' => 'Đang xử lý',
        'done'       => 'Hoàn tất',
        'cancelled'  => 'Đã huỷ',
    ];

    public function getLists($status = '', $from = '', $to = '', $keyword = ''){
        $q = $this->table($this->_table)
            ->select('`warranty_requests`.*, `partners`.`name` AS partner_full')
            ->leftJoinOn('partners', 'warranty_requests.partner_id', 'partners.id');

        if ($status !== '' && isset(self::$statuses[$status])) $q = $q->where('warranty_requests.status', '=', $status);
        if ($from !== '') $q = $q->where('warranty_requests.received_date', '>=', $from);
        if ($to !== '')   $q = $q->where('warranty_requests.received_date', '<=', $to);
        if ($keyword !== ''){
            $q = $q->where(function($sub) use ($keyword){
                $like = '%' . $keyword . '%';
                $sub->whereLike('warranty_requests.request_no', $like);
                $sub->whereOrLike('warranty_requests.customer_name', $like);
                $sub->whereOrLike('warranty_requests.phone', $like);
                $sub->whereOrLike('warranty_requests.serial_no', $like);
            });
        }
        return $q->orderBy('warranty_requests.received_date', 'DESC')
                 ->orderBy('warranty_requests.id', 'DESC')->get();
    }

    /** Lịch bảo hành: phiếu CHƯA hoàn tất/huỷ, sắp theo ngày hẹn (gần nhất trước) */
    public function getSchedule(){
        return $this->table($this->_table)
            ->select('`warranty_requests`.*, `partners`.`name` AS partner_full')
            ->leftJoinOn('partners', 'warranty_requests.partner_id', 'partners.id')
            ->whereIn('warranty_requests.status', ['received', 'processing'])
            ->orderBy('warranty_requests.appointment_date', 'ASC')
            ->orderBy('warranty_requests.received_date', 'ASC')->get();
    }

    /** Đếm phiếu theo trạng thái trong kỳ — cho báo cáo CSKH */
    public function countByStatus($from = '', $to = ''){
        $q = $this->table($this->_table)->select('`status`, COUNT(*) AS total, SUM(`fee`) AS total_fee');
        if ($from !== '') $q = $q->where('received_date', '>=', $from);
        if ($to !== '')   $q = $q->where('received_date', '<=', $to);
        $rows = $q->groupBy('status')->get();
        $out = [];
        foreach ($rows ?: [] as $r){ $out[$r['status']] = ['total' => (int) $r['total'], 'fee' => (float) $r['total_fee']]; }
        return $out;
    }

    /** Phiếu ĐÃ hoàn tất (có ngày hoàn tất) — nguồn tính nhắc bảo trì */
    public function getCompleted(){
        return $this->table($this->_table)
            ->select('`warranty_requests`.*, `partners`.`name` AS partner_full, `partners`.`phone` AS partner_phone')
            ->leftJoinOn('partners', 'warranty_requests.partner_id', 'partners.id')
            ->where('warranty_requests.status', '=', 'done')
            ->whereNotNull('warranty_requests.completed_date')
            ->orderBy('warranty_requests.completed_date', 'DESC')->get();
    }

    public function setReminded($id, $date){
        return $this->updateById(['reminded_at' => $date, 'update_at' => date('Y-m-d H:i:s')], (int) $id);
    }

    public function getDetail($id){ return $this->getFirst($id); }

    public function nextNo(){
        $row = $this->table($this->_table)->select('`request_no`')->orderBy('id', 'DESC')->first();
        $n = 0;
        if (!empty($row) && preg_match('/(\d+)$/', $row['request_no'], $m)){ $n = (int) $m[1]; }
        return 'BH-' . str_pad($n + 1, 6, '0', STR_PAD_LEFT);
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
}
