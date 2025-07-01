/**
 * UK Mortgage Calculator JavaScript (Fixed)
 * Handles real-time calculations and UI interactions
 */

(function($) {
    'use strict';

    const UKMortgageCalculator = {
        
        init: function() {
            // Check if required objects exist
            if (typeof ukMortgageAjax === 'undefined') {
                console.error('UK Mortgage Calculator: AJAX object not found');
                return;
            }
            
            this.bindEvents();
            this.initTooltips();
            this.loadInterestRates();
        },
        
        bindEvents: function() {
            const self = this;
            
            // Form submission
            $(document).on('submit', '.calculator-form', function(e) {
                e.preventDefault();
                self.handleFormSubmit.call(this, e);
            });
            
            // Real-time calculation on input change (with debounce)
            $(document).on('input change', '.calculator-form input, .calculator-form select', 
                          this.debounce(function(e) {
                self.handleInputChange.call(this, e);
            }, 500));
            
            // PDF download
            $(document).on('click', '.pdf-download-btn', function(e) {
                e.preventDefault();
                self.downloadPDF.call(this, e);
            });
            
            // Email report
            $(document).on('click', '.email-report-btn', function(e) {
                e.preventDefault();
                self.emailReport.call(this, e);
            });
            
            // Postcode validation
            $(document).on('blur', 'input[name="postcode"]', function() {
                self.validatePostcode.call(this);
            });
            
            // Number formatting
            $(document).on('blur', 'input[type="number"]', function() {
                self.formatNumber.call(this);
            });
            
            // Rate comparison
            $(document).on('click', '.compare-rates-btn', function(e) {
                e.preventDefault();
                self.showRateComparison.call(this, e);
            });
            
            // Modal close
            $(document).on('click', '.close-modal, .rate-comparison-modal', function(e) {
                if (e.target === this) {
                    $('.rate-comparison-modal').remove();
                }
            });
            
            // Form reset
            $(document).on('click', '.reset-form-btn', function(e) {
                e.preventDefault();
                const $form = $(this).closest('.calculator-form');
                self.resetForm($form);
            });
        },
        
        handleFormSubmit: function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $calculator = $form.closest('.uk-mortgage-calc');
            const calculatorType = $calculator.data('calculator-type');
            
            if (!calculatorType) {
                UKMortgageCalculator.showError('Calculator type not found');
                return;
            }
            
            UKMortgageCalculator.calculate(calculatorType, $form);
        },
        
        handleInputChange: function(e) {
            const $input = $(e.target);
            const $form = $input.closest('.calculator-form');
            const $calculator = $form.closest('.uk-mortgage-calc');
            const calculatorType = $calculator.data('calculator-type');
            const autoCalculate = $calculator.data('auto-calculate');
            
            // Clear previous field errors
            UKMortgageCalculator.clearFieldError($input);
            
            // Only auto-calculate if enabled and all required fields are filled
            if (autoCalculate !== 'no' && UKMortgageCalculator.validateForm($form, false)) {
                UKMortgageCalculator.calculate(calculatorType, $form);
            }
        },
        
        calculate: function(calculatorType, $form) {
            const $calculator = $form.closest('.uk-mortgage-calc');
            const $loading = $calculator.find('.calculator-loading');
            const $result = $calculator.find('.calculator-result');
            const $buttons = $calculator.find('.pdf-download-btn, .email-report-btn');
            
            // Clear previous errors
            $calculator.find('.calculator-error').remove();
            
            // Show loading
            $loading.show();
            $result.hide();
            
            // Collect form data
            const formData = this.collectFormData($form);
            
            // Validate required fields
            if (!this.validateRequiredFields(formData, calculatorType)) {
                $loading.hide();
                this.showError(ukMortgageAjax.messages?.invalid_input || 'Please fill in all required fields.');
                return;
            }
            
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
                timeout: 10000, // 10 second timeout
                beforeSend: function() {
                    // Disable calculate button
                    $form.find('.calculate-btn').prop('disabled', true).text(
                        ukMortgageAjax.messages?.calculating || 'Calculating...'
                    );
                },
                success: function(response) {
                    try {
                        if (response.success && response.data) {
                            UKMortgageCalculator.displayResults(response.data, calculatorType, $calculator);
                            $buttons.show();
                            
                            // Track analytics
                            UKMortgageCalculator.trackCalculation(calculatorType, formData, response.data);
                        } else {
                            const errorMessage = response.data || ukMortgageAjax.messages?.error || 'Calculation failed. Please check your inputs.';
                            UKMortgageCalculator.showError(errorMessage);
                        }
                    } catch (error) {
                        console.error('Error processing response:', error);
                        UKMortgageCalculator.showError('Error processing calculation results.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error, xhr.responseText);
                    
                    let errorMessage = ukMortgageAjax.messages?.error || 'Connection error. Please try again.';
                    
                    if (status === 'timeout') {
                        errorMessage = 'Request timed out. Please try again.';
                    } else if (xhr.status === 403) {
                        errorMessage = 'Security check failed. Please refresh the page and try again.';
                    } else if (xhr.status >= 500) {
                        errorMessage = 'Server error. Please try again later.';
                    }
                    
                    UKMortgageCalculator.showError(errorMessage);
                },
                complete: function() {
                    $loading.hide();
                    // Re-enable calculate button
                    $form.find('.calculate-btn').prop('disabled', false).text('Calculate');
                }
            });
        },
        
        collectFormData: function($form) {
            const data = {};
            
            $form.find('input, select, textarea').each(function() {
                const $field = $(this);
                const name = $field.attr('name');
                let value = $field.val();
                
                // Skip the nonce fields and empty names
                if (!name || name === 'uk_mortgage_form_nonce' || name === '_wp_http_referer') {
                    return;
                }
                
                // Handle checkboxes
                if ($field.attr('type') === 'checkbox') {
                    if (!data[name]) data[name] = [];
                    if ($field.is(':checked')) {
                        data[name].push(value);
                    }
                } else if ($field.attr('type') === 'radio') {
                    if ($field.is(':checked')) {
                        data[name] = value;
                    }
                } else if (name && value !== '') {
                    // Convert numeric values, handle comma-separated numbers
                    if ($field.attr('type') === 'number') {
                        // Remove commas and convert to number
                        value = value.toString().replace(/,/g, '');
                        value = parseFloat(value) || 0;
                    }
                    data[name] = value;
                }
            });
            
            return data;
        },
        
        validateRequiredFields: function(data, calculatorType) {
            const requiredFields = {
                'affordability': ['annual_income', 'monthly_outgoings', 'deposit'],
                'repayment': ['loan_amount', 'interest_rate', 'term_years'],
                'remortgage': ['current_balance', 'current_rate', 'new_rate', 'remaining_term'],
                'valuation': ['postcode', 'property_type', 'bedrooms']
            };
            
            const required = requiredFields[calculatorType] || [];
            
            for (let field of required) {
                if (!data.hasOwnProperty(field) || data[field] === '' || data[field] === 0) {
                    return false;
                }
            }
            
            return true;
        },
        
        validateForm: function($form, showErrors = true) {
            let isValid = true;
            
            $form.find('[required]').each(function() {
                const $field = $(this);
                const value = $field.val();
                
                if (!value || value.trim() === '') {
                    isValid = false;
                    if (showErrors) {
                        UKMortgageCalculator.showFieldError($field, 'This field is required');
                    }
                    return false;
                }
            });
            
            return isValid;
        },
        
        displayResults: function(results, calculatorType, $calculator) {
            const $result = $calculator.find('.calculator-result');
            const $content = $result.find('.result-content');
            
            let html = '';
            
            try {
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
                    default:
                        html = '<div class="alert alert-warning">Unknown calculator type</div>';
                }
                
                $content.html(html);
                $result.show();
                
                // Add animations
                $result.removeClass('fade-in');
                setTimeout(() => $result.addClass('fade-in'), 10);
                
                // Scroll to results
                this.scrollToResults($result);
                
            } catch (error) {
                console.error('Error displaying results:', error);
                this.showError('Error displaying results. Please try again.');
            }
        },
        
        renderAffordabilityResults: function(results) {
            const currency = ukMortgageAjax.currency_symbol || '£';
            
            return `
                <div class="results-grid">
                    <div class="result-card primary">
                        <h4>Maximum Borrowing</h4>
                        <div class="amount">${currency}${this.formatCurrency(results.max_borrowing)}</div>
                    </div>
                    <div class="result-card">
                        <h4>Maximum Property Value</h4>
                        <div class="amount">${currency}${this.formatCurrency(results.max_property_value)}</div>
                    </div>
                    <div class="result-card">
                        <h4>Monthly Budget</h4>
                        <div class="amount">${currency}${this.formatCurrency(results.monthly_budget)}</div>
                    </div>
                    <div class="result-card">
                        <h4>Loan to Value</h4>
                        <div class="percentage">${results.loan_to_value}%</div>
                    </div>
                </div>
                <div class="result-details">
                    <h5>Calculation Details</h5>
                    <ul>
                        <li>Based on UK lending criteria (typically 4.5x annual income)</li>
                        <li>Stress tested at ${results.stress_test_rate || 7}% interest rate</li>
                        <li>Assumes 35% of available income for mortgage payments</li>
                        <li>Debt-to-income ratio: ${results.debt_to_income_ratio}%</li>
                        <li>Available monthly income: ${currency}${this.formatCurrency(results.available_monthly_income || 0)}</li>
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
            const currency = ukMortgageAjax.currency_symbol || '£';
            
            const overpaymentSection = results.overpayment_savings > 0 ? `
                <div class="overpayment-benefits">
                    <h5>Overpayment Benefits</h5>
                    <div class="benefits-grid">
                        <div class="benefit">
                            <strong>Interest Saved:</strong> ${currency}${this.formatCurrency(results.overpayment_savings)}
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
                        <div class="amount">${currency}${this.formatCurrency(results.monthly_payment)}</div>
                        <small>${results.repayment_type || 'Repayment'} mortgage</small>
                    </div>
                    ${results.total_monthly_with_overpayment !== results.monthly_payment ? `
                    <div class="result-card">
                        <h4>With Overpayments</h4>
                        <div class="amount">${currency}${this.formatCurrency(results.total_monthly_with_overpayment)}</div>
                    </div>
                    ` : `
                    <div class="result-card">
                        <h4>Total Interest</h4>
                        <div class="amount">${currency}${this.formatCurrency(results.total_interest)}</div>
                    </div>
                    `}
                    <div class="result-card">
                        <h4>Total Amount Paid</h4>
                        <div class="amount">${currency}${this.formatCurrency(results.total_paid)}</div>
                    </div>
                    ${results.overpayment_savings > 0 ? `
                    <div class="result-card positive">
                        <h4>Interest Saved</h4>
                        <div class="amount">${currency}${this.formatCurrency(results.overpayment_savings)}</div>
                    </div>
                    ` : ''}
                </div>
                ${overpaymentSection}
            `;
        },
        
        renderRemortgageResults: function(results) {
            const currency = ukMortgageAjax.currency_symbol || '£';
            const worthwhileClass = results.worthwhile ? 'positive' : 'negative';
            const worthwhileText = results.worthwhile ? 
                'Remortgaging appears worthwhile' : 
                'Remortgaging may not be beneficial';
            
            return `
                <div class="results-grid">
                    <div class="result-card ${worthwhileClass}">
                        <h4>Monthly ${results.monthly_saving >= 0 ? 'Saving' : 'Extra Cost'}</h4>
                        <div class="amount">${currency}${this.formatCurrency(Math.abs(results.monthly_saving))}</div>
                    </div>
                    <div class="result-card">
                        <h4>Annual Impact</h4>
                        <div class="amount">${currency}${this.formatCurrency(Math.abs(results.annual_saving))}</div>
                    </div>
                    <div class="result-card">
                        <h4>Break Even</h4>
                        <div class="period">${results.break_even_months} months</div>
                    </div>
                    <div class="result-card">
                        <h4>Total Fees</h4>
                        <div class="amount">${currency}${this.formatCurrency(results.total_fees)}</div>
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
                                <th>Difference</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Monthly Payment</td>
                                <td>${currency}${this.formatCurrency(results.current_monthly_payment)}</td>
                                <td>${currency}${this.formatCurrency(results.new_monthly_payment)}</td>
                                <td class="${results.monthly_saving >= 0 ? 'positive' : 'negative'}">
                                    ${results.monthly_saving >= 0 ? '+' : ''}${currency}${this.formatCurrency(results.monthly_saving)}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            `;
        },
        
        renderValuationResults: function(results) {
            const currency = ukMortgageAjax.currency_symbol || '£';
            
            return `
                <div class="results-grid">
                    <div class="result-card primary">
                        <h4>Estimated Value</h4>
                        <div class="amount">${currency}${this.formatCurrency(results.estimated_value)}</div>
                    </div>
                    <div class="result-card">
                        <h4>Value Range</h4>
                        <div class="range">
                            ${currency}${this.formatCurrency(results.value_range_low)} - 
                            ${currency}${this.formatCurrency(results.value_range_high)}
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
                       Regional multiplier applied: ${results.regional_multiplier || 1.0}x.</p>
                </div>
            `;
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
            const value = parseFloat($input.val().replace(/,/g, ''));
            
            if (!isNaN(value) && $input.attr('name') !== 'interest_rate') {
                // Don't format if it's a decimal field like interest rate
                if ($input.attr('step') && $input.attr('step').includes('.')) {
                    return;
                }
                $input.val(value.toLocaleString('en-GB'));
            }
        },
        
        formatCurrency: function(amount) {
            if (typeof amount !== 'number' || isNaN(amount)) {
                return '0';
            }
            
            return new Intl.NumberFormat('en-GB', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(amount);
        },
        
        showError: function(message) {
            // Remove existing errors
            $('.calculator-error').remove();
            
            const $error = $('<div class="calculator-error alert alert-danger">' + this.escapeHtml(message) + '</div>');
            $('.uk-mortgage-calc').first().prepend($error);
            
            // Auto-hide after 10 seconds
            setTimeout(() => $error.fadeOut(), 10000);
            
            // Scroll to error
            $('html, body').animate({
                scrollTop: $error.offset().top - 100
            }, 500);
        },
        
        showFieldError: function($field, message) {
            this.clearFieldError($field);
            $field.addClass('error');
            const $error = $('<div class="field-error">' + this.escapeHtml(message) + '</div>');
            $field.after($error);
        },
        
        clearFieldError: function($field) {
            $field.removeClass('error').next('.field-error').remove();
        },
        
        resetForm: function($form) {
            $form[0].reset();
            $form.find('.error').removeClass('error');
            $form.find('.field-error').remove();
            $form.closest('.uk-mortgage-calc').find('.calculator-result').hide();
            $('.calculator-error').remove();
        },
        
        scrollToResults: function($result) {
            if ($result.length && $result.is(':visible')) {
                $('html, body').animate({
                    scrollTop: $result.offset().top - 100
                }, 500);
            }
        },
        
        downloadPDF: function(e) {
            e.preventDefault();
            
            const $calculator = $(this).closest('.uk-mortgage-calc');
            const calculatorType = $calculator.data('calculator-type');
            const $form = $calculator.find('.calculator-form');
            const formData = UKMortgageCalculator.collectFormData($form);
            
            // Show loading state
            const $btn = $(this);
            const originalText = $btn.text();
            $btn.text('Generating...').prop('disabled', true);
            
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
                    if (response.success && response.data.pdf_url) {
                        window.open(response.data.pdf_url, '_blank');
                    } else {
                        UKMortgageCalculator.showError('PDF generation failed. Please try again.');
                    }
                },
                error: function() {
                    UKMortgageCalculator.showError('PDF generation failed. Please try again.');
                },
                complete: function() {
                    $btn.text(originalText).prop('disabled', false);
                }
            });
        },
        
        emailReport: function(e) {
            e.preventDefault();
            
            const email = prompt('Enter your email address:');
            if (!email) return;
            
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alert('Please enter a valid email address');
                return;
            }
            
            // Implementation would send email via AJAX
            alert('Report will be sent to ' + email + ' (Feature coming soon)');
        },
        
        loadInterestRates: function() {
            // Load current interest rates from cached data or API
            const sampleRates = {
                fixed_2_year: 3.2,
                fixed_5_year: 3.8,
                variable: 4.1,
                tracker: 3.9
            };
            
            // Store rates for use in calculations
            window.ukMortgageRates = sampleRates;
        },
        
        showRateComparison: function(e) {
            e.preventDefault();
            
            if (!window.ukMortgageRates) {
                this.loadInterestRates();
            }
            
            const modal = `
                <div class="rate-comparison-modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4>Current Market Rates</h4>
                            <button class="close-modal" aria-label="Close">&times;</button>
                        </div>
                        <div class="modal-body">
                            <table class="rates-table">
                                <tr><td>2 Year Fixed</td><td>${window.ukMortgageRates.fixed_2_year}%</td></tr>
                                <tr><td>5 Year Fixed</td><td>${window.ukMortgageRates.fixed_5_year}%</td></tr>
                                <tr><td>Variable Rate</td><td>${window.ukMortgageRates.variable}%</td></tr>
                                <tr><td>Tracker Rate</td><td>${window.ukMortgageRates.tracker}%</td></tr>
                            </table>
                            <p class="rate-disclaimer">
                                <small>Rates are indicative and may vary by lender and individual circumstances.</small>
                            </p>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary close-modal">Close</button>
                        </div>
                    </div>
                </div>
            `;
            
            // Remove existing modals
            $('.rate-comparison-modal').remove();
            
            $('body').append(modal);
        },
        
        trackCalculation: function(type, inputs, results) {
            // Analytics tracking
            try {
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'mortgage_calculation', {
                        'calculator_type': type,
                        'loan_amount': inputs.loan_amount || inputs.current_balance || 0,
                        'result_value': results.max_borrowing || results.monthly_payment || results.estimated_value || 0
                    });
                }
                
                // Custom tracking if available
                if (typeof window.trackMortgageCalculation === 'function') {
                    window.trackMortgageCalculation(type, inputs, results);
                }
            } catch (error) {
                console.warn('Analytics tracking error:', error);
            }
        },
        
        initTooltips: function() {
            // Initialize tooltips if jQuery UI or Bootstrap is available
            try {
                if (typeof $.fn.tooltip === 'function') {
                    $('[data-toggle="tooltip"]').tooltip();
                }
            } catch (error) {
                console.warn('Tooltip initialization failed:', error);
            }
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
        },
        
        escapeHtml: function(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        try {
            UKMortgageCalculator.init();
        } catch (error) {
            console.error('UK Mortgage Calculator initialization failed:', error);
        }
    });
    
    // Expose to global scope
    window.UKMortgageCalculator = UKMortgageCalculator;
    
})(jQuery);