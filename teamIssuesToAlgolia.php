<?php

use Bitbucket\API\Http\Response\Pager;

$loader = require __DIR__ . '/vendor/autoload.php';

require_once  __DIR__ . '/auth.php';

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

echo 'Found ' . count($teamRepositories->values) . ' repositories' . PHP_EOL;

$issues = [];
foreach ($teamRepositories->values as $teamRepository) {

    $teamRepositorySlug = str_replace($team . '/', '', $teamRepository->full_name);

    $repoIssues = $bitbucket->api('Repositories\Issues');
    $response = $repoIssues->all($team, $teamRepositorySlug);

    $responseContentArray = json_decode($response->getContent(), true);

    if (array_key_exists('type', $responseContentArray) && $responseContentArray['type'] === 'error') {
        echo $teamRepository->full_name . ': ' . $responseContentArray['error']['message'] . PHP_EOL;
        continue;
    }

    $page = new Pager($repoIssues->getClient(), $response);

    $teamIssues = json_decode($page->fetchAll()->getContent());

    echo $teamRepository->full_name . ': Found ' . count($teamIssues->values) . ' issue(s)' . PHP_EOL;

    foreach ($teamIssues->values as $teamIssue) {
        if ($teamIssue->state === 'new') {
            $teamIssue->state = 'open';
        }

        $issueSearchData = [
            'title' => $teamIssue->title,
            'content' => $teamIssue->content->raw,
            'priority' => $teamIssue->priority,
            'kind' => $teamIssue->kind,
            'state' => $teamIssue->state,
            'component' => $teamIssue->component,
            'assignee' => $teamIssue->assignee,
            'reporter' => $teamIssue->reporter,
            'repository' => $teamIssue->repository,
            'edited_on' => $teamIssue->edited_on,
            'created_on' => $teamIssue->created_on,
        ];

        $searchIndex->addObject($issueSearchData, $teamIssue->repository->full_name . '-' . $teamIssue->id);
    }

}

echo 'Found ' . count($issues) . ' issues total' . PHP_EOL;
