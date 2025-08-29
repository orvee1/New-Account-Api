<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>CloudBook — Smart Accounting for SMEs</title>
  <meta name="description" content="CloudBook is a fast, secure, VAT-ready accounting app for small businesses. Create invoices, track expenses, manage inventory, and see real-time reports from anywhere." />
  <meta name="theme-color" content="#2563eb" />
  <link rel="icon" href="/favicon.ico" />

  <!-- Fonts: Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />

  <!-- Tailwind CSS via CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui'] },
        extend: {
          colors: {
            brand: {
              50: '#eff6ff',
              100: '#dbeafe',
              200: '#bfdbfe',
              300: '#93c5fd',
              400: '#60a5fa',
              500: '#3b82f6',
              600: '#2563eb',
              700: '#1d4ed8',
              800: '#1e40af',
              900: '#1e3a8a'
            }
          },
          boxShadow: {
            soft: '0 10px 40px -10px rgba(0,0,0,0.15)'
          }
        }
      }
    }
  </script>
  <style>
    html { scroll-behavior: smooth; }
  </style>

  <!-- Open Graph / Twitter -->
  <meta property="og:title" content="CloudBook — Smart Accounting for SMEs" />
  <meta property="og:description" content="Create invoices, track expenses, reconcile banks, and get real-time insights." />
  <meta property="og:type" content="website" />
  <meta property="og:image" content="/og-image.png" />
  <meta property="og:url" content="https://your-domain.com" />
  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:title" content="CloudBook — Smart Accounting for SMEs" />
  <meta name="twitter:description" content="Fast, secure, VAT-ready accounting for small businesses." />
  <meta name="twitter:image" content="/og-image.png" />

  <!-- JSON-LD -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "SoftwareApplication",
    "name": "CloudBook Accounting",
    "applicationCategory": "BusinessApplication",
    "operatingSystem": "Web, iOS, Android",
    "offers": {
      "@type": "Offer",
      "price": "12",
      "priceCurrency": "USD"
    },
    "description": "VAT-ready accounting for SMEs: invoicing, expenses, bank reconciliation, inventory, and real-time reports."
  }
  </script>
