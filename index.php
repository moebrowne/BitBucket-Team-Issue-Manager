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
            <th>Title</th>
            <th>T</th>
            <th>P</th>
            <th>Status</th>
            <th>Created</th>
            <th>Updated</th>
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

            ?>
            <tr class="<?= $rowClass; ?>">
                <td><?= $issue->repository->name; ?></td>
                <td><?= $issue->title; ?></td>
                <td><?= $issue->kind; ?></td>
                <td><?= $issue->priority; ?></td>
                <td <?= ($issue->state === 'new') ? 'data-order="open"':'' ?>>
                    <?= $issue->state; ?>
                </td>
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
        $(document).ready(function () {
            $('#issues').DataTable({
                "order": [[ 3, "asc" ]],
                "pageLength": 25,
                initComplete: function () {
                    this.api().columns().every(function () {
                        var column = this;

                        var select = $('<select><option value=""></option></select>')
                            .appendTo($(column.header()))
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
                }
            });
        });
    </script>
</div>


</body>
</html>
