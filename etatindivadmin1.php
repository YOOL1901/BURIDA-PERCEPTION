<?php 
session_start();
require("bdburida.php");
require("admincondition.php");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-eOJMYsd53ii+scO/bJGFsiCZc+5NDVN2yr8+0RDqr0Ql0h+rP48ckxlpbzKgwra6" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="style.css" />
    <title>ETAT CLIENT</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .info-card {
            border-left: 4px solid #ff9800;
        }
        .table th {
            background-color: #343a40;
            color: white;
        }
        .stat-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .text-orange {
            color: #ff9800 !important;
        }
        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.875rem;
            }
            .btn-group .btn {
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body class="bg-light">
<br>
<div class="container">
    <!-- En-tête avec bouton retour -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <!-- Bouton retour -->
                <a href="recherchepay.php" class="btn btn-black btn-sm">
                    <i class="bi bi-arrow-left me-2"></i>Retour
                </a>
                
                <!-- Logo centré -->
                <div class="text-center">
                    <a href="recherchepay.php">
                        <img src="burida.jfif" alt="LOGO1" height="80" width="160" class="img-fluid" />
                    </a>
                </div>
                
                <!-- Espace vide pour équilibrer -->
                <div style="width: 100px;"></div>
            </div>
        </div>
    </div>

    <!-- Carte principale -->
    <div class="card shadow-sm">
        <div class="card-header text-center py-3">
            <h4 class="mb-0">
                <i class="bi bi-file-text me-2"></i>
                ÉTAT DU CLIENT
            </h4>
        </div>
        
        <div class="card-body">
            <!-- Informations client -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="info-card p-3 bg-white rounded shadow-sm">
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
                                <span class="text-success fw-bold"><?= htmlspecialchars($_GET['mensuel']); ?> Frs</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistiques rapides -->
            <?php
            $affich = $_GET['affichclient'];
            
            // Calcul du total payé
            $totalQuery = $bdd->query("SELECT SUM(montant) as total_paye FROM PAYEMENT WHERE id_ets='$affich'");
            $totalData = $totalQuery->fetch();
            $totalPaye = $totalData['total_paye'] ?? 0;
            
            // Nombre de paiements
            $countQuery = $bdd->query("SELECT COUNT(*) as nb_paiements FROM PAYEMENT WHERE id_ets='$affich'");
            $countData = $countQuery->fetch();
            $nbPaiements = $countData['nb_paiements'];
            ?>
            
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="stat-card text-center">
                        <i class="bi bi-credit-card-2-front text-orange h3"></i>
                        <h5 class="mt-2">Total Payé</h5>
                        <h4 class="text-success"><?= number_format($totalPaye, 0, ',', ' '); ?> Frs</h4>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card text-center">
                        <i class="bi bi-receipt text-orange h3"></i>
                        <h5 class="mt-2">Nombre de Paiements</h5>
                        <h4 class="text-primary"><?= $nbPaiements; ?></h4>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card text-center">
                        <i class="bi bi-calendar-check text-orange h3"></i>
                        <h5 class="mt-2">Dernier Paiement</h5>
                        <h6 class="text-muted">
                            <?php 
                            $lastPay = $bdd->query("SELECT dates FROM PAYEMENT WHERE id_ets='$affich' ORDER BY id_pay DESC LIMIT 1");
                            $lastDate = $lastPay->fetch();
                            echo $lastDate ? $lastDate['dates'] : 'Aucun';
                            ?>
                        </h6>
                    </div>
                </div>
            </div>

            <!-- Tableau des paiements -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead>
                        <tr>
                            <th><i class="bi bi-calendar-range me-1"></i>PÉRIODES PAYÉES</th>
                            <th><i class="bi bi-receipt me-1"></i>N° QUITTANCE</th>
                            <th><i class="bi bi-cash-coin me-1"></i>MONTANT PAYÉ</th>
                            <th><i class="bi bi-credit-card me-1"></i>MODE DE PAIEMENT</th>
                            <th><i class="bi bi-hash me-1"></i>RÉFÉRENCE PAIEMENT</th>
                            <th><i class="bi bi-person-badge me-1"></i>AGENT</th>
                            <th><i class="bi bi-calendar-date me-1"></i>DATES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $recupclient = $bdd->query("SELECT * FROM PAYEMENT WHERE id_ets='$affich' ORDER BY id_pay DESC LIMIT 12");
                        $hasData = false;
                        
                        while ($cmd = $recupclient->fetch()) {
                            $hasData = true;
                            echo '<tr>
                                <td><i class="bi bi-calendar-check text-orange me-1"></i>' . htmlspecialchars($cmd['periode']) . '</td>
                                <td><span class="badge bg-secondary">' . htmlspecialchars($cmd['quittance']) . '</span></td>
                                <td class="fw-bold text-success">' . htmlspecialchars($cmd['montant']) . ' Frs</td>
                                <td><span class="badge bg-info">' . htmlspecialchars($cmd['mode']) . '</span></td>
                                <td><code>' . htmlspecialchars($cmd['reference']) . '</code></td>
                                <td>' . htmlspecialchars($cmd['agent']) . '</td>
                                <td><small class="text-muted">' . htmlspecialchars($cmd['dates']) . '</small></td>
                            </tr>';
                        }
                        
                        if (!$hasData) {
                            echo '<tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox display-4 d-block mb-2"></i>
                                    Aucun paiement enregistré pour cet établissement
                                </td>
                            </tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Boutons d'action -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between flex-wrap">
                        <div class="btn-group">
                            <a href="recherchepay.php" class="btn btn-black">
                                <i class="bi bi-arrow-left me-2"></i>Retour à la liste
                            </a>
                            <a href="paynormal.php?affichclient=<?= $_GET['affichclient'] ?>&nom=<?= urlencode($_GET['nom']) ?>&ville=<?= urlencode($_GET['ville']) ?>&client=<?= urlencode($_GET['client']) ?>&mensuel=<?= urlencode($_GET['mensuel']) ?>" 
                               class="btn btn-orange">
                                <i class="bi bi-plus-circle me-2"></i>Nouveau Paiement
                            </a>
                        </div>
                        <div class="btn-group">
                            <button class="btn btn-outline-secondary" onclick="window.print()">
                                <i class="bi bi-printer me-2"></i>Imprimer
                            </button>
                            <button class="btn btn-outline-primary" onclick="exportToExcel()">
                                <i class="bi bi-download me-2"></i>Exporter
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function exportToExcel() {
        // Simple export Excel (à améliorer selon vos besoins)
        let table = document.querySelector('table');
        let html = table.outerHTML;
        let url = 'data:application/vnd.ms-excel,' + escape(html);
        let link = document.createElement('a');
        link.href = url;
        link.download = 'etat_client_<?= $_GET['affichclient'] ?>.xls';
        link.click();
    }

    // Amélioration de l'affichage pour mobile
    document.addEventListener('DOMContentLoaded', function() {
        // Adaptation responsive
        if (window.innerWidth < 768) {
            document.querySelectorAll('.btn-group').forEach(group => {
                group.classList.add('btn-group-vertical');
            });
        }
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>