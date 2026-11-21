<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
requireLogin();

$userId = $_SESSION['user_id'];
$docId  = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Fetch the document, ensuring it belongs to the logged-in user
$stmt = $conn->prepare("SELECT * FROM documents WHERE document_id = ? AND user_id = ? AND status = 'active'");
$stmt->bind_param("ii", $docId, $userId);
$stmt->execute();
$document = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$document) {
    setMessage('danger', 'Document not found or access denied.');
    redirect(BASE_URL . 'documents.php');
}

$categories = $conn->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $documentName = sanitize($_POST['document_name']);
    $description  = sanitize($_POST['description']);
    $categoryId   = !empty($_POST['category_id']) ? (int) $_POST['category_id'] : null;

    $errors = [];
    if (empty($documentName)) {
        $errors[] = "Document name is required.";
    }

    // ----- Optional: re-upload a new version of the file -----
    $newVersionUploaded = false;
    if (isset($_FILES['new_file']) && $_FILES['new_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['new_file'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, ALLOWED_EXTENSIONS)) {
            $errors[] = "Invalid file type for new version.";
        } elseif ($file['size'] > MAX_FILE_SIZE) {
            $errors[] = "New file exceeds the 10 MB size limit.";
        } else {
            $uniqueName = uniqid('doc_', true) . '.' . $extension;
            $destination = UPLOAD_DIR . $uniqueName;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $newVersionUploaded = true;
                $newVersionNo = round($document['version_no'] + 1.0, 1);
                $relativePath = UPLOAD_DIR_REL . $uniqueName;
                $fileSize = $file['size'];
            } else {
                $errors[] = "Failed to upload new version.";
            }
        }
    }

    if (empty($errors)) {
        if ($newVersionUploaded) {
            $update = $conn->prepare("UPDATE documents SET document_name=?, description=?, category_id=?, file_name=?, file_path=?, file_type=?, file_size=?, version_no=? WHERE document_id=?");
            $update->bind_param("ssisssidi", $documentName, $description, $categoryId, $uniqueName, $relativePath, $extension, $fileSize, $newVersionNo, $docId);
            $update->execute();
            $update->close();

            // Log new version into uploads table (version history)
            $vstmt = $conn->prepare("INSERT INTO uploads (document_id, uploaded_by, file_name, file_path, version_no, file_size) VALUES (?, ?, ?, ?, ?, ?)");
            $vstmt->bind_param("iissdi", $docId, $userId, $uniqueName, $relativePath, $newVersionNo, $fileSize);
            $vstmt->execute();
            $vstmt->close();

            logActivity($conn, $userId, "Uploaded new version of: $documentName", "Document");
        } else {
            $update = $conn->prepare("UPDATE documents SET document_name=?, description=?, category_id=? WHERE document_id=?");
            $update->bind_param("ssii", $documentName, $description, $categoryId, $docId);
            $update->execute();
            $update->close();

            logActivity($conn, $userId, "Edited document details: $documentName", "Document");
        }

        setMessage('success', 'Document updated successfully.');
        redirect(BASE_URL . 'documents.php?view=' . $docId);
    } else {
        setMessage('danger', implode('<br>', $errors));
    }
}

$pageTitle = "Edit Document";
require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold"><i class="bi bi-pencil-square"></i> Edit Document Details</div>
            <div class="card-body p-4">
                <form method="POST" action="edit-document.php?id=<?php echo $docId; ?>" enctype="multipart/form-data" novalidate>
                    <div class="mb-3">
                        <label class="form-label">Document Name *</label>
                        <input type="text" name="document_name" class="form-control" required value="<?php echo sanitize($document['document_name']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"><?php echo sanitize($document['description']); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">-- Select Category --</option>
                            <?php while ($cat = $categories->fetch_assoc()): ?>
                                <option value="<?php echo $cat['category_id']; ?>" <?php echo $cat['category_id'] == $document['category_id'] ? 'selected' : ''; ?>>
                                    <?php echo sanitize($cat['category_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Replace File (optional - creates new version)</label>
                        <input type="file" name="new_file" class="form-control" accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.jpeg,.png">
                        <div class="form-text">Current version: v<?php echo $document['version_no']; ?>. Leave empty to keep the existing file.</div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-save"></i> Save Changes</button>
                        <a href="documents.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
