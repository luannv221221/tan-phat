<?php
$v = function($k, $mac = '') use ($old, $item){
    if (isset($old[$k])) return $old[$k];
    return isset($item[$k]) && $item[$k] !== null ? $item[$k] : $mac;
};
$badge = ['tiep_nhan' => 'secondary', 'dang_sua' => 'warning', 'hoan_tat' => 'info', 'da_giao' => 'success', 'huy' => 'danger'];
$tien  = function($x){ return $x === null || $x === '' ? '—' : number_format((float) $x, 0, ',', '.'); };
$soCT  = count($chungTu['quotations']) + count($chungTu['sales_invoices']) + count($chungTu['warranty']);
?>
@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}</div>
@endif

<div class="card card-outline card-info">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-clipboard-check mr-2"></i>Phiếu <code>{{$item['reception_no']}}</code>
            <span class="text-muted">— xe <span class="text-uppercase">{{$item['bien_so']}}</span> {{$tenXe !== '' ? '· '.$tenXe : ''}}</span>
        </h3>
        <div class="card-tools"><span class="badge badge-{{$badge[$item['status']] ?? 'secondary'}} p-2">{{$statuses[$item['status']] ?? $item['status']}}</span></div>
    </div>
    <div class="card-body py-2">
        <span class="mr-2 small text-muted">Chuyển trạng thái:</span>
        @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
        @foreach ($statuses as $k => $ten)
        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/set-status/'.$item['id'].'?status='.$k}}"
           class="btn btn-sm {{$item['status']===$k ? 'btn-'.($badge[$k] ?? 'secondary') : 'btn-outline-'.($badge[$k] ?? 'secondary')}}">{{$ten}}</a>
        @endforeach
        @endif
        <div class="text-muted small mt-2">
            Chủ xe:
            @if (!empty($item['chu_ten']))
                <b>{{$item['chu_ten']}}</b> {{!empty($item['chu_sdt']) ? '· '.$item['chu_sdt'] : ''}}
            @else
                <span class="text-danger">chưa gán chủ</span>
            @endif
            · Km vào / ra: <b>{{$tien($item['km_vao'])}}</b> / <b>{{$tien($item['km_ra'])}}</b>
            @if (route('admin/vehicles/edit/'.$item['vehicle_id']))
            · <a href="{{_WEB_URL.'/admin/vehicles/edit/'.$item['vehicle_id']}}">Xem lịch sử xe</a>
            @endif
        </div>
    </div>
</div>

<?php /* CHỨNG TỪ CỦA LẦN VÀO XƯỞNG NÀY — đây là chỗ trả lời "hôm ấy xe vào
         làm những gì, hết bao nhiêu". Lập từ đây thì chứng từ tự gắn vào phiếu
         và tự lấy xe, biển số, số km — không gõ lại. */ ?>
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-file-invoice mr-2"></i>Chứng từ của lần vào xưởng này</h3>
        <div class="card-tools">
            @if (route('admin/quotations/add'))
            <a href="{{_WEB_URL.'/admin/quotations/add?reception_id='.$item['id']}}" class="btn btn-sm btn-outline-primary"><i class="fas fa-file-alt mr-1"></i> Lập báo giá</a>
            @endif
            @if (route('admin/sales-invoices/add'))
            <a href="{{_WEB_URL.'/admin/sales-invoices/add?reception_id='.$item['id']}}" class="btn btn-sm btn-outline-success"><i class="fas fa-file-invoice-dollar mr-1"></i> Lập hoá đơn</a>
            @endif
            @if (route('admin/warranty/add'))
            <a href="{{_WEB_URL.'/admin/warranty/add?loai=bao_hanh&reception_id='.$item['id']}}" class="btn btn-sm btn-outline-info"><i class="fas fa-tools mr-1"></i> Phiếu bảo hành</a>
            <a href="{{_WEB_URL.'/admin/warranty/add?loai=bao_tri&reception_id='.$item['id']}}" class="btn btn-sm btn-outline-info"><i class="fas fa-oil-can mr-1"></i> Phiếu bảo trì</a>
            @endif
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <h6 class="text-muted">Báo giá</h6>
                @if (!empty($chungTu['quotations']))
                <ul class="list-unstyled mb-0">
                    @foreach ($chungTu['quotations'] as $x)
                    <li><a href="{{_WEB_URL.'/admin/quotations/edit/'.$x['id']}}"><code>{{$x['so']}}</code></a>
                        <span class="text-muted small">{{$x['ngay']}} · {{$tien($x['tien'])}} ₫</span></li>
                    @endforeach
                </ul>
                @else
                <p class="text-muted small mb-0">Chưa có</p>
                @endif
            </div>
            <div class="col-md-4">
                <h6 class="text-muted">Hoá đơn bán</h6>
                @if (!empty($chungTu['sales_invoices']))
                <ul class="list-unstyled mb-0">
                    @foreach ($chungTu['sales_invoices'] as $x)
                    <li><a href="{{_WEB_URL.'/admin/sales-invoices/edit/'.$x['id']}}"><code>{{$x['so']}}</code></a>
                        <span class="text-muted small">{{$x['ngay']}} · {{$tien($x['tien'])}} ₫</span></li>
                    @endforeach
                </ul>
                @else
                <p class="text-muted small mb-0">Chưa có</p>
                @endif
            </div>
            <div class="col-md-4">
                <h6 class="text-muted">Bảo hành / bảo trì</h6>
                @if (!empty($chungTu['warranty']))
                <ul class="list-unstyled mb-0">
                    @foreach ($chungTu['warranty'] as $x)
                    <li><a href="{{_WEB_URL.'/admin/warranty/edit/'.$x['id']}}"><code>{{$x['so']}}</code></a>
                        <span class="text-muted small">{{$x['ngay']}} · {{$x['loai']==='bao_tri' ? 'Bảo trì' : 'Bảo hành'}}</span></li>
                    @endforeach
                </ul>
                @else
                <p class="text-muted small mb-0">Chưa có</p>
                @endif
            </div>
        </div>
    </div>
