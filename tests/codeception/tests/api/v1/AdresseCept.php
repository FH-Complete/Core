<?php

$I = new ApiTester($scenario);
$I->wantTo("Test API call v1/person/Adresse/Adresse");
$I->amHttpAuthenticated("admin", "1q2w3");
$I->haveHttpHeader("FHC-API-KEY", "testapikey@fhcomplete.org");

$I->sendGET("v1/person/Adresse/Adresse", array());
$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();
$I->seeResponseContainsJson(["error" => 0]);