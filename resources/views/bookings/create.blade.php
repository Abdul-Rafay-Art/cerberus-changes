<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#071b36">
    <title>Request an inspection · Cerberus</title>
    <link rel="stylesheet" href="{{ asset('css/booking.css') }}?v=8">
</head>
<body>
<svg xmlns="http://www.w3.org/2000/svg" width="0" height="0" style="position:absolute" aria-hidden="true">
    <defs>
        <symbol id="icon-user" viewBox="0 0 24 24"><circle cx="12" cy="7" r="3"/><path d="M5 21v-3a7 7 0 0 1 14 0v3z"/></symbol>
        <symbol id="icon-mail" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 6 9 7 9-7"/></symbol>
        <symbol id="icon-phone" viewBox="0 0 24 24"><path d="m7 3 3 5-3 2c1 3 4 6 7 7l2-3 5 3c0 3-2 5-5 4C9 19 5 15 3 8 2 5 4 3 7 3Z"/></symbol>
        <symbol id="icon-pin" viewBox="0 0 24 24"><path d="M19 10c0 6-7 12-7 12S5 16 5 10a7 7 0 0 1 14 0Z"/><circle cx="12" cy="10" r="2.5"/></symbol>
        <symbol id="icon-car" viewBox="0 0 24 24"><path d="m4 10 2-6h12l2 6M3 17v3h3v-3m12 0v3h3v-3M3 10h18v7H3z"/><path d="M6 13h2m8 0h2"/></symbol>
        <symbol id="icon-gauge" viewBox="0 0 24 24"><path d="M4 19a10 10 0 1 1 16 0M12 3v3M4 9l3 2m13-2-3 2m-5 4 4-6"/><circle cx="12" cy="16" r="2"/></symbol>
        <symbol id="icon-calendar" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v5m10-5v5M3 11h18m-14 4h3m4 0h3"/></symbol>
        <symbol id="icon-shield" viewBox="0 0 24 24"><path fill="currentColor" stroke="none" d="m12 1 10 4v7c0 5-6 9-10 12C8 21 2 17 2 12V5z"/><path stroke="white" d="m7 12 3 3 7-7"/></symbol>
        <symbol id="icon-wrench" viewBox="0 0 24 24"><path fill="currentColor" stroke="none" d="M22 2a7 7 0 0 1-8 10L5 22a3 3 0 0 1-4-4l10-9a7 7 0 0 1 9-9l-5 5 4 3z"/></symbol>
        <symbol id="icon-info" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" fill="currentColor" stroke="none"/><path stroke="#071b36" d="M12 11v6m0-10v1"/></symbol>
        <symbol id="icon-send" viewBox="0 0 24 24"><path d="m3 9 18-6-6 18-4-8-8-4Zm8 4L21 3"/></symbol>
        <symbol id="icon-arrow" viewBox="0 0 24 24"><path d="M4 12h16m-6-6 6 6-6 6"/></symbol>
    </defs>
</svg>
<header class="hero">
    <div class="hero-photo" aria-hidden="true"></div>
    <nav class="nav" aria-label="Main navigation">
        <a class="brand" href="{{ route('home') }}"><img src="{{ asset('images/cerberus-logo.jpg') }}" alt=""><span><strong>CERBERUS</strong><small>PRE-PURCHASE VEHICLE INSPECTIONS</small></span></a>
    </nav>
    <div class="hero-content"><div class="eyebrow">Inspection request</div><h1>Tell us about the vehicle</h1><p>Choose a package and share a few details. Our team will get back to you with your inspection request.</p></div>
