<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login | Professional Portal</title>
    <style>
        :root {
            --bg-gradient: radial-gradient(circle at top left, #f8fafc, #e2e8f0);
            --card-bg: rgba(255, 255, 255, 0.95);
            --primary: #2563eb;
            --secondary: #0d9488;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background: var(--bg-gradient);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .main-container {
            width: 100%;
            max-width: 720px;
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 24px;
            padding: 40px;
            box-shadow: var(--shadow-xl);
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .header {
            text-align: left;
            margin-bottom: 32px;
            border-left: 4px solid var(--primary);
            padding-left: 20px;
        }

        .header h1 {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: -0.025em;
            margin-bottom: 8px;
        }

        .header p {
            color: var(--text-muted);
            font-size: 1rem;
        }

        .grid-layout {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .role-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 30px;
            transition: var(--transition);
            display: flex;
            flex-direction: column;
        }

        .role-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
            border-color: var(--primary);
        }

        .role-icon {
            margin-bottom: 20px;
            color: var(--primary);
            background: #eff6ff;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
        }

        .role-card:nth-child(2) .role-icon {
            color: var(--secondary);
            background: #f0fdfa;
        }

        .role-card h2 {
            font-size: 1.25rem;
            margin-bottom: 12px;
            font-weight: 600;
        }

        .role-card p {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-bottom: 24px;
            line-height: 1.5;
        }

        .btn {
            margin-top: auto;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            font-size: 0.95rem;
            color: #fff;
        }

        .btn-manager { background: var(--primary); }
        .btn-manager:hover { background: #1e40af; }
        .btn-trainer { background: var(--secondary); }
        .btn-trainer:hover { background: #0d7a6f; }

        .footer-note {
            margin-top: 32px;
            text-align: center;
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .icon {
            width: 20px;
            height: 20px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        @media (max-width: 480px) {
            .main-container { padding: 24px; }
            .header h1 { font-size: 1.5rem; }
        }
    </style>
</head>
<body>
    <main class="main-container">
        <header class="header">
            <h1>Staff Login</h1>
            <p>Select your role to access the correct panel.</p>
        </header>

        <div class="grid-layout">
            <article class="role-card">
                <div class="role-icon">
                    <svg class="icon" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12 3l7 4v5c0 5-3.5 8-7 9-3.5-1-7-4-7-9V7l7-4z"></path>
                        <path d="M9 12l2 2 4-4"></path>
                    </svg>
                </div>
                <h2>Manager</h2>
                <p>Manage operations, staff, and performance across the system.</p>
                <a href="{{ url('/manager/login') }}" class="btn btn-manager">
                    Login as Manager
                    <svg class="icon" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M5 12h14"></path>
                        <path d="M13 5l7 7-7 7"></path>
                    </svg>
                </a>
            </article>

            <article class="role-card">
                <div class="role-icon">
                    <svg class="icon" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="8.5" cy="7" r="4"></circle>
                        <path d="M20 8v6"></path>
                        <path d="M23 11h-6"></path>
                    </svg>
                </div>
                <h2>Trainer</h2>
                <p>Access classes, attendance, schedule, and coaching workflow.</p>
                <a href="{{ url('/trainer/login') }}" class="btn btn-trainer">
                    Login as Trainer
                    <svg class="icon" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M5 12h14"></path>
                        <path d="M13 5l7 7-7 7"></path>
                    </svg>
                </a>
            </article>
        </div>

        <footer class="footer-note">
            &copy; 2026 ERP System. All rights reserved.
        </footer>
    </main>
</body>
</html>

