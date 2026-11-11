# Guide Git & GitHub : comprendre, publier et fusionner la branche `frontend`

Ce guide explique **chaque action** faite pour publier le frontend, puis comment fusionner (« merger ») la branche sur GitHub, et ce qu'il faut savoir autour.

## Sommaire

1. [Vocabulaire](#1-vocabulaire)
2. [L'état du dépôt MangoTrack](#2-létat-du-dépôt-mangotrack)
3. [Ce qui a été fait, commande par commande](#3-ce-qui-a-été-fait-commande-par-commande)
4. [Fusionner sur GitHub avec une Pull Request](#4-fusionner-sur-github-avec-une-pull-request)
5. [Les trois façons de fusionner](#5-les-trois-façons-de-fusionner)
6. [Fusionner en ligne de commande (alternative)](#6-fusionner-en-ligne-de-commande-alternative)
7. [Après la fusion : nettoyer](#7-après-la-fusion--nettoyer)
8. [Résoudre un conflit](#8-résoudre-un-conflit)
9. [Pièges classiques](#9-pièges-classiques)
10. [Aide-mémoire](#10-aide-mémoire)

---

## 1. Vocabulaire

| Terme | Définition simple |
|---|---|
| **Dépôt (repository)** | Le dossier du projet + tout son historique |
| **Commit** | Une « photo » du projet à un instant, avec un message. Les commits forment l'historique |
| **Branche** | Une ligne de commits indépendante. Permet de travailler sans toucher à `main` |
| **`main`** | La branche principale : elle doit toujours fonctionner |
| **`origin`** | Le nom local du dépôt distant (GitHub) |
| **Push** | Envoyer ses commits locaux vers GitHub |
| **Pull** | Récupérer les commits de GitHub (fetch + merge) |
| **Pull Request (PR)** | Une demande, sur GitHub, de fusionner une branche dans une autre, avec revue possible |
| **Merge (fusion)** | Intégrer les commits d'une branche dans une autre |
| **Staging (index)** | Zone d'attente : `git add` y met les fichiers qui feront partie du prochain commit |

---

## 2. L'état du dépôt MangoTrack

```text
main ──●──●──●──●            (API complète : producteurs + livraisons)
                 \
test              ●          (+ tests unitaires et d'intégration)
                   \
frontend            ●        (+ dossier frontend/ Angular, README, ce guide)
```

- `main` : la version « de référence ».
- `test` : ajoute les tests de l'API.
- `frontend` : **créée à partir de `test`**, elle contient donc l'API, les tests **et** le frontend.

> ⚠️ Conséquence importante : fusionner `frontend` dans `main` apporte **aussi** les commits de `test` qui ne sont pas encore dans `main`. C'est normal et voulu (le frontend s'appuie sur la même API). Si vous préférez, fusionnez d'abord `test` dans `main`, puis `frontend` : le résultat final est identique.

---

## 3. Ce qui a été fait, commande par commande

### 3.1 Créer la branche

```bash
git switch -c frontend
```

- `git switch` change de branche ; `-c` la **crée** avant de s'y placer.
- Elle part de la branche courante (`test`). Tout nouveau commit ira sur `frontend`, `main` n'est pas touchée.
- Vérifier : `git branch` (l'étoile `*` marque la branche active).

### 3.2 Créer le projet Angular

```bash
npx @angular/cli new frontend --routing --style=css --ssr=false --skip-git --skip-tests --defaults
```

| Option | Rôle |
|---|---|
| `new frontend` | crée le dossier `frontend/` |
| `--routing` | prépare le routeur (navigation entre pages) |
| `--style=css` | CSS simple (pas de Sass) |
| `--ssr=false` | pas de rendu côté serveur (inutile ici) |
| `--skip-git` | **important** : sans ça, Angular ferait un `git init` dans `frontend/`, créant un dépôt dans le dépôt |
| `--skip-tests` | pas de fichiers de test générés |
| `--defaults` | accepte les réponses par défaut aux questions |

### 3.3 Vérifier avant de commiter

```bash
git status
```

Montre ce qui a changé. Y figurent aussi `.postman/`, `docs/` et `postman/` : ce sont des fichiers **déjà présents avant** et jamais versionnés. On ne les ajoute pas par accident : c'est pourquoi on évite `git add .` (« tout ajouter ») et on ajoute les chemins **explicitement** :

```bash
git add frontend docs/GUIDE_GIT_GITHUB.md README.md
```

> `frontend/node_modules` (des centaines de Mo) **n'est pas** ajouté : Angular a généré `frontend/.gitignore` qui l'exclut. Un fichier `.gitignore` liste ce que Git doit ignorer (dépendances, builds, secrets).

### 3.4 Commiter

```bash
git commit -m "Ajoute le frontend Angular (CRUD producteurs et livraisons) et sa documentation"
```

Un commit enregistre définitivement le contenu de l'index avec un message. Un bon message dit **ce que le commit apporte**.

### 3.5 Publier sur GitHub

```bash
git push -u origin frontend
```

- `push` envoie les commits ; `origin` = GitHub ; `frontend` = la branche.
- `-u` (`--set-upstream`) mémorise le lien branche locale ↔ branche distante : ensuite, un simple `git push` ou `git pull` suffit.
- GitHub crée la branche `frontend` distante. On la voit dans le menu déroulant des branches sur la page du dépôt.

---

## 4. Fusionner sur GitHub avec une Pull Request

C'est la méthode recommandée : on garde une trace, on relit, on peut discuter.

### Étape par étape

1. Ouvrir **https://github.com/sandwidi01/mangotrack**.
2. GitHub affiche souvent un bandeau jaune **« frontend had recent pushes — Compare & pull request »** : cliquer dessus. Sinon : onglet **Pull requests → New pull request**.
3. Choisir les branches :
   - **base : `main`** ← la branche qui *reçoit* ;
   - **compare : `frontend`** ← la branche qui *apporte*.
   (Les flèches se lisent « fusionner *compare* dans *base* ». Une inversion est l'erreur la plus fréquente.)
4. Lire l'onglet **Files changed** : c'est exactement ce qui entrera dans `main`.
5. Donner un titre clair, par exemple *« Ajoute le frontend Angular »*, et une description.
6. Cliquer **Create pull request**.
7. GitHub affiche l'un des deux messages :
   - ✅ **« This branch has no conflicts with the base branch »** → on peut fusionner ;
   - ❌ **« This branch has conflicts that must be resolved »** → voir [section 8](#8-résoudre-un-conflit).
8. Cliquer **Merge pull request**, puis **Confirm merge**.
9. Cliquer **Delete branch** (facultatif, voir [section 7](#7-après-la-fusion--nettoyer)).

### Mettre `main` à jour sur votre machine

Après la fusion, `main` a avancé **sur GitHub** mais pas encore chez vous :

```bash
git switch main
git pull
```

Le dossier `frontend/` apparaît alors sur `main`.

---

## 5. Les trois façons de fusionner

Le bouton vert de la PR propose trois modes (flèche à droite du bouton) :

| Mode | Ce qui arrive | À choisir quand |
|---|---|---|
| **Create a merge commit** | Tous les commits de la branche sont conservés + un commit de fusion | Historique complet voulu. **Bon choix par défaut ici** |
| **Squash and merge** | Tous les commits sont écrasés en **un seul** commit sur `main` | Branche avec beaucoup de petits commits « brouillon » |
| **Rebase and merge** | Les commits sont rejoués un par un au bout de `main`, sans commit de fusion | Historique parfaitement linéaire voulu |

Pour ce projet, les trois conviennent ; prenez **Create a merge commit** si vous hésitez.

---

## 6. Fusionner en ligne de commande (alternative)

Même résultat, sans passer par l'interface GitHub :

```bash
git switch main                 # 1. se placer sur la branche qui REÇOIT
git pull                        # 2. s'assurer qu'elle est à jour
git merge frontend              # 3. y intégrer frontend
git push                        # 4. publier le résultat sur GitHub
```

Pour **annuler** une fusion locale pas encore poussée : `git merge --abort` (si en cours de conflit) ou `git reset --hard ORIG_HEAD` (juste après une fusion terminée).

> La PR reste préférable : revue, historique des discussions, et possibilité de protéger `main`.

---

## 7. Après la fusion : nettoyer

La branche `frontend` a rempli son rôle. La supprimer évite l'encombrement ; **les commits restent dans `main`**, rien n'est perdu.

```bash
git branch -d frontend              # supprime la branche LOCALE
git push origin --delete frontend   # supprime la branche DISTANTE (ou bouton "Delete branch")
git fetch --prune                   # retire de votre liste les branches distantes disparues
```

`-d` refuse de supprimer une branche non fusionnée (sécurité) ; `-D` force (à éviter sans certitude).

Si vous voulez **continuer** à travailler sur le frontend, ne la supprimez pas, ou recréez-en une depuis `main` à jour.

---

## 8. Résoudre un conflit

Un conflit survient quand **les deux branches ont modifié les mêmes lignes du même fichier**. Git ne devine pas, il demande.

Dans le fichier, Git écrit :

```text
<<<<<<< HEAD
version de la branche qui reçoit (main)
=======
version de la branche qui apporte (frontend)
>>>>>>> frontend
```

Résolution :

1. Ouvrir le fichier, **choisir** (ou combiner) le bon contenu, **supprimer** les trois lignes de marqueurs `<<<<<<<`, `=======`, `>>>>>>>`.
2. `git add fichier` puis `git commit` (en ligne de commande), ou bouton **Mark as resolved → Commit merge** dans l'éditeur web de GitHub.

Ici, les risques sont faibles : `frontend` ajoute surtout un **nouveau dossier**. Le seul fichier existant modifié est `README.md` (ajout d'une section).

---

## 9. Pièges classiques

| Piège | Explication / parade |
|---|---|
| Oublier `git add` | Un fichier non ajouté n'est pas dans le commit. Contrôlez avec `git status` |
| `git add .` | Ajoute **tout**, y compris des fichiers qu'on ne voulait pas. Préférer des chemins explicites |
| Commiter `node_modules`, `.env` | Secrets ou dossiers énormes. Toujours dans `.gitignore`. Un secret poussé doit être considéré comme **compromis** même après suppression |
| Pousser sur la mauvaise branche | `git branch` avant de commiter |
| Inverser base et compare dans la PR | La base **reçoit**. Relire avant de créer |
| Fusionner sans relire | L'onglet *Files changed* existe pour ça |
| `git push --force` | Écrase l'historique distant. À ne **jamais** faire sur `main` |
| Oublier `git pull` après la fusion | Votre `main` local reste en retard |
| Un dépôt Git dans un dépôt Git | Cause : `ng new` sans `--skip-git`. Symptôme : `frontend` apparaît comme un « sous-module » vide |

---

## 10. Aide-mémoire

```bash
git status                    # où en suis-je ?
git branch                    # quelles branches, laquelle est active ?
git branch -a                 # idem, avec les branches distantes
git switch <branche>          # changer de branche
git switch -c <branche>       # créer puis changer
git add <chemin>              # préparer des fichiers
git commit -m "message"       # enregistrer
git push                      # envoyer
git pull                      # récupérer
git log --oneline --graph --all   # voir l'historique en arbre
git diff                      # voir les modifications non commitées
git diff main..frontend --stat    # ce que frontend apporte par rapport à main
```
