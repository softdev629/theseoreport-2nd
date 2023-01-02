<?PHP
session_start();
include("includes/common.php");
include("includes/database.php");
include("includes/init-session.php");
$pid = $_GET['pid'];
$cid = $_SESSION['UID'];

$sql = mysqli_query($link, "select * from rl_projects where id='" . $pid . "'");
$sql_data = mysqli_fetch_array($sql);

$projectName = "project+name";
$token = "myAPItoken";

$url = "https://api.awrcloud.com/v2/get.php?action=get_dates&token=" . $token . "&project=" . $projectName;

$response = json_decode(file_get_contents($url), true);

$dates = $response["details"]["dates"]; // date and search depth arrays

foreach ($dates as $dateAndDepth) {
  $date = $dateAndDepth["date"];
  $depth = $dateAndDepth["depth"];

  $url = "https://api.awrcloud.com/get.php?action=list&project=" . $projectName . "&date=" . $date . "&token=" . $token . "&compression=zip";
  $rankingResultsResponse = file_get_contents($url);

  $responseRows = explode("\n", $rankingResultsResponse);

  if ($responseRows[0] != "OK") {
    echo "No results for date: " . $date;
    continue;
  }

  $dateFilesCount = $responseRows[1];
  if ($dateFilesCount == 0) {
    continue;
  }

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

    $pathToExtractedJson = "path/to/extracted/jsons/";

    $zip = new ZipArchive;
    $res = $zip->open("tempfile.zip");

    if ($res === FALSE) {
      echo "Could not extract JSON files from the zip archive";
      continue;
    }

    $zip->extractTo($pathToExtractedJson);
    $zip->close();

    $dir_handle = opendir($pathToExtractedJson);

    while (false !== ($entry = readdir($dir_handle))) {
      if ($entry == ".." || $entry == ".") {
        continue;
      }

      $rankings = json_decode(file_get_contents($pathToExtractedJson . $entry), true); // the json file contains nested json objects, make sure you use associative arrays

      echo "";
      echo "Search Engine: " . $rankings["searchengine"];
      echo "Search Depth: " . $rankings["depth"];
      echo "Location: " . $rankings["location"];
      echo "Keyword: " . $rankings["keyword"];

      $rank_data_array = $rankings["rankdata"];
      foreach ($rank_data_array as $rank_data) {
        echo "<br/>" . $rank_data["position"] . ". " . $rank_data["url"] . " " . $rank_data["typedescription"] . " result on page " . $rank_data["page"];
      }
    }
  }
}


if ($pid == '') {
  exit;
}
?>

<table class="table table-striped b-t b-light">
  <thead>
    <tr>
      <th colspan="3" align="center">Ranking Report - <span style="color:#0066CC;">
          <?PHP echo $sql_data['websiteUrl']; ?>
        </span>
        <div align="right">
          <?PHP if ($sql_data['stopstatus'] == 0) {
          echo "<span class='text-danger'>Stopped</span>";
        } else {
          echo "<span class='text-success'>Active</span>";
        } ?>
        </div>
      </th>
    </tr>
  </thead>
  <tbody>
    <?PHP
    $qq = "select * from rl_report where pid='" . $pid . "' and $cid='" . $_SESSION['UID'] . "' and reportname='Ranking Report' order by reportmonth1 desc";
    $user = mysqli_query($link, $qq);
    $user_numee = mysqli_num_rows($user);
    $i = 1;
    while ($user_data = mysqli_fetch_array($user)) {
      $orgDate = $user_data['reportmonth1'];
      $newDate = date("d F Y", strtotime($orgDate));
      ?>
    <tr>
      <td>
        <?PHP echo $i; ?>.
      </td>
      <td>
        <?PHP echo $newDate; ?>
      </td>
      <td><a
          href=<?php echo "https://api.awrcloud.com/v2/get.php?action=get&project=" . $sql_data['projectName'] . ".com&date=" . date("Y-m-d", strtotime($orgDate)) . "&token=$awr_api_token" ?>
          target="_blank" download><i class="fa  fa-download text-inverse" title="Download Report"
            style="font-size:25px;"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <a href="reports/<?PHP echo $user_data['imgPath']; ?>" target="_blank"><i class="fa  fa-eye text-success"
            title="View Report" style="font-size:25px;"></i></a>
      </td>
    </tr>
    <?PHP
      $i = $i + 1;
    }
    if ($user_numee == 0) {
      ?>
    <tr>
      <td colspan="3" align="center" style="color:#FF0000;">No Report Found.</td>
    </tr>
    <?PHP } ?>
  </tbody>
</table>