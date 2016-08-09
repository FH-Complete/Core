<?php

$I = new ApiTester($scenario);
$I->wantTo("Test API call v1/organisation/Lehrverband/Lehrverband");
$I->amHttpAuthenticated("admin", "1q2w3");
$I->haveHttpHeader("FHC-API-KEY", "testapikey@fhcomplete.org");

$I->sendGET("v1/organisation/Lehrverband/Lehrverband", array("gruppe" => "0", "verband" => "0", "semester" => "0", "studiengang_kz" => "0"));
$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();
$I->seeResponseContainsJson(["error" => 0]);
$I->wait();