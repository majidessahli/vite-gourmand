<?php
session_start();
require_once "config.php";

// Vérification de la connexion du client
$connecte = isset($_SESSION["utilisateur_id"]);

// Récupération des filtres
$prixMax = $_GET["prix"] ?? "";
$theme = $_GET["theme"] ?? "";
$regime = $_GET["regime"] ?? "";

// Recherche des menus actifs
$sql = "SELECT * FROM menus WHERE actif = 1";
$parametres = [];

if ($prixMax !== "" && is_numeric($prixMax)) {
    $sql .= " AND prix_minimum <= ?";
    $parametres[] = (float) $prixMax;
}

if ($theme !== "") {
    $sql .= " AND theme = ?";
    $parametres[] = $theme;
}

if ($regime !== "") {
    $sql .= " AND type_regime = ?";
    $parametres[] = $regime;
}

$sql .= " ORDER BY prix_minimum ASC";

$requete = $pdo->prepare($sql);
$requete->execute($parametres);
$menus = $requete->fetchAll(PDO::FETCH_ASSOC);

// Récupération des thèmes disponibles
$themes = $pdo->query(
    "SELECT DISTINCT theme FROM menus
     WHERE actif = 1 AND theme IS NOT NULL AND theme != ''
     ORDER BY theme"
)->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nos menus - Vite & Gourmand</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<header>
    <h1>Vite & Gourmand</h1>

    <nav>
        <a href="index.php">Accueil</a>
        <a href="menus.php">Nos menus</a>

        <?php if ($connecte): ?>
            <a href="compte.php">Mon compte</a>
            <a href="deconnexion.php">Déconnexion</a>
        <?php else: ?>
            <a href="connexion.php">Connexion</a>
            <a href="inscription.php">Inscription</a>
        <?php endif; ?>
    </nav>
</header>

<main class="catalogue">

    <h2>Nos menus</h2>
    <p>Découvrez nos menus et choisissez celui qui vous convient.</p>

    <!-- Filtres de recherche -->
    <form method="GET" action="menus.php" class="filtres">

        <label for="prix">Prix maximum (€)</label>
        <input type="number" id="prix" name="prix"
               min="0" step="0.01"
               value="<?= htmlspecialchars($prixMax) ?>">

        <label for="theme">Thème</label>
        <select name="theme" id="theme">
            <option value="">Tous les thèmes</option>

            <?php foreach ($themes as $nomTheme): ?>
                <option value="<?= htmlspecialchars($nomTheme) ?>"
                    <?= $theme === $nomTheme ? "selected" : "" ?>>
                    <?= htmlspecialchars($nomTheme) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="regime">Régime alimentaire</label>
        <select name="regime" id="regime">
            <option value="">Tous les régimes</option>
            <option value="Classique"
                <?= $regime === "Classique" ? "selected" : "" ?>>
                Classique
            </option>
            <option value="Végétarien"
                <?= $regime === "Végétarien" ? "selected" : "" ?>>
                Végétarien
            </option>
        </select>

        <button type="submit">Rechercher</button>
        <a href="menus.php">Réinitialiser</a>

    </form>

    <!-- Affichage des menus -->
    <section class="menus">

        <?php if (!empty($menus)): ?>

            <?php foreach ($menus as $menu): ?>

                <article class="carte-menu">

                    <h3><?= htmlspecialchars($menu["titre"]) ?></h3>

                    <?php if (!empty($menu["image_url"])): ?>
                        <img
                            src="<?= htmlspecialchars($menu["image_url"]) ?>"
                            alt="<?= htmlspecialchars($menu["titre"]) ?>"
                            style="max-width: 100%; height: auto;"
                        >
                    <?php endif; ?>

                    <p><?= htmlspecialchars($menu["description"]) ?></p>

                    <p>
                        Thème : <?= htmlspecialchars($menu["theme"]) ?>
                    </p>

                    <p>
                        Minimum :
                        <?= (int) $menu["personnes_min"] ?> personnes
                    </p>

                    <p class="prix">
                        <?= number_format((float) $menu["prix_minimum"], 2, ",", " ") ?> €
                    </p>

                    <p>
                        Régime :
                        <?= htmlspecialchars($menu["type_regime"]) ?>
                    </p>

                    <p>
                        Stock disponible :
                        <?= (int) $menu["stock"] ?>
                    </p>

                    <?php if (!empty($menu["conditions_commande"])): ?>
                        <p>
                            Conditions :
                            <?= htmlspecialchars($menu["conditions_commande"]) ?>
                        </p>
                    <?php endif; ?>

                    <!-- Accès à la commande -->
                    <?php if ($connecte): ?>
                        <a href="commander.php" class="bouton">
                            Commander
                        </a>
                    <?php else: ?>
                        <a href="connexion.php" class="bouton">
                            Commander
                        </a>
                    <?php endif; ?>

                </article>

            <?php endforeach; ?>

        <?php else: ?>

            <p>Aucun menu ne correspond à votre recherche.</p>

        <?php endif; ?>

    </section>

</main>

<footer>
    <p>Vite & Gourmand - Bordeaux</p>
    <p>© 2026 - Tous droits réservés</p>
</footer>

</body>
</html>
