<style>
.master-form-card .card-body {
    padding-top: 1rem;
}

.master-form-page-card .card-body {
    padding-top: 1.15rem;
}

.master-form-page .profile-detail-card,
.master-form-page .profile-note-card {
    background: #fff;
}

.master-form-page .profile-input,
.master-form-page .form-select.profile-input {
    min-height: 48px;
}

.master-list-page .dashboard-card + .dashboard-card {
    margin-top: 0.75rem;
}

.master-list-page .card-body {
    padding-bottom: 0.9rem;
}

.master-list-page .card-header {
    min-height: 64px;
}

.master-list-add-btn {
    min-height: 38px;
}

.master-list-page th.master-action-col,
.master-list-page td.master-action-col {
    width: 170px;
    white-space: nowrap;
}

.master-list-page .table th,
.master-list-page .table td {
    vertical-align: middle;
}

.master-status-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 94px;
    padding: 0.38rem 0.85rem;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    line-height: 1;
    border: 1px solid transparent;
}

.master-status-active {
    color: #b86b00;
    background: #fff4df;
    border-color: #ffd78d;
}

.master-status-inactive {
    color: #5f6f85;
    background: #eef3f9;
    border-color: #d5e0ee;
}

.master-form-grid {
    row-gap: 1rem;
}

.master-field-group {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
}

.master-field-group .form-label {
    margin-bottom: 0;
    font-weight: 600;
    color: #123c6b;
}

.master-field-note {
    font-size: 12px;
    color: #6f7f96;
    line-height: 1.45;
}

.master-check-section {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    padding: 0.9rem 1rem;
    border: 1px solid #e3ebf7;
    border-radius: 14px;
    background: #fbfdff;
}

.master-check-title {
    font-size: 13px;
    font-weight: 700;
    color: #123c6b;
    margin: 0;
}

.master-check-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem 1.25rem;
}

.master-check-grid .form-check-label {
    padding: 0.45rem 0.8rem;
    border: 1px solid #dbe6f4;
    border-radius: 999px;
    background: #fff;
    min-height: 42px;
}

.master-check-grid .form-check-input {
    margin-top: 0;
}

.master-form-actions {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 0.75rem;
    padding-top: 0.5rem;
    border-top: 1px solid #edf2fa;
    margin-top: 0.25rem;
}

.master-page-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

@media (max-width: 767.98px) {
    .master-form-actions {
        flex-direction: column-reverse;
        align-items: stretch;
    }

    .master-form-actions .btn {
        width: 100%;
    }
}
</style>
