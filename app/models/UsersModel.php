<?php
use App\core\Model;
use App\core\Hash;

class UsersModel extends Model{
    protected $_table = 'users'; //Gán tên bảng
    protected $_fields = '*'; //Các field cần lấy khi fetch và fetchAll
    protected $_primary = 'id'; //Trường khoá chính

    /**
     * Hash bcrypt hợp lệ nhưng không ứng với mật khẩu nào.
     * Dùng để khi email không tồn tại, ta vẫn tốn đúng lượng thời gian như khi
     * email có thật => kẻ tấn công không dò được email nào đã đăng ký
     * bằng cách đo thời gian phản hồi.
     */
    const DUMMY_HASH = '$2y$10$ndnIHJT9vOAj.lN5k0jMyOYqFZ0ABE7Nt3mfQNDmyRb5lqrdLjEQC';

    /** Tìm user theo email (không đụng tới mật khẩu) */
    public function findByEmail($email){
        return $this->table($this->_table)
            ->where('email', '=', $email)
            ->first();
    }

    /**
     * Kiểm tra đăng nhập.
     *
     * @param string $email
     * @param string $plainPassword MẬT KHẨU THÔ (bản cũ nhận md5 đã băm sẵn)
     * @return array Thông tin user nếu đúng, mảng rỗng nếu sai
     *
     * Khác bản cũ:
     *   - Bản cũ so sánh hash NGAY TRONG SQL (`WHERE password = 'md5...'`).
     *     bcrypt có salt ngẫu nhiên nên không so sánh được bằng SQL —
     *     bắt buộc lấy user ra rồi verify trong PHP.
     *   - Đăng nhập đúng mà hash còn là md5 cũ thì nâng cấp luôn lên bcrypt.
     */
    public function checkLogin($email, $plainPassword){

        $dataUser = $this->findByEmail($email);

        if (empty($dataUser)){
            // Vẫn verify với hash giả để thời gian phản hồi không tiết lộ
            // email này có tồn tại hay không.
            Hash::check($plainPassword, self::DUMMY_HASH);
            return [];
        }

        if (!Hash::check($plainPassword, $dataUser['password'])){
            return [];
        }

        // Nâng cấp hash md5 cũ -> bcrypt, ngay lúc đăng nhập đúng.
        // Người dùng không cần đổi mật khẩu, dữ liệu tự sạch dần.
        if (Hash::needsRehash($dataUser['password'])){
            $this->updateById(['password' => Hash::make($plainPassword)], $dataUser['id']);
        }

        return $dataUser;
    }

    public function getDetail($userId){
        $dataUser = $this->table($this->_table)
                    ->where('id', '=', $userId)
                    ->first();
        return $dataUser;
    }

    public function getLists($filters=[], $likes=[]){
        //return $this->getList();

        // `groups` la tu khoa danh rieng cua MySQL 8 => moi cho nhac toi deu phai backtick.
        // leftJoinOn() lo phan ON; rieng select() van la chuoi raw nen phai backtick tay.
        $data = $this->table($this->_table)
                ->select('`users`.`id`, `groups`.`name` as group_name, `users`.`name`, `users`.`email`, `users`.`status`, `users`.`current_activity`')
                ->leftJoinOn('groups', 'users.group_id', 'groups.id');

        //Xử lý logic lọc
        if (!empty($filters)){
            foreach ($filters as $key => $value){
                $data = $data->where($key, '=', $value);
            }
        }

        if (!empty($likes)){
//            foreach ($likes as $key => $value){
//                $data = $data->whereOrLike($key, '%'.$value.'%');
//            }

            /*
             * $data = $data->where(function($query){
             *     $query->whereOrLike('users.name', '%hoàng an%');
             *     $query->whereOrLike('users.email', '%hoàng an%');
             * });
             *
             * */

            /*
             * anonymous function hoặc lamda function
             *
             * */
            $data = $data->where(function($query) use ($likes){
                /*
                 $query->whereOrLike('users.name', '%hoàng an%');
                 $query->whereOrLike('users.email', '%hoàng an%');
                 $query->whereOrLike('users.id', '%hoàng an%');
                */

                foreach ($likes as $key => $value){
                    $query->whereOrLike($key, '%'.$value.'%');
                }

                //$query->where('users.id', '=', 1);

//                $query->orWhere(function($subQuery){
//                    $subQuery->where('users.id', '=', 1);
//                    $subQuery->where('users.group_id', '=', 9);
//                });
            });
        }

        $data = $data->get();
        return $data;
    }

    public function add($data){
        return $this->addNew($data);
    }

    public function edit($data, $id){
        return $this->updateById($data, $id);
    }

    public function remove($id){
        return $this->deleteById($id);
    }

}