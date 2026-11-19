<?php
session_start();
require_once "config.php";

// Vérification administrateur
if (
    !isset($_SESSION["utilisateur_id"]) ||
    ($_SESSION["role"] ?? "") !== "admin"
) {
    header("Location: connexion.php");
    exit;
}

$message = "";

// Protection CSRF
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Traitement des actions
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        die("Erreur de sécurité.");
    }

    // Création d'un employé
    if (isset($_POST["creer_employe"])) {

        $nom = trim($_POST["nom"] ?? "");
        $prenom = trim($_POST["prenom"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $telephone = trim($_POST["telephone"] ?? "");
        $adresse = trim($_POST["adresse"] ?? "");
        $motDePasse = $_POST["mot_de_passe"] ?? "";

        if (
            $nom !== "" &&
            $prenom !== "" &&
            filter_var($email, FILTER_VALIDATE_EMAIL) &&
            strlen($motDePasse) >= 10 &&
            preg_match('/[A-Z]/', $motDePasse) &&
            preg_match('/[a-z]/', $motDePasse) &&
            preg_match('/[0-9]/', $motDePasse) &&
            preg_match('/[^A-Za-z0-9]/', $motDePasse)
        ) {
            try {
                $hash = password_hash($motDePasse, PASSWORD_DEFAULT);

                $requete = $pdo->prepare("
                    INSERT INTO utilisateurs
                    (nom, prenom, telephone, email, adresse, mot_de_passe, role, actif)
                    VALUES (?, ?, ?, ?, ?, ?, 'employe', 1)
                ");

                $requete->execute([
                    $nom, $prenom, $telephone,
                    $email, $adresse, $hash
                ]);

                $message = "Employé créé avec succès.";

            } catch (PDOException $e) {
                $message = "Impossible de créer le compte. Vérifiez que l'adresse email n'est pas déjà utilisée.";
            }
        } else {
            $message = "Informations invalides. Vérifiez le mot de passe et les champs.";
        }
    }

    // Activation / désactivation d'un employé
    if (isset($_POST["changer_actif"])) {

        $id = filter_var($_POST["employe_id"], FILTER_VALIDATE_INT);
        $actif = $_POST["actif"] ?? "";

        if ($id && in_array($actif, ["0", "1"], true)) {

            $requete = $pdo->prepare("
                UPDATE utilisateurs
                SET actif = ?
                WHERE id = ? AND role = 'employe'
            ");

            $requete->execute([(int)$actif, $id]);

            $message = "État du compte mis à jour.";
        }
    }
}

// Liste des employés
$employes = $pdo->query("
    SELECT id, nom, prenom, email, telephone, actif
    FROM utilisateurs
    WHERE role = 'employe'
    ORDER BY nom
")->fetchAll(PDO::FETCH_ASSOC);

// Statistiques des menus
$statistiques = $pdo->query("
    SELECT menu,
           COUNT(*) AS nombre_commandes,
           SUM(prix_total) AS chiffre_affaires
    FROM commandes
    WHERE statut NOT IN ('Refusée', 'Annulée')
    GROUP BY menu
    ORDER BY chiffre_affaires DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Chiffre d'affaires total
$chiffreAffaires = $pdo->query("
    SELECT COALESCE(SUM(prix_total), 0)
    FROM commandes
    WHERE statut NOT IN ('Refusée', 'Annulée')
")->fetchColumn();

function e($valeur) {
    return htmlspecialchars((string)$valeur, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Vite & Gourmand</title>
    <link rel="stylesheet" href="style.css">

    <style>
        body {
            background: #f5f2e9;
            font-family: Arial, sans-serif;
            padding: 20px;
        }

        .conteneur {
            max-width: 1100px;
            margin: auto;
        }

        .bloc {
            background: white;
            padding: 22px;
            margin: 22px 0;
            border-radius: 10px;
        }

        h1, h2 {
            color: #355b42;
        }

        input, select, button {
            padding: 10px;
            margin: 5px;
            max-width: 100%;
        }

        button {
            background: #355b42;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #355b42;
            color: white;
        }

        .tableau-conteneur {
            overflow-x: auto;
        }

        .message {
            background: #d9edda;
            padding: 12px;
            border-radius: 5px;
        }

        .montant {
            font-size: 25px;
            font-weight: bold;
            color: #355b42;
        }
    </style>
</head>

<body>

<div class="conteneur">

    <h1>Administration Vite & Gourmand</h1>

    <p>Bienvenue <?= e($_SESSION["prenom"] ?? "Administrateur") ?>.</p>

    <a href="index.php">Retour au site</a> |
    <a href="employer.php">Espace employé</a> |
    <a href="deconnexion.php">Déconnexion</a>

    <?php if ($message !== ""): ?>
        <p class="message"><?= e($message) ?></p>
    <?php endif; ?>

    <section class="bloc">
        <h2>Chiffre d'affaires global</h2>
        <p class="montant">
            <?= number_format((float)$chiffreAffaires, 2, ",", " ") ?> €
        </p>
    </section>

    <section class="bloc">
        <h2>Créer un employé</h2>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION["csrf_token"]) ?>">

            <input type="text" name="nom" placeholder="Nom" required>
            <input type="text" name="prenom" placeholder="Prénom" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="text" name="telephone" placeholder="Téléphone">
            <input type="text" name="adresse" placeholder="Adresse">

            <input type="password" name="mot_de_passe"
                placeholder="Mot de passe sécurisé" minlength="10" required>

            <button type="submit" name="creer_employe">
                Créer le compte
            </button>
        </form>
    </section>

    <section class="bloc">
        <h2>Gestion des employés</h2>

        <div class="tableau-conteneur">
        <table>
            <tr>
                <th>Nom</th>
                <th>Email</th>
                <th>Téléphone</th>
                <th>État</th>
                <th>Action</th>
            </tr>

            <?php foreach ($employes as $employe): ?>
                <tr>
                    <td><?= e($employe["prenom"] . " " . $employe["nom"]) ?></td>
                    <td><?= e($employe["email"]) ?></td>
                    <td><?= e($employe["telephone"]) ?></td>
                    <td><?= $employe["actif"] ? "Actif" : "Désactivé" ?></td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION["csrf_token"]) ?>">
                            <input type="hidden" name="employe_id" value="<?= (int)$employe["id"] ?>">

                            <input type="hidden" name="actif"
                                value="<?= $employe["actif"] ? "0" : "1" ?>">

                            <button type="submit" name="changer_actif">
                                <?= $employe["actif"] ? "Désactiver" : "Activer" ?>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        </div>
    </section>

    <section class="bloc">
        <h2>Statistiques des menus</h2>

        <div class="tableau-conteneur">
        <table>
            <tr>
                <th>Menu</th>
                <th>Nombre de commandes</th>
                <th>Chiffre d'affaires</th>
            </tr>

            <?php foreach ($statistiques as $stat): ?>
                <tr>
                    <td><?= e($stat["menu"]) ?></td>
                    <td><?= (int)$stat["nombre_commandes"] ?></td>
                    <td><?= number_format((float)$stat["chiffre_affaires"], 2, ",", " ") ?> €</td>
                </tr>
            <?php endforeach; ?>
        </table>
        </div>
    </section>

</div>

</body>
</html>
