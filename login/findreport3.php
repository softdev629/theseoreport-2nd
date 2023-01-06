<?PHP
session_start();

include("includes/common.php"); // loads constant variables
include("includes/database.php"); // connect to database
include("includes/init-session.php"); // session init

$pid = $_GET['pid']; // project ID
$cid = $_SESSION['UID']; // User ID
if ($pid == '') {
  exit;
}
$sql = mysqli_query($link, "select * from rl_projects where id='" . $pid . "'");
$sql_data = mysqli_fetch_array($sql);
$projectName = $sql_data['projectName'] . '.com';
$token = $awr_api_token;

// Gets all dates of this project from awr API
$url = "https://api.awrcloud.com/v2/get.php?action=get_dates&token=" . $token . "&project=" . $projectName;
$response = json_decode(file_get_contents($url), true);
$dates = $response["details"]["dates"]; // all date arrays

$last_update_date = $dates[count($dates) - 1]["date"];
// if date is empty, it's last update date
$chosen_date = ($_GET['date'] == '' ? $last_update_date : $_GET['date']);
$next_date = null;
$previous_date = null;

// searches selected date and gets previous date
for ($i = 0; $i < count($dates); $i++) {
  if ($dates[$i]["date"] == $chosen_date) {
    if ($i != 0)
      $previous_date = $dates[$i - 1]["date"];
    else // we have no previous date
      $previous_date = $dates[$i]["date"];
    if ($i == count($dates) - 1) { // we have no next report
      $next_date = date_create($current_date);
      date_add($next_date, date_interval_create_from_date_string('7 days'));
    } else
      $next_date = date_create($dates[$i + 1]["date"]);
  }
}
?>