</header>
<main class="page"><div class="wrap">
    @if ($errors->any())<div class="alert" role="alert"><strong>Please review the highlighted information and try again.</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @php
        $orderedPackages = $packages->sortBy(fn ($package) => match ($package->code) { 'gold' => 0, 'silver' => 1, 'bronze' => 2, default => 3 });
        $selectedPackageId = old('package_id', $orderedPackages->first()?->id);
        $selectedPackage = $orderedPackages->firstWhere('id', $selectedPackageId);
        $contactFields = [
            ['full_name', 'Full name', 'text', 'Enter your full name', 'user', 'name', 160, true],
            ['email', 'Email address', 'email', 'you@example.com', 'mail', 'email', 254, true],
            ['phone', 'Phone number', 'tel', '+44 7123 456789', 'phone', 'tel', 40, true],
            ['address', 'Address', 'text', 'Enter your address', 'pin', 'street-address', null, false],
        ];
        $vehicleFields = [
            ['registration', 'Vehicle registration', 'text', 'e.g. AB12 CDE', 'car', 'off', 20, true],
            ['mileage_display', 'Mileage', 'text', 'e.g. 64,000 miles', 'gauge', 'off', 80, false],
            ['make', 'Make', 'text', 'e.g. Toyota', 'car', 'off', 100, true],
            ['model', 'Model', 'text', 'e.g. Corolla', 'car', 'off', 100, true],
            ['year', 'Year', 'number', 'e.g. 2020', 'calendar', 'off', null, false],
            ['location', 'Inspection location', 'text', 'Town or postcode', 'pin', 'off', 2000, false],
        ];
    @endphp
    <form method="post" action="{{ route('bookings.store') }}" id="booking-form" class="booking-form">
        @csrf
        <input type="hidden" name="submission_key" value="{{ old('submission_key', $submissionKey) }}">
        <input type="text" name="website" value="" tabindex="-1" autocomplete="off" aria-hidden="true" class="honeypot">
        <section class="step" aria-labelledby="package-heading"><h2 id="package-heading">Select an inspection package</h2>
            <div class="packages">
                @forelse ($orderedPackages as $package)
                    @php($tier = in_array($package->code, ['gold', 'silver', 'bronze'], true) ? $package->code : null)
                    <label class="package {{ $tier }}">
                        <input type="radio" name="package_id" value="{{ $package->id }}" data-includes="{{ $package->includedFeatures->pluck('name')->join(', ') }}" @checked((string) $selectedPackageId === (string) $package->id) required>
                        @include('bookings.icon', ['name' => match($tier) {'gold' => 'shield', 'bronze' => 'wrench', default => 'car'}, 'class' => 'package-icon'])
                        <strong class="package-title">@if($tier)Cerberus {{ ucfirst($tier) }}<span class="package-subtitle">Pre-Purchase Vehicle Inspection</span>@else{{ $package->name }}@endif</strong>
                        <span class="money">@if((float) $package->base_price > 0){{ $package->currency }} {{ number_format((float) $package->base_price, 2) }}@else Price on request @endif</span>
                        <p>{{ match($tier) {'gold' => 'Full pre-purchase vehicle inspection based on the approved checklist.', 'silver' => 'Essential inspection for peace of mind.', 'bronze' => 'A quick and reliable overview.', default => $package->description} }}</p>
                    </label>
                @empty<div class="empty">Package selection is being finalized. Please check back when approved package details are available.</div>@endforelse
            </div>
            @if($packages->isNotEmpty())<div class="includes" role="status">@include('bookings.icon', ['name' => 'info', 'class' => ''])<span id="package-includes">Includes: {{ $selectedPackage?->includedFeatures->pluck('name')->join(', ') }}</span></div>@endif
            @foreach($orderedPackages as $package)@foreach($package->eligibleAddons as $addon)
                <label class="addon" data-addon-for="{{ $package->id }}"><input type="checkbox" name="addons[]" value="{{ $addon->id }}" @checked(in_array($addon->id, old('addons', [])))><span>{{ $addon->name }} <strong>+{{ $package->currency }} {{ number_format((float) $addon->addon_price, 2) }}</strong><br><span class="hint">{{ $addon->description }}</span></span></label>
            @endforeach @endforeach
            @error('package_id')<div class="error">{{ $message }}</div>@enderror @error('addons')<div class="error">{{ $message }}</div>@enderror
        </section>
        @foreach (['Your contact details' => $contactFields, 'Vehicle and inspection location' => $vehicleFields] as $heading => $fields)
            <section class="step"><h2>{{ $heading }}</h2><div class="grid">
                @foreach ($fields as [$name, $label, $type, $placeholder, $icon, $autocomplete, $maxLength, $required])
                    <div class="field"><label for="{{ $name }}">{{ $label }} @if($required)<span class="required" aria-hidden="true">*</span>@else<span class="hint">(optional)</span>@endif</label>
                        <div class="input-wrap">@include('bookings.icon', ['name' => $icon, 'class' => ''])<input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name) }}" placeholder="{{ $placeholder }}" autocomplete="{{ $autocomplete }}" @if($maxLength) maxlength="{{ $maxLength }}" @endif @required($required) @if($name === 'year') min="1900" max="{{ (int) date('Y') + 1 }}" @endif @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror></div>
                        @error($name)<div id="{{ $name }}-error" class="error">{{ $message }}</div>@enderror
                    </div>
                @endforeach
            </div></section>
        @endforeach
        <section class="step"><h2>Preferred appointment options</h2><p class="hint">Add up to three future options in priority order. Staff will confirm an appointment separately.</p>
            <div class="options">@for ($i = 0; $i < 3; $i++)<div class="field"><label for="preference-display-{{ $i }}">Option {{ $i + 1 }} @if($i === 0)<span class="required" aria-hidden="true">*</span>@else<span class="hint">(optional)</span>@endif</label><div class="appointment-picker" data-appointment-picker><input id="preference-display-{{ $i }}" class="appointment-display" type="text" placeholder="Choose date and time" autocomplete="off" aria-haspopup="dialog" aria-expanded="false" @required($i === 0)><input type="hidden" name="preferences[]" value="{{ old('preferences.'.$i) }}"><button class="appointment-trigger" type="button" aria-label="Open date and time picker">@include('bookings.icon', ['name' => 'calendar', 'class' => ''])</button><div class="appointment-popover" role="dialog" aria-label="Choose preferred appointment date and time" hidden><div class="appointment-calendar-head"><button type="button" data-month-step="-1" aria-label="Previous month">‹</button><strong data-month-label></strong><button type="button" data-month-step="1" aria-label="Next month">›</button></div><div class="appointment-weekdays" aria-hidden="true"><span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span></div><div class="appointment-days" role="grid"></div><div class="appointment-time"><label>Time</label><select data-hour aria-label="Hour">@for($hour = 1; $hour <= 12; $hour++)<option value="{{ sprintf('%02d', $hour) }}" @selected($hour === 9)>{{ sprintf('%02d', $hour) }}</option>@endfor</select><span>:</span><select data-minute aria-label="Minute">@for($minute = 0; $minute < 60; $minute++)<option value="{{ sprintf('%02d', $minute) }}">{{ sprintf('%02d', $minute) }}</option>@endfor</select><select data-period aria-label="AM or PM"><option>am</option><option>pm</option></select></div><div class="appointment-actions"><button type="button" data-clear>Clear</button><button type="button" data-today>Today</button><button type="button" class="appointment-done" data-done>Done</button></div></div></div>@error('preferences.'.$i)<div class="error">{{ $message }}</div>@enderror</div>@endfor</div>
            @error('preferences')<div class="error">{{ $message }}</div>@enderror
        </section>
        <section class="step"><h2>Review and submit</h2><label class="consent"><input type="checkbox" name="consent" value="1" required @checked(old('consent'))><span>I confirm these details are accurate and understand this is an inspection request. The appointment is not confirmed until Cerberus contacts me.</span></label>@error('consent')<div class="error">{{ $message }}</div>@enderror
            <button class="submit" type="submit" @disabled($packages->isEmpty())>@include('bookings.icon', ['name' => 'send', 'class' => '']) Submit inspection request @include('bookings.icon', ['name' => 'arrow', 'class' => ''])</button>
        </section>
    </form>
