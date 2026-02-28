# Dokumentasi Integrasi WhatsApp API (AIS - CRM)

Dokumen ini menjelaskan spesifikasi teknis dan cara integrasi antara **AIS Portal** (Backend PHP) dan **CRM WhatsApp Gateway** (Node.js).

---

## 1. Overview
Integrasi ini memungkinkan AIS Portal untuk mengirimkan notifikasi WhatsApp secara otomatis (misalnya notifikasi keamanan, presensi, dll) menggunakan gateway WhatsApp yang berjalan di CRM.

- **Frontend AIS**: Mengirim request trigger ke Backend AIS.
- **Backend AIS**: Memproses data, memformat nomor HP, dan meneruskan request ke API CRM.
- **CRM Gateway**: Menerima request dan mengirim pesan WhatsApp via library `whatsapp-web.js`.

---

## 2. Konfigurasi di AIS Portal
Admin mengisi pengaturan di **Pengaturan Sekolah → Integrasi WhatsApp**:

1. **WA API URL**  
   Alamat endpoint gateway WhatsApp Anda. Contoh:  
   - Local: `http://localhost:3004/api/whatsapp/send`  
   - Hosting: `https://crm.creativedivisions.my.id/api/whatsapp/send`
2. **WA API Token**  
   Nilai token yang dikirim sebagai header `Authorization: Bearer <token>`.  
   Jika gateway Anda memakai `clientId` di payload (tanpa header Authorization), gunakan nilai yang sama, dan lihat Mode B di bawah.
3. **Target WA (Grup/Nomor)**  
   Nomor/grup default untuk notifikasi Security. Bisa diisi nomor Indonesia (`08...` atau `62...`) atau ID grup sesuai gateway.

---

## 3. Spesifikasi API Gateway
Gateway WhatsApp dapat diintegrasikan dengan dua mode payload:

### Mode A (Default AIS)
- **Header**:  
  - `Content-Type: application/json`  
  - `Authorization: Bearer <wa_api_token>`
- **Body**:  
  ```json
  {
    "target": "6281234567890@c.us",
    "message": "Halo, ini pesan uji coba dari AIS."
  }
  ```
- Keterangan:
  - AIS akan mengirim `target` dan `message`. Gateway mengekstraksi token dari header.
  - Jika `target` berupa `08...`, AIS menyarankan normalisasi ke `62...` dan penambahan `@c.us` di sisi gateway.

### Mode B (Alternatif – beberapa CRM)
- **Header**:  
  - `Content-Type: application/json`
- **Body**:  
  ```json
  {
    "to": "6281234567890@c.us",
    "message": "Halo, ini pesan uji coba dari AIS.",
    "clientId": "ais"
  }
  ```
- Keterangan:
  - Tidak ada header Authorization. Gateway menggunakan `clientId` untuk memilih session WA.
  - Jika gateway Anda menggunakan Mode B, pastikan endpoint menerima format ini atau siapkan adapter yang memetakan `target`+Bearer ke `to`+`clientId`.

### Response (disarankan)
- Sukses:
  ```json
  { "success": true, "data": { "id": "msg-uuid", "timestamp": 1730000000 } }
  ```
- Gagal:
  ```json
  { "success": false, "error": "Client not ready" }
  ```

---

## 4. Implementasi Code

### Backend PHP (AIS/api/security.php)
Fungsi `sendWhatsAppMessage` mengirim permintaan ke gateway sesuai Mode A (default AIS):

```php
function sendWhatsAppMessage($pdo, $text, $targetOverride = null) {
    $url = trim((string)getSetting($pdo, 'wa_api_url'));
    $token = trim((string)getSetting($pdo, 'wa_api_token'));
    $target = $targetOverride ?: trim((string)getSetting($pdo, 'wa_security_target'));
    if ($url === '' || $token === '' || $target === '' || $text === '') return false;
    $payload = json_encode(['target' => $target, 'message' => $text], JSON_UNESCAPED_UNICODE);
    $headers = "Content-Type: application/json\r\nAuthorization: Bearer " . $token . "\r\n";
    $context = stream_context_create([
        'http' => ['method' => 'POST','header' => $headers,'content' => $payload,'timeout' => 10],
        'ssl' => ['verify_peer' => false,'verify_peer_name' => false]
    ]);
    try { $resp = @file_get_contents($url, false, $context); return $resp !== false; } catch (\Throwable $e) { return false; }
}
```

### Frontend JavaScript (`AIS/assets/js/modules/admin.js`)
Mixin Vue.js untuk memicu pengiriman pesan test.

```javascript
async testWaNotification() {
    try {
        // baseUrl dinamis (AIS/AIStest)
        const res = await fetch(baseUrl + 'api/security.php?action=send_wa_test', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                message: this.waTestMessage, 
                target: this.schoolSettings.wa_security_target 
            })
        });
        // ... handle response ...
    } catch (e) {
        console.error(e);
    }
}
```

---

## 5. Troubleshooting

### 1. Error: `SyntaxError: Unexpected token < in JSON`
* **Penyebab**: Gateway mengembalikan HTML (bukan JSON) atau terjadi error PHP.
* **Solusi**: Pastikan gateway merespon JSON. Pada AIS, endpoint selalu mengemas respons JSON.

### 2. Error: `Failed to send message`
* **Penyebab**: Client WhatsApp di CRM belum siap atau belum scan QR.
* **Solusi**: Cek dashboard CRM, pastikan status session (token/clientId) **Online**.

### 3. Pesan tidak masuk
* **Penyebab**: Format nomor salah atau Token tidak cocok.
* **Solusi**: Pastikan nomor diawali `62` (bukan `08`). Mode A: verifikasi Bearer token; Mode B: samakan `clientId`.

---

## 6. Contoh cURL

### Mode A (Default AIS)
```bash
curl -X POST "https://gateway.example.com/api/whatsapp/send" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{ "target": "6281234567890@c.us", "message": "Uji kirim dari AIS" }'
```

### Mode B (Alternatif)
```bash
curl -X POST "https://gateway.example.com/api/whatsapp/send" \
  -H "Content-Type: application/json" \
  -d '{ "to": "6281234567890@c.us", "message": "Uji kirim dari AIS", "clientId": "ais" }'
```

---
*Dokumen dibuat otomatis oleh AI Assistant untuk keperluan pengembangan.*
