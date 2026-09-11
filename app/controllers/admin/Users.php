<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;
use App\core\Hash;

/**
 * Người dùng (tài khoản vào trang quản trị).
 *
 * HAI MỨC:
 *   Toàn quyền  (nhóm sửa được bảng phân quyền — xem GroupsModel::laToanQuyen)
 *               thấy và sửa mọi tài khoản, gán nhóm và gara nào cũng được.
 *   Giới hạn    (vd. Manager) chỉ thấy và sửa tài khoản CÙNG GARA thuộc nhóm
 *               mình gán được; tài khoản tạo ra luôn thuộc gara của mình.
 *
 * Chốt chặn nằm ở ĐÂY, không ở giao diện: ẩn ô chọn gara trên form không
 * ngăn được ai tự gửi POST garage_id=1&group_id=<Admin>.
 */
class Users extends Controller{

    private $__data = [];

    private $__userModel, $__request, $__response, $__groupModel, $__userId;

    private $__phamVi = null;

    function __construct(){
        $this->__userModel = $this->model('UsersModel');
        $this->__groupModel = $this->model('GroupsModel');
        $this->__request = new Request();
        $this->__response = new Response();

        $this->__data['content']['groupData'] = $this->__groupModel->getLists();

        $this->__userId = Session::get('dataUser');

    }

