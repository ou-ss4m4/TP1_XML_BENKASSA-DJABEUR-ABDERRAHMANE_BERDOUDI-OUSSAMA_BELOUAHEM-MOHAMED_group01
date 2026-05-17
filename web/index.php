<?php
/**
 * index.php — Page 1 : Liste des concours disponibles
 * Lit club.xml via ../club.xml (un niveau au-dessus de web/)
 */
require_once __DIR__ . '/includes/data.php';
$pageTitle  = 'Concours';
$activePage = 'concours';
include __DIR__ . '/includes/header.php';

$categories = getCategories();
$concours   = getConcours();
$membres    = getMembres();
$totalPart  = array_sum(array_column($concours, 'nbPart'));
?>

<div class="wrap">
  <div class="page-header">
    <div class="eyebrow">Tableau de bord</div>
    <h1>Liste des Concours Disponibles</h1>
    <p>Compétitions organisées par le Club InfoTech — catégories, coefficients et résultats.</p>
  </div>

  <!-- Statistiques -->
  <div class="grid-4 mb-6">
    <div class="stat-card">
      <div class="lbl">Concours</div>
      <div class="val"><?= count($concours) ?></div>
      <div class="sub">sessions organisées</div>
    </div>
    <div class="stat-card">
      <div class="lbl">Catégories</div>
      <div class="val"><?= count($categories) ?></div>
      <div class="sub">spécialités</div>
    </div>
    <div class="stat-card">
      <div class="lbl">Membres</div>
      <div class="val"><?= count($membres) ?></div>
      <div class="sub">inscrits au club</div>
    </div>
    <div class="stat-card">
      <div class="lbl">Participations</div>
      <div class="val"><?= $totalPart ?></div>
      <div class="sub">au total</div>
    </div>
  </div>

  <!-- Liste des concours -->
  <div class="co-list mb-6">
    <?php foreach ($concours as $co):
      $col = catColor($co['categorieRef']);
    ?>
    <a class="co-card" href="resultats.php?id=<?= urlencode($co['id']) ?>">
      <div>
        <div class="co-card-meta">
          <span class="badge" style="background:<?= $col['bg'] ?>;color:<?= $col['text'] ?>;border-color:<?= $col['border'] ?>">
            <span class="badge-dot" style="background:<?= $col['dot'] ?>"></span>
            <?= htmlspecialchars($co['categorie']) ?>
          </span>
          <span style="font-size:.72rem;color:var(--muted);font-weight:700"><?= $co['id'] ?></span>
        </div>
        <div class="co-title"><?= htmlspecialchars($co['titre']) ?></div>
        <div class="co-details">
          <span>📅 <?= $co['date'] ?></span>
          <span>⚡ Coefficient ×<?= $co['coefficient'] ?></span>
          <span>👥 <?= $co['nbPart'] ?> participant<?= $co['nbPart']>1?'s':'' ?></span>
          <span>🥇 <?= htmlspecialchars($co['winners'][0]['prenom'].' '.$co['winners'][0]['nom']) ?></span>
        </div>
      </div>
      <div class="co-score">
        <div class="num"><?= number_format($co['maxScore'],2) ?></div>
        <div class="lbl2">score max</div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- Catégories + Membres -->
  <div class="grid-2" style="align-items:start">

    <div>
      <h2 class="mb-4">Catégories</h2>
      <div class="grid-3" style="gap:12px">
        <?php foreach ($categories as $id => $lib):
          $col  = catColor($id);
          $nbM  = count(array_filter($membres,  fn($m) => $m['categorieRef'] === $id));
          $nbC  = count(array_filter($concours, fn($c) => $c['categorieRef'] === $id));
        ?>
        <div class="cat-card" style="border-left-color:<?= $col['dot'] ?>">
          <div class="cat-id"><?= $id ?></div>
          <div class="cat-name"><?= htmlspecialchars($lib) ?></div>
          <div class="cat-meta"><?= $nbM ?> membres · <?= $nbC ?> concours</div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div>
      <h2 class="mb-4">Membres du club</h2>
      <div class="member-list">
        <?php foreach (array_slice($membres, 0, 6) as $m):
          $col = catColor($m['categorieRef']);
        ?>
        <div class="member-row">
          <div class="avatar" style="background:<?= $col['bg'] ?>;color:<?= $col['text'] ?>"><?= $m['initiales'] ?></div>
          <div class="member-info" style="flex:1">
            <div class="member-name"><?= htmlspecialchars($m['prenom'].' '.$m['nom']) ?></div>
            <div class="member-email"><?= htmlspecialchars($m['email']) ?></div>
          </div>
          <span class="badge" style="background:<?= $col['bg'] ?>;color:<?= $col['text'] ?>;border-color:<?= $col['border'] ?>;font-size:.62rem"><?= $m['categorieRef'] ?></span>
        </div>
        <?php endforeach; ?>
        <?php if (count($membres) > 6): ?>
          <p style="text-align:center;font-size:.8rem;color:var(--muted)">+ <?= count($membres)-6 ?> autres membres</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
