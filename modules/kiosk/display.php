<?php
require_once '../../config/database.php';
// Compute BASE_URL
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$baseUrl = (stripos($scriptName, '/AIS/') !== false) ? '/AIS/' : '/';

// Fetch settings
$stmt = $pdo->query("SELECT * FROM kiosk_settings");
$dbSettings = $stmt->fetchAll(PDO::FETCH_ASSOC);
$settings = [];
foreach ($dbSettings as $row) {
    $decoded = json_decode($row['setting_value'], true);
    $value = (json_last_error() === JSON_ERROR_NONE) ? $decoded : $row['setting_value'];
    $settings[$row['zone']][$row['setting_key']] = $value;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SekolahOS Kiosk Display</title>
    <script>
        window.BASE_URL = '<?php echo $baseUrl; ?>';
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?php echo $baseUrl; ?>assets/js/vue.global.js"></script>
    <link href="<?php echo $baseUrl; ?>assets/css/fontawesome.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;700&family=Inter:wght@400;600&family=Amiri&display=swap" rel="stylesheet">
    <style>
        .fade-enter-active, .fade-leave-active { transition: opacity 1s; }
        .fade-enter-from, .fade-leave-to { opacity: 0; }
        .animate-marquee { animation: scroll 25s linear infinite; }
        @keyframes scroll { from { transform: translateX(100vw); } to { transform: translateX(-100%); } }
        [v-cloak] { display: none; }
        /* Custom scrollbar for info panel */
        .custom-scroll::-webkit-scrollbar { width: 4px; }
        .custom-scroll::-webkit-scrollbar-track { background: rgba(255,255,255,0.05); }
        .custom-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 2px; }
    </style>
