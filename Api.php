<?php

namespace Villafinder\Payum2c2p;

use Http\Message\MessageFactory;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\HttpClientInterface;
use Payum\ISO4217\Currency;
use Payum\ISO4217\ISO4217;

class Api
{
    const VERSION = '6.9';

    const STATUS_SUCCESS  = '000';
    const STATUS_PENDING  = '001';
    const STATUS_REJECTED = '002';
    const STATUS_CANCEL   = '003';
    const STATUS_ERROR    = '999';

    /**
     * @var HttpClientInterface
     */
    protected $client;

    /**
     * @var MessageFactory
     */
    protected $messageFactory;

    /**
     * @var array
     */
    protected $options = [];

    public function __construct(array $options, HttpClientInterface $client, MessageFactory $messageFactory)
    {
        $options = ArrayObject::ensureArrayObject($options);
        $options->defaults($this->options);
        $options->validatedKeysSet(array(
            'currencies',
            'trust_user_request',
        ));

        if (!is_array($options['currencies'])) {
            throw new LogicException('The currencies option must be an array.');
        }

        $iso = new ISO4217();
        foreach ($options['currencies'] as $currency => $currencyOptions) {
            try {
                $iso->findByCode($currency);
            } catch (\RuntimeException $e) {
                throw new LogicException(sprintf('Currency "%s" is not found in ISO 4217.', $currency), $e->getCode(), $e);
            }

            if (empty($currencyOptions['merchant_id']) || empty($currencyOptions['merchant_auth_key'])) {
                throw new LogicException(sprintf('Currency %s must have its options merchant_id and merchant_auth_key defined. Got %s.', $currency, implode(', ', array_keys($currencyOptions))));
            }
        }

        if (false == is_bool($options['sandbox'])) {
            throw new LogicException('The boolean sandbox option must be set.');
        }

        $this->options        = $options;
        $this->client         = $client;
        $this->messageFactory = $messageFactory;
    }

    /**
     * @param  string $currencyCode
     * @return array
     */
    public function getCurrencyConfigByCode($currencyCode)
    {
        $currencyCode = strtolower($currencyCode);
        if (!isset($this->options['currencies'][$currencyCode])) {
            throw new LogicException(sprintf('Currency "%s" is not configured in 2C2P gateway.', $currencyCode));
        }

        return $this->options['currencies'][$currencyCode];
    }

    /**
     * @return bool
     */
    public function trustUserRequest()
    {
        return (bool) $this->options['trust_user_request'];
    }

    /**
     * @return string
     */
    public function getOffsiteUrl()
    {
        return $this->options['sandbox'] ?
            'https://demo2.2c2p.com/2C2PFrontEnd/RedirectV3/payment' :
            'https://t.2c2p.com/RedirectV3/Payment'
        ;
    }

    /**
     * @param  array $params
     * @return array
     */
    public function prepareOffsitePayment(array $params)
    {
        $supportedParams = array(
            'version' => '',
            'merchant_id' => '',
            'payment_description' => '',
            'order_id' => '',
            'invoice_no' => '',
            'user_defined_1' => '',
            'user_defined_2' => '',
            'user_defined_3' => '',
            'user_defined_4' => '',
            'user_defined_5' => '',
            'amount' => '',
            'currency' => '',
            'promotion' => '',
            'customer_email' => '',
            'pay_category_id' => '',
            'result_url_1' => '',
            'result_url_2' => '',
            'payment_option' => '',
            'ipp_interest_type' => '',
            'payment_expiry' => '',
            'default_lang' => '',
            'enable_store_card' => '',
            'stored_card_unique_id' => '',
            'request_3ds' => '',
            'recurring' => '',
            'order_prefix' => '',
            'recurring_amount' => '',
            'allow_accumulate' => '',
            'max_accumulate_amount' => '',
            'recurring_interval' => '',
            'recurring_count' => '',
            'charge_next_date' => '',
            'charge_on_date' => '',
            'statement_descriptor' => '',
            'hash_value' => '',
        );

        $params = array_merge($supportedParams, $params);

        $this->addGlobalParams($params);

        return $params;
    }

