<?php 
session_start();
require("bdburida.php");

$req = $bdd->query('SELECT * FROM ETABLISSEMENT ORDER BY id_ets DESC LIMIT 1000');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Liste des Établissements - BURIDA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --success-color: #4bb543;
            --warning-color: #ffc107;
            --info-color: #4cc9f0;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .logo-container {
            background: white;
            border-radius: 15px;
            padding: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: inline-block;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 10px 20px rgba(67, 97, 238, 0.3);
        }
        
        .user-info {
            background: white;
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            display: inline-block;
        }
        
        .stats-container {
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 15px;
            border-left: 4px solid var(--primary-color);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card.ouvert {
            border-left-color: var(--success-color);
        }
        
        .stat-card.ferme {
            border-left-color: var(--warning-color);
        }
        
        .stat-card.potentiel {
            border-left-color: var(--info-color);
        }
        
        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-number.ouvert {
            color: var(--success-color);
        }
        
        .stat-number.ferme {
            color: var(--warning-color);
        }
        
        .stat-number.potentiel {
            color: var(--info-color);
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: #6c757d;
            font-weight: 500;
        }
        
        .main-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .card-header-custom {
            background: white;
            border-bottom: 3px solid var(--primary-color);
            padding: 20px 25px;
        }
        
        .search-box {
            position: relative;
            margin-bottom: 0;
        }
        
        .search-box .form-control {
            border-radius: 50px;
            padding: 12px 20px 12px 45px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .search-box .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
        
        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            z-index: 5;
        }
        
        .btn-add {
            background: linear-gradient(45deg, var(--success-color), #2e8b57);
            border: none;
            border-radius: 50px;
            padding: 12px 25px;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(76, 175, 80, 0.3);
            color: white;
        }
        
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .table-custom {
            margin-bottom: 0;
        }
        
        .table-custom thead th {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            padding: 15px 12px;
            font-weight: 600;
            font-size: 0.9rem;
            vertical-align: middle;
        }
        
        .table-custom tbody tr {
            transition: all 0.3s ease;
        }
        
        .table-custom tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
            transform: translateY(-2px);
        }
        
        .table-custom tbody td {
            padding: 12px;
            vertical-align: middle;
            border-color: #f1f3f4;
        }
        
        .badge-etat {
            border-radius: 50px;
            padding: 6px 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .btn-action {
            border-radius: 50px;
            padding: 8px 16px;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-etat {
            background: linear-gradient(45deg, var(--info-color), #0096c7);
            color: white;
        }
        
        .btn-etat:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: white;
        }
        
        .highlight {
            background-color: #fff3cd;
            padding: 2px 4px;
            border-radius: 4px;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .table-custom thead {
                display: none;
            }
            
            .table-custom tbody tr {
                display: block;
                margin-bottom: 15px;
                border: 1px solid #e9ecef;
                border-radius: 10px;
                padding: 10px;
            }
            
            .table-custom tbody td {
                display: block;
                text-align: right;
                padding: 8px;
                border: none;
                position: relative;
            }
            
            .table-custom tbody td::before {
                content: attr(data-label);
                position: absolute;
                left: 15px;
                font-weight: 600;
                color: var(--primary-color);
            }
            
            .btn-action {
                width: 100%;
                margin: 5px 0;
            }
            
            .stat-card {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <!-- Header Section -->
        <div class="text-center mb-4">
            <div class="logo-container">
                <a href="gestionetsAgent.php?id=<?= $_SESSION['id'] ?>">
                    <img src="burida.jfif" alt="LOGO BURIDA" height="80" width="160" class="img-fluid">
                </a>
            </div>
        </div>

        <!-- Page Header -->
        <div class="page-header text-center">
            <h1 class="h3 mb-2"><i class="bi bi-building me-2"></i>LISTE DES ÉTABLISSEMENTS</h1>
            <p class="mb-0 opacity-75">Gestion complète de votre portefeuille d'établissements</p>
        </div>

        <!-- User Info -->
        <div class="user-info">
            <div class="d-flex align-items-center">
                <i class="bi bi-person-circle me-2 text-primary"></i>
                <span class="fw-bold"><?= $_SESSION['nom'] ?></span>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-container">
            <div class="row">
                <div class="col-md-4">
                    <div class="stat-card ouvert">
                        <div class="stat-number ouvert">
                            <?php 
                            $tot = $bdd->query("SELECT SUM(compte) AS cpt FROM ETABLISSEMENT WHERE etat='OUVERT'");
                            while ($cmd = $tot->fetch()) {
                                echo $cmd['cpt'] ?? '0';
                            }
                            ?>
                        </div>
                        <div class="stat-label">
                            <i class="bi bi-check-circle me-1"></i>Établissements Ouverts
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card ferme">
                        <div class="stat-number ferme">
                            <?php 
                            $tot = $bdd->query("SELECT SUM(compte) AS cpt FROM ETABLISSEMENT WHERE etat='FERME'");
                            while ($cmd = $tot->fetch()) {
                                echo $cmd['cpt'] ?? '0';
                            }
                            ?>
                        </div>
                        <div class="stat-label">
                            <i class="bi bi-x-circle me-1"></i>Établissements Fermés
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card potentiel">
                        <div class="stat-number potentiel">
                            <?php 
                            $tot = $bdd->query("SELECT SUM(mensualite) AS totpot FROM ETABLISSEMENT WHERE etat='OUVERT'");
                            while ($cmd = $tot->fetch()) {
                                echo number_format($cmd['totpot'] ?? '0', 0, ',', ' ') . ' FCFA';
                            }
                            ?>
                        </div>
                        <div class="stat-label">
                            <i class="bi bi-graph-up me-1"></i>Potentiel Total
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Bar -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0 text-dark">
                <i class="bi bi-building me-2"></i>Établissements de Marcory
            </h5>
            <a href="agtajoutets.php?id=<?= $_SESSION['id'] ?>" class="btn btn-add">
                <i class="bi bi-plus-circle me-2"></i>Ajouter un Établissement
            </a>
        </div>

        <!-- Main Content Card -->
        <div class="main-card">
            <div class="card-header-custom">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Liste des Établissements</h5>
                    </div>
                    <div class="col-md-6">
                        <div class="search-box">
                            <i class="bi bi-search search-icon"></i>
                            <input type="text" id="searchInput" class="form-control" 
                                   placeholder="Rechercher un établissement, localité, contact...">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom" id="facturesTable">
                        <thead>
                            <tr>
                                <th width="8%"><i class="bi bi-hash me-1"></i>CODE</th>
                                <th width="18%"><i class="bi bi-building me-1"></i>ÉTABLISSEMENT</th>
                                <th width="12%"><i class="bi bi-person me-1"></i>USAGER</th>
                                <th width="10%"><i class="bi bi-telephone me-1"></i>CONTACT</th>
                                <th width="10%"><i class="bi bi-geo-alt me-1"></i>LOCALITÉ</th>
                                <th width="10%"><i class="bi bi-pin-map me-1"></i>QUARTIER</th>
                                <th width="10%"><i class="bi bi-currency-dollar me-1"></i>MENSUALITÉ</th>
                                <th width="8%"><i class="bi bi-circle-fill me-1"></i>ÉTAT</th>
                                <th width="14%"><i class="bi bi-lightning me-1"></i>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $recupcmd = $bdd->query('SELECT * FROM ETABLISSEMENT ORDER BY id_ets DESC');
                            while ($user = $recupcmd->fetch()):
                                // Déterminer la couleur du badge d'état
                                $etatClass = (strtolower($user['etat']) === 'ouvert') ? 'bg-success' : 'bg-warning';
                            ?>
                                <tr>
                                    <td data-label="CODE"><?= htmlspecialchars($user['id_ets']) ?></td>
                                    <td data-label="ÉTABLISSEMENT"><?= htmlspecialchars($user['denomination']) ?></td>
                                    <td data-label="USAGER"><?= htmlspecialchars($user['nom']) ?></td>
                                    <td data-label="CONTACT"><?= htmlspecialchars($user['contact']) ?></td>
                                    <td data-label="LOCALITÉ"><?= htmlspecialchars($user['ville']) ?></td>
                                    <td data-label="QUARTIER"><?= htmlspecialchars($user['localite']) ?></td>
                                    <td data-label="MENSUALITÉ"><?= htmlspecialchars($user['mensualite']) ?> FCFA</td>
                                    <td data-label="ÉTAT">
                                        <span class="badge badge-etat <?= $etatClass ?>">
                                            <?= htmlspecialchars($user['etat']) ?>
                                        </span>
                                    </td>
                                    <td data-label="ACTIONS">
                                        <a href="etatets.php?affichclient=<?= $user['id_ets'] ?>&nom=<?= urlencode($user['denomination']) ?>&ville=<?= urlencode($user['localite']) ?>&client=<?= urlencode($user['nom']) ?>&mensuel=<?= $user['mensualite'] ?>" 
                                           class="btn btn-action btn-etat">
                                            <i class="bi bi-eye me-1"></i> ÉTAT
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- No Results Message (Hidden by default) -->
        <div id="noResults" class="alert alert-info text-center" style="display: none;">
            <i class="bi bi-info-circle me-2"></i>Aucun établissement trouvé avec ces critères de recherche.
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const table = document.getElementById('facturesTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            const noResults = document.getElementById('noResults');
            
            searchInput.addEventListener('keyup', function() {
                const searchTerm = this.value.toLowerCase();
                let visibleRows = 0;
                
                for (let i = 0; i < rows.length; i++) {
                    const row = rows[i];
                    const cells = row.getElementsByTagName('td');
                    let found = false;
                    
                    // Recherche dans toutes les colonnes sauf la dernière (actions)
                    for (let j = 0; j < cells.length - 1; j++) {
                        const cellText = cells[j].textContent.toLowerCase();
                        
                        if (cellText.includes(searchTerm)) {
                            found = true;
                            visibleRows++;
                            // Mise en évidence du texte trouvé
                            if (searchTerm.length > 0) {
                                const regex = new RegExp(searchTerm, 'gi');
                                cells[j].innerHTML = cells[j].textContent.replace(
                                    regex, 
                                    match => `<span class="highlight">${match}</span>`
                                );
                            }
                        }
                    }
                    
                    // Affiche ou masque la ligne selon le résultat
                    row.style.display = found ? '' : 'none';
                    
                    // Retire le surlignage si la recherche est vide
                    if (searchTerm === '') {
                        for (let j = 0; j < cells.length - 1; j++) {
                            cells[j].innerHTML = cells[j].textContent;
                        }
                    }
                }
                
                // Afficher/masquer le message "Aucun résultat"
                if (visibleRows === 0 && searchTerm.length > 0) {
                    noResults.style.display = 'block';
                    table.style.display = 'none';
                } else {
                    noResults.style.display = 'none';
                    table.style.display = 'table';
                }
            });
            
            // Animation des lignes au chargement
            setTimeout(() => {
                for (let i = 0; i < rows.length; i++) {
                    rows[i].style.opacity = '0';
                    rows[i].style.transform = 'translateY(10px)';
                    
                    setTimeout(() => {
                        rows[i].style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                        rows[i].style.opacity = '1';
                        rows[i].style.transform = 'translateY(0)';
                    }, i * 50);
                }
            }, 300);
        });
    </script>
</body>
</html>