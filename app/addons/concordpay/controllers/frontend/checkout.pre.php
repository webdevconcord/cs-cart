<?php

/** @var string $mode */
if ($mode === 'complete'
    && isset($_REQUEST['payment_status'])
    && in_array($_REQUEST['payment_status'], ['approve', 'decline', 'cancel'])
) {
    Tygh::$app['view']->assign('payment_status', $_REQUEST['payment_status']);
}
