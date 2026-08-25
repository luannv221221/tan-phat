<?php
/**
 * BỘ LỌC XE Ở HEADER — Hãng → Dòng xe → Model → Đời xe + ô từ khoá.
 *
 * Nằm trong header nên hiện ở MỌI trang storefront. Bấm nút thì chuyển sang
 * /san-pham kèm car_brand/car_body/car_model/car_year/q — Shop::index() đọc
 * các tham số này, PartsModel::applyCarFilter() lọc qua bảng part_fitments.
 *
 * Vì sao nhúng sẵn cả danh mục xe thay vì gọi AJAX: cây xe rất nhỏ (chục hãng,
 * vài trăm model) nên tải hết một lần rẻ hơn nhiều so với mỗi lần đổi ô lại
 * chờ một vòng mạng. Nếu về sau danh mục phình lên hàng nghìn dòng thì đổi
 * fillOptions() sang gọi API là đủ, phần còn lại giữ nguyên.
 *
 * File này được master.php gọi qua $this->render() nên $this là Controller.
 */

$cfBrands = $this->model('CarBrandsModel')->getLists(true);
$cfBodies = $this->model('CarBodyTypesModel')->getLists(true);
$cfModels = $this->model('CarModelsModel')->getLists(['car_models.status' => 1]);
$cfYears  = $this->model('CarYearsModel')->getLists(['car_years.status' => 1]);

/* Danh mục hàng hoá.
   Bốn ô bên trái tả CHIẾC XE, ô này tả MÓN HÀNG — nên nó không tham gia
   dây chuyền lọc xe ở phần JS bên dưới, chỉ đi thẳng vào URL.

   Chọn danh mục cha vẫn ra hàng của danh mục con: Shop::index() đã gọi
   expandWithDescendants() trước khi truy vấn. Nếu không, chọn "Phụ tùng"
   sẽ ra rỗng vì hàng hoá chỉ gắn vào danh mục lá. */
$cfCats = $this->model('PartCategoriesModel')->getTree();

// Lựa chọn hiện tại, để khách ở /san-pham thấy đúng bộ lọc đang áp dụng
$cfSel = [
    'brand' => isset($_GET['car_brand']) ? (int) $_GET['car_brand'] : 0,
    'body'  => isset($_GET['car_body'])  ? (int) $_GET['car_body']  : 0,
    'model' => isset($_GET['car_model']) ? (int) $_GET['car_model'] : 0,
    'year'  => isset($_GET['car_year'])  ? (int) $_GET['car_year']  : 0,
];

/* Sidebar ở /san-pham cho tick NHIỀU danh mục (category[]), ô này chỉ chọn
   được một. Đang tick nhiều thì hiện cái đầu tiên — và bấm tìm ở thanh này
   là thay hẳn bằng đúng cái vừa chọn, coi như một lượt tìm mới. */
$cfCatSel = 0;
if (isset($_GET['category'])){
    $raw = is_array($_GET['category']) ? reset($_GET['category']) : $_GET['category'];
    $cfCatSel = (int) $raw;
}

// id ép về chuỗi vì bên JS đem so với select.value — vốn luôn là chuỗi.
$cfData = [
    'bodyTypes' => [],
    'models'    => [],
    'years'     => [],
];
foreach ($cfBodies as $b){
    $cfData['bodyTypes'][] = ['id' => (string) $b['id'], 'name' => $b['name']];
}
foreach ($cfModels as $m){
    $cfData['models'][] = [
        'id'    => (string) $m['id'],
        'name'  => $m['name'],
        'brand' => (string) $m['brand_id'],
        'body'  => (string) $m['body_type_id'],   // rỗng nếu model chưa gắn kiểu dáng
    ];
}
foreach ($cfYears as $y){
    $cfData['years'][] = ['id' => (string) $y['id'], 'name' => $y['name'], 'model' => (string) $y['model_id']];
}

