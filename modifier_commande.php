<?php
session_start();
require_once "config.php";

if (!isset($_SESSION["utilisateur_id"])) {
    header("Location: connexion.php");
    exit;
}

$id = (int)($_GET["id"] ?? 0);

$requete = $pdo->prepare(
    "SELECT * FROM commandes WHERE id = ? AND utilisateur_id = ?"
);
$requete->execute([$id, $_SESSION["utilisateur_id"]]);
$commande = $requete->fetch(PDO::FETCH_ASSOC);

if (!$commande) {
    die("Commande introuvable.");
}

if (in_array(strtolower($commande["statut"]), ["acceptée", "acceptee", "accepted"])) {
    die("Cette commande a déjà été acceptée. Elle ne peut plus être modifiée en ligne.");
}

$menus = [
    "Menu Gourmand" => ["prix" => 35, "minimum" => 2],
    "Menu Végétarien" => ["prix" => 30, "minimum" => 2],
    "Menu Festif" => ["prix" => 55, "minimum" => 4]
];

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $menu = $_POST["menu"] ?? "";
    $personnes = (int)($_POST["personnes"] ?? 0);
    $date = $_POST["date"] ?? "";
    $adresse = trim($_POST["adresse"] ?? "");

    if (
        !isset($menus[$menu]) ||
        $personnes < $menus[$menu]["minimum"] ||
        !$date ||
        !$adresse
    ) {
        $message = "Veuillez vérifier les informations saisies.";
    } else {
        $total = $menus[$menu]["prix"] * $personnes;

        if ($personnes >= $menus[$menu]["minimum"] + 5) {
            $total *= 0.90;
        }

        $total += 5;

        $miseAJour = $pdo->prepare(
            "UPDATE commandes
             SET menu = ?, nombre_personnes = ?, date_evenement = ?,
                 adresse_evenement = ?, prix_total = ?
             WHERE id = ? AND utilisateur_id = ?"
        );

        $miseAJour->execute([
            $menu,
            $personnes,
            $date,
            $adresse,
            $total,
            $id,
            $_SESSION["utilisateur_id"]
        ]);

        header("Location: compte.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier ma commande</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<main>
    <h1>Modifier ma commande</h1>

    <?php if ($message): ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Menu</label>
        <select name="menu" required>
            <?php foreach ($menus as $nom => $details): ?>
                <option value="<?= htmlspecialchars($nom) ?>"
                    <?= $commande["menu"] === $nom ? "selected" : "" ?>>
                    <?= htmlspecialchars($nom) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Nombre de personnes</label>
        <input type="number" name="personnes" min="2"
            value="<?= (int)$commande["nombre_personnes"] ?>" required>

        <label>Date de prestation</label>
        <input type="date" name="date"
            value="<?= htmlspecialchars($commande["date_evenement"]) ?>" required>

        <label>Adresse</label>
        <input type="text" name="adresse"
            value="<?= htmlspecialchars($commande["adresse_evenement"]) ?>" required>

        <button type="submit">Enregistrer les modifications</button>
    </form>

    <p><a href="compte.php">Retour à mon compte</a></p>
</main>

</body>
</html>