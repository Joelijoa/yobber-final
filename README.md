# JobPortal - Plateforme de Recrutement

Une plateforme moderne de recrutement développée en PHP avec MySQL, permettant aux candidats de trouver des offres d'emploi et aux recruteurs de publier leurs offres.

## Structure du Projet

```
jobportal/
├── admin/                      # Interface d'administration
│   ├── dashboard.php          # Tableau de bord admin
│   ├── users/                 # Gestion des utilisateurs
│   ├── jobs/                  # Gestion des offres
│   └── statistics/            # Statistiques du site
│
├── assets/                    # Ressources statiques
│   ├── css/                  # Styles CSS
│   ├── js/                   # Scripts JavaScript
│   ├── images/               # Images
│   └── uploads/              # Fichiers uploadés (CV, logos)
│
├── candidate/                 # Interface candidat
│   ├── dashboard.php         # Tableau de bord candidat
│   ├── profile/              # Gestion du profil
│   ├── applications/         # Gestion des candidatures
│   ├── favorites/            # Offres favorites
│   └── notifications/        # Notifications
│
├── recruiter/                 # Interface recruteur
│   ├── dashboard.php         # Tableau de bord recruteur
│   ├── profile/              # Profil entreprise
│   ├── jobs/                 # Gestion des offres
│   ├── applications/         # Gestion des candidatures
│   └── cv-review/            # Analyse des CV
│
├── includes/                  # Fichiers inclus
│   ├── config/               # Configuration
│   ├── functions/            # Fonctions utilitaires
│   ├── header.php            # En-tête commun
│   └── footer.php            # Pied de page commun
│
├── public/                    # Pages publiques
│   ├── index.php             # Page d'accueil
│   ├── about.php             # À propos
│   ├── jobs.php              # Liste des offres
│   └── contact.php           # Contact
│
├── auth/                      # Authentification
│   ├── login.php             # Connexion
│   ├── register.php          # Inscription
│   └── logout.php            # Déconnexion
│
└── database.sql              # Structure de la base de données
```

## Fonctionnalités

### Pages Publiques
- Page d'accueil avec recherche d'emploi
- Liste des offres d'emploi
- À propos
- Contact
- Inscription/Connexion

### Interface Candidat
- Tableau de bord personnalisé
- Profil complet (compétences, expériences, etc.)
- Gestion des candidatures
- Liste des offres favorites
- Système de notifications
- Suivi des candidatures

### Interface Recruteur
- Tableau de bord avec statistiques
- Profil entreprise
- Gestion des offres d'emploi
- Gestion des candidatures
- Analyse des CV
- Système de notation des candidats

### Administration
- Gestion des utilisateurs
- Gestion des offres
- Statistiques globales
- Modération du contenu

## Prérequis

- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Serveur web (Apache/Nginx)
- Composer (pour la gestion des dépendances)

## Installation

1. Clonez le dépôt :
```bash
git clone https://github.com/votre-username/jobportal.git
cd jobportal
```

2. Créez une base de données MySQL :
```sql
CREATE DATABASE jobportal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

3. Importez le schéma de la base de données :
```bash
mysql -u votre_utilisateur -p jobportal < database.sql
```

4. Configurez la connexion à la base de données :
   - Ouvrez le fichier `includes/config/database.php`
   - Modifiez les paramètres de connexion selon votre configuration

5. Configurez votre serveur web :
   - Pour Apache, assurez-vous que le module mod_rewrite est activé
   - Le document root doit pointer vers le dossier du projet

## Sécurité

- Les mots de passe sont hashés avec l'algorithme bcrypt
- Protection contre les injections SQL avec PDO
- Protection XSS avec htmlspecialchars
- Validation des entrées utilisateur
- Sessions sécurisées
- Protection CSRF
- Validation des fichiers uploadés

## Contribution

Les contributions sont les bienvenues ! N'hésitez pas à :
1. Fork le projet
2. Créer une branche pour votre fonctionnalité
3. Commiter vos changements
4. Pousser vers la branche
5. Ouvrir une Pull Request

## Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.
