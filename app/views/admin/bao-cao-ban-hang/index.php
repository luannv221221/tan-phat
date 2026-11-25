<?php
$profit = (float) $totals['revenue'] - (float) $totals['cost'];
?>
<div class="card card-outline card-success">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-chart-line mr-2"></i>{{$page_name}}</h3></div>
    <div class="card-body border-bottom">
        <form method="get" class="form-row align-items-end">
            <div class="form-group col-md-3 mb-2">
                <label class="mb-1 small">Từ ngày</label>
                <input type="date" name="from" class="form-control form-control-sm" value="{{$filterFrom}}"/>
            </div>
            <div class="form-group col-md-3 mb-2">
                <label class="mb-1 small">Đến ngày</label>
                <input type="date" name="to" class="form-control form-control-sm" value="{{$filterTo}}"/>
            </div>
            <div class="form-group col-md-3 mb-2">
                <button type="submit" class="btn btn-sm btn-info"><i class="fas fa-search mr-1"></i> Lọc</button>
                <a href="{{_WEB_URL.'/admin/bao-cao-ban-hang'}}" class="btn btn-sm btn-default">Xoá</a>
            </div>
        </form>
    </div>
    <div class="card-body">
        <div class="row text-center">
            <div class="col-md-3"><div class="text-muted small">Số hoá đơn</div><div class="h4 mb-0">{{(int)$totals['count']}}</div></div>
            <div class="col-md-3"><div class="text-muted small">Doanh thu</div><div class="h4 mb-0 text-primary">{{number_format((float)$totals['revenue'],0,',','.')}} ₫</div></div>
            <div class="col-md-3"><div class="text-muted small">Giá vốn</div><div class="h4 mb-0">{{number_format((float)$totals['cost'],0,',','.')}} ₫</div></div>
            <div class="col-md-3"><div class="text-muted small">Lãi gộp</div><div class="h4 mb-0 {{$profit>=0?'text-success':'text-danger'}}">{{number_format($profit,0,',','.')}} ₫</div></div>
        </div>
    </div>
</div>

<?php /* HÀNG BÁN CHẠY — khách ghi 25/11/2026 cạnh "Báo cáo hàng tồn lâu". Có hàng tồn
         lâu (đẩy bán / giảm giá) thì cần cả chiều ngược lại: hàng nào bán nhiều để
         nhập thêm. Cùng kỳ lọc phía trên, chỉ hoá đơn đã ghi sổ. */ ?>
<div class="card card-outline card-warning" id="hang-ban-chay">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-fire mr-2"></i>Hàng bán chạy <span class="text-muted small">(20 mặt hàng bán nhiều nhất trong kỳ)</span></h3></div>
    <div class="card-body table-responsive p-0">
        <table class="table table-sm table-hover mb-0">
            <thead><tr><th class="text-center">#</th><th>Mã</th><th>Tên hàng</th><th class="text-right">SL bán</th><th class="text-center">Số HĐ</th><th class="text-right">Doanh thu</th><th class="text-right">Lãi gộp</th></tr></thead>
            <tbody>
            @if (!empty($banChay))
                @foreach ($banChay as $i => $r)
                <tr>
                    <td class="text-center">{{(int)$i + 1}}</td>
                    <td class="text-nowrap"><code>{{$r['ma']}}</code></td>
                    <td>{{$r['ten']}}<?php if ($r['loai'] === 'service'): ?> <span class="badge badge-light border">Dịch vụ</span><?php endif; ?></td>
                    <td class="text-right font-weight-bold">{{rtrim(rtrim(number_format((float)$r['so_luong'],3,',','.'),'0'),',')}}</td>
                    <td class="text-center">{{(int)$r['so_hd']}}</td>
                    <td class="text-right">{{number_format((float)$r['doanh_thu'],0,',','.')}}</td>
                    <td class="text-right {{$r['lai_gop']>=0?'text-success':'text-danger'}}">{{number_format((float)$r['lai_gop'],0,',','.')}}</td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="7" class="text-center text-muted py-3">Chưa có hàng bán trong kỳ này</td></tr>
            @endif
            </tbody>
        </table>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-users mr-2"></i>Theo khách hàng</h3></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead><tr><th>Khách hàng</th><th class="text-center">SL</th><th class="text-right">Doanh thu</th><th class="text-right">Lãi gộp</th></tr></thead>
                    <tbody>
                    @if (!empty($byCustomer))
                        @foreach ($byCustomer as $r)
                        <?php $pr = (float) $r['revenue'] - (float) $r['cost']; ?>
                        <tr>
                            <td>{{$r['name']}}</td>
                            <td class="text-center">{{(int)$r['count']}}</td>
                            <td class="text-right">{{number_format((float)$r['revenue'],0,',','.')}}</td>
                            <td class="text-right {{$pr>=0?'text-success':'text-danger'}}">{{number_format($pr,0,',','.')}}</td>
                        </tr>
                        @endforeach
                    @else
                        <tr><td colspan="4" class="text-center text-muted py-3">Chưa có dữ liệu</td></tr>
                    @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card card-outline card-info">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-user-tie mr-2"></i>Theo nhân viên</h3></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead><tr><th>Nhân viên</th><th class="text-center">SL</th><th class="text-right">Doanh thu</th><th class="text-right">Lãi gộp</th></tr></thead>
                    <tbody>
                    @if (!empty($byStaff))
                        @foreach ($byStaff as $r)
                        <?php $pr = (float) $r['revenue'] - (float) $r['cost']; ?>
                        <tr>
                            <td>{{$r['name']}}</td>
                            <td class="text-center">{{(int)$r['count']}}</td>
                            <td class="text-right">{{number_format((float)$r['revenue'],0,',','.')}}</td>
                            <td class="text-right {{$pr>=0?'text-success':'text-danger'}}">{{number_format($pr,0,',','.')}}</td>
                        </tr>
                        @endforeach
                    @else
                        <tr><td colspan="4" class="text-center text-muted py-3">Chưa có dữ liệu</td></tr>
                    @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
