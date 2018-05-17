<?php

use Carbon\Carbon;

$loader = require __DIR__ . '/../vendor/autoload.php';

$teamName = $_GET['teamName'];
$issueFileName = __DIR__ . '/../app/' . $teamName . '.json';

if (!file_exists($issueFileName)) {
    throw new Exception("Can't load issue JSON! (" . $issueFileName . ")");
}

$issues = json_decode(file_get_contents($issueFileName));

if (json_last_error() !== JSON_ERROR_NONE) {
    throw new Exception("Error parsing JSON! (" . json_last_error_msg() . ")");
}

$fileStats = stat($issueFileName);

$fileModTime = ($fileStats !== false) ? Carbon::createFromTimestamp($fileStats['mtime']) : null;

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= ucwords($teamName); ?> Issue Manager</title>
    <link rel="stylesheet" href="node_modules/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="node_modules/datatables-bootstrap/css/dataTables.bootstrap.min.css">
    <script src="node_modules/jquery/dist/jquery.min.js"></script>
    <script src="node_modules/bootstrap/dist/js/bootstrap.min.js"></script>
    <script src="node_modules/datatables/media/js/jquery.dataTables.min.js"></script>
    <script src="node_modules/datatables-bootstrap/js/dataTables.bootstrap.min.js"></script>
</head>
<body>

<div class="container">

    <div class="page-header">
        <h1>
            <?= ucwords($teamName); ?>
            <?php if ($fileModTime !== null) : ?>
                <small style="float: right; font-size: 13px;" title="<?= $fileModTime->format('c'); ?>">Updated <?= $fileModTime->diffForHumans(); ?></small>
            <?php endif; ?>
        </h1>
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
                <td data-order="<?= $createdOn->timestamp; ?>" title="<?= $createdOn->format(DateTime::RFC1123); ?>">
                    <?= $createdOn->diffForHumans(); ?>
                </td>
                <td data-order="<?= $updatedOn->timestamp; ?>" title="<?= $updatedOn->format(DateTime::RFC1123); ?>">
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
                $(nTd).html('<a href="' + href + '" target="_blank">' + text + "</a>");
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
                    { "targets": 6, "searchable": false, "visible": false }
                ],
                "language": {
                    search: "_INPUT_",
                    searchPlaceholder: "Search"
                },
                initComplete: function () {
                    this.api().columns(':not([data-noFilter])').every(function () {
                        var column = this;
                        var options = column.data().unique().sort();

                        // Dont show a dropdown filter if there is only a single option
                        if (options.length === 1) {
                            return;
                        }

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

                        options.each(function (d, j) {
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