/** In ra các <option> kèm sẵn lựa chọn hiện tại */
$cfOptions = function ($rows, $selected){
    foreach ($rows as $r){
        $sel = ((int) $r['id'] === (int) $selected) ? ' selected' : '';
        echo '<option value="' . (int) $r['id'] . '"' . $sel . '>' . e($r['name']) . '</option>';
    }
};
?>
<?php /* Nhãn nằm ở khối RIÊNG, không phải bên trong .car-filter.
         Lý do: .car-filter là phần bị ghim khi cuộn. Nhét nhãn vào trong rồi
         thu nó lại lúc ghim thì ô của nó trong dòng chảy cũng co theo, và
         toàn bộ nội dung phía dưới bị kéo lên — đo được là NHẢY 49px đúng
         vào khoảnh khắc ghim.
         Tách ra thì nhãn cứ trôi đi như nội dung thường, thanh lọc giữ nguyên
         chiều cao mãi mãi. Không cần JS, không nhảy. */ ?>
<div class="car-filter-nhan">
    <div class="container">
        <i class="fa fa-search" aria-hidden="true"></i>
        <span>Tra phụ tùng theo xe của bạn</span>
    </div>
</div>

<div class="car-filter">
    <div class="container">
        <form class="car-filter__form" id="carFilterForm" action="<?php echo _WEB_URL; ?>/san-pham" method="get">
            <select class="car-filter__select" name="car_brand" data-cf="brand" aria-label="Thương hiệu xe">
                <option value="">Thương Hiệu</option>
                <?php $cfOptions($cfBrands, $cfSel['brand']); ?>
            </select>

            <select class="car-filter__select" name="car_body" data-cf="body" aria-label="Dòng xe">
                <option value="">Dòng Xe</option>
                <?php $cfOptions($cfBodies, $cfSel['body']); ?>
            </select>

            <select class="car-filter__select" name="car_model" data-cf="model" aria-label="Model xe">
                <option value="">Model Xe</option>
                <?php $cfOptions($cfModels, $cfSel['model']); ?>
            </select>

            <select class="car-filter__select" name="car_year" data-cf="year" aria-label="Năm sản xuất">
                <option value="">Năm sản xuất</option>
                <?php $cfOptions($cfYears, $cfSel['year']); ?>
            </select>

            <?php /* name="category[]" (có ngoặc vuông) để khớp với ô tick ở
                     sidebar /san-pham — nhờ vậy chọn ở đây thì sang bên kia
                     thấy tick sẵn đúng danh mục đó, và ngược lại.

                     KHÔNG đặt data-cf: dây chuyền lọc xe chỉ dựng lại các ô
                     brand/body/model/year, thêm ô này vào đó là bị xoá sạch
                     danh sách mỗi lần đổi hãng xe. */ ?>
            <select class="car-filter__select car-filter__select--cat" name="category[]" aria-label="Danh mục hàng hoá">
                <option value="">Danh mục</option>
                <?php
                foreach ($cfCats as $c){
                    // Chặn ở depth 2 cho khớp đúng những gì sidebar dựng được:
                    // chọn ở đây một danh mục mà sidebar không vẽ ô tick thì
                    // khách bỏ lọc bằng sidebar không nổi.
                    if ((int) $c['depth'] > 2) continue;
                    if ((int) $c['status'] !== 1) continue;

                    $sel = ((int) $c['id'] === $cfCatSel) ? ' selected' : '';
                    echo '<option value="' . (int) $c['id'] . '"' . $sel . '>'
                       . str_repeat('— ', (int) $c['depth']) . e($c['name'])
                       . '</option>';
                }
                ?>
            </select>

            <div class="car-filter__search">
                <input class="car-filter__input" type="search" name="q" placeholder="Phụ tùng bạn muốn tìm ?"
                       autocomplete="off" role="combobox" aria-expanded="false"
                       aria-controls="cfSuggest" aria-autocomplete="list"
                       value="<?php echo e(isset($_GET['q']) ? $_GET['q'] : ''); ?>"/>
                <div class="car-filter__suggest" id="cfSuggest" role="listbox" hidden></div>
            </div>

            <button class="car-filter__btn" type="submit" aria-label="Tìm kiếm">
                <i class="fa fa-search" aria-hidden="true"></i>
            </button>
        </form>
    </div>
</div>