</div>

<form action="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$item['id']}}" method="post">
    <?php echo csrf_field(); ?>
    <div class="card">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-edit mr-2"></i>Nội dung phiếu</h3></div>
        <div class="card-body">
            <?php /* Xe KHÔNG đổi được: chứng từ đã gắn vào phiếu, đổi xe là trộn
                     lịch sử hai xe. Lập nhầm thì chuyển trạng thái Đã huỷ. */ ?>
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label>Ngày vào <span class="text-danger">*</span></label>
                    <input type="date" name="ngay_vao" class="form-control" value="{{$v('ngay_vao')}}"/>
                    {!! !empty($errors['ngay_vao'])?'<small class="text-danger">'.e($errors['ngay_vao']).'</small>':false !!}
                </div>
                <div class="form-group col-md-3">
                    <label>Ngày ra</label>
                    <input type="date" name="ngay_ra" class="form-control" value="{{$v('ngay_ra')}}"/>
                    {!! !empty($errors['ngay_ra'])?'<small class="text-danger">'.e($errors['ngay_ra']).'</small>':false !!}
                </div>
                <div class="form-group col-md-3">
                    <label>Km vào</label>
                    <input type="text" name="km_vao" class="form-control text-right" value="{{$v('km_vao')}}"/>
                    {!! !empty($errors['km_vao'])?'<small class="text-danger">'.e($errors['km_vao']).'</small>':false !!}
                </div>
                <div class="form-group col-md-3">
                    <label>Km ra</label>
                    <input type="text" name="km_ra" class="form-control text-right" value="{{$v('km_ra')}}"/>
                    {!! !empty($errors['km_ra'])?'<small class="text-danger">'.e($errors['km_ra']).'</small>':false !!}
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Cố vấn dịch vụ</label>
                    <select name="co_van_id" class="form-control">
                        <option value="">— Chọn nhân viên —</option>
                        @if (!empty($coVanDs))
                        @foreach ($coVanDs as $u)
                        <option value="{{$u['id']}}" {{(int)$v('co_van_id')===(int)$u['id']?'selected':''}}>{{$u['name']}}</option>
                        @endforeach
                        @endif
                    </select>
                    {!! !empty($errors['co_van_id'])?'<small class="text-danger">'.e($errors['co_van_id']).'</small>':false !!}
                </div>
                <div class="form-group col-md-4">
                    <label>Hoặc gõ tên cố vấn</label>
                    <input type="text" name="co_van" class="form-control" value="{{$v('co_van')}}"/>
                </div>
                <div class="form-group col-md-4">
                    <label>Trạng thái</label>
                    <select name="status" class="form-control">
                        @foreach ($statuses as $k => $ten)
                        <option value="{{$k}}" {{$v('status')===$k?'selected':''}}>{{$ten}}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Nội dung khách yêu cầu</label>
                <textarea name="yeu_cau_khach" class="form-control" rows="2">{{$v('yeu_cau_khach')}}</textarea>
            </div>
            <div class="form-group">
                <label>Tình trạng xe khi vào</label>
                <textarea name="tinh_trang_xe" class="form-control" rows="2">{{$v('tinh_trang_xe')}}</textarea>
            </div>
            <div class="form-group mb-0">
                <label>Ghi chú</label>
                <input type="text" name="note" class="form-control" value="{{$v('note')}}"/>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu</button>
            @if (route('admin/'.$routeBase.'/delete/'.$item['id']) && $soCT === 0)
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/delete/'.$item['id']}}" onclick="return confirm('Xoá phiếu tiếp nhận này?')" class="btn btn-outline-danger"><i class="fas fa-trash mr-1"></i> Xoá</a>
            @endif
            @if ($soCT > 0)
            <span class="text-muted small ml-2">Phiếu đã có {{$soCT}} chứng từ nên không xoá được — lập nhầm thì chuyển trạng thái "Đã huỷ".</span>
            @endif
            <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default float-right">Về danh sách</a>
        </div>
    </div>
</form>
