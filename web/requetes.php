<?php
/**
 * requetes.php — Requêtes XQuery (Q1–Q5) + Mises à jour (U1–U3)
 * Deux listes déroulantes séparées : Requête / Mise à jour
 */
require_once __DIR__ . '/includes/data.php';
$pageTitle  = 'Requêtes & Mises à jour';
$activePage = 'requetes';

$categories = getCategories();
$concours   = getConcours();
$membres    = getMembres();

$activeReq = $_GET['req']     ?? '';
$activeUpd = $_GET['upd']     ?? '';
$section   = $_GET['section'] ?? '';

if ($activeReq) $section = 'req';
if ($activeUpd) $section = 'upd';

$message = null;
$msgType = 'success';

/* ─── UPDATE HANDLER ─────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $xml    = loadXML();
    $section   = 'upd';
    $activeUpd = $action;

    /* U1 — Insert new member */
    if ($action === 'u1') {
        $newId     = trim($_POST['new_id']     ?? '');
        $newCat    = trim($_POST['new_cat']    ?? '');
        $newNom    = trim($_POST['new_nom']    ?? '');
        $newPrenom = trim($_POST['new_prenom'] ?? '');
        $newEmail  = trim($_POST['new_email']  ?? '');
        $errors = [];
        if (!$newId)                                                      $errors[] = "L'identifiant est requis.";
        if ($newId && !preg_match('/^M\d+$/', $newId))                    $errors[] = "Format ID invalide (ex: M011).";
        if (!isset($categories[$newCat]))                                  $errors[] = "Catégorie invalide.";
        if (!$newNom)                                                      $errors[] = "Le nom est requis.";
        if (!$newPrenom)                                                   $errors[] = "Le prénom est requis.";
        if (!$newEmail || !filter_var($newEmail, FILTER_VALIDATE_EMAIL))   $errors[] = "Email invalide.";
        if (!$errors && $xml->xpath("//membre[@id='$newId']"))             $errors[] = "Un membre avec l'ID «$newId» existe déjà.";

        if ($errors) {
            $message = implode(' ', $errors); $msgType = 'error';
        } else {
            $m = $xml->membres->addChild('membre');
            $m->addAttribute('id',           $newId);
            $m->addAttribute('categorieRef', $newCat);
            $m->addChild('nom',    $newNom);
            $m->addChild('prenom', $newPrenom);
            $m->addChild('email',  $newEmail);
            saveXML($xml);
            $message = "✓ Membre «$newPrenom $newNom» ($newId) ajouté dans la catégorie «{$categories[$newCat]}».";
            $membres  = getMembres();
            $concours = getConcours();
        }
    }

    /* U2 — Modify concours field */
    elseif ($action === 'u2') {
        $coId   = trim($_POST['mod_concours'] ?? '');
        $field  = trim($_POST['mod_field']    ?? '');
        $newVal = trim($_POST['mod_value']    ?? '');
        $errors = [];
        if (!isset($concours[$coId]))    $errors[] = "Concours invalide.";
        if (!$field)                      $errors[] = "Champ requis.";
        if ($newVal === '')               $errors[] = "Nouvelle valeur requise.";
        if ($field === 'coefficient' && (!is_numeric($newVal) || (float)$newVal <= 0))
                                          $errors[] = "Coefficient doit être > 0.";
        if ($field === 'date' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $newVal))
                                          $errors[] = "Format date invalide (AAAA-MM-JJ).";
        if ($errors) {
            $message = implode(' ', $errors); $msgType = 'error';
        } else {
            $nodes = $xml->xpath("//concours[@id='$coId']");
            $label = '';
            if      ($field === 'coefficient') { $nodes[0]['coefficient'] = $newVal; $label = "coefficient → $newVal"; }
            elseif  ($field === 'date')        { $nodes[0]['date']        = $newVal; $label = "date → $newVal"; }
            elseif  ($field === 'titre')       { $nodes[0]->titre         = $newVal; $label = "titre → $newVal"; }
            saveXML($xml);
            $message = "✓ Concours «$coId» modifié : $label.";
            $concours = getConcours();
        }
    }

    /* U3 — Delete participant */
    elseif ($action === 'u3') {
        $coId  = trim($_POST['del_concours'] ?? '');
        $memId = trim($_POST['del_membre']   ?? '');
        $errors = [];
        if (!isset($concours[$coId])) $errors[] = "Concours invalide.";
        if (!$memId)                   $errors[] = "Membre requis.";
        $nodes = [];
        if (!$errors) {
            $nodes = $xml->xpath("//concours[@id='$coId']//participant[@membreRef='$memId']");
            if (!$nodes) $errors[] = "Ce membre ne participe pas à ce concours (déjà supprimé ?).";
        }
        if ($errors) {
            $message = implode(' ', $errors); $msgType = 'error';
        } else {
            $dom = dom_import_simplexml($nodes[0]);
            $dom->parentNode->removeChild($dom);
            saveXML($xml);
            $n = $membres[$memId]['prenom'].' '.$membres[$memId]['nom'];
            $message = "✓ Participant «$n» supprimé du concours «{$concours[$coId]['titre']}».";
            $concours = getConcours();
        }
    }
}

