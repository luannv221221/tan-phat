<?php
/**
 * Trang Tổng quan.
 *
 * Dữ liệu do admin/Dashboard::index() dựng sẵn:
 *   $cards[]   mỗi thẻ: title, icon, color, sum, count, deltaSum, deltaCount
 *   $ranges    danh sách khoảng thời gian (số ngày => nhãn)
 *   $rangeDays khoảng đang chọn
 *   $compare   nhãn kỳ so sánh
 *
 * Viết bằng PHP thuần (không dùng @if/@foreach) cho chắc — bộ Template
 * xử lý các directive bằng regex và từng vấp lỗi với cú pháp lồng nhau.
 */

$arrows = ['up' => '&uarr;', 'down' => '&darr;', 'flat' => '&rarr;'];
?>

<form method="get" action="<?php echo _WEB_URL . '/admin'; ?>" class="adm-filters">
    <div class="adm-filters__item">
        <label for="f-range">Khoảng thời gian</label>
        <select id="f-range" name="range" class="form-control" onchange="this.form.submit()">
            <?php foreach ($ranges as $days => $label): ?>
                <option value="<?php echo (int) $days; ?>" <?php echo ((int) $days === (int) $rangeDays) ? 'selected' : ''; ?>>
                    <?php echo e($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="adm-filters__item">
        <label for="f-compare">So sánh với</label>
        <input id="f-compare" type="text" class="form-control" value="<?php echo e($compare); ?>" readonly/>
    </div>
</form>

<div class="row">
    <?php foreach ($cards as $c): ?>
        <div class="col-lg-4 col-md-6">
            <div class="card stat-card">
                <div class="card-body">

                    <div class="stat-card__head">
                        <span class="stat-card__icon stat-card__icon--<?php echo e($c['color']); ?>">
                            <?php echo icon($c['icon']); ?>
                        </span>
                        <span class="stat-card__title"><?php echo e($c['title']); ?></span>
                    </div>

                    <div class="stat-card__grid">
                        <div>
                            <div class="stat-metric__label">Tổng tiền</div>
                            <div class="stat-metric__value stat-metric__value--<?php echo e($c['color']); ?>">
                                <?php echo number_format($c['sum'], 0, ',', '.'); ?> đ
                            </div>
                            <div class="stat-metric__delta">
                                <?php echo $arrows[$c['deltaSum']['dir']]; ?>
                                <?php echo rtrim(rtrim(number_format($c['deltaSum']['percent'], 1, ',', '.'), '0'), ','); ?>%
                            </div>
                        </div>

                        <div>
                            <div class="stat-metric__label">Số đơn</div>
                            <div class="stat-metric__value">
                                <?php echo number_format($c['count'], 0, ',', '.'); ?>
                            </div>
                            <div class="stat-metric__delta">
                                <?php echo $arrows[$c['deltaCount']['dir']]; ?>
                                <?php echo rtrim(rtrim(number_format($c['deltaCount']['percent'], 1, ',', '.'), '0'), ','); ?>%
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
