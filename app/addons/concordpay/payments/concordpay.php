<?php

use Tygh\Http;
use Tygh\Registry;
use Tygh\Payments\Processors\Concordpay as Concordpay;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

if (defined('PAYMENT_NOTIFICATION')) {
    //Processing a response from a payment system
    $data = array();
    /** @var string $mode */
    switch ($mode) {
        case 'approve':
            fn_set_notification('N', __('concordpay.payment_approve'), __('concordpay.payment_approve_text'));
            break;
        case 'decline':
            fn_set_notification('E', __('concordpay.payment_decline'), __('concordpay.payment_decline_text'));
            break;
        case 'cancel':
            fn_set_notification('W', __('concordpay.payment_cancel'), __('concordpay.payment_cancel_text'));
            break;
        case 'callback':
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            break;
        default:
            return array(CONTROLLER_STATUS_NO_PAGE);
            break;
    }

    // Redirect and show checkout result notification message
    if (in_array($mode, ['approve', 'decline', 'cancel'])) {
        fn_redirect("checkout.complete&payment_status=$mode");
        exit;
    }

    // Change order status
    if ($mode === 'callback') {
        if (isset($data['order_id']) && !empty($data['order_id'])) {
            $data['orderReference'] = $data['order_id'];
        }

        if (empty($data) || empty($data['orderReference'])) {
            return array(CONTROLLER_STATUS_NO_PAGE);
        }
        // Current order info
        $order_info = fn_get_order_info($data['orderReference']);
        // Payment gateway info
        $processor_data = fn_get_payment_method_data($order_info['payment_id']);
        // Payment gateway params from admin menu
        $processor_params = $processor_data['processor_params'];
        $concordpay = new Concordpay($processor_params['merchantAccount'], $processor_params['merchantSecretKey']);

        if (empty($data['cardPan'])) {
            $data['cardPan'] = '';
        }

        $valid_signature = $concordpay->createSignature('CHECK_RESPONSE', $data);
        if ($data['merchantSignature'] !== $valid_signature) {
            return array(CONTROLLER_STATUS_NO_PAGE);
        }

        $pp_response = $concordpay->getFieldsNameForOrder($data, $processor_params['order_status']);

        $force_notification = array();
        if ($order_info['status'] == $pp_response['order_status']) {
            $force_notification = array('C' => false, 'A' => false, 'V' => false);
        }

        if ($order_info['status'] != STATUS_INCOMPLETED_ORDER) {
            if ($order_info['status'] != $pp_response['order_status']) {
                fn_change_order_status($order_info['order_id'], $pp_response['order_status']);
            }
            fn_update_order_payment_info($order_info['order_id'], $pp_response);
        } else {
            fn_finish_payment($order_info['order_id'], $pp_response);
            fn_order_placement_routines('route', $order_info['order_id'], $force_notification);
        }
    }
    exit;
}

//Sending the form to the payment system
/** @var array $processor_data */
$processor_params = $processor_data['processor_params'];

/** @var array $order_info */
$order_reference = !empty($order_info['repaid'])
    ? ($order_info['order_id'] . '_' . $order_info['repaid'])
    : $order_info['order_id'];

$concordpay = new Concordpay($processor_params['merchantAccount'], $processor_params['merchantSecretKey']);

$client_first_name = !empty($order_info['b_firstname']) ? $order_info['b_firstname'] : $order_info['s_firstname'];
$client_last_name  = !empty($order_info['b_lastname']) ? $order_info['b_lastname'] : $order_info['s_lastname'];
$client_email      = !empty($order_info['email']) ? $order_info['email'] : '';
$client_phone      = !empty($order_info['b_phone']) ? $order_info['b_phone'] : $order_info['s_phone'];

$params = array(
    'operation'    => 'Purchase',
    'merchant_id'  => $processor_params['merchantAccount'],
    'amount'       => fn_format_price_by_currency(
        $order_info['total'],
        CART_PRIMARY_CURRENCY,
        $processor_params['currency']
    ),
    'order_id'     => $order_reference,
    'currency_iso' => Concordpay::CURRENCY_UAH,
    'description'  => __('concordpay.payment.card_payment') . ' ' . htmlspecialchars($_SERVER["HTTP_HOST"]) . ', ' .
        $client_first_name . ' ' . $client_last_name . ', ' . $client_phone,
    'add_params'   => [],
    'approve_url'  => fn_url('payment_notification.approve&payment=concordpay'),
    'decline_url'  => fn_url('payment_notification.decline&payment=concordpay'),
    'cancel_url'   => fn_url('payment_notification.cancel&payment=concordpay'),
    'callback_url' => fn_url('payment_notification.callback&payment=concordpay'),
    // Statistics.
    'client_first_name' => $client_first_name,
    'client_last_name'  => $client_last_name,
    'email'             => $client_email,
    'phone'             => $client_phone
);

$form = $concordpay->buildForm($params);
echo __('text_cc_processor_connection', ['[processor]' => __('concordpay')]);
echo $form;
echo <<<EOT
        <noscript><p>
EOT;
echo __('text_cc_javascript_disabled');

echo <<<EOT
        </p><p><input type="submit" name="btn" value="
EOT;
echo __('cc_button_submit');
echo <<<EOT
"></p>
        </noscript>
        </form>
        <script type="text/javascript">
            window.onload = function() {
                document.process.submit();
            };
        </script>
        </body>
    </html>
EOT;

exit;
