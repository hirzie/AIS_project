<?php
require_once '../../config/database.php';
if (session_status() === PHP_SESSION_NONE) {
    $sessionPath = __DIR__ . '/../../sessions';
    if (!file_exists($sessionPath)) {
        @mkdir($sessionPath, 0777, true);
    }
    if (is_writable($sessionPath)) {
        session_save_path($sessionPath);
    }
    session_start();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['logo'])) {
        if (!headers_sent()) { header('Content-Type: application/json'); }
        if ($_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
            $code = $_FILES['logo']['error'];
            $messages = [
                UPLOAD_ERR_INI_SIZE => 'File melebihi batas upload_max_filesize',
                UPLOAD_ERR_FORM_SIZE => 'File melebihi batas MAX_FILE_SIZE',
                UPLOAD_ERR_PARTIAL => 'File terupload sebagian',
                UPLOAD_ERR_NO_FILE => 'Tidak ada file yang dikirim',
                UPLOAD_ERR_NO_TMP_DIR => 'Folder tmp tidak tersedia',
                UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk',
                UPLOAD_ERR_EXTENSION => 'Upload diblokir oleh ekstensi PHP'
            ];
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $messages[$code] ?? ('Kode error: ' . $code)]);
            exit;
        }
        if (!is_uploaded_file($_FILES['logo']['tmp_name'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'File tidak valid untuk upload']);
            exit;
        }
        $origName = $_FILES['logo']['name'] ?? '';
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if (!$ext || !in_array($ext, ['png','jpg','jpeg'])) {
            if (function_exists('mime_content_type')) {
                $mime = @mime_content_type($_FILES['logo']['tmp_name']);
                if ($mime === 'image/png') $ext = 'png';
                elseif ($mime === 'image/jpeg') $ext = 'jpg';
            }
        }
        if (!$ext || !in_array($ext, ['png','jpg','jpeg'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Format file tidak didukung (hanya PNG/JPG)']);
            exit;
        }
        if ($ext === 'jpeg') $ext = 'jpg';
        $dir = realpath(__DIR__ . '/../../uploads/kiosk');
        if ($dir === false) {
            @mkdir(__DIR__ . '/../../uploads', 0777, true);
            @mkdir(__DIR__ . '/../../uploads/kiosk', 0777, true);
            $dir = realpath(__DIR__ . '/../../uploads/kiosk');
        }
        if ($dir === false) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Gagal membuat folder upload']);
            exit;
        }
        $name = 'logo_' . time() . '_' . mt_rand(1000,9999) . '.' . $ext;
        $dest = $dir . DIRECTORY_SEPARATOR . $name;
        if (!move_uploaded_file($_FILES['logo']['tmp_name'], $dest)) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan file']);
            exit;
        }
        echo json_encode(['status' => 'success', 'path' => 'uploads/kiosk/' . $name]);
        exit;
    }
    $data = json_decode(file_get_contents('php://input'), true);
    if ($data) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO kiosk_settings (zone, setting_key, setting_value) VALUES (:zone, :key, :value) ON DUPLICATE KEY UPDATE setting_value = :value");
            foreach ($data as $item) {
                $val = is_array($item['value']) ? json_encode($item['value']) : $item['value'];
                $stmt->execute([
                    ':zone' => $item['zone'],
                    ':key' => $item['key'],
                    ':value' => $val
                ]);
            }
            $pdo->commit();
            if (!headers_sent()) { header('Content-Type: application/json'); }
            echo json_encode(['status' => 'success', 'message' => 'Settings saved successfully']);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    } else {
        if (!headers_sent()) { header('Content-Type: application/json'); }
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Tidak ada data yang dikirim']);
        exit;
    }
}
$stmt = $pdo->query("SELECT * FROM kiosk_settings");
$dbSettings = $stmt->fetchAll(PDO::FETCH_ASSOC);
$settings = [];
foreach ($dbSettings as $row) {
    $decoded = json_decode($row['setting_value'], true);
    $value = (json_last_error() === JSON_ERROR_NONE) ? $decoded : $row['setting_value'];
    $settings[$row['zone']][$row['setting_key']] = $value;
}
require_once '../../includes/header.php';
?>
<div id="app" v-cloak class="flex h-screen bg-slate-50">
    <main class="flex-1 overflow-y-auto h-full flex flex-col w-full">
        <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-8 z-10 sticky top-0 shadow-sm">
            <div class="flex items-center gap-4">
                <a href="<?php echo $baseUrl; ?>index.php" class="w-10 h-10 bg-slate-100 text-slate-600 rounded-lg flex items-center justify-center hover:bg-slate-200 transition-colors">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div class="w-10 h-10 bg-blue-600 text-white rounded-lg flex items-center justify-center text-xl shadow-lg shadow-blue-200">
                    <i class="fas fa-tv"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-800">Kiosk Display Settings</h1>
                    <p class="text-xs text-slate-500 font-medium">Konfigurasi Tampilan Layar Informasi</p>
                </div>
            </div>
            <div class="flex gap-3">
                <a href="<?php echo $baseUrl; ?>modules/kiosk/display.php?zone=aula" target="_blank" class="px-4 py-2 bg-white border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 font-medium transition-colors">
                    <i class="fas fa-external-link-alt mr-2"></i> Preview Display Utama
                </a>
                <button @click="saveSettings" :disabled="saving" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-bold shadow-lg shadow-blue-200 transition-all flex items-center gap-2">
                    <i v-if="saving" class="fas fa-spinner fa-spin"></i>
                    <span v-else><i class="fas fa-save"></i></span>
                    {{ saving ? 'Menyimpan...' : 'Simpan Perubahan' }}
                </button>
            </div>
        </header>
        <div class="p-8 max-w-5xl mx-auto w-full">
            <div class="mb-6">
                <h2 class="text-lg font-bold text-slate-700">Pengaturan Display Utama</h2>
                <p class="text-xs text-slate-500">Semua pengaturan untuk layar lobby utama</p>
            </div>
            <div v-if="true" class="animate-fade">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Judul Situs</label>
                            <input type="text" v-model="settings.header.title" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all" placeholder="Contoh: AIS - Al Amanah Islamic School">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Subjudul Situs</label>
                            <input type="text" v-model="settings.header.subtitle" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all" placeholder="Contoh: Sekolah Pendidikan Indonesia">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Logo Al Amanah</label>
                            <div class="flex items-center gap-4">
                                <img v-if="settings.header.logo_url" :src="(baseUrl.endsWith('/') ? baseUrl : (baseUrl + '/')) + settings.header.logo_url" class="max-h-24 h-auto w-auto object-contain" />
                                <input type="file" ref="logoInput" accept="image/*" class="block">
                                <button @click="uploadLogo" class="px-3 py-2 bg-slate-800 text-white rounded-lg font-bold hover:bg-slate-900">Upload Logo</button>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">Format: PNG/JPG, akan disimpan di uploads/kiosk/</p>
                        </div>
                        <div v-if="false">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Judul Masjid</label>
                            <input type="text" v-model="settings.header.masjid_title" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all" placeholder="Contoh: Masjid Abu Bakar Ash-Shiddiq">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Nomor Telepon</label>
                            <input type="text" v-model="settings.header.phone" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all" placeholder="Contoh: 022-xxxxxxx">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Email</label>
                            <input type="email" v-model="settings.header.email" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all" placeholder="Contoh: admin@alamanahlembang.sch.id">
                        </div>
                    </div>
                    <p class="text-xs text-slate-500 italic">Pengaturan Header mempengaruhi tampilan atas untuk semua zona dan judul Masjid.</p>
                </div>
            </div>
            <div v-if="true" class="animate-fade">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 space-y-6">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Running Text (Info Bawah)</label>
                        <textarea v-model="settings.display.running_text" rows="3" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Info berjalan di bawah layar..."></textarea>
                    </div>
                </div>
            </div>
            <div v-if="true" class="animate-fade">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <h3 class="font-bold text-lg text-slate-700">YouTube Utama</h3>
                                <a href="<?php echo $baseUrl; ?>modules/kiosk/display.php?zone=aula" target="_blank" class="text-sm bg-slate-100 text-slate-600 px-3 py-1.5 rounded-lg font-bold hover:bg-slate-200 transition-colors border border-slate-300">
                                    <i class="fas fa-eye mr-1"></i> Preview
                                </a>
                            </div>
                            <input type="text" v-model="settings.display.youtube_url" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all" placeholder="https://www.youtube.com/watch?v=...">
                            <div class="mt-3 flex items-center gap-6">
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                    <input type="checkbox" v-model="settings.display.youtube_mute" class="rounded border-slate-300">
                                    Auto mute
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                    <input type="checkbox" v-model="settings.display.youtube_cc" class="rounded border-slate-300">
                                    Tampilkan CC
                                </label>
                            </div>
                            <div class="grid grid-cols-2 gap-4 mt-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 mb-1">Durasi Video (detik)</label>
                                    <input type="number" v-model.number="settings.display.video_duration_sec" min="5" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 mb-1">Durasi Slideshow (detik)</label>
                                    <input type="number" v-model.number="settings.display.slideshow_duration_sec" min="5" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                                </div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <h3 class="font-bold text-lg text-slate-700">Slideshow Gambar</h3>
                                <button @click="addSlideshowImage" class="text-sm bg-blue-100 text-blue-700 px-3 py-1.5 rounded-lg font-bold hover:bg-blue-200 transition-colors">
                                    <i class="fas fa-plus mr-1"></i> Tambah
                                </button>
                            </div>
                            <div class="space-y-3">
                                <div v-for="(img, idx) in settings.display.slideshow_images" :key="'img-'+idx" class="flex gap-2 items-center">
                                    <input type="text" v-model="settings.display.slideshow_images[idx]" class="flex-1 px-3 py-2 rounded-lg border border-slate-300" placeholder="https://...">
                                    <button @click="removeSlideshowImage(idx)" class="text-slate-400 hover:text-red-500"><i class="fas fa-trash"></i></button>
                                </div>
                                <div v-if="!settings.display.slideshow_images || settings.display.slideshow_images.length === 0" class="text-slate-400 text-sm">Belum ada gambar.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="true" class="animate-fade">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <h3 class="font-bold text-lg text-slate-700">Pengumuman</h3>
                                <button @click="addAnnouncement" class="text-sm bg-amber-100 text-amber-700 px-3 py-1.5 rounded-lg font-bold hover:bg-amber-200 transition-colors">
                                    <i class="fas fa-plus mr-1"></i> Tambah
                                </button>
                            </div>
                            <div class="space-y-4">
                                <div v-for="(ann, index) in settings.display.announcements" :key="'a-'+index" class="bg-slate-50 p-4 rounded-xl border border-slate-100 relative group">
                                    <button @click="removeAnnouncement(index)" class="absolute top-4 right-4 text-slate-400 hover:text-red-500 transition-colors">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <div class="mb-3 pr-8">
                                        <label class="text-xs text-slate-500 font-bold uppercase">Judul</label>
                                        <input type="text" v-model="ann.title" class="w-full px-3 py-2 rounded-lg border border-slate-300 font-bold text-slate-800" placeholder="Judul Pengumuman">
                                    </div>
                                    <div>
                                        <label class="text-xs text-slate-500 font-bold uppercase">Konten</label>
                                        <textarea v-model="ann.content" rows="2" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm" placeholder="Isi pengumuman..."></textarea>
                                    </div>
                                </div>
                                <div v-if="!settings.display.announcements || settings.display.announcements.length === 0" class="text-center py-8 text-slate-400 border-2 border-dashed border-slate-200 rounded-xl">
                                    Belum ada pengumuman.
                                </div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <h3 class="font-bold text-lg text-slate-700">Agenda Akademik</h3>
                                <button @click="addAgenda" class="text-sm bg-green-100 text-green-700 px-3 py-1.5 rounded-lg font-bold hover:bg-green-200 transition-colors">
                                    <i class="fas fa-plus mr-1"></i> Tambah Agenda
                                </button>
                            </div>
                            <div class="space-y-4">
                                <div v-for="(item, index) in settings.display.agenda_items" :key="'ag-'+index" class="flex gap-4 items-start bg-slate-50 p-4 rounded-xl border border-slate-100 group">
                                    <div class="w-32">
                                        <label class="text-xs text-slate-500 font-bold uppercase">Jam</label>
                                        <input type="time" v-model="item.time" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">
                                    </div>
                                    <div class="flex-1">
                                        <label class="text-xs text-slate-500 font-bold uppercase">Kegiatan</label>
                                        <input type="text" v-model="item.activity" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm mb-2" placeholder="Nama Kegiatan">
                                        <input type="text" v-model="item.location" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm" placeholder="Lokasi">
                                    </div>
                                    <button @click="removeAgenda(index)" class="mt-6 text-slate-400 hover:text-red-500 transition-colors">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                <div v-if="!settings.display.agenda_items || settings.display.agenda_items.length === 0" class="text-center py-8 text-slate-400 border-2 border-dashed border-slate-200 rounded-xl">
                                    Belum ada agenda.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
