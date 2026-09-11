@php
    use App\Models\SiteSetting;

    $courriel = SiteSetting::lire('courriel');
    $telephone = SiteSetting::lire('telephone');
    $region = SiteSetting::lire('region');
    $instagram = SiteSetting::lire('instagram');
@endphp

<footer class="mt-24 border-t border-ink-800/10 bg-ivory-100">
    <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8">
        <div class="grid gap-12 md:grid-cols-3">
            <div>
                <img src="{{ asset('images/logo-fleora.svg') }}"
                     alt="{{ config('app.name') }}"
                     width="640" height="180"
                     class="-ml-3 h-20 w-auto">

                <p class="mt-4 max-w-xs text-sm leading-relaxed text-ink-600">
                    {{ __('commun.pied.slogan') }}
                </p>
                @if ($region)
                    <p class="mt-4 text-sm text-ink-400">{{ $region }}</p>
                @endif
            </div>

            <div>
                <h2 class="font-sans text-xs font-semibold uppercase tracking-widest text-ink-400">{{ __('commun.pied.navigation') }}</h2>
                <ul class="mt-5 space-y-3 text-sm">
                    <li><a href="{{ route_langue('creations') }}" class="text-ink-600 hover:text-blush-600">{{ __('commun.nav.creations') }}</a></li>
                    <li><a href="{{ route_langue('occasions') }}" class="text-ink-600 hover:text-blush-600">{{ __('commun.nav.occasions') }}</a></li>
                    <li><a href="{{ route_langue('a-propos') }}" class="text-ink-600 hover:text-blush-600">{{ __('commun.nav.a_propos') }}</a></li>
                    <li><a href="{{ route_langue('demande') }}" class="text-ink-600 hover:text-blush-600">{{ __('commun.cta.soumission') }}</a></li>
                </ul>
            </div>

            <div>
                <h2 class="font-sans text-xs font-semibold uppercase tracking-widest text-ink-400">{{ __('commun.pied.joindre') }}</h2>
                <ul class="mt-5 space-y-3 text-sm">
                    @if ($courriel)
                        <li>
                            <a href="mailto:{{ $courriel }}" class="text-ink-600 hover:text-blush-600">{{ $courriel }}</a>
                        </li>
                    @endif
                    @if ($telephone)
                        <li>
                            {{-- tel: sans espaces ni tirets : certains téléphones
                                 refusent de composer un numéro formaté. --}}
                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $telephone) }}"
                               class="text-ink-600 hover:text-blush-600">{{ $telephone }}</a>
                        </li>
                    @endif
                    @if ($instagram)
                        <li>
                            <a href="{{ $instagram }}" target="_blank" rel="noopener noreferrer"
                               class="text-ink-600 hover:text-blush-600">Instagram</a>
                        </li>
                    @endif
                </ul>
            </div>
        </div>

        <div class="mt-14 flex flex-col gap-4 border-t border-ink-800/10 pt-8 text-xs text-ink-400 sm:flex-row sm:items-center sm:justify-between">
            <p>© {{ now()->year }} {{ config('app.name') }}. {{ __('commun.pied.droits') }}</p>
            <a href="{{ route_langue('confidentialite') }}" class="hover:text-blush-600">{{ __('commun.pied.confidentialite') }}</a>
        </div>
    </div>
</footer>
