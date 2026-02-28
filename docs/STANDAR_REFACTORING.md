# Standar Refactoring & Optimasi Performa AIS

Dokumen ini menjelaskan standar arsitektur dan praktik terbaik (best practices) untuk pengembangan modul di SekolahOS (AIS). Standar ini disusun berdasarkan refactoring modul Principal untuk mencapai performa tinggi (Pro-Level) dan maintainability yang baik.

## 1. Arsitektur Hybrid (PHP Native + Vue 3)

Kita menggunakan pendekatan **Hybrid**:
*   **PHP (Server-Side)**: Menangani Routing, Auth, RBAC, Pre-fetching Data, dan Rendering Struktur Awal (HTML Shell).
*   **Vue 3 (Client-Side)**: Menangani interaktivitas, reaktivitas data, dan rendering dinamis di sisi klien.
*   **Tanpa Build Step**: Menggunakan ES Modules (`<script type="module">`) dan Vue Global Build untuk kemudahan deployment dan editing langsung di server.

## 2. Strategi Refactoring "The Mega-Script"

Untuk menghindari file monolitik yang sulit dirawat (ribuan baris kode), gunakan strategi **Component Splitting**:

### A. Pecah Logika JavaScript (ES Modules)
Jangan menulis ribuan baris logika Vue di dalam `index.php`. Pindahkan ke file terpisah.

*   **Lokasi**: `assets/js/modules/nama_modul_logic.js`
*   **Format**: Export objek `data`, `methods`, `computed`, dll.

**Contoh (`assets/js/modules/principal_logic.js`):**
```javascript
export const principalLogic = {
    data() {
        return { ... }
    },
    methods: {
        ...
    }
};
```

**Import di PHP (`index.php`):**
```html
<script type="module">
    import { principalLogic } from '<?php echo $baseUrl; ?>assets/js/modules/principal_logic.js?v=<?php echo time(); ?>';
    const { createApp } = Vue;
    createApp(principalLogic).mount('#app');
</script>
```

### B. Pecah Template (PHP Partials)
Pecah bagian HTML/Vue Template yang panjang menjadi file-file kecil berdasarkan fitur atau role.

*   **Lokasi**: `modules/nama_modul/views/`
*   **Format**: File PHP berisi fragmen HTML/Vue template.

**Contoh (`index.php`):**
```php
<!-- VIEW: WALI KELAS -->
<div v-if="currentPosition === 'wali'">
    <?php include 'views/wali.php'; ?>
</div>

<!-- VIEW: KEPALA SEKOLAH -->
<div v-if="currentPosition === 'kepala'">
    <?php include 'views/kepala.php'; ?>
</div>
```

## 3. Standar Optimasi Performa (Pro-Level)

Untuk menghilangkan "Lembah Kematian Visual" (blank page atau layout shift) saat loading:

### A. Server-Side Pre-fetching (PHP First)
Jangan biarkan Vue melakukan *blocking AJAX call* (seperti `fetchProfile` atau `fetchAgenda`) saat `mounted()`. Ambil data tersebut di PHP sebelum halaman dirender.

**Di PHP (`index.php`):**
```php
<?php
// Fetch data di server
$initialAgenda = $pdo->query("SELECT ...")->fetchAll();
?>
<script>
    // Suntikkan ke window object
    window.INITIAL_AGENDA = <?php echo json_encode($initialAgenda); ?>;
</script>
```

**Di Vue (`app.js` / logic):**
```javascript
mounted() {
    if (window.INITIAL_AGENDA) {
        this.agenda = window.INITIAL_AGENDA; // Instant render!
    } else {
        this.fetchAgenda(); // Fallback
    }
}
```

### B. Skeleton Loading & Pre-rendering
Render struktur dasar (sidebar, header, layout grid) menggunakan HTML/CSS statis atau Skeleton Loader agar user melihat konten instan sambil menunggu Vue siap.

**CSS Skeleton (`includes/header.php`):**
```css
.skeleton {
    background-color: #e2e8f0;
    position: relative;
    overflow: hidden;
}
.skeleton::after {
    animation: shimmer 2s infinite;
    /* ... gradient ... */
}
```

**Implementasi:**
Gunakan `v-if="false"` pada elemen skeleton agar otomatis hilang saat Vue mengambil alih, atau gunakan `v-cloak`.

```html
<!-- Skeleton (Tampil Awal) -->
<div v-if="false" class="skeleton h-32 w-full rounded"></div>

<!-- Vue Content (Tampil setelah mount) -->
<div v-cloak>
   <!-- Konten Asli -->
</div>
```

### C. Script Relocation (Footer)
Pindahkan script yang bersifat *blocking* (Vue, FontAwesome JS, Chart.js) ke bagian paling bawah `<body>`, bukan di `<head>`.

```html
    <!-- Konten HTML -->
    
    <!-- Script di Bawah -->
    <script src="vue.global.js"></script>
    <script src="app.js"></script>
</body>
```

## 4. Standar Database (Untuk Modul Keuangan & Kritis)

Untuk modul yang melibatkan integritas data (Keuangan, Inventory), **WAJIB** menggunakan Database Transactions.

```php
try {
    $pdo->beginTransaction();

    // Operasi 1: Simpan Transaksi
    $stmt1->execute();

    // Operasi 2: Update Saldo
    $stmt2->execute();

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    throw $e;
}
```

## 5. Ringkasan Checklist Refactoring

1.  [ ] **Split Logic**: Pindahkan kode JS > 500 baris ke file eksternal.
2.  [ ] **Split View**: Gunakan `include` untuk memecah template HTML.
3.  [ ] **PHP Pre-fetch**: Ambil data krusial di PHP, suntik ke `window`.
4.  [ ] **Skeleton**: Tambahkan loading state statis untuk elemen besar.
5.  [ ] **Script Footer**: Pindahkan library JS ke footer.
6.  [ ] **Cache Busting**: Tambahkan `?v=time()` pada import JS module.

---
*Dokumen ini dibuat otomatis oleh Trae AI sebagai standar pengembangan kedepan.*
