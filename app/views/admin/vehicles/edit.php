<?php
$v = function($k, $mac = '') use ($old, $item){
    if (isset($old[$k])) return $old[$k];
    return isset($item[$k]) && $item[$k] !== null ? $item[$k] : $mac;
};
$badgeTN = ['tiep_nhan' => 'secondary', 'dang_sua' => 'warning', 'hoan_tat' => 'info', 'da_giao' => 'success', 'huy' => 'danger'];
$tien = function($x){ return number_format((float) $x, 0, ',', '.'); };
$soPhieu = count((array) $phieuDs);
?>
@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}</div>
@endif

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-car mr-2"></i>
            <span class="text-uppercase">{{$item['bien_so']}}</span>
            <span class="text-muted">{{$tenXe !== '' ? ' — '.$tenXe : ''}}</span>
        </h3>
        <div class="card-tools">
            @if (route('admin/receptions/add'))
            <a href="{{_WEB_URL.'/admin/receptions/add?vehicle_id='.$item['id']}}" class="btn btn-info btn-sm"><i class="fas fa-clipboard-check mr-1"></i> Tiếp nhận xe này</a>
            @endif
        </div>
    </div>
    <div class="card-body py-2 text-muted small">
        Chủ xe:
        @if (!empty($item['chu_ten']))
            <b>{{$item['chu_ten']}}</b> {{!empty($item['chu_sdt']) ? '· '.$item['chu_sdt'] : ''}}
        @else
            <span class="text-danger">chưa gán chủ</span> — chọn ở ô Chủ xe bên dưới
        @endif
        · Số km: <b>{!! $item['so_km'] !== null ? $tien($item['so_km']) : '—' !!}</b>
        · Đã vào xưởng: <b>{{$soPhieu}}</b> lần
    </div>
</div>

<?php /* LỊCH SỬ XE — lý do chính của màn này: một xe nhiều lần vào xưởng.
         Lấy theo mã xe nên xe đổi biển số vẫn còn đủ lịch sử cũ. */ ?>
<div class="card card-outline card-info">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-clipboard-list mr-2"></i>Các lần vào xưởng</h3></div>
    <div class="card-body table-responsive p-0">
        <table class="table table-sm table-hover mb-0">
            <thead><tr>
                <th style="width:130px">Số phiếu</th>
                <th style="width:110px">Ngày vào</th>
                <th style="width:110px">Ngày ra</th>
                <th style="width:150px" class="text-right">Km vào / ra</th>
                <th>Yêu cầu của khách</th>
                <th style="width:130px">Cố vấn</th>
                <th style="width:110px" class="text-center">Trạng thái</th>
                <th style="width:60px" class="text-center">Mở</th>
            </tr></thead>
            <tbody>
            @if (!empty($phieuDs))
                @foreach ($phieuDs as $p)
                <tr>
                    <td><code>{{$p['reception_no']}}</code></td>
                    <td>{{$p['ngay_vao']}}</td>
                    <td>{{!empty($p['ngay_ra']) ? $p['ngay_ra'] : '—'}}</td>
                    <td class="text-right">{!! ($p['km_vao'] !== null ? $tien($p['km_vao']) : '—') . ' / ' . ($p['km_ra'] !== null ? $tien($p['km_ra']) : '—') !!}</td>
                    <td class="text-muted small">{{!empty($p['yeu_cau_khach']) ? mb_substr($p['yeu_cau_khach'], 0, 80) : '—'}}</td>
                    <td class="small">{{!empty($p['co_van_ten']) ? $p['co_van_ten'] : (!empty($p['co_van']) ? $p['co_van'] : '—')}}</td>
                    <td class="text-center"><span class="badge badge-{{$badgeTN[$p['status']] ?? 'secondary'}}">{{$statusTN[$p['status']] ?? $p['status']}}</span></td>
                    <td class="text-center">
                        @if (route('admin/receptions/edit/'.$p['id']))
                        <a href="{{_WEB_URL.'/admin/receptions/edit/'.$p['id']}}" class="btn btn-sm btn-outline-info"><i class="fas fa-folder-open"></i></a>
                        @endif
                    </td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="8" class="text-center text-muted py-3">Xe này chưa có lần vào xưởng nào. Bấm <b>Tiếp nhận xe này</b> khi khách mang xe tới.</td></tr>
            @endif
            </tbody>
        </table>
    </div>
</div>

