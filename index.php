<?php

$issues = json_decode(file_get_contents('issues.json'));

?>

<!<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Team Issue Manager</title>
    <link type="text/css" href="https://cdn.datatables.net/1.10.12/css/jquery.dataTables.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-1.12.3.js"></script>
    <script src="https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js"></script>
</head>
<body>
<table id="example" class="display" width="100%" cellspacing="0">
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
        <tr>
            <td><?= $issue->repository->name; ?></td>
            <td><?= $issue->title; ?></td>
            <td><?= $issue->kind; ?></td>
            <td><?= $issue->priority; ?></td>
            <td><?= $issue->state; ?></td>
            <td><?= $issue->created_on; ?></td>
            <td><?= $issue->updated_on; ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<script>
    $(document).ready(function () {
        $('#example').DataTable();
    });
</script>

</body>
</html>
