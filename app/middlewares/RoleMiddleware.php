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

        $groupId        = !empty($groupData['group_id']) ? (int) $groupData['group_id'] : 0;
        $permissionData = $groupId > 0 ? (array) $permissionModel->getPermission($groupId) : [];

        /* KHÔNG CÓ NHÓM, HOẶC NHÓM KHÔNG CÓ DÒNG QUYỀN NÀO = KHÔNG CÓ QUYỀN.
           Vá 08/10/2026, cùng lúc mở màn Quản lý nhóm cho gara.

           Trước đó hai trường hợp này rơi ra khỏi MỌI nhánh kiểm tra bên dưới
           và hàm kết thúc mà không chặn gì — với một request thật thì không ai
           redirect, nên trang vẫn vẽ ra. Nghĩa là NHÓM RỖNG LÀ NHÓM MẠNH NHẤT:
           vào được mọi màn hình, kể cả Quản lý gara và Người dùng.

           Hai cửa vào có thật, không phải giả thiết:
             - Tạo một nhóm mới ở màn Quản lý nhóm rồi chưa tick quyền nào.
             - Xoá một nhóm: khoá ngoại `users.group_id` là ON DELETE SET NULL,
               nên mọi người trong nhóm đó mất nhóm.
           Mở màn Quản lý nhóm cho gara là mở cả hai cửa đó cho gara.

           CHỈ chặn khi URL khớp một module. Không khớp thì giữ nguyên nếp cũ
           (đi tiếp) — nhiều màn admin không phải module: Tổng quan, trang
           "không có quyền", thêm nhanh, tra địa giới. Chặn ở đây là khoá cả
           trang "không có quyền", tức vòng lặp chuyển trang vô tận. */
        if (!empty($currentModuleId) && empty($permissionData)){
            if (empty($this->path)){
                $response->redirect('admin/khong-co-quyen');
            }
            return false;
        }

        if ($groupId > 0){

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