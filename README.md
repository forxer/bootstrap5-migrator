Bootstrap 5 Migrator pour Laravel
=================================

Un outil complet pour migrer automatiquement vos applications Laravel de Bootstrap 4.6 vers Bootstrap 5.x avec analyse approfondie, validation et rapports détaillés.

🚨 Changements majeurs Bootstrap 5
----------------------------------

Bootstrap 5 introduit des modifications importantes qui nécessitent une migration soigneuse :

- **jQuery supprimé** - Bootstrap 5 utilise du JavaScript vanilla
- **Popper.js** remplacé par **@popperjs/core**
- **Classes CSS renommées** (`ml-*` → `ms-*`, `form-group` supprimé, etc.)
- **Attributs data-*** préfixés par `data-bs-`
- **Composants supprimés** (Jumbotron, Media object)
- **Formulaires entièrement refactorisés**
- **Nouvelles variables SCSS** et options de configuration

📦 Installation
---------------

```bash
composer require forxer/bootstrap5-migrator --dev
```

### Publication de la configuration

```bash
php artisan vendor:publish --tag="bootstrap5-migrator-config"
```

### Publication des vues pour customisation

```bash
php artisan vendor:publish --tag=bootstrap5-migrator-views
```

Les vues seront copiées dans : `resources/views/vendor/bootstrap5-migrator/`


🚀 Utilisation rapide
---------------------

### Workflow de migration recommandé

```bash
# 1. Analyse complète de votre application
php artisan bootstrap:analyze --detailed --export=analysis.json

# 2. Migration avec sauvegarde automatique
php artisan bootstrap:migrate-to-5 --backup

# 3. Validation et correction automatique
php artisan bootstrap:validate --fix

# 4. Génération du rapport final
php artisan bootstrap:report --format=html
```

📋 Commandes disponibles
------------------------

### `bootstrap:analyze` - Analyse approfondie

Analyse votre application pour identifier tous les éléments à migrer.

```bash
# Analyse basique
php artisan bootstrap:analyze

# Analyse détaillée avec localisation
php artisan bootstrap:analyze --detailed

# Export des résultats
php artisan bootstrap:analyze --export=analysis.json --format=json
php artisan bootstrap:analyze --export=analysis.html --format=html
```

**Options :**
- `--format=table|json|html` : Format de sortie
- `--export=fichier` : Exporter vers un fichier
- `--detailed` : Analyse détaillée avec localisation des problèmes

### `bootstrap:migrate-to-5` - Migration principale

Migre automatiquement votre application vers Bootstrap 5.

```bash
# Migration complète avec sauvegarde
php artisan bootstrap:migrate-to-5 --backup

# Mode dry-run (aperçu sans modifications)
php artisan bootstrap:migrate-to-5 --dry-run

# Migration forcée sans confirmation
php artisan bootstrap:migrate-to-5 --force

# Conserver jQuery
php artisan bootstrap:migrate-to-5 --skip-jquery
```

**Options :**
- `--dry-run` : Affiche les changements sans les appliquer
- `--backup` : Crée une sauvegarde avant migration
- `--force` : Force la migration sans confirmation
- `--skip-jquery` : Ne supprime pas jQuery automatiquement

### `bootstrap:validate` - Validation post-migration

Valide votre migration et détecte les problèmes restants.

```bash
# Validation complète
php artisan bootstrap:validate

# Correction automatique des problèmes mineurs
php artisan bootstrap:validate --fix

# Mode strict pour CI/CD
php artisan bootstrap:validate --strict
```

**Options :**
- `--fix` : Corrige automatiquement les problèmes mineurs
- `--strict` : Échoue si des problèmes critiques sont détectés

### `bootstrap:report` - Génération de rapports

Génère des rapports détaillés de migration.

```bash
# Rapport HTML interactif
php artisan bootstrap:report --format=html

# Rapport PDF professionnel
php artisan bootstrap:report --format=pdf --output=migration-report.pdf

# Documentation Markdown
php artisan bootstrap:report --format=markdown --output=MIGRATION.md

# Avec captures d'écran (nécessite Puppeteer)
php artisan bootstrap:report --include-screenshots
```

