@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}</div>
@endif

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-map-marker-alt mr-2"></i>{{$page_name}}</h3>
        <div class="card-tools">
            @if (route('admin/'.$routeBase.'/add'))
            <a href="{{_WEB_URL.'/admin/'.$routeBase.'/add'}}" class="btn btn-sm btn-primary"><i class="fas fa-plus mr-1"></i> Thêm vị trí</a>
            @endif
        </div>
    </div>

    <div class="card-body border-bottom">
        <form method="get" class="form-row align-items-end">
            <div class="form-group col-md-4 mb-0">
                <label class="mb-1 small">Lọc theo kho</label>
                <select name="warehouse_id" class="form-control form-control-sm" onchange="this.form.submit()">
                    <option value="0">— Tất cả kho —</option>
                    @foreach ($warehouses as $w)
                    <option value="{{$w['id']}}" {{$filterWh==$w['id']?'selected':''}}>{{$w['code'].' - '.$w['name']}}</option>
                    @endforeach
                </select>
            </div>
        </form>
        <p class="text-muted small mb-0 mt-2"><i class="fas fa-info-circle mr-1"></i> Cây tối đa {{$maxLevel}} cấp (VD: Khu → Tầng → Kệ → Ngăn → Ô). Dùng làm gợi ý vị trí khi lập phiếu nhập kho.</p>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th style="width:120px">Mã</th>
                    <th>Vị trí (đường dẫn)</th>
                    <th>Kho</th>
                    <th style="width:70px" class="text-center">Cấp</th>
                    <th style="width:90px" class="text-center">Trạng thái</th>
                    <th style="width:130px" class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            @if (!empty($dataList))
                @foreach ($dataList as $r)
                <?php $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', max(0, (int)$r['level'] - 1)); ?>
                <tr>
                    <td><code>{{$r['code']}}</code></td>
                    <td>{!! $indent !!}<i class="fas fa-{{(int)$r['level']>1?'level-up-alt fa-rotate-90 text-muted':'folder text-warning'}} mr-1"></i> {{$r['name']}} <span class="text-muted small">{{$r['full_path']}}</span></td>
                    <td class="text-muted">{{$r['warehouse_code']}}</td>
                    <td class="text-center">{{$r['level']}}</td>
                    <td class="text-center">{!! (int)$r['status']===1 ? '<span class="badge badge-success">Bật</span>' : '<span class="badge badge-secondary">Tắt</span>' !!}</td>
                    <td class="text-center">
                        @if (route('admin/'.$routeBase.'/edit/'.$r['id']))
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/edit/'.$r['id']}}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                        @endif
                        @if (route('admin/'.$routeBase.'/delete/'.$r['id']))
                        <a href="{{_WEB_URL.'/admin/'.$routeBase.'/delete/'.$r['id']}}" onclick="return confirm('Xoá vị trí này? Mọi nhánh con cũng bị xoá.')" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></a>
                        @endif
                    </td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="6" class="text-center text-muted py-4"><i class="fas fa-map-marker-alt fa-2x d-block mb-2"></i> Chưa khai báo vị trí kho nào</td></tr>
            @endif
            </tbody>
        </table>
    </div>
</div>
