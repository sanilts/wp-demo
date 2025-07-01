/**
 * UK Mortgage Calculator JavaScript
 * Handles real-time calculations and UI interactions
 */

(function($) {
    'use strict';

    const UKMortgageCalculator = {
        
        init: function() {
            this.bindEvents();
            this.initTooltips();
            this.loadInterestRates();
        },
        
        bindEvents: function() {
            // Form submission
            $(document).on('submit', '.calculator-form', this.handleFormSubmit);
            
            // Real-time calculation on input change
            $(document).on('input', '.calculator-form input, .calculator-form select', 
                          this.debounce(this.handleInputChange, 500));
            
            // PDF download
            $(document).on('click', '.pdf-download-btn', this.downloadPDF);
            
            // Email report
            $(document).on('click', '.email-report-btn', this.emailReport);
            
            // Postcode validation
            $(document).on('blur', 'input[name="postcode"]', this.validatePostcode);
            
            // Number formatting
            $(document).on('blur', 'input[type="number"]', this.formatNumber);
            
            // Rate comparison
            $(document).on('click', '.compare-rates-btn', this.showRateComparison);
        },
        
        handleFormSubmit: function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $calculator = $form.closest('.uk-mortgage-calc');
            const calculatorType = $calculator.data('calculator-type');
            
            UKMortgageCalculator.calculate(calculatorType, $form);
        },
        
        handleInputChange: function(e) {
            const $input = $(e.target);
            const $form = $input.closest('.calculator-form');
            const $calculator = $form.closest('.uk-mortgage-calc');
            const calculatorType = $calculator.data('calculator-type');
            
            // Only auto-calculate if all required fields are filled
            if (UKMortgageCalculator.validateForm($form)) {
                UKMortgageCalculator.calculate(calculatorType, $form);
            }
        },
        
        calculate: function(calculatorType, $form) {
            const $calculator = $form.closest('.uk-mortgage-calc');
            const $loading = $calculator.find('.calculator-loading');
            const $result = $calculator.find('.calculator-result');
            const $buttons = $calculator.find('.pdf-download-btn, .email-report-btn');
            
            // Show loading
            $loading.show();
            $result.hide();
            
            // Collect form data
            const formData = this.collectFormData($form);
            
            // Perform calculation
            $.ajax({
                url: ukMortgageAjax.ajax_url,
                type: 'POST',
                data: {
                    action: 'calculate_mortgage',
                    calculator_type: calculatorType,
                    data: formData,
                    nonce: ukMortgageAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        UKMortgageCalculator.displayResults(response.data, calculatorType, $calculator);
                        $buttons.show();
                        
                        // Track analytics
                        UKMortgageCalculator.trackCalculation(calculatorType, formData, response.data);
                    } else {
                        UKMortgageCalculator.showError('Calculation failed. Please check your inputs.');
                    }
                },
                error: function() {
                    UKMortgageCalculator.showError('Connection error. Please try again.');
                },
                complete: function() {
                    $loading.hide();
                }
            });
        },
        
        collectFormData: function($form) {
            const data = {};
            
            $form.find('input, select').each(function() {
                const $field = $(this);
                const name = $field.attr('name');
                let value = $field.val();
                
                // Handle checkboxes
                if ($field.attr('type') === 'checkbox') {
                    if (!data[name]) data[name] = [];
                    if ($field.is(':checked')) {
                        data[name].push(value);
                    }
                } else if (name && value) {
                    // Convert numeric values
                    if ($field.attr('type') === 'number') {
                        value = parseFloat(value) || 0;
                    }
                    data[name] = value;
                }
            });
            
            // Calculate total fees for remortgage
            if (data.arrangement_fee || data.valuation_fee || data.legal_fees || data.exit_fee) {
                data.fees = (data.arrangement_fee || 0) + (data.valuation_fee || 0) + 
                           (data.legal_fees || 0) + (data.exit_fee || 0);
            }
            
            return data;
        },
        
        displayResults: function(results, calculatorType, $calculator) {
            const $result = $calculator.find('.calculator-result');
            const $content = $result.find('.result-content');
            
            let html = '';
            
            switch (calculatorType) {
                case 'affordability':
                    html = this.renderAffordabilityResults(results);
                    break;
                case 'repayment':
                    html = this.renderRepaymentResults(results);
                    break;
                case 'remortgage':
                    html = this.renderRemortgageResults(results);
                    break;
                case 'valuation':
                    html = this.renderValuationResults(results);
                    break;
            }
            
            $content.html(html);
            $result.show();
            
            // Add animations
            $result.addClass('fade-in');
            setTimeout(() => $result.removeClass('fade-in'), 500);
        },
        
        renderAffordabilityResults: function(results) {
            return `
                <div class="results-grid">
                    <div class="result-card primary">
                        <h4>Maximum Borrowing</h4>
                        <div class="amount">£${this.formatCurrency(results.max_borrowing)}</div>
                    </div>
                    <div class="result-card">
                        <h4>Maximum Property Value</h4>
                        <div class="amount">£${this.formatCurrency(results.max_property_value)}</div>
                    </div>
                    <div class="result-card">
                        <h4>Monthly Budget</h4>
                        <div class="amount">£${this.formatCurrency(results.monthly_budget)}</div>
                    </div>
                    <div class="result-card">
                        <h4>Loan to Value Ratio</h4>
                        <div class="percentage">${results.loan_to_value}%</div>
                    </div>
                </div>
                <div class="result-details">
                    <h5>Calculation Details</h5>
                    <ul>
                        <li>Based on UK lending criteria (typically 4.5x annual income)</li>
                        <li>Stress tested at 7% interest rate</li>
                        <li>Assumes 35% of available income for mortgage payments</li>
                        <li>Debt-to-income ratio: ${results.debt_to_income_ratio}%</li>
                    </ul>
                </div>
                <div class="next-steps">
                    <h5>Next Steps</h5>
                    <p>Get a mortgage in principle from a lender to confirm your borrowing capacity. 
                       Consider speaking to a mortgage broker for the best deals.</p>
                </div>
            `;
        },
        
        renderRepaymentResults: function(results) {
            const overpaymentSection = results.overpayment_savings > 0 ? `
                <div class="overpayment-benefits">
                    <h5>Overpayment Benefits</h5>
                    <div class="benefits-grid">
                        <div class="benefit">
                            <strong>Interest Saved:</strong> £${this.formatCurrency(results.overpayment_savings)}
                        </div>
                        <div class="benefit">
                            <strong>Time Saved:</strong> ${results.time_saved_months} months
                        </div>
                    </div>
                </div>
            ` : '';
            
            return `
                <div class="results-grid">
                    <div class="result-card primary">
                        <h4>Monthly Payment</h4>
                        <div class="amount">£${this.formatCurrency(results.monthly_payment)}</div>
                    </div>
                    ${results.total_monthly_with_overpayment !== results.monthly_payment ? `
                    <div class="result-card">
                        <h4>With Overpayments</h4>
                        <div class="amount">£${this.formatCurrency(results.total_monthly_with_overpayment)}</div>
                    </div>
                    ` : ''}
                    <div class="result-card">
                        <h4>Total Interest</h4>
                        <div class="amount">£${this.formatCurrency(results.total_interest)}</div>
                    </div>
                    <div class="result-card">
                        <h4>Total Amount Paid</h4>
                        <div class="amount">£${this.formatCurrency(results.total_paid)}</div>
                    </div>
                </div>
                ${overpaymentSection}
                <div class="repayment-chart">
                    <canvas id="repayment-chart-${Date.now()}"></canvas>
                </div>
            `;
        },
        
        renderRemortgageResults: function(results) {
            const worthwhileClass = results.worthwhile ? 'positive' : 'negative';
            const worthwhileText = results.worthwhile ? 
                'Remortgaging appears worthwhile' : 
                'Remortgaging may not be beneficial';
            
            return `
                <div class="results-grid">
                    <div class="result-card ${worthwhileClass}">
                        <h4>Monthly Saving</h4>
                        <div class="amount">£${this.formatCurrency(Math.abs(results.monthly_saving))}</div>
                        <small>${results.monthly_saving >= 0 ? 'Saving' : 'Extra cost'}</small>
                    </div>
                    <div class="result-card">
                        <h4>Annual Saving</h4>
                        <div class="amount">£${this.formatCurrency(Math.abs(results.annual_saving))}</div>
                    </div>
                    <div class="result-card">
                        <h4>Break Even</h4>
                        <div class="period">${results.break_even_months} months</div>
                    </div>
                    <div class="result-card">
                        <h4>Total Fees</h4>
                        <div class="amount">£${this.formatCurrency(results.total_fees)}</div>
                    </div>
                </div>
                <div class="recommendation ${worthwhileClass}">
                    <h5>Recommendation</h5>
                    <p><strong>${worthwhileText}</strong></p>
                    ${results.worthwhile ? 
                        '<p>The monthly savings justify the fees, and you would break even within 2 years.</p>' : 
                        '<p>The fees outweigh the potential savings in the short term.</p>'
                    }
                </div>
                <div class="comparison-table">
                    <table>
                        <thead>
                            <tr>
                                <th></th>
                                <th>Current Mortgage</th>
                                <th>New Mortgage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Monthly Payment</td>
                                <td>£${this.formatCurrency(results.current_monthly_payment)}</td>
                                <td>£${this.formatCurrency(results.new_monthly_payment)}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            `;
        },
        
        renderValuationResults: function(results) {
            return `
                <div class="results-grid">
                    <div class="result-card primary">
                        <h4>Estimated Value</h4>
                        <div class="amount">£${this.formatCurrency(results.estimated_value)}</div>
                    </div>
                    <div class="result-card">
                        <h4>Value Range</h4>
                        <div class="range">
                            £${this.formatCurrency(results.value_range_low)} - 
                            £${this.formatCurrency(results.value_range_high)}
                        </div>
                    </div>
                    <div class="result-card">
                        <h4>Confidence Level</h4>
                        <div class="percentage">${results.confidence_level}%</div>
                    </div>
                    <div class="result-card">
                        <h4>Comparable Sales</h4>
                        <div class="count">${results.comparable_sales}</div>
                    </div>
                </div>
                <div class="valuation-disclaimer">
                    <h5>Important Notice</h5>
                    <p>This is an automated estimate based on available data. For mortgage purposes, 
                       you will need a formal valuation from a RICS qualified surveyor.</p>
                </div>
                <div class="market-insights">
                    <h5>Local Market Insights</h5>
                    <p>Based on recent sales of similar properties in your area. 
                       Actual value may vary based on condition, improvements, and local factors.</p>
                </div>
            `;
        },
        
        validateForm: function($form) {
            let isValid = true;
            
            $form.find('[required]').each(function() {
                const $field = $(this);
                if (!$field.val() || $field.val().trim() === '') {
                    isValid = false;
                    return false;
                }
            });
            
            return isValid;
        },
        
        validatePostcode: function() {
            const $input = $(this);
            const postcode = $input.val().toUpperCase().replace(/\s/g, '');
            const regex = /^[A-Z]{1,2}[0-9R][0-9A-Z]?[0-9][ABD-HJLNP-UW-Z]{2}$/;
            
            if (postcode && !regex.test(postcode)) {
                $input.addClass('error');
                UKMortgageCalculator.showFieldError($input, 'Please enter a valid UK postcode');
            } else {
                $input.removeClass('error');
                UKMortgageCalculator.clearFieldError($input);
                
                // Format postcode
                if (postcode.length >= 5) {
                    const formatted = postcode.slice(0, -3) + ' ' + postcode.slice(-3);
                    $input.val(formatted);
                }
            }
        },
        
        formatNumber: function() {
            const $input = $(this);
            const value = parseFloat($input.val());
            
            if (!isNaN(value) && $input.attr('name') !== 'interest_rate') {
                $input.val(value.toLocaleString('en-GB'));
            }
        },
        
        formatCurrency: function(amount) {
            return new Intl.NumberFormat('en-GB', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(amount);
        },
        
        showError: function(message) {
            const $error = $('<div class="calculator-error alert alert-danger">' + message + '</div>');
            $('.uk-mortgage-calc').prepend($error);
            
            setTimeout(() => $error.fadeOut(), 5000);
        },
        
        showFieldError: function($field, message) {
            $field.addClass('error');
            const $error = $('<div class="field-error">' + message + '</div>');
            $field.after($error);
        },
        
        clearFieldError: function($field) {
            $field.removeClass('error').next('.field-error').remove();
        },
        
        downloadPDF: function() {
            const $calculator = $(this).closest('.uk-mortgage-calc');
            const calculatorType = $calculator.data('calculator-type');
            const $form = $calculator.find('.calculator-form');
            const formData = UKMortgageCalculator.collectFormData($form);
            
            $.ajax({
                url: ukMortgageAjax.ajax_url,
                type: 'POST',
                data: {
                    action: 'generate_pdf_report',
                    calculator_type: calculatorType,
                    data: formData,
                    nonce: ukMortgageAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        window.open(response.data.pdf_url, '_blank');
                    }
                }
            });
        },
        
        emailReport: function() {
            const email = prompt('Enter your email address:');
            if (!email) return;
            
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alert('Please enter a valid email address');
                return;
            }
            
            // Implementation would send email via AJAX
            alert('Report will be sent to ' + email);
        },
        
        loadInterestRates: function() {
            // Load current interest rates from API
            // This would integrate with bank APIs or financial data providers
            const sampleRates = {
                fixed_2_year: 3.2,
                fixed_5_year: 3.8,
                variable: 4.1,
                tracker: 3.9
            };
            
            // Store rates for use in calculations
            window.ukMortgageRates = sampleRates;
        },
        
        showRateComparison: function() {
            if (!window.ukMortgageRates) return;
            
            const modal = `
                <div class="rate-comparison-modal">
                    <div class="modal-content">
                        <h4>Current Market Rates</h4>
                        <table class="rates-table">
                            <tr><td>2 Year Fixed</td><td>${window.ukMortgageRates.fixed_2_year}%</td></tr>
                            <tr><td>5 Year Fixed</td><td>${window.ukMortgageRates.fixed_5_year}%</td></tr>
                            <tr><td>Variable Rate</td><td>${window.ukMortgageRates.variable}%</td></tr>
                            <tr><td>Tracker Rate</td><td>${window.ukMortgageRates.tracker}%</td></tr>
                        </table>
                        <button class="btn btn-secondary close-modal">Close</button>
                    </div>
                </div>
            `;
            
            $('body').append(modal);
            $('.close-modal').on('click', () => $('.rate-comparison-modal').remove());
        },
        
        trackCalculation: function(type, inputs, results) {
            // Analytics tracking
            if (typeof gtag !== 'undefined') {
                gtag('event', 'mortgage_calculation', {
                    'calculator_type': type,
                    'loan_amount': inputs.loan_amount || inputs.current_balance || 0,
                    'result_value': results.max_borrowing || results.monthly_payment || results.estimated_value || 0
                });
            }
        },
        
        initTooltips: function() {
            // Initialize tooltips for help text
            $('[data-toggle="tooltip"]').tooltip();
        },
        
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func.apply(this, args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        UKMortgageCalculator.init();
    });
    
    // Expose to global scope
    window.UKMortgageCalculator = UKMortgageCalculator;
    
})(jQuery);