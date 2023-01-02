<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

switch ($_GET['type']) {
  // If linking report, convert excel to pdf and pdf has to be downloaded
  case 'linking':
    $filename = basename($_GET['source']);
    $pdf_path = 'reports/' . $filename . '.pdf';
    if (!file_exists("reports/" . $filename . ".pdf")) {
      $new_excel_path = "reports/" . $_GET['source'];
      $reader = IOFactory::createReader("Xlsx");
      $spreadsheet = $reader->load("$new_excel_path");

      $writer = IOFactory::createWriter($spreadsheet, 'Mpdf');
      $writer->save($pdf_path);
    }
    $file = $filename . ".pdf";

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header("Content-Disposition: attachment; filename=$file");
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file));
    ob_clean();
    flush();
    readFile("reports/$file");

    break;
}

?>