</div></main>
<script>
(() => {
    const form = document.getElementById('booking-form');
    const radios = [...form.querySelectorAll('input[name="package_id"]')];
    const includes = document.getElementById('package-includes');
    const refresh = () => {
        const selected = radios.find(radio => radio.checked);
        if (includes) includes.textContent = selected ? `Includes: ${selected.dataset.includes || 'Vehicle inspection'}` : 'Select a package to view its inclusions';
        form.querySelectorAll('[data-addon-for]').forEach(row => {
            const available = !!selected && row.dataset.addonFor === selected.value;
            row.hidden = !available;
            if (!available) row.querySelector('input').checked = false;
        });
    };
    radios.forEach(radio => radio.addEventListener('change', refresh));
    refresh();

    const pad = value => String(value).padStart(2, '0');
    form.querySelectorAll('[data-appointment-picker]').forEach(picker => {
        const display = picker.querySelector('.appointment-display');
        const stored = picker.querySelector('input[type="hidden"]');
        const popover = picker.querySelector('.appointment-popover');
        const days = picker.querySelector('[data-month-label]');
        const dayGrid = picker.querySelector('.appointment-days');
        const hour = picker.querySelector('[data-hour]');
        const minute = picker.querySelector('[data-minute]');
        const period = picker.querySelector('[data-period]');
        let selected = stored.value ? new Date(stored.value) : null;
        let shown = selected && !Number.isNaN(selected.getTime()) ? new Date(selected.getFullYear(), selected.getMonth(), 1) : new Date(new Date().getFullYear(), new Date().getMonth(), 1);

        const syncValue = () => {
            if (!selected) { stored.value = ''; display.value = ''; return; }
            let h = Number(hour.value) % 12 + (period.value === 'pm' ? 12 : 0);
            selected.setHours(h, Number(minute.value), 0, 0);
            stored.value = `${selected.getFullYear()}-${pad(selected.getMonth() + 1)}-${pad(selected.getDate())}T${pad(h)}:${pad(selected.getMinutes())}`;
            display.value = `${selected.toLocaleDateString('en-GB', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' })} · ${hour.value}:${minute.value} ${period.value.toUpperCase()}`;
        };
        const renderCalendar = () => {
            days.textContent = shown.toLocaleDateString('en-GB', { month: 'long', year: 'numeric' });
            dayGrid.replaceChildren();
            const offset = new Date(shown.getFullYear(), shown.getMonth(), 1).getDay();
            const count = new Date(shown.getFullYear(), shown.getMonth() + 1, 0).getDate();
            for (let blank = 0; blank < offset; blank++) { const spacer = document.createElement('span'); spacer.className = 'appointment-day-spacer'; dayGrid.append(spacer); }
            for (let number = 1; number <= count; number++) {
                const button = document.createElement('button');
                const isSelected = selected && selected.getFullYear() === shown.getFullYear() && selected.getMonth() === shown.getMonth() && selected.getDate() === number;
                button.type = 'button'; button.textContent = number; button.setAttribute('aria-label', new Date(shown.getFullYear(), shown.getMonth(), number).toLocaleDateString('en-GB', { dateStyle: 'full' }));
                if (isSelected) { button.classList.add('selected'); button.setAttribute('aria-pressed', 'true'); }
                if (number === new Date().getDate() && shown.getMonth() === new Date().getMonth() && shown.getFullYear() === new Date().getFullYear()) button.classList.add('today');
                button.addEventListener('click', () => { selected = new Date(shown.getFullYear(), shown.getMonth(), number); syncValue(); renderCalendar(); });
                dayGrid.append(button);
            }
        };
        const open = () => {
            popover.hidden = false;
            if (popover.parentElement !== document.body) document.body.append(popover);
            display.setAttribute('aria-expanded', 'true');
            renderCalendar();
            const field = picker.getBoundingClientRect();
            const width = Math.min(310, window.innerWidth - 16);
            popover.style.width = `${width}px`;
            const height = popover.getBoundingClientRect().height;
            let top = field.bottom + window.scrollY + 8;
            if (field.bottom + height + 8 > window.innerHeight) top = Math.max(window.scrollY + 8, field.top + window.scrollY - height - 8);
            const left = Math.min(Math.max(8, field.left + window.scrollX), window.scrollX + window.innerWidth - width - 8);
            popover.style.top = `${top}px`;
            popover.style.left = `${left}px`;
        };
        const close = () => { popover.hidden = true; display.setAttribute('aria-expanded', 'false'); picker.append(popover); };
        if (selected && !Number.isNaN(selected.getTime())) {
            let h = selected.getHours(); period.value = h >= 12 ? 'pm' : 'am'; h = h % 12 || 12; hour.value = pad(h); minute.value = pad(selected.getMinutes()); syncValue();
        }
        display.addEventListener('focus', open); display.addEventListener('click', open);
        picker.querySelector('.appointment-trigger').addEventListener('click', () => { open(); display.focus(); });
        picker.querySelectorAll('[data-month-step]').forEach(button => button.addEventListener('click', () => { shown.setMonth(shown.getMonth() + Number(button.dataset.monthStep)); renderCalendar(); }));
        [hour, minute, period].forEach(control => control.addEventListener('change', syncValue));
        picker.querySelector('[data-today]').addEventListener('click', () => { const now = new Date(); selected = new Date(now.getFullYear(), now.getMonth(), now.getDate()); shown = new Date(now.getFullYear(), now.getMonth(), 1); syncValue(); renderCalendar(); });
        picker.querySelector('[data-clear]').addEventListener('click', () => { selected = null; syncValue(); renderCalendar(); });
        picker.querySelector('[data-done]').addEventListener('click', close);
        document.addEventListener('pointerdown', event => { if (!picker.contains(event.target) && !popover.contains(event.target)) close(); });
        display.addEventListener('keydown', event => { if (event.key === 'Escape') close(); });
    });
})();
</script>
</body>
</html>
