<?php
// api/library.php
require_once '../config/database.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {
    if ($action === 'get_classes') {
        $stmt = $pdo->query("SELECT id, name FROM acad_classes ORDER BY name ASC");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

    } elseif ($action === 'get_visits') {
        $stmt = $pdo->query("
            SELECT v.*, c.name as class_name 
            FROM lib_class_visits v
            JOIN acad_classes c ON v.class_id = c.id
            ORDER BY v.visit_date DESC, v.start_time DESC
        ");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

    } elseif ($action === 'save_visit') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        
        if ($id) {
            $sql = "UPDATE lib_class_visits SET class_id = ?, visit_date = ?, start_time = ?, end_time = ?, remarks = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$input['class_id'], $input['visit_date'], $input['start_time'] ?: null, $input['end_time'] ?: null, $input['remarks'] ?? '', $id]);
        } else {
            $sql = "INSERT INTO lib_class_visits (class_id, visit_date, start_time, end_time, remarks) VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$input['class_id'], $input['visit_date'], $input['start_time'] ?: null, $input['end_time'] ?: null, $input['remarks'] ?? '']);
            $id = $pdo->lastInsertId();
        }
        echo json_encode(['success' => true, 'id' => $id]);

    } elseif ($action === 'search_students') {
        $q = $_GET['q'] ?? '';
        $class_id = $_GET['class_id'] ?? null;
        
        if ($class_id) {
            $sql = "SELECT p.id, p.name, p.identity_number 
                    FROM core_people p
                    JOIN acad_student_classes sc ON p.id = sc.student_id
                    WHERE sc.class_id = ? AND sc.status = 'ACTIVE' 
                    AND (p.name LIKE ? OR p.identity_number LIKE ?) 
                    AND p.type = 'STUDENT' LIMIT 10";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$class_id, "%$q%", "%$q%"]);
        } else {
            $stmt = $pdo->prepare("SELECT id, name, identity_number FROM core_people WHERE (name LIKE ? OR identity_number LIKE ?) AND type = 'STUDENT' LIMIT 10");
            $stmt->execute(["%$q%", "%$q%"]);
        }
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

    } elseif ($action === 'search_books') {
        $q = $_GET['q'] ?? '';
        $stmt = $pdo->prepare("SELECT id, title, author, barcode FROM lib_books WHERE (title LIKE ? OR author LIKE ? OR barcode LIKE ?) LIMIT 10");
        $stmt->execute(["%$q%", "%$q%", "%$q%"]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

    } elseif ($action === 'get_student_by_barcode') {
        $barcode = $_GET['barcode'] ?? '';
        $class_id = $_GET['class_id'] ?? null;
        
        if ($class_id) {
            $sql = "SELECT p.id, p.name, p.identity_number 
                    FROM core_people p
                    JOIN acad_student_classes sc ON p.id = sc.student_id
                    WHERE p.identity_number = ? AND sc.class_id = ? AND sc.status = 'ACTIVE' AND p.type = 'STUDENT'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$barcode, $class_id]);
        } else {
            $stmt = $pdo->prepare("SELECT id, name, identity_number FROM core_people WHERE identity_number = ? AND type = 'STUDENT'");
            $stmt->execute([$barcode]);
        }
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['success' => !!$student, 'data' => $student]);

    } elseif ($action === 'get_book_by_barcode') {
        $barcode = $_GET['barcode'] ?? '';
        $stmt = $pdo->prepare("SELECT id, title, author, barcode FROM lib_books WHERE barcode = ?");
        $stmt->execute([$barcode]);
        $book = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['success' => !!book, 'data' => $book]);

    } elseif ($action === 'save_reading_log') {
        $input = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("INSERT INTO lib_reading_logs (visit_id, student_id, book_id) VALUES (?, ?, ?)");
        $stmt->execute([$input['visit_id'], $input['student_id'], $input['book_id']]);
        echo json_encode(['success' => true]);

    } elseif ($action === 'get_reading_logs') {
        $visit_id = $_GET['visit_id'] ?? 0;
        $stmt = $pdo->prepare("
            SELECT l.*, p.name as student_name, p.identity_number as nis, b.title as book_title
            FROM lib_reading_logs l
            JOIN core_people p ON l.student_id = p.id
            JOIN lib_books b ON l.book_id = b.id
            WHERE l.visit_id = ?
            ORDER BY l.read_at DESC
        ");
        $stmt->execute([$visit_id]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

    } elseif ($action === 'get_books') {
        $stmt = $pdo->query("
            SELECT b.*, c.name as category_name, s.name as shelf_name 
            FROM lib_books b
            LEFT JOIN lib_categories c ON b.category_id = c.id
            LEFT JOIN lib_shelves s ON b.shelf_id = s.id
            ORDER BY b.title ASC
        ");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

    } elseif ($action === 'save_book') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        if ($id) {
            $sql = "UPDATE lib_books SET title = ?, author = ?, publisher = ?, isbn = ?, barcode = ?, category_id = ?, shelf_id = ?, stock = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$input['title'], $input['author'], $input['publisher'], $input['isbn'], $input['barcode'], $input['category_id'], $input['shelf_id'], $input['stock'], $id]);
        } else {
            $sql = "INSERT INTO lib_books (title, author, publisher, isbn, barcode, category_id, shelf_id, stock, available_stock) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$input['title'], $input['author'], $input['publisher'], $input['isbn'], $input['barcode'], $input['category_id'], $input['shelf_id'], $input['stock'], $input['stock']]);
        }
        echo json_encode(['success' => true]);

    } elseif ($action === 'delete_book') {
        $input = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("DELETE FROM lib_books WHERE id = ?");
        $stmt->execute([$input['id']]);
        echo json_encode(['success' => true]);

    } elseif ($action === 'get_categories') {
        $stmt = $pdo->query("SELECT * FROM lib_categories ORDER BY name ASC");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

    } elseif ($action === 'save_category') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        if ($id) {
            $stmt = $pdo->prepare("UPDATE lib_categories SET name = ?, description = ? WHERE id = ?");
            $stmt->execute([$input['name'], $input['description'], $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO lib_categories (name, description) VALUES (?, ?)");
            $stmt->execute([$input['name'], $input['description']]);
        }
        echo json_encode(['success' => true]);

    } elseif ($action === 'get_shelves') {
        $stmt = $pdo->query("SELECT * FROM lib_shelves ORDER BY name ASC");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

    } elseif ($action === 'save_shelf') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        if ($id) {
            $stmt = $pdo->prepare("UPDATE lib_shelves SET name = ?, location = ? WHERE id = ?");
            $stmt->execute([$input['name'], $input['location'], $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO lib_shelves (name, location) VALUES (?, ?)");
            $stmt->execute([$input['name'], $input['location']]);
        }
        echo json_encode(['success' => true]);

    } elseif ($action === 'get_schedules') {
        $stmt = $pdo->query("
            SELECT s.*, c.name as class_name 
            FROM lib_visit_schedule s
            JOIN acad_classes c ON s.class_id = c.id
            ORDER BY FIELD(s.day_name, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'), s.start_time ASC
        ");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

    } elseif ($action === 'save_schedule') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        if ($id) {
            $sql = "UPDATE lib_visit_schedule SET class_id = ?, day_name = ?, start_time = ?, end_time = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$input['class_id'], $input['day_name'], $input['start_time'], $input['end_time'], $id]);
        } else {
            $sql = "INSERT INTO lib_visit_schedule (class_id, day_name, start_time, end_time) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$input['class_id'], $input['day_name'], $input['start_time'], $input['end_time']]);
        }
        echo json_encode(['success' => true]);

    } elseif ($action === 'delete_schedule') {
        $input = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("DELETE FROM lib_visit_schedule WHERE id = ?");
        $stmt->execute([$input['id']]);
        echo json_encode(['success' => true]);

    } elseif ($action === 'get_members') {
        $stmt = $pdo->query("
            SELECT m.*, p.name, p.identity_number as nis, p.type
            FROM lib_members m
            JOIN core_people p ON m.person_id = p.id
            ORDER BY m.created_at DESC
        ");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

    } elseif ($action === 'register_member') {
        $input = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("INSERT INTO lib_members (person_id, member_code) VALUES (?, ?)");
        $stmt->execute([$input['person_id'], $input['member_code']]);
        echo json_encode(['success' => true]);

    } elseif ($action === 'get_loans') {
        $stmt = $pdo->query("
            SELECT l.*, p.name as member_name, b.title as book_title, b.barcode as book_barcode
            FROM lib_loans l
            JOIN lib_members m ON l.member_id = m.id
            JOIN core_people p ON m.person_id = p.id
            JOIN lib_books b ON l.book_id = b.id
            ORDER BY l.loan_date DESC
        ");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

    } elseif ($action === 'save_loan') {
        $input = json_decode(file_get_contents('php://input'), true);
        $pdo->beginTransaction();
        
        // Check stock
        $stmt = $pdo->prepare("SELECT available_stock FROM lib_books WHERE id = ?");
        $stmt->execute([$input['book_id']]);
        $book = $stmt->fetch();
        
        if ($book['available_stock'] <= 0) {
            echo json_encode(['success' => false, 'error' => 'Stok buku habis']);
            $pdo->rollBack();
            exit;
        }

        $sql = "INSERT INTO lib_loans (member_id, book_id, loan_date, due_date, status) VALUES (?, ?, ?, ?, 'BORROWED')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$input['member_id'], $input['book_id'], $input['loan_date'], $input['due_date']]);
        
        // Update stock
        $pdo->prepare("UPDATE lib_books SET available_stock = available_stock - 1 WHERE id = ?")->execute([$input['book_id']]);
        
        $pdo->commit();
        echo json_encode(['success' => true]);

    } elseif ($action === 'return_book') {
        $input = json_decode(file_get_contents('php://input'), true);
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("UPDATE lib_loans SET return_date = ?, status = 'RETURNED', fine_amount = ? WHERE id = ?");
        $stmt->execute([$input['return_date'], $input['fine_amount'] ?? 0, $input['id']]);
        
        // Get book_id
        $stmt = $pdo->prepare("SELECT book_id FROM lib_loans WHERE id = ?");
        $stmt->execute([$input['id']]);
        $loan = $stmt->fetch();
        
        // Update stock
        $pdo->prepare("UPDATE lib_books SET available_stock = available_stock + 1 WHERE id = ?")->execute([$loan['book_id']]);
        
        $pdo->commit();
        echo json_encode(['success' => true]);

    } elseif ($action === 'get_fines') {
        $stmt = $pdo->query("
            SELECT l.*, p.name as member_name, b.title as book_title
            FROM lib_loans l
            JOIN lib_members m ON l.member_id = m.id
            JOIN core_people p ON m.person_id = p.id
            JOIN lib_books b ON l.book_id = b.id
            WHERE l.fine_amount > 0 OR l.status = 'OVERDUE'
            ORDER BY l.loan_date DESC
        ");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

    } elseif ($action === 'search_people_not_members') {
        $q = $_GET['q'] ?? '';
        $stmt = $pdo->prepare("
            SELECT p.id, p.name, p.identity_number 
            FROM core_people p
            LEFT JOIN lib_members m ON p.id = m.person_id
            WHERE m.id IS NULL AND (p.name LIKE ? OR p.identity_number LIKE ?)
            LIMIT 10
        ");
        $stmt->execute(["%$q%", "%$q%"]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
