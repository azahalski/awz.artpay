<?php

namespace Sale\Handlers\PaySystem;

use Bitrix\Main;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Request;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;
use Bitrix\Sale\PaymentCollection;
use Bitrix\Sale\PaySystem;
use Bitrix\Sale\Payment;
use Bitrix\Sale\PaySystem\ServiceResult;
use Bitrix\Sale\Internals\PaymentTable;

Loc::loadMessages(__FILE__);

/*
 * docs https://www.artpay.by/docs/API/v2/eripAddInvoice.html
 * да, в документации есть описание создания заказа E-POS.
 * Для E-POS добавляется параметр ap_epos_url_required=1, а в ответе придет ap_epos_url, ap_epos_qr
 * единственное не описан один нюанс. В ответе на создание заказа будет поле ap_erip_invoice_id со значением типа 19472-1-656, а в запросе GetEripInvoiceInfo нужно отправлять ap_erip_invoice_id=656
 * */

/**
 *
 */
class AwzArtpayHandler extends PaySystem\ServiceHandler implements PaySystem\IRefundExtended, PaySystem\IHold
{

    const TEST_URL = 'https://api-test-artpay.dev-3c.by/v2/';
    const ACTIVE_URL = 'https://api.artpay.by/v2/';

    /**
     * @param Payment $payment
     * @param Request|null $request
     * @return ServiceResult
     * @throws Main\ArgumentException
     * @throws Main\ArgumentNullException
     * @throws Main\ArgumentOutOfRangeException
     * @throws Main\NotImplementedException
     */
    public function initiatePay(Payment $payment, Request $request = null)
    {
        $result = new ServiceResult();

        if($payment->getField('PS_INVOICE_ID')){

            $invoiceData = unserialize($payment->getField('PAY_RETURN_COMMENT'), ['allowed_classes' => false]);
            $invoiceData['ap_erip_invoice_id']=$payment->getField('PS_INVOICE_ID');
            $invoiceData['ap_erip_service_no']=$this->getBusinessValue($payment, 'SERVICE_NO');

        }else{

            $createInvoiceResult = $this->createInvoice($payment);

            if (!$createInvoiceResult->isSuccess())
            {
                $result->addErrors($createInvoiceResult->getErrors());
                return $result;
            }

            $invoiceData = $createInvoiceResult->getData();

            if (!empty($invoiceData['ap_erip_invoice_id']))
            {
                $result->setPsData([
                    'PS_INVOICE_ID'=> $invoiceData['ap_erip_invoice_id']
                ]);
                $payment->setField('PS_INVOICE_ID', $invoiceData['ap_erip_invoice_id']);
                $payment->setField('PAY_RETURN_COMMENT', serialize($invoiceData));
                $payment->getOrder()->save();
            }

        }

        $invoiceData['PS_MODE']  = $this->service->getField('PS_MODE');
        $invoiceData['BX_PAYSYSTEM_CODE']  = $this->service->getField('ID');
        $invoiceData['instruction']  = $this->getBusinessValue($payment, 'INSTRUCTION');
        $invoiceData['SHOW_LINK']  = $this->getBusinessValue($payment, 'SHOW_LINK');
        $invoiceData['SHOW_QR']  = $this->getBusinessValue($payment, 'SHOW_QR');

        $this->setExtraParams($invoiceData);

        return $this->showTemplate($payment, "template");
    }

