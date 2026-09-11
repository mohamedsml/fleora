@php
    $courriel = config('fleora.contact.courriel');
    $mois = config('fleora.conservation.demandes_mois');
    $maj = \Carbon\Carbon::create(2026, 9, 10);
@endphp

<x-layout titre="Politique de confidentialité"
          description="Comment Fleora recueille, utilise et protège vos renseignements personnels, conformément à la Loi 25 du Québec.">

    <div class="mx-auto max-w-2xl px-6 py-20 lg:py-28">

        <h1 class="font-display text-4xl text-ink-900 sm:text-5xl">
            Politique de confidentialité
        </h1>

        <p class="mt-4 text-sm text-ink-400">
            Dernière mise à jour : {{ $maj->translatedFormat('j F Y') }}
        </p>

        {{-- `prose` suppose le plugin typography de Tailwind. Les styles sont
             posés à la main pour ne pas ajouter une dépendance au seul profit
             de deux pages légales. --}}
        <div class="mt-12 space-y-10 leading-relaxed text-ink-600">

            <p>
                {{ config('app.name') }} recueille certains renseignements personnels pour
                répondre à vos demandes et réaliser vos commandes. Cette page explique
                lesquels, pourquoi, combien de temps nous les conservons, et quels sont
                vos droits. Elle est rédigée en langage clair, conformément à la
                <strong class="text-ink-900">Loi 25</strong> du Québec.
            </p>

            <section>
                <h2 class="font-display text-2xl text-ink-900">Responsable de la protection des renseignements</h2>
                <p class="mt-4">
                    {{ config('app.name') }} est responsable des renseignements personnels
                    qu'elle détient. Pour toute question relative à cette politique ou à
                    l'exercice de vos droits :
                    <a href="mailto:{{ $courriel }}" class="text-ink-900 underline underline-offset-4 hover:text-terracotta-600">{{ $courriel }}</a>
                </p>
            </section>

            <section>
                <h2 class="font-display text-2xl text-ink-900">Renseignements recueillis</h2>
                <p class="mt-4">
                    Uniquement ceux que vous nous transmettez volontairement par le
                    formulaire de demande ou par courriel :
                </p>
                <ul class="mt-4 space-y-2 pl-5">
                    <li class="list-disc">Votre nom et votre adresse courriel — pour vous répondre.</li>
                    <li class="list-disc">Votre numéro de téléphone et votre ville, si vous les fournissez — pour la logistique de livraison.</li>
                    <li class="list-disc">Les détails de votre projet : occasion, date, couleurs, budget approximatif, commentaires.</li>
                    <li class="list-disc">Les images d'inspiration que vous choisissez de joindre.</li>
                </ul>
                <p class="mt-4">
                    Nous conservons également votre adresse IP et la date de votre
                    consentement. Ces deux éléments servent à limiter les envois
                    automatisés et à prouver que vous avez bien consenti — ils ne sont
                    utilisés à aucune autre fin.
                </p>
                <p class="mt-4">
                    <strong class="text-ink-900">Nous ne recueillons aucune donnée de paiement
                    sur ce site.</strong> Les paiements, le cas échéant, se font hors ligne.
                </p>
            </section>

            <section>
                <h2 class="font-display text-2xl text-ink-900">Utilisation</h2>
                <p class="mt-4">Ces renseignements servent exclusivement à :</p>
                <ul class="mt-4 space-y-2 pl-5">
                    <li class="list-disc">préparer et vous transmettre une proposition personnalisée ;</li>
                    <li class="list-disc">réaliser et livrer votre commande ;</li>
                    <li class="list-disc">vous envoyer notre infolettre, uniquement si vous l'avez explicitement demandé.</li>
                </ul>
                <p class="mt-4">
                    <strong class="text-ink-900">Nous ne vendons ni ne louons vos renseignements
                    à qui que ce soit</strong>, et nous ne les utilisons pas à des fins
                    publicitaires ciblées.
                </p>
            </section>

            <section>
                <h2 class="font-display text-2xl text-ink-900">Communication à des tiers</h2>
                <p class="mt-4">
                    Vos renseignements ne sont communiqués à personne, sauf lorsque c'est
                    strictement nécessaire à la réalisation de votre commande — par
                    exemple un transporteur, qui reçoit alors uniquement l'adresse de
                    livraison.
                </p>
                <p class="mt-4">
                    Le site et les courriels sont hébergés par Hostinger. Les données
                    peuvent donc être traitées à l'extérieur du Québec. Nous nous
                    assurons que le niveau de protection reste adéquat.
                </p>
            </section>

            <section>
                <h2 class="font-display text-2xl text-ink-900">Durée de conservation</h2>
                <p class="mt-4">
                    Vos renseignements sont conservés <strong class="text-ink-900">{{ $mois }} mois
                    après notre dernier échange</strong>, puis supprimés automatiquement — y
                    compris les images que vous avez jointes. Cette suppression est
                    définitive et exécutée par un traitement planifié, non par une
                    intervention manuelle.
                </p>
                <p class="mt-4">
                    Les documents comptables liés à une commande réalisée sont conservés
                    plus longtemps, comme l'exige la loi fiscale.
                </p>
            </section>

            <section>
                <h2 class="font-display text-2xl text-ink-900">Témoins (cookies)</h2>
                <p class="mt-4">
                    Ce site n'utilise <strong class="text-ink-900">aucun témoin publicitaire ni
                    de suivi</strong>. Seul un témoin technique est déposé pour faire
                    fonctionner les formulaires en toute sécurité ; il ne permet pas de
                    vous identifier et disparaît à la fermeture de votre navigateur.
                </p>
            </section>

            <section>
                <h2 class="font-display text-2xl text-ink-900">Vos droits</h2>
                <p class="mt-4">Vous pouvez à tout moment :</p>
                <ul class="mt-4 space-y-2 pl-5">
                    <li class="list-disc"><strong class="text-ink-900">Consulter</strong> les renseignements que nous détenons sur vous.</li>
                    <li class="list-disc"><strong class="text-ink-900">Faire corriger</strong> un renseignement inexact ou incomplet.</li>
                    <li class="list-disc"><strong class="text-ink-900">Demander la suppression</strong> de vos renseignements.</li>
                    <li class="list-disc"><strong class="text-ink-900">Retirer votre consentement</strong>, ce qui met fin au traitement.</li>
                    <li class="list-disc"><strong class="text-ink-900">Vous désabonner</strong> de l'infolettre en un clic, depuis n'importe quel envoi.</li>
                </ul>
                <p class="mt-4">
                    Écrivez à <a href="mailto:{{ $courriel }}" class="text-ink-900 underline underline-offset-4 hover:text-terracotta-600">{{ $courriel }}</a>.
                    Nous répondons dans un délai maximal de 30 jours.
                </p>
            </section>

            <section>
                <h2 class="font-display text-2xl text-ink-900">Sécurité</h2>
                <p class="mt-4">
                    Le site est servi en HTTPS, l'accès à l'administration est protégé par
                    mot de passe, et les images que vous nous envoyez sont stockées sur un
                    espace privé, inaccessible depuis le web. Les sauvegardes de la base
                    sont chiffrées au repos par l'hébergeur.
                </p>
            </section>

            <section>
                <h2 class="font-display text-2xl text-ink-900">Plainte</h2>
                <p class="mt-4">
                    Si notre réponse ne vous satisfait pas, vous pouvez porter plainte
                    auprès de la
                    <a href="https://www.cai.gouv.qc.ca" target="_blank" rel="noopener"
                       class="text-ink-900 underline underline-offset-4 hover:text-terracotta-600">Commission d'accès à l'information du Québec</a>.
                </p>
            </section>

            <section>
                <h2 class="font-display text-2xl text-ink-900">Modifications</h2>
                <p class="mt-4">
                    Toute modification de cette politique sera publiée sur cette page, avec
                    une date de mise à jour révisée.
                </p>
            </section>
        </div>
    </div>
</x-layout>
