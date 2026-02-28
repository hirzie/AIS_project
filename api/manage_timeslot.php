<?php
// api/manage_timeslot.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'POST') {
        $action = $input['action'] ?? 'create';
        
        // --- CATEGORY MANAGEMENT ---
        
        if ($action === 'create_category') {
            $unit_id = $input['unit_id'] ?? null;
            $name = $input['name'] ?? '';
            $description = $input['description'] ?? '';
            $academic_year_id = $input['academic_year_id'] ?? null;
            
            if (!$unit_id || !$name) {
                echo json_encode(['error' => 'Unit ID dan Nama Kategori wajib diisi']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO acad_schedule_categories (unit_id, academic_year_id, name, description) VALUES (?, ?, ?, ?)");
            $stmt->execute([$unit_id, $academic_year_id, $name, $description]);
            
            echo json_encode(['success' => true, 'message' => 'Kategori jadwal berhasil ditambahkan']);

        } elseif ($action === 'update_category') {
            $id = $input['id'] ?? null;
            $name = $input['name'] ?? '';
            $description = $input['description'] ?? '';
            $academic_year_id = $input['academic_year_id'] ?? null;
            
            if (!$id || !$name) {
                echo json_encode(['error' => 'ID dan Nama Kategori wajib diisi']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE acad_schedule_categories SET name = ?, description = ?, academic_year_id = ? WHERE id = ?");
            $stmt->execute([$name, $description, $academic_year_id, $id]);
            
            echo json_encode(['success' => true, 'message' => 'Kategori jadwal berhasil diperbarui']);

        } elseif ($action === 'delete_category') {
            $id = $input['id'] ?? null;
            
            if (!$id) {
                echo json_encode(['error' => 'ID Kategori tidak ditemukan']);
                exit;
            }

            // Check if active
            $stmt = $pdo->prepare("SELECT is_active FROM acad_schedule_categories WHERE id = ?");
            $stmt->execute([$id]);
            $cat = $stmt->fetch();
            if ($cat && $cat['is_active']) {
                 echo json_encode(['error' => 'Tidak dapat menghapus kategori yang sedang aktif. Silakan aktifkan kategori lain terlebih dahulu.']);
                 exit;
            }

            // Check usage in schedules (if strictly linked) or just let cascade handle it?
            // Schema has ON DELETE CASCADE, so slots and schedules will be deleted.
            // Warn user in UI.
            
            $stmt = $pdo->prepare("DELETE FROM acad_schedule_categories WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'Kategori jadwal berhasil dihapus']);

        } elseif ($action === 'set_active_category') {
            $id = $input['id'] ?? null;
            $unit_id = $input['unit_id'] ?? null;
            
            if (!$id || !$unit_id) {
                echo json_encode(['error' => 'ID Kategori dan Unit ID wajib diisi']);
                exit;
            }

            $pdo->beginTransaction();
            // Deactivate all for this unit
            $stmt = $pdo->prepare("UPDATE acad_schedule_categories SET is_active = 0 WHERE unit_id = ?");
            $stmt->execute([$unit_id]);
            
            // Activate selected
            $stmt = $pdo->prepare("UPDATE acad_schedule_categories SET is_active = 1 WHERE id = ?");
            $stmt->execute([$id]);
            $pdo->commit();
            
            echo json_encode(['success' => true, 'message' => 'Kategori aktif berhasil diubah']);

        } elseif ($action === 'duplicate_category') {
            $source_id = $input['source_id'] ?? null;
            $new_name = $input['new_name'] ?? '';
            $academic_year_id = $input['academic_year_id'] ?? null;
            
            if (!$source_id || !$new_name) {
                echo json_encode(['error' => 'ID Sumber dan Nama Baru wajib diisi']);
                exit;
            }

            // Get source details
            $stmt = $pdo->prepare("SELECT * FROM acad_schedule_categories WHERE id = ?");
            $stmt->execute([$source_id]);
            $source = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$source) {
                echo json_encode(['error' => 'Kategori sumber tidak ditemukan']);
                exit;
            }

            // If academic_year_id not provided, use source's year
            if (!$academic_year_id) {
                $academic_year_id = $source['academic_year_id'];
            }

            $pdo->beginTransaction();
            
            // Create new category
            $stmt = $pdo->prepare("INSERT INTO acad_schedule_categories (unit_id, academic_year_id, name, description, is_active) VALUES (?, ?, ?, ?, 0)");
            $stmt->execute([$source['unit_id'], $academic_year_id, $new_name, "Duplikat dari " . $source['name']]);
            $new_id = $pdo->lastInsertId();

            // Duplicate Time Slots
            $stmt = $pdo->prepare("SELECT * FROM acad_time_slots WHERE category_id = ?");
            $stmt->execute([$source_id]);
            $slots = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmtInsertSlot = $pdo->prepare("INSERT INTO acad_time_slots (unit_id, category_id, name, start_time, end_time, is_break) VALUES (?, ?, ?, ?, ?, ?)");
            
            foreach ($slots as $slot) {
                $stmtInsertSlot->execute([
                    $slot['unit_id'],
                    $new_id,
                    $slot['name'],
                    $slot['start_time'],
                    $slot['end_time'],
                    $slot['is_break']
                ]);
            }

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Kategori berhasil diduplikasi']);

        } 
        
        // --- TIME SLOT MANAGEMENT ---
        
        elseif ($action === 'create') {
            $unit_id = $input['unit_id'] ?? null;
            $category_id = $input['category_id'] ?? null; // New
            $name = $input['name'] ?? '';
            $start_time = $input['start_time'] ?? '';
            $end_time = $input['end_time'] ?? '';
            $is_break = !empty($input['is_break']) ? 1 : 0;

            if (!$unit_id || !$name || !$start_time || !$end_time || !$category_id) {
                echo json_encode(['error' => 'Data slot waktu tidak lengkap. Pastikan Kategori, Unit, Nama, Jam Mulai, dan Jam Selesai terisi.']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM acad_time_slots WHERE unit_id = ? AND category_id = ? AND start_time = ? AND end_time = ? AND is_break = ?");
            $stmt->execute([$unit_id, $category_id, $start_time, $end_time, $is_break]);
            if ($stmt->fetchColumn() > 0) {
                echo json_encode(['error' => 'Slot waktu dengan jam yang sama sudah ada untuk kategori ini']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO acad_time_slots (unit_id, category_id, name, start_time, end_time, is_break) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$unit_id, $category_id, $name, $start_time, $end_time, $is_break]);
            
            echo json_encode(['success' => true, 'message' => 'Jam pelajaran berhasil ditambahkan']);
            
        } elseif ($action === 'update') {
            $id = $input['id'] ?? null;
            $name = $input['name'] ?? '';
            $start_time = $input['start_time'] ?? '';
            $end_time = $input['end_time'] ?? '';
            $is_break = !empty($input['is_break']) ? 1 : 0;

            if (!$id) {
                echo json_encode(['error' => 'ID Slot tidak ditemukan']);
                exit;
            }

            // Fetch old values to check for changes and update linked schedules
            $stmtOld = $pdo->prepare("SELECT * FROM acad_time_slots WHERE id = ?");
            $stmtOld->execute([$id]);
            $oldSlot = $stmtOld->fetch(PDO::FETCH_ASSOC);
            
            if (!$oldSlot) {
                echo json_encode(['error' => 'Slot waktu tidak ditemukan']);
                exit;
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("UPDATE acad_time_slots SET name = ?, start_time = ?, end_time = ?, is_break = ? WHERE id = ?");
            $stmt->execute([$name, $start_time, $end_time, $is_break, $id]);
            
            // If time changed, update linked schedules to maintain consistency
            if ($oldSlot['start_time'] !== $start_time || $oldSlot['end_time'] !== $end_time) {
                // Update schedules that match the OLD time and category
                // We use start_time as the key link since we don't have time_slot_id FK
                $stmtSch = $pdo->prepare("
                    UPDATE acad_schedules 
                    SET start_time = ?, end_time = ? 
                    WHERE category_id = ? AND start_time = ?
                ");
                $stmtSch->execute([$start_time, $end_time, $oldSlot['category_id'], $oldSlot['start_time']]);
            }

            $pdo->commit();
            
            echo json_encode(['success' => true, 'message' => 'Jam pelajaran berhasil diperbarui']);
            
        } elseif ($action === 'delete') {
            $id = $input['id'] ?? null;

            if (!$id) {
                echo json_encode(['error' => 'ID Slot tidak ditemukan']);
                exit;
            }

            // Cek penggunaan di jadwal
            // Ambil start_time dan category_id dari slot yang akan dihapus
            $stmtTime = $pdo->prepare("SELECT start_time, category_id FROM acad_time_slots WHERE id = ?");
            $stmtTime->execute([$id]);
            $slot = $stmtTime->fetch(PDO::FETCH_ASSOC);

            if ($slot) {
                $start_time = $slot['start_time'];
                $category_id = $slot['category_id'];
                
                // Cek jadwal yang menggunakan waktu ini DAN kategori ini
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM acad_schedules WHERE category_id = ? AND start_time = ?");
                $stmt->execute([$category_id, $start_time]);
                
                if ($stmt->fetchColumn() > 0) {
                    echo json_encode(['error' => 'Tidak dapat menghapus jam ini karena sedang digunakan dalam jadwal pelajaran. Hapus jadwal terkait terlebih dahulu.']);
                    exit;
                }
            }

            $stmt = $pdo->prepare("DELETE FROM acad_time_slots WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'Jam pelajaran berhasil dihapus']);
        }
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>