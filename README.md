# Serenity Stay — Système de Réservation d'Hôtel (PHP + Oracle)

Application web de gestion de réservations pour un hostel/hôtel : inscription et connexion des utilisateurs, réservation de chambres, suivi des paiements, et un tableau de bord administrateur complet (gestion des chambres, réservations, utilisateurs, rapports).

## Fonctionnalités

- Inscription / connexion des utilisateurs (mots de passe hashés avec bcrypt pour les nouveaux comptes)
- Réservation de chambres avec calcul automatique du prix selon la durée du séjour
- Suivi des paiements liés à chaque réservation
- Espace "Mes réservations" pour les utilisateurs
- Tableau de bord administrateur : gestion des chambres, des réservations, des utilisateurs, et rapports
- Connexion à une base de données **Oracle Database (XE)**

## Stack technique

- PHP (natif, sans framework)
- Oracle Database XE (via l'extension PHP `oci8`)
- HTML / CSS / JavaScript côté front-end

## Schéma de la base de données

4 tables principales (voir `database_oracle/TABLES_Creation.sql`) :
- **users** — comptes utilisateurs et administrateurs
- **rooms** — chambres disponibles (numéro, type, capacité, prix, statut)
- **bookings** — réservations (dates, statut, prix total)
- **payments** — paiements liés à une réservation

##  Prérequis (installation plus lourde qu'un projet MySQL classique)

Ce projet nécessite :
1. **Un serveur PHP + Apache** (ex: XAMPP ou WampServer)
2. **Oracle Database XE** installé et configuré (plus lourd qu'une base MySQL/SQLite classique — environ 2-3 Go)
3. **L'extension PHP `oci8`** activée dans votre `php.ini` (pas activée par défaut, nécessite le client Oracle Instant Client)

## Installation

1. Cloner le repo :
   ```bash
   git clone <URL_DE_TON_REPO>
   ```

2. Placer le dossier `Hostel/` dans le répertoire de votre serveur web (ex: `htdocs/` pour XAMPP).

3. Installer Oracle Database XE, puis exécuter les scripts SQL dans l'ordre :
   ```
   database_oracle/creating_the_user_in_oracle.sql   -- crée l'utilisateur Oracle
   database_oracle/TABLES_Creation.sql               -- crée les tables
   database_oracle/insert_Data.sql                   -- insère des données de démo
   ```

4. Vérifier/adapter les identifiants de connexion dans `includes/config.php` si besoin (par défaut configuré pour une instance Oracle XE locale).

5. Activer l'extension `oci8` dans PHP (voir les captures d'écran dans `database_oracle/test_connection/` pour un exemple de configuration réussie).

6. Tester la connexion à la base avec `database_oracle/test_connection/test_oracle.php`.

7. Accéder à l'application via `http://localhost/Hostel/`.

##  Notes importantes / limitations connues

- **Comptes de démonstration** : le compte admin créé par `insert_Data.sql` (`admin@hostel.com`) utilise un mot de passe stocké en clair (`hostel123`) à des fins de démo/test. **Les nouveaux comptes créés via le formulaire d'inscription, eux, sont correctement hashés avec bcrypt** (voir `includes/functions.php`).
- Les identifiants de connexion Oracle dans `includes/config.php` sont ceux d'une base de développement locale — à adapter selon votre propre installation.
- Ce projet est un exercice académique / portfolio, non déployé en production.

## Technologies

PHP, Oracle Database (oci8), HTML/CSS, JavaScript
