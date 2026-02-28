<?php
require_once '../../includes/guard.php';
require_login_and_module('academic');
require_once '../../config/database.php';
require_once '../../includes/header.php';
?>

<!-- FLAG: Enable Lazy Loading for Academic Dashboard -->
<script>window.ACADEMIC_LAZY_LOAD = true;</script>

<!-- JS Injection Removed for Performance -->

<div id="app" class="flex flex-col h-screen bg-slate-50">

    <?php require_once '../../includes/academic_header.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="flex-1 overflow-y-auto p-6 relative">
        <!-- Background Decoration -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none opacity-50 z-0">
            <div class="absolute top-[10%] right-[10%] w-[400px] h-[400px] rounded-full bg-teal-100/50 blur-3xl"></div>
            <div class="absolute bottom-[10%] left-[10%] w-[300px] h-[300px] rounded-full bg-blue-100/50 blur-3xl"></div>
        </div>

        <!-- STATE 1: DASHBOARD (Data Loaded) -->
        <div class="relative z-10 max-w-6xl mx-auto">
            <?php
                if (!isset($pdo) || !($pdo instanceof PDO)) {
                    require __DIR__ . '/../../config/database.php';
                }
                
                // PREFETCH DATA (Refactoring Standard: Server-Side Prefetch)
                $prefetchedData = [
                    'years' => [], 
                    'units' => [], 
                    'current_term' => null,
                    'todaySchedule' => []
                ];
                try {
                    // 1. Global Academic Data
                    $stmt = $pdo->query("SELECT id, name, start_date, end_date, semester_active, status FROM academic_years WHERE status IN ('ACTIVE','PLANNED') ORDER BY start_date DESC");
                    $prefetchedData['years'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $stmt = $pdo->query("SELECT id, name, code, unit_level FROM academic_units ORDER BY id ASC");
                    $prefetchedData['units'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Current active term
                    foreach($prefetchedData['years'] as $y) {
                        if($y['status'] === 'ACTIVE') { $prefetchedData['current_term'] = $y; break; }
                    }

                    // 2. Personal Schedule Data (If Teacher)
                    if (!empty($_SESSION['person_id'])) {
                        $days = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
                        $todayName = $days[date('l')];
                        
                        $stmtSch = $pdo->prepare("
                            SELECT s.start_time, s.end_time, sub.name as subject, c.name as class_name
                            FROM acad_schedules s
                            JOIN acad_subjects sub ON s.subject_id = sub.id
                            JOIN acad_classes c ON s.class_id = c.id
                            WHERE s.teacher_id = ? AND s.day_name = ?
                            ORDER BY s.start_time ASC
                        ");
                        $stmtSch->execute([$_SESSION['person_id'], $todayName]);
                        $prefetchedData['todaySchedule'] = $stmtSch->fetchAll(PDO::FETCH_ASSOC);
                    }
                } catch (Exception $e) { /* Fail silently, JS will fetch */ }
                
                $displayName = $_SESSION['username'] ?? 'Pengguna';
                if (!empty($_SESSION['person_id'])) {
                    $st = $pdo->prepare("SELECT name FROM core_people WHERE id = ?");
                    $st->execute([$_SESSION['person_id']]);
                    $nm = $st->fetchColumn();
                    if ($nm) { $displayName = $nm; }
                }
            ?>
            <script>window.PREFETCHED_ACADEMIC_DATA = <?php echo json_encode($prefetchedData); ?>;</script>

            <div class="mb-4">
                <div class="rounded-xl bg-white border border-slate-200 px-4 py-2 flex items-center gap-2 shadow-sm">
                    <i class="fas fa-user text-slate-500"></i>
                    <span class="text-slate-700 text-sm font-medium">Hi <?php echo htmlspecialchars($displayName); ?></span>
                    <?php if (!empty($prefetchedData['todaySchedule'])): ?>
                    <div class="ml-auto flex items-center gap-2">
                        <span class="text-xs font-bold text-slate-500">Jadwal Hari Ini:</span>
                        <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 text-xs font-bold">
                            <?php echo count($prefetchedData['todaySchedule']); ?> Kelas
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- VIEW: PORTAL UTAMA (GRID LAYOUT) -->
            <?php require_once 'views/dashboard_cards.php'; ?>
        </div>

        <!-- CENTER WIDGET (Refactoring Standard: SSR) -->
        <div class="max-w-6xl mx-auto mt-8 mb-12">
            <?php require_once 'views/center_widget.php'; ?>
        </div>

    </main>
</div>

<script type="module">
    // ACADEMIC PORTAL LOGIC (Refactoring Standard: Modular JS)
    import { AcademicPortal } from '<?php echo $baseUrl; ?>assets/js/modules/academic_portal_logic.js?v=<?php echo time(); ?>';
    
    const initPortal = () => {
        // Remove Skeleton (if any left)
        const skeleton = document.getElementById('academic-skeleton');
        if(skeleton) skeleton.remove();

        // Initialize Portal
        // Logic will now hydrate existing #academic-center-widget instead of injecting
        new AcademicPortal(
            'main .max-w-6xl.mx-auto', 
            '<?php echo $baseUrl; ?>',
            {
                requesterName: <?php echo json_encode($displayName); ?>
            }
        );
    };

    // Immediate Init (SSR is fast)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPortal);
    } else {
        initPortal();
    }
</script>

<script>window.SKIP_GLOBAL_APP = true;</script>
<?php require_once '../../includes/footer.php'; ?>