**Options :**
- `--format=html|pdf|markdown` : Format du rapport
- `--output=fichier` : Chemin de sortie
- `--include-screenshots` : Inclut des captures d'écran

🔍 Ce que fait l'outil
----------------------

### 1. **Analyse automatique**
- ✅ Détecte la version actuelle de Bootstrap
- ✅ Identifie l'usage de jQuery et Popper.js
- ✅ Liste toutes les classes CSS obsolètes
- ✅ Trouve les composants JavaScript à migrer
- ✅ Analyse les liens CDN Bootstrap 4
- ✅ Détecte les cas spéciaux nécessitant attention manuelle

### 2. **Migration intelligente**

#### Dépendances NPM
- `package.json` : Bootstrap 4.6 → 5.3
- `popper.js` → `@popperjs/core`
- Maintien optionnel de jQuery

#### Fichiers de style (CSS/SCSS/Sass)
- Mise à jour des imports Bootstrap
- Remplacement des variables SCSS obsolètes
- Migration des classes dans les sélecteurs CSS

#### Fichiers JavaScript
- Mise à jour des imports Bootstrap
- Remplacement des attributs `data-*`
- Migration des événements Bootstrap

#### Templates (Blade/HTML/Twig/Vue)
- Remplacement automatique des classes CSS
- Migration des attributs `data-*` vers `data-bs-*`
- Restructuration des formulaires
- Simplification des input groups

### 3. **Compilation des assets**
- Exécute `npm install`
- Compile avec `npm run dev` ou `npm run build`

🔄 Mappings des classes principales
-----------------------------------

### Utilities de spacing (direction-aware)

| Bootstrap 4 | Bootstrap 5 | Description |
|-------------|-------------|-------------|
| `ml-*` | `ms-*` | Margin left → Margin start |
| `mr-*` | `me-*` | Margin right → Margin end |
| `pl-*` | `ps-*` | Padding left → Padding start |
| `pr-*` | `pe-*` | Padding right → Padding end |

### Alignement du texte

| Bootstrap 4 | Bootstrap 5 |
|-------------|-------------|
| `text-left` | `text-start` |
| `text-right` | `text-end` |

### Formulaires (changements majeurs)

| Bootstrap 4 | Bootstrap 5 | Notes |
|-------------|-------------|-------|
| `form-group` | `mb-3` | Espacement manuel |
| `form-row` | `row g-3` | Système de grille avec gutters |
| `custom-select` | `form-select` | Nouveau nom |
| `custom-file` | `form-control` | Simplifié |
| `custom-control` | `form-check` | Uniformisé |
| `custom-checkbox` | `form-check` | |
| `custom-radio` | `form-check` | |
| `custom-switch` | `form-check form-switch` | |

### Input Groups (simplifiés)

```html
<!-- Bootstrap 4 -->
<div class="input-group">
    <div class="input-group-prepend">
        <span class="input-group-text">@</span>
    </div>
    <input type="text" class="form-control">
    <div class="input-group-append">
        <span class="input-group-text">.com</span>
    </div>
</div>

<!-- Bootstrap 5 -->
<div class="input-group">
    <span class="input-group-text">@</span>
    <input type="text" class="form-control">
    <span class="input-group-text">.com</span>
</div>
```

### Badges (nouvelles classes background)

| Bootstrap 4 | Bootstrap 5 |
|-------------|-------------|
| `badge-primary` | `bg-primary` |
| `badge-secondary` | `bg-secondary` |
| `badge-success` | `bg-success` |
| `badge-danger` | `bg-danger` |
| `badge-warning` | `bg-warning text-dark` |
| `badge-info` | `bg-info text-dark` |
| `badge-light` | `bg-light text-dark` |
| `badge-dark` | `bg-dark` |

### Attributs data-* (préfixe data-bs-)

| Bootstrap 4 | Bootstrap 5 |
|-------------|-------------|
| `data-toggle` | `data-bs-toggle` |
| `data-target` | `data-bs-target` |
| `data-dismiss` | `data-bs-dismiss` |
| `data-slide` | `data-bs-slide` |
| `data-ride` | `data-bs-ride` |

