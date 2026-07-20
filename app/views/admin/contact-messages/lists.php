@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
@endif
@if (!empty($msgError))
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-exclamation-circle mr-1"></i> {{$msgError}}</div>
@endif

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-inbox mr-2"></i>{{$page_name}}</h3>
        <div class="card-tools">{!! $countNew>0 ? '<span class="badge badge-danger p-2">'.(int)$countNew.' mới</span>' : '<span class="badge badge-secondary p-2">Không có tin mới</span>' !!}</div>
    </div>
    <div class="card-body border-bottom">
        <form method="get" class="form-row align-items-end">
            <div class="form-group col-md-3 mb-0">
                <label class="mb-1 small">Trạng thái</label>
                <select name="status" class="form-control form-control-sm">
                    <option value="">— Tất cả —</option>
                    @foreach ($statuses as $k => $label)
                    <option value="{{$k}}" {{$filterStatus==$k?'selected':''}}>{{$label}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-5 mb-0"><label class="mb-1 small">Tìm kiếm</label><input type="text" name="q" class="form-control form-control-sm" placeholder="Tên / email / SĐT / tiêu đề" value="{{$filterKeyword}}"/></div>
            <div class="col-auto"><button class="btn btn-sm btn-info"><i class="fas fa-search mr-1"></i>Lọc</button></div>
        </form>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover mb-0">
            <thead><tr>
                <th style="width:150px">Người gửi</th>
                <th style="width:170px">Liên hệ</th>
                <th>Nội dung</th>
                <th style="width:150px">Thời gian</th>
                <th style="width:100px" class="text-center">Trạng thái</th>
                <th style="width:90px" class="text-center"></th>
            </tr></thead>
            <tbody>
            @if (!empty($dataList))
                @foreach ($dataList as $r)
                <tr class="{{$r['status']==='new'?'font-weight-bold':''}}">
                    <td>{{$r['name']}}</td>
                    <td class="text-muted small">{{!empty($r['phone'])?$r['phone']:''}}{{!empty($r['phone'])&&!empty($r['email'])?' · ':''}}{{!empty($r['email'])?$r['email']:''}}</td>
                    <td class="text-muted">{{mb_strimwidth($r['subject']?$r['subject'].' — ':'', 0, 40, '')}}{{mb_strimwidth((string)$r['message'], 0, 60, '…')}}</td>
                    <td class="text-muted small">{{$r['create_at']}}</td>
                    <td class="text-center">{!! $r['status']==='handled' ? '<span class="badge badge-success">Đã xử lý</span>' : '<span class="badge badge-warning">Mới</span>' !!}</td>
                    <td class="text-center"><a href="{{_WEB_URL.'/admin/'.$routeBase.'/view/'.$r['id']}}" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a></td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="6" class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x d-block mb-2"></i> Chưa có liên hệ nào</td></tr>
            @endif
            </tbody>
        </table>
    </div>
</div>
