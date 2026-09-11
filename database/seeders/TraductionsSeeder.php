<?php

namespace Database\Seeders;

use App\Models\Creation;
use App\Models\Faq;
use App\Models\Occasion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Traductions anglaises du contenu.
 *
 * ⚠️ RELECTURE REQUISE avant mise en ligne publique. L'anglais ci-dessous est
 * correct et idiomatique, mais il porte le ton de la marque — « nous faisons
 * souvent l'impossible » ne se traduit pas littéralement. Un anglophone doit
 * le relire : sur un site premium, un texte approximatif se remarque et coûte
 * plus cher qu'une page unilingue.
 *
 * ⚠️ IDEMPOTENT ET NON DESTRUCTIF. Ne remplit que les champs anglais VIDES :
 * une traduction corrigée dans Filament n'est jamais écrasée. Sûr à rejouer en
 * production.
 *
 *     php artisan db:seed --class=TraductionsSeeder
 */
class TraductionsSeeder extends Seeder
{
    public function run(): void
    {
        // Transaction : les slugs portent une contrainte d'unicité. Un doublon
        // ferait échouer le seeder à mi-parcours, laissant la moitié du contenu
        // traduit et l'autre non.
        DB::transaction(function (): void {
            $this->traduireLesOccasions();
            $this->traduireLesCreations();
            $this->traduireLesFaqs();
        });

        $this->command?->info('Traductions appliquées (champs vides uniquement).');
    }

    /**
     * Remplit les colonnes anglaises restées vides.
     *
     * @param  array<string, string>  $valeurs
     */
    private function completer(object $modele, array $valeurs): void
    {
        $aEcrire = [];

        foreach ($valeurs as $colonne => $valeur) {
            if (blank($modele->{$colonne})) {
                $aEcrire[$colonne] = $valeur;
            }
        }

        if ($aEcrire !== []) {
            $modele->update($aEcrire);
        }
    }

