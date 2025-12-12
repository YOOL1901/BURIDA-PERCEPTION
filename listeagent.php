<?php 
session_start();
require("bdburida.php");
require("admincondition.php");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <title>Liste des Agents - Burida</title>
    <style>
        :root {
            --orange-primary: #ff9800;
            --orange-dark: #e68900;
            --orange-light: #ffb74d;
            --black: #000000;
            --gray-dark: #333333;
            --white: #ffffff;
            --gray-light: #f8f9fa;
        }
        
        body {
            background: linear-gradient(135deg, #fff8f0 0%, #ffeccc 100%);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .container {
            max-width: 1400px;
        }
        
        .logo-container {
            padding: 20px 0;
        }
        
        .logo {
            transition: transform 0.3s ease;
        }
        
        .logo:hover {
            transform: scale(1.05);
        }
        
        .page-title {
            color: var(--orange-primary);
            font-weight: 700;
            margin-bottom: 30px;
            font-size: 1.8rem;
        }
        
        .btn-black {
            background: var(--black);
            border: 2px solid var(--black);
            color: var(--white);
            padding: 10px 25px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-black:hover {
            background: var(--gray-dark);
            border-color: var(--gray-dark);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .card-container {
            background: var(--white);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,152,0,0.1);
            margin-bottom: 30px;
        }
        
        .table-container {
            overflow-x: auto;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .table {
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .table thead th {
            background: linear-gradient(135deg, var(--orange-primary) 0%, var(--orange-dark) 100%);
            color: var(--white);
            border: none;
            padding: 15px 12px;
            font-weight: 600;
            text-align: center;
            vertical-align: middle;
        }
        
        .table tbody td {
            padding: 12px;
            vertical-align: middle;
            border-bottom: 1px solid #dee2e6;
            text-align: center;
        }
        
        .table tbody tr {
            transition: all 0.3s ease;
        }
        
        .table tbody tr:hover {
            background-color: rgba(255,152,0,0.05);
            transform: translateY(-1px);
        }
        
        .agent-photo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--orange-primary);
            transition: transform 0.3s ease;
        }
        
        .agent-photo:hover {
            transform: scale(1.1);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border: none;
            border-radius: 8px;
            padding: 8px 15px;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220,53,69,0.3);
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-agent {
            background: rgba(40,167,69,0.1);
            color: #28a745;
            border: 1px solid #28a745;
        }
        
        .status-admin {
            background: rgba(255,152,0,0.1);
            color: var(--orange-primary);
            border: 1px solid var(--orange-primary);
        }
        
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .info-card {
            background: var(--white);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-left: 4px solid var(--orange-primary);
            margin-bottom: 20px;
        }
        
        .info-card i {
            color: var(--orange-primary);
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .card-container {
            animation: fadeInUp 0.6s ease-out;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .card-container {
                padding: 20px 15px;
            }
            
            .page-title {
                font-size: 1.5rem;
                text-align: center;
            }
            
            .header-section {
                flex-direction: column;
                text-align: center;
            }
            
            .table thead th,
            .table tbody td {
                padding: 8px 6px;
                font-size: 0.85rem;
            }
            
            .agent-photo {
                width: 50px;
                height: 50px;
            }
            
            .btn-danger {
                padding: 6px 10px;
                font-size: 0.8rem;
            }
        }
        
        @media (max-width: 576px) {
            .table-container {
                font-size: 0.8rem;
            }
            
            .agent-photo {
                width: 40px;
                height: 40px;
            }
            
            .status-badge {
                font-size: 0.7rem;
                padding: 4px 8px;
            }
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <!-- En-tête avec bouton retour -->
        <div class="header-section">
            <a href="gestionagent.php" class="btn-black">
                <i class="bi bi-arrow-left"></i>
                Retour
            </a>
            <div class="logo-container">
                <a href="gestionagent.php">
                    <img src="burida.jfif" alt="LOGO BURIDA" height="70" width="140" class="logo">
                </a>
            </div>
        </div>

        <!-- Titre principal -->
        <div class="text-center mb-4">
            <h1 class="page-title">
                <i class="bi bi-people-fill"></i>
                LISTE DES AGENTS
            </h1>
            <p class="text-muted">Bureau Urbain de Marcory</p>
        </div>

        <!-- Carte d'information -->
        <div class="info-card">
            <i class="bi bi-building"></i>
            <h5>Gestion des Agents</h5>
            <p class="mb-0">Consultez et gérez tous les agents enregistrés dans le système</p>
        </div>

        <!-- Conteneur du tableau -->
        <div class="card-container">
            <div class="table-container">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th><i class="bi bi-person"></i> NOM</th>
                            <th><i class="bi bi-person-badge"></i> PRÉNOMS</th>
                            <th><i class="bi bi-telephone"></i> CONTACT</th>
                            <th><i class="bi bi-card-text"></i> MATRICULE</th>
                            <th><i class="bi bi-person-gear"></i> STATUT</th>
                            <th><i class="bi bi-camera"></i> PHOTO</th>
                            <th><i class="bi bi-gear"></i> ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $recupgerant = $bdd->query('SELECT * FROM percepteur ORDER BY id_agent DESC LIMIT 20');
                        $hasAgents = false;
                        
                        while ($cmd = $recupgerant->fetch()) {
                            $hasAgents = true;
                        ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($cmd['nom']) ?></td>
                            <td><?= htmlspecialchars($cmd['prenoms']) ?></td>
                            <td>
                                <i class="bi bi-telephone text-primary me-1"></i>
                                <?= htmlspecialchars($cmd['contact']) ?>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark">
                                    <?= htmlspecialchars($cmd['matricule']) ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                $statusClass = ($cmd['statut'] == 'ADMINISTRATEUR') ? 'status-admin' : 'status-agent';
                                $statusIcon = ($cmd['statut'] == 'ADMINISTRATEUR') ? 'bi-shield-check' : 'bi-person-check';
                                ?>
                                <span class="status-badge <?= $statusClass ?>">
                                    <i class="bi <?= $statusIcon ?> me-1"></i>
                                    <?= htmlspecialchars($cmd['statut']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($cmd['photo'])): ?>
                                    <img src="PHOTOS/<?= htmlspecialchars($cmd['photo']) ?>" 
                                         alt="Photo de <?= htmlspecialchars($cmd['nom']) ?>" 
                                         class="agent-photo">
                                <?php else: ?>
                                    <div class="agent-photo bg-light d-flex align-items-center justify-content-center">
                                        <i class="bi bi-person text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="deleteagent.php?suppgerant=<?= $cmd['id_agent'] ?>&nom=<?= urlencode($cmd['nom']) ?>&prenoms=<?= urlencode($cmd['prenoms']) ?>&contact=<?= urlencode($cmd['contact']) ?>&matricule=<?= urlencode($cmd['matricule']) ?>&zone=<?= urlencode($cmd['statut']) ?>&password=<?= urlencode($cmd['pass']) ?>" 
                                   class="btn btn-danger"
                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer l\\'agent <?= htmlspecialchars(addslashes($cmd['nom'])) ?> ?')">
                                    <i class="bi bi-trash"></i>
                                    Supprimer
                                </a>
                            </td>
                        </tr>
                        <?php 
                        }
                        
                        if (!$hasAgents): 
                        ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="bi bi-people"></i>
                                    <h5>Aucun agent trouvé</h5>
                                    <p>Commencez par ajouter un nouvel agent</p>
                                    <a href="adminformagent.php" class="btn btn-primary">
                                        <i class="bi bi-person-plus"></i>
                                        Ajouter un agent
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pied de page informatif -->
        <div class="text-center text-muted mt-4">
            <small>
                <i class="bi bi-info-circle"></i>
                Affichage des 20 derniers agents ajoutés
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Animation au chargement
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach((row, index) => {
                row.style.animationDelay = (index * 0.1) + 's';
            });
        });
    </script>
</body>
</html>