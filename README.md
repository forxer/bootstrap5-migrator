Bootstrap 5 Migrator pour Laravel
=================================

Ce package facilite la migration d'une application Laravel utilisant Bootstrap 4.6 vers Bootstrap 5.x.

## ⚠️ Points Importants

**Bootstrap 5 introduit des changements majeurs :**
- **jQuery n'est plus requis** - Bootstrap 5 utilise du JavaScript vanilla
- **Popper.js** est remplacé par **@popperjs/core**
- **Nombreuses classes CSS ont changé** (ml-* → ms-*, form-group supprimé, etc.)
- **Attributs data-*** préfixés par `data-bs-`
- **Composants supprimés** : Jumbotron, Media object
- **Formulaires entièrement refactorisés**

## Installation

```bash
composer require forxer/bootstrap5-migrator
```

## Configuration

Si besoin, publiez le fichier de configuration :

```bash
php artisan vendor:publish --tag="bootstrap5-migrator-config"
```

## Utilisation

### Analyse préliminaire

La commande commence toujours par analyser votre application :

```bash
php artisan bootstrap:migrate-to-5 --dry-run
```

### Migration complète

```bash
php artisan bootstrap:migrate-to-5 --backup
```

### Options disponibles

```bash
# Mode dry-run (affiche les changements sans les appliquer)
php artisan bootstrap:migrate-to-5 --dry-run

# Créer une sauvegarde avant migration
php artisan bootstrap:migrate-to-5 --backup

# Forcer la migration sans confirmation
php artisan bootstrap:migrate-to-5 --force

# Conserver jQuery (ne pas le supprimer automatiquement)
php artisan bootstrap:migrate-to-5 --skip-jquery
```

## Ce que fait le package

### 1. Analyse de l'application
- Détecte la version actuelle de Bootstrap
- Identifie l'usage de jQuery et Popper.js
- Liste les classes CSS obsolètes
- Trouve les composants JavaScript à migrer

### 2. Mise à jour des dépendances
- **package.json** : Bootstrap 4.6 → 5.3
- **Popper.js** → **@popperjs/core**
- **jQuery** : maintenu mais signalé comme optionnel

### 3. Migration des fichiers

#### Fichiers CSS/SCSS
- Mise à jour des imports Bootstrap
- Remplacement des variables SCSS obsolètes
- Migration des classes dans les fichiers de style

#### Fichiers JavaScript
- Mise à jour des imports Bootstrap
- Remplacement des attributs `data-*`
- Migration des événements Bootstrap

#### Templates Blade
- Remplacement automatique des classes CSS
- Migration des attributs `data-*`
- Restructuration des formulaires
- Simplification des input groups

### 4. Compilation des assets
- Exécute `npm install`
- Compile avec `npm run dev` ou `npm run build`

## Mappings des classes principales

### Utilities de spacing (direction-aware)
```html
<!-- Bootstrap 4 -->
<div class="ml-3 mr-2 pl-4 pr-1">

<!-- Bootstrap 5 -->
<div class="ms-3 me-2 ps-4 pe-1">
```

### Alignement du texte
```html
<!-- Bootstrap 4 -->
<p class="text-left text-right">

<!-- Bootstrap 5 -->
<p class="text-start text-end">
```

### Formulaires (changements majeurs)
```html
<!-- Bootstrap 4 -->
<div class="form-group">
    <select class="custom-select">
        <option>...</option>
    </select>
</div>

<!-- Bootstrap 5 -->
<div class="mb-3">
    <select class="form-select">
        <option>...</option>
    </select>
</div>
```

### Input Groups (simplifiés)
```html
<!-- Bootstrap 4 -->
<div class="input-group">
    <div class="input-group-prepend">
        <span class="input-group-text">@</span>
    </div>
    <input type="text" class="form-control">
</div>

<!-- Bootstrap 5 -->
<div class="input-group">
    <span class="input-group-text">@</span>
    <input type="text" class="form-control">
</div>
```

### Badges (nouvelles classes background)
```html
<!-- Bootstrap 4 -->
<span class="badge badge-primary badge-warning">

<!-- Bootstrap 5 -->
<span class="badge bg-primary bg-warning text-dark">
```

### Attributs data-* (préfixe data-bs-)
```html
<!-- Bootstrap 4 -->
<button data-toggle="modal" data-target="#myModal">
<button data-dismiss="modal">

<!-- Bootstrap 5 -->
<button data-bs-toggle="modal" data-bs-target="#myModal">
<button data-bs-dismiss="modal">
```

## Composants supprimés et remplacements

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

## Après la migration

### Tests recommandés
1. **Composants interactifs** : Modals, dropdowns, tooltips, popovers
2. **Formulaires** : Validation, contrôles customisés
3. **Grille et responsivité** : Points de rupture, gutters
4. **JavaScript** : Si vous utilisez jQuery avec Bootstrap

### Vérifications manuelles
- **Icons** : Considérez Bootstrap Icons ou une alternative
- **jQuery** : Évaluez si vous en avez encore besoin
- **CSS custom** : Vérifiez les surcharges de styles
- **Plugins tiers** : Assurez-vous de leur compatibilité

## Personnalisation

Modifiez `config/bootstrap5-migrator.php` pour :
- Ajouter vos propres mappings de classes
- Configurer les répertoires à analyser
- Personnaliser les commandes de build
- Gérer la suppression automatique de jQuery

## Support et Migration manuelle

Pour des cas complexes non couverts par ce package :
- [Guide officiel de migration Bootstrap 5](https://getbootstrap.com/docs/5.0/migration/)
- [Documentation Bootstrap 5](https://getbootstrap.com/docs/5.0/)

## Tests

```bash
composer test
```

## Licence

MIT