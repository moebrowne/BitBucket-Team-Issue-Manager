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
                <div class="col-md-12" style="line-height: 24px;">
                    <h2>Type</h2>
                    <span data-facet-name="kind" data-facet-value="bug" class="label label-success">bug</span>
                    <span data-facet-name="kind" data-facet-value="enhancement" class="label label-success">enhancement</span>
                    <span data-facet-name="kind" data-facet-value="task" class="label label-success">task</span>
                    <span data-facet-name="kind" data-facet-value="proposal" class="label label-success">proposal</span>
                </div>
            </div>
			<div class="row">
				<div class="col-md-12" style="line-height: 24px;">
					<hr>
					<h2>Priority</h2>
					<span data-facet-name="priority" data-facet-value="blocker" class="label label-success">blocker</span>
					<span data-facet-name="priority" data-facet-value="critical" class="label label-success">critical</span>
					<span data-facet-name="priority" data-facet-value="major" class="label label-success">major</span>
					<span data-facet-name="priority" data-facet-value="minor" class="label label-success">minor</span>
					<span data-facet-name="priority" data-facet-value="trivial" class="label label-success">trivial</span>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12" style="line-height: 24px;">
					<hr>
					<h2>Status</h2>
					<span data-facet-name="state" data-facet-value="open" class="label label-success">open</span>
					<span data-facet-name="state" data-facet-value="closed" class="label label-default">closed</span>
					<span data-facet-name="state" data-facet-value="resolved" class="label label-default">resolved</span>
					<span data-facet-name="state" data-facet-value="invalid" class="label label-default">invalid</span>
					<span data-facet-name="state" data-facet-value="wontfix" class="label label-default">wontfix</span>
					<span data-facet-name="state" data-facet-value="onhold" class="label label-default">onhold</span>
					<span data-facet-name="state" data-facet-value="duplicate" class="label label-default">duplicate</span>
				</div>
			</div>
            <div class="row">
                <div class="col-md-12" style="line-height: 24px;">
                    <hr>
                    <h2>Repo</h2>
                    <?php foreach ($searchIndex->searchForFacetValues('repository.name', '*')['facetHits'] as $facet) : ?>
                        <span data-facet-name="repository.name" data-facet-value="<?= $facet['value'] ?>" class="label label-success"><?= $facet['value'] ?></span>
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
					<th>Updated</th>
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

            console.log(facets);

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
						'<td>' + hitData.repository.name + '</td>' +
						'<td>' + hitData.title + '</td>' +
						'<td>' + hitData.kind + '</td>' +
						'<td>' + hitData.priority + '</td>' +
						'<td>' + hitData.state + '</td>' +
						'</tr>'
				);
				$('#issues tbody').append(hit)
			}
		});

	}

    $('#search').on('keyup', doSearch);
    doSearch();

</script>

</body>
</html>
