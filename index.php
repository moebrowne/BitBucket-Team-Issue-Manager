<?php

use Carbon\Carbon;

$loader = require 'vendor/autoload.php';

$issues = json_decode(file_get_contents('issues.json'));

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Team Issue Manager</title>
    <link type="text/css" href="https://cdn.datatables.net/1.10.12/css/jquery.dataTables.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-1.12.3.js"></script>
    <script src="https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js"></script>
</head>
<body>
<table id="issues" class="display" width="100%" cellspacing="0">
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

        ?>
        <tr>
            <td><?= $issue->repository->name; ?></td>
            <td><?= $issue->title; ?></td>
            <td><?= $issue->kind; ?></td>
            <td><?= $issue->priority; ?></td>
            <td><?= $issue->state; ?></td>
            <td data-order="<?= $createdOn->timestamp; ?>">
                <?= $createdOn->diffForHumans(); ?>
            </td>
            <td data-order="<?= $updatedOn->timestamp; ?>">
                <?= $updatedOn->diffForHumans(); ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
        <th>Repo</th>
        <th>Title</th>
        <th>T</th>
        <th>P</th>
        <th>Status</th>
        <th>Created</th>
        <th>Updated</th>
    </tfoot>
</table>

<script>
    $(document).ready(function () {
        $('#issues').DataTable({
            initComplete: function () {
                this.api().columns().every(function () {
                    var column = this;

                    var select = $('<select><option value=""></option></select>')
                        .appendTo($(column.footer()).empty())
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

</body>
</html>
