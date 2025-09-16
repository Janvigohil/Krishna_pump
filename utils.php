<?php
function format_amount($number) {
    return number_format((float)$number, 2, '.', ',');
}
?>
