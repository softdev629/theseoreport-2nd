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
$projectName = $sql_data['projectName'];
$token = $awr_api_token;

// Gets all dates of this project from awr API
$url = "https://api.awrcloud.com/v2/get.php?action=get_dates&token=" . $token . "&project=" . $projectName;
$response = json_decode(file_get_contents($url), true);
if ($response["response_code"] != 0) {
  echo "This project doesn't have any history dates!!!";
  return;
}
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

// export visibility API url in awr
$url = "https://api.awrcloud.com/v2/get.php?action=visibility_export&project=$projectName&token=$awr_api_token&startDate=$previous_date&stopDate=$chosen_date";
$visibilityResponse = json_decode(file_get_contents($url), true);
if ($visibilityResponse["response_code"] != 0 && $visibilityResponse["response_code"] != 10) {
  echo "No visibility datas for date: " . $date;
} else {
  // download visibility zip file
  $urlRequest = $visibilityResponse["details"];
  $urlHandle = fopen($urlRequest, 'r');
  $tempZip = fopen("tempfile.zip", "w");
  while (!feof($urlHandle)) {
    $readChunk = fread($urlHandle, 1024 * 8);
    fwrite($tempZip, $readChunk);
  }
  fclose($tempZip);
  fclose($urlHandle);
  // print_r("step 1");
  // zip file extract
  $pathToExtractedJson = "reports/jsons/";
  $zip = new ZipArchive;
  $res = $zip->open("tempfile.zip");
  if ($res === FALSE) { // zip file extract failed
    echo "Could not extract JSON files from the zip archive";
  } else { //zip file extract success
    $zip->extractTo($pathToExtractedJson);
    $zip->close();
    // unlink("tempfile.zip");
    // print_r("step 2");
    $dir_handle = opendir($pathToExtractedJson);
    // stores information as array
    $visibility_report = array(); //chosen date
    while (false !== ($entry = readdir($dir_handle))) {
      if ($entry == ".." || $entry == ".") {
        continue;
      }
      // print_r("step 3");
      // (A) PHPSPREADSHEET TO LOAD EXCEL FILES
      require "vendor/autoload.php";

      $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
      $spreadsheet = $reader->load($pathToExtractedJson . $entry);
      $worksheet = $spreadsheet->getActiveSheet();
      // print_r("step 4");
      // (B) LOOP THROUGH ROWS OF CURRENT WORKSHEET
      foreach ($worksheet->getRowIterator() as $row) {
        // (B1) READ CELLS
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);

        $flag = 0;
        foreach ($cellIterator as $cell) {
          if ($flag > 3)
            break;
          if (substr_compare($cell->getValue(), "Top", 0, 3) == 0 || substr_compare($cell->getValue(), "Move", 0, 3) == 0) {
            $cellValue = $worksheet->getCell("B" . $cell->getRow())->getValue();
            $cellData = explode(" ", $cellValue);
            $visibility_report[$cell->getValue()] = $cellData[0];
          }
          ++$flag;
        }
      }
      // print_r("step 5");
      // removes json file
      unlink($pathToExtractedJson . $entry);
    }
    closedir($dir_handle);
  }
}
?>

<?php

// current report & previous store file name
$report_file_name = $chosen_date . "_" . $projectName . ".xlsx";
$previous_report_file_name = $previous_date . "_" . $projectName . ".xlsx";

