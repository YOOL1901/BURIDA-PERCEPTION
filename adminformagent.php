<?php 
session_start();
require("bdburida.php");

if (isset($_POST['ajouter'])) 
{
    $nom = htmlspecialchars($_POST['nom']);
    $contact = htmlspecialchars($_POST['contact']);
    $statut = htmlspecialchars($_POST['statut']);
    $bureau = htmlspecialchars($_POST['bureau']);
    $agentphoto = $_FILES['agentphoto']['name'];
    $PHOTOS = "PHOTOS/".$agentphoto;
    move_uploaded_file($_FILES['agentphoto']['tmp_name'], $PHOTOS);
    $motdepasse = sha1($_POST['motdepasse']);
    $motdepasse2 = sha1($_POST['motdepasse2']);
    
    if (!empty($_POST['nom'])  AND !empty($_POST['contact']) AND !empty($_POST['bureau']) AND !empty($_POST['statut']) AND !empty($_POST['motdepasse']) AND !empty($_POST['motdepasse2']))
    {
        if (is_numeric($_POST['contact'])) 
        {
            if ($motdepasse == $motdepasse2) 
            {
                $insertagent = $bdd->prepare('INSERT INTO percepteur (nom, contact, statut, bureau, photo, pass) VALUES (?,?,?,?,?,?)');
                $insertagent->execute(array($nom, $contact, $statut, $bureau, $agentphoto, $motdepasse));
                header("Location: admin.php?id=".$_SESSION['id']);
                exit();
            }
            else
            {
                $erreur = "Les mots de passe ne correspondent pas";
            }
        }
        else
        {
            $erreur = "Veuillez saisir un numéro de contact valide";
        }
    }
    else
    {
        $erreur = "Veuillez remplir tous les champs obligatoires";
    }
}

