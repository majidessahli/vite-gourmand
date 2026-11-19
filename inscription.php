<?php
require_once "config.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nom = trim($_POST["nom"] ?? "");
    $prenom = trim($_POST["prenom"] ?? "");
    $telephone = trim($_POST["telephone"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $adresse = trim($_POST["adresse"] ?? "");
    $motDePasse = $_POST["mot_de_passe"] ?? "";

    if (
        empty($nom) || empty($prenom) ||
        empty($telephone) || empty($adresse)
    ) {
        $message = "Veuillez remplir tous les champs.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Adresse e-mail invalide.";
    } elseif (
        strlen($motDePasse) < 10 ||
        !preg_match('/[A-Z]/', $motDePasse) ||
        !preg_match('/[a-z]/', $motDePasse) ||
        !preg_match('/[0-9]/', $motDePasse) ||
        !preg_match('/[^a-zA-Z0-9]/', $motDePasse)
    ) {
        $message = "Le mot de passe doit contenir 10 caractères minimum, une majuscule, une minuscule, un chiffre et un caractère spécial.";
    } else {

        $hash = password_hash($motDePasse, PASSWORD_DEFAULT);

        try {
            $sql = "INSERT INTO utilisateurs
            (nom, prenom, telephone, email, adresse, mot_de_passe)
            VALUES (?, ?, ?, ?, ?, ?)";

            $requete = $pdo->prepare($sql);

            $requete->execute([
                $nom,
                $prenom,
                $telephone,
                $email,
                $adresse,
                $hash
            ]);

            $message = "Votre compte a été créé avec succès !";

        } catch (PDOException $e) {
            $message = "Impossible de créer le compte. Cet e-mail est peut-être déjà utilisé.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Vite & Gourmand</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<header>
    <h1>Vite & Gourmand</h1>

    <nav>
        <a href="index.php">Accueil</a>
        <a href="menus.php">Nos menus</a>
        <a href="connexion.php">Connexion</a>
    </nav>
</header>

<main class="presentation">

    <h2>Créer un compte</h2>

    <?php if ($message !== ""): ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="POST">

        <p>
            <input type="text" name="nom" placeholder="Nom" required>
        </p>

        <p>
            <input type="text" name="prenom" placeholder="Prénom" required>
        </p>

        <p>
            <input type="tel" name="telephone" placeholder="Téléphone" required>
        </p>

        <p>
            <input type="email" name="email" placeholder="Adresse e-mail" required>
        </p>

        <p>
            <input type="text" name="adresse" placeholder="Adresse postale" required>
        </p>

        <p>
            <input type="password" name="mot_de_passe"
            placeholder="Mot de passe" required>
        </p>

        <button type="submit">Créer mon compte</button>

    </form>

    <p>Déjà inscrit ?
        <a href="connexion.php">Se connecter</a>
    </p>

</main>

<footer>
    <p>Vite & Gourmand - Bordeaux</p>
    <p>Horaires | Mentions légales | CGV</p>
</footer>

</body>
</html>