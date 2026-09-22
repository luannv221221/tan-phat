<?php

namespace App\app\middlewares;

use App\core\Middleware;

use App\core\Request;
use App\core\Session;

use App\core\Load;

use App\core\Response;

class RoleMiddleware extends Middleware{

    public function handle(){

        $response = new Response();

        $userId = Session::get('dataUser');

        //Truy vấn lấy group_id
        $groupModel = Load::model('GroupsModel');

        $permissionModel = Load::model('PermissionsModel');

        $moduleModel = Load::model('ModulesModel');

        $groupData = $groupModel->getGroupByUser($userId);

        $moduleLists = $moduleModel->getLists();
        $currentModuleId = 0;
        $currentLink = '';
        $currentModule = null;
        if (!empty($moduleLists)){
            foreach ($moduleLists as $item){
                if (Request::is('admin/'.$item['link'].'/*', $this->path)){
                    $currentModuleId = $item['id'];
                    $currentLink = $item['link'];
                    $currentModule = $item;
                    break;
                }
            }
        }

        /* Màn của riêng Tân Phát (website, kho tổng, đơn web...). Nhóm quyền
           dùng chung cho mọi gara, nên Manager của gara khác cũng "có quyền" —
           phải chặn theo GARA của tài khoản, TRƯỚC cả phần kiểm quyền nhóm, và
           kể cả với nhóm không có dòng quyền nào. Menu trái hỏi qua route()
           nên cũng tự ẩn các màn này. */
        if (!empty($currentModule['chi_tan_phat']) && !la_gara_tong()){
            if (empty($this->path)){
                $response->redirect('admin/khong-co-quyen');
            }
            return false;
        }

        if (!empty($groupData)){
            $groupId = $groupData['group_id'];

            $permissionData = $permissionModel->getPermission($groupId);



            if (!empty($currentModuleId) && !empty($permissionData)){


                $permissionDataArr = [];

                foreach ($permissionData as $item){
                    if ($item['module_id']==$currentModuleId){
                        $permissionDataArr[] = $item['role'];
                    }
                }


                //Check quyền view (Cho phép vào module)
                if ((!empty($permissionDataArr) && !in_array('view', $permissionDataArr)) || empty($permissionDataArr)){

                    if (empty($this->path)){
                        $response->redirect('admin/khong-co-quyen');
                    }else{
                        return false;
                    }

                }

                //Check các action: thêm, sửa, xoá
                if (Request::is('admin/'.$currentLink.'/add', $this->path)){

                    if ((!empty($permissionDataArr) && !in_array('add', $permissionDataArr)) || empty($permissionDataArr)){
                        if (empty($this->path)){
                            $response->redirect('admin/khong-co-quyen');
                        }else{
                            return false;
                        }

                    }

                }elseif (Request::is('admin/'.$currentLink.'/edit/*', $this->path)){

                    if ((!empty($permissionDataArr) && !in_array('edit', $permissionDataArr)) || empty($permissionDataArr)){
                        if (empty($this->path)){
                            $response->redirect('admin/khong-co-quyen');
                        }else{
                            return false;
                        }
                    }

                }elseif (Request::is('admin/'.$currentLink.'/delete/*', $this->path)){

                    if ((!empty($permissionDataArr) && !in_array('delete', $permissionDataArr)) || empty($permissionDataArr)){
                        if (empty($this->path)){
                            $response->redirect('admin/khong-co-quyen');
                        }else{
                            return false;
                        }
                    }

                }elseif (Request::is('admin/'.$currentLink.'/permission/*', $this->path)){
                    if ((!empty($permissionDataArr) && !in_array('permission', $permissionDataArr)) || empty($permissionDataArr)){
                        if (empty($this->path)){
                            $response->redirect('admin/khong-co-quyen');
                        }else{
                            return false;
                        }
                    }
                }

                return true;
            }

        }

    }
}