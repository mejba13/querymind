/**
 * QueryMind Admin JavaScript
 *
 * Fallback JavaScript for when React build is not available.
 */

(function($) {
    'use strict';

    const QueryMind = {
        data: window.queryMindData || {},
        currentQuery: null,
        currentSql: null,

        init: function() {
            this.bindEvents();
            this.loadSuggestions();
            this.checkUrlParams();
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

            // View SQL
            $(document).on('click', '.querymind-view-sql', this.showSqlModal.bind(this));
            $(document).on('click', '.querymind-modal-close', this.closeModals.bind(this));
            $('#querymind-copy-sql').on('click', this.copySql.bind(this));

            // Rerun query
            $(document).on('click', '.querymind-rerun', this.handleRerun.bind(this));

            // Delete saved query
            $(document).on('click', '.querymind-delete-saved', this.deleteSavedQuery.bind(this));

            // Close modals on outside click
            $('.querymind-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).hide();
                }
            });
        },

        loadSuggestions: function() {
            const container = $('#querymind-suggestions');
            if (!container.length) return;

            $.ajax({
                url: this.data.restUrl + 'suggestions',
                method: 'GET',
                headers: {
                    'X-WP-Nonce': this.data.nonce
                },
                success: (response) => {
                    if (response.suggestions) {
                        container.empty();
                        response.suggestions.slice(0, 5).forEach(suggestion => {
                            container.append(
                                $('<button>')
                                    .addClass('suggestion-chip')
                                    .text(suggestion)
                                    .attr('type', 'button')
                            );
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
            }
        },

        handleInput: function(e) {
            const value = e.target.value || $(e.target).val();
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
            $('#querymind-form').submit();
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
                    const error = xhr.responseJSON?.message || this.data.strings.error;
                    this.addMessage(error, 'assistant', true);
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
                this.addMessage(response.message || this.data.strings.error, 'assistant', true);
                return;
            }

            this.currentSql = response.sql;

            // Add explanation message
            if (response.explanation) {
                this.addMessage(response.explanation, 'assistant');
            }

            // Show results
            this.showResults(response);
        },

        showResults: function(response) {
            const panel = $('#querymind-results');
            const content = $('#querymind-results-content');
            const meta = $('#querymind-results-meta');
            const sqlCode = $('#querymind-sql-code');

            // Meta info
            meta.html(`
                <span>${response.row_count} ${this.data.strings.rows}</span>
                <span>${this.data.strings.executionTime}: ${response.execution_time}s</span>
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
                response.data.forEach(row => {
                    tableHtml += '<tr>';
                    columns.forEach(col => {
                        const value = row[col] !== null ? row[col] : '';
                        tableHtml += `<td>${this.escapeHtml(String(value))}</td>`;
                    });
                    tableHtml += '</tr>';
                });
                tableHtml += '</tbody></table>';

                content.html(tableHtml);
            } else {
                content.html(`<p class="querymind-no-results">${this.data.strings.noResults}</p>`);
            }

            // SQL code
            sqlCode.text(response.sql);

            panel.show();
        },

        addMessage: function(content, type, isError = false) {
            const messages = $('#querymind-messages');
            const welcome = messages.find('.querymind-welcome');
            if (welcome.length) {
                welcome.hide();
            }

            const messageClass = isError ? 'error' : '';
            const html = `
                <div class="querymind-message ${type} ${messageClass}">
                    <div class="querymind-message-content">
                        ${this.escapeHtml(content)}
                    </div>
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
                        <div class="querymind-loading">
                            <div class="querymind-loading-dots">
                                <div class="querymind-loading-dot"></div>
                                <div class="querymind-loading-dot"></div>
                                <div class="querymind-loading-dot"></div>
                            </div>
                            <span>${this.data.strings.loading}</span>
                        </div>
                    </div>
                `);
            } else {
                input.prop('disabled', false);
                $('.querymind-loading-message').remove();
            }
        },

        exportCsv: function() {
            const table = $('.querymind-results-table');
            if (!table.length) return;

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
        },

        openSaveModal: function() {
            if (!this.currentQuery) return;
            $('#querymind-save-name').val('');
            $('#querymind-save-modal').show();
            $('#querymind-save-name').focus();
        },

        closeSaveModal: function() {
            $('#querymind-save-modal').hide();
        },

        saveQuery: function() {
            const name = $('#querymind-save-name').val().trim();
            if (!name || !this.currentQuery || !this.currentSql) return;

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
                    // Show success notice
                    alert('Query saved successfully!');
                },
                error: () => {
                    alert('Failed to save query.');
                }
            });
        },

        showSqlModal: function(e) {
            e.preventDefault();
            const sql = $(e.currentTarget).data('sql');
            $('#querymind-modal-sql-code').text(sql);
            $('#querymind-sql-modal').show();
        },

        closeModals: function() {
            $('.querymind-modal').hide();
        },

        copySql: function() {
            const sql = $('#querymind-modal-sql-code').text();
            navigator.clipboard.writeText(sql).then(() => {
                const btn = $('#querymind-copy-sql');
                const originalText = btn.html();
                btn.html('<span class="dashicons dashicons-yes"></span> Copied!');
                setTimeout(() => btn.html(originalText), 2000);
            });
        },

        handleRerun: function(e) {
            e.preventDefault();
            const question = $(e.currentTarget).data('question');
            window.location.href = this.data.settingsUrl.replace('querymind-settings', 'querymind') + '&query=' + encodeURIComponent(question);
        },

        deleteSavedQuery: function(e) {
            e.preventDefault();
            if (!confirm('Are you sure you want to delete this saved query?')) return;

            const card = $(e.currentTarget).closest('.querymind-saved-card');
            const id = card.data('id');

            $.ajax({
                url: this.data.restUrl + 'saved/' + id,
                method: 'DELETE',
                headers: {
                    'X-WP-Nonce': this.data.nonce
                },
                success: () => {
                    card.fadeOut(300, function() { $(this).remove(); });
                },
                error: () => {
                    alert('Failed to delete query.');
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
