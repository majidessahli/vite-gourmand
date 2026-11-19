<?php
session_start();
require_once "config.php";

// Vérification des droits d'accès
if (
    !isset($_SESSION["utilisateur_id"]) ||
    !in_array($_SESSION["role"], ["employe", "admin"])
) {
    header("Location: connexion.php");
    exit;
}

// Protection CSRF
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$message = "";

// Traitement des actions
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        die("Erreur de sécurité. Rechargez la page.");
    }

    // Modification du statut d'une commande
    if (isset($_POST["modifier_commande"])) {

        $id = filter_var($_POST["commande_id"], FILTER_VALIDATE_INT);
        $statut = $_POST["statut"] ?? "";

        $statutsAutorises = [
            "Acceptée",
            "En préparation",
            "En livraison",
            "Livrée",
            "En attente de retour du matériel",
            "Terminée",
            "Refusée"
        ];

        if ($id && in_array($statut, $statutsAutorises, true)) {

            $requete = $pdo->prepare(
                "UPDATE commandes SET statut = ? WHERE id = ?"
            );

            $requete->execute([$statut, $id]);

            $message = "Statut de la commande mis à jour.";
        } else {
            $message = "Informations invalides.";
        }
    }

    // Validation ou refus d'un avis
    if (isset($_POST["modifier_avis"])) {

        $id = filter_var($_POST["avis_id"], FILTER_VALIDATE_INT);
        $statut = $_POST["statut_avis"] ?? "";

        if ($id && in_array($statut, ["Validé", "Refusé"], true)) {

            $requete = $pdo->prepare(
                "UPDATE avis SET statut = ? WHERE id = ?"
            );

            $requete->execute([$statut, $id]);

            $message = "Avis mis à jour.";
        } else {
            $message = "Informations invalides.";
        }
    }
}

