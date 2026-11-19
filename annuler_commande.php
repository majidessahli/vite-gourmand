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

$statutsBloques = ["acceptée", "acceptee", "accepted"];

if (in_array(strtolower($commande["statut"]), $statutsBloques, true)) {
    die("Cette commande a déjà été acceptée. Contactez l'entreprise pour toute demande.");
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $raison = trim($_POST["raison"] ?? "");

    if (strlen($raison) < 5) {
        $message = "Veuillez indiquer une raison (5 caractères minimum).";
    } else {
        $miseAJour = $pdo->prepare(
            "UPDATE commandes
             SET statut = 'Annulée'
             WHERE id = ? AND utilisateur_id = ?"
        );
        $miseAJour->execute([$id, $_SESSION["utilisateur_id"]]);

        header("Location: compte.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Annuler ma commande</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main>
    <h1>Annuler ma commande</h1>

    <p>Confirmez-vous votre demande d'annulation ?</p>

    <?php if ($message): ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label for="raison">Raison de l'annulation</label>
        <textarea id="raison" name="raison" required></textarea>

        <button type="submit">Confirmer l'annulation</button>
    </form>

    <p><a href="compte.php">Retour à mon compte</a></p>
</main>
</body>
</html>
