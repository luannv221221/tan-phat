<?php
function test(){
    return 'PHP';
}
$str = '<?php echo "Unicode"; echo test(); ?>';

eval(' ?> '.$str.' <?php ');