<?php
/**
 * resultats.php — Page 3 : Résultats & Palmarès
 * Implémente Q3 (scores) et Q4 (palmarès) via SimpleXML + PHP
 */
require_once __DIR__ . '/includes/data.php';
$pageTitle  = 'Résultats';
$activePage = 'resultats';

$concours = getConcours();
$activeId = $_GET['id'] ?? array_key_first($concours);
if (!isset($concours[$activeId])) $activeId = array_key_first($concours);
$co = $concours[$activeId];

include __DIR__ . '/includes/header.php';
?>

<div class="wrap">
  <div class="page-header">
    <div class="eyebrow">Palmarès</div>
    <h1>Résultats des Concours</h1>
    <p>Score = (complexité + tempsExécution) × coefficient — Q3 &amp; Q4</p>
  </div>

  <!-- Onglets concours -->
  <div class="tab-bar">
    <?php foreach ($concours as $c): ?>
      <a href="?id=<?= urlencode($c['id']) ?>"
         class="tab-btn <?= $c['id']===$activeId?'active':'' ?>">
        <?= htmlspecialchars($c['titre']) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <?php
    $col     = catColor($co['categorieRef']);
    $ranked  = $co['participants'];
    $maxScore= $co['maxScore'];
  ?>

  <!-- Vainqueur -->
  <div class="winner-banner">
    <div class="winner-trophy">🥇</div>
    <div class="winner-info">
      <h3>
        <?php foreach ($co['winners'] as $i => $w): ?>
          <?= htmlspecialchars($w['prenom'].' '.$w['nom']) ?><?= $i<count($co['winners'])-1?' &amp; ':'' ?>
        <?php endforeach; ?>
        <?php if (count($co['winners'])>1): ?>
          <span style="font-size:.78rem;color:#92400e;font-weight:400"> — Ex-aequo</span>
        <?php endif; ?>
      </h3>
      <p>Vainqueur · <?= htmlspecialchars($co['titre']) ?> · <?= $co['date'] ?></p>
    </div>
    <div class="winner-score"><?= number_format($maxScore,2) ?></div>
  </div>

  <div class="grid-2" style="align-items:start">

    <!-- Classement -->
    <div>
      <h2 class="mb-4">Classement complet</h2>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>#</th><th>Participant</th><th>Complexité</th><th>Temps (ms)</th><th>Score</th></tr>
          </thead>
          <tbody>
            <?php foreach ($ranked as $i => $p):
              $isWinner = abs($p['score'] - $maxScore) < 0.001;
              $pct = $maxScore > 0 ? ($p['score'] / $maxScore) * 100 : 0;
              $medal = $i===0?'🥇':($i===1?'🥈':($i===2?'🥉':($i+1)));
            ?>
            <tr <?= $isWinner?'style="background:#fffbeb"':'' ?>>
              <td><span style="font-weight:700;color:var(--muted)"><?= $medal ?></span></td>
              <td>
                <div style="display:flex;align-items:center;gap:10px">
                  <div class="avatar" style="background:<?=$col['bg']?>;color:<?=$col['text']?>">
                    <?= strtoupper(substr($p['prenom'],0,1).substr($p['nom'],0,1)) ?>
                  </div>
                  <div>
                    <div style="font-weight:600;font-size:.9rem"><?= htmlspecialchars($p['prenom'].' '.$p['nom']) ?></div>
                    <div style="font-size:.73rem;color:var(--muted)"><?= $p['membreRef'] ?></div>
                  </div>
                </div>
              </td>
              <td><?= $p['complexite'] ?></td>
              <td><?= $p['tempsExecution'] ?></td>
              <td>
                <div class="score-bar-wrap">
                  <div class="score-bar">
                    <div class="score-fill" style="width:<?= round($pct) ?>%;<?= $isWinner?'background:#d97706':'' ?>"></div>
                  </div>
                  <span class="score-val" style="<?= $isWinner?'color:#d97706':'' ?>"><?= number_format($p['score'],2) ?></span>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Détails + XQuery -->
    <div style="display:flex;flex-direction:column;gap:16px">

      <div class="card">
        <div class="card-body">
          <h3 style="margin-bottom:14px">Détails du concours</h3>
          <?php foreach ([
            ['Identifiant',  $co['id']],
            ['Date',         $co['date']],
            ['Catégorie',    $co['categorie']],
            ['Coefficient',  '×'.$co['coefficient']],
            ['Participants', $co['nbPart'].' membre(s)'],
            ['Score max',    number_format($co['maxScore'],2)],
          ] as [$l,$v]): ?>
          <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);font-size:.86rem">
            <span style="color:var(--muted)"><?= $l ?></span>
            <span style="font-weight:600"><?= htmlspecialchars((string)$v) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <h3 style="margin-bottom:12px">XQuery — Q3 Scores</h3>
          <div class="xquery-box"><span class="xq-cmt">(: Q3 — Score de chaque participant :)</span>
