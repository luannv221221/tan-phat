<?php
$nhanLoai = ['part' => 'Phụ tùng', 'equipment' => 'Thiết bị', 'service' => 'Dịch vụ'];
$tien = function ($v){ return $v === null || $v === '' ? '' : number_format((float) $v, 0, ',', '.'); };
?>
@if (!empty($msg))
<div class="alert alert-success alert-dismissible">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
    <i class="fas fa-check-circle mr-1"></i> {{$msg}}
</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
    <i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}
</div>
@endif

@if (empty($gara))
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle mr-1"></i>
    Hệ thống chưa khai gara nào. Vào <b>Hệ thống → Quản lý gara</b> để thêm trước đã.
</div>
@else

<div class="alert alert-info py-2">
    <i class="fas fa-map-pin mr-1"></i>
    Đang sửa danh mục của <b>{{$gara['name']}}</b>.
    Đổi chi nhánh bằng ô chọn gara ở góc trên bên phải.
</div>

<?php /* ================= KHỐI 1: danh mục hiện có ================= */ ?>
<div class="card card-outline card-success">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-clipboard-list mr-2"></i>Danh mục đang dùng của {{$gara['name']}}</h3>
        <div class="card-tools text-muted small">{{count($dsDanhMuc)}} mặt hàng</div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-sm table-hover mb-0">
            <thead><tr>
                <th style="width:60px" class="text-center">STT</th>
                <th style="width:130px">Mã</th>
                <th>Tên</th>
                <th style="width:100px">Loại</th>
                <th style="width:90px">ĐVT</th>
                <th style="width:140px" class="text-right">Giá áp dụng</th>
                <th style="width:150px">Nguồn</th>
                <th style="width:70px" class="text-center">Xoá</th>
            </tr></thead>
            <tbody>
            @if (!empty($dsDanhMuc))
                @foreach ($dsDanhMuc as $k => $r)
                <tr>
                    <td class="text-center text-muted">{{$k+1}}</td>
                    <td><code>{{$r['code']}}</code></td>
                    <td class="font-weight-bold">{{$r['name']}}</td>
                    <td>{{isset($nhanLoai[$r['item_type']])?$nhanLoai[$r['item_type']]:$r['item_type']}}</td>
                    <td class="text-muted">{{!empty($r['unit_name'])?$r['unit_name']:'—'}}</td>
                    <td class="text-right font-weight-bold">{{$tien($r['price'])}} đ</td>
                    <td>
                        @if (!empty($r['hang_rieng']))
                            <span class="badge badge-warning">Hàng riêng</span>
                        @else
                            @if ($r['gia_rieng'] !== null)
                                <span class="badge badge-primary">Giá riêng</span>
                                <span class="text-muted small">tổng: {{$tien($r['gia_tong'])}}</span>
                            @else
                                <span class="badge badge-secondary">Theo giá tổng</span>
                            @endif
                        @endif
                    </td>
                    <td class="text-center">
                        @if (!empty($r['hang_rieng']) && route('admin/'.$routeBase.'/xoa-rieng/'.$r['id']))
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/xoa-rieng/'.$r['id']}}"
                           onclick="return confirm('Xoá {{$r['name']}} khỏi danh mục riêng?')"
                           class="btn btn-danger btn-sm" title="Xoá hàng riêng"><i class="fas fa-trash"></i></a>
                        @else
                        <span class="text-muted small" title="Hàng của danh mục tổng — bỏ tick ở bảng dưới">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="8" class="text-center text-muted py-4">
                    <i class="fas fa-clipboard fa-2x d-block mb-2"></i>
                    Gara này chưa chọn mặt hàng nào. Tick ở bảng bên dưới để thêm.
                </td></tr>
            @endif
            </tbody>
        </table>
    </div>
</div>

