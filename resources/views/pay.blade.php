<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ ucfirst($provider) }} Automated Payment - Order #{{ $order_id }}</title>
  
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- FontAwesome 6 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  @php
    $brandColors = [
      'bkash'  => ['primary' => '#e2136e', 'dark' => '#9f1239', 'light' => '#fdf2f8', 'border' => '#fbcfe8'],
      'nagad'  => ['primary' => '#f04923', 'dark' => '#9a3412', 'light' => '#fff7ed', 'border' => '#fed7aa'],
      'rocket' => ['primary' => '#8c2d82', 'dark' => '#581c87', 'light' => '#faf5ff', 'border' => '#e9d5ff'],
    ];
    $theme = $brandColors[$provider] ?? $brandColors['bkash'];
  @endphp

  <style>
    :root {
      --mfs-primary: {{ $theme['primary'] }};
      --mfs-dark: {{ $theme['dark'] }};
      --mfs-light: {{ $theme['light'] }};
      --mfs-border: {{ $theme['border'] }};
    }
    body {
      background: #f1f5f9;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem 0.75rem;
    }
    .mfs-card {
      background: #ffffff;
      border-radius: 18px;
      box-shadow: 0 20px 45px rgba(15, 23, 42, 0.12);
      border: 1px solid #e2e8f0;
      max-width: 540px;
      width: 100%;
      overflow: hidden;
      animation: fadeIn 0.4s ease-out;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(12px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .mfs-header {
      background: linear-gradient(135deg, var(--mfs-primary) 0%, var(--mfs-dark) 100%);
      color: #ffffff;
      padding: 1.75rem 1.5rem 1.5rem;
      text-align: center;
      position: relative;
    }
    .mfs-header .badge-pill {
      background: rgba(255, 255, 255, 0.2);
      backdrop-filter: blur(8px);
      color: #ffffff;
      font-size: 0.75rem;
      padding: 0.35rem 0.85rem;
      border-radius: 50px;
      letter-spacing: 0.5px;
      font-weight: 600;
    }
    .pulse-radar {
      position: relative;
      width: 60px;
      height: 60px;
      margin: 0 auto 0.75rem;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .pulse-radar .circle {
      position: absolute;
      width: 100%;
      height: 100%;
      border-radius: 50%;
      background: var(--mfs-primary);
      opacity: 0.25;
      animation: pulseAnim 2s infinite ease-out;
    }
    .pulse-radar .circle:nth-child(2) {
      animation-delay: 0.75s;
    }
    .pulse-radar .center-icon {
      position: relative;
      z-index: 2;
      width: 44px;
      height: 44px;
      border-radius: 50%;
      background: var(--mfs-primary);
      color: #ffffff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.25rem;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    @keyframes pulseAnim {
      0% { transform: scale(0.6); opacity: 0.6; }
      100% { transform: scale(1.6); opacity: 0; }
    }
    .copy-box {
      background: #f8fafc;
      border: 1px dashed #cbd5e1;
      border-radius: 12px;
      padding: 0.75rem 1rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.5rem;
    }
    .countdown-badge {
      background: #fef3c7;
      color: #92400e;
      border: 1px solid #fde68a;
      border-radius: 50px;
      padding: 0.35rem 0.9rem;
      font-size: 0.82rem;
      font-weight: 700;
    }
    .step-pill {
      width: 24px;
      height: 24px;
      border-radius: 50%;
      background: var(--mfs-light);
      color: var(--mfs-dark);
      font-weight: 700;
      font-size: 0.78rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-right: 0.5rem;
      border: 1px solid var(--mfs-border);
    }
  </style>
</head>
<body>

<div class="mfs-card">
  <!-- Header -->
  <div class="mfs-header">
    <span class="badge-pill mb-2 d-inline-block">
      <i class="fa-solid fa-bolt me-1"></i> AUTOMATED SMS BOT VERIFICATION
    </span>
    <h3 class="fw-bold mb-1">{{ ucfirst($provider) }} Payment</h3>
    <p class="mb-0 opacity-75 small">Order #{{ $order_id }} &bull; Instant Auto-Confirmation</p>
  </div>

  <div class="p-4">
    <!-- Amount Banner -->
    <div class="p-3 rounded-3 text-center mb-4" style="background: var(--mfs-light); border: 1px solid var(--mfs-border);">
      <small class="text-uppercase fw-bold text-muted" style="font-size: 0.75rem; letter-spacing: 0.5px;">Payable Amount</small>
      <div class="display-6 fw-bold" style="color: var(--mfs-dark);">
        ৳{{ number_format($payableAmount, 2) }}
      </div>
      <div class="d-flex align-items-center justify-content-center gap-2 mt-2">
        <span class="countdown-badge" id="countdown_badge">
          <i class="fa-regular fa-clock me-1"></i> <span id="countdown_timer">15:00</span>
        </span>
        <button type="button" class="btn btn-sm btn-link text-decoration-none py-0 text-muted" onclick="extendCountdown()" title="Add 15 more minutes">
          <i class="fa-solid fa-plus-circle me-1"></i> Extend
        </button>
      </div>
    </div>

    <!-- Live Radar Pulse -->
    <div class="text-center mb-4">
      <div class="pulse-radar">
        <div class="circle"></div>
        <div class="circle"></div>
        <div class="center-icon">
          <i class="fa-solid fa-satellite-dish"></i>
        </div>
      </div>
      <h6 class="fw-bold mb-1" style="color: var(--mfs-dark);">Listening for your transfer SMS...</h6>
      <small class="text-muted d-block" id="status_text">
        Send money from your {{ ucfirst($provider) }} app or dial {{ $gatewayConfig['ussd_code'] ?? '*167#' }}. No TrxID typing needed!
      </small>
    </div>

    <!-- Payment Steps & Details -->
    <div class="mb-4">
      <!-- Step 1: Wallet Number -->
      <div class="mb-3">
        <label class="form-label small fw-bold text-muted mb-1">
          <span class="step-pill">1</span> Send Money to this {{ ucfirst($provider) }} {{ $gatewayConfig['type'] ?? 'Personal' }} Number:
        </label>
        <div class="copy-box">
          <div>
            <span class="badge me-1" style="background: var(--mfs-primary); color: #fff;">{{ ucfirst($provider) }}</span>
            <strong class="font-monospace fs-5 text-dark" id="wallet_number">{{ $gatewayConfig['number'] ?? '01735254295' }}</strong>
          </div>
          <button type="button" class="btn btn-sm btn-outline-secondary" onclick="copyText('{{ $gatewayConfig['number'] ?? '01735254295' }}', 'Wallet number copied!')">
            <i class="fa-regular fa-copy me-1"></i> Copy
          </button>
        </div>
      </div>

      <!-- Step 2: Reference -->
      <div class="mb-3">
        <label class="form-label small fw-bold text-muted mb-1">
          <span class="step-pill">2</span> Type Reference in {{ ucfirst($provider) }} App:
        </label>
        <div class="copy-box">
          <div>
            <strong class="font-monospace fs-6 text-dark">{{ $reference }}</strong>
          </div>
          <button type="button" class="btn btn-sm btn-outline-secondary" onclick="copyText('{{ $reference }}', 'Reference copied!')">
            <i class="fa-regular fa-copy me-1"></i> Copy
          </button>
        </div>
      </div>
    </div>

    <!-- Manual Fallback Accordion -->
    <div class="accordion mb-3" id="fallbackAccordion">
      <div class="accordion-item border rounded-3 overflow-hidden">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed py-2 px-3 small text-muted bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseManual">
            <i class="fa-solid fa-keyboard me-2"></i> SMS delayed? Click here to enter Transaction ID manually
          </button>
        </h2>
        <div id="collapseManual" class="accordion-collapse collapse" data-bs-parent="#fallbackAccordion">
          <div class="accordion-body p-3 bg-white">
            <form id="manualVerifyForm" onsubmit="submitManualTrx(event)">
              <div class="input-group">
                <input type="text" class="form-control font-monospace text-uppercase" id="manual_trx_id" placeholder="e.g. 72J9KA21" required>
                <button type="submit" class="btn btn-dark" id="manual_btn">
                  <i class="fa-solid fa-check me-1"></i> Verify TrxID
                </button>
              </div>
              <div id="manual_feedback" class="mt-2 small" style="display: none;"></div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Cancel / Back -->
    <div class="text-center pt-2">
      <a href="{{ config('mfs-sms.order.cancel_url', '/checkout') }}" class="text-muted small text-decoration-none">
        <i class="fa-solid fa-arrow-left me-1"></i> Return to Checkout
      </a>
    </div>
  </div>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
  const orderId = "{{ $order_id }}";
  const provider = "{{ $provider }}";
  const statusUrl = "{{ route('mfs.sms.status', ['order_id' => $order_id]) }}";
  const manualVerifyUrl = "{{ route('mfs.sms.manual_verify', ['order_id' => $order_id]) }}";
  const extendTimerUrl = "{{ route('mfs.sms.extend_timer', ['order_id' => $order_id]) }}";
  const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  let expiresAt = {{ $expiresAt }};
  let pollInterval = null;

  // Non-intrusive Toast Helper
  function showToast(msg) {
    let toast = document.getElementById('mfs_toast');
    if (!toast) {
      toast = document.createElement('div');
      toast.id = 'mfs_toast';
      toast.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#0f172a;color:#fff;padding:8px 18px;border-radius:50px;font-size:0.85rem;box-shadow:0 8px 24px rgba(0,0,0,0.25);z-index:9999;transition:opacity 0.3s ease;';
      document.body.appendChild(toast);
    }
    toast.innerHTML = '<i class="fa-solid fa-check me-1 text-success"></i> ' + msg;
    toast.style.opacity = '1';
    clearTimeout(window.__toastTimeout);
    window.__toastTimeout = setTimeout(() => {
      toast.style.opacity = '0';
    }, 2500);
  }

  // Clipboard copy helper
  function copyText(text, msg) {
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(text).then(function() {
        showToast(msg || 'Copied to clipboard!');
      }).catch(function() {
        fallbackCopyText(text, msg);
      });
    } else {
      fallbackCopyText(text, msg);
    }
  }

  function fallbackCopyText(text, msg) {
    const el = document.createElement('textarea');
    el.value = text;
    el.style.position = 'fixed';
    el.style.opacity = '0';
    document.body.appendChild(el);
    el.select();
    try {
      document.execCommand('copy');
      showToast(msg || 'Copied to clipboard!');
    } catch (err) {
      alert(msg || 'Copied to clipboard!');
    }
    document.body.removeChild(el);
  }

  // Countdown Timer
  function updateCountdown() {
    const now = Math.floor(Date.now() / 1000);
    const diff = expiresAt - now;

    if (diff <= 0) {
      document.getElementById('countdown_timer').innerText = "Expired";
      document.getElementById('status_text').innerText = "Session expired. If you transferred, please enter your TrxID below.";
      
      const collapseEl = document.getElementById('collapseManual');
      if (collapseEl && !collapseEl.classList.contains('show') && window.bootstrap) {
        const bsCollapse = bootstrap.Collapse.getOrCreateInstance(collapseEl);
        bsCollapse.show();
      }
      return;
    }

    const minutes = Math.floor(diff / 60);
    const seconds = diff % 60;
    document.getElementById('countdown_timer').innerText = 
      (minutes < 10 ? '0' : '') + minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
  }
  setInterval(updateCountdown, 1000);
  updateCountdown();

  // Extend Timer
  function extendCountdown() {
    fetch(extendTimerUrl, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json'
      }
    }).then(res => res.json()).then(data => {
      if (data.new_expiry) {
        expiresAt = data.new_expiry;
        updateCountdown();
        showToast('Added 15 more minutes to your session!');
      }
    });
  }

  // Real-time Status Polling (every 3 seconds)
  function startPolling() {
    pollInterval = setInterval(function() {
      fetch(statusUrl, {
        headers: { 'Accept': 'application/json' }
      })
      .then(res => res.json())
      .then(data => {
        if (data.status === 'paid') {
          clearInterval(pollInterval);
          document.getElementById('status_text').innerHTML = 
            '<span class="text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i> Payment Verified! Redirecting...</span>';
          setTimeout(function() {
            window.location.href = data.redirect_url;
          }, 1200);
        }
      })
      .catch(err => console.log('Polling...', err));
    }, 3000);
  }
  startPolling();

  // Manual TrxID Verification Form
  function submitManualTrx(e) {
    e.preventDefault();
    const trxId = document.getElementById('manual_trx_id').value.trim();
    const feedback = document.getElementById('manual_feedback');
    const btn = document.getElementById('manual_btn');

    if (!trxId) return;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Verifying...';
    feedback.style.display = 'none';

    fetch(manualVerifyUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json'
      },
      body: JSON.stringify({ trx_id: trxId, provider: provider })
    })
    .then(res => res.json())
    .then(data => {
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Verify TrxID';
      feedback.style.display = 'block';

      if (data.success) {
        feedback.className = 'alert alert-success mt-2 p-2 small';
        feedback.innerHTML = '<i class="fa-solid fa-circle-check me-1"></i> ' + data.message;
        setTimeout(function() {
          window.location.href = data.redirect_url;
        }, 1200);
      } else {
        let errMsg = data.message;
        if (data.errors && typeof data.errors === 'object') {
          const keys = Object.keys(data.errors);
          if (keys.length > 0 && Array.isArray(data.errors[keys[0]])) {
            errMsg = data.errors[keys[0]][0];
          }
        }
        feedback.className = 'alert alert-danger mt-2 p-2 small';
        feedback.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1"></i> ' + (errMsg || 'Verification failed. Please check your TrxID.');
      }
    })
    .catch(err => {
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Verify TrxID';
      feedback.style.display = 'block';
      feedback.className = 'alert alert-danger mt-2 p-2 small';
      feedback.innerHTML = 'Error communicating with server.';
    });
  }
</script>

</body>
</html>
