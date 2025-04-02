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
        "PS_IS_TEST" => [
            "NAME" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_IS_TEST"),
            'SORT' => 100,
            'GROUP' => 'GENERAL_SETTINGS',
            'DEFAULT' => ['PROVIDER_KEY'=>'INPUT','PROVIDER_VALUE'=>'Y'],
            "INPUT" => [
                'TYPE' => 'Y/N'
            ]
        ],
        "USER" => [
            "NAME" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_USER"),
            "DESCRIPTION" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_USER_DESC"),
            'SORT' => 200,
            'GROUP' => 'GENERAL_SETTINGS',
            'DEFAULT' => [
                'PROVIDER_VALUE' => '600100',
                'PROVIDER_KEY' => 'VALUE'
            ]
        ],
        "SERVICE_NO" => [
            "NAME" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_SERVICE_NO"),
            "DESCRIPTION" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_SERVICE_NO_DESC"),
            'SORT' => 204,
            'GROUP' => 'GENERAL_SETTINGS',
            'DEFAULT' => [
                'PROVIDER_VALUE' => '45',
                'PROVIDER_KEY' => 'VALUE'
            ]
        ],
        "KEY1" => [
            "NAME" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_KEY1"),
            "DESCRIPTION" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_KEY1_DESC"),
            'SORT' => 210,
            'GROUP' => 'GENERAL_SETTINGS',
            'DEFAULT' => [
                'PROVIDER_VALUE' => 'EJvay6nrJ',
                'PROVIDER_KEY' => 'VALUE'
            ]
        ],
        "KEY2" => [
            "NAME" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_KEY2"),
            "DESCRIPTION" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_KEY2_DESC"),
            'SORT' => 220,
            'GROUP' => 'GENERAL_SETTINGS',
            'DEFAULT' => [
                'PROVIDER_VALUE' => 'YWUyec4fa',
                'PROVIDER_KEY' => 'VALUE'
            ]
        ],
        "PAYMENT_SHOULD_PAY" => [
            "NAME" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_SHOULD_PAY"),
            'SORT' => 600,
            'GROUP' => 'PAYMENT',
            'DEFAULT' => [
                'PROVIDER_KEY' => 'PAYMENT',
                'PROVIDER_VALUE' => 'SUM'
            ]
        ],
        "PAYMENT_SHOULD_PAY_CURRENCY" => [
            "NAME" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_PAYMENT_SHOULD_PAY_CURRENCY"),
            'SORT' => 610,
            'GROUP' => 'PAYMENT',
            'DEFAULT' => [
                'PROVIDER_KEY' => 'PAYMENT',
                'PROVIDER_VALUE' => 'CURRENCY'
            ]
        ],
        "PAYMENT_ID" => [
            "NAME" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_PAYMENT_ID"),
            'SORT' => 400,
            'GROUP' => 'PAYMENT',
            'DEFAULT' => [
                'PROVIDER_KEY' => 'PAYMENT',
                'PROVIDER_VALUE' => 'ID'
            ]
        ],
        "PAYMENT_DESC" => [
            "NAME" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_PAYMENT_DESC"),
            "DESCRIPTION" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_PAYMENT_DESC_DESC"),
            'SORT' => 500,
            'GROUP' => 'PAYMENT',
            'DEFAULT' => [
                'PROVIDER_KEY' => 'VALUE',
                'PROVIDER_VALUE'=>Loc::getMessage('AWZ_ARTPAY_HANDLER_PARAM_PAYMENT_DESC_DESC_VAL')
            ]
        ],
        "PAYMENT_SROK" => [
            "NAME" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_PAYMENT_SROK"),
            "DESCRIPTION" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_PAYMENT_SROK_DESC"),
            'SORT' => 530,
            'GROUP' => 'PAYMENT',
            'DEFAULT' => [
                'PROVIDER_KEY' => 'VALUE',
                'PROVIDER_VALUE'=>'+3days'
            ]
        ],
        "INSTRUCTION" => [
            "NAME" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_PAYMENT_INSTRUCTION"),
            "DESCRIPTION" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_PAYMENT_INSTRUCTION_DESC"),
            'SORT' => 530,
            'GROUP' => 'GENERAL_SETTINGS',
            'DEFAULT' => [
                'PROVIDER_KEY' => 'VALUE'
            ]
        ],
        "SHOW_LINK" => [
            "NAME" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_PAYMENT_SHOW_LINK"),
            "DESCRIPTION" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_PAYMENT_SHOW_LINK_DESC"),
            'SORT' => 535,
            'GROUP' => 'GENERAL_SETTINGS',
            'DEFAULT' => ['PROVIDER_KEY'=>'INPUT','PROVIDER_VALUE'=>'Y'],
            "INPUT" => [
                'TYPE' => 'Y/N'
            ]
        ],
        "SHOW_QR" => [
            "NAME" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_PAYMENT_SHOW_QR"),
            "DESCRIPTION" => Loc::getMessage("AWZ_ARTPAY_HANDLER_PARAM_PAYMENT_SHOW_QR_DESC"),
            'SORT' => 540,
            'GROUP' => 'GENERAL_SETTINGS',
            'DEFAULT' => ['PROVIDER_KEY'=>'INPUT','PROVIDER_VALUE'=>'Y'],
            "INPUT" => [
                'TYPE' => 'Y/N'
            ]
        ],
    ]
];