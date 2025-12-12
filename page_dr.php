<?php
session_start();
require("bdburida.php");
require("conditionconnexion.php");

// Récupérer le bureau de la session
$bureau_session = $_SESSION['bureau'];

// Traitement des filtres de date
$date_debut = isset($_POST['date_debut']) ? $_POST['date_debut'] : date('Y-m-01');
$date_fin = isset($_POST['date_fin']) ? $_POST['date_fin'] : date('Y-m-d');

// Tableau de correspondance des mois
$mois_fr = array(
    'janvier' => 1, 'février' => 2, 'fevrier' => 2, 'mars' => 3, 'avril' => 4,
    'mai' => 5, 'juin' => 6, 'juillet' => 7, 'août' => 8, 'aout' => 8,
    'septembre' => 9, 'octobre' => 10, 'novembre' => 11, 'décembre' => 12, 'decembre' => 12
);

// Fonction pour extraire le dernier mois d'une période multiple
function extraireDernierMois($periode) {
    if (strpos($periode, '-') !== false) {
        $periodes = explode('-', $periode);
        $derniere_periode = trim(end($periodes));
        return $derniere_periode;
    }
    return trim($periode);
}

// Fonction pour parser le mois et l'année
function parserPeriode($periode, $mois_fr) {
    $periode_clean = trim($periode);
    
    // Format standard
    $parts = preg_split('/\s+/', $periode_clean);
    
    if (count($parts) >= 2) {
        $mois = strtolower(trim($parts[0]));
        $annee = intval(trim($parts[1]));
        
        if (isset($mois_fr[$mois]) && $annee > 0) {
            return [
                'mois' => $mois,
                'mois_num' => $mois_fr[$mois],
                'annee' => $annee
            ];
        }
    }
    return null;
}

// Fonction pour calculer le montant dû avec filtre de période
function calculerMontantDuAvecPeriode($derniere_periode_data, $mensualite_value, $mois_fr, $debut_ets = null, $date_debut_filtre, $date_fin_filtre) {
    $total_due_value = 0;
    
    // Convertir les dates de filtre en mois/année
    $debut_filtre = DateTime::createFromFormat('Y-m-d', $date_debut_filtre);
    $fin_filtre = DateTime::createFromFormat('Y-m-d', $date_fin_filtre);
    
    if (!$debut_filtre || !$fin_filtre) {
        return 0;
    }
    
    $mois_debut_filtre = (int)$debut_filtre->format('n');
    $annee_debut_filtre = (int)$debut_filtre->format('Y');
    $mois_fin_filtre = (int)$fin_filtre->format('n');
    $annee_fin_filtre = (int)$fin_filtre->format('Y');
    
    if (!$derniere_periode_data && $debut_ets) {
        // Cas où il n'y a jamais eu de paiement mais il y a une date de début
        $debut_parts = explode('-', $debut_ets);
        if (count($debut_parts) == 2) {
            $annee_debut = intval($debut_parts[0]);
            $mois_debut = intval($debut_parts[1]);
            
            // Calculer les mois impayés dans la période du filtre
            $mois_courant = $mois_debut;
            $annee_courante = $annee_debut;
            
            while (($annee_courante < $annee_fin_filtre) || 
                   ($annee_courante == $annee_fin_filtre && $mois_courant <= $mois_fin_filtre)) {
                
                // Vérifier si le mois est dans la période du filtre
                if (($annee_courante > $annee_debut_filtre) || 
                    ($annee_courante == $annee_debut_filtre && $mois_courant >= $mois_debut_filtre)) {
                    $total_due_value += $mensualite_value;
                }
                
                $mois_courant++;
                if ($mois_courant > 12) {
                    $mois_courant = 1;
                    $annee_courante++;
                }
                
                // Éviter la boucle infinie
                if ($annee_courante > $annee_fin_filtre + 1) break;
            }
        }
    } else if ($derniere_periode_data && !empty($derniere_periode_data['periode'])) {
        // Cas où il y a des paiements
        $derniere_periode = trim($derniere_periode_data['periode']);
        $derniere_periode_simple = extraireDernierMois($derniere_periode);
        $parsed = parserPeriode($derniere_periode_simple, $mois_fr);
        
        if ($parsed !== null) {
            // Commencer au mois suivant la dernière période payée
            $mois_reference = $parsed['mois_num'] + 1;
            $annee_reference = $parsed['annee'];
            
            if ($mois_reference > 12) {
                $mois_reference = 1;
                $annee_reference++;
            }
            
            // Calculer les mois impayés dans la période du filtre
            $mois_courant = $mois_reference;
            $annee_courante = $annee_reference;
            
            while (($annee_courante < $annee_fin_filtre) || 
                   ($annee_courante == $annee_fin_filtre && $mois_courant <= $mois_fin_filtre)) {
                
                // Vérifier si le mois est dans la période du filtre
                if (($annee_courante > $annee_debut_filtre) || 
                    ($annee_courante == $annee_debut_filtre && $mois_courant >= $mois_debut_filtre)) {
                    $total_due_value += $mensualite_value;
                }
                
                $mois_courant++;
                if ($mois_courant > 12) {
                    $mois_courant = 1;
                    $annee_courante++;
                }
                
                // Éviter la boucle infinie
                if ($annee_courante > $annee_fin_filtre + 1) break;
            }
        }
    } else {
        // Cas par défaut (aucun paiement, aucune date de début)
        // On considère que c'est impayé pour la période du filtre
        $mois_courant = $mois_debut_filtre;
        $annee_courante = $annee_debut_filtre;
        
        while (($annee_courante < $annee_fin_filtre) || 
               ($annee_courante == $annee_fin_filtre && $mois_courant <= $mois_fin_filtre)) {
            
            $total_due_value += $mensualite_value;
            
            $mois_courant++;
            if ($mois_courant > 12) {
                $mois_courant = 1;
                $annee_courante++;
            }
            
            // Éviter la boucle infinie
            if ($annee_courante > $annee_fin_filtre + 1) break;
        }
    }
    
    return $total_due_value;
}

