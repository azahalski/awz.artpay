<?php
use Bitrix\Main\Loader,
    Bitrix\Main\Localization\Loc,
    Bitrix\Sale\PaySystem;


Loc::loadMessages(__FILE__);

$description = array(
    'RETURN' => Loc::getMessage('AWZ_ARTPAY_HANDLER_RETURN'),
    'RESTRICTION' => Loc::getMessage('AWZ_ARTPAY_HANDLER_RESTRICTION'),
    'COMMISSION' => Loc::getMessage('AWZ_ARTPAY_HANDLER_COMMISSION'),
    'MAIN' => Loc::getMessage('AWZ_ARTPAY_HANDLER_DESCRIPTION')
);

if (IsModuleInstalled('bitrix24'))
{
    $description['REFERRER'] = Loc::getMessage('AWZ_ARTPAY_HANDLER_REFERRER');
}

$isAvailable = PaySystem\Manager::HANDLER_AVAILABLE_TRUE;

$portalZone = Loader::includeModule('intranet') ? CIntranetUtils::getPortalZone() : "";
$licensePrefix = Loader::includeModule('bitrix24') ? \CBitrix24::getLicensePrefix() : "";

if (Loader::includeModule('bitrix24'))
{
    if ($licensePrefix !== 'by')
    {
        $isAvailable = PaySystem\Manager::HANDLER_AVAILABLE_FALSE;
    }
}
elseif (Loader::includeModule('intranet') && $portalZone !== 'by')
{
    $isAvailable = PaySystem\Manager::HANDLER_AVAILABLE_FALSE;
}
$isAvailable = PaySystem\Manager::HANDLER_AVAILABLE_TRUE;
//print_r(CIntranetUtils::getPortalZone());
//die();

$data = [
    'NAME' => Loc::getMessage('AWZ_ARTPAY_HANDLER_NAME'),
    'SORT' => 500,
    'IS_AVAILABLE' => $isAvailable,
    'CODES' => [
        "PS_IS_TEST" => array(
            "NAME" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_IS_TEST"),
            'SORT' => 100,
            'GROUP' => 'GENERAL_SETTINGS',
            'DEFAULT' => ['PROVIDER_KEY'=>'INPUT','PROVIDER_VALUE'=>'Y'],
            "INPUT" => array(
                'TYPE' => 'Y/N'
            )
        ),
        "USER" => array(
            "NAME" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_USER"),
            "DESCRIPTION" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_USER_DESC"),
            'SORT' => 200,
            'GROUP' => 'GENERAL_SETTINGS',
        ),
        "SERVICE_NO" => array(
            "NAME" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_SERVICE_NO"),
            "DESCRIPTION" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_SERVICE_NO_DESC"),
            'SORT' => 204,
            'GROUP' => 'GENERAL_SETTINGS',
        ),
        "KEY1" => array(
            "NAME" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_KEY1"),
            "DESCRIPTION" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_KEY1R_DESC"),
            'SORT' => 210,
            'GROUP' => 'GENERAL_SETTINGS',
        ),
        "KEY2" => array(
            "NAME" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_KEY1"),
            "DESCRIPTION" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_KEY1_DESC"),
            'SORT' => 220,
            'GROUP' => 'GENERAL_SETTINGS',
        ),
        "PAYMENT_SHOULD_PAY" => array(
            "NAME" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_SHOULD_PAY"),
            'SORT' => 600,
            'GROUP' => 'PAYMENT',
            'DEFAULT' => array(
                'PROVIDER_KEY' => 'PAYMENT',
                'PROVIDER_VALUE' => 'SUM'
            )
        ),
        "PAYMENT_SHOULD_PAY_CURRENCY" => array(
            "NAME" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_PAYMENT_SHOULD_PAY_CURRENCY"),
            'SORT' => 610,
            'GROUP' => 'PAYMENT',
            'DEFAULT' => array(
                'PROVIDER_KEY' => 'PAYMENT',
                'PROVIDER_VALUE' => 'CURRENCY'
            )
        ),
        "PAYMENT_ID" => array(
            "NAME" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_PAYMENT_ID"),
            'SORT' => 400,
            'GROUP' => 'PAYMENT',
            'DEFAULT' => array(
                'PROVIDER_KEY' => 'PAYMENT',
                'PROVIDER_VALUE' => 'ID'
            )
        ),
        "PAYMENT_DESC" => array(
            "NAME" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_PAYMENT_DESC"),
            "DESCRIPTION" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_PAYMENT_DESC_DESC"),
            'SORT' => 500,
            'GROUP' => 'PAYMENT',
            'DEFAULT' => array(
                'PROVIDER_KEY' => 'VALUE'
            )
        ),
        "PAYMENT_SROK" => array(
            "NAME" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_PAYMENT_SROK"),
            "DESCRIPTION" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_PAYMENT_SROK_DESC"),
            'SORT' => 530,
            'GROUP' => 'PAYMENT',
            'DEFAULT' => array(
                'PROVIDER_KEY' => 'VALUE'
            )
        ),
        "INSTRUCTION" => array(
            "NAME" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_PAYMENT_INSTRUCTION"),
            "DESCRIPTION" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_PAYMENT_INSTRUCTION_DESC"),
            'SORT' => 530,
            'GROUP' => 'GENERAL_SETTINGS',
            'DEFAULT' => array(
                'PROVIDER_KEY' => 'VALUE'
            )
        ),
        "SHOW_LINK" => array(
            "NAME" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_PAYMENT_SHOW_LINK"),
            "DESCRIPTION" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_PAYMENT_SHOW_LINK_DESC"),
            'SORT' => 535,
            'GROUP' => 'GENERAL_SETTINGS',
            'DEFAULT' => ['PROVIDER_KEY'=>'INPUT','PROVIDER_VALUE'=>'Y'],
            "INPUT" => array(
                'TYPE' => 'Y/N'
            )
        ),
        "SHOW_QR" => array(
            "NAME" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_PAYMENT_SHOW_QR"),
            "DESCRIPTION" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_PAYMENT_SHOW_QR_DESC"),
            'SORT' => 540,
            'GROUP' => 'GENERAL_SETTINGS',
            'DEFAULT' => ['PROVIDER_KEY'=>'INPUT','PROVIDER_VALUE'=>'Y'],
            "INPUT" => array(
                'TYPE' => 'Y/N'
            )
        ),
    ]
];