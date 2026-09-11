@props(['donnees'])

{{-- JSON-LD : le format recommandé par Google, indépendant du balisage HTML.
     JSON_UNESCAPED_SLASHES garde les URL lisibles ; JSON_UNESCAPED_UNICODE
     évite d'échapper les accents, qui alourdiraient inutilement la sortie. --}}
<script type="application/ld+json">
{!! json_encode($donnees, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
