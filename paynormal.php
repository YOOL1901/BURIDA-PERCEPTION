<?php 
session_start();
require("bdburida.php");
require("admincondition.php");

$agent = $_SESSION['nom'];

// Récupérer le dernier paiement pour cet établissement
$affichclient = $_GET['affichclient'];
$dernierPaiement = $bdd->prepare('SELECT periode, dates, montant FROM PAYEMENT WHERE id_ets = ? ORDER BY id_pay DESC LIMIT 1');
$dernierPaiement->execute(array($affichclient));
$lastPayment = $dernierPaiement->fetch();

// Récupérer la mensualité pour les calculs
$mensualite = (float)$_GET['mensuel'];

// Récupérer la date de début de l'établissement depuis la table ETABLISSEMENT
$req_etablissement = $bdd->prepare('SELECT debut FROM ETABLISSEMENT WHERE id_ets = ?');
$req_etablissement->execute(array($affichclient));
$etablissement = $req_etablissement->fetch();
$debut_ets = $etablissement['debut'] ?? null;

// Fonction pour obtenir la prochaine période à partir d'une période donnée
function getProchainePeriode($periode) {
    $moisFrancais = [
        'JANVIER' => 0, 'FEVRIER' => 1, 'MARS' => 2, 'AVRIL' => 3,
        'MAI' => 4, 'JUIN' => 5, 'JUILLET' => 6, 'AOUT' => 7,
        'SEPTEMBRE' => 8, 'OCTOBRE' => 9, 'NOVEMBRE' => 10, 'DECEMBRE' => 11
    ];
    
    $moisNoms = ['JANVIER', 'FEVRIER', 'MARS', 'AVRIL', 'MAI', 'JUIN', 
                 'JUILLET', 'AOUT', 'SEPTEMBRE', 'OCTOBRE', 'NOVEMBRE', 'DECEMBRE'];
    
    // Séparer le mois et l'année
    $parts = explode(' ', trim($periode));
    $moisActuel = strtoupper($parts[0]);
    $anneeActuelle = isset($parts[1]) ? intval($parts[1]) : intval(date('Y'));
    
    // Trouver l'index du mois actuel
    if (isset($moisFrancais[$moisActuel])) {
        $indexMoisActuel = $moisFrancais[$moisActuel];
        
        // Calculer le mois suivant
        $indexMoisSuivant = ($indexMoisActuel + 1) % 12;
        
        // Si on passe de décembre à janvier, incrémenter l'année
        $anneeSuivante = $anneeActuelle;
        if ($indexMoisActuel == 11) { // Décembre
            $anneeSuivante++;
        }
        
        $moisSuivant = $moisNoms[$indexMoisSuivant];
        
        return $moisSuivant . ' ' . $anneeSuivante;
    }
    
    // Si le format n'est pas reconnu, retourner la période telle quelle
    return $periode;
}

// Fonction pour extraire la DERNIÈRE période d'une chaîne de périodes
function getDernierePeriodeDeChaine($periodeChaine) {
    $periodes = explode(' - ', $periodeChaine);
    $dernierePeriode = end($periodes);
    
    if (strpos($dernierePeriode, 'AVANCE') !== false) {
        preg_match('/AVANCE \d+ SUR (.+)/', $dernierePeriode, $matches);
        if (count($matches) >= 2) {
            return $matches[1];
        }
    }
    
    if (strpos($dernierePeriode, 'SOLDE') !== false) {
        preg_match('/SOLDE \d+ SUR (.+)/', $dernierePeriode, $matches);
        if (count($matches) >= 2) {
            return $matches[1];
        }
    }
    
    return $dernierePeriode;
}

// Fonction pour extraire le montant d'une avance ou solde
function extraireMontantAvanceOuSolde($periodeChaine) {
    $periodes = explode(' - ', $periodeChaine);
    $dernierePeriode = end($periodes);
    
    if (strpos($dernierePeriode, 'AVANCE') !== false) {
        preg_match('/AVANCE (\d+)/', $dernierePeriode, $matches);
        if (count($matches) >= 2) {
            return floatval($matches[1]);
        }
    }
    
    if (strpos($dernierePeriode, 'SOLDE') !== false) {
        preg_match('/SOLDE (\d+)/', $dernierePeriode, $matches);
        if (count($matches) >= 2) {
            return floatval($matches[1]);
        }
    }
    
    return 0;
}

