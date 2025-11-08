<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CleanSuite</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="stylesheet" href="https://fonts.bunny.net/css?family=inter:400,500,600,700">
    <style>
        body { font-family: 'Inter', sans-serif; margin: 0; background: #0f172a; color: #f8fafc; }
        main { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh; padding: 2rem; }
        h1 { font-size: clamp(2.5rem, 6vw, 4rem); margin-bottom: 1.5rem; text-align: center; }
        p { max-width: 640px; text-align: center; font-size: 1.125rem; line-height: 1.6; color: #cbd5f5; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem; margin-top: 2.5rem; width: 100%; max-width: 1024px; }
        .card { background: rgba(15, 23, 42, 0.7); border: 1px solid rgba(148, 163, 184, 0.2); border-radius: 1rem; padding: 1.5rem; backdrop-filter: blur(16px); }
        .card h2 { font-size: 1.25rem; margin: 0 0 .75rem; color: #38bdf8; }
        .card ul { list-style: none; padding: 0; margin: 0; }
        .card li { margin-bottom: 0.5rem; color: #e2e8f0; }
        footer { margin-top: 3rem; font-size: 0.9rem; color: #94a3b8; }
    </style>
</head>
<body>
    <main>
        <h1>CleanSuite Operations Cloud</h1>
        <p>All-in-one CRM, scheduling, loyalty, and automation platform tailor-made for high-growth cleaning companies. Seamlessly manage clients, teams, jobs, finances, and engagement in a single Laravel-powered workspace.</p>
        <div class="grid">
            <div class="card">
                <h2>CRM &amp; Loyalty</h2>
                <ul>
                    <li>360° client timeline &amp; segmentation</li>
                    <li>Loyalty tiers, rewards, and referrals</li>
                    <li>Smart customer portal with payments</li>
                </ul>
            </div>
            <div class="card">
                <h2>Operations</h2>
                <ul>
                    <li>Drag-and-drop dispatch calendar</li>
                    <li>Checklist-driven quality control</li>
                    <li>Inventory, payroll, and job costing</li>
                </ul>
            </div>
            <div class="card">
                <h2>Finance</h2>
                <ul>
                    <li>Quotes → invoices with payment links</li>
                    <li>Recurring billing &amp; AR analytics</li>
                    <li>Excel/CSV exports &amp; webhooks</li>
                </ul>
            </div>
            <div class="card">
                <h2>Automation</h2>
                <ul>
                    <li>Telegram, SMS, email campaign builder</li>
                    <li>Zapier/Make &amp; payment integrations</li>
                    <li>Workflow triggers &amp; custom rules</li>
                </ul>
            </div>
        </div>
        <footer>Powered by Laravel 11 · Crafted for franchise-ready cleaning businesses.</footer>
    </main>
</body>
</html>
