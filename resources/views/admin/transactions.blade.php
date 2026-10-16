<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>MFS SMS Transactions Audit Log</title>

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
  </style>
</head>
<body>

<header class="top-navbar mb-4">
  <div class="container d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-3">
      <div style="background: rgba(255,255,255,0.15); width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
        <i class="fa-solid fa-receipt text-warning fs-5"></i>
      </div>
      <div>
        <h5 class="mb-0 fw-bold">SMS Transactions Audit Log</h5>
        <small class="opacity-75" style="font-size: 0.8rem;">Real-time incoming forwarded SMS records & order match audit</small>
      </div>
    </div>
    <div class="d-flex align-items-center gap-2">
      <a href="{{ route('mfs.sms.admin.index') }}" class="btn btn-sm btn-outline-light">
        <i class="fa-solid fa-arrow-left me-1"></i> Credentials Panel
      </a>
      <a href="{{ url('admin/paymentgeteway/manage') }}" class="btn btn-sm btn-secondary">
        <i class="fa-solid fa-gauge me-1"></i> Admin Panel
      </a>
    </div>
  </div>
</header>

<main class="container mb-5">
  <!-- Filters Card -->
  <div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
    <form action="{{ route('mfs.sms.admin.transactions') }}" method="GET" class="row g-2 align-items-center">
      <div class="col-md-5">
        <div class="input-group">
          <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
          <input type="text" name="q" class="form-control" placeholder="Search by TrxID, Phone, Ref, or Order ID..." value="{{ request('q') }}">
        </div>
      </div>
      <div class="col-md-3">
        <select name="provider" class="form-select">
          <option value="">All Providers</option>
          <option value="bkash" {{ request('provider') == 'bkash' ? 'selected' : '' }}>bKash</option>
          <option value="nagad" {{ request('provider') == 'nagad' ? 'selected' : '' }}>Nagad</option>
          <option value="rocket" {{ request('provider') == 'rocket' ? 'selected' : '' }}>Rocket</option>
        </select>
      </div>
      <div class="col-md-2">
        <select name="status" class="form-select">
          <option value="">All Statuses</option>
          <option value="matched" {{ request('status') == 'matched' ? 'selected' : '' }}>Matched</option>
          <option value="unmatched" {{ request('status') == 'unmatched' ? 'selected' : '' }}>Unmatched</option>
          <option value="manual_verified" {{ request('status') == 'manual_verified' ? 'selected' : '' }}>Manual Verified</option>
          <option value="duplicate" {{ request('status') == 'duplicate' ? 'selected' : '' }}>Duplicate</option>
        </select>
      </div>
      <div class="col-md-2 d-flex gap-2">
        <button type="submit" class="btn btn-dark w-100">
          Filter
        </button>
        <a href="{{ route('mfs.sms.admin.transactions') }}" class="btn btn-outline-secondary" title="Clear Filters">
          <i class="fa-solid fa-arrows-rotate"></i>
        </a>
      </div>
    </form>
  </div>

  <!-- Transactions Table -->
  <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light small text-uppercase">
          <tr>
            <th>#</th>
            <th>Date / Time</th>
            <th>Provider</th>
            <th>Amount</th>
            <th>Transaction ID</th>
            <th>Sender Mobile</th>
            <th>Reference</th>
            <th>Status</th>
            <th>Order ID</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($transactions as $tx)
            <tr>
              <td class="text-muted small">{{ $tx->id }}</td>
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
              <td class="text-end">
                @if($tx->status === 'unmatched')
                  <button type="button" class="btn btn-sm btn-outline-primary" onclick="openMatchModal({{ $tx->id }}, '{{ $tx->trx_id }}', {{ $tx->amount }})">
                    <i class="fa-solid fa-link me-1"></i> Match Order
                  </button>
                @else
                  <span class="text-muted small"><i class="fa-solid fa-check text-success"></i> Linked</span>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="10" class="text-center py-5 text-muted">
                No transactions found matching your criteria.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($transactions->hasPages())
      <div class="card-footer bg-white py-3">
        {{ $transactions->withQueryString()->links() }}
      </div>
    @endif
  </div>
</main>

<!-- Match Order Modal -->
<div class="modal fade" id="matchModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold">Manual Link to Order</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <p class="text-muted small">
          Manually link Transaction <strong class="font-monospace" id="modal_trx_id"></strong> (৳<span id="modal_amount"></span>) to an order. The order will be immediately updated to Paid status.
        </p>
        <div class="mb-3">
          <label class="form-label small fw-bold">Order ID or Invoice Number</label>
          <input type="text" id="modal_order_id" class="form-control" placeholder="e.g. 46" required>
        </div>
        <div id="modal_feedback" class="small mb-3" style="display: none;"></div>
        <button type="button" class="btn btn-primary w-100 fw-bold" id="modal_submit_btn" onclick="submitManualMatch()">
          <i class="fa-solid fa-check me-1"></i> Apply Payment to Order
        </button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  let currentTxId = null;
  let matchModal = null;

  function openMatchModal(id, trxId, amount) {
    currentTxId = id;
    document.getElementById('modal_trx_id').innerText = trxId;
    document.getElementById('modal_amount').innerText = amount;
    document.getElementById('modal_order_id').value = '';
    document.getElementById('modal_feedback').style.display = 'none';
    
    if (!matchModal) {
      matchModal = new bootstrap.Modal(document.getElementById('matchModal'));
    }
    matchModal.show();
  }

  function submitManualMatch() {
    const orderId = document.getElementById('modal_order_id').value.trim();
    const btn = document.getElementById('modal_submit_btn');
    const feedback = document.getElementById('modal_feedback');

    if (!orderId) {
      alert('Please enter an Order ID');
      return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Matching...';
    feedback.style.display = 'none';

    fetch("{{ url(config('mfs-sms.routes.admin_prefix', 'admin/mfs-sms')) }}/transactions/" + currentTxId + "/match", {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json'
      },
      body: JSON.stringify({ order_id: orderId })
    })
    .then(res => res.json())
    .then(data => {
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Apply Payment to Order';
      feedback.style.display = 'block';

      if (data.success) {
        feedback.className = 'alert alert-success p-2 small';
        feedback.innerText = data.message;
        setTimeout(() => {
          location.reload();
        }, 1200);
      } else {
        feedback.className = 'alert alert-danger p-2 small';
        feedback.innerText = data.message;
      }
    })
    .catch(err => {
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Apply Payment to Order';
      feedback.style.display = 'block';
      feedback.className = 'alert alert-danger p-2 small';
      feedback.innerText = 'Server error occurred.';
    });
  }
</script>
</body>
</html>