</head>
<body class="bg-slate-950 text-white overflow-hidden h-screen w-screen selection:bg-emerald-500/30">
    <div id="app" v-cloak class="h-full w-full flex flex-col relative">
        
        <!-- HEADER -->
        <header class="h-24 bg-white border-b border-slate-200 flex items-center justify-between px-8 z-50 shrink-0 shadow-md">
            <!-- Logo & Title -->
            <div class="flex items-center gap-6">
                <div v-if="!fullLogoUrl" class="w-16 h-16 bg-gradient-to-br from-blue-900 to-blue-700 rounded-xl flex items-center justify-center text-white font-bold text-3xl shadow-lg shadow-blue-900/20">S</div>
                <img v-else :src="fullLogoUrl" class="h-16 w-auto object-contain drop-shadow-sm" />
                <div class="flex flex-col justify-center h-full pt-1">
                    <h1 class="text-3xl font-bold font-['Oswald'] tracking-wider uppercase text-blue-900 leading-none mb-1">{{ headerTitle }}</h1>
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 bg-lime-100 text-lime-700 text-xs font-bold uppercase rounded border border-lime-200 tracking-wider">LIVE DISPLAY</span>
                        <p class="text-sm text-slate-500 font-medium tracking-wide">{{ zoneLabel }}</p>
                    </div>
                </div>
            </div>

            <!-- Time & Date -->
            <div class="text-right">
                <h2 class="text-4xl font-bold font-mono text-blue-900 tracking-tight leading-none">{{ currentTime }}</h2>
                <p class="text-sm text-slate-500 font-medium mt-1 uppercase tracking-widest">{{ currentDate }}</p>
            </div>
        </header>

        <!-- MAIN CONTENT -->
        <main class="flex-1 flex overflow-hidden relative">
            
            <!-- LEFT: VIDEO / SLIDESHOW (75%) -->
            <section class="w-3/4 h-full bg-black relative flex items-center justify-center overflow-hidden border-r border-white/10">
                <!-- Video Layer (Always Rendered if URL exists to keep playing) -->
                <div v-if="youtubeUrl" class="absolute inset-0 w-full h-full bg-black transition-opacity duration-1000 ease-in-out"
                     :class="showVideo ? 'opacity-100 z-10' : 'opacity-0 z-0'">
                     <iframe 
                        :src="youtubeEmbedUrl" 
                        class="w-full h-full object-cover" 
                        frameborder="0" 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                        allowfullscreen>
                    </iframe>
                </div>

                <!-- Slide Layer (Always Rendered) -->
                <div class="absolute inset-0 w-full h-full bg-slate-900 transition-opacity duration-1000 ease-in-out"
                     :class="!showVideo ? 'opacity-100 z-10' : 'opacity-0 z-0'">
                    <transition name="fade">
                        <img :key="currentSlide" :src="currentSlide" class="w-full h-full object-cover opacity-90">
                    </transition>
                    
                    <!-- Overlay Text for Slide -->
                    <div class="absolute bottom-0 left-0 w-full bg-gradient-to-t from-black/90 to-transparent p-12">
                        <h2 class="text-4xl font-bold text-white mb-2">{{ headerTitle }}</h2>
                        <p class="text-xl text-slate-300">Selamat Datang di Sistem Informasi Sekolah</p>
                    </div>
                </div>
            </section>

            <!-- RIGHT: INFO PANEL (25%) -->
            <aside class="w-1/4 h-full bg-slate-900/95 border-l border-white/5 flex flex-col relative z-20 shadow-2xl shadow-black/50">
                <!-- Title -->
                <div class="mb-6 border-l-4 border-lime-500 pl-4">
                    <h2 class="text-2xl font-bold text-lime-400 font-['Oswald'] uppercase tracking-wide">Informasi Terkini</h2>
                    <p class="text-slate-400 text-sm mt-1">Update kegiatan & pengumuman sekolah</p>
                </div>

                <!-- Scrollable Content -->
                <div class="flex-1 overflow-y-auto p-5 space-y-6 custom-scroll">
                    
                    <!-- Agenda Items -->
                    <div v-if="teacherAgenda.length > 0" class="mb-6">
                        <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span> Agenda Akademik
                        </h4>
                        <div class="space-y-3">
                            <div v-for="(item, i) in teacherAgenda" :key="i" 
                                 class="bg-slate-800/80 p-3 rounded border-l-2 border-blue-500 hover:bg-slate-800 transition-colors">
                                <div class="flex justify-between items-start mb-1">
                                    <span class="text-blue-300 font-bold text-sm">{{ item.time }}</span>
                                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-blue-500/10 text-blue-300 border border-blue-500/20 uppercase">{{ item.type || 'Agenda' }}</span>
                                </div>
                                <p class="text-slate-200 text-sm font-medium leading-tight">{{ item.activity }}</p>
                                <p v-if="item.desc" class="text-slate-400 text-xs mt-1 line-clamp-2">{{ item.desc }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Announcements -->
                    <div v-if="announcements.length > 0" class="flex-1 overflow-hidden flex flex-col">
                        <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-lime-500"></span> Pengumuman
                        </h4>
                        <div class="space-y-3 overflow-y-auto pr-2 custom-scrollbar">
                            <div v-for="(ann, i) in announcements" :key="i" 
                                 class="bg-slate-800/80 p-3 rounded border-l-2 border-lime-500 hover:bg-slate-800 transition-colors">
                                <h5 class="text-lime-300 font-bold text-sm mb-1">{{ ann.title }}</h5>
                                <p class="text-slate-300 text-xs leading-relaxed">{{ ann.content }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Achievers Section (Compact) -->
                    <div v-if="achievers.length > 0">
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3 border-l-2 border-purple-500 pl-2 mt-6">Siswa Berprestasi</div>
                        <div class="space-y-2">
                            <div v-for="(s, idx) in achievers" :key="'ac-'+idx" class="flex items-center gap-3 bg-slate-800/30 p-2 rounded-lg border border-white/5">
                                <div class="w-8 h-8 rounded-full bg-purple-500/20 flex items-center justify-center text-purple-300 text-xs font-bold border border-purple-500/20">
                                    {{ s.name.charAt(0) }}
                                </div>
                                <div>
                                    <div class="text-slate-200 text-xs font-bold">{{ s.name }}</div>
                                    <div class="text-purple-400 text-[10px]">{{ s.achievement }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
        </main>

        <!-- RUNNING TEXT FOOTER -->
        <footer class="h-12 bg-blue-900 flex items-center shrink-0 border-t border-blue-800 z-50">
            <div class="px-6 h-full flex items-center bg-blue-800 font-bold text-white uppercase text-sm tracking-wider shadow-lg z-20">
                <i class="fas fa-info-circle mr-2 text-lime-400"></i> Info Sekolah
            </div>
            <div class="flex-1 overflow-hidden relative h-full flex items-center">
                <div class="whitespace-nowrap animate-marquee text-white font-medium text-lg tracking-wide px-4">
                    {{ runningText }}
                </div>
            </div>
        </footer>

    </div>

    <script>
        const { createApp } = Vue;
        const app = createApp({
            data() {
                return {
                    currentTime: '',
                    currentDate: '',
                    settings: <?php echo json_encode($settings); ?>,
                    zone: 'aula', // Default zone
                    currentSlideIndex: 0,
                    showVideo: true,
                    teacherAgenda: [],
                    announcements: [],
                    achievers: [],
                    slides: [
                        'https://placehold.co/1920x1080/1e293b/cbd5e1?text=Sekolah+OS',
                        'https://placehold.co/1920x1080/0f172a/94a3b8?text=Welcome'
                    ],
                    slideInterval: null
                }
            },
            computed: {
                zoneLabel() { return this.zone.charAt(0).toUpperCase() + this.zone.slice(1); },
                // Header Settings (Global)
                headerTitle() { return this.settings.header?.title || this.settings[this.zone]?.header_title || 'SEKOLAH OS'; },
                runningText() { return this.settings.display?.running_text || this.settings[this.zone]?.running_text || ''; },
                fullLogoUrl() {
                    const logo = this.settings.header?.logo_url || this.settings[this.zone]?.logo_url;
                    if (!logo) return null;
                    return logo.startsWith('http') ? logo : window.BASE_URL + logo;
                },
                // Display Settings (Main)
                youtubeUrl() { return this.settings.display?.youtube_url || this.settings[this.zone]?.youtube_url || ''; },
                slideshowDurationMs() { return (parseInt(this.settings.display?.slideshow_duration_sec || this.settings[this.zone]?.slideshow_duration) || 10) * 1000; },
                videoDurationMs() { return (parseInt(this.settings.display?.video_duration_sec || this.settings[this.zone]?.video_duration) || 300) * 1000; },
                currentSlide() { return this.slides[this.currentSlideIndex] || ''; },
                
                youtubeEmbedUrl() {
                    const u = this.youtubeUrl || '';
                    if (!u) return '';
                    // Robust regex for YouTube ID
                    const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
                    const match = u.match(regExp);
                    const id = (match && match[2].length === 11) ? match[2] : null;
                    
                    if (!id && !u.includes('/embed/')) return '';
                    
                    const d = this.settings.display || {};
                    const z = this.settings[this.zone] || {};
                    
                    const mute = (d.youtube_mute !== undefined ? d.youtube_mute : (z.youtube_mute === false ? false : true)) ? 1 : 0;
                    // Loose check for true (handles boolean true, string "true", number 1, string "1")
                    const isTrue = (v) => v === true || v === 'true' || v === 1 || v === '1';
                    const cc = (isTrue(d.youtube_cc) || isTrue(z.youtube_cc)) ? 1 : 0;
                    
                    const params = [
                        'autoplay=1',
                        `mute=${mute}`,
                        'controls=1', // Show controls to verify CC
                        'playsinline=1',
                        'modestbranding=1',
                        'rel=0',
                        'showinfo=0',
                        'iv_load_policy=3',
                        'disablekb=0', // Enable keyboard
                        cc ? 'cc_load_policy=1' : '',
                        cc ? 'hl=id' : '' // Set interface language to ID but don't force caption language
                    ].filter(Boolean).join('&');
                    
                    return id ? `https://www.youtube.com/embed/${id}?${params}` : u;
                }
            },
            mounted() {
                // Get zone from URL
                const params = new URLSearchParams(window.location.search);
                const z = params.get('zone');
                if (z) this.zone = z;

                // Clock
                this.updateTime();
                setInterval(() => this.updateTime(), 1000);

                // Initial Data Load
                this.fetchDynamicData();
                setInterval(() => this.fetchDynamicData(), 60000); // Refresh every minute

                // Start Cycle
                this.startCycle();
            },
            methods: {
                updateTime() {
                    const now = new Date();
                    this.currentTime = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                    this.currentDate = now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
                },
                fetchDynamicData() {
                    // Pull data directly from settings.display (and header)
                    // We avoid hardcoded mocks so the display reflects exactly what is saved.
                    
                    const d = this.settings.display || {};
                    const h = this.settings.header || {};

                    // Mapping
                    this.teacherAgenda = Array.isArray(d.agenda_items) ? d.agenda_items : [];
                    this.announcements = Array.isArray(d.announcements) ? d.announcements : [];
                    this.achievers = Array.isArray(d.achievers) ? d.achievers : [];
                    
                    // Slideshow images
                    if (Array.isArray(d.slideshow_images) && d.slideshow_images.length > 0) {
                        this.slides = d.slideshow_images;
                    } else {
                        // Fallback only if absolutely no slides are set, to avoid broken image
                        // or just leave it empty and let the logic handle it (black screen?)
                        // Better to have a default placeholder if empty to prevent total darkness if video also fails
                         this.slides = [
                            'https://placehold.co/1920x1080/1e293b/cbd5e1?text=Sekolah+OS',
                            'https://placehold.co/1920x1080/0f172a/94a3b8?text=Welcome'
                        ];
                    }
                },
                startCycle() {
                    // Logic:
                    // 1. If video exists, play video for X seconds (or forever if no slides).
                    // 2. Then switch to slides for Y seconds each.
                    // 3. Repeat.
                    
                    const hasVideo = !!this.youtubeUrl;
                    
                    // If no video, just cycle slides
                    if (!hasVideo) {
                        this.showVideo = false;
                        this.startSlideCycle();
                        return;
                    }

                    const cycleLoop = () => {
                        this.showVideo = true;
                        console.log("Showing Video...");
                        
                        setTimeout(() => {
                            // Check if we actually have real slides to show
                            // If we only have placeholders and user wants video, maybe just keep video?
                            // For now, let's respect the cycle.
                            this.showVideo = false;
                            console.log("Showing Slides...");
                            
                            // Show slides for slideshowDurationMs
                            // If we have multiple slides, we might want to cycle through them
                            // Simple version: Show slides container for X duration, then back to video
                            
                            setTimeout(() => {
                                cycleLoop();
                            }, this.slideshowDurationMs);
                            
                        }, this.videoDurationMs);
                    };

                    cycleLoop();
                },
                startSlideCycle() {
                    setInterval(() => {
                        this.currentSlideIndex = (this.currentSlideIndex + 1) % this.slides.length;
                    }, 5000); // Change slide every 5s
                }
            }
        }).mount('#app');
    </script>
</body>
</html>