// Fonction pour vérifier si le dernier paiement est soldé
function dernierPaiementEstSolde($dernierePeriodeChaine, $montantDernierPaiement, $mensualite) {
    $periodes = explode(' - ', $dernierePeriodeChaine);
    $dernierePeriode = end($periodes);
    
    // Si la dernière période contient "AVANCE" ou "SOLDE", le mois n'est pas soldé
    if (strpos($dernierePeriode, 'AVANCE') !== false || strpos($dernierePeriode, 'SOLDE') !== false) {
        return false;
    }
    
    // Si pas d'AVANCE ni SOLDE dans la dernière période, c'est un mois complet donc soldé
    return true;
}

// Fonction pour convertir une date YYYY-MM en période française
function convertirDateEnPeriode($date_debut) {
    if (empty($date_debut) || $date_debut == '0000-00') {
        return null;
    }
    
    $moisFrancais = [
        1 => 'JANVIER', 2 => 'FEVRIER', 3 => 'MARS', 4 => 'AVRIL', 5 => 'MAI', 6 => 'JUIN',
        7 => 'JUILLET', 8 => 'AOUT', 9 => 'SEPTEMBRE', 10 => 'OCTOBRE', 11 => 'NOVEMBRE', 12 => 'DECEMBRE'
    ];
    
    $parts = explode('-', $date_debut);
    if (count($parts) == 2) {
        $annee = intval($parts[0]);
        $mois = intval($parts[1]);
        
        if (isset($moisFrancais[$mois]) && $annee > 0) {
            return $moisFrancais[$mois] . ' ' . $annee;
        }
    }
    
    return null;
}

// Calculer la période de départ et le reste à payer
$periodeDepart = "";
$resteAPayer = 0;
$detailCalcul = "";

if ($lastPayment) {
    $dernierePeriodeChaine = $lastPayment['periode'];
    $montantDernierPaiement = floatval($lastPayment['montant']);
    
    // Extraire la dernière période
    $dernierePeriode = getDernierePeriodeDeChaine($dernierePeriodeChaine);
    
    // Vérifier si le dernier paiement est soldé
    $estSolde = dernierPaiementEstSolde($dernierePeriodeChaine, $montantDernierPaiement, $mensualite);
    
    if ($estSolde) {
        // Le dernier paiement est soldé, on passe au mois suivant
        $periodeDepart = getProchainePeriode($dernierePeriode);
        $resteAPayer = 0;
        $detailCalcul = "Le dernier paiement de <strong>{$dernierePeriode}</strong> est soldé. Le prochain paiement commence à <strong>{$periodeDepart}</strong>.";
    } else {
        // Le dernier paiement n'est pas soldé, on doit d'abord solder le mois en cours
        $montantAvanceOuSolde = extraireMontantAvanceOuSolde($dernierePeriodeChaine);
        $resteAPayer = $mensualite - $montantAvanceOuSolde;
        $periodeDepart = $dernierePeriode;
        $detailCalcul = "Le mois de <strong>{$dernierePeriode}</strong> n'est pas soldé. Il reste <strong class='text-danger'>{$resteAPayer} Frs</strong> à payer avant de passer au mois suivant.";
    }
    
} else {
    // Si aucun paiement précédent, utiliser la date de début de l'établissement
    if ($debut_ets && $debut_ets != '0000-00') {
        $periodeDepart = convertirDateEnPeriode($debut_ets);
        if ($periodeDepart) {
            $resteAPayer = 0;
            $detailCalcul = "Premier paiement pour cet établissement. Le paiement commence à <strong>{$periodeDepart}</strong> (selon la date de début du contrat).";
        } else {
            // Si la conversion échoue, utiliser le mois courant
            $moisFrancais = [
                1 => 'JANVIER', 2 => 'FEVRIER', 3 => 'MARS', 4 => 'AVRIL', 5 => 'MAI', 6 => 'JUIN',
                7 => 'JUILLET', 8 => 'AOUT', 9 => 'SEPTEMBRE', 10 => 'OCTOBRE', 11 => 'NOVEMBRE', 12 => 'DECEMBRE'
            ];
            $moisActuel = (int)date('n');
            $anneeActuelle = date('Y');
            $periodeDepart = $moisFrancais[$moisActuel] . ' ' . $anneeActuelle;
            $resteAPayer = 0;
            $detailCalcul = "Premier paiement pour cet établissement. Le paiement commence à <strong>{$periodeDepart}</strong> (mois courant - date de début non définie).";
        }
    } else {
        // Si aucune date de début définie, utiliser le mois courant
        $moisFrancais = [
            1 => 'JANVIER', 2 => 'FEVRIER', 3 => 'MARS', 4 => 'AVRIL', 5 => 'MAI', 6 => 'JUIN',
            7 => 'JUILLET', 8 => 'AOUT', 9 => 'SEPTEMBRE', 10 => 'OCTOBRE', 11 => 'NOVEMBRE', 12 => 'DECEMBRE'
        ];
        $moisActuel = (int)date('n');
        $anneeActuelle = date('Y');
        $periodeDepart = $moisFrancais[$moisActuel] . ' ' . $anneeActuelle;
        $resteAPayer = 0;
        $detailCalcul = "Premier paiement pour cet établissement. Le paiement commence à <strong>{$periodeDepart}</strong> (mois courant - date de début non définie).";
    }
}

