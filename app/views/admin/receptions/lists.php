<?php
$pg = phan_trang((array) $dataList); $dataList = $pg['rows'];
$badge = ['tiep_nhan' => 'secondary', 'dang_sua' => 'warning', 'hoan_tat' => 'info', 'da_giao' => 'success', 'huy' => 'danger'];
$tien = function($x){ return $x === null ? '—' : number_format((float) $x, 0, ',', '.'); };
?>
@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}</div>
@endif

<div class="card card-outline card-info">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-clipboard-check mr-2"></i>{{$page_name}}</h3>
        <div class="card-tools">
            <span class="text-muted small mr-2">
                @if ($dangLoc)
                    {{$pg['total']}} / {{$tongTatCa}} phiếu
                @else
                    {{$tongTatCa}} phiếu · <b>{{$demDangMo}}</b> xe còn ở xưởng
                @endif
            </span>
            @if (route('admin/'.$routeBase.'/add'))
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/add'}}" class="btn btn-info btn-sm"><i class="fas fa-plus mr-1"></i> Tiếp nhận xe</a>
            @endif
        </div>
    </div>

    <div class="card-body border-bottom">
        <form method="get" action="{{_WEB_URL.'/admin/'.$routeBase}}" class="form-row align-items-end">
            <div class="form-group col-md-3 mb-2">
                <label class="mb-1 small">Tìm (số phiếu / BIỂN SỐ / khách / cố vấn)</label>
                <input type="text" name="q" class="form-control form-control-sm" value="{{$loc['q']}}"/>
            </div>
            <div class="form-group col-md-2 mb-2">
                <label class="mb-1 small">Trạng thái</label>
                <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
                    <option value="">— Tất cả —</option>
                    @foreach ($statuses as $k => $ten)
                    <option value="{{$k}}" {{$loc['status']===$k?'selected':''}}>{{$ten}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-2 mb-2">
                <label class="mb-1 small">Vào từ ngày</label>
                <input type="date" name="from" class="form-control form-control-sm" value="{{$loc['from']}}"/>
            </div>
            <div class="form-group col-md-2 mb-2">
                <label class="mb-1 small">Đến ngày</label>
                <input type="date" name="to" class="form-control form-control-sm" value="{{$loc['to']}}"/>
            </div>
            <div class="form-group col-md-3 mb-2">
                <div class="custom-control custom-checkbox mb-1">
                    <?php /* "Còn ở xưởng" = chưa giao xe và chưa huỷ — câu hỏi
                             đầu ngày của người trực quầy. */ ?>
                    <input type="checkbox" class="custom-control-input" name="dang_mo" id="dang_mo" value="1"
                           {{!empty($loc['dang_mo'])?'checked':''}} onchange="this.form.submit()"/>
                    <label class="custom-control-label small" for="dang_mo">Chỉ xe còn ở xưởng</label>
                </div>
                <button type="submit" class="btn btn-sm btn-info"><i class="fas fa-search mr-1"></i> Lọc</button>
                @if ($dangLoc)
                <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-sm btn-default">Xoá lọc</a>
                @endif
            </div>
        </form>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap mb-0">
            <thead>
                <tr>
                    <th style="width:130px">Số phiếu</th>
                    <th style="width:110px">Ngày vào</th>
                    <th style="width:150px">Xe</th>
                    <th>Khách / yêu cầu</th>
                    <th style="width:150px" class="text-right">Km vào / ra</th>
                    <th style="width:130px">Cố vấn</th>
                    <th style="width:110px" class="text-center">Trạng thái</th>
                    <th style="width:60px" class="text-center">Mở</th>
                </tr>
            </thead>
            <tbody>
            @if (!empty($dataList))
                @foreach ($dataList as $item)
                <?php $tenXe = VehiclesModel::tenXe($item); ?>
                <tr>
                    <td><code>{{$item['reception_no']}}</code></td>
                    <td>{{$item['ngay_vao']}}{!! !empty($item['ngay_ra']) ? '<span class="text-muted small d-block">ra: '.e($item['ngay_ra']).'</span>' : '' !!}</td>
                    <td>
                        <span class="font-weight-bold text-uppercase">{{$item['bien_so']}}</span>
                        <span class="text-muted small d-block">{{$tenXe}}</span>
                    </td>
                    <td>
                        {{!empty($item['chu_ten']) ? $item['chu_ten'] : '—'}}
                        <span class="text-muted small d-block">{{!empty($item['yeu_cau_khach']) ? mb_substr($item['yeu_cau_khach'], 0, 60) : ''}}</span>
                    </td>
                    <td class="text-right">{{$tien($item['km_vao'])}} / {{$tien($item['km_ra'])}}</td>
                    <td class="small">{{!empty($item['co_van_ten']) ? $item['co_van_ten'] : (!empty($item['co_van']) ? $item['co_van'] : '—')}}</td>
                    <td class="text-center"><span class="badge badge-{{$badge[$item['status']] ?? 'secondary'}}">{{$statuses[$item['status']] ?? $item['status']}}</span></td>
                    <td class="text-center">
                        @if (route('admin/'.$routeBase.'/edit/'.$item['id']))
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$item['id']}}" class="btn btn-info btn-sm"><i class="fas fa-folder-open"></i></a>
                        @endif
                    </td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="8" class="text-center text-muted py-4">
                    <i class="fas fa-clipboard fa-2x d-block mb-2"></i>
                    @if ($dangLoc)
                        Không có phiếu nào khớp bộ lọc.
                        <a href="{{_WEB_URL.'/admin/'.$routeBase}}">Xoá lọc</a> để xem tất cả {{$tongTatCa}} phiếu.
                    @else
                        Chưa có phiếu tiếp nhận nào. Bấm <b>Tiếp nhận xe</b> khi khách mang xe tới.
                    @endif
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
