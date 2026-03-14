{{--
    Raw settings wrapper.
    The actual editors live in smaller accordion partials to keep this shell easy to follow.
--}}
@include('services.modules.partials.form.settings.raw-accordions', [
    'rawJson' => $rawJson,
])
