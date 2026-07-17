<?php
//Lớp này chứa các hàm thao tác với CSDL
//1 số ứng dụng có tên là db_driver

namespace App\core;

use App\core\Connection;
use App\core\QueryBuilder;
use \PDO;

class Database extends Connection {

    use QueryBuilder; //Gọi trait QueryBuilder

    private $__last_query = null;    //Câu lệnh SQL cuối cùng (để log khi lỗi)
    private $__last_bindings = [];   //Giá trị bind cuối cùng

    /**
     * Thực thi câu lệnh SQL với bound parameters.
     *
     * @param string $sql      SQL có placeholder `?`
     * @param array  $bindings Giá trị bind, đúng thứ tự placeholder
     */
    public function query($sql, array $bindings = []){

        $this->__last_query    = $sql;
        $this->__last_bindings = $bindings;

        try{
            $statement = $this->_conn->prepare($sql);

            // Bind thật sự — đây là điểm khác cốt lõi so với bản cũ.
            // Bản cũ nối giá trị vào $sql rồi execute() không tham số,
            // nên prepare() chỉ là hình thức và không chặn được injection.
            $statement->execute($bindings);

            return $statement;

        }catch (\PDOException $exception){
            // Bản cũ viết `catch (Exception ...)` trong namespace App\core
            // => PHP hiểu là App\core\Exception (không tồn tại) => không bao giờ bắt được.
            $this->logError($exception);

            // NÉM LẠI, không die().
            // die() ở đây làm hỏng ba thứ:
            //   - transaction() không thể rollBack() (khối catch không bao giờ chạy tới)
            //   - Migration::hasTable() không thể dò bảng có tồn tại không
            //   - mọi caller mất khả năng tự xử lý lỗi
            // Trang lỗi thân thiện do bootstrap.php lo (set_exception_handler).
            if (defined('_DEBUG') && _DEBUG === true){
                throw new \RuntimeException(
                    'Loi truy van: ' . $exception->getMessage() . "\nSQL: " . $sql,
                    0,
                    $exception
                );
            }

            // Production: không để lộ SQL ra ngoài — chi tiết đã nằm trong log.
            throw new \RuntimeException(
                'Da co loi xay ra khi truy van du lieu.',
                0,
                $exception
            );
        }
    }

    /** Ghi lỗi ra file thay vì in stack trace ra trình duyệt */
    protected function logError(\PDOException $e){
        $logDir  = dirname(__DIR__).'/public/logs/errors';
        if (!is_dir($logDir)) @mkdir($logDir, 0775, true);

        $msg = sprintf(
            "[%s] %s | SQL: %s | Bindings: %s\n",
            date('Y-m-d H:i:s'),
            $e->getMessage(),
            $this->__last_query,
            json_encode($this->__last_bindings, JSON_UNESCAPED_UNICODE)
        );

        @file_put_contents($logDir.'/db-error.log', $msg, FILE_APPEND);
    }

    /** Lấy tất cả bản ghi */
    public function getRaw($sql, array $bindings = []){
        $query = $this->query($sql, $bindings);

        $data = array();
        if (!empty($query)){
            $data = $query->fetchAll(PDO::FETCH_ASSOC);
        }
        return $data;
    }

    /** Lấy bản ghi đầu tiên */
    public function firstRaw($sql, array $bindings = []){
        $query = $this->query($sql, $bindings);

        $row = array();
        if (!empty($query)){
            $row = $query->fetch(PDO::FETCH_ASSOC);
        }
        return $row;
    }

    // ================= Transaction =================
    // Bắt buộc cho nghiệp vụ ghi nhiều bảng (nhập kho, phiếu thu, điều chuyển kho...).
    // Không có transaction thì một request đứt giữa chừng sẽ để lại dữ liệu lệch sổ.

    public function beginTransaction(){
        return $this->_conn->beginTransaction();
    }

    public function commit(){
        return $this->_conn->commit();
    }

    public function rollBack(){
        if ($this->_conn->inTransaction()){
            return $this->_conn->rollBack();
        }
        return false;
    }

    public function inTransaction(){
        return $this->_conn->inTransaction();
    }

    /**
     * Bọc một khối lệnh trong transaction. Ném exception thì tự rollback.
     *
     *   $db->transaction(function() use ($db) {
     *       $db->insert('goods_receipts', [...]);
     *       $db->update('stock', [...], 'id = ?', [$id]);
     *   });
     */
    public function transaction(\Closure $callback){
        $this->beginTransaction();
        try{
            $result = $callback($this);
            $this->commit();
            return $result;
        }catch (\Throwable $e){
            $this->rollBack();
            throw $e;
        }
    }

    // ================= Ghi dữ liệu =================

    /**
     * Thêm bản ghi.
     * @param array $data ['cot' => 'gia tri', ...]
     */
    public function insert($table='', $data=array()){
        if (empty($table) || empty($data)) return null;

        $keys         = [];
        $placeholders = [];
        $bindings     = [];

        foreach ($data as $key => $value){
            $keys[]         = $this->wrapField($key);
            $placeholders[] = '?';
            $bindings[]     = $value;
        }

        $sql = 'INSERT INTO '.$this->wrapField($table)
             . ' ('.implode(',', $keys).')'
             . ' VALUES ('.implode(',', $placeholders).')';

        return $this->query($sql, $bindings);
    }

    /**
     * Sửa bản ghi.
     *
     * @param string $where         Mệnh đề WHERE dùng placeholder `?`, vd: 'id = ?'
     * @param array  $whereBindings Giá trị cho các `?` trong $where
     *
     * CẢNH BÁO: $where là chuỗi raw. Chỉ viết tên cột + `?` vào đây,
     * TUYỆT ĐỐI không nối giá trị người dùng vào chuỗi $where.
     */
    public function update($table, $data, $where='', array $whereBindings = []){
        if (empty($table) || empty($data)) return null;

        // Bản cũ dùng thuộc tính $__update_set không bao giờ reset
        // => gọi update() lần 2 sẽ kéo theo các cột của lần 1. Nay dùng biến cục bộ.
        $set      = [];
        $bindings = [];

        foreach ($data as $key => $value){
            $set[]      = $this->wrapField($key).' = ?';
            $bindings[] = $value;
        }

        $sql = 'UPDATE '.$this->wrapField($table).' SET '.implode(',', $set);

        if (!empty($where)){
            $sql     .= ' WHERE '.$where;
            $bindings = array_merge($bindings, $whereBindings);
        }

        return $this->query($sql, $bindings);
    }

    /**
     * Xoá bản ghi.
     *
     * @param string $where         Mệnh đề WHERE dùng placeholder `?`, vd: 'id = ?'
     * @param array  $whereBindings Giá trị cho các `?`
     */
    public function delete($table, $where, array $whereBindings = []){
        if (empty($table)) return null;

        if (empty($where)){
            // Chặn xoá sạch bảng do quên truyền $where
            throw new \InvalidArgumentException('delete() bat buoc phai co dieu kien WHERE');
        }

        $sql = 'DELETE FROM '.$this->wrapField($table).' WHERE '.$where;

        return $this->query($sql, $whereBindings);
    }

    /** Lấy id vừa insert */
    public function lastId(){
        return $this->_conn->lastInsertId();
    }
}
