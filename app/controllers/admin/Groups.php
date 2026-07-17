<?php
use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

class Groups extends Controller{

    private $__data = [];
    private $__groupModel, $__request, $__response, $__permissionModel;

    function __construct(){
        $this->__groupModel = $this->model('GroupsModel');
        $this->__permissionModel = $this->model('PermissionsModel');
        $this->__request = new Request();
        $this->__response = new Response();
    }

    public function index(){
        $this->__data['sub_content'] = 'admin/groups/lists';

        $this->__data['page_title'] = 'Quản lý nhóm người dùng';
        $this->__data['content']['page_name'] = 'Danh sách nhóm';

        $dataGroups = $this->__groupModel->getLists();
        $this->__data['content']['dataGroups'] = $dataGroups;

        //Lấy dữ liệu từ flash data
        $this->__data['content']['msg'] = Session::flash('msg');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function add(){
        $this->__data['sub_content'] = 'admin/groups/add';

        $this->__data['page_title'] = 'Thêm nhóm người dùng';
        $this->__data['content']['page_name'] = 'Thêm nhóm người dùng';

        //Lấy dữ liệu từ flash data
        $this->__data['content']['msg'] = Session::flash('msg');
        $this->__data['content']['errors'] = Session::flash('errors');
        $this->__data['content']['old'] = Session::flash('old');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postAdd(){
        $this->__request->rules([
            'name' => 'required|min:4'
        ]);

        $this->__request->message([
            'name.required' => 'Tên nhóm không được để trống',
            'name.min' => 'Tên nhóm không được nhỏ hơn 4 ký tự',
        ]);

        if ( $this->__request->validate()){

            $dataInsert = [
                'name' => $this->__request->getFields()['name'],
                'create_at' => date('Y-m-d H:i:s')
            ];
            $addStatus = $this->__groupModel->add($dataInsert);
            if ($addStatus){
                Session::flash('msg', 'Thêm nhóm thành công');
                $this->__response->redirect('admin/groups');
            }

        }else{
            $errors = $this->__request->error();
            Session::flash('errors', $errors);
            Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
            Session::flash('old', $this->__request->getFields());
            $this->__response->redirect();
        }


    }

    public function edit($id=0){

        if (!empty($id)){
            $groupDetail = $this->__groupModel->getDetail($id);
            if (empty($groupDetail)){
                Session::flash('msg', 'Nhóm này không tồn tại');
                $this->__response->redirect('admin/groups');
            }

        }else{
            $this->__response->redirect('admin/groups');
        }

        $this->__data['sub_content'] = 'admin/groups/edit';

        $this->__data['page_title'] = 'Cập nhật nhóm người dùng';
        $this->__data['content']['page_name'] = 'Cập nhât nhóm người dùng';

        //Lấy dữ liệu từ flash data
        $this->__data['content']['msg'] = Session::flash('msg');
        $this->__data['content']['errors'] = Session::flash('errors');

        $oldFlash = Session::flash('old');
        if (empty($oldFlash)){;
            $this->__data['content']['old'] = $groupDetail;
        }else{
            $this->__data['content']['old'] = $oldFlash;
        }

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postEdit($id){

        $this->__request->rules([
            'name' => 'required|min:4'
        ]);

        $this->__request->message([
            'name.required' => 'Tên nhóm không được để trống',
            'name.min' => 'Tên nhóm không được nhỏ hơn 4 ký tự',
        ]);

        if ( $this->__request->validate()){

            $dataUpdate = [
                'name' => $this->__request->getFields()['name'],
                'update_at' => date('Y-m-d H:i:s')
            ];
            $updateStatus = $this->__groupModel->edit($dataUpdate, $id);
            if ($updateStatus){
                Session::flash('msg', 'Cập nhật nhóm thành công');
            }

        }else{

            $errors = $this->__request->error();
            Session::flash('errors', $errors);
            Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
            Session::flash('old', $this->__request->getFields());
        }

        $this->__response->redirect();

    }

    public function delete($id=0){
        if (!empty($id)){
            $groupDetail = $this->__groupModel->getDetail($id);
            if (empty($groupDetail)){
                Session::flash('msg', 'Nhóm này không tồn tại');
                $this->__response->redirect('admin/groups');
            }

        }else{
            $this->__response->redirect('admin/groups');
        }

        $delete = $this->__groupModel->remove($id);

        if ($delete){
            Session::flash('msg', 'Xoá nhóm thành công');
            $this->__response->redirect('admin/groups');
        }
    }

    public function permission($id=0){

        if (!empty($id)){
            $groupDetail = $this->__groupModel->getDetail($id);
            if (empty($groupDetail)){
                Session::flash('msg', 'Nhóm này không tồn tại');
                $this->__response->redirect('admin/groups');
            }

        }else{
            $this->__response->redirect('admin/groups');
        }

        $permissionData = $this->__permissionModel->getPermission($id);
        $this->__data['content']['permissionData'] = $permissionData;

        $this->__data['sub_content'] = 'admin/groups/permission';

        $this->__data['page_title'] = 'Phân quyền nhóm';
        $this->__data['content']['page_name'] = 'Phân quyền nhóm: '.$groupDetail['name'];

        //Lấy dữ liệu từ flash data
        $this->__data['content']['msg'] = Session::flash('msg');
        $this->__data['content']['errors'] = Session::flash('errors');
        $this->__data['content']['old'] = Session::flash('old');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postPermission($id){
        if (!empty($this->__request->getFields()['permission'])){
            $permissionData = $this->__request->getFields()['permission'];

            //xoá tất cả quyền theo group_id
            $this->__permissionModel->remove($id);

            if (!empty($permissionData)){
                foreach ($permissionData as $moduleId => $roleArr){
                    if (!empty($roleArr)){
                        foreach ($roleArr as $role){
                            //insert quyền vào bảng permission
                            $dataInsert = [
                                 'module_id' => $moduleId,
                                 'group_id' => $id,
                                 'role' => $role
                            ];

                            $this->__permissionModel->add($dataInsert);
                        }
                    }
                }
            }

            Session::flash('msg', 'Phân quyền thành công');
            $this->__response->redirect();
        }
    }
}