<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!check_bitrix_sessid()) die();

global $APPLICATION;

$APPLICATION->ShowAjaxHead();

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

$dealId = $request->get('PARAMS')['deal_id'] ? $request->get('PARAMS')['deal_id'] : $request->get('deal_id');

$APPLICATION->IncludeComponent(
    'sultanov.efusion:custom_deal_tab.grid',
    '',
    [
        'DEAL_ID' => $dealId,
    ]
);

\CMain::FinalActions();