    /**
     * @param Payment $payment
     * @return ServiceResult
     */
    private function createInvoice(Payment $payment): ServiceResult
    {
        $result = new ServiceResult();

        $invoiceCreatedDate = time();

        $params = [
            'ap_request'=>'EripAddInvoice',
            'ap_storeid'=>$this->getBusinessValue($payment, 'USER'),
            'ap_client_dt'=>$invoiceCreatedDate,
            'ap_proto_ver'=>'1.3.0',
            'ap_lang'=>'ru',
            'ap_test'=>$this->isTestMode() ? 1 : 0,
            'ap_order_num'=>$this->getBusinessValue($payment, 'PAYMENT_ID'),
            'ap_invoice_desc'=>$this->getInvoiceDescription($payment),
            'ap_amount'=>$this->getBusinessValue($payment, 'PAYMENT_SHOULD_PAY'),
            'ap_currency'=>$this->getBusinessValue($payment, 'PAYMENT_SHOULD_PAY_CURRENCY'),
        ];
        if(!$this->isTestMode()) unset($params['ap_test']);

        $eripNum = $this->getBusinessValue($payment, 'SERVICE_NO');
        if($eripNum){
            $params['ap_erip_service_no'] = $eripNum;
        }

        if($this->service->getField('PS_MODE')=='EP'){
            $params['ap_epos_url_required'] = 1;
        }

        $appInvoiceExpire = $this->getBusinessValue($payment, 'PAYMENT_SROK');
        if($appInvoiceExpire){
            if((int)$appInvoiceExpire == $appInvoiceExpire){
                $appInvoiceExpire = (int)$appInvoiceExpire;
            }else{
                $appInvoiceExpire = strtotime($appInvoiceExpire);
            }
            $params['ap_invoice_expire'] = (int) $appInvoiceExpire;
        }
        $params['ap_signature'] = $this->createHash($params, $payment);

        $sendResult = $this->send(
            HttpClient::HTTP_POST,
            $this->getUrl($payment, 'createInvoice'),
            $params
        );

        if ($sendResult->isSuccess())
        {
            $invoiceData = $sendResult->getData();
            $verifyResponseResult = $this->verifyResponse($invoiceData);
            if ($verifyResponseResult->isSuccess())
            {
                $result->setData($invoiceData);
            }
            else
            {
                $result->addErrors($verifyResponseResult->getErrors());
            }
        }
        else
        {
            $result->addErrors($sendResult->getErrors());
        }

        return $result;
    }

    /**
     * @param array $arFields
     * @param Payment $payment
     * @return string
     */
    private function createHash(array $arFields, Payment $payment): string
    {
        $string = '';
        uksort($arFields, 'strnatcmp');
        foreach ($arFields as $param => $value){
            $string .= $value . ';';
        }

        $string .=  $this->getBusinessValue($payment, 'KEY1');
        $hash =  hash('sha512', $string);
        return $hash;
    }

    /**
     * @param Payment $payment
     * @return string
     */
    private function getUserEmail(Payment $payment): string
    {
        /** @var PaymentCollection $collection */
        $collection = $payment->getCollection();
        $order = $collection->getOrder();
        $userEmail = $order->getPropertyCollection()->getUserEmail();

        return $userEmail ? (string)$userEmail->getValue() : '';
    }

    /**
     * @param Payment $payment
     * @return string
     */
    private function getInvoiceDescription(Payment $payment): string
    {
        /** @var PaymentCollection $collection */
        $collection = $payment->getCollection();
        $order = $collection->getOrder();

        $descText = $this->getBusinessValue($payment, 'PAYMENT_DESC');
        if(!$descText) $descText = Loc::getMessage('AWZ_ARTPAY_HANDLER_PS_PAY_DESC');

        $description =  str_replace(
            [
                '#PAYMENT_NUMBER#',
                '#ORDER_NUMBER#',
                '#PAYMENT_ID#',
                '#ORDER_ID#',
                '#USER_EMAIL#'
            ],
            [
                $payment->getField('ACCOUNT_NUMBER'),
                $order->getField('ACCOUNT_NUMBER'),
                $payment->getId(),
                $order->getId(),
                $this->getUserEmail($payment)
            ],
            $descText
        );

        return $description;
    }

    /**
     * @param Request $request
     * @return int
     */
    public function getPaymentIdFromRequest(Request $request): int
    {
        $paymentId = $request->get('ORDER');
        $paymentId = preg_replace("/^[0]+/","",$paymentId);
        $paymentId = (int) $paymentId;
        if(!$paymentId){
            $paymentData = self::getPaymentData($request);
            if(!empty($paymentData)){
                $paymentId = (int) $paymentData['ID'];
            }
        }
        return $paymentId;
    }

    /**
     * @return string[]
     */
    public function getCurrencyList(): array
    {
        return ['BYN','USD','EUR','RUB'];
    }

    /**
     * @return string[]
     */
    public static function getIndicativeFields(): array
    {
        return ['service'=>'artpay'];
    }

    /**
     * @param Request $request
     * @return array
     */
    protected static function getJsonData(Request $request): array
    {
        try{
            $jsonData = $request->getJsonList()->toArray();
            if(is_array($jsonData)) {
                return $jsonData;
            }
        }catch (\Exception $e){

        }
        return [];
    }

