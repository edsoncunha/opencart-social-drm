<?php

class PDFWatermarker {

    private $_originalPdf;
    private $_newPdf;
    private $_tempPdf;
    private $_watermark;

    public function __construct($originalPdf, $newPdf, $watermark) {

        $this->_originalPdf = $originalPdf;
        $this->_newPdf = $newPdf;
        $this->_tempPdf = new FPDI();
        $this->_watermark = $watermark;

        $this->_validateAssets();
    }

    private function _validateAssets() {

        if (!file_exists($this->_originalPdf)) {
            throw new Exception("Inputted PDF file doesn't exist");
        }
    }


    private function _updatePDF() {
        $pageCtr = $this->_tempPdf->setSourceFile($this->_originalPdf);
        for ($ctr = 1; $ctr <= $pageCtr; $ctr++) {
            $this->_watermarkPage($ctr);
        }
    }

    private function _watermarkPage($page_number) {
        $templateId = $this->_tempPdf->importPage($page_number);
        $templateDimension = $this->_tempPdf->getTemplateSize($templateId);

        if ($templateDimension['w'] > $templateDimension['h']) {
            $orientation = "L";
        } else {
            $orientation = "P";
        }

        $this->_tempPdf->DefOrientation = $orientation;

        $this->_tempPdf->addPage($orientation, array($templateDimension['w'], $templateDimension['h']));

        $this->_tempPdf->SetTextColor(0, 0, 255);

        $text = iconv('UTF-8', 'windows-1252', $this->_watermark);

        $this->_tempPdf->useTemplate($templateId);
        $this->_tempPdf->SetFont('Arial', 'BI', 10, 'blue');
        $this->_tempPdf->MultiCell(0, 5, $text);
    }

    public function savePdf() {
        $this->_updatePDF();
        $this->_tempPdf->Output($this->_newPdf);
    }

}

?>
