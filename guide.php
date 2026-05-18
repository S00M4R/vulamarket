<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vula Market — User Guide</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
<style>
  :root {
    --amber: #F97316;
    --amber-light: #FFF7ED;
    --amber-mid: #FDBA74;
    --dark: #18181B;
    --dark-mid: #27272A;
    --muted: #71717A;
    --border: #E4E4E7;
    --bg: #FAFAF9;
    --white: #FFFFFF;
    --green: #16A34A;
    --green-light: #F0FDF4;
    --blue: #2563EB;
    --blue-light: #EFF6FF;
    --red: #DC2626;
    --red-light: #FEF2F2;
  }

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg);
    color: var(--dark);
    font-size: 16px;
    line-height: 1.7;
  }

  /* ── SIDEBAR NAV ── */
  .layout { display: flex; min-height: 100vh; }

  .sidebar {
    width: 280px;
    min-width: 280px;
    background: var(--dark);
    color: #fff;
    position: sticky;
    top: 0;
    height: 100vh;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
  }

  .sidebar-logo {
    padding: 2rem 1.5rem 1.5rem;
    border-bottom: 1px solid rgba(255,255,255,.08);
  }

  .sidebar-logo .wordmark {
    font-family: 'Syne', sans-serif;
    font-weight: 800;
    font-size: 1.4rem;
    color: var(--amber);
    letter-spacing: -.02em;
  }

  .sidebar-logo .subtitle {
    font-size: .75rem;
    color: rgba(255,255,255,.4);
    margin-top: .15rem;
    text-transform: uppercase;
    letter-spacing: .08em;
    font-weight: 500;
  }

  .nav-section {
    padding: .75rem 1.5rem .25rem;
    font-size: .65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .12em;
    color: rgba(255,255,255,.25);
    margin-top: .5rem;
  }

  .nav-link {
    display: flex;
    align-items: center;
    gap: .6rem;
    padding: .5rem 1.5rem;
    color: rgba(255,255,255,.6);
    text-decoration: none;
    font-size: .88rem;
    font-weight: 400;
    transition: all .15s;
    border-left: 3px solid transparent;
  }

  .nav-link:hover, .nav-link.active {
    color: #fff;
    background: rgba(255,255,255,.05);
    border-left-color: var(--amber);
  }

  .nav-icon { font-size: 1rem; width: 1.2rem; text-align: center; }

  /* ── MAIN CONTENT ── */
  .main {
    flex: 1;
    max-width: 860px;
    padding: 3rem 4rem;
  }

  /* ── HERO ── */
  .guide-hero {
    background: linear-gradient(135deg, var(--dark) 0%, #3F3F46 100%);
    color: #fff;
    border-radius: 16px;
    padding: 3rem;
    margin-bottom: 3rem;
    position: relative;
    overflow: hidden;
  }

  .guide-hero::before {
    content: '';
    position: absolute;
    top: -60px; right: -60px;
    width: 220px; height: 220px;
    background: radial-gradient(circle, rgba(249,115,22,.35) 0%, transparent 70%);
    border-radius: 50%;
  }

  .guide-hero::after {
    content: '';
    position: absolute;
    bottom: -40px; left: 40%;
    width: 160px; height: 160px;
    background: radial-gradient(circle, rgba(249,115,22,.15) 0%, transparent 70%);
    border-radius: 50%;
  }

  .guide-hero .tag {
    display: inline-block;
    background: rgba(249,115,22,.2);
    color: var(--amber-mid);
    font-size: .72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .1em;
    padding: .3rem .75rem;
    border-radius: 100px;
    margin-bottom: 1rem;
    border: 1px solid rgba(249,115,22,.3);
  }

  .guide-hero h1 {
    font-family: 'Syne', sans-serif;
    font-size: 2.4rem;
    font-weight: 800;
    line-height: 1.1;
    margin-bottom: .75rem;
    position: relative;
    z-index: 1;
  }

  .guide-hero h1 span { color: var(--amber); }

  .guide-hero p {
    color: rgba(255,255,255,.65);
    font-size: 1.05rem;
    max-width: 520px;
    position: relative;
    z-index: 1;
  }

  /* ── SECTIONS ── */
  .section {
    margin-bottom: 3.5rem;
    scroll-margin-top: 2rem;
  }

  .section-header {
    display: flex;
    align-items: center;
    gap: .75rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--border);
  }

  .section-icon {
    width: 44px; height: 44px;
    background: var(--amber-light);
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
    border: 1px solid var(--amber-mid);
  }

  .section-header h2 {
    font-family: 'Syne', sans-serif;
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--dark);
    letter-spacing: -.02em;
  }

  /* ── STEPS ── */
  .steps { display: flex; flex-direction: column; gap: 1rem; }

  .step {
    display: flex;
    gap: 1rem;
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 1.25rem;
    transition: border-color .15s, box-shadow .15s;
  }

  .step:hover {
    border-color: var(--amber-mid);
    box-shadow: 0 2px 12px rgba(249,115,22,.08);
  }

  .step-num {
    width: 32px; height: 32px;
    background: var(--amber);
    color: #fff;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-family: 'Syne', sans-serif;
    font-weight: 800;
    font-size: .9rem;
    flex-shrink: 0;
  }

  .step-body h3 {
    font-family: 'Syne', sans-serif;
    font-size: 1rem;
    font-weight: 700;
    margin-bottom: .3rem;
    color: var(--dark);
  }

  .step-body p { font-size: .9rem; color: #52525B; line-height: 1.6; }
  .step-body p + p { margin-top: .4rem; }

  /* ── CALLOUTS ── */
  .callout {
    display: flex;
    gap: .75rem;
    border-radius: 10px;
    padding: 1rem 1.25rem;
    margin: 1.25rem 0;
    font-size: .9rem;
    line-height: 1.6;
  }

  .callout-icon { font-size: 1.1rem; flex-shrink: 0; margin-top: .1rem; }
  .callout strong { display: block; margin-bottom: .2rem; font-weight: 600; }

  .callout-tip    { background: var(--blue-light);  border: 1px solid #BFDBFE; color: #1E40AF; }
  .callout-warn   { background: var(--amber-light); border: 1px solid var(--amber-mid); color: #92400E; }
  .callout-success{ background: var(--green-light); border: 1px solid #BBF7D0; color: #166534; }
  .callout-danger { background: var(--red-light);   border: 1px solid #FECACA; color: #991B1B; }

  /* ── STATUS BADGES ── */
  .badge-row { display: flex; flex-wrap: wrap; gap: .6rem; margin: .75rem 0; }

  .badge {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .3rem .75rem;
    border-radius: 100px;
    font-size: .8rem;
    font-weight: 600;
  }

  .badge-pending   { background: #FEF9C3; color: #854D0E; border: 1px solid #FDE68A; }
  .badge-escrow    { background: #DBEAFE; color: #1E40AF; border: 1px solid #BFDBFE; }
  .badge-completed { background: var(--green-light); color: #166534; border: 1px solid #BBF7D0; }
  .badge-cancelled { background: var(--red-light); color: #991B1B; border: 1px solid #FECACA; }

  /* ── BOX SIZE TABLE ── */
  .box-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: .5rem;
    margin: 1rem 0;
  }

  .box-card {
    background: var(--white);
    border: 2px solid var(--border);
    border-radius: 10px;
    padding: .75rem .5rem;
    text-align: center;
  }

  .box-card .box-label {
    font-family: 'Syne', sans-serif;
    font-weight: 800;
    font-size: 1.1rem;
    color: var(--amber);
  }

  .box-card .box-dims { font-size: .72rem; color: var(--muted); margin-top: .2rem; line-height: 1.4; }

  /* ── FLOW DIAGRAM ── */
  .flow {
    display: flex;
    flex-direction: column;
    gap: 0;
    margin: 1rem 0;
  }

  .flow-step {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    position: relative;
  }

  .flow-left {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 36px;
    flex-shrink: 0;
  }

  .flow-dot {
    width: 36px; height: 36px;
    border-radius: 50%;
    background: var(--amber);
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: .9rem;
    flex-shrink: 0;
    z-index: 1;
  }

  .flow-line {
    width: 2px;
    flex: 1;
    background: var(--border);
    min-height: 24px;
  }

  .flow-body {
    flex: 1;
    padding-bottom: 1.25rem;
  }

  .flow-body h4 {
    font-family: 'Syne', sans-serif;
    font-weight: 700;
    font-size: .95rem;
    margin-bottom: .2rem;
    color: var(--dark);
  }

  .flow-body p { font-size: .85rem; color: var(--muted); }

  /* ── INFO TABLE ── */
  .info-table { width: 100%; border-collapse: collapse; margin: .75rem 0; }
  .info-table th {
    background: var(--dark);
    color: #fff;
    font-family: 'Syne', sans-serif;
    font-size: .8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    padding: .65rem 1rem;
    text-align: left;
  }
  .info-table td {
    padding: .7rem 1rem;
    font-size: .88rem;
    border-bottom: 1px solid var(--border);
    vertical-align: top;
  }
  .info-table tr:last-child td { border-bottom: none; }
  .info-table tr:nth-child(even) td { background: var(--bg); }
  .info-table th:first-child { border-radius: 8px 0 0 0; }
  .info-table th:last-child  { border-radius: 0 8px 0 0; }

  /* ── QUICK REF CARD ── */
  .quick-card {
    background: var(--dark);
    color: #fff;
    border-radius: 14px;
    padding: 1.75rem;
    margin: 1.25rem 0;
  }

  .quick-card h3 {
    font-family: 'Syne', sans-serif;
    font-size: 1rem;
    font-weight: 700;
    color: var(--amber);
    margin-bottom: 1rem;
    text-transform: uppercase;
    letter-spacing: .06em;
  }

  .quick-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: .5rem 0;
    border-bottom: 1px solid rgba(255,255,255,.08);
    font-size: .88rem;
  }

  .quick-row:last-child { border-bottom: none; }
  .quick-row .label { color: rgba(255,255,255,.5); }
  .quick-row .val { font-weight: 600; color: #fff; }
  .quick-row .val.amber { color: var(--amber); }

  /* ── FAQ ── */
  .faq-item {
    border: 1px solid var(--border);
    border-radius: 10px;
    margin-bottom: .6rem;
    background: var(--white);
    overflow: hidden;
  }

  .faq-q {
    padding: 1rem 1.25rem;
    font-weight: 600;
    font-size: .92rem;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: .5rem;
  }

  .faq-q:hover { background: var(--bg); }

  .faq-q::after {
    content: '+';
    font-size: 1.2rem;
    font-weight: 300;
    color: var(--muted);
    flex-shrink: 0;
  }

  .faq-item.open .faq-q::after { content: '−'; }

  .faq-a {
    display: none;
    padding: 0 1.25rem 1rem;
    font-size: .88rem;
    color: #52525B;
    line-height: 1.6;
    border-top: 1px solid var(--border);
    padding-top: .75rem;
  }

  .faq-item.open .faq-a { display: block; }

  /* ── PAGE FOOTER ── */
  .guide-footer {
    margin-top: 4rem;
    padding-top: 2rem;
    border-top: 2px solid var(--border);
    text-align: center;
    color: var(--muted);
    font-size: .85rem;
    padding-bottom: 3rem;
  }

  .guide-footer strong { color: var(--amber); font-family: 'Syne', sans-serif; }

  /* ── RESPONSIVE ── */
  @media (max-width: 900px) {
    .sidebar { display: none; }
    .main { padding: 2rem 1.5rem; max-width: 100%; }
    .box-grid { grid-template-columns: repeat(3, 1fr); }
    .guide-hero h1 { font-size: 1.8rem; }
  }
</style>
</head>
<body>

<div class="layout">

  <!-- SIDEBAR -->
  <nav class="sidebar">
    <div class="sidebar-logo">
      <div class="wordmark">Vula Market</div>
      <div class="subtitle">User Guide</div>
    </div>

    <div class="nav-section">Getting Started</div>
    <a class="nav-link" href="#getting-started"><span class="nav-icon">👋</span> Welcome</a>
    <a class="nav-link" href="#registration"><span class="nav-icon">✍️</span> Creating an Account</a>
    <a class="nav-link" href="#profile"><span class="nav-icon">👤</span> Setting Up Your Profile</a>

    <div class="nav-section">Buying</div>
    <a class="nav-link" href="#browsing"><span class="nav-icon">🔍</span> Browsing & Searching</a>
    <a class="nav-link" href="#buying"><span class="nav-icon">🛒</span> Buying an Item</a>
    <a class="nav-link" href="#tracking"><span class="nav-icon">📦</span> Tracking Your Order</a>
    <a class="nav-link" href="#receiving"><span class="nav-icon">✅</span> Confirming Receipt</a>

    <div class="nav-section">Selling</div>
    <a class="nav-link" href="#listing"><span class="nav-icon">📝</span> Creating a Listing</a>
    <a class="nav-link" href="#shipping-seller"><span class="nav-icon">🚚</span> Shipping an Order</a>
    <a class="nav-link" href="#wallet"><span class="nav-icon">💰</span> Wallet & Payouts</a>

    <div class="nav-section">Reference</div>
    <a class="nav-link" href="#order-statuses"><span class="nav-icon">📊</span> Order Statuses</a>
    <a class="nav-link" href="#faq"><span class="nav-icon">❓</span> FAQs</a>

    <div class="nav-section">Admin</div>
    <a class="nav-link" href="#admin-overview"><span class="nav-icon">🔒</span> Admin Overview</a>
    <a class="nav-link" href="#admin-dashboard"><span class="nav-icon">📈</span> Dashboard & Stats</a>
    <a class="nav-link" href="#admin-payouts"><span class="nav-icon">💸</span> Processing Payouts</a>
    <a class="nav-link" href="#admin-orders"><span class="nav-icon">📦</span> Managing Orders</a>
    <a class="nav-link" href="#admin-shipments"><span class="nav-icon">🚚</span> Shipments Dashboard</a>
    <a class="nav-link" href="#admin-checklist"><span class="nav-icon">✅</span> Go-Live Checklist</a>
  </nav>

  <!-- MAIN -->
  <main class="main">

    <!-- HERO -->
    <div class="guide-hero">
      <div class="tag">Official User Guide</div>
      <h1>How to use<br><span>Vula Market</span></h1>
      <p>South Africa's simplest C2C marketplace. This guide walks you through everything — from creating an account to getting paid.</p>
    </div>

    <!-- ── GETTING STARTED ── -->
    <section class="section" id="getting-started">
      <div class="section-header">
        <div class="section-icon">👋</div>
        <h2>Welcome to Vula Market</h2>
      </div>
      <p>Vula Market is a consumer-to-consumer (C2C) marketplace designed for South Africans. You can buy and sell almost anything — all payments are secured by escrow and shipping is handled through <strong>The Courier Guy's PUDO locker network</strong>.</p>

      <div class="callout callout-success" style="margin-top:1.25rem">
        <span class="callout-icon">🔒</span>
        <div>
          <strong>Safe by design</strong>
          Your money is held in escrow after payment and only released to the seller once you confirm you've received your item. Neither party can be scammed.
        </div>
      </div>
    </section>

    <!-- ── REGISTRATION ── -->
    <section class="section" id="registration">
      <div class="section-header">
        <div class="section-icon">✍️</div>
        <h2>Creating an Account</h2>
      </div>

      <div class="steps">
        <div class="step">
          <div class="step-num">1</div>
          <div class="step-body">
            <h3>Go to the Register page</h3>
            <p>Click <strong>Get Started Free</strong> on the homepage, or navigate to <code>/auth/register.php</code>.</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">2</div>
          <div class="step-body">
            <h3>Fill in your details</h3>
            <p><strong>Full Name</strong> — minimum 2 characters.</p>
            <p><strong>Email Address</strong> — used to log in; cannot be changed later.</p>
            <p><strong>Password</strong> — minimum 8 characters. Confirm it in the second field.</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">3</div>
          <div class="step-body">
            <h3>Click Create Account</h3>
            <p>You'll be logged in immediately and redirected to the homepage.</p>
          </div>
        </div>
      </div>

      <div class="callout callout-tip">
        <span class="callout-icon">💡</span>
        <div>Already have an account? Click <strong>Log In</strong> on the homepage and enter your email and password.</div>
      </div>
    </section>

    <!-- ── PROFILE ── -->
    <section class="section" id="profile">
      <div class="section-header">
        <div class="section-icon">👤</div>
        <h2>Setting Up Your Profile</h2>
      </div>

      <p>Before you can buy or sell, complete your profile. Navigate to <strong>My Profile</strong> from the menu or go to <code>/auth/profile.php</code>.</p>

      <div class="callout callout-warn" style="margin-top:1rem">
        <span class="callout-icon">⚠️</span>
        <div>
          <strong>Profile must be complete to transact</strong>
          Buyers need a phone number so TCG can coordinate delivery. Sellers additionally need to set a preferred drop-off locker before buyers can get a shipping quote on their listings.
        </div>
      </div>

      <div class="steps" style="margin-top:1.25rem">
        <div class="step">
          <div class="step-num">1</div>
          <div class="step-body">
            <h3>Add your phone number</h3>
            <p>Enter a valid South African mobile number (e.g. <em>+27 82 000 0000</em>). This is required so The Courier Guy can contact you about collections and deliveries.</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">2</div>
          <div class="step-body">
            <h3>Select your preferred drop-off locker <em>(sellers)</em></h3>
            <p>Choose the TCG PUDO locker nearest to you from the dropdown. This is where you'll drop parcels when you make a sale. Buyers use this locker to calculate your shipping costs.</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">3</div>
          <div class="step-body">
            <h3>Save your profile</h3>
            <p>Click <strong>Save Profile</strong>. You're ready to buy and sell.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ── BROWSING ── -->
    <section class="section" id="browsing">
      <div class="section-header">
        <div class="section-icon">🔍</div>
        <h2>Browsing & Searching</h2>
      </div>

      <div class="steps">
        <div class="step">
          <div class="step-num">1</div>
          <div class="step-body">
            <h3>Browse the homepage</h3>
            <p>The homepage shows all active listings sorted by newest first, displayed as a product grid with photo, title, price, and seller name.</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">2</div>
          <div class="step-body">
            <h3>Search for something specific</h3>
            <p>Use the search bar at the top of the listings grid. Type any keyword — the search checks both listing titles and descriptions. Click <strong>Clear</strong> to go back to all listings.</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">3</div>
          <div class="step-body">
            <h3>Click any listing to view details</h3>
            <p>The listing detail page shows the product photo, full description, price, seller name, and the shipping quote tool.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ── BUYING ── -->
    <section class="section" id="buying">
      <div class="section-header">
        <div class="section-icon">🛒</div>
        <h2>Buying an Item</h2>
      </div>

      <div class="flow">
        <div class="flow-step">
          <div class="flow-left"><div class="flow-dot">1</div><div class="flow-line"></div></div>
          <div class="flow-body">
            <h4>Open a listing and enter your delivery address</h4>
            <p>Fill in your street, suburb, city, province, and postal code in the delivery form on the right-hand panel of the listing page.</p>
          </div>
        </div>
        <div class="flow-step">
          <div class="flow-left"><div class="flow-dot">2</div><div class="flow-line"></div></div>
          <div class="flow-body">
            <h4>Get a live shipping quote</h4>
            <p>Click <strong>Get Shipping Quote</strong>. The system fetches a real-time rate from The Courier Guy based on your address and the item's size. The shipping cost is shown before you commit.</p>
          </div>
        </div>
        <div class="flow-step">
          <div class="flow-left"><div class="flow-dot">3</div><div class="flow-line"></div></div>
          <div class="flow-body">
            <h4>Click Proceed to Payment</h4>
            <p>Review the item price and shipping total, then click <strong>Proceed to Payment</strong>. The listing is reserved for you at this point.</p>
          </div>
        </div>
        <div class="flow-step">
          <div class="flow-left"><div class="flow-dot">4</div><div class="flow-line"></div></div>
          <div class="flow-body">
            <h4>Pay securely via Yoco</h4>
            <p>You'll be redirected to Yoco's hosted payment page. Pay with your card. Vula Market never sees your card details.</p>
          </div>
        </div>
        <div class="flow-step">
          <div class="flow-left"><div class="flow-dot">5</div><div class="flow-line"></div></div>
          <div class="flow-body">
            <h4>Funds held in escrow</h4>
            <p>Once payment is confirmed, your order moves to <strong>Paid in Escrow</strong>. Your money is held safely until you receive and confirm your item.</p>
          </div>
        </div>
      </div>

      <div class="callout callout-tip">
        <span class="callout-icon">💡</span>
        <div>You cannot buy your own listings. If you're the seller, the buy panel will show "This is your listing" instead.</div>
      </div>
    </section>

    <!-- ── TRACKING ── -->
    <section class="section" id="tracking">
      <div class="section-header">
        <div class="section-icon">📦</div>
        <h2>Tracking Your Order</h2>
      </div>

      <div class="steps">
        <div class="step">
          <div class="step-num">1</div>
          <div class="step-body">
            <h3>Go to My Orders</h3>
            <p>Navigate to <strong>Orders</strong> in the menu to see all your purchases. Click any order to open the detail page.</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">2</div>
          <div class="step-body">
            <h3>Check the order chat</h3>
            <p>The order detail page includes a shared chat between you and the seller. The seller will post the TCG tracking reference and deposit code here after booking the shipment.</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">3</div>
          <div class="step-body">
            <h3>Track via TCG</h3>
            <p>Use the tracking reference posted in the chat to follow your parcel on the TCG PUDO website or app. You can also view tracking from the order detail page once the seller books the shipment.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ── CONFIRMING RECEIPT ── -->
    <section class="section" id="receiving">
      <div class="section-header">
        <div class="section-icon">✅</div>
        <h2>Confirming Receipt</h2>
      </div>

      <p>Once your parcel arrives, you must confirm receipt on Vula Market to release payment to the seller.</p>

      <div class="steps" style="margin-top:1.25rem">
        <div class="step">
          <div class="step-num">1</div>
          <div class="step-body">
            <h3>Open your order</h3>
            <p>Go to <strong>Orders → View</strong> for the relevant order.</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">2</div>
          <div class="step-body">
            <h3>Click "Mark as Received"</h3>
            <p>This button appears when the order is in <strong>Paid in Escrow</strong> status. Clicking it confirms you've received the item in the expected condition.</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">3</div>
          <div class="step-body">
            <h3>Seller gets paid</h3>
            <p>The escrow funds are immediately credited to the seller's wallet, minus the platform fee. The order status changes to <strong>Completed</strong>.</p>
          </div>
        </div>
      </div>

      <div class="callout callout-danger">
        <span class="callout-icon">⚠️</span>
        <div>
          <strong>Only confirm if you're satisfied</strong>
          Once you click Mark as Received, payment is released and cannot be reversed. If there's an issue with your order, use the order chat to communicate with the seller first.
        </div>
      </div>
    </section>

    <!-- ── CREATING A LISTING ── -->
    <section class="section" id="listing">
      <div class="section-header">
        <div class="section-icon">📝</div>
        <h2>Creating a Listing</h2>
      </div>

      <p>Any registered user can list an item. Navigate to <strong>Post a Listing</strong> from the homepage or menu.</p>

      <div class="steps" style="margin-top:1.25rem">
        <div class="step">
          <div class="step-num">1</div>
          <div class="step-body">
            <h3>Write a clear title</h3>
            <p>Minimum 3 characters, maximum 120. Be specific — include brand, model, and condition. Example: <em>Sony PS5 DualSense Controller – Like New</em>.</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">2</div>
          <div class="step-body">
            <h3>Write a description</h3>
            <p>Minimum 10 characters, maximum 2000. Describe condition, what's included, any defects, and why you're selling. Honest descriptions build trust and reduce disputes.</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">3</div>
          <div class="step-body">
            <h3>Set your price in ZAR</h3>
            <p>Enter the item price only — shipping is calculated separately at checkout. Note that a <strong>5% platform fee</strong> is deducted from your payout when the item sells.</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">4</div>
          <div class="step-body">
            <h3>Upload a photo</h3>
            <p>Upload a JPEG, PNG, or WebP image of your actual item. Maximum 5MB. A clear, well-lit photo significantly increases buyer trust and conversions.</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">5</div>
          <div class="step-body">
            <h3>Select the parcel size</h3>
            <p>Choose the smallest locker size your packaged item will fit in. This is used to calculate accurate shipping quotes for buyers.</p>
            <div class="box-grid" style="margin-top:.75rem">
              <div class="box-card"><div class="box-label">XS</div><div class="box-dims">20×15 cm<br>max 1 kg</div></div>
              <div class="box-card"><div class="box-label">S</div><div class="box-dims">32×22 cm<br>max 3 kg</div></div>
              <div class="box-card"><div class="box-label">M</div><div class="box-dims">40×30 cm<br>max 7 kg</div></div>
              <div class="box-card"><div class="box-label">L</div><div class="box-dims">50×40 cm<br>max 15 kg</div></div>
              <div class="box-card"><div class="box-label">XL</div><div class="box-dims">60×50 cm<br>max 25 kg</div></div>
            </div>
          </div>
        </div>
        <div class="step">
          <div class="step-num">6</div>
          <div class="step-body">
            <h3>Click Publish Listing</h3>
            <p>Your listing is immediately live and visible to all buyers on the homepage.</p>
          </div>
        </div>
      </div>

      <div class="callout callout-tip">
        <span class="callout-icon">💡</span>
        <div>You can remove your listing at any time by opening it and clicking <strong>Remove Listing</strong>. This is permanent and cannot be undone.</div>
      </div>
    </section>

    <!-- ── SHIPPING (SELLER) ── -->
    <section class="section" id="shipping-seller">
      <div class="section-header">
        <div class="section-icon">🚚</div>
        <h2>Shipping an Order (Sellers)</h2>
      </div>

      <p>When a buyer pays for your item, you'll receive a notification and the order will appear in your <strong>Orders</strong> list with status <em>Paid in Escrow</em>. Here's what to do next:</p>

      <div class="flow" style="margin-top:1.25rem">
        <div class="flow-step">
          <div class="flow-left"><div class="flow-dot">1</div><div class="flow-line"></div></div>
          <div class="flow-body">
            <h4>Open the order</h4>
            <p>Go to <strong>Orders</strong> and open the order. Review the buyer's delivery address and the item details.</p>
          </div>
        </div>
        <div class="flow-step">
          <div class="flow-left"><div class="flow-dot">2</div><div class="flow-line"></div></div>
          <div class="flow-body">
            <h4>Pack the item securely</h4>
            <p>Package your item well before booking the shipment. Use appropriate padding and ensure it fits the parcel size you selected when listing.</p>
          </div>
        </div>
        <div class="flow-step">
          <div class="flow-left"><div class="flow-dot">3</div><div class="flow-line"></div></div>
          <div class="flow-body">
            <h4>Book the shipment</h4>
            <p>On the order page, select your drop-off locker (pre-filled from your profile) and click <strong>Book Shipment</strong>. Vula Market creates the TCG shipment automatically.</p>
          </div>
        </div>
        <div class="flow-step">
          <div class="flow-left"><div class="flow-dot">4</div><div class="flow-line"></div></div>
          <div class="flow-body">
            <h4>Get your deposit code</h4>
            <p>After booking, your unique locker deposit code is posted in the order chat. You'll need this code to open the locker compartment and deposit your parcel.</p>
          </div>
        </div>
        <div class="flow-step">
          <div class="flow-left"><div class="flow-dot">5</div><div class="flow-line"></div></div>
          <div class="flow-body">
            <h4>Drop the parcel at your locker</h4>
            <p>Visit your chosen TCG PUDO locker, enter your deposit code on the touchscreen, place the parcel inside, and close the door. TCG will collect and deliver to the buyer.</p>
          </div>
        </div>
        <div class="flow-step">
          <div class="flow-left"><div class="flow-dot">6</div><div class="flow-line"></div></div>
          <div class="flow-body">
            <h4>Wait for buyer confirmation</h4>
            <p>Once the buyer receives and confirms the item, your earnings (minus the 5% platform fee) are credited to your Vula Market wallet.</p>
          </div>
        </div>
      </div>

      <div class="callout callout-warn">
        <span class="callout-icon">⚠️</span>
        <div>
          <strong>Complete your profile first</strong>
          If your phone number or preferred locker is missing from your profile, buyers will be unable to get a shipping quote on your listings and won't be able to check out. Go to <strong>My Profile</strong> to fix this.
        </div>
      </div>
    </section>

    <!-- ── WALLET ── -->
    <section class="section" id="wallet">
      <div class="section-header">
        <div class="section-icon">💰</div>
        <h2>Wallet & Payouts</h2>
      </div>

      <p>Every time a buyer confirms receipt of your item, your earnings are credited to your Vula Market wallet. Go to <strong>My Wallet</strong> (<code>/wallet.php</code>) to manage your balance.</p>

      <div class="quick-card">
        <h3>How earnings work</h3>
        <div class="quick-row"><span class="label">Item sells for</span><span class="val">R 500.00</span></div>
        <div class="quick-row"><span class="label">Platform fee (5%)</span><span class="val" style="color:#F87171">− R 25.00</span></div>
        <div class="quick-row"><span class="label">Your wallet credit</span><span class="val amber">R 475.00</span></div>
      </div>

      <div class="steps" style="margin-top:1.25rem">
        <div class="step">
          <div class="step-num">1</div>
          <div class="step-body">
            <h3>View your cleared balance</h3>
            <p>Your wallet shows your total cleared balance — funds from all completed orders that are ready to withdraw.</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">2</div>
          <div class="step-body">
            <h3>Request a payout</h3>
            <p>Click <strong>Request Payout</strong> and enter your full bank details (bank name, account holder, account number, branch code, account type). Your entire cleared balance will be requested.</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">3</div>
          <div class="step-body">
            <h3>Wait for EFT processing</h3>
            <p>The admin will process your EFT within 1–2 business days and mark the payout as paid. You can view your full payout history on the wallet page.</p>
          </div>
        </div>
      </div>

      <div class="callout callout-tip">
        <span class="callout-icon">💡</span>
        <div>You can only have one pending payout request at a time. Wait for it to be processed before submitting another.</div>
      </div>
    </section>

    <!-- ── ORDER STATUSES ── -->
    <section class="section" id="order-statuses">
      <div class="section-header">
        <div class="section-icon">📊</div>
        <h2>Order Statuses Explained</h2>
      </div>

      <table class="info-table">
        <thead>
          <tr><th>Status</th><th>What it means</th><th>Who acts next</th></tr>
        </thead>
        <tbody>
          <tr>
            <td><span class="badge badge-pending">⏳ Pending</span></td>
            <td>Order created, awaiting payment confirmation from Yoco.</td>
            <td>Buyer (complete payment)</td>
          </tr>
          <tr>
            <td><span class="badge badge-escrow">🔒 Paid in Escrow</span></td>
            <td>Payment confirmed. Funds held safely in escrow. Seller must ship.</td>
            <td>Seller (book shipment & drop off)</td>
          </tr>
          <tr>
            <td><span class="badge badge-completed">✅ Completed</span></td>
            <td>Buyer confirmed receipt. Seller has been paid. Transaction done.</td>
            <td>— (complete)</td>
          </tr>
          <tr>
            <td><span class="badge badge-cancelled">✖ Cancelled</span></td>
            <td>Payment failed or was cancelled. The listing is restored automatically.</td>
            <td>Buyer (try again if desired)</td>
          </tr>
        </tbody>
      </table>
    </section>

    <!-- ── FAQ ── -->
    <section class="section" id="faq">
      <div class="section-header">
        <div class="section-icon">❓</div>
        <h2>Frequently Asked Questions</h2>
      </div>

      <div class="faq-item">
        <div class="faq-q">Can the same account be used for both buying and selling?</div>
        <div class="faq-a">Yes. Every Vula Market account can buy and sell. Simply complete your profile (phone number + preferred locker) to unlock both capabilities.</div>
      </div>

      <div class="faq-item">
        <div class="faq-q">What happens if I don't confirm receipt after my item arrives?</div>
        <div class="faq-a">The seller won't be paid until you confirm. If you're satisfied with the item, please confirm promptly so the seller receives their funds. If there's an issue, contact the seller via the order chat first.</div>
      </div>

      <div class="faq-item">
        <div class="faq-q">Can I cancel an order after paying?</div>
        <div class="faq-a">Orders in Paid in Escrow status cannot be self-cancelled by buyers or sellers. Contact the admin via the order chat if you need to resolve an issue.</div>
      </div>

      <div class="faq-item">
        <div class="faq-q">What parcel size should I choose when listing?</div>
        <div class="faq-a">Choose the smallest TCG PUDO locker size that your fully packaged item (including wrapping/box) will fit into. Undersizing may mean your parcel doesn't fit at the locker. When in doubt, size up.</div>
      </div>

      <div class="faq-item">
        <div class="faq-q">How long does delivery take?</div>
        <div class="faq-a">Delivery times depend on The Courier Guy's schedule and your location. Typically 1–3 business days within major metros. Use the tracking reference in your order chat to follow your parcel.</div>
      </div>

      <div class="faq-item">
        <div class="faq-q">What payment methods are accepted?</div>
        <div class="faq-a">All major South African debit and credit cards are accepted via Yoco's secure hosted checkout. No card details are stored on Vula Market's servers.</div>
      </div>

      <div class="faq-item">
        <div class="faq-q">When is the 5% platform fee charged?</div>
        <div class="faq-a">The fee is deducted when the buyer confirms receipt and your wallet is credited. It is not charged on the shipping cost — only on the item sale price.</div>
      </div>

      <div class="faq-item">
        <div class="faq-q">Can I change my email address?</div>
        <div class="faq-a">No. Your email address is fixed after registration and cannot be changed. Choose carefully when signing up.</div>
      </div>

      <div class="faq-item">
        <div class="faq-q">What do I do if the buyer never confirms receipt?</div>
        <div class="faq-a">Send a message via the order chat to follow up. If the issue persists, contact the Vula Market admin who can manually complete the order if delivery has been confirmed.</div>
      </div>
    </section>

    <!-- ── ADMIN OVERVIEW ── -->
    <section class="section" id="admin-overview">
      <div class="section-header">
        <div class="section-icon">🔒</div>
        <h2>Admin Section Overview</h2>
      </div>

      <p>The Vula Market admin panel is restricted to accounts with the <strong>admin role</strong>. It gives you full visibility and control over orders, payouts, users, and shipments across the entire platform.</p>

      <div class="callout callout-danger" style="margin-top:1.25rem">
        <span class="callout-icon">🔒</span>
        <div>
          <strong>Admin access is role-gated</strong>
          Any attempt to access <code>/admin/</code> pages without an admin account will be blocked and redirected. Never share your admin credentials.
        </div>
      </div>

      <table class="info-table" style="margin-top:1.25rem">
        <thead>
          <tr><th>Page</th><th>URL</th><th>What you can do</th></tr>
        </thead>
        <tbody>
          <tr>
            <td><strong>Admin Dashboard</strong></td>
            <td><code>/admin/index.php</code></td>
            <td>View platform stats, manage all orders, process seller payout requests</td>
          </tr>
          <tr>
            <td><strong>Shipments</strong></td>
            <td><code>/admin/shipments.php</code></td>
            <td>Monitor all booked TCG shipments with live tracking status, view waybills</td>
          </tr>
          <tr>
            <td><strong>Order Detail</strong></td>
            <td><code>/orders/view.php?id=X</code></td>
            <td>View full order info, read/write order chat, access buyer & seller contact details</td>
          </tr>
        </tbody>
      </table>
    </section>

    <!-- ── ADMIN DASHBOARD ── -->
    <section class="section" id="admin-dashboard">
      <div class="section-header">
        <div class="section-icon">📈</div>
        <h2>Dashboard & Stats</h2>
      </div>

      <p>The dashboard (<code>/admin/index.php</code>) opens with a live stats row giving you a pulse on the platform at a glance.</p>

      <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:.75rem;margin:1.25rem 0">
        <div class="step" style="flex-direction:column;gap:.4rem">
          <div style="font-family:'Syne',sans-serif;font-weight:800;font-size:1rem;color:var(--dark)">Total Orders</div>
          <p style="font-size:.88rem;color:#52525B">All orders ever placed on the platform, regardless of status.</p>
        </div>
        <div class="step" style="flex-direction:column;gap:.4rem">
          <div style="font-family:'Syne',sans-serif;font-weight:800;font-size:1rem;color:var(--dark)">In Escrow</div>
          <p style="font-size:.88rem;color:#52525B">Total rand value of all orders currently in <em>paid_in_escrow</em> — funds being held on behalf of sellers.</p>
        </div>
        <div class="step" style="flex-direction:column;gap:.4rem">
          <div style="font-family:'Syne',sans-serif;font-weight:800;font-size:1rem;color:var(--dark)">Completed GMV</div>
          <p style="font-size:.88rem;color:#52525B">Gross Merchandise Value — total rand of all successfully completed sales.</p>
        </div>
        <div class="step" style="flex-direction:column;gap:.4rem">
          <div style="font-family:'Syne',sans-serif;font-weight:800;font-size:1rem;color:var(--dark)">Pending Payouts</div>
          <p style="font-size:.88rem;color:#52525B">Number of seller payout requests waiting to be processed via EFT. This should stay near zero.</p>
        </div>
      </div>

      <div class="callout callout-tip">
        <span class="callout-icon">💡</span>
        <div>Keep an eye on <strong>Pending Payouts</strong> daily. Sellers expect EFT within 1–2 business days of requesting a withdrawal.</div>
      </div>
    </section>

    <!-- ── ADMIN PAYOUTS ── -->
    <section class="section" id="admin-payouts">
      <div class="section-header">
        <div class="section-icon">💸</div>
        <h2>Processing Seller Payouts</h2>
      </div>

      <p>Sellers request EFT payouts from their wallet. As admin, you process these manually and mark them as paid once the transfer is done.</p>

      <div class="steps">
        <div class="step">
          <div class="step-num">1</div>
          <div class="step-body">
            <h3>Open the Admin Dashboard</h3>
            <p>Go to <code>/admin/index.php</code>. The <strong>Payout Requests</strong> table at the top of the page lists all pending and historical payouts.</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">2</div>
          <div class="step-body">
            <h3>Review the payout details</h3>
            <p>Each row shows the seller's name and email, the <strong>amount</strong> to transfer, and their full <strong>bank details</strong> (bank name, account holder, account number, branch code, account type) exactly as they entered them.</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">3</div>
          <div class="step-body">
            <h3>Perform the EFT in your banking app</h3>
            <p>Log into your business banking portal and send the exact amount to the seller's account. Use the payout ID (e.g. <em>Payout #12</em>) as the payment reference so it's traceable.</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">4</div>
          <div class="step-body">
            <h3>Click "✓ Mark Paid"</h3>
            <p>Once the EFT is done, click the <strong>Mark Paid</strong> button on the payout row. The system records the payment date, updates the payout status to <em>paid</em>, and the seller sees the update in their wallet history.</p>
          </div>
        </div>
      </div>

      <div class="callout callout-warn">
        <span class="callout-icon">⚠️</span>
        <div>
          <strong>Mark Paid only after the EFT is sent</strong>
          Clicking Mark Paid is irreversible. The seller's wallet balance has already been zeroed when they submitted the request — marking it as paid is your confirmation that the money has left your account.
        </div>
      </div>

      <div class="quick-card" style="margin-top:1.5rem">
        <h3>Payout status reference</h3>
        <div class="quick-row"><span class="label">pending</span><span class="val">Seller has requested a payout — needs your action</span></div>
        <div class="quick-row"><span class="label">paid</span><span class="val amber">EFT processed and confirmed by admin</span></div>
      </div>
    </section>

    <!-- ── ADMIN ORDERS ── -->
    <section class="section" id="admin-orders">
      <div class="section-header">
        <div class="section-icon">📦</div>
        <h2>Managing Orders</h2>
      </div>

      <p>The <strong>All Orders</strong> table below the payout section lists every order on the platform — newest first — with buyer, seller, total, status, and a direct link to the order detail page.</p>

      <div class="steps">
        <div class="step">
          <div class="step-num">1</div>
          <div class="step-body">
            <h3>View any order</h3>
            <p>Click <strong>View</strong> on any row to open the full order detail page. As admin, you can see both the buyer and seller's contact details, the full order chat history, shipment info, and all amounts.</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">2</div>
          <div class="step-body">
            <h3>Intervene via order chat</h3>
            <p>If a buyer and seller have a dispute or a buyer hasn't confirmed receipt, you can write a message in the order chat as admin. Use this to coordinate resolution between parties.</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">3</div>
          <div class="step-body">
            <h3>Manually complete stuck orders</h3>
            <p>If a buyer is unresponsive after confirmed delivery, the admin can manually update the order status in the database to <code>completed</code>, which credits the seller's wallet. This should only be done when delivery is verified via TCG tracking.</p>
          </div>
        </div>
      </div>

      <div class="callout callout-tip">
        <span class="callout-icon">💡</span>
        <div>Filter the orders table visually by scanning the <strong>Status</strong> column. Orders stuck in <em>paid_in_escrow</em> for more than a week without a shipment booked usually need admin attention.</div>
      </div>
    </section>

    <!-- ── ADMIN SHIPMENTS ── -->
    <section class="section" id="admin-shipments">
      <div class="section-header">
        <div class="section-icon">🚚</div>
        <h2>Shipments Dashboard</h2>
      </div>

      <p>The shipments page (<code>/admin/shipments.php</code>) shows every parcel that has been booked with TCG, with live tracking status pulled directly from the TCG Locker API.</p>

      <div class="steps" style="margin-top:1rem">
        <div class="step">
          <div class="step-num">1</div>
          <div class="step-body">
            <h3>Read the stats row</h3>
            <p>At the top you'll see: <strong>Total Shipped</strong>, <strong>Awaiting Booking</strong> (paid orders where the seller hasn't booked yet), <strong>In Transit</strong>, <strong>Delivered</strong>, and <strong>Exceptions</strong> (failed or problem parcels).</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">2</div>
          <div class="step-body">
            <h3>Monitor the live status column</h3>
            <p>Each row shows the live TCG status badge fetched from the API — colour-coded for quick scanning. Green = delivered, blue = in transit, yellow = awaiting collection, red = exception.</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">3</div>
          <div class="step-body">
            <h3>Track or print a waybill</h3>
            <p>Use the <strong>🔍 Track</strong> button to open the tracking page for that order. Use the <strong>🖨 Waybill</strong> button to open a printable waybill for that shipment — useful if a seller needs a replacement copy.</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">4</div>
          <div class="step-body">
            <h3>Act on exceptions</h3>
            <p>If the TCG status shows an exception or failure, contact TCG directly with the waybill number. Then notify the buyer and seller via the order chat with an update.</p>
          </div>
        </div>
      </div>

      <div class="callout callout-warn">
        <span class="callout-icon">⚠️</span>
        <div>
          <strong>Live status is capped at 20 shipments per page load</strong>
          To avoid hitting TCG API rate limits, only the 20 most recent shipments fetch live status on each page load. Refresh the page to update. Older shipments show their last known database status.
        </div>
      </div>

      <div class="callout callout-danger" style="margin-top:.75rem">
        <span class="callout-icon">🔴</span>
        <div>
          <strong>TCG API not configured?</strong>
          If the API status card at the bottom of the shipments page shows red, add your <code>TCG_API_KEY</code> to <code>config/config.php</code>. Without it, shipments cannot be booked and live tracking is unavailable.
        </div>
      </div>
    </section>

    <!-- ── ADMIN GO-LIVE CHECKLIST ── -->
    <section class="section" id="admin-checklist">
      <div class="section-header">
        <div class="section-icon">✅</div>
        <h2>Go-Live Checklist</h2>
      </div>

      <p>Before making Vula Market publicly accessible, work through this checklist. Each item is a real production risk if skipped.</p>

      <div class="steps">
        <div class="step">
          <div class="step-num">1</div>
          <div class="step-body">
            <h3>Swap to live API keys</h3>
            <p>Replace <code>sk_test_...</code> with your Yoco <strong>live secret key</strong> (<code>sk_live_...</code>) in <code>config/config.php</code>. Do the same for TCG — update <code>TCG_API_KEY</code> and change <code>TCG_API_BASE</code> from the sandbox URL to the production endpoint.</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">2</div>
          <div class="step-body">
            <h3>Change all default passwords</h3>
            <p>The seed script creates accounts with passwords like <code>admin123</code>. Log into each seeded account (admin, buyer test, seller test) and change the passwords immediately, or delete the test accounts entirely.</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">3</div>
          <div class="step-body">
            <h3>Enable HTTPS</h3>
            <p>Install an SSL certificate (Let's Encrypt is free) and redirect all HTTP traffic to HTTPS. Update <code>APP_URL</code> in <code>config.php</code> to use <code>https://</code>. Payment providers require HTTPS on production.</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">4</div>
          <div class="step-body">
            <h3>Protect the database directory</h3>
            <p>Confirm that the Apache config denies direct HTTP access to <code>/db/</code>. The SQLite file must never be downloadable via browser. Test by visiting <code>https://yourdomain.co.za/db/vulamarket.sqlite</code> — it must return a 403.</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">5</div>
          <div class="step-body">
            <h3>Turn off PHP error display</h3>
            <p>In <code>php.ini</code> (or via <code>.htaccess</code>), set <code>display_errors = Off</code> and <code>log_errors = On</code>. Stack traces on screen expose file paths and logic to attackers.</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">6</div>
          <div class="step-body">
            <h3>Set up database backups</h3>
            <p>Schedule a daily cron job to copy <code>db/vulamarket.sqlite</code> to a remote location (S3, Backblaze, or another server). SQLite is a single file — one corrupt disk and everything is gone without a backup.</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">7</div>
          <div class="step-body">
            <h3>Set file permissions correctly</h3>
            <p><code>/uploads/</code> and <code>/db/</code> should be writable by the web server user only (<code>chmod 755</code> on directories, <code>chmod 644</code> on files). Don't leave them world-writable (<code>777</code>) in production.</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">8</div>
          <div class="step-body">
            <h3>Test the full buy/sell flow end-to-end</h3>
            <p>With live keys active, place a real test order with a small amount (R1 if Yoco allows), ship it, and confirm receipt. Verify the seller wallet is credited and a payout request can be submitted and marked paid. Only go live after this passes.</p>
          </div>
        </div>
      </div>
    </section>

    <div class="guide-footer">
      <p>© 2026 <strong>Vula Market</strong> · South Africa's simplest C2C marketplace</p>
      <p style="margin-top:.4rem">Secured by Yoco · Shipped by The Courier Guy PUDO</p>
    </div>

  </main>
</div>

<script>
  // FAQ accordion
  document.querySelectorAll('.faq-q').forEach(function(q) {
    q.addEventListener('click', function() {
      var item = q.closest('.faq-item');
      var wasOpen = item.classList.contains('open');
      document.querySelectorAll('.faq-item').forEach(function(i) { i.classList.remove('open'); });
      if (!wasOpen) item.classList.add('open');
    });
  });

  // Active nav link on scroll
  var sections = document.querySelectorAll('section[id]');
  var navLinks = document.querySelectorAll('.nav-link');

  function setActive() {
    var scrollY = window.scrollY + 100;
    sections.forEach(function(s) {
      if (scrollY >= s.offsetTop && scrollY < s.offsetTop + s.offsetHeight) {
        navLinks.forEach(function(l) { l.classList.remove('active'); });
        var link = document.querySelector('.nav-link[href="#' + s.id + '"]');
        if (link) link.classList.add('active');
      }
    });
  }

  window.addEventListener('scroll', setActive);
  setActive();
</script>

</body>
</html>
