<?php
// require_once '../vendor/autoload.php'; // Kama una Composer

use Dompdf\Dompdf;
use Dompdf\Options;

function generate_pdf($html, $filename = 'document.pdf') {
    $options = new Options();
    $options->set('isRemoteEnabled', true); // For images/CSS from URL
    $options->set('defaultFont', 'DejaVuSans'); // Better Unicode support (Swahili)

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream($filename, ['Attachment' => true]);
    exit();
}
?>