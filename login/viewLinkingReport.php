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
  <link rel="stylesheet" href="http://localhost/login/css/bootstrap.min.css">
  <link href="http://localhost/login/css/style.css" rel='stylesheet' type='text/css' />
  <link href="http://localhost/login/css/style-responsive.css" rel="stylesheet" />
  <link href="http://localhost/login/css/font-awesome.css" rel="stylesheet">
</head>

<body>
  <div class="container">

    <nav class="navbar navbar-inverse">
      <div class="container-fluid">
        <div class="navbar-header">
          <a class="navbar-brand" href="#">
            <?php echo $_GET['title'] ?>
          </a>
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
      $spreadsheet = $reader->load("reports/" . $_GET["path"]);
      $worksheet = $spreadsheet->getActiveSheet();

      // (B) LOOP THROUGH ROWS OF CURRENT WORKSHEET
      foreach ($worksheet->getRowIterator() as $row) {
        // (B1) READ CELLS
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);

        // Checkes entire row is empty
        $flag = 0;
        foreach ($cellIterator as $cell) {
          if ($cell->getValue() == 'S. No.') {
            $flag = 2;
            break;
          }
          if ($cell->getValue() != '') {
            $flag = 1;
            break;
          }
        }
        if ($flag == 0)
          continue;

        // (B2) OUTPUT HTML
        if ($flag == 2)
          echo "<tr class='bg-info' style='font-weight: bold' >";
        else
          echo "<tr>";
        $flag = 0;
        foreach ($cellIterator as $cell) {
          if ($flag > 5)
            break;
          echo "<td>" . $cell->getValue() . "</td>";
          ++$flag;
        }
        echo "</tr>";
      }
      ?>
    </table>
  </div>

</body>

</html>