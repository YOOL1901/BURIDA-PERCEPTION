<?php 
session_start();
require("bdburida.php");

// Récupérer le bureau de la session
$bureau_session = $_SESSION['bureau'];
$nom_session = $_SESSION['nom']; // Nom de l'utilisateur
$id_session = $_SESSION['id']; // ID de l'utilisateur

// Fonction pour extraire le dernier mois d'une période multiple
function extraireDernierMois($periode) {
    // Gérer les formats comme "janvier 2024-février 2024-mars 2024"
    if (strpos($periode, '-') !== false) {
        $periodes = explode('-', $periode);
        $derniere_periode = trim(end($periodes));
        return $derniere_periode;
    }
    return trim($periode);
}

// Fonction pour parser le mois et l'année d'une période (avec gestion des avances et soldes)
function parserPeriode($periode, $mois_fr) {
    $periode_clean = trim($periode);
    $avance = 0;
    $solde = 0;
    $type_paiement = 'normal'; // normal, avance, solde
    
    // Vérifier s'il y a un SOLDE (format: "SOLDE 3000 SUR JUILLET 2025")
    if (preg_match('/SOLDE\s+(\d+(?:\s?\d+)*)\s+(?:SUR\s+)?(\w+)\s+(\d{4})/i', $periode_clean, $matches)) {
        // Extraire le montant du solde (enlever les espaces dans le nombre)
        $solde = floatval(str_replace(' ', '', $matches[1]));
        $mois = strtolower(trim($matches[2]));
        $annee = intval(trim($matches[3]));
        $type_paiement = 'solde';
        
        if (isset($mois_fr[$mois]) && $annee > 0) {
            return [
                'mois' => $mois,
                'mois_num' => $mois_fr[$mois],
                'annee' => $annee,
                'avance' => 0,
                'solde' => $solde,
                'type' => $type_paiement
            ];
        }
    }
    
    // Vérifier s'il y a une avance (format: "AVANCE 1000 SUR JUILLET 2025" ou "AVANCE 1000 JUILLET 2025")
    if (preg_match('/AVANCE\s+(\d+(?:\s?\d+)*)\s+(?:SUR\s+)?(\w+)\s+(\d{4})/i', $periode_clean, $matches)) {
        // Extraire le montant de l'avance (enlever les espaces dans le nombre)
        $avance = floatval(str_replace(' ', '', $matches[1]));
        $mois = strtolower(trim($matches[2]));
        $annee = intval(trim($matches[3]));
        $type_paiement = 'avance';
        
        if (isset($mois_fr[$mois]) && $annee > 0) {
            return [
                'mois' => $mois,
                'mois_num' => $mois_fr[$mois],
                'annee' => $annee,
                'avance' => $avance,
                'solde' => 0,
                'type' => $type_paiement
            ];
        }
    }
    
    // Format standard sans avance ni solde
    $parts = preg_split('/\s+/', $periode_clean);
    
    if (count($parts) >= 2) {
        $mois = strtolower(trim($parts[0]));
        $annee = intval(trim($parts[1]));
        
        if (isset($mois_fr[$mois]) && $annee > 0) {
            return [
                'mois' => $mois,
                'mois_num' => $mois_fr[$mois],
                'annee' => $annee,
                'avance' => 0,
                'solde' => 0,
                'type' => 'normal'
            ];
        }
    }
    
    return null;
}

