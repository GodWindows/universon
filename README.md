# Universon

Une plateforme web pour créer et partager votre univers musical personnel.

**Déployé sur : [universon.fr](https://universon.fr)**

## Fonctionnalités

- **Authentification Google** : Connexion sécurisée via OAuth2
- **Gestion d'Albums par Catégories** : Organisez vos albums préférés dans des catégories personnalisables
- **Profils Publics/Privés** : Partagez votre collection musicale avec un pseudo unique (format `@username`)
- **Recherche d'Albums** : Recherchez et ajoutez des albums à votre collection
- **Partage de Profil** : Partagez facilement votre profil musical avec un lien direct

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

- **Frontend** : HTML5, CSS3, JavaScript ES6+
- **Backend** : PHP 8+, PDO
- **Base de Données** : MySQL
- **Authentification** : Google OAuth2

## Structure du Projet

```
universon/
├── api/                              # API endpoints
│   ├── add_album_to_category.php     # Ajouter un album à une catégorie
│   ├── check_pseudo.php              # Vérifier la disponibilité d'un pseudo
│   ├── get_albums_by_category.php    # Récupérer les albums par catégorie
│   ├── get_categories.php            # Récupérer toutes les catégories
│   ├── logout.php                    # Déconnexion
│   ├── remove_album_from_category.php # Retirer un album d'une catégorie
│   ├── search_albums.php             # Rechercher des albums
│   ├── update_bio.php                # Mise à jour de la bio
│   ├── update_profile_visibility.php # Changer la visibilité du profil
│   └── update_pseudo.php             # Changer le pseudo
├── css/                              # Styles CSS
│   └── base.css                      # Règles fonctionnelles uniquement (pas d'habillage)
├── js/                               # JavaScript
│   └── app.js                        # Logique frontend
├── migrations/                       # Scripts de migration de base de données
├── pages/                            # Pages principales
│   ├── dashboard.php                 # Tableau de bord utilisateur
│   ├── landing.php                   # Page d'accueil
│   ├── login.php                     # Page de connexion
│   └── public_profile.php            # Profil public (/@username)
├── util/                             # Utilitaires
│   ├── functions.php                 # Fonctions PHP
│   └── redirect.php                  # Gestion OAuth callbacks
├── vendor/                           # Dépendances Composer
├── .htaccess                         # Configuration Apache
├── index.php                         # Point d'entrée principal
├── composer.json                     # Dépendances PHP
├── package.json                      # Dépendances Node.js
└── README.md                         # Documentation
```

## Installation

1. **Cloner le projet**
   ```bash
   git clone https://github.com/votre-username/universon.git
   cd universon
   ```

2. **Installer les dépendances**
   ```bash
   composer install
   npm install  # Optionnel, pour JSLint
   ```

3. **Configurer la base de données**
   - Créer une base de données MySQL/MariaDB
   - Exécuter les scripts de migration dans le dossier `migrations/`
   - Créer un fichier `env_data.php` à la racine avec vos informations de BDD :
   ```php
   <?php
   // Configuration de la base de données
   $db_host = 'localhost';
   $db_name = 'votre_db';
   $db_user = 'votre_user';
   $db_pass = 'votre_password';
   
   // Configuration Google OAuth2
   $google_client_id = 'votre_client_id';
   $google_client_secret = 'votre_client_secret';
   $google_redirect_uri = 'https://universon.fr/util/redirect.php';
   
   // Configuration du site
   $site_title = 'Universon';
   $site_url = 'https://universon.fr';
   ```

4. **Configurer Google OAuth**
   - Créer un projet sur [Google Cloud Console](https://console.cloud.google.com/)
   - Activer l'API Google+ ou People API
   - Créer des identifiants OAuth 2.0
   - Ajouter les URI de redirection autorisées
   - Copier le Client ID et Client Secret dans `env_data.php`

5. **Configurer le serveur web**
   - Pour Apache : Le fichier `.htaccess` est déjà configuré
   - Pour Nginx : Configurer les redirections pour les profils publics `/@username`
   - S'assurer que `mod_rewrite` est activé (Apache)

6. **Lancer le serveur en local (développement)**
   ```bash
   php -S localhost:8000
   ```
   Puis accéder à `http://localhost:8000`

## Appliquer un habillage visuel

Aucune direction visuelle n'est choisie à ce stade. Pour en appliquer une, ajoutez
une feuille de style à côté de `css/base.css` et référencez-la dans les pages —
sans modifier `base.css`, qui doit rester limité aux règles fonctionnelles.

Les principaux points d'accroche disponibles dans le HTML :

| Classe / id | Rôle |
| --- | --- |
| `.site-header`, `.site-logo`, `.site-footer` | En-tête et pied de page |
| `.profile`, `.profile-avatar`, `.pseudo-display`, `.bio` | Identité et bio |
| `.category`, `.album-list`, `.album`, `.album-title`, `.album-artist` | Collection par catégorie |
| `.modal`, `.add-album-modal`, `.album-suggestions` | Modales et autocomplétion |
| `.notification` (`-success` / `-error` / `-info`) | Messages créés par le JS |
| `.error`, `.no-albums`, `.feedback` | États vides, erreurs, retours de saisie |

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

## Contribution

1. Fork le projet
2. Créer une branche feature
3. Commiter les changements
4. Pousser vers la branche
5. Ouvrir une Pull Request

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
