<footer class="footer">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                © {{ \Carbon\Carbon::now()->year }} {{ config('app.name') }}. {{ __('messages.all_rights_reserved') }}.
            </div>
        </div>
    </div>
</footer>
