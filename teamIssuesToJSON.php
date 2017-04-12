<?php

use Bitbucket\API\Http\Response\Pager;

$loader = require 'vendor/autoload.php';

require_once 'auth.php';

$team = $argv[1];

if (empty($team) === true) {
    throw new Exception('Team name must be specified: '.$argv[0].' team-name');
}

$repoIssues = $bitbucket->api('Repositories');
$page = new Pager($repoIssues->getClient(), $repoIssues->all($team));

$teamRepositories = json_decode($page->fetchAll()->getContent());

if (json_last_error() !== JSON_ERROR_NONE) {
    throw new Exception("ERROR: ".json_last_error_msg());
}

$issues = [];
foreach ($teamRepositories->values as $teamRepository) {

    $teamRepositorySlug = str_replace($team . '/', '', $teamRepository->full_name);

    $repoIssues = $bitbucket->api('Repositories\Issues');
    $page = new Pager($repoIssues->getClient(), $repoIssues->all($team, $teamRepositorySlug));

    $teamIssues = json_decode($page->fetchAll()->getContent());

    foreach ($teamIssues->values as $teamIssue) {
        array_push($issues, $teamIssue);
    }

}

header('Content-Type: application/json');
$json = json_encode($issues);

echo $json;

file_put_contents($team . '.json', $json);
