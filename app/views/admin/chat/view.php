@if (!empty($msg))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fas fa-check-circle mr-1"></i> {{$msg}}</div>
@endif

<div class="row justify-content-center"><div class="col-lg-8">
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-comment-dots mr-2"></i>{{!empty($conv['guest_name']) ? $conv['guest_name'] : 'Khách'}}{{!empty($conv['guest_phone']) ? ' · '.$conv['guest_phone'] : ''}}</h3>
            <div class="card-tools">
                @if (route('admin/'.$routeBase.'/edit/'.$conv['id']))
                    @if ($conv['status']=='open')
                    <a href="{{_WEB_URL.'/admin/'.$routeBase.'/set-status/'.$conv['id'].'?status=closed'}}" class="btn btn-sm btn-outline-secondary">Đóng</a>
                    @else
                    <a href="{{_WEB_URL.'/admin/'.$routeBase.'/set-status/'.$conv['id'].'?status=open'}}" class="btn btn-sm btn-outline-success">Mở lại</a>
                    @endif
                @endif
            </div>
        </div>
        <div class="card-body" style="max-height:460px;overflow-y:auto;background:#f5f6f8">
            @if (!empty($messages))
                @foreach ($messages as $m)
                <?php $staff = ($m['sender'] === 'staff'); ?>
                <div style="display:flex;margin-bottom:8px;{{$staff?'justify-content:flex-end':''}}">
                    <div style="max-width:75%;padding:8px 12px;border-radius:12px;{{$staff?'background:#c0392b;color:#fff':'background:#fff;border:1px solid #e6e6e6'}}">
                        {{$m['body']}}
                        <div style="font-size:11px;opacity:.7;margin-top:3px">{{$m['create_at']}}</div>
                    </div>
                </div>
                @endforeach
            @else
                <p class="text-muted text-center">Chưa có tin nhắn.</p>
            @endif
        </div>
        <div class="card-footer">
            <form action="{{_WEB_URL.'/admin/'.$routeBase.'/reply/'.$conv['id']}}" method="post" class="form-inline">
                <?php echo csrf_field(); ?>
                <input type="text" name="body" class="form-control flex-grow-1 mr-2" placeholder="Nhập trả lời..." autocomplete="off" required/>
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane mr-1"></i> Gửi</button>
            </form>
        </div>
    </div>
    <a href="{{_WEB_URL.'/admin/'.$routeBase}}" class="btn btn-default">← Về inbox</a>
</div></div>