// Fonction pour calculer le total perçu d'un bureau (TOUS les paiements)
function calculerTotalPercuBureau($bdd, $bureau, $date_debut = null, $date_fin = null) {
    $total_percu = 0;
    
    try {
        // Récupérer tous les établissements du bureau
        $req_ets = $bdd->prepare('SELECT id_ets FROM ETABLISSEMENT WHERE bureau = ?');
        $req_ets->execute([$bureau]);
        $etablissements = $req_ets->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($etablissements)) {
            return 0;
        }
        
        // Construire la requête pour sommer tous les paiements des établissements du bureau
        if ($date_debut && $date_fin) {
            // Avec filtre de période
            $placeholders = str_repeat('?,', count($etablissements) - 1) . '?';
            $sql = "SELECT SUM(montant) as total FROM PAYEMENT WHERE id_ets IN ($placeholders) AND dates BETWEEN ? AND ?";
            
            $params = array_merge($etablissements, [$date_debut, $date_fin]);
            $req_paiements = $bdd->prepare($sql);
            $req_paiements->execute($params);
        } else {
            // Sans filtre (tous les paiements)
            $placeholders = str_repeat('?,', count($etablissements) - 1) . '?';
            $sql = "SELECT SUM(montant) as total FROM PAYEMENT WHERE id_ets IN ($placeholders)";
            
            $req_paiements = $bdd->prepare($sql);
            $req_paiements->execute($etablissements);
        }
        
        $result = $req_paiements->fetch(PDO::FETCH_ASSOC);
        $total_percu = $result['total'] ?? 0;
        
    } catch(Exception $e) {
        error_log("Erreur calcul total perçu bureau " . $bureau . ": " . $e->getMessage());
        $total_percu = 0;
    }
    
    return $total_percu;
}

// Fonction pour calculer les impayés d'un établissement avec filtre de période
function calculerImpayesEtablissementAvecPeriode($bdd, $id_ets, $mois_fr, $date_debut, $date_fin) {
    $total_due_value = 0;
    
    try {
        // Récupérer l'établissement
        $req_ets = $bdd->prepare('SELECT * FROM ETABLISSEMENT WHERE id_ets = ?');
        $req_ets->execute([$id_ets]);
        $ets = $req_ets->fetch(PDO::FETCH_ASSOC);
        
        if (!$ets) return 0;
        
        $debut_ets = $ets['debut'] ?? null;
        $mensualite_value = floatval(preg_replace('/[^0-9.]/', '', $ets['mensualite']));
        
        // Récupérer la dernière période payée
        try {
            $req_derniere_periode = $bdd->prepare('SELECT id_pay, periode, dates FROM PAYEMENT WHERE id_ets = ? ORDER BY id_pay DESC LIMIT 1');
            $req_derniere_periode->execute([$id_ets]);
            $derniere_periode_data = $req_derniere_periode->fetch(PDO::FETCH_ASSOC);
        } catch(Exception $e) {
            $derniere_periode_data = null;
        }
        
        $total_due_value = calculerMontantDuAvecPeriode($derniere_periode_data, $mensualite_value, $mois_fr, $debut_ets, $date_debut, $date_fin);
        
    } catch(Exception $e) {
        error_log("Erreur calcul impayés établissement " . $id_ets . ": " . $e->getMessage());
        $total_due_value = 0;
    }
    
    return $total_due_value;
}

// Récupérer la liste des bureaux
$bureaux = [];
try {
    $req_bureaux = $bdd->prepare('SELECT DISTINCT bureau FROM ETABLISSEMENT WHERE bureau IS NOT NULL AND bureau != "" ORDER BY bureau');
    $req_bureaux->execute();
    $bureaux = $req_bureaux->fetchAll(PDO::FETCH_COLUMN);
} catch(Exception $e) {
    error_log("Erreur récupération bureaux: " . $e->getMessage());
    $bureaux = [];
}

