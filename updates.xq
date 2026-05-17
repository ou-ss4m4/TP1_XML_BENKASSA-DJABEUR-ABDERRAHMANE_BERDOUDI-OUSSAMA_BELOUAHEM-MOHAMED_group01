(: ================================================================ :)
(: FICHIER  : updates.xq                                         :)
(: PROJET   : Club InfoTech — Mini-projet XML/XQuery             :)
(: PARTIE   : 3.4 — Les 3 mises à jour XQuery Update             :)
(: USAGE    : Ouvrir dans BaseX, sélectionner UN bloc, F5        :)
(:            Exécuter dans l'ordre : U1 → U2 → U3              :)
(:            Chaque bloc modifie club.xml de façon persistante  :)
(: ================================================================ :)

(: ---------------------------------------------------------------- :)
(: U1 — Insertion d'un nouveau membre                              :)
(:                                                                 :)
(:  Ajoute Yasmine Boukerma (id=M011) dans la catégorie C2        :)
(:  (Intelligence Artificielle)                                    :)
(:                                                                 :)
(:  Opération XQuery : insert node ... into                        :)
(:  Cible         : //membres                                      :)
(:  Vérification  : doc("club.xml")//membre[@id="M011"]           :)
(: ---------------------------------------------------------------- :)

insert node
  <membre id="M011" categorieRef="C2">
    <nom>Boukerma</nom>
    <prenom>Yasmine</prenom>
    <email>y.boukerma@infotech.dz</email>
  </membre>
into doc("club.xml")//membres

(: ---------------------------------------------------------------- :)
(: U2 — Modification de la valeur d'un attribut                    :)
(:                                                                 :)
(:  Met à jour le coefficient du concours CO3 : 1.5 → 2.5         :)
(:  Cela affecte le calcul des scores de tous ses participants     :)
(:                                                                 :)
(:  Opération XQuery : replace value of node                       :)
(:  Cible         : //concours[@id="CO3"]/@coefficient             :)
(:  Vérification  : doc("club.xml")//concours[@id="CO3"]/@coefficient :)
(: ---------------------------------------------------------------- :)

replace value of node
  doc("club.xml")//concours[@id = "CO3"]/@coefficient
with "2.5"

(: ---------------------------------------------------------------- :)
(: U3 — Suppression d'un nœud                                     :)
(:                                                                 :)
(:  Retire la participation de M009 (Ziane Dounia) au concours CO3:)
(:  Le membre reste dans la liste des membres, seule sa           :)
(:  participation à CO3 est supprimée                             :)
(:                                                                 :)
(:  Opération XQuery : delete node                                 :)
(:  Cible         : //concours[@id="CO3"]//participant[@membreRef="M009"] :)
(:  Vérification  : doc("club.xml")//concours[@id="CO3"]//participant :)
(: ---------------------------------------------------------------- :)

delete node
  doc("club.xml")//concours[@id = "CO3"]
    //participant[@membreRef = "M009"]
