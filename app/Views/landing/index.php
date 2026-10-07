<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AR Tilik — Sistem Penelaah Kesepadanan Naskah Akademik Mandiri & Keadilan Bukti</title>
    <meta name="description" content="AR Tilik adalah instrumen penelaah kesepadanan karya ilmiah institusional berbasis bukti ilmiah, arsitektur Dual-Lens, kesadaran konteks sitasi, dan transparansi akademik.">
    <!-- Favicon & Brand Icons -->
    <link rel="icon" type="image/x-icon" href="<?= base_url('favicon.ico?v=20261006') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('favicon-32x32.png?v=20261006') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= base_url('favicon-16x16.png?v=20261006') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('apple-touch-icon.png?v=20261006') ?>">
    <link rel="shortcut icon" type="image/x-icon" href="<?= base_url('favicon.ico?v=20261006') ?>">
    <link rel="manifest" href="<?= base_url('site.webmanifest') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Animate On Scroll (AOS) -->
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css">
    <style>
        :root {
            --primary: #4338ca;
            --primary-dark: #312e81;
            --primary-light: #6366f1;
            --secondary: #0ea5e9;
            --dark: #0f172a;
            --dark-card: #1e293b;
            --light-bg: #f8fafc;
            --surface: #ffffff;
            --border: #e2e8f0;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --emerald: #10b981;
            --amber: #f59e0b;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--light-bg);
            color: var(--text-main);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* Navbar */
        .navbar {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            padding: 16px 0;
            transition: all 0.3s ease;
        }

        .nav-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            transition: transform 0.2s ease;
        }

        .brand:hover {
            transform: scale(1.02);
        }

        .brand-logo {
            width: 40px;
            height: 40px;
            object-fit: contain;
            border-radius: 10px;
            filter: drop-shadow(0 2px 5px rgba(67, 56, 202, 0.18));
        }

        .brand-text {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .brand-title {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, #4338ca 0%, #6366f1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .brand-badge {
            font-size: 10px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 12px;
            background: #eef2ff;
            color: #4338ca;
            border: 1px solid #c7d2fe;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 24px;
            list-style: none;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-muted);
            font-size: 14px;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 13px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(67, 56, 202, 0.25);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(67, 56, 202, 0.35);
        }

        .btn-outline {
            border-color: var(--border);
            color: var(--text-main);
            background: #ffffff;
        }

        .btn-outline:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: #f8fafc;
        }

        .btn-secondary {
            background: #f1f5f9;
            color: var(--text-main);
            border-color: #cbd5e1;
        }

        /* Hero Section */
        .hero {
            padding: 90px 0 60px;
            position: relative;
            background: radial-gradient(circle at 50% 10%, rgba(99, 102, 241, 0.08) 0%, transparent 60%);
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
            background: rgba(67, 56, 202, 0.08);
            border: 1px solid rgba(67, 56, 202, 0.2);
            border-radius: 30px;
            font-size: 13px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 24px;
        }

        .hero-title {
            font-size: 48px;
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -1.5px;
            color: var(--dark);
            margin-bottom: 20px;
            max-width: 900px;
        }

        .hero-title span {
            background: linear-gradient(135deg, #4338ca 0%, #0284c7 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-subtitle {
            font-size: 18px;
            color: var(--text-muted);
            max-width: 760px;
            margin-bottom: 36px;
            line-height: 1.6;
        }

        .hero-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: center;
            margin-bottom: 50px;
        }

        .stats-bar {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 24px 32px;
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.04);
        }

        .stat-item {
            text-align: left;
        }

        .stat-val {
            font-size: 28px;
            font-weight: 800;
            color: var(--dark);
            font-family: 'JetBrains Mono', monospace;
        }

        .stat-lbl {
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 500;
            margin-top: 4px;
        }

        /* Section Styling */
        .section {
            padding: 90px 0;
        }

        .section-header {
            text-align: center;
            max-width: 740px;
            margin: 0 auto 60px;
        }

        .section-tag {
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: 1px;
            color: var(--primary);
            margin-bottom: 12px;
        }

        .section-title {
            font-size: 34px;
            font-weight: 800;
            color: var(--dark);
            letter-spacing: -0.8px;
            margin-bottom: 16px;
        }

        .section-desc {
            font-size: 16px;
            color: var(--text-muted);
            line-height: 1.6;
        }

        /* Theory Cards */
        .grid-4 {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        @media (max-width: 1120px) {
            .grid-4 {
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 28px;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 32px;
        }

        .theory-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 32px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .theory-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px -4px rgba(0, 0, 0, 0.06);
            border-color: rgba(99, 102, 241, 0.4);
        }

        .card-icon-badge {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 22px;
        }

        .badge-indigo { background: rgba(99, 102, 241, 0.12); color: #4338ca; }
        .badge-emerald { background: rgba(16, 185, 129, 0.12); color: #059669; }
        .badge-amber { background: rgba(245, 158, 11, 0.12); color: #d97706; }
        .badge-cyan { background: rgba(14, 165, 233, 0.12); color: #0284c7; }

        .theory-title {
            font-size: 20px;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 12px;
            letter-spacing: -0.3px;
        }

        .theory-desc {
            font-size: 14px;
            color: var(--text-muted);
            line-height: 1.6;
        }

        /* Interactive Layers Diagram */
        .layer-box {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 22px;
            padding: 36px;
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.04);
        }

        .layer-item {
            display: flex;
            gap: 20px;
            padding: 20px;
            border-radius: 14px;
            margin-bottom: 14px;
            border: 1px solid var(--border);
            background: #f8fafc;
        }

        .layer-item.layer-a { border-left: 6px solid #4338ca; }
        .layer-item.layer-b { border-left: 6px solid #059669; }
        .layer-item.layer-c { border-left: 6px solid #d97706; }

        .layer-num {
            font-size: 14px;
            font-weight: 800;
            font-family: 'JetBrains Mono', monospace;
            color: var(--dark);
            padding: 4px 10px;
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 8px;
            align-self: flex-start;
        }

        /* How to use step cards */
        .step-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 28px;
            position: relative;
        }

        .step-index {
            font-size: 32px;
            font-weight: 800;
            color: rgba(67, 56, 202, 0.15);
            font-family: 'JetBrains Mono', monospace;
            position: absolute;
            top: 20px;
            right: 24px;
        }

        .step-title {
            font-size: 18px;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 10px;
        }

        .step-desc {
            font-size: 14px;
            color: var(--text-muted);
            line-height: 1.6;
        }

        /* Incentive & Crowdsourcing Banner */
        .incentive-banner {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
            border-radius: 24px;
            padding: 48px;
            color: #ffffff;
            position: relative;
            overflow: hidden;
        }

        .incentive-grid {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 40px;
            align-items: center;
        }

        .incentive-title {
            font-size: 32px;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 16px;
        }

        .incentive-desc {
            font-size: 15px;
            color: #cbd5e1;
            line-height: 1.7;
            margin-bottom: 24px;
        }

        .formula-box {
            background: rgba(255, 255, 255, 0.08);
            border: 1px dashed rgba(255, 255, 255, 0.25);
            border-radius: 16px;
            padding: 24px;
            text-align: center;
        }

        .formula-text {
            font-size: 20px;
            font-weight: 800;
            font-family: 'JetBrains Mono', monospace;
            color: #38bdf8;
            margin-bottom: 8px;
        }

        /* Pricing Cards */
        #langganan .container {
            max-width: 1240px;
        }

        .pricing-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 26px 20px;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.25s ease;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03);
        }

        .pricing-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 32px -6px rgba(15, 23, 42, 0.08);
        }

        .pricing-card.featured {
            border: 2px solid var(--primary);
            box-shadow: 0 10px 28px -4px rgba(67, 56, 202, 0.15);
        }

        .pricing-card.popular {
            border: 2px solid #f59e0b;
            box-shadow: 0 12px 30px -4px rgba(245, 158, 11, 0.18);
            background: linear-gradient(180deg, #fffdf8 0%, #ffffff 100%);
        }

        .pricing-card.popular:hover {
            transform: translateY(-4px);
            box-shadow: 0 18px 36px -6px rgba(245, 158, 11, 0.25);
        }

        .pricing-badge {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--primary);
            color: #fff;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .pricing-badge.popular {
            background: #f59e0b;
        }

        .plan-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 9999px;
            font-size: 10.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
        }

        .plan-pill-free {
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }

        .plan-pill-payg {
            background: #f0f9ff;
            color: #0284c7;
            border: 1px solid #bae6fd;
        }

        .plan-pill-popular {
            background: #fffbeb;
            color: #d97706;
            border: 1px solid #fde68a;
        }

        .plan-pill-pro {
            background: #f5f3ff;
            color: #7c3aed;
            border: 1px solid #ddd6fe;
        }

        .plan-name {
            font-size: 19px;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 8px;
            line-height: 1.25;
            letter-spacing: -0.3px;
        }

        .plan-price {
            font-size: 26px;
            font-weight: 800;
            color: var(--dark);
            font-family: inherit;
            margin-bottom: 10px;
            display: flex;
            align-items: baseline;
            flex-wrap: wrap;
            gap: 4px;
            line-height: 1.15;
        }

        .plan-price .currency {
            font-size: 16px;
            font-weight: 700;
            color: var(--dark);
        }

        .plan-price .amount {
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .plan-period {
            font-size: 12.5px;
            color: var(--text-muted);
            font-weight: 600;
            letter-spacing: normal;
        }

        .plan-desc {
            font-size: 12.5px;
            color: var(--text-muted);
            line-height: 1.5;
            min-height: 52px;
            margin-bottom: 16px;
        }

        .plan-features {
            list-style: none;
            padding: 16px 0 0 0;
            margin: 0 0 24px 0;
            display: flex;
            flex-direction: column;
            gap: 11px;
            font-size: 12.5px;
            line-height: 1.45;
            color: var(--text-main);
            border-top: 1px dashed var(--border);
        }

        .plan-features li {
            position: relative;
            padding-left: 20px;
            display: block;
            text-align: left;
            word-break: normal;
        }

        .plan-features li::before {
            content: "✓";
            position: absolute;
            left: 0;
            top: 0;
            color: #10b981;
            font-weight: 800;
            font-size: 13px;
            line-height: 1.45;
        }

        .plan-features li strong {
            color: var(--dark);
            font-weight: 700;
        }

        .pricing-card .btn {
            width: 100%;
            padding: 11px 16px;
            font-size: 13.5px;
            font-weight: 700;
            border-radius: 12px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        /* Modal Dialog */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(6px);
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-card {
            background: #ffffff;
            border-radius: 20px;
            max-width: 440px;
            width: 100%;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            animation: modalFadeIn 0.25s ease-out;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(12px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .modal-header {
            padding: 20px 24px;
            background: #f8fafc;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-title {
            font-size: 17px;
            font-weight: 800;
            color: var(--dark);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 20px;
            color: var(--text-muted);
            cursor: pointer;
            padding: 4px;
        }

        .modal-body {
            padding: 24px;
        }

        .modal-card-md {
            background: #ffffff;
            border-radius: 20px;
            max-width: 540px;
            width: 100%;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            animation: modalFadeIn 0.25s ease-out;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
        }

        .modal-card-lg {
            background: #ffffff;
            border-radius: 20px;
            max-width: 860px;
            width: 100%;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            animation: modalFadeIn 0.25s ease-out;
            max-height: 88vh;
            display: flex;
            flex-direction: column;
        }

        .modal-body-scroll {
            overflow-y: auto;
            padding: 28px 32px;
            flex: 1;
        }

        /* Legal Document Styling inside Modal */
        .legal-document { font-size: 14px; color: #334155; line-height: 1.7; }
        .legal-header { margin-bottom: 24px; border-bottom: 1px solid var(--border); padding-bottom: 18px; }
        .legal-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; background: rgba(67, 56, 202, 0.1); color: var(--primary); font-size: 11px; font-weight: 700; margin-bottom: 10px; text-transform: uppercase; }
        .legal-title { font-size: 24px; font-weight: 800; color: var(--dark); line-height: 1.3; letter-spacing: -0.5px; margin-bottom: 6px; }
        .legal-subtitle { font-size: 12px; color: var(--text-muted); }
        .legal-callout { background: #eff6ff; border: 1px solid #bfdbfe; border-left: 4px solid #3b82f6; border-radius: 12px; padding: 16px 20px; margin-bottom: 28px; font-size: 13px; color: #1e3a8a; line-height: 1.6; }
        .legal-section { margin-bottom: 28px; }
        .legal-section h2 { font-size: 17px; font-weight: 800; color: var(--dark); margin-bottom: 10px; letter-spacing: -0.2px; }
        .legal-section p { font-size: 13.5px; color: #334155; line-height: 1.65; margin-bottom: 10px; }
        .legal-section ul { padding-left: 20px; margin-bottom: 12px; }
        .legal-section li { font-size: 13.5px; color: #334155; line-height: 1.65; margin-bottom: 6px; }
        .legal-contact-card { background: #f8fafc; border: 1px solid var(--border); border-radius: 12px; padding: 16px 20px; font-size: 13px; color: #475569; line-height: 1.6; margin-top: 12px; }
        .legal-contact-card a { color: var(--primary); font-weight: 700; text-decoration: underline; }


        .tab-switch {
            display: flex;
            background: #f1f5f9;
            border-radius: 10px;
            padding: 4px;
            margin-bottom: 20px;
        }

        .tab-btn {
            flex: 1;
            padding: 8px;
            border: none;
            background: transparent;
            font-size: 13px;
            font-weight: 700;
            color: var(--text-muted);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .tab-btn.active {
            background: #ffffff;
            color: var(--primary);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
            transition: border 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.1);
        }

        .alert-box {
            padding: 12px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 16px;
            display: none;
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }

        .alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
        }

        /* Footer */
        .footer {
            background: var(--dark);
            color: #94a3b8;
            padding: 60px 0 30px;
            font-size: 13px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 40px;
            margin-bottom: 50px;
        }

        .footer-brand {
            color: #ffffff;
            font-size: 18px;
            font-weight: 800;
            margin-bottom: 12px;
        }

        .footer-links {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .footer-links a {
            color: #94a3b8;
            text-decoration: none;
            transition: color 0.2s;
        }

        .footer-links a:hover {
            color: #ffffff;
        }

        .footer-bottom {
            border-top: 1px solid #334155;
            padding-top: 24px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
        }

        .footer-counter-wrap {
            text-align: center;
            margin-bottom: 24px;
        }

        .visitor-counter-badge {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 30px;
            padding: 8px 22px;
            font-size: 13px;
            color: #cbd5e1;
            flex-wrap: wrap;
            justify-content: center;
        }

        .visitor-counter-badge strong {
            color: #38bdf8;
            font-family: 'JetBrains Mono', monospace;
            font-weight: 800;
        }

        .counter-sep {
            color: #64748b;
        }

        .live-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10b981;
            display: inline-block;
            box-shadow: 0 0 8px #10b981;
            animation: livePulse 2s infinite;
        }

        @keyframes livePulse {
            0% { transform: scale(0.95); opacity: 0.8; }
            50% { transform: scale(1.2); opacity: 1; }
            100% { transform: scale(0.95); opacity: 0.8; }
        }

        /* Hero Visual & Layout */
        .hero-grid {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 48px;
            align-items: center;
            margin-bottom: 50px;
        }

        .hero-visual-card {
            position: relative;
            border-radius: 22px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(67, 56, 202, 0.28);
            border: 1px solid rgba(99, 102, 241, 0.25);
            background: #0f172a;
            transition: all 0.4s ease;
        }

        .hero-visual-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 30px 60px -12px rgba(67, 56, 202, 0.38);
        }

        .hero-visual-card img {
            width: 100%;
            height: auto;
            display: block;
            object-fit: cover;
            aspect-ratio: 16 / 9;
        }

        .hero-visual-overlay {
            position: absolute;
            bottom: 14px;
            left: 14px;
            right: 14px;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            padding: 10px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: #ffffff;
            font-size: 12px;
        }

        .hero-visual-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 700;
            color: #38bdf8;
        }

        .hero-visual-status {
            font-size: 11px;
            color: #10b981;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .hero-visual-status::before {
            content: "";
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #10b981;
            display: inline-block;
            box-shadow: 0 0 8px #10b981;
        }

        /* Dual-Lens Architecture Spotlight */
        .spotlight-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 36px;
            box-shadow: 0 8px 30px -4px rgba(0, 0, 0, 0.05);
            margin: 40px 0 30px;
            display: grid;
            grid-template-columns: 1fr 1.05fr;
            gap: 36px;
            align-items: center;
        }

        .spotlight-img-wrap {
            border-radius: 18px;
            overflow: hidden;
            border: 1px solid var(--border);
            box-shadow: 0 12px 30px -6px rgba(0, 0, 0, 0.1);
            background: #0f172a;
            position: relative;
        }

        .spotlight-img-wrap img {
            width: 100%;
            height: auto;
            display: block;
            aspect-ratio: 16 / 9;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .spotlight-img-wrap:hover img {
            transform: scale(1.025);
        }

        .spotlight-caption {
            position: absolute;
            bottom: 12px;
            left: 12px;
            right: 12px;
            background: rgba(15, 23, 42, 0.82);
            backdrop-filter: blur(8px);
            border-radius: 10px;
            padding: 8px 12px;
            font-size: 11px;
            color: #e2e8f0;
            font-family: 'JetBrains Mono', monospace;
            display: flex;
            justify-content: space-between;
        }

        .lens-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin-top: 18px;
        }

        .lens-item {
            display: flex;
            gap: 14px;
            align-items: flex-start;
            padding: 12px 14px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid var(--border);
            transition: all 0.2s ease;
        }

        .lens-item:hover {
            background: #ffffff;
            border-color: rgba(99, 102, 241, 0.3);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
        }

        .lens-item-badge {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            flex-shrink: 0;
        }

        /* Dual-Reader Mockup Showcase */
        .dual-reader-showcase {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            border-radius: 28px;
            padding: 44px;
            color: #ffffff;
            margin-top: 50px;
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.12);
        }

        .dual-reader-header {
            text-align: center;
            max-width: 700px;
            margin: 0 auto 36px;
        }

        .dual-reader-tag {
            display: inline-block;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            font-weight: 800;
            color: #38bdf8;
            background: rgba(56, 189, 248, 0.15);
            padding: 4px 12px;
            border-radius: 20px;
            margin-bottom: 12px;
        }

        .dual-reader-title {
            font-size: 30px;
            font-weight: 800;
            letter-spacing: -0.6px;
            margin-bottom: 10px;
            color: #ffffff;
        }

        .dual-reader-desc {
            font-size: 15px;
            color: #94a3b8;
            line-height: 1.6;
        }

        .browser-mockup {
            background: #1e293b;
            border-radius: 18px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 25px 45px -10px rgba(0, 0, 0, 0.5);
        }

        .browser-bar {
            background: #0f172a;
            padding: 12px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .browser-dots {
            display: flex;
            gap: 7px;
        }

        .browser-dot {
            width: 11px;
            height: 11px;
            border-radius: 50%;
        }
        .dot-red { background: #ef4444; }
        .dot-yellow { background: #f59e0b; }
        .dot-green { background: #10b981; }

        .browser-url {
            background: rgba(255, 255, 255, 0.06);
            border-radius: 8px;
            padding: 5px 14px;
            font-size: 12px;
            font-family: 'JetBrains Mono', monospace;
            color: #cbd5e1;
            flex: 1;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .browser-mockup img {
            width: 100%;
            height: auto;
            display: block;
            aspect-ratio: 16 / 9;
            object-fit: cover;
        }

        .dual-reader-features {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 36px;
        }

        .feature-card-dark {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 22px;
            transition: all 0.2s ease;
        }

        .feature-card-dark:hover {
            background: rgba(255, 255, 255, 0.07);
            border-color: rgba(56, 189, 248, 0.3);
            transform: translateY(-2px);
        }

        .feature-dark-icon {
            font-size: 24px;
            margin-bottom: 12px;
        }

        .feature-dark-title {
            font-size: 16px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 6px;
        }

        .feature-dark-desc {
            font-size: 13px;
            color: #94a3b8;
            line-height: 1.6;
        }

        /* Dynamic Bidirectional AOS Transitions (Scroll-Down vs Scroll-Up) */
        body.scrolling-down [data-aos="fade-up"]:not(.aos-animate) {
            transform: translate3d(0, 50px, 0);
        }
        body.scrolling-down [data-aos="fade-down"]:not(.aos-animate) {
            transform: translate3d(0, -50px, 0);
        }

        body.scrolling-up [data-aos="fade-up"]:not(.aos-animate) {
            transform: translate3d(0, -50px, 0);
        }
        body.scrolling-up [data-aos="fade-down"]:not(.aos-animate) {
            transform: translate3d(0, 50px, 0);
        }

        body.scrolling-up [data-aos="fade-left"]:not(.aos-animate) {
            transform: translate3d(-50px, 0, 0);
        }
        body.scrolling-up [data-aos="fade-right"]:not(.aos-animate) {
            transform: translate3d(50px, 0, 0);
        }

        /* Instant reaction on reverse scroll (eliminate delay on upward reading) */
        body.scrolling-up [data-aos] {
            transition-delay: 0s !important;
        }

        /* Modern Additions: Badges & Section Components */
        .badge-rose { background: rgba(244, 63, 94, 0.12); color: #e11d48; }
        .badge-purple { background: rgba(168, 85, 247, 0.12); color: #9333ea; }

        /* Hero Pills Micro-badges */
        .hero-pills-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: -30px;
            margin-bottom: 42px;
        }

        .hero-pill-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            color: var(--dark);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            transition: all 0.2s ease;
        }

        .hero-pill-badge:hover {
            border-color: var(--primary-light);
            color: var(--primary);
            transform: translateY(-1px);
        }

        /* OJS Banner & Showcase */
        .ojs-banner-card {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 55%, #1e293b 100%);
            border: 1px solid rgba(244, 63, 94, 0.35);
            border-radius: 24px;
            padding: 42px;
            color: #ffffff;
            box-shadow: 0 20px 45px -15px rgba(244, 63, 94, 0.2);
            position: relative;
            overflow: hidden;
            margin-bottom: 40px;
        }

        .ojs-banner-card::before {
            content: '';
            position: absolute;
            top: -40%;
            right: -15%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(244, 63, 94, 0.16) 0%, transparent 70%);
            pointer-events: none;
        }

        .ojs-grid {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 36px;
            align-items: center;
            position: relative;
            z-index: 2;
        }

        .ojs-code-card {
            background: #020617;
            border: 1px solid rgba(51, 65, 85, 0.8);
            border-radius: 16px;
            padding: 22px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            color: #38bdf8;
            overflow-x: auto;
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.6);
        }

        /* Persona Solution Cards */
        .persona-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 32px;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .persona-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 14px 30px -4px rgba(67, 56, 202, 0.12);
            border-color: rgba(99, 102, 241, 0.45);
        }

        .persona-list {
            list-style: none;
            padding: 0;
            margin: 20px 0 28px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .persona-list li {
            font-size: 13px;
            color: var(--text-muted);
            display: flex;
            align-items: flex-start;
            gap: 10px;
            line-height: 1.5;
        }

        .persona-list li span.check-icon {
            color: #10b981;
            font-weight: 800;
            font-size: 14px;
            flex-shrink: 0;
            margin-top: 1px;
        }

        /* Responsive */
        @media (max-width: 900px) {
            .hero-grid { grid-template-columns: 1fr; gap: 32px; margin-bottom: 36px; }
            .hero-title { font-size: 34px; }
            .spotlight-card { grid-template-columns: 1fr; gap: 24px; padding: 24px; }
            .dual-reader-features { grid-template-columns: 1fr; gap: 14px; }
            .dual-reader-showcase { padding: 24px 18px; }
            .dual-reader-title { font-size: 24px; }
            .grid-4, .grid-3, .grid-2 { grid-template-columns: 1fr; }
            .stats-bar { grid-template-columns: repeat(2, 1fr); gap: 16px; }
            .incentive-grid { grid-template-columns: 1fr; }
            .footer-grid { grid-template-columns: 1fr; }
            .nav-links { display: none; }
            .ojs-grid { grid-template-columns: 1fr; }
            .ojs-banner-card { padding: 24px; }
        }
    </style>
</head>
<body>

    <!-- Header Navigation -->
    <header class="navbar">
        <div class="container nav-inner">
            <a href="<?= base_url() ?>" class="brand">
                <img src="<?= base_url('images/logo.png') ?>" alt="AR Tilik Logo" class="brand-logo">
                <div class="brand-text">
                    <span class="brand-title">AR Tilik</span>
                    <span class="brand-badge">v1.3.0</span>
                </div>
            </a>

            <ul class="nav-links">
                <li><a href="#teori">Filosofi & Teori</a></li>
                <li><a href="#fitur-unggulan">Inovasi Mesin</a></li>
                <li><a href="#cara-kerja">Cara Kerja</a></li>
                <li><a href="#integrasi-ojs">Integrasi OJS 3.x</a></li>
                <li><a href="#solusi">Solusi Institusi</a></li>
                <li><a href="#langganan">Paket Tarif</a></li>
                <li><a href="<?= base_url('verify') ?>">Verifikasi QR</a></li>
            </ul>

            <div class="nav-actions">
                <?php if ($currentUser) : ?>
                    <a href="<?= base_url('dashboard') ?>" class="btn btn-outline btn-sm">
                        <span>📊</span> Dashboard
                    </a>
                    <a href="<?= base_url('auth/logout') ?>" class="btn btn-secondary btn-sm">
                        Keluar
                    </a>
                <?php else : ?>
                    <button type="button" onclick="openAuthModal('login')" class="btn btn-outline btn-sm">
                        Masuk
                    </button>
                    <button type="button" onclick="openAuthModal('register')" class="btn btn-primary btn-sm">
                        Daftar Gratis
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-grid">
                <div>
                    <div class="hero-badge" data-aos="fade-down" data-aos-duration="600">
                        <span>🏛️</span> Standar Keadilan Akademik &bull; OJS 3.x Ready &bull; Lintas Bahasa EN⇄ID &bull; Anti-Cheat Shield
                    </div>
                    <h1 class="hero-title" data-aos="fade-up" data-aos-duration="700">
                        Deteksi Kesepadanan Naskah Akademik Berbasis <span>Bukti Ilmiah Terbuka</span>
                    </h1>
                    <p class="hero-subtitle" data-aos="fade-up" data-aos-delay="100" data-aos-duration="700">
                        AR Tilik hadir bukan untuk memvonis karya, melainkan menyediakan dekomposisi bukti kemiripan transparan, kesadaran konteks sitasi, audit kepatuhan daftar pustaka, integrasi otomatis OJS 3.x, serta sintesis risiko akademik terkalibrasi untuk sivitas perguruan tinggi.
                    </p>

                    <div class="hero-buttons" data-aos="fade-up" data-aos-delay="200" data-aos-duration="700">
                        <?php if ($currentUser) : ?>
                            <a href="<?= base_url('dashboard') ?>" class="btn btn-primary">
                                <span>🚀</span> Buka Dashboard Pemeriksaan
                            </a>
                        <?php else : ?>
                            <button type="button" onclick="openAuthModal('register')" class="btn btn-primary">
                                <span>✨</span> Mulai Gratis (3 Submisi/Minggu)
                            </button>
                        <?php endif; ?>
                        <a href="#integrasi-ojs" class="btn btn-outline" style="border-color: #f43f5e; color: #e11d48; background: #fff1f2;">
                            <span>📕</span> Panduan OJS 3.x (PDF)
                        </a>
                        <a href="#fitur-unggulan" class="btn btn-outline">
                            <span>⚡</span> Inovasi Mesin
                        </a>
                    </div>

                    <!-- Micro Feature Badges -->
                    <div class="hero-pills-wrap" data-aos="fade-up" data-aos-delay="250" data-aos-duration="700">
                        <div class="hero-pill-badge"><span>⚡</span> Dual-Lens 5-Gram & TF-IDF</div>
                        <div class="hero-pill-badge"><span>🌐</span> Cross-Lingual EN⇄ID</div>
                        <div class="hero-pill-badge"><span>🛡️</span> Anti-Cheat Font/Homoglyph</div>
                        <div class="hero-pill-badge"><span>📑</span> Auditor Sitasi APA/IEEE</div>
                        <div class="hero-pill-badge"><span>🦅</span> Pemanen Neliti & GARUDA Dikti</div>
                    </div>
                </div>

                <div data-aos="fade-left" data-aos-delay="200" data-aos-duration="900">
                    <div class="hero-visual-card">
                        <img src="<?= base_url('images/hero_illustration.jpg') ?>" alt="Analisis Naskah Akademik AR Tilik" width="800" height="450" fetchpriority="high">
                        <div class="hero-visual-overlay">
                            <span class="hero-visual-tag">
                                <span>🔬</span> Dual-Lens Spectrum Analysis
                            </span>
                            <span class="hero-visual-status">
                                Engine Active &bull; 96% Precision
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="stats-bar" data-aos="fade-up" data-aos-delay="300" data-aos-duration="800">
                <div class="stat-item">
                    <div class="stat-val"><?= number_format($totalCorpus) ?>+</div>
                    <div class="stat-lbl">Korpus Terindeks</div>
                </div>
                <div class="stat-item">
                    <div class="stat-val"><?= number_format($totalReports) ?></div>
                    <div class="stat-lbl">Laporan Terbit</div>
                </div>
                <div class="stat-item">
                    <div class="stat-val"><?= number_format($visitorStats['total_unique'] ?? 1) ?></div>
                    <div class="stat-lbl">Pengunjung Unik</div>
                </div>
                <div class="stat-item">
                    <div class="stat-val">100%</div>
                    <div class="stat-lbl">Reviewer Agreement</div>
                </div>
                <div class="stat-item">
                    <div class="stat-val">&lt; 150ms</div>
                    <div class="stat-lbl">SLA Parsing Sitasi</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 1: Teori & Filosofi Akademik -->
    <section id="teori" class="section">
        <div class="container">
            <div class="section-header" data-aos="fade-up" data-aos-duration="700">
                <div class="section-tag">Filosofi Ilmiah</div>
                <h2 class="section-title">Mengapa Similarity Bukan Berarti Plagiarisme?</h2>
                <p class="section-desc">
                    Banyak sistem komersial hanya menyodorkan satu angka persentase global yang sering disalahartikan sebagai vonis plagiarisme. AR Tilik dibangun dengan komitmen teguh terhadap asas keadilan akademik:
                </p>
            </div>

            <div class="grid-3" style="margin-bottom: 40px;">
                <div class="theory-card" data-aos="fade-up" data-aos-delay="100" data-aos-duration="700">
                    <div class="card-icon-badge badge-indigo">🔍</div>
                    <h3 class="theory-title">Dual-Lens Architecture</h3>
                    <p class="theory-desc">
                        Membedakan secara matematis antara <strong>Exact Matcher</strong> (5-gram shingle) untuk mendeteksi tumpang tindih kata berurutan, dan <strong>Sparse TF-IDF Cosine</strong> untuk menganalisis orientasi kosakata dan topik bersama.
                    </p>
                </div>

                <div class="theory-card" data-aos="fade-up" data-aos-delay="200" data-aos-duration="700">
                    <div class="card-icon-badge badge-emerald">📚</div>
                    <h3 class="theory-title">Citation Context Awareness</h3>
                    <p class="theory-desc">
                        Mengenali tanda petik langsung, blok kutipan, dan gaya sitasi standar (APA/IEEE/Harvard). Kutipan yang diatribusikan dengan benar tidak ditekan skor fisiknya, namun diberi label konteks pelindung agar tidak disalahpahami.
                    </p>
                </div>

                <div class="theory-card" data-aos="fade-up" data-aos-delay="300" data-aos-duration="700">
                    <div class="card-icon-badge badge-amber">🤖</div>
                    <h3 class="theory-title">XGBoost Risk Synthesizer</h3>
                    <p class="theory-desc">
                        Model Machine Learning Gradient Boosted yang dilatih dengan data telaah dewan etik institusi untuk mensintesis 11 dimensi bukti—termasuk estilometri panjang kalimat (burstiness) dan frasa baku metodologi.
                    </p>
                </div>
            </div>

            <!-- Spotlight Card: Visualisasi Dual-Lens Engine -->
            <div class="spotlight-card" data-aos="fade-up" data-aos-duration="800">
                <div class="spotlight-img-wrap" data-aos="fade-right" data-aos-delay="100" data-aos-duration="800">
                    <img src="<?= base_url('images/dual_lens_concept.jpg') ?>" alt="Konsep Arsitektur Dual-Lens AR Tilik" loading="lazy" width="800" height="450">
                    <div class="spotlight-caption">
                        <span>LENSA 1: Sequential 5-Gram</span>
                        <span>LENSA 2: Lexical TF-IDF</span>
                    </div>
                </div>

                <div data-aos="fade-left" data-aos-delay="150" data-aos-duration="800">
                    <div style="font-size: 11px; text-transform: uppercase; font-weight: 800; color: var(--primary); letter-spacing: 1px; margin-bottom: 6px;">Arsitektur Komparasi Presisi</div>
                    <h3 style="font-size: 24px; font-weight: 800; color: var(--dark); margin-bottom: 12px; letter-spacing: -0.5px;">Konvergensi Dua Lensa Bukti Ilmiah</h3>
                    <p style="font-size: 14px; color: var(--text-muted); line-height: 1.6; margin-bottom: 16px;">
                        Algoritma AR Tilik menolak pendekatan monolitik. Kami menggunakan dua lensa matematis independen yang dikombinasikan secara transparan untuk memisahkan karya orisinal dari modifikasi dangkal:
                    </p>

                    <div class="lens-list">
                        <div class="lens-item">
                            <div class="lens-item-badge badge-indigo">🎯</div>
                            <div>
                                <strong style="font-size: 13px; color: var(--dark);">Lensa Sequential (5-Gram Jaccard Shingle)</strong>
                                <p style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                                    Menangkap frasa identik berturutan, pasase salin-tempel langsung, dan kontinuitas struktur kalimat secara deterministik.
                                </p>
                            </div>
                        </div>

                        <div class="lens-item">
                            <div class="lens-item-badge badge-cyan">📊</div>
                            <div>
                                <strong style="font-size: 13px; color: var(--dark);">Lensa Leksikal (Sparse TF-IDF Cosine Vector)</strong>
                                <p style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                                    Mengukur kedekatan medan makna, distribusi leksikon tematik, dan konsistensi terminologi disiplin ilmu yang diteliti.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3-Layer Separation Explanation Box -->
            <div class="layer-box" data-aos="fade-up" data-aos-duration="800">
                <h3 style="font-size: 22px; font-weight: 800; margin-bottom: 8px;">Prinsip Pemisahan Tiga Lapis (Three-Layer Separation)</h3>
                <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 24px;">
                    Setiap hasil pemeriksaan naskah di AR Tilik didekomposisi ke dalam tiga domain yang terpisah tegas:
                </p>

                <div class="layer-item layer-a" data-aos="fade-up" data-aos-delay="100" data-aos-duration="600">
                    <div class="layer-num">LAPISAN A</div>
                    <div>
                        <strong style="color: #4338ca;">Machine Textual Evidence (Bukti Fisik Mesin)</strong>
                        <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">
                            Mengukur tumpang tindih huruf dan kata secara objektif tanpa manipulasi threshold (Exact similarity %, pasase terpanjang, dan kemiripan kosinus leksikal).
                        </p>
                    </div>
                </div>

                <div class="layer-item layer-b" data-aos="fade-up" data-aos-delay="200" data-aos-duration="600">
                    <div class="layer-num">LAPISAN B</div>
                    <div>
                        <strong style="color: #059669;">Academic Context (Konteks Sitasi & Kutipan)</strong>
                        <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">
                            Menjelaskan status kepatuhan ilmiah pasase: apakah berada di dalam tanda petik, disertai sitasi nama penulis dan tahun, atau bagian dari daftar pustaka.
                        </p>
                    </div>
                </div>

                <div class="layer-item layer-c" data-aos="fade-up" data-aos-delay="300" data-aos-duration="600">
                    <div class="layer-num">LAPISAN C</div>
                    <div>
                        <strong style="color: #d97706;">Human Academic Judgment (Keputusan Penelaah/Dosen)</strong>
                        <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">
                            Kewenangan akhir tetap berada di tangan dosen pembimbing dan dewan etik akademik untuk menilai orisinalitas ide dan substansi naskah.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 2: Inovasi Mesin Pendeteksi Generasi Baru -->
    <section id="fitur-unggulan" class="section" style="background: #f8fafc; border-top: 1px solid var(--border);">
        <div class="container">
            <div class="section-header" data-aos="fade-up" data-aos-duration="700">
                <div class="section-tag">Integritas Ilmiah Mutakhir</div>
                <h2 class="section-title">Lebih Cerdas dari Matcher Teks Biasa</h2>
                <p class="section-desc">
                    Plagiarisme modern telah berevolusi melampaui salin-tempel langsung. AR Tilik dipersenjatai 4 pilar komputasi cerdas untuk membongkar alih-bahasa tersembunyi, manipulasi font, sitasi fiktif, dan terhubung ke pangkalan data riset nasional:
                </p>
            </div>

            <div class="grid-4">
                <!-- Fitur 1: Cross-Lingual -->
                <div class="theory-card" data-aos="fade-up" data-aos-delay="100" data-aos-duration="700">
                    <div class="card-icon-badge badge-rose">🌐</div>
                    <div style="font-size: 11px; font-weight: 800; color: #e11d48; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Penyelarasan Bilingual</div>
                    <h3 class="theory-title">Deteksi Plagiarisme Lintas Bahasa (EN ⇄ ID)</h3>
                    <p class="theory-desc">
                        Membongkar modus plagiarisme terjemahan langsung dari literatur asing (bahasa Inggris) ke bahasa Indonesia tanpa atribusi. Model representasi multilingual kami mendeteksi kesepadanan semantik dan susunan alur argumen sekalipun naskah telah dialihbahasakan.
                    </p>
                </div>

                <!-- Fitur 2: Anti-Cheat Evasion -->
                <div class="theory-card" data-aos="fade-up" data-aos-delay="200" data-aos-duration="700">
                    <div class="card-icon-badge badge-amber">🛡️</div>
                    <div style="font-size: 11px; font-weight: 800; color: #d97706; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Perisai Manipulasi</div>
                    <h3 class="theory-title">Perisai Anti-Kecurangan Karakter & Font</h3>
                    <p class="theory-desc">
                        Melacak trik manipulasi naskah yang kerap lolos dari sistem komersial: alfabet Cyrillic/Greek homoglyph yang menyerupai Latin, spasi nol-lebar (<em>zero-width space</em>), pemenggalan kata mikro, dan teks putih transparan yang sengaja disisipkan untuk mengecoh persentase mesin.
                    </p>
                </div>

                <!-- Fitur 3: Citation Auditor -->
                <div class="theory-card" data-aos="fade-up" data-aos-delay="300" data-aos-duration="700">
                    <div class="card-icon-badge badge-emerald">📑</div>
                    <div style="font-size: 11px; font-weight: 800; color: #059669; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Audit Referensi</div>
                    <h3 class="theory-title">Auditor Konsistensi Sitasi & Daftar Pustaka</h3>
                    <p class="theory-desc">
                        Menganalisis koherensi sitasi (APA/IEEE/Harvard) secara otomatis. Mendeteksi <strong>Ghost Citations</strong> (nama penulis dikutip di badan teks tapi raib dari referensi) dan <strong>Orphan References</strong> (tercantum di bibliografi namun tidak pernah dirujuk dalam tubuh artikel).
                    </p>
                </div>

                <!-- Fitur 4: National Harvester -->
                <div class="theory-card" data-aos="fade-up" data-aos-delay="400" data-aos-duration="700">
                    <div class="card-icon-badge badge-cyan">🦅</div>
                    <div style="font-size: 11px; font-weight: 800; color: #0284c7; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Korpus Nasional</div>
                    <h3 class="theory-title">Pemanenan Neliti & GARUDA Dikti (OAI-PMH)</h3>
                    <p class="theory-desc">
                        Terhubung ke protokol OAI-PMH 2.0 untuk pemanenan berkala artikel jurnal nasional terakreditasi SINTA 1–6 dari pangkalan data GARUDA Kemdiktisaintek dan portal riset Neliti. Memastikan basis data pembanding terus bertumbuh tanpa bergantung pada unggahan manual.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 3: Cara Penggunaan -->
    <section id="cara-kerja" class="section" style="background: #ffffff; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border);">
        <div class="container">
            <div class="section-header" data-aos="fade-up" data-aos-duration="700">
                <div class="section-tag">Alur Kerja Praktis</div>
                <h2 class="section-title">Bagaimana Cara Menggunakan AR Tilik?</h2>
                <p class="section-desc">
                    Dirancang dengan antarmuka yang intuitif dan cepat, Anda dapat menyelesaikan pemeriksaan naskah karya ilmiah hanya dalam 4 langkah mudah:
                </p>
            </div>

            <div class="grid-2">
                <div class="step-card" data-aos="fade-up" data-aos-delay="100" data-aos-duration="700">
                    <div class="step-index">01</div>
                    <div class="card-icon-badge badge-indigo">📝</div>
                    <h3 class="step-title">Daftarkan Akun Valid</h3>
                    <p class="step-desc">
                        Buat akun gratis menggunakan alamat email Anda. Setiap akun terdaftar langsung memperoleh hak periksa <strong>3 submisi gratis tiap minggu</strong>.
                    </p>
                </div>

                <div class="step-card" data-aos="fade-up" data-aos-delay="200" data-aos-duration="700">
                    <div class="step-index">02</div>
                    <div class="card-icon-badge badge-cyan">📤</div>
                    <h3 class="step-title">Unggah Dokumen Karya Ilmiah</h3>
                    <p class="step-desc">
                        Kirimkan naskah dalam format PDF, DOCX, atau TXT (maks. 25 MB). Sistem secara otomatis mengekstrak teks, menormalisasi tanda baca, dan menghitung shingle sekuensial.
                    </p>
                </div>

                <div class="step-card" data-aos="fade-up" data-aos-delay="300" data-aos-duration="700">
                    <div class="step-index">03</div>
                    <div class="card-icon-badge badge-emerald">⚡</div>
                    <h3 class="step-title">Pemrosesan Asinkron Kilat</h3>
                    <p class="step-desc">
                        Worker background membandingkan naskah terhadap ratusan korpus institusi dan jurnal terbuka. Dalam rentang waktu kurang dari 1 detik, laporan lengkap terbit.
                    </p>
                </div>

                <div class="step-card" data-aos="fade-up" data-aos-delay="400" data-aos-duration="700">
                    <div class="step-index">04</div>
                    <div class="card-icon-badge badge-amber">🛡️</div>
                    <h3 class="step-title">Telaah Interaktif & Sertifikat QR</h3>
                    <p class="step-desc">
                        Buka perbandingan di <strong>Layar Ganda (Dual-Reader)</strong> dengan penanda warna, gunakan filter dinamis pengabaian kutipan, dan unduh sertifikat resmi ber-QR Code.
                    </p>
                </div>
            </div>

            <!-- Showcase: Dual-Reader Interface Mockup -->
            <div class="dual-reader-showcase" data-aos="zoom-in" data-aos-duration="850">
                <div class="dual-reader-header">
                    <span class="dual-reader-tag">Antarmuka Unggulan Penelaah</span>
                    <h3 class="dual-reader-title">Dual-Reader: Komparasi Berdampingan Interaktif</h3>
                    <p class="dual-reader-desc">
                        Tidak perlu lagi mencetak lembaran kertas atau berpindah tab secara melelahkan. Periksa kalimat sumber dan naskah mahasiswa secara berdampingan lengkap dengan tanda kutip, sitasi, dan verifikasi sertifikat digital.
                    </p>
                </div>

                <div class="browser-mockup">
                    <div class="browser-bar">
                        <div class="browser-dots">
                            <span class="browser-dot dot-red"></span>
                            <span class="browser-dot dot-yellow"></span>
                            <span class="browser-dot dot-green"></span>
                        </div>
                        <div class="browser-url">
                            <span>🔒</span> https://tilik.arsetontong.top/report/compare/SUB-704B1494
                        </div>
                    </div>
                    <img src="<?= base_url('images/dual_reader_mockup.jpg') ?>" alt="Antarmuka Dual-Reader AR Tilik" loading="lazy" width="800" height="450">
                </div>

                <div class="dual-reader-features">
                    <div class="feature-card-dark" data-aos="fade-up" data-aos-delay="100" data-aos-duration="600">
                        <div class="feature-dark-icon">↔️</div>
                        <h4 class="feature-dark-title">Sinkronisasi Gulir Otomatis</h4>
                        <p class="feature-dark-desc">
                            Mengklik pasase mirip di naskah mahasiswa seketika mengarahkan dan menyoroti pasase rujukan yang bersesuaian di korpus pembanding.
                        </p>
                    </div>

                    <div class="feature-card-dark" data-aos="fade-up" data-aos-delay="200" data-aos-duration="600">
                        <div class="feature-dark-icon">🔍</div>
                        <h4 class="feature-dark-title">Filter Sitasi & Pustaka Dinamis</h4>
                        <p class="feature-dark-desc">
                            Sakelar interaktif untuk mengecualikan kalimat dalam tanda petik, sitasi sah, maupun daftar referensi langsung menghitung ulang persentase di layar.
                        </p>
                    </div>

                    <div class="feature-card-dark" data-aos="fade-up" data-aos-delay="300" data-aos-duration="600">
                        <div class="feature-dark-icon">🛡️</div>
                        <h4 class="feature-dark-title">Verifikasi QR Code Kriptografis</h4>
                        <p class="feature-dark-desc">
                            Setiap naskah memiliki hash SHA-256 unik dan QR code yang dapat dipindai oleh dewan sidang skripsi untuk memeriksa keabsahan laporan.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 4: Ekosistem Integrasi OJS 3.x & REST API -->
    <section id="integrasi-ojs" class="section" style="background: #ffffff; border-bottom: 1px solid var(--border);">
        <div class="container">
            <div class="section-header" data-aos="fade-up" data-aos-duration="700">
                <div class="section-tag" style="color: #e11d48;">Ekosistem Jurnal Ilmiah SINTA</div>
                <h2 class="section-title">Integrasi Otomatis Open Journal Systems (OJS 3.x)</h2>
                <p class="section-desc">
                    Tingkatkan efisiensi dan reputasi jurnal ilmiah kampus Anda. Hubungkan OJS 3.x ke AR Tilik untuk verifikasi naskah masuk secara otomatis dan penerbitan Sertifikat Keabsahan digital ber-QR Code bagi Dewan Editor.
                </p>
            </div>

            <!-- OJS Banner Card -->
            <div class="ojs-banner-card" data-aos="zoom-in" data-aos-duration="800">
                <div class="ojs-grid">
                    <div>
                        <div style="display: inline-flex; align-items: center; gap: 8px; padding: 4px 12px; background: rgba(244, 63, 94, 0.2); border: 1px solid rgba(244, 63, 94, 0.4); border-radius: 20px; font-size: 11px; font-weight: 700; color: #fecdd3; text-transform: uppercase; margin-bottom: 16px;">
                            <span>📕</span> Buku Panduan Resmi &amp; Konektor PHP Siap Pakai
                        </div>
                        <h3 style="font-size: 26px; font-weight: 800; color: #ffffff; line-height: 1.3; margin-bottom: 14px;">
                            Buku Panduan Integrasi OJS 3.x (Format PDF Resolusi Tinggi - 8 Halaman)
                        </h3>
                        <p style="font-size: 14px; color: #cbd5e1; line-height: 1.6; margin-bottom: 24px;">
                            Disusun khusus untuk pengelola jurnal, IT kampus, dan Dewan Redaksi. Dilengkapi diagram alur kerja <em>end-to-end</em> (Penulis &rarr; OJS 3 &rarr; REST API &rarr; Dual-Lens &rarr; Sertifikat Digital), konsep komputasi Dual-Lens, panduan instalasi langkah demi langkah, dan contoh kode PHP konektor.
                        </p>

                        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                            <a href="<?= base_url('integrations/ojs/Panduan_Integrasi_OJS_ARTilik.pdf') ?>" target="_blank" download="Panduan_Integrasi_OJS_ARTilik.pdf" class="btn" style="background: linear-gradient(135deg, #e11d48 0%, #be123c 100%); color: #ffffff; box-shadow: 0 4px 14px rgba(225, 29, 72, 0.4); border: 1px solid rgba(255,255,255,0.2);">
                                <span>📥</span> Unduh Panduan PDF (1.9 MB)
                            </a>
                            <a href="<?= base_url('integrations/ojs/ARTilikOJSConnector.php') ?>" download class="btn" style="background: rgba(255, 255, 255, 0.1); color: #ffffff; border: 1px solid rgba(255,255,255,0.25);">
                                <span>💻</span> Unduh Konektor PHP
                            </a>
                            <a href="<?= base_url('integrations/ojs/README.md') ?>" target="_blank" class="btn" style="background: transparent; color: #94a3b8; border: 1px solid #334155;">
                                <span>📖</span> Dokumentasi Online
                            </a>
                        </div>
                    </div>

                    <!-- Code Box Preview -->
                    <div>
                        <div class="ojs-code-card">
                            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #1e293b; padding-bottom: 8px; margin-bottom: 12px; color: #94a3b8; font-size: 11px;">
                                <span>PHP &bull; artilik_ojs_runner.php</span>
                                <span style="color: #10b981;">● OJS 3.x Ready</span>
                            </div>
                            <pre style="margin: 0; line-height: 1.5;"><span style="color: #f43f5e;">require_once</span> <span style="color: #fde047;">'ARTilikOJSConnector.php'</span>;

<span style="color: #64748b;">// 1. Inisialisasi dengan API Key Jurnal</span>
<span style="color: #38bdf8;">$tilik</span> = <span style="color: #f43f5e;">new</span> <span style="color: #a78bfa;">ARTilikOJSConnector</span>(
    <span style="color: #fde047;">'artilik_ojs_YOUR_API_KEY'</span>
);

<span style="color: #64748b;">// 2. Kirim naskah submisi penulis</span>
<span style="color: #38bdf8;">$res</span> = <span style="color: #38bdf8;">$tilik</span>-&gt;<span style="color: #60a5fa;">submitManuscript</span>(
    <span style="color: #fde047;">'/path/to/manuscript.pdf'</span>,
    <span style="color: #fde047;">'Judul Naskah Ilmiah'</span>,
    <span style="color: #fde047;">'Dr. Penulis Utama'</span>,
    <span style="color: #fde047;">'submission_4892'</span>
);

<span style="color: #64748b;">// 3. Ambil sertifikat digital ber-QR</span>
<span style="color: #f43f5e;">echo</span> <span style="color: #38bdf8;">$res</span>[<span style="color: #fde047;">'certificate_url'</span>];</pre>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3 Alur Kerja Integrasi Jurnal -->
            <div class="grid-3" style="margin-top: 40px;">
                <div class="step-card" data-aos="fade-up" data-aos-delay="100" data-aos-duration="700">
                    <div class="step-index">A</div>
                    <div class="card-icon-badge badge-rose">🔌</div>
                    <h3 class="step-title">1 Berkas Tanpa Konfigurasi Rumit</h3>
                    <p class="step-desc">
                        Cukup letakkan berkas pustaka <code>ARTilikOJSConnector.php</code> pada folder instalasi OJS Anda. Tidak memerlukan instalasi ekstensi kernel peladen atau dependensi eksternal yang rumit.
                    </p>
                </div>

                <div class="step-card" data-aos="fade-up" data-aos-delay="200" data-aos-duration="700">
                    <div class="step-index">B</div>
                    <div class="card-icon-badge badge-indigo">⚡</div>
                    <h3 class="step-title">Pemeriksaan Submisi Otomatis</h3>
                    <p class="step-desc">
                        Dipicu saat penulis mengunggah naskah (<em>Author Submission</em>) atau saat naskah dialihkan ke tahap penelaahan mitra bestari (<em>Peer Review Stage</em>) secara otomatis di latar belakang.
                    </p>
                </div>

                <div class="step-card" data-aos="fade-up" data-aos-delay="300" data-aos-duration="700">
                    <div class="step-index">C</div>
                    <div class="card-icon-badge badge-emerald">📜</div>
                    <h3 class="step-title">Sertifikat Digital Ber-QR Code</h3>
                    <p class="step-desc">
                        Menerbitkan Sertifikat Keabsahan Orisinalitas ber-QR Code kriptografis (SHA-256) yang dapat dilampirkan langsung pada surat keputusan dewan redaksi (<em>Editorial Decision</em>).
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 5: Solusi Tepat Guna Berdasarkan Kebutuhan (Persona) -->
    <section id="solusi" class="section" style="background: #f8fafc; border-bottom: 1px solid var(--border);">
        <div class="container">
            <div class="section-header" data-aos="fade-up" data-aos-duration="700">
                <div class="section-tag">Segmentasi Pemangku Kepentingan</div>
                <h2 class="section-title">Solusi Tepat Guna untuk Seluruh Sivitas Akademika</h2>
                <p class="section-desc">
                    AR Tilik dirancang untuk memberikan nilai nyata bagi mahasiswa, pengelola jurnal ilmiah, hingga pimpinan universitas:
                </p>
            </div>

            <div class="grid-3">
                <!-- Card 1: Mahasiswa & Peneliti -->
                <div class="persona-card" data-aos="fade-up" data-aos-delay="100" data-aos-duration="700">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                            <div class="card-icon-badge badge-indigo" style="margin-bottom: 0;">🎓</div>
                            <span style="font-size: 11px; font-weight: 800; background: rgba(99, 102, 241, 0.1); color: var(--primary); padding: 4px 10px; border-radius: 20px;">Individu</span>
                        </div>
                        <h3 class="theory-title">Mahasiswa &amp; Peneliti Mandiri</h3>
                        <p class="theory-desc">
                            Cek mandiri sebelum maju sidang skripsi/tesis tanpa rasa was-was naskah tersimpan ke repositori publik.
                        </p>

                        <ul class="persona-list">
                            <li><span class="check-icon">✓</span> <span><strong>Bebas Risiko Self-Plagiarism:</strong> Naskah pra-sidang Anda tidak disimpan ke korpus publik.</span></li>
                            <li><span class="check-icon">✓</span> <span><strong>Audit Sitasi Otomatis:</strong> Pastikan tidak ada kutipan fiktif sebelum naskah dicetak.</span></li>
                            <li><span class="check-icon">✓</span> <span><strong>Lensa Ganda (Dual-Reader):</strong> Telusuri kalimat yang perlu diparafrasa berdampingan.</span></li>
                            <li><span class="check-icon">✓</span> <span><strong>Tarif Ringan Mahasiswa:</strong> Gratis 3x/minggu atau top-up fleksibel Rp 3.500/dokumen.</span></li>
                        </ul>
                    </div>

                    <button type="button" onclick="openAuthModal('register')" class="btn btn-outline" style="width: 100%; justify-content: center; font-weight: 700;">
                        Daftar Akun Mahasiswa
                    </button>
                </div>

                <!-- Card 2: Pengelola Jurnal OJS -->
                <div class="persona-card" style="border-color: rgba(244, 63, 94, 0.4); box-shadow: 0 10px 30px -10px rgba(244, 63, 94, 0.15);" data-aos="fade-up" data-aos-delay="200" data-aos-duration="700">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                            <div class="card-icon-badge badge-rose" style="margin-bottom: 0;">🏛️</div>
                            <span style="font-size: 11px; font-weight: 800; background: rgba(244, 63, 94, 0.1); color: #e11d48; padding: 4px 10px; border-radius: 20px;">Jurnal Ilmiah</span>
                        </div>
                        <h3 class="theory-title">Pengelola Jurnal (OJS 3.x)</h3>
                        <p class="theory-desc">
                            Otomatisasi telaah kemiripan artikel submisi untuk menjaga reputasi dan kepatuhan akreditasi ARJUNA &amp; SINTA.
                        </p>

                        <ul class="persona-list">
                            <li><span class="check-icon" style="color: #e11d48;">✓</span> <span><strong>Konektor OJS 3.x Plug &amp; Play:</strong> Cukup pasang 1 berkas pustaka PHP.</span></li>
                            <li><span class="check-icon" style="color: #e11d48;">✓</span> <span><strong>Deteksi Lintas Bahasa:</strong> Membongkar artikel terjemahan asing tanpa sitasi.</span></li>
                            <li><span class="check-icon" style="color: #e11d48;">✓</span> <span><strong>Sertifikat Digital Ber-QR Code:</strong> Bukti verifikasi resmi untuk dewan editor.</span></li>
                            <li><span class="check-icon" style="color: #e11d48;">✓</span> <span><strong>Manajemen Kuota API Jurnal:</strong> Kontrol batas pemakaian per bulan secara presisi.</span></li>
                        </ul>
                    </div>

                    <a href="#integrasi-ojs" class="btn btn-primary" style="width: 100%; justify-content: center; font-weight: 700; background: linear-gradient(135deg, #e11d48 0%, #be123c 100%);">
                        Pelajari Integrasi OJS
                    </a>
                </div>

                <!-- Card 3: Pimpinan Perguruan Tinggi -->
                <div class="persona-card" data-aos="fade-up" data-aos-delay="300" data-aos-duration="700">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                            <div class="card-icon-badge badge-emerald" style="margin-bottom: 0;">🏢</div>
                            <span style="font-size: 11px; font-weight: 800; background: rgba(16, 185, 129, 0.1); color: #059669; padding: 4px 10px; border-radius: 20px;">Institusi Kampus</span>
                        </div>
                        <h3 class="theory-title">Perguruan Tinggi &amp; Perpustakaan</h3>
                        <p class="theory-desc">
                            Kedaulatan data naskah akademik sivitas kampus dengan opsi server mandiri (<em>On-Premise</em>) dan dasbor analitik.
                        </p>

                        <ul class="persona-list">
                            <li><span class="check-icon">✓</span> <span><strong>Kedaulatan Data Penuh:</strong> Naskah sivitas tidak dijual/diserahkan ke pihak luar.</span></li>
                            <li><span class="check-icon">✓</span> <span><strong>Opsi On-Premise / Private Cloud:</strong> Dipasang di pusat data kampus sendiri.</span></li>
                            <li><span class="check-icon">✓</span> <span><strong>Sinkronisasi OAI-PMH:</strong> Hubungkan repositori EPrints / DSpace institusi.</span></li>
                            <li><span class="check-icon">✓</span> <span><strong>Dasbor Integritas Kampus:</strong> Analitik tren kemiripan dan risiko antar fakultas.</span></li>
                        </ul>
                    </div>

                    <a href="#pricing" class="btn btn-outline" style="width: 100%; justify-content: center; font-weight: 700;">
                        Konsultasi Paket Kampus
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 6: Gotong Royong Korpus (Crowdsourcing Incentive) -->
    <section id="kuota-korpus" class="section">
        <div class="container">
            <div class="incentive-banner" data-aos="fade-up" data-aos-duration="800">
                <div class="incentive-grid">
                    <div>
                        <span style="background: rgba(56, 189, 248, 0.2); color: #38bdf8; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase;">Inisiatif Gotong Royong Akademik</span>
                        <h2 class="incentive-title" style="margin-top: 14px;">Perlu Lebih dari 3 Submisi Tiap Minggu?</h2>
                        <p class="incentive-desc">
                            AR Tilik percaya bahwa repositori institusi yang kuat dibangun bersama. Jika kuota 3 submisi gratis mingguan Anda telah terpakai, Anda dapat menambah kuota secara cuma-cuma dengan <strong>menyumbangkan 3 berkas korpus karya ilmiah valid</strong> (jurnal/skripsi referensi non-duplikat).
                        </p>
                        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                            <button type="button" onclick="openAuthModal('register')" class="btn" style="background: #ffffff; color: var(--primary);">
                                Daftar & Mulai Kontribusi
                            </button>
                            <a href="<?= base_url('verify') ?>" class="btn" style="background: rgba(255, 255, 255, 0.15); color: #ffffff; border: 1px solid rgba(255,255,255,0.3);">
                                Portal Verifikasi Publik
                            </a>
                        </div>
                    </div>

                    <div class="formula-box">
                        <div style="font-size: 13px; color: #94a3b8; margin-bottom: 6px;">FORMULA INSENTIF KORPUS:</div>
                        <div class="formula-text">3 Korpus Valid = +1 Submisi</div>
                        <p style="font-size: 13px; color: #cbd5e1; line-height: 1.5; margin-top: 10px;">
                            Setiap naskah yang Anda sumbangkan melewati verifikasi anti-duplikasi otomatis dan langsung memperkaya khazanah pembanding perguruan tinggi kita.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 4: Paket Berlangganan & Top-Up Kuota (Midtrans Aligned) -->
    <section id="langganan" class="section" style="background: #ffffff; border-top: 1px solid var(--border); position: relative;">
        <!-- Anchor link alias for #pricing -->
        <span id="pricing" style="position: absolute; top: -70px; left: 0;"></span>
        <div class="container">
            <div class="section-header" data-aos="fade-up" data-aos-duration="700">
                <div class="section-tag">Tarif Transparan & Pilihan Akses</div>
                <h2 class="section-title">Pilihan Paket & Top-Up Kuota AR Tilik</h2>
                <p class="section-desc">
                    Mulai dari penggunaan mandiri gratis, top-up fleksibel tanpa batas waktu, paket bulanan pejuang skripsi, hingga integrasi institusi kampus:
                </p>
            </div>

            <div class="grid-4">
                <!-- Plan 1: Free Academic -->
                <div class="pricing-card" data-aos="fade-up" data-aos-delay="100" data-aos-duration="700">
                    <div>
                        <span class="plan-pill plan-pill-free">Tier Gratis</span>
                        <div class="plan-name">Civitas Akademika</div>
                        <div class="plan-price">
                            <span class="amount">Gratis</span>
                            <span class="plan-period">/ selamanya</span>
                        </div>
                        <p class="plan-desc">
                            Cocok untuk mahasiswa, dosen, dan peneliti mandiri yang memeriksa tugas atau draf artikel secara berkala.
                        </p>

                        <ul class="plan-features">
                            <li><strong>3 submisi gratis</strong> per minggu (reset tiap Senin)</li>
                            <li><strong>Bonus gotong royong</strong> (+1 cek tiap 3 korpus valid)</li>
                            <li>Akses Dual-Reader (layar komparasi berdampingan)</li>
                            <li>Filter cerdas sitasi pustaka (eksklusi referensi)</li>
                            <li>Sertifikat resmi keaslian naskah ber-QR Code</li>
                            <li>Deteksi sitasi format APA, IEEE, dan Harvard</li>
                        </ul>
                    </div>

                    <?php if ($currentUser) : ?>
                        <a href="<?= base_url('dashboard') ?>" class="btn btn-outline">
                            Gunakan Kuota Saya
                        </a>
                    <?php else : ?>
                        <button type="button" onclick="openAuthModal('register')" class="btn btn-outline">
                            Daftar Akun Gratis
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Plan 2: Top-Up Fleksibel (Pay-As-You-Go) -->
                <div class="pricing-card" data-aos="fade-up" data-aos-delay="150" data-aos-duration="700">
                    <div>
                        <span class="plan-pill plan-pill-payg">Top-Up Fleksibel</span>
                        <div class="plan-name">Top-Up Kuota</div>
                        <div class="plan-price">
                            <span class="currency">Rp</span><span class="amount">10.000</span>
                            <span class="plan-period">/ 3 cek</span>
                        </div>
                        <p class="plan-desc">
                            Beli kuota saat dibutuhkan tanpa komitmen bulanan. Tersedia opsi hemat <strong>Rp 25.000 / 10 cek</strong>.
                        </p>

                        <ul class="plan-features">
                            <li><strong>Aktif selamanya</strong> (kuota tanpa masa kedaluwarsa)</li>
                            <li>Pilihan paket isi ulang 3 cek atau 10 cek</li>
                            <li>Prioritas pemrosesan antrean dokumen cepat</li>
                            <li>Semua fitur deteksi kecerdasan buatan aktif</li>
                            <li>Ekspor PDF laporan resmi lengkap ber-QR</li>
                            <li>Aktivasi instan via QRIS & Virtual Account</li>
                        </ul>
                    </div>

                    <?php if ($currentUser) : ?>
                        <a href="<?= base_url('dashboard') ?>" class="btn btn-primary">
                            ⚡ Top-Up di Dashboard
                        </a>
                    <?php else : ?>
                        <button type="button" onclick="openAuthModal('login')" class="btn btn-primary">
                            Masuk & Beli Kuota
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Plan 3: Paket Pejuang Skripsi (Featured) -->
                <div class="pricing-card popular" data-aos="fade-up" data-aos-delay="200" data-aos-duration="700">
                    <div class="pricing-badge popular">⭐ Paling Diminati</div>
                    <div>
                        <span class="plan-pill plan-pill-popular">Paket Skripsi</span>
                        <div class="plan-name">Pejuang Skripsi</div>
                        <div class="plan-price">
                            <span class="currency">Rp</span><span class="amount">25.000</span>
                            <span class="plan-period">/ bulan</span>
                        </div>
                        <p class="plan-desc">
                            Hanya <strong>Rp 1.250 / dokumen</strong>. Solusi hemat untuk persiapan sidang & revisi bab skripsi/tesis.
                        </p>

                        <ul class="plan-features">
                            <li><strong>20 submisi dokumen</strong> selama masa aktif</li>
                            <li>Prioritas antrean pemrosesan cepat</li>
                            <li>Ekspor PDF laporan resmi lengkap tanpa batas</li>
                            <li>Verifikasi keaslian naskah ber-QR Code</li>
                            <li>Masa aktif paket 30 hari penuh</li>
                            <li>Aktivasi instan via QRIS & Virtual Account</li>
                        </ul>
                    </div>

                    <?php if ($currentUser) : ?>
                        <a href="<?= base_url('dashboard') ?>" class="btn" style="background: #f59e0b; border: 1px solid #d97706; color: #ffffff; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);">
                            Langganan di Dashboard →
                        </a>
                    <?php else : ?>
                        <button type="button" onclick="openAuthModal('register')" class="btn" style="background: #f59e0b; border: 1px solid #d97706; color: #ffffff; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);">
                            Daftar & Langganan →
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Plan 4: Dosen & Peneliti Pro -->
                <div class="pricing-card" data-aos="fade-up" data-aos-delay="250" data-aos-duration="700">
                    <div>
                        <span class="plan-pill plan-pill-pro">Kapasitas Besar</span>
                        <div class="plan-name">Dosen & Peneliti Pro</div>
                        <div class="plan-price">
                            <span class="currency">Rp</span><span class="amount">75.000</span>
                            <span class="plan-period">/ bulan</span>
                        </div>
                        <p class="plan-desc">
                            Hanya <strong>Rp 1.000 / dokumen</strong>. Dirancang untuk dosen pembimbing, reviewer, dan peneliti aktif.
                        </p>

                        <ul class="plan-features">
                            <li><strong>75 submisi dokumen</strong> selama masa aktif</li>
                            <li>Prioritas antrean tertinggi sistem</li>
                            <li>Dukungan berkas besar hingga <strong>30 MB</strong></li>
                            <li>Pemeriksaan draf jurnal & tugas kelas</li>
                            <li>Masa aktif paket 30 hari penuh</li>
                            <li>Aktivasi instan via QRIS & Virtual Account</li>
                        </ul>
                    </div>

                    <?php if ($currentUser) : ?>
                        <a href="<?= base_url('dashboard') ?>" class="btn btn-outline">
                            Pilih Paket Dosen
                        </a>
                    <?php else : ?>
                        <button type="button" onclick="openAuthModal('register')" class="btn btn-outline">
                            Daftar & Langganan
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Institutional & Enterprise Banner -->
            <div data-aos="fade-up" data-aos-delay="300" data-aos-duration="700" style="margin-top: 36px; background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%); border-radius: 20px; padding: 32px 36px; color: #ffffff; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 24px; border: 1px solid rgba(99, 102, 241, 0.25); box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.3);">
                <div style="max-width: 780px;">
                    <div style="display: inline-flex; align-items: center; gap: 8px; padding: 4px 12px; border-radius: 20px; background: rgba(99, 102, 241, 0.2); border: 1px solid rgba(99, 102, 241, 0.4); font-size: 11px; font-weight: 800; color: #a5b4fc; text-transform: uppercase; margin-bottom: 12px;">
                        🏛️ Solusi Kampus & Lembaga Riset
                    </div>
                    <h3 style="font-size: 22px; font-weight: 800; margin-bottom: 8px; color: #ffffff;">Paket Kampus, OJS Jurnal & LPPM (Enterprise)</h3>
                    <p style="font-size: 14px; color: #cbd5e1; line-height: 1.6; margin: 0;">
                        Butuh integrasi dengan repositori skripsi internal prodi, plugin OJS 3 untuk penerbitan jurnal, atau plugin Moodle LMS kampus? Kami menyediakan instalasi mandiri (on-premise / private cloud), korpus tertutup institusi, serta sinkronisasi OAI-PMH otomatis dengan SLA dukungan teknis dedicated.
                    </p>
                </div>
                <div style="flex-shrink: 0; width: 100%; max-width: 260px; text-align: center;">
                    <button type="button" onclick="openContactModal('Konsultasi Institusi & Lisensi Kampus')" class="btn" style="background: #ffffff; color: #0f172a; font-weight: 800; width: 100%; border: none; padding: 14px 20px; display: inline-block; text-decoration: none; border-radius: 14px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); cursor: pointer;">
                        Konsultasi Institusi →
                    </button>
                    <div style="font-size: 11px; color: #94a3b8; margin-top: 8px;">Mulai Rp 250.000 / bln untuk pengelolaan jurnal</div>
                </div>
            </div>

            <!-- Payment Methods Supported Bar -->
            <div style="margin-top: 24px; padding: 18px 24px; background: #f8fafc; border: 1px solid var(--border); border-radius: 16px; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 14px; font-size: 13px; color: var(--text-muted);" data-aos="fade-up" data-aos-delay="350">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 18px;">💳</span>
                    <span><strong>Metode Pembayaran Resmi (Midtrans Gateway):</strong> QRIS (GoPay, OVO, Dana, ShopeePay, LinkAja) & Virtual Account (BCA, BRI, BNI, Mandiri)</span>
                </div>
                <div style="font-size: 12px; color: #64748b;">
                    🔒 Terenkripsi SSL 256-bit • Kuota langsung bertambah otomatis dalam hitungan detik
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" data-aos="fade-in" data-aos-duration="800">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 14px;">
                        <img src="<?= base_url('images/logo.png') ?>" alt="AR Tilik Logo" style="width: 34px; height: 34px; object-fit: contain; border-radius: 8px;">
                        <span style="font-size: 20px; font-weight: 800; color: #ffffff; letter-spacing: -0.5px;">AR Tilik</span>
                    </div>
                    <p style="line-height: 1.6; max-width: 380px;">
                        Sistem Pemeriksa Kesepadanan Naskah Akademik Berbasis Bukti Transparan. Dikembangkan dengan dedikasi untuk keadilan dan integritas ilmiah perguruan tinggi di Indonesia.
                    </p>
                </div>

                <div>
                    <strong style="color: #ffffff; display: block; margin-bottom: 14px;">Fitur &amp; Inovasi</strong>
                    <ul class="footer-links">
                        <li><a href="#teori">Dual-Lens Engine (5-Gram &amp; Cosine)</a></li>
                        <li><a href="#fitur-unggulan">Deteksi Lintas Bahasa (EN⇄ID)</a></li>
                        <li><a href="#fitur-unggulan">Perisai Anti-Manipulasi Karakter</a></li>
                        <li><a href="#fitur-unggulan">Auditor Sitasi &amp; Daftar Pustaka</a></li>
                        <li><a href="#fitur-unggulan">Pemanen Neliti &amp; GARUDA Dikti</a></li>
                        <li><a href="#cara-kerja">Layar Ganda (Dual-Reader)</a></li>
                    </ul>
                </div>

                <div>
                    <strong style="color: #ffffff; display: block; margin-bottom: 14px;">Layanan &amp; Solusi</strong>
                    <ul class="footer-links">
                        <li><a href="#integrasi-ojs">Integrasi OJS 3.x Jurnal</a></li>
                        <li><a href="#solusi">Solusi Sivitas Akademika</a></li>
                        <li><a href="<?= base_url('verify') ?>">Portal Verifikasi QR</a></li>
                        <li><a href="#kuota-korpus">Gotong Royong Korpus</a></li>
                        <li><a href="#langganan">Paket Tarif &amp; Top-Up</a></li>
                        <?php if ($currentUser) : ?>
                            <li><a href="<?= base_url('dashboard') ?>">Dashboard Pemeriksaan</a></li>
                        <?php else : ?>
                            <li><a href="javascript:void(0)" onclick="openAuthModal('login')">Masuk Dashboard</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div>
                    <strong style="color: #ffffff; display: block; margin-bottom: 14px;">Dokumentasi &amp; Legal</strong>
                    <ul class="footer-links">
                        <li><a href="<?= base_url('integrations/ojs/Panduan_Integrasi_OJS_ARTilik.pdf') ?>" target="_blank" download="Panduan_Integrasi_OJS_ARTilik.pdf">Buku Panduan OJS (PDF)</a></li>
                        <li><a href="<?= base_url('integrations/ojs/ARTilikOJSConnector.php') ?>" download>Konektor PHP OJS 3.x</a></li>
                        <li><a href="<?= base_url('integrations/ojs/README.md') ?>" target="_blank">Spesifikasi REST API v1</a></li>
                        <li><a href="<?= base_url('privacy') ?>" onclick="openLegalModal('privacy', event)">Kebijakan Privasi</a></li>
                        <li><a href="<?= base_url('terms') ?>" onclick="openLegalModal('terms', event)">Ketentuan Layanan (ToS)</a></li>
                        <li><a href="javascript:void(0)" onclick="openContactModal('Pertanyaan Umum')">Kontak Pengembang</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-counter-wrap">
                <div class="visitor-counter-badge">
                    <span class="live-dot"></span>
                    <span>Pengunjung Unik: <strong><?= number_format($visitorStats['total_unique'] ?? 1) ?></strong></span>
                    <span class="counter-sep">&bull;</span>
                    <span>Hari Ini: <strong><?= number_format($visitorStats['today_unique'] ?? 1) ?></strong></span>
                    <span class="counter-sep">&bull;</span>
                    <span>Total Kunjungan: <strong><?= number_format($visitorStats['total_hits'] ?? 1) ?></strong></span>
                </div>
            </div>

            <div class="footer-bottom">
                AR Tilik &copy; <?= date('Y') ?> &bull; Academic Writing Similarity & Evidence Checker &bull; <a href="https://www.arsetontong.top" target="_blank" rel="noopener noreferrer" style="color: #94a3b8; text-decoration: underline; text-underline-offset: 2px;">Powered by ARSetontong</a>
            </div>
        </div>
    </footer>

    <!-- Auth Modal (Registration & Login) -->
    <div id="authModal" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <img src="<?= base_url('images/logo.png') ?>" alt="AR Tilik Logo" style="width: 26px; height: 26px; object-fit: contain; border-radius: 6px;">
                    <div class="modal-title" id="modalTitle">Masuk ke AR Tilik</div>
                </div>
                <button type="button" class="modal-close" onclick="closeAuthModal()">&times;</button>
            </div>

            <div class="modal-body">
                <div class="tab-switch">
                    <button type="button" id="tabLogin" class="tab-btn active" onclick="switchTab('login')">Masuk</button>
                    <button type="button" id="tabRegister" class="tab-btn" onclick="switchTab('register')">Daftar Akun Gratis</button>
                </div>

                <div id="authAlert" class="alert-box"></div>

                <!-- Login Form -->
                <form id="formLogin" onsubmit="handleLogin(event)">
                    <!-- Invisible Honeypot Trap for Bots -->
                    <div style="display:none; visibility:hidden; opacity:0; position:absolute; left:-9999px;" aria-hidden="true">
                        <input type="text" name="website_hp" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="loginEmail">Alamat Email</label>
                        <input type="email" id="loginEmail" name="email" class="form-control" placeholder="nama@kampus.ac.id" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="loginPassword">Kata Sandi</label>
                        <input type="password" id="loginPassword" name="password" class="form-control" placeholder="••••••••" required>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 8px;">
                        Masuk ke Akun
                    </button>
                </form>

                <!-- Register Form -->
                <form id="formRegister" onsubmit="handleRegister(event)" style="display: none;">
                    <!-- Anti-Spam Time Token & Honeypot Trap -->
                    <input type="hidden" name="security_token" id="regSecurityToken" value="<?= esc($authSecurityToken ?? '') ?>">
                    <div style="display:none; visibility:hidden; opacity:0; position:absolute; left:-9999px;" aria-hidden="true">
                        <input type="text" name="fax_number" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="regName">Nama Lengkap</label>
                        <input type="text" id="regName" name="name" class="form-control" placeholder="Nama Anda" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="regEmail">Alamat Email Valid</label>
                        <input type="email" id="regEmail" name="email" class="form-control" placeholder="nama@kampus.ac.id" required>
                        <small style="font-size: 11px; color: var(--text-muted); margin-top: 4px; display: block;">
                            ✉️ Tautan aktivasi akun akan dikirimkan ke email ini sebelum Anda dapat masuk.
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="regInstitution">Institusi / Universitas</label>
                        <input type="text" id="regInstitution" name="institution" class="form-control" placeholder="Contoh: Universitas Indonesia / UGM">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="regPassword">Kata Sandi</label>
                        <input type="password" id="regPassword" name="password" class="form-control" placeholder="Minimal 6 karakter" minlength="6" required>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 8px;">
                        Daftar Gratis (3 Submisi/Minggu)
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Contact & Inquiry Modal -->
    <div id="contactModal" class="modal-overlay">
        <div class="modal-card modal-card-md">
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(79, 70, 229, 0.1); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 16px;">
                        📬
                    </div>
                    <div>
                        <div class="modal-title" style="font-size: 17px; font-weight: 800; color: var(--dark);">Hubungi Tim Pengembang</div>
                        <div style="font-size: 12px; color: var(--text-muted);">Pesan langsung diteruskan ke tim pengembang AR Tilik</div>
                    </div>
                </div>
                <button type="button" class="modal-close" onclick="closeContactModal()">&times;</button>
            </div>

            <div class="modal-body">
                <div id="contactAlert" class="alert-box"></div>

                <form id="formContact" onsubmit="handleContactSubmit(event)">
                    <!-- Anti-Spam Security Token -->
                    <input type="hidden" name="security_token" id="contactSecurityToken" value="<?= esc($authSecurityToken ?? '') ?>">
                    
                    <!-- Hidden Honeypot Traps (Invisibly trapped for spambots) -->
                    <div style="display:none; visibility:hidden; opacity:0; position:absolute; left:-9999px;" aria-hidden="true">
                        <input type="text" name="website_hp" tabindex="-1" autocomplete="off">
                        <input type="text" name="fax_number" tabindex="-1" autocomplete="off">
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
                        <div class="form-group" style="margin-bottom: 12px;">
                            <label class="form-label" for="contactName">Nama Lengkap *</label>
                            <input type="text" id="contactName" name="name" class="form-control" placeholder="Prof. / Dr. / Bpk / Ibu" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 12px;">
                            <label class="form-label" for="contactEmail">Email Aktif *</label>
                            <input type="email" id="contactEmail" name="email" class="form-control" placeholder="nama@kampus.ac.id" required>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
                        <div class="form-group" style="margin-bottom: 12px;">
                            <label class="form-label" for="contactInstitution">Institusi / Kampus</label>
                            <input type="text" id="contactInstitution" name="institution" class="form-control" placeholder="Contoh: Univ. Indonesia / UGM">
                        </div>
                        <div class="form-group" style="margin-bottom: 12px;">
                            <label class="form-label" for="contactSubject">Subjek Kebutuhan *</label>
                            <select id="contactSubject" name="subject" class="form-control" required style="padding: 10px 14px; height: 42px;">
                                <option value="Pertanyaan Umum">Pertanyaan Umum</option>
                                <option value="Konsultasi Institusi & Lisensi Kampus">Konsultasi Institusi & Lisensi Kampus</option>
                                <option value="Integrasi Plugin OJS 3.x">Integrasi Plugin OJS 3.x</option>
                                <option value="Kemitraan Korpus & Repositori">Kemitraan Korpus & Repositori</option>
                                <option value="Kebijakan Privasi & Hak Cipta">Kebijakan Privasi & Hak Cipta</option>
                                <option value="Laporan Kendala Teknis">Laporan Kendala Teknis</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 12px;">
                        <label class="form-label" for="contactMessage">Pesan / Pertanyaan Detail *</label>
                        <textarea id="contactMessage" name="message" class="form-control" rows="4" placeholder="Jelaskan kebutuhan, jumlah jurnal/mahasiswa, atau kendala teknis Anda..." required style="resize: vertical; min-height: 85px;"></textarea>
                    </div>

                    <!-- Math Captcha Challenge to defeat Automated AI / Spambots -->
                    <div class="form-group" style="margin-bottom: 16px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 12px; padding: 12px 16px;">
                        <label class="form-label" style="font-size: 12px; color: #475569; display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                            <span>🛡️ Uji Anti-Spam: Berapakah hasil dari <strong id="mathProblemLabel" style="color: var(--primary); font-size: 13px;">...</strong>?</span>
                            <button type="button" onclick="generateMathCaptcha()" style="background:none; border:none; color:var(--primary); font-size:11px; font-weight:700; cursor:pointer; text-decoration:underline;">Ganti Soal</button>
                        </label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="number" id="mathAnswerInput" name="captcha_ans" class="form-control" placeholder="Jawaban angka" required style="max-width: 140px; font-weight: 700; text-align: center;">
                            <input type="hidden" id="mathChallengeToken" name="captcha_token">
                            <span style="font-size: 11px; color: var(--text-muted); line-height: 1.4;">Diperlukan untuk memvalidasi bahwa pengirim adalah akademisi sungguhan.</span>
                        </div>
                    </div>

                    <button type="submit" id="btnSubmitContact" class="btn btn-primary" style="width: 100%;">
                        Kirim Pesan ke Pengembang 🚀
                    </button>
                    <div style="font-size: 11px; color: var(--text-muted); text-align: center; margin-top: 10px;">
                        📧 Pesan Anda langsung diteruskan ke <strong>admin@tilik.arsetontong.top</strong>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Legal Document Modal (Privacy Policy & Terms) -->
    <div id="legalModal" class="modal-overlay">
        <div class="modal-card modal-card-lg">
            <div class="modal-header" style="border-bottom: 1px solid var(--border); padding: 18px 24px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <img src="<?= base_url('images/logo.png') ?>" alt="AR Tilik Logo" style="width: 24px; height: 24px; object-fit: contain;">
                    <div class="modal-title" id="legalModalTitle" style="font-size: 17px; font-weight: 800; color: var(--dark);">Dokumen Legal</div>
                </div>
                <div style="display: flex; align-items: center; gap: 12px;">
                    <a id="legalFullPageLink" href="#" target="_blank" style="font-size: 12px; color: var(--primary); font-weight: 700; text-decoration: underline;">
                        Buka Halaman Penuh ↗
                    </a>
                    <button type="button" class="modal-close" onclick="closeLegalModal()">&times;</button>
                </div>
            </div>
            <div id="legalModalBody" class="modal-body-scroll">
                <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                    <div class="spinner" style="margin: 0 auto 14px;"></div>
                    <span>Memuat dokumen legal...</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        async function openAuthModal(mode) {
            const modal = document.getElementById('authModal');
            modal.style.display = 'flex';
            switchTab(mode);

            const tokenInput = document.getElementById('regSecurityToken');
            if (tokenInput && (!tokenInput.value || tokenInput.value === '')) {
                try {
                    const res = await fetch('<?= base_url('auth/security-token') ?>');
                    const data = await res.json();
                    if (data.token) {
                        tokenInput.value = data.token;
                    }
                } catch (e) {}
            }
        }

        function closeAuthModal() {
            const modal = document.getElementById('authModal');
            modal.style.display = 'none';
            hideAlert();
        }

        window.onclick = function(event) {
            const authModal = document.getElementById('authModal');
            const contactModal = document.getElementById('contactModal');
            const legalModal = document.getElementById('legalModal');

            if (event.target === authModal) closeAuthModal();
            if (event.target === contactModal) closeContactModal();
            if (event.target === legalModal) closeLegalModal();
        };

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeAuthModal();
                closeContactModal();
                closeLegalModal();
            }
        });

        function switchTab(mode) {
            const tabLogin = document.getElementById('tabLogin');
            const tabRegister = document.getElementById('tabRegister');
            const formLogin = document.getElementById('formLogin');
            const formRegister = document.getElementById('formRegister');
            const modalTitle = document.getElementById('modalTitle');

            hideAlert();

            if (mode === 'register') {
                tabRegister.classList.add('active');
                tabLogin.classList.remove('active');
                formRegister.style.display = 'block';
                formLogin.style.display = 'none';
                modalTitle.textContent = 'Daftar Akun AR Tilik Gratis';
            } else {
                tabLogin.classList.add('active');
                tabRegister.classList.remove('active');
                formLogin.style.display = 'block';
                formRegister.style.display = 'none';
                modalTitle.textContent = 'Masuk ke AR Tilik';
            }
        }

        function showAlert(msg, isError = true) {
            const alertBox = document.getElementById('authAlert');
            alertBox.innerHTML = msg;
            alertBox.className = 'alert-box ' + (isError ? 'alert-error' : 'alert-success');
            alertBox.style.display = 'block';
        }

        function hideAlert() {
            const alertBox = document.getElementById('authAlert');
            alertBox.style.display = 'none';
        }

        async function handleLogin(e) {
            e.preventDefault();
            hideAlert();

            const form = e.target;
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span>Memverifikasi...</span>';

            const formData = new FormData(form);
            const loginEmailVal = formData.get('email');

            try {
                const res = await fetch('<?= base_url('auth/login') ?>', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: formData
                });
                const data = await res.json();

                if (!res.ok || data.status === 'error') {
                    if (data.needs_verification) {
                        showAlert('⚠️ ' + data.message + 
                            '<br><a href="javascript:void(0)" onclick="resendVerification(\'' + (data.email || loginEmailVal) + '\')" style="color:var(--primary); font-weight:700; text-decoration:underline; display:inline-block; margin-top:8px;">✉️ Kirim Ulang Tautan Aktivasi Sekarang</a>', true);
                    } else {
                        showAlert(data.message || 'Gagal masuk akun. Periksa email & kata sandi.', true);
                    }
                } else {
                    showAlert(data.message || 'Berhasil masuk! Mengarahkan...', false);
                    setTimeout(() => {
                        window.location.href = data.redirect || '<?= base_url('dashboard') ?>';
                    }, 600);
                }
            } catch (err) {
                showAlert('Kendala koneksi peladen. Silakan coba kembali.', true);
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        }

        async function handleRegister(e) {
            e.preventDefault();
            hideAlert();

            const form = e.target;
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span>Mengirim Email Aktivasi...</span>';

            const formData = new FormData(form);
            const userEmail = formData.get('email');

            try {
                const res = await fetch('<?= base_url('auth/register') ?>', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: formData
                });
                const data = await res.json();

                if (!res.ok || data.status === 'error') {
                    if (data.needs_verification) {
                        showAlert('⚠️ ' + (data.message || 'Email sudah terdaftar namun belum diverifikasi.') + 
                            '<br><a href="javascript:void(0)" onclick="resendVerification(\'' + userEmail + '\')" style="color:var(--primary); font-weight:700; text-decoration:underline; display:inline-block; margin-top:6px;">Kirim Ulang Email Aktivasi</a>', true);
                    } else {
                        showAlert(data.message || 'Gagal mendaftar. Silakan lengkapi formulir.', true);
                    }
                } else {
                    form.reset();
                    showAlert('🎉 <strong>Pendaftaran Berhasil!</strong><br>' + 
                        'Kami telah mengirimkan tautan verifikasi ke <strong>' + userEmail + '</strong>.<br>' +
                        '<span style="font-size:12px; color:#475569; display:block; margin-top:6px;">Silakan buka kotak masuk atau spam email Anda dan klik tautan aktivasi untuk mengaktifkan akun AR Tilik.</span>' +
                        '<div style="margin-top:10px;"><a href="javascript:void(0)" onclick="resendVerification(\'' + userEmail + '\')" style="font-size:12px; color:var(--primary); font-weight:700; text-decoration:underline;">Belum menerima email? Kirim Ulang</a></div>', false);
                }
            } catch (err) {
                showAlert('Kendala koneksi peladen. Silakan coba kembali.', true);
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        }

        async function resendVerification(email) {
            if (!email) {
                email = prompt('Masukkan alamat email yang Anda daftarkan:');
            }
            if (!email) return;

            showAlert('⏳ Sedang mengirimkan ulang tautan verifikasi...', false);
            try {
                const res = await fetch('<?= base_url('auth/resend-verification') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ email: email })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    showAlert('✉️ ' + data.message, false);
                } else {
                    showAlert(data.message || 'Gagal mengirim ulang email verifikasi.', true);
                }
            } catch (err) {
                showAlert('Kendala koneksi peladen. Silakan coba kembali.', true);
            }
        }

        /* --- Contact Form & Anti-Spam Functions --- */
        async function openContactModal(subject = 'Pertanyaan Umum') {
            const modal = document.getElementById('contactModal');
            modal.style.display = 'flex';
            hideContactAlert();

            const subjectSelect = document.getElementById('contactSubject');
            if (subjectSelect) {
                for (let i = 0; i < subjectSelect.options.length; i++) {
                    if (subjectSelect.options[i].value === subject || subjectSelect.options[i].text.includes(subject)) {
                        subjectSelect.selectedIndex = i;
                        break;
                    }
                }
            }

            await generateMathCaptcha();
        }

        function closeContactModal() {
            const modal = document.getElementById('contactModal');
            modal.style.display = 'none';
            hideContactAlert();
        }

        async function generateMathCaptcha() {
            const label = document.getElementById('mathProblemLabel');
            const tokenInput = document.getElementById('mathChallengeToken');
            const ansInput = document.getElementById('mathAnswerInput');
            const secTokenInput = document.getElementById('contactSecurityToken');

            if (label) label.textContent = 'Memuat soal...';
            if (ansInput) ansInput.value = '';

            try {
                const res = await fetch('<?= base_url('contact/challenge') ?>');
                const data = await res.json();
                if (data.status === 'success' && data.challenge) {
                    if (label) label.textContent = data.challenge.question + ' = ?';
                    if (tokenInput) tokenInput.value = data.challenge.token;
                    if (data.time_token && secTokenInput) {
                        secTokenInput.value = data.time_token;
                    }
                } else {
                    if (label) label.textContent = '3 + 4 = ?';
                }
            } catch (e) {
                if (label) label.textContent = '3 + 4 = ?';
            }
        }

        function showContactAlert(msg, isError = true) {
            const alertBox = document.getElementById('contactAlert');
            if (!alertBox) return;
            alertBox.innerHTML = msg;
            alertBox.className = 'alert-box ' + (isError ? 'alert-error' : 'alert-success');
            alertBox.style.display = 'block';
        }

        function hideContactAlert() {
            const alertBox = document.getElementById('contactAlert');
            if (alertBox) alertBox.style.display = 'none';
        }

        async function handleContactSubmit(e) {
            e.preventDefault();
            hideContactAlert();

            const form = e.target;
            const submitBtn = document.getElementById('btnSubmitContact');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span>Mengirim Pesan...</span>';

            const formData = new FormData(form);

            try {
                const res = await fetch('<?= base_url('contact/send') ?>', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: formData
                });
                const data = await res.json();

                if (!res.ok || data.status === 'error') {
                    showContactAlert(data.message || 'Gagal mengirimkan pesan. Silakan periksa kembali formulir Anda.', true);
                    generateMathCaptcha();
                } else {
                    form.reset();
                    showContactAlert('🎉 <strong>Pesan Berhasil Terkirim!</strong><br>' + 
                        (data.message || 'Terima kasih, tim pengembang akan merespons pesan Anda via email dalam 1x24 jam kerja.'), false);
                    setTimeout(() => {
                        generateMathCaptcha();
                    }, 500);
                }
            } catch (err) {
                showContactAlert('Kendala koneksi peladen saat mengirim pesan. Silakan coba kembali.', true);
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        }

        /* --- Legal Document Modal (Privacy Policy & Terms) --- */
        const legalCache = {};

        async function openLegalModal(type, e) {
            if (e) {
                e.preventDefault();
            }

            const modal = document.getElementById('legalModal');
            const titleEl = document.getElementById('legalModalTitle');
            const bodyEl = document.getElementById('legalModalBody');
            const fullLink = document.getElementById('legalFullPageLink');

            const isPrivacy = (type === 'privacy');
            const endpoint = isPrivacy ? '<?= base_url('privacy') ?>' : '<?= base_url('terms') ?>';
            const pageTitle = isPrivacy ? 'Kebijakan Privasi & Kedaulatan Data' : 'Ketentuan Layanan (Terms of Service)';

            if (titleEl) titleEl.textContent = pageTitle;
            if (fullLink) fullLink.href = endpoint;
            modal.style.display = 'flex';

            if (legalCache[type]) {
                if (bodyEl) bodyEl.innerHTML = legalCache[type];
                return;
            }

            if (bodyEl) {
                bodyEl.innerHTML = '<div style="text-align: center; padding: 50px 20px; color: var(--text-muted);">' +
                    '<div style="font-size: 24px; margin-bottom: 8px;">⏳</div>' +
                    '<div>Memuat dokumen ' + pageTitle + '...</div>' +
                    '</div>';
            }

            try {
                const res = await fetch(endpoint, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.status === 'success' && data.html) {
                    legalCache[type] = data.html;
                    if (bodyEl) bodyEl.innerHTML = data.html;
                } else {
                    if (bodyEl) bodyEl.innerHTML = '<div class="alert-box alert-error" style="display:block;">Gagal memuat dokumen. Silakan buka melalui <a href="' + endpoint + '" target="_blank" style="color:var(--primary); font-weight:700;">Halaman Penuh</a>.</div>';
                }
            } catch (err) {
                if (bodyEl) bodyEl.innerHTML = '<div class="alert-box alert-error" style="display:block;">Kendala koneksi jaringan saat memuat dokumen. Silakan buka melalui <a href="' + endpoint + '" target="_blank" style="color:var(--primary); font-weight:700;">Halaman Penuh</a>.</div>';
            }
        }

        function closeLegalModal() {
            const modal = document.getElementById('legalModal');
            modal.style.display = 'none';
        }
    </script>

    <!-- AOS (Animate On Scroll) Library & Initialization -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto open modal when redirected from protected route
            const urlParams = new URLSearchParams(window.location.search);
            const authParam = urlParams.get('auth');
            const verifiedParam = urlParams.get('verified');
            const reasonParam = urlParams.get('reason');

            if (verifiedParam === 'error') {
                openAuthModal('login');
                let errText = 'Tautan verifikasi tidak valid atau telah kedaluwarsa.';
                if (reasonParam === 'expired') {
                    const unvEmail = urlParams.get('email') || '';
                    errText = '⏱️ Tautan verifikasi telah kedaluwarsa (berlaku 24 jam).<br>' +
                        '<a href="javascript:void(0)" onclick="resendVerification(\'' + unvEmail + '\')" style="color:var(--primary); font-weight:700; text-decoration:underline; display:inline-block; margin-top:6px;">Klik di sini untuk mengirimkan tautan aktivasi baru.</a>';
                }
                showAlert(errText, true);
            } else if (verifiedParam === 'success') {
                openAuthModal('login');
                showAlert('🎉 Alamat email Anda telah berhasil diverifikasi! Silakan masuk ke akun Anda.', false);
            } else if (authParam === 'login' || authParam === 'register') {
                openAuthModal(authParam);
                <?php $flashNotice = session()->getFlashdata('auth_notice') ?: session()->getFlashdata('error'); ?>
                <?php if (!empty($flashNotice)) : ?>
                    showAlert(<?= json_encode($flashNotice) ?>, true);
                <?php endif; ?>
            }

            // Directional scroll tracker for bidirectional AOS (fade-up when scrolling down, fade-down when scrolling up)
            let lastScrollTop = window.pageYOffset || document.documentElement.scrollTop;
            document.body.classList.add('scrolling-down');

            window.addEventListener('scroll', function() {
                const currentScrollTop = window.pageYOffset || document.documentElement.scrollTop;
                if (Math.abs(currentScrollTop - lastScrollTop) > 6) {
                    if (currentScrollTop > lastScrollTop) {
                        document.body.classList.add('scrolling-down');
                        document.body.classList.remove('scrolling-up');
                    } else if (currentScrollTop < lastScrollTop) {
                        document.body.classList.add('scrolling-up');
                        document.body.classList.remove('scrolling-down');
                    }
                    lastScrollTop = currentScrollTop <= 0 ? 0 : currentScrollTop;
                }
            }, { passive: true });

            if (typeof AOS !== 'undefined') {
                AOS.init({
                    duration: 750,
                    easing: 'ease-out-cubic',
                    once: false,
                    mirror: true,
                    offset: 70
                });
            }
        });
    </script>
</body>
</html>
