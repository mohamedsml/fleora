-- ────────────────────────────────────────────────────────────────
-- Contenu des six pages d’occasion — Fleora
--
-- Généré depuis la base locale le 2026-09-11 19:28.
-- À exécuter sur la base de PRODUCTION.
--
-- Les occasions sont ciblées par slug_fr, jamais par id : les
-- identifiants peuvent différer entre les deux bases.
--
-- Transaction : soit les six passent, soit aucune. Une mise à jour
-- partielle laisserait le site à moitié traduit.
-- ────────────────────────────────────────────────────────────────

START TRANSACTION;

-- Mariage (/occasions/mariage)
UPDATE occasions SET
    icone                 = 'heroicon-o-heart',
    cta_fr                = 'Créer pour mon mariage',
    cta_en                = 'Create for my wedding',
    intro_fr              = 'Des créations florales et cadeaux personnalisés imaginés sur mesure pour votre mariage. Créées avec soin dans la région de Montréal, chaque pièce est pensée pour s’harmoniser à votre journée.',
    intro_en              = 'Floral creations and personalised gifts made to measure for your wedding. Created with care in the Montreal area, each piece is designed to match your day.',
    meta_title_fr         = 'Cadeaux de mariage personnalisés à Montréal',
    meta_title_en         = 'Personalised Wedding Gifts in Montreal',
    meta_description_fr   = 'Créations florales et cadeaux personnalisés pour votre mariage : cadeaux d’invités, demoiselles d’honneur, souvenirs des mariés. Montréal et Laval.',
    meta_description_en   = 'Floral creations and personalised gifts for your wedding: guest favours, bridesmaid proposals and keepsakes for the couple. Greater Montreal and Laval.',
    contenu_seo_fr        = 'Un mariage se compose de cent petits gestes, et ceux que vos invités rapportent chez eux sont ceux dont ils se souviennent le plus longtemps.\n\nNous créons des cadeaux d’invités dans votre palette, des boîtes pour demander à vos demoiselles d’honneur — chacune portant son prénom — et des créations souvenirs pour les mariés. Chaque pièce est assemblée à la main, dans les couleurs et le style de votre journée.\n\nFleurs naturelles ou artificielles, selon ce qui convient le mieux à votre événement : les artificielles se conservent des années, les naturelles apportent une fraîcheur incomparable le jour même. Nous vous conseillons selon votre date, votre lieu et la durée de votre réception.\n\nNous desservons le Grand Montréal, Laval et la Rive-Nord. Pour les grandes quantités, prévoyez quatre semaines ; pour une pièce unique, deux à trois semaines suffisent généralement. Votre date approche ? Écrivez-nous quand même — nous vous dirons franchement si c’est réalisable.\n\nRacontez-nous votre journée : nous vous répondons sous 24 heures avec une proposition personnalisée et un prix, sans engagement.',
    contenu_seo_en        = 'A wedding is made of a hundred small gestures, and the ones your guests take home stay with them the longest.\n\nWe create guest favours in your palette, bridesmaid proposal boxes that carry each name, and keepsake creations for the couple. Every piece is assembled by hand, in the colours and style of your day.\n\nFresh or artificial flowers, whichever suits your event best: artificial ones last for years, fresh ones bring an unmatched vibrancy on the day itself. We advise you based on your date, your venue and how long your reception runs.\n\nWe serve Greater Montreal, Laval and the North Shore. Allow four weeks for large quantities; two to three weeks is usually enough for a single piece. Date coming up fast? Write to us anyway — we will tell you honestly whether it can be done.\n\nTell us about your day: we reply within 24 hours with a personalised proposal and a price, with no obligation.'
WHERE slug_fr = 'mariage';

