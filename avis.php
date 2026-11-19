<?php
session_start();
require_once "config.php";

if (!isset($_SESSION["utilisateur_id"])) {
    header("Location: connexion.php");
    exit;
}

$id = (int)($_GET["id"] ?? 0);

$requete = $pdo->prepare(
    "SELECT id, statut FROM commandes
     WHERE id = ? AND utilisateur_id = ?"
);
$requete->execute([$id, $_SESSION["utilisateur_id"]]);
$commande = $requete->fetch(PDO::FETCH_ASSOC);

if (!$commande) {
    die("Commande introuvable.");
}

if (strtolower($commande["statut"]) !== "terminée" &&
    strtolower($commande["statut"]) !== "terminee" &&
    strtolower($commande["statut"]) !== "completed") {
    die("Vous pourrez laisser un avis lorsque votre commande sera terminée.");
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $note = (int)($_POST["note"] ?? 0);
    $commentaire = trim($_POST["commentaire"] ?? "");

    if ($note < 1 || $note > 5 || $commentaire === "") {
        $message = "Veuillez choisir une note et écrire un commentaire.";
    } else {
        $requete = $pdo->prepare(
            "INSERT INTO avis (utilisateur_id, note, commentaire, statut)
             VALUES (?, ?, ?, 'En attente')"
        );

        $requete->execute([
            $_SESSION["utilisateur_id"],
            $note,
            $commentaire
        ]);

        $message = "Merci ! Votre avis a été envoyé pour validation.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Donner mon avis - Vite & Gourmand</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<main>
    <h1>Donner mon avis</h1>

    <?php if ($message): ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label for="note">Votre note</label>

        <select name="note" id="note" required>
            <option value="">Choisir une note</option>
            <option value="1">1 étoile</option>
            <option value="2">2 étoiles</option>
            <option value="3">3 étoiles</option>
            <option value="4">4 étoiles</option>
            <option value="5">5 étoiles</option>
        </select>

        <label for="commentaire">Votre commentaire</label>
        <textarea name="commentaire" id="commentaire"
                  required maxlength="1000"></textarea>

        <button type="submit">Envoyer mon avis</button>
    </form>

    <p><a href="compte.php">Retour à mon compte</a></p>
</main>

</body>
</html>