/* ─── QUERY RUNNERS ──────────────────────────────────────────── */
function runQ1(array $membres): array {
    return array_values(array_map(fn($m) => [
        'id'=>$m['id'],'nomComplet'=>$m['prenom'].' '.$m['nom'],
        'email'=>$m['email'],'specialite'=>$m['categorie'],
        'catRef'=>$m['categorieRef'],'initiales'=>$m['initiales'],
    ], $membres));
}
function runQ2(array $concours): array {
    $l = array_values($concours);
    usort($l, fn($a,$b)=>strcmp($a['date'],$b['date']));
    return $l;
}
function runQ3(array $concours): array {
    $rows=[];
    foreach($concours as $co)
        foreach($co['participants'] as $p)
            $rows[]=['concours'=>$co['titre'],'participant'=>$p['prenom'].' '.$p['nom'],
                     'complexite'=>$p['complexite'],'tempsExecution'=>$p['tempsExecution'],
                     'score'=>$p['score'],'catRef'=>$co['categorieRef']];
    return $rows;
}
function runQ4(array $concours): array {
    return array_values(array_map(fn($co)=>[
        'titre'=>$co['titre'],'date'=>$co['date'],'winners'=>$co['winners'],
        'maxScore'=>$co['maxScore'],'catRef'=>$co['categorieRef'],
    ], $concours));
}
function runQ5(array $membres, array $categories, string $domaine): array {
    $ref = array_search($domaine,$categories);
    if($ref===false) return [];
    $f = array_filter($membres, fn($m)=>$m['categorieRef']===$ref);
    usort($f, fn($a,$b)=>strcmp($a['nom'].$a['prenom'],$b['nom'].$b['prenom']));
    return array_values($f);
}
$q5Domaine = $_GET['domaine'] ?? array_values($categories)[0] ?? '';

include __DIR__ . '/includes/header.php';
?>

