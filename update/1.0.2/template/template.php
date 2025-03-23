<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\IO\File;

\Bitrix\Main\Page\Asset::getInstance()->addCss("/bitrix/themes/.default/sale.css");
$fileCss = new File($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/awz.artpay/template/style.css');

Loc::loadMessages(__FILE__);
$sum = round($params['PAYMENT_SHOULD_PAY'], 2);
?>
<?if($fileCss->isExists()){?><style><?=$fileCss->getContents()?></style><?}?>
<div class="mb-4 awz-artpay-handler">
    <p><?=Loc::getMessage('AWZ_ARTPAY_HANDLER_TMPL_DESCRIPTION')." <strong>".SaleFormatCurrency($params['PAYMENT_SHOULD_PAY'], $payment->getField('CURRENCY'))."</strong>";?></p>
    <?if($params['PS_MODE']=='EP'){?>
        <p><?= Loc::getMessage('AWZ_ARTPAY_HANDLER_TMPL_EPOS_INSTRUCTION',
                [
                    '#ACCOUNT_NUMBER#' => $params['ap_erip_invoice_id']
                ]
            ) ?></p>
        <?if($params['SHOW_LINK']=='Y' && isset($params['ap_epos_url']) && $params['ap_epos_url']){?>
            <p class="awz-artpay-handler-link">
                <?= Loc::getMessage('AWZ_ARTPAY_HANDLER_TMPL_EPOS_SHOW_LINK_INSTRUCTION',
                    [
                        '#LINK#' => $params['ap_epos_url']
                    ]
                ) ?>
            </p>
        <?}?>
        <?if($params['SHOW_QR']=='Y' && isset($params['ap_epos_qr']) && $params['ap_epos_qr']){?>
            <p class="awz-artpay-handler-qr">
                <?= Loc::getMessage('AWZ_ARTPAY_HANDLER_TMPL_EPOS_SHOW_QR_INSTRUCTION',
                    [
                        '#IMG#' => 'data:image/jpeg;base64,'.$params['ap_epos_qr']
                    ]
                ) ?>
            </p>
        <?}?>
    <?}else{?>
        <p><?= Loc::getMessage('AWZ_ARTPAY_HANDLER_TMPL_ERIP_INSTRUCTION',
                [
                    '#INSTRUCTION#' => $params['instruction'],
                    '#ACCOUNT_NUMBER#' => $params['ap_erip_invoice_id'],
                    '#ERIP_SERVICE_CODE#' => $params['ap_erip_service_no']
                ]
            ) ?></p>
    <?}?>

</div>