🗑️ Composants supprimés et remplacements
-----------------------------------------

### Jumbotron
```html
<!-- Bootstrap 4 -->
<div class="jumbotron">
    <h1>Hero section</h1>
</div>

<!-- Bootstrap 5 -->
<div class="bg-light p-5 rounded-3">
    <h1>Hero section</h1>
</div>
```

### Media Object
```html
<!-- Bootstrap 4 -->
<div class="media">
    <img src="..." class="media-object">
    <div class="media-body">Content</div>
</div>

<!-- Bootstrap 5 -->
<div class="d-flex">
    <img src="..." class="flex-shrink-0">
    <div class="flex-grow-1 ms-3">Content</div>
</div>
```

### Card deck/columns
```html
<!-- Bootstrap 4 -->
<div class="card-deck">...</div>
<div class="card-columns">...</div>

<!-- Bootstrap 5 -->
<div class="row row-cols-1 row-cols-md-3 g-4">...</div>
<div class="row row-cols-1 row-cols-md-2 row-cols-xl-3">...</div>
```

📁 Support de fichiers étendus
------------------------------

L'outil supporte une large gamme de types de fichiers :

### Templates
- **Laravel** : `.blade.php`, `.php`
- **HTML** : `.html`, `.htm`
- **Twig** : `.twig`
- **Vue.js** : `.vue`
- **Autres** : `.ejs`, `.erb`, `.hbs`, `.jsp`, `.asp`, `.aspx`, `.cshtml`

### Styles
- **CSS** : `.css`
- **Sass** : `.scss`, `.sass`
- **Less** : `.less`

### Scripts
- **JavaScript** : `.js`, `.ts`
- **React** : `.jsx`, `.tsx`

⚡ Cas spéciaux détectés
-----------------------

L'outil identifie automatiquement les cas nécessitant une attention manuelle :

### 🔴 Priorité haute
- **Usage jQuery des plugins Bootstrap** - Migration vers JavaScript vanilla requise
- **Attributs data-* sans préfixe** - Ajout automatique de `data-bs-`
- **Composants supprimés** - Remplacement par les alternatives Bootstrap 5

### 🟡 Priorité moyenne
- **Classes à marges négatives** (`mt-n1`, `pb-n2`) - Non disponibles via CDN
- **Structures input-group obsolètes** - Simplification automatique
- **Contrôles de formulaire customisés** - Migration vers les nouvelles classes

### 🟢 Priorité faible
- **Styles d'impression** - Non inclus dans Bootstrap 5
- **Problèmes de contraste Windows** - Considérer Bootstrap Forced Colors CSS

📊 Scoring et validation
------------------------

L'outil génère un score de migration sur 100 basé sur :

- **Classes CSS obsolètes** (pénalité -15 par problème critique)
- **Attributs data-* obsolètes** (pénalité -10 par problème)
- **Liens CDN Bootstrap 4** (pénalité -5 par lien)
- **Cas spéciaux non résolus** (pénalité -2 à -15 selon criticité)
- **Dépendances NPM obsolètes** (pénalité -10)

### Interprétation du score
- **90-100** : 🎉 Migration excellente, prête pour production
- **70-89** : 👍 Bon progrès, quelques ajustements nécessaires
- **50-69** : ⚠️ Migration en cours, attention requise
- **<50** : 🚨 Nombreux problèmes à résoudre

🔧 Configuration
----------------

Le fichier `config/bootstrap5-migrator.php` permet de personnaliser :

### Répertoires analysés
```php
'scan_directories' => [
    'css' => [resource_path('css'), resource_path('scss')],
    'js' => [resource_path('js')],
    'views' => [resource_path('views')],
],
```

### Mappings de classes personnalisés
```php
'class_mappings' => [
    'ma-classe-custom' => 'nouvelle-classe-custom',
    // Ajoutez vos propres mappings
],
```

### Commandes de build
```php
'build_commands' => [
    'dev' => ['npm', 'run', 'dev'],
    'build' => ['npm', 'run', 'build'],
    'production' => ['npm', 'run', 'production'],
],
```

