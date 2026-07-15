@extends('layouts.master')

@section('title') Test Guides @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') TouristGuidePh @endslot
@slot('title') Test Guides @endslot
@endcomponent

<div class="alert alert-info">
    <strong><i class="bx bx-info-circle me-1"></i>Developer / QA reference page.</strong>
    This page lists every place where bids, listings, and clients are created, plus dummy logins and pre-seeded data so you can click through the bidding flow end-to-end.
</div>

{{-- KPI strip --}}
<div class="row g-2 mb-4">
    @php
        $kpis = [
            ['label' => 'Keywords',          'value' => $stats['keywords'],             'color' => 'primary'],
            ['label' => 'SEO Pages',          'value' => $stats['pages_published'] . ' / ' . $stats['pages_total'], 'color' => 'success'],
            ['label' => 'Clients',            'value' => $stats['clients'],              'color' => 'info'],
            ['label' => 'Published Properties','value' => $stats['properties_published'].' / '.$stats['properties'], 'color' => 'warning'],
            ['label' => 'Active Listings',    'value' => $stats['listings_active'],      'color' => 'danger'],
            ['label' => 'Bid Events',         'value' => $stats['bid_events'],           'color' => 'secondary'],
            ['label' => 'Pending GCash',      'value' => $stats['gp_topups_pending'],    'color' => 'dark'],
        ];
    @endphp
    @foreach($kpis as $k)
        <div class="col-md col-6">
            <div class="card mb-0">
                <div class="card-body py-3">
                    <small class="text-muted text-uppercase" style="font-size:10px">{{ $k['label'] }}</small>
                    <h4 class="mb-0 text-{{ $k['color'] }}">{{ $k['value'] }}</h4>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row">
    {{-- LEFT: Where bids/listings are created (admin POV) --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bx bx-target-lock me-1"></i>Where Bids & Listings Are Created</h5>
            </div>
            <div class="card-body">
                <ol class="ps-3">
                    <li class="mb-3">
                        <strong>Admin can monitor bids here:</strong><br>
                        <a href="{{ route('resort-guru-listings.index') }}" class="btn btn-sm btn-outline-primary mt-1">
                            <i class="bx bx-trophy"></i> Listings &amp; Bids
                        </a>
                        <p class="small text-muted mt-1 mb-0">Live ranking table across all keywords. Drill into a listing to see its bid history.</p>
                    </li>
                    <li class="mb-3">
                        <strong>Clients create listings via the owner dashboard:</strong><br>
                        <code class="small">{{ $frontendUrl }}/dashboard/listings</code><br>
                        <span class="text-muted small">→ Browse keywords → Pick a published property → Pay base GP → Top up bid GP to climb</span>
                    </li>
                    <li class="mb-3">
                        <strong>Admin can credit/debit GP manually (so clients can bid without real GCash):</strong><br>
                        Open any client's profile and use the "Credit / Debit GP" form.
                        <a href="{{ route('resort-guru-owners.index') }}" class="btn btn-sm btn-outline-info mt-1"><i class="bx bx-user"></i> Open Clients</a>
                    </li>
                    <li class="mb-3">
                        <strong>Admin can approve real GCash top-ups:</strong><br>
                        <a href="{{ route('resort-guru-gcash.index') }}" class="btn btn-sm btn-outline-success mt-1"><i class="bx bx-wallet"></i> GCash Approvals</a>
                    </li>
                    <li>
                        <strong>Admin can view raw GP ledger / manual adjustments:</strong><br>
                        <a href="{{ route('resort-guru-gp.index') }}" class="btn btn-sm btn-outline-warning mt-1"><i class="bx bx-coin-stack"></i> Gold Points</a>
                    </li>
                </ol>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bx bx-cog me-1"></i>Admin Settings &amp; Pricing</h5>
            </div>
            <div class="card-body">
                <ul class="ps-3">
                    <li>Base price formula + listing duration + GP-to-top quantum: <a href="{{ route('resort-guru-settings.index') }}">Settings</a></li>
                    <li>Keywords (volume, KD, base price cache): <a href="{{ route('resort-guru-keywords.index') }}">Keywords</a></li>
                    <li>Pages per keyword (multiple supported): Open any keyword and click <strong>Pages</strong></li>
                    <li>Properties / approval state machine: <a href="{{ route('resort-guru-resorts.index') }}">Properties</a></li>
                    <li>Site Pages (homepage hero, about, etc.): <a href="{{ route('resort-guru-static.index') }}">Site Pages</a></li>
                </ul>
            </div>
        </div>
    </div>

    {{-- RIGHT: Dummy logins + sample keyword pages --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bx bx-key me-1"></i>Dummy Client Logins</h5>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-3">All dummy clients share the same password.</p>
                <div class="alert alert-warning py-2 mb-3">
                    <strong>Frontend login:</strong> <code>{{ $frontendUrl }}/login</code><br>
                    <strong>Password (all):</strong> <code>password123</code>
                </div>
                @if($dummyClients->isEmpty())
                    <p class="text-muted">No dummy clients yet. Run <code>php artisan db:seed --class=DummyClientsAndPropertiesSeeder</code></p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm small">
                            <thead class="table-light"><tr><th>Name</th><th>Email</th><th>Status</th></tr></thead>
                            <tbody>
                            @foreach($dummyClients as $c)
                                <tr>
                                    <td>
                                        <a href="{{ route('resort-guru-owners.show', ['id' => $c->id]) }}">{{ $c->name }}</a>
                                    </td>
                                    <td><code class="small">{{ $c->email }}</code></td>
                                    <td>
                                        @php $sc = ['active'=>'success','suspended'=>'danger','pending'=>'warning'][$c->status] ?? 'secondary'; @endphp
                                        <span class="badge bg-{{ $sc }}">{{ ucfirst($c->status) }}</span>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="bx bx-trophy me-1"></i>Keyword Pages With Active Bids</h5>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-3">Click any URL to view the live page on the frontend &mdash; you'll see the bidding ranking applied.</p>
                @if($keywordsWithBids->isEmpty())
                    <div class="alert alert-light">
                        No sample bids yet. Run:<br>
                        <code class="small">cd c:\xampp\htdocs\resortguruph<br>php artisan db:seed --class=SampleBidsSeeder</code>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm small">
                            <thead class="table-light">
                                <tr>
                                    <th>Keyword</th>
                                    <th class="text-end">Bids</th>
                                    <th class="text-end">Top GP</th>
                                    <th class="text-end">Live URL</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($keywordsWithBids as $kw)
                                <tr>
                                    <td>
                                        <strong class="text-capitalize">{{ $kw->phrase }}</strong><br>
                                        <small class="text-muted">{{ number_format($kw->search_volume_monthly) }} vol/mo</small>
                                    </td>
                                    <td class="text-end"><span class="badge bg-primary">{{ $kw->bid_count }}</span></td>
                                    <td class="text-end"><strong class="text-success">{{ number_format($kw->top_bid) }}</strong></td>
                                    <td class="text-end">
                                        <a href="{{ $frontendUrl }}/{{ $kw->page_slug }}" target="_blank" class="btn btn-sm btn-outline-success">
                                            <i class="bx bx-link-external"></i> View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bx bx-building-house me-1"></i>Sample Properties</h5>
            </div>
            <div class="card-body">
                @if($sampleProperties->isEmpty())
                    <p class="text-muted small">No published properties yet.</p>
                @else
                    <ul class="list-group list-group-flush small">
                        @foreach($sampleProperties as $p)
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <div>
                                    <strong>{{ $p->name }}</strong><br>
                                    <small class="text-muted">{{ $p->city }}{{ $p->province ? ', ' . $p->province : '' }}</small>
                                </div>
                                <a href="{{ $frontendUrl }}/listing/{{ $p->slug }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="bx bx-link-external"></i> View
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Walkthroughs --}}
<div class="card">
    <div class="card-header bg-secondary text-white">
        <h5 class="mb-0"><i class="bx bx-list-ol me-1"></i>End-to-End Test Walkthroughs</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <h6 class="text-primary"><i class="bx bx-target-lock"></i> Test #1: Place a Bid as a Client</h6>
                <ol class="small ps-3">
                    <li>Open frontend login <code>{{ $frontendUrl }}/login</code></li>
                    <li>Log in as <code>maria.santos@dummy.test</code> / <code>password123</code></li>
                    <li>Click <strong>My Listings</strong> in dashboard sidebar</li>
                    <li>Click <strong>+ Claim new listing</strong> to browse keywords</li>
                    <li>Pick a keyword (e.g. "resort in Tagaytay")</li>
                    <li>Pick one of your published properties</li>
                    <li>Confirm the base GP cost (deducted immediately)</li>
                    <li>From My Listings, click <strong>💰 Top up bid</strong> to bid more</li>
                    <li>Refresh — your listing now ranks higher on the public keyword page</li>
                </ol>
            </div>

            <div class="col-md-6 mb-3">
                <h6 class="text-success"><i class="bx bx-check"></i> Test #2: Approve a GCash Top-Up (Admin)</h6>
                <ol class="small ps-3">
                    <li>As a client, submit a GP top-up at <code>{{ $frontendUrl }}/dashboard/gold-points/topup</code></li>
                    <li>Upload any image as the "screenshot" (proof of payment)</li>
                    <li>Submit — top-up goes to <code>pending</code> status</li>
                    <li>Back in admin, open <a href="{{ route('resort-guru-gcash.index') }}">GCash Approvals</a></li>
                    <li>Click the pending top-up and review</li>
                    <li>Click <strong>Approve</strong> — GP is credited to the client's ledger</li>
                    <li>Verify in <a href="{{ route('resort-guru-gp.index') }}">Gold Points</a> ledger</li>
                </ol>
            </div>

            <div class="col-md-6 mb-3">
                <h6 class="text-warning"><i class="bx bx-edit"></i> Test #3: Edit a Keyword Page (SEO + Builder)</h6>
                <ol class="small ps-3">
                    <li>Open <a href="{{ route('resort-guru-keywords.index') }}">Keywords</a></li>
                    <li>Click the green <strong>Pages</strong> action on any keyword</li>
                    <li>Click any listed page or <strong>+ Add New Page</strong></li>
                    <li>In the builder, click <strong>+ Heading</strong>, <strong>+ Rich Text</strong>, <strong>+ Image</strong>, etc.</li>
                    <li>Each block saves via AJAX — no page reload</li>
                    <li>Click <strong>View live</strong> button — changes are immediate on the frontend</li>
                    <li>Watch the SEO Score panel update as you add content</li>
                </ol>
            </div>

            <div class="col-md-6 mb-3">
                <h6 class="text-info"><i class="bx bx-user-plus"></i> Test #4: Create a New Client + Initial GP</h6>
                <ol class="small ps-3">
                    <li>Open <a href="{{ route('resort-guru-owners.index') }}">Clients</a></li>
                    <li>Click <strong>+ Add Client</strong></li>
                    <li>Fill in name, email, optional password (or auto-generate), initial GP</li>
                    <li>Save — temporary password is shown once (copy it)</li>
                    <li>Share password with client — they can log in at <code>{{ $frontendUrl }}/login</code></li>
                    <li>From client profile, use <strong>Reset Password</strong> to set a new one any time</li>
                </ol>
            </div>

            <div class="col-md-6 mb-3">
                <h6 class="text-danger"><i class="bx bx-trophy"></i> Test #5: Watch Bidding Ranking in Real Time</h6>
                <ol class="small ps-3">
                    <li>Open <a href="{{ $frontendUrl }}/resort-in-bulacan" target="_blank">{{ $frontendUrl }}/resort-in-bulacan</a> in one tab</li>
                    <li>Open <a href="{{ route('resort-guru-listings.index') }}" target="_blank">Listings &amp; Bids</a> admin in another tab</li>
                    <li>In admin GP page, manually credit a client +500 GP</li>
                    <li>Log in as that client on frontend, top up their lowest-ranked listing</li>
                    <li>Refresh the keyword page — they jump up in the ranking</li>
                </ol>
            </div>

            <div class="col-md-6 mb-3">
                <h6 class="text-dark"><i class="bx bx-refresh"></i> Re-seed Sample Data</h6>
                <p class="small text-muted">Run from the frontend project to regenerate test data:</p>
                <pre class="small bg-light p-2 rounded mb-0"><code>cd c:\xampp\htdocs\resortguruph

# 91 real keywords + content (idempotent)
php artisan db:seed --class=Import91KeywordsSeeder

# 8 dummy clients + 12 properties
php artisan db:seed --class=DummyClientsAndPropertiesSeeder

# 15 sample bids across top 5 keywords
php artisan db:seed --class=SampleBidsSeeder</code></pre>
            </div>
        </div>
    </div>
</div>
@endsection