<script>
(function(){
    var form = document.getElementById('carFilterForm');
    if (!form) return;

    // JSON_HEX_TAG đổi dấu ngoặc nhọn sang dạng < >. Thiếu cờ này,
    // một tên xe chứa thẻ đóng script sẽ cắt ngang khối này và chèn được HTML
    // tuỳ ý. (Cũng vì vậy mà comment đây không viết thẳng thẻ đó ra.)
    var DATA = <?php echo json_encode($cfData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP); ?>;

    var el = {};
    ['brand', 'body', 'model', 'year'].forEach(function(k){
        el[k] = form.querySelector('[data-cf="' + k + '"]');
    });

    // Dòng đầu mỗi ô là nhãn gợi ý ("Thương Hiệu"...). Nhớ lại trước khi dựng
    // lại danh sách, nếu không thì sau lần lọc đầu tiên ô sẽ mất nhãn.
    var labels = {};
    Object.keys(el).forEach(function(k){ labels[k] = el[k].options[0].text; });

    function option(value, text){
        var o = document.createElement('option');
        o.value = value;
        o.textContent = text;   // gán qua textContent nên tên lấy từ CSDL không cần escape
        return o;
    }

    /** Dựng lại một ô; giữ lựa chọn cũ nếu nó vẫn còn hợp lệ, không thì xoá */
    function fill(select, items, label){
        var keep = select.value, found = false;

        select.textContent = '';
        select.appendChild(option('', label));

        items.forEach(function(it){
            select.appendChild(option(it.id, it.name));
            if (it.id === keep) found = true;
        });

        select.value = found ? keep : '';
    }

    function sync(){
        var brand = el.brand.value;

        // Dòng xe: chỉ giữ kiểu dáng mà hãng đang chọn thực sự có xe
        fill(el.body, DATA.bodyTypes.filter(function(b){
            return !brand || DATA.models.some(function(m){ return m.brand === brand && m.body === b.id; });
        }), labels.body);

        // Đọc lại sau mỗi fill(): lựa chọn cũ có thể vừa bị xoá vì không còn hợp lệ
        var body = el.body.value;
        var models = DATA.models.filter(function(m){
            return (!brand || m.brand === brand) && (!body || m.body === body);
        });
        fill(el.model, models, labels.model);

        // Chưa chọn model thì liệt kê đời xe của mọi model còn hợp lệ — tên đời
        // xe đã kèm tên model ("Vios 2018-2023") nên không sợ nhầm.
        var model = el.model.value;
        var ids = model ? [model] : models.map(function(m){ return m.id; });
        fill(el.year, DATA.years.filter(function(y){
            return ids.indexOf(y.model) !== -1;
        }), labels.year);
    }

    ['brand', 'body', 'model'].forEach(function(k){
        el[k].addEventListener('change', sync);
    });

    // Ô để trống thì tắt đi cho khỏi lọt vào URL dưới dạng tham số rỗng.
    form.addEventListener('submit', function(){
        var off = [];
        Array.prototype.forEach.call(form.elements, function(c){
            if (c.name && !String(c.value).trim()){
                c.disabled = true;
                off.push(c);
            }
        });
        // Trình duyệt đã gom dữ liệu form xong khi handler này kết thúc, nên bật
        // lại ngay tick sau — để bấm Back (bfcache) không thấy các ô bị khoá.
        setTimeout(function(){ off.forEach(function(c){ c.disabled = false; }); }, 0);
    });

    sync();   // thu hẹp ngay theo tham số đang có trên URL

    // ---------------------------------------------------------------
    // GỢI Ý TÌM KIẾM — gõ tới đâu gợi ý tới đó
    // ---------------------------------------------------------------
    var kw   = form.querySelector('.car-filter__input');
    var box  = form.querySelector('.car-filter__suggest');
    var timer = null, seq = 0, items = [], cur = -1;

    function close(){
        box.hidden = true;
        box.textContent = '';
        kw.setAttribute('aria-expanded', 'false');
        items = []; cur = -1;
    }

    function highlight(i){
        var els = box.querySelectorAll('.cf-sug');
        if (!els.length) return;
        if (cur >= 0 && els[cur]) els[cur].classList.remove('is-on');
        cur = i;
        if (cur >= 0 && els[cur]){
            els[cur].classList.add('is-on');
            els[cur].scrollIntoView({block: 'nearest'});
        }
    }

    function money(n){ return n > 0 ? n.toLocaleString('vi-VN') + ' ₫' : 'Liên hệ'; }

    function render(list){
        box.textContent = '';
        if (!list.length){
            var e = document.createElement('div');
            e.className = 'cf-sug-empty';
            e.textContent = 'Không tìm thấy phụ tùng nào';
            box.appendChild(e);
            box.hidden = false;
            kw.setAttribute('aria-expanded', 'true');
            return;
        }

        list.forEach(function(it, i){
            var a = document.createElement('a');
            a.className = 'cf-sug';
            a.href = it.url;
            a.setAttribute('role', 'option');

            if (it.image){
                var img = document.createElement('img');
                img.src = it.image; img.alt = ''; img.loading = 'lazy';
                a.appendChild(img);
            }

            var main = document.createElement('span');
            main.className = 'cf-sug__main';

            // textContent, không phải innerHTML: tên hàng do người dùng nhập ở
            // admin, dựng bằng chuỗi HTML là mở đường chèn thẻ tuỳ ý.
            var nm = document.createElement('span');
            nm.className = 'cf-sug__name';
            nm.textContent = it.name;
            main.appendChild(nm);

            var meta = document.createElement('span');
            meta.className = 'cf-sug__meta';
            meta.textContent = it.code + ' · ' + money(it.price);
            main.appendChild(meta);

            a.appendChild(main);
            a.addEventListener('mouseenter', function(){ highlight(i); });
            box.appendChild(a);
        });

        box.hidden = false;
        kw.setAttribute('aria-expanded', 'true');
        cur = -1;
    }

    function fetchSuggest(){
        var q = kw.value.trim();
        if (q.length < 2){ close(); return; }

        // Kèm luôn xe đang chọn để gợi ý khớp với kết quả lúc bấm Enter
        var p = new URLSearchParams({q: q});
        ['brand', 'body', 'model', 'year'].forEach(function(k){
            if (el[k].value) p.append('car_' + (k === 'body' ? 'body' : k), el[k].value);
        });

        // Mạng không đảm bảo thứ tự trả về: đánh số rồi bỏ qua phản hồi cũ,
        // nếu không thì gõ nhanh sẽ thấy gợi ý của ký tự trước đè lên.
        var mine = ++seq;
        fetch('<?php echo _WEB_URL; ?>/tim-kiem/goi-y?' + p.toString(), {headers: {'Accept': 'application/json'}})
            .then(function(r){ return r.ok ? r.json() : {items: []}; })
            .then(function(d){
                if (mine !== seq) return;
                items = d.items || [];
                render(items);
            })
            .catch(function(){ if (mine === seq) close(); });
    }

    kw.addEventListener('input', function(){
        clearTimeout(timer);
        timer = setTimeout(fetchSuggest, 250);   // gõ xong mới hỏi, đỡ dội server
    });

    kw.addEventListener('keydown', function(e){
        if (box.hidden) return;
        var els = box.querySelectorAll('.cf-sug');
        if (e.key === 'ArrowDown'){ e.preventDefault(); highlight(cur + 1 >= els.length ? 0 : cur + 1); }
        else if (e.key === 'ArrowUp'){ e.preventDefault(); highlight(cur - 1 < 0 ? els.length - 1 : cur - 1); }
        else if (e.key === 'Enter' && cur >= 0 && els[cur]){ e.preventDefault(); els[cur].click(); }
        else if (e.key === 'Escape'){ close(); }
    });

    // mousedown chứ không phải click: blur chạy trước click, đóng mất khung là
    // cú click rơi vào khoảng trống.
    box.addEventListener('mousedown', function(e){ e.preventDefault(); });
    kw.addEventListener('blur', function(){ setTimeout(close, 120); });
    kw.addEventListener('focus', function(){ if (kw.value.trim().length >= 2) fetchSuggest(); });
})();
</script>
