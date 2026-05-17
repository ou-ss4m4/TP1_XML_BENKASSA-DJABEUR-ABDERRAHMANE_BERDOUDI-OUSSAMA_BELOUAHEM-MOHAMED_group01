<?php
/**
 * header.php — En-tête et navigation partagés
 * Variables attendues : $pageTitle (string), $activePage (string)
 */
$activePage = $activePage ?? '';
$pageTitle  = $pageTitle  ?? 'Club InfoTech';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> — Club InfoTech</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="nav">
  <div class="wrap nav-inner">
    <a href="index.php" class="nav-brand">
      <div class="nav-brand-icon">
        <svg width="14" height="14" viewBox="0 0 22 22" fill="none">
          <rect width="22" height="22" rx="5" fill="#2563eb"/>
          <path d="M6 16V8l5-3 5 3v8" stroke="#fff" stroke-width="1.8" stroke-linejoin="round"/>
          <rect x="9" y="11" width="4" height="5" rx="1" fill="#fff"/>
        </svg>
      </div>
      Info<span>Tech</span>
    </a>
    <div class="nav-links">
      <a href="index.php"       class="<?= $activePage==='concours'    ? 'active':'' ?>">Concours</a>
      <a href="inscription.php" class="<?= $activePage==='inscription' ? 'active':'' ?>">Inscription</a>
      <a href="resultats.php"   class="<?= $activePage==='resultats'   ? 'active':'' ?>">Résultats</a>
      <a href="requetes.php"    class="<?= $activePage==='requetes'    ? 'active':'' ?>">Requêtes</a>
    </div>
  </div>
</nav>
