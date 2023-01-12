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
  <link href="<?php echo $website_url ?>/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?php echo $website_url ?>/css/style.css" rel='stylesheet' type='text/css' />
  <link href="<?php echo $website_url ?>/css/style-responsive.css" rel="stylesheet" />
  <link href="<?php echo $website_url ?>/css/font-awesome.css" rel="stylesheet">
  <link href="<?php echo $website_url ?>/css/table-main.css" rel="stylesheet">
  <link href="<?php echo $website_url ?>/css/perfect-scrollbar.css" rel="stylesheet">
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
          <!-- Back Icon -->
          <li>
            <a href="#" onclick="history.go(-1)">
              <i class="fa fa-arrow-left" title="Download Report" style="font-size:25px;"></i>
              Back
            </a>
          </li>
        </ul>
      </div>
    </nav>
    <?php
    if (file_exists("reports/" . $_GET["path"])) {
      ?>
    <!---------------- Generates table from excel file  ------------------>
    <div class="table100 ver3 m-b-110">
      <?php

        // (A) PHPSPREADSHEET TO LOAD EXCEL FILES
        require "vendor/autoload.php";

        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load("reports/" . $_GET["path"]);
        $worksheet = $spreadsheet->getActiveSheet();
        // Checkes entire row is empty
        $flag = 0;

        // (B) LOOP THROUGH ROWS OF CURRENT WORKSHEET
        foreach ($worksheet->getRowIterator() as $row) {
          // (B1) READ CELLS
          $cellIterator = $row->getCellIterator();
          $cellIterator->setIterateOnlyExistingCells(false);

          foreach ($cellIterator as $cell) {
            if ($cell->getValue() == 'S. No.' || $cell->getValue() == 'S.No') {
              $flag = 2;
              break;
            }
            if ($cell->getValue() != '') {
              $flag = 1;
              break;
            }
            if ($cell->getValue() == '')
              break;
          }
          if ($flag == 0)
            continue;

          // (B2) OUTPUT HTML
          if ($flag == 2)
            echo "<div class='table100-head'><table><thead><tr class='row100 head'>";
          else
            echo "<tr class='row100 body'>";
          $index = 0;
          foreach ($cellIterator as $cell) {
            if ($index > 5)
              break;
            if ($flag == 2)
              echo "<th class='cell100 column" . ($index + 1) . "'>" . $cell->getValue() . "</th>";
            else
              echo "<td class='cell100 column" . ($index + 1) . "'>" . $cell->getValue() . "</td>";
            ++$index;
          }
          if ($flag == 2)
            echo "</tr></thead></table></div><div class='table100-body js-pscroll ps ps--active-y'><table><tbody>";
          else
            echo "</tr>";
        }
        echo "</tbody></table></div>";
        ?>
    </div>
    <?php
    } else
      echo "<p class='text-center'>Report doesn't exists!</p>";
    ?>
  </div>

</body>

</html>