-- Baby shower (/occasions/baby-shower)
UPDATE occasions SET
    icone                 = 'heroicon-o-sparkles',
    cta_fr                = 'Créer pour mon baby shower',
    cta_en                = 'Create for my baby shower',
    intro_fr              = 'Des créations délicates et personnalisées pour célébrer l’arrivée de bébé. Choisissez les couleurs, les fleurs et les petits détails qui rendront votre attention unique.',
    intro_en              = 'Delicate, personalised creations to celebrate a baby’s arrival. Choose the colours, the flowers and the small details that make your gift unique.',
    meta_title_fr         = 'Cadeaux de baby shower personnalisés à Laval',
    meta_title_en         = 'Personalised Baby Shower Gifts in Laval',
    meta_description_fr   = 'Créations florales et cadeaux personnalisés pour baby shower : prénom de bébé, couleurs douces, cadeaux pour les invitées. Laval, Montréal et Rive-Nord.',
    meta_description_en   = 'Floral creations and personalised baby shower gifts: baby’s name, soft colours, favours for guests. Delivery in Laval, Montreal and the North Shore.',
    contenu_seo_fr        = 'Un baby shower se prépare dans la douceur, et les créations qui l’accompagnent devraient lui ressembler.\n\nNous composons des créations florales et des cadeaux personnalisés portant le prénom de bébé, dans les teintes choisies par la famille : blush et ivoire, bleu poudré, sauge, ou un mélange qui vous ressemble. Pour la future maman comme pour les invitées, chaque pièce est assemblée à la main.\n\nLe prénom n’est pas encore décidé ? Beaucoup de familles choisissent alors un message — « Bienvenue », « Notre petite merveille » — ou une date. Nous vous proposons les options selon ce que vous préférez révéler.\n\nPour les lots d’invitées, la commande démarre à dix unités et demande environ trois semaines. Une pièce unique pour la future maman se prépare plus vite. Nous desservons Laval, le Grand Montréal et la Rive-Nord, avec cueillette gratuite sur rendez-vous.\n\nParlez-nous de votre fête : nous vous répondons sous 24 heures avec une proposition et un prix.',
    contenu_seo_en        = 'A baby shower is planned in gentleness, and the creations that go with it should feel the same way.\n\nWe compose floral creations and personalised gifts carrying the baby’s name, in the shades the family chooses: blush and ivory, powder blue, sage, or a blend of your own. For the mother-to-be and for the guests alike, every piece is assembled by hand.\n\nName not decided yet? Many families choose a message instead — “Welcome”, “Our little wonder” — or a date. We suggest options based on what you would rather reveal.\n\nGuest favours start at ten units and take around three weeks. A single piece for the mother-to-be comes together faster. We serve Laval, Greater Montreal and the North Shore, with free pickup by appointment.\n\nTell us about your celebration: we reply within 24 hours with a proposal and a price.'
WHERE slug_fr = 'baby-shower';

-- Baptême (/occasions/bapteme)
UPDATE occasions SET
    icone                 = 'heroicon-o-gift',
    cta_fr                = 'Créer pour un baptême',
    cta_en                = 'Create for a baptism',
    intro_fr              = 'Des créations florales et cadeaux personnalisés pour célébrer ce moment précieux en famille. Chaque détail est choisi avec soin pour créer une attention douce, élégante et mémorable.',
    intro_en              = 'Floral creations and personalised gifts to celebrate this precious family moment. Every detail is chosen with care for a gentle, elegant and memorable gift.',
    meta_title_fr         = 'Cadeaux de baptême personnalisés à Montréal',
    meta_title_en         = 'Personalised Baptism Gifts in Montreal',
    meta_description_fr   = 'Créations florales et cadeaux personnalisés pour un baptême : prénom de l’enfant, teintes douces, souvenirs pour les invités. Grand Montréal.',
    meta_description_en   = 'Floral creations and personalised baptism gifts: the child’s name, soft tones, keepsakes for family and guests. Greater Montreal and the North Shore.',
    contenu_seo_fr        = 'Un baptême réunit la famille autour d’un moment simple et important. Les créations qui l’accompagnent gagnent à rester sobres.\n\nNous travaillons des teintes claires — ivoire, blanc, blush, or discret — et inscrivons le prénom de l’enfant, la date, ou les deux. Les compositions peuvent accompagner la table, être offertes aux parrain et marraine, ou remises aux invités en souvenir de la journée.\n\nPour les familles qui souhaitent conserver la création, les fleurs artificielles haut de gamme se gardent indéfiniment sans entretien. Pour une célébration où la fraîcheur compte davantage, les fleurs naturelles apportent un parfum que rien ne remplace.\n\nPrévoyez deux à trois semaines, davantage pour un lot d’invités. Nous desservons le Grand Montréal, Laval et la Rive-Nord.\n\nDécrivez-nous la célébration : nous vous répondons sous 24 heures avec une proposition personnalisée.',
    contenu_seo_en        = 'A baptism brings family together around a simple, important moment. The creations that accompany it are better kept understated.\n\nWe work in light tones — ivory, white, blush, a discreet touch of gold — and inscribe the child’s name, the date, or both. The pieces can dress the table, be offered to the godparents, or given to guests as a keepsake of the day.\n\nFor families who want to keep the creation, premium artificial flowers last indefinitely with no upkeep. For a celebration where freshness matters more, fresh flowers bring a scent nothing else replaces.\n\nAllow two to three weeks, more for guest favours. We serve Greater Montreal, Laval and the North Shore.\n\nTell us about the celebration: we reply within 24 hours with a personalised proposal.'
