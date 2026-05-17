<?php
/**
 * inscription.php — Page 2 : Inscription d'un membre à un concours
 * Valide la catégorie, calcule le score estimé et persiste dans ../club.xml
 */
require_once __DIR__ . '/includes/data.php';
$pageTitle  = 'Inscription';
$activePage = 'inscription';

$concours = getConcours();
$membres  = getMembres();
$message  = null;
$msgType  = 'success';
$xqueryGenerated = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $coId       = trim($_POST['concours_id'] ?? '');
    $memId      = trim($_POST['membre_id']   ?? '');
    $complexite = (int)($_POST['complexite'] ?? -1);
    $temps      = (int)($_POST['temps']      ?? 0);

    $errors = [];
    if (!isset($concours[$coId]))              $errors[] = "Concours invalide.";
    if (!isset($membres[$memId]))              $errors[] = "Membre invalide.";
    if ($complexite < 0 || $complexite > 100)  $errors[] = "Complexité doit être entre 0 et 100.";
    if ($temps < 1)                            $errors[] = "Temps d'exécution doit être > 0 ms.";

    if (!$errors && $membres[$memId]['categorieRef'] !== $concours[$coId]['categorieRef']) {
        $errors[] = "Catégorie incompatible : le membre est en «{$membres[$memId]['categorie']}» mais le concours est pour «{$concours[$coId]['categorie']}».";
    }
    if (!$errors) {
        foreach ($concours[$coId]['participants'] as $p) {
            if ($p['membreRef'] === $memId) {
                $errors[] = "Ce membre participe déjà à ce concours."; break;
            }
        }
    }

    if ($errors) {
        $message = implode(' ', $errors); $msgType = 'error';
    } else {
        // XQuery Update équivalent (affiché à l'utilisateur)
        $xqueryGenerated = "insert node\n"
            . "  <participant membreRef=\"{$memId}\">\n"
            . "    <complexite>{$complexite}</complexite>\n"
            . "    <tempsExecution>{$temps}</tempsExecution>\n"
            . "  </participant>\n"
            . "into doc(\"club.xml\")//concours[@id = \"{$coId}\"]/participants";

        // Persistance dans ../club.xml via SimpleXML
        $xml = loadXML();
        foreach ($xml->concours->concours as $co) {
            if ((string)$co['id'] === $coId) {
                $p = $co->participants->addChild('participant');
                $p->addAttribute('membreRef', $memId);
                $p->addChild('complexite',     $complexite);
                $p->addChild('tempsExecution', $temps);
                break;
            }
        }
        saveXML($xml);

        $score   = ($complexite + $temps) * $concours[$coId]['coefficient'];
        $message = "✓ {$membres[$memId]['prenom']} {$membres[$memId]['nom']} inscrit(e) à «{$concours[$coId]['titre']}» — score estimé : " . number_format($score, 2);
        $concours = getConcours(); // rechargement
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="wrap">
  <div class="page-header">
    <div class="eyebrow">Gestion</div>
    <h1>Nouvelle Inscription</h1>
    <p>Inscrivez un membre à un concours de sa spécialité. La participation est enregistrée dans club.xml.</p>
  </div>

  <?php if ($message): ?>
    <div class="alert alert-<?= $msgType ?>">
      <?= $msgType==='success' ? '✓' : '✗' ?> <?= htmlspecialchars($message) ?>
    </div>
  <?php endif; ?>

  <div class="grid-2" style="align-items:start">

    <!-- Formulaire -->
    <div class="card">
      <div class="card-body">
        <h2 style="margin-bottom:20px">Formulaire d'inscription</h2>
        <form method="POST" class="form-grid">

          <div class="form-group">
            <label class="form-label" for="concours_id">Concours</label>
            <select class="form-control" name="concours_id" id="concours_id" required onchange="filterMembres(this)">
              <option value="">— Sélectionner un concours —</option>
              <?php foreach ($concours as $co): ?>
                <option value="<?= $co['id'] ?>"
                        data-cat="<?= $co['categorieRef'] ?>"
                        data-coef="<?= $co['coefficient'] ?>"
                        <?= (($_POST['concours_id']??'')===$co['id'])?'selected':'' ?>>
                  <?= htmlspecialchars($co['titre']) ?> (×<?= $co['coefficient'] ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div id="cat-hint" style="display:none" class="alert alert-info" style="margin:0"></div>

          <div class="form-group">
            <label class="form-label" for="membre_id">Membre</label>
            <select class="form-control" name="membre_id" id="membre_id" required>
              <option value="">— Choisir un concours d'abord —</option>
              <?php foreach ($membres as $m): ?>
                <option value="<?= $m['id'] ?>"
                        data-cat="<?= $m['categorieRef'] ?>"
                        <?= (($_POST['membre_id']??'')===$m['id'])?'selected':'' ?>>
                  <?= htmlspecialchars($m['prenom'].' '.$m['nom']) ?> (<?= $m['categorie'] ?>)
                </option>
              <?php endforeach; ?>
            </select>
            <span class="form-hint" id="eligible-hint"></span>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="complexite">Complexité (0–100)</label>
              <input class="form-control" type="number" name="complexite" id="complexite"
                     min="0" max="100" placeholder="85"
                     value="<?= htmlspecialchars($_POST['complexite']??'') ?>"
                     required oninput="updateScore()">
              <span class="form-hint">Score de l'algorithme soumis</span>
            </div>
            <div class="form-group">
              <label class="form-label" for="temps">Temps exec. (ms)</label>
              <input class="form-control" type="number" name="temps" id="temps"
                     min="1" placeholder="120"
                     value="<?= htmlspecialchars($_POST['temps']??'') ?>"
                     required oninput="updateScore()">
              <span class="form-hint">Durée en millisecondes</span>
            </div>
          </div>

          <div class="score-preview" id="score-preview" style="display:none">
            <div class="sp-label">Score estimé</div>
            <div class="sp-value" id="score-val">—</div>
            <div class="sp-hint">(complexité + temps) × coefficient</div>
          </div>

          <button type="submit" class="btn btn-primary btn-full">Enregistrer l'inscription →</button>
        </form>
      </div>
    </div>

    <!-- Colonne droite -->
    <div style="display:flex;flex-direction:column;gap:18px">

      <?php if ($xqueryGenerated): ?>
      <div class="card">
        <div class="card-body">
          <h3 style="margin-bottom:12px">XQuery Update généré</h3>
          <div class="xquery-box"><?php
            $q = htmlspecialchars($xqueryGenerated);
            $q = preg_replace('/\b(insert|node|into|doc|replace|value|of|delete|with|for|let|return|where|order|by|ascending)\b/', '<span class="xq-kw">$0</span>', $q);
            $q = preg_replace('/"([^"]*)"/', '<span class="xq-str">"$1"</span>', $q);
            $q = preg_replace('/(<\/?)(\w+)/', '<span class="xq-tag">$1$2</span>', $q);
            echo $q;
          ?></div>
        </div>
      </div>
      <?php endif; ?>

      <div class="card">
        <div class="card-body">
          <h3 style="margin-bottom:14px">Concours disponibles</h3>
          <?php foreach ($concours as $co):
            $col = catColor($co['categorieRef']);
          ?>
          <div style="padding:10px 0;border-bottom:1px solid var(--border)">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
              <span class="badge" style="background:<?=$col['bg']?>;color:<?=$col['text']?>;border-color:<?=$col['border']?>">
                <span class="badge-dot" style="background:<?=$col['dot']?>"></span>
                <?= htmlspecialchars($co['categorie']) ?>
              </span>
              <span style="font-weight:600;font-size:.88rem"><?= htmlspecialchars($co['titre']) ?></span>
            </div>
            <div style="font-size:.78rem;color:var(--muted)"><?= $co['nbPart'] ?> participant(s) · ×<?= $co['coefficient'] ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="alert alert-info">
        <strong>Règles :</strong> un membre ne peut s'inscrire qu'à un concours de sa catégorie, et une seule fois par concours.
      </div>
    </div>
  </div>
</div>

<script>
const membres = <?= json_encode(array_values($membres)) ?>;
const concours = <?= json_encode(array_values($concours)) ?>;

function filterMembres(sel) {
  const cat   = sel.options[sel.selectedIndex]?.dataset.cat || '';
  const coef  = parseFloat(sel.options[sel.selectedIndex]?.dataset.coef || 0);
  const mSel  = document.getElementById('membre_id');
  const hint  = document.getElementById('eligible-hint');
  const catHint = document.getElementById('cat-hint');

  mSel.innerHTML = '<option value="">— Sélectionner un membre —</option>';
  const elig = membres.filter(m => m.categorieRef === cat);

  if (cat) {
    catHint.style.display = 'block';
    catHint.textContent   = elig.length + ' membre(s) éligible(s) pour cette catégorie';
    elig.forEach(m => {
      const o = document.createElement('option');
      o.value = m.id; o.textContent = m.prenom + ' ' + m.nom;
      mSel.appendChild(o);
    });
    hint.textContent = elig.length + ' membre(s) de cette catégorie';
  } else {
    catHint.style.display = 'none';
    hint.textContent = '';
  }
  updateScore();
}

function updateScore() {
  const coSel = document.getElementById('concours_id');
  const coId  = coSel.value;
  const co    = concours.find(c => c.id === coId);
  const c     = parseInt(document.getElementById('complexite').value) || 0;
  const t     = parseInt(document.getElementById('temps').value)      || 0;
  const prev  = document.getElementById('score-preview');

  if (co && c >= 0 && t > 0) {
    document.getElementById('score-val').textContent = ((c + t) * co.coefficient).toFixed(2);
    prev.style.display = 'block';
  } else {
    prev.style.display = 'none';
  }
}

window.addEventListener('DOMContentLoaded', () => {
  const coSel = document.getElementById('concours_id');
  if (coSel.value) filterMembres(coSel);
  updateScore();
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