</head>
<body class="bg-white text-slate-800 antialiased">
  <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 bg-brand-700 text-white px-3 py-2 rounded">Skip to content</a>

  <!-- Header / Nav -->
  <header class="sticky top-0 z-40 bg-white/70 backdrop-blur border-b border-slate-200/60">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex h-16 items-center justify-between">
        <!-- Logo -->
        <a href="#" class="flex items-center gap-2" aria-label="CloudBook home">
          <div class="h-9 w-9 rounded-xl bg-brand-600 grid place-items-center text-white font-bold shadow-soft">CB</div>
          <span class="hidden sm:block text-xl font-semibold tracking-tight">CloudBook</span>
        </a>
        <!-- Nav -->
        <nav class="hidden md:flex items-center gap-8" aria-label="Primary">
          <a href="#features" class="hover:text-brand-700">Features</a>
          <a href="#how" class="hover:text-brand-700">How it works</a>
          <a href="#pricing" class="hover:text-brand-700">Pricing</a>
          <a href="#faq" class="hover:text-brand-700">FAQ</a>
        </nav>
        <div class="hidden md:flex items-center gap-3">
          <a href="/login" class="px-4 py-2 rounded-xl border border-slate-300 hover:border-brand-500 hover:text-brand-700 transition">Sign in</a>
          <a href="#waitlist" class="px-4 py-2 rounded-xl bg-brand-600 text-white hover:bg-brand-700 transition shadow-soft">Join waitlist</a>
        </div>
        <!-- Mobile menu button -->
        <button id="menuBtn" type="button" class="md:hidden inline-flex items-center justify-center p-2 rounded-lg border border-slate-300" aria-label="Open menu" aria-controls="mobileNav" aria-expanded="false">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
        </button>
      </div>
    </div>
    <!-- Mobile drawer -->
    <div id="mobileNav" class="md:hidden hidden border-t border-slate-200 bg-white">
      <div class="max-w-7xl mx-auto px-4 py-3 flex flex-col gap-3">
        <a href="#features" class="py-2">Features</a>
        <a href="#how" class="py-2">How it works</a>
        <a href="#pricing" class="py-2">Pricing</a>
        <a href="#faq" class="py-2">FAQ</a>
        <div class="flex gap-3 pt-2">
          <a href="#" class="flex-1 px-4 py-2 rounded-xl border border-slate-300 text-center">Sign in</a>
          <a href="#waitlist" class="flex-1 px-4 py-2 rounded-xl bg-brand-600 text-white text-center">Join waitlist</a>
        </div>
      </div>
    </div>
  </header>

  <!-- Hero -->
  <section class="relative overflow-hidden">
    <div class="absolute inset-0 -z-10 bg-gradient-to-b from-brand-50 via-white to-white"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-16">
      <div class="grid lg:grid-cols-2 gap-10 items-center">
        <div>
          <h1 class="text-4xl sm:text-5xl font-semibold tracking-tight leading-tight">Accounting that <span class="text-brand-700">simplifies</span> your business.</h1>
          <p class="mt-5 text-lg text-slate-600">Create GST/VAT-ready invoices, record expenses, reconcile banks, and get real-time insights from anywhere.</p>
          <div class="mt-8 flex flex-col sm:flex-row gap-3">
            <a href="#waitlist" class="px-6 py-3 rounded-xl bg-brand-600 text-white hover:bg-brand-700 transition shadow-soft">Join the waitlist</a>
            <a href="#features" class="px-6 py-3 rounded-xl border border-slate-300 hover:border-brand-500 transition">See features</a>
          </div>
          <div class="mt-6 text-sm text-slate-500">No spam. Early users get 3 months Pro free.</div>
        </div>
        <!-- App mockup -->
        <div class="relative">
          <div class="aspect-[16/10] rounded-2xl border border-slate-200 bg-white shadow-soft overflow-hidden">
            <div class="h-full grid grid-cols-12">
              <aside class="col-span-3 border-r border-slate-200 p-4 bg-slate-50">
                <div class="font-medium mb-3">Modules</div>
                <ul class="space-y-2 text-sm">
                  <li class="flex items-center justify-between"><span>💸 Sales</span><span class="text-slate-400">28</span></li>
                  <li class="flex items-center justify-between"><span>🧾 Purchases</span><span class="text-slate-400">16</span></li>
                  <li class="flex items-center justify-between"><span>🏦 Banking</span><span class="text-slate-400">2</span></li>
                  <li class="flex items-center justify-between"><span>📦 Inventory</span><span class="text-slate-400">134</span></li>
                  <li class="flex items-center justify-between"><span>📊 Reports</span><span class="text-slate-400">8</span></li>
                </ul>
              </aside>
              <main class="col-span-9 p-4">
                <div class="flex items-center gap-2">
                  <div class="h-8 w-8 rounded-md bg-brand-600/10 grid place-items-center text-brand-700">✦</div>
                  <div class="text-slate-500 text-sm">Sales › Invoices</div>
                </div>
                <h3 class="mt-3 text-xl font-semibold">Invoice #INV-0198 — Arif Traders</h3>
                <p class="mt-2 text-slate-600 text-sm leading-relaxed">Draft • Due in 7 days • VAT 15%</p>
                <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                  <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">🧾 Create invoice</div>
                  <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">📥 Scan receipts (OCR)</div>
                  <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">🏦 Match bank feeds</div>
                  <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">📊 Live P&L & Cash Flow</div>
                </div>
              </main>
            </div>
          </div>
        </div>
        <!-- /App mockup -->
      </div>
    </div>
  </section>

  <main id="main">
    <!-- Features -->
    <section id="features" class="py-20 border-t border-slate-200/70">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto">
          <h2 class="text-3xl font-semibold tracking-tight">Everything you need to run your accounts</h2>
          <p class="mt-3 text-slate-600">Simple for daily bookkeeping. Powerful for growth and compliance.</p>
        </div>
        <div class="mt-12 grid md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div class="p-6 rounded-2xl border border-slate-200 bg-white shadow-soft">
            <div class="h-10 w-10 grid place-items-center rounded-lg bg-brand-600/10 text-brand-700">🧾</div>
            <h3 class="mt-4 font-semibold text-lg">Invoicing & Billing</h3>
            <p class="mt-2 text-slate-600 text-sm">Create GST/VAT-ready invoices, quotes, and recurring bills in seconds.</p>
          </div>
          <div class="p-6 rounded-2xl border border-slate-200 bg-white shadow-soft">
            <div class="h-10 w-10 grid place-items-center rounded-lg bg-brand-600/10 text-brand-700">📥</div>
            <h3 class="mt-4 font-semibold text-lg">Expenses & Receipts (OCR)</h3>
            <p class="mt-2 text-slate-600 text-sm">Snap receipts, auto-capture totals, and categorize spending instantly.</p>
          </div>
          <div class="p-6 rounded-2xl border border-slate-200 bg-white shadow-soft">
            <div class="h-10 w-10 grid place-items-center rounded-lg bg-brand-600/10 text-brand-700">🏦</div>
            <h3 class="mt-4 font-semibold text-lg">Bank Reconciliation</h3>
            <p class="mt-2 text-slate-600 text-sm">Import statements, auto-match transactions, and keep books accurate.</p>
          </div>
          <div class="p-6 rounded-2xl border border-slate-200 bg-white shadow-soft">
            <div class="h-10 w-10 grid place-items-center rounded-lg bg-brand-600/10 text-brand-700">📦</div>
            <h3 class="mt-4 font-semibold text-lg">Inventory & Sales</h3>
            <p class="mt-2 text-slate-600 text-sm">Track stock, cost, and sales with low-stock alerts and SKU support.</p>
          </div>
          <div class="p-6 rounded-2xl border border-slate-200 bg-white shadow-soft">
            <div class="h-10 w-10 grid place-items-center rounded-lg bg-brand-600/10 text-brand-700">🧮</div>
            <h3 class="mt-4 font-semibold text-lg">Tax & VAT</h3>
            <p class="mt-2 text-slate-600 text-sm">Configure VAT rates and get ready-made tax summaries for filing.</p>
          </div>
          <div class="p-6 rounded-2xl border border-slate-200 bg-white shadow-soft">
            <div class="h-10 w-10 grid place-items-center rounded-lg bg-brand-600/10 text-brand-700">📊</div>
            <h3 class="mt-4 font-semibold text-lg">Reports & Dashboards</h3>
            <p class="mt-2 text-slate-600 text-sm">Balance Sheet, P&L, Cash Flow—live insights on any device.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- How it works -->
    <section id="how" class="py-20 border-t border-slate-200/70 bg-slate-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-3 gap-8 items-start">
          <div class="lg:col-span-1">
            <h2 class="text-3xl font-semibold tracking-tight">How it works</h2>
            <p class="mt-3 text-slate-600">Record → Reconcile → Report. That’s it.</p>
          </div>
          <div class="lg:col-span-2 grid sm:grid-cols-3 gap-6">
            <div class="p-6 rounded-2xl border border-slate-200 bg-white shadow-soft">
              <div class="text-sm text-slate-500">Step 1</div>
              <h3 class="mt-1 font-semibold">Record</h3>
              <p class="mt-1 text-sm text-slate-600">Create invoices, bills, and journal entries. Attach receipts easily.</p>
            </div>
            <div class="p-6 rounded-2xl border border-slate-200 bg-white shadow-soft">
              <div class="text-sm text-slate-500">Step 2</div>
              <h3 class="mt-1 font-semibold">Reconcile</h3>
              <p class="mt-1 text-sm text-slate-600">Import bank statements and auto-match transactions—zero guesswork.</p>
            </div>
            <div class="p-6 rounded-2xl border border-slate-200 bg-white shadow-soft">
              <div class="text-sm text-slate-500">Step 3</div>
              <h3 class="mt-1 font-semibold">Report</h3>
              <p class="mt-1 text-sm text-slate-600">See P&L, Balance Sheet, and tax summaries. Share or export anytime.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Pricing -->
    <section id="pricing" class="py-20 border-t border-slate-200/70">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto">
          <h2 class="text-3xl font-semibold tracking-tight">Simple, transparent pricing</h2>
          <p class="mt-3 text-slate-600">Start free. Upgrade when your business grows.</p>
        </div>
        <div class="mt-12 grid md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div class="p-6 rounded-2xl border border-slate-200 bg-white shadow-soft flex flex-col">
            <h3 class="text-xl font-semibold">Starter</h3>
            <p class="text-slate-600 text-sm mt-1">For freelancers</p>
            <div class="mt-4 text-3xl font-bold">$0<span class="text-base font-medium text-slate-500">/mo</span></div>
            <ul class="mt-4 space-y-2 text-sm text-slate-600">
              <li>✓ 10 invoices / month</li>
              <li>✓ Expense tracking</li>
              <li>✓ Basic reports</li>
            </ul>
            <a href="#waitlist" class="mt-6 px-4 py-2 rounded-xl bg-brand-600 text-white text-center hover:bg-brand-700 transition">Get started</a>
          </div>
          <div class="p-6 rounded-2xl border-2 border-brand-600 bg-white shadow-soft flex flex-col">
            <div class="self-start px-2 py-1 text-xs rounded-full bg-brand-600 text-white">Popular</div>
            <h3 class="mt-2 text-xl font-semibold">Business</h3>
            <p class="text-slate-600 text-sm mt-1">For growing SMEs</p>
            <div class="mt-4 text-3xl font-bold">$12<span class="text-base font-medium text-slate-500">/mo</span></div>
            <ul class="mt-4 space-y-2 text-sm text-slate-600">
              <li>✓ Unlimited invoices</li>
              <li>✓ Bank reconciliation</li>
              <li>✓ Inventory & VAT</li>
              <li>✓ Priority support</li>
            </ul>
            <a href="#waitlist" class="mt-6 px-4 py-2 rounded-xl bg-brand-600 text-white text-center hover:bg-brand-700 transition">Join Business waitlist</a>
          </div>
          <div class="p-6 rounded-2xl border border-slate-200 bg-white shadow-soft flex flex-col">
            <h3 class="text-xl font-semibold">Enterprise</h3>
            <p class="text-slate-600 text-sm mt-1">For teams & accountants</p>
            <div class="mt-4 text-3xl font-bold">$20<span class="text-base font-medium text-slate-500">/user</span></div>
            <ul class="mt-4 space-y-2 text-sm text-slate-600">
              <li>✓ Roles & permissions</li>
              <li>✓ Audit logs & SSO</li>
              <li>✓ Advanced reporting</li>
            </ul>
            <a href="#waitlist" class="mt-6 px-4 py-2 rounded-xl border border-slate-300 text-center hover:border-brand-500 transition">Talk to sales</a>
          </div>
        </div>
      </div>
    </section>

    <!-- Waitlist -->
    <section id="waitlist" class="py-20 border-t border-slate-200/70 bg-gradient-to-b from-white to-brand-50">
      <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-semibold tracking-tight">Be first to try CloudBook Accounting</h2>
        <p class="mt-3 text-slate-600">Sign up to get early access and product updates. We’ll only email important things.</p>
        <form id="waitlistForm" class="mt-6 flex flex-col sm:flex-row gap-3 justify-center" novalidate>
          <!-- honeypot -->
          <input type="text" name="company" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true" />
          <input id="email" name="email" type="email" required autocomplete="email" inputmode="email"
                 placeholder="Enter your email"
                 pattern="^[^\s@]+@[^\s@]+\.[^\s@]+$"
                 class="w-full sm:w-2/3 px-4 py-3 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500" />
          <button type="submit" class="px-6 py-3 rounded-xl bg-brand-600 text-white hover:bg-brand-700 transition shadow-soft">Join waitlist</button>
        </form>
        <div id="formMsg" class="mt-3 text-sm text-slate-600" role="status" aria-live="polite"></div>
      </div>
    </section>

    <!-- FAQ -->
    <section id="faq" class="py-20 border-t border-slate-200/70">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-semibold tracking-tight text-center">Frequently asked questions</h2>
        <div class="mt-10 grid md:grid-cols-2 gap-6">
          <details class="p-5 rounded-2xl border border-slate-200 bg-white shadow-soft">
            <summary class="font-medium cursor-pointer">Does CloudBook support VAT?</summary>
            <p class="mt-2 text-slate-600 text-sm">Yes. You can configure VAT rates and generate tax summaries to help with filing.</p>
          </details>
          <details class="p-5 rounded-2xl border border-slate-200 bg-white shadow-soft">
            <summary class="font-medium cursor-pointer">Can I import from other software?</summary>
            <p class="mt-2 text-slate-600 text-sm">Import contacts, items, and transactions from CSV. Importers for popular tools are on the way.</p>
          </details>
          <details class="p-5 rounded-2xl border border-slate-200 bg-white shadow-soft">
            <summary class="font-medium cursor-pointer">Is my data secure?</summary>
            <p class="mt-2 text-slate-600 text-sm">We use strong encryption in transit and at rest. Role-based access keeps your books safe.</p>
          </details>
          <details class="p-5 rounded-2xl border border-slate-200 bg-white shadow-soft">
            <summary class="font-medium cursor-pointer">When will CloudBook launch?</summary>
            <p class="mt-2 text-slate-600 text-sm">We’re aiming for a public beta soon. Join the waitlist for early access.</p>
          </details>
        </div>
      </div>
    </section>
  </main>

  <!-- Footer -->
  <footer class="border-t border-slate-200/80">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
      <div class="flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="flex items-center gap-2">
          <div class="h-8 w-8 rounded-lg bg-brand-600 grid place-items-center text-white font-bold">CB</div>
          <div class="text-slate-500">© <span id="year"></span> CloudBook. All rights reserved.</div>
        </div>
        <nav class="flex items-center gap-5 text-sm text-slate-600" aria-label="Footer">
          <a href="#" class="hover:text-brand-700">Privacy</a>
          <a href="#" class="hover:text-brand-700">Terms</a>
          <a href="#" class="hover:text-brand-700">Contact</a>
        </nav>
      </div>
    </div>
  </footer>

  <script defer>
    // Mobile nav toggle + a11y
    const btn = document.getElementById('menuBtn');
    const mobile = document.getElementById('mobileNav');
    btn?.addEventListener('click', () => {
      const expanded = btn.getAttribute('aria-expanded') === 'true';
      btn.setAttribute('aria-expanded', String(!expanded));
      mobile.classList.toggle('hidden');
    });

    // Waitlist submit (fake)
    const form = document.getElementById('waitlistForm');
    const msg = document.getElementById('formMsg');
    form?.addEventListener('submit', (e) => {
      e.preventDefault();
      const honeypot = form.querySelector('input[name="company"]').value;
      if (honeypot) return; // bot
      const email = /** @type {HTMLInputElement} */ (form.querySelector('#email'));
      if (!email.checkValidity()) {
        msg.textContent = 'Please enter a valid email address.';
        msg.classList.remove('text-green-700');
        msg.classList.add('text-red-600');
        email.focus();
        return;
      }
      msg.textContent = `Thanks, ${email.value}! We’ll be in touch soon.`;
      msg.classList.remove('text-red-600');
      msg.classList.add('text-green-700');
      form.reset();
    });

    // Year in footer
    document.getElementById('year').textContent = new Date().getFullYear();
  </script>
</body>
</html>
