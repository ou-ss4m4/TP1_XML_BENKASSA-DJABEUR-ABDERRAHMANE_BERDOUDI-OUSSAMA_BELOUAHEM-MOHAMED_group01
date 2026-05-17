<?php
/**
 * data.php — Accès à club.xml via SimpleXML
 *
 * Structure du projet :
 *   projet/
 *   ├── club.xml        ← fichier XML (un niveau au-dessus de web/)
 *   ├── club.xsd
 *   ├── requetes.xq
 *   ├── updates.xq
 *   └── web/
 *       ├── index.php
 *       └── includes/
 *           └── data.php  ← CE FICHIER
 *
 * Le chemin '../club.xml' remonte d'un niveau depuis web/
 * pour atteindre club.xml à la racine du projet.
 */

define('XML_FILE', __DIR__ . '/../../club.xml');

function loadXML(): SimpleXMLElement {
    static $xml = null;
    if ($xml === null) {
        if (!file_exists(XML_FILE)) {
            die('<div style="font-family:monospace;color:red;padding:20px">
                Erreur : club.xml introuvable à : ' . XML_FILE . '<br>
                Vérifiez que la structure est :<br>
                <pre>projet/
├── club.xml
└── web/
    └── index.php</pre></div>');
        }
        $xml = simplexml_load_file(XML_FILE);
        if (!$xml) die('Erreur: impossible de charger club.xml');
    }
    return $xml;
}

function getCategories(): array {
    $xml = loadXML();
    $cats = [];
    foreach ($xml->categories->categorie as $c) {
        $cats[(string)$c['id']] = (string)$c['libelle'];
    }
    return $cats;
}

function getMembres(): array {
    $xml  = loadXML();
    $cats = getCategories();
    $membres = [];
    foreach ($xml->membres->membre as $m) {
        $id     = (string)$m['id'];
        $catRef = (string)$m['categorieRef'];
        $membres[$id] = [
            'id'           => $id,
            'categorieRef' => $catRef,
            'categorie'    => $cats[$catRef] ?? '',
            'nom'          => (string)$m->nom,
            'prenom'       => (string)$m->prenom,
            'email'        => (string)$m->email,
            'initiales'    => strtoupper(
                                substr((string)$m->prenom, 0, 1) .
                                substr((string)$m->nom,    0, 1)
                              ),
        ];
    }
    return $membres;
}

function getConcours(): array {
    $xml  = loadXML();
    $cats = getCategories();
    $mems = getMembres();
    $list = [];

    foreach ($xml->concours->concours as $co) {
        $id     = (string)$co['id'];
        $coef   = (float)$co['coefficient'];
        $catRef = (string)$co['categorieRef'];

        $participants = [];
        foreach ($co->participants->participant as $p) {
            $ref   = (string)$p['membreRef'];
            $compl = (int)$p->complexite;
            $tps   = (int)$p->tempsExecution;
            $score = ($compl + $tps) * $coef;
            $participants[] = [
                'membreRef'      => $ref,
                'nom'            => $mems[$ref]['nom']      ?? '',
                'prenom'         => $mems[$ref]['prenom']   ?? '',
                'initiales'      => $mems[$ref]['initiales']?? '??',
                'complexite'     => $compl,
                'tempsExecution' => $tps,
                'score'          => $score,
            ];
        }

        // Tri décroissant par score (Q3/Q4)
        usort($participants, fn($a, $b) => $b['score'] <=> $a['score']);
        $maxScore = !empty($participants) ? $participants[0]['score'] : 0;
        $winners  = array_values(
                      array_filter($participants, fn($p) => $p['score'] == $maxScore)
                    );

        $list[$id] = [
            'id'           => $id,
            'titre'        => (string)$co->titre,
            'date'         => (string)$co['date'],
            'coefficient'  => $coef,
            'categorieRef' => $catRef,
            'categorie'    => $cats[$catRef] ?? '',
            'participants' => $participants,
            'maxScore'     => $maxScore,
            'winners'      => $winners,
            'nbPart'       => count($participants),
        ];
    }

    return $list;
}

/**
 * Retourne les couleurs CSS pour une catégorie donnée.
 * Utilisé pour les badges, avatars, etc.
 */
function catColor(string $catRef): array {
    $map = [
        'C1' => ['bg'=>'#fef3c7','text'=>'#92400e','border'=>'#fcd34d','dot'=>'#f59e0b'],
        'C2' => ['bg'=>'#ede9fe','text'=>'#5b21b6','border'=>'#c4b5fd','dot'=>'#7c3aed'],
        'C3' => ['bg'=>'#dcfce7','text'=>'#166534','border'=>'#86efac','dot'=>'#16a34a'],
    ];
    return $map[$catRef] ?? ['bg'=>'#f1f5f9','text'=>'#475569','border'=>'#cbd5e1','dot'=>'#94a3b8'];
}

/**
 * Sauvegarde le SimpleXMLElement modifié dans club.xml
 * Utilisé par inscription.php et requetes.php (updates)
 */
function saveXML(SimpleXMLElement $xml): bool {
    return (bool) file_put_contents(XML_FILE, $xml->asXML());
}
