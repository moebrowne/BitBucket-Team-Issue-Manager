<?php

use Bitbucket\API\Api;
use Bitbucket\API\Http\Listener\OAuthListener;

$credentials = json_decode(file_get_contents('auth.json'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    throw new Exception("ERROR: ".json_last_error_msg());
}

$bitbucket = new Api();
$bitbucket->getClient()->setApiVersion('2.0')->addListener(
    new OAuthListener($credentials)
);