<?php /* ================= KHỐI 2: thêm hàng riêng ================= */ ?>
<div class="card card-outline card-warning">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-plus-circle mr-2"></i>Thêm mặt hàng chỉ gara này có</h3>
    </div>
    <form action="{{_WEB_URL.'/admin/'.$routeBase.'/them-rieng'}}" method="post">
        <?php echo csrf_field(); ?>
        <div class="card-body">
            <div class="form-row align-items-end">
                <div class="col-md-4">
                    <label class="small mb-1">Tên mặt hàng <span class="text-danger">*</span></label>
                    <input name="name" class="form-control form-control-sm"
                           placeholder="VD: Công thợ thay dầu"
                           value="{{!empty($old['name'])?$old['name']:''}}"/>
                    {!! !empty($errors['name'])?'<small class="text-danger">'.e($errors['name']).'</small>':false !!}
                </div>
                <div class="col-md-2">
                    <label class="small mb-1">Loại</label>
                    <select name="item_type" class="form-control form-control-sm">
                        <option value="service">Dịch vụ</option>
                        <option value="part">Phụ tùng</option>
                        <option value="equipment">Thiết bị</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small mb-1">Đơn vị tính</label>
                    <select name="unit_id" class="form-control form-control-sm">
                        <option value="">—</option>
                        @if (!empty($dsDonVi))
                        @foreach ($dsDonVi as $u)
                        <option value="{{$u['id']}}">{{$u['name']}}</option>
                        @endforeach
                        @endif
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small mb-1">Giá</label>
                    <input name="price" class="form-control form-control-sm text-right" placeholder="150000"/>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-warning btn-block">
                        <i class="fas fa-plus mr-1"></i> Thêm
                    </button>
                </div>
            </div>
            <small class="form-text text-muted mt-2">
                Mã hàng tự sinh theo mã gara. Mặt hàng riêng <b>không hiện trên website</b> —
                trang bán hàng là trang chung cho cả hệ thống. Cần khai đầy đủ danh mục, hãng,
                ảnh, thông số thì dùng màn <b>Hàng hoá</b>.
            </small>
        </div>
    </form>
</div>

<?php /* ================= KHỐI 3: chọn từ kho tổng ================= */ ?>
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-warehouse mr-2"></i>Chọn mặt hàng từ kho tổng</h3>
        <div class="card-tools text-muted small">{{count($dsTong)}} mặt hàng trong danh mục tổng</div>
    </div>

    <form action="{{_WEB_URL.'/admin/'.$routeBase.'/chon'}}" method="post">
        <?php echo csrf_field(); ?>
        <div class="card-body table-responsive p-0">
            <table class="table table-sm table-hover mb-0">
                <thead><tr>
                    <th style="width:60px" class="text-center">Làm</th>
                    <th style="width:130px">Mã</th>
                    <th>Tên</th>
                    <th style="width:100px">Loại</th>
                    <th style="width:140px" class="text-right">Giá tổng</th>
                    <th style="width:180px">Giá riêng của gara</th>
                </tr></thead>
                <tbody>
                @if (!empty($dsTong))
                    @foreach ($dsTong as $r)
                    <?php
                    $__id  = (int) $r['id'];
                    $__co  = isset($dangChon[$__id]);
                    $__gia = $__co && $dangChon[$__id]['price'] !== null ? $dangChon[$__id]['price'] : '';
                    ?>
                    <tr>
                        <?php /* co_mat[] gửi kèm MỌI dòng đang hiển thị. Thiếu nó thì
                                 không phân biệt được "bỏ tick" với "dòng không có trên
                                 trang", và thao tác bỏ tick sẽ chẳng có tác dụng gì. */ ?>
                        <input type="hidden" name="co_mat[]" value="{{$__id}}"/>
                        <td class="text-center">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input"
                                       name="chon[{{$__id}}]" id="chon{{$__id}}" value="1" {{$__co?'checked':''}}/>
                                <label class="custom-control-label" for="chon{{$__id}}"></label>
                            </div>
                        </td>
                        <td><code>{{$r['code']}}</code></td>
                        <td>{{$r['name']}}</td>
                        <td class="text-muted">{{isset($nhanLoai[$r['item_type']])?$nhanLoai[$r['item_type']]:$r['item_type']}}</td>
                        <td class="text-right text-muted">{{$tien($r['price'])}} đ</td>
                        <td>
                            <input name="gia[{{$__id}}]" class="form-control form-control-sm text-right"
                                   value="{{$tien($__gia)}}" placeholder="theo giá tổng"/>
                        </td>
                    </tr>
                    @endforeach
                @else
                    <tr><td colspan="6" class="text-center text-muted py-4">Danh mục tổng chưa có mặt hàng nào</td></tr>
                @endif
                </tbody>
            </table>
        </div>

        <div class="card-footer">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu danh mục</button>
            <span class="text-muted small ml-2">
                Bỏ trống ô giá riêng = vẫn làm mặt hàng đó nhưng lấy giá tổng.
                Bỏ tick = gỡ khỏi danh mục gara (không ảnh hưởng danh mục tổng).
            </span>
        </div>
    </form>
</div>

@endif