<div class="card card-outline card-secondary">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-file-invoice mr-2"></i>Chứng từ của xe</h3></div>
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
        <div class="card-header"><h3 class="card-title"><i class="fas fa-edit mr-2"></i>Thông tin xe</h3></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Chủ xe <span class="text-muted small">(đối tượng khách)</span></label>
                    <select name="partner_id" class="form-control js-search" data-placeholder="Gõ tên hoặc mã để tìm...">
                        <option value="">— Chưa gán chủ / khách vãng lai —</option>
                        @if (!empty($partners))
                        @foreach ($partners as $p)
                        <option value="{{$p['id']}}" {{(int)$v('partner_id')===(int)$p['id']?'selected':''}}>{{$p['code'].' - '.$p['name']}}</option>
                        @endforeach
                        @endif
                    </select>
                    {!! !empty($errors['partner_id'])?'<small class="text-danger">'.e($errors['partner_id']).'</small>':false !!}
                </div>
                <div class="form-group col-md-3">
                    <label>Biển số xe <span class="text-danger">*</span></label>
                    <input type="text" name="bien_so" class="form-control text-uppercase" value="{{$v('bien_so')}}"/>
                    {!! !empty($errors['bien_so'])?'<small class="text-danger">'.e($errors['bien_so']).'</small>':false !!}
                </div>
                <div class="form-group col-md-3">
                    <label>Số km hiện tại</label>
                    <input type="text" name="so_km" class="form-control text-right" value="{{$v('so_km')}}"/>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Số khung (VIN)</label>
                    <input type="text" name="so_khung" class="form-control text-uppercase" value="{{$v('so_khung')}}"/>
                    {!! !empty($errors['so_khung'])?'<small class="text-danger">'.e($errors['so_khung']).'</small>':false !!}
                </div>
                <div class="form-group col-md-6">
                    <label>Số máy</label>
                    <input type="text" name="so_may" class="form-control text-uppercase" value="{{$v('so_may')}}"/>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Hãng xe <span class="text-muted small">(danh mục)</span></label>
                    <select name="brand_id" class="form-control" data-xe="hang" data-url="{{_WEB_URL.'/admin/vehicles'}}">
                        <option value="">— Không chọn —</option>
                        @if (!empty($hangDs))
                        @foreach ($hangDs as $h)
                        <option value="{{$h['id']}}" {{(int)$v('brand_id')===(int)$h['id']?'selected':''}}>{{$h['name']}}</option>
                        @endforeach
                        @endif
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label>Model</label>
                    <select name="model_id" class="form-control" data-xe="model" data-chon="{{$v('model_id')}}" disabled>
                        <option value="">— Chọn hãng trước —</option>
                    </select>
                    {!! !empty($errors['model_id'])?'<small class="text-danger">'.e($errors['model_id']).'</small>':false !!}
                </div>
                <div class="form-group col-md-4">
                    <label>Năm sản xuất</label>
                    <select name="car_year_id" class="form-control" data-xe="nam" data-chon="{{$v('car_year_id')}}" disabled>
                        <option value="">— Chọn model trước —</option>
                    </select>
                    {!! !empty($errors['car_year_id'])?'<small class="text-danger">'.e($errors['car_year_id']).'</small>':false !!}
                </div>
            </div>

            <div class="form-row">
                <div class="col-12"><p class="text-muted small mb-1"><i class="fas fa-info-circle mr-1"></i> Xe lạ chưa có trong Danh mục xe thì gõ tay ba ô dưới đây:</p></div>
                <div class="form-group col-md-4">
                    <label class="small">Hãng (gõ tay)</label>
                    <input type="text" name="hang_xe" class="form-control form-control-sm" value="{{$v('hang_xe')}}"/>
                </div>
                <div class="form-group col-md-4">
                    <label class="small">Model (gõ tay)</label>
                    <input type="text" name="model_xe" class="form-control form-control-sm" value="{{$v('model_xe')}}"/>
                </div>
                <div class="form-group col-md-4">
                    <label class="small">Năm SX (gõ tay)</label>
                    <input type="text" name="nam_sx" class="form-control form-control-sm" value="{{$v('nam_sx')}}"/>
                    {!! !empty($errors['nam_sx'])?'<small class="text-danger">'.e($errors['nam_sx']).'</small>':false !!}
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Phiên bản</label>
                    <input type="text" name="phien_ban" class="form-control" value="{{$v('phien_ban')}}"/>
                </div>
                <div class="form-group col-md-4">
                    <label>Màu xe</label>
                    <input type="text" name="mau_xe" class="form-control" value="{{$v('mau_xe')}}"/>
                </div>
                <div class="form-group col-md-4 align-self-end">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" name="status" id="status" value="1" {{(int)$v('status', 1)===1?'checked':''}}/>
                        <label class="custom-control-label" for="status">Đang dùng</label>
                    </div>
                </div>
            </div>

            <div class="form-group mb-0">
                <label>Ghi chú</label>
                <input type="text" name="ghi_chu" class="form-control" value="{{$v('ghi_chu')}}"/>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu</button>
            @if (route('admin/'.$routeBase.'/delete/'.$item['id']) && $soPhieu === 0)
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/delete/'.$item['id']}}" onclick="return confirm('Xoá xe này?')" class="btn btn-outline-danger"><i class="fas fa-trash mr-1"></i> Xoá</a>
            @endif
            @if ($soPhieu > 0)
            <span class="text-muted small ml-2">Xe đã có phiếu tiếp nhận nên không xoá được — tắt "Đang dùng" nếu muốn ẩn.</span>
            @endif
            <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default float-right">Về danh sách</a>
        </div>
    </div>
</form>
<script src="{{asset('public/assets/js/xe-danh-muc.js')}}"></script>
