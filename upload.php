<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
requireLogin();

$userId = $_SESSION['user_id'];

// Fetch categories for dropdown
$categories = $conn->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $documentName = sanitize($_POST['document_name']);
    $description  = sanitize($_POST['description']);
    $categoryId   = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;

    $errors = [];

    if (empty($documentName)) {
        $errors[] = "Document name is required.";
    }

    if (!isset($_FILES['document_file']) || $_FILES['document_file']['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = "Please choose a file to upload.";
    } else {
        $file = $_FILES['document_file'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Error uploading file. Please try again.";
        }

        $fileSize = $file['size'];
        if ($fileSize > MAX_FILE_SIZE) {
            $errors[] = "File size must be less than " . formatSize(MAX_FILE_SIZE) . ".";
        }

        $originalName = $file['name'];
        $extension    = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($extension, ALLOWED_EXTENSIONS)) {
            $errors[] = "Invalid file type. Allowed types: " . implode(', ', ALLOWED_EXTENSIONS);
        }
    }

    if (empty($errors)) {
        // ----- Generate a unique file name to avoid overwrite / collisions -----
        $uniqueName = uniqid('doc_', true) . '.' . $extension;
        $destination = UPLOAD_DIR . $uniqueName;

        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0755, true);
        }

        if (move_uploaded_file($file['tmp_name'], $destination)) {

            $relativePath = UPLOAD_DIR_REL . $uniqueName;
            $versionNo = 1.0;

            $stmt = $conn->prepare("INSERT INTO documents (user_id, category_id, document_name, description, file_name, file_path, file_type, file_size, version_no)
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisssssid", $userId, $categoryId, $documentName, $description, $uniqueName, $relativePath, $extension, $fileSize, $versionNo);

            if ($stmt->execute()) {
                $documentId = $stmt->insert_id;
                $stmt->close();

                // ----- Log this upload into the uploads (version history) table -----
                $vstmt = $conn->prepare("INSERT INTO uploads (document_id, uploaded_by, file_name, file_path, version_no, file_size)
                                          VALUES (?, ?, ?, ?, ?, ?)");
                $vstmt->bind_param("iissdi", $documentId, $userId, $uniqueName, $relativePath, $versionNo, $fileSize);
                $vstmt->execute();
                $vstmt->close();

                logActivity($conn, $userId, "Uploaded document: $documentName", "Document");

                setMessage('success', 'Document uploaded successfully!');
                redirect(BASE_URL . 'documents.php');
            } else {
                $errors[] = "Database error. Please try again.";
            }
        } else {
            $errors[] = "Failed to move uploaded file. Check folder permissions.";
        }
    }

    if (!empty($errors)) {
        setMessage('danger', implode('<br>', $errors));
    }
}

$pageTitle = "Upload Document";
require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold"><i class="bi bi-cloud-upload"></i> Upload New Document</div>
            <div class="card-body p-4">
                <form method="POST" action="upload.php" enctype="multipart/form-data" novalidate>
                    <div class="mb-3">
                        <label class="form-label">Document Name *</label>
                        <input type="text" name="document_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Optional short description..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">-- Select Category --</option>
                            <?php while ($cat = $categories->fetch_assoc()): ?>
                                <option value="<?php echo $cat['category_id']; ?>"><?php echo sanitize($cat['category_name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Choose File *</label>
                        <input type="file" name="document_file" class="form-control" required
                               accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.jpeg,.png">
                        <div class="form-text">Allowed: PDF, DOCX, DOC, PPT, PPTX, JPG, PNG. Max size: 10 MB.</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2"><i class="bi bi-upload"></i> Upload Document</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