if(isset($_POST['payer'])) {
    $quittance = htmlspecialchars(stripcslashes($_POST['quittance']));
    $ets = htmlspecialchars(stripcslashes($_POST['ets']));
    $codeagt = htmlspecialchars(stripcslashes($_POST['codeagt']));
    $montant = (float)htmlspecialchars(stripcslashes($_POST['montant']));
    $modepay = htmlspecialchars(stripcslashes($_POST['modepay']));
    $referencepay = htmlspecialchars(stripcslashes($_POST['referencepay']));
    $periode = htmlspecialchars(stripcslashes($_POST['periode']));
    $datepay = htmlspecialchars(stripcslashes($_POST['datepay']));
    $agent = htmlspecialchars(stripcslashes($_POST['agent']));
    
    if(!empty($_POST['quittance']) AND !empty($_POST['montant']) AND !empty($_POST['periode']) AND !empty($_POST['datepay'])) {
        $pay = $bdd->prepare('INSERT INTO PAYEMENT (quittance, id_ets, agt_id, montant, mode, reference, periode, agent, dates) VALUES(?,?,?,?,?,?,?,?,?)');
        $pay->execute(array($quittance, $ets, $codeagt, $montant, $modepay, $referencepay, strtoupper($periode), $agent, $datepay));
        
        $_SESSION['quittance'] = $_POST['quittance'];
        $_SESSION['ets'] = $_POST['ets'];
        $_SESSION['montant'] = $_POST['montant'];
        $_SESSION['modepay'] = $_POST['modepay'];
        $_SESSION['referencepay'] = $_POST['referencepay'];
        $_SESSION['periode'] = $periode;
        $_SESSION['datepay'] = $_POST['datepay'];
        $_SESSION['agent'] = $_POST['agent'];
        $_SESSION['nom_ets'] = $_GET['nom'];
        $_SESSION['ville_ets'] = $_GET['ville'];
        $_SESSION['client_ets'] = $_GET['client'];
        $_SESSION['paiement_success'] = true;
        
        // Rediriger vers la même page pour afficher la modale
        header("Location: " . $_SERVER['PHP_SELF'] . "?affichclient=" . $ets . "&nom=" . urlencode($_GET['nom']) . "&ville=" . urlencode($_GET['ville']) . "&client=" . urlencode($_GET['client']) . "&mensuel=" . $mensualite . "&success=1");
        exit();
    } else {
        $erreur = "Veuillez bien remplir tous les champs obligatoires avant d'effectuer le paiement. Merci!";
    }
}

