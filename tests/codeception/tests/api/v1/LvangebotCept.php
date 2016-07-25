<?php

$I = new ApiTester($scenario);
$I->wantTo("Test API call v1/education/Lvangebot/Lvangebot");
$I->amHttpAuthenticated("admin", "1q2w3");
$I->haveHttpHeader("FHC-API-KEY", "testapikey@fhcomplete.org");

$I->sendGET("v1/education/Lvangebot/Lvangebot", array("lvangebot_id" => "0"));
$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();
$I->seeResponseContainsJson(["error" => 0]);