(function ($) {
    'use strict';

    const AIBG = {
        init: function () {
            this.bindEvents();
            this.initCharts();
        },

        bindEvents: function () {
            // Generate post form
            $('#aibg-generate-form').on('submit', this.handleGenerate);

            // Tab navigation
            $('.nav-tab').on('click', this.handleTabClick);

            // Bulk actions
            $('#doaction, #doaction2').on('click', this.handleBulkAction);
        },

        handleGenerate: function (e) {
            e.preventDefault();

            const $form = $(this);
            const $btn = $('#aibg-generate-btn');
            const $progress = $('#aibg-generation-progress');
            const $result = $('#aibg-generation-result');

            const topic = $('#aibg-topic').val();
            const bulkCount = parseInt($('#aibg-bulk-count').val()) || 1;

            // Disable button and show progress
            $btn.prop('disabled', true).find('.dashicons').addClass('spinning');
            $progress.show();
            $result.hide().html('');

            if (bulkCount === 1) {
                AIBG.generateSinglePost(topic, $btn, $progress, $result);
            } else {
                AIBG.generateBulkPosts(topic, bulkCount, $btn, $progress, $result);
            }
        },

        generateSinglePost: function (topic, $btn, $progress, $result) {
            $.ajax({
                url: aibgAdmin.restUrl + '/generate',
                method: 'POST',
                data: JSON.stringify({ topic: topic }),
                contentType: 'application/json',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', aibgAdmin.nonce);
                },
                success: function (response) {
                    if (response.success) {
                        $result.html(
                            '<div class="notice notice-success">' +
                            '<p><strong>' + aibgAdmin.i18n.success + '</strong></p>' +
                            '<p>Topic: ' + response.topic + '</p>' +
                            '<p><a href="/wp-admin/post.php?post=' + response.post_id + '&action=edit" class="button">Edit Post</a> ' +
                            '<a href="' + response.permalink + '" class="button" target="_blank">View Post</a></p>' +
                            '</div>'
                        ).show();
                    } else {
                        $result.html(
                            '<div class="notice notice-error">' +
                            '<p><strong>' + aibgAdmin.i18n.error + '</strong></p>' +
                            '<p>' + response.error + '</p>' +
                            '</div>'
                        ).show();
                    }
                },
                error: function (xhr) {
                    $result.html(
                        '<div class="notice notice-error">' +
                        '<p><strong>' + aibgAdmin.i18n.error + '</strong></p>' +
                        '<p>' + xhr.responseText + '</p>' +
                        '</div>'
                    ).show();
                },
                complete: function () {
                    $btn.prop('disabled', false).find('.dashicons').removeClass('spinning');
                    $progress.hide();
                }
            });
        },

        generateBulkPosts: function (topic, count, $btn, $progress, $result) {
            let completed = 0;
            const results = [];

            const generateNext = function () {
                if (completed >= count) {
                    // All done
                    $btn.prop('disabled', false).find('.dashicons').removeClass('spinning');
                    $progress.hide();

                    let successCount = results.filter(r => r.success).length;
                    let failCount = results.filter(r => !r.success).length;

                    $result.html(
                        '<div class="notice notice-success">' +
                        '<p><strong>Bulk Generation Complete!</strong></p>' +
                        '<p>Successfully generated: ' + successCount + ' posts</p>' +
                        '<p>Failed: ' + failCount + ' posts</p>' +
                        '<p><a href="/wp-admin/admin.php?page=aibg-posts" class="button">View All Posts</a></p>' +
                        '</div>'
                    ).show();
                    return;
                }

                // Update progress
                const percent = (completed / count) * 100;
                $progress.find('.progress-fill').css('width', percent + '%');
                $progress.find('.progress-text').text('Generating post ' + (completed + 1) + ' of ' + count + '...');

                // Generate post
                $.ajax({
                    url: aibgAdmin.restUrl + '/generate',
                    method: 'POST',
                    data: JSON.stringify({ topic: topic || null }),
                    contentType: 'application/json',
                    beforeSend: function (xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', aibgAdmin.nonce);
                    },
                    success: function (response) {
                        results.push(response);
                        completed++;

                        // Wait 2 seconds before next request to avoid rate limits
                        setTimeout(generateNext, 2000);
                    },
                    error: function (xhr) {
                        results.push({ success: false, error: xhr.responseText });
                        completed++;
                        setTimeout(generateNext, 2000);
                    }
                });
            };

            generateNext();
        },

        handleTabClick: function (e) {
            e.preventDefault();
            const target = $(this).attr('href');

            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');

            $('.tab-content').removeClass('active');
            $(target).addClass('active');
        },

        handleBulkAction: function (e) {
            const action = $(this).siblings('select').val();

            if (action === 'delete') {
                if (!confirm(aibgAdmin.i18n.confirm_delete)) {
                    e.preventDefault();
                    return false;
                }
            }
        },

        initCharts: function () {
            // Initialize Chart.js charts if on analytics page
            if ($('#aibg-chart-trends').length && typeof Chart !== 'undefined') {
                // Trends chart
                if (typeof trendsData !== 'undefined') {
                    new Chart($('#aibg-chart-trends')[0].getContext('2d'), {
                        type: 'line',
                        data: {
                            labels: trendsData.map(d => d.date),
                            datasets: [{
                                label: 'Posts Generated',
                                data: trendsData.map(d => d.count),
                                borderColor: '#0073aa',
                                backgroundColor: 'rgba(0, 115, 170, 0.1)',
                                tension: 0.4
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: { display: false }
                            }
                        }
                    });
                }

                // Status chart
                if (typeof statusData !== 'undefined') {
                    new Chart($('#aibg-chart-status')[0].getContext('2d'), {
                        type: 'doughnut',
                        data: {
                            labels: statusData.map(d => d.status.charAt(0).toUpperCase() + d.status.slice(1)),
                            datasets: [{
                                data: statusData.map(d => d.count),
                                backgroundColor: [
                                    '#46b450',
                                    '#ffb900',
                                    '#dc3232',
                                    '#00a0d2'
                                ]
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: { position: 'bottom' }
                            }
                        }
                    });
                }
            }
        }
    };

    // Initialize on document ready
    $(document).ready(function () {
        AIBG.init();
    });

})(jQuery);

// Add spinning animation
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    .dashicons.spinning {
        animation: spin 1s linear infinite;
    }
    .aibg-progress-bar {
        width: 100%;
        height: 30px;
        background: #f0f0f0;
        border-radius: 5px;
        overflow: hidden;
        margin: 20px 0;
    }
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #0073aa, #00a0d2);
        width: 0%;
        transition: width 0.3s ease;
    }
    .progress-text {
        text-align: center;
        font-weight: bold;
        color: #555;
    }
`;
document.head.appendChild(style);