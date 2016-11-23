<?php

$I = new ApiTester($scenario);
$I->wantTo("Test API call v1/content/Contentsprache/: Contentsprache");
$I->amHttpAuthenticated("admin", "1q2w3");
$I->haveHttpHeader("FHC-API-KEY", "testapikey@fhcomplete.org");
$I->sendGET("v1/content/Contentsprache/Contentsprache", array("contentsprache_id" => "1"));
$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();
$I->seeResponseContainsJson(["error" => 0]);
$I->wait();