// Réinitialiser toutes les variables pour vider les champs
$nom = $prenoms = $contact = $matricule = $statut = '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <title>Ajouter Agent - Burida</title>
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
            max-width: 600px;
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
        
        .form-container {
            background: var(--white);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,152,0,0.1);
        }
        
        .form-label {
            font-weight: 600;
            color: var(--black);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--white);
        }
        
        .form-control:focus {
            border-color: var(--orange-primary);
            box-shadow: 0 0 0 3px rgba(255,152,0,0.1);
            background: var(--white);
        }
        
        .form-select {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-select:focus {
            border-color: var(--orange-primary);
            box-shadow: 0 0 0 3px rgba(255,152,0,0.1);
        }
        
        .btn-submit {
            background: linear-gradient(135deg, var(--orange-primary) 0%, var(--orange-dark) 100%);
            border: none;
            color: var(--white);
            padding: 15px 40px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 20px;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255,152,0,0.3);
            background: linear-gradient(135deg, var(--orange-dark) 0%, var(--orange-primary) 100%);
        }
        
        .alert-custom {
            border-radius: 12px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 25px;
        }
        
        .alert-danger {
            background: #fee;
            color: #c33;
            border-left: 4px solid #c33;
        }
        
        .alert-success {
            background: #efe;
            color: #363;
            border-left: 4px solid #363;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            z-index: 5;
        }
        
        .input-with-icon {
            padding-left: 45px;
        }
        
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
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
        
        .form-container {
            animation: fadeInUp 0.6s ease-out;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .form-container {
                padding: 30px 25px;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
            
            .header-section {
                flex-direction: column;
                text-align: center;
            }
        }
        
        @media (max-width: 576px) {
            .form-container {
                padding: 25px 20px;
            }
            
            .btn-submit {
                padding: 12px 30px;
            }
        }
        
        .file-input-container {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }
        
        .file-input {
            position: absolute;
            left: -9999px;
        }
        
        .file-input-label {
            display: block;
            padding: 12px 15px;
            background: var(--gray-light);
            border: 2px dashed #dee2e6;
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #6c757d;
        }
        
        .file-input-label:hover {
            border-color: var(--orange-primary);
            background: rgba(255,152,0,0.05);
        }
        
        .file-input-label i {
            margin-right: 8px;
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
                <i class="bi bi-person-fill-add"></i>
                AJOUTER UN AGENT
            </h1>
        </div>

        <!-- Messages d'alerte -->
        <?php if(isset($erreur)): ?>
            <div class="alert alert-danger alert-custom">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= $erreur ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($erreur2)): ?>
            <div class="alert alert-success alert-custom">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?= $erreur2 ?>
            </div>
        <?php endif; ?>

        <!-- Formulaire -->
        <div class="form-container">
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="row g-3">
                    <!-- Nom -->
                    <div class="col-12">
                        <label class="form-label">
                            <i class="bi bi-person"></i>
                            NOM
                        </label>
                        <input type="text" class="form-control" autocomplete="off" name="nom" 
                               placeholder="Nom de l'agent" value="" required>
                    </div>

                    <!-- Contact -->
                    <div class="col-12">
                        <label class="form-label">
                            <i class="bi bi-telephone"></i>
                            CONTACT
                        </label>
                        <input type="number" class="form-control" autocomplete="off" name="contact" 
                               placeholder="Numéro de contact" value="" required>
                    </div>
                    
                    <!-- ZONE -->
                    <select name="bureau" class="form-select" required>
                            <option value="">-- Sélectionnez le Bureau --</option>
                            <option value="ADMINISTRATION">ADMINISTRATION</option>
                            <option value="ABOBO">ABOBO</option>
                            <option value="COCODY 1">COCODY 1</option>
                            <option value="COCODY 2">COCODY 2</option>
                            <option value="BINGERVILLE">BINGERVILLE</option>
                            <option value="ATTECOUBE-ADJAME">ATTECOUBE-ADJAME</option>
                            <option value="AGNEBY-TIASSA">AGNEBY-TIASSA</option>
                             <option value="PLATEAU">PLATEAU</option>
                            <option value="MARCORY">MARCORY</option>
                            <option value="ZONE 3 et 4">ZONE 3 et 4</option>
                            <option value="PORT-BOUET">PORT-BOUET</option>
                            <option value="KOUMASSI">KOUMASSI</option>
                            <option value="YOPOUGON 1">YOPOUGON 1</option>
                            <option value="YOPOUGON 2">YOPOUGON 2</option>
                            <option value="GRANDS PONTS">GRANDS PONTS</option>
                            <option value="ABENGOUROU">ABENGOUROU</option>
                            <option value="ABOISSO">ABOISSO</option>
                            <option value="BONOUA">BONOUA</option>
                            <option value="BOUAKE">BOUAKE</option>
                            <option value="DALOA">DALOA</option>
                            <option value="GAGNOA-DIVO">GAGNOA-DIVO</option>
                            <option value="KORHOGO">KORHOGO</option>
                             <option value="MAN">MAN</option>
                            <option value="SAN-PEDRO">SAN-PEDRO</option>
                            <option value="SOUBRE">SOUBRE</option>
                            <option value="YAMOUSSOUKRO">YAMOUSSOUKRO</option>
                        </select>
                        
                    <!-- Statut -->
                    <div class="col-12">
                        <label class="form-label">
                            <i class="bi bi-person-gear"></i>
                            STATUT
                        </label>
                        <select name="statut" class="form-select" required>
                            <option value="">-- Sélectionnez le statut --</option>
                            <option value="DIRECTEUR">DIRECTEUR</option>
                            <option value="CONTROLEUR">CONTROLEUR</option>
                            <option value="AGENT">AGENT</option>
                            <option value="ADMINISTRATEUR">ADMINISTRATEUR</option>
                            <option value="ADMINISTRATEUR">SUPERADMINISTRATEUR</option>
                        </select>
                    </div>

                    <!-- Photo -->
                    <div class="col-12">
                        <label class="form-label">
                            <i class="bi bi-camera"></i>
                            PHOTO
                        </label>
                        <div class="file-input-container">
                            <input type="file" class="file-input" name="agentphoto" id="agentphoto">
                            <label for="agentphoto" class="file-input-label">
                                <i class="bi bi-cloud-arrow-up"></i>
                                Choisir une photo...
                            </label>
                        </div>
                    </div>

                    <!-- Mot de passe -->
                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="bi bi-lock"></i>
                            MOT DE PASSE
                        </label>
                        <input type="password" class="form-control" name="motdepasse" 
                               placeholder="Mot de passe" required>
                    </div>

                    <!-- Confirmation mot de passe -->
                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="bi bi-lock-fill"></i>
                            CONFIRMATION
                        </label>
                        <input type="password" class="form-control" name="motdepasse2" 
                               placeholder="Confirmez le mot de passe" required>
                    </div>
                </div>

                <!-- Bouton de soumission -->
                <button type="submit" class="btn-submit" name="ajouter">
                    <i class="bi bi-person-plus-fill me-2"></i>
                    AJOUTER L'AGENT
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Réinitialiser le formulaire au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            // Réinitialiser l'input file
            const fileInput = document.getElementById('agentphoto');
            const fileLabel = document.querySelector('.file-input-label');
            
            // S'assurer que l'input file est vide
            fileInput.value = '';
            fileLabel.innerHTML = '<i class="bi bi-cloud-arrow-up"></i> Choisir une photo...';
            
            // Réinitialiser le select
            document.querySelector('select[name="statut"]').selectedIndex = 0;
        });

        // Animation pour l'input file
        document.getElementById('agentphoto').addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : 'Choisir une photo...';
            const label = document.querySelector('.file-input-label');
            label.innerHTML = `<i class="bi bi-cloud-arrow-up"></i> ${fileName}`;
        });

        // Validation en temps réel
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        });
    </script>
</body>
</html>