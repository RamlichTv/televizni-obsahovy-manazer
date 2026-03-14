{{--
    Guided settings wrapper.
    The provider and module-settings sections stay separate so each concern can stay compact.
--}}
<div class="space-y-4">
    @include('services.modules.partials.form.settings.module-settings-guided')

    @include('services.modules.partials.form.settings.provider-guided', [
        'providers' => $providers,
        'pickers' => $pickers,
        'settingsUi' => $settingsUi,
    ])
</div>
