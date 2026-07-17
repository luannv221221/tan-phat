<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;
use App\core\Hash;

class Users extends Controller{

    private $__data = [];

    private $__userModel, $__request, $__response, $__groupModel, $__userId;

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


        $dataUsers = $this->__userModel->getLists($dataFilters, $dataLike);

        $this->__data['content']['dataUsers'] = $dataUsers;

        //Lấy dữ liệu từ flash data
        $this->__data['content']['msg'] = Session::flash('msg');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function add(){
        $this->__data['sub_content'] = 'admin/users/add';

        $this->__data['page_title'] = 'Thêm người dùng';
        $this->__data['content']['page_name'] = 'Thêm người dùng';

        $this->__data['content']['listGroup'] = $this->__groupModel->getLists();


        //Lấy dữ liệu từ flash data
        $this->__data['content']['msg'] = Session::flash('msg');
        $this->__data['content']['errors'] = Session::flash('errors');
        $this->__data['content']['old'] = Session::flash('old');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postAdd(){

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


        if ( $this->__request->validate()){

            $passwordHash = Hash::make($this->__request->getFields()['password']);
            $dataInsert = [
                'name' => $this->__request->getFields()['name'],
                'email' => $this->__request->getFields()['email'],
                'password' => $passwordHash,
                'group_id' => $this->__request->getFields()['group_id'],
                'status' => $this->__request->getFields()['status'],
                'create_at' => date('Y-m-d H:i:s')
            ];
            $addStatus = $this->__userModel->add($dataInsert);
            if ($addStatus){
                Session::flash('msg', 'Thêm người dùng thành công');
                $this->__response->redirect('admin/users');
            }

        }else{

            $errors = $this->__request->error();
            Session::flash('errors', $errors);
            Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
            Session::flash('old', $this->__request->getFields());
            $this->__response->redirect();
        }

    }

    public function edit($id = 0){
        if (!empty($id)){
            $userDetail = $this->__userModel->getDetail($id);
            if (empty($userDetail)){
                Session::flash('msg', 'Người dùng này không tồn tại');
                $this->__response->redirect('admin/users');
            }

        }else{
            $this->__response->redirect('admin/users');
        }

        $this->__data['sub_content'] = 'admin/users/edit';

        $this->__data['page_title'] = 'Cập nhật người dùng';
        $this->__data['content']['page_name'] = 'Cập nhât người dùng';

        $this->__data['content']['listGroup'] = $this->__groupModel->getLists();

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


        if ( $this->__request->validate()){

            $dataUpdate = [
                'name' => $this->__request->getFields()['name'],
                'email' => $this->__request->getFields()['email'],
               // 'password' => $passwordHash,
                'group_id' => $this->__request->getFields()['group_id'],
                'status' => $this->__request->getFields()['status'],
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

            $errors = $this->__request->error();
            Session::flash('errors', $errors);
            Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
            Session::flash('old', $this->__request->getFields());
            $this->__response->redirect();
        }
    }

    public function delete($id=0){

        if (!empty($id)){
            if ($id!=$this->__userId){
                $userDetail = $this->__userModel->getDetail($id);
                if (empty($userDetail)){
                    Session::flash('msg', 'Người dùng này không tồn tại');
                    $this->__response->redirect('admin/users');
                }
            }else{
                Session::flash('msg', 'Người dùng đang đăng nhập. Bạn không thể xoá');
                $this->__response->redirect('admin/users');
            }

        }else{
            $this->__response->redirect('admin/users');
        }

        $delete = $this->__userModel->remove($id);

        if ($delete){
            Session::flash('msg', 'Xoá người dùng thành công');
            $this->__response->redirect('admin/users');
        }
    }
}