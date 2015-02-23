<?php

class PDFWatermarker {

    private $_originalPdf;
    private $_newPdf;
    private $_tempPdf;
    private $_watermark;

    /**
     * Creates an instance of the watermarker
     *
     * @param string $originalPDF - inputted PDF path
     * @param string $newPDF - outputted PDF path
     * @param mixed $watermark Watermark - watermark text
     *
     * @return void
     */
    public function __construct($originalPdf, $newPdf, $watermark) {

        $this->_originalPdf = $originalPdf;
        $this->_newPdf = $newPdf;
        $this->_tempPdf = new FPDI();
        $this->_watermark = $watermark;

        $this->_validateAssets();
    }

    /**
     * Ensures that the watermark and the PDF file are valid
     *
     * @return void
     */
    private function _validateAssets() {

        if (!file_exists($this->_originalPdf)) {
            throw new Exception("Inputted PDF file doesn't exist");
        }
    }

    /**
     * Loop through the pages while applying the watermark
     *
     * @return void
     */
    private function _updatePDF() {
        $pageCtr = $this->_tempPdf->setSourceFile($this->_originalPdf);
        for ($ctr = 1; $ctr <= $pageCtr; $ctr++) {
            $this->_watermarkPage($ctr);
        }
    }

    /**
     * Apply the watermark to each page on the PDF file
     *
     * @param int $page_number - page number
     *
     * @return void
     */
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

        //$wWidth = ($this->_watermark->getWidth() / 96) * 25.4; //in mm
        //$wHeight = ($this->_watermark->getHeight() / 96) * 25.4; //in mm

        $this->_tempPdf->SetTextColor(0, 0, 255);

        $text = iconv('UTF-8', 'windows-1252', $this->_watermark);

        //TODO: calcular a posição do texto nas margens do documento.
        $this->_tempPdf->useTemplate($templateId);
        $this->_tempPdf->SetFont('Arial', 'BI', 10, 'blue');
        //$this->_tempPdf->Cell(1,1);
        $this->_tempPdf->Write(0, $text);
    }

    /**
     * Save the PDF to the specified location
     *
     * @return void
     */
    public function savePdf() {
        $this->_updatePDF();
        $this->_tempPdf->Output($this->_newPdf);
    }

}

?>
