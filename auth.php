<?php

use Bitbucket\API\Api;
use Bitbucket\API\Http\Listener\OAuthListener;

$authFileName = __DIR__ . '/auth.json';

if (!file_exists($authFileName)) {
    throw new Exception("ERROR: auth.json is missing.");
}

$credentials = json_decode(file_get_contents($authFileName), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    throw new Exception("ERROR: ".json_last_error_msg());
}

$bitbucket = new Api();
$bitbucket->getClient()->setApiVersion('2.0')->addListener(
    new OAuthListener($credentials)
);
