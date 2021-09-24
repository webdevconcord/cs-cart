<?php

namespace Tygh\Payments\Processors;

use Tygh\Exceptions\InputException;

/**
 * Class Concordpay
 * @package Tygh\Payments\Processors
 */
class Concordpay
{
    const PURCHASE_URL      = 'https://pay.concord.ua/api/';
    const API_URL           = 'https://pay.concord.ua/api/';
    const FIELDS_DELIMITER  = ';';
    const DEFAULT_CHARSET   = 'utf8';
    const MODE_PURCHASE     = 'Purchase';
    const MODE_CHECK_STATUS = 'CHECK_STATUS';

    const MODE_REPLY_TO_RESPONSE = 'REPLY_TO_RESPONSE';
    const MODE_CHECK_RESPONSE    = 'CHECK_RESPONSE';
    const RESPONSE_TYPE_PAYMENT  = 'payment';
    const RESPONSE_TYPE_REVERSE  = 'reverse';
    const CURRENCY_UAH           = 'UAH';

    const TRANSACTION_STATUS_APPROVED = 'Approved';

    private $merchant_account;
    private $merchant_password;
    private $action;
    private $params;
    private $charset;

    /**
     * @var string[]
     */
    protected $keys_for_response_signature = array(
        'merchantAccount',
        'orderReference',
        'amount',
        'currency'
    );

    /**
     * @var string[]
     */
    protected $keys_for_signature = array(
        'merchant_id',
        'order_id',
        'amount',
        'currency_iso',
        'description'
    );

    /**
     * Init
     *
     * @param $merchant_account
     * @param $merchant_password
     * @param string $charset
     */
    public function __construct($merchant_account, $merchant_password, string $charset = self::DEFAULT_CHARSET)
    {
        if (!is_string($merchant_account) || trim($merchant_account) === '') {
            throw new InputException('Merchant account must be string and not empty');
        }

        if (!is_string($merchant_password) || trim($merchant_password) === '') {
            throw new InputException('Merchant password must be string and not empty');
        }

        $this->merchant_account  = $merchant_account;
        $this->merchant_password = $merchant_password;
        $this->charset           = $charset;
    }

    /**
     * MODE_CHECK_STATUS
     *
     * @param $fields
     * @return mixed
     */
    public function checkStatus($fields)
    {
        $this->prepare(self::MODE_CHECK_STATUS, $fields);
        return $this->query();
    }

    /**
     * MODE_PURCHASE
     * Generate html form
     *
     * @param $fields
     * @return string
     */
    public function buildForm($fields)
    {
        $this->prepare(self::MODE_PURCHASE, $fields);

        $form = sprintf('<form method="POST" action="%s" accept-charset="utf-8" name="process">', self::PURCHASE_URL);

        foreach ($this->params as $key => $value) {
            $form .= sprintf('<input type="hidden" name="%s" value="%s" />', $key, htmlspecialchars($value));
        }

        $form .= '</form>';

        return $form;
    }

    /**
     * MODE_PURCHASE
     * If GET redirect is used to redirect to purchase form
     *
     * @param $fields
     * @return string
     */
    public function generatePurchaseUrl($fields)
    {
        $this->prepare(self::MODE_PURCHASE, $fields);

        return self::PURCHASE_URL . '/get?' . http_build_query($this->params);
    }

    /**
     * Return signature hash
     *
     * @param $action
     * @param $fields
     * @return mixed
     */
    public function createSignature($action, $fields)
    {
        $this->prepare($action, $fields);

        return $this->buildSignature();
    }

    /**
     * @param $action
     * @param array $params
     */
    private function prepare($action, array $params)
    {
        $this->action = $action;

        if (empty($params)) {
            throw new InputException('Arguments must be not empty');
        }

        $this->params = $params;
        $this->params['signature'] = $this->buildSignature();

        $this->checkFields();
    }

    /**
     * Check required fields
     *
     * @return bool
     */
    private function checkFields()
    {
        $required = $this->getRequiredFields();
        $error = array();

        foreach ($required as $item) {
            if (!array_key_exists($item, $this->params)) {
                $error[] = $item;
            }
        }
        if (!empty($error)) {
            throw new InputException('Missed required field(s): ' . implode(', ', $error) . '.');
        }

        return true;
    }