// Vérifier si on vient de faire un paiement réussi
$paiementReussi = isset($_GET['success']) && $_GET['success'] == '1' && isset($_SESSION['paiement_success']);
if ($paiementReussi) {
    unset($_SESSION['paiement_success']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Paiement - Burida</title>
    <style>
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
        }
        .btn-black:hover {
            background-color: #333333;
            border-color: #333333;
            color: white;
        }
        .card-header {
            background: linear-gradient(135deg, #ff8c00 0%, #ff6600 100%);
            color: white;
        }
        .info-card {
            border-left: 4px solid #ff9800;
            background: #f8f9fa;
        }
        .form-control:focus {
            border-color: #ff9800;
            box-shadow: 0 0 0 0.2rem rgba(255, 152, 0, 0.25);
        }
        .radio-group {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        .radio-option {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .radio-option:hover {
            border-color: #ff9800;
        }
        .radio-option.selected {
            border-color: #ff9800;
            background-color: rgba(255, 152, 0, 0.1);
        }
        .radio-option input[type="radio"] {
            margin-right: 8px;
            cursor: pointer;
        }
        .radio-option label {
            cursor: pointer;
            margin-bottom: 0;
        }
        .text-orange {
            color: #ff9800 !important;
        }
        .alert-custom {
            border-left: 4px solid #dc3545;
        }
        .periode-info {
            background: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .periode-info.warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        .periode-info.success {
            background: #d4edda;
            border-left: 4px solid #28a745;
        }
        .montant-info {
            font-size: 0.9em;
            color: #6c757d;
            margin-top: 5px;
        }
        .periode-field {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #495057;
        }
        .calcul-detail {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 6px;
            margin-top: 10px;
            border-left: 3px solid #ff9800;
        }
        /* CACHER LA CARTE DEBUT CONTRAT */
        .info-debut {
            display: none;
        }
        @media (max-width: 768px) {
            .radio-group {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body class="bg-light">
<div class="container py-4">
    <!-- En-tête avec bouton retour -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <a href="recherchepay.php" class="btn btn-black btn-sm">
                    <i class="bi bi-arrow-left me-2"></i>Retour
                </a>
                <div class="text-center">
                    <a href="recherchepay.php">
                        <img src="burida.jfif" alt="LOGO BURIDA" height="80" width="160" class="img-fluid">
                    </a>
                </div>
                <div style="width: 100px;"></div>
            </div>
        </div>
    </div>

    <!-- Carte principale -->
    <div class="card shadow-lg">
        <div class="card-header text-center py-3">
            <h4 class="mb-0">
                <i class="bi bi-credit-card me-2"></i>
                ENREGISTREMENT DE PAIEMENT
            </h4>
        </div>
        
        <div class="card-body">
            <!-- Informations de l'établissement -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="info-card p-3 rounded">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <strong><i class="bi bi-building text-orange me-2"></i>Établissement :</strong><br>
                                <span class="text-dark"><?= htmlspecialchars($_GET['nom']); ?></span>
                            </div>
                            <div class="col-md-3 mb-2">
                                <strong><i class="bi bi-geo-alt text-orange me-2"></i>Localité :</strong><br>
                                <span class="text-dark"><?= htmlspecialchars($_GET['ville']); ?></span>
                            </div>
                            <div class="col-md-3 mb-2">
                                <strong><i class="bi bi-person text-orange me-2"></i>Responsable :</strong><br>
                                <span class="text-dark"><?= htmlspecialchars($_GET['client']); ?></span>
                            </div>
                            <div class="col-md-3 mb-2">
                                <strong><i class="bi bi-currency-dollar text-orange me-2"></i>Mensualité :</strong><br>
                                <span class="text-success fw-bold"><?= number_format($mensualite, 0, ',', ' '); ?> Frs</span>
                            </div>
                        </div>
                        <!-- CARTE DEBUT CONTRAT CACHÉE -->
                        <?php if ($debut_ets && $debut_ets != '0000-00'): ?>
                        <div class="info-debut mt-2">
                            <i class="bi bi-calendar-check text-success me-2"></i>
                            <strong>Date de début du contrat :</strong> 
                            <span class="fw-bold"><?= convertirDateEnPeriode($debut_ets) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Information du dernier paiement et calcul -->
            <?php if($lastPayment): ?>
            <div class="periode-info <?= $resteAPayer > 0 ? 'warning' : 'success' ?> mb-4">
                <div class="row">
                    <div class="col-md-6">
                        <strong><i class="bi bi-calendar-check <?= $resteAPayer > 0 ? 'text-warning' : 'text-success' ?> me-2"></i>Dernier paiement :</strong><br>
                        <span class="fw-bold"><?= htmlspecialchars($lastPayment['periode']); ?></span><br>
                        <small>Montant : <?= number_format($lastPayment['montant'], 0, ',', ' '); ?> Frs</small>
                    </div>
                    <div class="col-md-6">
                        <strong><i class="bi bi-calendar-date text-primary me-2"></i>Date :</strong><br>
                        <span><?= htmlspecialchars($lastPayment['dates']); ?></span>
                    </div>
                </div>
                
                <div class="calcul-detail mt-3">
                    <strong><i class="bi bi-calculator text-orange me-2"></i>Analyse du paiement :</strong><br>
                    <p class="mb-0 mt-2"><?= $detailCalcul ?></p>
                </div>
            </div>
            <?php else: ?>
            <div class="periode-info mb-4">
                <i class="bi bi-info-circle text-primary me-2"></i>
                <strong>Premier paiement pour cet établissement</strong><br>
                <div class="calcul-detail mt-2">
                    <?= $detailCalcul ?>
                </div>
                <!-- MESSAGE DEBUT CONTRAT CACHÉ -->
                <?php if ($debut_ets && $debut_ets != '0000-00'): ?>
                <div class="info-debut mt-2">
                    <i class="bi bi-info-circle text-success me-2"></i>
                    Le premier paiement commence à partir de la date de début du contrat.
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Message d'erreur -->
            <?php if(isset($erreur)): ?>
            <div class="alert alert-danger alert-custom d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                <div><?= $erreur ?></div>
            </div>
            <?php endif; ?>

            <!-- Formulaire de paiement -->
            <form method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="ets" value="<?= $_GET['affichclient']; ?>">
                <input type="hidden" name="codeagt" value="<?= $_SESSION['id']; ?>">
                <input type="hidden" name="agent" value="<?= $agent; ?>">
                <input type="hidden" id="periodeDepart" value="<?= $periodeDepart ?>">
                <input type="hidden" id="mensualite" value="<?= $mensualite ?>">
                <input type="hidden" id="resteAPayer" value="<?= $resteAPayer ?>">

                <div class="row g-3">
                    <!-- Numéro de quittance -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold">
                            <i class="bi bi-receipt text-orange me-1"></i>
                            N° QUITTANCE <span class="text-danger">*</span>
                        </label>
                        <input type="number" name="quittance" class="form-control form-control-lg" 
                               autocomplete="off" required>
                        <div class="invalid-feedback">
                            Veuillez saisir le numéro de quittance.
                        </div>
                    </div>

                    <!-- Montant payé -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold">
                            <i class="bi bi-cash-coin text-orange me-1"></i>
                            MONTANT PAYÉ (Frs) <span class="text-danger">*</span>
                        </label>
                        <input type="number" name="montant" id="montantInput" class="form-control form-control-lg" 
                               autocomplete="off" required step="0.01" min="0"
                               oninput="calculerPeriodeAuto()">
                        <div class="invalid-feedback">
                            Veuillez saisir le montant payé.
                        </div>
                        <div class="montant-info" id="infoMois"></div>
                    </div>

                    <!-- Mode de paiement -->
                    <div class="col-12">
                        <label class="form-label fw-bold">
                            <i class="bi bi-credit-card-2-front text-orange me-1"></i>
                            MODE DE PAIEMENT <span class="text-danger">*</span>
                        </label>
                        <div class="radio-group">
                            <div class="radio-option" onclick="selectMode('ESPECE')">
                                <input type="radio" name="modepay" id="espece" value="ESPECE" checked 
                                       onchange="toggleReference()">
                                <label for="espece">
                                    <i class="bi bi-cash text-success me-1"></i>ESPÈCE
                                </label>
                            </div>
                            <div class="radio-option" onclick="selectMode('CHEQUE')">
                                <input type="radio" name="modepay" id="cheque" value="CHEQUE" 
                                       onchange="toggleReference()">
                                <label for="cheque">
                                    <i class="bi bi-bank text-primary me-1"></i>CHÈQUE
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Référence du chèque -->
                    <div class="col-12" id="referenceField" style="display: none;">
                        <label class="form-label fw-bold">
                            <i class="bi bi-hash text-orange me-1"></i>
                            RÉFÉRENCE DU CHÈQUE
                        </label>
                        <input type="text" name="referencepay" class="form-control" 
                               placeholder="Ex: NSIA 5445654" autocomplete="off">
                    </div>

                    <!-- Période payée -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold">
                            <i class="bi bi-calendar-range text-orange me-1"></i>
                            PÉRIODE PAYÉE <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="periode" id="periodeInput" class="form-control periode-field" 
                               readonly required>
                        <div class="invalid-feedback">
                            La période est requise.
                        </div>
                        <small class="text-muted" id="periodeDescription">
                            La période sera calculée automatiquement
                        </small>
                    </div>

                    <!-- Date de paiement -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold">
                            <i class="bi bi-calendar-date text-orange me-1"></i>
                            DATE DE PAIEMENT <span class="text-danger">*</span>
                        </label>
                        <input type="date" name="datepay" class="form-control" required
                               value="<?= date('Y-m-d') ?>">
                        <div class="invalid-feedback">
                            Veuillez sélectionner la date de paiement.
                        </div>
                    </div>

                    <!-- Bouton de soumission -->
                    <div class="col-12 text-center mt-4">
                        <button type="submit" name="payer" class="btn btn-orange btn-lg px-5">
                            <i class="bi bi-check-circle me-2"></i>
                            ENREGISTRER LE PAIEMENT
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modale de succès -->
<?php if ($paiementReussi): ?>
<div class="modal fade show" id="successModal" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    Paiement Enregistré avec Succès
                </h5>
            </div>
            <div class="modal-body text-center py-4">
                <div class="mb-3">
                    <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
                </div>
                <h5 class="mb-3">Détails du paiement</h5>
                <div class="text-start">
                    <p><strong>Quittance N° :</strong> <?= htmlspecialchars($_SESSION['quittance']) ?></p>
                    <p><strong>Établissement :</strong> <?= htmlspecialchars($_SESSION['nom_ets']) ?></p>
                    <p><strong>Montant :</strong> <span class="text-success fw-bold"><?= number_format($_SESSION['montant'], 0, ',', ' ') ?> Frs</span></p>
                    <p><strong>Période :</strong> <?= htmlspecialchars($_SESSION['periode']) ?></p>
                    <p><strong>Mode :</strong> <?= htmlspecialchars($_SESSION['modepay']) ?></p>
                    <p><strong>Date :</strong> <?= htmlspecialchars($_SESSION['datepay']) ?></p>
                </div>
            </div>
            <div class="modal-footer justify-content-center">
                <a href="recherchepay.php" class="btn btn-orange btn-lg px-4">
                    <i class="bi bi-arrow-left me-2"></i>
                    Retour à la Liste
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
    // Fonction pour basculer l'affichage du champ référence
    function toggleReference() {
        const chequeRadio = document.getElementById('cheque');
        const referenceField = document.getElementById('referenceField');
        
        if (chequeRadio.checked) {
            referenceField.style.display = 'block';
            referenceField.querySelector('input').setAttribute('required', 'required');
        } else {
            referenceField.style.display = 'none';
            referenceField.querySelector('input').removeAttribute('required');
            referenceField.querySelector('input').value = '';
        }
    }

    // Fonction pour sélectionner le mode de paiement
    function selectMode(mode) {
        const especeOption = document.querySelector('.radio-option:first-child');
        const chequeOption = document.querySelector('.radio-option:last-child');
        
        if (mode === 'ESPECE') {
            document.getElementById('espece').checked = true;
            especeOption.classList.add('selected');
            chequeOption.classList.remove('selected');
        } else {
            document.getElementById('cheque').checked = true;
            chequeOption.classList.add('selected');
            especeOption.classList.remove('selected');
        }
        toggleReference();
    }

    // Fonction pour obtenir la prochaine période
    function getProchainePeriode(periode) {
        const moisFrancais = {
            'JANVIER': 0, 'FEVRIER': 1, 'MARS': 2, 'AVRIL': 3, 'MAI': 4, 'JUIN': 5,
            'JUILLET': 6, 'AOUT': 7, 'SEPTEMBRE': 8, 'OCTOBRE': 9, 'NOVEMBRE': 10, 'DECEMBRE': 11
        };
        
        const moisNoms = ['JANVIER', 'FEVRIER', 'MARS', 'AVRIL', 'MAI', 'JUIN', 
                         'JUILLET', 'AOUT', 'SEPTEMBRE', 'OCTOBRE', 'NOVEMBRE', 'DECEMBRE'];
        
        const parts = periode.trim().split(' ');
        const moisDebut = parts[0].toUpperCase();
        let anneeDebut = parseInt(parts[1]) || new Date().getFullYear();
        
        if (!moisFrancais.hasOwnProperty(moisDebut)) {
            console.error('Mois invalide:', moisDebut);
            return periode;
        }
        
        const indexMoisDebut = moisFrancais[moisDebut];
        let indexMoisSuivant = (indexMoisDebut + 1) % 12;
        let anneeSuivante = anneeDebut;
        
        // Si on passe de décembre à janvier, incrémenter l'année
        if (indexMoisDebut === 11) {
            anneeSuivante++;
        }
        
        const moisSuivant = moisNoms[indexMoisSuivant];
        
        return moisSuivant + ' ' + anneeSuivante;
    }

    // Fonction pour calculer la période avec reste
    function calculerPeriodeAvecReste(periodeDebut, montant, mensualite, resteAPayer) {
        let montantRestant = montant;
        let periodes = [];
        let periodeActuelle = periodeDebut.trim();
        
        // Validation des entrées
        if (!periodeActuelle || montant <= 0 || mensualite <= 0) {
            return { periode: '', info: '', description: '' };
        }
        
        // Si il y a un reste à payer du mois précédent
        if (resteAPayer > 0) {
            if (montantRestant >= resteAPayer) {
                // On solde d'abord le mois en cours
                periodes.push('SOLDE ' + Math.round(resteAPayer) + ' SUR ' + periodeActuelle);
                montantRestant -= resteAPayer;
                periodeActuelle = getProchainePeriode(periodeActuelle);
            } else {
                // Le montant ne suffit pas pour solder le mois en cours
                const nouveauReste = Math.round(resteAPayer - montantRestant);
                const avance = Math.round(montantRestant);
                return {
                    periode: 'AVANCE ' + avance + ' SUR ' + periodeActuelle,
                    info: '<i class="bi bi-exclamation-triangle text-warning me-1"></i>Avance partielle',
                    description: '<span class="text-warning">Il restera encore ' + nouveauReste.toLocaleString('fr-FR') + ' Frs à payer sur ' + periodeActuelle + '</span>'
                };
            }
        }
        
        // Calculer combien de mois complets peuvent être payés avec le montant restant
        const nbMoisComplets = Math.floor(montantRestant / mensualite);
        const reste = Math.round(montantRestant % mensualite);
        
        // Ajouter les mois complets
        for (let i = 0; i < nbMoisComplets; i++) {
            periodes.push(periodeActuelle);
            periodeActuelle = getProchainePeriode(periodeActuelle);
        }
        
        // Gérer le reste (avance)
        if (reste > 0 && reste < mensualite) {
            periodes.push('AVANCE ' + reste + ' SUR ' + periodeActuelle);
        }
        
        // Construire la période affichée
        if (periodes.length === 0) {
            return { periode: '', info: '', description: '' };
        } else if (periodes.length === 1) {
            const periode = periodes[0];
            if (periode.startsWith('AVANCE')) {
                const match = periode.match(/AVANCE (\d+)/);
                const montantAvance = match ? match[1] : '0';
                const moisAvance = periode.split('SUR ')[1];
                return {
                    periode: periode,
                    info: '<i class="bi bi-exclamation-triangle text-warning me-1"></i>Avance sur le mois',
                    description: '<span class="text-warning">Avance de ' + parseInt(montantAvance).toLocaleString('fr-FR') + ' Frs sur ' + moisAvance + '</span>'
                };
            } else if (periode.startsWith('SOLDE')) {
                const match = periode.match(/SOLDE (\d+)/);
                const montantSolde = match ? match[1] : '0';
                const moisSolde = periode.split('SUR ')[1];
                return {
                    periode: periode,
                    info: '<i class="bi bi-check-circle text-success me-1"></i>Solde du mois',
                    description: '<span class="text-success">Solde de ' + parseInt(montantSolde).toLocaleString('fr-FR') + ' Frs sur ' + moisSolde + '</span>'
                };
            } else {
                return {
                    periode: periode,
                    info: '<i class="bi bi-check-circle text-success me-1"></i>Paiement complet',
                    description: '<span class="text-success">Paiement pour ' + periode + '</span>'
                };
            }
        } else {
            const periodeAffichee = periodes.join(' - ');
            let description = '';
            let info = '';
            
            // Compter les mois soldés et les avances
            let nbMoisSoldes = 0;
            let hasAvance = false;
            let hasSolde = false;
            
            for (let p of periodes) {
                if (p.startsWith('SOLDE')) {
                    hasSolde = true;
                    nbMoisSoldes++;
                } else if (p.startsWith('AVANCE')) {
                    hasAvance = true;
                } else {
                    nbMoisSoldes++;
                }
            }
            
            // Construire la description
            if (hasSolde && resteAPayer > 0) {
                const nbMoisCompletsApres = nbMoisSoldes - 1;
                if (nbMoisCompletsApres > 0) {
                    description = '<span class="text-success">Solde du mois précédent + ' + nbMoisCompletsApres + ' mois complet' + (nbMoisCompletsApres > 1 ? 's' : '') + '</span>';
                    info = '<i class="bi bi-check-circle text-success me-1"></i>Solde + ' + nbMoisCompletsApres + ' mois';
                } else {
                    description = '<span class="text-success">Solde du mois précédent</span>';
                    info = '<i class="bi bi-check-circle text-success me-1"></i>Solde du mois';
                }
            } else {
                description = '<span class="text-success">Paiement de ' + nbMoisSoldes + ' mois complet' + (nbMoisSoldes > 1 ? 's' : '') + '</span>';
                info = '<i class="bi bi-check-circle text-success me-1"></i>' + nbMoisSoldes + ' mois';
            }
            
            if (hasAvance) {
                description += ' + <span class="text-warning">avance partielle</span>';
                info += ' + avance';
            }
            
            return {
                periode: periodeAffichee,
                info: info,
                description: description
            };
        }
    }

    // Fonction pour calculer automatiquement la période
    function calculerPeriodeAuto() {
        const montantInput = document.getElementById('montantInput');
        const periodeInput = document.getElementById('periodeInput');
        const infoMois = document.getElementById('infoMois');
        const periodeDescription = document.getElementById('periodeDescription');
        const periodeDepart = document.getElementById('periodeDepart').value;
        const mensualite = parseFloat(document.getElementById('mensualite').value);
        const resteAPayer = parseFloat(document.getElementById('resteAPayer').value);
        
        const montant = parseFloat(montantInput.value) || 0;
        
        if (montant === 0 || isNaN(montant)) {
            periodeInput.value = '';
            infoMois.innerHTML = '';
            periodeDescription.textContent = 'La période sera calculée automatiquement';
            return;
        }
        
        if (mensualite > 0 && montant > 0 && periodeDepart) {
            let resultat = calculerPeriodeAvecReste(periodeDepart, montant, mensualite, resteAPayer);
            
            periodeInput.value = resultat.periode;
            infoMois.innerHTML = resultat.info;
            periodeDescription.innerHTML = resultat.description;
        } else {
            periodeInput.value = '';
            infoMois.innerHTML = '<i class="bi bi-exclamation-circle text-danger me-1"></i>Données invalides';
            periodeDescription.innerHTML = '<span class="text-danger">Impossible de calculer la période</span>';
        }
    }

    // Initialisation au chargement de la page
    document.addEventListener('DOMContentLoaded', function() {
        // Sélectionner le mode ESPECE par défaut
        selectMode('ESPECE');
        
        // Calculer la période si un montant est déjà présent
        const montantInitial = document.getElementById('montantInput').value;
        if (montantInitial && parseFloat(montantInitial) > 0) {
            calculerPeriodeAuto();
        }
        
        // Validation Bootstrap
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });

        // Gestion de la modale de succès
        <?php if ($paiementReussi): ?>
        // Empêcher la fermeture de la modale en cliquant à l'extérieur
        const successModal = document.getElementById('successModal');
        if (successModal) {
            successModal.addEventListener('click', function(e) {
                if (e.target === this) {
                    e.stopPropagation();
                }
            });
        }
        <?php endif; ?>
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>