### Gestion de jQuery
```php
'jquery' => [
    'remove_automatically' => false,
    'show_warnings' => true,
],
```

🔄 Système de sauvegarde et rollback
------------------------------------

### Création automatique de sauvegardes
```php
// Crée automatiquement une sauvegarde horodatée
$migrator->createBackup();
```

### Points de rollback personnalisés
```php
// Via l'API programmatique
$reporter = app(\Bootstrap5Migrator\Reporters\MigrationReporter::class);
$reporter->createRollbackPoint('before-migration-' . date('Y-m-d-H-i'));

// Liste des points de rollback disponibles
$rollbackPoints = $reporter->listRollbackPoints();
```

🔍 Intégration CI/CD
--------------------

### Pipeline de migration automatisé

```yaml
# .github/workflows/bootstrap-migration.yml
name: Bootstrap 5 Migration Check

on: [push, pull_request]

jobs:
  migration-check:
    runs-on: ubuntu-latest
    steps:
    - uses: actions/checkout@v3

    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.2'

    - name: Install dependencies
      run: composer install

    - name: Bootstrap Migration Analysis
      run: php artisan bootstrap:analyze --export=ci-analysis.json

    - name: Bootstrap Migration Validation
      run: php artisan bootstrap:validate --strict

    - name: Upload Analysis Report
      uses: actions/upload-artifact@v3
      with:
        name: bootstrap-analysis
        path: ci-analysis.json
```

### Script de surveillance continue

L'outil génère automatiquement un script bash pour surveiller les régressions :

```bash
# Généré par bootstrap:report
php artisan bootstrap:report --monitoring-script

# Utilisation dans votre CI/CD
./monitor-bootstrap.sh && echo "✅ Bootstrap 5 migration OK"
```

📈 Rapports et monitoring
-------------------------

### Types de rapports disponibles

#### 1. Rapport HTML interactif
- Interface moderne et responsive
- Graphiques de progression
- Détails par catégorie de problème
- Score visuel de migration

#### 2. Rapport PDF professionnel
- Formatage pour impression
- Résumé exécutif
- Recommandations détaillées
- Annexes techniques

#### 3. Documentation Markdown
- Intégration dans votre wiki/documentation
- Format compatible GitHub/GitLab
- Checklist de migration
- Commandes de référence

#### 4. Export JSON pour API
- Intégration dans vos outils de monitoring
- Métriques programmables
- Historique de progression
- Alertes automatisées

### Métriques de performance

Les rapports incluent des métriques détaillées :

- **Temps d'exécution** de la migration
- **Nombre de fichiers traités**
- **Types de modifications** effectuées
- **Taux de réussite** par catégorie
- **Utilisation mémoire** pendant le processus

🧪 Tests et qualité
-------------------

### Tests automatisés
```bash
# Exécuter la suite de tests
composer test

# Tests avec coverage
composer test-coverage

# Tests spécifiques
./vendor/bin/phpunit tests/Unit/MigrationTest.php
```

### Analyse statique
```bash
# PHPStan
composer analyse

# PHP CS Fixer
composer format
```

🔧 Développement et contribution
--------------------------------

### Installation pour développement
```bash
git clone https://github.com/forxer/bootstrap5-migrator.git
cd bootstrap5-migrator
composer install
composer test
```

### Structure du projet
```
src/
├── Commands/           # Commandes Artisan
├── Analyzers/         # Analyseurs de code
├── Validators/        # Validateurs post-migration
├── Reporters/         # Générateurs de rapports
├── FileTypeSupport/   # Support multi-fichiers
├── Bootstrap5Migrator.php
└── ServiceProvider.php

tests/
├── Unit/              # Tests unitaires
├── Feature/           # Tests d'intégration
└── fixtures/          # Fichiers de test

config/
└── bootstrap5-migrator.php
```

### Standards de code
- **PSR-12** pour le style de code
- **PHPDoc** obligatoire pour les méthodes publiques
- **Tests unitaires** pour toute nouvelle fonctionnalité
- **Versioning sémantique** pour les releases