WHERE slug_fr = 'bapteme';

-- Anniversaire (/occasions/anniversaire)
UPDATE occasions SET
    icone                 = 'heroicon-o-cake',
    cta_fr                = 'Créer pour un anniversaire',
    cta_en                = 'Create for a birthday',
    intro_fr              = 'Une création pensée pour la personne qui compte, dans ses couleurs et avec son prénom. Un anniversaire mérite mieux qu’un cadeau choisi à la dernière minute.',
    intro_en              = 'A creation made for the person who matters, in their colours and with their name. A birthday deserves better than a last-minute gift.',
    meta_title_fr         = 'Cadeaux d’anniversaire personnalisés à Montréal',
    meta_title_en         = 'Personalised Birthday Gifts in Montreal',
    meta_description_fr   = 'Créations florales et cadeaux personnalisés pour un anniversaire : prénom, couleurs et message au choix. Grand Montréal, Laval et Rive-Nord.',
    meta_description_en   = 'Floral creations and personalised birthday gifts: name, colours and message of your choice. Greater Montreal, Laval and the North Shore.',
    contenu_seo_fr        = 'Ce qui distingue un cadeau d’anniversaire, ce n’est pas son prix : c’est la preuve qu’on a pensé à la personne.\n\nNous composons des créations florales et des cadeaux personnalisés autour de ce qu’elle aime — ses couleurs, son style, un message qui ne veut rien dire pour personne d’autre. Le prénom, une date, une phrase à elle : tout s’inscrit.\n\nPour un anniversaire marquant — vingt ans, cinquante ans, quatre-vingts ans — nous proposons des compositions plus généreuses, à poser sur une table de fête. Pour une attention plus discrète, une pièce unique suffit souvent à dire l’essentiel.\n\nComptez deux semaines pour être tranquille. Nous desservons le Grand Montréal, Laval et la Rive-Nord, avec cueillette gratuite sur rendez-vous.\n\nDites-nous pour qui c’est : nous vous répondons sous 24 heures avec une proposition.',
    contenu_seo_en        = 'What sets a birthday gift apart is not its price: it is the proof that someone thought about the person.\n\nWe compose floral creations and personalised gifts around what they love — their colours, their style, a message that means nothing to anyone else. A name, a date, a phrase of their own: it can all be inscribed.\n\nFor a milestone birthday — twenty, fifty, eighty — we suggest more generous compositions, made to sit on a celebration table. For something quieter, a single piece often says everything that needs saying.\n\nAllow two weeks to be comfortable. We serve Greater Montreal, Laval and the North Shore, with free pickup by appointment.\n\nTell us who it is for: we reply within 24 hours with a proposal.'
WHERE slug_fr = 'anniversaire';

-- Graduation (/occasions/graduation)
UPDATE occasions SET
    icone                 = 'heroicon-o-academic-cap',
    cta_fr                = 'Créer pour une graduation',
    cta_en                = 'Create for a graduation',
    intro_fr              = 'Célébrez une grande réussite avec une création pensée spécialement pour cette personne. Fleurs, couleurs et détails personnalisés : une façon élégante de dire « Je suis fier·ère de toi ».',
    intro_en              = 'Celebrate a great achievement with a creation made especially for them. Flowers, colours and personalised details: an elegant way to say “I’m proud of you”.',
    meta_title_fr         = 'Cadeaux de graduation personnalisés à Montréal',
    meta_title_en         = 'Personalised Graduation Gifts in Montreal',
    meta_description_fr   = 'Créations florales et cadeaux personnalisés pour une graduation : prénom du diplômé, couleurs de l’école, message de félicitations. Montréal et Laval.',
    meta_description_en   = 'Floral creations and personalised graduation gifts: the graduate’s name, school colours, a message of congratulations. Greater Montreal and Laval.',
    contenu_seo_fr        = 'Une graduation clôt des années de travail. Le cadeau qui la marque devrait tenir plus longtemps qu’une soirée.\n\nNous créons des compositions florales et des cadeaux personnalisés portant le prénom du diplômé, l’année, le nom de l’établissement ou un simple « Bravo ». Les couleurs de l’école se travaillent bien, et rappellent immédiatement ce qui vient d’être accompli.\n\nLes fleurs artificielles haut de gamme sont souvent choisies pour cette occasion : la création reste intacte sur une étagère pendant des années, là où un bouquet fane en une semaine. Nous vous conseillons selon l’usage prévu.\n\nLa saison des graduations est chargée en mai et juin — prévoyez trois semaines à cette période. Nous desservons le Grand Montréal, Laval et la Rive-Nord.\n\nParlez-nous du diplômé : nous vous répondons sous 24 heures avec une proposition personnalisée.',
    contenu_seo_en        = 'A graduation closes years of work. The gift that marks it should last longer than an evening.\n\nWe create floral compositions and personalised gifts carrying the graduate’s name, the year, the name of the school, or a simple “Well done”. School colours work well, and instantly recall what has just been achieved.\n\nPremium artificial flowers are often chosen for this occasion: the creation stays intact on a shelf for years, where a bouquet fades within a week. We advise you based on how it will be kept.\n\nGraduation season is busy in May and June — allow three weeks during that period. We serve Greater Montreal, Laval and the North Shore.\n\nTell us about the graduate: we reply within 24 hours with a personalised proposal.'
