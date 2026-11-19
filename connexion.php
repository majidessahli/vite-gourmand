<?php
session_start();
require_once "config.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $motDePasse = $_POST["mot_de_passe"] ?? "";

    if ($email === "" || $motDePasse === "") {
        $message = "Veuillez remplir tous les champs.";
    } else {

        $requete = $pdo->prepare(
            "SELECT id, nom, prenom, email, mot_de_passe, role
             FROM utilisateurs
             WHERE email = ?
             LIMIT 1"
        );

        $requete->execute([$email]);
        $utilisateur = $requete->fetch(PDO::FETCH_ASSOC);

        if (
            $utilisateur &&
            password_verify($motDePasse, $utilisateur["mot_de_passe"])
        ) {

            session_regenerate_id(true);

            $_SESSION["utilisateur_id"] = $utilisateur["id"];
            $_SESSION["nom"] = $utilisateur["nom"];
            $_SESSION["prenom"] = $utilisateur["prenom"];
            $_SESSION["role"] = $utilisateur["role"];

            if ($utilisateur["role"] === "employe") {
                header("Location: employer.php");
                exit;
            }

            if ($utilisateur["role"] === "admin") {
                header("Location: admin.php");
                exit;
            }

            header("Location: compte.php");
            exit;

        } else {
            $message = "Email ou mot de passe incorrect.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Vite & Gourmand</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<header>
    <h1>Vite & Gourmand</h1>

    <nav>
        <a href="index.php">Accueil</a>
        <a href="menus.php">Nos menus</a>
        <a href="inscription.php">Inscription</a>
    </nav>
</header>

<main>

    <h2>Connexion à mon compte</h2>

    <?php if ($message !== ""): ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="POST" action="connexion.php">

        <label for="email">Adresse email</label>

        <input
            type="email"
            id="email"
            name="email"
            autocomplete="username"
            required
        >

        <label for="mot_de_passe">Mot de passe</label>

        <input
            type="password"
            id="mot_de_passe"
            name="mot_de_passe"
            autocomplete="current-password"
            required
        >

        <button type="submit">Se connecter</button>

    </form>

</main>

</body>
</html>