    /**
     * @param Request $request
     * @return array
     */
    protected static function getPaymentData(Request $request): array
    {
        static $paymentData;
        if(!$paymentData){
            try{
                $jsonData = self::getJsonData($request);
                $paymentData = PaymentTable::getRowById((int)$jsonData['ap_order_num']);
            }catch (\Exception $e){
                PaySystem\ErrorLog::add(array(
                    'ACTION' => "isMyResponseExtended",
                    'MESSAGE' => $e->getMessage()
                ));
            }
        }
        if(!is_array($paymentData)) return [];
        return $paymentData;
    }

    /**
     * @param Request $request
     * @param $paySystemId
     * @return bool
     */
    protected static function isMyResponseExtended(Request $request, $paySystemId): bool
    {
        $id = $request->get('BX_PAYSYSTEM_CODE') ? $request->get('BX_PAYSYSTEM_CODE'): 0;
        if(!$id){
            $paymentData = self::getPaymentData($request);
            if(!empty($paymentData)){
                $id = (int) $paymentData['PAY_SYSTEM_ID'];
            }
        }
        return (int)$id === (int)$paySystemId;
    }

    /**
     * @param Payment $payment
     * @param Request $request
     * @return ServiceResult
     */
    public function processRequest(Payment $payment, Request $request): ServiceResult
    {
        $result = new ServiceResult();

        if(!$this->verifyRequest($payment, $request)){
            $result->addError(new Error(Loc::getMessage('AWZ_ARTPAY_HANDLER_PS_SIGN_ERROR')));
            return $result;
        }

        $jsonData = self::getJsonData($request);

        $data = [];
        $fields = [
            "PS_STATUS_CODE" => 1,
            "PS_STATUS_MESSAGE" => '',
            "PS_SUM" => $jsonData['ap_amount'],
            "PS_CURRENCY" => $payment->getField('CURRENCY'),
            "PS_RESPONSE_DATE" => new DateTime(),
            "PS_INVOICE_ID" => $jsonData['ap_erip_invoice_id'],
        ];

        if($jsonData['ap_erip_trn_state'] == 'Paid'){

            if(round($jsonData['ap_amount'],2)!=round($payment->getField('SUM'),2)){
                $data['CODE'] = 200;
                $fields["PS_STATUS"] = "N";
                $message = Loc::getMessage('AWZ_ARTPAY_HANDLER_PS_SUMM_ERROR');
                $fields['PS_STATUS_DESCRIPTION'] = $message;
                $result->addError(new Error($message));
            }else{
                $data['CODE'] = 0;
                $fields["PS_STATUS"] = "Y";
                $fields['PS_STATUS_DESCRIPTION'] = Loc::getMessage('AWZ_ARTPAY_HANDLER_PS_SUMM_OK');
                $result->setOperationType(ServiceResult::MONEY_COMING);
            }

        }else{
            $data['CODE'] = 200;
            $message = Loc::getMessage('AWZ_ARTPAY_HANDLER_PS_ERR_0');
            if($jsonData['ap_erip_trn_state'] == 'Canceled'){
                $message = Loc::getMessage('AWZ_ARTPAY_HANDLER_PS_ERR_1');
            }
            if($jsonData['ap_erip_trn_state'] == 'PayError'){
                $message = Loc::getMessage('AWZ_ARTPAY_HANDLER_PS_ERR_2');
            }
            if($jsonData['ap_erip_trn_state'] == 'CancelError'){
                $message = Loc::getMessage('AWZ_ARTPAY_HANDLER_PS_ERR_3');
            }
            $result->addError(new Error($message));
            $fields["PS_STATUS"] = "N";
            $fields['PS_STATUS_DESCRIPTION'] = $message;
        }
        $result->setPsData($fields);
        $result->setData($data);

        if (!$result->isSuccess())
        {
            PaySystem\ErrorLog::add(array(
                'ACTION' => "processRequest",
                'MESSAGE' => join('\n', $result->getErrorMessages())
            ));
        }

        return $result;

    }

    /**
     * @param Payment $payment
     * @return void
     */
    public function cancel(Payment $payment)
    {
        // TODO: Implement cancel() method.
    }

    /**
     * @param Payment $payment
     * @return void
     */
    public function confirm(Payment $payment)
    {
        // TODO: Implement confirm() method.
    }

