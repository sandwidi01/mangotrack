# 🥭 MangoTrack — Frontend Angular

> Interface web simple et propre pour l'[API MangoTrack](../README.md) : un CRUD complet des **producteurs** et la gestion des **livraisons** d'une coopérative de mangues.

![Angular](https://img.shields.io/badge/Angular-22-DD0031?logo=angular&logoColor=white)
![TypeScript](https://img.shields.io/badge/TypeScript-6-3178C6?logo=typescript&logoColor=white)
![Statut](https://img.shields.io/badge/CRUD-conforme%20à%20l'API-brightgreen)

## Sommaire

1. [Ce que fait l'application](#1-ce-que-fait-lapplication)
2. [Démarrage rapide](#2-démarrage-rapide)
3. [Correspondance écrans ↔ API](#3-correspondance-écrans--api)
4. [Structure du projet](#4-structure-du-projet)
5. [Les concepts Angular utilisés](#5-les-concepts-angular-utilisés)
6. [Comment le frontend parle à l'API (proxy, CORS)](#6-comment-le-frontend-parle-à-lapi-proxy-cors)
7. [Gestion des erreurs 422 et 409](#7-gestion-des-erreurs-422-et-409)
8. [Ajouter une fonctionnalité : mode d'emploi](#8-ajouter-une-fonctionnalité--mode-demploi)
9. [Commandes utiles](#9-commandes-utiles)
10. [Dépannage](#10-dépannage)

---

## 1. Ce que fait l'application

| Page | URL | Fonctions |
|---|---|---|
| Liste des producteurs | `/producteurs` | Voir tous les producteurs **et leur stock (kg)**, supprimer (avec confirmation) |
| Création | `/producteurs/nouveau` | Ajouter un producteur |
| Modification | `/producteurs/:id/modifier` | Modifier le nom ou le village |
| Liste des livraisons | `/livraisons` | Historique, **filtre par producteur**, **pagination** |
| Nouvelle livraison | `/livraisons/nouvelle` | Enregistrer une entrée ou une sortie de stock |

Les trois règles métier de l'API sont visibles à l'écran :

- sortie supérieure au stock → message **409** « Stock insuffisant » affiché dans le formulaire ;
- suppression d'un producteur qui a des livraisons → notification **409** ;
- données invalides → message **422** affiché sous le champ concerné.

---

## 2. Démarrage rapide

**Prérequis :** Node.js ≥ 20, npm, et l'API Laravel qui fonctionne (voir le [README racine](../README.md)).

```bash
# Terminal 1 — l'API (depuis la racine du dépôt). MySQL doit tourner (XAMPP).
php artisan serve              # http://localhost:8000

# Terminal 2 — le frontend
cd frontend
npm install                    # une seule fois : télécharge les dépendances
npm start                      # http://localhost:4200
```

Ouvrez **http://localhost:4200**. Les deux serveurs doivent tourner **en même temps**.

---

## 3. Correspondance écrans ↔ API

Chaque action de l'interface correspond exactement à une route de l'API :

| Action à l'écran | Méthode du service | Requête HTTP | Réponses gérées |
|---|---|---|---|
| Lister les producteurs | `ProducteurService.list()` | `GET /api/producteurs` | 200 |
| Afficher le stock | `ProducteurService.stock(id)` | `GET /api/producteurs/{id}/stock` | 200 |
| Charger pour modifier | `ProducteurService.get(id)` | `GET /api/producteurs/{id}` | 200, 404 |
| Créer | `ProducteurService.create()` | `POST /api/producteurs` | 201, 422 |
| Modifier | `ProducteurService.update()` | `PUT /api/producteurs/{id}` | 200, 422 |
| Supprimer | `ProducteurService.delete()` | `DELETE /api/producteurs/{id}` | 204, 409 |
| Lister les livraisons | `LivraisonService.list()` | `GET /api/livraisons?page=&per_page=&producteur_id=` | 200 + `X-Total-Count` |
| Créer une livraison | `LivraisonService.create()` | `POST /api/livraisons` | 201, 422, 409 |

> L'API n'expose **ni** `PUT` **ni** `DELETE` sur les livraisons (un historique ne se réécrit pas). Le frontend ne les propose donc pas : un CRUD « conforme à l'API » ne montre que ce que l'API permet.

Détails du contrat à connaître :

- la réponse n'est **pas** enveloppée dans `{"data": ...}` (`JsonResource::withoutWrapping()` côté Laravel) ;
- à la création d'une livraison, le producteur s'envoie sous la clé **`producteur`** (pas `producteur_id`), alors que la lecture renvoie `producteur_id` ;
- le nombre total de livraisons est dans l'en-tête **`X-Total-Count`**, pas dans le corps.

---

## 4. Structure du projet

```text
frontend/
├── proxy.conf.json              # redirige /api vers Laravel (évite le CORS en développement)
├── angular.json                 # configuration du projet Angular
├── package.json                 # dépendances et scripts npm
└── src/
    ├── index.html               # l'unique page HTML, contient <app-root>
    ├── main.ts                  # point d'entrée : démarre l'application
    ├── styles.css               # styles globaux (variables de couleurs, boutons, tableaux…)
    └── app/
        ├── app.ts / app.html    # coque de l'application : barre de navigation + <router-outlet>
        ├── app.config.ts        # fournisseurs globaux (HttpClient, routeur)
        ├── app.routes.ts        # table URL → composant
        ├── core/                # tout ce qui n'est pas visuel
        │   ├── models.ts        # interfaces TypeScript = formes JSON de l'API
        │   ├── api-error.ts     # lit l'erreur {message, errors} de l'API
        │   ├── producteur.service.ts
        │   ├── livraison.service.ts
        │   └── toast.service.ts # notifications
        ├── producteurs/         # liste + formulaire (création ET modification)
        └── livraisons/          # liste (filtre, pagination) + formulaire
```

**Règle d'or de l'organisation :** les *composants* affichent et réagissent aux clics ; les *services* parlent à l'API ; les *modèles* décrivent les données. Un composant ne construit jamais une URL.

---

## 5. Les concepts Angular utilisés

| Concept | Où le voir | En une phrase |
|---|---|---|
| **Composant standalone** | `producteur-list.ts` | Une classe TypeScript + un gabarit HTML = un morceau d'écran |
| **Routeur** | `app.routes.ts` | Associe chaque URL à un composant |
| **Service injectable** | `core/*.service.ts` | Classe partagée, obtenue avec `inject(...)` |
| **HttpClient** | `producteur.service.ts` | Envoie les requêtes ; retourne des `Observable` |
| **Observable / `subscribe`** | partout | Une requête est « promise » : rien ne part avant le `subscribe()` |
| **Signaux** (`signal`, `computed`) | `livraison-list.ts` | Variables réactives : l'écran se met à jour quand elles changent |
| **Contrôle de flux** (`@if`, `@for`) | fichiers `.html` | Affichage conditionnel et boucles dans les gabarits |
| **Formulaires réactifs** | `*-form.ts` | Le formulaire est décrit en TypeScript, avec ses validateurs |
| **`forkJoin`** | `producteur-list.ts` | Lance plusieurs requêtes en parallèle et attend la fin de toutes |

---

## 6. Comment le frontend parle à l'API (proxy, CORS)

Le navigateur applique la règle de **même origine** : une page servie par `localhost:4200` n'a pas le droit de lire librement les réponses de `localhost:8000` (c'est le **CORS**).

Solution retenue en développement : le **proxy** d'Angular. Le code appelle `/api/producteurs` (URL relative) ; le serveur de développement transmet la requête à Laravel :

```json
{ "/api": { "target": "http://localhost:8000", "secure": false, "changeOrigin": true } }
```

Pour le navigateur, tout vient de `localhost:4200` : plus de problème de CORS. Bonus : l'en-tête `X-Total-Count` reste lisible.

> **En production**, le proxy n'existe plus (`ng build` produit des fichiers statiques). Deux options : servir le frontend et l'API sous le même domaine (reverse proxy Nginx/Apache), ou configurer le CORS dans Laravel (`config/cors.php`, avec `exposed_headers: ['X-Total-Count']`).

---

## 7. Gestion des erreurs 422 et 409

Toutes les erreurs de l'API ont la même forme : `{"message": "...", "errors": {"champ": ["..."]}}`. La classe `ApiError` la transforme en objet simple :

| Code | Sens | Réaction de l'interface |
|---|---|---|
| **0** | API injoignable | Message « Le serveur Laravel est-il démarré ? » |
| **422** | Validation échouée | Message sous chaque champ fautif (`fieldErrors`) |
| **409** | Règle métier violée | Bandeau rouge dans le formulaire, ou notification rouge |
| **404** | Ressource introuvable | Notification + retour à la liste |

Les formulaires valident **aussi** côté navigateur (champ requis, longueurs, poids > 0) pour un retour immédiat, mais l'API reste l'autorité : on affiche toujours ses erreurs.

---

## 8. Ajouter une fonctionnalité : mode d'emploi

Exemple : afficher un nouvel endpoint `GET /api/stats`.

1. **Modèle** : ajouter l'interface dans `core/models.ts`.
2. **Service** : ajouter la méthode dans un service (`this.http.get<Stats>('/api/stats')`).
3. **Composant** : `ng generate component stats` ; l'injecter, appeler le service, stocker le résultat dans un `signal`.
4. **Route** : l'ajouter dans `app.routes.ts`.
5. **Navigation** : ajouter le lien dans `app.html`.

---

## 9. Commandes utiles

| Commande | Effet |
|---|---|
| `npm install` | Installe les dépendances (à refaire si `package.json` change) |
| `npm start` | Serveur de développement avec rechargement automatique |
| `npm run build` | Build de production dans `dist/frontend/` |
| `npx ng generate component nom` | Génère un composant |

---

## 10. Dépannage

| Symptôme | Cause probable | Solution |
|---|---|---|
| « Impossible de joindre l'API » | Laravel ou MySQL arrêté | `php artisan serve` + démarrer MySQL dans XAMPP |
| `http proxy error ... ECONNREFUSED` dans le terminal | Idem | Idem |
| Page blanche, erreur dans la console (F12) | Erreur de compilation ou de code | Lire le terminal de `npm start` |
| Modifications de `proxy.conf.json` ignorées | Le proxy n'est lu qu'au démarrage | Arrêter (`Ctrl+C`) puis relancer `npm start` |
| `Port 4200 is already in use` | Un ancien serveur tourne encore | Le fermer, ou `npm start -- --port 4300` |
| Le total des livraisons vaut 0 en production | `X-Total-Count` non exposé en CORS | `exposed_headers` dans `config/cors.php` |
