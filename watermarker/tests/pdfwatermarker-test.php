<?php

$parent_directory = dirname(__FILE__);

require_once($parent_directory . "/../fpdf/fpdf.php");
require_once($parent_directory . "/../fpdi/fpdi.php");
require_once($parent_directory . "/../pdfwatermarker/pdfwatermarker.php");

class PDFWatermarker_test extends PHPUnit_Framework_TestCase {

    function setUp() {
        
    }

    /*
     * Test watermark as background
     * 
     * @return void
     */

    public function testAsOverlay() {
        $parent_directory = dirname(__FILE__);

        $watermark = "Licenciado para Edson Cunha às 11h32. ";

        $output = $parent_directory . "/../assets/test-output.pdf";

        $watermarker = new PDFWatermarker($parent_directory . '/../assets/test.pdf', $output, $watermark);


        $watermarker->savePdf();
        $this->assertTrue(file_exists($output) === true);
    }

}
