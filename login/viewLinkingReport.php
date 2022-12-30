<?php

session_start();

require_once __DIR__ . '/includes/common.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/check-session.php';
require_once __DIR__ . '/includes/init-session.php';

if ($_SESSION['usertype'] != 'Administrator') {
  header("Location: $DASHBOARD_PAGE_PATH?account_id=". $_SESSION['account_id']);
  exit;
}
?>

<!DOCTYPE html>

<head>
  <title>Upload Report</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>

<body>

  <table>
    <?php
      // (A) PHPSPREADSHEET TO LOAD EXCEL FILES
      require "vendor/autoload.php";
      $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
      $spreadsheet = $reader->load($_GET["path"]);
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

</body>

</html>