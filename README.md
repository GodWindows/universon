# Universon

Une plateforme web pour créer et partager votre univers musical personnel.

**Déployé sur : [universon.fr](https://universon.fr)**

## Fonctionnalités

- **Authentification Google** : Connexion sécurisée via OAuth2
- **Gestion d'Albums par Catégories** : Organisez vos albums préférés dans des catégories personnalisables
- **Profils Publics/Privés** : Partagez votre collection musicale avec un pseudo unique (format `@username`)
- **Recherche d'Albums** : Recherchez et ajoutez des albums à votre collection
- **Partage de Profil** : Partagez facilement votre profil musical avec un lien direct
- **Notes et avis** : Une section « Avis » distincte des listes : notez n'importe quel album (0,5 à 5 étoiles) et écrivez un avis, même s'il n'est dans aucune liste

## État de la présentation

Le projet est actuellement **sans habillage visuel** : aucune couleur, police,
animation ni décoration n'est définie. Le HTML est structurel et sémantique, le
JavaScript ne fait que du comportement (appels API, formulaires, état du DOM), et
`css/base.css` ne contient que les quelques règles dont un comportement dépend
réellement :

- masquage initial des modales (`.modal`, `.add-album-modal`) que le JS affiche
- positionnement de la liste d'autocomplétion des albums, qui doit se superposer
  au contenu suivant plutôt que le décaler

Tout le reste de l'apparence est volontairement laissé indéfini, pour qu'une
direction visuelle puisse être appliquée par-dessus sans avoir à défaire quoi que
ce soit au préalable. Les noms de classes présents dans le HTML sont là comme
points d'accroche pour cet habillage à venir.

## Technologies Utilisées

- **Frontend** : React 19 + Vite, servi par nginx
- **Backend** : Node.js 24 + Express 5, mysql2
- **Base de Données** : MySQL 8.4, supervisée par phpMyAdmin
- **Authentification** : Google OAuth2
- **Déploiement** : Docker Compose, réseau partagé `universon-shared`

## Structure du Projet

```
universon/
├── backend/                 # API Node (Express)
│   └── src/
│       ├── server.js        # Routes /api/* et connexion Google
│       ├── queries.js       # Requêtes SQL
│       └── db.js            # Pool MySQL
├── frontend/                # Application React (Vite)
│   ├── public/              # css/, img/, manifest, robots, sitemap
│   ├── src/
│   │   ├── pages/           # Landing, Login, Dashboard, PublicProfile
│   │   ├── lang/            # fr.json, en.json, fon.json
│   │   └── ...
│   └── nginx.conf           # Sert React et relaie /api vers le backend
├── db/init/                 # Dump .sql importé au premier démarrage
├── docker-compose.yml
└── .env.example
```

| Conteneur | Image | Accès |
| --- | --- | --- |
| `frontend` | nginx + build React | http://localhost:8080 |
| `backend` | node:24-alpine | interne (port 3000), via `/api` |
| `db` | mysql:8.4 | interne uniquement |
| `phpmyadmin` | phpmyadmin:5.2 | http://127.0.0.1:8081 (machine hôte seulement) |

## Installation

1. `cp .env.example .env` et remplir les valeurs (Google, mots de passe de la base, `SITE_URL`).
2. Importer la base existante : déposer le dump dans `db/init/` (ex. `db/init/universon.sql`)
   **avant** le premier démarrage. Il n'est importé que si le volume de la base est vide
   (`docker compose down -v` pour repartir de zéro). Sinon, importer via phpMyAdmin.
3. `docker compose up -d --build`
4. Dans la console Google Cloud, ajouter l'URI de redirection `${SITE_URL}/api/auth/google/callback`.

Développement du frontend avec rechargement à chaud : la stack lancée, `cd frontend && npm install && npm run dev`
(Vite relaie `/api` vers http://localhost:8080).

## Accessibilité

- Navigation au clavier
- Textes alternatifs pour les images
- Régions `aria-live` pour les messages de statut

## Fonctionnalités Principales

### Gestion d'Albums
- Recherche d'albums via une API musicale
- Ajout d'albums à votre collection
- Organisation par catégories (Favoris, Écoute fréquente, etc.)
- Affichage des pochettes et informations

### Profil Utilisateur
- Choix d'un pseudo unique (@username)
- Profil public ou privé
- Bio personnalisable
- Partage de profil via URL (/@username)
- Statistiques de collection

### Catégories Dynamiques
- Gestion de catégories d'albums
- Ajout/suppression d'albums dans les catégories
- Vue organisée de votre collection

## Roadmap

- [ ] Intégration API Spotify/Apple Music
- [ ] Système de playlists personnalisées
- [ ] Statistiques avancées (artistes les plus écoutés, genres, etc.)
- [ ] Recommandations musicales basées sur les goûts
- [ ] Mode sombre/clair
- [ ] PWA (Progressive Web App)
- [ ] Système de followers/following
- [ ] Commentaires et likes sur les profils


## Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.

## Support

Pour toute question ou problème :
- Ouvrir une issue sur GitHub
- Consulter la documentation
- Contacter l'équipe de développement

---

**Universon** - Créez et partagez votre univers musical

Déployé sur **[universon.fr](https://universon.fr)**
