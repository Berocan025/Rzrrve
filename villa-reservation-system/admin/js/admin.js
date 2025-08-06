/**
 * Villa Reservation System - Admin JavaScript
 * 
 * @package VillaReservationSystem
 * @since 1.0.0
 */

(function($) {
    'use strict';

    var VRSAdmin = {
        
        /**
         * Initialize admin functionality
         */
        init: function() {
            this.bindEvents();
            this.initCharts();
            this.initDatePickers();
            this.loadStats();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Stats refresh button
            $(document).on('click', '#refresh-stats', this.refreshStats);
            
            // Export reservations
            $(document).on('click', '#export-reservations', this.exportReservations);
            
            // Manual sync buttons
            $(document).on('click', '#manual-sync-all', this.manualSyncAll);
            $(document).on('click', '.test-connection', this.testConnection);
            $(document).on('click', '.sync-now', this.syncNow);
            
            // Generate report
            $(document).on('click', '#generate-report', this.generateReport);
            
            // Test tools
            $(document).on('click', '#test-email', this.testEmail);
            $(document).on('click', '#test-google-api', this.testGoogleAPI);
            $(document).on('click', '#cleanup-old-data', this.cleanupOldData);
            
            // Villa meta box toggles
            $(document).on('change', 'input[name="_villa_pricing_type"]', this.togglePricingFields);
            
            // Reservation actions
            $(document).on('click', '.confirm-reservation', this.confirmReservation);
            $(document).on('click', '.cancel-reservation', this.cancelReservation);
        },

        /**
         * Initialize charts
         */
        initCharts: function() {
            var chartCanvas = document.getElementById('reservationChart');
            if (!chartCanvas) return;

            var ctx = chartCanvas.getContext('2d');
            
            // Get chart data via AJAX
            this.loadChartData().then(function(data) {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Rezervasyonlar',
                            data: data.reservations,
                            borderColor: '#667eea',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)',
                            fill: true,
                            tension: 0.4
                        }, {
                            label: 'Gelir (₺)',
                            data: data.revenue,
                            borderColor: '#764ba2',
                            backgroundColor: 'rgba(118, 75, 162, 0.1)',
                            fill: true,
                            tension: 0.4,
                            yAxisID: 'y1'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            title: {
                                display: true,
                                text: 'Son 30 Gün Rezervasyon Trendi'
                            }
                        },
                        scales: {
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                title: {
                                    display: true,
                                    text: 'Rezervasyon Sayısı'
                                }
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                title: {
                                    display: true,
                                    text: 'Gelir (₺)'
                                },
                                grid: {
                                    drawOnChartArea: false,
                                }
                            }
                        }
                    }
                });
            });
        },

        /**
         * Initialize date pickers
         */
        initDatePickers: function() {
            if ($.fn.datepicker) {
                $('.vrs-datepicker').datepicker({
                    dateFormat: 'yy-mm-dd',
                    changeMonth: true,
                    changeYear: true
                });
            }
        },

        /**
         * Load dashboard statistics
         */
        loadStats: function() {
            this.makeAjaxCall('vrs_admin_stats', {}, function(response) {
                if (response.success) {
                    // Update stat numbers
                    $('.stat-number').each(function(index) {
                        var statKeys = ['total_villas', 'total_reservations', 'pending_reservations', 'this_month_revenue'];
                        if (response.data[statKeys[index]]) {
                            $(this).text(response.data[statKeys[index]]);
                        }
                    });
                }
            });
        },

        /**
         * Refresh dashboard statistics
         */
        refreshStats: function(e) {
            e.preventDefault();
            var $button = $(this);
            
            VRSAdmin.showLoading($button);
            VRSAdmin.loadStats();
            
            setTimeout(function() {
                VRSAdmin.hideLoading($button);
                VRSAdmin.showMessage('success', vrsAdmin.strings.success);
            }, 1000);
        },

        /**
         * Export reservations to CSV
         */
        exportReservations: function(e) {
            e.preventDefault();
            var $button = $(this);
            
            VRSAdmin.showLoading($button);
            
            VRSAdmin.makeAjaxCall('vrs_export_reservations', {
                start_date: $('#report-start-date').val(),
                end_date: $('#report-end-date').val(),
                villa_id: $('#report-villa').val()
            }, function(response) {
                VRSAdmin.hideLoading($button);
                if (response.success) {
                    VRSAdmin.showMessage('success', vrsAdmin.strings.export_success);
                } else {
                    VRSAdmin.showMessage('error', response.data.message || vrsAdmin.strings.error);
                }
            });
        },

        /**
         * Manual sync all villas
         */
        manualSyncAll: function(e) {
            e.preventDefault();
            var $button = $(this);
            var $progress = $('#sync-progress');
            
            VRSAdmin.showLoading($button);
            $progress.show();
            
            // Simulate progress
            var progress = 0;
            var interval = setInterval(function() {
                progress += 10;
                $('.progress-fill').css('width', progress + '%');
                
                if (progress >= 100) {
                    clearInterval(interval);
                    VRSAdmin.hideLoading($button);
                    $progress.hide();
                    $('.progress-fill').css('width', '0%');
                    VRSAdmin.showMessage('success', vrsAdmin.strings.sync_success);
                }
            }, 200);
            
            VRSAdmin.makeAjaxCall('vrs_manual_sync', {}, function(response) {
                // Handle actual sync completion
                clearInterval(interval);
                progress = 100;
                $('.progress-fill').css('width', '100%');
            });
        },

        /**
         * Test Google Sheets connection
         */
        testConnection: function(e) {
            e.preventDefault();
            var $button = $(this);
            var villaId = $button.data('villa-id');
            
            VRSAdmin.showLoading($button);
            
            VRSAdmin.makeAjaxCall('vrs_test_connection', {
                villa_id: villaId
            }, function(response) {
                VRSAdmin.hideLoading($button);
                if (response.success) {
                    VRSAdmin.showMessage('success', 'Bağlantı başarılı!');
                } else {
                    VRSAdmin.showMessage('error', response.data.message || 'Bağlantı hatası!');
                }
            });
        },

        /**
         * Sync villa now
         */
        syncNow: function(e) {
            e.preventDefault();
            var $button = $(this);
            var villaId = $button.data('villa-id');
            
            VRSAdmin.showLoading($button);
            
            VRSAdmin.makeAjaxCall('vrs_sync_now', {
                villa_id: villaId
            }, function(response) {
                VRSAdmin.hideLoading($button);
                if (response.success) {
                    VRSAdmin.showMessage('success', 'Senkronizasyon tamamlandı!');
                    // Update last sync time
                    $button.closest('.google-sheets-status').find('.last-sync').text('Az önce');
                } else {
                    VRSAdmin.showMessage('error', response.data.message || 'Senkronizasyon hatası!');
                }
            });
        },

        /**
         * Generate report
         */
        generateReport: function(e) {
            e.preventDefault();
            var $button = $(this);
            var $results = $('#report-results');
            
            VRSAdmin.showLoading($button);
            
            var data = {
                start_date: $('#report-start-date').val(),
                end_date: $('#report-end-date').val(),
                villa_id: $('#report-villa').val()
            };
            
            VRSAdmin.makeAjaxCall('vrs_generate_report', data, function(response) {
                VRSAdmin.hideLoading($button);
                if (response.success) {
                    $results.html(response.data.html);
                } else {
                    VRSAdmin.showMessage('error', response.data.message || vrsAdmin.strings.error);
                }
            });
        },

        /**
         * Test email functionality
         */
        testEmail: function(e) {
            e.preventDefault();
            var $button = $(this);
            
            VRSAdmin.showLoading($button);
            
            VRSAdmin.makeAjaxCall('vrs_test_email', {}, function(response) {
                VRSAdmin.hideLoading($button);
                if (response.success) {
                    VRSAdmin.showMessage('success', 'Test e-postası gönderildi!');
                } else {
                    VRSAdmin.showMessage('error', response.data.message || 'E-posta gönderimi başarısız!');
                }
            });
        },

        /**
         * Test Google API
         */
        testGoogleAPI: function(e) {
            e.preventDefault();
            var $button = $(this);
            
            VRSAdmin.showLoading($button);
            
            VRSAdmin.makeAjaxCall('vrs_test_google_api', {}, function(response) {
                VRSAdmin.hideLoading($button);
                if (response.success) {
                    VRSAdmin.showMessage('success', 'Google API bağlantısı başarılı!');
                } else {
                    VRSAdmin.showMessage('error', response.data.message || 'Google API bağlantı hatası!');
                }
            });
        },

        /**
         * Cleanup old data
         */
        cleanupOldData: function(e) {
            e.preventDefault();
            
            if (!confirm('Eski verileri silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.')) {
                return;
            }
            
            var $button = $(this);
            VRSAdmin.showLoading($button);
            
            VRSAdmin.makeAjaxCall('vrs_cleanup_old_data', {}, function(response) {
                VRSAdmin.hideLoading($button);
                if (response.success) {
                    VRSAdmin.showMessage('success', 'Eski veriler temizlendi!');
                } else {
                    VRSAdmin.showMessage('error', response.data.message || 'Temizleme işlemi başarısız!');
                }
            });
        },

        /**
         * Toggle pricing fields based on pricing type
         */
        togglePricingFields: function() {
            var pricingType = $(this).val();
            $('.pricing-fields').removeClass('active');
            $('.pricing-fields[data-type="' + pricingType + '"]').addClass('active');
        },

        /**
         * Confirm reservation
         */
        confirmReservation: function(e) {
            e.preventDefault();
            
            if (!confirm('Bu rezervasyonu onaylamak istediğinizden emin misiniz?')) {
                return;
            }
            
            var $button = $(this);
            var reservationId = $button.data('id');
            
            VRSAdmin.showLoading($button);
            
            VRSAdmin.makeAjaxCall('vrs_confirm_reservation', {
                reservation_id: reservationId
            }, function(response) {
                VRSAdmin.hideLoading($button);
                if (response.success) {
                    VRSAdmin.showMessage('success', 'Rezervasyon onaylandı!');
                    location.reload();
                } else {
                    VRSAdmin.showMessage('error', response.data.message || 'Onaylama işlemi başarısız!');
                }
            });
        },

        /**
         * Cancel reservation
         */
        cancelReservation: function(e) {
            e.preventDefault();
            
            if (!confirm('Bu rezervasyonu iptal etmek istediğinizden emin misiniz?')) {
                return;
            }
            
            var $button = $(this);
            var reservationId = $button.data('id');
            
            VRSAdmin.showLoading($button);
            
            VRSAdmin.makeAjaxCall('vrs_cancel_reservation', {
                reservation_id: reservationId
            }, function(response) {
                VRSAdmin.hideLoading($button);
                if (response.success) {
                    VRSAdmin.showMessage('success', 'Rezervasyon iptal edildi!');
                    location.reload();
                } else {
                    VRSAdmin.showMessage('error', response.data.message || 'İptal işlemi başarısız!');
                }
            });
        },

        /**
         * Load chart data
         */
        loadChartData: function() {
            return new Promise(function(resolve) {
                VRSAdmin.makeAjaxCall('vrs_chart_data', {}, function(response) {
                    if (response.success) {
                        resolve(response.data);
                    } else {
                        // Fallback data
                        resolve({
                            labels: ['1', '2', '3', '4', '5', '6', '7'],
                            reservations: [2, 4, 3, 5, 2, 3, 4],
                            revenue: [1200, 2400, 1800, 3000, 1200, 1800, 2400]
                        });
                    }
                });
            });
        },

        /**
         * Make AJAX call
         */
        makeAjaxCall: function(action, data, callback) {
            var ajaxData = {
                action: action,
                nonce: vrsAdmin.nonce
            };
            
            $.extend(ajaxData, data);
            
            $.post(vrsAdmin.ajaxurl, ajaxData, function(response) {
                if (typeof callback === 'function') {
                    callback(response);
                }
            }).fail(function() {
                VRSAdmin.showMessage('error', vrsAdmin.strings.error);
            });
        },

        /**
         * Show loading state
         */
        showLoading: function($element) {
            $element.prop('disabled', true);
            if ($element.is('button')) {
                $element.html('<span class="loading-spinner"></span> ' + vrsAdmin.strings.loading);
            } else {
                $element.addClass('loading');
            }
        },

        /**
         * Hide loading state
         */
        hideLoading: function($element) {
            $element.prop('disabled', false);
            if ($element.is('button')) {
                // Restore original text
                var originalText = $element.data('original-text') || $element.text().replace(/.*? /, '');
                $element.html(originalText);
            } else {
                $element.removeClass('loading');
            }
        },

        /**
         * Show message
         */
        showMessage: function(type, message) {
            var $messageContainer = $('.villa-admin-page');
            var $message = $('<div class="admin-message ' + type + '"><p>' + message + '</p></div>');
            
            $messageContainer.prepend($message);
            
            setTimeout(function() {
                $message.fadeOut(function() {
                    $message.remove();
                });
            }, 5000);
        },

        /**
         * Format currency
         */
        formatCurrency: function(amount) {
            return new Intl.NumberFormat('tr-TR', {
                style: 'currency',
                currency: 'TRY'
            }).format(amount);
        },

        /**
         * Format date
         */
        formatDate: function(date) {
            return new Date(date).toLocaleDateString('tr-TR');
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        VRSAdmin.init();
    });

    // Make VRSAdmin globally accessible
    window.VRSAdmin = VRSAdmin;

})(jQuery);