// FONCTION CORRIGEE : Calculer le montant dû en partant du mois suivant jusqu'au mois en cours
function calculerMontantDu($derniere_periode_data, $mensualite_value, $mois_fr, $debut_ets = null) {
    $mois_actuel = intval(date('n'));
    $annee_actuelle = intval(date('Y'));
    $total_due_value = 0;
    $nombre_mois_impayes = 0;
    $avance_restante = 0;
    
    // NOUVELLE LOGIQUE : Si pas de paiement mais il y a une date de début
    if (!$derniere_periode_data && $debut_ets) {
        // Parser la date de début (format: YYYY-MM)
        $debut_parts = explode('-', $debut_ets);
        if (count($debut_parts) == 2) {
            $annee_debut = intval($debut_parts[0]);
            $mois_debut = intval($debut_parts[1]);
            
            // Calculer le nombre de mois depuis le début jusqu'au mois actuel
            $mois_diff = (($annee_actuelle - $annee_debut) * 12) + ($mois_actuel - $mois_debut);
            
            // Si la différence est positive, il y a des mois impayés
            if ($mois_diff >= 0) {
                $nombre_mois_impayes = $mois_diff + 1; // +1 pour inclure le mois de début
                $total_due_value = $mensualite_value * $nombre_mois_impayes;
            } else {
                // Si la date de début est dans le futur
                $nombre_mois_impayes = 0;
                $total_due_value = 0;
            }
        }
    }
    // LOGIQUE EXISTANTE : S'il y a des paiements
    else if ($derniere_periode_data && !empty($derniere_periode_data['periode'])) {
        $derniere_periode = trim($derniere_periode_data['periode']);
        $derniere_periode_simple = extraireDernierMois($derniere_periode);
        $parsed = parserPeriode($derniere_periode_simple, $mois_fr);
        
        if ($parsed !== null) {
            // CORRECTION : TOUJOURS commencer au mois suivant la période payée
            $mois_reference = $parsed['mois_num'] + 1;
            $annee_reference = $parsed['annee'];
            
            // Gestion du passage à l'année suivante
            if ($mois_reference > 12) {
                $mois_reference = 1;
                $annee_reference++;
            }
            
            // Calculer le nombre de mois entre le mois suivant et le mois actuel
            $mois_diff = (($annee_actuelle - $annee_reference) * 12) + ($mois_actuel - $mois_reference);
            
            // CORRECTION : Si la différence est négative ou nulle, c'est qu'on est à jour
            if ($mois_diff < 0) {
                $nombre_mois_impayes = 0;
                $total_due_value = 0;
            } else {
                // CORRECTION : Le nombre de mois impayés est la différence + 1
                // car on compte du mois suivant jusqu'au mois actuel INCLUS
                $nombre_mois_impayes = $mois_diff + 1;
                $total_brut = $mensualite_value * $nombre_mois_impayes;
                
                // Gestion des avances seulement (les soldes ne déduisent pas)
                if ($parsed['type'] === 'avance') {
                    $avance_restante = $parsed['avance'];
                    $total_due_value = $total_brut - $avance_restante;
                    if ($total_due_value < 0) {
                        $total_due_value = 0;
                    }
                } else {
                    // Pour les soldes et paiements normaux, pas de déduction
                    $total_due_value = $total_brut;
                }
            }
        }
    } else {
        // Si aucun paiement et aucune date de début
        $nombre_mois_impayes = 1;
        $total_due_value = $mensualite_value * $nombre_mois_impayes;
    }
    
    return [
        'total_due' => $total_due_value,
        'mois_impayes' => $nombre_mois_impayes,
        'avance_restante' => $avance_restante
    ];
}