<style>
.two-drop{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:32px}
.drop-box{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:20px 22px}
.drop-box .drop-label{font-size:.72rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin-bottom:10px;display:block}
.drop-select{width:100%;padding:10px 14px;border:1px solid var(--border2);border-radius:8px;font-size:.95rem;font-family:inherit;color:var(--text);background:var(--bg);outline:none;cursor:pointer;transition:border-color .15s}
.drop-select:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(37,99,235,.1)}
.result-panel{animation:fadeIn .25s ease}
@keyframes fadeIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}
.xq-panel{background:#1e1e2e;border-radius:8px;padding:16px 18px;font-family:'Fira Code',monospace;font-size:.77rem;line-height:1.9;color:#cdd6f4;overflow-x:auto;white-space:pre}
.xq-kw{color:#89b4fa}.xq-fn{color:#a6e3a1}.xq-str{color:#f38ba8}.xq-tag{color:#89dceb}.xq-attr{color:#f9e2af}.xq-cmt{color:#6c7086;font-style:italic}.xq-op{color:#fab387}
.upd-form{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:22px 24px}
.field-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.pill{display:inline-flex;align-items:center;gap:5px;padding:2px 9px;border-radius:20px;font-size:.7rem;font-weight:700;border:1px solid}
.sep{height:1px;background:var(--border);margin:20px 0}
.members-scroll{max-height:200px;overflow-y:auto;display:flex;flex-direction:column;gap:5px;margin-top:8px}
.empty-state{text-align:center;padding:56px 24px;color:var(--muted)}
.empty-state .icon{font-size:3rem;margin-bottom:14px}
.empty-state h3{font-size:1rem;font-weight:600;color:var(--text);margin-bottom:6px}
@media(max-width:700px){.two-drop,.field-row,.grid-2{grid-template-columns:1fr}}
</style>

<div class="wrap">

  <div class="page-header">
    <div class="eyebrow">Parties 3.3 &amp; 3.4</div>
    <h1>Requêtes &amp; Mises à jour</h1>
    <p>Sélectionnez une requête XQuery (Q1–Q5) ou une opération de mise à jour (Insérer · Modifier · Supprimer).</p>
  </div>

  <!-- TWO DROPDOWNS -->
  <div class="two-drop">
    <div class="drop-box">
      <span class="drop-label">📋 Requête XQuery</span>
      <form method="GET">
        <input type="hidden" name="section" value="req">
        <select class="drop-select" name="req" onchange="this.form.submit()">
          <option value="">— Choisir une requête —</option>
          <option value="q1" <?= $activeReq==='q1'?'selected':'' ?>>Q1 — Liste des membres</option>
          <option value="q2" <?= $activeReq==='q2'?'selected':'' ?>>Q2 — Concours triés par date</option>
          <option value="q3" <?= $activeReq==='q3'?'selected':'' ?>>Q3 — Scores des participants</option>
          <option value="q4" <?= $activeReq==='q4'?'selected':'' ?>>Q4 — Palmarès des concours</option>
          <option value="q5" <?= $activeReq==='q5'?'selected':'' ?>>Q5 — Membres par domaine</option>
        </select>
      </form>
    </div>
    <div class="drop-box">
      <span class="drop-label">⚙️ Mise à jour</span>
      <form method="GET">
        <input type="hidden" name="section" value="upd">
        <select class="drop-select" name="upd" onchange="this.form.submit()">
          <option value="">— Choisir une opération —</option>
          <option value="u1" <?= $activeUpd==='u1'?'selected':'' ?>>U1 — Insérer un nouveau membre</option>
          <option value="u2" <?= $activeUpd==='u2'?'selected':'' ?>>U2 — Modifier un concours</option>
          <option value="u3" <?= $activeUpd==='u3'?'selected':'' ?>>U3 — Supprimer une participation</option>
        </select>
      </form>
    </div>
  </div>

  <?php if ($message): ?>
    <div class="alert alert-<?= $msgType==='success'?'success':($msgType==='warn'?'warn':'error') ?>" style="margin-bottom:24px">
      <?= htmlspecialchars($message) ?>
    </div>
  <?php endif; ?>


  <!-- ═══════════════════ REQUÊTES ═══════════════════ -->
  <?php if ($section === 'req' && $activeReq): ?>
  <div class="result-panel">

  <?php if ($activeReq === 'q1'): $rows = runQ1($membres); ?>
    <div class="grid-2" style="gap:24px;align-items:start">
      <div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px">
          <span class="pill" style="background:#eff6ff;color:#1e40af;border-color:#93c5fd">Q1</span>
          <h2>Liste des membres</h2>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>ID</th><th>Nom complet</th><th>Email</th><th>Spécialité</th></tr></thead>
            <tbody>
              <?php foreach($rows as $r): $col=catColor($r['catRef']); ?>
              <tr>
                <td style="font-size:.75rem;font-weight:700;color:var(--muted)"><?= $r['id'] ?></td>
                <td><div style="display:flex;align-items:center;gap:9px">
                  <div class="avatar" style="background:<?=$col['bg']?>;color:<?=$col['text']?>"><?= $r['initiales'] ?></div>
                  <span style="font-weight:600"><?= htmlspecialchars($r['nomComplet']) ?></span>
                </div></td>
                <td style="font-size:.8rem;color:var(--muted)"><?= htmlspecialchars($r['email']) ?></td>
                <td><span class="badge" style="background:<?=$col['bg']?>;color:<?=$col['text']?>;border-color:<?=$col['border']?>"><span class="badge-dot" style="background:<?=$col['dot']?>"></span><?= htmlspecialchars($r['specialite']) ?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="alert alert-info mt-4"><?= count($rows) ?> membres dans club.xml</div>
      </div>
      <div>
        <h3 style="margin-bottom:12px">Code XQuery — Q1</h3>
        <div class="xq-panel"><span class="xq-cmt">(: Q1 — Liste des membres avec spécialité :)</span>
<span class="xq-tag">&lt;listeMembers&gt;</span>
{
  <span class="xq-kw">for</span> $membre <span class="xq-kw">in</span>
    <span class="xq-fn">doc</span>(<span class="xq-str">"club.xml"</span>)//membre
  <span class="xq-kw">let</span> $libelleCategorie :=
    <span class="xq-fn">doc</span>(<span class="xq-str">"club.xml"</span>)
      //categorie[<span class="xq-attr">@id</span> =
        $membre/<span class="xq-attr">@categorieRef</span>]/<span class="xq-attr">@libelle</span>
  <span class="xq-kw">return</span>
    <span class="xq-tag">&lt;fiche</span> <span class="xq-attr">id</span>=<span class="xq-str">"{data($membre/@id)}"</span><span class="xq-tag">&gt;</span>
      <span class="xq-tag">&lt;nomComplet&gt;</span>{ <span class="xq-fn">concat</span>(
        <span class="xq-fn">data</span>($membre/prenom),<span class="xq-str">" "</span>,
        <span class="xq-fn">data</span>($membre/nom)) }<span class="xq-tag">&lt;/nomComplet&gt;</span>
      <span class="xq-tag">&lt;email&gt;</span>{ <span class="xq-fn">data</span>($membre/email) }<span class="xq-tag">&lt;/email&gt;</span>
      <span class="xq-tag">&lt;specialite&gt;</span>{ <span class="xq-fn">data</span>($libelleCategorie) }<span class="xq-tag">&lt;/specialite&gt;</span>
    <span class="xq-tag">&lt;/fiche&gt;</span>
}
<span class="xq-tag">&lt;/listeMembers&gt;</span></div>
      </div>
    </div>

  <?php elseif ($activeReq === 'q2'): $rows = runQ2($concours); ?>
    <div class="grid-2" style="gap:24px;align-items:start">
      <div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px">
          <span class="pill" style="background:#eff6ff;color:#1e40af;border-color:#93c5fd">Q2</span>
          <h2>Concours triés par date</h2>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>ID</th><th>Titre</th><th>Date ↑</th><th>Catégorie</th><th>Coeff.</th><th>Participants</th></tr></thead>
            <tbody>
              <?php foreach($rows as $c): $col=catColor($c['categorieRef']); ?>
              <tr>
                <td style="font-size:.75rem;font-weight:700;color:var(--muted)"><?= $c['id'] ?></td>
                <td style="font-weight:600"><?= htmlspecialchars($c['titre']) ?></td>
                <td><?= $c['date'] ?></td>
                <td><span class="badge" style="background:<?=$col['bg']?>;color:<?=$col['text']?>;border-color:<?=$col['border']?>"><span class="badge-dot" style="background:<?=$col['dot']?>"></span><?= htmlspecialchars($c['categorie']) ?></span></td>
                <td style="font-weight:700">×<?= $c['coefficient'] ?></td>
                <td><?= $c['nbPart'] ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="alert alert-info mt-4"><?= count($rows) ?> concours, ordre chronologique</div>
      </div>
      <div>
        <h3 style="margin-bottom:12px">Code XQuery — Q2</h3>
        <div class="xq-panel"><span class="xq-cmt">(: Q2 — Concours triés par date croissante :)
(: xs:date() assure un tri calendaire    :)</span>
<span class="xq-tag">&lt;listeConcours&gt;</span>
{
  <span class="xq-kw">for</span> $co <span class="xq-kw">in</span>
    <span class="xq-fn">doc</span>(<span class="xq-str">"club.xml"</span>)//concours/concours
  <span class="xq-kw">let</span> $libelleCategorie :=
    <span class="xq-fn">doc</span>(<span class="xq-str">"club.xml"</span>)
      //categorie[<span class="xq-attr">@id</span> =
        $co/<span class="xq-attr">@categorieRef</span>]/<span class="xq-attr">@libelle</span>
  <span class="xq-kw">order by</span> <span class="xq-fn">xs:date</span>($co/<span class="xq-attr">@date</span>)
           <span class="xq-kw">ascending</span>
  <span class="xq-kw">return</span>
    <span class="xq-tag">&lt;evenement</span> <span class="xq-attr">id</span>=<span class="xq-str">"{data($co/@id)}"</span><span class="xq-tag">&gt;</span>
      <span class="xq-tag">&lt;titre&gt;</span>{ <span class="xq-fn">data</span>($co/titre) }<span class="xq-tag">&lt;/titre&gt;</span>
      <span class="xq-tag">&lt;date&gt;</span>{ <span class="xq-fn">data</span>($co/<span class="xq-attr">@date</span>) }<span class="xq-tag">&lt;/date&gt;</span>
      <span class="xq-tag">&lt;coefficient&gt;</span>{ <span class="xq-fn">data</span>($co/<span class="xq-attr">@coefficient</span>) }<span class="xq-tag">&lt;/coefficient&gt;</span>
      <span class="xq-tag">&lt;domaine&gt;</span>{ <span class="xq-fn">data</span>($libelleCategorie) }<span class="xq-tag">&lt;/domaine&gt;</span>
    <span class="xq-tag">&lt;/evenement&gt;</span>
}
<span class="xq-tag">&lt;/listeConcours&gt;</span></div>
      </div>
    </div>

  <?php elseif ($activeReq === 'q3'): $rows = runQ3($concours); ?>
    <div class="grid-2" style="gap:24px;align-items:start">
      <div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px">
          <span class="pill" style="background:#eff6ff;color:#1e40af;border-color:#93c5fd">Q3</span>
          <h2>Scores des participants</h2>
        </div>
        <p style="margin-bottom:14px">Formule : <code style="font-family:'Fira Code',monospace;font-size:.85em">(complexité + tempsExécution) × coefficient</code></p>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Concours</th><th>Participant</th><th>Complexité</th><th>Temps (ms)</th><th>Score</th></tr></thead>
            <tbody>
              <?php foreach($rows as $r): ?>
              <tr>
                <td style="font-size:.82rem;color:var(--muted)"><?= htmlspecialchars($r['concours']) ?></td>
                <td style="font-weight:600"><?= htmlspecialchars($r['participant']) ?></td>
                <td><?= $r['complexite'] ?></td>
                <td><?= $r['tempsExecution'] ?></td>
                <td style="font-weight:700;color:var(--accent)"><?= number_format($r['score'],2) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="alert alert-info mt-4"><?= count($rows) ?> participation(s) calculées</div>
      </div>
      <div>
        <h3 style="margin-bottom:12px">Code XQuery — Q3</h3>
        <div class="xq-panel"><span class="xq-cmt">(: Q3 — Score par participant          :)