// Récupérer les objectifs des bureaux
$objectifs_bureaux = [];
try {
    $req_objectifs = $bdd->prepare('SELECT bureau, mensuel, annuel FROM OBJECTIF WHERE bureau IS NOT NULL');
    $req_objectifs->execute();
    $objectifs_data = $req_objectifs->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($objectifs_data as $objectif) {
        $objectifs_bureaux[$objectif['bureau']] = [
            'mensuel' => $objectif['mensuel'],
            'annuel' => $objectif['annuel']
        ];
    }
} catch(Exception $e) {
    error_log("Erreur récupération objectifs: " . $e->getMessage());
    $objectifs_bureaux = [];
}

// Calculer les statistiques pour chaque bureau avec filtre de période
$stats_bureaux = [];
$total_potentiel = 0;
$total_impayes_global = 0;
$total_percu_global = 0;
$total_objectif_mensuel_global = 0;
$total_objectif_annuel_global = 0;
$total_ecart_mensuel_global = 0;
$total_ecart_annuel_global = 0;

foreach ($bureaux as $bureau) {
    // Potentiel du bureau (somme des mensualités) - reste constant
    try {
        $req_potentiel = $bdd->prepare('SELECT SUM(CAST(REPLACE(REPLACE(mensualite, " FCFA", ""), " ", "") AS UNSIGNED)) as potentiel FROM ETABLISSEMENT WHERE bureau = ?');
        $req_potentiel->execute([$bureau]);
        $result = $req_potentiel->fetch(PDO::FETCH_ASSOC);
        $potentiel = $result['potentiel'] ?? 0;
    } catch(Exception $e) {
        error_log("Erreur calcul potentiel bureau " . $bureau . ": " . $e->getMessage());
        $potentiel = 0;
    }
    
    // Impayés du bureau avec filtre de période
    $impayes = 0;
    try {
        $req_ets_bureau = $bdd->prepare('SELECT id_ets FROM ETABLISSEMENT WHERE bureau = ?');
        $req_ets_bureau->execute([$bureau]);
        $etablissements = $req_ets_bureau->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($etablissements as $id_ets) {
            $impayes += calculerImpayesEtablissementAvecPeriode($bdd, $id_ets, $mois_fr, $date_debut, $date_fin);
        }
    } catch(Exception $e) {
        error_log("Erreur calcul impayés bureau " . $bureau . ": " . $e->getMessage());
        $impayes = 0;
    }
    
    // Total perçu du bureau (CUMUL de TOUS les paiements des établissements du bureau)
    $total_percu = calculerTotalPercuBureau($bdd, $bureau, $date_debut, $date_fin);
    
    // Objectifs du bureau
    $objectif_mensuel = $objectifs_bureaux[$bureau]['mensuel'] ?? 0;
    $objectif_annuel = $objectifs_bureaux[$bureau]['annuel'] ?? 0;
    
    // Calcul des écarts (Objectif - Total perçu)
    $ecart_mensuel = $objectif_mensuel - $total_percu;
    $ecart_annuel = $objectif_annuel - $total_percu;
    
    // Calcul des taux de réalisation
    $taux_mensuel = $objectif_mensuel > 0 ? ($total_percu / $objectif_mensuel) * 100 : 0;
    $taux_annuel = $objectif_annuel > 0 ? ($total_percu / $objectif_annuel) * 100 : 0;
    
    // Compter le nombre d'établissements
    try {
        $req_count = $bdd->prepare('SELECT COUNT(*) as count FROM ETABLISSEMENT WHERE bureau = ?');
        $req_count->execute([$bureau]);
        $result = $req_count->fetch(PDO::FETCH_ASSOC);
        $nombre_ets = $result['count'] ?? 0;
    } catch(Exception $e) {
        error_log("Erreur comptage établissements bureau " . $bureau . ": " . $e->getMessage());
        $nombre_ets = 0;
    }
    
    $stats_bureaux[$bureau] = [
        'potentiel' => $potentiel,
        'impayes' => $impayes,
        'total_percu' => $total_percu,
        'nombre_ets' => $nombre_ets,
        'objectif_mensuel' => $objectif_mensuel,
        'objectif_annuel' => $objectif_annuel,
        'ecart_mensuel' => $ecart_mensuel,
        'ecart_annuel' => $ecart_annuel,
        'taux_mensuel' => $taux_mensuel,
        'taux_annuel' => $taux_annuel
    ];
    
    $total_potentiel += $potentiel;
    $total_impayes_global += $impayes;
    $total_percu_global += $total_percu;
    $total_objectif_mensuel_global += $objectif_mensuel;
    $total_objectif_annuel_global += $objectif_annuel;
    $total_ecart_mensuel_global += $ecart_mensuel;
    $total_ecart_annuel_global += $ecart_annuel;
}

$nombre_bureaux = count($bureaux);

