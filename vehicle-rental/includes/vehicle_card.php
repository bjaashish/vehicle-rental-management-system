<?php /* Reusable vehicle card. Expects $v (vehicle row) and $base. */ ?>
<article class="card">
    <a href="vehicle.php?id=<?= (int)$v['id'] ?>" class="card-img">
        <?= vehicle_image($v, $base) ?>
        <?= status_badge($v['status']) ?>
    </a>
    <div class="card-body">
        <h3><?= e($v['brand'] . ' ' . $v['name']) ?></h3>
        <span class="muted"><?= e($v['type']) ?></span>
        <div class="specs">
            <span class="spec">⛽ <?= e($v['fuel']) ?></span>
            <span class="spec">⚙️ <?= e($v['transmission']) ?></span>
            <span class="spec">👤 <?= (int)$v['seats'] ?> seats</span>
        </div>
        <div class="card-footer">
            <div class="price"><?= money($v['price_per_day']) ?> <small>/day</small></div>
            <a href="vehicle.php?id=<?= (int)$v['id'] ?>" class="btn btn-primary btn-sm">
                <?= $v['status'] === 'available' ? 'Book Now' : 'Details' ?>
            </a>
        </div>
    </div>
</article>