WHERE slug_fr = 'graduation';

-- Cadeau personnalisé (/occasions/cadeau-personnalise)
UPDATE occasions SET
    icone                 = 'heroicon-o-star',
    cta_fr                = 'Créer un cadeau',
    cta_en                = 'Create a gift',
    intro_fr              = 'Vous cherchez un cadeau qui ne ressemble à aucun autre ? Nous créons une composition florale et cadeau sur mesure, inspirée de la personne, de son style et du message que vous souhaitez lui transmettre.',
    intro_en              = 'Looking for a gift unlike any other? We create a bespoke floral and gift composition, inspired by the person, their style and the message you want to convey.',
    meta_title_fr         = 'Cadeaux personnalisés sur mesure au Québec',
    meta_title_en         = 'Bespoke Personalised Gifts in Quebec',
    meta_description_fr   = 'Créations florales et cadeaux personnalisés sur mesure, sans occasion particulière. Couleurs, fleurs et message au choix. Grand Montréal et Laval.',
    meta_description_en   = 'Bespoke floral creations and personalised gifts, for no particular occasion. Colours, flowers and message of your choice. Greater Montreal and Laval.',
    contenu_seo_fr        = 'Les plus beaux cadeaux n’attendent pas toujours une occasion. Un remerciement, une déclaration, un « je pense à toi » sans raison précise : ce sont souvent ceux dont on se souvient.\n\nNous créons des compositions florales et des cadeaux personnalisés à partir de ce que vous nous dites de la personne : ce qu’elle aime, son style, ce que vous voulez lui faire comprendre. Vous choisissez les couleurs, les fleurs et le message à inscrire — ou vous nous laissez proposer.\n\nAucun minimum de commande pour une pièce unique. Si vous hésitez sur le type de création, dites-le simplement : nous vous conseillons selon l’occasion, le budget et le style de la personne.\n\nComptez deux semaines, moins si la date presse — écrivez-nous, nous vous dirons franchement ce qui est réalisable. Nous desservons le Grand Montréal, Laval et la Rive-Nord.\n\nRacontez-nous votre idée : nous vous répondons sous 24 heures, sans engagement.',
    contenu_seo_en        = 'The best gifts do not always wait for an occasion. A thank you, a declaration, an “I’m thinking of you” for no particular reason: those are often the ones people remember.\n\nWe create floral compositions and personalised gifts from what you tell us about the person: what they love, their style, what you want them to understand. You choose the colours, the flowers and the message to inscribe — or you let us suggest.\n\nNo minimum order for a single piece. If you are unsure which type of creation suits, simply say so: we advise you based on the occasion, the budget and the person’s style.\n\nAllow two weeks, less if the date is close — write to us and we will tell you honestly what is feasible. We serve Greater Montreal, Laval and the North Shore.\n\nTell us your idea: we reply within 24 hours, with no obligation.'
WHERE slug_fr = 'cadeau-personnalise';

-- Vérification avant validation : doit renvoyer 6 lignes, toutes à 0 champ vide.
SELECT slug_fr,
       (icone IS NULL OR icone = "")
     + (cta_fr IS NULL OR cta_fr = "")
     + (intro_fr IS NULL OR intro_fr = "")
     + (meta_title_fr IS NULL OR meta_title_fr = "")
     + (meta_description_fr IS NULL OR meta_description_fr = "")
     + (contenu_seo_fr IS NULL OR contenu_seo_fr = "") AS champs_vides_fr
FROM occasions ORDER BY ordre;

COMMIT;
-- En cas de doute sur le résultat du SELECT : ROLLBACK; au lieu de COMMIT;
