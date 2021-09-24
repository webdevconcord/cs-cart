<?php

$schema = array(
    'concordpay' => array(
        'processor'        => 'concordpay',
        'processor_script' => 'concordpay.php',
        'admin_template'   => 'concordpay.tpl',
        'callback'         => 'Y',
        'type'             => 'P',
        'position'         => 9,
        'addon'            => 'concordpay',
    )
);

return $schema;