// get lists of date that project has
$url = "https://api.awrcloud.com/v2/get.php?action=export_ranking&token=" . $awr_api_token . "&project=" . $projectName . "&startDate=" . $previous_date . "&stopDate=" . $chosen_date . "&format=json";
$rankingResultsResponse = file_get_contents($url);
$responseRows = json_decode($rankingResultsResponse, true);
// print_r("step 6");
// If it has no downloadable url, no result
if ($responseRows["response_code"] != 0 && $responseRows["response_code"] != 10) {
  echo "No results for date: " . $date;
} else {
  // print_r("step 7");
  // download zip file
  $urlRequest1 = $responseRows["details"];
  $urlHandle1 = fopen($urlRequest1, 'r');
  $tempZip1 = fopen("tempfile1.zip", "w");
  while (!feof($urlHandle1)) {
    $readChunk = fread($urlHandle1, 1024 * 8);
    fwrite($tempZip1, $readChunk);
  }
  fclose($tempZip1);
  fclose($urlHandle1);
  // print_r("step 8");
  // zip file extract
  $pathToExtractedJson = "reports/jsons/";
  $zip1 = new ZipArchive;
  $res = $zip1->open("tempfile1.zip");
  if ($res === FALSE) { // zip file extract failed
    echo "Could not extract JSON files from the zip archive";
  } else { //zip file extract success
    $zip1->extractTo($pathToExtractedJson);
    $zip1->close();
    // unlink("tempfile1.zip");
    // print_r("step 9");
    $dir_handle = opendir($pathToExtractedJson);
    // stores information as array: we have to split current and previous because of comparision
    $ranking_report = array(); //chosen date
    $previous_report = array(); // previous date
    while (false !== ($entry = readdir($dir_handle))) {
      if ($entry == ".." || $entry == ".") {
        continue;
      }
      // print_r("step 10");
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
      // print_r("step 11");
      // removes json file
      unlink($pathToExtractedJson . $entry);
    }
    closedir($dir_handle);
    ?>
<!--------------------- visibility table --------------------->
<div class="col-md-6 text-center col-md-offset-3">
  <table class="table table-responsible">
    <thead>
      <tr class="bg-warning">
        <th class="text-center">Top 10</th>
        <th class="text-center">Top 20</th>
        <th class="text-center">Top 30</th>
        <th class="text-center">Total Move</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>
          <?php echo $visibility_report["Top 10"] ?>
        </td>
        <td><?php echo $visibility_report["Top 20"] ?></td>
        <td>
          <?php echo $visibility_report["Top 30"] ?>
        </td>
        <!-- <td style="color: #27c24c">+ <?php echo $visibility_report["Moved Up"] ?></td>
            <td style="color: #f05050">
              - <?php echo $visibility_report["Moved Down"] ?>
            </td> -->
        <td>
          <?php echo intval($visibility_report["Moved Up"]) - intval($visibility_report["Moved Down"]) ?>
        </td>
      </tr>
    </tbody>
  </table>
</div>

<!------------------ ranking report header -------------------->
<div class="row text-center">
  <!-- chosen date div -->
  <div class="col-md-4" style="padding: 15px; background-color: #939597; border-right: 1px solid">
    <strong class="p-2">Current Report</strong>
    <p>
      <?php
          echo date_format(date_create($chosen_date), 'm-d-Y');
          ?>
    </p>
  </div>
  <!-- next report date div -->
  <div class="col-md-4" style="padding: 15px; background-color: #939597; border-right: 1px solid">
    <strong>Next Report</strong>
    <p>
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
      <option value=<?php echo $date ?> <?php echo $date == $chosen_date ? 'selected="selected"' : '' ?>>
        <?php echo date_format(date_create($date), 'm-d-Y') ?>
      </option>
      <?php
          }
          ?>
    </select>
  </div>
</div>
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
                echo "<span class='badge bg-success'><i class='fa fa-arrow-up' style='font-size:12px'></i> " . (intval($previous_report[$key]["google"]) - intval($value["google"])) . "</span>"
                  ?>
      </td>
      <td>
        <?php echo $value["bing"] ?>
        <?php
              if ($previous_report[$key]["bing"] != '-' && $value["bing"] != '-' && intval($previous_report[$key]["bing"]) > intval($value["bing"]))
                echo "<span class='badge bg-success'><i class='fa fa-arrow-up' style='font-size:12px'></i> " . (intval($previous_report[$key]["bing"]) - intval($value["bing"])) . "</span>"
                  ?>
      </td>
      <td>
        <?php echo $value["yahoo"] ?>
        <?php
              if ($previous_report[$key]["yahoo"] != '-' && $value["yahoo"] != '-' && intval($previous_report[$key]["yahoo"]) > intval($value["yahoo"]))
                echo "<span class='badge bg-success'><i class='fa fa-arrow-up' style='font-size:12px'></i> " . (intval($previous_report[$key]["yahoo"]) - intval($value["yahoo"])) . "</span>"
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