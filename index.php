<?php 
session_start();
require("bdburida.php");

if (isset($_POST['formconnexion'])) 
{
    $mailconnect = htmlspecialchars($_POST['mailconnect']);
    $motdepassconnect = sha1($_POST['motdepassconnect']);
    
    if (!empty($mailconnect) AND !empty($motdepassconnect)) 
    {
        $pseudoadmin = 'BURIDA';
        $mdpadmin = 'COULIBALY';

        $pseudsaisi = htmlspecialchars($_POST['mailconnect']);
        $mdpsaisi = htmlspecialchars($_POST['motdepassconnect']);

        // Vérification administrateur système
        if ($pseudsaisi == $pseudoadmin AND $mdpsaisi == $mdpadmin) 
        {
            $_SESSION['mailconnect'] = $mailconnect;
            $_SESSION['statut'] = 'ADMINISTRATEUR';
            header("Location: admin.php");
            exit();
        }
        
        // Vérification dans la base de données
        $requser = $bdd->prepare("SELECT * FROM percepteur WHERE contact = ? AND pass = ?");
        $requser->execute(array($mailconnect, $motdepassconnect));
        $userexist = $requser->rowCount();
        
        if ($userexist == 1) 
        {
            $userinfo = $requser->fetch();

            $_SESSION['id'] = $userinfo['id_agent'];
            $_SESSION['motdepassconnect'] = $userinfo['pass'];
            $_SESSION['nom'] = $userinfo['nom'];
            $_SESSION['photo'] = $userinfo['photo'];
            $_SESSION['zone'] = $userinfo['zone'];
            $_SESSION['contact'] = $userinfo['contact'];
            $_SESSION['statut'] = $userinfo['statut'];
            $_SESSION['bureau'] = $userinfo['bureau'];
            $_SESSION['mailconnect'] = $userinfo['contact'];

            // Redirection selon le statut
            if ($_SESSION['statut'] == 'ADMINISTRATEUR') 
            {
                header("Location: admin.php?id=".$_SESSION['id']);
                exit();
            }
            elseif ($_SESSION['statut'] == 'AGENT') 
            {
                header("Location: menu.php?id=".$_SESSION['id']);
                exit();
            }
            elseif ($_SESSION['statut'] == 'DIRECTEUR') 
            {
                header("Location: menudirecteur.php?id=".$_SESSION['id']);
                exit();
            }
            elseif ($_SESSION['statut'] == 'CONTROLEUR') 
            {
                header("Location: menucontroleur.php?id=".$_SESSION['id']);
                exit();
            }
            else 
            {
                header("Location: index.php");
                exit();
            }
        }
        else
        {
            $erreur = "Mauvais contact ou mot de passe !";
        }
    }
    else
    {
        $erreur = "Veuillez remplir tous les champs !";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CONNEXION - BURIDA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-eOJMYsd53ii+scO/bJGFsiCZc+5NDVN2yr8+0RDqr0Ql0h+rP48ckxlpbzKgwra6" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #ff7b00;
            --primary-dark: #e66a00;
            --light-bg: #fff9f2;
            --text-color: #333;
        }
        
        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .login-container {
            max-width: 420px;
            width: 100%;
            margin: 0 auto;
            padding: 20px;
        }
        
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            border-top: 5px solid var(--primary-color);
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .logo {
            max-width: 180px;
            height: auto;
        }
        
        .form-title {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
        
        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #777;
            z-index: 5;
        }
        
        .form-control {
            padding-left: 45px;
            height: 50px;
            border-radius: 8px;
            border: 1px solid #ddd;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(255, 123, 0, 0.25);
        }
        
        .btn-login {
            background-color: var(--primary-color);
            border: none;
            color: white;
            height: 50px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            width: 100%;
        }
        
        .btn-login:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 123, 0, 0.3);
        }
        
        .alert-error {
            background-color: #ffe6e6;
            color: #d9534f;
            border-radius: 8px;
            padding: 10px 15px;
            margin-top: 15px;
            border-left: 4px solid #d9534f;
        }
        
        @media (max-width: 576px) {
            .login-container {
                padding: 15px;
            }
            
            .login-card {
                padding: 1.5rem;
            }
        }
    </style>
  </head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-card">
                <div class="logo-container">
                    <img src="burida.jfif" alt="LOGO BURIDA" class="logo">
                </div>
                
                <h2 class="form-title">
                    <i class="bi bi-person-lock me-2"></i>CONNEXION
                </h2>
                
                <form method="POST" action="">
                    <div class="input-group">
                        <i class="bi bi-telephone-fill"></i>
                        <input autocomplete="off" type="text" class="form-control" name="mailconnect" id="pseudo" placeholder="Votre numéro de contact" value="<?php if(isset($mailconnect)) { echo $mailconnect; } ?>" />
                    </div>
                    
                    <div class="input-group">
                        <i class="bi bi-key-fill"></i>
                        <input autocomplete="off" type="password" class="form-control" name="motdepassconnect" id="motdepass" placeholder="Votre mot de passe" />
                    </div>
                    
                    <button type="submit" class="btn btn-login" name="formconnexion">
                        <i class="bi bi-box-arrow-in-right me-2"></i>SE CONNECTER
                    </button>
                </form>
                
                <?php 
                if (isset($erreur)) 
                {
                    echo '<div class="alert-error mt-3"><i class="bi bi-exclamation-triangle-fill me-2"></i>'.$erreur."</div>";
                }
                ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/js/bootstrap.bundle.min.js" integrity="sha384-JEW9xMcG8R+pH31jmWH6WWP0WintQrMb4s7ZOdauHnUtxwoG2vI5DkLtS3qm9Ekf" crossorigin="anonymous"></script>
</body>
</html>