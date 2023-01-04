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
  <div class="col-md-4" style="padding: 15px; background-color: #939597;">
    <strong class="p-2">Current Report</strong>
    <p style="padding: 6px;">
      <?php
      $current_date = $dates[count($dates) - 1]["date"];
      echo date_format(date_create($current_date), 'm-d-Y');
      ?>
    </p>
  </div>
  <div class="col-md-4" style="padding: 15px; background-color: #A09998;">
    <strong>Next Report</strong>
    <p style="padding: 6px;">
      <?php
      $next_date = date_create($current_date);
      date_add($next_date, date_interval_create_from_date_string('7 days'));
      echo date_format($next_date, 'm-d-Y');
      ?>
    </p>
  </div>
  <div class="col-md-4" style="padding: 15px; background-color: #939597;">
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

$report_file_name = $chosen_date . "_" . $projectName . ".xlsx";

if (file_exists("reports/ranking/$report_file_name")) {
  ?>
<table class="table table-striped b-t b-light">
  <?php
    // (A) PHPSPREADSHEET TO LOAD EXCEL FILES
    require "vendor/autoload.php";

    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
    $spreadsheet = $reader->load("reports/ranking/$report_file_name");
    $worksheet = $spreadsheet->getActiveSheet();

    // (B) LOOP THROUGH ROWS OF CURRENT WORKSHEET
    foreach ($worksheet->getRowIterator() as $row) {
      // (B1) READ CELLS
      $cellIterator = $row->getCellIterator();
      $cellIterator->setIterateOnlyExistingCells(false);

      // (B2) OUTPUT HTML
      echo "<tr>";
      foreach ($cellIterator as $cell) {
        echo "<td>" . $cell->getValue() . "</td>";
      }
      echo "</tr>";
    }
    ?>
</table>
<?php
} else {
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
        // zip file extract
        $zip = new ZipArchive;
        $res = $zip->open("tempfile.zip");

        if ($res === FALSE) {
          echo "Could not extract JSON files from the zip archive";
          continue;
        }

        $zip->extractTo($pathToExtractedJson);
        $zip->close();

        $dir_handle = opendir($pathToExtractedJson);

        // stores information as array
        $ranking_report = array();
        while (false !== ($entry = readdir($dir_handle))) {
          if ($entry == ".." || $entry == ".") {
            continue;
          }

          $rankings = json_decode(file_get_contents($pathToExtractedJson . $entry), true); // the json file contains nested json objects, make sure you use associative arrays
          $searchengine = $rankings['searchengine'];
          $keyword = $rankings['keyword'];
          // Use three dimensional array to save data as table
          if (!array_key_exists($keyword, $ranking_report)) {
            $ranking_report[$keyword] = array("google" => 0, "bing" => 0, "yahoo" => 0);
          }
          switch ($searchengine) {
            case (substr_compare("google", $searchengine, 0, 6) == 0):
              $ranking_report[$keyword]["google"] = count($rankings["rankdata"]);
              break;
            case (substr_compare("bing", $searchengine, 0, 4) == 0):
              $ranking_report[$keyword]["bing"] = count($rankings["rankdata"]);
              break;
            case (substr_compare("yahoo", $searchengine, 0, 4) == 0):
              $ranking_report[$keyword]["yahoo"] = count($rankings["rankdata"]);
              break;
          }
          unlink($pathToExtractedJson . $entry);
        }

        $htmlString = '<table><tr><td>' . date_format(date_create($chosen_date), 'm-d-Y') . '</td><td>Google</td><td>Bing</td><td>Yahoo</td>';
        foreach ($ranking_report as $key => $value) {
          $htmlString .= '<tr><td>' . $key . '</td>';
          $htmlString .= '<td>' . $value["google"] . '</td>';
          $htmlString .= '<td>' . $value["bing"] . '</td>';
          $htmlString .= '<td>' . $value["yahoo"] . '</td></tr>';
        }
        $htmlString .= '</table>';

        require "vendor/autoload.php";

        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Html();
        $spreadsheet = $reader->loadFromString($htmlString);
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('reports/ranking/' . $report_file_name);
        ?>

<!-- Displays Report as table -->
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
        <?php echo $value["google"] ?>
      </td>
      <td>
        <?php echo $value["bing"] ?>
      </td>
      <td>
        <?php echo $value["yahoo"] ?>
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
  }
}
?>