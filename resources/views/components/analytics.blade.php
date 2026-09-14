{{--
    Google Analytics 4.

    Ne s'affiche que si GOOGLE_ANALYTICS_ID est renseigné : sans cette garde,
    les visites locales et celles de la suite de tests iraient gonfler les
    statistiques réelles.

    Exclu de l'administration par le layout : compter ses propres passages dans
    /admin fausserait les chiffres de fréquentation du site public.

    ⚠️ Dépose les témoins `_ga` et `_ga_*`, conservés deux ans. La section
    « Témoins » de la politique de confidentialité le mentionne, comme l'exige
    la Loi 25.
--}}
@php($mesure = config('services.google_analytics.id'))

{{-- Les adresses exclues ne chargent pas le tag : sans cela, vérifier une
     page après chaque modification gonflerait les chiffres de Google, que
     l'on ne peut plus corriger après coup. La même liste sert au comptage
     interne — voir EnregistrerVisite::ipExclue(). --}}
@if ($mesure && ! \App\Http\Middleware\EnregistrerVisite::ipExclue(request()->ip()))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $mesure }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', '{{ $mesure }}', {
            // Tronque le dernier octet de l'adresse IP avant enregistrement.
            // GA4 le fait déjà par défaut, mais le déclarer rend l'intention
            // vérifiable dans le code plutôt que supposée.
            anonymize_ip: true,
        });
    </script>
@endif
