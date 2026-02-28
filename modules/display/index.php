<?php
// modules/display/index.php
// Display TV Mode for Classrooms

// HARDCODED FIX FOR OPEN_BASEDIR
// 1. Try relative (works if CWD is correct)
if (file_exists('config/database.php')) {
    require_once 'config/database.php';
} 
// 2. Try absolute from DOCUMENT_ROOT (works if standard setup)
elseif (isset($_SERVER['DOCUMENT_ROOT']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/config/database.php')) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
}
// 3. Try absolute from SCRIPT_FILENAME directory (up 2 levels)
else {
    $scriptDir = dirname($_SERVER['SCRIPT_FILENAME']); // e.g., /www/wwwroot/AIStest/modules/display
    $rootDir = dirname(dirname($scriptDir)); // e.g., /www/wwwroot/AIStest
    if (file_exists($rootDir . '/config/database.php')) {
        require_once $rootDir . '/config/database.php';
    } else {
        die("Error: config/database.php not found. Checked: relative, doc_root, and calculated root ($rootDir).");
    }
}



// Get Base URL for assets
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
if (strpos($host, 'localhost') !== false || $host === '127.0.0.1') {
    if (preg_match('#^/(AIS|AIStest)/#i', $scriptName, $m)) {
        $baseUrl = '/' . $m[1] . '/';
    } else {
        $baseUrl = '/AIS/';
    }
} else {
    $baseUrl = '/';
}

$slug = $_GET['slug'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Display Kelas - <?php echo htmlspecialchars($slug); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/mermaid@10.8.0/dist/mermaid.min.js"></script>
    <script>mermaid.initialize({ startOnLoad: true, theme: 'dark' });</script>
    <script src="<?php echo $baseUrl; ?>assets/js/vue.global.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    </script>
    <link href="<?php echo $baseUrl; ?>assets/css/fontawesome.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        [v-cloak] { display: none; }
        .fade-enter-active, .fade-leave-active { transition: opacity 0.5s ease; }
        .fade-enter-from, .fade-leave-to { opacity: 0; }
        
        /* Progress Bar Animation */
        .progress-bar { transition: width 1s linear; }
        
        /* Ticker Animation */
        .ticker-wrap {
            width: 100%;
            overflow: hidden;
            white-space: nowrap;
            box-sizing: border-box;
        }
        .ticker {
            display: inline-block;
            padding-left: 100%;
            animation: ticker 30s linear infinite;
        }
        @keyframes ticker {
            0% { transform: translate3d(0, 0, 0); }
            100% { transform: translate3d(-100%, 0, 0); }
        }

        /* Glassmorphism */
        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .glass-dark {
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="bg-slate-900 text-white overflow-hidden">

<div id="app" v-cloak class="h-screen w-screen flex flex-col relative transition-colors duration-1000 bg-gradient-to-br from-slate-900 to-slate-800">

    <!-- URGENT OVERLAY -->
    <transition name="fade">
        <div v-if="urgentMessage" class="absolute inset-0 z-50 bg-red-600 flex flex-col items-center justify-center p-12 text-center animate-pulse">
            <div class="text-6xl font-black mb-8 uppercase tracking-widest text-white border-4 border-white px-8 py-4">PENGUMUMAN PENTING</div>
            <div class="text-5xl font-bold text-white leading-tight">{{ urgentMessage }}</div>
        </div>
    </transition>

    <!-- IMAGE OVERRIDE OVERLAY -->
    <transition name="fade">
        <div v-if="imageOverride && !urgentMessage" class="absolute inset-0 z-40 bg-slate-900/95 flex flex-col items-center justify-center p-8">
            <div class="relative w-full h-full max-w-7xl max-h-full flex items-center justify-center">
                 <img :src="baseUrl + imageOverride" class="max-w-full max-h-full object-contain drop-shadow-2xl rounded-xl border-2 border-white/20">
            </div>
        </div>
    </transition>

    <!-- PDF OVERRIDE OVERLAY -->
    <transition name="fade">
        <div v-if="pdfOverride && !urgentMessage" class="absolute inset-0 z-40 bg-slate-900 flex flex-col items-center justify-center overflow-hidden">
            <div class="relative w-full h-full flex items-center justify-center">
                 <div v-if="pdfLoading" class="absolute inset-0 flex items-center justify-center z-50 bg-slate-900/50 backdrop-blur-sm">
                     <div class="flex flex-col items-center">
                         <div class="w-16 h-16 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin mb-4"></div>
                         <div class="text-white font-bold text-xl">Memuat Dokumen...</div>
                     </div>
                 </div>
                 <div v-if="pdfError" class="absolute inset-0 flex items-center justify-center z-50 bg-slate-900/80">
                     <div class="bg-red-600/20 border border-red-500/50 p-8 rounded-2xl max-w-lg text-center backdrop-blur-md">
                         <i class="fas fa-exclamation-triangle text-5xl text-red-500 mb-4"></i>
                         <h3 class="text-2xl font-bold text-white mb-2">Gagal Memuat PDF</h3>
                         <p class="text-red-200">{{ pdfError }}</p>
                     </div>
                 </div>
                 <canvas ref="pdfCanvas" class="max-w-full max-h-full shadow-2xl"></canvas>
                 
                 <!-- Laser Pointer -->
                 <div v-if="pdfOverride.laser_active" 
                      class="absolute w-4 h-4 bg-red-600 rounded-full shadow-[0_0_15px_rgba(255,0,0,0.9)] z-50 transition-all duration-75 ease-linear pointer-events-none ring-2 ring-red-400"
                      :style="{ left: pdfOverride.laser_x + '%', top: pdfOverride.laser_y + '%' }">
                 </div>
            </div>
            <!-- Page Indicator -->
            <div class="absolute bottom-8 bg-black/60 text-white px-6 py-3 rounded-full backdrop-blur-md font-mono font-bold text-xl border border-white/10 shadow-xl">
                Page {{ pdfOverride.page }}
            </div>
        </div>
    </transition>

    <!-- AI OVERRIDE OVERLAY -->
    <transition name="fade">
        <div v-if="aiOverride && !urgentMessage" class="absolute inset-0 z-40 bg-slate-900/95 flex flex-col items-center justify-center p-12 overflow-y-auto">
            <div class="relative w-full max-w-7xl bg-white text-slate-900 p-12 rounded-3xl shadow-2xl border-4 border-indigo-500/50 min-h-[50vh]">
                <div class="prose prose-xl max-w-none w-full" v-html="aiOverride"></div>
            </div>
        </div>
    </transition>

    <!-- HEADER -->
    <header class="flex-none h-24 glass-dark flex items-center justify-between px-8 border-b border-white/10 z-10">
        <div class="flex items-center gap-6">
            <div class="w-16 h-16 bg-indigo-600 rounded-xl flex items-center justify-center text-3xl font-bold shadow-lg shadow-indigo-500/50">
                {{ className ? className.substring(0,2).toUpperCase() : '...' }}
            </div>
            <div>
                <h1 class="text-4xl font-black tracking-tight text-white">{{ className || 'Loading...' }}</h1>
                <div class="text-white/60 text-lg flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full animate-pulse" :class="isConnected ? 'bg-emerald-400' : 'bg-red-500'"></span>
                    {{ isConnected ? 'Online' : 'Offline' }}
                </div>
            </div>
        </div>
        <div class="text-right">
            <div class="text-5xl font-mono font-bold tracking-wider">{{ currentTime }}</div>
            <div class="text-xl text-white/80 font-medium">{{ currentDate }}</div>
        </div>
    </header>

    <!-- MAIN CONTENT -->
    <main class="flex-1 flex p-8 gap-8 relative overflow-hidden z-0">
        
        <!-- MODE: CLASS (A) -->
        <transition name="fade" mode="out-in">
            <div v-if="state === 'CLASS' && sessionData" key="class" class="w-full h-full flex gap-8">
                <!-- LEFT: SUBJECT & MATERIAL -->
                <div class="flex-1 flex flex-col justify-center pl-8">
                    <div class="mb-6 flex items-center gap-4">
                        <span class="bg-indigo-600 text-white px-6 py-2 rounded-full text-xl font-bold uppercase tracking-wider shadow-lg shadow-indigo-500/30">
                            <i class="fas fa-book-open mr-2"></i> Sedang Berlangsung
                        </span>
                        <span class="text-white/50 text-xl font-mono">{{ sessionData.start_time }} - {{ sessionData.end_time }}</span>
                    </div>
                    
                    <h2 class="text-[6rem] leading-none font-black mb-6 drop-shadow-2xl text-transparent bg-clip-text bg-gradient-to-r from-white to-slate-400">
                        {{ sessionData.subject }}
                    </h2>
                    
                    <div class="glass p-8 rounded-3xl border-l-8 border-indigo-500 mb-8 max-w-4xl">
                        <div class="text-indigo-300 text-lg font-bold uppercase tracking-widest mb-2">Materi Hari Ini</div>
                        <div class="text-4xl font-bold text-white mb-2 leading-tight">
                            {{ sessionData.topic }}
                        </div>
                        <div class="text-xl text-white/70" v-if="sessionData.subtopic">
                            {{ sessionData.subtopic }}
                        </div>
                    </div>
                    
                    <!-- Progress Bar -->
                    <div class="w-full max-w-4xl bg-white/10 rounded-full h-6 overflow-hidden mb-4 relative">
                        <div class="bg-indigo-500 h-full progress-bar shadow-[0_0_20px_rgba(99,102,241,0.6)]" :style="{ width: sessionData.progress + '%' }"></div>
                    </div>
                    <div class="flex justify-between text-xl font-mono font-bold text-white/50 max-w-4xl">
                        <span>Progress: {{ sessionData.progress }}%</span>
                        <span>Sisa: {{ sessionData.remaining_minutes }} Menit</span>
                    </div>
                </div>

                <!-- RIGHT: TEACHER PROFILE -->
                <div class="w-[400px] glass rounded-3xl p-8 flex flex-col items-center justify-center text-center shadow-2xl relative overflow-hidden group">
                    <div class="absolute inset-0 bg-gradient-to-b from-indigo-500/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-700"></div>
                    
                    <div class="w-64 h-64 rounded-full overflow-hidden border-4 border-indigo-400/50 mb-8 shadow-2xl bg-slate-800 relative z-10">
                        <img :src="getPhotoUrl(sessionData.photo)" class="w-full h-full object-cover" @error="handleImgError">
                    </div>
                    
                    <h3 class="text-3xl font-bold mb-2 relative z-10">{{ sessionData.teacher }}</h3>
                    <div class="bg-indigo-600/80 px-4 py-1 rounded-full text-sm font-bold uppercase tracking-wider relative z-10">
                        Guru Pengampu
                    </div>
                </div>
            </div>

            <!-- MODE: BREAK (B) -->
            <div v-else-if="state === 'BREAK' && sessionData" key="break" class="w-full h-full flex flex-col items-center justify-center text-center relative">
                <!-- Background Animation -->
                <div class="absolute inset-0 overflow-hidden pointer-events-none">
                    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] bg-emerald-500/20 rounded-full blur-[100px] animate-pulse"></div>
                </div>

                <div class="relative z-10 glass p-12 rounded-[3rem] border border-emerald-500/30 shadow-2xl backdrop-blur-xl">
                    <div class="text-3xl uppercase tracking-[0.5em] mb-8 text-emerald-300 font-bold">Istirahat</div>
                    
                    <div class="text-[10rem] font-mono font-black mb-8 leading-none text-white drop-shadow-[0_0_30px_rgba(16,185,129,0.5)]">
                        {{ countdown }}
                    </div>
                    
                    <div class="text-2xl text-white/60 mb-8">Menuju pelajaran berikutnya:</div>
                    
                    <div class="bg-white/10 p-6 rounded-2xl inline-block min-w-[400px]">
                        <div class="text-emerald-400 text-sm font-bold uppercase tracking-wider mb-2">Next Subject</div>
                        <div class="text-4xl font-bold">{{ sessionData.next_subject }}</div>
                        <div class="text-xl text-white/50 mt-2">Jam {{ sessionData.next_start }}</div>
                    </div>
                </div>
            </div>

            <!-- MODE: HOME (D) -->
            <div v-else key="home" class="w-full h-full flex flex-col items-center justify-center text-center relative">
                <div class="absolute inset-0 bg-[url('<?php echo $baseUrl; ?>assets/img/pattern.png')] opacity-5"></div>
                
                <div class="max-w-5xl glass-dark p-16 rounded-[3rem] border border-amber-500/30 shadow-2xl relative z-10">
                    <div class="mb-12">
                        <i class="fas fa-school text-8xl text-amber-500 mb-6"></i>
                        <h2 class="text-5xl font-bold text-white mb-2">Sampai Jumpa Besok!</h2>
                        <p class="text-xl text-white/50">Hati-hati di jalan dan tetap semangat belajar.</p>
                    </div>
                    
                    <div class="border-t border-white/10 pt-12">
                        <i class="fas fa-quote-left text-4xl text-amber-500/50 mb-6 block"></i>
                        <p class="text-4xl italic font-serif leading-relaxed mb-8 text-amber-100">
                            "{{ sessionData ? sessionData.quote : 'Loading...' }}"
                        </p>
                        <div class="text-xl font-bold text-amber-500 uppercase tracking-widest">
                            — {{ sessionData ? sessionData.source : '' }}
                        </div>
                    </div>
                </div>
            </div>
        </transition>

    </main>

    <!-- FOOTER TICKER (C - Transition/Running Text) -->
    <footer class="flex-none h-20 bg-black/60 backdrop-blur-xl flex items-center overflow-hidden border-t border-white/10 z-20">
        <div class="bg-indigo-600 text-white font-black px-8 h-full flex items-center z-10 shadow-lg text-xl tracking-widest">
            INFO
        </div>
        <div class="ticker-wrap flex-1 bg-black/40 h-full flex items-center">
            <div class="ticker text-2xl font-medium text-indigo-100">
                <span v-if="tickerMessage" class="inline-block mr-32">
                    <i class="fas fa-bullhorn mr-4 text-indigo-400"></i> {{ tickerMessage }}
                </span>
                <span v-else class="inline-block mr-32">
                    <i class="fas fa-info-circle mr-4 text-indigo-400"></i> Selamat Datang di Smart Classroom TV.
                </span>
            </div>
        </div>
    </footer>

    <div id="demo-controls" class="fixed bottom-4 right-4 z-[100] flex gap-2 p-2 rounded-xl bg-black/40 backdrop-blur-md border border-white/10 shadow-2xl transition-all duration-300 hover:bg-black/60">
        <button @click="setDemoState(null)" :class="!demoState ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/50' : 'bg-white/5 text-white/50 hover:bg-white/10'" class="px-3 py-2 rounded-lg text-xs font-bold uppercase tracking-wider transition-all duration-200">Auto</button>
        <button @click="setDemoState('CLASS')" :class="demoState === 'CLASS' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/50' : 'bg-white/5 text-white/50 hover:bg-white/10'" class="px-3 py-2 rounded-lg text-xs font-bold uppercase tracking-wider transition-all duration-200">Class</button>
        <button @click="setDemoState('BREAK')" :class="demoState === 'BREAK' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/50' : 'bg-white/5 text-white/50 hover:bg-white/10'" class="px-3 py-2 rounded-lg text-xs font-bold uppercase tracking-wider transition-all duration-200">Break</button>
        <button @click="setDemoState('HOME')" :class="demoState === 'HOME' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/50' : 'bg-white/5 text-white/50 hover:bg-white/10'" class="px-3 py-2 rounded-lg text-xs font-bold uppercase tracking-wider transition-all duration-200">Home</button>
        <button @click="toggleUrgentDemo()" :class="urgentMessage ? 'bg-red-600 text-white shadow-lg shadow-red-500/50' : 'bg-white/5 text-white/50 hover:bg-white/10'" class="px-3 py-2 rounded-lg text-xs font-bold uppercase tracking-wider transition-all duration-200">Urg</button>
    </div>

</div>

<script>
const { createApp, ref, computed, onMounted, onUnmounted, shallowRef, markRaw } = Vue;

createApp({
    setup() {
        const slug = "<?php echo $slug; ?>";
        const baseUrl = "<?php echo $baseUrl; ?>";
        
        const className = ref('');
        const state = ref('HOME'); // CLASS, BREAK, HOME
        const sessionData = ref(null);
        const urgentMessage = ref(null);
        const imageOverride = ref(null);
        const pdfOverride = ref(null);
        const aiOverride = ref(null);
        const pdfLoading = ref(false);
        const pdfError = ref(null);
        const pdfDoc = shallowRef(null); // Use shallowRef to avoid Proxy issues with PDF.js
        const pdfCanvas = ref(null);
        
        const tickerMessage = ref('');
        const isConnected = ref(true);
        
        const currentTime = ref('');
        const currentDate = ref('');
        const countdown = ref('00:00');
        
        // Data Fetcher
        const demoState = ref(null); // 'CLASS', 'BREAK', 'HOME', or null
        const showDemoControls = ref(false);

        // PDF Functions
        const loadPdf = async (url, pageNum) => {
            if (!url) return;
            pdfLoading.value = true;
            pdfError.value = null;
            try {
                const finalUrl = url.startsWith('http') ? url : baseUrl + url;
                const loadingTask = pdfjsLib.getDocument(finalUrl);
                const pdf = await loadingTask.promise;
                pdfDoc.value = markRaw(pdf);
                await renderPage(pageNum);
            } catch (err) {
                console.error('Error loading PDF:', err);
                pdfError.value = 'Gagal memuat PDF: ' + (err.message || 'Unknown error');
            } finally {
                pdfLoading.value = false;
            }
        };

        const renderPage = async (num) => {
            if (!pdfDoc.value) return;
            
            try {
                const page = await pdfDoc.value.getPage(num);
                
                // Wait for canvas to be mounted (next tick)
                await Vue.nextTick();
                
                const canvas = pdfCanvas.value;
                if (!canvas) return;

                const context = canvas.getContext('2d');
                
                // Fit to container logic
                const container = canvas.parentElement;
                const containerWidth = container ? container.clientWidth : window.innerWidth;
                const containerHeight = container ? container.clientHeight : window.innerHeight;
                
                const viewport = page.getViewport({ scale: 1.0 });
                const scaleX = containerWidth / viewport.width;
                const scaleY = containerHeight / viewport.height;
                const scale = Math.min(scaleX, scaleY) * 0.95; // 95% fit
                
                const scaledViewport = page.getViewport({ scale });

                canvas.height = scaledViewport.height;
                canvas.width = scaledViewport.width;

                const renderContext = {
                    canvasContext: context,
                    viewport: scaledViewport
                };
                
                await page.render(renderContext).promise;
            } catch (err) {
                console.error('Render error:', err);
            }
        };

        const fetchData = async () => {
            try {
                // Add timestamp to prevent caching
                let url = `${baseUrl}api/display_data.php?slug=${slug}&t=${Date.now()}`;
                if (demoState.value) {
                    url += `&demo_state=${demoState.value}`;
                }
                
                const res = await fetch(url);
                const data = await res.json();
                
                if (data.status === 'success') {
                    isConnected.value = true;
                    className.value = data.class_name;
                    state.value = data.state;
                    sessionData.value = data.data;
                    
                    // Messages
                    tickerMessage.value = data.messages.ticker;
                    urgentMessage.value = data.messages.urgent;
                    imageOverride.value = data.messages.image_override;
                    
                    if (data.messages.ai_override !== aiOverride.value) {
                         aiOverride.value = data.messages.ai_override;
                         // Init mermaid if AI content is present
                         if (aiOverride.value && aiOverride.value.includes('mermaid')) {
                             setTimeout(() => {
                                 mermaid.init(undefined, document.querySelectorAll('.mermaid'));
                             }, 500);
                         }
                    }
                    
                    // Handle PDF
                    if (data.pdf_override) {
                        const newUrl = data.pdf_override.url;
                        const newPage = parseInt(data.pdf_override.page);
                        const laserActive = parseInt(data.pdf_override.laser_active) === 1;
                        const laserX = parseFloat(data.pdf_override.laser_x);
                        const laserY = parseFloat(data.pdf_override.laser_y);
                        
                        if (!pdfOverride.value || pdfOverride.value.url !== newUrl) {
                            // New PDF
                            pdfOverride.value = { 
                                url: newUrl, 
                                page: newPage,
                                laser_active: laserActive,
                                laser_x: laserX,
                                laser_y: laserY
                            };
                            loadPdf(newUrl, newPage);
                        } else {
                            // Same PDF
                            if (pdfOverride.value.page !== newPage) {
                                pdfOverride.value.page = newPage;
                                renderPage(newPage);
                            }
                            // Update laser reactively
                            pdfOverride.value.laser_active = laserActive;
                            pdfOverride.value.laser_x = laserX;
                            pdfOverride.value.laser_y = laserY;
                        }
                    } else {
                        pdfOverride.value = null;
                        pdfDoc.value = null;
                    }
                    
                    // Handle Countdown logic for BREAK
                    if (state.value === 'BREAK' && data.data && data.data.countdown_to) {
                        // For demo, countdown_to might be relative to now, need to ensure logic works
                    }
                } else {
                    isConnected.value = false;
                }
            } catch (e) {
                console.error("Fetch Error", e);
                isConnected.value = false;
            }
        };
        
        // Countdown Logic
        const updateCountdown = () => {
            if (state.value !== 'BREAK' || !sessionData.value || !sessionData.value.countdown_to) return;
            
            const targetTimeStr = sessionData.value.countdown_to; // HH:mm:ss
            const now = new Date();
            const target = new Date();
            const [h, m, s] = targetTimeStr.split(':');
            target.setHours(h, m, s || 0);
            
            // If target is earlier than now (e.g. next day), add 24h? 
            // For simplicity, just show diff. If negative, it means break over?
            
            let diff = Math.floor((target - now) / 1000);
            if (diff < 0) {
                 // Maybe it's for tomorrow? or passed?
                 diff = 0;
            }
            
            const mm = Math.floor(diff / 60).toString().padStart(2, '0');
            const ss = (diff % 60).toString().padStart(2, '0');
            countdown.value = `${mm}:${ss}`;
        };

        // Clock
        setInterval(() => {
            const now = new Date();
            currentTime.value = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            currentDate.value = now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
            
            updateCountdown();
        }, 1000);

        // Auto Refresh
        setInterval(fetchData, 3000); // Fetch every 3 seconds

        onMounted(() => {
            fetchData();
        });
        
        const getPhotoUrl = (photo) => {
            if (!photo || photo === 'default.png') return baseUrl + 'assets/img/avatar.png'; // Fallback
            return photo.startsWith('http') ? photo : baseUrl + 'uploads/photos/' + photo;
        };
        
        const handleImgError = (e) => {
            e.target.src = baseUrl + 'assets/img/avatar.png';
        };

        const setDemoState = (s) => {
            demoState.value = s;
            fetchData(); // Immediate refresh
        };
        
        const toggleUrgentDemo = () => {
            if (urgentMessage.value) {
                urgentMessage.value = '';
            } else {
                urgentMessage.value = 'PERHATIAN: UJIAN SEDANG BERLANGSUNG HARAP TENANG';
            }
        };

        return {
            className,
            state,
            sessionData,
            urgentMessage,
            imageOverride,
            pdfOverride,
            aiOverride,
            pdfLoading,
            pdfError,
            pdfCanvas,
            tickerMessage,
            isConnected,
            currentTime,
            currentDate,
            countdown,
            getPhotoUrl,
            handleImgError,
            demoState,
            setDemoState,
            toggleUrgentDemo,
            showDemoControls,
            baseUrl
        };
    }
}).mount('#app');
</script>
</body>
</html>