<script>
const { createApp } = Vue;
window.SERVER_SETTINGS = <?php echo json_encode($settings); ?>;
createApp({
    data() {
        const s = window.SERVER_SETTINGS || {};
        const toBool = (val, def) => {
            if (val === undefined || val === null) return def;
            return val === true || val === 'true' || val === 1 || val === '1';
        };
        return {
            baseUrl: window.BASE_URL || '/',
            saving: false,
            activeTab: 'main',
            settings: {
                header: {
                    title: '',
                    subtitle: '',
                    masjid_title: '',
                    phone: '',
                    email: '',
                    ...(s.header || {})
                },
                display: {
                    youtube_url: (s.display?.youtube_url) || (s.aula?.youtube_url) || '',
                    video_duration_sec: parseInt(s.display?.video_duration_sec || 30, 10),
                    slideshow_duration_sec: parseInt(s.display?.slideshow_duration_sec || 10, 10),
                    slideshow_images: Array.isArray(s.display?.slideshow_images) ? s.display.slideshow_images : [],
                    youtube_mute: toBool(s.display?.youtube_mute, true),
                    youtube_cc: toBool(s.display?.youtube_cc, false),
                    announcements: Array.isArray(s.display?.announcements) ? s.display.announcements : (Array.isArray(s.aula?.announcements) ? s.aula.announcements : []),
                    achievers: Array.isArray(s.display?.achievers) ? s.display.achievers : (Array.isArray(s.aula?.achievers) ? s.aula.achievers : []),
                    running_text: (s.display?.running_text) || (s.aula?.running_text) || '',
                    agenda_items: Array.isArray(s.display?.agenda_items) ? s.display.agenda_items : []
                }
            }
        }
    },
    mounted() {
        if (!this.settings) this.settings = {};
        if (!this.settings.header) this.settings.header = { title: '', subtitle: '', masjid_title: '', phone: '', email: '' };
        if (!this.settings.display) this.settings.display = { youtube_url:'', video_duration_sec:30, slideshow_duration_sec:10, slideshow_images:[], announcements:[], achievers:[], running_text:'', agenda_items:[] };
        if (!Array.isArray(this.settings.display.slideshow_images)) this.settings.display.slideshow_images = [];
        if (!Array.isArray(this.settings.display.announcements)) this.settings.display.announcements = [];
        if (!Array.isArray(this.settings.display.achievers)) this.settings.display.achievers = [];
        if (!Array.isArray(this.settings.display.agenda_items)) this.settings.display.agenda_items = [];
        if (typeof this.settings.display.running_text !== 'string') this.settings.display.running_text = '';
    },
    methods: {
        normalizeBaseUrl() {
            if (this.baseUrl === '/' || !this.baseUrl) {
                const m = (window.location.pathname || '').match(/^\/(AIS|AIStest)\//i);
                this.baseUrl = m ? `/${m[1]}/` : '/';
            }
        },
        addAgenda() {
            if (!this.settings.display.agenda_items) this.settings.display.agenda_items = [];
            this.settings.display.agenda_items.push({ time: '', activity: '', location: '' });
        },
        removeAgenda(index) {
            this.settings.display.agenda_items.splice(index, 1);
        },
        addSlideshowImage() {
            if (!this.settings.display.slideshow_images) this.settings.display.slideshow_images = [];
            this.settings.display.slideshow_images.push('');
        },
        removeSlideshowImage(index) {
            this.settings.display.slideshow_images.splice(index, 1);
        },
        addAnnouncement() {
            if (!this.settings.display.announcements) this.settings.display.announcements = [];
            this.settings.display.announcements.push({ title: '', content: '' });
        },
        removeAnnouncement(index) {
            this.settings.display.announcements.splice(index, 1);
        },
        addAchiever() {
            if (!this.settings.display.achievers) this.settings.display.achievers = [];
            this.settings.display.achievers.push({ name: '', class: '', achievement: '' });
        },
        removeAchiever(index) {
            this.settings.display.achievers.splice(index, 1);
        },
        async uploadLogo() {
            const input = this.$refs.logoInput;
            if (!input || !input.files || !input.files[0]) { alert('Pilih file logo terlebih dahulu'); return; }
            const fd = new FormData();
            fd.append('logo', input.files[0]);
            try {
                const res = await fetch(window.location.href, { method: 'POST', body: fd });
                const json = await res.json();
                if (json.status === 'success' && json.path) {
                    this.settings.header.logo_url = json.path;
                } else {
                    alert(json.message || 'Upload gagal');
                }
            } catch (e) {
                alert('Upload gagal');
            }
        },
        async saveSettings() {
            this.saving = true;
            try {
                this.normalizeBaseUrl();
                const payload = [];
                for (const zone of ['header', 'display']) {
                    const zoneSettings = this.settings[zone] || {};
                    for (const key in zoneSettings) {
                        payload.push({ zone, key, value: zoneSettings[key] });
                    }
                }
                const res = await fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const json = await res.json();
                alert(json.message || 'Tersimpan');
            } catch (e) {
                alert('Gagal menyimpan');
            } finally {
                this.saving = false;
            }
        }
    }
}).mount('#app');
</script>
