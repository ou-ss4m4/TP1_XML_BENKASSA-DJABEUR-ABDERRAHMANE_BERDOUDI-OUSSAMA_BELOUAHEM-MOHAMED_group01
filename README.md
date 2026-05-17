# Mini-projet XML/XQuery — Club InfoTech

**Licence 3 ISIL — Université de Skikda**

---

## Structure du projet

```
miniprojet_XML_NomPrenom1_NomPrenom2_groupe/
│
├── club.xml          # Document XML principal         (Partie 3.1)
├── club.xsd          # Schéma de validation            (Partie 3.2)
├── requetes.xq       # Fichier des 5 requêtes          (Partie 3.3)
├── updates.xq        # Fichier des mises à jour        (Partie 3.4)
│
├── capture/          # Captures d'écran BaseX
│   └── ...
│
└── web/              # Application web PHP             (Partie 3.5)
    ├── index.php         ← Page 1 : Concours
    ├── inscription.php   ← Page 2 : Inscription
    ├── resultats.php     ← Page 3 : Résultats & Palmarès
    ├── requetes.php      ← Page 4 : Requêtes Q1/Q2/Q5 + Updates
    ├── style.css         ← Feuille de style
    ├── 404.php           ← Page d'erreur
    ├── .htaccess         ← Config Apache
    └── includes/
        ├── data.php      ← Accès à ../club.xml via SimpleXML
        ├── header.php    ← En-tête partagée
        └── footer.php    ← Pied de page partagé
```

> **Important** : `web/includes/data.php` charge `../club.xml`
> (un niveau au-dessus de `web/`). La structure ci-dessus doit
> être respectée pour que le site fonctionne.

---

## Prérequis

- **PHP 8.0+** avec extensions `simplexml` et `dom` (actives par défaut)
- **Apache** avec `mod_rewrite` — ou XAMPP / Laragon / WAMP

---

## Installation

### XAMPP (recommandé)

1. Copiez le dossier dans `C:\xampp\htdocs\` :
   ```
   C:\xampp\htdocs\miniprojet_XML_NomPrenom1.../
   ```
2. Démarrez **Apache** depuis le panneau XAMPP
3. Ouvrez dans le navigateur :
   ```
   http://localhost/miniprojet_XML_NomPrenom1.../web/
   ```

### Laragon

1. Copiez dans le dossier `www/`
2. Ouvrez `http://miniprojet.test/web/`

### Serveur PHP intégré (test rapide)

```bash
cd web/
php -S localhost:8080
# Puis ouvrir http://localhost:8080/
```

---

## Pages de l'application

| Page | Fichier | Requête XQuery équivalente |
|---|---|---|
| Concours | `index.php` | — vue générale |
| Inscription | `inscription.php` | `insert node` (U1-style) |
| Résultats | `resultats.php` | Q3 scores + Q4 palmarès |
| Q1 Membres | `requetes.php?tab=q1` | Q1 liste membres |
| Q2 Concours | `requetes.php?tab=q2` | Q2 triés par date |
| Q5 Domaine | `requetes.php?tab=q5` | Q5 membres par catégorie |
| U1 Insert | `requetes.php?tab=updates` | `insert node ... into` |
| U2 Replace | `requetes.php?tab=updates` | `replace value of node` |
| U3 Delete | `requetes.php?tab=updates` | `delete node` |

---

## Correspondance XQuery → PHP (SimpleXML)

| XQuery | PHP SimpleXML |
|---|---|
| `doc("club.xml")//membre` | `$xml->membres->membre` |
| `order by xs:date(@date) ascending` | `usort()` + `strcmp()` |
| `let $score := (C+T)*coef` | calcul en boucle PHP |
| `max($tousScores)` | `max(array_column(...))` |
| `insert node ... into` | `addChild()` + `saveXML()` |
| `replace value of node ... with` | `$node['attr'] = valeur` |
| `delete node` | `dom_import_simplexml()` + `removeChild()` |

---

## Notes importantes

- Les mises à jour **U1, U2, U3** modifient `club.xml` directement.
- Assurez-vous que `club.xml` est **accessible en écriture** par le serveur web.
  - Windows (XAMPP) : pas de problème par défaut
  - Linux : `chmod 664 club.xml`
- Pour réinitialiser, remplacez `club.xml` par une sauvegarde.
