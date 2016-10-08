<?php

$loader = require 'vendor/autoload.php';

require_once 'auth.php';

$team = $arg[1];

if (empty($team) === true) {
    throw new Exception('Team name must be specified: '.$argv[0].' team-name');
}

$teamRepositoryResponse = $bitbucket->api('Repositories')->all($team);
$teamRepositories = json_decode($teamRepositoryResponse->getContent());

/**
 * @param $team
 * @param $teamRepositorySlug
 * @param int $page
 * @return array
 */
function getAllIssues($team, $teamRepositorySlug, $page = 1) {

    global $bitbucket;

    $options = [
        'page' => $page,
    ];

    $teamIssuesResponse = $bitbucket->api('Repositories\Issues')->all($team, $teamRepositorySlug, $options);
    $teamIssues = json_decode($teamIssuesResponse->getContent());

    $issues = $teamIssues->values;

    $nextPageIssues = [];
    if ($teamIssues->size > $page*$teamIssues->pagelen) {
        $nextPageIssues = getAllIssues($team, $teamRepositorySlug, $page+1);
    }

    $issuesAll = array_merge($issues, $nextPageIssues);
    return $issuesAll;
}


$issues = [];
foreach ($teamRepositories->values as $teamRepository) {

    $teamRepositorySlug = str_replace($team . '/', '', $teamRepository->full_name);

    $teamIssues = getAllIssues($team, $teamRepositorySlug);

    foreach ($teamIssues as $teamIssue) {
        array_push($issues, $teamIssue);
    }

}

header('Content-Type: application/json');
$json = json_encode($issues);

echo $json;

file_put_contents($team . '.json', $json);
