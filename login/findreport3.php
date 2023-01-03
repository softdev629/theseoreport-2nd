<?PHP
session_start();

include("includes/common.php");
include("includes/database.php");
include("includes/init-session.php");

$pid = $_GET['pid'];
$cid = $_SESSION['UID'];
if ($pid == '') {
  exit;
}

$sql = mysqli_query($link, "select * from rl_projects where id='" . $pid . "'");
$sql_data = mysqli_fetch_array($sql);

// Gets data from advanced web ranking site API

$projectName = $sql_data['projectName'] . '.com';
$token = $awr_api_token;

// Gets all dates of this project
$url = "https://api.awrcloud.com/v2/get.php?action=get_dates&token=" . $token . "&project=" . $projectName;
$response = json_decode(file_get_contents($url), true);
$dates = $response["details"]["dates"]; // date and search depth arrays
?>

<div class="row text-center">
  <div class="col-md-4 bg-success" style="padding: 15px;">
    <strong class="p-2">Current Report</strong>
    <p style="padding: 6px;">
      <?php
      $current_date = $dates[count($dates) - 1]["date"];
      echo $current_date;
      ?>
    </p>
  </div>
  <div class="col-md-4 bg-info" style="padding: 15px;">
    <strong>Next Report</strong>
    <p style="padding: 6px;">
      <?php
      $next_date = date_create($current_date);
      date_add($next_date, date_interval_create_from_date_string('7 days'));
      echo date_format($next_date, 'Y-m-d');
      ?>
    </p>
  </div>
  <div class="col-md-4 bg-success" style="padding: 15px;">
    <strong>Report History</strong><br>
    <select id="select-date" placeholder="Choose Date"
      onchange="getProjectDate('findreport3.php?pid=' + <?php echo $pid ?> + '&date=' + this.value)">
      <option value="">Choose Date</option>
      <?php
      foreach ($dates as $dateAndDepth) {
        $date = $dateAndDepth["date"];
        $depth = $dateAndDepth["depth"];
        ?>
      <option value=<?php echo $date ?>>
        <?php echo $date ?>
      </option>
      <?php
      }
      ?>
    </select>
  </div>
</div>

<?php
// if date is empty, it's last update date
$chosen_date = ($_GET['date'] == '' ? $current_date : $_GET['date']);

// get lists of date that project has
$url = "https://api.awrcloud.com/get.php?action=list&project=" . $projectName . "&date=" . ($_GET['date'] == '' ? $current_date : $_GET['date']) . "&token=" . $token . "&compression=zip";
$rankingResultsResponse = file_get_contents($url);
$responseRows = explode("\n", $rankingResultsResponse);

if ($responseRows[0] != "OK") {
  echo "No results for date: " . $date;
} else {
  $dateFilesCount = $responseRows[1];
  if ($dateFilesCount != 0) {
    for ($i = 0; $i < $dateFilesCount; $i++) {
      $urlRequest = $responseRows[2 + $i];
      $urlHandle = fopen($urlRequest, 'r');

      $tempZip = fopen("tempfile.zip", "w");

      while (!feof($urlHandle)) {
        $readChunk = fread($urlHandle, 1024 * 8);
        fwrite($tempZip, $readChunk);
      }
      fclose($tempZip);
      fclose($urlHandle);

      $pathToExtractedJson = "reports/jsons/";

      $zip = new ZipArchive;
      $res = $zip->open("tempfile.zip");

      if ($res === FALSE) {
        echo "Could not extract JSON files from the zip archive";
        continue;
      }

      $zip->extractTo($pathToExtractedJson);
      $zip->close();

      $dir_handle = opendir($pathToExtractedJson);

      ?>
<table class="table table-responsible table-striped">
  <thead>
    <tr class="info">
      <th>
        <?php echo $chosen_date ?>
      </th>
      <th>Google</th>
      <th>Bing</th>
      <th>Yahoo</th>
    </tr>
  </thead>
  <tbody>
    <?php
          while (false !== ($entry = readdir($dir_handle))) {
            if ($entry == ".." || $entry == ".") {
              continue;
            }

            $rankings = json_decode(file_get_contents($pathToExtractedJson . $entry), true); // the json file contains nested json objects, make sure you use associative arrays
            unlink($pathToExtractedJson . $entry);
            if (substr_compare("google", $rankings["searchengine"], 0, 6) == 0) {
              ?>
    <tr>
      <td>
        <?php echo $rankings["keyword"] ?>
      </td>
      <?php }
            if ($rankings["searchengine"] != "") {
              ?>
      <td>
        <?php echo count($rankings["rankdata"]) ?>
      </td>
      <?php
            }
            if (substr_compare("yahoo", $rankings["searchengine"], 0, 6) == 0) {
              ?>
    </tr>
    <?php }
            ?>
    <?php
          }
    }
  }
}
?>