    /**
     * @param Payment $payment
     * @param $refundableSum
     * @return void
     */
    public function refund(Payment $payment, $refundableSum)
    {
        // TODO: Implement refund() method.
    }

    /**
     * @return array
     */
    public static function getHandlerModeList(): array
    {
        return [
            'EP'=>Loc::getMessage('AWZ_ARTPAY_HANDLER_PS_EP'),
            'ER'=>Loc::getMessage('AWZ_ARTPAY_HANDLER_PS_ER')
        ];
    }

    /**
     * @return bool
     */
    public function isRefundableExtended(): bool
    {
        $whiteList = array('ER', 'EP', 'CD');
        return in_array($this->service->getField('PS_MODE'), $whiteList);
    }

    /**
     * @param Payment $payment
     * @return bool
     */
    protected function isTestMode(Payment $payment = null): bool
    {
        return ($this->getBusinessValue($payment, 'PS_IS_TEST') == 'Y');
    }

    /**
     * @return string[]
     */
    protected function getUrlList(): array
    {
        return [
            'createInvoice'=>$this->isTestMode() ? static::TEST_URL : static::ACTIVE_URL,
            //'confirm'=>[],
            //'cancel'=>[],
            //'return'=>[]
        ];
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $params
     * @param array $headers
     * @return ServiceResult
     */
    private function send(string $method, string $url,
                          array $params = [], array $headers = []
    ): ServiceResult
    {
        $result = new ServiceResult();

        $httpClient = new HttpClient();
        $httpClient->disableSslVerification();
        foreach ($headers as $name => $value)
        {
            $httpClient->setHeader($name, $value);
        }

        PaySystem\Logger::addDebugInfo(__CLASS__.': request url: '.$url);

        if ($method === HttpClient::HTTP_GET)
        {
            $response = $httpClient->get($url);
        }
        else
        {
            $postData = null;
            if ($params)
            {
                $postData = self::encode($params);
            }

            PaySystem\Logger::addDebugInfo(__CLASS__.': request data: '.$postData);

            $response = $httpClient->query($method, $url, $postData);
            if ($response)
            {
                $response = $httpClient->getResult();
            }
        }

        if ($response === false)
        {
            $errors = $httpClient->getError();
            foreach ($errors as $code => $message)
            {
                $result->addError(PaySystem\Error::create($message, $code));
            }

            return $result;
        }

        PaySystem\Logger::addDebugInfo(__CLASS__.': response data: '.$response);

        $response = self::decode($response);
        if ($response === false)
        {
            return $result->addError(PaySystem\Error::create(
                Loc::getMessage('AWZ_ARTPAY_HANDLER_PS_RESPONSE_ERROR')
            ));
        }

        $result->setData($response);

        return $result;
    }

    /**
     * @param array $response
     * @return ServiceResult
     */
    private function verifyResponse(array $response): ServiceResult
    {
        $result = new ServiceResult();

        if (!empty($response['ap_status']) && $response['ap_status'] == 'Error')
        {
            $result->addError(PaySystem\Error::create($response['ap_result_text']));
        }

        return $result;
    }

    /**
     * @param array $data
     * @return mixed
     */
    private static function encode(array $data)
    {
        return Json::encode($data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param string $data
     * @return mixed
     */
    private static function decode($data)
    {
        try
        {
            return Json::decode($data);
        }
        catch (\Bitrix\Main\ArgumentException $exception)
        {
            return false;
        }
    }

    /**
     * @param Payment $payment
     * @param Request $request
     * @return bool
     */
    private function verifyRequest(Payment $payment, Request $request): bool
    {

        static $verify;
        if (is_null($verify)){
            $verify = false;
            try{
                $jsonData = self::getJsonData($request);

                $addSignature = $jsonData['ap_signature'];
                unset($jsonData['ap_signature']);

                uksort($jsonData, 'strnatcmp');
                $string =  implode(';', $jsonData) . ';' . trim($this->getBusinessValue($payment, 'KEY2'));
                if(hash("sha512", $string) == $addSignature){
                    $verify = true;
                }
            }catch (\Exception $e){
                PaySystem\ErrorLog::add(array(
                    'ACTION' => "verifyRequest",
                    'MESSAGE' => $e->getMessage()
                ));
            }
        }
        return $verify;
    }
}
