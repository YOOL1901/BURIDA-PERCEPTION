<?php 
session_start();
require("bdburida.php");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestion des Agents - Burida</title>
    <style>
        :root {
            --orange-primary: #ff9800;
            --orange-dark: #e68900;
            --orange-light: #ffb74d;
            --black: #000000;
            --gray-dark: #333333;
            --white: #ffffff;
        }
        
        body {
            background: linear-gradient(135deg, #fff8f0 0%, #ffeccc 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .container {
            max-width: 1200px;
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
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .user-info {
            background: var(--white);
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-left: 4px solid var(--orange-primary);
        }
        
        .user-info i {
            color: var(--orange-primary);
        }
        
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        
        .feature-card {
            background: var(--white);
            border-radius: 15px;
            padding: 30px 25px;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }
        
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--orange-primary);
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(0,0,0,0.15);
            border-color: var(--orange-primary);
            text-decoration: none;
        }
        
        .feature-card.add-agent {
            background: linear-gradient(135deg, var(--orange-primary) 0%, var(--orange-dark) 100%);
            color: var(--white);
        }
        
        .feature-card.list-agent {
            background: var(--white);
            color: var(--black);
        }
        
        .feature-card.add-agent:hover {
            background: linear-gradient(135deg, var(--orange-dark) 0%, var(--orange-primary) 100%);
        }
        
        .feature-card.list-agent:hover {
            background: #f8f9fa;
        }
        
        .card-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            display: block;
        }
        
        .feature-card.add-agent .card-icon {
            color: var(--white);
        }
        
        .feature-card.list-agent .card-icon {
            color: var(--orange-primary);
        }
        
        .card-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .card-description {
            font-size: 0.9rem;
            opacity: 0.8;
            line-height: 1.4;
        }
        
        .btn-black {
            background: var(--black);
            border: 2px solid var(--black);
            color: var(--white);
            padding: 10px 25px;
            border-radius: 8px;
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
        
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .welcome-text {
            color: var(--orange-primary);
            font-size: 1.1rem;
            font-weight: 500;
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
        
        .feature-card {
            animation: fadeInUp 0.6s ease-out;
        }
        
        .feature-card:nth-child(1) {
            animation-delay: 0.1s;
        }
        
        .feature-card:nth-child(2) {
            animation-delay: 0.2s;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .card-grid {
                grid-template-columns: 1fr;
            }
            
            .header-section {
                flex-direction: column;
                text-align: center;
            }
            
            .page-title {
                font-size: 1.8rem;
            }
            
            .feature-card {
                padding: 25px 20px;
            }
        }
        
        @media (max-width: 576px) {
            .container {
                padding: 15px;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
            
            .card-icon {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <!-- En-tête avec bouton retour -->
        <div class="header-section">
            <a href="admin.php" class="btn-black">
                <i class="bi bi-arrow-left"></i>
                Retour
            </a>
            <div class="welcome-text">
                <i class="bi bi-person-circle"></i>
                Connecté en tant que : <?= htmlspecialchars($_SESSION['nom']); ?>
            </div>
        </div>

        <!-- Logo -->
        <div class="logo-container text-center">
            <a href="admin.php">
                <img src="burida.jfif" alt="LOGO BURIDA" height="80" width="160" class="logo">
            </a>
        </div>

        <!-- Titre principal -->
        <div class="text-center">
            <h1 class="page-title">
                <i class="bi bi-people-fill"></i>
                GESTION DES AGENTS
            </h1>
        </div>

        <!-- Grille des fonctionnalités -->
        <div class="card-grid">
            <!-- Carte Ajouter Agent -->
            <a href="adminformagent.php" class="feature-card add-agent">
                <i class="bi bi-person-fill-add card-icon"></i>
                <div class="card-title">AJOUTER UN AGENT</div>
                <div class="card-description">
                    Créer un nouveau compte agent avec ses informations personnelles et ses permissions
                </div>
            </a>

            <!-- Carte Liste des Agents -->
            <a href="listeagent.php" class="feature-card list-agent">
                <i class="bi bi-people-fill card-icon"></i>
                <div class="card-title">LISTE DES AGENTS</div>
                <div class="card-description">
                    Consulter, modifier ou gérer tous les agents enregistrés dans le système
                </div>
            </a>
        </div>

        <!-- Informations supplémentaires -->
        <div class="text-center mt-5">
            <div class="user-info d-inline-block">
                <i class="bi bi-info-circle"></i>
                <strong>Espace Administration</strong> - Gestion complète des agents utilisateurs
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Animation supplémentaire au chargement
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.feature-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = (index * 0.1) + 's';
            });
        });
    </script>
</body>
</html>