// Calcul des taux globaux
$taux_mensuel_global = $total_objectif_mensuel_global > 0 ? ($total_percu_global / $total_objectif_mensuel_global) * 100 : 0;
$taux_annuel_global = $total_objectif_annuel_global > 0 ? ($total_percu_global / $total_objectif_annuel_global) * 100 : 0;

// Récupérer les données pour la courbe évolutive
$courbe_data = [];
$periode_type = 'jours'; // Par défaut

// Calculer la différence en jours entre les dates
$date1 = new DateTime($date_debut);
$date2 = new DateTime($date_fin);
$interval = $date1->diff($date2);
$jours_difference = $interval->days;

// Déterminer le type de période
if ($jours_difference <= 31) {
    // Moins d'un mois : données par jour
    $periode_type = 'jours';
    
    // Récupérer les paiements par jour
    try {
        $req_courbe = $bdd->prepare("
            SELECT DATE(dates) as date_jour, SUM(montant) as total_jour 
            FROM PAYEMENT 
            WHERE dates BETWEEN ? AND ? 
            GROUP BY DATE(dates) 
            ORDER BY date_jour
        ");
        $req_courbe->execute([$date_debut, $date_fin]);
        $courbe_data = $req_courbe->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e) {
        error_log("Erreur récupération données courbe par jour: " . $e->getMessage());
    }
} else {
    // Plus d'un mois : données par mois
    $periode_type = 'mois';
    
    // Récupérer les paiements par mois
    try {
        $req_courbe = $bdd->prepare("
            SELECT 
                YEAR(dates) as annee, 
                MONTH(dates) as mois, 
                SUM(montant) as total_mois 
            FROM PAYEMENT 
            WHERE dates BETWEEN ? AND ? 
            GROUP BY YEAR(dates), MONTH(dates) 
            ORDER BY annee, mois
        ");
        $req_courbe->execute([$date_debut, $date_fin]);
        $courbe_data = $req_courbe->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e) {
        error_log("Erreur récupération données courbe par mois: " . $e->getMessage());
    }
}

// Préparer les données pour le graphique
$labels = [];
$data = [];
$couleurs = [];

if ($periode_type === 'jours') {
    // Remplir les jours manquants avec 0
    $date_courante = new DateTime($date_debut);
    $date_fin_obj = new DateTime($date_fin);
    
    while ($date_courante <= $date_fin_obj) {
        $date_str = $date_courante->format('Y-m-d');
        $labels[] = $date_courante->format('d/m');
        
        // Chercher si des données existent pour cette date
        $trouve = false;
        foreach ($courbe_data as $item) {
            if ($item['date_jour'] == $date_str) {
                $data[] = $item['total_jour'];
                $trouve = true;
                break;
            }
        }
        
        if (!$trouve) {
            $data[] = 0;
        }
        
        // Générer une couleur orange dégradée
        $couleurs[] = 'rgba(255, 152, 0, 0.7)';
        
        $date_courante->modify('+1 day');
    }
} else {
    // Données par mois
    foreach ($courbe_data as $item) {
        $nom_mois = getNomMois($item['mois']);
        $labels[] = $nom_mois . ' ' . $item['annee'];
        $data[] = $item['total_mois'];
        $couleurs[] = 'rgba(255, 152, 0, 0.7)';
    }
}

// Fonction pour obtenir le nom du mois
function getNomMois($numero_mois) {
    $mois = [
        1 => 'Jan', 2 => 'Fév', 3 => 'Mar', 4 => 'Avr', 5 => 'Mai', 6 => 'Jun',
        7 => 'Jul', 8 => 'Aoû', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Déc'
    ];
    return $mois[$numero_mois] ?? $numero_mois;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Synthèse des Bureaux</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --orange: #ff9800;
            --orange-dark: #e68900;
            --black: #000000;
            --black-light: #333333;
            --white: #ffffff;
        }
        
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }
        
        .full-height-container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .main-content {
            flex: 1;
            padding: 0;
            margin: 0;
            width: 100%;
        }
        
        .header-section {
            background: linear-gradient(135deg, var(--orange), var(--black));
            color: var(--white);
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            padding: 1rem 0;
        }
        
        .logo-container {
            padding: 0.5rem 0;
        }
        
        .stats-card {
            border-radius: 8px;
            transition: all 0.3s ease;
            border: none;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            height: 100%;
        }
        
        .stats-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.12);
        }
        
        .stats-icon {
            font-size: 1.8rem;
            opacity: 0.9;
        }
        
        .stats-value {
            font-size: 1.4rem;
            font-weight: 700;
        }
        
        .stats-label {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .card-orange {
            border-left: 3px solid var(--orange);
        }
        
        .card-black {
            border-left: 3px solid var(--black);
        }
        
        .card-success {
            border-left: 3px solid #28a745;
        }
        
        .card-info {
            border-left: 3px solid #17a2b8;
        }
        
        .card-warning {
            border-left: 3px solid #ffc107;
        }
        
        .card-danger {
            border-left: 3px solid #dc3545;
        }
        
        .card-purple {
            border-left: 3px solid #6f42c1;
        }
        
        .btn-orange {
            background-color: var(--orange);
            border-color: var(--orange);
            color: var(--white);
            font-weight: 500;
        }
        
        .btn-orange:hover {
            background-color: var(--orange-dark);
            border-color: var(--orange-dark);
            color: var(--white);
        }
        
        .btn-black {
            background-color: var(--black);
            border-color: var(--black);
            color: var(--white);
            font-weight: 500;
        }
        
        .btn-black:hover {
            background-color: var(--black-light);
            border-color: var(--black-light);
            color: var(--white);
        }
        
        .table th {
            background-color: var(--black);
            color: var(--white);
            position: sticky;
            top: 0;
            z-index: 10;
            font-size: 0.85rem;
            padding: 0.5rem 0.3rem;
        }
        
        .table td {
            font-size: 0.8rem;
            padding: 0.4rem 0.3rem;
            vertical-align: middle;
        }
        
        .table-responsive {
            max-height: calc(100vh - 300px);
            overflow-y: auto;
        }
        
        .badge-orange {
            background-color: var(--orange);
            color: var(--white);
        }
        
        .badge-black {
            background-color: var(--black);
            color: var(--white);
        }
        
        .badge-success {
            background-color: #28a745;
            color: var(--white);
        }
        
        .badge-warning {
            background-color: #ffc107;
            color: var(--black);
        }
        
        .badge-danger {
            background-color: #dc3545;
            color: var(--white);
        }
        
        .filter-section {
            background-color: var(--white);
            border-radius: 8px;
            padding: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 1rem;
        }
        
        .text-orange {
            color: var(--orange) !important;
        }
        
        .text-black {
            color: var(--black) !important;
        }
        
        .progress {
            height: 6px;
            border-radius: 3px;
        }
        
        .progress-bar {
            border-radius: 3px;
        }
        
        .export-btn {
            background: linear-gradient(135deg, var(--orange), var(--black));
            border: none;
            color: var(--white);
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .export-btn:hover {
            background: linear-gradient(135deg, var(--orange-dark), var(--black-light));
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .footer-section {
            background-color: var(--black);
            color: var(--white);
            padding: 0.8rem 0;
            margin-top: auto;
        }
        
        .bureau-active {
            background-color: rgba(255, 152, 0, 0.1);
        }
        
        .btn-outline-orange {
            border-color: var(--orange);
            color: var(--orange);
        }
        
        .btn-outline-orange:hover {
            background-color: var(--orange);
            color: var(--white);
        }
        
        .btn-outline-black {
            border-color: var(--black);
            color: var(--black);
        }
        
        .btn-outline-black:hover {
            background-color: var(--black);
            color: var(--white);
        }
        
        .periode-active {
            background-color: rgba(255, 152, 0, 0.1);
            border: 1px solid var(--orange);
        }
        
        .taux-excellent { background-color: #28a745; }
        .taux-bon { background-color: #20c997; }
        .taux-moyen { background-color: #ffc107; }
        .taux-faible { background-color: #fd7e14; }
        .taux-critique { background-color: #dc3545; }
        
        .small-text {
            font-size: 0.7rem;
        }
        
        .ecart-positif {
            color: #28a745;
            font-weight: bold;
        }
        
        .ecart-negatif {
            color: #dc3545;
            font-weight: bold;
        }
        
        .ecart-nul {
            color: #6c757d;
            font-weight: bold;
        }
        
        .chart-container {
            background: var(--white);
            border-radius: 8px;
            padding: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 1rem;
            height: 300px;
        }
        
        .chart-header {
            border-bottom: 2px solid var(--orange);
            padding-bottom: 0.8rem;
            margin-bottom: 0.8rem;
        }
        
        .montant-cell {
            text-align: right;
            font-family: 'Courier New', monospace;
            font-weight: bold;
            white-space: nowrap;
            font-size: 0.75rem;
        }
        
        .stats-section {
            margin-top: 1rem;
        }
        
        .compact-row {
            margin: 0 -5px;
        }
        
        .compact-col {
            padding: 0 5px;
        }
        
        .table-container {
            margin: 0;
            padding: 0;
        }
        
        h1 {
            font-size: 1.5rem;
        }
        
        h5 {
            font-size: 1.1rem;
        }
        
        .card-header {
            padding: 0.8rem 1rem;
        }
        
        .card-body {
            padding: 0.8rem;
        }
        
        .form-control, .btn {
            font-size: 0.85rem;
        }
        
        .form-label {
            margin-bottom: 0.2rem;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <div class="full-height-container">
        <!-- Header Section -->
        <div class="header-section">
            <div class="container-fluid py-2">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="logo-container text-center text-md-start">
                            <h1 class="mb-0">
                                <i class="bi bi-building me-2"></i>
                                SYNTHESE DES BUREAUX
                            </h1>
                            <p class="mb-0 mt-1 small">Tableau de bord des performances par bureau</p>
                        </div>
                    </div>
                    <div class="col-md-6 text-center text-md-end">
                        <div class="btn-group">
                            <button class="btn export-btn me-2" onclick="exporterTableau()">
                                <i class="bi bi-download me-1"></i> Exporter
                            </button>
                            <a href="admin.php?id=<?php echo $_SESSION['id']; ?>" class="btn btn-black">
                                <i class="bi bi-arrow-left me-1"></i> Retour
                            </a>
                        </div>
                        
                          <div class="col-md-6 text-center text-md-end">
                        <div class="btn-group">
                           
                            <a href="performancepagent.php?id=<?php echo $_SESSION['id']; ?>" class="btn btn-black">
                                Performance des Agents
                            </a>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content container-fluid">
            <!-- Filtres de période -->
            <div class="filter-section">
                <form method="POST" action="">
                    <div class="row align-items-center">
                        <div class="col-lg-4 col-md-5 mb-2 mb-md-0">
                            <h5 class="mb-0 text-orange">
                                <i class="bi bi-funnel me-2"></i>
                                Filtres de période
                            </h5>
                            <p class="mb-0 text-muted small">Sélectionnez une période pour afficher les données</p>
                        </div>
                        <div class="col-lg-5 col-md-4 mb-2 mb-md-0">
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label text-black"><small>Date début</small></label>
                                    <input type="date" class="form-control form-control-sm" name="date_debut" value="<?php echo $date_debut; ?>" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label text-black"><small>Date fin</small></label>
                                    <input type="date" class="form-control form-control-sm" name="date_fin" value="<?php echo $date_fin; ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-3 text-end">
                            <button type="submit" class="btn btn-sm btn-orange me-1">
                                <i class="bi bi-filter me-1"></i> Appliquer
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetFiltres()">
                                <i class="bi bi-arrow-clockwise me-1"></i> Reset
                            </button>
                        </div>
                    </div>
                    <?php if ($date_debut && $date_fin): ?>
                    <div class="row mt-2">
                        <div class="col-12">
                            <div class="alert alert-info periode-active py-1 small">
                                <i class="bi bi-calendar-range me-1"></i>
                                Période : du <?php echo date('d/m/Y', strtotime($date_debut)); ?> au <?php echo date('d/m/Y', strtotime($date_fin)); ?>
                                (<?php echo $jours_difference + 1; ?> jours) | 
                                <strong>Affichage : <?php echo $periode_type === 'jours' ? 'Par jour' : 'Par mois'; ?></strong>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Courbe Évolutive -->
            <?php if ($date_debut && $date_fin): ?>
            <div class="chart-container">
                <div class="chart-header">
                    <h5 class="mb-0 text-orange">
                        <i class="bi bi-graph-up me-2"></i>
                        COURBE ÉVOLUTIVE
                        <small class="text-muted">
                            (<?php echo $periode_type === 'jours' ? 'Quotidienne' : 'Mensuelle'; ?>)
                        </small>
                    </h5>
                    <p class="mb-0 text-muted small">
                        Période : <?php echo date('d/m/Y', strtotime($date_debut)); ?> - <?php echo date('d/m/Y', strtotime($date_fin)); ?>
                        | Total : <?php echo number_format($total_percu_global, 0, ',', ' '); ?> FCFA
                    </p>
                </div>
                <canvas id="courbeEvolutive"></canvas>
            </div>
            <?php endif; ?>
            <br><br>
            <!-- Statistiques Générales -->
            <div class="stats-section">
                <div class="row compact-row g-2">
                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 compact-col">
                        <div class="card stats-card card-orange h-100">
                            <div class="card-body p-2">
                                <div class="d-flex align-items-center">
                                    <div class="me-2">
                                        <i class="bi bi-building stats-icon text-orange"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="stats-value text-orange" id="totalBureaux"><?php echo $nombre_bureaux; ?></div>
                                        <div class="stats-label">Bureaux actifs</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 compact-col">
                        <div class="card stats-card card-purple h-100">
                            <div class="card-body p-2">
                                <div class="d-flex align-items-center">
                                    <div class="me-2">
                                        <i class="bi bi-bullseye stats-icon text-purple"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="stats-value text-purple"><?php echo number_format($total_objectif_mensuel_global, 0, ',', ' '); ?></div>
                                        <div class="stats-label">Objectif Mensuel total</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 compact-col">
                        <div class="card stats-card card-black h-100">
                            <div class="card-body p-2">
                                <div class="d-flex align-items-center">
                                    <div class="me-2">
                                        <i class="bi bi-cash-coin stats-icon text-black"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="stats-value text-black" id="potentielTotal"><?php echo number_format($total_potentiel, 0, ',', ' '); ?></div>
                                        <div class="stats-label">Potentiel total</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 compact-col">
                        <div class="card stats-card card-orange h-100">
                            <div class="card-body p-2">
                                <div class="d-flex align-items-center">
                                    <div class="me-2">
                                        <i class="bi bi-exclamation-triangle stats-icon text-orange"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="stats-value text-orange" id="totalImpayes"><?php echo number_format($total_impayes_global, 0, ',', ' '); ?></div>
                                        <div class="stats-label">Total impayé</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 compact-col">
                        <div class="card stats-card card-black h-100">
                            <div class="card-body p-2">
                                <div class="d-flex align-items-center">
                                    <div class="me-2">
                                        <i class="bi bi-graph-up-arrow stats-icon text-black"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="stats-value text-black" id="totalPercu"><?php echo number_format($total_percu_global, 0, ',', ' '); ?></div>
                                        <div class="stats-label">Total perçu</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 compact-col">
                        <div class="card stats-card card-warning h-100">
                            <div class="card-body p-2">
                                <div class="d-flex align-items-center">
                                    <div class="me-2">
                                        <i class="bi bi-arrow-down-up stats-icon text-warning"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="stats-value <?php echo getEcartClass($total_ecart_mensuel_global); ?>">
                                            <?php echo number_format($total_ecart_mensuel_global, 0, ',', ' '); ?>
                                        </div>
                                        <div class="stats-label">Écart mensuel</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tableau des Bureaux -->
            <div class="table-container">
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 text-orange">
                                <i class="bi bi-table me-2"></i>
                                SYNTHESE PAR BUREAU
                                <?php if ($date_debut && $date_fin): ?>
                                <small class="text-muted">(<?php echo date('d/m/Y', strtotime($date_debut)); ?> - <?php echo date('d/m/Y', strtotime($date_fin)); ?>)</small>
                                <?php endif; ?>
                            </h5>
                            <span class="badge bg-secondary small">
                                <i class="bi bi-info-circle me-1"></i>
                                <?php echo $nombre_bureaux; ?> bureaux
                            </span>
                        </div>
                    </div>
                    
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-sm mb-0" id="tableauBureaux">
                                <thead>
                                    <tr>
                                        <th width="25">#</th>
                                        <th>BUREAU</th>
                                        <th class="text-center">NOMBRE ETS</th>
                                        <th class="montant-cell">POTENTIEL</th>
                                        <th class="montant-cell">IMPAYES</th>
                                        <th class="montant-cell">PERÇU</th>
                                        <th class="montant-cell">OBJ. MENSUEL</th>
                                        <th class="montant-cell">OBJ. ANNUEL</th>
                                        <th class="montant-cell">ECART M</th>
                                        <th class="montant-cell">ECART A</th>
                                        <th>TAUX M</th>
                                        <th>TAUX A</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (empty($stats_bureaux)) {
                                        echo '<tr>
                                            <td colspan="12" class="text-center text-muted py-3">
                                                <i class="bi bi-building-x fs-1 d-block mb-2"></i>
                                                Aucun bureau trouvé
                                            </td>
                                        </tr>';
                                    } else {
                                        $index = 1;
                                        foreach ($stats_bureaux as $bureau => $stats) {
                                            // Classes pour les taux mensuel/annuel
                                            $taux_mensuel_class = getTauxClass($stats['taux_mensuel']);
                                            $taux_annuel_class = getTauxClass($stats['taux_annuel']);
                                            
                                            // Classes pour les écarts
                                            $ecart_mensuel_class = getEcartClass($stats['ecart_mensuel']);
                                            $ecart_annuel_class = getEcartClass($stats['ecart_annuel']);
                                            
                                            $is_current_bureau = ($bureau == $bureau_session);
                                            $row_class = $is_current_bureau ? 'bureau-active' : '';
                                            
                                            echo '<tr class="' . $row_class . '">
                                                <td class="text-center fw-bold">' . $index . '</td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-building me-1 text-orange small"></i>
                                                        <span class="text-truncate" style="max-width: 120px;" title="' . htmlspecialchars($bureau) . '">' . htmlspecialchars($bureau) . '</span>
                                                        ' . ($is_current_bureau ? '<span class="badge badge-orange ms-1 small-text">VOTRE</span>' : '') . '
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary small">' . $stats['nombre_ets'] . '</span>
                                                </td>
                                                <td class="montant-cell text-success">' . number_format($stats['potentiel'], 0, ',', ' ') . '</td>
                                                <td class="montant-cell text-danger">' . number_format($stats['impayes'], 0, ',', ' ') . '</td>
                                                <td class="montant-cell text-success">' . number_format($stats['total_percu'], 0, ',', ' ') . '</td>
                                                <td class="montant-cell text-info">' . number_format($stats['objectif_mensuel'], 0, ',', ' ') . '</td>
                                                <td class="montant-cell text-info">' . number_format($stats['objectif_annuel'], 0, ',', ' ') . '</td>
                                                <td class="montant-cell ' . $ecart_mensuel_class . '">
                                                    ' . number_format($stats['ecart_mensuel'], 0, ',', ' ') . '
                                                </td>
                                                <td class="montant-cell ' . $ecart_annuel_class . '">
                                                    ' . number_format($stats['ecart_annuel'], 0, ',', ' ') . '
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress flex-grow-1 me-1">
                                                            <div class="progress-bar ' . $taux_mensuel_class . '" 
                                                                 role="progressbar" 
                                                                 style="width: ' . min($stats['taux_mensuel'], 100) . '%" 
                                                                 aria-valuenow="' . $stats['taux_mensuel'] . '" 
                                                                 aria-valuemin="0" 
                                                                 aria-valuemax="100">
                                                        </div>
                                                        </div>
                                                        <small class="text-nowrap">' . number_format($stats['taux_mensuel'], 1) . '%</small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress flex-grow-1 me-1">
                                                            <div class="progress-bar ' . $taux_annuel_class . '" 
                                                                 role="progressbar" 
                                                                 style="width: ' . min($stats['taux_annuel'], 100) . '%" 
                                                                 aria-valuenow="' . $stats['taux_annuel'] . '" 
                                                                 aria-valuemin="0" 
                                                                 aria-valuemax="100">
                                                        </div>
                                                        </div>
                                                        <small class="text-nowrap">' . number_format($stats['taux_annuel'], 1) . '%</small>
                                                    </div>
                                                </td>
                                            </tr>';
                                            $index++;
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="card-footer bg-white py-2">
                        <div class="row">
                            <div class="col-md-6">
                                <small class="text-muted">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Synthèse des performances par bureau
                                </small>
                            </div>
                            <div class="col-md-6 text-end">
                                <small class="text-muted">
                                    MAJ: <?php echo date('d/m/Y H:i'); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer-section">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-6 text-center text-md-start">
                        <small>
                            <i class="bi bi-c-circle me-1"></i>
                            <?php echo date('Y'); ?> - Système de Gestion des Paiements
                        </small>
                    </div>
                    <div class="col-md-6 text-center text-md-end">
                        <small>
                            <i class="bi bi-person me-1"></i>
                            Connecté: <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fonction pour réinitialiser les filtres
        function resetFiltres() {
            // Définir les dates par défaut (mois en cours)
            const aujourdhui = new Date();
            const premierJour = new Date(aujourdhui.getFullYear(), aujourdhui.getMonth(), 1);
            const dernierJour = new Date(aujourdhui.getFullYear(), aujourdhui.getMonth() + 1, 0);
            
            // Formater les dates au format YYYY-MM-DD
            const formatDate = (date) => {
                return date.toISOString().split('T')[0];
            };
            
            document.querySelector('input[name="date_debut"]').value = formatDate(premierJour);
            document.querySelector('input[name="date_fin"]').value = formatDate(dernierJour);
            document.querySelector('form').submit();
        }
        
        // Fonction pour exporter le tableau
        function exporterTableau() {
            alert('Fonction d\'export Excel à implémenter');
            // Ici vous pouvez ajouter le code pour exporter en Excel
        }

        // Graphique de la courbe évolutive
        <?php if ($date_debut && $date_fin && !empty($data)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('courbeEvolutive').getContext('2d');
            
            const chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($labels); ?>,
                    datasets: [{
                        label: 'Montants Perçus (FCFA)',
                        data: <?php echo json_encode($data); ?>,
                        backgroundColor: 'rgba(255, 152, 0, 0.1)',
                        borderColor: 'rgba(255, 152, 0, 0.8)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: 'rgba(255, 152, 0, 1)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 1,
                        pointRadius: 3,
                        pointHoverRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Montant: ' + new Intl.NumberFormat('fr-FR').format(context.parsed.y) + ' FCFA';
                                }
                            }
                        },
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                boxWidth: 12,
                                font: {
                                    size: 11
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Montant (FCFA)',
                                font: {
                                    size: 11
                                }
                            },
                            ticks: {
                                callback: function(value) {
                                    return new Intl.NumberFormat('fr-FR').format(value);
                                },
                                font: {
                                    size: 9
                                }
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: '<?php echo $periode_type === 'jours' ? 'Jours' : 'Mois'; ?>',
                                font: {
                                    size: 11
                                }
                            },
                            ticks: {
                                font: {
                                    size: 9
                                }
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    animation: {
                        duration: 1000,
                        easing: 'easeInOutQuart'
                    }
                }
            });
        });
        <?php endif; ?>
    </script>
</body>
</html>

<?php
// Fonction pour déterminer la classe CSS en fonction du taux
function getTauxClass($taux) {
    if ($taux >= 100) return 'taux-excellent';
    if ($taux >= 80) return 'taux-bon';
    if ($taux >= 60) return 'taux-moyen';
    if ($taux >= 40) return 'taux-faible';
    return 'taux-critique';
}

// Fonction pour déterminer la classe CSS en fonction de l'écart
function getEcartClass($ecart) {
    if ($ecart > 0) return 'ecart-positif';
    if ($ecart < 0) return 'ecart-negatif';
    return 'ecart-nul';
}
?>