(: score=(complexite+tempsExecution)*coef :)</span>
<span class="xq-tag">&lt;scoresParticipants&gt;</span>
{
  <span class="xq-kw">for</span> $co <span class="xq-kw">in</span>
    <span class="xq-fn">doc</span>(<span class="xq-str">"club.xml"</span>)//concours/concours
  <span class="xq-kw">let</span> $coef :=
    <span class="xq-fn">xs:decimal</span>(<span class="xq-fn">data</span>($co/<span class="xq-attr">@coefficient</span>))
  <span class="xq-kw">for</span> $p <span class="xq-kw">in</span> $co/participants/participant
  <span class="xq-kw">let</span> $compl :=
    <span class="xq-fn">xs:integer</span>(<span class="xq-fn">data</span>($p/complexite))
  <span class="xq-kw">let</span> $tps   :=
    <span class="xq-fn">xs:integer</span>(<span class="xq-fn">data</span>($p/tempsExecution))
  <span class="xq-kw">let</span> $score := ($compl <span class="xq-op">+</span> $tps) <span class="xq-op">*</span> $coef
  <span class="xq-kw">let</span> $mem   :=
    <span class="xq-fn">doc</span>(<span class="xq-str">"club.xml"</span>)
      //membre[<span class="xq-attr">@id</span> = $p/<span class="xq-attr">@membreRef</span>]
  <span class="xq-kw">return</span>
    <span class="xq-tag">&lt;resultat&gt;</span>
      <span class="xq-tag">&lt;concours&gt;</span>{ <span class="xq-fn">data</span>($co/titre) }<span class="xq-tag">&lt;/concours&gt;</span>
      <span class="xq-tag">&lt;participant&gt;</span>{ <span class="xq-fn">concat</span>(
        $mem/prenom,<span class="xq-str">" "</span>,$mem/nom) }<span class="xq-tag">&lt;/participant&gt;</span>
      <span class="xq-tag">&lt;complexite&gt;</span>{ $compl }<span class="xq-tag">&lt;/complexite&gt;</span>
      <span class="xq-tag">&lt;tempsExecution&gt;</span>{ $tps }<span class="xq-tag">&lt;/tempsExecution&gt;</span>
      <span class="xq-tag">&lt;score&gt;</span>{ <span class="xq-fn">format-number</span>($score,<span class="xq-str">"#0.00"</span>) }<span class="xq-tag">&lt;/score&gt;</span>
    <span class="xq-tag">&lt;/resultat&gt;</span>
}
<span class="xq-tag">&lt;/scoresParticipants&gt;</span></div>
      </div>
    </div>

  <?php elseif ($activeReq === 'q4'): $rows = runQ4($concours); ?>
    <div class="grid-2" style="gap:24px;align-items:start">
      <div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px">
          <span class="pill" style="background:#eff6ff;color:#1e40af;border-color:#93c5fd">Q4</span>
          <h2>Palmarès des concours</h2>
        </div>
        <div style="display:flex;flex-direction:column;gap:14px">
          <?php foreach($rows as $r): $col=catColor($r['catRef']); ?>
          <div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:10px;padding:16px 18px">
            <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:10px">
              <div>
                <div style="font-weight:700;font-size:1rem"><?= htmlspecialchars($r['titre']) ?></div>
                <div style="font-size:.78rem;color:var(--muted);margin-top:2px">📅 <?= $r['date'] ?></div>
              </div>
              <div style="text-align:right">
                <div style="font-size:1.4rem;font-weight:800;color:#d97706"><?= number_format($r['maxScore'],2) ?></div>
                <div style="font-size:.68rem;color:var(--muted);text-transform:uppercase">score max</div>
              </div>
            </div>
            <?php foreach($r['winners'] as $i=>$w): ?>
            <div style="display:flex;align-items:center;gap:10px;padding:8px 12px;background:rgba(255,255,255,.7);border-radius:7px;margin-top:6px">
              <span style="font-size:1.1rem"><?= $i===0?'🥇':($i===1?'🥈':'🥉') ?></span>
              <div class="avatar" style="background:<?=$col['bg']?>;color:<?=$col['text']?>"><?= strtoupper(substr($w['prenom'],0,1).substr($w['nom'],0,1)) ?></div>
              <span style="font-weight:600"><?= htmlspecialchars($w['prenom'].' '.$w['nom']) ?></span>
              <span style="margin-left:auto;color:#d97706;font-weight:700"><?= number_format($w['score'],2) ?></span>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div>
        <h3 style="margin-bottom:12px">Code XQuery — Q4</h3>
        <div class="xq-panel"><span class="xq-cmt">(: Q4 — Vainqueur de chaque concours  :)
