/**
 * Enhanced UK Mortgage Calculator JavaScript
 * Handles real-time calculations, UI interactions, and email functionality
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
            this.initFormValidation();
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
            
            // Postcode validation and formatting
            $(document).on('blur', 'input[name="postcode"]', function() {
                self.validateAndFormatPostcode.call(this);
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
            
            // Email consent handling
            $(document).on('change', 'input[name="email_consent"]', function() {
                self.handleConsentChange.call(this);
            });
            
            // Auto-save form data (optional)
            $(document).on('change', '.calculator-form input, .calculator-form select', 
                          this.debounce(function() {
                self.autoSaveFormData.call(this);
            }, 2000));
            
            // Progress indicator for calculations
            $(document).on('ajaxStart', '.calculator-form', function() {
                self.showCalculationProgress();
            });
            
            $(document).on('ajaxComplete', '.calculator-form', function() {
                self.hideCalculationProgress();
            });
        },
        
        initFormValidation: function() {
            // Add real-time validation
            $('.calculator-form').each(function() {
                const $form = $(this);
                self.setupFormValidation($form);
            });
        },
        
        setupFormValidation: function($form) {
            const self = this;
            
            $form.find('input[required]').on('blur', function() {
                self.validateField($(this));
            });
            
            $form.find('input[type="email"]').on('blur', function() {
                self.validateEmailField($(this));
            });
            
            $form.find('input[type="number"]').on('blur', function() {
                self.validateNumberField($(this));
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
            
            // Validate form before submission
            if (!UKMortgageCalculator.validateForm($form, true)) {
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
            
            // Validate field on change
            UKMortgageCalculator.validateField($input);
            
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
            
            // Store form data for later use
            this.lastCalculationData = {
                type: calculatorType,
                input: formData
            };
            
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
                timeout: 15000, // 15 second timeout
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
                            
                            // Store results for email functionality
                            UKMortgageCalculator.lastCalculationData.results = response.data;
                            
                            // Show email form if user email is collected
                            UKMortgageCalculator.toggleEmailReportButton($form, response.data);
                            
                            // Track analytics
                            UKMortgageCalculator.trackCalculation(calculatorType, formData, response.data);
                            
                            // Auto-save calculation if enabled
                            UKMortgageCalculator.saveCalculationLocally(calculatorType, formData, response.data);
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
                    } else if (xhr.status === 429) {
                        errorMessage = 'Too many requests. Please wait a moment and try again.';
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
        
        emailReport: function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            const $calculator = $btn.closest('.uk-mortgage-calc');
            const $form = $calculator.find('.calculator-form');
            
            // Check if we have calculation data
            if (!this.lastCalculationData || !this.lastCalculationData.results) {
                this.showError('Please calculate first before sending email.');
                return;
            }
            
            // Get user email and consent
            const userEmail = $form.find('input[name="user_email"]').val();
            const userName = $form.find('input[name="user_name"]').val();
            const emailConsent = $form.find('input[name="email_consent"]:checked').length > 0;
            
            // Validate email requirements
            if (!userEmail) {
                this.showError(ukMortgageAjax.messages.email_required || 'Please enter your email address.');
                $form.find('input[name="user_email"]').focus();
                return;
            }
            
            if (!this.isValidEmail(userEmail)) {
                this.showError('Please enter a valid email address.');
                $form.find('input[name="user_email"]').focus();
                return;
            }
            
            // Check consent if required
            const requireConsent = $form.find('input[name="email_consent"]').length > 0;
            if (requireConsent && !emailConsent) {
                this.showError(ukMortgageAjax.messages.consent_required || 'Please agree to receive emails.');
                return;
            }
            
            // Show loading state
            const originalText = $btn.text();
            $btn.prop('disabled', true).html('<span class="spinner"></span> Sending...');
            
            // Send email
            $.ajax({
                url: ukMortgageAjax.ajax_url,
                type: 'POST',
                data: {
                    action: 'send_email_report',
                    user_email: userEmail,
                    user_name: userName,
                    calculator_type: this.lastCalculationData.type,
                    input_data: this.lastCalculationData.input,
                    result_data: this.lastCalculationData.results,
                    email_consent: emailConsent ? '1' : '0',
                    nonce: ukMortgageAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        UKMortgageCalculator.showSuccess(response.data || ukMortgageAjax.messages.email_sent || 'Results sent to your email!');
                        // Optionally hide the email button after successful send
                        $btn.fadeOut();
                    } else {
                        UKMortgageCalculator.showError(response.data || 'Failed to send email. Please try again.');
                    }
                },
                error: function() {
                    UKMortgageCalculator.showError('Network error. Please try again.');
                },
                complete: function() {
                    $btn.prop('disabled', false).html(originalText);
                }
            });
        },
        
        toggleEmailReportButton: function($form, results) {
            const $emailBtn = $form.find('.email-report-btn');
            const hasEmail = $form.find('input[name="user_email"]').length > 0;
            
            if (hasEmail && results) {
                $emailBtn.show();
            }
        },
        
        validateAndFormatPostcode: function() {
            const $input = $(this);
            const postcode = $input.val().toUpperCase().replace(/\s/g, '');
            const regex = /^[A-Z]{1,2}[0-9R][0-9A-Z]?[0-9][ABD-HJLNP-UW-Z]{2}$/;
            
            if (postcode && !regex.test(postcode)) {
                $input.addClass('error');
                UKMortgageCalculator.showFieldError($input, 'Please enter a valid UK postcode');
                return false;
            } else if (postcode) {
                $input.removeClass('error');
                UKMortgageCalculator.clearFieldError($input);
                
                // Format postcode
                if (postcode.length >= 5) {
                    const formatted = postcode.slice(0, -3) + ' ' + postcode.slice(-3);
                    $input.val(formatted);
                }
                return true;
            }
            return true;
        },
        
        validateField: function($field) {
            const fieldType = $field.attr('type');
            const fieldName = $field.attr('name');
            const value = $field.val();
            
            // Check required fields
            if ($field.prop('required') && (!value || value.trim() === '')) {
                this.showFieldError($field, 'This field is required');
                return false;
            }
            
            // Validate specific field types
            switch (fieldType) {
                case 'email':
                    return this.validateEmailField($field);
                case 'number':
                    return this.validateNumberField($field);
                default:
                    if (fieldName === 'postcode') {
                        return this.validateAndFormatPostcode.call($field[0]);
                    }
                    break;
            }
            
            this.clearFieldError($field);
            return true;
        },
        
        validateEmailField: function($field) {
            const email = $field.val();
            
            if (email && !this.isValidEmail(email)) {
                this.showFieldError($field, 'Please enter a valid email address');
                return false;
            }
            
            this.clearFieldError($field);
            return true;
        },
        
        validateNumberField: function($field) {
            const value = parseFloat($field.val());
            const min = parseFloat($field.attr('min'));
            const max = parseFloat($field.attr('max'));
            
            if ($field.val() && isNaN(value)) {
                this.showFieldError($field, 'Please enter a valid number');
                return false;
            }
            
            if (!isNaN(min) && value < min) {
                this.showFieldError($field, `Value must be at least ${min}`);
                return false;
            }
            
            if (!isNaN(max) && value > max) {
                this.showFieldError($field, `Value must not exceed ${max}`);
                return false;
            }
            
            this.clearFieldError($field);
            return true;
        },
        
        handleConsentChange: function() {
            const $checkbox = $(this);
            const $emailBtn = $checkbox.closest('.calculator-form').find('.email-report-btn');
            
            if ($checkbox.is(':checked')) {
                $emailBtn.removeClass('disabled').prop('disabled', false);
            } else {
                $emailBtn.addClass('disabled').prop('disabled', true);
            }
        },
        
        autoSaveFormData: function() {
            const $form = $(this).closest('.calculator-form');
            const calculatorType = $form.closest('.uk-mortgage-calc').data('calculator-type');
            const formData = UKMortgageCalculator.collectFormData($form);
            
            // Save to localStorage (optional feature)
            try {
                const saveKey = `uk_mortgage_${calculatorType}_draft`;
                localStorage.setItem(saveKey, JSON.stringify({
                    data: formData,
                    timestamp: Date.now()
                }));
            } catch (e) {
                // Handle localStorage errors silently
            }
        },
        
        loadSavedFormData: function($form) {
            const calculatorType = $form.closest('.uk-mortgage-calc').data('calculator-type');
            const saveKey = `uk_mortgage_${calculatorType}_draft`;
            
            try {
                const saved = localStorage.getItem(saveKey);
                if (saved) {
                    const data = JSON.parse(saved);
                    // Only load if saved within last 24 hours
                    if (Date.now() - data.timestamp < 24 * 60 * 60 * 1000) {
                        this.populateForm($form, data.data);
                    }
                }
            } catch (e) {
                // Handle errors silently
            }
        },
        
        populateForm: function($form, data) {
            Object.keys(data).forEach(key => {
                const $field = $form.find(`[name="${key}"]`);
                if ($field.length) {
                    if ($field.attr('type') === 'checkbox') {
                        $field.prop('checked', data[key]);
                    } else {
                        $field.val(data[key]);
                    }
                }
            });
        },
        
        saveCalculationLocally: function(type, input, results) {
            try {
                const history = JSON.parse(localStorage.getItem('uk_mortgage_history') || '[]');
                history.unshift({
                    type: type,
                    input: input,
                    results: results,
                    timestamp: Date.now()
                });
                
                // Keep only last 10 calculations
                localStorage.setItem('uk_mortgage_history', JSON.stringify(history.slice(0, 10)));
            } catch (e) {
                // Handle localStorage errors silently
            }
        },
        
        showCalculationProgress: function() {
            // Add progress indicator
            $('.calculator-form').addClass('calculating');
        },
        
        hideCalculationProgress: function() {
            $('.calculator-form').removeClass('calculating');
        },
        
        showSuccess: function(message) {
            this.showNotification(message, 'success');
        },
        
        showNotification: function(message, type = 'info') {
            // Remove existing notifications
            $('.calculator-notification').remove();
            
            const $notification = $(`
                <div class="calculator-notification notification-${type}">
                    <span class="notification-icon"></span>
                    <span class="notification-message">${this.escapeHtml(message)}</span>
                    <button class="notification-close">&times;</button>
                </div>
            `);
            
            $('.uk-mortgage-calc').first().prepend($notification);
            
            // Auto-hide after 5 seconds
            setTimeout(() => $notification.fadeOut(), 5000);
            
            // Close button functionality
            $notification.find('.notification-close').on('click', function() {
                $notification.fadeOut();
            });
        },
        
        isValidEmail: function(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        },
        
        // Enhanced methods from original
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
                
                if (!UKMortgageCalculator.validateField($field) && showErrors) {
                    isValid = false;
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
        
        // Enhanced result rendering methods
        renderAffordabilityResults: function(results) {
            const currency = ukMortgageAjax.currency_symbol || '£';
            
            return `
                <div class="results-grid">
                    <div class="result-card primary">
                        <h4>Maximum Borrowing</h4>
                        <div class="amount">${currency}${this.formatCurrency(results.max_borrowing)}</div>
                        <small>Up to ${results.income_multiplier}x your income</small>
                    </div>
                    <div class="result-card">
                        <h4>Maximum Property Value</h4>
                        <div class="amount">${currency}${this.formatCurrency(results.max_property_value)}</div>
                        <small>Including your deposit</small>
                    </div>
                    <div class="result-card">
                        <h4>Estimated Monthly Payment</h4>
                        <div class="amount">${currency}${this.formatCurrency(results.estimated_monthly_payment || 0)}</div>
                        <small>At ${results.current_market_rate}% interest</small>
                    </div>
                    <div class="result-card">
                        <h4>Loan to Value</h4>
                        <div class="percentage">${results.loan_to_value}%</div>
                        <small>Lower is better</small>
                    </div>
                </div>
                <div class="result-details">
                    <h5>Calculation Details</h5>
                    <ul>
                        <li>Based on UK lending criteria (${results.income_multiplier}x annual income)</li>
                        <li>Stress tested at ${results.stress_test_rate}% interest rate</li>
                        <li>Assumes ${results.affordability_percentage}% of available income for mortgage payments</li>
                        <li>Debt-to-income ratio: ${results.debt_to_income_ratio}%</li>
                        <li>Available monthly income: ${currency}${this.formatCurrency(results.available_monthly_income || 0)}</li>
                    </ul>
                </div>
                ${results.recommendations && results.recommendations.length > 0 ? `
                <div class="recommendations">
                    <h5>Recommendations</h5>
                    <ul>
                        ${results.recommendations.map(rec => `<li>${rec}</li>`).join('')}
                    </ul>
                </div>
                ` : ''}
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
            
            const paymentBreakdown = results.payment_breakdown ? `
                <div class="payment-breakdown">
                    <h5>Payment Breakdown (First 5 Years)</h5>
                    <table class="breakdown-table">
                        <thead>
                            <tr><th>Year</th><th>Interest</th><th>Principal</th><th>Remaining Balance</th></tr>
                        </thead>
                        <tbody>
                            ${results.payment_breakdown.map(year => `
                                <tr>
                                    <td>${year.year}</td>
                                    <td>${currency}${this.formatCurrency(year.interest)}</td>
                                    <td>${currency}${this.formatCurrency(year.principal)}</td>
                                    <td>${currency}${this.formatCurrency(year.remaining_balance)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
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
                        <small>Including overpayment</small>
                    </div>
                    ` : `
                    <div class="result-card">
                        <h4>Annual Payment</h4>
                        <div class="amount">${currency}${this.formatCurrency(results.annual_payment || results.monthly_payment * 12)}</div>
                        <small>Total per year</small>
                    </div>
                    `}
                    <div class="result-card">
                        <h4>Total Interest</h4>
                        <div class="amount">${currency}${this.formatCurrency(results.total_interest)}</div>
                        <small>Over the full term</small>
                    </div>
                    ${results.overpayment_savings > 0 ? `
                    <div class="result-card positive">
                        <h4>Interest Saved</h4>
                        <div class="amount">${currency}${this.formatCurrency(results.overpayment_savings)}</div>
                        <small>With overpayments</small>
                    </div>
                    ` : `
                    <div class="result-card">
                        <h4>Total Amount Paid</h4>
                        <div class="amount">${currency}${this.formatCurrency(results.total_paid)}</div>
                        <small>Loan + Interest</small>
                    </div>
                    `}
                </div>
                ${overpaymentSection}
                ${paymentBreakdown}
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
                        <small>${results.monthly_saving >= 0 ? 'Potential saving' : 'Additional cost'}</small>
                    </div>
                    <div class="result-card">
                        <h4>Annual Impact</h4>
                        <div class="amount">${currency}${this.formatCurrency(Math.abs(results.annual_saving))}</div>
                        <small>Per year</small>
                    </div>
                    <div class="result-card">
                        <h4>Break Even</h4>
                        <div class="period">${results.break_even_months} months</div>
                        <small>Time to recover fees</small>
                    </div>
                    <div class="result-card">
                        <h4>Total Fees</h4>
                        <div class="amount">${currency}${this.formatCurrency(results.total_fees)}</div>
                        <small>All switching costs</small>
                    </div>
                </div>
                
                <div class="recommendation ${worthwhileClass}">
                    <h5>Recommendation</h5>
                    <p><strong>${worthwhileText}</strong></p>
                    ${results.recommendations ? results.recommendations.map(rec => `<p>${rec}</p>`).join('') : ''}
                </div>
                
                <div class="comparison-table">
                    <h5>Payment Comparison</h5>
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
                            <tr>
                                <td>Annual Payment</td>
                                <td>${currency}${this.formatCurrency(results.current_monthly_payment * 12)}</td>
                                <td>${currency}${this.formatCurrency(results.new_monthly_payment * 12)}</td>
                                <td class="${results.annual_saving >= 0 ? 'positive' : 'negative'}">
                                    ${results.annual_saving >= 0 ? '+' : ''}${currency}${this.formatCurrency(results.annual_saving)}
                                </td>
                            </tr>
                            ${results.lifetime_saving ? `
                            <tr>
                                <td>Lifetime Saving</td>
                                <td colspan="2">After deducting all fees</td>
                                <td class="${results.lifetime_saving >= 0 ? 'positive' : 'negative'}">
                                    ${currency}${this.formatCurrency(results.lifetime_saving)}
                                </td>
                            </tr>
                            ` : ''}
                        </tbody>
                    </table>
                </div>
                
                ${results.fee_breakdown ? `
                <div class="fee-breakdown">
                    <h5>Fee Breakdown</h5>
                    <ul>
                        ${Object.entries(results.fee_breakdown).map(([fee, amount]) => 
                            amount > 0 ? `<li>${fee.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}: ${currency}${this.formatCurrency(amount)}</li>` : ''
                        ).join('')}
                    </ul>
                </div>
                ` : ''}
            `;
        },
        
        renderValuationResults: function(results) {
            const currency = ukMortgageAjax.currency_symbol || '£';
            
            return `
                <div class="results-grid">
                    <div class="result-card primary">
                        <h4>Estimated Value</h4>
                        <div class="amount">${currency}${this.formatCurrency(results.estimated_value)}</div>
                        <small>${results.confidence_level}% confidence</small>
                    </div>
                    <div class="result-card">
                        <h4>Value Range</h4>
                        <div class="range">
                            ${currency}${this.formatCurrency(results.value_range_low)} - 
                            ${currency}${this.formatCurrency(results.value_range_high)}
                        </div>
                        <small>Estimated range</small>
                    </div>
                    <div class="result-card">
                        <h4>Confidence Level</h4>
                        <div class="percentage">${results.confidence_level}%</div>
                        <small>Accuracy estimate</small>
                    </div>
                    <div class="result-card">
                        <h4>Comparable Sales</h4>
                        <div class="count">${results.comparable_sales}</div>
                        <small>Recent sales used</small>
                    </div>
                </div>
                
                ${results.market_trends && Object.keys(results.market_trends).length > 0 ? `
                <div class="market-insights">
                    <h5>Local Market Insights</h5>
                    <div class="trends-grid">
                        ${results.market_trends.price_change_12m ? `
                        <div class="trend-item">
                            <strong>Price Change (12m):</strong> 
                            <span class="${results.market_trends.price_change_12m >= 0 ? 'positive' : 'negative'}">
                                ${results.market_trends.price_change_12m > 0 ? '+' : ''}${results.market_trends.price_change_12m}%
                            </span>
                        </div>
                        ` : ''}
                        ${results.market_trends.average_time_to_sell ? `
                        <div class="trend-item">
                            <strong>Average Time to Sell:</strong> ${results.market_trends.average_time_to_sell} days
                        </div>
                        ` : ''}
                    </div>
                    <p>Regional multiplier applied: ${results.regional_multiplier}x</p>
                </div>
                ` : ''}
                
                <div class="valuation-disclaimer">
                    <h5>Important Notice</h5>
                    <p>This is an automated estimate based on available data. For mortgage purposes, 
                       you will need a formal valuation from a RICS qualified surveyor.</p>
                    ${results.recommendations ? results.recommendations.map(rec => `<p>${rec}</p>`).join('') : ''}
                </div>
                
                ${results.valuation_factors ? `
                <div class="valuation-factors">
                    <h5>Location Factors</h5>
                    <div class="factors-grid">
                        ${Object.entries(results.valuation_factors).map(([factor, score]) => `
                            <div class="factor-item">
                                <span class="factor-name">${factor.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}</span>
                                <span class="factor-score">${score}/10</span>
                            </div>
                        `).join('')}
                    </div>
                </div>
                ` : ''}
            `;
        },
        
        // Rest of the methods remain the same...
        formatCurrency: function(amount) {
            if (typeof amount !== 'number' || isNaN(amount)) {
                return '0';
            }
            
            return new Intl.NumberFormat('en-GB', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(amount);
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
        
        showError: function(message) {
            this.showNotification(message, 'error');
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
            $('.calculator-error, .calculator-notification').remove();
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