<?php
session_start();
require_once "config.php";

if (!isset($_SESSION["utilisateur_id"])) {
    header("Location: connexion.php");
    exit;
}

$requete = $pdo->prepare(
    "SELECT id, menu, nombre_personnes, date_evenement, prix_total, statut
     FROM commandes
     WHERE utilisateur_id = ?
     ORDER BY date_creation DESC"
);

$requete->execute([$_SESSION["utilisateur_id"]]);
$commandes = $requete->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon compte - Vite & Gourmand</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<header>
    <h1>Vite & Gourmand</h1>
    <nav>
        <a href="index.php">Accueil</a>
        <a href="menus.php">Nos menus</a>
        <a href="deconnexion.php">Déconnexion</a>
    </nav>
</header>

<main>

    <h2>Bienvenue <?= htmlspecialchars($_SESSION["prenom"]) ?> !</h2>

    <h3>Mes commandes</h3>

    <?php if (count($commandes) === 0): ?>

        <p>Vous n'avez pas encore de commande.</p>

    <?php else: ?>

        <?php foreach ($commandes as $commande): ?>

            <article class="carte-menu">

                <h4><?= htmlspecialchars($commande["menu"]) ?></h4>

                <p>
                    Nombre de personnes :
                    <?= (int)$commande["nombre_personnes"] ?>
                </p>

                <p>
                    Date :
                    <?= htmlspecialchars($commande["date_evenement"]) ?>
                </p>

                <p>
                    Prix :
                    <?= number_format($commande["prix_total"], 2, ",", " ") ?> €
                </p>

                <p>
                    Statut :
                    <?= htmlspecialchars($commande["statut"]) ?>
                </p>

                <?php
                $statut = strtolower($commande["statut"]);
                $statutsAcceptes = ["acceptée", "acceptee", "accepted"];
                $statutsAnnules = ["annulée", "annulee", "cancelled"];
                $statutsTermines = ["terminée", "terminee", "completed"];
                ?>

                <?php if (
                    !in_array($statut, $statutsAcceptes, true) &&
                    !in_array($statut, $statutsAnnules, true) &&
                    !in_array($statut, $statutsTermines, true)
                ): ?>

                    <p>
                        <a href="modifier_commande.php?id=<?= (int)$commande["id"] ?>">
                            Modifier ma commande
                        </a>
                    </p>

                <?php endif; ?>

                <?php if (
                    !in_array($statut, $statutsAcceptes, true) &&
                    !in_array($statut, $statutsAnnules, true) &&
                    !in_array($statut, $statutsTermines, true)
                ): ?>

                    <p>
                        <a href="annuler_commande.php?id=<?= (int)$commande["id"] ?>">
                            Annuler ma commande
                        </a>
                    </p>

                <?php endif; ?>

                <?php if (in_array($statut, $statutsTermines, true)): ?>

                    <p>
                        <a href="avis.php?id=<?= (int)$commande["id"] ?>">
                            Donner mon avis
                        </a>
                    </p>

                <?php endif; ?>

            </article>

        <?php endforeach; ?>

    <?php endif; ?>

    <p>
        <a href="menus.php">Découvrir nos menus</a>
    </p>

</main>

</body>
</html>
