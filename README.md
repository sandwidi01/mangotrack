# 🥭 MangoTrack API

> API REST de suivi des livraisons et du stock d'une coopérative de mangues, construite avec **Laravel** et **MySQL**.
> Ce dépôt est à la fois **un projet fonctionnel**, **une roadmap** et **un cours** sur la conception d'une API REST.

![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-MariaDB-4479A1?logo=mysql&logoColor=white)
![Statut](https://img.shields.io/badge/phases-0%20→%203%20terminées-brightgreen)

Projet réalisé dans le cadre de la **séance 2 « Travail de groupe »** du cours *Applications N-tiers & DevOps* (L3 MIAGE).

---

## Sommaire

1. [Le problème à résoudre](#1-le-problème-à-résoudre)
2. [Démarrage rapide](#2-démarrage-rapide)
3. [Le contrat de l'API](#3-le-contrat-de-lapi)
4. [Exemples d'appels](#4-exemples-dappels)
5. [🗺️ Roadmap](#5-️-roadmap)
6. [🎓 Formation : concevoir une API REST, pas à pas](#6--formation--concevoir-une-api-rest-pas-à-pas)
7. [Architecture du projet](#7-architecture-du-projet)
8. [Pièges classiques et comment les éviter](#8-pièges-classiques-et-comment-les-éviter)
9. [Exercices pour aller plus loin](#9-exercices-pour-aller-plus-loin)
10. [Glossaire](#10-glossaire)
11. [Tests automatisés](#11-tests-automatisés)
12. [🖥️ Frontend Angular](#12-️-frontend-angular)

---

## 1. Le problème à résoudre

Salimata dirige une coopérative de mangues. Les agents notent les livraisons sur des cahiers et elle recopie tout le soir. Elle a trois problèmes :

| Sa plainte | L'entité concernée | La règle à respecter | Code HTTP si violée |
|---|---|---|---|
| « Un agent sort plus que ce qu'il y a : on a déjà eu moins quarante kilos. » | `livraisons` | Le stock d'un producteur ne devient **jamais négatif** | **409** |
| « Les acheteurs demandent d'où vient leur lot. » | `producteurs` ↔ `livraisons` | Toute livraison est **reliée à un producteur** existant | **422** |
| « Un agent a effacé Awa : tout son historique est parti. » | `producteurs` | On ne **supprime pas** un producteur qui a des livraisons | **409** |

> 💡 **Leçon n°1 :** un client décrit des *douleurs*, jamais des solutions. Le travail du concepteur est de les traduire en **entités**, **règles** et **codes de réponse** *avant* d'écrire la moindre ligne de code. Le tableau ci-dessus est le vrai point de départ du projet.

---

## 2. Démarrage rapide

**Prérequis :** PHP ≥ 8.2, Composer, MySQL/MariaDB (par exemple via XAMPP), Git.

```bash
# 1. Cloner et installer
git clone <url-du-depot> mangotrack
cd mangotrack
composer install

# 2. Configurer l'environnement
cp .env.example .env
php artisan key:generate

# 3. Créer la base (MySQL doit tourner)
mysql -u root -e "CREATE DATABASE mangotrack CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
#    Adaptez DB_USERNAME / DB_PASSWORD dans .env si besoin

# 4. Créer les tables
php artisan migrate

# 5. Lancer le serveur
php artisan serve        # → http://127.0.0.1:8000/api/producteurs
```

Vérification : `curl http://127.0.0.1:8000/api/producteurs` doit répondre `[]` avec le code `200`.

> Port déjà utilisé ? `php artisan serve --port=8001`.

---

## 3. Le contrat de l'API

Un **contrat** est la liste exacte de ce que l'API promet : adresses, verbes, corps, codes. Le client et le serveur se mettent d'accord dessus *avant* de coder.

| Adresse | Verbe | Succès | Échecs |
|---|---|---|---|
| `/api/producteurs` | GET | **200** : la liste | |
| `/api/producteurs` | POST | **201** : le producteur créé | **422** : nom absent, vide, trop court ou déjà pris |
| `/api/producteurs/{id}` | GET | **200** : le producteur | **404** : id inconnu |
| `/api/producteurs/{id}` | PUT | **200** : le producteur modifié | **404**, **422** |
| `/api/producteurs/{id}` | DELETE | **204** : aucun contenu | **404** ; **409** : il a des livraisons |
| `/api/producteurs/{id}/stock` | GET | **200** : `{"producteur_id": 3, "stock_kg": 40}` | **404** |
| `/api/livraisons` | GET | **200** : la liste (paginée, filtrable) + en-tête `X-Total-Count` | **422** : `page`, `per_page` ou `producteur_id` invalide |
| `/api/livraisons` | POST | **201** : la livraison créée | **422** : poids ≤ 0, sens inconnu, producteur inconnu ; **409** : sortie supérieure au stock |
| `/api/livraisons/{id}` | GET | **200** : la livraison | **404** |

**Corps d'un producteur**

```json
{ "nom": "Awa", "village": "Orodara" }
```

**Corps d'une livraison** (`sens` vaut `entree` ou `sortie`)

```json
{ "producteur": 3, "variete": "Kent", "poids_kg": 40, "sens": "entree" }
```

**Forme de TOUTES les erreurs (404, 405, 409, 422)** : une seule forme, donc un seul code à écrire côté client.

```json
{ "message": "Donnees invalides", "errors": { "nom": ["The nom field is required."] } }
{ "message": "Ressource introuvable", "errors": {} }
{ "message": "Stock insuffisant : sortie de 50 kg refusee", "errors": {} }
```

**Pagination et filtre sur `/api/livraisons`**

| Paramètre | Rôle | Défaut | Règle |
|---|---|---|---|
| `page` | Numéro de la page | `1` | entier ≥ 1, sinon **422** |
| `per_page` | Taille de la page | `50` | entier ≥ 1, **plafonné à 100** |
| `producteur_id` | Ne garder que les livraisons de ce producteur | aucun | entier ≥ 1, sinon **422** |

La réponse reste un tableau JSON ; le nombre total d'éléments (après filtre) est dans l'en-tête `X-Total-Count`.

```bash
curl -i "http://127.0.0.1:8000/api/livraisons?producteur_id=3&page=2&per_page=10"
```

**Règle du stock :** `stock = somme(entrées) − somme(sorties)`. Il ne devient jamais négatif. Une sortie *égale* au stock est permise (le stock tombe à 0).

### Modèle de données

```
┌──────────────────────┐          ┌──────────────────────────────┐
│     producteurs      │          │          livraisons          │
├──────────────────────┤   1    N ├──────────────────────────────┤
│ id          (PK)     │◄─────────│ id             (PK)          │
│ nom         (UNIQUE) │          │ producteur_id  (FK, RESTRICT)│
│ village     (null)   │          │ variete                      │
└──────────────────────┘          │ poids_kg       DECIMAL(10,2) │
                                  │ sens           ENUM          │
                                  └──────────────────────────────┘
```

Supprimer un producteur qui a des livraisons est **refusé par la base** (`RESTRICT`) *et* par l'API (409 lisible). Deux protections valent mieux qu'une.

---

## 4. Exemples d'appels

```bash
B=http://127.0.0.1:8000/api

# Créer un producteur → 201
curl -X POST $B/producteurs -H "Content-Type: application/json" \
     -d '{"nom":"Awa","village":"Orodara"}'

# Le même nom une deuxième fois → 422 (jamais 500)
curl -X POST $B/producteurs -H "Content-Type: application/json" -d '{"nom":"Awa"}'

# Entrée de 40 kg → 201
curl -X POST $B/livraisons -H "Content-Type: application/json" \
     -d '{"producteur":1,"variete":"Kent","poids_kg":40,"sens":"entree"}'

# Sortie de 50 kg avec 40 kg en stock → 409 (requête valide, état interdit)
curl -X POST $B/livraisons -H "Content-Type: application/json" \
     -d '{"producteur":1,"variete":"Kent","poids_kg":50,"sens":"sortie"}'

# Consulter le stock → {"producteur_id":1,"stock_kg":40}
curl $B/producteurs/1/stock

# Supprimer un producteur qui a des livraisons → 409
curl -i -X DELETE $B/producteurs/1
```

Scénario vérifié de bout en bout : entrée 40 kg → sortie 50 kg (409, stock reste 40) → sortie 15 kg (201, stock 25) → sortie 25 kg (201, stock 0) → suppression du producteur (409).

---

## 5. 🗺️ Roadmap

La roadmap suit les phases du TP. Chaque phase a un **point de passage** vérifiable : on n'avance pas tant qu'il n'est pas atteint.

### Phase 0 · Préparer ✅
- [x] Vérifier PHP ≥ 8.2, Composer, Git
- [x] Créer le projet Laravel et installer l'API (`php artisan install:api`)
- [x] Configurer MySQL (base `mangotrack`)

### Phase 1 · API Producteur ✅
- [x] Migration `producteurs` (nom unique, village)
- [x] Validation (`ProducteurRequest`) : 422 avec le format d'erreur du contrat
- [x] Créer (201), lire (200/404), modifier (200/422), supprimer (204)
- [x] Doublon de nom → 422 et non 500

### Phase 2 · Livraison et stock ✅
- [x] Migration `livraisons` avec clé étrangère `RESTRICT`
- [x] Validation : poids > 0, sens ∈ {entree, sortie}, producteur existant (422)
- [x] Créer et lister les livraisons (201 / 200)
- [x] Calcul du stock (`GET /api/producteurs/{id}/stock`)
- [x] Règle du stock : sortie > stock → **409**, aucun écrit en base
- [x] Protéger la suppression d'un producteur avec livraisons → **409**
- [x] Transaction + verrou pour les sorties simultanées

### Phase 3 · Défis ✅
- [x] **Pagination** : `?page=2&per_page=10`, maximum 100, total dans l'en-tête `X-Total-Count`
- [x] **Filtre** : `GET /api/livraisons?producteur_id=3` (appliqué *avant* la pagination)
- [x] **Erreurs structurées** : 404, 405, 409 et 422 avec la *même* forme `{"message", "errors"}` (`ErreurApi`)
- [x] **Index** `(producteur_id, sens)` sur `livraisons` et compromis lecture/écriture expliqué (étape 10)

### Au-delà du TP 🔭
- [x] Tests automatisés (PHPUnit) pour chaque ligne du contrat : voir [§11](#11-tests-automatisés)
- [ ] Authentification des agents (Laravel Sanctum, déjà installé)
- [ ] Table `acheteurs` et traçabilité d'un lot jusqu'au producteur
- [ ] Documentation OpenAPI / Swagger générée
- [ ] Intégration continue (GitHub Actions) : tests à chaque push
- [ ] Déploiement (Docker + serveur) et variables d'environnement de production

---

## 6. 🎓 Formation : concevoir une API REST, pas à pas

Cette partie explique **la démarche**, pas seulement le code. Chaque étape renvoie vers le fichier correspondant du projet.

### Étape 1 · Traduire les douleurs en règles
Remplissez, avant de coder, le tableau « problème → entité → règle → code HTTP » (voir [§1](#1-le-problème-à-résoudre)). Si vous ne savez pas remplir une case, vous n'avez pas encore compris le besoin.

### Étape 2 · Trouver les entités et dessiner le schéma
Cherchez les **noms** dans les plaintes : *producteur*, *livraison*. Ce sont vos tables. Puis posez :
- une **clé primaire** par table (`id`) ;
- les **clés étrangères** : une livraison appartient à *un* producteur, un producteur a *plusieurs* livraisons (relation **1 — N**, la clé est du côté N) ;
- les **contraintes** que la base fait respecter elle-même (`UNIQUE` sur le nom, `RESTRICT` sur la suppression).

📁 `database/migrations/*_create_producteurs_table.php`, `*_create_livraisons_table.php`

> **Pourquoi le stock n'est pas une colonne ?** Parce que c'est une donnée *dérivée* : on peut la calculer à partir des livraisons. Une colonne `stock` pourrait diverger de la réalité (deux écritures, un oubli). Une seule source de vérité : les livraisons.

### Étape 3 · Nommer les ressources et choisir les verbes
Une **ressource** est une chose que l'application gère. Son adresse est un **nom au pluriel**. L'**action** est portée par le **verbe HTTP**, jamais par l'adresse.

| ✅ Bon | ❌ Mauvais |
|---|---|
| `POST /api/producteurs` | `POST /api/ajouterProducteur` |
| `DELETE /api/producteurs/3` | `GET /api/supprimerProducteur?id=3` |
| `GET /api/livraisons?producteur_id=3` | `GET /api/livraisonsDuProducteur3` |

Verbes : **GET** lit · **POST** crée · **PUT** modifie · **DELETE** supprime. Un filtre est un *paramètre d'adresse*, pas une nouvelle route.

📁 `routes/api.php`

### Étape 4 · Choisir les bons codes de réponse
Le code dit au client **si ça a marché** et, sinon, **qui est en cause**.

| Code | Sens | Quand |
|---|---|---|
| 200 | OK | Lecture ou modification réussie |
| 201 | Créé | Un POST a créé quelque chose (jamais 200) |
| 204 | Pas de contenu | Suppression réussie, on ne renvoie rien |
| 404 | Introuvable | L'**adresse** ne désigne rien |
| 422 | Donnée invalide | La requête est invalide *en elle-même* |
| 409 | Conflit | La requête est valide mais l'**état** des données l'interdit |
| 500 | Erreur serveur | C'est *votre* faute. À ne jamais produire exprès |

#### Le test des deux questions : 422 ou 409 ?
1. **Peut-on décider en regardant seulement la requête ?** Oui → **422** (un poids négatif est invalide quel que soit l'état de la base).
2. **Faut-il regarder la base ?** Oui → **409** (une sortie de 50 kg est valide en soi, mais il n'y a que 40 kg en stock).

Cas piège : un producteur inconnu **dans le corps** d'une livraison est un **422** (donnée invalide) ; un producteur inconnu **dans l'adresse** (`/api/producteurs/999`) est un **404**.

### Étape 5 · Valider à l'entrée : le serveur ne fait jamais confiance au client
Toute donnée est vérifiée *avant* d'être écrite. Dans Laravel, on utilise une **FormRequest** : si une règle échoue, la requête s'arrête avant même d'atteindre le contrôleur.

```php
// app/Http/Requests/LivraisonRequest.php
return [
    'producteur' => ['required', 'integer', Rule::exists('producteurs', 'id')],
    'variete'    => ['required', 'string', 'max:100'],
    'poids_kg'   => ['required', 'numeric', 'gt:0'],
    'sens'       => ['required', Rule::in(['entree', 'sortie'])],
];
```

La validation ne connaît que la **requête**. Elle ne regarde pas le stock : c'est le rôle de l'étape suivante.

### Étape 6 · Placer chaque règle dans la bonne couche
Une API propre sépare les responsabilités. Chaque couche a **un seul travail** :

```
Requête HTTP
    │
    ▼
 routes/api.php     Aiguille : quelle adresse → quel contrôleur
 FormRequest        Valide la forme de la donnée                  → 422
 Controller         Traduit HTTP ⇄ métier, choisit le code (201, 409…)
 Service            Porte la règle métier (stock) → lève une erreur métier
 Model (Eloquent)   Parle à la base (ORM)
 Resource           Choisit les champs visibles par le client
```

**Où va la règle du stock ?** Ni dans la route, ni dans le client (le client peut mentir ou être contourné). Elle va dans une **couche logique réutilisable** : le `LivraisonService`. Un script qui importerait 500 livraisons devrait respecter la même règle.

```php
// app/Services/LivraisonService.php : le service ne connaît PAS HTTP
if ($sens === 'sortie' && $poids > $this->stock($producteur)) {
    throw new StockInsuffisant('Stock insuffisant : sortie de '.$poids.' kg refusee');
}
```

```php
// app/Http/Controllers/LivraisonController.php : le contrôleur traduit en code HTTP
try {
    $livraison = $this->livraisons->enregistrer(/* ... */);
} catch (StockInsuffisant $e) {
    return response()->json(['message' => $e->getMessage()], 409);
}
```

> 🔑 **Principe :** le service lève une **erreur métier**, le contrôleur la **traduit** en code HTTP. Demain, si vous ajoutez une commande console ou une file de messages, le service ne change pas.

### Étape 7 · Penser à la concurrence : « et si deux agents expédient en même temps ? »
Sans précaution, deux sorties de 30 kg avec 40 kg en stock peuvent passer *toutes les deux* (chacune voit 40 kg avant que l'autre n'écrive) → stock à −20 kg. La parade : une **transaction** et un **verrou** sur la ligne du producteur, pour que les vérifications se fassent l'une après l'autre.

```php
DB::transaction(function () use (...) {
    Producteur::whereKey($producteur->id)->lockForUpdate()->first(); // file d'attente par producteur
    // vérifier le stock puis écrire : plus personne ne passe entre les deux
});
```

### Étape 8 · Tester chaque ligne du contrat
Pour chaque ligne du tableau de la [§3](#3-le-contrat-de-lapi), faites **un appel qui réussit** et **un appel qui échoue**, et vérifiez le *code*, pas seulement le texte. Utilisez `curl`, Postman ou Insomnia (exemples en [§4](#4-exemples-dappels)). Ces appels sont automatisés dans la suite de tests : voir [§11](#11-tests-automatisés).

### Étape 9 · Versionner avec Git
Un commit = un changement + sa raison. Écrivez « ajoute la validation du poids de livraison », pas « modif ».

```bash
git add .
git commit -m "règle du stock : sortie trop grande répond 409"
git log --oneline
```

Ne commitez **jamais** `.env`, `vendor/` ni les fichiers de base locale (déjà dans `.gitignore`).

### Étape 10 · Paginer, filtrer, uniformiser, indexer
**Pagination.** Une liste ne se renvoie jamais en entier : sur une connexion lente, 50 000 lignes bloquent le client. On découpe avec `?page=2&per_page=10`, on **plafonne** `per_page` (sinon `per_page=10000000` contourne la protection) et on donne le total dans l'en-tête `X-Total-Count` pour que le client sache combien de pages afficher.

**Filtre.** `?producteur_id=3` est un *paramètre d'adresse*, pas une nouvelle route. Il s'applique **avant** la pagination : sinon le total et les pages seraient faux.

```php
$query = Livraison::query()->orderBy('id');
if ($request->has('producteur_id')) {
    $query->where('producteur_id', $request->integer('producteur_id'));
}
$total = $query->count();                                  // total APRÈS filtre
$items = $query->forPage($page, $request->perPage())->get();
```

**Erreurs structurées.** Qui lira vos erreurs ? Un programme. S'il doit gérer trois formes différentes (404, 409, 422), il aura trois fois plus de code et de bugs. Une seule fonction construit toutes les réponses d'erreur :

```php
ErreurApi::reponse('Stock insuffisant ...', 409);   // {"message": "...", "errors": {}}
```

Elle est appelée par les contrôleurs *et* par le gestionnaire d'exceptions (`bootstrap/app.php`) pour les 404 et 405 que Laravel génère lui-même.

**Index : un compromis, pas une baguette magique.** Un index accélère les lectures (le calcul du stock cherche par `producteur_id` et `sens`) mais ralentit un peu chaque écriture, car il faut le tenir à jour, et il occupe de la place. Deux enseignements tirés de ce projet :
1. **MySQL indexe déjà les clés étrangères.** Un index seul sur `producteur_id` aurait été un doublon. On a donc créé un index **composite** `(producteur_id, sens)` qui couvre exactement le filtre du calcul du stock (et rend l'index automatique de la clé étrangère redondant : MySQL le remplace).
2. **Mesurez avant de décider.** Avec 145 lignes, `EXPLAIN` montre que MySQL *propose* l'index mais préfère lire toute la table (plus rapide sur un si petit volume). L'intérêt n'apparaît qu'avec des milliers de lignes : voir l'exercice 5.

---

## 7. Architecture du projet

```
mangotrack/
├── app/
│   ├── Exceptions/StockInsuffisant.php        # Erreur métier (pas de HTTP ici)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── ProducteurController.php       # CRUD + stock + garde-fou de suppression
│   │   │   └── LivraisonController.php        # liste, création, lecture
│   │   ├── Middleware/ForceJson.php           # Force "Accept: application/json"
│   │   ├── Requests/                          # Validation (422)
│   │   │   ├── ApiFormRequest.php             # Base commune : erreur 422 au format du contrat
│   │   │   ├── ProducteurRequest.php
│   │   │   ├── LivraisonRequest.php
│   │   │   └── LivraisonIndexRequest.php      # page, per_page, producteur_id
│   │   ├── Responses/ErreurApi.php            # LA fonction qui construit toute erreur
│   │   └── Resources/                         # Champs exposés au client
│   │       ├── ProducteurResource.php
│   │       └── LivraisonResource.php
│   ├── Models/{Producteur,Livraison}.php      # Eloquent + relations
│   ├── Providers/AppServiceProvider.php       # Réponses sans enveloppe "data"
│   └── Services/LivraisonService.php          # Règle du stock + transaction
├── tests/                                     # Unit/ (règles isolées) + Feature/ (HTTP → base)
├── bootstrap/app.php                          # 404/405 JSON au même format
├── database/factories/                        # Producteur / Livraison de test
├── database/migrations/                       # Schéma des deux tables + index composite
├── routes/api.php                             # Le contrat, côté code
└── .env.example                               # Configuration MySQL d'exemple
```

---

## 8. Pièges classiques et comment les éviter

| Symptôme | Cause probable | Remède |
|---|---|---|
| Une **page HTML** ou une redirection au lieu d'un 422 | L'en-tête `Accept: application/json` manque | Le middleware `ForceJson` l'ajoute ; gardez les routes dans son groupe |
| **500** sur un doublon | La contrainte unique n'est pas validée en amont | Règle `unique` dans la FormRequest → 422 |
| 404 sur toutes les adresses `/api` | `routes/api.php` non branché | `php artisan install:api`, vérifier avec `php artisan route:list` |
| « Base table not found » | Migrations non lancées | `php artisan migrate` |
| « Class … not found » | Autoload obsolète | `composer dump-autoload` |
| « Connection refused » | MySQL est arrêté | Démarrer MySQL (XAMPP) |
| Port déjà utilisé | Un autre serveur tourne | `php artisan serve --port=8001` |
| **200** à la création | Oubli du code | `->response()->setStatusCode(201)` |
| Stock négatif en cas d'accès simultané | Pas de transaction ni de verrou | Voir étape 7 |
| Total de pages faux avec un filtre | Pagination appliquée avant le filtre | Filtrer d'abord, compter, puis paginer (étape 10) |
| `EXPLAIN` n'utilise pas mon index | Table trop petite, ou colonnes dans le mauvais ordre | Mesurer sur un gros volume ; mettre en premier la colonne filtrée par égalité |

---

## 9. Exercices pour aller plus loin

1. **Prédire avant de tester.** Pour chaque requête de la §4, écrivez le code attendu *avant* de l'envoyer.
2. **Casser volontairement.** Retirez le verrou `lockForUpdate()`, lancez deux sorties simultanées et observez le stock.
3. **Transférer la méthode.** Ajoutez une ressource `acheteurs` (id, nom, ville) en refaisant les étapes 2 à 8 sans copier-coller.
4. **Ajouter un tri.** Implémentez `?tri=poids_kg` et `?tri=-poids_kg` (le `-` inverse l'ordre) avec une liste blanche de colonnes autorisées. Que répondre pour `?tri=mot_de_passe` ?
5. **Mesurer un index.** Ajoutez 100 000 livraisons, comparez `EXPLAIN` avec et sans l'index `(producteur_id, sens)`. Sur quelques centaines de lignes, MySQL l'ignore : pourquoi ?
6. **Expliquer à voix haute.** Choisissez un appel qui échoue (422 ou 409) et expliquez en une minute *pourquoi ce code et pas un autre*.

---

## 10. Glossaire

| Mot | Définition | Exemple MangoTrack |
|---|---|---|
| **API REST** | Programme qui répond à des demandes réseau et renvoie des données, pas des pages | Le client demande les producteurs, l'API répond en JSON |
| **Ressource** | Une chose gérée par l'application, avec une adresse au pluriel | `/api/producteurs/3` |
| **Verbe HTTP** | L'action demandée : GET, POST, PUT, DELETE | `POST /api/producteurs` |
| **JSON** | Texte de paires nom/valeur entre accolades | `{"id": 3, "nom": "Awa"}` |
| **Code de réponse** | Nombre qui dit si ça a marché et qui est en cause | 201 créé, 404 introuvable |
| **Clé primaire** | Colonne qui identifie une ligne de façon unique | `id` dans `producteurs` |
| **Clé étrangère** | Colonne qui pointe vers la clé primaire d'une autre table | `producteur_id` dans `livraisons` |
| **Contrainte** | Règle que la base fait respecter elle-même | nom unique, suppression restreinte |
| **ORM** | Outil qui traduit une table en classe et une ligne en objet | Eloquent (Laravel) |
| **Validation** | Contrôle de la donnée à l'entrée, avant écriture | Refuser un poids négatif |
| **Service** | Classe qui porte la règle métier, indépendante de HTTP | `LivraisonService` |
| **Pagination** | Découper une longue liste en pages | `?page=2&per_page=10` + `X-Total-Count` |
| **Index** | Structure qui accélère la recherche au prix d'écritures plus lentes | `(producteur_id, sens)` sur `livraisons` |
| **Transaction** | Groupe d'opérations qui réussissent ou échouent ensemble | Vérifier le stock puis écrire |

---

## 11. Tests automatisés

La suite vérifie **chaque ligne du contrat** de la [§3](#3-le-contrat-de-lapi) : un cas qui réussit, au moins un cas qui échoue, et on contrôle le *code* HTTP, la *forme* du JSON et l'*état de la base*.

### Lancer les tests

```bash
php artisan test                       # toute la suite
php artisan test --testsuite=Unit      # uniquement les tests unitaires
php artisan test --testsuite=Feature   # uniquement les tests d'intégration
php artisan test --filter=test_sortie_superieure_au_stock_409   # un seul test
```

Aucune configuration : `phpunit.xml` utilise **SQLite en mémoire**, donc ni MySQL ni le fichier `.env` ne sont touchés, et chaque test repart d'une base vide (`RefreshDatabase`). Il faut l'extension PHP `pdo_sqlite` (activée par défaut avec XAMPP).

### Ce qui est testé

| Fichier | Type | Ce qu'il vérifie |
|---|---|---|
| `tests/Unit/LivraisonServiceTest.php` | Unitaire | Calcul du stock (entrées − sorties, par producteur) ; sortie égale au stock permise ; sortie supérieure refusée (`StockInsuffisant`) **sans rien écrire** |
| `tests/Unit/LivraisonIndexRequestTest.php` | Unitaire | `per_page` par défaut (50) et plafonné à 100 ; règles de `page`, `per_page`, `producteur_id` |
| `tests/Unit/ErreurApiTest.php` | Unitaire | `errors` est sérialisé en `{}` (et non `[]`) quand il est vide |
| `tests/Feature/ProducteurApiTest.php` | Intégration | CRUD complet : 200 / 201 / 204 ; 422 (nom absent, trop court, trop long, doublon) ; 404 ; 409 (suppression avec livraisons) ; `/stock` ; 405 ; réponses JSON même sans `Accept` |
| `tests/Feature/LivraisonApiTest.php` | Intégration | Création 201 ; 409 (sortie > stock, sans stock, stock d'un autre producteur) ; 10 corps invalides → 422 ; lecture 200/404 ; PUT/DELETE → 405 ; pagination, plafond, filtre, en-tête `X-Total-Count` ; 5 paramètres de liste invalides → 422 |

### Unitaire ou intégration ?

- **Unitaire** : on teste une règle isolée, sans passer par HTTP (`LivraisonService`, la validation, `ErreurApi`). Rapide, et l'échec pointe directement la règle fautive.
- **Intégration (Feature)** : on envoie une vraie requête (`$this->postJson(...)`) et on vérifie toute la chaîne route → validation → contrôleur → service → base → JSON.

### Écrire un nouveau test

Les *factories* fabriquent des données de test valides ; on ne précise que ce qui compte pour le scénario :

```php
use RefreshDatabase;

public function test_sortie_superieure_au_stock_409(): void
{
    $p = Producteur::factory()->create();
    Livraison::factory()->for($p)->create(['poids_kg' => 40]);          // entrée de 40 kg

    $this->postJson('/api/livraisons', [
        'producteur' => $p->id, 'variete' => 'Kent', 'poids_kg' => 50, 'sens' => 'sortie',
    ])
        ->assertStatus(409)                                              // le CODE, pas seulement le texte
        ->assertJsonStructure(['message', 'errors']);                    // la FORME du contrat

    $this->assertDatabaseCount('livraisons', 1);                         // rien n'a été écrit
}
```

- `Livraison::factory()->sortie()` crée une sortie ; `->for($producteur)` rattache la livraison.
- Pour tester plusieurs entrées invalides d'un coup, utilisez un `#[DataProvider]` (voir `corpsInvalides` dans `LivraisonApiTest`).

### Limites connues

- Les tests tournent sur SQLite, pas sur MySQL : le **verrou** `lockForUpdate()` (sorties simultanées, étape 7) est ignoré par SQLite et n'est donc pas prouvé par la suite. Pour le valider, lancez les mêmes tests avec une base MySQL de test (`DB_CONNECTION=mysql` et une base dédiée, jamais `mangotrack`) et testez la concurrence avec deux requêtes parallèles.
- Pas encore d'authentification à tester (Sanctum est installé mais non branché).

---

## 12. 🖥️ Frontend Angular

Une interface web (Angular) consomme cette API : CRUD des producteurs, liste filtrée et paginée des livraisons, affichage du stock, gestion des erreurs **422** et **409**. Elle vit dans le dossier [`frontend/`](frontend/) (branche Git `frontend`).

```bash
php artisan serve                  # terminal 1 : l'API sur http://localhost:8000
cd frontend && npm install && npm start   # terminal 2 : l'interface sur http://localhost:4200
```

```text
Navigateur ──► localhost:4200 (Angular) ──proxy /api──► localhost:8000 (Laravel) ──► MySQL
```

- Documentation complète du frontend : [`frontend/README.md`](frontend/README.md)
- Publier et fusionner la branche sur GitHub : [`docs/GUIDE_GIT_GITHUB.md`](docs/GUIDE_GIT_GITHUB.md)

---

## Licence

Projet pédagogique, libre d'utilisation à des fins d'apprentissage.
