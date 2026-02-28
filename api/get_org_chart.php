<?php
// api/get_org_chart.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$target_unit_id = $_GET['unit'] ?? 'all';
$target_sub_unit = $_GET['sub_unit'] ?? '';

try {
    // 1. Fetch Positions
    $sql = "
        SELECT 
            p.id,
            p.parent_id,
            p.name as position_name,
            p.unit_id,
            p.sub_unit,
            p.sort_order,
            p.vertical_spacer,
            p.horizontal_spacer,
            u.name as unit_name,
            u.receipt_code as unit_prefix
        FROM hr_positions p
        LEFT JOIN core_units u ON p.unit_id = u.id
    ";

    $conditions = [];
    $params = [];

    if ($target_unit_id !== 'all') {
        $conditions[] = "p.unit_id = ?";
        $params[] = $target_unit_id;
    }

    if (!empty($target_sub_unit)) {
        $conditions[] = "p.sub_unit = ?";
        $params[] = $target_sub_unit;
    }

    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $sql .= " ORDER BY p.unit_id, p.level, p.sort_order, p.name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Fetch Employees for these positions
    if (!empty($positions)) {
        $positionIds = array_column($positions, 'id');
        $inQuery = implode(',', array_fill(0, count($positionIds), '?'));
        
        $empSql = "
            SELECT 
                he.position_id,
                cp.name as official_name,
                he.employee_number as nip,
                he.sk_number
            FROM hr_employees he
            JOIN core_people cp ON he.person_id = cp.id
            WHERE he.employment_status != 'RESIGNED' 
            AND he.position_id IN ($inQuery)
        ";
        
        $empStmt = $pdo->prepare($empSql);
        $empStmt->execute($positionIds);
        $employees = $empStmt->fetchAll(PDO::FETCH_ASSOC);

        // Group employees by position_id
        $employeesByPos = [];
        foreach ($employees as $emp) {
            $employeesByPos[$emp['position_id']][] = $emp;
        }

        // Attach employees to positions
        foreach ($positions as &$pos) {
            $pos['employees'] = $employeesByPos[$pos['id']] ?? [];
        }
        unset($pos); // Break reference to avoid array corruption in next loop
    }

    // 3. MERGE LOGIC: Group duplicate positions into one node
    // Key = parent_id + position_name + unit_id + sub_unit + sort_order
    // We only merge if they are visually identical.
    
    $mergedPositions = [];
    $positionMap = []; // Key -> Index in $mergedPositions

    foreach ($positions as $pos) {
        $key = ($pos['parent_id'] ?? 'root') . '|' . 
               $pos['position_name'] . '|' . 
               ($pos['unit_id'] ?? '') . '|' . 
               ($pos['sub_unit'] ?? '') . '|' . 
               ($pos['sort_order'] ?? 0) . '|' . 
               ($pos['vertical_spacer'] ?? 0) . '|' .
               ($pos['horizontal_spacer'] ?? 0);
               
        if (isset($positionMap[$key])) {
            // Merge into existing
            $index = $positionMap[$key];
            
            // If this position has employees, add them
            if (!empty($pos['employees'])) {
                foreach ($pos['employees'] as $emp) {
                    $emp['position_id'] = $pos['id']; // Ensure correct ID
                    $mergedPositions[$index]['employees'][] = $emp;
                }
            } else {
                // If it's an empty position, adds a "Vacant" placeholder
                $mergedPositions[$index]['employees'][] = [
                    'official_name' => null, // Flag for vacancy
                    'nip' => null,
                    'sk_number' => null,
                    'is_vacancy' => true,
                    'position_id' => $pos['id'] // Keep track of which ID is vacant
                ];
            }
            
            // Keep track of merged IDs (optional, if we want to allow editing individual slots)
            $mergedPositions[$index]['merged_ids'][] = $pos['id'];
            
        } else {
            // New Entry
            if (empty($pos['employees'])) {
                 // Convert empty array to 1 vacancy placeholder so it counts as a slot
                 $pos['employees'][] = [
                    'official_name' => null,
                    'nip' => null,
                    'sk_number' => null,
                    'is_vacancy' => true,
                    'position_id' => $pos['id']
                ];
            } else {
                // Ensure position_id is set in employees
                 foreach ($pos['employees'] as &$emp) {
                    $emp['position_id'] = $pos['id'];
                }
            }
            
            $pos['merged_ids'] = [$pos['id']];
            $mergedPositions[] = $pos;
            $positionMap[$key] = count($mergedPositions) - 1;
        }
    }
    
    // Reset keys
    $nodes = array_values($mergedPositions);

    // 4. Build Tree
    // Note: Since we merged, IDs might have changed (we kept the ID of the first occurrence).
    // Children need to point to the correct parent.
    // If we merged Parent A1 and Parent A2 into Parent A1...
    // Child B who points to A2 needs to be re-mapped to A1?
    // This is tricky.
    
    // If the user duplicates a position, they usually don't attach children to the duplicate?
    // Or if they do, we should handle it.
    
    // Mapping Old IDs to Merged IDs
    $idMap = [];
    foreach ($nodes as $node) {
        foreach ($node['merged_ids'] as $mid) {
            $idMap[$mid] = $node['id'];
        }
    }
    
    // Fix parent_ids in nodes
    foreach ($nodes as &$node) {
        if ($node['parent_id'] && isset($idMap[$node['parent_id']])) {
            $node['parent_id'] = $idMap[$node['parent_id']];
        }
    }
    unset($node); // Break reference

    // Helper for subtree from flat array
    function buildSubTree(&$elements, $parentId) {
        $branch = [];
        foreach ($elements as $element) {
            if ($element['parent_id'] == $parentId) {
                $children = buildSubTree($elements, $element['id']);
                if ($children) {
                    $element['children'] = $children;
                }
                $branch[] = $element;
            }
        }
        return $branch;
    }

    if ($target_unit_id !== 'all' || !empty($target_sub_unit)) {
        $nodeIds = array_column($nodes, 'id');
        $tree = [];
        
        foreach ($nodes as $node) {
            if (!$node['parent_id'] || !in_array($node['parent_id'], $nodeIds)) {
                $node['children'] = buildSubTree($nodes, $node['id']);
                $tree[] = $node;
            }
        }
    } else {
        $tree = buildSubTree($nodes, null);
    }

    echo json_encode($tree);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