    /**
     * @param  array  $params
     * @param  string $currency Some responses from 2C2P do not include currency, we are then using the one from model
     * @return bool
     */
    public function checkResponseHash(array $params, $currency)
    {
        $toHash =
            $params['version'].
            $params['request_timestamp'].
            $params['merchant_id'].
            $params['order_id'].
            $this->emptyOr('invoice_no', $params).
            $this->emptyOr('currency', $params).
            $params['amount'].
            $this->emptyOr('transaction_ref', $params).
            $this->emptyOr('approval_code', $params).
            $this->emptyOr('eci', $params).
            $params['transaction_datetime'].
            $params['payment_channel'].
            $params['payment_status'].
            $this->emptyOr('channel_response_code', $params).
            $this->emptyOr('channel_response_desc', $params).
            $this->emptyOr('masked_pan', $params).
            $this->emptyOr('stored_card_unique_id', $params).
            $this->emptyOr('backend_invoice', $params).
            $this->emptyOr('paid_channel', $params).
            $this->emptyOr('paid_agent', $params).
            $this->emptyOr('recurring_unique_id', $params).
            $this->emptyOr('user_defined_1', $params).
            $this->emptyOr('user_defined_2', $params).
            $this->emptyOr('user_defined_3', $params).
            $this->emptyOr('user_defined_4', $params).
            $this->emptyOr('user_defined_5', $params).
            $params['browser_info'].
            $this->emptyOr('ippPeriod', $params).
            $this->emptyOr('ippInterestType', $params).
            $this->emptyOr('ippInterestRate', $params).
            $this->emptyOr('ippMerchantAbsorbRate', $params)
        ;

        return $params['hash_value'] === $this->calculateHash($toHash, $params['currency'] ?: $currency);
    }

    /**
     * @param array $params
     */
    protected function addGlobalParams(array &$params)
    {
        $params['version']     = self::VERSION;
        $params['merchant_id'] = $this->getMerchantIdForCurrency($params['currency']);
        $params['hash_value']  = $this->calculateRequestHash($params);
    }

    /**
     * @param  string $currencyNumeric
     * @return string
     */
    protected function getMerchantIdForCurrency($currencyNumeric)
    {
        return $this->getCurrencyConfigByNumeric($currencyNumeric)['merchant_id'];
    }

    /**
     * @param  string $currencyNumeric
     * @return string
     */
    protected function getMerchantAuthKeyForCurrency($currencyNumeric)
    {
        return $this->getCurrencyConfigByNumeric($currencyNumeric)['merchant_auth_key'];
    }

    /**
     * @param  string $currencyNumeric
     * @return array
     */
    protected function getCurrencyConfigByNumeric($currencyNumeric)
    {
        $iso = new ISO4217();

        /** @var Currency $currency */
        $currency = $iso->findByNumeric($currencyNumeric);

        return $this->getCurrencyConfigByCode($currency->getAlpha3());
    }

    /**
     * @param  array $params
     * @return string
     */
    protected function calculateRequestHash(array $params)
    {
        $toHash =
            $params['version'].
            $params['merchant_id'].
            $params['payment_description'].
            $params['order_id'].
            $params['invoice_no'].
            $params['currency'].
            $params['amount'].
            $params['customer_email'].
            $params['pay_category_id'].
            $params['promotion'].
            $params['user_defined_1'].
            $params['user_defined_2'].
            $params['user_defined_3'].
            $params['user_defined_4'].
            $params['user_defined_5'].
            $params['result_url_1'].
            $params['result_url_2']
        ;

        return $this->calculateHash($toHash, $params['currency']);
    }

    /**
     * @param  string $toHash
     * @param  string $currencyNumeric
     * @return string
     */
    private function calculateHash($toHash, $currencyNumeric)
    {
        return strtoupper(hash_hmac('sha1', $toHash, $this->getMerchantAuthKeyForCurrency($currencyNumeric), false));
    }

    private function emptyOr(string $index, array $array)
    {
        if (!array_key_exists($index, $array)) {
            return '';
        }

        return $array[$index];
    }
}