    private function traduireLesOccasions(): void
    {
        // Les meta et le contenu SEO portent le référencement anglophone : une
        // page sans texte n'a rien à indexer au-delà de son titre.
        $traductions = [
            'mariage' => [
                'nom_en' => 'Weddings',
                'slug_en' => 'wedding',
                'intro_en' => 'Guest favours, bridesmaid proposals and keepsake boxes for the couple — each piece assembled by hand in your colours.',
                'meta_title_en' => 'Personalised Wedding Boxes | Fleora Montreal',
                'meta_description_en' => 'Handmade guest favours, bridesmaid proposal boxes and keepsake gifts for your wedding. Custom colours and names. Delivery across Greater Montreal.',
                'contenu_seo_en' => "A wedding is made of a hundred small gestures, and the ones your guests take home stay with them the longest.\n\nWe create guest favours in your palette, bridesmaid proposal boxes that carry each name, and keepsake boxes for the couple. Every piece is assembled by hand — no two orders are the same.\n\nWe deliver throughout Greater Montreal, Laval and the North Shore, and we work to your date.",
            ],
            'baby-shower' => [
                'nom_en' => 'Baby showers',
                'slug_en' => 'baby-shower',
                'intro_en' => 'Boxes carrying the baby\'s name, in the colours you have chosen for the day.',
                'meta_title_en' => 'Personalised Baby Shower Gifts | Fleora Laval',
                'meta_description_en' => 'Decorative boxes with the baby\'s name, flowers and colours of your choice. Unique creations for baby showers. Free quote within 24 hours.',
                'contenu_seo_en' => "A baby shower is the first celebration of a story that is just beginning — and the gift you choose becomes part of it.\n\nWe create boxes that carry the baby's name, composed in the palette you have picked for the day. Soft blush, sage, powder blue, or something entirely yours.\n\nTell us the name and the date; we reply within 24 hours with a proposal.",
            ],
            'bapteme' => [
                'nom_en' => 'Baptisms',
                'slug_en' => 'baptism',
                'intro_en' => 'Keepsake boxes for a day that is remembered — the name, the date, the colours of the ceremony.',
                'meta_title_en' => 'Personalised Baptism Gifts | Fleora Quebec',
                'meta_description_en' => 'Handmade keepsake boxes for baptisms, with the child\'s name and the colours of the ceremony. Delivery across Greater Montreal.',
                'contenu_seo_en' => "A baptism is one of those days a family returns to for years. The gift should hold up just as long.\n\nWe make keepsake boxes carrying the child's name and the date, in the colours of the ceremony — often ivory, gold and soft green, but the choice is yours.\n\nEternal flowers keep their shape indefinitely, so the piece stays on the shelf long after the day itself.",
            ],
            'anniversaire' => [
                'nom_en' => 'Birthdays',
                'slug_en' => 'birthday',
                'intro_en' => 'A gift that carries their name — because the thought shows before the box is even opened.',
                'meta_title_en' => 'Personalised Birthday Gift Boxes | Fleora Montreal',
                'meta_description_en' => 'Decorative boxes with a name, flowers and the colours they love. Handmade birthday gifts. Free quote within 24 hours.',
                'contenu_seo_en' => "The gifts people remember are rarely the most expensive ones. They are the ones that were clearly chosen for them.\n\nWe create boxes carrying a first name, composed in the colours that person actually likes. A milestone birthday, a first one, or simply a Tuesday that deserved marking.\n\nTell us who it is for and what they love; we take it from there.",
            ],
            'graduation' => [
                'nom_en' => 'Graduations',
                'slug_en' => 'graduation',
                'intro_en' => 'Marking the end of years of work with something that stays on the shelf.',
                'meta_title_en' => 'Personalised Graduation Gifts | Fleora Montreal',
                'meta_description_en' => 'Handmade graduation boxes with the graduate\'s name and school colours. Custom creations, delivery across Greater Montreal.',
                'contenu_seo_en' => "Years of work end in a single afternoon. A gift that carries their name makes the moment last a little longer.\n\nWe create graduation boxes in school colours or in a palette of your choosing, with the name and year inscribed. They sit well on a desk or a shelf, which is exactly where they tend to end up.\n\nOrder two to three weeks ahead — graduation season is our busiest.",
            ],
            'cadeau-personnalise' => [
                'nom_en' => 'Personalised gifts',
                'slug_en' => 'personalised-gift',
                'intro_en' => 'No particular occasion needed. Sometimes that is the best reason of all.',
                'meta_title_en' => 'Custom Personalised Gift Boxes | Fleora Quebec',
                'meta_description_en' => 'Handmade decorative boxes with a name, colours and theme of your choice. For any occasion — or none at all.',
                'contenu_seo_en' => "A thank you. An apology. A welcome home. Some of the gifts that matter most have no date attached to them.\n\nWe create boxes for exactly those moments: a name, a short message, the colours that mean something to the person receiving it.\n\nThere is no minimum order for a single piece. Tell us the story and we will propose something.",
            ],
        ];

        foreach ($traductions as $slug => $valeurs) {
            if ($occasion = Occasion::where('slug_fr', $slug)->first()) {
                $this->completer($occasion, $valeurs);
            }
        }
    }

    private function traduireLesCreations(): void
    {
        $traductions = [
            'boite-blush-ivoire' => [
                'titre_en' => 'Blush and ivory box',
                'slug_en' => 'blush-and-ivory-box',
                'description_en' => 'Eternal roses in blush and ivory, set in a rigid box finished by hand. The name is inscribed in gold leaf.',
            ],
            'coffret-prenom-bleu' => [
                'titre_en' => 'Sky blue name box',
                'slug_en' => 'sky-blue-name-box',
                'description_en' => 'A soft blue composition with the first name at the centre — a favourite for baby showers and baptisms.',
            ],
            'boite-ronde-pivoines' => [
                'titre_en' => 'Round peony box',
                'slug_en' => 'round-peony-box',
                'description_en' => 'Generous peonies arranged in a round box. Substantial enough to stand on its own as a centrepiece.',
            ],
            'lot-invites-mariage' => [
                'titre_en' => 'Wedding guest favours',
                'slug_en' => 'wedding-guest-favours',
                'description_en' => 'Small boxes for wedding guests, each carrying a name. Ordered as a set, assembled one by one.',
            ],
            'coffret-graduation' => [
                'titre_en' => 'Graduation box',
                'slug_en' => 'graduation-box',
                'description_en' => 'School colours, the graduate\'s name and the year — a keepsake that sits well on a desk.',
            ],
            'boite-automne' => [
                'titre_en' => 'Autumn box (draft)',
                'slug_en' => 'autumn-box',
                'description_en' => 'Warm tones and dried textures for the autumn season.',
            ],
        ];

        foreach ($traductions as $slug => $valeurs) {
            if ($creation = Creation::where('slug_fr', $slug)->first()) {
                $this->completer($creation, $valeurs);
            }
        }
    }

