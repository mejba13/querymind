/**
 * QueryMind Admin JavaScript
 *
 * Enhanced interactions with toast notifications and smooth animations.
 *
 * @package QueryMind
 */

(function($) {
    'use strict';

    /**
     * Toast notification system
     */
    const Toast = {
        container: null,

        init: function() {
            this.container = $('#querymind-toast-container');
            if (!this.container.length) {
                this.container = $('<div id="querymind-toast-container" class="querymind-toast-container" aria-live="polite"></div>');
                $('body').append(this.container);
            }
        },

        show: function(message, type = 'success', duration = 4000) {
            if (!this.container) this.init();

            const icons = {
                success: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
                error: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" x2="9" y1="9" y2="15"></line><line x1="9" x2="15" y1="9" y2="15"></line></svg>',
                warning: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><line x1="12" x2="12" y1="9" y2="13"></line><line x1="12" x2="12.01" y1="17" y2="17"></line></svg>',
                info: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" x2="12" y1="16" y2="12"></line><line x1="12" x2="12.01" y1="8" y2="8"></line></svg>'
            };

            const toast = $(`
                <div class="querymind-toast querymind-toast-${type}" role="alert">
                    <span class="querymind-toast-icon">${icons[type] || icons.info}</span>
                    <span class="querymind-toast-message">${this.escapeHtml(message)}</span>
                    <button class="querymind-toast-close" aria-label="Dismiss">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" x2="6" y1="6" y2="18"></line><line x1="6" x2="18" y1="6" y2="18"></line></svg>
                    </button>
                </div>
            `);

            toast.find('.querymind-toast-close').on('click', () => this.dismiss(toast));
            this.container.append(toast);

            if (duration > 0) {
                setTimeout(() => this.dismiss(toast), duration);
            }

            return toast;
        },

        dismiss: function(toast) {
            toast.addClass('closing');
            setTimeout(() => toast.remove(), 200);
        },

        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    /**
     * Main QueryMind application
     */
    const QueryMind = {
        data: window.queryMindData || {},
        currentQuery: null,
        currentSql: null,
        deleteTargetId: null,

        init: function() {
            Toast.init();
            this.bindEvents();
            this.loadSuggestions();
            this.checkUrlParams();
            this.initKeyboardShortcuts();
        },

        bindEvents: function() {
            // Form submission
            $('#querymind-form').on('submit', this.handleSubmit.bind(this));

            // Input handling
            $('#querymind-input').on('input', this.handleInput.bind(this));
            $('#querymind-input').on('keydown', this.handleKeydown.bind(this));

            // Suggestion chips
            $(document).on('click', '.suggestion-chip', this.handleSuggestionClick.bind(this));

            // Export
            $('#querymind-export-csv').on('click', this.exportCsv.bind(this));

            // Save query
            $('#querymind-save-query').on('click', this.openSaveModal.bind(this));
            $('#querymind-save-confirm').on('click', this.saveQuery.bind(this));
            $('#querymind-save-cancel').on('click', this.closeSaveModal.bind(this));
            $('#querymind-save-name').on('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.saveQuery();
                }
            });

            // View SQL
            $(document).on('click', '.querymind-view-sql', this.showSqlModal.bind(this));
            $(document).on('click', '.querymind-modal-close', this.closeModals.bind(this));
            $('#querymind-copy-sql').on('click', this.copySql.bind(this));

            // Rerun query
            $(document).on('click', '.querymind-rerun', this.handleRerun.bind(this));

            // Delete saved query (with confirmation modal)
            $(document).on('click', '.querymind-delete-saved', this.showDeleteModal.bind(this));
            $('#querymind-delete-confirm').on('click', this.confirmDelete.bind(this));
            $('#querymind-delete-cancel').on('click', this.closeDeleteModal.bind(this));

            // Toggle favorite
            $(document).on('click', '.querymind-toggle-favorite', this.toggleFavorite.bind(this));

            // Close modals on outside click
            $('.querymind-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).fadeOut(200);
                }
            });

            // Close modals on escape
            $(document).on('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.closeModals();
                }
            });
        },

        initKeyboardShortcuts: function() {
            $(document).on('keydown', (e) => {
                // Ctrl/Cmd + K to focus search
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    $('#querymind-input').focus();
                }
            });
        },

        loadSuggestions: function() {
            const container = $('#querymind-suggestions');
            if (!container.length || !this.data.restUrl) return;

            $.ajax({
                url: this.data.restUrl + 'suggestions',
                method: 'GET',
                headers: {
                    'X-WP-Nonce': this.data.nonce
                },
                success: (response) => {
                    if (response.suggestions && response.suggestions.length) {
                        container.empty();
                        response.suggestions.slice(0, 5).forEach((suggestion, index) => {
                            const chip = $('<button>')
                                .addClass('suggestion-chip')
                                .text(suggestion)
                                .attr('type', 'button')
                                .css('animation-delay', `${index * 0.1}s`);
                            container.append(chip);
                        });
                    }
                }
            });
        },

        checkUrlParams: function() {
            const urlParams = new URLSearchParams(window.location.search);
            const query = urlParams.get('query');
            if (query) {
                $('#querymind-input').val(query);
                this.handleInput({ target: { value: query } });
                // Auto-submit if coming from history/saved
                if (urlParams.get('autorun') === '1') {
                    setTimeout(() => $('#querymind-form').submit(), 500);
                }
            }
        },

        handleInput: function(e) {
            const value = e.target ? (e.target.value || $(e.target).val()) : '';
            $('#querymind-submit').prop('disabled', !value.trim());

            // Auto-resize textarea
            const textarea = $('#querymind-input');
            textarea.css('height', 'auto');
            textarea.css('height', Math.min(textarea[0].scrollHeight, 120) + 'px');
        },

        handleKeydown: function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                $('#querymind-form').submit();
            }
        },

        handleSuggestionClick: function(e) {
            const question = $(e.target).text();
            $('#querymind-input').val(question);
            this.handleInput({ target: { value: question } });

            // Add visual feedback
            $(e.target).addClass('selected');
            setTimeout(() => {
                $('#querymind-form').submit();
            }, 150);
        },

        handleSubmit: function(e) {
            e.preventDefault();

            const question = $('#querymind-input').val().trim();
            if (!question) return;

            this.currentQuery = question;
            this.setLoading(true);
            this.addMessage(question, 'user');

            $.ajax({
                url: this.data.restUrl + 'query',
                method: 'POST',
                headers: {
                    'X-WP-Nonce': this.data.nonce
                },
                contentType: 'application/json',
                data: JSON.stringify({ question: question }),
                success: (response) => {
                    this.handleQuerySuccess(response);
                },
                error: (xhr) => {
                    const error = xhr.responseJSON?.message || this.data.strings?.error || 'An error occurred';
                    this.addMessage(error, 'assistant', true);
                    Toast.show(error, 'error');
                },
                complete: () => {
                    this.setLoading(false);
                    $('#querymind-input').val('');
                    this.handleInput({ target: { value: '' } });
                }
            });
        },

        handleQuerySuccess: function(response) {
            if (!response.success) {
                this.addMessage(response.message || this.data.strings?.error || 'Query failed', 'assistant', true);
                return;
            }

            this.currentSql = response.sql;

            // Add explanation message
            if (response.explanation) {
                this.addMessage(response.explanation, 'assistant');
            }

            // Show results
            this.showResults(response);

            // Success toast
            Toast.show(`Found ${response.row_count} results in ${response.execution_time}s`, 'success');
        },

        showResults: function(response) {
            const panel = $('#querymind-results');
            const content = $('#querymind-results-content');
            const meta = $('#querymind-results-meta');
            const sqlCode = $('#querymind-sql-code');

            // Meta info with icons
            meta.html(`
                <span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -3px; margin-right: 4px;"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"></rect><line x1="3" x2="21" y1="9" y2="9"></line><line x1="9" x2="9" y1="21" y2="9"></line></svg>
                    ${response.row_count} ${this.data.strings?.rows || 'rows'}
                </span>
                <span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -3px; margin-right: 4px;"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    ${this.data.strings?.executionTime || 'Execution time'}: ${response.execution_time}s
                </span>
            `);

            // Build table
            if (response.data && response.data.length > 0) {
                const columns = response.columns || Object.keys(response.data[0]);
                let tableHtml = '<table class="querymind-results-table">';

                // Header
                tableHtml += '<thead><tr>';
                columns.forEach(col => {
                    tableHtml += `<th>${this.escapeHtml(col)}</th>`;
                });
                tableHtml += '</tr></thead>';

                // Body
                tableHtml += '<tbody>';
                response.data.forEach((row, index) => {
                    tableHtml += `<tr style="animation-delay: ${index * 0.02}s">`;
                    columns.forEach(col => {
                        const value = row[col] !== null ? row[col] : '';
                        tableHtml += `<td>${this.escapeHtml(String(value))}</td>`;
                    });
                    tableHtml += '</tr>';
                });
                tableHtml += '</tbody></table>';

                content.html(tableHtml);
            } else {
                content.html(`
                    <div style="text-align: center; padding: 40px; color: var(--qm-text-muted);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 16px; opacity: 0.5;"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path></svg>
                        <p>${this.data.strings?.noResults || 'No results found'}</p>
                    </div>
                `);
            }

            // SQL code
            sqlCode.text(response.sql);

            // Show panel with animation
            panel.hide().slideDown(300);
        },

        addMessage: function(content, type, isError = false) {
            const messages = $('#querymind-messages');
            const welcome = messages.find('.querymind-welcome');
            if (welcome.length) {
                welcome.fadeOut(200, function() { $(this).remove(); });
            }

            const avatarIcon = type === 'user'
                ? '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>'
                : '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M3 5V19A9 3 0 0 0 21 19V5"></path><path d="M3 12A9 3 0 0 0 21 12"></path></svg>';

            const messageClass = isError ? 'error' : '';
            const html = `
                <div class="querymind-message ${type} ${messageClass}">
                    <div class="querymind-message-avatar">${avatarIcon}</div>
                    <div class="querymind-message-content">${this.escapeHtml(content)}</div>
                </div>
            `;

            messages.append(html);
            messages.scrollTop(messages[0].scrollHeight);
        },

        setLoading: function(loading) {
            const submit = $('#querymind-submit');
            const input = $('#querymind-input');

            if (loading) {
                submit.prop('disabled', true);
                input.prop('disabled', true);

                // Add loading message
                const messages = $('#querymind-messages');
                messages.append(`
                    <div class="querymind-message assistant querymind-loading-message">
                        <div class="querymind-message-avatar">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M3 5V19A9 3 0 0 0 21 19V5"></path><path d="M3 12A9 3 0 0 0 21 12"></path></svg>
                        </div>
                        <div class="querymind-loading">
                            <div class="querymind-loading-dots">
                                <div class="querymind-loading-dot"></div>
                                <div class="querymind-loading-dot"></div>
                                <div class="querymind-loading-dot"></div>
                            </div>
                            <span>${this.data.strings?.loading || 'Analyzing your question...'}</span>
                        </div>
                    </div>
                `);
                messages.scrollTop(messages[0].scrollHeight);
            } else {
                input.prop('disabled', false);
                $('.querymind-loading-message').fadeOut(200, function() { $(this).remove(); });
            }
        },

        exportCsv: function() {
            const table = $('.querymind-results-table');
            if (!table.length) {
                Toast.show('No data to export', 'warning');
                return;
            }

            const rows = [];
            const headers = [];

            // Get headers
            table.find('thead th').each(function() {
                headers.push($(this).text());
            });
            rows.push(headers);

            // Get data
            table.find('tbody tr').each(function() {
                const row = [];
                $(this).find('td').each(function() {
                    row.push($(this).text());
                });
                rows.push(row);
            });

            // Convert to CSV
            const csv = rows.map(row => row.map(cell => {
                cell = String(cell).replace(/"/g, '""');
                return cell.includes(',') || cell.includes('"') || cell.includes('\n')
                    ? `"${cell}"` : cell;
            }).join(',')).join('\n');

            // Download
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'querymind-export-' + new Date().toISOString().slice(0, 10) + '.csv';
            link.click();
            URL.revokeObjectURL(url);

            Toast.show('CSV exported successfully', 'success');
        },

        openSaveModal: function() {
            if (!this.currentQuery) {
                Toast.show('No query to save', 'warning');
                return;
            }
            $('#querymind-save-name').val('');
            $('#querymind-save-modal').fadeIn(200);
            setTimeout(() => $('#querymind-save-name').focus(), 100);
        },

        closeSaveModal: function() {
            $('#querymind-save-modal').fadeOut(200);
        },

        saveQuery: function() {
            const name = $('#querymind-save-name').val().trim();
            if (!name) {
                Toast.show('Please enter a query name', 'warning');
                $('#querymind-save-name').focus();
                return;
            }

            if (!this.currentQuery || !this.currentSql) {
                Toast.show('No query data available', 'error');
                return;
            }

            const btn = $('#querymind-save-confirm');
            const originalHtml = btn.html();
            btn.prop('disabled', true).html('Saving...');

            $.ajax({
                url: this.data.restUrl + 'saved',
                method: 'POST',
                headers: {
                    'X-WP-Nonce': this.data.nonce
                },
                contentType: 'application/json',
                data: JSON.stringify({
                    name: name,
                    question: this.currentQuery,
                    sql: this.currentSql
                }),
                success: () => {
                    this.closeSaveModal();
                    Toast.show('Query saved successfully!', 'success');
                },
                error: (xhr) => {
                    const msg = xhr.responseJSON?.message || 'Failed to save query';
                    Toast.show(msg, 'error');
                },
                complete: () => {
                    btn.prop('disabled', false).html(originalHtml);
                }
            });
        },

        showSqlModal: function(e) {
            e.preventDefault();
            const sql = $(e.currentTarget).data('sql');
            $('#querymind-modal-sql-code').text(sql);
            $('#querymind-sql-modal').fadeIn(200);
        },

        closeModals: function() {
            $('.querymind-modal').fadeOut(200);
        },

        copySql: function() {
            const sql = $('#querymind-modal-sql-code').text();
            navigator.clipboard.writeText(sql).then(() => {
                const btn = $('#querymind-copy-sql');
                const originalHtml = btn.html();
                btn.html(`
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px; vertical-align: -3px;"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    Copied!
                `);
                Toast.show('SQL copied to clipboard', 'success', 2000);
                setTimeout(() => btn.html(originalHtml), 2000);
            }).catch(() => {
                Toast.show('Failed to copy', 'error');
            });
        },

        handleRerun: function(e) {
            e.preventDefault();
            const question = $(e.currentTarget).data('question');
            const baseUrl = this.data.settingsUrl ?
                this.data.settingsUrl.replace('querymind-settings', 'querymind') :
                window.location.pathname.replace('querymind-history', 'querymind').replace('querymind-saved', 'querymind');
            window.location.href = baseUrl + '&query=' + encodeURIComponent(question);
        },

        showDeleteModal: function(e) {
            e.preventDefault();
            e.stopPropagation();
            const card = $(e.currentTarget).closest('.querymind-saved-card');
            this.deleteTargetId = card.data('id');
            $('#querymind-delete-modal').fadeIn(200);
        },

        closeDeleteModal: function() {
            $('#querymind-delete-modal').fadeOut(200);
            this.deleteTargetId = null;
        },

        confirmDelete: function() {
            if (!this.deleteTargetId) return;

            const id = this.deleteTargetId;
            const card = $(`.querymind-saved-card[data-id="${id}"]`);
            const btn = $('#querymind-delete-confirm');
            const originalHtml = btn.html();

            btn.prop('disabled', true).html('Deleting...');

            $.ajax({
                url: this.data.restUrl + 'saved/' + id,
                method: 'DELETE',
                headers: {
                    'X-WP-Nonce': this.data.nonce
                },
                success: () => {
                    this.closeDeleteModal();
                    card.css('transform', 'scale(0.9)').fadeOut(300, function() {
                        $(this).remove();
                        // Check if grid is now empty
                        if ($('.querymind-saved-card').length === 0) {
                            location.reload();
                        }
                    });
                    Toast.show('Query deleted successfully', 'success');
                },
                error: (xhr) => {
                    const msg = xhr.responseJSON?.message || 'Failed to delete query';
                    Toast.show(msg, 'error');
                },
                complete: () => {
                    btn.prop('disabled', false).html(originalHtml);
                }
            });
        },

        toggleFavorite: function(e) {
            e.preventDefault();
            e.stopPropagation();

            const btn = $(e.currentTarget);
            const card = btn.closest('.querymind-saved-card');
            const id = card.data('id');

            // Optimistic UI update
            const svg = btn.find('svg');
            const isFavorite = svg.attr('fill') !== 'none';

            svg.attr('fill', isFavorite ? 'none' : 'var(--qm-sunlit-clay)');

            $.ajax({
                url: this.data.restUrl + 'saved/' + id + '/favorite',
                method: 'POST',
                headers: {
                    'X-WP-Nonce': this.data.nonce
                },
                success: (response) => {
                    Toast.show(
                        response.is_favorite ? 'Added to favorites' : 'Removed from favorites',
                        'success',
                        2000
                    );
                },
                error: () => {
                    // Revert on error
                    svg.attr('fill', isFavorite ? 'var(--qm-sunlit-clay)' : 'none');
                    Toast.show('Failed to update favorite', 'error');
                }
            });
        },

        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        QueryMind.init();
    });

})(jQuery);
