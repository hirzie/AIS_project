<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/guard.php';

// Only allow logged in users (Wali Kelas / Admin)
// In a real scenario, check if user is authorized for this specific class
// For now, rely on session and class_slug provided (and maybe verify ownership)

$action = $_GET['action'] ?? '';
$slug = $_GET['slug'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

if (!$slug) {
    echo json_encode(['success' => false, 'message' => 'Slug required']);
    exit;
}

// Security: Verify user has access to this class (optional but recommended)
// ...

if ($method === 'GET') {
    if ($action === 'get_images') {
        try {
            $stmt = $pdo->prepare("SELECT id, content, is_active, created_at FROM app_display_messages WHERE target_slug = ? AND type = 'IMAGE_OVERRIDE' ORDER BY created_at DESC");
            $stmt->execute([$slug]);
            $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $images]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    try {
        // Fetch active settings
        // 1. Ticker (INFO)
        $stmtInfo = $pdo->prepare("SELECT content, is_active FROM app_display_messages WHERE target_slug = ? AND type = 'INFO' ORDER BY id DESC LIMIT 1");
        $stmtInfo->execute([$slug]);
        $info = $stmtInfo->fetch(PDO::FETCH_ASSOC);

        // 2. Urgent (URGENT)
        $stmtUrgent = $pdo->prepare("SELECT content, is_active FROM app_display_messages WHERE target_slug = ? AND type = 'URGENT' ORDER BY id DESC LIMIT 1");
        $stmtUrgent->execute([$slug]);
        $urgent = $stmtUrgent->fetch(PDO::FETCH_ASSOC);

        // 3. Image Override (IMAGE_OVERRIDE)
        // Prefer active image, otherwise latest
        $stmtImg = $pdo->prepare("SELECT content, is_active FROM app_display_messages WHERE target_slug = ? AND type = 'IMAGE_OVERRIDE' ORDER BY is_active DESC, id DESC LIMIT 1");
        $stmtImg->execute([$slug]);
        $img = $stmtImg->fetch(PDO::FETCH_ASSOC);

        // 4. PDF Override (PDF_OVERRIDE)
        $stmtPdf = $pdo->prepare("SELECT id, content, is_active, metadata FROM app_display_messages WHERE target_slug = ? AND type = 'PDF_OVERRIDE' ORDER BY is_active DESC, created_at DESC LIMIT 1");
        $stmtPdf->execute([$slug]);
        $pdf = $stmtPdf->fetch(PDO::FETCH_ASSOC);
        $pdfMetadata = $pdf ? json_decode($pdf['metadata'] ?? '{}', true) : [];
        $pdfPage = $pdfMetadata['current_page'] ?? 1;
        $laserActive = $pdfMetadata['laser_active'] ?? false;
        $laserX = $pdfMetadata['laser_x'] ?? 0;
        $laserY = $pdfMetadata['laser_y'] ?? 0;

        // 5. AI Override (AI_OVERRIDE)
        $stmtAi = $pdo->prepare("SELECT id, content, is_active FROM app_display_messages WHERE target_slug = ? AND type = 'AI_OVERRIDE' ORDER BY is_active DESC, created_at DESC LIMIT 1");
        $stmtAi->execute([$slug]);
        $ai = $stmtAi->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => [
                'tickerText' => $info['content'] ?? '',
                'tickerActive' => (bool)($info['is_active'] ?? 0),
                'urgentText' => $urgent['content'] ?? '',
                'urgentActive' => (bool)($urgent['is_active'] ?? 0),
                'imageOverride' => $img['content'] ?? '',
                'imageActive' => (bool)($img['is_active'] ?? 0),
                'pdfActive' => (bool)($pdf['is_active'] ?? 0),
                'pdfUrl' => $pdf['content'] ?? '',
                'pdfId' => $pdf['id'] ?? null,
                'pdfPage' => $pdfPage,
                'laserActive' => (bool)$laserActive,
                'laserX' => $laserX,
                'laserY' => $laserY,
                'aiActive' => (bool)($ai['is_active'] ?? 0)
            ]
        ]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} 
elseif ($method === 'POST') {
    $input = [];
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true);
    } else {
        $input = $_POST;
    }
    
    // Handle Image Management Actions
    if ($action === 'get_images') {
        try {
            $stmt = $pdo->prepare("SELECT id, content, is_active, created_at FROM app_display_messages WHERE target_slug = ? AND type = 'IMAGE_OVERRIDE' ORDER BY created_at DESC");
            $stmt->execute([$slug]);
            $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $images]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'set_active_image') {
        $id = $input['id'] ?? 0;
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID required']);
            exit;
        }

        try {
            // Disable all
            $stmtReset = $pdo->prepare("UPDATE app_display_messages SET is_active = 0 WHERE target_slug = ? AND type = 'IMAGE_OVERRIDE'");
            $stmtReset->execute([$slug]);

            // Also disable URGENT messages to ensure image shows immediately
            $stmtResetUrgent = $pdo->prepare("UPDATE app_display_messages SET is_active = 0 WHERE target_slug = ? AND type = 'URGENT'");
            $stmtResetUrgent->execute([$slug]);

            // Enable specific
            $stmtUpdate = $pdo->prepare("UPDATE app_display_messages SET is_active = 1 WHERE id = ? AND target_slug = ?");
            $stmtUpdate->execute([$id, $slug]);

            echo json_encode(['success' => true, 'message' => 'Gambar berhasil diaktifkan']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'delete_image') {
        $id = $input['id'] ?? 0;
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID required']);
            exit;
        }

        try {
            // Get file path first to delete file
            $stmtGet = $pdo->prepare("SELECT content FROM app_display_messages WHERE id = ? AND target_slug = ?");
            $stmtGet->execute([$id, $slug]);
            $path = $stmtGet->fetchColumn();

            if ($path) {
                $fullPath = '../' . $path;
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }

            $stmtDelete = $pdo->prepare("DELETE FROM app_display_messages WHERE id = ? AND target_slug = ?");
            $stmtDelete->execute([$id, $slug]);

            echo json_encode(['success' => true, 'message' => 'Gambar berhasil dihapus']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    // Handle Image Upload Action
    if ($action === 'set_ai_override') {
            $content = $input['content'] ?? '';
            if (empty($content)) {
                echo json_encode(['success' => false, 'message' => 'Content empty']);
                exit;
            }

            // Disable all previous overrides
            $stmtReset = $pdo->prepare("UPDATE app_display_messages SET is_active = 0 WHERE target_slug = ? AND type IN ('IMAGE_OVERRIDE', 'PDF_OVERRIDE', 'URGENT', 'AI_OVERRIDE')");
            $stmtReset->execute([$slug]);

            // Insert AI override
            $stmtInsert = $pdo->prepare("INSERT INTO app_display_messages (type, content, is_active, target_slug, created_at) VALUES ('AI_OVERRIDE', ?, 1, ?, NOW())");
            $stmtInsert->execute([$content, $slug]);
            
            echo json_encode(['success' => true]);
            exit;
        }

        if ($action === 'update_ai_status') {
            $isActive = $input['isActive'] ?? false;
            
            // If turning off, we disable all AI_OVERRIDE for this slug
            if (!$isActive) {
                $stmt = $pdo->prepare("UPDATE app_display_messages SET is_active = 0 WHERE target_slug = ? AND type = 'AI_OVERRIDE'");
                $stmt->execute([$slug]);
            }
            // If turning on, we try to reactivate latest AI content
            else {
                // Find latest AI content
                $stmtCheck = $pdo->prepare("SELECT id FROM app_display_messages WHERE target_slug = ? AND type = 'AI_OVERRIDE' ORDER BY id DESC LIMIT 1");
                $stmtCheck->execute([$slug]);
                $lastId = $stmtCheck->fetchColumn();

                if ($lastId) {
                    $stmt = $pdo->prepare("UPDATE app_display_messages SET is_active = 1 WHERE id = ?");
                    $stmt->execute([$lastId]);
                    
                    // Also ensure mutual exclusion
                    $stmtReset = $pdo->prepare("UPDATE app_display_messages SET is_active = 0 WHERE target_slug = ? AND type IN ('IMAGE_OVERRIDE', 'PDF_OVERRIDE', 'URGENT')");
                    $stmtReset->execute([$slug]);
                } else {
                    // No previous content, cannot activate
                     echo json_encode(['success' => false, 'message' => 'No AI content found']);
                     exit;
                }
            }
            
            echo json_encode(['success' => true]);
            exit;
        }

        if ($action === 'upload_image') {
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
             echo json_encode(['success' => false, 'message' => 'No valid image uploaded']);
             exit;
        }
        
        $file = $_FILES['image'];
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Format file tidak didukung (Gunakan JPG, PNG, WebP)']);
            exit;
        }
        
        // 2MB Limit
        if ($file['size'] > 2 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Ukuran file maksimal 2MB']);
            exit;
        }
        
        $uploadDir = '../uploads/display_overrides/' . $slug . '/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $newFilename = time() . '.' . $ext;
        $dest = $uploadDir . $newFilename;
        
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $url = 'uploads/display_overrides/' . $slug . '/' . $newFilename;
            
            // Disable all previous overrides (IMAGE & PDF & URGENT)
            $stmtReset = $pdo->prepare("UPDATE app_display_messages SET is_active = 0 WHERE target_slug = ? AND type IN ('IMAGE_OVERRIDE', 'PDF_OVERRIDE', 'URGENT')");
            $stmtReset->execute([$slug]);
            
            // Insert new record
            $dbType = ($ext === 'pdf') ? 'PDF_OVERRIDE' : 'IMAGE_OVERRIDE';
            $metadata = ($dbType === 'PDF_OVERRIDE') ? json_encode(['current_page' => 1]) : null;
            
            $stmtInsert = $pdo->prepare("INSERT INTO app_display_messages (type, content, is_active, target_slug, metadata, created_at) VALUES (?, ?, 1, ?, ?, NOW())");
            $stmtInsert->execute([$dbType, $url, $slug, $metadata]);
            $newId = $pdo->lastInsertId();
            
            echo json_encode(['success' => true, 'file_path' => $url, 'type' => $dbType, 'id' => $newId]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal mengupload file']);
        }
        exit;
    }
    
    // Handle Material Save
    if ($action === 'save_material') {
        $text = $input['text'] ?? '';
        if (!$text) {
            echo json_encode(['success' => false, 'message' => 'Materi tidak boleh kosong']);
            exit;
        }

        try {
            // Get Class ID
            $stmtClass = $pdo->prepare("SELECT id FROM acad_classes WHERE slug = ?");
            $stmtClass->execute([$slug]);
            $classId = $stmtClass->fetchColumn();

            if (!$classId) {
                echo json_encode(['success' => false, 'message' => 'Kelas tidak ditemukan']);
                exit;
            }

            // Parse Text (First line = Title, Rest = Content)
            $lines = explode("\n", $text, 2);
            $title = trim($lines[0]);
            $content = isset($lines[1]) ? trim($lines[1]) : '';

            // Insert into app_daily_materials
            $stmtInsert = $pdo->prepare("INSERT INTO app_daily_materials (class_id, title, content, created_at) VALUES (?, ?, ?, NOW())");
            $stmtInsert->execute([$classId, $title, $content]);

            echo json_encode(['success' => true, 'message' => 'Materi berhasil disimpan']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'update_pdf_control') {
        // Debug Log
        file_put_contents('debug_pdf.log', date('Y-m-d H:i:s') . " - Update Request: " . print_r($input, true) . "\n", FILE_APPEND);

        $id = $input['id'] ?? null;
        if (!$id) {
             echo json_encode(['success' => false, 'message' => 'ID required']);
             exit;
        }
        
        try {
            // Fetch current metadata first (Robust fallback for JSON_SET compatibility)
            $stmtGet = $pdo->prepare("SELECT metadata FROM app_display_messages WHERE id = ?");
            $stmtGet->execute([$id]);
            $row = $stmtGet->fetch(PDO::FETCH_ASSOC);
            
            if (!$row) {
                echo json_encode(['success' => false, 'message' => 'Record not found']);
                exit;
            }
            
            $meta = json_decode($row['metadata'] ?? '{}', true) ?: [];
            
            if (isset($input['page'])) {
                $meta['current_page'] = (int)$input['page'];
            }
            if (isset($input['laserActive'])) {
                $meta['laser_active'] = $input['laserActive'] ? 1 : 0;
            }
            if (isset($input['laserX'])) {
                $meta['laser_x'] = $input['laserX'];
            }
            if (isset($input['laserY'])) {
                $meta['laser_y'] = $input['laserY'];
            }
            
            $newMeta = json_encode($meta);
            
            $stmtUpdate = $pdo->prepare("UPDATE app_display_messages SET metadata = ? WHERE id = ?");
            $stmtUpdate->execute([$newMeta, $id]);
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
             echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'set_pdf_page') {
        $id = $input['id'] ?? null;
        $page = $input['page'] ?? 1;

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID missing']);
            exit;
        }

        try {
            // Update metadata
            $stmt = $pdo->prepare("UPDATE app_display_messages SET metadata = JSON_SET(COALESCE(metadata, '{}'), '$.current_page', CAST(? AS UNSIGNED)) WHERE id = ?");
            $stmt->execute([$page, $id]);
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // Handle Display Messages (Ticker/Urgent/Image/PDF)
    $type = $input['type'] ?? ''; // 'ticker', 'urgent', 'image_override', 'pdf_override'
    $text = $input['text'] ?? '';
    $isActive = $input['isActive'] ?? false;
    
    if ($type === 'ticker' || $type === 'urgent' || $type === 'image_override' || $type === 'pdf_override') {
        $dbType = match($type) {
            'ticker' => 'INFO',
            'urgent' => 'URGENT',
            'image_override' => 'IMAGE_OVERRIDE',
            'pdf_override' => 'PDF_OVERRIDE',
            default => 'INFO'
        };

        try {
            // Disable all previous active messages of this type if we are activating
            if ($isActive) {
                 $stmtReset = $pdo->prepare("UPDATE app_display_messages SET is_active = 0 WHERE target_slug = ? AND type = ?");
                 $stmtReset->execute([$slug, $dbType]);

                 // Mutual Exclusion: If enabling URGENT, disable IMAGE_OVERRIDE/PDF_OVERRIDE.
                 // If enabling IMAGE_OVERRIDE/PDF_OVERRIDE, disable URGENT and other overrides.
                 if ($dbType === 'URGENT') {
                     $stmtMutual = $pdo->prepare("UPDATE app_display_messages SET is_active = 0 WHERE target_slug = ? AND type IN ('IMAGE_OVERRIDE', 'PDF_OVERRIDE')");
                     $stmtMutual->execute([$slug]);
                 } elseif ($dbType === 'IMAGE_OVERRIDE' || $dbType === 'PDF_OVERRIDE') {
                     $stmtMutual = $pdo->prepare("UPDATE app_display_messages SET is_active = 0 WHERE target_slug = ? AND type IN ('URGENT', 'IMAGE_OVERRIDE', 'PDF_OVERRIDE') AND type != ?");
                     $stmtMutual->execute([$slug, $dbType]);
                 }
            }

            // Check if exists - Get Active or Latest
            $stmtCheck = $pdo->prepare("SELECT id FROM app_display_messages WHERE target_slug = ? AND type = ? ORDER BY is_active DESC, id DESC LIMIT 1");
            $stmtCheck->execute([$slug, $dbType]);
            $existing = $stmtCheck->fetchColumn();

            if ($existing) {
                // If text is empty and type is image_override, we don't want to clear the content (URL)
                // We only want to update is_active.
                // But the generic logic updates content too.
                // For image_override, usually we just toggle active. 
                // Let's check if text is provided. If not, don't update content.
                
                if (($type === 'image_override' || $type === 'pdf_override') && empty($text)) {
                     $stmtUpdate = $pdo->prepare("UPDATE app_display_messages SET is_active = ?, created_at = NOW() WHERE id = ?");
                     $stmtUpdate->execute([$isActive ? 1 : 0, $existing]);
                } else {
                     $stmtUpdate = $pdo->prepare("UPDATE app_display_messages SET content = ?, is_active = ?, created_at = NOW() WHERE id = ?");
                     $stmtUpdate->execute([$text, $isActive ? 1 : 0, $existing]);
                }
            } else {
                // Insert new (only if text provided or image/pdf override active)
                // For image/pdf override, if no content, we can't really insert anything useful unless we have a default
                if (($type === 'image_override' || $type === 'pdf_override') && empty($text)) {
                     // Can't insert without content
                } else {
                     $stmtInsert = $pdo->prepare("INSERT INTO app_display_messages (type, content, is_active, target_slug) VALUES (?, ?, ?, ?)");
                     $stmtInsert->execute([$dbType, $text, $isActive ? 1 : 0, $slug]);
                }
            }

            echo json_encode(['success' => true, 'message' => 'Settings updated']);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