<span class="xq-tag">&lt;scoresParticipants&gt;</span>
{
  <span class="xq-kw">for</span> $co <span class="xq-kw">in</span> <span class="xq-fn">doc</span>(<span class="xq-str">"club.xml"</span>)
              //concours/concours
  <span class="xq-kw">let</span> $coef := <span class="xq-fn">xs:decimal</span>($co/<span class="xq-attr">@coefficient</span>)
  <span class="xq-kw">for</span> $p <span class="xq-kw">in</span> $co/participants/participant
  <span class="xq-kw">let</span> $c := <span class="xq-fn">xs:integer</span>($p/complexite)
  <span class="xq-kw">let</span> $t := <span class="xq-fn">xs:integer</span>($p/tempsExecution)
  <span class="xq-kw">let</span> $s := ($c <span class="xq-op">+</span> $t) <span class="xq-op">*</span> $coef
  <span class="xq-kw">return</span> <span class="xq-tag">&lt;resultat&gt;</span>
    <span class="xq-tag">&lt;score&gt;</span>{ <span class="xq-fn">format-number</span>($s,<span class="xq-str">"#0.00"</span>) }<span class="xq-tag">&lt;/score&gt;</span>
  <span class="xq-tag">&lt;/resultat&gt;</span>
}
<span class="xq-tag">&lt;/scoresParticipants&gt;</span></div>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <h3 style="margin-bottom:12px">XQuery — Q4 Palmarès</h3>
          <div class="xquery-box"><span class="xq-cmt">(: Q4 — Vainqueur de chaque concours :)</span>
<span class="xq-tag">&lt;palmares&gt;</span>
{
  <span class="xq-kw">for</span> $co <span class="xq-kw">in</span> <span class="xq-fn">doc</span>(<span class="xq-str">"club.xml"</span>)
              //concours/concours
  <span class="xq-kw">let</span> $coef := <span class="xq-fn">xs:decimal</span>($co/<span class="xq-attr">@coefficient</span>)
  <span class="xq-kw">let</span> $max  := <span class="xq-fn">max</span>(
    <span class="xq-kw">for</span> $p <span class="xq-kw">in</span> $co//participant
    <span class="xq-kw">return</span> (<span class="xq-fn">xs:integer</span>($p/complexite)
           <span class="xq-op">+</span> <span class="xq-fn">xs:integer</span>($p/tempsExecution))
           <span class="xq-op">*</span> $coef)
  <span class="xq-kw">return</span>
    <span class="xq-tag">&lt;concours</span> <span class="xq-attr">titre</span>=<span class="xq-str">"{$co/titre}"</span><span class="xq-tag">&gt;</span>
    { <span class="xq-kw">for</span> $p <span class="xq-kw">in</span> $co//participant
      <span class="xq-kw">where</span> (...) <span class="xq-op">=</span> $max
      <span class="xq-kw">return</span> <span class="xq-tag">&lt;premier&gt;</span>...<span class="xq-tag">&lt;/premier&gt;</span> }
    <span class="xq-tag">&lt;/concours&gt;</span>
}
<span class="xq-tag">&lt;/palmares&gt;</span></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Résumé global -->
  <div class="mt-8">
    <h2 class="mb-4">Résumé de tous les concours</h2>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Concours</th><th>Catégorie</th><th>Date</th><th>Coeff.</th><th>Participants</th><th>Score max</th><th>Vainqueur</th></tr>
        </thead>
        <tbody>
          <?php foreach ($concours as $c):
            $cl = catColor($c['categorieRef']);
          ?>
          <tr <?= $c['id']===$activeId?'style="background:#eff6ff"':'' ?>>
            <td><a href="?id=<?= urlencode($c['id']) ?>" style="font-weight:600"><?= htmlspecialchars($c['titre']) ?></a></td>
            <td>
              <span class="badge" style="background:<?=$cl['bg']?>;color:<?=$cl['text']?>;border-color:<?=$cl['border']?>">
                <span class="badge-dot" style="background:<?=$cl['dot']?>"></span>
                <?= htmlspecialchars($c['categorie']) ?>
              </span>
            </td>
            <td><?= $c['date'] ?></td>
            <td>×<?= $c['coefficient'] ?></td>
            <td><?= $c['nbPart'] ?></td>
            <td style="font-weight:700;color:var(--accent)"><?= number_format($c['maxScore'],2) ?></td>
            <td><?php foreach($c['winners'] as $i=>$w): ?><?= htmlspecialchars($w['prenom'].' '.$w['nom']) ?><?= $i<count($c['winners'])-1?' &amp; ':'' ?><?php endforeach; ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
