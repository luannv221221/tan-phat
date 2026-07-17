<div class="container py-3">
    <h3>{{$page_name}}</h3>
    <hr>
    <form action="" method="post">
        <?php echo csrf_field(); ?>
        @if (!empty($msg))
        <div class="alert alert-danger text-center">{{$msg}}</div>
        @endif

        <div class="form-group">
            <label for="">Tên</label>
            <input type="text" class="form-control" name="name" placeholder="Tên..." value="{{!empty($old['name'])?$old['name']:''}}"/>
            {!! !empty($errors['name'])?'<span style="color:red">'.$errors['name'].'</span>':false !!}
        </div>

        <div class="form-group">
            <label for="">Email</label>
            <input type="text" class="form-control" name="email" placeholder="Email..." value="{{!empty($old['email'])?$old['email']:''}}"/>
            {!! !empty($errors['email'])?'<span style="color:red">'.$errors['email'].'</span>':false !!}
        </div>

        <div class="form-group">
            <label for="">Mật khẩu</label>
            <input type="password" class="form-control" name="password" placeholder="Mật khẩu..." value=""/>
            {!! !empty($errors['password'])?'<span style="color:red">'.$errors['password'].'</span>':false !!}
        </div>

        <div class="form-group">
            <label for="">Nhập lại mật khẩu</label>
            <input type="password" class="form-control" name="confirm_password" placeholder="Nhập lại mật khẩu..." value=""/>
            {!! !empty($errors['confirm_password'])?'<span style="color:red">'.$errors['confirm_password'].'</span>':false !!}
        </div>

        <div class="form-group">
            <label for="">Trạng thái</label>
            <select name="status" class="form-control">
                <option value="0" {{!empty($old['status']) && $old['status']==0?'selected':false}}>Chưa kích hoạt</option>
                <option value="1" {{!empty($old['status']) && $old['status']==1?'selected':false}}>Kích hoạt</option>
            </select>
        </div>

        <div class="form-group">
            <label for="">Nhóm</label>
            <select name="group_id" class="form-control">
                <option value="0">Chọn nhóm</option>
                @if (!empty($listGroup))
                    @foreach ($listGroup as $item)
                        <option value="{{$item['id']}}" {{!empty($old['group_id']) && $old['group_id']==$item['id']?'selected':false}}>{{$item['name']}}</option>
                    @endforeach
                @endif
            </select>
            {!! !empty($errors['group_id'])?'<span style="color:red">'.$errors['group_id'].'</span>':false !!}
        </div>

        <button type="submit" class="btn btn-primary">Thêm mới</button>
    </form>
</div>