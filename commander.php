<?php
session_start();
require_once "config.php";

if (!isset($_SESSION["utilisateur_id"])) {
    header("Location: connexion.php");
    exit;
}

$menus = [
    "Menu Gourmand" => ["prix" => 35, "minimum" => 2],
    "Menu Végétarien" => ["prix" => 30, "minimum" => 2],
    "Menu Festif" => ["prix" => 55, "minimum" => 4]
];

$message = "";

$menuChoisi = $_POST["menu"] ?? "";
$personnesChoisies = $_POST["personnes"] ?? "";
$dateChoisie = $_POST["date"] ?? "";
$heureChoisie = $_POST["heure"] ?? "";
$adresseChoisie = $_POST["adresse"] ?? "";
$horsBordeaux = isset($_POST["hors_bordeaux"]);
$distanceSaisie = $_POST["distance_km"] ?? "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $menu = $menuChoisi;
    $personnes = (int)$personnesChoisies;
    $date = $dateChoisie;
    $heure = $heureChoisie;
    $adresse = trim($adresseChoisie);

    $distanceKm = $horsBordeaux && is_numeric($distanceSaisie)
        ? (float)$distanceSaisie : 0;

    if (
        !isset($menus[$menu]) ||
        $personnes < ($menus[$menu]["minimum"] ?? 1) ||
        !$date || !$heure || !$adresse ||
        ($horsBordeaux && (!is_numeric($distanceSaisie) || $distanceKm < 0))
    ) {
        $message = "Veuillez vérifier tous les champs et le nombre minimum de personnes.";
    } else {

        // Calcul du prix des menus
        $prixUnitaire = $menus[$menu]["prix"];
        $minimum = $menus[$menu]["minimum"];

        $total = $prixUnitaire * $personnes;

        // Réduction de 10 % à partir de 5 personnes au-dessus du minimum
        if ($personnes >= $minimum + 5) {
            $total *= 0.90;
        }

        // Calcul des frais de livraison
        $fraisLivraison = 5;

        if ($horsBordeaux) {
            $fraisLivraison += $distanceKm * 0.59;
        }

        $totalFinal = $total + $fraisLivraison;

        $sql = "INSERT INTO commandes
        (utilisateur_id, menu, nombre_personnes, date_evenement,
        adresse_evenement, prix_total, heure_prestation,
        date_livraison, heure_livraison, adresse_livraison,
        distance_km, frais_livraison)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $requete = $pdo->prepare($sql);

        $requete->execute([
            $_SESSION["utilisateur_id"],
            $menu,
            $personnes,
            $date,
            $adresse,
            $totalFinal,
            $heure,
            $date,
            $heure,
            $adresse,
            $distanceKm,
            $fraisLivraison
        ]);

        $message = "Votre commande a bien été enregistrée !";

        $menuChoisi = "";
        $personnesChoisies = "";
        $dateChoisie = "";
        $heureChoisie = "";
        $adresseChoisie = "";
        $distanceSaisie = "";
        $horsBordeaux = false;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commander - Vite &amp; Gourmand</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<header>
    <h1>Vite &amp; Gourmand</h1>
    <nav>
        <a href="index.php">Accueil</a>
        <a href="menus.php">Nos menus</a>
        <a href="compte.php">Mon compte</a>
    </nav>
</header>

<main class="presentation">

    <h2>Passer une commande</h2>

    <p><?= htmlspecialchars($message) ?></p>

    <form method="POST">

        <p>
            <label>Choisir un menu</label><br>
            <select name="menu" id="menu" required>
                <option value="">Sélectionner un menu</option>

                <?php foreach ($menus as $nom => $details): ?>
                    <option
                        value="<?= htmlspecialchars($nom) ?>"
                        data-prix="<?= $details["prix"] ?>"
                        data-minimum="<?= $details["minimum"] ?>"
                        <?= $menuChoisi === $nom ? "selected" : "" ?>
                    >
                        <?= htmlspecialchars($nom) ?> -
                        <?= $details["prix"] ?> € / personne
                    </option>
                <?php endforeach; ?>

            </select>
        </p>

        <p>
            <label>Nombre de personnes</label><br>
            <input
                type="number"
                name="personnes"
                id="personnes"
                min="2"
                value="<?= htmlspecialchars((string)$personnesChoisies) ?>"
                required
            >
        </p>

        <p>
            <label>Date de prestation</label><br>
            <input
                type="date"
                name="date"
                min="<?= date('Y-m-d') ?>"
                value="<?= htmlspecialchars($dateChoisie) ?>"
                required
            >
        </p>

        <p>
            <label>Heure de prestation</label><br>
            <input
                type="time"
                name="heure"
                value="<?= htmlspecialchars($heureChoisie) ?>"
                required
            >
        </p>

        <p>
            <label>Adresse de livraison</label><br>
            <input
                type="text"
                name="adresse"
                placeholder="Adresse complète"
                value="<?= htmlspecialchars($adresseChoisie) ?>"
                required
            >
        </p>

        <p>
            <label>
                <input
                    type="checkbox"
                    name="hors_bordeaux"
                    id="hors_bordeaux"
                    value="1"
                    <?= $horsBordeaux ? "checked" : "" ?>
                >
                Livraison hors Bordeaux
            </label>
        </p>

        <p id="distance-bloc" style="display:none;">
            <label>Distance supplémentaire en kilomètres</label><br>
            <input
                type="number"
                name="distance_km"
                id="distance_km"
                min="0"
                step="0.1"
                value="<?= htmlspecialchars((string)$distanceSaisie) ?>"
            >
            <small>Tarif : 0,59 € par kilomètre.</small>
        </p>

        <p>Frais de livraison de base : 5 €.</p>

        <p>
            Réduction de 10 % à partir de 5 personnes au-dessus
            du minimum du menu.
        </p>

        <section class="calcul-prix">
            <h3>Récapitulatif de votre commande</h3>

            <p id="prix-menus">Prix des menus : -- €</p>
            <p id="reduction">Réduction : 0 €</p>
            <p id="livraison">Frais de livraison : 5 €</p>

            <h3 id="total">Total à payer : -- €</h3>
        </section>

        <button type="submit">Confirmer ma commande</button>

    </form>

</main>

<footer>
    <p>Vite &amp; Gourmand - Bordeaux</p>
</footer>

<script>

const menu = document.getElementById("menu");
const personnes = document.getElementById("personnes");
const horsBordeaux = document.getElementById("hors_bordeaux");
const distance = document.getElementById("distance_km");
const distanceBloc = document.getElementById("distance-bloc");

const prixMenus = document.getElementById("prix-menus");
const reduction = document.getElementById("reduction");
const livraison = document.getElementById("livraison");
const total = document.getElementById("total");

// Affiche ou masque le champ de distance
function afficherDistance() {
    distanceBloc.style.display = horsBordeaux.checked ? "block" : "none";
    distance.required = horsBordeaux.checked;

    if (!horsBordeaux.checked) {
        distance.value = "";
    }

    calculerPrix();
}

// Calcul automatique du montant
function calculerPrix() {

    const option = menu.options[menu.selectedIndex];

    if (!option.dataset.prix) {
        prixMenus.textContent = "Prix des menus : -- €";
        reduction.textContent = "Réduction : 0 €";
        livraison.textContent = "Frais de livraison : 5 €";
        total.textContent = "Total à payer : -- €";
        return;
    }

    const prixUnitaire = Number(option.dataset.prix);
    const minimum = Number(option.dataset.minimum);
    const nombre = Number(personnes.value);

    if (nombre < minimum || nombre <= 0) {
        prixMenus.textContent = "Prix des menus : -- €";
        reduction.textContent = "Réduction : 0 €";
        livraison.textContent = "Frais de livraison : 5 €";
        total.textContent = "Total à payer : -- €";
        return;
    }

    let prixTotal = prixUnitaire * nombre;

    prixMenus.textContent =
        "Prix des menus : " + prixTotal.toFixed(2) + " €";

    let montantReduction = 0;

    if (nombre >= minimum + 5) {
        montantReduction = prixTotal * 0.10;
        prixTotal -= montantReduction;
    }

    reduction.textContent =
        "Réduction : -" + montantReduction.toFixed(2) + " €";

    let fraisLivraison = 5;

    if (horsBordeaux.checked) {
        const km = Number(distance.value) || 0;
        fraisLivraison += km * 0.59;
    }

    livraison.textContent =
        "Frais de livraison : " + fraisLivraison.toFixed(2) + " €";

    const prixFinal = prixTotal + fraisLivraison;

    total.textContent =
        "Total à payer : " + prixFinal.toFixed(2) + " €";
}

menu.addEventListener("change", calculerPrix);
personnes.addEventListener("input", calculerPrix);
horsBordeaux.addEventListener("change", afficherDistance);
distance.addEventListener("input", calculerPrix);

afficherDistance();

</script>

</body>
</html>
