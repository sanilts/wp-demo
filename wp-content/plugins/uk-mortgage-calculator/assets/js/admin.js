/**
 * Simple Admin JavaScript
 */

(function($) {
    'use strict';

    const UKMortgageAdmin = {
        
        init: function() {
            this.bindEvents();
        },
        
        bindEvents: function() {
            // Delete entry
            $(document).on('click', '.delete-entry', this.deleteEntry);
            
            // View entry details
            $(document).on('click', '.view-details', this.viewEntryDetails);
            
            // Export data
            $(document).on('click', '.export-data-btn', this.exportData);
            
            // Clear all data
            $(document).on('click', '.clear-data-btn', this.clearAllData);
        },
        
        deleteEntry: function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            const entryId = $btn.data('id');
            
            if (!confirm(ukMortgageAdmin.messages.confirm_delete)) {
                return;
            }
            
            $btn.prop('disabled', true).text('Deleting...');
            
            $.ajax({
                url: ukMortgageAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'uk_mortgage_delete_entry',
                    entry_id: entryId,
                    nonce: ukMortgageAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $btn.closest('tr').fadeOut(300, function() {
                            $(this).remove();
                        });
                        UKMortgageAdmin.showNotice('Entry deleted successfully.', 'success');
                    } else {
                        UKMortgageAdmin.showNotice(response.data || 'Failed to delete entry.', 'error');
                        $btn.prop('disabled', false).text('Delete');
                    }
                },
                error: function() {
                    UKMortgageAdmin.showNotice('Network error occurred.', 'error');
                    $btn.prop('disabled', false).text('Delete');
                }
            });
        },
        
        viewEntryDetails: function(e) {
            e.preventDefault();
            
            const entryId = $(this).data('id');
            
            $.ajax({
                url: ukMortgageAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'uk_mortgage_get_entry_details',
                    entry_id: entryId,
                    nonce: ukMortgageAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        UKMortgageAdmin.showEntryModal(response.data);
                    } else {
                        UKMortgageAdmin.showNotice(response.data || 'Failed to load entry details.', 'error');
                    }
                },
                error: function() {
                    UKMortgageAdmin.showNotice('Network error occurred.', 'error');
                }
            });
        },
        
        showEntryModal: function(entry) {
            const modalHtml = `
                <div class="uk-mortgage-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 999999; display: flex; align-items: center; justify-content: center;">
                    <div class="modal-content" style="background: white; padding: 20px; border-radius: 8px; max-width: 800px; width: 90%; max-height: 80vh; overflow-y: auto;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h3>Entry Details - ${entry.calculator_type.toUpperCase()}</h3>
                            <button class="close-modal" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
                        </div>
                        <div>
                            <p><strong>ID:</strong> ${entry.id}</p>
                            <p><strong>Type:</strong> ${entry.calculator_type}</p>
                            <p><strong>Email:</strong> ${entry.user_email || 'Not provided'}</p>
                            <p><strong>Name:</strong> ${entry.user_name || 'Not provided'}</p>
                            <p><strong>Date:</strong> ${entry.created_at}</p>
                            <h4>Input Data</h4>
                            <pre style="background: #f5f5f5; padding: 10px; border-radius: 4px; overflow: auto;">${JSON.stringify(entry.input_data, null, 2)}</pre>
                            <h4>Result Data</h4>
                            <pre style="background: #f5f5f5; padding: 10px; border-radius: 4px; overflow: auto;">${JSON.stringify(entry.result_data, null, 2)}</pre>
                        </div>
                        <div style="text-align: right; margin-top: 20px;">
                            <button class="button close-modal">Close</button>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modalHtml);
            
            // Close modal functionality
            $('.close-modal, .uk-mortgage-modal').on('click', function(e) {
                if (e.target === this) {
                    $('.uk-mortgage-modal').remove();
                }
            });
        },
        
        exportData: function(e) {
            e.preventDefault();
            
            if (!confirm(ukMortgageAdmin.messages.confirm_export)) {
                return;
            }
            
            const $btn = $(this);
            const originalText = $btn.text();
            
            $btn.prop('disabled', true).text('Exporting...');
            
            $.ajax({
                url: ukMortgageAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'uk_mortgage_export_data',
                    nonce: ukMortgageAdmin.nonce
                },
                success: function(response) {
                    if (response.success && response.data.download_url) {
                        // Create download link
                        const link = document.createElement('a');
                        link.href = response.data.download_url;
                        link.download = '';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        
                        UKMortgageAdmin.showNotice('Data exported successfully!', 'success');
                    } else {
                        UKMortgageAdmin.showNotice(response.data || 'Export failed.', 'error');
                    }
                },
                error: function() {
                    UKMortgageAdmin.showNotice('Network error occurred.', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        },
        
        clearAllData: function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to delete ALL calculation data? This action cannot be undone!')) {
                return;
            }
            
            const $btn = $(this);
            const originalText = $btn.text();
            
            $btn.prop('disabled', true).text('Clearing...');
            
            $.ajax({
                url: ukMortgageAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'uk_mortgage_clear_all_data',
                    nonce: ukMortgageAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload(); // Reload to show empty table
                    } else {
                        UKMortgageAdmin.showNotice(response.data || 'Failed to clear data.', 'error');
                    }
                },
                error: function() {
                    UKMortgageAdmin.showNotice('Network error occurred.', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        },
        
        showNotice: function(message, type = 'info') {
            const noticeHtml = `
                <div class="notice notice-${type} is-dismissible" style="margin: 15px 0;">
                    <p>${message}</p>
                </div>
            `;
            
            $('.wrap h1').after(noticeHtml);
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                $('.notice').fadeOut();
            }, 5000);
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        UKMortgageAdmin.init();
    });
    
})(jQuery);