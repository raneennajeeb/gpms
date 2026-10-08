{{-- Live strength meter for the input marked with data-password-strength-input (see app.js) --}}
<div class="password-strength" data-password-strength>
    <div class="progress-bar">
        <div class="progress-bar__fill fill-danger" data-strength-bar style="width: 0%"></div>
    </div>
    <ul class="password-rules">
        <li data-rule="length">{{ __('auth.rule_length') }}</li>
        <li data-rule="letters">{{ __('auth.rule_letters') }}</li>
        <li data-rule="numbers">{{ __('auth.rule_numbers') }}</li>
    </ul>
</div>