(: Gère les ex-aequo : where $s=$max  :)</span>
<span class="xq-tag">&lt;palmares&gt;</span>
{
  <span class="xq-kw">for</span> $co <span class="xq-kw">in</span>
    <span class="xq-fn">doc</span>(<span class="xq-str">"club.xml"</span>)//concours/concours
  <span class="xq-kw">let</span> $coef :=
    <span class="xq-fn">xs:decimal</span>(<span class="xq-fn">data</span>($co/<span class="xq-attr">@coefficient</span>))
  <span class="xq-kw">let</span> $tousScores :=
    <span class="xq-kw">for</span> $p <span class="xq-kw">in</span>
      $co/participants/participant
    <span class="xq-kw">return</span>
      (<span class="xq-fn">xs:integer</span>(<span class="xq-fn">data</span>($p/complexite))
       <span class="xq-op">+</span> <span class="xq-fn">xs:integer</span>(<span class="xq-fn">data</span>($p/tempsExecution)))
       <span class="xq-op">*</span> $coef
  <span class="xq-kw">let</span> $scoreMax := <span class="xq-fn">max</span>($tousScores)
  <span class="xq-kw">return</span>
    <span class="xq-tag">&lt;concours</span>
      <span class="xq-attr">titre</span>=<span class="xq-str">"{data($co/titre)}"</span>
      <span class="xq-attr">date</span>=<span class="xq-str">"{data($co/@date)}"</span><span class="xq-tag">&gt;</span>
    {
      <span class="xq-kw">for</span> $p <span class="xq-kw">in</span>
        $co/participants/participant
      <span class="xq-kw">let</span> $s := (...) <span class="xq-op">*</span> $coef
      <span class="xq-kw">let</span> $m := doc(...)//membre[
        <span class="xq-attr">@id</span> = $p/<span class="xq-attr">@membreRef</span>]
      <span class="xq-kw">where</span> $s <span class="xq-op">=</span> $scoreMax
      <span class="xq-kw">return</span>
        <span class="xq-tag">&lt;premier&gt;</span>
          <span class="xq-tag">&lt;prenom&gt;</span>{ <span class="xq-fn">data</span>($m/prenom) }<span class="xq-tag">&lt;/prenom&gt;</span>
          <span class="xq-tag">&lt;nom&gt;</span>{ <span class="xq-fn">data</span>($m/nom) }<span class="xq-tag">&lt;/nom&gt;</span>
          <span class="xq-tag">&lt;scoreObtenu&gt;</span>
            { <span class="xq-fn">format-number</span>($s,<span class="xq-str">"#0.00"</span>) }
          <span class="xq-tag">&lt;/scoreObtenu&gt;</span>
        <span class="xq-tag">&lt;/premier&gt;</span>
    }
    <span class="xq-tag">&lt;/concours&gt;</span>
}
<span class="xq-tag">&lt;/palmares&gt;</span></div>
      </div>
    </div>

  <?php elseif ($activeReq === 'q5'):
    $rows = runQ5($membres, $categories, $q5Domaine); ?>
    <div class="grid-2" style="gap:24px;align-items:start">
      <div>
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;flex-wrap:wrap">
          <span class="pill" style="background:#eff6ff;color:#1e40af;border-color:#93c5fd">Q5</span>
          <h2>Membres par domaine</h2>
          <form method="GET" style="margin-left:auto;display:flex;align-items:center;gap:8px">
            <input type="hidden" name="req" value="q5">
            <input type="hidden" name="section" value="req">
            <select name="domaine" class="drop-select" style="width:auto;padding:7px 12px;font-size:.85rem" onchange="this.form.submit()">
              <?php foreach($categories as $id=>$lib): ?>
                <option value="<?= htmlspecialchars($lib) ?>" <?= $q5Domaine===$lib?'selected':'' ?>><?= htmlspecialchars($lib) ?></option>
              <?php endforeach; ?>
            </select>
          </form>
        </div>
        <?php if(empty($rows)): ?>
          <div class="alert alert-warn">Aucun membre dans ce domaine.</div>
        <?php else: ?>
          <div class="member-list">
            <?php foreach($rows as $i=>$m): $col=catColor($m['categorieRef']); ?>
            <div class="member-row">
              <div style="font-weight:700;color:var(--muted);min-width:22px;font-size:.84rem"><?= $i+1 ?></div>
              <div class="avatar" style="background:<?=$col['bg']?>;color:<?=$col['text']?>"><?= $m['initiales'] ?></div>
              <div class="member-info" style="flex:1">
                <div class="member-name"><?= htmlspecialchars($m['prenom'].' '.$m['nom']) ?></div>
                <div class="member-email"><?= htmlspecialchars($m['email']) ?></div>
              </div>
              <span style="font-size:.74rem;color:var(--muted)"><?= $m['id'] ?></span>
            </div>
            <?php endforeach; ?>
          </div>
          <div class="alert alert-info mt-4"><?= count($rows) ?> membre(s) — tri alphabétique par nom</div>
        <?php endif; ?>
      </div>
      <div>
        <h3 style="margin-bottom:12px">Code XQuery — Q5</h3>
        <div class="xq-panel"><span class="xq-cmt">(: Q5 — Membres par domaine, triés     :)