// Fonction pour formater l'affichage de la date de début
function formaterDateDebut($date_debut) {
    if (empty($date_debut) || $date_debut == '0000-00') {
        return 'Non définie';
    }
    
    $mois_fr = array(
        1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
        5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
        9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre'
    );
    
    $parts = explode('-', $date_debut);
    if (count($parts) == 2) {
        $annee = intval($parts[0]);
        $mois = intval($parts[1]);
        
        if (isset($mois_fr[$mois]) && $annee > 0) {
            return ucfirst($mois_fr[$mois]) . ' ' . $annee;
        }
    }
    
    return $date_debut;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>LISTE DES ETABLISSEMENTS - <?php echo htmlspecialchars($bureau_session); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        a { text-decoration: none; }
        .search-box { margin-bottom: 20px; }
        .highlight { background-color: #ffc107; color: #000; padding: 2px 4px; border-radius: 3px; }
        .bg-orange { background-color: #ff9800; }
        .text-orange { color: #ff9800; }
        .btn-orange { 
            background-color: #ff9800; 
            border-color: #ff9800;
            color: white;
        }
        .btn-orange:hover {
            background-color: #e68900;
            border-color: #e68900;
            color: white;
        }
        .btn-black {
            background-color: #000000;
            border-color: #000000;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-black:hover {
            background-color: #333333;
            border-color: #333333;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .table-responsive {
            max-height: 600px;
            overflow-y: auto;
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        .table th {
            background-color: #343a40;
            color: white;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .badge-etat {
            font-size: 0.8em;
            padding: 0.4em 0.6em;
        }
        .text-danger { color: #dc3545 !important; }
        .text-warning { color: #ffc107 !important; }
        tr:hover { cursor: pointer; }
        .table td {
            vertical-align: middle;
        }
        .stats-card {
            border-left: 4px solid #dc3545;
            transition: all 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .stats-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        .stats-value {
            font-size: 1.8rem;
            font-weight: 700;
        }
        .stats-label {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .btn-add-ets {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            color: white;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .btn-add-ets:hover {
            background: linear-gradient(135deg, #218838, #1e9e8a);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(40, 167, 69, 0.4);
        }
        .badge-debut {
            background-color: #6f42c1;
            font-size: 0.7em;
        }
        .badge-bureau {
            background-color: #17a2b8;
            font-size: 0.8em;
        }
        /* Cacher la colonne DÉBUT CONTRAT */
        .col-debut-contrat {
            display: none;
        }
        /* Styles pour le menu utilisateur */
        .user-menu {
            position: relative;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ff9800, #ff6600);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 16px;
        }
        .dropdown-menu-user {
            min-width: 200px;
            border: 1px solid rgba(0,0,0,.15);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .user-info {
            padding: 10px 15px;
            border-bottom: 1px solid #dee2e6;
        }
        .user-name {
            font-weight: 600;
            color: #343a40;
        }
        .user-bureau {
            font-size: 0.85rem;
            color: #6c757d;
        }
        .dropdown-item:hover {
            background-color: #f8f9fa;
        }
        .dropdown-item:active {
            background-color: #e9ecef;
        }
        .user-status {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: #28a745;
            margin-right: 8px;
        }
        /* Header amélioré */
        .header-container {
            background: linear-gradient(135deg, #ff9800, #ff6600);
            padding: 10px 0;
            margin-bottom: 20px;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-light">

<div class="container-fluid">
   
                <?php include("sessioninfo.php"); ?>
                

    <!-- Contenu principal -->
    <div class="container py-3">
        <?php
        // Calcul des statistiques avant la boucle
        $total_general_du = 0;
        $total_etablissements_impayes = 0;
        $total_etablissements = 0;
        
        // Tableau de correspondance des mois
        $mois_fr = array(
            'janvier' => 1, 'février' => 2, 'fevrier' => 2, 'mars' => 3, 'avril' => 4,
            'mai' => 5, 'juin' => 6, 'juillet' => 7, 'août' => 8, 'aout' => 8,
            'septembre' => 9, 'octobre' => 10, 'novembre' => 11, 'décembre' => 12, 'decembre' => 12
        );
        
        try {
            // Récupération de tous les établissements POUR LE BUREAU DE LA SESSION SEULEMENT
            $recupcmd_stats = $bdd->prepare('SELECT * FROM ETABLISSEMENT WHERE bureau = ? ORDER BY id_ets DESC');
            $recupcmd_stats->execute([$bureau_session]);
            
            // Compter le total des établissements pour ce bureau
            $req_total = $bdd->prepare('SELECT COUNT(*) as total FROM ETABLISSEMENT WHERE bureau = ?');
            $req_total->execute([$bureau_session]);
            $total_etablissements = $req_total->fetch()['total'];
            
            while ($user = $recupcmd_stats->fetch(PDO::FETCH_ASSOC)) {
                $id_ets = $user['id_ets'];
                $debut_ets = $user['debut'] ?? null;
                
                // Récupérer la dernière période payée pour cet établissement
                try {
                    $req_derniere_periode = $bdd->prepare('SELECT id_pay, periode, dates FROM PAYEMENT WHERE id_ets = ? ORDER BY id_pay DESC LIMIT 1');
                    $req_derniere_periode->execute([$id_ets]);
                    $derniere_periode_data = $req_derniere_periode->fetch(PDO::FETCH_ASSOC);
                } catch(Exception $e) {
                    $derniere_periode_data = null;
                }
                
                // Utiliser la nouvelle fonction de calcul avec la date de début
                $mensualite_value = floatval(preg_replace('/[^0-9.]/', '', $user['mensualite']));
                $calcul = calculerMontantDu($derniere_periode_data, $mensualite_value, $mois_fr, $debut_ets);
                
                $total_due_value = $calcul['total_due'];
                
                $total_general_du += $total_due_value;
                if ($total_due_value > 0) {
                    $total_etablissements_impayes++;
                }
            }
        } catch(Exception $e) {
            $total_general_du = 0;
            $total_etablissements_impayes = 0;
        }
        
        $total_general_du_formatted = number_format($total_general_du, 0, ',', ' ');
        $pourcentage_impayes = $total_etablissements > 0 ? round(($total_etablissements_impayes / $total_etablissements) * 100, 1) : 0;
        ?>

        <!-- Carte des statistiques du total dû -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card stats-card shadow-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h5 class="card-title text-danger mb-3">
                                    <i class="bi bi-graph-up-arrow me-2"></i>
                                    SYNTHÈSE DES IMPAYÉS - BUREAU <?php echo htmlspecialchars($bureau_session); ?>
                                </h5>
                                <div class="row">
                                    <div class="col-lg-4 mb-3">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <i class="bi bi-cash-coin text-danger stats-icon"></i>
                                            </div>
                                            <div>
                                                <div class="stats-value text-danger" id="totalDuDisplay">
                                                    <?php echo $total_general_du_formatted; ?> FCFA
                                                </div>
                                                <div class="stats-label">Total général dû</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 mb-3">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <i class="bi bi-building-exclamation text-warning stats-icon"></i>
                                            </div>
                                            <div>
                                                <div class="stats-value text-warning" id="totalEtsImpayes">
                                                    <?php echo $total_etablissements_impayes; ?>
                                                </div>
                                                <div class="stats-label">Établissements en retard</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 mb-3">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <i class="bi bi-percent text-info stats-icon"></i>
                                            </div>
                                            <div>
                                                <div class="stats-value text-info">
                                                    <?php echo $pourcentage_impayes; ?>%
                                                </div>
                                                <div class="stats-label">Taux d'impayés</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="bg-light p-3 rounded">
                                    <small class="text-muted d-block">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Bureau actuel
                                    </small>
                                    <strong class="text-primary"><?php echo htmlspecialchars($bureau_session); ?></strong>
                                    <div class="mt-2">
                                        <span class="badge bg-success">Total ETS: <?php echo $total_etablissements; ?></span>
                                    </div>
                                    <small class="text-muted d-block mt-2">
                                        <i class="bi bi-calendar me-1"></i>
                                        <?php echo date('d/m/Y à H:i'); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Carte principale du tableau -->
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-building me-2 text-orange"></i>
                        LISTE DES ETABLISSEMENTS - BUREAU <?php echo htmlspecialchars($bureau_session); ?>
                        <span class="badge badge-bureau ms-2"><?php echo htmlspecialchars($bureau_session); ?></span>
                    </h5>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-secondary">
                            <i class="bi bi-info-circle me-1"></i>
                            <?php echo $total_etablissements . " établissements"; ?>
                        </span>
                    </div>
                </div>
                
                <!-- Barre de recherche -->
                <div class="search-box mt-3">
                    <div class="input-group">
                        <span class="input-group-text bg-orange text-white">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" id="searchInput" class="form-control" 
                               placeholder="Tapez votre recherche ici (nom, localité, contact...) - Bureau: <?php echo htmlspecialchars($bureau_session); ?>">
                        <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0" id="facturesTable">
                        <thead>
                            <tr>
                                <th width="60">ID PAY</th>
                                <th width="80">CODE</th>
                                <th>ETABLISSEMENT</th>
                                <th>USAGER</th>
                                <th>CONTACT</th>
                                <th>LOCALITE</th>
                                <th>BUREAU</th>
                                <th>MENSUALITE</th>
                                <!-- COLONNE DÉBUT CONTRAT CACHÉE MAIS TOUJOURS PRÉSENTE -->
                                <th style="display: none;">DÉBUT CONTRAT</th>
                                <th>DERNIERE PERIODE PAYEE</th>
                                <th>TOTAL DÛ</th>
                                <th>ETAT</th>
                                <th width="200">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            try {
                                // REQUÊTE CORRIGÉE : Filtrer uniquement par bureau (sans filtre agent)
                                $recupcmd = $bdd->prepare('SELECT * FROM ETABLISSEMENT WHERE bureau = ? ORDER BY id_ets DESC');
                                $recupcmd->execute([$bureau_session]);
                                
                                if ($recupcmd->rowCount() == 0) {
                                    echo '<tr><td colspan="12" class="text-center text-muted py-4">
                                        <i class="bi bi-building-x fs-1 d-block mb-2"></i>
                                        Aucun établissement trouvé pour le bureau <strong>'.htmlspecialchars($bureau_session).'</strong>
                                    </td></tr>';
                                }
                                
                                while ($user = $recupcmd->fetch(PDO::FETCH_ASSOC)) {
                                    $id_ets = $user['id_ets'];
                                    $debut_ets = $user['debut'] ?? null;
                                    
                                    // Récupérer la dernière période payée pour cet établissement
                                    try {
                                        $req_derniere_periode = $bdd->prepare('SELECT id_pay, periode, dates FROM PAYEMENT WHERE id_ets = ? ORDER BY id_pay DESC LIMIT 1');
                                        $req_derniere_periode->execute([$id_ets]);
                                        $derniere_periode_data = $req_derniere_periode->fetch(PDO::FETCH_ASSOC);
                                    } catch(Exception $e) {
                                        $derniere_periode_data = null;
                                    }
                                    
                                    // Initialisation des variables
                                    $id_pay_display = '-';
                                    $periode_due = 'Aucun paiement';
                                    $total_due_value = 0;
                                    $nombre_mois_impayes = 0;
                                    $periodeClass = 'text-muted';
                                    $avance_restante = 0;
                                    $type_paiement = 'normal';
                                    $info_debut = '';
                                    
                                    // NOUVELLE LOGIQUE : Affichage de la date de début
                                    $date_debut_formatted = formaterDateDebut($debut_ets);
                                    $debutClass = 'text-info';
                                    
                                    if ($derniere_periode_data && !empty($derniere_periode_data['periode'])) {
                                        // Récupérer l'ID du paiement
                                        $id_pay_display = htmlspecialchars($derniere_periode_data['id_pay']);
                                        
                                        $derniere_periode = trim($derniere_periode_data['periode']);
                                        
                                        // Extraire le dernier mois de la période (gère les périodes multiples)
                                        $derniere_periode_simple = extraireDernierMois($derniere_periode);
                                        
                                        // Parser la période pour obtenir mois, année et type de paiement
                                        $parsed = parserPeriode($derniere_periode_simple, $mois_fr);
                                        
                                        if ($parsed !== null) {
                                            // Utiliser la nouvelle fonction de calcul avec date de début
                                            $mensualite_value = floatval(preg_replace('/[^0-9.]/', '', $user['mensualite']));
                                            $calcul = calculerMontantDu($derniere_periode_data, $mensualite_value, $mois_fr, $debut_ets);
                                            
                                            $total_due_value = $calcul['total_due'];
                                            $nombre_mois_impayes = $calcul['mois_impayes'];
                                            $avance_restante = $calcul['avance_restante'];
                                            $type_paiement = $parsed['type'];
                                            
                                            // Préparer l'affichage de la période
                                            $mois_affichage = ucfirst($parsed['mois']) . ' ' . $parsed['annee'];
                                            
                                            // Afficher selon le type de paiement
                                            if ($parsed['type'] === 'solde') {
                                                $periode_due = 'SOLDE ' . number_format($parsed['solde'], 0, ',', ' ') . ' SUR ' . $mois_affichage;
                                            } elseif ($parsed['type'] === 'avance') {
                                                $periode_due = 'AVANCE ' . number_format($parsed['avance'], 0, ',', ' ') . ' SUR ' . $mois_affichage;
                                            } else {
                                                $periode_due = $mois_affichage;
                                            }
                                            
                                            // Déterminer la classe CSS pour la période
                                            if ($total_due_value > 0) {
                                                $periodeClass = 'text-danger fw-bold';
                                            } else if ($avance_restante > 0) {
                                                $periodeClass = 'text-info fw-bold';
                                            } else {
                                                $periodeClass = 'text-success';
                                            }
                                        } else {
                                            // Si on n'arrive pas à parser, afficher tel quel
                                            $periode_due = htmlspecialchars($derniere_periode_simple);
                                            $periodeClass = 'text-warning';
                                            
                                            // Calcul par défaut si parsing échoue
                                            $mensualite_value = floatval(preg_replace('/[^0-9.]/', '', $user['mensualite']));
                                            $calcul = calculerMontantDu($derniere_periode_data, $mensualite_value, $mois_fr, $debut_ets);
                                            $total_due_value = $calcul['total_due'];
                                            $nombre_mois_impayes = $calcul['mois_impayes'];
                                        }
                                    } else {
                                        // Si aucun paiement n'a jamais été fait, utiliser la date de début
                                        $calcul = calculerMontantDu(null, floatval(preg_replace('/[^0-9.]/', '', $user['mensualite'])), $mois_fr, $debut_ets);
                                        $total_due_value = $calcul['total_due'];
                                        $nombre_mois_impayes = $calcul['mois_impayes'];
                                        $periodeClass = 'text-muted fst-italic';
                                        
                                        // Ajouter une info sur le calcul basé sur la date de début
                                        if ($debut_ets && $debut_ets != '0000-00') {
                                            $info_debut = ' <span class="badge badge-debut" title="Calcul basé sur la date de début">Début: ' . $date_debut_formatted . '</span>';
                                        }
                                    }
                                    
                                    $total_due = number_format($total_due_value, 0, ',', ' ') . ' FCFA';
                                    
                                    // Préparer le texte du tooltip avec les détails
                                    $tooltip_text = '';
                                    if ($nombre_mois_impayes > 0) {
                                        if ($type_paiement === 'solde') {
                                            $tooltip_text = "{$nombre_mois_impayes} mois impayé(s) - Solde payé pour le dernier mois";
                                        } elseif ($avance_restante > 0) {
                                            $tooltip_text = "{$nombre_mois_impayes} mois impayé(s) - Avance de " . number_format($avance_restante, 0, ',', ' ') . " FCFA déduite";
                                        } else {
                                            $tooltip_text = "{$nombre_mois_impayes} mois impayé(s)";
                                        }
                                        
                                        // Ajouter l'info de la date de début si applicable
                                        if (!$derniere_periode_data && $debut_ets) {
                                            $tooltip_text .= " - Calcul depuis " . $date_debut_formatted;
                                        }
                                    } else {
                                        if ($type_paiement === 'solde') {
                                            $tooltip_text = "Solde payé - à jour";
                                        } elseif ($avance_restante > 0) {
                                            $tooltip_text = "Avance de " . number_format($avance_restante, 0, ',', ' ') . " FCFA";
                                        } else {
                                            $tooltip_text = "à jour";
                                        }
                                    }
                                    
                                    // Ajouter l'info du nombre de mois impayés en tooltip
                                    $tooltip_total = "title=\"{$tooltip_text}\" data-bs-toggle=\"tooltip\"";
                                    
                                    // Style pour l'état
                                    $etatClass = 'bg-secondary';
                                    $etat_value = isset($user['etat']) ? strtolower(trim($user['etat'])) : '';
                                    if ($etat_value === 'actif') {
                                        $etatClass = 'bg-success';
                                    } elseif ($etat_value === 'inactif') {
                                        $etatClass = 'bg-danger';
                                    } elseif ($etat_value === 'en attente') {
                                        $etatClass = 'bg-warning text-dark';
                                    } elseif ($etat_value === 'ouvert') {
                                        $etatClass = 'bg-success';
                                    }
                                    
                                    // Style pour le total dû (rouge si > 0, vert si = 0)
                                    $totalClass = ($total_due_value > 0) ? 'text-danger fw-bold' : 'text-success';
                                    
                                    // Sécuriser toutes les valeurs
                                    $id_ets_safe = htmlspecialchars($user['id_ets'] ?? '');
                                    $denomination_safe = htmlspecialchars($user['denomination'] ?? 'N/A');
                                    $nom_safe = htmlspecialchars($user['nom'] ?? 'N/A');
                                    $contact_safe = htmlspecialchars($user['contact'] ?? 'N/A');
                                    $localite_safe = htmlspecialchars($user['localite'] ?? 'N/A');
                                    $bureau_safe = htmlspecialchars($user['bureau'] ?? 'N/A');
                                    $mensualite_safe = htmlspecialchars($user['mensualite'] ?? '0');
                                    $etat_safe = htmlspecialchars($user['etat'] ?? 'N/A');
                                    
                                    echo '<tr data-id="'.$id_ets_safe.'">
                                        <td class="text-center"><span class="badge bg-secondary">'.$id_pay_display.'</span></td>
                                        <td class="fw-bold">'.$id_ets_safe.'</td>
                                        <td>'.$denomination_safe.'</td>
                                        <td>'.$nom_safe.'</td>
                                        <td>
                                            <i class="bi bi-telephone text-orange me-1"></i>
                                            '.$contact_safe.'
                                        </td>
                                        <td>'.$localite_safe.'</td>
                                        <td>
                                            <span class="badge badge-bureau">'.$bureau_safe.'</span>
                                        </td>
                                        <td class="fw-bold text-success">'.$mensualite_safe.' FCFA</td>
                                        <!-- COLONNE DÉBUT CONTRAT CACHÉE MAIS TOUJOURS PRÉSENTE -->
                                        <td style="display: none;" class="'.$debutClass.'">
                                            '.$date_debut_formatted.'
                                        </td>
                                        <td class="'.$periodeClass.'">'.$periode_due.$info_debut.'</td>
                                        <td class="'.$totalClass.'" '.$tooltip_total.'>
                                            '.$total_due.'
                                            '.($nombre_mois_impayes > 0 ? '<br><small class="text-muted">('.$nombre_mois_impayes.' mois'.($type_paiement === 'solde' ? ' - solde payé' : ($avance_restante > 0 ? ' - avance déduite' : '')).')</small>' : '').'
                                        </td>
                                        <td>
                                            <span class="badge badge-etat '.$etatClass.'">
                                                '.$etat_safe.'
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="agthistoriquepay.php?affichclient='.$id_ets_safe.'&nom='.urlencode($user['denomination']).'&ville='.urlencode($user['localite']).'&client='.urlencode($user['nom']).'&mensuel='.urlencode($user['mensualite']).'" 
                                                   class="btn btn-orange" 
                                                   title="Voir l\'état">
                                                    <i class="bi bi-eye-fill me-1"></i> Historique
                                                </a>
                                                <a href="agtpaynormal.php?affichclient='.$id_ets_safe.'&nom='.urlencode($user['denomination']).'&ville='.urlencode($user['localite']).'&client='.urlencode($user['nom']).'&mensuel='.urlencode($user['mensualite']).'" 
                                                   class="btn btn-success" 
                                                   title="Effectuer un paiement">
                                                    <i class="bi bi-credit-card me-1"></i> Payer
                                                </a>
                                            </div>
                                        </td>
                                    </tr>';
                                }
                            } catch(Exception $e) {
                                echo '<tr><td colspan="12" class="text-center text-danger">Erreur lors du chargement des données: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="card-footer bg-white py-3">
                <div class="row">
                    <div class="col-md-6">
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            Double-cliquez sur une ligne pour plus d'options
                        </small>
                    </div>
                    <div class="col-md-6 text-end">
                        <a href="menupay.php?id=<?php echo $id_session; ?>" 
                           class="btn btn-black btn-sm">
                            <i class="bi bi-arrow-left me-1"></i> Retour au menu
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const clearSearch = document.getElementById('clearSearch');
        const table = document.getElementById('facturesTable');
        const tbody = table.getElementsByTagName('tbody')[0];
        const rows = tbody.getElementsByTagName('tr');
        
        // Fonction de recherche
        function performSearch() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            let visibleCount = 0;
            
            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const cells = row.getElementsByTagName('td');
                
                // Ignorer les lignes d'erreur
                if (cells.length < 2) {
                    continue;
                }
                
                let searchableText = '';
                
                // Collecter le texte de toutes les colonnes sauf la dernière (actions)
                for (let j = 0; j < cells.length - 1; j++) {
                    searchableText += cells[j].textContent.toLowerCase() + ' ';
                }
                
                // Vérifier si le terme de recherche est présent
                if (searchTerm === '' || searchableText.includes(searchTerm)) {
                    row.style.display = '';
                    visibleCount++;
                    
                    // Restaurer le contenu original d'abord
                    for (let j = 0; j < cells.length - 1; j++) {
                        const original = cells[j].getAttribute('data-original');
                        if (original) {
                            cells[j].innerHTML = original;
                            cells[j].removeAttribute('data-original');
                        }
                    }
                    
                    // Mise en évidence du texte trouvé (sauf dans la colonne actions)
                    if (searchTerm.length > 0) {
                        for (let j = 0; j < cells.length - 1; j++) {
                            const cellText = cells[j].textContent;
                            const cellLower = cellText.toLowerCase();
                            
                            if (cellLower.includes(searchTerm)) {
                                // Sauvegarder le contenu original
                                if (!cells[j].hasAttribute('data-original')) {
                                    cells[j].setAttribute('data-original', cells[j].innerHTML);
                                }
                                
                                // Mettre en évidence
                                const regex = new RegExp('(' + searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
                                const highlighted = cellText.replace(regex, '<span class="highlight">$1</span>');
                                cells[j].innerHTML = highlighted;
                            }
                        }
                    }
                } else {
                    row.style.display = 'none';
                }
            }
            
            // Afficher un message si aucun résultat
            const noResultsRow = tbody.querySelector('.no-results');
            if (noResultsRow) {
                noResultsRow.remove();
            }
            
            if (visibleCount === 0 && searchTerm !== '') {
                const noResults = document.createElement('tr');
                noResults.className = 'no-results';
                noResults.innerHTML = '<td colspan="12" class="text-center text-muted py-4">' +
                    '<i class="bi bi-search fs-1 d-block mb-2"></i>' +
                    'Aucun établissement trouvé pour "' + searchTerm + '" dans le bureau <?php echo htmlspecialchars($bureau_session); ?>' +
                    '</td>';
                tbody.appendChild(noResults);
            }
        }
        
        // Événement de recherche
        searchInput.addEventListener('input', performSearch);
        
        // Bouton effacer recherche
        clearSearch.addEventListener('click', function() {
            searchInput.value = '';
            performSearch();
            searchInput.focus();
        });
        
        // Double-clic sur une ligne
        for (let i = 0; i < rows.length; i++) {
            rows[i].addEventListener('dblclick', function() {
                const cells = this.getElementsByTagName('td');
                if (cells.length < 2) return; // Ignorer les lignes d'erreur
                
                const idEts = this.getAttribute('data-id');
                const nomEts = cells[2].textContent; // Colonne ETABLISSEMENT (index 2 maintenant)
                
                if (confirm('Voulez-vous voir les détails de l\'établissement : ' + nomEts + ' ?')) {
                    const denomination = encodeURIComponent(nomEts);
                    const ville = encodeURIComponent(cells[5].textContent);
                    const client = encodeURIComponent(cells[3].textContent);
                    const mensuel = encodeURIComponent(cells[7].textContent.replace(' FCFA', '').trim());
                    
                    window.location.href = 'agthistoriquepay.php?affichclient=' + idEts + 
                                          '&nom=' + denomination + 
                                          '&ville=' + ville + 
                                          '&client=' + client + 
                                          '&mensuel=' + mensuel;
                }
            });
        }

        // Initialiser les tooltips Bootstrap
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>