<div class="container py-3">
    <h3>{{$page_name}}</h3>
    <hr>
    <form action="" method="post">
        <?php echo csrf_field(); ?>
        @if (!empty($msg))
        <div class="alert alert-danger text-center">{{$msg}}</div>
        @endif

        <div class="form-group">
            <label for="">Tên nhóm</label>
            <input type="text" class="form-control" name="name" placeholder="Tên nhóm..." value="{{!empty($old['name'])?$old['name']:''}}"/>
            {!! !empty($errors['name'])?'<span style="color:red">'.$errors['name'].'</span>':false !!}
        </div>
        <button type="submit" class="btn btn-primary">Cập nhật</button>
    </form>
</div>