    private function traduireLesFaqs(): void
    {
        // Clé : la question française, qui sert déjà d'identifiant dans
        // DemoSeeder::updateOrCreate.
        $traductions = [
            // ── Ordering ────────────────────────────────────────────────
            'Combien de temps à l’avance dois-je commander ?' => [
                'categorie_en' => 'Ordering',
                'question_en' => 'How far in advance should I order?',
                'reponse_en' => "Ideally two to three weeks before your event. For larger quantities, allow four weeks.\n\nIs your date sooner? Write to us anyway — we often manage the impossible, and we will tell you honestly if it is not feasible.",
            ],
            'Quel est le montant minimum de commande ?' => [
                'categorie_en' => 'Ordering',
                'question_en' => 'Is there a minimum order?',
                'reponse_en' => 'No minimum for a single piece. For guest favours, orders start at 10 units.',
            ],
            'Comment se passe une commande ?' => [
                'categorie_en' => 'Ordering',
                'question_en' => 'How does ordering work?',
                'reponse_en' => 'You describe your project through the form. We come back within 24 hours with a proposal and a price. Once confirmed, we assemble your creation and send you a photo before delivery.',
            ],
            'Puis-je modifier ma commande après l’avoir validée ?' => [
                'categorie_en' => 'Ordering',
                'question_en' => 'Can I change my order after confirming it?',
                'reponse_en' => 'As long as assembly has not started, yes, at no charge. After that it depends on the change — write to us and we will find a solution.',
            ],

            // ── Personalisation ─────────────────────────────────────────
            'Puis-je faire inscrire un prénom sur la boîte ?' => [
                'categorie_en' => 'Personalisation',
                'question_en' => 'Can you inscribe a name on the box?',
                'reponse_en' => 'Absolutely — it is what we do best. A name, a date, a short message: you choose the text, the lettering and the colour.',
            ],
            'Utilisez-vous des fleurs naturelles ou artificielles ?' => [
                'categorie_en' => 'Personalisation',
                'question_en' => 'Do you use fresh or artificial flowers?',
                'reponse_en' => "Both. High-end artificial flowers last indefinitely; fresh ones bring a scent and a softness nothing else matches, but last only a few days.\n\nIf you are unsure, say so — we will advise you based on your event.",
            ],
            'Puis-je choisir mes propres couleurs ?' => [
                'categorie_en' => 'Personalisation',
                'question_en' => 'Can I choose my own colours?',
                'reponse_en' => 'Yes. Send us an inspiration photo, a colour code, or simply the theme of your event — we compose from there.',
            ],
            'Puis-je m’inspirer d’une création de la galerie ?' => [
                'categorie_en' => 'Personalisation',
                'question_en' => 'Can I start from a creation in the gallery?',
                'reponse_en' => 'Of course. Every listing has an "I want something like this" button that pre-fills your request. We then adapt the colours and the text.',
            ],

            // ── Delivery ────────────────────────────────────────────────
            'Livrez-vous à Laval et sur la Rive-Nord ?' => [
                'categorie_en' => 'Delivery',
                'question_en' => 'Do you deliver to Laval and the North Shore?',
                'reponse_en' => 'Yes. Pickup is free, delivery is charged by distance. We serve Montreal, Laval, the North Shore and the South Shore.',
            ],
            'Puis-je venir chercher ma commande ?' => [
                'categorie_en' => 'Delivery',
                'question_en' => 'Can I pick up my order?',
                'reponse_en' => 'Yes, by appointment. We agree on a time together once your creation is ready.',
            ],

            // ── Pricing ─────────────────────────────────────────────────
            'Pourquoi les prix sont-ils indiqués « à partir de » ?' => [
                'categorie_en' => 'Pricing',
                'question_en' => 'Why are prices shown as "from"?',
                'reponse_en' => 'Every creation is unique: the price depends on size, the flowers chosen, the personalisation and the quantity. The ranges give you an order of magnitude; your quote gives you the exact figure.',
            ],
            'La soumission est-elle payante ?' => [
                'categorie_en' => 'Pricing',
                'question_en' => 'Is the quote free?',
                'reponse_en' => 'Yes, free and with no obligation.',
            ],
        ];

        foreach ($traductions as $questionFr => $valeurs) {
            if ($faq = Faq::where('question_fr', $questionFr)->first()) {
                $this->completer($faq, $valeurs);
            }
        }
    }
}
