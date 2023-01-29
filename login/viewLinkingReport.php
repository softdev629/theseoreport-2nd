<?php

session_start();

require_once __DIR__ . '/includes/common.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/check-session.php';
require_once __DIR__ . '/includes/init-session.php';
?>

<!DOCTYPE html>

<?php
session_start();

require_once __DIR__ . '/includes/common.php';
require_once __DIR__ . '/includes/database.php';

require_once __DIR__ . '/includes/check-session.php';
require_once __DIR__ . '/includes/init-session.php';

if ($_SESSION['usertype'] != 'Client') {
  header("Location: $DASHBOARD_PAGE_PATH" . "?account_id=" . $_SESSION['account_id']);
  exit;
}

?>

<head>

  <title>Upload Report</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="<?php echo $website_url ?>/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?php echo $website_url ?>/css/font-awesome.css" rel="stylesheet">
  <link rel="stylesheet" href="css/main.css">
  <link href="<?php echo $website_url ?>/css/link.css" rel="stylesheet">

</head>

<body>

  <?php include("includes/header.php"); ?>
  <!--header end-->
  <!--sidebar start-->

  <?php include("includes/left.php"); ?>

  <section id="main-content">
    <section class="wrapper">
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

          // (B) LOOP THROUGH ROWS OF CURRENT WORKSHEET
          foreach ($worksheet->getRowIterator() as $row) {
            // Checkes entire row is empty
            $flag = 0;

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
              echo "<table class='linktable'><thead><tr>";
            else
              echo "<tr>";
            $index = 0;
            foreach ($cellIterator as $cell) {
              if ($index > 3)
                break;
              if ($flag == 2)
                echo "<th>" . $cell->getValue() . "</th>";
              else
                echo "<td>" . $cell->getValue() . "</td>";
              ++$index;
            }
            if ($flag == 2)
              echo "</tr></thead><tbody>";
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
    </section>
  </section>

</body>

</html>