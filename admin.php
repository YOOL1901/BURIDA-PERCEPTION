<?php 
session_start();
require("bdburida.php");

// Récupération des informations de session
$nom_utilisateur = $_SESSION['nom'] ?? 'Administrateur';
$photo_utilisateur = $_SESSION['photo'] ?? '';
$id_utilisateur = $_SESSION['id'] ?? '';

// Vérifier si l'utilisateur est COULIBALY
$est_coulibaly = ($nom_utilisateur === 'COULIBALY');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ADMINISTRATION - BURIDA</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-eOJMYsd53ii+scO/bJGFsiCZc+5NDVN2yr8+0RDqr0Ql0h+rP48ckxlpbzKgwra6" crossorigin="anonymous">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary-color: #fd7e14;
            --primary-light: #ffe5d0;
            --primary-dark: #e86108;
            --secondary-color: #495057;
        }
        
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        
        .container {
            max-width: 800px;
            position: relative;
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .page-title {
            color: var(--primary-color);
            font-weight: bold;
            margin-bottom: 30px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }
        
        /* Styles pour le profil utilisateur */
        .user-profile {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border-radius: 15px;
            padding: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border: 2px solid var(--primary-color);
            z-index: 1000;
            max-width: 280px;
            transition: transform 0.3s ease;
        }
        
        .user-profile:hover {
            transform: translateY(-2px);
        }
        
        .user-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-light);
        }
        
        .user-avatar-default {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid var(--primary-light);
        }
        
        .user-avatar-default i {
            font-size: 1.8rem;
            color: white;
        }
        
        .user-info {
            margin-left: 15px;
        }
        
        .user-name {
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 5px;
            font-size: 1.1rem;
        }
        
        .user-role {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 0;
        }
        
        .user-status {
            color: #28a745;
            font-size: 0.8rem;
        }
        
        /* Styles pour les cartes de menu */
        .menu-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border: none;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none !important;
            display: block;
            color: inherit;
        }
        
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            text-decoration: none !important;
            color: inherit;
        }
        
        .menu-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            flex-shrink: 0;
        }
        
        .menu-icon i {
            font-size: 1.8rem;
            color: var(--primary-color);
        }
        
        .menu-content {
            flex-grow: 1;
        }
        
        .menu-title {
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 5px;
            font-size: 1.1rem;
        }
        
        .menu-description {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 0;
        }
        
        .btn-logout {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: bold;
            box-shadow: 0 4px 15px rgba(253, 126, 20, 0.3);
            transition: all 0.3s ease;
            text-decoration: none !important;
            display: inline-block;
        }
        
        .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(253, 126, 20, 0.4);
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            text-decoration: none !important;
        }
        
        /* Suppression de tous les soulignements */
        a {
            text-decoration: none !important;
        }
        
        a:hover {
            text-decoration: none !important;
        }
        
        .alert-link {
            text-decoration: none !important;
        }
        
        .alert-link:hover {
            text-decoration: none !important;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .user-profile {
                position: relative;
                top: auto;
                right: auto;
                margin: 0 auto 20px auto;
                max-width: 100%;
            }
            
            .menu-card {
                padding: 15px;
            }
            
            .menu-icon {
                width: 50px;
                height: 50px;
                margin-right: 15px;
            }
            
            .menu-icon i {
                font-size: 1.5rem;
            }
        }
        
        @media (max-width: 576px) {
            .menu-card {
                text-align: center;
            }
            
            .menu-icon {
                margin: 0 auto 10px auto;
            }
            
            .d-flex {
                flex-direction: column;
            }
            
            .user-profile {
                position: relative;
                margin-bottom: 20px;
            }
        }
        
        /* Pour les très grands écrans */
        @media (min-width: 1400px) {
            .user-profile {
                right: calc(50% - 600px);
            }
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <!-- Profil utilisateur en haut à droite (position fixed) -->
        <div class="user-profile">
            <div class="d-flex align-items-center">
                <div class="user-avatar-container">
                    <?php if (!empty($photo_utilisateur) && file_exists("PHOTOS/" . $photo_utilisateur)): ?>
                        <img src="PHOTOS/<?= $photo_utilisateur ?>" alt="Photo de profil" class="user-avatar">
                    <?php else: ?>
                        <div class="user-avatar-default">
                            <i class="bi bi-person-fill"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="user-info">
                    <h6 class="user-name">
                        <?= htmlspecialchars($nom_utilisateur) ?>
                        <?php if ($est_coulibaly): ?>
                            <span class="badge bg-warning ms-1" title="Accès complet">
                                <i class="bi bi-star-fill"></i>
                            </span>
                        <?php endif; ?>
                    </h6>
                    <p class="user-role">
                        <i class="bi bi-shield-check me-1"></i>
                        Administrateur
                    </p>
                    <small class="user-status">
                        <i class="bi bi-circle-fill me-1"></i>
                        En ligne
                    </small>
                </div>
            </div>
        </div>

        <!-- Logo et titre au centre -->
        <div class="logo-container">
            <img src="burida.jfif" alt="LOGO BURIDA" height="100" width="200" class="img-fluid">
            <h1 class="page-title mt-3">ESPACE ADMINISTRATION</h1>
        </div>

        <!-- Menu de navigation -->
        <div class="row">
            <!-- Gestion d'établissement -->
            <div class="col-md-6 mb-4">
                <a href="gestionets.php?id=<?= $id_utilisateur ?>" class="menu-card">
                    <div class="d-flex align-items-center">
                        <div class="menu-icon">
                            <i class="bi bi-shop"></i>
                        </div>
                        <div class="menu-content">
                            <h5 class="menu-title">Gestion d'Établissement</h5>
                            <p class="menu-description">Gérer les établissements et leurs informations</p>
                        </div>
                    </div>
                </a>
            </div>
            
            <!-- Paiement -->
            <div class="col-md-6 mb-4">
                <a href="adminmenupay.php" class="menu-card">
                    <div class="d-flex align-items-center">
                        <div class="menu-icon">
                            <i class="bi bi-cash-coin"></i>
                        </div>
                        <div class="menu-content">
                            <h5 class="menu-title">Perception</h5>
                            <p class="menu-description">Gérer les paiements et transactions</p>
                        </div>
                    </div>
                </a>
            </div>
            
            <!-- Rendez-vous -->
            <div class="col-md-6 mb-4">
                <a href="menurdv.php?id=<?= $id_utilisateur ?>" class="menu-card">
                    <div class="d-flex align-items-center">
                        <div class="menu-icon">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <div class="menu-content">
                            <h5 class="menu-title">Rendez-vous</h5>
                            <p class="menu-description">Gérer les rendez-vous et planning</p>
                        </div>
                    </div>
                </a>
            </div>
            
            <!-- Statistiques -->
            <div class="col-md-6 mb-4">
                <a href="percqot.php?id=<?= $id_utilisateur ?>" class="menu-card">
                    <div class="d-flex align-items-center">
                        <div class="menu-icon">
                            <i class="bi bi-graph-up"></i>
                        </div>
                        <div class="menu-content">
                            <h5 class="menu-title">Statistiques</h5>
                            <p class="menu-description">Analyser les données et performances</p>
                        </div>
                    </div>
                </a>
            </div>
            
            <!-- Gestion des agents (Uniquement pour COULIBALY) -->
            <?php if ($est_coulibaly): ?>
            <div class="col-md-6 mb-4">
                <a href="gestionagent.php" class="menu-card">
                    <div class="d-flex align-items-center">
                        <div class="menu-icon">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div class="menu-content">
                            <h5 class="menu-title">Gestion des Agents</h5>
                            <p class="menu-description">Gérer les comptes agents et permissions</p>
                        </div>
                    </div>
                </a>
            </div>
            <?php endif; ?>
            
            <!-- Option supplémentaire pour équilibrer la grille -->
            <div class="col-md-6 mb-4">
                <a href="page_dr.php" class="menu-card">
                <div class="menu-card" style="opacity: 0.7; cursor: default; pointer-events: none;">
                    <div class="d-flex align-items-center">
                        <div class="menu-icon">
                            <i class="bi bi-plus-circle"></i>
                        </div>
                        <div class="menu-content">
                            <h5 class="menu-title">Module Futur</h5>
                            <p class="menu-description">Fonctionnalité à venir</p>
                        </div>
                    </div>
                </div>
            </div>
            </a>
        </div>

        <!-- Bouton de déconnexion -->
        <div class="text-center mt-4">
            <a href="logout.php" class="btn-logout text-white">
                <i class="bi bi-box-arrow-left me-2"></i>Déconnexion
            </a>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/js/bootstrap.bundle.min.js" integrity="sha384-JEW9xMcG8R+pH31jmWH6WWP0WintQrMb4s7ZOdauHnUtxwoG2vI5DkLtS3qm9Ekf" crossorigin="anonymous"></script>
</body>
</html>