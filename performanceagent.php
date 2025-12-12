<?php
session_start();
require("bdburida.php");
require("conditionconnexion.php");

// Récupérer le bureau de la session
$bureau_session = $_SESSION['bureau'];

// Traitement des filtres de date
$date_debut = isset($_POST['date_debut']) ? $_POST['date_debut'] : date('Y-m-01');
$date_fin = isset($_POST['date_fin']) ? $_POST['date_fin'] : date('Y-m-d');

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

// Récupérer tous les agents distincts avec leurs données consolidées
$agents_data = [];
$total_percu_global = 0;
$nombre_agents_global = 0;

try {
    // Récupérer tous les agents distincts
    $req_agents = $bdd->prepare('SELECT DISTINCT agent FROM ETABLISSEMENT WHERE agent IS NOT NULL AND agent != "" ORDER BY agent');
    $req_agents->execute();
    $tous_agents = $req_agents->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tous_agents as $agent) {
        // Calculer le total perçu par l'agent dans la période (tous bureaux confondus)
        $req_total_agent = $bdd->prepare('
            SELECT SUM(p.montant) as total_percu
            FROM PAYEMENT p
            INNER JOIN ETABLISSEMENT e ON p.id_ets = e.id_ets
            WHERE e.agent = ? AND p.dates BETWEEN ? AND ?
        ');
        $req_total_agent->execute([$agent, $date_debut, $date_fin]);
        $result = $req_total_agent->fetch(PDO::FETCH_ASSOC);
        $total_percu = $result['total_percu'] ?? 0;
        
        // Compter le nombre total d'établissements de l'agent
        $req_count_ets = $bdd->prepare('SELECT COUNT(*) as count FROM ETABLISSEMENT WHERE agent = ?');
        $req_count_ets->execute([$agent]);
        $result_count = $req_count_ets->fetch(PDO::FETCH_ASSOC);
        $nombre_ets = $result_count['count'] ?? 0;
        
        // Récupérer le bureau principal de l'agent (le plus fréquent)
        $req_bureau_agent = $bdd->prepare('
            SELECT bureau, COUNT(*) as count 
            FROM ETABLISSEMENT 
            WHERE agent = ? AND bureau IS NOT NULL AND bureau != ""
            GROUP BY bureau 
            ORDER BY count DESC 
            LIMIT 1
        ');
        $req_bureau_agent->execute([$agent]);
        $result_bureau = $req_bureau_agent->fetch(PDO::FETCH_ASSOC);
        $bureau_principal = $result_bureau['bureau'] ?? 'Non assigné';
        
        // Calculer le potentiel total de l'agent (somme des mensualités de tous ses établissements)
        $req_potentiel = $bdd->prepare('
            SELECT SUM(CAST(REPLACE(REPLACE(mensualite, " FCFA", ""), " ", "") AS UNSIGNED)) as potentiel 
            FROM ETABLISSEMENT 
            WHERE agent = ?
        ');
        $req_potentiel->execute([$agent]);
        $result_potentiel = $req_potentiel->fetch(PDO::FETCH_ASSOC);
        $potentiel = $result_potentiel['potentiel'] ?? 0;
        
        // Calculer le taux de réalisation
        $taux_realisation = $potentiel > 0 ? ($total_percu / $potentiel) * 100 : 0;
        
        $agents_data[] = [
            'agent' => $agent,
            'bureau' => $bureau_principal,
            'total_percu' => $total_percu,
            'nombre_ets' => $nombre_ets,
            'potentiel' => $potentiel,
            'taux_realisation' => $taux_realisation
        ];
        
        $total_percu_global += $total_percu;
        $nombre_agents_global++;
    }
} catch(Exception $e) {
    error_log("Erreur récupération agents: " . $e->getMessage());
}

// Trier les agents par montant perçu (décroissant)
usort($agents_data, function($a, $b) {
    return $b['total_percu'] - $a['total_percu'];
});

// Calculer les statistiques par bureau
$stats_bureaux = [];
foreach ($bureaux as $bureau) {
    $total_percu_bureau = 0;
    $nombre_agents_bureau = 0;
    $nombre_ets_bureau = 0;
    
    foreach ($agents_data as $agent_data) {
        if ($agent_data['bureau'] == $bureau) {
            $total_percu_bureau += $agent_data['total_percu'];
            $nombre_agents_bureau++;
            $nombre_ets_bureau += $agent_data['nombre_ets'];
        }
    }
    
    $stats_bureaux[$bureau] = [
        'total_percu' => $total_percu_bureau,
        'nombre_agents' => $nombre_agents_bureau,
        'nombre_ets' => $nombre_ets_bureau
    ];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Synthèse des Perceptions des Agents</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
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
        
        .agent-active {
            background-color: rgba(255, 152, 0, 0.05);
        }
        
        .btn-outline-orange {
            border-color: var(--orange);
            color: var(--orange);
        }
        
        .btn-outline-orange:hover {
            background-color: var(--orange);
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
        
        .ranking-badge {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: bold;
        }
        
        .ranking-1 { background-color: #ffd700; color: #000; }
        .ranking-2 { background-color: #c0c0c0; color: #000; }
        .ranking-3 { background-color: #cd7f32; color: #000; }
        .ranking-other { background-color: #6c757d; color: #fff; }
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
                                <i class="bi bi-people me-2"></i>
                                SYNTHESE DES AGENTS
                            </h1>
                            <p class="mb-0 mt-1 small">Classement des agents par perception</p>
                        </div>
                    </div>
                    <div class="col-md-6 text-center text-md-end">
                        <div class="btn-group">
                            <button class="btn export-btn me-2" onclick="exporterTableau()">
                                <i class="bi bi-download me-1"></i> Exporter
                            </button>
                            <a href="page_dr.php?id=<?php echo $_SESSION['id']; ?>" class="btn btn-black">
                                <i class="bi bi-arrow-left me-1"></i> Retour
                            </a>
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
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </form>
            </div>

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
                                        <div class="stats-value text-orange"><?php echo count($bureaux); ?></div>
                                        <div class="stats-label">Bureaux</div>
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
                                        <i class="bi bi-people stats-icon text-black"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="stats-value text-black"><?php echo $nombre_agents_global; ?></div>
                                        <div class="stats-label">Agents</div>
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
                                        <i class="bi bi-cash-coin stats-icon text-purple"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="stats-value text-purple"><?php echo number_format($total_percu_global, 0, ',', ' '); ?></div>
                                        <div class="stats-label">Total perçu</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 compact-col">
                        <div class="card stats-card card-success h-100">
                            <div class="card-body p-2">
                                <div class="d-flex align-items-center">
                                    <div class="me-2">
                                        <i class="bi bi-graph-up-arrow stats-icon text-success"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="stats-value text-success"><?php echo number_format($total_percu_global / max($nombre_agents_global, 1), 0, ',', ' '); ?></div>
                                        <div class="stats-label">Moyenne par agent</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 compact-col">
                        <div class="card stats-card card-info h-100">
                            <div class="card-body p-2">
                                <div class="d-flex align-items-center">
                                    <div class="me-2">
                                        <i class="bi bi-calendar-check stats-icon text-info"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="stats-value text-info"><?php echo $date_debut && $date_fin ? (new DateTime($date_debut))->diff(new DateTime($date_fin))->days + 1 : 0; ?></div>
                                        <div class="stats-label">Jours dans la période</div>
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
                                        <i class="bi bi-percent stats-icon text-warning"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="stats-value text-warning"><?php echo number_format($total_percu_global / max($total_percu_global, 1) * 100, 1); ?>%</div>
                                        <div class="stats-label">Taux de réalisation</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tableau des Agents -->
            <div class="table-container">
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 text-orange">
                                <i class="bi bi-table me-2"></i>
                                CLASSEMENT DES AGENTS PAR PERCEPTION
                                <?php if ($date_debut && $date_fin): ?>
                                <small class="text-muted">(<?php echo date('d/m/Y', strtotime($date_debut)); ?> - <?php echo date('d/m/Y', strtotime($date_fin)); ?>)</small>
                                <?php endif; ?>
                            </h5>
                            <span class="badge bg-secondary small">
                                <i class="bi bi-info-circle me-1"></i>
                                <?php echo $nombre_agents_global; ?> agents
                            </span>
                        </div>
                    </div>
                    
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-sm mb-0" id="tableauAgents">
                                <thead>
                                    <tr>
                                        <th width="30">RANG</th>
                                        <th>AGENT</th>
                                        <th>BUREAU PRINCIPAL</th>
                                        <th class="text-center">NOMBRE ETS</th>
                                        <th class="montant-cell">POTENTIEL</th>
                                        <th class="montant-cell">PERÇU</th>
                                        <th>TAUX RÉALISATION</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (empty($agents_data)) {
                                        echo '<tr>
                                            <td colspan="7" class="text-center text-muted py-3">
                                                <i class="bi bi-people-x fs-1 d-block mb-2"></i>
                                                Aucun agent trouvé
                                            </td>
                                        </tr>';
                                    } else {
                                        $rang = 1;
                                        foreach ($agents_data as $agent_data) {
                                            // Classes pour les taux de réalisation
                                            $taux_class = getTauxClass($agent_data['taux_realisation']);
                                            
                                            // Classes pour le rang
                                            $rang_class = '';
                                            if ($rang == 1) $rang_class = 'ranking-1';
                                            elseif ($rang == 2) $rang_class = 'ranking-2';
                                            elseif ($rang == 3) $rang_class = 'ranking-3';
                                            else $rang_class = 'ranking-other';
                                            
                                            $is_current_agent = ($agent_data['agent'] == $_SESSION['nom']);
                                            $row_class = $is_current_agent ? 'agent-active' : '';
                                            
                                            echo '<tr class="' . $row_class . '">
                                                <td class="text-center">
                                                    <div class="ranking-badge ' . $rang_class . ' mx-auto">
                                                        ' . $rang . '
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-person me-1 text-orange small"></i>
                                                        <span class="text-truncate" style="max-width: 150px;" title="' . htmlspecialchars($agent_data['agent']) . '">' . htmlspecialchars($agent_data['agent']) . '</span>
                                                        ' . ($is_current_agent ? '<span class="badge badge-orange ms-1 small-text">VOUS</span>' : '') . '
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-dark small">' . htmlspecialchars($agent_data['bureau']) . '</span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary small">' . $agent_data['nombre_ets'] . '</span>
                                                </td>
                                                <td class="montant-cell text-success">' . number_format($agent_data['potentiel'], 0, ',', ' ') . '</td>
                                                <td class="montant-cell text-success fw-bold">' . number_format($agent_data['total_percu'], 0, ',', ' ') . '</td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress flex-grow-1 me-1">
                                                            <div class="progress-bar ' . $taux_class . '" 
                                                                 role="progressbar" 
                                                                 style="width: ' . min($agent_data['taux_realisation'], 100) . '%" 
                                                                 aria-valuenow="' . $agent_data['taux_realisation'] . '" 
                                                                 aria-valuemin="0" 
                                                                 aria-valuemax="100">
                                                        </div>
                                                        </div>
                                                        <small class="text-nowrap">' . number_format($agent_data['taux_realisation'], 1) . '%</small>
                                                    </div>
                                                </td>
                                            </tr>';
                                            $rang++;
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
                                    Classement des agents par montant perçu
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
            
            <!-- Statistiques par Bureau -->
            <div class="table-container mt-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 text-orange">
                                <i class="bi bi-building me-2"></i>
                                SYNTHESE PAR BUREAU
                            </h5>
                            <span class="badge bg-secondary small">
                                <i class="bi bi-info-circle me-1"></i>
                                <?php echo count($bureaux); ?> bureaux
                            </span>
                        </div>
                    </div>
                    
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>BUREAU</th>
                                        <th class="text-center">NOMBRE AGENTS</th>
                                        <th class="text-center">NOMBRE ETS</th>
                                        <th class="montant-cell">TOTAL PERÇU</th>
                                        <th class="montant-cell">MOYENNE PAR AGENT</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (empty($stats_bureaux)) {
                                        echo '<tr>
                                            <td colspan="5" class="text-center text-muted py-3">
                                                Aucune donnée de bureau disponible
                                            </td>
                                        </tr>';
                                    } else {
                                        foreach ($stats_bureaux as $bureau => $stats) {
                                            $is_current_bureau = ($bureau == $bureau_session);
                                            $row_class = $is_current_bureau ? 'bureau-active' : '';
                                            
                                            echo '<tr class="' . $row_class . '">
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-building me-1 text-orange small"></i>
                                                        <span class="text-truncate" style="max-width: 150px;" title="' . htmlspecialchars($bureau) . '">' . htmlspecialchars($bureau) . '</span>
                                                        ' . ($is_current_bureau ? '<span class="badge badge-orange ms-1 small-text">VOTRE</span>' : '') . '
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary small">' . $stats['nombre_agents'] . '</span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-info small">' . $stats['nombre_ets'] . '</span>
                                                </td>
                                                <td class="montant-cell text-success fw-bold">' . number_format($stats['total_percu'], 0, ',', ' ') . '</td>
                                                <td class="montant-cell text-success">' . number_format($stats['total_percu'] / max($stats['nombre_agents'], 1), 0, ',', ' ') . '</td>
                                            </tr>';
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
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
                            Connecté: <?php echo htmlspecialchars($_SESSION['nom'] ?? 'Admin'); ?>
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
?>