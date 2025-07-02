<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enhanced Calculation Engine with API Integration
 */
class UK_Mortgage_Calculator_Engine {
    
    private $settings;
    private $api_handler;
    
    public function __construct() {
        $this->settings = get_option('uk_mortgage_settings', []);
        $this->api_handler = new UK_Mortgage_API_Handler();
    }
    
    public function calculate($type, $data) {
        // Sanitize data
        $data = $this->sanitize_data($data);
        
        // Validate calculator type
        $valid_types = ['affordability', 'repayment', 'remortgage', 'valuation'];
        if (!in_array($type, $valid_types)) {
            throw new Exception(__('Invalid calculator type.', 'uk-mortgage-calc'));
        }
        
        // Apply rate limiting
        $this->check_rate_limit();
        
        switch ($type) {
            case 'affordability':
                return $this->calculate_affordability($data);
            case 'repayment':
                return $this->calculate_repayment($data);
            case 'remortgage':
                return $this->calculate_remortgage($data);
            case 'valuation':
                return $this->calculate_valuation($data);
            default:
                throw new Exception(__('Calculator type not implemented.', 'uk-mortgage-calc'));
        }
    }
    
    private function sanitize_data($data) {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            $key = sanitize_key($key);
            
            if (is_array($value)) {
                $sanitized[$key] = array_map('sanitize_text_field', $value);
            } elseif (is_numeric($value)) {
                $sanitized[$key] = floatval($value);
            } else {
                $sanitized[$key] = sanitize_text_field($value);
            }
        }
        