<!------------------ ranking report header -------------------->
<div class="row text-center">
  <!-- chosen date div -->
  <div class="col-md-4" style="padding: 15px; background-color: #939597;">
    <strong class="p-2">Current Report</strong>
    <p style="padding: 6px;">
      <?php
      echo date_format(date_create($chosen_date), 'm-d-Y');
      ?>
    </p>
  </div>
  <!-- next report date div -->
  <div class="col-md-4" style="padding: 15px; background-color: #A09998;">
    <strong>Next Report</strong>
    <p style="padding: 6px;">
      <?php
      echo date_format($next_date, 'm-d-Y');
      ?>
    </p>
  </div>
  <!-- div which lets you select date -->
  <div class="col-md-4" style="padding: 15px; background-color: #939597;">
    <strong>Report History</strong><br>
    <select id="select-date" placeholder="Choose Date"
      onchange="getProjectDate('findreport3.php?pid=' + <?php echo $pid ?> + '&date=' + this.value)"
      class="col-md-6 col-md-offset-3">
      <option value="">Choose Date</option>
      <?php
      // puts all dates here to select past date
      foreach ($dates as $dateAndDepth) {
        $date = $dateAndDepth["date"];
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

// current report & previous store file name
$report_file_name = $chosen_date . "_" . $projectName . ".xlsx";
$previous_report_file_name = $previous_date . "_" . $projectName . ".xlsx";

// get lists of date that project has
$url = "https://api.awrcloud.com/v2/get.php?action=export_ranking&token=" . $awr_api_token . "&project=" . $projectName . "&startDate=" . $previous_date . "&stopDate=" . $chosen_date . "&format=json";
$rankingResultsResponse = file_get_contents($url);
$responseRows = json_decode($rankingResultsResponse, true);
// If it has no downloadable url, no result
if ($responseRows["code"] != 0 && $responseRows["code"] != 10) {
  echo "No results for date: " . $date;
} else {
  // download zip file
  $urlRequest = $responseRows["details"];
  $urlHandle = fopen($urlRequest, 'r');
  $tempZip = fopen("tempfile.zip", "w");
  while (!feof($urlHandle)) {
    $readChunk = fread($urlHandle, 1024 * 8);
    fwrite($tempZip, $readChunk);
  }
  fclose($tempZip);
  fclose($urlHandle);

  // zip file extract
  $pathToExtractedJson = "reports/jsons/";
  $zip = new ZipArchive;
  $res = $zip->open("tempfile.zip");
  if ($res === FALSE) { // zip file extract failed
    echo "Could not extract JSON files from the zip archive";
  } else { //zip file extract success
    $zip->extractTo($pathToExtractedJson);
    $zip->close();

    $dir_handle = opendir($pathToExtractedJson);
    // stores information as array: we have to split current and previous because of comparision
    $ranking_report = array(); //chosen date
    $previous_report = array(); // previous date
    while (false !== ($entry = readdir($dir_handle))) {
      if ($entry == ".." || $entry == ".") {
        continue;
      }

      // the json file contains nested json objects, make sure you use associative arrays
      $rankings = json_decode(file_get_contents($pathToExtractedJson . $entry), true);

      foreach ($rankings as $ranking_items) {
        $keyword = $ranking_items["keyword"];
        $searchengine = $ranking_items["se"];
        $rankdate = $ranking_items["date"];
        if ($rankdate == $chosen_date) { // date is chosen date, stores in current report
          if (!array_key_exists($keyword, $ranking_report)) { // if keyword doesn't exist in store creates new array of (google, bing, yahoo)
            $ranking_report[$keyword] = array("google" => "-", "bing" => "-", "yahoo" => "-");
          }
          // Use three dimensional array to save data as table
          switch ($searchengine) {
            case "Google.com-EN": // Google Engine
              $ranking_report[$keyword]["google"] = $ranking_items["position"];
              break;
            case "Bing": // Bing Engine
              $ranking_report[$keyword]["bing"] = $ranking_items["position"];
              break;
            case "Yahoo": // Yahoo Engine
              $ranking_report[$keyword]["yahoo"] = $ranking_items["position"];
              break;
          }
        } else if ($rankdate == $previous_date) { // previous report store
          if (!array_key_exists($keyword, $previous_report)) { // if keyword doesn't exist in store creates new array of (google, bing, yahoo)
            $previous_report[$keyword] = array("google" => "-", "bing" => "-", "yahoo" => "-");
          }
          // Use three dimensional array to save data as table
          switch ($searchengine) {
            case "Google.com-EN":
              $previous_report[$keyword]["google"] = $ranking_items["position"];
              break;
            case "Bing":
              $previous_report[$keyword]["bing"] = $ranking_items["position"];
              break;
            case "Yahoo":
              $previous_report[$keyword]["yahoo"] = $ranking_items["position"];
              break;
          }
        }
      }
      // removes json file
      unlink($pathToExtractedJson . $entry);
    }
    ?>

<!---------------- Displays Report as table -------------------->
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
        foreach ($ranking_report as $key => $value) {
          ?>
    <tr>
      <td>
        <?php echo $key ?>
      </td>
      <td>
        <?php
              echo $value["google"];
              if ($previous_report[$key]["google"] != '-' && $value["google"] != '-' && intval($previous_report[$key]["google"]) > intval($value["google"]))
                echo "<span class='badge bg-info'><i class='fa fa-arrow-up' style='font-size:12px'></i> " . (intval($previous_report[$key]["google"]) - intval($value["google"])) . "</span>"
                  ?>
      </td>
      <td>
        <?php echo $value["bing"] ?>
        <?php
              if ($previous_report[$key]["bing"] != '-' && $value["bing"] != '-' && intval($previous_report[$key]["bing"]) > intval($value["bing"]))
                echo "<span class='badge bg-info'><i class='fa fa-arrow-up' style='font-size:12px'></i> " . (intval($previous_report[$key]["bing"]) - intval($value["bing"])) . "</span>"
                  ?>
      </td>
      <td>
        <?php echo $value["yahoo"] ?>
        <?php
              if ($previous_report[$key]["yahoo"] != '-' && $value["yahoo"] != '-' && intval($previous_report[$key]["yahoo"]) > intval($value["yahoo"]))
                echo "<span class='badge bg-info'><i class='fa fa-arrow-up' style='font-size:12px'></i> " . (intval($previous_report[$key]["yahoo"]) - intval($value["yahoo"])) . "</span>"
                  ?>
      </td>
    </tr>
    <?php
        }
        ?>
  </tbody>
  <table>
    <?php
  }
}
?>