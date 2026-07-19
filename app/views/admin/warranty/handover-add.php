<?php
$o = function($field, $default = '') use ($old){ return isset($old[$field]) ? $old[$field] : $default; };
?>
<form action="{{_WEB_URL.'/admin/'.$routeBase.'/handover-store/'.$item['id']}}" method="post">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="type" value="{{$type}}"/>

    <div class="card card-outline card-info">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-file-signature mr-2"></i>{{$typeLabel}}</h3>
            <div class="card-tools"><span class="badge badge-info p-2">Phiếu {{$item['request_no']}}</span></div>
        </div>
        <div class="card-body">
            <div class="callout callout-info py-2 mb-3">
                <b>Khách:</b> {{$ctx['customer']!==''?$ctx['customer']:'—'}}
                {{$ctx['phone']!==''?' · '.$ctx['phone']:''}}
                &nbsp;|&nbsp; <b>Thiết bị:</b> {{$ctx['product']!==''?$ctx['product']:'—'}}
                {{!empty($item['serial_no'])?' · Serial: '.$item['serial_no']:''}}
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Ngày lập biên bản <span class="text-danger">*</span></label>
                    <input type="date" name="handover_date" class="form-control" value="{{$o('handover_date',$today)}}"/>
                    {!! !empty($errors['handover_date'])?'<small class="text-danger">'.e($errors['handover_date']).'</small>':false !!}
                </div>
                <div class="form-group col-md-4">
                    <label>Bên giao (họ tên)</label>
                    <input type="text" name="deliverer" class="form-control" value="{{$o('deliverer')}}" placeholder="Người bàn giao thiết bị"/>
                </div>
                <div class="form-group col-md-4">
                    <label>Bên nhận (họ tên)</label>
                    <input type="text" name="receiver" class="form-control" value="{{$o('receiver')}}" placeholder="Người nhận thiết bị"/>
                </div>
            </div>
            <div class="form-group">
                <label>Phụ kiện đi kèm</label>
                <textarea name="accessories" class="form-control" rows="2" placeholder="VD: 01 sạc, 01 túi đựng, 01 cáp...">{{$o('accessories')}}</textarea>
            </div>
            <div class="form-group">
                <label>Tình trạng thiết bị khi giao nhận</label>
                <textarea name="condition_note" class="form-control" rows="2" placeholder="VD: Máy trầy nhẹ góc phải, màn hình nguyên vẹn...">{{$o('condition_note')}}</textarea>
            </div>
            <div class="form-group mb-0">
                <label>Ghi chú</label>
                <input type="text" name="note" class="form-control" value="{{$o('note')}}"/>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu biên bản</button>
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$item['id']}}" class="btn btn-default">Huỷ</a>
        </div>
    </div>
</form>