// Récupération des commandes
$requete = $pdo->query("
    SELECT
        commandes.id,
        commandes.menu,
        commandes.nombre_personnes,
        commandes.date_evenement,
        commandes.prix_total,
        commandes.statut,
        utilisateurs.nom,
        utilisateurs.prenom
    FROM commandes
    INNER JOIN utilisateurs
    ON commandes.utilisateur_id = utilisateurs.id
    ORDER BY commandes.date_creation DESC
");

$commandes = $requete->fetchAll(PDO::FETCH_ASSOC);

// Récupération des avis
$requete = $pdo->query("
    SELECT
        avis.id,
        avis.note,
        avis.commentaire,
        avis.statut,
        utilisateurs.nom,
        utilisateurs.prenom
    FROM avis
    INNER JOIN utilisateurs
    ON avis.utilisateur_id = utilisateurs.id
    ORDER BY avis.id DESC
");

$avis = $requete->fetchAll(PDO::FETCH_ASSOC);

function securiser($valeur) {
    return htmlspecialchars((string)$valeur, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace employé - Vite & Gourmand</title>
    <link rel="stylesheet" href="style.css">

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f2e9;
            margin: 0;
            padding: 20px;
        }

        .conteneur {
            max-width: 1100px;
            margin: auto;
        }

        h1, h2 {
            color: #355b42;
        }

        .bloc {
            background: white;
            padding: 20px;
            margin: 25px 0;
            border-radius: 10px;
            box-shadow: 0 2px 8px #00000012;
        }

        .tableau {
            width: 100%;
            border-collapse: collapse;
        }

        .tableau th,
        .tableau td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        .tableau th {
            background: #355b42;
            color: white;
        }

        select, button {
            padding: 9px;
            margin: 4px 0;
            border-radius: 5px;
        }

        button {
            background: #355b42;
            color: white;
            border: none;
            cursor: pointer;
        }

        button:hover {
            background: #24412e;
        }

        .message {
            background: #d9edda;
            padding: 12px;
            border-radius: 5px;
        }

        .retour {
            display: inline-block;
            margin: 15px 0;
            color: #355b42;
        }

        .tableau-conteneur {
            overflow-x: auto;
        }
    </style>
</head>

<body>

<div class="conteneur">

    <h1>Espace employé</h1>

    <p>
        Bienvenue
        <?= securiser($_SESSION["prenom"] ?? "Employé") ?> !
    </p>

    <a class="retour" href="index.php">Retour au site</a>
    <a class="retour" href="deconnexion.php">Déconnexion</a>

    <?php if ($message !== ""): ?>
        <p class="message"><?= securiser($message) ?></p>
    <?php endif; ?>

    <section class="bloc">

        <h2>Gestion des commandes</h2>

        <div class="tableau-conteneur">

        <table class="tableau">

            <tr>
                <th>Client</th>
                <th>Menu</th>
                <th>Personnes</th>
                <th>Date</th>
                <th>Prix</th>
                <th>Statut</th>
                <th>Action</th>
            </tr>

            <?php foreach ($commandes as $commande): ?>

                <tr>
                    <td>
                        <?= securiser($commande["prenom"]) ?>
                        <?= securiser($commande["nom"]) ?>
                    </td>

                    <td><?= securiser($commande["menu"]) ?></td>

                    <td>
                        <?= securiser($commande["nombre_personnes"]) ?>
                    </td>

                    <td>
                        <?= securiser($commande["date_evenement"]) ?>
                    </td>

                    <td>
                        <?= securiser($commande["prix_total"]) ?> €
                    </td>

                    <td><?= securiser($commande["statut"]) ?></td>

                    <td>
                        <form method="POST">

                            <input type="hidden"
                                name="csrf_token"
                                value="<?= securiser($_SESSION["csrf_token"]) ?>">

                            <input type="hidden"
                                name="commande_id"
                                value="<?= (int)$commande["id"] ?>">

                            <select name="statut">

                                <?php
                                $statuts = [
                                    "Acceptée",
                                    "En préparation",
                                    "En livraison",
                                    "Livrée",
                                    "En attente de retour du matériel",
                                    "Terminée",
                                    "Refusée"
                                ];
                                ?>

                                <?php foreach ($statuts as $statut): ?>
                                    <option value="<?= securiser($statut) ?>"
                                        <?= $commande["statut"] === $statut ? "selected" : "" ?>>
                                        <?= securiser($statut) ?>
                                    </option>
                                <?php endforeach; ?>

                            </select>

                            <button type="submit" name="modifier_commande">
                                Modifier
                            </button>

                        </form>
                    </td>
                </tr>

            <?php endforeach; ?>

        </table>

        </div>

        <?php if (count($commandes) === 0): ?>
            <p>Aucune commande pour le moment.</p>
        <?php endif; ?>

    </section>


    <section class="bloc">

        <h2>Gestion des avis clients</h2>

        <?php if (count($avis) === 0): ?>

            <p>Aucun avis pour le moment.</p>

        <?php else: ?>

            <?php foreach ($avis as $unAvis): ?>

                <div class="bloc">

                    <h3>
                        <?= securiser($unAvis["prenom"]) ?>
                        <?= securiser($unAvis["nom"]) ?>
                    </h3>

                    <p>
                        Note :
                        <?= securiser($unAvis["note"]) ?>/5
                    </p>

                    <p>
                        <?= nl2br(securiser($unAvis["commentaire"])) ?>
                    </p>

                    <p>
                        Statut :
                        <strong><?= securiser($unAvis["statut"]) ?></strong>
                    </p>

                    <form method="POST">

                        <input type="hidden"
                            name="csrf_token"
                            value="<?= securiser($_SESSION["csrf_token"]) ?>">

                        <input type="hidden"
                            name="avis_id"
                            value="<?= (int)$unAvis["id"] ?>">

                        <select name="statut_avis">

                            <option value="Validé">Valider l'avis</option>
                            <option value="Refusé">Refuser l'avis</option>

                        </select>

                        <button type="submit" name="modifier_avis">
                            Enregistrer
                        </button>

                    </form>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </section>

</div>

</body>
</html>
