<?php

// =============================================================================
// FILE: includes/class-pdf-generator.php
// =============================================================================

/**
 * PDF Report Generator using TCPDF
 */
class UK_Mortgage_PDF_Generator {
    
    private $tcpdf;
    
    public function __construct() {
        if (!class_exists('TCPDF')) {
            require_once(ABSPATH . 'wp-content/plugins/uk-mortgage-calculator/lib/tcpdf/tcpdf.php');
        }
    }
    
    public function generate($calculator_type, $inputs, $results) {
        $this->tcpdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $this->tcpdf->SetCreator('UK Mortgage Calculator');
        $this->tcpdf->SetAuthor(get_bloginfo('name'));
        $this->tcpdf->SetTitle('Mortgage Calculation Report');
        
        // Set margins
        $this->tcpdf->SetMargins(15, 20, 15);
        $this->tcpdf->SetHeaderMargin(10);
        $this->tcpdf->SetFooterMargin(10);
        
        // Add page
        $this->tcpdf->AddPage();
        
        // Generate content based on calculator type
        switch ($calculator_type) {
            case 'affordability':
                $this->generate_affordability_pdf($inputs, $results);
                break;
            case 'repayment':
                $this->generate_repayment_pdf($inputs, $results);
                break;
            case 'remortgage':
                $this->generate_remortgage_pdf($inputs, $results);
                break;
            case 'valuation':
                $this->generate_valuation_pdf($inputs, $results);
                break;
        }
        
        // Save PDF
        $filename = 'mortgage-report-' . date('Y-m-d-H-i-s') . '.pdf';
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . $filename;
        
        $this->tcpdf->Output($file_path, 'F');
        
        return $upload_dir['url'] . '/' . $filename;
    }
    
    private function generate_affordability_pdf($inputs, $results) {
        $html = '<h1>Mortgage Affordability Report</h1>';
        $html .= '<h2>Your Details</h2>';
        $html .= '<p><strong>Annual Income:</strong> £' . number_format($inputs['annual_income']) . '</p>';
        $html .= '<p><strong>Partner Income:</strong> £' . number_format($inputs['partner_income'] ?? 0) . '</p>';
        $html .= '<p><strong>Monthly Outgoings:</strong> £' . number_format($inputs['monthly_outgoings']) . '</p>';
        $html .= '<p><strong>Available Deposit:</strong> £' . number_format($inputs['deposit']) . '</p>';
        
        $html .= '<h2>Results</h2>';
        $html .= '<p><strong>Maximum Borrowing:</strong> £' . number_format($results['max_borrowing']) . '</p>';
        $html .= '<p><strong>Maximum Property Value:</strong> £' . number_format($results['max_property_value']) . '</p>';
        $html .= '<p><strong>Monthly Budget:</strong> £' . number_format($results['monthly_budget']) . '</p>';
        $html .= '<p><strong>Loan to Value:</strong> ' . $results['loan_to_value'] . '%</p>';
        
        $html .= '<h2>Important Notes</h2>';
        $html .= '<ul>';
        $html .= '<li>This calculation is based on typical UK lending criteria</li>';
        $html .= '<li>Actual lending decisions depend on credit history and lender policies</li>';
        $html .= '<li>Consider additional costs like legal fees, surveys, and moving costs</li>';
        $html .= '</ul>';
        
        $this->tcpdf->writeHTML($html, true, false, true, false, '');
    }
    
    private function generate_repayment_pdf($inputs, $results) {
        // Similar implementation for repayment calculator
        $html = '<h1>Mortgage Repayment Report</h1>';
        // Add content...
        $this->tcpdf->writeHTML($html, true, false, true, false, '');
    }
    
    private function generate_remortgage_pdf($inputs, $results) {
        // Similar implementation for remortgage calculator
        $html = '<h1>Remortgage Analysis Report</h1>';
        // Add content...
        $this->tcpdf->writeHTML($html, true, false, true, false, '');
    }
    
    private function generate_valuation_pdf($inputs, $results) {
        // Similar implementation for valuation calculator
        $html = '<h1>Property Valuation Report</h1>';
        // Add content...
        $this->tcpdf->writeHTML($html, true, false, true, false, '');
    }
}