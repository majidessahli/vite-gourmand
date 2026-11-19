<?php
session_start();
require_once "config.php";

// Vérifie que seul l'administrateur peut accéder à cette page
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: connexion.php");
    exit;
}

// Création du jeton de sécurité
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$message = "";

// Traitement des actions du formulaire
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Vérifie le jeton de sécurité
    if (!hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"] ?? "")) {
        die("Erreur de sécurité.");
    }

    // Ajout d'un nouveau menu
    if (isset($_POST["ajouter"])) {

        $titre = trim($_POST["titre"] ?? "");
        $description = trim($_POST["description"] ?? "");
        $theme = trim($_POST["theme"] ?? "");
        $personnesMin = (int) ($_POST["personnes_min"] ?? 0);
        $prix = (float) ($_POST["prix_minimum"] ?? 0);
        $regime = trim($_POST["type_regime"] ?? "");
        $stock = (int) ($_POST["stock"] ?? 0);
        $conditions = trim($_POST["conditions_commande"] ?? "");
        $image = trim($_POST["image_url"] ?? "");

        // Vérifie les informations obligatoires
        if (
            $titre !== "" &&
            $description !== "" &&
            $theme !== "" &&
            $personnesMin > 0 &&
            $prix > 0 &&
            $regime !== ""
        ) {
            // Enregistre le menu dans la base de données
            $requete = $pdo->prepare(
                "INSERT INTO menus
                (titre, description, theme, personnes_min,
                prix_minimum, type_regime, stock,
                conditions_commande, image_url, actif)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)"
            );

            $requete->execute([
                $titre,
                $description,
                $theme,
                $personnesMin,
                $prix,
                $regime,
                $stock,
                $conditions,
                $image
            ]);

            $message = "Menu ajouté avec succès !";

        } else {
            $message = "Veuillez remplir correctement les champs obligatoires.";
        }
    }

    // Activation ou désactivation d'un menu
    if (isset($_POST["changer_statut"])) {

        $id = (int) ($_POST["menu_id"] ?? 0);

        if ($id > 0) {
            $requete = $pdo->prepare(
                "UPDATE menus
                 SET actif = IF(actif = 1, 0, 1)
                 WHERE id = ?"
            );

            $requete->execute([$id]);

            $message = "Statut du menu modifié.";
        }
    }
}

// Récupération de tous les menus
$menus = $pdo->query(
    "SELECT * FROM menus ORDER BY id DESC"
)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gestion des menus - Vite & Gourmand</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

<header>
    <h1>Administration - Vite & Gourmand</h1>

    <nav>
        <a href="admin.php">Administration</a>
        <a href="employer.php">Gestion des commandes</a>
        <a href="index.php">Accueil du site</a>
        <a href="deconnexion.php">Déconnexion</a>
    </nav>
</header>

<main class="catalogue">

    <h2>Gestion des menus</h2>

    <!-- Affichage des messages -->
    <?php if ($message !== ""): ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <!-- Formulaire d'ajout d'un menu -->
    <section>
        <h3>Ajouter un menu</h3>

        <form method="POST" action="gestion_menus.php">

            <input type="hidden" name="csrf_token"
                   value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">

            <label for="titre">Nom du menu</label>
            <input type="text" id="titre" name="titre" required>

            <label for="description">Description</label>
            <textarea id="description" name="description" required></textarea>

            <label for="theme">Thème</label>
            <input type="text" id="theme" name="theme" required>

            <label for="personnes_min">Nombre minimum de personnes</label>
            <input type="number" id="personnes_min"
                   name="personnes_min" min="1" required>

            <label for="prix_minimum">Prix minimum (€)</label>
            <input type="number" id="prix_minimum"
                   name="prix_minimum" min="0.01" step="0.01" required>

            <label for="type_regime">Régime alimentaire</label>
            <input type="text" id="type_regime"
                   name="type_regime" required>

            <label for="stock">Stock disponible</label>
            <input type="number" id="stock" name="stock" min="0" value="0">

            <label for="conditions_commande">Conditions de commande</label>
            <textarea id="conditions_commande"
                      name="conditions_commande"></textarea>

            <label for="image_url">Adresse de l'image</label>
            <input type="url" id="image_url" name="image_url">

            <button type="submit" name="ajouter">
                Ajouter le menu
            </button>

        </form>
    </section>

    <!-- Liste des menus enregistrés -->
    <section>
        <h3>Menus existants</h3>

        <?php if (count($menus) > 0): ?>

            <?php foreach ($menus as $menu): ?>

                <article class="carte-menu">

                    <h4><?= htmlspecialchars($menu["titre"]) ?></h4>

                    <p>
                        <?= htmlspecialchars($menu["description"]) ?>
                    </p>

                    <p>
                        Prix minimum :
                        <?= number_format((float) $menu["prix_minimum"], 2, ",", " ") ?> €
                    </p>

                    <p>
                        Statut :
                        <?= $menu["actif"] ? "Actif" : "Désactivé" ?>
                    </p>

                    <!-- Bouton pour changer le statut -->
                    <form method="POST" action="gestion_menus.php">

                        <input type="hidden" name="csrf_token"
                               value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">

                        <input type="hidden" name="menu_id"
                               value="<?= (int) $menu["id"] ?>">

                        <button type="submit" name="changer_statut">
                            <?= $menu["actif"] ? "Désactiver" : "Activer" ?>
                        </button>

                    </form>

                </article>

            <?php endforeach; ?>

        <?php else: ?>

            <p>Aucun menu enregistré pour le moment.</p>

        <?php endif; ?>

    </section>

</main>

<footer>
    <p>Vite & Gourmand - Bordeaux</p>
    <p>© 2026 - Tous droits réservés</p>
</footer>

</body>
</html>