🛟 Dépannage
------------

### Problèmes courants

#### Erreur "Bootstrap version not detected"
```bash
# Vérifiez package.json
cat package.json | grep bootstrap

# Réinstallez les dépendances
npm install
```

#### Classes non migrées
```bash
# Analyse détaillée
php artisan bootstrap:analyze --detailed

# Vérifiez la configuration
php artisan config:show bootstrap5-migrator
```

#### Compilation échouée après migration
```bash
# Nettoyez le cache
npm run clean  # ou rm -rf node_modules package-lock.json
npm install
npm run dev
```

#### Score de migration faible
```bash
# Correction automatique
php artisan bootstrap:validate --fix

# Analyse des problèmes restants
php artisan bootstrap:analyze --detailed --export=debug.json
```

### Support et communauté

- 📖 **Documentation** : [GitHub Wiki](https://github.com/forxer/bootstrap5-migrator/wiki)
- 🐛 **Bug reports** : [GitHub Issues](https://github.com/forxer/bootstrap5-migrator/issues)
- 💬 **Discussions** : [GitHub Discussions](https://github.com/forxer/bootstrap5-migrator/discussions)
- 📧 **Contact** : [Email du mainteneur](mailto:forxer@gmail.com)

🔗 Ressources utiles
--------------------

### Documentation officielle
- [Guide de migration Bootstrap 5](https://getbootstrap.com/docs/5.3/migration/)
- [Documentation Bootstrap 5](https://getbootstrap.com/docs/5.3/)
- [Bootstrap Icons](https://icons.getbootstrap.com/)

### Outils complémentaires
- [Bootstrap Print CSS](https://github.com/coliff/bootstrap-print-css)
- [Bootstrap Forced Colors CSS](https://github.com/coliff/bootstrap-forced-colors-css)
- [Extension navigateur Classes obsolètes](https://github.com/coliff/bootstrap-deprecated-classes-extension)

### Migrations similaires
- [Outil de migration Node.js](https://github.com/coliff/bootstrap-5-migrate-tool)
- [Guide de migration CommCare](https://www.commcarehq.org/styleguide/b5/migration/)

📋 Checklist post-migration
---------------------------

Après avoir utilisé l'outil, vérifiez manuellement :

### ✅ Tests fonctionnels
- [ ] **Modals** s'ouvrent et se ferment correctement
- [ ] **Dropdowns** fonctionnent sur tous les appareils
- [ ] **Tooltips et popovers** s'affichent bien
- [ ] **Carrousels** défilent normalement
- [ ] **Accordéons/collapses** se plient/déplient
- [ ] **Navigation** responsive fonctionne
- [ ] **Formulaires** se valident correctement

### ✅ Tests visuels
- [ ] **Espacement** cohérent partout
- [ ] **Couleurs** des badges et alertes OK
- [ ] **Typographie** conforme aux attentes
- [ ] **Grille responsive** fonctionne sur mobile/tablette/desktop
- [ ] **Print styles** si utilisés (ajout manuel requis)

### ✅ Tests de performance
- [ ] **Bundle size** n'a pas trop augmenté
- [ ] **Temps de chargement** acceptable
- [ ] **JavaScript** sans erreurs console
- [ ] **CSS** compile sans warnings

### ✅ Tests de compatibilité
- [ ] **Navigateurs supportés** fonctionnent
- [ ] **Mode sombre** si implémenté
- [ ] **Contrastes élevés** Windows si requis
- [ ] **Accessibilité** maintenue

📄 Licence
----------

Ce package est distribué sous licence [MIT](LICENSE.md).

🙏 Remerciements
----------------

- **Bootstrap Team** pour le framework exceptionnel
- **Laravel Team** pour l'écosystème de développement
- **Christian Oliff** pour son outil de migration Node.js inspirant
- **Communauté open source** pour les retours et contributions

---

**Développé avec ❤️ par [forxer](https://github.com/forxer)**

Pour toute question ou suggestion, n'hésitez pas à [ouvrir une issue](https://github.com/forxer/bootstrap5-migrator/issues) ! 🚀