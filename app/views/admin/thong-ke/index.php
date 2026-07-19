<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-chart-area mr-2"></i>{{$page_name}}</h3>
        <div class="card-tools">
            <form method="get" class="form-inline">
                <select name="days" class="form-control form-control-sm" onchange="this.form.submit()">
                    <option value="7" {{$days==7?'selected':''}}>7 ngày</option>
                    <option value="30" {{$days==30?'selected':''}}>30 ngày</option>
                    <option value="90" {{$days==90?'selected':''}}>90 ngày</option>
                </select>
            </form>
        </div>
    </div>
    <div class="card-body">
        <div class="row text-center mb-2">
            <div class="col-md-4"><div class="text-muted small">Lượt xem ({{(int)$days}} ngày)</div><div class="h3 mb-0 text-primary">{{number_format((int)$totalRange,0,',','.')}}</div></div>
            <div class="col-md-4"><div class="text-muted small">Khách (IP) ({{(int)$days}} ngày)</div><div class="h3 mb-0 text-success">{{number_format((int)$uniqueRange,0,',','.')}}</div></div>
            <div class="col-md-4"><div class="text-muted small">Tổng lượt (toàn thời gian)</div><div class="h3 mb-0">{{number_format((int)$totalAll,0,',','.')}}</div></div>
        </div>
    </div>
</div>

<div class="card card-outline card-info">
    <div class="card-header"><h3 class="card-title">Lượt xem theo ngày</h3></div>
    <div class="card-body">
        @if (!empty($byDay))
        <div style="display:flex;align-items:flex-end;gap:2px;height:160px;border-bottom:1px solid #ddd;padding-bottom:2px">
            @foreach ($byDay as $d => $cnt)
            <?php $h = $maxDay > 0 ? max(2, (int) round($cnt / $maxDay * 150)) : 2; ?>
            <div title="{{$d}}: {{(int)$cnt}}" style="flex:1;background:#3498db;height:{{$h}}px;border-radius:2px 2px 0 0" data-toggle="tooltip"></div>
            @endforeach
        </div>
        <div class="d-flex justify-content-between text-muted small mt-1">
            <span>{{array_key_first($byDay)}}</span>
            <span>Cao nhất: {{(int)$maxDay}}/ngày</span>
            <span>{{array_key_last($byDay)}}</span>
        </div>
        @else
        <p class="text-muted mb-0">Chưa có dữ liệu.</p>
        @endif
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card card-outline card-secondary">
            <div class="card-header"><h3 class="card-title">Trang xem nhiều</h3></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead><tr><th>URL</th><th style="width:90px" class="text-right">Lượt</th></tr></thead>
                    <tbody>
                    @if (!empty($topPages))
                        @foreach ($topPages as $url => $cnt)
                        <tr><td><code>/{{$url=='/'?'':$url}}</code></td><td class="text-right">{{(int)$cnt}}</td></tr>
                        @endforeach
                    @else
                        <tr><td colspan="2" class="text-center text-muted py-3">—</td></tr>
                    @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card card-outline card-secondary">
            <div class="card-header"><h3 class="card-title">Nguồn giới thiệu</h3></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead><tr><th>Nguồn</th><th style="width:90px" class="text-right">Lượt</th></tr></thead>
                    <tbody>
                    @if (!empty($topRefs))
                        @foreach ($topRefs as $ref => $cnt)
                        <tr><td>{{$ref}}</td><td class="text-right">{{(int)$cnt}}</td></tr>
                        @endforeach
                    @else
                        <tr><td colspan="2" class="text-center text-muted py-3">Chưa có (truy cập trực tiếp)</td></tr>
                    @endif
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card card-outline card-secondary">
            <div class="card-header"><h3 class="card-title">Từ khoá tìm kiếm</h3></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead><tr><th>Từ khoá</th><th style="width:90px" class="text-right">Lượt</th></tr></thead>
                    <tbody>
                    @if (!empty($topKeywords))
                        @foreach ($topKeywords as $kw => $cnt)
                        <tr><td>{{$kw}}</td><td class="text-right">{{(int)$cnt}}</td></tr>
                        @endforeach
                    @else
                        <tr><td colspan="2" class="text-center text-muted py-3">—</td></tr>
                    @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
