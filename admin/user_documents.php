<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/init.php';

// Vérifie si admin connecté
if (!is_admin_logged_in()) {
    header('Location: ../login.php');
    exit;
}

$conn = Connect();

// Récupère user_id depuis GET (obligatoire)
if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    $_SESSION['error'] = "Utilisateur non spécifié.";
    header('Location: users.php');
    exit;
}
$user_id = (int)$_GET['user_id'];

// Récupérer info utilisateur pour affichage titre
$stmt = $conn->prepare("SELECT name, email FROM users WHERE user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
if ($user_result->num_rows === 0) {
    $_SESSION['error'] = "Utilisateur introuvable.";
    header('Location: users.php');
    exit;
}
$user_info = $user_result->fetch_assoc();
$stmt->close();

// POST actions : add, edit, delete document
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // Ajouter un document
    if ($action === 'add_document') {
        $document_name = trim($_POST['document_name'] ?? '');

        if (empty($document_name) || !isset($_FILES['document_file'])) {
            $_SESSION['error'] = "Tous les champs sont obligatoires.";
            header("Location: user_documents.php?user_id=$user_id");
            exit;
        }

        // Validation upload
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
        $file_name = $_FILES['document_file']['name'];
        $file_tmp = $_FILES['document_file']['tmp_name'];
        $file_error = $_FILES['document_file']['error'];

        if ($file_error !== 0) {
            $_SESSION['error'] = "Erreur lors du téléchargement du fichier.";
            header("Location: user_documents.php?user_id=$user_id");
            exit;
        }

        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed_extensions)) {
            $_SESSION['error'] = "Type de fichier invalide. Autorisés: JPG, JPEG, PNG, PDF.";
            header("Location: user_documents.php?user_id=$user_id");
            exit;
        }

        $target_dir = "../uploads/user_documents/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $new_file_name = uniqid() . '.' . $ext;
        $target_file = $target_dir . $new_file_name;

        if (!move_uploaded_file($file_tmp, $target_file)) {
            $_SESSION['error'] = "Échec du déplacement du fichier.";
            header("Location: user_documents.php?user_id=$user_id");
            exit;
        }

        $sql = "INSERT INTO user_documents (user_id, document_name, file_path) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $file_path_db = "uploads/user_documents/" . $new_file_name;
        $stmt->bind_param('iss', $user_id, $document_name, $file_path_db);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Document ajouté avec succès.";
        } else {
            $_SESSION['error'] = "Échec de l'ajout du document.";
            @unlink($target_file);
        }
        $stmt->close();

        header("Location: user_documents.php?user_id=$user_id");
        exit;
    }

    // Supprimer un document
    if ($action === 'delete_document' && isset($_POST['document_id'])) {
        $document_id = $_POST['document_id'];

        $sql = "SELECT file_path FROM user_documents WHERE document_id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $document_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $file_path = null;
        if ($row = $result->fetch_assoc()) {
            $file_path = $row['file_path'];
        }
        $stmt->close();

        if ($file_path && file_exists("../" . $file_path)) {
            unlink("../" . $file_path);
        }

        $sql = "DELETE FROM user_documents WHERE document_id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $document_id, $user_id);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Document supprimé avec succès.";
        } else {
            $_SESSION['error'] = "Échec de la suppression du document.";
        }
        $stmt->close();

        header("Location: user_documents.php?user_id=$user_id");
        exit;
    }

    // Modifier un document
    if ($action === 'edit_document' && isset($_POST['document_id'])) {
        $document_id = $_POST['document_id'];
        $document_name = trim($_POST['document_name'] ?? '');

        if (empty($document_name)) {
            $_SESSION['error'] = "Le nom du document est obligatoire.";
            header("Location: user_documents.php?user_id=$user_id");
            exit;
        }

        if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === 0) {
            $sql = "SELECT file_path FROM user_documents WHERE document_id = ? AND user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ii', $document_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $old_file_path = null;
            if ($row = $result->fetch_assoc()) {
                $old_file_path = $row['file_path'];
            }
            $stmt->close();

            $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
            $file_name = $_FILES['document_file']['name'];
            $file_tmp = $_FILES['document_file']['tmp_name'];
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed_extensions)) {
                $_SESSION['error'] = "Type de fichier invalide.";
                header("Location: user_documents.php?user_id=$user_id");
                exit;
            }

            $target_dir = "../uploads/user_documents/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $new_file_name = uniqid() . '.' . $ext;
            $target_file = $target_dir . $new_file_name;

            if (!move_uploaded_file($file_tmp, $target_file)) {
                $_SESSION['error'] = "Échec du téléchargement du nouveau fichier.";
                header("Location: user_documents.php?user_id=$user_id");
                exit;
            }

            if ($old_file_path && file_exists("../" . $old_file_path)) {
                unlink("../" . $old_file_path);
            }

            $file_path_db = "uploads/user_documents/" . $new_file_name;

            $sql = "UPDATE user_documents SET document_name = ?, file_path = ? WHERE document_id = ? AND user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssii', $document_name, $file_path_db, $document_id, $user_id);
        } else {
            $sql = "UPDATE user_documents SET document_name = ? WHERE document_id = ? AND user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('sii', $document_name, $document_id, $user_id);
        }

        if ($stmt->execute()) {
            $_SESSION['success'] = "Document mis à jour avec succès.";
        } else {
            $_SESSION['error'] = "Échec de la mise à jour du document.";
        }
        $stmt->close();

        header("Location: user_documents.php?user_id=$user_id");
        exit;
    }
}

