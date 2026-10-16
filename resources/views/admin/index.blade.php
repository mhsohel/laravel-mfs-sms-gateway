<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>MFS SMS Gateway Credentials & Control Panel</title>

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- FontAwesome 6 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    body {
      background-color: #f8fafc;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      color: #334155;
    }
    .top-navbar {
      background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
      color: #fff;
      padding: 1rem 0;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }
    .stat-card {
      background: #ffffff;
      border-radius: 14px;
      padding: 1.25rem 1.5rem;
      border: 1px solid #e2e8f0;
      box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .stat-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
    }
    .gateway-card {
      background: #ffffff;
      border-radius: 16px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05);
      overflow: hidden;
      margin-bottom: 1.5rem;
    }
    .gateway-card .card-header {
      padding: 1.1rem 1.5rem;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid #f1f5f9;
    }
    .bg-bkash {
      background: linear-gradient(135deg, #e2136e 0%, #9f1239 100%);
      color: #ffffff;
    }
    .bg-nagad {
      background: linear-gradient(135deg, #f04923 0%, #9a3412 100%);
      color: #ffffff;
    }
    .bg-rocket {
      background: linear-gradient(135deg, #8c2d82 0%, #581c87 100%);
      color: #ffffff;
    }
    .nav-tabs .nav-link {
      font-weight: 600;
      color: #64748b;
      border: none;
      border-bottom: 2px solid transparent;
      padding: 0.75rem 1.25rem;
    }
    .nav-tabs .nav-link.active {
      color: #0f172a;
      border-bottom: 2px solid #0284c7;
      background: transparent;
    }
    .form-label {
      font-size: 0.82rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: #475569;
      margin-bottom: 0.35rem;
    }
    .copy-input-group {
      background: #f1f5f9;
      border-radius: 8px;
    }
    .copy-input-group input {
      font-family: monospace;
      font-size: 0.85rem;
      background: transparent;
      border: none;
    }
    .badge-pill {
      border-radius: 50px;
      padding: 0.35rem 0.75rem;
      font-size: 0.75rem;
      font-weight: 700;
    }
    .form-switch .form-check-input {
      width: 2.75em;
      height: 1.4em;
      cursor: pointer;
    }
  </style>
</head>
<body>

<!-- Top Navigation -->
<header class="top-navbar mb-4">
  <div class="container d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-3">
      <div style="background: rgba(255,255,255,0.15); width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
        <i class="fa-solid fa-tower-broadcast text-warning fs-5"></i>
      </div>
      <div>
        <h5 class="mb-0 fw-bold">MFS SMS Gateway Credentials & Control</h5>
        <small class="opacity-75" style="font-size: 0.8rem;">Automated bKash &bull; Nagad &bull; Rocket SMS Forwarder Management</small>
      </div>
    </div>
    <div class="d-flex align-items-center gap-2">
      <a href="{{ url('admin/paymentgeteway/manage') }}" class="btn btn-sm btn-outline-light">
        <i class="fa-solid fa-arrow-left me-1"></i> Admin Panel
      </a>
      <a href="{{ route('mfs.sms.admin.transactions') }}" class="btn btn-sm btn-outline-light">
        <i class="fa-solid fa-list-check me-1"></i> SMS Transactions Log
      </a>
      <a href="{{ url('/') }}" class="btn btn-sm btn-secondary" target="_blank">
        <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> View Website
      </a>
    </div>
  </div>
</header>

<main class="container mb-5">
  <!-- Flash Alert -->
  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4" role="alert">
      <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <!-- Top Metrics Overview -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="stat-card">
        <div class="text-muted small fw-bold text-uppercase">Forwarded SMS</div>
        <div class="fs-3 fw-bold text-dark mt-1">{{ number_format($stats['total_transactions']) }}</div>
        <small class="text-success fw-bold"><i class="fa-solid fa-arrow-up me-1"></i> {{ $stats['today_received'] }} today</small>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card">
        <div class="text-muted small fw-bold text-uppercase">Auto-Matched Orders</div>
        <div class="fs-3 fw-bold text-primary mt-1">{{ number_format($stats['matched_transactions']) }}</div>
        <small class="text-muted">Instant verifications</small>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card">
        <div class="text-muted small fw-bold text-uppercase">Unmatched SMS</div>
        <div class="fs-3 fw-bold text-warning mt-1">{{ number_format($stats['unmatched_transactions']) }}</div>
        <small class="text-muted">Pending matching</small>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card">
        <div class="text-muted small fw-bold text-uppercase">Total Collected Volume</div>
        <div class="fs-3 fw-bold text-success mt-1">৳{{ number_format($stats['total_volume'], 2) }}</div>
        <small class="text-muted">Verified MFS deposits</small>
      </div>
    </div>
  </div>

  <!-- Main Navigation Tabs -->
  <ul class="nav nav-tabs mb-4" id="adminTabs" role="tablist">
    <li class="nav-item">
      <button class="nav-link active" id="gateways-tab" data-bs-toggle="tab" data-bs-target="#gatewaysTab" type="button">
        <i class="fa-solid fa-wallet me-2"></i> MFS Gateways & Credentials
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link" id="simulator-tab" data-bs-toggle="tab" data-bs-target="#simulatorTab" type="button">
        <i class="fa-solid fa-vial me-2"></i> SMS Test Simulator
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link" id="setup-tab" data-bs-toggle="tab" data-bs-target="#setupTab" type="button">
        <i class="fa-solid fa-mobile-screen-button me-2"></i> Android App Setup Guide
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link" id="recent-tab" data-bs-toggle="tab" data-bs-target="#recentTab" type="button">
        <i class="fa-solid fa-clock-rotate-left me-2"></i> Recent SMS Transactions
      </button>
    </li>
  </ul>

  <div class="tab-content" id="adminTabContent">
    <!-- TAB 1: GATEWAYS CREDENTIALS -->
    <div class="tab-pane fade show active" id="gatewaysTab">
      <div class="row g-4">
        
        <!-- bKash Card -->
        @php $bkash = $gateways['bkash'] ?? []; @endphp
        <div class="col-lg-6">
          <div class="gateway-card">
            <div class="card-header bg-bkash">
              <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-money-bill-transfer fs-5"></i>
                <span class="fs-5">bKash Direct</span>
              </div>
              <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="toggle_bkash" onchange="toggleProvider('bkash')" {{ !empty($bkash['enabled']) ? 'checked' : '' }}>
              </div>
            </div>
            <div class="card-body p-4">
              <form action="{{ route('mfs.sms.admin.save', ['provider' => 'bkash']) }}" method="POST">
                @csrf
                <div class="row g-3">
                  <div class="col-md-7">
                    <label class="form-label">bKash Wallet Number</label>
                    <div class="input-group">
                      <span class="input-group-text"><i class="fa-solid fa-phone"></i></span>
                      <input type="text" name="number" class="form-control fw-bold font-monospace" value="{{ $bkash['number'] ?? '01735254295' }}" required>
                    </div>
                  </div>
                  <div class="col-md-5">
                    <label class="form-label">Account Type</label>
                    <select name="type" class="form-select">
                      <option value="Personal" {{ ($bkash['type'] ?? '') == 'Personal' ? 'selected' : '' }}>Personal (Send Money)</option>
                      <option value="Merchant" {{ ($bkash['type'] ?? '') == 'Merchant' ? 'selected' : '' }}>Merchant (Payment)</option>
                      <option value="Agent" {{ ($bkash['type'] ?? '') == 'Agent' ? 'selected' : '' }}>Agent (Cash In)</option>
                    </select>
                  </div>

                  <div class="col-12">
                    <label class="form-label">Webhook Secret Token</label>
                    <div class="input-group">
                      <span class="input-group-text"><i class="fa-solid fa-key"></i></span>
                      <input type="text" name="webhook_token" id="token_bkash" class="form-control font-monospace" value="{{ $bkash['webhook_token'] ?? 'mfs_secret_token_bkash_2026' }}" required>
                      <button type="button" class="btn btn-outline-secondary" onclick="generateToken('bkash')" title="Generate New Secure Token">
                        <i class="fa-solid fa-arrows-rotate me-1"></i> New
                      </button>
                    </div>
                    <small class="text-muted" style="font-size: 0.76rem;">Match this token in your Android SMS Forwarder webhook URL query parameter.</small>
                  </div>

                  <div class="col-12">
                    <label class="form-label">Android Webhook URL (Copy to SMS App)</label>
                    <div class="input-group copy-input-group p-1 border">
                      <input type="text" id="url_bkash" class="form-control" readonly value="{{ $webhookUrls['bkash'] }}?token={{ $bkash['webhook_token'] ?? 'mfs_secret_token_bkash_2026' }}">
                      <button type="button" class="btn btn-sm btn-dark" onclick="copyToClipboard('url_bkash')">
                        <i class="fa-regular fa-copy me-1"></i> Copy
                      </button>
                    </div>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label">USSD Dial Code</label>
                    <input type="text" name="ussd_code" class="form-control font-monospace" value="{{ $bkash['ussd_code'] ?? '*247#' }}">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <div class="pt-2">
                      <span class="badge {{ !empty($bkash['enabled']) ? 'bg-success' : 'bg-secondary' }} px-3 py-2" id="badge_bkash">
                        {{ !empty($bkash['enabled']) ? 'Active & Receiving' : 'Disabled' }}
                      </span>
                    </div>
                  </div>

                  <div class="col-12 pt-2">
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold" style="background: #e2136e; border-color: #e2136e;">
                      <i class="fa-solid fa-floppy-disk me-1"></i> Save bKash Credentials
                    </button>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- Nagad Card -->
        @php $nagad = $gateways['nagad'] ?? []; @endphp
        <div class="col-lg-6">
          <div class="gateway-card">
            <div class="card-header bg-nagad">
              <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-bolt fs-5"></i>
                <span class="fs-5">Nagad Direct</span>
              </div>
              <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="toggle_nagad" onchange="toggleProvider('nagad')" {{ !empty($nagad['enabled']) ? 'checked' : '' }}>
              </div>
            </div>
            <div class="card-body p-4">
              <form action="{{ route('mfs.sms.admin.save', ['provider' => 'nagad']) }}" method="POST">
                @csrf
                <div class="row g-3">
                  <div class="col-md-7">
                    <label class="form-label">Nagad Wallet Number</label>
                    <div class="input-group">
                      <span class="input-group-text"><i class="fa-solid fa-phone"></i></span>
                      <input type="text" name="number" class="form-control fw-bold font-monospace" value="{{ $nagad['number'] ?? '01735254295' }}" required>
                    </div>
                  </div>
                  <div class="col-md-5">
                    <label class="form-label">Account Type</label>
                    <select name="type" class="form-select">
                      <option value="Personal" {{ ($nagad['type'] ?? '') == 'Personal' ? 'selected' : '' }}>Personal (Send Money)</option>
                      <option value="Merchant" {{ ($nagad['type'] ?? '') == 'Merchant' ? 'selected' : '' }}>Merchant (Payment)</option>
                      <option value="Uddokta" {{ ($nagad['type'] ?? '') == 'Uddokta' ? 'selected' : '' }}>Uddokta (Cash In)</option>
                    </select>
                  </div>

                  <div class="col-12">
                    <label class="form-label">Webhook Secret Token</label>
                    <div class="input-group">
                      <span class="input-group-text"><i class="fa-solid fa-key"></i></span>
                      <input type="text" name="webhook_token" id="token_nagad" class="form-control font-monospace" value="{{ $nagad['webhook_token'] ?? 'mfs_secret_token_nagad_2026' }}" required>
                      <button type="button" class="btn btn-outline-secondary" onclick="generateToken('nagad')" title="Generate New Secure Token">
                        <i class="fa-solid fa-arrows-rotate me-1"></i> New
                      </button>
                    </div>
                    <small class="text-muted" style="font-size: 0.76rem;">Match this token in your Android SMS Forwarder webhook URL query parameter.</small>
                  </div>

                  <div class="col-12">
                    <label class="form-label">Android Webhook URL (Copy to SMS App)</label>
                    <div class="input-group copy-input-group p-1 border">
                      <input type="text" id="url_nagad" class="form-control" readonly value="{{ $webhookUrls['nagad'] }}?token={{ $nagad['webhook_token'] ?? 'mfs_secret_token_nagad_2026' }}">
                      <button type="button" class="btn btn-sm btn-dark" onclick="copyToClipboard('url_nagad')">
                        <i class="fa-regular fa-copy me-1"></i> Copy
                      </button>
                    </div>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label">USSD Dial Code</label>
                    <input type="text" name="ussd_code" class="form-control font-monospace" value="{{ $nagad['ussd_code'] ?? '*167#' }}">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <div class="pt-2">
                      <span class="badge {{ !empty($nagad['enabled']) ? 'bg-success' : 'bg-secondary' }} px-3 py-2" id="badge_nagad">
                        {{ !empty($nagad['enabled']) ? 'Active & Receiving' : 'Disabled' }}
                      </span>
                    </div>
                  </div>

                  <div class="col-12 pt-2">
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold" style="background: #f04923; border-color: #f04923;">
                      <i class="fa-solid fa-floppy-disk me-1"></i> Save Nagad Credentials
                    </button>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- Rocket Card -->
        @php $rocket = $gateways['rocket'] ?? []; @endphp
        <div class="col-lg-6">
          <div class="gateway-card">
            <div class="card-header bg-rocket">
              <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-rocket fs-5"></i>
                <span class="fs-5">DBBL Rocket Direct</span>
              </div>
              <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="toggle_rocket" onchange="toggleProvider('rocket')" {{ !empty($rocket['enabled']) ? 'checked' : '' }}>
              </div>
            </div>
            <div class="card-body p-4">
              <form action="{{ route('mfs.sms.admin.save', ['provider' => 'rocket']) }}" method="POST">
                @csrf
                <div class="row g-3">
                  <div class="col-md-7">
                    <label class="form-label">Rocket Wallet Number</label>
                    <div class="input-group">
                      <span class="input-group-text"><i class="fa-solid fa-phone"></i></span>
                      <input type="text" name="number" class="form-control fw-bold font-monospace" value="{{ $rocket['number'] ?? '01735254295-9' }}" required>
                    </div>
                  </div>
                  <div class="col-md-5">
                    <label class="form-label">Account Type</label>
                    <select name="type" class="form-select">
                      <option value="Personal" {{ ($rocket['type'] ?? '') == 'Personal' ? 'selected' : '' }}>Personal (12 Digits)</option>
                      <option value="Merchant" {{ ($rocket['type'] ?? '') == 'Merchant' ? 'selected' : '' }}>Merchant</option>
                    </select>
                  </div>

                  <div class="col-12">
                    <label class="form-label">Webhook Secret Token</label>
                    <div class="input-group">
                      <span class="input-group-text"><i class="fa-solid fa-key"></i></span>
                      <input type="text" name="webhook_token" id="token_rocket" class="form-control font-monospace" value="{{ $rocket['webhook_token'] ?? 'mfs_secret_token_rocket_2026' }}" required>
                      <button type="button" class="btn btn-outline-secondary" onclick="generateToken('rocket')" title="Generate New Secure Token">
                        <i class="fa-solid fa-arrows-rotate me-1"></i> New
                      </button>
                    </div>
                  </div>

                  <div class="col-12">
                    <label class="form-label">Android Webhook URL</label>
                    <div class="input-group copy-input-group p-1 border">
                      <input type="text" id="url_rocket" class="form-control" readonly value="{{ $webhookUrls['rocket'] }}?token={{ $rocket['webhook_token'] ?? 'mfs_secret_token_rocket_2026' }}">
                      <button type="button" class="btn btn-sm btn-dark" onclick="copyToClipboard('url_rocket')">
                        <i class="fa-regular fa-copy me-1"></i> Copy
                      </button>
                    </div>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label">USSD Dial Code</label>
                    <input type="text" name="ussd_code" class="form-control font-monospace" value="{{ $rocket['ussd_code'] ?? '*322#' }}">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <div class="pt-2">
                      <span class="badge {{ !empty($rocket['enabled']) ? 'bg-success' : 'bg-secondary' }} px-3 py-2" id="badge_rocket">
                        {{ !empty($rocket['enabled']) ? 'Active & Receiving' : 'Disabled' }}
                      </span>
                    </div>
                  </div>

                  <div class="col-12 pt-2">
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold" style="background: #8c2d82; border-color: #8c2d82;">
                      <i class="fa-solid fa-floppy-disk me-1"></i> Save Rocket Credentials
                    </button>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- Unified Endpoint Card -->
        <div class="col-lg-6">
          <div class="gateway-card h-100">
            <div class="card-header bg-dark text-white">
              <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-diagram-project fs-5 text-info"></i>
                <span class="fs-5">Unified Webhook Endpoint</span>
              </div>
              <span class="badge bg-info text-dark">Auto-Detect</span>
            </div>
            <div class="card-body p-4 d-flex flex-column justify-content-between">
              <div>
                <p class="text-muted small">
                  If you have one Android phone that receives SMS for <strong>all MFS providers (bKash, Nagad, Rocket)</strong>, configure this single endpoint in your forwarder app. The package will automatically detect provider, amount, and TrxID:
                </p>
                <div class="input-group copy-input-group p-1 border mb-3">
                  <input type="text" id="url_unified" class="form-control" readonly value="{{ $webhookUrls['unified'] }}?token={{ $bkash['webhook_token'] ?? 'mfs_secret_token_bkash_2026' }}">
                  <button type="button" class="btn btn-sm btn-dark" onclick="copyToClipboard('url_unified')">
                    <i class="fa-regular fa-copy me-1"></i> Copy
                  </button>
                </div>
                <div class="alert alert-light border small text-muted">
                  <i class="fa-solid fa-shield-halved text-success me-1"></i>
                  Any valid secret token configured in any active provider will authorize incoming forwarded SMS.
                </div>
              </div>
              <div class="text-end">
                <a href="#setupTab" data-bs-toggle="tab" class="btn btn-outline-dark btn-sm">
                  <i class="fa-solid fa-book me-1"></i> View Android Setup Guide
                </a>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- TAB 2: SMS TEST SIMULATOR -->
    <div class="tab-pane fade" id="simulatorTab">
      <div class="card border-0 shadow-sm rounded-4 p-4">
        <h5 class="fw-bold mb-3"><i class="fa-solid fa-vial text-primary me-2"></i> Live SMS Parser & Auto-Match Simulator</h5>
        <p class="text-muted small">
          Paste any forwarded SMS text below or choose a template to test parser extraction, TrxID validation, and order matching in real-time.
        </p>

        <div class="row g-3 mb-3">
          <div class="col-md-4">
            <label class="form-label">Select MFS Template</label>
            <select id="sim_template" class="form-select" onchange="loadSimulatorTemplate()">
              <option value="bkash_personal">bKash Personal (Received Tk 7,710.00)</option>
              <option value="bkash_merchant">bKash Merchant Pay (Received payment Tk 1,500.00)</option>
              <option value="nagad_money">Nagad Money Received (Amount: Tk 7,710.00)</option>
              <option value="nagad_merchant">Nagad Merchant Pay (Amount: Tk 500.00)</option>
              <option value="rocket_dbbl">DBBL Rocket (Tk. 500.00 received from A/C)</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Provider Hint</label>
            <select id="sim_provider" class="form-select">
              <option value="bkash">bKash</option>
              <option value="nagad">Nagad</option>
              <option value="rocket">Rocket</option>
            </select>
          </div>
          <div class="col-md-5 d-flex align-items-end">
            <button type="button" class="btn btn-outline-secondary w-100" onclick="randomizeSimTrx()">
              <i class="fa-solid fa-dice me-1"></i> Randomize TrxID in SMS
            </button>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Raw SMS Text</label>
          <textarea id="sim_text" class="form-control font-monospace" rows="4"></textarea>
        </div>

        <button type="button" class="btn btn-dark fw-bold px-4 py-2" id="sim_run_btn" onclick="runSimulator()">
          <i class="fa-solid fa-play me-1"></i> Run Parser Simulation
        </button>

        <!-- Result Box -->
        <div id="sim_result" class="mt-4" style="display: none;"></div>
      </div>
    </div>

    <!-- TAB 3: ANDROID SETUP GUIDE -->
    <div class="tab-pane fade" id="setupTab">
      <div class="card border-0 shadow-sm rounded-4 p-4">
        <h5 class="fw-bold mb-3"><i class="fa-brands fa-android text-success me-2"></i> How to Connect Your Android Phone to Forward SMS</h5>
        
        <div class="row g-4 mt-1">
          <div class="col-md-4">
            <div class="p-3 border rounded-3 bg-white h-100">
              <span class="badge bg-primary mb-2">Step 1</span>
              <h6 class="fw-bold">Install Forwarder App</h6>
              <p class="small text-muted">
                Install any SMS forwarding app on your merchant Android phone. Recommended: <strong>SMS Forwarder</strong> (free open-source) or <strong>MacroDroid</strong>.
              </p>
              <a href="https://github.com/bogkonstantin/android-sms-forwarder/releases" target="_blank" class="btn btn-sm btn-outline-primary">
                <i class="fa-brands fa-github me-1"></i> Download APK
              </a>
            </div>
          </div>

          <div class="col-md-4">
            <div class="p-3 border rounded-3 bg-white h-100">
              <span class="badge bg-primary mb-2">Step 2</span>
              <h6 class="fw-bold">Configure Webhook Rule</h6>
              <p class="small text-muted mb-2">In the app, create a webhook forwarding rule:</p>
              <ul class="small text-muted ps-3 mb-0">
                <li><strong>Target URL</strong>: Copy the Webhook URL from Tab 1.</li>
                <li><strong>HTTP Method</strong>: <code>POST</code></li>
                <li><strong>Send JSON Body</strong>: <code>{"text": "%msg", "from": "%from"}</code></li>
                <li><strong>Filter Sender</strong>: <code>bKash, 16247, NAGAD, 16167, 16216</code></li>
              </ul>
            </div>
          </div>

          <div class="col-md-4">
            <div class="p-3 border rounded-3 bg-white h-100">
              <span class="badge bg-primary mb-2">Step 3</span>
              <h6 class="fw-bold">Disable Battery Optimization</h6>
              <p class="small text-muted">
                Go to Android <strong>Settings &rarr; Battery Optimization &rarr; Don't Optimize</strong> for the SMS forwarder app so Android doesn't stop background forwarding when screen is locked.
              </p>
              <span class="badge bg-success-subtle text-success">Instant Delivery Guaranteed</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 4: RECENT TRANSACTIONS -->
    <div class="tab-pane fade" id="recentTab">
      <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
          <h6 class="mb-0 fw-bold">Recent SMS Forwarded Transactions</h6>
          <a href="{{ route('mfs.sms.admin.transactions') }}" class="btn btn-sm btn-outline-dark">
            View All Transactions &rarr;
          </a>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light small text-uppercase">
              <tr>
                <th>Date / Time</th>
                <th>Provider</th>
                <th>Amount</th>
                <th>TrxID</th>
                <th>Sender Phone</th>
                <th>Reference</th>
                <th>Status</th>
                <th>Matched Order</th>
              </tr>
            </thead>
            <tbody>
              @forelse($recentTransactions as $tx)
                <tr>
                  <td class="small">{{ $tx->created_at->format('d M Y, h:i A') }}</td>
                  <td>
                    <span class="badge text-white" style="background: {{ $tx->provider === 'nagad' ? '#f04923' : ($tx->provider === 'rocket' ? '#8c2d82' : '#e2136e') }};">
                      {{ ucfirst($tx->provider) }}
                    </span>
                  </td>
                  <td class="fw-bold text-dark">৳{{ number_format($tx->amount, 2) }}</td>
                  <td class="font-monospace fw-bold">{{ $tx->trx_id }}</td>
                  <td class="font-monospace small">{{ $tx->sender_number ?: '-' }}</td>
                  <td class="small">{{ $tx->reference ?: '-' }}</td>
                  <td>
                    <span class="badge {{ $tx->status === 'matched' ? 'bg-success' : ($tx->status === 'manual_verified' ? 'bg-info' : 'bg-warning text-dark') }}">
                      {{ ucfirst(str_replace('_', ' ', $tx->status)) }}
                    </span>
                  </td>
                  <td>
                    @if($tx->order_id)
                      <span class="badge bg-light text-dark border">Order #{{ $tx->order_id }}</span>
                    @else
                      <span class="text-muted small">None</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="8" class="text-center py-4 text-muted">
                    No SMS transactions recorded yet. Forwarded SMS will appear here in real-time.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
  const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  // Copy to clipboard helper
  function copyToClipboard(elementId) {
    const input = document.getElementById(elementId);
    if (!input) return;
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value).then(() => {
      alert('Copied to clipboard: ' + input.value);
    });
  }

  // Toggle Provider Status AJAX
  function toggleProvider(provider) {
    fetch("{{ url(config('mfs-sms.routes.admin_prefix', 'admin/mfs-sms')) }}/toggle/" + provider, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json'
      }
    })
    .then(res => res.json())
    .then(data => {
      const badge = document.getElementById('badge_' + provider);
      if (badge) {
        badge.className = 'badge px-3 py-2 ' + (data.enabled ? 'bg-success' : 'bg-secondary');
        badge.innerText = data.enabled ? 'Active & Receiving' : 'Disabled';
      }
      alert(data.message);
    });
  }

  // Generate Token AJAX
  function generateToken(provider) {
    fetch("{{ url(config('mfs-sms.routes.admin_prefix', 'admin/mfs-sms')) }}/generate-token/" + provider, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json'
      }
    })
    .then(res => res.json())
    .then(data => {
      if (data.token) {
        document.getElementById('token_' + provider).value = data.token;
        const urlInput = document.getElementById('url_' + provider);
        if (urlInput) {
          const base = urlInput.value.split('?')[0];
          urlInput.value = base + '?token=' + data.token;
        }
        alert('New token generated! Click "Save ' + provider.toUpperCase() + ' Credentials" to persist.');
      }
    });
  }

  // Simulator Templates
  const templates = {
    bkash_personal: "You have received Tk 7,710.00 from 01735254295. Ref Order #46. Fee Tk 0.00. Balance Tk 25,400.00. TrxID 9K8B7C6D5E at 16/10/2026 18:30",
    bkash_merchant: "You have received payment Tk 1,500.00 from 01711000000. Ref 46 Fee Tk 0.00 Balance Tk 15,000.00. TrxID 9K8B7C6D5E",
    nagad_money: "Money Received. Amount: Tk 7,710.00. Sender: 01735254295. Ref: 46. TxnID: 72J9KA21. Balance: Tk 15,200.00. 16/10/2026 18:30",
    nagad_merchant: "Merchant Pay Received. Amount: Tk 500.00. Sender: 01900000000. Ref: Order #46. TxnID: 888AAA. Current Balance: Tk 10,000.",
    rocket_dbbl: "Tk. 500.00 received from A/C: 017352542951. TxnId: 198273645. Ref: 46. Balance: Tk. 2000.00"
  };

  function loadSimulatorTemplate() {
    const key = document.getElementById('sim_template').value;
    document.getElementById('sim_text').value = templates[key] || '';
    if (key.includes('bkash')) document.getElementById('sim_provider').value = 'bkash';
    if (key.includes('nagad')) document.getElementById('sim_provider').value = 'nagad';
    if (key.includes('rocket')) document.getElementById('sim_provider').value = 'rocket';
  }
  loadSimulatorTemplate();

  function randomizeSimTrx() {
    const rand = 'SIM' + Math.random().toString(36).substring(2, 8).toUpperCase();
    let text = document.getElementById('sim_text').value;
    text = text.replace(/(?:TrxID|TxnID|TxnId)[\s:]*([A-Za-z0-9]+)/i, 'TrxID ' + rand);
    document.getElementById('sim_text').value = text;
  }

  function runSimulator() {
    const btn = document.getElementById('sim_run_btn');
    const resultBox = document.getElementById('sim_result');
    const sms = document.getElementById('sim_text').value.trim();
    const provider = document.getElementById('sim_provider').value;

    if (!sms) return;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Processing...';
    resultBox.style.display = 'none';

    fetch("{{ route('mfs.sms.test_simulator') }}", {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json'
      },
      body: JSON.stringify({ test_sms: sms, provider: provider })
    })
    .then(res => res.json())
    .then(data => {
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-play me-1"></i> Run Parser Simulation';
      resultBox.style.display = 'block';

      if (data.success) {
        resultBox.innerHTML = `
          <div class="alert alert-success border-0 shadow-sm p-4 rounded-3">
            <h6 class="fw-bold text-success mb-2"><i class="fa-solid fa-circle-check me-1"></i> SMS Parsed Successfully!</h6>
            <div class="row g-2 mt-1 small">
              <div class="col-md-3"><strong>Provider:</strong> ${data.provider}</div>
              <div class="col-md-3"><strong>Amount:</strong> ৳${data.amount}</div>
              <div class="col-md-3"><strong>TrxID:</strong> ${data.trx_id}</div>
              <div class="col-md-3"><strong>Matched Order:</strong> ${data.order_id ? 'Order #' + data.order_id : '<span class="text-warning">None</span>'}</div>
            </div>
            <p class="mb-0 mt-3 small text-muted">${data.message}</p>
          </div>
        `;
      } else {
        resultBox.innerHTML = `
          <div class="alert alert-danger border-0 shadow-sm p-4 rounded-3">
            <h6 class="fw-bold text-danger mb-1"><i class="fa-solid fa-triangle-exclamation me-1"></i> Parser Result:</h6>
            <p class="mb-0 small">${data.message}</p>
          </div>
        `;
      }
    })
    .catch(err => {
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-play me-1"></i> Run Parser Simulation';
      resultBox.style.display = 'block';
      resultBox.innerHTML = `<div class="alert alert-danger small">Error running simulation.</div>`;
    });
  }
</script>

</body>
</html>