    /**
     * Generate signature hash
     *
     * @return string
     */
    private function buildSignature()
    {
        $signFields = $this->getFieldsNameForSignature();

        $data = array();
        $error = array();

        foreach ($signFields as $item) {
            if (array_key_exists($item, $this->params)) {
                $value = $this->params[$item];
                if (is_array($value)) {
                    $data[] = implode(self::FIELDS_DELIMITER, $value);
                } else {
                    $data[] = (string)$value;
                }
            } else {
                $error[] = $item;
            }
        }

        if (mb_strtolower($this->charset) !== self::DEFAULT_CHARSET) {
            foreach ($data as $key => $value) {
                $data[$key] = iconv($this->charset, self::DEFAULT_CHARSET, $value);
            }
        }

        if (!empty($error)) {
            throw new InputException('Missed signature field(s): ' . implode(', ', $error) . '.');
        }

        return hash_hmac('md5', implode(self::FIELDS_DELIMITER, $data), $this->merchant_password);
    }

    /**
     * Request method
     * @return mixed
     */
    private function query()
    {
        $fields = json_encode($this->params);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::API_URL);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json;charset=utf-8'));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * Prepare a list of fields for writing to the order - delete all fields except the required ones.
     *
     * @param $fields
     * @param $order_statuses
     * @param false $detail
     * @return array
     */
    public function getFieldsNameForOrder($fields, $order_statuses, bool $detail = false)
    {
        if ($detail) {
            $required = array(
                'operation',
                'merchant_id',
                'amount',
                'signature',
                'order_id',
                'currency_iso',
                'description',
                'add_params',
                'approve_url',
                'decline_url',
                'cancel_url',
                'callback_url'
            );
        } else {
            $required = array(
                'merchantAccount',
                'orderReference',
                'amount',
                'operation',
                'currency',
                'phone',
                'createdDate',
                'cardPan',
                'cardType',
                'fee',
                'transactionId',
                'type',
                'recToken',
                'add_params',
                'pcApprovalCode',
                'pcTransactionID',
                'transactionStatus',
                'reason',
                'reasonCode',
                'merchantSignature',
            );
        }

        $return = array();

        foreach ($required as $item) {
            if (array_key_exists($item, $fields) && !empty($fields[$item])) {
                $return[$item] = $fields[$item];
            }
        }
        $return['order_status'] = $order_statuses[
            $this->getOrderStatus($return['transactionStatus'], $return['type'])
        ];
        if ($return['transactionStatus'] === self::TRANSACTION_STATUS_APPROVED) {
            unset($return['reasonCode'], $return['reason']);
        }

        return $return;
    }

    /**
     * @param string $transaction_status
     * @param string $transaction_type
     * @return string
     */
    private function getOrderStatus(string $transaction_status, string $transaction_type)
    {
        if ($transaction_status === self::TRANSACTION_STATUS_APPROVED
            && $transaction_type === self::RESPONSE_TYPE_REVERSE
        ) {
            return 'RefundedVoided';
        }

        switch ($transaction_status) {
            case 'Approved':
                return 'Approved';
            case 'Created':
                return 'Created';
            case 'InProcessing':
                return 'InProcessing';
            case 'WaitingAuthComplete':
                return 'WaitingAuthComplete';
            case 'Pending':
                return 'Pending';
            case 'RefundInProcessing':
                return 'RefundInProcessing';
            case 'Voided':
            case 'Refunded':
                return 'RefundedVoided';
            case 'Declined':
                return 'Declined';
        }
    }


    /**
     * Signature fields
     *
     * @return array
     */
    private function getFieldsNameForSignature()
    {
        switch ($this->action) {
            case self::MODE_PURCHASE:
                return $this->keys_for_signature;
                break;
            case self::MODE_CHECK_RESPONSE:
                return $this->keys_for_response_signature;
                break;
            default:
                throw new InputException('Unknown transaction type: ' . $this->action);
        }
    }

    /**
     * Required fields
     *
     * @return array
     */
    private function getRequiredFields()
    {
        switch ($this->action) {
            case self::MODE_PURCHASE:
                return array(
                    'operation',
                    'merchant_id',
                    'amount',
                    'signature',
                    'order_id',
                    'currency_iso',
                    'description',
                    'add_params',
                    'approve_url',
                    'decline_url',
                    'cancel_url',
                    'callback_url'
                );
            case self::MODE_CHECK_RESPONSE:
                return array(
                    'merchantAccount',
                    'orderReference',
                    'amount',
                    'currency',
                    'type',
                    'transactionStatus',
                    'merchantSignature'
                );
                break;
            default:
                throw new InputException('Unknown transaction type');
        }
    }
}