// Récupérer documents
$sql = "SELECT * FROM user_documents WHERE user_id = ? ORDER BY uploaded_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

include 'includes/header.php';
?>

<div class="main-content">
    <div class="container-fluid p-4">
        <h1 class="h3 mb-4"><?= sprintf(__('Documents de l’utilisateur : %s (%s)'), htmlspecialchars($user_info['name']), htmlspecialchars($user_info['email'])); ?></h1>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <!-- Formulaire ajout -->
        <div class="card mb-4">
            <div class="card-header"><h5>Ajouter un document</h5></div>
            <div class="card-body">
                <form method="POST" action="user_documents.php?user_id=<?= $user_id; ?>" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_document">

                    <div class="mb-3">
                        <label for="document_name" class="form-label">Nom du document</label>
                        <input type="text" id="document_name" name="document_name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="document_file" class="form-label">Fichier</label>
                        <input type="file" class="form-control" id="document_file" name="document_file" required>
                    </div>

                    <button type="submit" class="btn btn-primary">Ajouter</button>
                    <a href="users.php" class="btn btn-secondary ms-2">Retour</a>
                </form>
            </div>
        </div>

        <!-- Tableau documents -->
        <div class="card shadow">
            <div class="card-body">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom du document</th>
                            <th>Fichier</th>
                            <th>Date upload</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($doc = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $doc['document_id']; ?></td>
                                <td><?= htmlspecialchars($doc['document_name']); ?></td>
                                <td><a href="../<?= htmlspecialchars($doc['file_path']); ?>" target="_blank">Voir</a></td>
                                <td><?= format_date($doc['uploaded_at']); ?></td>
                                <td>
                                    <!-- Bouton Modifier -->
                                    <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $doc['document_id']; ?>">Modifier</button>
                                    <!-- Bouton Supprimer -->
                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $doc['document_id']; ?>">Supprimer</button>

                                    <!-- Modal Modifier -->
                                    <div class="modal fade" id="editModal<?= $doc['document_id']; ?>">
                                        <div class="modal-dialog">
                                            <form method="POST" action="user_documents.php?user_id=<?= $user_id; ?>" enctype="multipart/form-data" class="modal-content">
                                                <input type="hidden" name="action" value="edit_document">
                                                <input type="hidden" name="document_id" value="<?= $doc['document_id']; ?>">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Modifier le document</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Nom du document</label>
                                                        <input type="text" name="document_name" class="form-control" value="<?= htmlspecialchars($doc['document_name']); ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Remplacer le fichier</label>
                                                        <input type="file" name="document_file" class="form-control">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>

                                    <!-- Modal Supprimer -->
                                    <div class="modal fade" id="deleteModal<?= $doc['document_id']; ?>">
                                        <div class="modal-dialog">
                                            <form method="POST" action="user_documents.php?user_id=<?= $user_id; ?>" class="modal-content">
                                                <input type="hidden" name="action" value="delete_document">
                                                <input type="hidden" name="document_id" value="<?= $doc['document_id']; ?>">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Supprimer le document</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Êtes-vous sûr de vouloir supprimer ce document ?
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                    <button type="submit" class="btn btn-danger">Supprimer</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>

                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center">Aucun document trouvé</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<?php
$conn->close();
include 'includes/footer.php';