    public function index(){
        $this->__data['sub_content'] = 'admin/users/lists';

        $this->__data['page_title'] = 'Quản lý người dùng';
        $this->__data['content']['page_name'] = 'Danh sách người dùng';

        $pv = $this->phamVi();

        //xử lý lọc
        $fieldData = $this->__request->getFields();
        $dataFilters = [];

        $dataLike = [];

        if (isset($fieldData['status']) && $fieldData['status']!=='all'){
            $status = $fieldData['status'];

            $dataFilters['users.status'] = $status;
        }

        if (!empty($fieldData['group_id'])){
            $groupId = $fieldData['group_id'];

            $dataFilters['users.group_id'] = $groupId;
        }

        if (!empty($fieldData['keyword'])){
            $keyword = $fieldData['keyword'];

            $dataLike['users.name'] = $keyword;
            $dataLike['users.email'] = $keyword;
        }

        /* Lọc theo gara. Người bị giới hạn LUÔN bị khoá vào gara của mình —
           tham số trên URL bỏ qua, không thì sửa ?garage_id= là xem được gara khác. */
        $locGara = '';
        if ($pv['toan_quyen']){
            if (isset($fieldData['garage_id']) && $fieldData['garage_id'] === 'none'){
                $locGara = 'none';
                $dataFilters['users.garage_id'] = null;
            } elseif (!empty($fieldData['garage_id']) && (int) $fieldData['garage_id'] > 0){
                $locGara = (string) (int) $fieldData['garage_id'];
                $dataFilters['users.garage_id'] = (int) $fieldData['garage_id'];
            }
        } elseif ($pv['gara_id'] !== null){
            $dataFilters['users.garage_id'] = $pv['gara_id'];
        }

        $msgError = Session::flash('msgError');

        /* Giới hạn mà chưa gán gara: KHÔNG hiện gì. Đừng rơi sang lọc
           `garage_id IS NULL` — thế là cho họ quản lý mọi tài khoản chưa gán. */
        if (!$pv['toan_quyen'] && $pv['gara_id'] === null){
            $dataUsers = [];
            if (empty($msgError)) $msgError = $this->cauChuaCoGara();
        } else {
            $dataUsers = $this->__userModel->getLists($dataFilters, $dataLike);
        }

        $c = &$this->__data['content'];
        $c['dataUsers']       = $dataUsers;
        $c['toanQuyen']       = $pv['toan_quyen'];
        $c['garaCuaToi']      = $pv['gara'];
        $c['nhomIds']         = $pv['nhom_ids'];
        $c['tenNhomGiaoDuoc'] = !empty($pv['nhom'])
                                ? implode(', ', array_column($pv['nhom'], 'name'))
                                : '(chưa có nhóm nào)';
        $c['listGarage']      = $pv['toan_quyen'] ? $this->model('GaragesModel')->getLists() : [];
        $c['locGara']         = $locGara;

        //Lấy dữ liệu từ flash data
        $c['msg']      = Session::flash('msg');
        $c['msgError'] = $msgError;

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function add(){
        $pv = $this->phamVi();
        if (!$pv['toan_quyen'] && $pv['gara_id'] === null){
            $this->__response->redirect('admin/users'); return;
        }

        $this->__data['sub_content'] = 'admin/users/add';

        $this->__data['page_title'] = 'Thêm người dùng';
        $this->__data['content']['page_name'] = 'Thêm người dùng';

        $this->formData($pv);

        //Lấy dữ liệu từ flash data
        $this->__data['content']['msg'] = Session::flash('msg');
        $this->__data['content']['errors'] = Session::flash('errors');
        $this->__data['content']['old'] = Session::flash('old');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postAdd(){
        $pv = $this->phamVi();
        if (!$pv['toan_quyen'] && $pv['gara_id'] === null){
            $this->__response->redirect('admin/users'); return;
        }

        $this->__request->rules([
            'name' => 'required|min:4',
            'email' => 'required|email|unique:users:email',
            'password' => 'required|min:6',
            'confirm_password' => 'required|match:password',
            'group_id' => 'required'
        ]);

        $this->__request->message([
            'name.required' => 'Tên người dùng không được để trống',
            'name.min' => 'Tên người dùng không được nhỏ hơn 4 ký tự',
            'email.required' => 'Email không được để trống',
            'email.email' => 'Email không đúng định dạng',
            'email.unique' => 'Email bị trùng trong hệ thống',
            'password.required' => 'Mật khẩu không được để trống',
            'password.min' => 'Mật khẩu không được nhỏ hơn 6 ký tự',
            'confirm_password.required' => 'Xác nhận mật khẩu không được để trống',
            'confirm_password.match' => 'Xác nhận mật khẩu không khớp',
            'group_id.required' => 'Chưa chọn nhóm người dùng',
        ]);

        $errors = $this->loiForm($pv);

        if (empty($errors)){

            $passwordHash = Hash::make($this->__request->getFields()['password']);
            $dataInsert = [
                'name' => $this->__request->getFields()['name'],
                'email' => $this->__request->getFields()['email'],
                'password' => $passwordHash,
                'group_id' => $this->__request->getFields()['group_id'],
                'status' => $this->__request->getFields()['status'],
                'garage_id' => $this->garaDuocGhi($pv),
                'create_at' => date('Y-m-d H:i:s')
            ];
            $addStatus = $this->__userModel->add($dataInsert);
            if ($addStatus){
                Session::flash('msg', 'Thêm người dùng thành công');
                $this->__response->redirect('admin/users');
            }

        }else{

            Session::flash('errors', $errors);
            Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
            Session::flash('old', $this->__request->getFields());
            $this->__response->redirect();
        }

    }

    public function edit($id = 0){
        if (empty($id)){
            $this->__response->redirect('admin/users');
        }
        $userDetail = $this->__userModel->getDetail($id);
        if (empty($userDetail)){
            Session::flash('msg', 'Người dùng này không tồn tại');
            $this->__response->redirect('admin/users');
        }

        $pv = $this->phamVi();
        $this->chanNeuNgoaiPhamVi($pv, $userDetail);

        $this->__data['sub_content'] = 'admin/users/edit';

        $this->__data['page_title'] = 'Cập nhật người dùng';
        $this->__data['content']['page_name'] = 'Cập nhât người dùng';

        $this->formData($pv);

        //Lấy dữ liệu từ flash data
        $this->__data['content']['msg'] = Session::flash('msg');
        $this->__data['content']['errors'] = Session::flash('errors');

        $oldFlash = Session::flash('old');
        if (empty($oldFlash)){;
            $this->__data['content']['old'] = $userDetail;
        }else{
            $this->__data['content']['old'] = $oldFlash;
        }

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postEdit($id=0){

        /* Bản cũ không kiểm tra tài khoản có tồn tại không — bây giờ bắt buộc,
           vì phải biết nó thuộc gara nào, nhóm nào trước khi cho sửa. */
        $userDetail = !empty($id) ? $this->__userModel->getDetail($id) : null;
        if (empty($userDetail)){
            Session::flash('msg', 'Người dùng này không tồn tại');
            $this->__response->redirect('admin/users');
        }

        $pv = $this->phamVi();
        $this->chanNeuNgoaiPhamVi($pv, $userDetail);

        $rulesArr = [
            'name' => 'required|min:4',
            'email' => 'required|email|unique:users:email:id='.$id,
            //'password' => 'required|min:6',
            //'confirm_password' => 'required|match:password',
            'group_id' => 'required'
        ];

        $messageArr = [
            'name.required' => 'Tên người dùng không được để trống',
            'name.min' => 'Tên người dùng không được nhỏ hơn 4 ký tự',
            'email.required' => 'Email không được để trống',
            'email.email' => 'Email không đúng định dạng',
            'email.unique' => 'Email bị trùng trong hệ thống',
            // 'password.required' => 'Mật khẩu không được để trống',
            //  'password.min' => 'Mật khẩu không được nhỏ hơn 6 ký tự',
            //  'confirm_password.required' => 'Xác nhận mật khẩu không được để trống',
            //  'confirm_password.match' => 'Xác nhận mật khẩu không khớp',
            'group_id.required' => 'Chưa chọn nhóm người dùng',
        ];

        if (!empty($this->__request->getFields()['password'])){
            $rulesArr['password'] = 'min:6';
            $rulesArr['confirm_password'] = 'required|match:password';

            $messageArr['password.min'] = 'Mật khẩu không được nhỏ hơn 6 ký tự';
            $messageArr['confirm_password.required'] = 'Xác nhận mật khẩu không được để trống';
            $messageArr['confirm_password.match'] = 'Xác nhận mật khẩu không khớp';
        }

        $this->__request->rules($rulesArr);

        $this->__request->message($messageArr);

        $errors = $this->loiForm($pv);

        if (empty($errors)){

            $dataUpdate = [
                'name' => $this->__request->getFields()['name'],
                'email' => $this->__request->getFields()['email'],
               // 'password' => $passwordHash,
                'group_id' => $this->__request->getFields()['group_id'],
                'status' => $this->__request->getFields()['status'],
                'garage_id' => $this->garaDuocGhi($pv),
                'update_at' => date('Y-m-d H:i:s')
            ];

            if (!empty($this->__request->getFields()['password'])){
                $passwordHash = Hash::make($this->__request->getFields()['password']);
                $dataUpdate['password'] = $passwordHash;
            }

            $updateStatus = $this->__userModel->edit($dataUpdate, $id);
            if ($updateStatus){
                Session::flash('msg', 'Cập nhật người dùng thành công');
                $this->__response->redirect();
            }

        }else{

            Session::flash('errors', $errors);
            Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
            Session::flash('old', $this->__request->getFields());
            $this->__response->redirect();
        }
    }

    public function delete($id=0){

        if (empty($id)){
            $this->__response->redirect('admin/users');
        }
        if ($id==$this->__userId){
            Session::flash('msg', 'Người dùng đang đăng nhập. Bạn không thể xoá');
            $this->__response->redirect('admin/users');
        }
        $userDetail = $this->__userModel->getDetail($id);
        if (empty($userDetail)){
            Session::flash('msg', 'Người dùng này không tồn tại');
            $this->__response->redirect('admin/users');
        }

        // Manager hiện không có quyền `delete` ở đây — chốt này để phòng khi
        // có ai cấp thêm sau này.
        $this->chanNeuNgoaiPhamVi($this->phamVi(), $userDetail);

        $delete = $this->__userModel->remove($id);

        if ($delete){
            Session::flash('msg', 'Xoá người dùng thành công');
            $this->__response->redirect('admin/users');
        }
    }

    // ===== Phạm vi quản lý =====

    /**
     * Phạm vi quản lý người dùng của người đang đăng nhập.
     *
     * Gara lấy từ TÀI KHOẢN (CSDL), KHÔNG lấy từ ô đổi gara trên đầu trang:
     * Manager có quyền xem gara nên đổi được gara đang làm việc trong phiên —
     * dựa vào đó thì đổi sang gara khác là thêm được người cho gara khác.
     */
    private function phamVi(){
        if ($this->__phamVi !== null) return $this->__phamVi;

        $toi    = $this->__userModel->getDetail($this->__userId);
        $gid    = !empty($toi['group_id']) ? (int) $toi['group_id'] : 0;
        $toan   = $gid > 0 && $this->__groupModel->laToanQuyen($gid);
        $nhom   = $gid > 0 ? $this->__groupModel->nhomGiaoDuoc($gid) : [];
        $garaId = !empty($toi['garage_id']) ? (int) $toi['garage_id'] : null;
        $gara   = $garaId !== null ? $this->model('GaragesModel')->getDetail($garaId) : null;

        return $this->__phamVi = [
            'toan_quyen' => $toan,
            'gara_id'    => $garaId,
            'gara'       => !empty($gara) ? $gara : null,
            'nhom'       => $nhom,
            'nhom_ids'   => array_map('intval', array_column($nhom, 'id')),
        ];
    }

    /**
     * Tài khoản $u có nằm trong phạm vi được sửa không.
     *
     * Người bị giới hạn chỉ đụng được tài khoản CÙNG GARA, thuộc nhóm mình gán
     * được. Hệ quả có chủ đích: không sửa được Admin, Manager khác, và chính
     * tài khoản của mình (nhóm mình không phải tập con thực sự của mình) —
     * không thì Manager tự đổi nhóm mình thành Admin là xong.
     */
    private function trongPhamVi($pv, $u){
        if ($pv['toan_quyen']) return true;
        if ($pv['gara_id'] === null) return false;
        return (int) $u['garage_id'] === $pv['gara_id']
            && in_array((int) $u['group_id'], $pv['nhom_ids'], true);
    }

    private function chanNeuNgoaiPhamVi($pv, $u){
        if ($this->trongPhamVi($pv, $u)) return;
        Session::flash('msgError', $pv['gara_id'] === null && !$pv['toan_quyen']
            ? $this->cauChuaCoGara()
            : 'Bạn chỉ sửa được tài khoản nhân viên thuộc gara của mình.');
        $this->__response->redirect('admin/users');   // có exit
    }

    private function cauChuaCoGara(){
        return 'Tài khoản của bạn chưa được gán gara nên chưa quản lý được nhân viên nào. Nhờ Admin gán gara cho bạn.';
    }

    /** Lỗi của form: lỗi kiểm tra thông thường + nhóm có được phép gán không. */
    private function loiForm($pv){
        $errors = $this->__request->validate() ? [] : (array) $this->__request->error();

        $f   = $this->__request->getFields();
        $gid = isset($f['group_id']) ? (int) $f['group_id'] : 0;
        // $gid <= 0 thì rule `required` đã báo "Chưa chọn nhóm"
        if ($gid > 0 && !in_array($gid, $pv['nhom_ids'], true)){
            $errors['group_id'] = 'Bạn không được cấp nhóm này';
        }
        return $errors;
    }

    /** Gara ghi vào tài khoản: người bị giới hạn luôn là gara của họ, POST gửi gì cũng bỏ qua. */
    private function garaDuocGhi($pv){
        return $pv['toan_quyen'] ? $this->garaTuForm() : $pv['gara_id'];
    }

    /** Dữ liệu chung của form thêm / sửa */
    private function formData($pv){
        $c = &$this->__data['content'];
        // Chỉ những nhóm được phép gán — không hiện nhóm Admin cho Manager chọn
        $c['listGroup']  = $pv['nhom'];
        // Gara của nhân viên: quyết định lúc lập báo giá lấy danh mục nào
        $c['listGarage'] = $pv['toan_quyen'] ? $this->model('GaragesModel')->getActive() : [];
        $c['toanQuyen']  = $pv['toan_quyen'];
        $c['garaCuaToi'] = $pv['gara'];
    }

    /**
     * Gara chọn trên form, hoặc null nếu để trống.
     *
     * Để trống KHÔNG phải lỗi: nhân viên chưa gán gara thì gara_hien_tai()
     * rơi về gara tổng. Ép buộc ở đây sẽ chặn việc sửa mọi tài khoản cũ.
     */
    private function garaTuForm(){
        $f = $this->__request->getFields();
        return !empty($f['garage_id']) ? (int) $f['garage_id'] : null;
    }
}