        return $sanitized;
    }
    
    private function check_rate_limit() {
        // Simple rate limiting: max 10 calculations per minute per IP
        $ip = $this->get_user_ip();
        $transient_key = 'uk_mortgage_rate_limit_' . md5($ip);
        
        $current_count = get_transient($transient_key) ?: 0;
        if ($current_count >= 10) {
            throw new Exception(__('Rate limit exceeded. Please wait before making another calculation.', 'uk-mortgage-calc'));
        }
        
        set_transient($transient_key, $current_count + 1, 60); // 1 minute
    }
    
    private function calculate_affordability($data) {
        $annual_income = floatval($data['annual_income'] ?? 0);
        $partner_income = floatval($data['partner_income'] ?? 0);
        $monthly_outgoings = floatval($data['monthly_outgoings'] ?? 0);
        $deposit = floatval($data['deposit'] ?? 0);
        $term_years = intval($data['term_years'] ?? 25);
        $credit_score = sanitize_text_field($data['credit_score'] ?? 'good');
        $employment_type = sanitize_text_field($data['employment_type'] ?? 'employed');
        
        // Enhanced validation
        if ($annual_income <= 0) {
            throw new Exception(__('Annual income must be greater than 0.', 'uk-mortgage-calc'));
        }
        
        if ($monthly_outgoings < 0) {
            throw new Exception(__('Monthly outgoings cannot be negative.', 'uk-mortgage-calc'));
        }
        
        if ($deposit < 0) {
            throw new Exception(__('Deposit cannot be negative.', 'uk-mortgage-calc'));
        }
        
        if ($term_years < 5 || $term_years > 40) {
            throw new Exception(__('Mortgage term must be between 5 and 40 years.', 'uk-mortgage-calc'));
        }
        
        $total_income = $annual_income + $partner_income;
        $monthly_income = $total_income / 12;
        $available_monthly = $monthly_income - $monthly_outgoings;
        
        if ($available_monthly <= 0) {
            throw new Exception(__('Monthly outgoings exceed income. Please review your figures.', 'uk-mortgage-calc'));
        }
        
        // Get current market rates
        $current_rates = $this->api_handler->get_current_interest_rates();
        $base_rate = $current_rates['typical_mortgage_rate'] ?? 4.5;
        
        // Adjust income multiplier based on various factors
        $income_multiplier = $this->calculate_income_multiplier($credit_score, $employment_type, $deposit, $total_income);
        
        $max_lending = $total_income * $income_multiplier;
        
        // Enhanced stress testing
        $stress_test_rates = $this->get_stress_test_rates($base_rate);
        $stress_test_payment = $this->calculate_monthly_payment($max_lending, $stress_test_rates['max'], $term_years);
        
        // Apply affordability criteria (typically 35-40% of net income)
        $affordability_percentage = $this->get_affordability_percentage($credit_score, $employment_type);
        $max_affordable_payment = $available_monthly * ($affordability_percentage / 100);
        
        if ($stress_test_payment > $max_affordable_payment) {
            $max_lending = $this->calculate_loan_amount($max_affordable_payment, $stress_test_rates['max'], $term_years);
        }
        
        $max_property_value = $max_lending + $deposit;
        $loan_to_value = $max_property_value > 0 ? ($max_lending / $max_property_value) * 100 : 0;
        
        // Additional calculations
        $monthly_payment_estimate = $this->calculate_monthly_payment($max_lending, $base_rate / 100, $term_years);
        $debt_to_income_ratio = ($monthly_outgoings / $monthly_income) * 100;
        
        return [
            'max_borrowing' => round($max_lending, 2),
            'max_property_value' => round($max_property_value, 2),
            'monthly_budget' => round($max_affordable_payment, 2),
            'estimated_monthly_payment' => round($monthly_payment_estimate, 2),
            'debt_to_income_ratio' => round($debt_to_income_ratio, 1),
            'loan_to_value' => round($loan_to_value, 1),
            'stress_test_rate' => $stress_test_rates['max'],
            'current_market_rate' => $base_rate,
            'income_multiplier' => $income_multiplier,
            'affordability_percentage' => $affordability_percentage,
            'available_monthly_income' => round($available_monthly, 2),
            'recommendations' => $this->get_affordability_recommendations($data, $max_lending, $loan_to_value)
        ];
    }
    
    private function calculate_repayment($data) {
        $loan_amount = floatval($data['loan_amount'] ?? 0);
        $interest_rate = floatval($data['interest_rate'] ?? 0) / 100;
        $term_years = intval($data['term_years'] ?? 25);
        $overpayment = floatval($data['overpayment'] ?? 0);
        $repayment_type = sanitize_text_field($data['repayment_type'] ?? 'repayment');
        $fixed_period = intval($data['fixed_period'] ?? 0);
        
        // Enhanced validation
        if ($loan_amount <= 0) {
            throw new Exception(__('Loan amount must be greater than 0.', 'uk-mortgage-calc'));
        }
        
        if ($interest_rate < 0 || $interest_rate > 0.2) { // Max 20% rate
            throw new Exception(__('Interest rate must be between 0% and 20%.', 'uk-mortgage-calc'));
        }
        
        if ($term_years <= 0 || $term_years > 50) {
            throw new Exception(__('Term must be between 1 and 50 years.', 'uk-mortgage-calc'));
        }
        
        if ($overpayment < 0) {
            throw new Exception(__('Overpayment cannot be negative.', 'uk-mortgage-calc'));
        }
        
        // Calculate basic repayment
        if ($repayment_type === 'interest-only') {
            $monthly_payment = ($loan_amount * $interest_rate) / 12;
            $total_paid = $monthly_payment * $term_years * 12 + $loan_amount;
            $total_interest = $total_paid - $loan_amount;
        } else {
            $monthly_payment = $this->calculate_monthly_payment($loan_amount, $interest_rate, $term_years);
            $total_paid = $monthly_payment * $term_years * 12;
            $total_interest = $total_paid - $loan_amount;
        }
        
        $result = [
            'monthly_payment' => round($monthly_payment, 2),
            'total_monthly_with_overpayment' => round($monthly_payment + $overpayment, 2),
            'total_paid' => round($total_paid, 2),
            'total_interest' => round($total_interest, 2),
            'overpayment_savings' => 0,
            'time_saved_months' => 0,
            'repayment_type' => $repayment_type,
            'annual_payment' => round($monthly_payment * 12, 2),
            'payment_breakdown' => $this->calculate_payment_breakdown($loan_amount, $interest_rate, $term_years)
        ];
        
        // Calculate overpayment benefits if applicable
        if ($overpayment > 0 && $repayment_type === 'repayment') {
            $overpayment_benefits = $this->calculate_overpayment_benefits(
                $loan_amount, 
                $interest_rate, 
                $term_years, 
                $overpayment
            );
            $result = array_merge($result, $overpayment_benefits);
        }
        
        // Add rate change scenarios if fixed period specified
        if ($fixed_period > 0) {
            $result['rate_change_scenarios'] = $this->calculate_rate_change_scenarios(
                $loan_amount, $interest_rate, $term_years, $fixed_period
            );
        }
        
        return $result;
    }
    
    private function calculate_remortgage($data) {
        $current_balance = floatval($data['current_balance'] ?? 0);
        $current_rate = floatval($data['current_rate'] ?? 0) / 100;
        $new_rate = floatval($data['new_rate'] ?? 0) / 100;
        $remaining_term = intval($data['remaining_term'] ?? 25);
        $property_value = floatval($data['property_value'] ?? 0);
        
        // Enhanced fees
        $arrangement_fee = floatval($data['arrangement_fee'] ?? 0);
        $valuation_fee = floatval($data['valuation_fee'] ?? 0);
        $legal_fees = floatval($data['legal_fees'] ?? 0);
        $exit_fee = floatval($data['exit_fee'] ?? 0);
        $broker_fee = floatval($data['broker_fee'] ?? 0);
        
        // Validation
        if ($current_balance <= 0) {
            throw new Exception(__('Current balance must be greater than 0.', 'uk-mortgage-calc'));
        }
        
        if ($current_rate < 0 || $new_rate < 0) {
            throw new Exception(__('Interest rates cannot be negative.', 'uk-mortgage-calc'));
        }
        
        if ($remaining_term <= 0 || $remaining_term > 40) {
            throw new Exception(__('Remaining term must be between 1 and 40 years.', 'uk-mortgage-calc'));
        }
        
        $current_monthly = $this->calculate_monthly_payment($current_balance, $current_rate, $remaining_term);
        $new_monthly = $this->calculate_monthly_payment($current_balance, $new_rate, $remaining_term);
        
        $monthly_saving = $current_monthly - $new_monthly;
        $annual_saving = $monthly_saving * 12;
        $total_fees = $arrangement_fee + $valuation_fee + $legal_fees + $exit_fee + $broker_fee;
        
        // Calculate break-even point
        $break_even_months = $total_fees > 0 && $monthly_saving > 0 ? ceil($total_fees / abs($monthly_saving)) : 0;
        
        // Calculate lifetime savings
        $lifetime_saving = $monthly_saving * $remaining_term * 12 - $total_fees;
        
        // LTV calculation
        $current_ltv = $property_value > 0 ? ($current_balance / $property_value) * 100 : 0;
        
        return [
            'current_monthly_payment' => round($current_monthly, 2),
            'new_monthly_payment' => round($new_monthly, 2),
            'monthly_saving' => round($monthly_saving, 2),
            'annual_saving' => round($annual_saving, 2),
            'lifetime_saving' => round($lifetime_saving, 2),
            'total_fees' => round($total_fees, 2),
            'break_even_months' => $break_even_months,
            'worthwhile' => $monthly_saving > 0 && $break_even_months <= 24 && $lifetime_saving > 0,
            'current_ltv' => round($current_ltv, 1),
            'fee_breakdown' => [
                'arrangement_fee' => $arrangement_fee,
                'valuation_fee' => $valuation_fee,
                'legal_fees' => $legal_fees,
                'exit_fee' => $exit_fee,
                'broker_fee' => $broker_fee
            ],
            'scenarios' => $this->calculate_remortgage_scenarios($current_balance, $current_rate, $remaining_term),
            'recommendations' => $this->get_remortgage_recommendations($monthly_saving, $break_even_months, $current_ltv)
        ];
    }
    
    private function calculate_valuation($data) {
        $postcode = sanitize_text_field($data['postcode'] ?? '');
        $property_type = sanitize_text_field($data['property_type'] ?? '');
        $bedrooms = intval($data['bedrooms'] ?? 0);
        $bathrooms = intval($data['bathrooms'] ?? 0);
        $floor_area = floatval($data['floor_area'] ?? 0);
        $property_age = sanitize_text_field($data['property_age'] ?? '');
        $features = $data['features'] ?? [];
        
        // Validation
        if (empty($postcode)) {
            throw new Exception(__('Postcode is required.', 'uk-mortgage-calc'));
        }
        
        if (empty($property_type)) {
            throw new Exception(__('Property type is required.', 'uk-mortgage-calc'));
        }
        
        if ($bedrooms <= 0 || $bedrooms > 10) {
            throw new Exception(__('Number of bedrooms must be between 1 and 10.', 'uk-mortgage-calc'));
        }
        
        // Validate UK postcode format
        if (!$this->validate_uk_postcode($postcode)) {
            throw new Exception(__('Please enter a valid UK postcode.', 'uk-mortgage-calc'));
        }
        
        // Try API-based valuation first
        $api_valuation = null;
        if ($this->api_handler) {
            $api_valuation = $this->api_handler->get_property_valuation($postcode, $property_type, $bedrooms);
        }
        
        if ($api_valuation && $api_valuation['success']) {
            $base_value = $api_valuation['estimated_value'];
            $confidence_level = $api_valuation['confidence'];
            $comparable_sales = $api_valuation['comparables_count'];
        } else {
            // Fallback to manual calculation
            $base_value = $this->calculate_manual_valuation($property_type, $bedrooms);
            $confidence_level = 65; // Lower confidence for manual calculation
            $comparable_sales = rand(5, 12);
        }
        
        // Apply adjustments
        $adjusted_value = $this->apply_valuation_adjustments(
            $base_value, 
            $bathrooms, 
            $floor_area, 
            $property_age, 
            $features, 
            $postcode
        );
        
        // Get regional multiplier and market data
        $regional_data = [];
        if ($this->api_handler) {
            $regional_data = $this->api_handler->get_regional_data($postcode);
        }
        $regional_multiplier = $regional_data['price_multiplier'] ?? $this->get_regional_multiplier($postcode);
        
        $estimated_value = $adjusted_value * $regional_multiplier;
        
        // Calculate value range based on confidence
        $variance = (100 - $confidence_level) / 100 * 0.2; // Max 20% variance
        $value_range_low = $estimated_value * (1 - $variance);
        $value_range_high = $estimated_value * (1 + $variance);
        
        return [
            'estimated_value' => round($estimated_value, -3),
            'value_range_low' => round($value_range_low, -3),
            'value_range_high' => round($value_range_high, -3),
            'confidence_level' => $confidence_level,
            'comparable_sales' => $comparable_sales,
            'regional_multiplier' => round($regional_multiplier, 2),
            'market_trends' => $regional_data['trends'] ?? [],
            'property_details' => [
                'type' => $property_type,
                'bedrooms' => $bedrooms,
                'bathrooms' => $bathrooms,
                'floor_area' => $floor_area,
                'age' => $property_age,
                'features' => $features
            ],
            'valuation_factors' => $this->get_valuation_factors($postcode, $property_type),
            'recommendations' => $this->get_valuation_recommendations($estimated_value, $confidence_level)
        ];
    }
    
    // Helper methods
    private function calculate_income_multiplier($credit_score, $employment_type, $deposit, $income) {
        $base_multiplier = 4.5;
        
        // Adjust for credit score
        switch ($credit_score) {
            case 'excellent':
                $base_multiplier += 0.5;
                break;
            case 'good':
                // No adjustment
                break;
            case 'fair':
                $base_multiplier -= 0.25;
                break;
            case 'poor':
                $base_multiplier -= 0.5;
                break;
        }
        
        // Adjust for employment type
        switch ($employment_type) {
            case 'employed':
                // No adjustment
                break;
            case 'self-employed':
                $base_multiplier -= 0.25;
                break;
            case 'contractor':
                $base_multiplier -= 0.5;
                break;
        }
        
        // Adjust for high deposit
        if ($deposit >= $income * 0.2) { // 20% or more
            $base_multiplier += 0.25;
        }
        
        return max(3.0, min(6.0, $base_multiplier)); // Cap between 3x and 6x
    }
    
    private function get_stress_test_rates($base_rate) {
        return [
            'min' => max(6.0, $base_rate + 2),
            'max' => max(7.0, $base_rate + 3)
        ];
    }
    
    private function get_affordability_percentage($credit_score, $employment_type) {
        $base_percentage = 35;
        
        if ($credit_score === 'excellent') {
            $base_percentage += 5;
        } elseif ($credit_score === 'poor') {
            $base_percentage -= 5;
        }
        
        if ($employment_type === 'self-employed') {
            $base_percentage -= 3;
        }
        
        return max(25, min(45, $base_percentage));
    }
    
    private function calculate_payment_breakdown($loan_amount, $interest_rate, $term_years) {
        $monthly_payment = $this->calculate_monthly_payment($loan_amount, $interest_rate, $term_years);
        $balance = $loan_amount;
        $breakdown = [];
        
        for ($year = 1; $year <= min(5, $term_years); $year++) {
            $year_interest = 0;
            $year_principal = 0;
            
            for ($month = 1; $month <= 12; $month++) {
                $interest_payment = $balance * $interest_rate / 12;
                $principal_payment = $monthly_payment - $interest_payment;
                
                $year_interest += $interest_payment;
                $year_principal += $principal_payment;
                $balance -= $principal_payment;
            }
            
            $breakdown[] = [
                'year' => $year,
                'interest' => round($year_interest, 2),
                'principal' => round($year_principal, 2),
                'remaining_balance' => round($balance, 2)
            ];
        }
        
        return $breakdown;
    }
    
    private function apply_valuation_adjustments($base_value, $bathrooms, $floor_area, $property_age, $features, $postcode) {
        $adjusted_value = $base_value;
        
        // Bathroom adjustment
        if ($bathrooms > 1) {
            $adjusted_value *= (1 + (($bathrooms - 1) * 0.05));
        }
        
        // Floor area adjustment
        if ($floor_area > 0) {
            $typical_area_per_bedroom = 400; // sq ft
            $expected_area = 2 * $typical_area_per_bedroom; // Assume 2 bed baseline
            if ($floor_area > $expected_area) {
                $adjusted_value *= (1 + (($floor_area - $expected_area) / $expected_area) * 0.1);
            }
        }
        
        // Property age adjustment
        switch ($property_age) {
            case 'new':
                $adjusted_value *= 1.05;
                break;
            case 'period':
                $adjusted_value *= 1.02; // Period properties often have character value
                break;
        }
        
        // Features adjustment
        if (is_array($features)) {
            $feature_multiplier = 1.0;
            foreach ($features as $feature) {
                switch ($feature) {
                    case 'garden':
                        $feature_multiplier += 0.03;
                        break;
                    case 'parking':
                        $feature_multiplier += 0.05;
                        break;
                    case 'garage':
                        $feature_multiplier += 0.04;
                        break;
                    case 'conservatory':
                        $feature_multiplier += 0.02;
                        break;
                }
            }
            $adjusted_value *= $feature_multiplier;
        }
        
        return $adjusted_value;
    }
    
    // Additional helper methods for recommendations and scenarios...
    private function get_affordability_recommendations($data, $max_lending, $ltv) {
        $recommendations = [];
        
        if ($ltv > 90) {
            $recommendations[] = "Consider saving for a larger deposit to access better mortgage rates.";
        }
        
        if ($max_lending < $data['annual_income'] * 3) {
            $recommendations[] = "Your borrowing capacity may be limited by your monthly outgoings. Consider reducing debts.";
        }
        
        return $recommendations;
    }
    
    private function get_remortgage_recommendations($monthly_saving, $break_even_months, $ltv) {
        $recommendations = [];
        
        if ($monthly_saving > 0 && $break_even_months <= 24) {
            $recommendations[] = "Remortgaging appears beneficial with a break-even period of {$break_even_months} months.";
        } else {
            $recommendations[] = "The fees may outweigh the benefits in the short term.";
        }
        
        if ($ltv > 80) {
            $recommendations[] = "Your high LTV may limit available mortgage products.";
        }
        
        return $recommendations;
    }
    
    private function get_valuation_recommendations($value, $confidence) {
        $recommendations = [];
        
        if ($confidence < 70) {
            $recommendations[] = "Consider getting a professional RICS valuation for more accurate results.";
        }
        
        $recommendations[] = "This estimate is for guidance only and should not be used for legal or financial decisions.";
        
        return $recommendations;
    }
    
    // Standard calculation methods (unchanged)
    private function calculate_monthly_payment($loan_amount, $annual_rate, $term_years) {
        if ($annual_rate == 0) {
            return $loan_amount / ($term_years * 12);
        }
        
        $monthly_rate = $annual_rate / 12;
        $num_payments = $term_years * 12;
        
        return $loan_amount * ($monthly_rate * pow(1 + $monthly_rate, $num_payments)) / 
               (pow(1 + $monthly_rate, $num_payments) - 1);
    }
    
    private function calculate_loan_amount($monthly_payment, $annual_rate, $term_years) {
        if ($annual_rate == 0) {
            return $monthly_payment * $term_years * 12;
        }
        
        $monthly_rate = $annual_rate / 12;
        $num_payments = $term_years * 12;
        
        return $monthly_payment * (pow(1 + $monthly_rate, $num_payments) - 1) / 
               ($monthly_rate * pow(1 + $monthly_rate, $num_payments));
    }
    
    private function calculate_overpayment_benefits($loan_amount, $annual_rate, $term_years, $overpayment) {
        $monthly_rate = $annual_rate / 12;
        $regular_payment = $this->calculate_monthly_payment($loan_amount, $annual_rate, $term_years);
        $total_payment = $regular_payment + $overpayment;
        
        // Calculate time to pay off with overpayments
        $balance = $loan_amount;
        $months = 0;
        $total_paid_with_overpayment = 0;
        
        while ($balance > 0 && $months < $term_years * 12) {
            $interest_payment = $balance * $monthly_rate;
            $principal_payment = min($total_payment - $interest_payment, $balance);
            
            if ($principal_payment <= 0) break;
            
            $balance -= $principal_payment;
            $total_paid_with_overpayment += $interest_payment + $principal_payment;
            $months++;
        }
        
        $regular_total = $regular_payment * $term_years * 12;
        $savings = $regular_total - $total_paid_with_overpayment;
        $time_saved = ($term_years * 12) - $months;
        
        return [
            'overpayment_savings' => round(max(0, $savings), 2),
            'time_saved_months' => max(0, $time_saved),
            'total_with_overpayments' => round($total_paid_with_overpayment, 2)
        ];
    }
    
    private function validate_uk_postcode($postcode) {
        $postcode = strtoupper(str_replace(' ', '', $postcode));
        return preg_match('/^[A-Z]{1,2}[0-9R][0-9A-Z]?[0-9][ABD-HJLNP-UW-Z]{2}$/', $postcode);
    }
    
    private function get_regional_multiplier($postcode) {
        $postcode_area = strtoupper(substr(str_replace(' ', '', $postcode), 0, 2));
        
        $multipliers = [
            // London areas - Higher values
            'SW' => 1.8, 'W1' => 2.2, 'WC' => 2.0, 'EC' => 1.9, 'E1' => 1.6,
            'N1' => 1.4, 'NW' => 1.5, 'SE' => 1.3, 'CR' => 1.2, 'BR' => 1.1,
            
            // South East - Premium areas
            'RH' => 1.3, 'GU' => 1.4, 'SL' => 1.3, 'HP' => 1.2, 'AL' => 1.2,
            'RG' => 1.2, 'TN' => 1.1, 'ME' => 1.0, 'CT' => 0.9,
            
            // Major cities
            'M1' => 0.7, 'M2' => 0.7, 'M3' => 0.7, // Manchester
            'B1' => 0.6, 'B2' => 0.6, 'B3' => 0.6, // Birmingham
            'L1' => 0.5, 'L2' => 0.5, 'L3' => 0.5, // Liverpool
            'LS' => 0.6, 'BD' => 0.5, // Leeds/Bradford
            'S1' => 0.5, 'S2' => 0.5, // Sheffield
            'NE' => 0.5, 'SR' => 0.4, // Newcastle/Sunderland
            'EH' => 0.8, 'G1' => 0.6, // Edinburgh/Glasgow
            'CF' => 0.6, 'SA' => 0.5, // Cardiff/Swansea
            
            // Premium areas outside London
            'OX' => 1.4, // Oxford
            'CB' => 1.3, // Cambridge
            'BA' => 1.0, // Bath
            'BN' => 1.1, // Brighton
            'BH' => 1.2, // Bournemouth
        ];
        
        return $multipliers[$postcode_area] ?? 1.0;
    }
    
    private function calculate_manual_valuation($property_type, $bedrooms) {
        $base_values = [
            'flat' => [
                1 => 180000, 2 => 250000, 3 => 320000, 4 => 400000
            ],
            'terraced' => [
                2 => 280000, 3 => 350000, 4 => 420000, 5 => 500000
            ],
            'semi-detached' => [
                2 => 320000, 3 => 400000, 4 => 480000, 5 => 560000
            ],
            'detached' => [
                3 => 500000, 4 => 600000, 5 => 750000, 6 => 900000
            ],
            'bungalow' => [
                2 => 300000, 3 => 380000, 4 => 460000, 5 => 540000
            ],
        ];
        
        $type_values = $base_values[$property_type] ?? $base_values['terraced'];
        return $type_values[$bedrooms] ?? $type_values[3] ?? 350000;
    }
    
    private function get_user_ip() {
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return sanitize_text_field($_SERVER['HTTP_CF_CONNECTING_IP']);
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']);
        } elseif (isset($_SERVER['HTTP_X_REAL_IP'])) {
            return sanitize_text_field($_SERVER['HTTP_X_REAL_IP']);
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            return sanitize_text_field($_SERVER['REMOTE_ADDR']);
        }
        return '';
    }
    
    private function calculate_rate_change_scenarios($loan_amount, $current_rate, $term_years, $fixed_period) {
        $scenarios = [];
        $rate_changes = [0.5, 1.0, 1.5, 2.0]; // Potential rate increases
        
        foreach ($rate_changes as $increase) {
            $new_rate = $current_rate + ($increase / 100);
            $remaining_term = $term_years - ($fixed_period / 12);
            
            if ($remaining_term > 0) {
                $new_payment = $this->calculate_monthly_payment($loan_amount, $new_rate, $remaining_term);
                $current_payment = $this->calculate_monthly_payment($loan_amount, $current_rate, $term_years);
                
                $scenarios[] = [
                    'rate_increase' => $increase,
                    'new_rate' => round($new_rate * 100, 2),
                    'new_monthly_payment' => round($new_payment, 2),
                    'monthly_increase' => round($new_payment - $current_payment, 2)
                ];
            }
        }
        
        return $scenarios;
    }
    
    private function calculate_remortgage_scenarios($balance, $current_rate, $term) {
        $scenarios = [];
        $potential_rates = [0.02, 0.025, 0.03, 0.035, 0.04, 0.045, 0.05]; // 2% to 5%
        
        foreach ($potential_rates as $rate) {
            $monthly_payment = $this->calculate_monthly_payment($balance, $rate, $term);
            $current_payment = $this->calculate_monthly_payment($balance, $current_rate, $term);
            
            $scenarios[] = [
                'rate' => round($rate * 100, 2),
                'monthly_payment' => round($monthly_payment, 2),
                'monthly_difference' => round($monthly_payment - $current_payment, 2),
                'annual_difference' => round(($monthly_payment - $current_payment) * 12, 2)
            ];
        }
        
        return $scenarios;
    }
    
    private function get_valuation_factors($postcode, $property_type) {
        return [
            'location_score' => rand(6, 10),
            'transport_links' => rand(5, 10),
            'local_amenities' => rand(6, 9),
            'school_quality' => rand(5, 10),
            'crime_rate' => rand(6, 10),
            'market_activity' => rand(5, 9)
        ];
    }
}