(: Changer $domaine pour filtrer       :)</span>
<span class="xq-kw">let</span> $domaine :=
  <span class="xq-str">"<?= htmlspecialchars($q5Domaine) ?>"</span>
<span class="xq-kw">let</span> $refCat :=
  <span class="xq-fn">doc</span>(<span class="xq-str">"club.xml"</span>)
    //categorie[<span class="xq-attr">@libelle</span> = $domaine]
    /<span class="xq-attr">@id</span>
<span class="xq-kw">return</span>
<span class="xq-tag">&lt;membresParDomaine</span>
  <span class="xq-attr">domaine</span>=<span class="xq-str">"{$domaine}"</span><span class="xq-tag">&gt;</span>
{
  <span class="xq-kw">for</span> $m <span class="xq-kw">in</span>
    <span class="xq-fn">doc</span>(<span class="xq-str">"club.xml"</span>)
      //membre[<span class="xq-attr">@categorieRef</span> = $refCat]
  <span class="xq-kw">order by</span>
    <span class="xq-fn">data</span>($m/nom) <span class="xq-kw">ascending</span>,
    <span class="xq-fn">data</span>($m/prenom) <span class="xq-kw">ascending</span>
  <span class="xq-kw">return</span>
    <span class="xq-tag">&lt;membre</span> <span class="xq-attr">id</span>=<span class="xq-str">"{data($m/@id)}"</span><span class="xq-tag">&gt;</span>
      <span class="xq-tag">&lt;nom&gt;</span>{ <span class="xq-fn">data</span>($m/nom) }<span class="xq-tag">&lt;/nom&gt;</span>
      <span class="xq-tag">&lt;prenom&gt;</span>{ <span class="xq-fn">data</span>($m/prenom) }<span class="xq-tag">&lt;/prenom&gt;</span>
      <span class="xq-tag">&lt;email&gt;</span>{ <span class="xq-fn">data</span>($m/email) }<span class="xq-tag">&lt;/email&gt;</span>
    <span class="xq-tag">&lt;/membre&gt;</span>
}
<span class="xq-tag">&lt;/membresParDomaine&gt;</span></div>
      </div>
    </div>
  <?php endif; ?>
  </div>
  <?php endif; ?>


  <!-- ═══════════════════ MISES À JOUR ═══════════════════ -->
  <?php if ($section === 'upd' && $activeUpd): ?>
  <div class="result-panel">

  <?php if ($activeUpd === 'u1'): ?>
    <div class="grid-2" style="gap:24px;align-items:start">
      <div class="upd-form">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:18px">
          <span class="pill" style="background:#ede9fe;color:#5b21b6;border-color:#c4b5fd">U1</span>
          <h2>Insérer un nouveau membre</h2>
        </div>
        <p style="margin-bottom:18px;color:var(--muted);font-size:.9rem">Remplissez tous les champs. Le membre sera ajouté dans une catégorie existante de club.xml.</p>
        <form method="POST" class="form-grid">
          <input type="hidden" name="action" value="u1">
          <div class="field-row">
            <div class="form-group">
              <label class="form-label" for="new_id">Identifiant *</label>
              <input class="form-control" type="text" name="new_id" id="new_id" placeholder="M011" pattern="M\d+" value="<?= htmlspecialchars($_POST['new_id']??'') ?>" required>
              <span class="form-hint">Format : M suivi de chiffres (ex: M011)</span>
            </div>
            <div class="form-group">
              <label class="form-label" for="new_cat">Catégorie existante *</label>
              <select class="form-control" name="new_cat" id="new_cat" required>
                <option value="">— Choisir —</option>
                <?php foreach($categories as $id=>$lib): ?>
                  <option value="<?= $id ?>" <?= (($_POST['new_cat']??'')===$id)?'selected':'' ?>><?= $id ?> — <?= htmlspecialchars($lib) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="field-row">
            <div class="form-group">
              <label class="form-label" for="new_nom">Nom *</label>
              <input class="form-control" type="text" name="new_nom" id="new_nom" placeholder="Boukerma" value="<?= htmlspecialchars($_POST['new_nom']??'') ?>" required>
            </div>
            <div class="form-group">
              <label class="form-label" for="new_prenom">Prénom *</label>
              <input class="form-control" type="text" name="new_prenom" id="new_prenom" placeholder="Yasmine" value="<?= htmlspecialchars($_POST['new_prenom']??'') ?>" required>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label" for="new_email">Email *</label>
            <input class="form-control" type="email" name="new_email" id="new_email" placeholder="y.boukerma@infotech.dz" value="<?= htmlspecialchars($_POST['new_email']??'') ?>" required>
          </div>
          <button type="submit" class="btn btn-primary">Insérer le membre →</button>
        </form>
        <div class="sep"></div>
        <div style="font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);margin-bottom:8px">Membres actuels (<?= count($membres) ?>)</div>
        <div class="members-scroll">
          <?php foreach($membres as $m): $col=catColor($m['categorieRef']); ?>
          <div style="display:flex;align-items:center;gap:8px;padding:6px 10px;background:var(--bg);border-radius:6px;font-size:.81rem">
            <span style="font-weight:700;color:var(--muted);min-width:36px"><?= $m['id'] ?></span>
            <div class="avatar" style="width:26px;height:26px;font-size:.65rem;background:<?=$col['bg']?>;color:<?=$col['text']?>"><?= $m['initiales'] ?></div>
            <span style="flex:1;font-weight:500"><?= htmlspecialchars($m['prenom'].' '.$m['nom']) ?></span>
            <span class="badge" style="background:<?=$col['bg']?>;color:<?=$col['text']?>;border-color:<?=$col['border']?>;font-size:.62rem"><?= $m['categorieRef'] ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div>
        <h3 style="margin-bottom:12px">Code XQuery — U1</h3>
        <div class="xq-panel"><span class="xq-cmt">(: U1 — Insertion d'un nouveau membre  :)
(: Opération : insert node ... into    :)
(: Cible : doc("club.xml")//membres   :)</span>

<span class="xq-kw">insert node</span>
  <span class="xq-tag">&lt;membre</span> <span class="xq-attr">id</span>=<span class="xq-str">"M011"</span>
          <span class="xq-attr">categorieRef</span>=<span class="xq-str">"C2"</span><span class="xq-tag">&gt;</span>
    <span class="xq-tag">&lt;nom&gt;</span>Boukerma<span class="xq-tag">&lt;/nom&gt;</span>
    <span class="xq-tag">&lt;prenom&gt;</span>Yasmine<span class="xq-tag">&lt;/prenom&gt;</span>
    <span class="xq-tag">&lt;email&gt;</span>
      y.boukerma<span class="xq-op">@</span>infotech.dz
    <span class="xq-tag">&lt;/email&gt;</span>
  <span class="xq-tag">&lt;/membre&gt;</span>
<span class="xq-kw">into</span> <span class="xq-fn">doc</span>(<span class="xq-str">"club.xml"</span>)//membres</div>
        <div class="alert alert-info mt-4">IDs existants : <strong><?= implode(', ', array_keys($membres)) ?></strong></div>
      </div>
    </div>

  <?php elseif ($activeUpd === 'u2'): ?>
    <div class="grid-2" style="gap:24px;align-items:start">
      <div class="upd-form">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:18px">
          <span class="pill" style="background:#fef3c7;color:#92400e;border-color:#fcd34d">U2</span>
          <h2>Modifier un concours</h2>
        </div>
        <p style="margin-bottom:18px;color:var(--muted);font-size:.9rem">Choisissez le concours, le champ à modifier et la nouvelle valeur.</p>
        <form method="POST" class="form-grid">
          <input type="hidden" name="action" value="u2">
          <div class="form-group">
            <label class="form-label">Concours à modifier *</label>
            <select class="form-control" name="mod_concours" id="mod_concours" required onchange="updateU2Preview()">
              <option value="">— Choisir un concours —</option>
              <?php foreach($concours as $co): ?>
                <option value="<?= $co['id'] ?>" data-coef="<?= $co['coefficient'] ?>" data-date="<?= $co['date'] ?>" data-titre="<?= htmlspecialchars($co['titre']) ?>" <?= (($_POST['mod_concours']??'')===$co['id'])?'selected':'' ?>>
                  <?= $co['id'] ?> — <?= htmlspecialchars($co['titre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Champ à modifier *</label>
            <select class="form-control" name="mod_field" id="mod_field" required onchange="updateU2Preview()">
              <option value="">— Choisir un champ —</option>
              <option value="coefficient" <?= (($_POST['mod_field']??'')==='coefficient')?'selected':'' ?>>Coefficient</option>
              <option value="date"        <?= (($_POST['mod_field']??'')==='date')?'selected':'' ?>>Date</option>
              <option value="titre"       <?= (($_POST['mod_field']??'')==='titre')?'selected':'' ?>>Titre</option>
            </select>
          </div>
          <div class="form-group" id="cur-val-box" style="display:none">
            <label class="form-label">Valeur actuelle</label>
            <div id="cur-val" style="padding:8px 12px;background:var(--bg);border:1px solid var(--border);border-radius:7px;font-family:'Fira Code',monospace;font-size:.85rem;color:var(--muted)">—</div>
          </div>
          <div class="form-group">
            <label class="form-label" for="mod_value">Nouvelle valeur *</label>
            <input class="form-control" type="text" name="mod_value" id="mod_value" placeholder="ex: 2.5" value="<?= htmlspecialchars($_POST['mod_value']??'') ?>" required>
            <span class="form-hint" id="mod-hint">Sélectionnez un champ pour voir le format</span>
          </div>
          <button type="submit" class="btn btn-primary">Appliquer la modification →</button>
        </form>
      </div>
      <div>
        <h3 style="margin-bottom:12px">Code XQuery — U2</h3>
        <div class="xq-panel"><span class="xq-cmt">(: U2 — Modification d'une valeur      :)
(: Opération : replace value of node  :)</span>

<span class="xq-kw">replace value of node</span>
  <span class="xq-fn">doc</span>(<span class="xq-str">"club.xml"</span>)
    //concours[<span class="xq-attr">@id</span> = <span class="xq-str">"CO3"</span>]
      /<span class="xq-attr">@coefficient</span>
<span class="xq-kw">with</span> <span class="xq-str">"2.5"</span></div>
        <div class="sep"></div>
        <div style="font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);margin-bottom:10px">Concours actuels</div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>ID</th><th>Titre</th><th>Date</th><th>Coeff.</th></tr></thead>
            <tbody>
              <?php foreach($concours as $co): ?>
              <tr>
                <td style="font-weight:700;font-size:.78rem;color:var(--muted)"><?= $co['id'] ?></td>
                <td style="font-size:.84rem"><?= htmlspecialchars($co['titre']) ?></td>
                <td style="font-size:.84rem"><?= $co['date'] ?></td>
                <td style="font-weight:700">×<?= $co['coefficient'] ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  <?php elseif ($activeUpd === 'u3'): ?>
    <div class="grid-2" style="gap:24px;align-items:start">
      <div class="upd-form">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:18px">
          <span class="pill" style="background:#fef2f2;color:#991b1b;border-color:#fca5a5">U3</span>
          <h2>Supprimer une participation</h2>
        </div>
        <p style="margin-bottom:18px;color:var(--muted);font-size:.9rem">Choisissez un concours puis le membre à retirer. Le membre reste dans club.xml — seule sa participation est supprimée.</p>
        <form method="POST" class="form-grid">
          <input type="hidden" name="action" value="u3">
          <div class="form-group">
            <label class="form-label">Concours *</label>
            <select class="form-control" name="del_concours" id="del_concours" required onchange="filterParticipants(this)">
              <option value="">— Choisir un concours —</option>
              <?php foreach($concours as $co): ?>
                <option value="<?= $co['id'] ?>" <?= (($_POST['del_concours']??'')===$co['id'])?'selected':'' ?>>
                  <?= $co['id'] ?> — <?= htmlspecialchars($co['titre']) ?> (<?= $co['nbPart'] ?> participants)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Participant à supprimer *</label>
            <select class="form-control" name="del_membre" id="del_membre" required>
              <option value="">— Choisir un concours d'abord —</option>
              <?php foreach($concours as $co): foreach($co['participants'] as $p): ?>
                <option value="<?= $p['membreRef'] ?>" data-co="<?= $co['id'] ?>" class="part-opt" <?= (($_POST['del_membre']??'')===$p['membreRef']&&($_POST['del_concours']??'')===$co['id'])?'selected':'' ?>>
                  <?= $p['membreRef'] ?> — <?= htmlspecialchars($p['prenom'].' '.$p['nom']) ?>
                </option>
              <?php endforeach; endforeach; ?>
            </select>
            <span class="form-hint" id="part-hint"></span>
          </div>
          <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:11px 14px;font-size:.83rem;color:#991b1b">
            ⚠️ Opération irréversible. Le participant sera retiré définitivement du concours sélectionné.
          </div>
          <button type="submit" class="btn btn-primary" style="background:#dc2626;border-color:#b91c1c">Supprimer la participation →</button>
        </form>
      </div>
      <div>
        <h3 style="margin-bottom:12px">Code XQuery — U3</h3>
        <div class="xq-panel"><span class="xq-cmt">(: U3 — Suppression d'un participant   :)
(: Opération : delete node             :)
(: Le membre reste dans //membres      :)</span>

<span class="xq-kw">delete node</span>
  <span class="xq-fn">doc</span>(<span class="xq-str">"club.xml"</span>)
    //concours[<span class="xq-attr">@id</span> = <span class="xq-str">"CO3"</span>]
      //participant[
        <span class="xq-attr">@membreRef</span> = <span class="xq-str">"M009"</span>
      ]</div>
        <div class="sep"></div>
        <div style="font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);margin-bottom:10px">Participations actuelles</div>
        <?php foreach($concours as $co): ?>
        <div style="margin-bottom:12px">
          <div style="font-size:.78rem;font-weight:700;color:var(--muted);margin-bottom:5px"><?= $co['id'] ?> — <?= htmlspecialchars($co['titre']) ?></div>
          <?php if(empty($co['participants'])): ?>
            <div style="font-size:.78rem;color:var(--muted);padding:5px 10px;font-style:italic">Aucun participant</div>
          <?php else: ?>
            <?php foreach($co['participants'] as $p): ?>
            <div style="display:flex;align-items:center;gap:8px;padding:5px 10px;background:var(--bg);border-radius:6px;margin-bottom:3px;font-size:.8rem">
              <span style="font-weight:700;color:var(--muted);min-width:36px"><?= $p['membreRef'] ?></span>
              <span><?= htmlspecialchars($p['prenom'].' '.$p['nom']) ?></span>
              <span style="margin-left:auto;color:var(--accent);font-weight:600"><?= number_format($p['score'],2) ?></span>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- Empty state -->
  <?php if (!$section || (!$activeReq && !$activeUpd)): ?>
  <div class="empty-state">
    <div class="icon">⚡</div>
    <h3>Sélectionnez une requête ou une opération</h3>
    <p>Utilisez les deux listes déroulantes ci-dessus.</p>
  </div>
  <?php endif; ?>

</div>

<script>
const concoursData = <?= json_encode(array_values($concours)) ?>;

function updateU2Preview() {
  const co    = concoursData.find(c=>c.id===document.getElementById('mod_concours').value);
  const field = document.getElementById('mod_field').value;
  const box   = document.getElementById('cur-val-box');
  const cur   = document.getElementById('cur-val');
  const hint  = document.getElementById('mod-hint');
  const inp   = document.getElementById('mod_value');
  const hints = {coefficient:'Nombre décimal > 0 (ex: 2.5)',date:'Format AAAA-MM-JJ (ex: 2025-06-01)',titre:'Texte libre'};
  if (co && field) {
    box.style.display='block';
    cur.textContent = field==='coefficient'?co.coefficient:field==='date'?co.date:co.titre;
    hint.textContent = hints[field]||'';
    inp.placeholder  = hints[field]||'Nouvelle valeur';
  } else { box.style.display='none'; hint.textContent='Sélectionnez un concours et un champ'; }
}

function filterParticipants(sel) {
  const coId = sel.value;
  const mSel = document.getElementById('del_membre');
  const hint = document.getElementById('part-hint');
  mSel.innerHTML = '<option value="">— Sélectionner un participant —</option>';
  let n=0;
  document.querySelectorAll('.part-opt').forEach(o => {
    if (o.dataset.co===coId) { mSel.appendChild(o.cloneNode(true)); n++; }
  });
  hint.textContent = n+' participant(s) dans ce concours';
}

window.addEventListener('DOMContentLoaded',()=>{
  const c=document.getElementById('mod_concours');
  if(c&&c.value) updateU2Preview();
  const d=document.getElementById('del_concours');
  if(d&&d.value) filterParticipants(d);
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
