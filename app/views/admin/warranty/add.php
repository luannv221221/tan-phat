<form action="" method="post">
    <?php echo csrf_field(); ?>
    @if (!empty($msg))
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-1"></i> {{$msg}}</div>
    @endif

    <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-tools mr-2"></i>{{$page_name}}</h3></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Đối tượng (khách)</label>
                    <select name="partner_id" class="form-control">
                        <option value="">— Chọn / khách lẻ —</option>
                        @foreach ($partners as $pn)
                        <option value="{{$pn['id']}}" {{(!empty($old['partner_id']) && $old['partner_id']==$pn['id'])?'selected':''}}>{{$pn['code'].' - '.$pn['name']}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label>Tên khách (nếu lẻ)</label>
                    <input type="text" name="customer_name" class="form-control" value="{{!empty($old['customer_name'])?$old['customer_name']:''}}"/>
                    {!! !empty($errors['customer_name'])?'<small class="text-danger">'.e($errors['customer_name']).'</small>':false !!}
                </div>
                <div class="form-group col-md-4">
                    <label>Điện thoại</label>
                    <input type="text" name="phone" class="form-control" value="{{!empty($old['phone'])?$old['phone']:''}}"/>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Sản phẩm (nếu có trong danh mục)</label>
                    <select name="part_id" class="form-control">
                        <option value="">— Không chọn —</option>
                        @foreach ($parts as $p)
                        <option value="{{$p['id']}}" {{(!empty($old['part_id']) && $old['part_id']==$p['id'])?'selected':''}}>{{$p['code'].' - '.$p['name']}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label>Tên thiết bị (nhập tay)</label>
                    <input type="text" name="product_name" class="form-control" value="{{!empty($old['product_name'])?$old['product_name']:''}}"/>
                    {!! !empty($errors['product_name'])?'<small class="text-danger">'.e($errors['product_name']).'</small>':false !!}
                </div>
                <div class="form-group col-md-4">
                    <label>Số serial</label>
                    <input type="text" name="serial_no" class="form-control" value="{{!empty($old['serial_no'])?$old['serial_no']:''}}"/>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label>Ngày tiếp nhận <span class="text-danger">*</span></label>
                    <input type="date" name="received_date" class="form-control" value="{{!empty($old['received_date'])?$old['received_date']:$today}}"/>
                    {!! !empty($errors['received_date'])?'<small class="text-danger">'.e($errors['received_date']).'</small>':false !!}
                </div>
                <div class="form-group col-md-3">
                    <label>Ngày hẹn trả</label>
                    <input type="date" name="appointment_date" class="form-control" value="{{!empty($old['appointment_date'])?$old['appointment_date']:''}}"/>
                </div>
                <div class="form-group col-md-3">
                    <label>Kỹ thuật viên</label>
                    <input type="text" name="technician" class="form-control" value="{{!empty($old['technician'])?$old['technician']:''}}"/>
                </div>
                <div class="form-group col-md-3">
                    <label>Phí sửa (₫)</label>
                    <input type="text" name="fee" class="form-control text-right" value="{{!empty($old['fee'])?$old['fee']:'0'}}"/>
                </div>
            </div>
            <div class="form-group">
                <label>Mô tả lỗi / tình trạng</label>
                <textarea name="issue" class="form-control" rows="2">{{!empty($old['issue'])?$old['issue']:''}}</textarea>
            </div>
            <div class="form-group">
                <label>Chẩn đoán / xử lý</label>
                <textarea name="diagnosis" class="form-control" rows="2">{{!empty($old['diagnosis'])?$old['diagnosis']:''}}</textarea>
            </div>
            <div class="form-group mb-0">
                <label>Ghi chú</label>
                <input type="text" name="note" class="form-control" value="{{!empty($old['note'])?$old['note']:''}}"/>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lập phiếu</button>
            <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i> Quay lại</a>
        </div>
    </div>
</form>
