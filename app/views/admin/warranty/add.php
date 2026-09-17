<?php $__laBaoTri = ($loai === 'bao_tri'); ?>
<form action="" method="post">
    <?php echo csrf_field(); ?>
    <?php /* Lập từ phiếu tiếp nhận: giữ mã phiếu để chứng từ gắn vào đúng
             lần vào xưởng, và nói rõ cho người lập biết. */ ?>
    @if (!empty($old['reception_id']))
    <input type="hidden" name="reception_id" value="{{$old['reception_id']}}"/>
    @endif
    @if (!empty($tuPhieu))
    <div class="alert alert-info"><i class="fas fa-clipboard-check mr-1"></i>
        Lập từ phiếu tiếp nhận <b>{{$tuPhieu['phieu']['reception_no']}}</b>
        — xe <b class="text-uppercase">{{$tuPhieu['xe']['bien_so']}}</b>.
        Chứng từ này sẽ gắn vào lần vào xưởng đó.
    </div>
    @endif
    @if (!empty($msg))
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-1"></i> {{$msg}}</div>
    @endif
    @if (!empty($tu))
    <div class="alert alert-info"><i class="fas fa-redo mr-1"></i> Lập tiếp từ phiếu <b>{{$tu['request_no']}}</b> — đã điền sẵn khách và xe của lần trước.</div>
    @endif

    <div class="card card-outline {{$__laBaoTri ? 'card-info' : 'card-primary'}}">
        <div class="card-header"><h3 class="card-title"><i class="fas {{$__laBaoTri ? 'fa-oil-can' : 'fa-tools'}} mr-2"></i>{{$page_name}}</h3></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Loại phiếu</label>
                    <select name="loai" class="form-control">
                        @foreach ($loais as $k => $ten)
                        <option value="{{$k}}" {{$loai===$k?'selected':''}}>{{$ten}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-8">
                    <?php /* Hai loại dễ lẫn nhất ở quầy: khách mang xe tới vì HỎNG
                             (bảo hành) hay vì TỚI HẠN (bảo trì). Chọn nhầm thì
                             màn Nhắc bảo trì tính lịch sai. */ ?>
                    <small class="form-text text-muted mt-md-4">
                        <b>Bảo hành</b>: hàng hoặc xe hỏng, còn trong hạn bảo hành.
                        <b>Bảo trì</b>: bảo dưỡng định kỳ (thay dầu, kiểm tra…) — hoàn tất phiếu thì màn Nhắc bảo trì tự tính lần kế tiếp.
                        Loại không đổi được sau khi lập.
                    </small>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Đối tượng (khách)</label>
                    <select name="partner_id" class="form-control js-search" data-placeholder="Gõ tên hoặc mã để tìm...">
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
                    <input type="tel" name="phone" class="form-control" value="{{!empty($old['phone'])?$old['phone']:''}}"/>
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
                    <small class="form-text text-muted">Serial của phụ tùng, khác biển số xe bên dưới.</small>
                </div>
            </div>

            <?php /* XE MANG PHỤ TÙNG ĐÓ — hoặc chính chiếc xe mang tới bảo dưỡng.
                     Bảo hành một cái đĩa phanh mà không biết nó lắp trên xe nào
                     thì gần như vô nghĩa, và khi khách quay lại, biển số mới là
                     thứ người ta đọc. Để trống được: bảo hành thiết bị cầm tay
                     thì không có xe nào. */ ?>
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Biển số xe</label>
                    <input type="text" name="bien_so" class="form-control text-uppercase"
                           placeholder="VD: 30A-123.45"
                           value="{{!empty($old['bien_so'])?$old['bien_so']:''}}"/>
                    <small class="form-text text-muted">Bảo dưỡng xe thì chỉ cần biển số, không phải chọn sản phẩm.</small>
                </div>
                <div class="form-group col-md-3">
                    <label>Số km</label>
                    <input type="text" name="so_km" class="form-control text-right"
                           placeholder="VD: 100.000"
                           value="{{!empty($old['so_km'])?$old['so_km']:''}}"/>
                    <small class="form-text text-muted">Nhắc bảo trì theo km tính từ số này.</small>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label>Ngày tiếp nhận <span class="text-danger">*</span></label>
                    <input type="date" name="received_date" class="form-control" value="{{!empty($old['received_date'])?$old['received_date']:$today}}"/>
                    {!! !empty($errors['received_date'])?'<small class="text-danger">'.e($errors['received_date']).'</small>':false !!}
                </div>
                <div class="form-group col-md-3">
                    <label>Ngày hẹn</label>
                    <input type="date" name="appointment_date" class="form-control" value="{{!empty($old['appointment_date'])?$old['appointment_date']:''}}"/>
                </div>
                <div class="form-group col-md-3">
                    <label>Kỹ thuật viên</label>
                    <input type="text" name="technician" class="form-control" value="{{!empty($old['technician'])?$old['technician']:''}}"/>
                </div>
                <div class="form-group col-md-3">
                    <label>Phí (₫)</label>
                    <input type="number" min="0" step="1" name="fee" class="form-control text-right" value="{{!empty($old['fee'])?$old['fee']:'0'}}"/>
                </div>
            </div>
            <div class="form-group">
                <label>Tình trạng / việc cần làm</label>
                <textarea name="issue" class="form-control" rows="2">{{!empty($old['issue'])?$old['issue']:''}}</textarea>
            </div>
            <div class="form-group">
                <label>Chẩn đoán / đã làm</label>
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
