<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'CRM') }}</title>
    <style>
        :root {
            color-scheme: light;
            font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
            line-height: 1.5;
        }

        body {
            margin: 0;
            background: linear-gradient(180deg, #f6f8fc 0%, #eef1f8 100%);
            color: #111827;
        }

        a {
            color: #2563eb;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .layout {
            max-width: 1100px;
            margin: 0 auto;
            padding: 2rem 1.5rem 4rem;
        }

        .card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 30px 60px -35px rgba(15, 23, 42, 0.3);
            padding: 1.75rem;
        }

        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .nav h1 {
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            border: none;
            border-radius: 8px;
            padding: 0.6rem 1.25rem;
            font-size: 0.95rem;
            cursor: pointer;
            transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
            font-weight: 600;
        }

        .btn-primary {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #ffffff;
            box-shadow: 0 10px 25px -15px rgba(37, 99, 235, 0.65);
        }

        .btn-secondary {
            background: #edf2ff;
            color: #3730a3;
            border: 1px solid #c7d2fe;
        }

        .btn-ghost {
            background: transparent;
            color: #0f172a;
            border: 1px solid rgba(148, 163, 184, 0.35);
        }

        .btn-ghost:hover {
            background: #f1f5f9;
            color: #1f2937;
        }

        .btn-danger {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            box-shadow: none;
        }

        .btn:not(:disabled):hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 24px -20px rgba(15, 23, 42, 0.45);
        }

        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 12px;
            margin-top: 1.1rem;
        }

        .table thead th {
            padding: 0.65rem 0.95rem;
            text-align: left;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
            border: none;
            border-bottom: 1px solid rgba(148, 163, 184, 0.35);
        }

        .table thead th:first-child {
            padding-left: 1.15rem;
        }

        .table thead th:last-child {
            padding-right: 1.1rem;
        }

        .table tbody td {
            background: #ffffff;
            border: none;
            padding: 1rem 0.95rem;
            text-align: left;
            vertical-align: middle;
        }

        .table tbody td + td {
            border-left: 1px solid rgba(241, 245, 249, 0.9);
        }

        .table tbody tr {
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }

        .table tbody tr.contact-row {
            box-shadow: 0 18px 45px -40px rgba(15, 23, 42, 0.65);
            background: #ffffff;
        }

        .table tbody tr.contact-row:hover {
            transform: translateY(-2px);
            box-shadow: 0 24px 55px -30px rgba(37, 99, 235, 0.35);
        }

        .table tbody tr.contact-row td:first-child {
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
            padding-left: 1.15rem;
        }

        .table tbody tr.contact-row td:last-child {
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
            padding-right: 1.1rem;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            padding: 0.15rem 0.6rem;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 600;
        }

        .status-active {
            background: #dcfce7;
            color: #16a34a;
        }

        .status-merged {
            background: #e0e7ff;
            color: #3730a3;
        }

        .status-archived {
            background: #fef3c7;
            color: #d97706;
        }

        .filters {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            padding: 1rem 1.25rem;
            background: linear-gradient(135deg, rgba(248, 250, 252, 0.92), rgba(231, 233, 251, 0.92));
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.2);
            box-shadow: 0 25px 45px -35px rgba(15, 23, 42, 0.45);
        }

        .filter-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.55rem 0.85rem;
            border-radius: 12px;
            background: #ffffff;
            border: 1px solid rgba(148, 163, 184, 0.28);
            box-shadow: 0 4px 10px -8px rgba(15, 23, 42, 0.25);
        }

        .filter-chip label {
            font-weight: 600;
            font-size: 0.82rem;
            color: #0f172a;
        }

        .filter-chip .input,
        .filter-chip select {
            border: none;
            padding: 0.3rem 0.35rem;
            min-width: 140px;
            background: transparent;
        }

        .filter-chip .input:focus,
        .filter-chip select:focus {
            outline: none;
            box-shadow: none;
        }

        .filters .input,
        .filters select {
            border: none !important;
            box-shadow: none !important;
            background: transparent;
        }

        .filters .filter-actions {
            margin-left: auto;
            display: inline-flex;
            gap: 0.6rem;
        }

        .input,
        select,
        textarea {
            /* width: 100%; */
            border-radius: 8px;
            border: 1px solid #d1d5db;
            padding: 0.55rem 0.75rem;
            font-size: 0.95rem;
            background: #ffffff;
            transition: border-color 0.18s ease, box-shadow 0.18s ease;
        }

        .input:focus,
        select:focus,
        textarea:focus {
            outline: 2px solid #2563eb22;
            border-color: #2563eb;
        }

        .modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 40;
            background: rgba(15, 23, 42, 0.35);
            display: none;
        }

        .modal {
            position: fixed;
            inset: 0;
            z-index: 50;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .modal.active,
        .modal-backdrop.active {
            display: flex;
        }

        .modal-card {
            width: min(680px, 100% - 2rem);
            background: #ffffff;
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 28px 75px -35px rgba(15, 23, 42, 0.6);
            max-height: 85vh;
            overflow-y: auto;
        }

        .tag {
            display: inline-flex;
            align-items: center;
            padding: 0.15rem 0.45rem;
            border-radius: 6px;
            font-size: 0.75rem;
            background: #e2e8f0;
            color: #1f2937;
            margin: 0.15rem;
        }

        .stacked {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .message {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: none;
            border: 1px solid transparent;
        }

        .message.success {
            display: block;
            background: #ecfdf5;
            color: #047857;
            border-color: #a7f3d0;
        }

        .message.error {
            display: block;
            background: #fee2e2;
            color: #b91c1c;
            border-color: #fecaca;
        }

        .grid-two {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1.2rem 1.25rem;
        }

        .grid-two > .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            min-width: 0;
        }

        @media (max-width: 640px) {
            .grid-two {
                grid-template-columns: 1fr;
            }
        }

        .form-label {
            font-weight: 600;
            color: #0f172a;
            font-size: 0.9rem;
        }

        .modal-card .input,
        .modal-card select,
        .modal-card textarea {
            /* width: 100%; */
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            padding: 0.6rem 0.85rem;
            transition: border-color 0.18s ease, box-shadow 0.18s ease;
            /* min-height: 48px; */
        }

        .modal-card .input:focus,
        .modal-card select:focus,
        .modal-card textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .file-upload {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            background: #f8fafc;
            padding: 0.6rem 0.85rem;
            min-height: 48px;
        }

        .file-upload-button {
            position: relative;
            overflow: hidden;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
            border-radius: 10px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #ffffff;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 12px 25px -18px rgba(37, 99, 235, 0.6);
            min-width: 120px;
        }

        .file-upload-button input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
        }

        .file-upload-name {
            font-size: 0.85rem;
            color: #475569;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex: 1;
        }

        .merge-summary {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
        }

        .table-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
        }

        .avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            background: linear-gradient(135deg, #6366f1, #3b82f6);
            color: #ffffff;
            letter-spacing: 0.03em;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.25);
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .stat-card {
            background: #ffffff;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 12px 24px -20px rgba(15, 23, 42, 0.35);
            padding: 1rem 1.25rem;
        }

        .name-cell {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .name-cell .meta {
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
            padding: 0.55rem 0.7rem;
            border-radius: 12px;
            background: linear-gradient(135deg, rgba(241, 245, 249, 0.7), rgba(224, 231, 255, 0.65));
            box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.2);
            min-width: 180px;
        }

        .name-cell .meta strong {
            font-size: 1rem;
            font-weight: 700;
            color: #0f172a;
        }

        .name-cell .meta span {
            font-size: 0.78rem;
            color: #64748b;
            letter-spacing: 0.02em;
        }

        .stat-card span {
            display: block;
        }

        .stat-card .label {
            font-size: 0.85rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .stat-card .value {
            font-size: 1.4rem;
            font-weight: 700;
            margin-top: 0.35rem;
            color: #1f2937;
        }
    </style>
</head>
<body>
    <div class="layout">
        <div class="nav">
            <h1 style="margin: 0; font-size: 1.6rem;">{{ $header ?? 'Contacts CRM' }}</h1>
            <div class="nav-links">
                <a href="{{ route('contacts.index') }}">Contacts</a>
                <a href="{{ route('custom-fields.index') }}">Custom Fields</a>
            </div>
        </div>

        <div id="flash-message" class="message"></div>

        @yield('content')
    </div>

    @stack('modals')
    @stack('scripts')
</body>
</html>
