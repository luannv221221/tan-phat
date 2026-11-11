<?php
/**
 * BẢNG TÍNH GIÁ — giá vốn + giá bán theo từng nhóm khách (khách chốt 11/11/2026).
 *
 * Số cột thay đổi theo số nhóm khách của gara, nên colspan của dòng "chưa có
 * dữ liệu" phải TÍNH RA chứ không viết cứng — gara thêm một nhóm là dòng đó
 * thiếu một ô.
 */
$pg = phan_trang((array) $dataList); $dataList = $pg['rows'];
$soCot = 4 + count((array) $nhom);   // STT + Mã + Tên + Giá vốn + mỗi nhóm một cột

$tien = function($v){
    return $v === null ? '—' : number_format((float) $v, 0, ',', '.');
};
?>
@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}</div>
@endif

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-tags mr-2"></i>{{$page_name}}</h3>
        @if (!empty($coQuyenTinh))
        <div class="card-tools">
            <form action="{{_WEB_URL.'/admin/'.$routeBase.'/edit/tinh-lai'}}" method="post" class="d-inline"
                  onsubmit="return confirm('Cập nhật toàn bộ bảng giá theo giá vốn hiện tại?');">
                <?php echo csrf_field(); ?>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-calculator mr-1"></i> Cập nhật giá</button>
            </form>
        </div>
        @endif
    </div>

    <div class="card-body border-bottom">
        <div class="row">
            <div class="col-md-8">
                <p class="mb-1">
                    <span class="text-muted">Kiểu tính giá vốn:</span>
                    <span class="badge badge-info">{{isset($tenKieu[$kieu]) ? $tenKieu[$kieu] : $kieu}}</span>
                    <span class="text-muted ml-3">Lịch tự tính:</span>
                    <span class="badge badge-secondary">{{isset($tenChuKy[$chuKy]) ? $tenChuKy[$chuKy] : $chuKy}}</span>
                    @if (route('admin/settings'))
                    <a href="{{_WEB_URL.'/admin/settings'}}" class="ml-2 small"><i class="fas fa-cog mr-1"></i>Đổi ở Cấu hình gara</a>
                    @endif
                </p>
                <p class="mb-0 text-muted small">
                    Giá bán = (giá vốn + chi phí) × (1 + hệ số lãi của nhóm). Hệ số lãi đặt ở
                    <a href="{{_WEB_URL.'/admin/customer-groups'}}">Nhóm khách hàng</a>, ô chi phí đặt ở từng mặt hàng.
                    <strong>Giá vốn không sửa tay được</strong> — nó là kết quả của việc nhập kho.
                </p>
            </div>
            <div class="col-md-4">
                <form method="get" action="">
                    <div class="input-group input-group-sm">
                        <input type="text" name="q" class="form-control" placeholder="Tìm theo mã hoặc tên hàng…" value="{{$tuKhoa}}"/>
                        <span class="input-group-append"><button class="btn btn-default"><i class="fas fa-search"></i></button></span>
                    </div>
                </form>
                @if (!empty($tinhLanCuoi))
                <div class="text-muted small mt-2 text-md-right">Tính lần cuối: {{date('d/m/Y H:i', strtotime($tinhLanCuoi))}}</div>
                @else
                <div class="text-warning small mt-2 text-md-right">Chưa tính lần nào — bấm “Cập nhật giá”.</div>
                @endif
            </div>
        </div>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th style="width:60px" class="text-center">STT</th>
                    <th style="width:130px">Mã</th>
                    <th>Tên hàng hoá</th>
                    <th style="width:140px" class="text-right">Giá vốn</th>
                    @foreach ((array) $nhom as $n)
                    <th style="width:150px" class="text-right">
                        {{$n['name']}}
                        <div class="text-muted font-weight-normal small">
                            +{{rtrim(rtrim(number_format((float)(isset($n['markup_percent'])?$n['markup_percent']:0),2,'.',''),'0'),'.')}}%
                        </div>
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
            @if (!empty($dataList))
                @foreach ($dataList as $key => $item)
                <tr>
                    <td class="text-center text-muted">{{$key+1}}</td>
                    <td class="text-muted">{{$item['code']}}</td>
                    <td class="font-weight-bold">{{$item['name']}}</td>
                    <td class="text-right">
                        @if ((float) $item['gia_von'] > 0)
                        {{$tien($item['gia_von'])}}
                        @else
                        <span class="badge badge-warning" title="Mặt hàng này chưa từng nhập kho nên chưa có giá vốn">chưa có</span>
                        @endif
                    </td>
                    @foreach ((array) $nhom as $n)
                    <?php $g = isset($item['gia_theo_nhom'][$n['id']]) ? $item['gia_theo_nhom'][$n['id']] : null; ?>
                    <td class="text-right {{(float) $g > 0 ? 'font-weight-bold' : 'text-muted'}}">
                        {{(float) $g > 0 ? $tien($g) : '—'}}
                    </td>
                    @endforeach
                </tr>
                @endforeach
            @else
                <tr><td colspan="{{$soCot}}" class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                    {{$tuKhoa !== '' ? 'Không có mặt hàng nào khớp từ khoá' : 'Chưa có mặt hàng nào đang bán'}}
                </td></tr>
            @endif
            </tbody>
        </table>
    </div>

<?php if ($pg['total'] > 0): ?>
    <div class="card-footer d-flex justify-content-between align-items-center flex-wrap" style="gap:.5rem">
        {!! phan_trang_html($pg) !!}
    </div>
<?php endif; ?>
</div>
