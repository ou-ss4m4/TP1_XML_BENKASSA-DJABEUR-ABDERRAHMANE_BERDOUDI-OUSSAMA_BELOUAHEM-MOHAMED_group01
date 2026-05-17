

(: ---------------------------------------------------------------- :)
(: Q1 — Liste complète des membres                                 :)
(:      Pour chaque membre : id, nom complet, email, spécialité    :)
(:      La spécialité est récupérée via le lien @categorieRef      :)
(: ---------------------------------------------------------------- :)
<listeMembers>
{
  for $membre in doc("club.xml")//membre
  let $libelleCategorie :=
        doc("club.xml")//categorie[@id = $membre/@categorieRef]/@libelle
  return
    <fiche id="{data($membre/@id)}">
      <nomComplet>{ concat(data($membre/prenom), " ", data($membre/nom)) }</nomComplet>
      <email>{ data($membre/email) }</email>
      <specialite>{ data($libelleCategorie) }</specialite>
    </fiche>
}
</listeMembers>

(: ---------------------------------------------------------------- :)
(: Q2 — Liste des concours triés par date croissante               :)
(:      Affiche : id, titre, date, coefficient, domaine            :)
(:      Tri : order by xs:date() pour un tri chronologique correct :)
(: ---------------------------------------------------------------- :)
<listeConcours>
{
  for $co in doc("club.xml")//concours/concours
  let $libelleCategorie :=
        doc("club.xml")//categorie[@id = $co/@categorieRef]/@libelle
  order by xs:date($co/@date) ascending
  return
    <evenement id="{data($co/@id)}">
      <titre>{ data($co/titre) }</titre>
      <date>{ data($co/@date) }</date>
      <coefficient>{ data($co/@coefficient) }</coefficient>
      <domaine>{ data($libelleCategorie) }</domaine>
    </evenement>
}
</listeConcours>

(: ---------------------------------------------------------------- :)
(: Q3 — Calcul du score de chaque participant                      :)
(:      Formule : score = (complexite + tempsExecution) x coeff.   :)
(:      Affiche le détail par concours et par participant           :)
(: ---------------------------------------------------------------- :)
<scoresParticipants>
{
  for $co in doc("club.xml")//concours/concours
  let $coef := xs:decimal(data($co/@coefficient))
  for $p in $co/participants/participant
  let $compl := xs:integer(data($p/complexite))
  let $tps   := xs:integer(data($p/tempsExecution))
  let $score := ($compl + $tps) * $coef
  let $mem   := doc("club.xml")//membre[@id = $p/@membreRef]
  return
    <resultat>
      <concours>{ data($co/titre) }</concours>
      <participant>{ concat(data($mem/prenom), " ", data($mem/nom)) }</participant>
      <complexite>{ $compl }</complexite>
      <tempsExecution>{ $tps }</tempsExecution>
      <score>{ format-number($score, "#0.00") }</score>
    </resultat>
}
</scoresParticipants>

(: ---------------------------------------------------------------- :)
(: Q4 — Palmarès : vainqueur de chaque concours                   :)
(:      Calcule le score max par concours                          :)
(:      Gère les ex-aequo (where $s = $scoreMax)                  :)
(: ---------------------------------------------------------------- :)
<palmares>
{
  for $co in doc("club.xml")//concours/concours
  let $coef := xs:decimal(data($co/@coefficient))
  let $tousScores :=
    for $p in $co/participants/participant
    return (xs:integer(data($p/complexite)) + xs:integer(data($p/tempsExecution))) * $coef
  let $scoreMax := max($tousScores)
  return
    <concours titre="{data($co/titre)}" date="{data($co/@date)}">
    {
      for $p in $co/participants/participant
      let $s := (xs:integer(data($p/complexite)) + xs:integer(data($p/tempsExecution))) * $coef
      let $m := doc("club.xml")//membre[@id = $p/@membreRef]
      where $s = $scoreMax
      return
        <premier>
          <prenom>{ data($m/prenom) }</prenom>
          <nom>{ data($m/nom) }</nom>
          <scoreObtenu>{ format-number($s, "#0.00") }</scoreObtenu>
        </premier>
    }
    </concours>
}
</palmares>

(: ---------------------------------------------------------------- :)
(: Q5 — Membres d'un domaine donné, triés alphabétiquement        :)
(:      Filtre par libellé de catégorie (ex: "Cybersécurité")      :)
(:      Tri double : nom ASC puis prénom ASC                       :)
(:      Modifier $domaine pour filtrer une autre catégorie         :)
(: ---------------------------------------------------------------- :)
let $domaine := "Cybersécurité"
let $refCat  := doc("club.xml")//categorie[@libelle = $domaine]/@id
return
<membresParDomaine domaine="{$domaine}">
{
  for $m in doc("club.xml")//membre[@categorieRef = $refCat]
  order by data($m/nom) ascending, data($m/prenom) ascending
  return
    <membre id="{data($m/@id)}">
      <nom>{ data($m/nom) }</nom>
      <prenom>{ data($m/prenom) }</prenom>
      <email>{ data($m/email) }</email>
    </membre>
}
</membresParDomaine>
