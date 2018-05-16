<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/auth.php';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Issue Manager</title>
    <link rel="stylesheet" href="node_modules/bootstrap/dist/css/bootstrap.min.css">
    <script src="node_modules/jquery/dist/jquery.min.js"></script>
    <script src="node_modules/bootstrap/dist/js/bootstrap.min.js"></script>
    <script src="node_modules/algoliasearch/dist/algoliasearch.min.js"></script>
    <script src="node_modules/moment/min/moment.min.js"></script>
    <style type="text/css">
        [data-facet-name] {
            cursor: pointer;
        }

        img.repoAvatar {
            max-width: 18px;
            border-radius: 50%;
        }
    </style>
</head>
<body>

<div class="container">

    <div class="page-header">
        <h1>TeamName</h1>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="input-group">
                <input id="search" type="text" class="form-control" placeholder="Search for issues">
                <span class="input-group-btn">
                    <button class="btn btn-primary" type="button"><span class="glyphicon glyphicon-search" aria-hidden="true"></span></button>
                </span>
            </div>
        </div>
    </div>

    <div id="facets" class="row">
        <div class="col-md-3">
            <div class="row">
                <div class="facet-group facet-kind col-md-12" style="line-height: 24px;">
                    <h2>Type</h2>
                    <?php foreach ($searchIndex->searchForFacetValues('kind', '*')['facetHits'] as $facet) : ?>
                        <span data-facet-name="kind" data-facet-value="<?= $facet['value'] ?>" class="label label-default"><?= $facet['value'] ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="row">
                <div class="facet-group facet-priority col-md-12" style="line-height: 24px;">
                    <hr>
                    <h2>Priority</h2>
                    <?php foreach ($searchIndex->searchForFacetValues('priority', '*')['facetHits'] as $facet) : ?>
                        <span data-facet-name="priority" data-facet-value="<?= $facet['value'] ?>" class="label label-default"><?= $facet['value'] ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="row">
                <div class="facet-group facet-status col-md-12" style="line-height: 24px;">
                    <hr>
                    <h2>Status</h2>
                    <?php foreach ($searchIndex->searchForFacetValues('state', '*')['facetHits'] as $facet) : ?>
                        <span data-facet-name="state" data-facet-value="<?= $facet['value'] ?>" class="label label-default"><?= $facet['value'] ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="row">
                <div class="facet-group facet-repoName col-md-12" style="line-height: 24px;">
                    <hr>
                    <h2>Repo</h2>
                    <?php foreach ($searchIndex->searchForFacetValues('repository.name', '*')['facetHits'] as $facet) : ?>
                        <span data-facet-name="repository.name" data-facet-value="<?= $facet['value'] ?>" class="label label-default"><?= $facet['value'] ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="col-md-9">
            <table id="issues" class="table table-striped table-hover" width="100%" cellspacing="0">
                <thead>
                <tr>
                    <th>Repo</th>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Created</th>
                </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    var client = algoliasearch("ZZ283W0ONK", "a1d504c7811feee4c48d2eb22ae6e4ac");
    var index = client.initIndex('BITBUCKET ISSUE');

    var facets = {};

    function populateFacetArray() {

        facets = {};

        let selectedFacets = $('#facets [data-facet-name].label-success');

        selectedFacets.each(function (index, facet) {
            facet = $(facet);

            let facetName = facet.attr('data-facet-name');
            let facetValue = facet.attr('data-facet-value');

            if (typeof facets[facetName] === 'undefined') {
                facets[facetName] = [];
            }

            facets[facetName].push(facetValue);

            if (facetValue === 'open') {
                facets[facetName].push('new');
            }
        });
    }

    $('#facets').on('click', '[data-facet-name]', function (e) {

        let facet = $(this);
        let facetName = facet.attr('data-facet-name');
        let facetValue = facet.attr('data-facet-value');

        if (typeof facets[facetName] !== 'undefined' && facets[facetName].indexOf(facetValue) !== -1) {
            facet.addClass('label-default').removeClass('label-success');
        }
        else {
            facet.addClass('label-success').removeClass('label-default');
        }

        doSearch();
    });

    function doSearch() {
        populateFacetArray();

        let box = $('#search');

        let filterStrings = [];

        for (var facetName in facets) {

            let facetTypeValues = [];

            for (var facetValueIndex in facets[facetName]) {
                facetTypeValues.push(facetName + ':' + facets[facetName][facetValueIndex])
            }

            if (facetTypeValues.length > 0) {
                filterStrings.push(facetTypeValues.join(' OR '))
            }
        }

        let filterString = '';
        if (filterStrings.length > 0) {
            filterString = '(' + filterStrings.join(') AND (') + ')';
        }

        // with params
        index.search(box.val(), {
            filters: filterString,
            hitsPerPage: 50
        }, function searchDone(err, content) {
            if (err) {
                console.error(err);
                return;
            }

            $('#issues tbody').html('');

            for (var h in content.hits) {
                var hitData = content.hits[h];

                let hit = $(
                    '<tr>' +
                        '<td><img class="repoAvatar" src="' + hitData.repository.links.avatar.href + '"> ' + hitData.repository.name + '</td>' +
                        '<td>' + hitData.title + '</td>' +
                        '<td>' + hitData.kind + '</td>' +
                        '<td>' + hitData.priority + '</td>' +
                        '<td>' + hitData.state + '</td>' +
                        '<td>' + moment(hitData.created_on).fromNow() + '</td>' +
                    '</tr>'
                );
                $('#issues tbody').append(hit)
            }
        });

    }

    function setFacetDefaults() {
        $('.facet-kind span, .facet-priority span, .facet-repoName span').removeClass('label-default').addClass('label-success');
        $('[data-facet-name="state"][data-facet-value="open"]').removeClass('label-default').addClass('label-success');
    }

    $('#search').on('keyup', doSearch);
    setFacetDefaults();
    doSearch();

</script>

</body>
</html>
