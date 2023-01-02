<?php

session_start();

require_once __DIR__ . '/includes/common.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/check-session.php';
require_once __DIR__ . '/includes/init-session.php';
?>

<!DOCTYPE html>

<head>

  <title>Upload Report</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link rel="stylesheet" href="<?PHP echo $website_url; ?>/css/bootstrap.min.css">
  <link href="<?PHP echo $website_url; ?>/css/style.css" rel='stylesheet' type='text/css' />
  <link href="<?PHP echo $website_url; ?>/css/style-responsive.css" rel="stylesheet" />
  <link href="http://localhost/login/css/font-awesome.css" rel="stylesheet">
</head>

<body>
  <div class="container">

    <nav class="navbar navbar-inverse">
      <div class="container-fluid">
        <div class="navbar-header">
          <a class="navbar-brand" href="#"><?php echo $_GET['title'] ?></a>
        </div>
        <ul class="nav navbar-nav navbar-right">
          <!---- Download Icon ---->
          <li>
            <a href="download.php?type=linking&source=<?php echo $_GET['path'] ?>" onclick="linkingReportDownload()">
              <i class="fa fa-download" title="Download Report" style="font-size:25px;"></i>
              Download
            </a>
          </li>
        </ul>
      </div>
    </nav>

    <!---------------- Generates table from excel file  ------------------>

    <table class="table table-striped b-t b-light">
      <?php
      // (A) PHPSPREADSHEET TO LOAD EXCEL FILES
      require "vendor/autoload.php";
      
      $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
      // die($website_url ."/reports/". $_GET["path"]);
      $spreadsheet = $reader->load("reports/". $_GET["path"]);
      $worksheet = $spreadsheet->getActiveSheet();
      
      // (B) LOOP THROUGH ROWS OF CURRENT WORKSHEET
      foreach ($worksheet->getRowIterator() as $row) {
        // (B1) READ CELLS
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);

        // (B2) OUTPUT HTML
        echo "<tr>";
        foreach ($cellIterator as $cell) { echo "<td>". $cell->getValue() ."</td>"; }
        echo "</tr>";
      }
    ?>
    </table>
  </div>

</body>

</html>