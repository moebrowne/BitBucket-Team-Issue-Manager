<?php

use Carbon\Carbon;

$loader = require 'vendor/autoload.php';

$teamName = $_GET['teamName'];
$issueFileName = $teamName . '.json';

if (!file_exists($issueFileName)) {
    die("Can't load issue JSON file: " . $issueFileName);
}

$issues = json_decode(file_get_contents($issueFileName));

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= ucwords($teamName); ?> Issue Manager</title>
    <link rel="stylesheet" href="node_modules/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.12/css/dataTables.bootstrap.min.css">
    <script src="node_modules/jquery/dist/jquery.min.js"></script>
    <script src="node_modules/bootstrap/dist/js/bootstrap.min.js"></script>
    <script src="node_modules/datatables/media/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.12/js/dataTables.bootstrap.min.js"></script>
</head>
<body>

<div class="container">

    <div class="page-header">
        <h1><?= ucwords($teamName); ?></h1>
    </div>

    <table id="issues" class="table table-striped table-hover" width="100%" cellspacing="0">
        <thead>
        <tr>
            <th>Repo</th>
            <th data-noFilter>Title</th>
            <th>Type</th>
            <th>Priority</th>
            <th>Status</th>
            <th data-noFilter>Created</th>
            <th data-noFilter>Updated</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($issues as $issue) : ?>
            <?php

            // Dates
            $createdOn = Carbon::parse($issue->created_on);
            $updatedOn = Carbon::parse($issue->updated_on);


            // Set the row class
            $rowClass = '';
            switch($issue->priority) {
                case 'critical':
                    $rowClass = 'warning';
                    break;
                case 'blocker':
                    $rowClass = 'danger';
                    break;
            }

            switch($issue->state) {
                case 'resolved':
                    $rowClass = 'success';
                    break;
            }

            // Alias the New state to Open
            $state = ($issue->state === 'new') ? 'open':$issue->state;

            ?>
            <tr class="<?= $rowClass; ?>">
                <td data-href="<?= $issue->repository->links->html->href; ?>" data-icon="<?= $issue->repository->links->avatar->href; ?>">
                    <?= $issue->repository->name; ?>
                </td>
                <td data-href="<?= $issue->links->html->href; ?>">
                    <?= $issue->title; ?>
                </td>
                <td><?= $issue->kind; ?></td>
                <td><?= $issue->priority; ?></td>
                <td><?= $state; ?></td>
                <td data-order="<?= $createdOn->timestamp; ?>">
                    <?= $createdOn->diffForHumans(); ?>
                </td>
                <td data-order="<?= $updatedOn->timestamp; ?>">
                    <?= $updatedOn->diffForHumans(); ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <script>

        function datatablesCellRender(nTd) {
            var href = $(nTd).data('href');
            var icon = $(nTd).data('icon');
            var text = $(nTd).text();

            var hasLink = (typeof href !== 'undefined' && href !== '');
            var hasIcon = (typeof icon !== 'undefined' && icon !== '');

            if (hasLink === true) {
                $(nTd).html('<a href="' + href + '">' + text + "</a>");
            }

            if (hasIcon) {
                var targetElement = (hasLink === true) ? $(nTd).children('a'):$(nTd);
                targetElement.prepend('<img src="' + icon + '" width="16" class="img-circle">&nbsp;');
            }
        }

        $(document).ready(function () {
            $('#issues').DataTable({
                "order": [[ 3, "asc" ]],
                "pageLength": 25,
                "columnDefs": [
                    { "targets": 0, "fnCreatedCell": datatablesCellRender },
                    { "targets": 1, "fnCreatedCell": datatablesCellRender },
                    { "targets": 5, "searchable": false },
                    { "targets": 6, "searchable": false }
                ],
                initComplete: function () {
                    this.api().columns(':not([data-noFilter])').every(function () {
                        var column = this;

                        var select = $('<select><option value="">Any ' + $(column.header()).text() + '</option></select>')
                            .appendTo($(column.header()).empty())
                            .on('click', function(e) {
                                e.stopPropagation();
                            })
                            .on('change', function () {
                                var val = $.fn.dataTable.util.escapeRegex(
                                    $(this).val()
                                );

                                column
                                    .search(val ? '^' + val + '$' : '', true, false)
                                    .draw();
                            });

                        column.data().unique().sort().each(function (d, j) {
                            select.append('<option value="' + d + '">' + d + '</option>')
                        });
                    });

                    // Set the default filtering
                    this.api().columns([4]).every(function () {
                        var column = this;

                        $(column.header()).children('select').val('open').trigger('change');
                    });
                }
            });
        });
    </script>
</div>


</body>
</html>
