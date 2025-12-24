@use(App\Settings\SystemPreferences)

<!-- Start footer -->
<footer class="custom-footer bg-dark py-5 position-relative">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mt-4">
                <div>
                    <div>
                        <img src="{{ asset(app(SystemPreferences::class)->logo_lg) }}" alt="logo light" height="40">
                    </div>
                    <div class="mt-4 fs-18">
                        <p>We provide solution for your organization through digital.</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-7 ms-lg-auto">
                <div class="row">
                    <div class="col-sm-4 mt-4">
                        <h5 class="text-white mb-0">About</h5>
                        <div class="text-muted mt-3">
                            <ul class="list-unstyled ff-secondary footer-list">
                                <li><a href="#">Company</a></li>
                                <li><a href="#">Case Studies</a></li>
                                <li><a href="#">Affiliate Programs</a></li>
                                <li><a href="#">Become a Partners</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-sm-4 mt-4">
                        <h5 class="text-white mb-0">Reference</h5>
                        <div class="text-muted mt-3">
                            <ul class="list-unstyled ff-secondary footer-list">
                                <li><a href="#">Version History</a></li>
                                <li><a href="#">Term & Conditions</a></li>
                                <li><a href="#">Privacy Policy</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-sm-4 mt-4">
                        <h5 class="text-white mb-0">Contact</h5>
                        <div class="text-muted mt-3">
                            <ul class="list-unstyled ff-secondary footer-list">
                                <li><a href="#">product@aidan.my</a></li>
                                <li><a href="#">+6016.937.1340 [Shafiqa]</a></li>
                                <li><a href="#">+6013.294.6282 [Ali Abid]</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row text-center text-sm-start align-items-center mt-5">
            <div class="col-sm-6">
                <div>
                    <p class="copy-rights mb-0">
                        © {{ \Carbon\Carbon::now()->year }} {{ config('app.name') }}.
                        {{ __('messages.all_rights_reserved') }}.
                    </p>
                </div>
            </div>
        </div>
    </div>
</footer>
<!-- end footer -->
