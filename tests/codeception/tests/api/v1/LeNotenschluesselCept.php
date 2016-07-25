<?php

$I = new ApiTester($scenario);
$I->wantTo("Test API call v1/education/LeNotenschluessel/LeNotenschluessel");
$I->amHttpAuthenticated("admin", "1q2w3");
$I->haveHttpHeader("FHC-API-KEY", "testapikey@fhcomplete.org");

$I->sendGET("v1/education/LeNotenschluessel/LeNotenschluessel", array("note" => "0", "lehreinheit_id" => "0"));
$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();
$I->seeResponseContainsJson(["error" => 0]);