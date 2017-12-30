<!DOCTYPE html>
<html>
<head>
    <?php
    require_once("./carta.php");
    ?>

    <title>Error Logs</title>

</head>

<body>
<?php include_once($_SERVER['DOCUMENT_ROOT']."/analyticstracking.php") ?>
<div class="row text-center">
    <h3>Error logs as of <b><?php echo date(CUSTOM_DATE_FORMAT, time()); ?></b></h3>
</div>

<?php
try
{
    $logs = getErrorLogs();
}
catch (Exception $exc)
{
    echo "<div class='row text-center'><p>Error logs could not be retrieved.</p>";
    echo "<p>Message \"".$exc->getMessage()."\"</p> </div>";

    if (logException($exc) == false)
    {
        echo "<div class='row text-center'><p>Even error logging failed!</p></div>";
    }

    goto phpend;
}

$logCount = count($logs);

if ($logCount == 0)
{
    echo "<p>No error occured so far :)</p>";
}

echo <<<_HTML
<div class="error-logs-div-wrapper">
<div class="error-logs-div">
<table class="text-center">
<thead>
<tr>
    <th>Error ID</th>
    <th>Message</th>
    <th>Exception message</th>
    <th>When?</th>
    <th>File</th>
    <th>Line</th>
    <th>PHP Error Message</th>
    <th>Trace</th>
    <th>IP</th>
</tr>
</thead>
<tbody>
_HTML;

for ($counter = 0; $counter < $logCount; ++$counter)
{
    echo "<tr>";

    echo "<td>".$logs[$counter]['ID']."</td>";
    echo "<td>".$logs[$counter]['MESSAGE']."</td>";
    echo "<td>".$logs[$counter]['EXCEPTION_MESSAGE']."</td>";
    echo "<td>".date(CUSTOM_DATE_FORMAT, $logs[$counter]['LOG_TIME'] + UTC_TO_TURKEY)."</td>";
    echo "<td>".$logs[$counter]['FILE']."</td>";
    echo "<td>".$logs[$counter]['LINE']."</td>";
    echo "<td>".$logs[$counter]['PHP_ERROR_MESSAGE']."</td>";
    echo "<td>".$logs[$counter]['TRACE']."</td>";
    echo "<td>".$logs[$counter]['IP']."</td>";

    echo "</tr>";
}

echo "</tbody></table></div></div>";

phpend:
?>

<script>
    $(document).foundation();
</script>

</body>
</html>