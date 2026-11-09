{{-- Streamr > Payment reviews > Payment settings: how Streamr is paid (what GET /api/streamr/v1/config gives Streamr). --}}
<form method="POST" action="{{ route('streamr-payments.settings') }}">
    @csrf
    <div class="row g-3">
        <div class="col-12">
            <div class="card mb-0"><div class="card-body">
                <h5 class="card-title text-dark mb-1"><i class="bx bx-transfer-alt me-1"></i>Where the receiving details come from</h5>
                <p class="text-secondary small mb-3">
                    Streamr shows buyers where to send the money and its AI checks every receipt against it.
                    @if ($lastFetch)
                        Last fetched by Streamr: <b class="text-dark">{{ $lastFetch->diffForHumans() }}</b>.
                    @else
                        Streamr has not fetched them yet.
                    @endif
                </p>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="source" id="stSourceEcom" value="ecom" @checked(old('source', $pay['source']) === 'ecom')>
                    <label class="form-check-label text-dark" for="stSourceEcom">
                        Receiving details from <b>E-commerce › Stores › {{ $storeName }}</b>
                        @if ($storeSettingsUrl)
                            (<a href="{{ $storeSettingsUrl }}">open its payment settings</a>)
                        @else
                            <span class="text-secondary">(the store appears after Streamr's first order, or add it under E-commerce › Stores)</span>
                        @endif
                    </label>
                    <div class="form-text">Anything filled in below replaces that field; the name as receipts print it, the QR names, the branch, the guide and the switches always come from here.</div>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="source" id="stSourceOwn" value="own" @checked(old('source', $pay['source']) === 'own')>
                    <label class="form-check-label text-dark" for="stSourceOwn">Use the details below only</label>
                </div>
            </div></div>
        </div>

        <div class="col-xl-6">
            <div class="card h-100 mb-0"><div class="card-body">
                <h5 class="card-title text-dark mb-1"><i class="bx bx-mobile-alt me-1"></i>GCash</h5>
                <p class="text-secondary small">Type the name <b>exactly as GCash prints it on receipts</b>, masked (e.g. <span class="st-mono">JU•••N D.</span>): the AI compares it with the receipt.</p>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label text-dark" for="stGcashNumber">GCash number</label>
                        <input class="form-control" id="stGcashNumber" name="gcashNumber" value="{{ old('gcashNumber', $pay['gcashNumber']) }}" placeholder="09XXXXXXXXX" inputmode="numeric">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label text-dark" for="stGcashName">Name, as GCash prints it</label>
                        <input class="form-control" id="stGcashName" name="gcashNameMasked" maxlength="80" value="{{ old('gcashNameMasked', $pay['gcashNameMasked']) }}">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label text-dark" for="stQrName">QR display name <span class="text-secondary small">(as printed on QR Ph receipts)</span></label>
                        <input class="form-control" id="stQrName" name="qrDisplayName" maxlength="80" value="{{ old('qrDisplayName', $pay['qrDisplayName']) }}">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label text-dark" for="stQrAccount">QR account <span class="text-secondary small">(as printed)</span></label>
                        <input class="form-control" id="stQrAccount" name="qrAccount" maxlength="80" value="{{ old('qrAccount', $pay['qrAccount']) }}">
                    </div>
                </div>
                <hr>
                <h5 class="card-title text-dark mb-1"><i class="bx bx-wallet me-1"></i>Maya</h5>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label text-dark" for="stMayaNumber">Maya number</label>
                        <input class="form-control" id="stMayaNumber" name="mayaNumber" value="{{ old('mayaNumber', $pay['mayaNumber']) }}" placeholder="09XXXXXXXXX" inputmode="numeric">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label text-dark" for="stMayaName">Name, as Maya prints it</label>
                        <input class="form-control" id="stMayaName" name="mayaName" maxlength="80" value="{{ old('mayaName', $pay['mayaName']) }}">
                    </div>
                </div>
            </div></div>
        </div>

        <div class="col-xl-6">
            <div class="card h-100 mb-0"><div class="card-body">
                <h5 class="card-title text-dark mb-1"><i class="bx bx-building-house me-1"></i>Bank transfer</h5>
                <p class="text-secondary small">Offered once the bank, the account name and the number are all set. Bank slips are always checked by a person.</p>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label text-dark" for="stBankName">Bank</label>
                        <input class="form-control" id="stBankName" name="bankName" maxlength="80" value="{{ old('bankName', $pay['bankName']) }}" placeholder="e.g. BPI, BDO">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label text-dark" for="stBankBranch">Branch <span class="text-secondary small">(optional)</span></label>
                        <input class="form-control" id="stBankBranch" name="bankBranch" maxlength="80" value="{{ old('bankBranch', $pay['bankBranch']) }}">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label text-dark" for="stBankAccountName">Account name</label>
                        <input class="form-control" id="stBankAccountName" name="bankAccountName" maxlength="120" value="{{ old('bankAccountName', $pay['bankAccountName']) }}">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label text-dark" for="stBankAccountNumber">Account number</label>
                        <input class="form-control" id="stBankAccountNumber" name="bankAccountNumber" maxlength="40" value="{{ old('bankAccountNumber', $pay['bankAccountNumber']) }}">
                    </div>
                </div>
                <hr>
                <label class="form-label text-dark" for="stInstructions">Instructions for buyers <span class="text-secondary small">(optional)</span></label>
                <textarea class="form-control mb-3" id="stInstructions" name="instructions" rows="3" maxlength="2000">{{ old('instructions', $pay['instructions']) }}</textarea>
                <label class="form-label text-dark" for="stGuide">More for the AI to know about genuine receipts <span class="text-secondary small">(optional)</span></label>
                <textarea class="form-control" id="stGuide" name="receiptGuide" rows="3" maxlength="4000">{{ old('receiptGuide', $pay['receiptGuide']) }}</textarea>
            </div></div>
        </div>

        <div class="col-12">
            <div class="card mb-0"><div class="card-body">
                <h5 class="card-title text-dark mb-1"><i class="bx bx-check-shield me-1"></i>What Streamr's AI may approve on its own</h5>
                <p class="text-secondary small">Only when every check on a GCash QR or Maya receipt passes. Everything else waits in Payment reviews. A passing check proves a consistent screenshot, not that the money arrived: compare the auto-approved list with the wallet now and then.</p>
                <div class="row g-3 align-items-end">
                    <div class="col-md-6 col-xl-3">
                        <div class="form-check form-switch">
                            <input type="hidden" name="autoApprovePlans" value="0">
                            <input class="form-check-input" type="checkbox" role="switch" id="stAutoPlans" name="autoApprovePlans" value="1" @checked(old('autoApprovePlans', $pay['autoApprovePlans']))>
                            <label class="form-check-label text-dark" for="stAutoPlans">Auto-approve plans</label>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <div class="form-check form-switch">
                            <input type="hidden" name="autoApproveLifetime" value="0">
                            <input class="form-check-input" type="checkbox" role="switch" id="stAutoLifetime" name="autoApproveLifetime" value="1" @checked(old('autoApproveLifetime', $pay['autoApproveLifetime']))>
                            <label class="form-check-label text-dark" for="stAutoLifetime">Auto-approve Lifetime</label>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <label class="form-label text-dark" for="stCap">Daily cap for auto-approved credit top-ups (₱)</label>
                        <input class="form-control" type="number" id="stCap" name="creditsDailyCapPhp" min="0" max="100000" step="1" value="{{ old('creditsDailyCapPhp', $pay['creditsDailyCapPhp']) }}" required>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <label class="form-label text-dark" for="stHours">Review hours (what buyers are told)</label>
                        <input class="form-control" type="number" id="stHours" name="reviewHours" min="1" max="168" step="1" value="{{ old('reviewHours', $pay['reviewHours']) }}" required>
                    </div>
                </div>
            </div></div>
        </div>
    </div>
    <div class="d-flex justify-content-end mt-3">
        <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Save payment settings</button>
    </div>
</form>

<div class="row g-3 mt-1">
    @foreach (['gcash' => 'GCash', 'maya' => 'Maya'] as $method => $label)
        <div class="col-md-6">
            <div class="card h-100 mb-0"><div class="card-body">
                <h5 class="card-title text-dark mb-1"><i class="bx bx-qr me-1"></i>{{ $label }} QR</h5>
                <p class="text-secondary small">Used when the store's QR cannot be read (pictures under public/ do not survive a deploy) or with "Use the details below". JPG, PNG or WebP, up to 4 MB.</p>
                <div class="d-flex gap-3 align-items-start flex-wrap">
                    @if ($qrUrls[$method])
                        <img src="{{ $qrUrls[$method] }}" alt="{{ $label }} QR uploaded here" class="qr-preview">
                    @endif
                    <div class="flex-grow-1" style="min-width: 12rem">
                        <form method="POST" action="{{ route('streamr-payments.qr', ['method' => $method]) }}" enctype="multipart/form-data" class="d-flex gap-2 flex-wrap">
                            @csrf
                            <input class="form-control" type="file" name="qr" accept="image/png,image/jpeg,image/webp" required aria-label="{{ $label }} QR picture">
                            <button type="submit" class="btn btn-outline-primary"><i class="bx bx-upload me-1"></i>Upload</button>
                        </form>
                        @if ($qrUrls[$method])
                            <form method="POST" action="{{ route('streamr-payments.qr', ['method' => $method]) }}" class="mt-2" onsubmit="return confirm('Remove this QR?');">
                                @csrf
                                <input type="hidden" name="remove" value="1">
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bx bx-trash me-1"></i>Remove</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div></div>
        </div>
    @endforeach
</div>
